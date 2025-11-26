# 🔴 PROBLEMAS CRÍTICOS - Visualización Detallada

## Problema #1: DUPLICACIÓN DE CÓDIGO Y LÓGICA

### Vista General del Método `guardar()`
```
┌─────────────────────────────────────────────────────┐
│ guardar(Request $request)                           │
├─────────────────────────────────────────────────────┤
│                                                     │
│ BLOQUE 1: Líneas 81-127                            │
│ ┌─────────────────────────────────────────────┐    │
│ │ Recopilar input del request                 │    │
│ │ $productos = input('productos')             │    │
│ │ $tecnicas = input('tecnicas')               │    │
│ │ $ubicacionesRaw = input('ubicaciones')      │    │
│ │ $imagenes = input('imagenes')               │    │
│ │ Procesar observaciones...                   │    │
│ └─────────────────────────────────────────────┘    │
│                           ↓                         │
│                  Crear Cotización                   │
│                           ↓                         │
│ BLOQUE 2: Líneas 259-310 (¡REPETIDO CASI IGUAL!)  │
│ ┌─────────────────────────────────────────────┐    │
│ │ Recopilar input del request (AGAIN!)        │    │
│ │ $observacionesValor = input(...)            │    │
│ │ $observacionesValor = input(...) ← 2da vez  │    │
│ │ $tecnicas = input('tecnicas') ← DUPLICADO   │    │
│ │ $ubicacionesRaw = input('ubicaciones') ← DUP│    │
│ │ $imagenes = input('imagenes') ← DUPLICADO   │    │
│ │ Procesar observaciones... (IGUAL QUE BLOQUE 1)   │
│ └─────────────────────────────────────────────┘    │
│                           ↓                         │
│                  Crear LogoCotizacion               │
│                           ↓                         │
│         Crear HistorialCotizacion                   │
│                           ↓                         │
│              return response.json()                 │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Líneas Problemáticas Exactas

**Primera Declaración de Variables (Línea 81-90):**
```php
// LÍNEA 81-90
$productos = $request->input('productos', []);
$tecnicas = $request->input('tecnicas', []);
$ubicacionesRaw = $request->input('ubicaciones', []);
$imagenes = $request->input('imagenes', []);
$especificacionesGenerales = $request->input('especificaciones', []);
$observacionesTexto = $request->input('observaciones_generales', []);
$observacionesCheck = $request->input('observaciones_check', []);
$observacionesValor = $request->input('observaciones_valor', []);
```

**Segunda Declaración (Línea 244, 251, 324, 325) - DUPLICADA:**
```php
// LÍNEA 244 - PRIMERA VEZ
$observacionesValor = $request->input('observaciones_valor', []);
$observacionesValor = $request->input('observaciones_valor', []); // ← LÍNEA 251, DUPLICADA

// LÍNEA 324
$tecnicas = $request->input('tecnicas', []);

// LÍNEA 325
$ubicacionesRaw = $request->input('ubicaciones', []);

// LÍNEA 326
$imagenes = $request->input('imagenes', []);
```

---

## Problema #2: OBSERVACIONES PROCESADAS DOS VECES

### Flujo de Observaciones

```
INPUT FORM
    ↓
