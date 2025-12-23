# 📐 RESUMEN TÉCNICO: Implementación de Pedidos Combinados (PL)

## 🎯 OBJETIVO

Crear **2 pedidos independientes** cuando se acepta una **cotización combinada (PL)**:
1. **Pedido de PRENDAS** → tabla `pedidos_produccion`
2. **Pedido de LOGO** → tabla `logo_pedidos`

Vinculados automáticamente mediante `pedido_id` en `logo_pedidos`.

---

## 🗂️ ARCHIVOS MODIFICADOS

### 1. Base de Datos

**Archivo:** `database/migrations/2025_12_23_add_cantidad_to_logo_pedidos_table.php`

```php
// Agrega columna cantidad a logo_pedidos
Schema::table('logo_pedidos', function (Blueprint $table) {
    $table->integer('cantidad')->default(0)->after('descripcion');
});
```

**Estado:** ✅ EJECUTADA
**Verificar:**
```sql
DESCRIBE logo_pedidos;
-- Debe mostrar: cantidad INT(11) DEFAULT 0
```

---

### 2. Backend - Modelo

**Archivo:** `app/Models/LogoPedido.php`

```php
protected $fillable = [
    // ... otros campos ...
    'cantidad',  // ← AGREGADO
    // ... otros campos ...
];
```

**Cambio:** Agregar `'cantidad'` al array `$fillable`

---

### 3. Backend - Controlador (Parte 1)

**Archivo:** `app/Http/Controllers/Asesores/PedidosProduccionController.php`

#### Método: `crearDesdeCotizacion($cotizacionId)`

**Líneas:** 550-630 (aproximadamente)

**Responsabilidad:** Crear pedido de PRENDAS en `pedidos_produccion`

**Cambios realizados:**

```php
// 1️⃣ OBTENER TIPO DE COTIZACIÓN
$tipoCotizacionCodigo = $cotizacion->tipo_cotizacion_codigo; // P, L, PL, RF

// 2️⃣ CREAR SOLO en pedidos_produccion (nunca crear en logo_pedidos aquí)
$pedido = DB::table('pedidos_produccion')->insertGetId([
    'cotizacion_id' => $cotizacionId,
    'numero_pedido' => $numeroPedido,
    'cliente' => $cliente,
    'asesor_id' => Auth::id(),
    'forma_de_pago' => $request->input('forma_de_pago'),
    // ... otros campos ...
]);

// 3️⃣ SI ES COMBINADA (PL), INDICAR AL FRONTEND
if ($tipoCotizacionCodigo === 'PL') {
    return response()->json([
        'success' => true,
        'pedido_id' => $pedido,
        'es_combinada' => true  // ← SEÑAL para frontend
    ]);
}
```

**Key Point:** NO crear `logo_pedido` en este método para tipo PL.

---

### 4. Backend - Controlador (Parte 2)

**Archivo:** `app/Http/Controllers/Asesores/PedidosProduccionController.php`

#### Método: `guardarLogoPedido(Request $request)`

**Líneas:** 743-912 (aproximadamente)

**Responsabilidad:** Crear O actualizar registro de LOGO en `logo_pedidos`

**Cambios realizados:**

