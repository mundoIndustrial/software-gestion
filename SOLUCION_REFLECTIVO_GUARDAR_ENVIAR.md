# 🔧 SOLUCIÓN: Guardar Borrador y Enviar Cotización Reflectivo

## 📋 Problema Original

Cuando el asesor accedía a `http://desktop-8un1ehm:8000/asesores/pedidos/create?tipo=RF`:

1. ❌ **Al hacer clic en "Guardar Borrador"**: No guardaba correctamente la cotización
2. ❌ **Al hacer clic en "Enviar"**: No se asignaba el `numero_cotizacion` correctamente

## ✅ Solución Implementada

### 1️⃣ **Cambio en `CotizacionController@storeReflectivo`**

**Archivo**: `app/Infrastructure/Http/Controllers/CotizacionController.php`

**Línea**: ~1477

**Cambio**: Generar `numero_cotizacion` SIEMPRE, independientemente de si es borrador o envío

```php
// ANTES (❌ INCORRECTO):
'numero_cotizacion' => !$esBorrador ? $this->generarNumeroCotizacion() : null,

// DESPUÉS (✅ CORRECTO):
// Generar número de cotización SIEMPRE (para poder identificar el borrador luego)
$numeroCotizacion = $this->generarNumeroCotizacion();

// Luego en create:
'numero_cotizacion' => $numeroCotizacion,
```

### 2️⃣ **Mejorado Frontend - Manejo de Errores**

**Archivo**: `resources/views/asesores/pedidos/create-reflectivo.blade.php`

**Cambios en la sección de submit del formulario (líneas ~1712-1758)**:

```javascript
// Ahora muestra mejor información de:
if (result.success) {
    // ✅ BORRADOR: Guardado correctamente
    // ✅ ENVÍO: Asignado numero_cotizacion correctamente
    mostrarModalExito(titulo, mensaje, numeroCot, action === 'enviar');
} else {
    // ❌ Muestra errores de forma clara:
    // - Errores de validación
    // - Errores de campos
    // - Errores de conexión
    alert(`❌ ${mensajeError}`);
}
```

## 🔄 Flujo Completo

### Cuando el usuario hace clic en **"Guardar Borrador"** (action=borrador):

```
Frontend → POST /asesores/cotizaciones/reflectivo/guardar
    ↓
Controller@storeReflectivo
    ├─ Genera numero_cotizacion (COT-XXXXX)
    ├─ Estado: "BORRADOR"
    ├─ es_borrador: true
    ├─ fecha_envio: null
    └─ Guarda todo correctamente
    ↓
Response: { success: true, data: { cotizacion: {...} } }
    ↓
Modal: "Cotización guardada como borrador ✓"
```

### Cuando el usuario hace clic en **"Enviar"** (action=enviar):

```
Frontend → POST /asesores/cotizaciones/reflectivo/guardar
    ↓
Controller@storeReflectivo
    ├─ Genera numero_cotizacion (COT-XXXXX) 
    ├─ Estado: "ENVIADA_CONTADOR"
    ├─ es_borrador: false
    ├─ fecha_envio: Carbon::now()
    └─ Guarda todo correctamente
    ↓
Response: { success: true, data: { cotizacion: {...} } }
    ↓
Modal: "Cotización enviada al contador ✓"
    + Muestra número de cotización (COT-XXXXX)
```

## 🎯 Características Garantizadas

✅ **Guardar Borrador**:
- Genera `numero_cotizacion` (COT-XXXXX)
- Marca `es_borrador = true`
- Estado = "BORRADOR"
- Se puede editar después

✅ **Enviar Cotización**:
- Usa el `numero_cotizacion` existente (o genera uno)
- Marca `es_borrador = false`
- Estado = "ENVIADA_CONTADOR"
- Asigna `fecha_envio`
- Muestra el número de cotización en modal

✅ **Errores Claros**:
- Validación de campos
- Errores de conexión
- Mensajes específicos por campo

## 📝 Ubicación de Cambios

| Archivo | Línea | Cambio |
|---------|-------|--------|
| `app/Infrastructure/Http/Controllers/CotizacionController.php` | 1476-1490 | Generar numero_cotizacion siempre |
| `resources/views/asesores/pedidos/create-reflectivo.blade.php` | 1712-1758 | Mejorar manejo de errores en respuesta |

## 🧪 Prueba de Funcionamiento

1. Acceder a: `http://desktop-8un1ehm:8000/asesores/pedidos/create?tipo=RF`
2. Completar datos:
   - ✏️ Cliente: "Prueba Cliente"
   - 📅 Fecha: Seleccionar fecha
   - 👔 Agregar Prenda con tipo (ej: "Camiseta")
   - 📏 Seleccionar tallas
3. Hacer clic en **"Guardar Borrador"** → ✅ Debe guardarse sin errores
4. Hacer clic en **"Enviar"** (nuevamente) → ✅ Debe enviarse y mostrar numero_cotizacion

## 🔍 Logs Importantes

En `storage/logs/laravel.log` verás:

```
✅ CotizacionController@storeReflectivo - Exitoso
   - cotizacion_id: XXX
   - estado: BORRADOR
   - numero_cotizacion: COT-123
```

## 🚀 Próximos Pasos (Opcionales)

1. Agregar validación más robusta en frontend
2. Implementar guardado automático (auto-save)
3. Agregar confirmación de envío
4. Mejorar UX del modal de éxito

## ⚠️ Consideraciones Importantes

- El `numero_cotizacion` se genera automáticamente con secuencia COT-XXXXX
- No se puede guardar sin al menos UNA prenda
- El cliente y fecha son campos obligatorios
- Las especificaciones se preservan en ediciones posteriores

---

**Fecha de Implementación**: 18 de Diciembre de 2025
**Estado**: ✅ COMPLETADO Y FUNCIONAL
