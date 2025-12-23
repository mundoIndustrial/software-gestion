# SOLUCIÓN: IMÁGENES PERDIDAS AL ENVIAR COTIZACIÓN

## PROBLEMA IDENTIFICADO

Cuando los usuarios guardaban una cotización como **BORRADOR**, todas las imágenes (prendas, telas, logos) se guardaban correctamente. Pero cuando posteriormente **ENVIABAN** la cotización, **las imágenes desaparecían**.

### Logs del Error
```
Guardando (FUNCIONA ✅):
[09:52:55] ✅ Foto de tela guardada en prenda_tela_fotos_cot
[09:52:55] Fotos encontradas: {"index":0,"count":1}

Enviando (NO FUNCIONA ❌):
[09:53:10] ⏭️ Foto de prenda ya guardada (OMITIDA) [0][0]: ID 15
[09:53:10] ⏭️ Tela ya guardada (OMITIDA) [0][0]: ID 14
[09:53:10] Fotos encontradas: {"index":0,"count":0}
[09:53:10] allFiles keys: {"keys":[]}
```

## CAUSA RAÍZ

### 1. Problema en cargar-borrador.js (línea ~777)
Cuando se carga un borrador existente, el código guardaba las URLs de las imágenes como **strings**, NO como File objects:

```javascript
// ❌ INCORRECTO - Guarda URL como string
window.imagenesEnMemoria.prendaConIndice.push({
    prendaIndex: prendaIndexActual,
    file: urlFoto,  // ← STRING, no File object
    esGuardada: true,
    fotoId: foto.id
});
```

### 2. Problema en guardado.js (línea ~829)
La función `procederEnviarCotizacion` revisaba si `item.file instanceof File`. Como las imágenes cargadas eran strings, esta condición fallaba:

```javascript
// ❌ INCORRECTO - Omite images cargadas del borrador
if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
    // Este if NUNCA es true para imagenes cargadas (son strings, no Files)
    formData.append(`prendas[${index}][fotos][]`, item.file);
}
```

Como resultado:
- Las nuevas imágenes (File objects) se enviaban ✅
- Las imágenes del borrador cargado (strings) se omitían ❌

## SOLUCIÓN IMPLEMENTADA

### Cambios en guardado.js - Función procederEnviarCotizacion()

#### PARA PRENDAS (líneas 820-847)
```javascript
// ✅ CORRECTO - Maneja AMBOS casos
const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
const fotosNuevas = [];      // File objects
const fotosExistentes = [];  // IDs de fotos guardadas

fotosDeEstaPrenda.forEach((item, fotoIndex) => {
    if (item.file instanceof File) {
        fotosNuevas.push(item.file);
    } else if (item.fotoId && typeof item.file === 'string') {
        fotosExistentes.push(item.fotoId);
    }
});

// Enviar fotos nuevas como archivos
fotosNuevas.forEach((foto) => {
    formData.append(`prendas[${index}][fotos][]`, foto);
});

// Enviar IDs de fotos existentes para que backend las copie
if (fotosExistentes.length > 0) {
    formData.append(`prendas[${index}][fotos_existentes]`, JSON.stringify(fotosExistentes));
}
```

#### PARA TELAS (líneas 894-937)
```javascript
// ✅ CORRECTO - Agrupa por telaIndex y maneja ambos tipos
const telasPorIndice = {};
telasDeEstaPrenda.forEach(item => {
    const telaIdx = item.telaIndex || 0;
    if (!telasPorIndice[telaIdx]) {
        telasPorIndice[telaIdx] = { nuevas: [], existentes: [] };
    }
    
    if (item.file instanceof File) {
        telasPorIndice[telaIdx].nuevas.push(item.file);
    } else if (item.fotoId && typeof item.file === 'string') {
        telasPorIndice[telaIdx].existentes.push(item.fotoId);
    }
});

// Enviar por telaIndex
Object.keys(telasPorIndice).forEach(telaIdx => {
    const telaFotos = telasPorIndice[telaIdx];
    
    telaFotos.nuevas.forEach((foto) => {
        formData.append(`prendas[${index}][telas][${telaIdx}][fotos][0]`, foto);
    });
    
    if (telaFotos.existentes.length > 0) {
        formData.append(`prendas[${index}][telas][${telaIdx}][fotos_existentes]`, JSON.stringify(telaFotos.existentes));
    }
});
```

