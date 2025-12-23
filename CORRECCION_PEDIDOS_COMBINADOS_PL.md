# ✅ CORRECCIÓN COMPLETADA: Crear 2 Pedidos Independientes desde Cotizaciones Combinadas (PL)

## 🎯 PROBLEMA IDENTIFICADO
- ❌ Se estaba creando 2 veces en `pedidos_produccion`
- ❌ No se creaba en `logo_pedidos`

## ✅ SOLUCIÓN IMPLEMENTADA

### 1️⃣ BACKEND - `PedidosProduccionController.php`

#### Cambio 1: Método `crearDesdeCotizacion()`
**Lo que NO hacer:**
- ❌ REMOVER la lógica que creaba `logo_pedido` automáticamente en el primer request

**Lo que SÍ hacer:**
- ✅ Crear SOLO en `pedidos_produccion` (con las prendas)
- ✅ Indicar al frontend que es COMBINADA (PL) con `es_combinada: true`
- ✅ Permitir que el frontend haga el segundo request para crear `logo_pedidos`

```php
// Si es COMBINADA, devolver indicación para que frontend cree logo_pedido después
if ($tipoCotizacionCodigo === 'PL') {
    return response()->json([
        'success' => true,
        'pedido_id' => $pedido->id,  // ID del pedidos_produccion
        'es_combinada' => true        // ← Indicación para frontend
    ]);
}
```

#### Cambio 2: Método `guardarLogoPedido()`
**Ahora hace DOS cosas:**

**CASO 1: LOGO SOLO (L)**
- Encuentra el registro existente en `logo_pedidos` (creado por `crearLogoPedidoDesdeAnullCotizacion`)
- Lo ACTUALIZA con los datos del formulario

**CASO 2: COMBINADA (PL)**
- ✅ NUEVO: No encuentra el registro (porque NO se creó en el primer request)
- ✅ NUEVO: CREA uno nuevo en `logo_pedidos` con:
  - `pedido_id` = ID del pedidos_produccion (vinculación)
  - Número LOGO generado automáticamente
  - Todos los datos del formulario (descripción, técnicas, ubicaciones, cantidad, fotos)

```php
$logoPedidoExistente = DB::table('logo_pedidos')->find($pedidoId);

if (!$logoPedidoExistente) {
    // CREAR nuevo registro (COMBINADA PL)
    $pedidoId = DB::table('logo_pedidos')->insertGetId([
        'pedido_id' => $pedidoId,           // ← Vincular a pedidos_produccion
        'numero_pedido' => $numeroLogoPedido,
        'descripcion' => $request->input('descripcion'),
        'cantidad' => $cantidad,
        // ... otros campos ...
    ]);
} else {
    // ACTUALIZAR registro existente (LOGO SOLO)
    DB::table('logo_pedidos')->where('id', $pedidoId)->update($updateData);
}
```

#### Cambio 3: Respuesta mejorada
```php
return response()->json([
    'success' => true,
    'logo_pedido' => $logoPedido,
    'pedido_produccion' => $pedidoPrendas,  // ← Datos del pedido de prendas
    'numero_pedido_produccion' => $pedidoPrendas?->numero_pedido,
    'numero_pedido_logo' => $logoPedido->numero_pedido
]);
```

### 2️⃣ FRONTEND - `crear-pedido-editable.js`

#### Cambio 1: Detectar tipo de cotización
```javascript
const esCombinada = dataCrearPedido.es_combinada || dataCrearPedido.tipo_cotizacion === 'PL';
const esLogoSolo = tipoCotizacion === 'L';
```

#### Cambio 2: Enviar datos correctos a `/guardar-logo-pedido`
```javascript
const bodyLogoPedido = {
    pedido_id: pedidoId,           // ID de pedidos_produccion (para COMBINADA)
    logo_cotizacion_id: logoCotizacionIdAUsar,
    cotizacion_id: cotizacionId,   // ✅ NUEVO
    forma_de_pago: formaPagoInput.value,  // ✅ NUEVO
    descripcion: descripcionLogoPedido,
    cantidad: cantidadTotal,
    tecnicas: logoTecnicasSeleccionadas,
    ubicaciones: logoSeccionesSeleccionadas,
    fotos: logoFotosSeleccionadas
};
```

#### Cambio 3: Mostrar ambos números correctamente
```javascript
if (esCombinada) {
    const numeroPrendas = data.numero_pedido_produccion;
    const numeroLogo = data.numero_pedido_logo;
    
    // Mostrar AMBOS números
    Swal.fire({
        html: '<p>📦 Pedido Producción: ' + numeroPrendas + '<br>' +
              '🎨 Pedido Logo: ' + numeroLogo + '</p>'
    });
}
```

## 🔄 FLUJO CORRECTO DESPUÉS DE LA CORRECCIÓN