observaciones_generales = ['Obs 1', 'Obs 2']
observaciones_check = ['on', null]
observaciones_valor = ['', 'Valor 2']
    ↓
    ├─────────────────────────────────────────────┐
    │                                             │
    │ PROCESAMIENTO 1 (Líneas 91-104)            │
    │ ┌───────────────────────────────────────┐  │
    │ │ foreach ($observacionesTexto as ...)  │  │
    │ │ $observacionesGenerales = [           │  │
    │ │   [                                   │  │
    │ │     'texto' => 'Obs 1',               │  │
    │ │     'tipo' => 'checkbox',             │  │
    │ │     'valor' => ''                     │  │
    │ │   ],                                  │  │
    │ │   [                                   │  │
    │ │     'texto' => 'Obs 2',               │  │
    │ │     'tipo' => 'texto',                │  │
    │ │     'valor' => 'Valor 2'              │  │
    │ │   ]                                   │  │
    │ │ ]                                     │  │
    │ └───────────────────────────────────────┘  │
    │         ↓ (Usado en línea 175)             │
    │   Cotizacion::create($datos)               │
    │         ↓                                   │
    │   Cotizacion.observaciones_generales       │
    │   ✓ Guardado en tabla                      │
    │                                             │
    └─────────────────────────────────────────────┘
           ↓ ↓ ↓
    ├─────────────────────────────────────────────┐
    │                                             │
    │ PROCESAMIENTO 2 (Líneas 261-289)           │
    │ MISMO CÓDIGO, MISMOS DATOS ¿POR QUÉ?       │
    │ ┌───────────────────────────────────────┐  │
    │ │ foreach ($observacionesTexto as ...)  │  │
    │ │ $observacionesGenerales = [           │  │
    │ │   [                                   │  │
    │ │     'texto' => 'Obs 1',               │  │
    │ │     'tipo' => 'checkbox',             │  │
    │ │     'valor' => ''                     │  │
    │ │   ],                                  │  │
    │ │   [                                   │  │
    │ │     'texto' => 'Obs 2',               │  │
    │ │     'tipo' => 'texto',                │  │
    │ │     'valor' => 'Valor 2'              │  │
    │ │   ]                                   │  │
    │ │ ]                                     │  │
    │ └───────────────────────────────────────┘  │
    │         ↓ (Usado en línea 328)             │
    │   LogoCotizacion::create($datos)           │
    │         ↓                                   │
    │   LogoCotizacion.observaciones_generales   │
    │   ✓ Guardado en tabla (DUPLICADO)          │
    │                                             │
    └─────────────────────────────────────────────┘
```

### Problema: Inconsistencia de Datos

```
Base de Datos DESPUÉS de ejecutar guardar():

┌─ Tabla: cotizaciones ──────────────────────────┐
│ id | cliente | observaciones_generales        │
├─────────────────────────────────────────────┤
│ 1  | Acme   | [{texto, tipo, valor}, {...}] │
└──────────────────────────────────────────────┘

┌─ Tabla: logo_cotizaciones ─────────────────────┐
│ id | cotizacion_id | observaciones_generales  │
├─────────────────────────────────────────────┤
│ 1  | 1             | [{texto, tipo, valor}, 
{...}] │
└──────────────────────────────────────────────┘

⚠️ MISMO DATO EN DOS TABLAS = VIOLACIÓN DE NORMALIZACIÓN
```

---

## Problema #3: VARIABLES REASIGNADAS

```python
# Línea 244
$observacionesValor = $request->input('observaciones_valor', []);
# ├─ Variable se crea/asigna

# ... 7 líneas de código (logs y procesos)

# Línea 251
$observacionesValor = $request->input('observaciones_valor', []);
# ├─ ¿Por qué se asigna OTRA VEZ?
# ├─ Es el MISMO input
# └─ ¿Posible copy-paste error?

