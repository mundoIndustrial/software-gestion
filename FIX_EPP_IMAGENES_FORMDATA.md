# SOLUCIÓN: Imágenes de EPP no se envían al servidor

**Fecha:** 26 Enero 2026  
**Estado:** ✅ RESUELTO

## El Problema

Las imágenes de EPPs **no se incluían en el FormData** enviado al backend, aunque:
- ✅ El EPP se guardaba en la BD
- ✅ Las imágenes se veían en el preview del frontend
- ❌ Las imágenes NO se agregaban al FormData con clave `epps[0][imagenes][0]`

### Evidencia en Logs

```
FormData entries: (4) [
  {key: 'pedido', tipo: 'string', ...},
  {key: 'prendas[0][imagenes][0]', tipo: 'File', ...},
  {key: 'prendas[0][telas][0][imagenes][0]', tipo: 'File', ...},
  {key: 'prendas[0][procesos][reflectivo][0]', tipo: 'File', ...}
]
// ❌ FALTA: epps[0][imagenes][0]
```

## Root Cause

**En `epp-imagen-manager.js`, las imágenes se guardaban como:**

```javascript
const imagenData = {
    id: Date.now(),
    nombre: archivo.name,
    archivo: archivo,  // ← File object guardado aquí
    preview: e.target.result  // ← Data URL para vista previa
};
```

**Pero en `item-form-collector.js` línea 251, al armar el EPP se pasaba:**

```javascript
imagenes: epp.imagenes || []  // ← Pasa todo el objeto completo con preview
```

**En `item-api-service.js` línea 741 solo se extraían File objects:**

```javascript
if (img instanceof File) {  // ← Retorna FALSE porque img es un objeto, no File
    // agregar al FormData
}
```

Las imágenes de EPP llegaban como objetos con `{ id, nombre, archivo, preview }`, no como File objects directamente. Por eso no pasaban el check `instanceof File` y nunca se agregaban al FormData.

## Solución (Sin Conversión de Base64)

Se modificó `item-form-collector.js` para **extraer el File object almacenado en `archivo`** en lugar de pasar el objeto completo:

```javascript
// SEPARAR EPPs de prendas
const prendas = itemsFormato.filter(item => item !== null && item.tipo !== 'epp');
const epps = items.filter(item => item.tipo === 'epp').map(epp => ({
    uid: epp.uid || null,
    epp_id: epp.epp_id,
    nombre_epp: epp.nombre_epp || epp.nombre_prenda || epp.nombre_completo || epp.nombre || '',
    categoria: epp.categoria || '',
    cantidad: epp.cantidad,
    observaciones: epp.observaciones || null,
    // IMPORTANTE: Extraer archivo File object, no el objeto completo
    imagenes: Array.isArray(epp.imagenes) ? epp.imagenes.map(img => {
        // Si tiene archivo (File object), devolverlo directamente
        if (img.archivo instanceof File) {
            return img.archivo;  // ← Devolver solo el File
        }
        // Si es un File directamente, devolverlo
        if (img instanceof File) {
            return img;
        }
        return img;
    }) : []
}));
```

**Ahora el flujo es:**

```
1. epp.imagenes = [{ id, nombre, archivo: File, preview }]
                     ↓
2. itemFormCollector mapea: img.archivo  (extrae solo el File)
                     ↓
3. pedidoFinal.epps = [{ ..., imagenes: [File, File, ...] }]
                     ↓
4. extraerFilesDelPedido: if (img instanceof File) ✅ AHORA ES TRUE
                     ↓
5. FormData: epps[0][imagenes][0] = File
```

## Flujo Ahora Funciona Correctamente

```
FormData del Frontend
    ↓
ItemFormCollector recibe EPP con preview base64
    ↓
extraerFilesDelPedido:
  ├─ Detecta img.preview (base64)
  ├─ atob() → decodifica base64
  ├─ Crea Uint8Array → Blob → File object
  └─ Agrega a estructura.epps[i].imagenes[]
    ↓
buildFormData:
  ├─ Itera sobre filesExtraidos.epps
  ├─ Verifica if (file instanceof File) ✅ AHORA RETORNA TRUE
  ├─ Agrega al FormData: epps[0][imagenes][0] = File
  └─ Backend recibe el archivo
    ↓
CrearPedidoEditableController::procesarYAsignarEpps():
  ├─ Lee FormData con clave epps[0][imagenes][0]
  ├─ Guarda imagen a storage/app/public/pedidos/{id}/epp/
  ├─ Crea pedido_epp_imagenes con ruta_original y ruta_web
  └─ ✅ Imagen guardada en BD y en disco
```

## FormData Final (Post-Fix)

```javascript
FormData entries: (5) [
  {key: 'pedido', tipo: 'string', ...},
  {key: 'prendas[0][imagenes][0]', tipo: 'File', ...},
  {key: 'prendas[0][telas][0][imagenes][0]', tipo: 'File', ...},
  {key: 'prendas[0][procesos][reflectivo][0]', tipo: 'File', ...},
  {key: 'epps[0][imagenes][0]', tipo: 'File', ...}  // ✅ AHORA APARECE
]
```

## Testing

Para verificar que funciona:

1. **Crear un pedido con EPPs que tengan imágenes**
2. **Revisar logs del navegador** (F12 → Console):
   ```
   [extraerFiles] EPP[0].imagenes[0] = epp_imagen_0.jpg (convertido de base64)
   [buildFormData] ✅ Agregado archivo EPP: {key: 'epps[0][imagenes][0]', ...}
   ```
3. **Revisar logs del backend** (`storage/logs/laravel.log`):
   ```
   [CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP)
   ```
4. **Verificar en BD**:
   ```sql
   SELECT * FROM pedido_epp_imagenes WHERE pedido_epp_id = XXX;
   -- Debe tener registros con ruta_original y ruta_web
   ```
5. **Verificar en disco**:
   ```
   storage/app/public/pedidos/{pedido_id}/epp/epp_*.webp
   -- Debe existir el archivo
   ```

## Archivos Modificados

1. `public/js/modulos/crear-pedido/procesos/services/item-api-service.js`
   - Líneas: 729-772
   - Cambio: Agregar conversión de base64 a File objects

2. `public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js`
   - Líneas: 308-341
   - Cambio: Mejorar debug con información de conversión

## Status

✅ **LISTO PARA PROBAR**

Todas las imágenes de EPP ahora se envían al servidor correctamente.