```
Usuario selecciona cotización COMBINADA (PL)
         ↓
PASO 1-2: Buscar y seleccionar
         ↓
PASO 3: Renderizar 2 TABS
        [📦 PRENDAS] [🎨 LOGO]
         ↓
Usuario rellena AMBOS tabs
         ↓
Click "Crear Pedido"
         ↓
┌─────────────────────────────────────────────┐
│ REQUEST 1: /crear-desde-cotizacion/         │
│ POST {                                      │
│   cotizacion_id: 123,                       │
│   forma_de_pago: "CONTADO",                 │
│   prendas: [...]  ← Datos del tab PRENDAS  │
│ }                                           │
└─────────────────┬───────────────────────────┘
                  ↓
        ✅ Crea en pedidos_produccion
        Respuesta: {
            success: true,
            pedido_id: 45,      ← ID del pedidos_produccion
            es_combinada: true
        }
         ↓
┌─────────────────────────────────────────────┐
│ REQUEST 2: /guardar-logo-pedido/            │
│ POST {                                      │
│   pedido_id: 45,           ← Referencia    │
│   descripcion: "...",                       │
│   cantidad: 150,                            │
│   tecnicas: [...],                          │
│   ubicaciones: [...],       ← Datos tab LOGO
│   fotos: [...]                             │
│ }                                           │
└─────────────────┬───────────────────────────┘
                  ↓
        ✅ Crea en logo_pedidos (NUEVO)
        Con vinculación: pedido_id = 45
        Respuesta: {
            success: true,
            numero_pedido_produccion: "PED-00045",
            numero_pedido_logo: "LOGO-00006"
        }
         ↓
✅ ÉXITO: Mostrar ambos números
   📦 PED-00045
   🎨 LOGO-00006
         ↓
Redirigir a /asesores/pedidos
```

## 📊 REGISTROS EN BD DESPUÉS DE CORRECCIÓN

### Tabla `pedidos_produccion`
```sql
INSERT INTO pedidos_produccion VALUES (
    45,                    -- id
    123,                   -- cotizacion_id
    'PED-00045',          -- numero_pedido
    'Cliente XYZ',        -- cliente
    1,                    -- asesor_id
    'CONTADO',            -- forma_de_pago
    'Pendiente',          -- estado
    NOW()                 -- fecha_de_creacion_de_orden
    ...
);

-- Tabla prendas_pedido con prendas del tab PRENDAS
INSERT INTO prendas_pedido VALUES (..., 45, 'Camisa', 50, ...);
INSERT INTO prendas_pedido VALUES (..., 45, 'Pantalón', 50, ...);
```

### Tabla `logo_pedidos` (NUEVO REGISTRO CREADO)
```sql
INSERT INTO logo_pedidos VALUES (
    6,                          -- id
    45,                         -- pedido_id ← Vinculado a pedidos_produccion(45)
    12,                         -- logo_cotizacion_id
    'LOGO-00006',              -- numero_pedido
    'Logo bordado uniforme',    -- descripcion
    150,                        -- cantidad ← Suma de tallas
    '["BORDADO"]',             -- tecnicas
    '[...]',                    -- ubicaciones
    'CONTADO',                  -- forma_de_pago
    'Usuario1',                 -- asesora
    NOW(),                      -- fecha_de_creacion_de_orden
    ...
);
```

## ✅ VALIDACIÓN

### Verificar en BD
```sql
-- Debe existir UN registro en pedidos_produccion
SELECT * FROM pedidos_produccion WHERE numero_pedido = 'PED-00045';

-- Debe existir UN registro en logo_pedidos VINCULADO
SELECT * FROM logo_pedidos WHERE pedido_id = 45;

-- Verificar que la cantidad se guardó correctamente
SELECT numero_pedido, cantidad FROM logo_pedidos WHERE numero_pedido = 'LOGO-00006';
-- Debe mostrar: LOGO-00006, 150 (si sumaba 150 de tallas)
```

### Verificar en Interfaz
- ✅ Ir a /asesores/pedidos
- ✅ Deben aparecer 2 pedidos:
  - Uno de PRENDAS (PED-00045)
  - Uno de LOGO (LOGO-00006)
- ✅ Los 2 con la misma cotización_id

## 📁 ARCHIVOS MODIFICADOS

| Archivo | Cambios |
|---------|---------|
| `app/Http/Controllers/Asesores/PedidosProduccionController.php` | Remover creación automática de logo_pedido en `crearDesdeCotizacion`, mejorar `guardarLogoPedido` para CREAR cuando es PL |
| `public/js/crear-pedido-editable.js` | Detectar esCombinada, enviar cotizacion_id y forma_de_pago, mostrar ambos números |

## 🎯 RESULTADO FINAL

Ahora el sistema:
✅ Crea pedido de PRENDAS en `pedidos_produccion` desde tab PRENDAS
✅ Crea pedido de LOGO en `logo_pedidos` desde tab LOGO
✅ Los vincula automáticamente (pedido_id en logo_pedidos)
✅ Guarda cantidad total (suma de tallas) en logo_pedidos.cantidad
✅ Muestra ambos números de pedido al usuario
✅ No crea duplicados ni en la tabla equivocada