# Línea 275
foreach ($observacionesTexto as $index => $obs) {
    if (!empty($obs)) {
        $checkValue = $observacionesCheck[$index] ?? null;
        $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
        $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
        # ├─ Aquí se usa $observacionesValor
        # ├─ ¿De cuál reasignación se está usando?
        # └─ La segunda (línea 251), pero ¿por qué la primera (244) existe?
```

---

## Problema #4: AUSENCIA DE VALIDACIÓN

### Flujo sin Validación

```
┌─ Usuario envía formulario ────────────────────┐
│                                               │
│ POST /asesores/cotizaciones/guardar           │
│                                               │
│ {                                             │
│   "cliente": "<script>alert('XSS')</script>", │ ← Inyección
│   "tipo": "malicioso",                        │ ← Valor no esperado
│   "productos": [                              │
│     {                                         │
│       "nombre_producto": null,                │ ← Null
│       "tallas": "no-es-array",                │ ← Tipo incorrecto
│       "descripcion": 123                      │ ← Número en lugar de string
│     }                                         │
│   ]                                           │
│ }                                             │
│                                               │
└───────────────────────────────────────────────┘
         ↓ (sin validación)
┌─ CotizacionesController::guardar() ───────────┐
│                                               │
│ $tipo = $request->input('tipo', 'borrador');  │
│ // ✓ "malicioso" pasa sin validar             │
│                                               │
│ $cliente = $request->input('cliente');        │
│ // ✓ "<script>alert(...)</script>" pasa       │
│                                               │
│ $productos = $request->input('productos');    │
│ // ✓ Array con tipos incorrectos pasa         │
│                                               │
│ $cotizacion = Cotizacion::create($datos);     │
│                                               │
└───────────────────────────────────────────────┘
         ↓
┌─ Base de Datos ────────────────────────────────┐
│                                               │
│ INSERT INTO cotizaciones (                    │
│   cliente = "<script>alert('XSS')</script>",  │
│   tipo = "malicioso",                         │
│   productos = {...}  ← Estructura inválida    │
│ )                                             │
│                                               │
│ ✓ Datos maliciosos guardados                  │
│ ✓ Estructura de datos corrompida              │
│ ✓ Posible SQL Injection si hay raw SQL        │
│                                               │
└───────────────────────────────────────────────┘
```

---

## Problema #5: SHELL_EXEC SIN ESCAPING

### Vulnerabilidad de Command Injection

```
CÓDIGO VULNERABLE (Línea 603-616):

$rutaOriginal = $archivo->getRealPath();
// Resultado: /tmp/php_uploads/imagen.jpg

$rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreRenombrado}");
// Resultado: /app/storage/app/public/cotizaciones/1/tipo/image.webp

if (shell_exec('where cwebp 2>nul') || shell_exec('which cwebp 2>/dev/null')) {
    $comando = "cwebp -q 80 \"{$rutaOriginal}\" -o \"{$rutaTemporal}\"";
    @shell_exec($comando . " 2>&1");
}

═════════════════════════════════════════════════════════════

ATAQUE POSIBLE #1: Command Injection a través de ruta

Usuario crea archivo con nombre:
  "imagen.jpg; rm -rf /app/storage; #.jpg"

Luego sube en formulario...

$rutaOriginal = "/tmp/php_uploads/imagen.jpg; rm -rf /app/storage; #.jpg"

$comando construido:
  cwebp -q 80 "/tmp/php_uploads/imagen.jpg; rm -rf /app/storage; #.jpg" -o "..."

Ejecución:
  → cwebp procesa: /tmp/php_uploads/imagen.jpg
  → ; separa comandos
  → rm -rf /app/storage se EJECUTA
  → # comenta el resto

RESULTADO: ¡Carpeta storage eliminada!

═════════════════════════════════════════════════════════════

SOLUCIÓN CORRECTA:

$comando = sprintf(
    'cwebp -q 80 %s -o %s',
    escapeshellarg($rutaOriginal),    // Escapa caracteres especiales
    escapeshellarg($rutaTemporal)     // Escapa caracteres especiales
);

Con escapeshellarg:
  escapeshellarg("/tmp/imagen.jpg; rm -rf /") 
  → '/tmp/imagen.jpg; rm -rf /\'

Los caracteres peligrosos se escapan y se trata como STRING literal
```

---

## Problema #6: FALTA DE TRANSACCIÓN

### Escenario de Corrupción de Datos

```
TRANSACCIÓN SIN CONTROL:

1. Cotizacion::create()               ✓ EXITOSO
   └─ Cotizacion ID: 100 creada
   
2. foreach ($productos) {
     PrendaCotizacionFriendly::create()  ✓ EXITOSO (3 prendas)
     guardarVariantesPrenda()            ✓ EXITOSO
   }
   
3. LogoCotizacion::create()            ⚠️ ERROR: Columna no existe
   └─ Laravel exception

4. HistorialCotizacion::create()       ❌ NUNCA SE EJECUTA
   └─ Transacción fallida

═════════════════════════════════════════════════════════════

ESTADO DE BASE DE DATOS DESPUÉS:

Tabla cotizaciones:
│ id  | numero_cotizacion | cliente | es_borrador │
├─────┼───────────────────┼─────────┼─────────────┤
│ 100 | COT-00001         | Acme    | false       │ ✓ Existe

Tabla prendas_cotizaciones:
│ id  | cotizacion_id | nombre_producto │
├─────┼───────────────┼─────────────────┤
│ 200 | 100           | CAMISA          │ ✓ Existe
│ 201 | 100           | PANTALÓN        │ ✓ Existe
│ 202 | 100           | POLO            │ ✓ Existe

Tabla logo_cotizaciones:
│ id  | cotizacion_id │
├─────┼───────────────┤
(VACÍA) ❌ NO se creó

Tabla historial_cotizaciones:
│ id  | cotizacion_id │
├─────┼───────────────┤
(VACÍA) ❌ NO se creó

═════════════════════════════════════════════════════════════

CONSECUENCIAS:

⚠️ Cotización existe pero sin logo/bordado
⚠️ Usuario ve error pero datos parciales guardados
⚠️ No hay registro en historial
⚠️ Posible estado inconsistente
⚠️ Búsqueda de bugs muy difícil

═════════════════════════════════════════════════════════════

SOLUCIÓN CON TRANSACCIÓN:

DB::beginTransaction();
try {
    $cotizacion = Cotizacion::create($datos);           // 1
    foreach ($productos as $producto) {                 // 2
        $prenda = PrendaCotizacionFriendly::create(...);
        $this->guardarVariantesPrenda($prenda, $producto);
    }
    LogoCotizacion::create($logoCotizacionData);        // 3
    HistorialCotizacion::create($historialData);        // 4
    
    DB::commit();  // ← TODOS SE CONFIRMAN O NINGUNO
    
} catch (\Exception $e) {
    DB::rollBack();  // ← TODOS SE REVIERTEN
    throw $e;
}

Si falla en paso 3:
  → rollBack() elimina TODO
  → Base de datos queda limpia
  → Sin datos huérfanos
```

---

## Problema #7: MÉTODO FALTANTE

### `heredarVariantesDePrendaPedido()` NO EXISTE

```
Línea 1020 en CotizacionesController:

$this->heredarVariantesDePrendaPedido($cotizacion, $prenda, $index);
│
└─ Se llama al método...

Búsqueda en archivo CotizacionesController.php:
┌─────────────────────────────────────────┐
│ DEFINICIÓN DEL MÉTODO                   │
│                                         │
│ ❌ NO ENCONTRADO EN EL ARCHIVO          │
│                                         │
│ grep "heredarVariantesDePrendaPedido"   │
│ c:\...\CotizacionesController.php:1020  │
│                                         │
│ (Solo aparece LA LLAMADA, no la def)    │
│                                         │
└─────────────────────────────────────────┘

RESULTADO EN RUNTIME:

⚠️ Error: Call to undefined method 
          heredarVariantesDePrendaPedido()

📍 Location: 
   app/Http/Controllers/Asesores/CotizacionesController.php:1020

🔴 FUNCIONALIDAD ROTA:
   - aceptarCotizacion() fallará
   - Pedidos de producción no se crean correctamente
   - Variantes no se heredan
```

---

## Resumen Visual de Impactos

```
┌──────────────────────────────────────────────────────────────────┐
│ IMPACTO EN FLUJO DE NEGOCIO                                      │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ASESOR CREA COTIZACIÓN                                           │
│        ↓                                                          │
│   [GUARDAR] ← Duplicación de código + falta de validación       │
│        ↓                                                          │
│   Datos no validados en BD                                       │
│        ↓                                                          │
│   Sin transacción: Datos potencialmente inconsistentes           │
│        ↓                                                          │
│   ASESOR ENVÍA COTIZACIÓN                                        │
│        ↓                                                          │
│   [CAMBIAR ESTADO]                                               │
│        ↓                                                          │
│   CLIENTE ACEPTA                                                 │
│        ↓                                                          │
│   [ACEPTAR] ← CRASH: heredarVariantesDePrendaPedido() no existe  │
│        ↓                                                          │
│   ❌ ERROR 500 - PEDIDO NO SE CREA                               │
│   ❌ DATOS PARCIALES EN BD                                       │
│   ❌ USUARIO CONFUNDIDO                                          │
│   ❌ PROCESO DE NEGOCIO DETENIDO                                 │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

