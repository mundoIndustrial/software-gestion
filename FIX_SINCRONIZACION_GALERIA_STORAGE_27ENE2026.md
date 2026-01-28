# 🔧 FIX SINCRONIZACIÓN GALERÍA ↔️ STORAGE - 27 ENERO 2026

## ❌ PROBLEMA IDENTIFICADO

El usuario eliminaba 1 imagen pero la prenda seguía guardando **2 imágenes** (no se eliminaba).

### Root Cause (Raíz del Problema)

**`window.imagenesPrendaStorage` NO estaba siendo sincronizado** entre:
- **Galería** (donde se elimina la imagen)  
- **Modal de guardado** (donde se lee el storage para enviar al servidor)

**Flujo Roto:**
```
1. Usuario abre modal de edición
   ↓
2. window.imagenesPrendaStorage = new ImageStorageService(3)  // ⚠️ VACÍO
   ↓
3. Usuario abre galería (lee desde storage vacío)
   ↓
4. Usuario elimina imagen en galería
   ↓ 
5. Galería actualiza LOCALMENTE (imagenesValidas.splice)
   ↓
6. Pero NO actualiza window.imagenesPrendaStorage ❌
   ↓
7. Modal intenta leer window.imagenesPrendaStorage
   ↓
8. Lee array vacío o antiguo ❌
   ↓
9. Backend recibe imagenes_existentes con todas las imágenes originales
```

---

## ✅ SOLUCIONES IMPLEMENTADAS

### 1️⃣ Método `establecerImagenes` en ImageStorageService
**Archivo:** `image-storage-service.js` (líneas 48-64)

```javascript
/**
 * 🔧 Establecer/reemplazar el array completo de imágenes
 * Usado cuando la galería elimina una imagen y necesita sincronizar el storage
 */
establecerImagenes(nuevasImagenes) {
    if (!Array.isArray(nuevasImagenes)) {
        console.warn('⚠️ [ImageStorageService.establecerImagenes] No es un array válido');
        return;
    }
    
    // Limpiar URLs de imágenes que serán reemplazadas
    this.images.forEach(img => {
        if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(img.previewUrl);
        }
    });
    
    // Reemplazar el array
    this.images = nuevasImagenes || [];
    console.log('✅ [ImageStorageService.establecerImagenes] Array sincronizado, ahora hay', this.images.length, 'imágenes');
}
```

**Propósito:** Permite reemplazar TODO el array de imágenes en el storage desde la galería.

---

### 2️⃣ Sincronización en Galería al Eliminar
**Archivo:** `prendas-wrappers.js` (líneas 666-669)

```javascript
btnConfirmarEliminar.onclick = () => {
    confirmModalDiv.remove();
    
    console.log('🗑️ [mostrarGaleriaImagenesPrenda] Eliminando imagen en índice', indiceActual);
    
    // Eliminar de imagenesValidas
    imagenesValidas.splice(indiceActual, 1);
    
    // Eliminar del array original (imagenes)
    const imagenAEliminar = imagenes[indiceActual];
    const indiceEnOriginal = imagenes.indexOf(imagenAEliminar);
    if (indiceEnOriginal !== -1) {
        imagenes.splice(indiceEnOriginal, 1);
        console.log('✅ Imagen eliminada del array original');
    }
    
    // 🔧 IMPORTANTE: Actualizar window.imagenesPrendaStorage con el nuevo array
    if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.establecerImagenes === 'function') {
        window.imagenesPrendaStorage.establecerImagenes(imagenes);
        console.log('✅ [SYNC] window.imagenesPrendaStorage actualizado con', imagenes.length, 'imágenes');
    }
    
    // Actualizar UI
    actualizarUI();
};
```

**Propósito:** Cuando se elimina una imagen, actualizar `window.imagenesPrendaStorage` con el array ACTUALIZADO.

---

### 3️⃣ Inicialización de Storage al Abrir Editor
**Archivo:** `modal-novedad-edicion.js` (líneas 87-96)

