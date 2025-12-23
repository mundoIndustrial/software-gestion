# SOLUCIÓN: PRESERVAR IMÁGENES AL GUARDAR BORRADOR MÚLTIPLES VECES

## PROBLEMA

Cuando el usuario:
1. Carga un borrador con imágenes existentes
2. Guarda el borrador varias veces (5+ veces) SIN agregar nuevas imágenes
3. Las imágenes DESAPARECÍAN porque no se estaban reenviando sus IDs

## CAUSA RAÍZ

La función `guardarCotizacion()` NO estaba enviando los IDs de las imágenes existentes al backend. Solo enviaba nuevos File objects, ignorando las imágenes que ya estaban guardadas (almacenadas como URLs strings).

Cuando el backend recibía la solicitud SIN los IDs de fotos existentes, asumía que NO había fotos para esa prenda y las eliminaba.

## SOLUCIÓN IMPLEMENTADA

### 1. Actualizar `guardarCotizacion()` - Prendas (líneas 247-270)

**ANTES (incorrecto):**
```javascript
// ❌ Solo enviaba archivos nuevos (File objects)
if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
    formData.append(`prendas[${index}][fotos][]`, item.file);
} else if (esEdicion && item.esGuardada) {
    console.log(`⏭️ Foto ya guardada (OMITIDA)`); // Se omitían!
}
```

**DESPUÉS (correcto):**
```javascript
// ✅ Envía AMBOS tipos: nuevos archivos + IDs de existentes
const fotosNuevas = [];
const fotosExistentes = [];

fotosDeEstaPrenda.forEach((item) => {
    if (item.file instanceof File) {
        fotosNuevas.push(item.file); // Archivo nuevo
    } else if (item.fotoId && typeof item.file === 'string') {
        fotosExistentes.push(item.fotoId); // ID del archivo guardado
    }
});

// Enviar archivos nuevos
fotosNuevas.forEach((foto) => {
    formData.append(`prendas[${index}][fotos][]`, foto);
});

// Enviar IDs de existentes
if (fotosExistentes.length > 0) {
    formData.append(`prendas[${index}][fotos_existentes]`, JSON.stringify(fotosExistentes));
}
```

### 2. Actualizar `guardarCotizacion()` - Telas (líneas 322-350)

**Mismo cambio:** Ahora agrupa por `telaIndex` y envía:
- `prendas[${index}][telas][${telaIdx}][fotos][0]` - Archivos nuevos
- `prendas[${index}][telas][${telaIdx}][fotos_existentes]` - IDs de existentes

### 3. Actualizar `guardarCotizacion()` - Logos (líneas 371-394)

**ANTES:**
```javascript
// ❌ Solo enviaba File objects nuevos
if (imagen instanceof File) {
    formData.append(`logo[imagenes][${imagenIndex}]`, imagen);
}
```

**DESPUÉS:**
```javascript
// ✅ Envía archivos nuevos + IDs de existentes
const logosNuevos = [];
const logosExistentes = [];

window.imagenesEnMemoria.logo.forEach((imagen) => {
    if (imagen instanceof File) {
        logosNuevos.push(imagen);
    } else if (imagen.fotoId && typeof imagen.ruta === 'string') {
        logosExistentes.push(imagen.fotoId);
    }
});

logosNuevos.forEach((imagen) => {
    formData.append(`logo[imagenes][]`, imagen);
});

if (logosExistentes.length > 0) {
    formData.append(`logo_fotos_existentes`, JSON.stringify(logosExistentes));
}
```

## FLUJO COMPLETO AHORA CORRECTO

### Escenario: Guardar Borrador Múltiples Veces

**Paso 1: Cargar un borrador existente**
```
Backend devuelve: prenda.fotos = [{id: 15, ruta_webp: '/storage/...'}]
Frontend almacena en memoria:
{
  file: "/storage/...",  // URL string (no File object)
  fotoId: 15,
  esGuardada: true
}
```

**Paso 2: Usuario GUARDA sin agregar nuevas imágenes**
```
guardarCotizacion() ahora:
1. Detecta: file es string → lo categoriza como EXISTENTE
2. Recolecta ID: fotosExistentes = [15]
3. Envía: prendas[0][fotos_existentes] = "[15]"
4. Backend recibe el ID y PRESERVA la foto ✅
```