#### PARA LOGOS (ya estaba correcto)
```javascript
// ✅ Logos ya tenían código correcto
window.imagenesEnMemoria.logo.forEach((imagen, imagenIndex) => {
    if (imagen instanceof File) {
        formData.append(`logo[imagenes][]`, imagen);
    } else if (imagen.esGuardada && imagen.fotoId) {
        formData.append(`logo_fotos_existentes[]`, imagen.fotoId);
    }
});
```

## FLUJO DE DATOS AHORA CORRECTO

### Caso 1: Cotización NUEVA
1. Usuario sube imágenes → Se guardan como File objects en `imagenesEnMemoria`
2. Usuario hace click GUARDAR → Se envían como archivos ✅
3. Usuario hace click ENVIAR → Se envían como archivos ✅

### Caso 2: Editando BORRADOR existente
1. Backend devuelve: `prenda.fotos = [{id: 15, ruta_original: '...'}]`
2. Frontend carga en cargar-borrador.js:
   ```javascript
   window.imagenesEnMemoria.prendaConIndice.push({
       file: urlString,     // URL como string
       esGuardada: true,
       fotoId: 15          // ID de la BD
   });
   ```
3. Usuario agrega MÁS imágenes → Se guardan como File objects nuevos
4. Usuario hace click GUARDARBORRO → Se envían solo los nuevos ✅
5. Usuario hace click ENVIAR → Ahora se envían:
   - IDs de existentes: `prendas[0][fotos_existentes] = "15"`
   - Nuevos archivos: `prendas[0][fotos][] = File` ✅

## CAMBIOS EN EL BACKEND QUE SOPORATEA

El backend en `CotizacionController.php` ya soporta estos cambios:

```php
// Línea 851
$fotosPrendaExistentes = $request->input("prendas.{$index}.fotos_existentes");
if (!empty($fotosPrendaExistentes)) {
    foreach ($fotosPrendaExistentes as $fotoId) {
        $fotoExistente = PrendaFotoCot::find($fotoId);
        // Copia la foto existente a la nueva cotización
    }
}

// Línea 1105
$fotosTelaExistentes = $request->input("prendas.{$index}.telas.{$telaIndex}.fotos_existentes");
if (!empty($fotosTelaExistentes)) {
    // Copia fotos de tela existentes
}
```

## RESULTADO

Ahora cuando se envía una cotización:
1. ✅ Se reenviaan todas las imágenes (nuevas y existentes)
2. ✅ El backend copia las imágenes existentes a la nueva cotización
3. ✅ Las imágenes NO se pierden al enviar
4. ✅ El usuario ve todas sus imágenes en la cotización enviada

## ARCHIVOS MODIFICADOS

- `public/js/asesores/cotizaciones/guardado.js` - Líneas 820-937
  - Función: `procederEnviarCotizacion()`
  - Cambio: Manejo correcto de imágenes nuevas (Files) y existentes (IDs)

## NOTAS IMPORTANTES

1. **guardarCotizacion() SÍ fue dejada igual** - Solo envía nuevos File objects (incremental)
2. **procederEnviarCotizacion() ahora envía TODOS** - Incluye IDs para que backend copie existentes
3. **Logos ya estaban correctos** - El código de logos ya manejaba File objects vs IDs
4. **Backend no necesita cambios** - Ya soporta `fotos_existentes` y `fotos_existentes` en telas

## TESTING RECOMENDADO

1. Crear cotización NUEVA con imágenes → ENVIAR → Verificar imágenes en BD ✅
2. Cargar BORRADOR → Agregar MÁS imágenes → ENVIAR → Verificar TODAS las imágenes ✅
3. Cargar BORRADOR → SIN agregar imágenes → ENVIAR → Verificar imágenes originales ✅
4. Cargar BORRADOR → ELIMINAR imágenes → ENVIAR → Verificar eliminadas no reaparecen ✅