```php
public function guardarLogoPedido(Request $request): JsonResponse
{
    // 1️⃣ EXTRAER DATOS DEL REQUEST
    $pedidoId = $request->input('pedido_id');           // ID del pedidos_produccion
    $logoCotizacionId = $request->input('logo_cotizacion_id');
    $cantidad = $request->input('cantidad', 0);         // Suma de tallas
    $cotizacionId = $request->input('cotizacion_id');   // NUEVO: Para vincular
    
    // 2️⃣ VERIFICAR: ¿Existe ya logo_pedido con este ID?
    $logoPedidoExistente = DB::table('logo_pedidos')->find($pedidoId);
    
    if (!$logoPedidoExistente) {
        // ===== CASO COMBINADA (PL) =====
        // El logo_pedido NO EXISTE porque:
        // - crearDesdeCotizacion() solo creó en pedidos_produccion
        // - Ahora es el momento de crear el registro en logo_pedidos
        
        $numeroLogoPedido = $this->generarNumeroLogoPedido();
        
        $pedidoId = DB::table('logo_pedidos')->insertGetId([
            'pedido_id' => $pedidoId,                   // ← FK a pedidos_produccion
            'logo_cotizacion_id' => $logoCotizacionId,
            'numero_pedido' => $numeroLogoPedido,
            'cotizacion_id' => $cotizacionId,           // ← NUEVO
            'numero_cotizacion' => $numeroCotizacion,
            'cliente' => $cliente,
            'asesora' => Auth::user()->name,
            'forma_de_pago' => $formaPago,              // ← NUEVO
            'descripcion' => $request->input('descripcion'),
            'cantidad' => $cantidad,                    // ← NUEVO: Suma de tallas
            'tecnicas' => json_encode($request->input('tecnicas', [])),
            'ubicaciones' => json_encode($request->input('ubicaciones', [])),
            'estado' => 'pendiente',
            'fecha_de_creacion_de_orden' => now(),
            // ... otros campos ...
        ]);
        
        // Crear proceso inicial
        \App\Models\ProcesosPedidosLogo::crearProcesoInicial($pedidoId, Auth::id());
        
    } else {
        // ===== CASO LOGO SOLO (L) =====
        // El logo_pedido EXISTE porque:
        // - crearLogoPedidoDesdeAnullCotizacion() lo creó automáticamente
        // - Ahora solo necesitamos ACTUALIZAR los datos del formulario
        
        DB::table('logo_pedidos')
            ->where('id', $pedidoId)
            ->update([
                'descripcion' => $request->input('descripcion'),
                'cantidad' => $cantidad,
                'tecnicas' => json_encode($request->input('tecnicas', [])),
                'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                'updated_at' => now(),
            ]);
    }
    
    // 3️⃣ RESPONDER AL FRONTEND CON AMBOS NÚMEROS
    $logoPedido = DB::table('logo_pedidos')->find($pedidoId);
    $pedidoPrendas = DB::table('pedidos_produccion')
        ->where('id', $logoPedido->pedido_id)
        ->select('id', 'numero_pedido')
        ->first();
    
    return response()->json([
        'success' => true,
        'numero_pedido_produccion' => $pedidoPrendas?->numero_pedido,  // PED-xxxxx
        'numero_pedido_logo' => $logoPedido->numero_pedido              // LOGO-xxxxx
    ]);
}
```

**Key Points:**
- Detecta si el logo_pedido ya existe
- Si NO existe (COMBINADA): CREA uno nuevo
- Si EXISTE (LOGO SOLO): ACTUALIZA el existente
- Devuelve ambos números para COMBINADA

---

### 5. Frontend - JavaScript

**Archivo:** `public/js/crear-pedido-editable.js`

**Líneas:** 3050-3180 (aproximadamente)

#### Cambio 1: Detectar tipo de cotización

```javascript
// Línea ~3078
const esCombinada = dataCrearPedido.es_combinada || dataCrearPedido.tipo_cotizacion === 'PL';
const esLogoSolo = tipoCotizacion === 'L';
```

**Propósito:** Distinguir entre COMBINADA (PL) y LOGO SOLO (L)

#### Cambio 2: Preparar datos para guardarLogoPedido

```javascript
// Línea ~3111
const bodyLogoPedido = {
    pedido_id: pedidoId,                    // ← ID de pedidos_produccion (para COMBINADA)
    logo_cotizacion_id: logoCotizacionIdAUsar,
    cotizacion_id: cotizacionId,            // ✅ NUEVO: Vinculación a cotización
    forma_de_pago: formaPagoInput.value,    // ✅ NUEVO: Guardar forma de pago
    descripcion: descripcionLogoPedido,
    cantidad: cantidadTotal,                // ✅ NUEVO: Suma de tallas
    tecnicas: logoTecnicasSeleccionadas,
    ubicaciones: logoSeccionesSeleccionadas,
    fotos: logoFotosSeleccionadas
};
```

#### Cambio 3: Mostrar respuesta correcta para COMBINADA

```javascript
// Línea ~3152
if (esCombinada) {
    // Para COMBINADA (PL), mostrar AMBOS números
    const numeroPrendas = data.numero_pedido_produccion;
    const numeroLogo = data.numero_pedido_logo;
    
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        html: '<p style="font-size: 16px; line-height: 1.8;">' +
              'Pedidos creados exitosamente<br><br>' +
              '<strong>📦 Pedido Producción:</strong> ' + numeroPrendas + '<br>' +
              '<strong>🎨 Pedido Logo:</strong> ' + numeroLogo +
              '</p>',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = '/asesores/pedidos';
    });
}
```

---

## 🔄 FLUJO DETALLADO

