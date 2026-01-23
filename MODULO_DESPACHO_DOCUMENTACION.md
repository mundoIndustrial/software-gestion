# 📦 MÓDULO DE DESPACHO - Documentación Completa

## 📋 Índice
1. [Descripción General](#descripción-general)
2. [Arquitectura](#arquitectura)
3. [Modelos Eloquent](#modelos-eloquent)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Vistas Blade](#vistas-blade)
7. [JavaScript / Lógica de Cálculos](#javascript--lógica-de-cálculos)
8. [Guía de Uso](#guía-de-uso)
9. [Ejemplos de Implementación](#ejemplos-de-implementación)

---

## 🎯 Descripción General

El **Módulo de Despacho** es una solución completa para el control de entregas parciales de:

- **Prendas** (con y sin tallas)
- **EPP** (Elementos de Protección Personal)

### Características principales:
- ✅ Visualización de pedidos listos para despacho
- ✅ Tabla interactiva de control de entregas
- ✅ Cálculo automático de pendientes
- ✅ Despacho parcial en 3 fases
- ✅ Validaciones en tiempo real
- ✅ Impresión print-friendly
- ✅ Separación visual clara entre prendas y EPP

### ¿QUÉ NO HACE?
- ❌ No crea pedidos nuevos
- ❌ No modifica datos de producción
- ❌ Solo visualiza y controla entregas

---

## 🏗️ Arquitectura

```
Módulo de Despacho
├── Backend (Laravel)
│   ├── DespachoController
│   ├── Modelos con relaciones
│   │   ├── PedidoProduccion (métodos helpers)
│   │   ├── PrendaPedido
│   │   ├── PrendaPedidoTalla
│   │   └── PedidoEpp
│   └── Rutas (routes/despacho.php)
├── Frontend (Blade + TailwindCSS + JS vanilla)
│   ├── index.blade.php (listado)
│   ├── show.blade.php (despacho interactivo)
│   └── print.blade.php (impresión)
└── Funcionalidades
    ├── Cálculo automático de pendientes
    ├── Validación de rangos
    ├── Prevención de valores negativos
    └── Impresión optimizada
```

---

## 🗄️ Modelos Eloquent

### PedidoProduccion

Métodos nuevos agregados:

```php
// Obtener todas las filas de despacho (prendas + EPP unificadas)
$filas = $pedido->getFilasDespacho(); 
// Retorna Collection[
//   {tipo: 'prenda', id, talla_id, descripcion, cantidad_total, talla, genero, ...}
//   {tipo: 'epp', id, descripcion, cantidad_total, ...}
// ]

// Obtener solo prendas con tallas
$prendas = $pedido->getPrendasParaDespacho();

// Obtener solo EPP
$epps = $pedido->getEppParaDespacho();
```

### PrendaPedido

Relaciones existentes + nuevo alias:

```php
// Relación con tallas (relacional)
$prenda->prendaPedidoTallas(); // Alias para compatibilidad con getFilasDespacho()
$prenda->tallas(); // Relación original
```

### Estructura de datos unificada

Cada fila de despacho es un array con:

```php
[
    'tipo' => 'prenda|epp',           // Tipo de ítem
    'id' => 1,                         // ID (prenda_id o pedido_epp_id)
    'talla_id' => 1,                   // Solo para prendas con tallas
    'descripcion' => 'Polo XL',        // Texto para mostrar
    'cantidad_total' => 50,            // Cantidad a despachar
    'talla' => 'XL|—',                 // Talla (— para EPP)
    'genero' => 'Hombre',              // Género (null para EPP)
    'objeto_prenda' => PrendaPedido,   // Objeto del modelo
    'objeto_talla' => PrendaPedidoTalla, // Objeto del modelo
    'objeto_epp' => PedidoEpp,         // Objeto del modelo (null para prenda)
]
```

---

## 🎮 Controlador

**Archivo:** `app/Http/Controllers/DespachoController.php`

### Métodos

#### 1. `index()`
Listar pedidos disponibles para despacho

```php
GET /despacho
```

**Parámetros:** Paginación (15 por página)

**Retorna:** Vista Blade con tabla de pedidos

---

#### 2. `show(PedidoProduccion $pedido)`
Mostrar interfaz detallada de despacho

```php
GET /despacho/{pedido}
```

**Parámetros:**
- `pedido` (ID del pedido)

**Retorna:** Vista Blade con tabla interactiva de despacho

---

#### 3. `guardarDespacho(Request $request, PedidoProduccion $pedido)`
Guardar parciales de despacho

```php
POST /despacho/{pedido}/guardar
```

**Body JSON:**
```json
{
  "fecha_hora": "2026-01-23T14:30",
  "cliente_empresa": "Empresa XYZ",
  "despachos": [
    {
      "tipo": "prenda",
      "id": 1,
      "parcial_1": 10,
      "parcial_2": 5,
      "parcial_3": 0
    },
    {
      "tipo": "epp",
      "id": 2,
      "parcial_1": 5,
      "parcial_2": 3,
      "parcial_3": 0
    }
  ]
}
```

**Validaciones:**
- `tipo`: Debe ser 'prenda' o 'epp'
- `parcial_*`: Números enteros no negativos
- Total despachado ≤ cantidad disponible

**Retorna:**
```json
{
  "success": true,
  "message": "Despacho guardado correctamente",
  "pedido_id": 123
}
```

---

#### 4. `printDespacho(PedidoProduccion $pedido)`
Vista de impresión optimizada

```php
GET /despacho/{pedido}/print
```

**Retorna:** HTML print-friendly

---

## 🔗 Rutas

**Archivo:** `routes/despacho.php`

```php
Route::prefix('despacho')->group(function () {
    // Listar pedidos
    Route::get('/', [DespachoController::class, 'index'])
        ->name('despacho.index');

    // Ver despacho de un pedido
    Route::get('/{pedido}', [DespachoController::class, 'show'])
        ->name('despacho.show');

    // Guardar despacho
    Route::post('/{pedido}/guardar', [DespachoController::class, 'guardarDespacho'])
        ->name('despacho.guardar');

    // Imprimir
    Route::get('/{pedido}/print', [DespachoController::class, 'printDespacho'])
        ->name('despacho.print');
});
```

---

## 📄 Vistas Blade

### 1. `resources/views/despacho/index.blade.php`

Listado de pedidos con:
- Estadísticas en tarjetas
- Tabla paginada
- Enlaces a despachos individuales
- Instrucciones de uso

### 2. `resources/views/despacho/show.blade.php`

Interfaz principal de despacho:

**Encabezado editable:**
- Fecha y hora (datetime-local)
- Cliente / Empresa receptora (texto)

**Tabla interactiva:**
- Columnas: Descripción | Talla | P | Parcial 1 | P | Parcial 2 | P | Parcial 3 | P
- Filas por cada talla de prenda
- Filas por cada EPP (sin talla)
- Separación visual: prendas (azul) vs EPP (verde)

**Cálculos en tiempo real:**
- Validación de números negativos
- Prevención de exceso de cantidad
- Actualización automática de pendientes

**Botones:**
- Cancelar (vuelve a índice)
- Guardar Despacho (POST al servidor)

### 3. `resources/views/despacho/print.blade.php`

Documento de impresión con:
- Encabezado profesional
- Información del pedido
- Tabla separada: prendas vs EPP
- Área de firmas (preparado, recibido, autorizado)
- Estilos print-optimizados
- Notas importantes

---

## 🎯 JavaScript / Lógica de Cálculos

**Ubicación:** Inline en `show.blade.php` (script al final del body)

### Funciones principales

#### 1. `calcularPendientes(event)`

Se ejecuta en cada cambio de input `.parcial-input`

**Lógica:**
```
P1 (Pendiente 1) = Cantidad Total - Parcial 1
P2 (Pendiente 2) = P1 - Parcial 2  
P3 (Pendiente 3) = P2 - Parcial 3
```

**Validaciones:**
- ❌ No permite números negativos → automáticamente pone a 0
- ❌ No permite parciales > cantidad total → recorta al máximo
- ✅ Actualiza DOM en tiempo real
- ✅ Cambia color de fila si pendiente = 0 (verde)

#### 2. `guardarDespacho()`

Ejecutada al hacer click en "Guardar Despacho"

**Proceso:**
1. Recolecta datos de todos los inputs
2. Construye array de despachos
3. Valida que haya al menos 1 parcial
4. Envía POST a servidor (JSON)
5. Maneja respuesta (éxito/error)
6. Recarga página si es exitoso

**Error Handling:**
- Validación de servidor (422)
- Catch de excepciones (500)
- Mensajes de usuario amigables

---

## 📖 Guía de Uso

### Para el usuario final:

1. **Acceder al módulo**
   ```
   Ir a: /despacho
   ```

2. **Seleccionar pedido**
   - Hacer click en "Ver despacho" en la tabla
   - Se abre la interfaz de control de entregas

3. **Completar información del encabezado**
   - Verificar o ajustar fecha/hora
   - Ingresar nombre del cliente/empresa que recibe

4. **Ingresar parciales**
   - Para cada ítem (prenda o EPP):
     - Parcial 1: Primera cantidad entregada
     - Parcial 2: Segunda cantidad entregada
     - Parcial 3: Tercera cantidad entregada
   - Los pendientes se calculan automáticamente

5. **Guardar**
   - Click en botón "💾 Guardar Despacho"
   - Se valida que no haya errores
   - Mensaje de confirmación

6. **Imprimir**
   - Click en botón "🖨️ Imprimir"
   - Se abre vista print en otra pestaña
   - Firmar y archivar el documento

### Para el desarrollador:

#### Obtener filas de despacho en un controlador:
```php
$pedido = PedidoProduccion::find(1);
$filas = $pedido->getFilasDespacho();

foreach ($filas as $fila) {
    echo $fila['descripcion'] . ': ' . $fila['cantidad_total'];
}
```

#### Separar prendas y EPP:
```php
$filas = $pedido->getFilasDespacho();

$prendas = $filas->filter(fn($f) => $f['tipo'] === 'prenda');
$epps = $filas->filter(fn($f) => $f['tipo'] === 'epp');
```

#### Acceder a objetos Eloquent:
```php
foreach ($filas as $fila) {
    if ($fila['tipo'] === 'prenda') {
        $prenda = $fila['objeto_prenda']; // PrendaPedido
        $talla = $fila['objeto_talla'];   // PrendaPedidoTalla
    } elseif ($fila['tipo'] === 'epp') {
        $epp = $fila['objeto_epp'];       // PedidoEpp
    }
}
```

---

## 🔧 Ejemplos de Implementación

### Ejemplo 1: Listar todos los ítems de un pedido

```php
// En un controlador
$pedido = PedidoProduccion::find(1);
$filas = $pedido->getFilasDespacho();

dd($filas); // Debug

// Output:
// Collection [
//   [
//     'tipo' => 'prenda',
//     'id' => 1,
//     'talla_id' => 5,
//     'descripcion' => 'Polo - Hombre',
//     'cantidad_total' => 50,
//     'talla' => 'XL',
//     ...
//   ],
//   [
//     'tipo' => 'epp',
//     'id' => 2,
//     'descripcion' => 'Casco de seguridad (CASCO-001)',
//     'cantidad_total' => 10,
//     ...
//   ]
// ]
```

### Ejemplo 2: Obtener solo prendas con cantidad

```php
$prendas = $pedido->getPrendasParaDespacho();

foreach ($prendas as $prenda) {
    echo $prenda->nombre_prenda . ': ' . $prenda->cantidad . ' unidades';
    
    foreach ($prenda->prendaPedidoTallas as $talla) {
        echo "  - Talla {$talla->talla}: {$talla->cantidad}";
    }
}
```

### Ejemplo 3: Generar reporte de despachos

```php
$pedidos = PedidoProduccion::where('estado', 'Entregado')
    ->with(['prendas.prendaPedidoTallas', 'epps.epp'])
    ->get();

foreach ($pedidos as $pedido) {
    $filas = $pedido->getFilasDespacho();
    
    echo "Pedido: {$pedido->numero_pedido}\n";
    echo "Total ítems: {$filas->count()}\n";
    echo "Prendas: " . $filas->where('tipo', 'prenda')->count() . "\n";
    echo "EPP: " . $filas->where('tipo', 'epp')->count() . "\n";
    echo "---\n";
}
```

### Ejemplo 4: API endpoint personalizado

```php
// En un controlador API
Route::get('/api/despacho/{pedido}/items', function(PedidoProduccion $pedido) {
    return response()->json([
        'pedido_id' => $pedido->id,
        'numero_pedido' => $pedido->numero_pedido,
        'items' => $pedido->getFilasDespacho(),
    ]);
});
```

---

## 📊 Flujo de datos

```
Usuario accede a /despacho
    ↓
DespachoController::index()
    ↓ (GET pedidos activos)
index.blade.php (lista de pedidos)
    ↓ (usuario selecciona pedido)
DespachoController::show($pedido)
    ↓ (obtiene $pedido->getFilasDespacho())
show.blade.php (tabla interactiva)
    ↓ (usuario ingresa parciales)
JavaScript: calcularPendientes() (actualización en tiempo real)
    ↓ (usuario hace click guardar)
DespachoController::guardarDespacho() (POST)
    ↓ (validación)
Log y respuesta JSON
    ↓ (éxito)
Recarga página / print.blade.php
```

---

## 🔒 Notas de seguridad

- ✅ Validación en servidor (no solo cliente)
- ✅ CSRF token en formulario
- ✅ Modelo binding automático de PedidoProduccion
- ✅ Transacción DB para guardar despacho
- ✅ Logs de auditoría para errores

---

## 🚀 Mejoras futuras

- [ ] Tabla de histórico de despachos (`despacho_historico`)
- [ ] Generación de PDF con datos de despacho
- [ ] Integración con sistema de facturación
- [ ] Notificaciones en tiempo real
- [ ] API REST completa
- [ ] Dashboard con métricas de despacho
- [ ] Códigos de barras para ítems

---

## 📞 Contacto / Soporte

Para dudas o issues, revisar:
- Logs: `storage/logs/laravel.log`
- Tabla: `pedidos_produccion`, `prendas_pedido`, `prenda_pedido_tallas`, `pedido_epp`
- Controlador: `app/Http/Controllers/DespachoController.php`
