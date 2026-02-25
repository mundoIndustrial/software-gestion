# Fix: Content Security Policy (CSP) Error con Blob URLs en Producción

## Problema Reportado

En **producción**, cuando se intenta guardar una cotización EPP, ocurre el siguiente error:

```
Connecting to 'blob:https://sistemamundoindustrial.online/...' violates the following 
Content Security Policy directive: "connect-src 'self' https://fonts.googleapis.com 
https://cdn.jsdelivr.net https://cdnjs.cloudflare.com ws: wss: https:". 
The action has been blocked.
```

**Síntoma**: Las imágenes no se guardan en producción, pero sí se guardan en local.

## Causa Root

El código estaba intentando hacer `fetch()` a URLs blob (blob:https://...) para convertirlas a objetos File para enviar al servidor. Los blob URLs no podem ser fetched por razones de seguridad del navegador cuando el Content Security Policy (CSP) no permite `blob:` en la directiva `connect-src`.

**En Local**: Probablemente se está usando HTTP (no HTTPS), o el CSP es más permisivo.
**En Producción**: El CSP está correctamente configurado y es más restrictivo.

## Solución Implementada

### 1. **Priorizar el uso del objeto File existente** 

En lugar de:
```javascript
// ❌ INCORRECTO - Viola CSP
fetch(blobUrl) → blob → convertir a File
```

Ahora hace:
```javascript
// ✅ CORRECTO - Sin violación CSP
if (imagen.file) {
    return imagen.file; // Usar el File guardado en window.fotosEPP
}
```

### 2. **No hacer fetch a blob URLs**

La función `convertirImagenAFile()` ahora:

- **Opción 1**: Si existe `img.file` (objeto File original), lo retorna sin fetch
- **Opción 2**: Si es DataURL (`data:`), hace fetch (permitido por CSP)
- **Opción 3**: Si es URL normal (http/https/relativa), hace fetch (permitido)
- **Opción 4**: Si es blob URL sin file object, retorna `null` y se salta (evita CSP)

### 3. **Separar imágenes nuevas de imágenes existentes**

En `imagenes_keep`, ahora se:
- **Ignoran** imágenes con file object (son nuevas, se subirán como archivos)
- **Mantienen** imágenes existentes con URLs válidas (no blob)
- **Logs** detallados de qué se mantiene y qué no

## Código Actualizado

### Función `convertirImagenAFile()` - create.blade.php línea 747

```javascript
async function convertirImagenAFile(img, fallbackName = 'epp_imagen.webp') {
    try {
        if (!img) return null;

        // PRIORIDAD 1: Si ya es un File object, retornarlo directamente
        if (img instanceof File) {
            return img;
        }

        // PRIORIDAD 2: Si tiene un file object guardado (desde window.fotosEPP), usarlo sin fetch
        if (img?.file && img.file instanceof File) {
            console.log(`[convertirImagenAFile] Usando File object guardado: ${img.nombre}`);
            return img.file; // ← SIN FETCH - Evita CSP violation
        }

        // ... resto del código mira DataURLs, URLs HTTP, etc.
        
        // PRIORIDAD 4: Ignorar blob URLs sin file object (evita CSP)
        if (src.startsWith('blob:')) {
            console.warn(`[convertirImagenAFile] Blob URL detectado sin File object. Ignorando...`);
            return null; // ← Retorna null en lugar de hacer fetch
        }
    }
}
```

### Manejo de `imagenes_keep` - create.blade.php línea 900

```javascript
imagenes_keep: (() => {
    const keep = [];
    for (const im of imgs) {
        // Ignorar imágenes nuevas (con file object)
        if (im?.file && im.file instanceof File) {
            continue; // Serán subidas como archivos nuevos
        }
        
        // Ignorar blob URLs sin file object
        if (src.startsWith('blob:')) {
            continue; // No pueden ser referenciadas después
        }
        
        // Mantener URLs reales
        if (src.includes('/storage/') || src.startsWith('http')) {
            keep.push(src); // Se conservan en la BD
        }
    }
    return keep;
})()
```

## Flujo de Guardado Ahora

### Imágenes Nuevas (con file object)
1. ✅ Detectadas en `window.fotosEPP` ($file: File)
2. ✅ Extraídas sin fetch (usan el File object original)
3. ✅ Subidas como FormData['items[idx][imagenes][]']
4. ✅ Guardadas en storage

### Imágenes Existentes (sin cambios)
1. ✅ Detectadas en `epp.imagenes` (sin file object)
2. ✅ Extraídas de `imagenes_keep` (URLs reales)
3. ✅ Reenviadas al backend via `imagenes_keep` payload
4. ✅ Asociadas al EPP (sin resubir)

### Imágenes en Edición (blob temporales sin file object)
1. ⚠️ Detectadas con blob URL
2. ✅ Ignoradas en fetch (evita CSP)
3. ✅ No se guardan si no tienen file object
4. ℹ️ Usuario debe reseleccionar si desea cambiar

## Testing Recomendado

```
1. Local (HTTP):
   - Agregar EPP con imágenes → Verificar que guarda
   - Editar EPP existente → Verificar que mantiene imágenes
   
2. Producción (HTTPS con CSP):
   - Agregar EPP con imágenes → Verificar sin error CSP
   - Editar EPP existente → Verificar que no pierde imágenes
   - Cambiar imágenes en edición → Verificar que actualiza
```

## Console Logs para Debugging

Buscar en Console del navegador:
- `[convertirImagenAFile] Usando File object guardado:` ← OK, usando file original
- `[convertirImagenAFile] Blob URL detectado sin File object` ← Advertencia, imagen ignorada
- `[itemsPayload] Imagen a mantener:` ← Imágenes que se conservan

## Archivos Modificados

- ✅ [create.blade.php](create.blade.php#L747) - Función `convertirImagenAFile()` actualizada
- ✅ [create.blade.php](create.blade.php#L900) - Lógica `imagenes_keep` mejorada
