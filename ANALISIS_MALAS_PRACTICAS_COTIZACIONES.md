# Análisis de Malas Prácticas en Módulo de Cotizaciones

## 🚨 Problemas Críticos Encontrados

### 1. **DUPLICACIÓN MASIVA DE CÓDIGO** ⚠️ CRÍTICO
**Ubicación:** `CotizacionesController.php` líneas 81-127 y 259-310

**Problema:**
```php
// Líneas 81-127 (PRIMERA VEZ)
$productos = $request->input('productos', []);
$tecnicas = $request->input('tecnicas', []);
$ubicacionesRaw = $request->input('ubicaciones', []);
$imagenes = $request->input('imagenes', []);
$especificacionesGenerales = $request->input('especificaciones', []);
$observacionesTexto = $request->input('observaciones_generales', []);

// Líneas 259-310 (SEGUNDA VEZ - DUPLICADO)
$observacionesValor = $request->input('observaciones_valor', []);
$observacionesValor = $request->input('observaciones_valor', []); // ¡DUPLICADO!

$tecnicas = $request->input('tecnicas', []);
$ubicacionesRaw = $request->input('ubicaciones', []);
$imagenes = $request->input('imagenes', []);
```

**Impacto:**
- Mismo código repetido en la misma función
- Variables se declaran dos veces (línea 244 y 251)
- Difícil de mantener
- Riesgo de inconsistencias

**Solución:** Extraer lógica común a método privado `processFormInputs()`

---

### 2. **PROCESAMIENTO DE OBSERVACIONES INCORRECTO** ⚠️ CRÍTICO
**Ubicación:** Líneas 91-104 y 261-289

**Problema:**
```php
// Primera vez (líneas 91-104)
foreach ($observacionesTexto as $index => $obs) {
    if (!empty($obs)) {
        $checkValue = $observacionesCheck[$index] ?? null;
        $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
        $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
        
        $observacionesGenerales[] = [
            'texto' => $obs,
            'tipo' => $tipo,
            'valor' => $valor
        ];
    }
}

// Segunda vez (líneas 261-289)
foreach ($observacionesTexto as $index => $obs) {
    if (!empty($obs)) {
        $checkValue = $observacionesCheck[$index] ?? null;
        $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
        $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
        
        \Log::info('📝 Procesando observación:', [...]);
        $observacionesGenerales[] = [
            'texto' => $obs,
            'tipo' => $tipo,
            'valor' => $valor
        ];
    }
}
```

**Impacto:**
- Las observaciones se procesan TWICE
- Se guardan diferentes datos en `Cotizacion` vs `LogoCotizacion`
- Inconsistencia de datos

**Detalles:**
```
1. Primera iteración (línea 91): Se procesan y guardan en $observacionesGenerales
2. Segunda iteración (línea 261): Se vuelven a procesar IGUAL
3. Primera se usa en: Cotizacion::create() - línea 175
4. Segunda se usa en: LogoCotizacion::create() - línea 328
```

---

### 3. **VALIDACIÓN AUSENTE** ⚠️ CRÍTICO
**Ubicación:** Método `guardar()` líneas 40-360

**Problema:**
```php
public function guardar(Request $request)
{
    try {
        // ❌ NO HAY VALIDACIÓN
        $tipo = $request->input('tipo', 'borrador');
        $cliente = $request->input('cliente');
        // ... más sin validar
```

**Lo que falta:**
```php
// Debería tener:
$request->validate([
    'cliente' => 'required|string|max:255',
    'tipo' => 'required|in:borrador,enviada',
    'productos' => 'array',
    'productos.*.nombre_producto' => 'required|string',
    'productos.*.tallas' => 'required|array',
    'tecnicas' => 'array',
    'imagenes' => 'array',
    'especificaciones' => 'array'
]);
```

**Impacto:**
- SQL Injection posible
- Datos inválidos en BD
- Posible corrupción de datos

---

### 4. **ACCESO DIRECTO A ARRAY SIN VALIDACIÓN** ⚠️ ALTO
**Ubicación:** Líneas 202-215

**Problema:**
```php
foreach ($productos as $index => $producto) {
    $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
    $nombrePrenda = $producto['nombre_producto'] ?? '';
    
    $nombreUpper = strtoupper(trim($nombrePrenda));
    $palabraPrincipal = explode(' ', $nombreUpper)[0];
    // ❌ ¿Y si $nombreUpper está vacío? explode devuelve array [0 => '']
    
    $esJeanPantalon = preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal) === 1;
    
    $tipoJeanPantalon = null;
    if ($esJeanPantalon && is_array($producto['variantes'] ?? null)) {
        $tipoJeanPantalon = $producto['variantes']['tipo'] ?? null;
        // ❌ ¿Y si 'tipo' no existe?
    }
```

