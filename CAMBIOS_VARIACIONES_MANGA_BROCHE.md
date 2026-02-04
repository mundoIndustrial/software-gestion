# ✅ FIX: Variaciones Manga y Broche en Modal de Edición

## Problema Identificado

Cuando se abre una prenda para editar, las variaciones (manga, broche) NO se rellenaban en el modal, aunque estaban guardadas en la BD con los valores:
- `tipo_manga_id: 2` → debería mostrar "Corta"
- `tipo_broche_boton_id: 1` → debería mostrar "Broche"

**Causa raíz:** El servidor devolvía solo los IDs (`tipo_manga_id`, `tipo_broche_boton_id`), pero no los nombres. El código esperaba que los nombres estuvieran en `tipo_manga` y `tipo_broche`, pero estos campos venían vacíos.

---

## Solución Implementada

### 1. **Crear función `cargarTiposBrocheBotonDisponibles()` en manejadores-variaciones.js**

Agregué una nueva función para cargar tipos de broche desde BD (similar a `cargarTiposMangaDisponibles()`):

```javascript
async function cargarTiposBrocheBotonDisponibles() {
    // Implementación idéntica a cargarTiposMangaDisponibles
    // Carga desde /asesores/api/tipos-broche-boton
    // Con cache para evitar múltiples llamadas
}
```

**Archivo:** `public/js/modulos/crear-pedido/prendas/manejadores-variaciones.js`

---

### 2. **Fix en prenda-editor-modal.js (líneas 433-490)**

Cuando se transforman las variantes del servidor, ahora:

```javascript
// 🔴 FIX: Si tipo_manga_id existe pero tipo_manga está vacío, buscar el nombre
let nombreTipoManga = v.tipo_manga || v.manga || '';

if ((v.tipo_manga_id || v.manga_id) && !nombreTipoManga) {
    // Cargar tipos de manga desde BD
    const tiposManga = await cargarTiposMangaDisponibles();
    const mangaId = v.tipo_manga_id || v.manga_id;
    const tipoMangaEncontrado = tiposManga.find(tm => tm.id === mangaId);
    if (tipoMangaEncontrado) {
        nombreTipoManga = tipoMangaEncontrado.nombre; // "Corta"
    }
}

// Mismo proceso para broche
let nombreTipoBroche = v.tipo_broche_boton || v.broche || v.tipo_broche || '';

if ((v.tipo_broche_boton_id || v.broche_id) && !nombreTipoBroche) {
    const tiposBroche = await cargarTiposBrocheBotonDisponibles();
    const brocheId = v.tipo_broche_boton_id || v.broche_id;
    const tipoBrocheEncontrado = tiposBroche.find(tb => tb.id === brocheId);
    if (tipoBrocheEncontrado) {
        nombreTipoBroche = tipoBrocheEncontrado.nombre; // "Broche"
    }
}

// Resultado: variantes contiene los nombres correctos
variantes = {
    tipo_manga: nombreTipoManga,      // "Corta" (no vacío)
    tipo_manga_id: v.tipo_manga_id,   // 2
    tipo_broche: nombreTipoBroche,    // "Broche" (no vacío)
    tipo_broche_id: v.tipo_broche_boton_id, // 1
    // ... más campos
};
```

**Archivo:** `public/js/componentes/prenda-editor-modal.js`

---

### 3. **Fix en prenda-editor.js - Función cargarVariaciones()**

Actualicé la función para manejar correctamente los datos cuando llegan como strings directos:

#### Para Manga (líneas 1862-1906):
```javascript
// Antes: Buscaba en variantes.manga.opcion (esperaba objeto)
// Ahora: Busca primero en variantes.tipo_manga (string directo)

let mangaOpcion = '';

// Prioridad 1: Si viene tipo_manga como string directo (caso nuevo)
if (typeof variantes.tipo_manga === 'string' && variantes.tipo_manga) {
    mangaOpcion = variantes.tipo_manga;  // Obtiene "Corta"
}
// Prioridad 2: Si viene como objeto (caso antiguo)
else if (typeof mangaData === 'object') {
    mangaOpcion = mangaData.opcion || mangaData.tipo_manga || '';
}

if (aplicaManga && mangaOpcion) {
    // Rellenar campo manga-input
    mangaInput.value = mangaOpcion.toLowerCase(); // "corta"
}
```

