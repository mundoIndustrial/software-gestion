# FIX: Tabla de Telas no se mostraba en Modal de Edición de Prenda (27 ENE 2026)

## Problema Identificado 🔴

En el modal de edición de prendas, las telas (nombre, color, referencia y foto) **NO se mostraban en la tabla** aunque se cargaban correctamente en el backend.

El log mostrado indicaba:
```javascript
[cargarTelas] 📊 Cargando telas: [{…}]
[cargarTelas] ✓ Telas disponibles: 1
[cargarTelas] ✅ window.telasAgregadas asignadas: [{…}]
[cargarTelas] 🔄 Llamando a actualizarTablaTelas()
```

Pero la tabla permanecía vacía.

## Causa Raíz 🎯

La función `window.actualizarTablaTelas()` en [gestion-telas.js](public/js/modulos/crear-pedido/telas/gestion-telas.js) estaba iterando **SOLO sobre `window.telasCreacion`**:

```javascript
// ❌ ANTES - Solo creación
window.telasCreacion.forEach((telaData, index) => {
    // renderizar...
});
```

Sin embargo:
- **Flujo CREACIÓN**: Las telas se guardan en `window.telasCreacion` 
- **Flujo EDICIÓN**: Las telas se cargan en `window.telasAgregadas` (desde BD)

En edición, `window.telasCreacion` estaba vacío, por lo que **no había nada que iterar**.

## Solución Implementada ✅

### 1. Detectar Modo Automáticamente
Se agregó lógica para determinar si estamos en modo EDICIÓN o CREACIÓN. Soporta ambas variables de edición (`telasAgregadas` y `telasEdicion` para compatibilidad):

```javascript
// ✅ DESPUÉS - Detecta modo automáticamente
const telasParaMostrar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
    ? window.telasAgregadas 
    : (window.telasEdicion && window.telasEdicion.length > 0)
        ? window.telasEdicion
        : window.telasCreacion;

const modoEdicion = (window.telasAgregadas && window.telasAgregadas.length > 0) || 
                    (window.telasEdicion && window.telasEdicion.length > 0);
```

### 2. Normalizar Propiedades de Datos
Las telas vienen con estructura diferente según su origen (CREACIÓN vs EDICIÓN).

**Creación:**
- `nombre_tela`, `color`, `referencia`

**Edición (desde BD):**
- `nombre_tela`, `color_nombre`, `tela_referencia`

Se normalizó la lectura:

```javascript
// Normalizar datos para que funcione en ambos modos
const nombre_tela = telaData.nombre_tela || telaData.tela || telaData.nombre || '(Sin nombre)';
const color = telaData.color || telaData.color_nombre || '(Sin color)';
const referencia = telaData.referencia || telaData.tela_referencia || '';
```

### 3. Priorizar previewUrl en Imágenes
Se reordenó la lógica de detección de URLs de imagen para que `previewUrl` (que viene directo de la transformación) sea checado **primero**:

```javascript
// CASO 0: previewUrl (viene de transformación en prenda-editor.js)
if (img && img.previewUrl) {
    blobUrl = img.previewUrl;
}
// ... resto de casos
```

### 4. Actualizar Eliminación de Telas
La función `eliminarTela()` también necesitaba conocer el modo:

```javascript
// Eliminar según el modo (EDICIÓN o CREACIÓN)
if (window.telasAgregadas && window.telasAgregadas.length > 0) {
    window.telasAgregadas.splice(index, 1);
} else {
    window.telasCreacion.splice(index, 1);
}
```

## Archivos Modificados 📝

### 1. [prenda-editor-modal.js](public/js/componentes/prenda-editor-modal.js)
**Línea ~177**: Traer referencia de `prenda_pedido_colores_telas`
```javascript
referencia: ct.referencia || ct.tela?.referencia || ct.tela_referencia || '',
```
✅ Ahora busca primero en `ct.referencia` (tabla pivot), luego fallback a tela

### 2. [prenda-editor.js](public/js/modulos/crear-pedido/procesos/services/prenda-editor.js)
**Línea ~352**: Traer referencia de `prenda_pedido_colores_telas`
```javascript
referencia: ct.referencia || ct.tela_referencia || '',
```
✅ Ahora busca primero en `ct.referencia` (tabla pivot), luego fallback