**Paso 3: Usuario GUARDA NUEVAMENTE (sin cambios)**
```
Mismo proceso - Las fotos se mantienen ✅
```

**Paso 4: Usuario AGREGA una nueva imagen**
```
Nueva imagen: file = File object
guardarCotizacion() ahora envía:
- prendas[0][fotos][] = [File object nuevo]      ← Nueva
- prendas[0][fotos_existentes] = "[15]"          ← Existente preservada
```

**Paso 5: Usuario hace ENVIAR la cotización**
```
procederEnviarCotizacion() envía IGUAL:
- prendas[0][fotos][] = File objects nuevos
- prendas[0][fotos_existentes] = IDs existentes
El backend copia las existentes a la nueva cotización ✅
```

## DATOS ENVIADOS AL BACKEND

### Guardando borrador (guardarCotizacion):

```
tipo: "borrador"
accion: "guardar"
es_borrador: "1"
prendas[0][fotos][]: [File objects nuevos]
prendas[0][fotos_existentes]: JSON.stringify([15, 16])
prendas[0][telas][0][fotos][0]: [File nuevos]
prendas[0][telas][0][fotos_existentes]: JSON.stringify([14])
logo[imagenes][]: [File nuevos]
logo_fotos_existentes: JSON.stringify([50])
```

### Enviando cotización (procederEnviarCotizacion):

```
tipo: "enviada"
accion: "enviar"
es_borrador: "0"
prendas[0][fotos][]: [File objects nuevos]
prendas[0][fotos_existentes]: JSON.stringify([15, 16])
prendas[0][telas][0][fotos][0]: [File nuevos]
prendas[0][telas][0][fotos_existentes]: JSON.stringify([14])
logo[imagenes][]: [File nuevos]
logo_fotos_existentes: JSON.stringify([50])
```

## COMPATIBILIDAD CON BACKEND

El backend en `CotizacionController.php` ya soporta estos campos:

- Línea 851: `fotos_existentes` para prendas ✅
- Línea 1105: `fotos_existentes` para telas ✅  
- Línea 1341: `logo_fotos_existentes` para logos ✅

El backend:
1. Recibe los IDs de fotos existentes
2. Busca las fotos en la BD por ID
3. Copia la información (ruta, etc.) a la nueva cotización
4. Preserva las fotos existentes

## ARCHIVOS MODIFICADOS

- `public/js/asesores/cotizaciones/guardado.js`
  - Líneas 247-270: Prendas en `guardarCotizacion()`
  - Líneas 322-350: Telas en `guardarCotizacion()`
  - Líneas 371-394: Logos en `guardarCotizacion()`
  - Líneas 845-872: Prendas en `procederEnviarCotizacion()`
  - Líneas 900-945: Telas en `procederEnviarCotizacion()`

## TESTING RECOMENDADO

1. ✅ **Guardar nuevo borrador con imágenes** → Verificar imágenes en BD
2. ✅ **Cargar borrador existente** → Guardar sin cambios → Imágenes se preservan
3. ✅ **Cargar borrador → GUARDAR 5 veces sin cambios** → Imágenes persisten
4. ✅ **Cargar borrador → Agregar nuevas imágenes → GUARDAR** → Todas las imágenes existen
5. ✅ **Cargar borrador → ENVIAR sin cambios** → Imágenes en cotización enviada
6. ✅ **Cargar borrador → Agregar imágenes → ENVIAR** → Todas persisten en enviada
7. ✅ **Cargar borrador → Eliminar imágenes → GUARDAR** → Eliminadas no reaparecen

## NOTAS IMPORTANTES

1. **Los cambios en `guardarCotizacion()` y `procederEnviarCotizacion()` ahora son IDÉNTICOS en lógica**
   - Ambos envían IDs de existentes para preservar
   - La diferencia es solo en qué campos se envían (`tipo` y `accion`)

2. **Las imágenes ahora se COPIAN, no se MUEVEN**
   - El backend puede reutilizar la misma foto en múltiples cotizaciones
   - No hay duplicación de archivos en storage

3. **Performance**
   - Solo se envían nuevos archivos (File objects) que son pesados
   - Los IDs de existentes son muy ligeros (JSON array de números)

4. **Seguridad**
   - Los IDs de fotos se validan en el backend (no se aceptan IDs aleatorios)
   - Solo se copian fotos que pertenecen a cotizaciones del usuario