#### Para Broche (líneas 1961-2005):
```javascript
// Mismo patrón que manga, pero para broche
let brocheOpcion = '';

// Prioridad 1: Si viene tipo_broche como string directo (caso nuevo)
if (typeof variantes.tipo_broche === 'string' && variantes.tipo_broche) {
    brocheOpcion = variantes.tipo_broche;  // Obtiene "Broche"
}
// Prioridad 2: Si viene como objeto (caso antiguo)
else if (typeof brocheData === 'object') {
    brocheOpcion = brocheData.opcion || brocheData.tipo_broche || '';
}

if (aplicaBroche && (brocheOpcion || brocheObs)) {
    // Rellenar campo broche-input
    brocheInput.value = brocheOpcion.toLowerCase(); // "broche"
}
```

**Archivo:** `public/js/modulos/crear-pedido/procesos/services/prenda-editor.js`

---

## Flujo Completo

```
1. Usuario abre modal de edición de prenda
   ↓
2. prenda-editor-modal.js obtiene datos del servidor
   ├─ Datos del servidor incluyen:
   │  └─ variantes[0]: {tipo_manga_id: 2, tipo_manga: null, ...}
   ↓
3. FIX NUEVO: Busca el nombre del tipo_manga_id en BD
   ├─ Llama a cargarTiposMangaDisponibles() (cache)
   ├─ Encuentra: {id: 2, nombre: "Corta"}
   └─ Asigna nombreTipoManga = "Corta"
   ↓
4. Objeto variantes transformado:
   └─ {tipo_manga: "Corta", tipo_manga_id: 2, ...}
   ↓
5. prenda-editor.js recibe variantes con datos llenos
   ├─ cargarVariaciones() verifica tipo_manga
   └─ Rellenar campo manga-input con "corta"
   ↓
6. ✅ Modal muestra manga correctamente: "corta" (normalizado)
```

---

## Archivos Modificados

1. **prenda-editor-modal.js** - Líneas 433-490
   - Agregó búsqueda de nombres cuando viene solo ID

2. **manejadores-variaciones.js** - Líneas 110-169
   - Agregó función `cargarTiposBrocheBotonDisponibles()`
   - Agregó caché y limpiar caché para broche

3. **prenda-editor.js** - Líneas 1862-2005
   - Actualizo lógica de `cargarVariaciones()` para manga y broche
   - Ahora maneja strings directos además de objetos

---

## Logs Esperados

Con estos cambios, deberías ver en la consola:

```
🔍 [VARIANTES] tipo_manga_id encontrado pero sin nombre, buscando...
[Manga] Usando cache de tipos de manga
✓ [VARIANTES] Nombre de manga encontrado: Corta

🔍 [VARIANTES] tipo_broche_boton_id encontrado pero sin nombre, buscando...
[Broche] Usando cache de tipos de broche/botón
✓ [VARIANTES] Nombre de broche encontrado: Broche

[cargarVariaciones] ✓ manga-input asignado: corta
[cargarVariaciones] ✓ manga-obs asignado: RETERTR345345
[cargarVariaciones] ✓ broche-input asignado: broche
[cargarVariaciones] ✓ broche-obs asignado: ERT4 43534534
```

---

## Testing

Pasos para verificar:

1. Abre un pedido existente con prendas que tengan variaciones guardadas
2. Haz click en "Editar" en la prenda
3. Se debe abrir el modal y cargar correctamente:
   - ✅ Checkbox de "Aplica Manga" debe estar marcado
   - ✅ Campo manga-input debe mostrar "corta" (u otro tipo)
   - ✅ Campo manga-obs debe mostrar "RETERTR345345"
   - ✅ Checkbox de "Aplica Broche" debe estar marcado
   - ✅ Campo broche-input debe mostrar "broche" (u otro tipo)
   - ✅ Campo broche-obs debe mostrar "ERT4 43534534"

---

## Notas Importantes

- ✅ Las funciones son **async** para usar `await`
- ✅ Sistema de **caché** evita múltiples llamadas innecesarias
- ✅ **Retrocompatibilidad** mantenida para datos antiguos en formato objeto
- ✅ **Logs detallados** para debugging
- ✅ **Normalización** de valores (minúsculas, sin acentos)