```
┌─────────────────────────────────────────────────────────────────┐
│ USUARIO: Aceptar cotización COMBINADA (tipo_cotizacion_codigo = 'PL')
└────────────────────┬────────────────────────────────────────────┘
                     ↓
         📱 FRONTEND: crear-pedido-editable.js
         ────────────────────────────────────────
         
         1️⃣ Detecta: esCombinada = true
         2️⃣ Captura datos de 2 TABS:
            - Tab PRENDAS: Artículos, tallas, cantidades
            - Tab LOGO: Descripción, ubicaciones, técnicas
         3️⃣ Calcula: cantidadTotal = suma de tallas
            Ej: 30 (S) + 50 (M) + 20 (L) = 100
         4️⃣ POST /asesores/pedidos-produccion/crear-desde-cotizacion
            Body: {
               cotizacion_id: 123,
               forma_de_pago: "CONTADO",
               prendas: [{...}, {...}]  // Solo datos de PRENDAS
            }
         ↓
        🔗 API: PedidosProduccionController::crearDesdeCotizacion()
        ────────────────────────────────────────────────────────────
        
        1️⃣ BEGIN TRANSACTION
        2️⃣ Obtener tipo_cotizacion_codigo = 'PL'
        3️⃣ Crear SOLO en pedidos_produccion (con prendas):
           INSERT pedidos_produccion {
               id: 45,
               numero_pedido: 'PED-00045',
               cotizacion_id: 123,
               cliente: 'Client XYZ',
               forma_de_pago: 'CONTADO',
               ...
           }
           
           INSERT prendas_pedido {
               pedido_id: 45,
               codigo: 'CAMISA',
               ...
           }
        4️⃣ NO crear en logo_pedidos (¡IMPORTANTE!)
        5️⃣ COMMIT TRANSACTION
        6️⃣ Response: {
               success: true,
               pedido_id: 45,
               es_combinada: true        // ← SEÑAL PARA FRONTEND
           }
         ↓
        📱 FRONTEND: Recibe respuesta
        ────────────────────────────
        
        1️⃣ Detecta: data.es_combinada = true
        2️⃣ Extrae: pedidoId = 45
        3️⃣ POST /asesores/pedidos/guardar-logo-pedido
           Body: {
               pedido_id: 45,                    // ← REF a pedidos_produccion
               logo_cotizacion_id: 12,
               cotizacion_id: 123,
               forma_de_pago: 'CONTADO',
               descripcion: 'Logo bordado',
               cantidad: 100,                    // ← SUMA de tallas
               tecnicas: ['BORDADO'],
               ubicaciones: ['Pecho'],
               fotos: [...]
           }
         ↓
        🔗 API: PedidosProduccionController::guardarLogoPedido()
        ─────────────────────────────────────────────────────────
        
        1️⃣ BEGIN TRANSACTION
        2️⃣ Verificar: ¿Existe logo_pedido con id=45?
           SELECT * FROM logo_pedidos WHERE id = 45
           Resultado: NO EXISTE (porque lo creó crearDesdeCotizacion())
        3️⃣ Como NO existe, CREAR NUEVO en logo_pedidos:
           INSERT logo_pedidos {
               id: 6,
               pedido_id: 45,                  // ← FK a pedidos_produccion
               numero_pedido: 'LOGO-00006',
               logo_cotizacion_id: 12,
               cotizacion_id: 123,
               descripcion: 'Logo bordado',
               cantidad: 100,
               tecnicas: '["BORDADO"]',
               ubicaciones: '["Pecho"]',
               forma_de_pago: 'CONTADO',
               estado: 'pendiente',
               fecha_de_creacion_de_orden: NOW(),
               ...
           }
           
           // Crear proceso inicial
           INSERT procesos_pedidos_logo {...}
        4️⃣ COMMIT TRANSACTION
        5️⃣ Obtener números de ambos pedidos
        6️⃣ Response: {
               success: true,
               numero_pedido_produccion: 'PED-00045',
               numero_pedido_logo: 'LOGO-00006'
           }
         ↓
        📱 FRONTEND: Muestra éxito
        ─────────────────────────
        
        SweetAlert2 {
            icon: 'success',
            html: '📦 Pedido Producción: PED-00045<br>🎨 Pedido Logo: LOGO-00006'
        }
         ↓
        Redirige a: /asesores/pedidos
        
         ✅ FIN EXITOSO
```

---

## 📊 COMPARACIÓN DE CASOS

### CASO 1: LOGO SOLO (tipo_cotizacion_codigo = 'L')