```javascript
async mostrarModalYActualizar(pedidoId, prendaData, prendaIndex) {
    this.pedidoId = pedidoId;
    this.prendaData = prendaData;
    this.prendaIndex = prendaIndex;

    // 🔧 CRÍTICO: Inicializar window.imagenesPrendaStorage con las imágenes ACTUALES de la prenda
    // Esto asegura que cuando la galería se abre, tenga las imágenes correctas
    if (window.imagenesPrendaStorage && prendaData && prendaData.imagenes) {
        // Limpiar el storage antes de cargar nuevas imágenes
        window.imagenesPrendaStorage.limpiar();
        
        // Establecer las imágenes de la prenda actual
        window.imagenesPrendaStorage.establecerImagenes(prendaData.imagenes);
        console.log('[modal-novedad-edicion] ✅ [INIT-SYNC] window.imagenesPrendaStorage inicializado con', prendaData.imagenes.length, 'imágenes');
    }

    return new Promise((resolve) => {
```

**Propósito:** Cuando se abre el modal para editar una prenda, llenar el storage con sus imágenes actuales.

---

### 4️⃣ Inicialización para Nueva Prenda
**Archivo:** `modal-novedad-prenda.js` (líneas 40-48)

```javascript
async mostrarModalYGuardar(pedidoId, prendaData) {
    this.pedidoId = pedidoId;
    this.prendaData = prendaData;

    // 🔧 CRÍTICO: Inicializar window.imagenesPrendaStorage limpio para prenda NUEVA
    if (window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage.limpiar();
        console.log('[modal-novedad-prenda] ✅ [INIT-SYNC] window.imagenesPrendaStorage limpiado para nueva prenda');
    }
```

**Propósito:** Asegurar storage limpio para nuevas prendas sin datos anteriores.

---

## 🔄 FLUJO CORREGIDO

```
1. Usuario abre modal de edición de prenda
   ↓
2. ✅ window.imagenesPrendaStorage.establecerImagenes(prendaData.imagenes)
   ↓
   Storage ahora contiene: [img1, img2]
   ↓
3. Usuario abre galería
   ↓
4. Galería LEE desde window.imagenesPrendaStorage
   ↓
   Muestra: [img1, img2]
   ↓
5. Usuario elimina imagen #1
   ↓
6. ✅ Galería llama window.imagenesPrendaStorage.establecerImagenes(imagenes)
   ↓
   Storage ahora contiene: [img2]
   ↓
7. Modal de guardado LEE window.imagenesPrendaStorage
   ↓
   Lee correctamente: [img2]
   ↓
8. Backend recibe imagenes_existentes con 1 imagen ✅
   ↓
9. Prenda se guarda con 1 imagen (correctamente eliminada)
```

---

## 📊 CAMBIOS REALIZADOS

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `image-storage-service.js` | Agregado método `establecerImagenes()` | 48-64 |
| `prendas-wrappers.js` | Sincronización al eliminar imagen | 666-669 |
| `modal-novedad-edicion.js` | Inicialización al abrir editor | 87-96 |
| `modal-novedad-prenda.js` | Inicialización para nueva prenda | 40-48 |

---

## 🧪 VALIDACIÓN

**Log esperado cuando eliminas imagen:**
```
✅ [SYNC] window.imagenesPrendaStorage actualizado con 1 imágenes
```

**Log esperado al abrir editor:**
```
✅ [INIT-SYNC] window.imagenesPrendaStorage inicializado con 2 imágenes
```

---

## 🎯 RESULTADO ESPERADO

- ✅ Usuario elimina 1 imagen → Backend recibe 1 imagen
- ✅ Usuario elimina todas las imágenes → Backend recibe array vacío
- ✅ Usuario no toca imágenes → Backend recibe imágenes originales
- ✅ Usuario agrega nuevas imágenes → Backend recibe mix de nuevas + preservadas