**Impacto:**
- PHP Notice si estructuras no existen
- Lógica inconsistente

---

### 5. **LOG LLAMADAS INNECESARIAS** ⚠️ MEDIO
**Ubicación:** Todo el método `guardar()`

```php
\Log::info('🚀 MÉTODO GUARDAR LLAMADO');
\Log::info('Guardando cotización', [...]);
\Log::info('Tipo de cotización recibido', [...]);
\Log::info('Tipo de cotización encontrado', [...]);
\Log::info('Productos a guardar en prendas_cotizaciones', [...]);
\Log::info('Guardando prenda individual', [...]);
\Log::info('Prenda guardada exitosamente', [...]);
\Log::info('Prendas guardadas exitosamente', [...]);
\Log::info('🔍 DATOS RECIBIDOS DEL CLIENTE:', [...]);
// ... más de 20 logs

// Y lo peor:
\Log::info("Check[$idx] = " . json_encode($val) . " (type: " . gettype($val) . ")");
// ⚠️ Esto se repite en CADA iteración

foreach ($observacionesTexto as $index => $obs) {
    if (!empty($obs)) {
        // ... más logs
        \Log::info('📝 Procesando observación:', [...]);
    }
}
```

**Impacto:**
- Logs enormes en producción
- Degradación de rendimiento
- Archivos de log muy grandes

---

### 6. **VARIABLES REASIGNADAS** ⚠️ ALTO
**Ubicación:** Línea 244 y 251

```php
$observacionesValor = $request->input('observaciones_valor', []);
// ... líneas de código ...
$observacionesValor = $request->input('observaciones_valor', []); // ❌ REASIGNADA
```

También línea 251 y luego se vuelve a usar línea 275

---

### 7. **FALTA DE TRANSACCIÓN EN GUARDAR** ⚠️ CRÍTICO
**Ubicación:** Método `guardar()` líneas 40-360

**Problema:**
```php
// Se crean 3 registros SIN transacción:
$cotizacion = Cotizacion::create($datos); // Línea 175

// Crear prendas
foreach ($productos as $index => $producto) {
    $prenda = \App\Models\PrendaCotizacionFriendly::create([...]); // Línea 225
    $this->guardarVariantesPrenda($prenda, $producto); // Línea 244
}

\App\Models\LogoCotizacion::create($logoCotizacionData); // Línea 328
\App\Models\HistorialCotizacion::create([...]); // Línea 330

// Si falla en la mitad:
// - Cotización se creó ✓
// - Prendas parciales ✓
// - LogoCotizacion NO se creó ❌
// - Historial NO se creó ❌
// = BASE DE DATOS INCONSISTENTE
```

**Impacto:**
- Datos inconsistentes en BD
- Cotización huérfana o incompleta
- Difícil de debuggear

---

### 8. **USO DE SHELL_EXEC SIN VALIDACIÓN** ⚠️ CRÍTICO (SEGURIDAD)
**Ubicación:** Líneas 603-616

```php
// Intentar usar cwebp si está disponible
if (shell_exec('where cwebp 2>nul') || shell_exec('which cwebp 2>/dev/null')) {
    $comando = "cwebp -q 80 \"{$rutaOriginal}\" -o \"{$rutaTemporal}\"";
    @shell_exec($comando . " 2>&1");
    // ❌ VULNERABILIDAD: $rutaOriginal y $rutaTemporal no están escapadas
}
```

**Impacto:**
- Command Injection posible
- Acceso no autorizado a servidor

---

### 9. **MÉTODOS PRIVADOS DUPLICADOS** ⚠️ ALTO
**Ubicación:** Líneas 198-214 (en guardar) y 482-497 (en actualizarBorrador)

```php
// Líneas 198-214
$nombreUpper = strtoupper(trim($nombrePrenda));
$palabraPrincipal = explode(' ', $nombreUpper)[0];
$esJeanPantalon = preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal) === 1;

// Líneas 482-497 (REPETIDO)
$nombreUpper = strtoupper(trim($nombrePrenda));
$palabraPrincipal = explode(' ', $nombreUpper)[0];
$esJeanPantalon = preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal) === 1;
```

---

### 10. **FALTA DE VALIDACIÓN DE AUTORIZACIÓN** ⚠️ CRÍTICO
**Ubicación:** Método `guardar()` línea 40

```php
public function guardar(Request $request)
{
    // ❌ NO HAY VALIDACIÓN DE AUTORIZACIÓN
    // ¿Qué pasa si envían cotizacion_id de otro usuario?
    
    if ($cotizacionId) {
        return $this->actualizarBorrador($request, $cotizacionId);
    }
    // ...
}
```