### 3. [gestion-telas.js](public/js/modulos/crear-pedido/telas/gestion-telas.js)

**Línea ~265**: Agregar detección de modo
```javascript
// ===== DETECTAR MODO: CREACIÓN o EDICIÓN =====
// En EDICIÓN: window.telasAgregadas O window.telasEdicion contienen las telas desde BD
// En CREACIÓN: window.telasCreacion contiene las telas nuevas
const telasParaMostrar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
    ? window.telasAgregadas 
    : (window.telasEdicion && window.telasEdicion.length > 0)
        ? window.telasEdicion
        : window.telasCreacion;

const modoEdicion = (window.telasAgregadas && window.telasAgregadas.length > 0) || 
                    (window.telasEdicion && window.telasEdicion.length > 0);
```

**Línea ~303**: Normalizar propiedades
```javascript
// ===== NORMALIZAR DATOS: Compatible tanto CREACIÓN como EDICIÓN =====
const nombre_tela = telaData.nombre_tela || telaData.tela || telaData.nombre || '(Sin nombre)';
const color = telaData.color || telaData.color_nombre || '(Sin color)';
const referencia = telaData.referencia || telaData.tela_referencia || '';
```

**Línea ~369**: Usar variables normalizadas
```javascript
<td style="padding: 0.75rem; vertical-align: middle;">${nombre_tela}</td>
<td style="padding: 0.75rem; vertical-align: middle;">${color}</td>
<td style="padding: 0.75rem; vertical-align: middle;">${referencia}</td>
```

**Línea ~329-332**: Priorizar previewUrl
```javascript
// CASO 0: previewUrl (viene de transformación en prenda-editor.js)
if (img && img.previewUrl) {
    blobUrl = img.previewUrl;
    console.log(`[actualizarTablaTelas] 📋 Caso previewUrl: ${blobUrl}`);
}
```

**Línea ~469**: Actualizar eliminación
```javascript
// Eliminar según el modo (EDICIÓN o CREACIÓN)
// Soporta ambas variables: telasAgregadas (modo edición actual) y telasEdicion (legacy)
if (window.telasAgregadas && window.telasAgregadas.length > 0) {
    window.telasAgregadas.splice(index, 1);
} else if (window.telasEdicion && window.telasEdicion.length > 0) {
    window.telasEdicion.splice(index, 1);
} else {
    window.telasCreacion.splice(index, 1);
}
```

## Validación 🧪

Para verificar que funcione correctamente:

1. **Abrir modal de edición de prenda**
   - Debe mostrar la tabla de telas con:
     - ✅ Nombre de tela (ej: "drill")
     - ✅ Color (ej: "dsfdfs")  
     - ✅ Referencia (si existe)
     - ✅ Foto thumbnail
     - ✅ Botón de eliminar

2. **Comportamiento esperado:**
   - Las telas de BD se muestran automáticamente
   - Pueden agregarse nuevas telas
   - Pueden eliminarse telas existentes
   - Las fotos se muestran en la tabla

3. **Flujo sin regresiones:**
   - Creación de prendas nuevas: `window.telasCreacion` funciona
   - Edición de prendas: `window.telasAgregadas` funciona

## Logs de Debug 📋

Los cambios incluyen logs mejorados para facilitar debugging:

```javascript
[actualizarTablaTelas] 📋 Modo: EDICIÓN, Telas a mostrar: 1
[actualizarTablaTelas] 🧵 Procesando tela 0: {nombre: "drill", color: "dsfdfs", referencia: ""}
[actualizarTablaTelas] 📸 Primera imagen de tela 0: {previewUrl: "/storage/..."}
[actualizarTablaTelas] 📋 Caso previewUrl: /storage/pedidos/2763/tela/...
```

## Impacto 🎯

- ✅ Tabla de telas se renderiza correctamente en edición
- ✅ Compatible con modo creación (sin regresiones)
- ✅ Manejo robusto de estructuras de datos variadas
- ✅ Mejor debugging con logs contextuales

---

**Fecha:** 27 ENE 2026  
**Estado:** ✅ Implementado  
**Probado con:** Prenda ID 3475 (CAMISA DRILL), Pedido ID 2763