```
Flujo:
┌─────────────────────────────────────────────────┐
│ POST /crear-desde-cotizacion (tipo='L')         │
├─────────────────────────────────────────────────┤
│ crearLogoPedidoDesdeAnullCotizacion()           │
│  → INSERT en logo_pedidos (con pedido_id = NULL)│
│  → genera numero_pedido (LOGO-xxxxx)            │
└─────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────┐
│ POST /guardar-logo-pedido                       │
├─────────────────────────────────────────────────┤
│ guardarLogoPedido()                             │
│  → Encuentra logo_pedido (existe)               │
│  → UPDATE registro con datos del formulario     │
└─────────────────────────────────────────────────┘

Resultado en BD:
  pedidos_produccion: VACÍO (no se crea)
  logo_pedidos: 1 registro (pedido_id = NULL)
```

### CASO 2: COMBINADA (tipo_cotizacion_codigo = 'PL')

```
Flujo:
┌─────────────────────────────────────────────────┐
│ POST /crear-desde-cotizacion (tipo='PL')        │
├─────────────────────────────────────────────────┤
│ crearDesdeCotizacion()                          │
│  → INSERT en pedidos_produccion (prendas)       │
│  → NO crear en logo_pedidos                     │
│  → Devuelve: es_combinada = true                │
└─────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────┐
│ POST /guardar-logo-pedido                       │
├─────────────────────────────────────────────────┤
│ guardarLogoPedido()                             │
│  → NO encuentra logo_pedido (no existe)         │
│  → INSERT nuevo en logo_pedidos                 │
│  → Con pedido_id = ID de pedidos_produccion     │
└─────────────────────────────────────────────────┘

Resultado en BD:
  pedidos_produccion: 1 registro (prendas)
  logo_pedidos: 1 registro (pedido_id = PED_ID)
  
Vinculación:
  logo_pedidos.pedido_id → pedidos_produccion.id
```

---

## 🔑 PUNTOS CLAVE

| Aspecto | Antes ❌ | Ahora ✅ |
|--------|----------|----------|
| **Entrada** | POST /crear-desde-cotizacion (PL) | Mismo |
| **Crea en** | Ambas tablas (INCORRECTO) | Solo pedidos_produccion |
| **Señal** | (ninguna) | Devuelve `es_combinada: true` |
| **Segunda llamada** | /guardar-logo-pedido | Mismo |
| **Verifica existencia** | No | Sí: `if (!$logoPedidoExistente)` |
| **Si no existe** | (intenta actualizar) | CREA nuevo registro |
| **Si existe** | Actualiza | ACTUALIZA registro |
| **Vinculación** | (ninguna o incorrecta) | `logo_pedidos.pedido_id` = pedidos_produccion.id |
| **Cantidad** | No se guarda | Se calcula y guarda suma de tallas |
| **Resultado** | 2 en pedidos_produccion, 1 en logo_pedidos | 1 en cada tabla ✅ |

---

## ✅ VALIDACIÓN RÁPIDA

```sql
-- Verificar COMBINADA correcta (PL)
SELECT 
    pp.numero_pedido as prendas,
    lp.numero_pedido as logo,
    lp.pedido_id,
    lp.cantidad,
    pp.id
FROM pedidos_produccion pp
JOIN logo_pedidos lp ON lp.pedido_id = pp.id
WHERE pp.cotizacion_id = 123;

-- Resultado esperado:
┌──────────┬────────┬──────────┬──────────┬────┐
│ prendas  │ logo   │ pedido_id│ cantidad │ id │
├──────────┼────────┼──────────┼──────────┼────┤
│PED-00045 │LOGO-06 │    45    │   100    │ 45 │
└──────────┴────────┴──────────┴──────────┴────┘

-- Verificar que NO hay duplicados
SELECT numero_pedido, COUNT(*)
FROM pedidos_produccion
WHERE cotizacion_id = 123
GROUP BY numero_pedido;

-- Resultado: Cada pedido aparece 1 sola vez
```

---

## 🎯 RESUMEN EJECUTIVO

**Problema:** Sistema creaba 2 pedidos en tabla equivocada para COMBINADA (PL)

**Solución:**
1. ✅ Primera llamada: Crea SOLO en `pedidos_produccion` (prendas)
2. ✅ Segunda llamada: Crea en `logo_pedidos` (logo) y vincula automáticamente
3. ✅ Calcula y guarda cantidad (suma de tallas)
4. ✅ Devuelve ambos números para confirmación

**Archivos:** 3 modificados (BD, Backend, Frontend)
**Complejidad:** Media (requiere coordinación entre 2 requests)
**Testing:** Incluido en GUIA_PRUEBA_PEDIDOS_COMBINADOS.md

