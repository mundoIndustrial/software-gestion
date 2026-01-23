# ANÁLISIS: DATOS QUE NO SE ESTÁN GUARDANDO EN EDICIÓN DE PRENDAS

**Fecha**: 2026-01-23  
**Evento**: POST `/asesores/pedidos/2700/actualizar-prenda`  
**Log URL**: Line 14:47:01

---

## PROBLEMAS IDENTIFICADOS

### 1. ❌ **MANGA NO SE GUARDA**
- **Valor en log**: `"tipo_manga_id":null`
- **Debe ser**: `"tipo_manga_id":1` (o algún ID de tipo de manga)
- **Causa Root**: El campo `manga-input` no está siendo seleccionado en el formulario

### 2. ❌ **TALLAS NO SE GUARDAN**
- **Valor en log**: `"cantidad_talla": "{}"`
- **Debe ser**: `"cantidad_talla": "{\"DAMA\":{\"S\":5,\"M\":3}}"` (con tallas y cantidades)
- **Causa Root**: `window.tallasRelacionales` está vacío cuando se envía el formulario

### 3. ❌ **FOTOS DE TELA NO SE GUARDAN**
- **Valor en log**: No aparecen en los datos enviados
- **Debe ser**: Array de File objects con clave `fotos_tela` o similar
- **Causa Root**: No se capturan en el FormData

### 4. ❌ **FOTOS DE PRENDA NO SE GUARDAN**
- **Valor en log**: No aparecen en los datos enviados
- **Debe ser**: Array de File objects con clave `imagenes`
- **Causa Root**: No se capturan en el FormData

### 5. ⚠️ **TELAS: Parcialmente guardadas**
- Las telas se envían vía `telasAgregadas` (después de la corrección anterior)
- Pero las **FOTOS DE LAS TELAS** no se incluyen en el envío

---

## FLUJO ACTUAL DE CAPTURA DE DATOS

### Archivo: `public/js/componentes/prenda-form-collector.js`

**Línea 82-86**: Inicializa estructura base
```javascript
const prendaData = {
    cantidad_talla: window.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {} },
    variantes: {}
}
```

**PROBLEMA**: Si `window.tallasRelacionales` está vacío, envía `{}`

---

## ARCHIVOS IMPLICADOS Y FLUJO CORRECTO

### 1. TALLAS - Flujo Completo

**Inicialización** (gestion-tallas.js:16):
```javascript
window.tallasRelacionales = {
    DAMA: {},      // { S: 5, M: 3 }
    CABALLERO: {}  // { 32: 10, 34: 8 }
}
```

**Llenado** (gestion-tallas.js):
- Se llena cuando usuario clickea "DAMA" o "CABALLERO"
- Se llena cuando usuario selecciona tallas y cantidades
- Se llama a `abrirModalSeleccionarTallas('dama')`
- Se llama a `actualizarCantidadTalla()` para cada cantidad

**Captura en formulario** (prenda-form-collector.js:82):
- Copia de `window.tallasRelacionales` al objeto `prendaData`
- Se envía como JSON string en FormData

**FALLO EN EDICIÓN**: Cuando se abre el modal de edición, `window.tallasRelacionales` se reinicia PERO NO SE CARGA CON LAS TALLAS ANTERIORES

### 2. MANGA - Flujo Completo

**Captura** (prenda-form-collector.js:210-215):
```javascript
const checkManga = document.getElementById('aplica-manga');
if (checkManga && checkManga.checked) {
    const mangaInput = document.getElementById('manga-input');
    variantes.manga = mangaInput?.value || '';
```

**PROBLEMA**: `manga-input` es un SELECT pero su value contiene STRING ('CORTA', 'LARGA', etc.)
- Debe convertir a `tipo_manga_id` (número ID)
- Similar a lo que ya se hace con broche: `"broche" → tipo_broche_boton_id = 1`

### 3. FOTOS DE PRENDA - Flujo Actual

**Inicialización** (prenda-form-collector.js:52-67):
```javascript
const imagenesTemporales = window.imagenesPrendaStorage?.obtenerImagenes?.() || [];
const imagenesCopia = imagenesTemporales.map(img => {
    if (img instanceof File) return img;
    if (img && img.file instanceof File) return img.file;
    if (img && img.previewUrl && !img.file) return null;
    return img;
}).filter(img => img !== null && img instanceof File);
```