**En `actualizarBorrador()` SÍ se valida (línea 435), pero en `guardar()` NO**

---

### 11. **GESTIÓN INCONSISTENTE DE ERRORES** ⚠️ MEDIO
**Ubicación:** Métodos con try-catch

```php
// En guardar() - Expone datos en debug mode
return response()->json([
    'success' => false,
    'message' => 'Error: ' . $e->getMessage(),
    'debug' => config('app.debug') ? $e->getTraceAsString() : null // ❌ Stack trace expuesto
], 500);

// Pero en destroy() - Más seguro
return response()->json([
    'success' => false,
    'message' => 'Error al eliminar el borrador'
], 500);
```

---

### 12. **ASINCRONÍA NO DOCUMENTADA** ⚠️ MEDIO
**Ubicación:** Método `heredarVariantesDePrendaPedido()`

**Problema:**
```php
$this->heredarVariantesDePrendaPedido($cotizacion, $prenda, $index);
// ❌ Este método se llama pero NUNCA ESTÁ DEFINIDO en el controller
// Se busca en toda la clase y NO EXISTE
```

**Impacto:**
- Error fatal en runtime
- Funcionalidad rota

---

## 📋 Resumen de Problemas

| Severidad | Cantidad | Tipo |
|-----------|----------|------|
| 🔴 CRÍTICO | 6 | Seguridad, Lógica, Validación |
| 🟠 ALTO | 4 | Duplicación, Autorización |
| 🟡 MEDIO | 3 | Logs, Errores, Métodos faltantes |
| **TOTAL** | **13** | **Problemas encontrados** |

---

## ✅ Recomendaciones de Refactoring

### Paso 1: Extraer métodos comunes
```php
private function processFormInputs(Request $request): array
{
    return [
        'productos' => $request->input('productos', []),
        'tecnicas' => $request->input('tecnicas', []),
        'ubicaciones' => $request->input('ubicaciones', []),
        'imagenes' => $request->input('imagenes', []),
        'especificaciones' => $request->input('especificaciones', []),
        'observaciones' => $this->processObservaciones($request)
    ];
}

private function processObservaciones(Request $request): array
{
    $observacionesTexto = $request->input('observaciones_generales', []);
    $observacionesCheck = $request->input('observaciones_check', []);
    $observacionesValor = $request->input('observaciones_valor', []);
    
    $observacionesGenerales = [];
    foreach ($observacionesTexto as $index => $obs) {
        if (!empty($obs)) {
            $checkValue = $observacionesCheck[$index] ?? null;
            $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
            $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
            
            $observacionesGenerales[] = [
                'texto' => $obs,
                'tipo' => $tipo,
                'valor' => $valor
            ];
        }
    }
    return $observacionesGenerales;
}
```

### Paso 2: Usar transacciones
```php
public function guardar(Request $request)
{
    $validated = $request->validate([
        'cliente' => 'required|string|max:255',
        'tipo' => 'required|in:borrador,enviada',
        // ...
    ]);
    
    DB::beginTransaction();
    try {
        // Todas las operaciones aquí
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        // Manejo de error
    }
}
```

### Paso 3: Eliminar logs innecesarios
- Mantener solo logs de EVENTOS importantes (crear, actualizar, eliminar)
- Eliminar logs de DEBUG en loops

### Paso 4: Crear método para detectar tipo de prenda
```php
private function detectarTipoPrenda(string $nombrePrenda): array
{
    $nombreUpper = strtoupper(trim($nombrePrenda));
    $palabraPrincipal = explode(' ', $nombreUpper)[0] ?? '';
    
    return [
        'esJeanPantalon' => (bool)preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal),
        'tipo' => $palabraPrincipal
    ];
}
```

### Paso 5: Escapar comandos shell
```php
// En lugar de:
$comando = "cwebp -q 80 \"{$rutaOriginal}\" -o \"{$rutaTemporal}\"";

// Usar:
$comando = sprintf(
    'cwebp -q 80 %s -o %s',
    escapeshellarg($rutaOriginal),
    escapeshellarg($rutaTemporal)
);
```

---

## 🔍 Archivos Relacionados a Revisar

1. `app/Models/Cotizacion.php` - Revisar relaciones
2. `app/Models/LogoCotizacion.php` - Revisar si duplica data
3. `app/Models/HistorialCotizacion.php` - Deprecated, considerar eliminar
4. `app/Services/ImagenCotizacionService.php` - Revisar seguridad
5. `routes/web.php` - Revisar rutas sin validación
6. Tests en `tests/Feature/Asesores/CotizacionesTest.php` - Revisar cobertura

