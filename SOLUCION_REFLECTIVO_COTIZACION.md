# ✅ SOLUCIÓN: Guardar Cotización Reflectivo (RF) Correctamente

## 🔴 PROBLEMA IDENTIFICADO
Cuando se creaba una cotización tipo RF (reflectivo) desde `/asesores/pedidos/create?tipo=RF`, 
la información no se guardaba en las tablas correspondientes:
- `reflectivo_cotizacion` 
- `cotizaciones`

El problema era que el formulario enviaba datos con estructura diferente a la que esperaba el `CotizacionController@store`.

## ✅ SOLUCIÓN IMPLEMENTADA

### 1. **Nuevo Endpoint Específico para RF**
**Archivo**: `app/Infrastructure/Http/Controllers/CotizacionController.php`

Creé el método `storeReflectivo()` que:
- Valida datos específicos para reflectivo
- Crea la cotización en tabla `cotizaciones`
- Crea el registro en tabla `reflectivo_cotizacion`
- Guarda imágenes en el disco
- Usa transacciones DB para garantizar integridad

```php
public function storeReflectivo(Request $request): JsonResponse
{
    // Validar datos
    // Crear cotización
    // Crear reflectivo_cotizacion
    // Procesar imágenes
    // Retornar respuesta JSON
}
```

### 2. **Nueva Ruta**
**Archivo**: `routes/web.php` (línea 369)

```php
Route::post('/cotizaciones/reflectivo/guardar', 
    [App\Infrastructure\Http\Controllers\CotizacionController::class, 'storeReflectivo'])
    ->name('cotizaciones.reflectivo.guardar');
```

### 3. **Actualizar Vista para Usar Nueva Ruta**
**Archivo**: `resources/views/asesores/pedidos/create-reflectivo.blade.php`

Cambié el endpoint del formulario:
```javascript
// ANTES:
fetch('{{ route("asesores.cotizaciones.guardar") }}', ...)

// DESPUÉS:
fetch('{{ route("cotizaciones.reflectivo.guardar") }}', ...)
```

### 4. **Relación en Modelo Cotizacion**
**Archivo**: `app/Models/Cotizacion.php`

Agregué alias para acceder a reflectivo:
```php
public function reflectivoCotizacion()
{
    return $this->reflectivo();
}
```

### 5. **Migración de Base de Datos**
**Archivo**: `database/migrations/2025_12_12_000000_create_reflectivo_cotizacion_table.php`

Estructura de tabla con columnas JSON para:
- `ubicacion` (array de ubicaciones)
- `imagenes` (array de rutas de imágenes)
- `observaciones_generales` (array de observaciones)

## 📊 FLUJO COMPLETO

```
Usuario completa formulario RF
    ↓
POST /cotizaciones/reflectivo/guardar
    ↓
CotizacionController@storeReflectivo
    ↓
✅ Crear Cotizacion (tabla cotizaciones)
✅ Crear ReflectivoCotizacion (tabla reflectivo_cotizacion)
✅ Guardar imágenes en /storage/public/cotizaciones/reflectivo/
✅ Retornar JSON con IDs
    ↓
Frontend recibe confirmación
```

## 🧪 TEST CREADO
**Archivo**: `tests/Feature/Cotizaciones/GuardarReflectivoCotizacionTest.php`

Tests incluidos:
1. ✅ Guardar cotización reflectivo exitosamente
2. ✅ Guardar y enviar cotización
3. ✅ Validar errores si faltan datos
4. ✅ Guardar sin imágenes
5. ✅ Números de cotización únicos
6. ✅ Relaciones entre modelos
7. ✅ Control de acceso por usuario

**Configuración**: Usa transacciones en lugar de RefreshDatabase para NO borrar datos

## 🔍 VALIDACIONES IMPLEMENTADAS

```php
'cliente' => 'required|string|max:255',
'asesora' => 'nullable|string|max:255',
'fecha' => 'required|date',
'action' => 'required|in:borrador,enviar',
'descripcion_reflectivo' => 'required|string',
'ubicaciones_reflectivo' => 'nullable',
'observaciones_generales' => 'nullable',
'imagenes_reflectivo.*' => 'nullable|image|...|max:5120',
```

## 📁 DATOS GUARDADOS

### Tabla `cotizaciones`
- `id` (auto)
- `asesor_id` (del usuario autenticado)
- `cliente_id` (creado/obtenido)
- `numero_cotizacion` (COT-YYMMDD-XXXX)
- `tipo_cotizacion_id` (RF = Reflectivo)
- `tipo_venta` (M por defecto)
- `fecha_inicio` (fecha del formulario)
- `es_borrador` (true/false según action)
- `estado` (BORRADOR o ENVIADA_CONTADOR)

### Tabla `reflectivo_cotizacion`
- `cotizacion_id` (FK)
- `descripcion` (texto del reflectivo)
- `ubicacion` (JSON array)
- `observaciones_generales` (JSON array)
- `imagenes` (JSON array con rutas)

### Storage
- Imágenes guardadas en: `/storage/public/cotizaciones/reflectivo/`

## 🚀 CÓMO USAR

1. **Acceder al formulario**:
   ```
   GET /asesores/pedidos/create?tipo=RF
   ```

2. **El formulario enviará a**:
   ```
   POST /cotizaciones/reflectivo/guardar
   ```

3. **Datos se guardarán en**:
   - ✅ `cotizaciones`
   - ✅ `reflectivo_cotizacion`
   - ✅ Imágenes en storage

## 📝 LOGS DISPONIBLES

El controller genera logs detallados:
```
✅ Cliente obtenido/creado
✅ Cotización RF creada
📍 Ubicaciones procesadas
📝 Observaciones procesadas
📸 Imagen guardada
✅ ReflectivoCotizacion creado
```

Todos los cambios están listos para usar. ¡Sin borrar datos de la BD! 🎯
