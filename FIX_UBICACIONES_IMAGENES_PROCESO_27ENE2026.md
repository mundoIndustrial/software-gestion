# ✅ FIX: Ubicaciones e Imágenes de Procesos No Se Guardaban

## 🐛 PROBLEMA
Cuando se editaba un proceso existente en una prenda, las **ubicaciones** y las **imágenes** NO se guardaban en la base de datos. El servidor recibía un PATCH vacío:

```
[PROCESOS-ACTUALIZAR-PATCH] Recibido PATCH {
  "prenda_id": 3472,
  "proceso_id": 113,
  "request_keys": [],  // ← VACÍO
  "ubicaciones": null,
  "observaciones": null
}
```

## 🔍 ROOT CAUSE

### Problema 1: FormData No Incluía Datos Vacíos
En [modal-novedad-edicion.js](public/js/componentes/modal-novedad-edicion.js) línea ~465, el código solo añadía ubicaciones si `procesoEditado.cambios.ubicaciones` era truthy:

```javascript
// ANTES (INCORRECTO):
if (procesoEditado.cambios.ubicaciones) {
    patchFormData.append('ubicaciones', JSON.stringify(procesoEditado.cambios.ubicaciones));
}
```

Pero `procesoEditado.cambios` era un objeto **vacío** porque los métodos `registrarCambioUbicaciones()` nunca fueron llamados correctamente.

### Problema 2: Detección de Cambios Fallaba
El código detectaba "sin cambios" y saltaba el PATCH completamente:

```javascript
// ANTES (INCORRECTO):
const hayAlgunCambio = tieneCambiosOtros || tieneImagenesNuevas || tieneImagenesExistentes;
// ↑ No incluía ubicaciones/observaciones actuales
```

## ✅ SOLUCIÓN IMPLEMENTADA

### Fix 1: Fallback a Datos Actuales (Línea ~465)
```javascript
// AHORA (CORRECTO):
const ubicacionesAEnviar = procesoEditado.cambios.ubicaciones || 
                           window.ubicacionesProcesoSeleccionadas || 
                           [];
if (ubicacionesAEnviar && ubicacionesAEnviar.length > 0) {
    patchFormData.append('ubicaciones', JSON.stringify(ubicacionesAEnviar));
}

const observacionesAEnviar = procesoEditado.cambios.observaciones || 
                             (obsTextarea?.value) || 
                             '';
if (observacionesAEnviar) {
    patchFormData.append('observaciones', observacionesAEnviar);
}
```

**Ventajas:**
- ✅ Incluye ubicaciones de `window.ubicacionesProcesoSeleccionadas`
- ✅ Incluye observaciones del DOM textarea
- ✅ Usa fallback si `cambios` está vacío

### Fix 2: Mejorar Detección de Cambios (Línea ~443)
```javascript
// AHORA (CORRECTO):
const tieneUbicacionesActuales = window.ubicacionesProcesoSeleccionadas?.length > 0;
const tieneObservacionesActuales = obsTextarea?.value?.trim?.() ? true : false;

const hayAlgunCambio = tieneCambiosOtros || 
                       tieneImagenesNuevas || 
                       tieneImagenesExistentes || 
                       tieneUbicacionesActuales ||          // ← NUEVO
                       tieneObservacionesActuales;          // ← NUEVO
```

**Ventajas:**
- ✅ Detecta ubicaciones actuales aunque no haya "cambios"
- ✅ Detecta observaciones aunque no haya "cambios"
- ✅ Nunca salta el PATCH si hay datos para enviar

## 📝 ARCHIVOS MODIFICADOS

- **[modal-novedad-edicion.js](public/js/componentes/modal-novedad-edicion.js)**
  - Línea ~443: Mejorada detección de cambios
  - Línea ~465-495: Fallback a datos actuales en FormData

## 🧪 VERIFICACIÓN

Para verificar que el fix funciona:

1. Abrir un pedido en edición
2. Editar una prenda con procesos existentes
3. Hacer clic en editar el proceso
4. **Importante:** Las ubicaciones y observaciones ya deberían estar cargadas
5. Cerrar el modal y guardar la prenda
6. **Verificar en el log:** Debe mostrar las ubicaciones siendo enviadas:
   ```
   [modal-novedad-edicion] 📍 Ubicaciones añadidas al PATCH: ['pecho', 'espalda']
   [modal-novedad-edicion] 📝 Observaciones añadidas al PATCH: "Comentario del proceso"
   ```

7. **Verificar en la BD:** `pedidos_procesos_prenda_detalles.ubicaciones` debe contener JSON con las ubicaciones
8. **Verificar en la BD:** `pedidos_procesos_imagenes` debe contener las imágenes del proceso

## 🎯 RESULTADO ESPERADO

Después del fix, cuando se edita un proceso:

```
[PROCESOS-ACTUALIZAR-PATCH] Recibido PATCH {
  "prenda_id": 3472,
  "proceso_id": 113,
  "request_keys": ["ubicaciones", "observaciones"],  // ← AHORA TIENE DATOS
  "ubicaciones": ["pecho", "espalda"],                // ← SE ENVÍA
  "observaciones": "Comentario del proceso"           // ← SE ENVÍA
}
```

Y en la BD:
- ✅ `ubicaciones` = `["pecho", "espalda"]` (JSON)
- ✅ Imágenes guardadas en `pedidos_procesos_imagenes`

## 💡 NOTAS TÉCNICAS

- **Sin cambios a backend:** El controlador PHP ya estaba correctamente implementado
- **Fallback inteligente:** Si `cambios` está vacío, usa valores actuales del DOM
- **Detección mejorada:** No salta el PATCH si hay ubicaciones u observaciones
- **Compatibilidad:** Mantiene compatibilidad con imágenes existentes y nuevas