**PROBLEMA**: Se capturan pero NO se agregan al FormData en `modal-novedad-edicion.js`

### 4. FOTOS DE TELA - Flujo Actual

**Inicialización** (prenda-form-collector.js:119-137):
```javascript
prendaData.telasAgregadas = window.telasAgregadas.map((tela, telaIdx) => {
    const imagenesCopia = (tela.imagenes || []).map(img => {
        if (img instanceof File) return img;
        if (img && img.file instanceof File) return img.file;
        return null;
    }).filter(img => img !== null && img instanceof File);
    
    return {
        tela: tela.tela || '',
        imagenes: imagenesCopia  // <-- AQUÍ ESTÁN!
    };
});
```

**PROBLEMA**: Se capturan pero NO se agregan al FormData en `modal-novedad-edicion.js`

---

## RAÍZ DEL PROBLEMA: `modal-novedad-edicion.js`

**Ubicación**: `public/js/componentes/modal-novedad-edicion.js` (línea ~101)

**Código actual**:
```javascript
formData.append('cantidad_talla', JSON.stringify(this.prendaData.cantidad_talla || {}));
formData.append('procesos', JSON.stringify(this.prendaData.procesos || {}));
// ... agregar variantes ...
```

**FALTA**:
1. NO agrega `imagenes` (fotos de prenda)
2. NO agrega `telasAgregadas` con sus `imagenes` (fotos de tela)
3. NO convierte `tela.imagenes` (Array de Files) correctamente

---

## CORRECCIONES NECESARIAS

### Corrección 1: Capturar y enviar FOTOS DE PRENDA

En `modal-novedad-edicion.js` (línea ~101), agregar:

```javascript
// Agregar imágenes de prenda
if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
    this.prendaData.imagenes.forEach((img, idx) => {
        if (img instanceof File) {
            formData.append(`imagenes[${idx}]`, img);
        }
    });
}
```

### Corrección 2: Capturar y enviar FOTOS DE TELA

En `modal-novedad-edicion.js` (línea ~101), agregar:

```javascript
// Agregar telas con imágenes
if (this.prendaData.telasAgregadas && Array.isArray(this.prendaData.telasAgregadas)) {
    this.prendaData.telasAgregadas.forEach((tela, telaIdx) => {
        formData.append(`telas[${telaIdx}][tela]`, tela.tela);
        formData.append(`telas[${telaIdx}][color]`, tela.color);
        formData.append(`telas[${telaIdx}][referencia]`, tela.referencia);
        
        // Agregar imágenes de tela
        if (tela.imagenes && Array.isArray(tela.imagenes)) {
            tela.imagenes.forEach((img, imgIdx) => {
                if (img instanceof File) {
                    formData.append(`telas[${telaIdx}][imagenes][${imgIdx}]`, img);
                }
            });
        }
    });
}
```

### Corrección 3: MANGA - Convertir STRING a tipo_manga_id

En `prenda-form-collector.js` (línea 210-215), reemplazar:

```javascript
// ANTES:
const checkManga = document.getElementById('aplica-manga');
if (checkManga && checkManga.checked) {
    const mangaInput = document.getElementById('manga-input');
    variantes.manga = mangaInput?.value || '';
    variantes.obs_manga = mangaObs?.value || '';
}

// DESPUÉS:
const checkManga = document.getElementById('aplica-manga');
if (checkManga && checkManga.checked) {
    const mangaInput = document.getElementById('manga-input');
    const mangaObs = document.getElementById('manga-obs');
    variantes.manga = mangaInput?.value || '';
    variantes.obs_manga = mangaObs?.value || '';
    
    // Mapear valor del select a tipo_manga_id
    // manga-input contiene: "CORTA" → ID ?, "LARGA" → ID ?, etc.
    const mangaValor = mangaInput?.value?.toUpperCase() || '';
    
    // NECESARIO: Verificar en BD qué IDs corresponden a CORTA/LARGA
    // Por ahora, placeholder:
    if (mangaValor === 'CORTA') {
        variantes.tipo_manga_id = 1;  // VERIFICAR en BD
    } else if (mangaValor === 'LARGA') {
        variantes.tipo_manga_id = 2;  // VERIFICAR en BD
    } else {
        variantes.tipo_manga_id = null;
    }
} else {
    variantes.manga = '';
    variantes.obs_manga = '';
    variantes.tipo_manga_id = null;
}
```

