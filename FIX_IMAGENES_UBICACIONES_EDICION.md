# Fix: Preservar Imágenes y Ubicaciones al Editar Prendas (Formulario vs BD)

## Problema Reportado
Cuando se edita una prenda que fue creada desde el formulario:
-  Las imágenes de la prenda desaparecen
-  Las imágenes de los procesos desaparecen  
-  Las ubicaciones de los procesos desaparecen
- Las tallas se cargan correctamente (fix anterior)

## Causa Raíz
Los datos vienen en diferentes formatos según su origen:
- **Formulario**: Imágenes como `File` objects, procesos como objeto `{reflectivo: {...}, bordado: {...}}`
- **BD**: Imágenes como URLs/strings, procesos como array `[{...}, {...}]`

El código no detectaba estos formatos y no cargaba los datos correctamente.

## Soluciones Implementadas

### 1. `cargarImagenes(prenda)` - Detección Adaptativa
**Antes:** Solo verificaba `prenda.imagenes` sin considerar su formato.

**Después:** 
```javascript
// PRIORIDAD 0: imagenes (formulario con archivos)
if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
    const primerItem = prenda.imagenes[0];
    
    if (primerItem instanceof File || primerItem.file instanceof File) {
        // Formulario: archivos File
        imagenesACargar = prenda.imagenes;
        origen = 'formulario';
    } else if (typeof primerItem === 'string' || (primerItem && (primerItem.url || primerItem.ruta))) {
        // BD: URLs/strings
        imagenesACargar = prenda.imagenes;
        origen = 'bd-urls';
    }
}

// PRIORIDAD 1: fotos (BD alternativo)
if (!imagenesACargar && prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
    imagenesACargar = prenda.fotos;
    origen = 'bd-fotos';
}
```

**Impacto:**
- Detecta automáticamente si las imágenes vienen del formulario o BD
- Maneja múltiples nombres de campos (`imagenes`, `fotos`)
- Logging detallado para debugging

### 2. `procesarImagen(img, idx)` - Manejo Completo de Formatos
**Antes:** Solo manejaba 3 casos, fallaba con `File` directo.

**Después:** Maneja 5 casos:
```javascript
// CASO 1: File directo (formulario)
if (img instanceof File) { ... }

// CASO 2: Wrapper con .file (formulario)
else if (img.file instanceof File) { ... }

// CASO 3: Objeto con URL (BD)
else if (img.url || img.ruta || img.ruta_webp || img.ruta_original) { ... }

// CASO 4: String URL (BD alternativo)
else if (typeof img === 'string') { ... }

// CASO 5: Blob (formulario)
else if (img instanceof Blob) { ... }
```

**Impacto:**
- Soporta File objects directos (caso principal del formulario)
- Soporta múltiples estructuras de URL
- Logging para cada caso

### 3. `cargarProcesos(prenda)` - Ubicaciones Adaptativas
**Antes:** Solo verificaba `proceso.ubicaciones || []` sin detectar formato.

**Después:**
```javascript
let ubicacionesFormato = [];

if (proceso.ubicaciones) {
    if (Array.isArray(proceso.ubicaciones)) {
        // Array: usar directamente
        ubicacionesFormato = proceso.ubicaciones;
    } else if (typeof proceso.ubicaciones === 'string') {
        // String: convertir a array
        ubicacionesFormato = proceso.ubicaciones
            .split(',')
            .map(u => u.trim())
            .filter(u => u && u.length > 0);
    } else if (typeof proceso.ubicaciones === 'object') {
        // Objeto: extraer valores
        ubicacionesFormato = Object.values(proceso.ubicaciones)
            .filter(u => u && (typeof u === 'string' || typeof u === 'object'));
    }
}
```

**Impacto:**
- Maneja ubicaciones como array (estándar)
- Maneja ubicaciones como string CSV (alternativo)
- Logging detallado de fuente

## Ejemplos de Detección

### Imágenes del Formulario
```javascript
prenda.imagenes = [
    File { name: 'prenda.png', size: 12345 },
    File { name: 'prenda2.png', size: 67890 }
]
// → Detectado: 'formulario'
// → Procesadas con agregarImagen() para File objects
```

### Imágenes de BD
```javascript
prenda.imagenes = [
    { url: '/storage/prendas/123.webp', size: 12345 },
    '/storage/prendas/124.webp'
]
// → Detectado: 'bd-urls'
// → Procesadas como URLs con urlDesdeDB: true
```

### Ubicaciones del Formulario
```javascript
proceso.ubicaciones = ['pecho', 'espalda', 'manga']
// → Detectado: ARRAY
// → Usado directamente
```

### Ubicaciones de BD
```javascript
proceso.ubicaciones = 'pecho, espalda, manga'
// → Detectado: STRING
// → Convertido a ['pecho', 'espalda', 'manga']
```

## Logging Añadido

### Para Imágenes de Prenda
```
🖼️ [CARGAR-IMAGENES] Iniciando carga de imágenes
✅ [CARGAR-IMAGENES] Detectado: imagenes de FORMULARIO (File objects)
🔄 [CARGAR-IMAGENES] Limpiando y cargando 2 imágenes (origen: formulario)
  [PROCESAR-IMAGEN] Imagen 0: File object detectado
  [PROCESAR-IMAGEN] Imagen 1: File object detectado
✅ [CARGAR-IMAGENES] 2 imágenes cargadas desde formulario
```

### Para Ubicaciones de Proceso
```
 [UBICACIONES] Detectado ARRAY: ['pecho', 'espalda']
```

## Validación

✅ **No rompe BD logic:** Las imágenes y ubicaciones de BD se cargan igual
✅ **Preserva datos del formulario:** File objects se mantienen
✅ **Manejo defensivo:** Múltiples formatos soportados
✅ **Debugging mejorado:** Logs detallados para troubleshooting

## Archivos Modificados
- `public/js/modulos/crear-pedido/procesos/services/prenda-editor.js`
  - Línea 149-205: `cargarImagenes()` mejorado
  - Línea 210-262: `procesarImagen()` mejorado
  - Línea 645-750: `cargarProcesos()` mejorado con ubicaciones adaptativas

## Testing Recomendado

1. **Crear prenda con imágenes desde formulario**
   - Agregar 2+ imágenes
   - Verificar que se muestren en tarjeta

2. **Editar prenda del paso 1**
   - Abrir modal de edición
   - Verificar imágenes presentes
   - Verificar ubicaciones presentes
   - Verificar tallas presentes

3. **Crear proceso con ubicaciones**
   - Agregar proceso con 2+ ubicaciones
   - Verificar que se muestren en tarjeta

4. **Editar proceso del paso 3**
   - Abrir modal para editar proceso
   - Verificar ubicaciones cargadas
   - Verificar imágenes cargadas