### Corrección 4: TALLAS - Garantizar que se cargan en modo edición

En `prenda-editor.js` (cargarTallasYCantidades - línea 328+), asegurar que:

```javascript
// AGREGAR DESPUÉS DE VACIAR:
window.tallasRelacionales.DAMA = {};
window.tallasRelacionales.CABALLERO = {};

// CARGAR DESDE PRENDA SI ESTÁ EN MODO EDICIÓN:
if (prenda.tallas && Array.isArray(prenda.tallas)) {
    const generosMap = {};
    prenda.tallas.forEach(t => {
        const genero = t.genero.toUpperCase() || 'DAMA';
        if (!generosMap[genero]) generosMap[genero] = {};
        generosMap[genero][t.talla] = parseInt(t.cantidad) || 0;
    });
    
    Object.entries(generosMap).forEach(([genero, tallas]) => {
        window.tallasRelacionales[genero] = tallas;
    });
}
```

### Corrección 5: VARIANTES - Cargar MANGA en modo edición

En `prenda-editor.js` (cargarVariaciones - línea 455+), agregar:

```javascript
// Cargar tipo_manga_id
if (prenda.tipo_manga_id) {
    // Crear mapping inverso: ID → STRING
    // VERIFICAR en BD: ID 1 = CORTA, ID 2 = LARGA, etc.
    let valorSeleccionar = '';
    if (prenda.tipo_manga_id === 1) {
        valorSeleccionar = 'CORTA';
    } else if (prenda.tipo_manga_id === 2) {
        valorSeleccionar = 'LARGA';
    }
    
    const mangaInput = document.getElementById('manga-input');
    if (mangaInput) {
        mangaInput.value = valorSeleccionar;
        mangaInput.dispatchEvent(new Event('change'));
    }
}
```

---

## VERIFICACIONES NECESARIAS EN BD

Ejecutar para confirmar IDs:

```sql
-- Tipos de manga
SELECT id, nombre FROM tipos_manga;
-- Esperado: 1 = CORTA, 2 = LARGA, etc.

-- Tipos de broche/botón (ya verificado: 1 = Broche, 2 = Botón)
SELECT id, nombre FROM tipos_broche_boton;
```

---

## RESUMEN DE FIXES

| # | Problema | Archivo | Línea | Prioridad |
|---|----------|---------|-------|-----------|
| 1 | Fotos de prenda no se envían | modal-novedad-edicion.js | ~101 | 🔴 CRÍTICA |
| 2 | Fotos de tela no se envían | modal-novedad-edicion.js | ~101 | 🔴 CRÍTICA |
| 3 | Manga sin tipo_manga_id | prenda-form-collector.js | 210-215 | 🟡 ALTA |
| 4 | Tallas vacías en edición | prenda-editor.js | 328+ | 🟡 ALTA |
| 5 | Manga no se carga en edición | prenda-editor.js | 455+ | 🟡 ALTA |

---

## LOG DE REFERENCIA

**POST enviado con PROBLEMAS**:
```json
{
  "variantes_recibidas": "[{\"tipo_manga_id\":null,\"tipo_broche_boton_id\":2,\"manga_obs\":\"Rew\",\"broche_boton_obs\":\"WERWERW\",\"tiene_bolsillos\":true,\"bolsillos_obs\":\"RWER\",\"tiene_reflectivo\":false,\"reflectivo_obs\":\"\"}]",
  "cantidad_talla": "{}",
  "procesos": "{}"
}
```

**Esperado**:
```json
{
  "variantes_recibidas": "[{\"tipo_manga_id\":1,\"tipo_broche_boton_id\":2,...}]",
  "cantidad_talla": "{\"DAMA\":{\"S\":5},\"CABALLERO\":{\"32\":3}}",
  "procesos": "{}",
  "imagenes": [File, File, ...],
  "telas": [{tela: "...", imagenes: [File, File]}]
}
```
