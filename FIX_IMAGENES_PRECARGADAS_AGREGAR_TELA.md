# FIX: Imágenes de Telas Precargadas en Input de Agregar

## 🔴 Problema

Cuando se abría el modal de **edición de prenda**, las imágenes de telas guardadas en BD aparecían **precargadas en el input de "Agregar Tela Nueva"**.

**Síntomas:**
- Editar prenda con telas
- El preview de "agregar tela" muestra foto de tela ya guardada
- Usuario ve imagen que NO agregó
- Confusión sobre qué es nuevo vs qué es existente

**Causa raíz:** La función `cargarTelas()` en `prenda-editor.js` estaba cargando las imágenes de telas de BD en `window.imagenesTelaStorage`, que es el storage compartido para agregar telas nuevas.

---

## ✅ Solución

### Archivo: `prenda-editor.js`
**Línea: 415-420 (antes había ~40 líneas de código)**

**ANTES (❌ Cargaba imágenes en storage):**
```javascript
// Cargar cada tela
prenda.telasAgregadas.forEach((tela, idx) => {
    
    // Cargar imágenes de tela
    if (tela.imagenes && tela.imagenes.length > 0 && window.imagenesTelaStorage) {
        
        tela.imagenes.forEach((img, imgIdx) => {
            
            if (img.file instanceof File) {
                window.imagenesTelaStorage.agregarImagen(img.file);
            } else if (img.previewUrl || img.url || img.ruta) {
                const urlImg = img.previewUrl || img.url || img.ruta;
                
                if (!window.imagenesTelaStorage.images) {
                    window.imagenesTelaStorage.images = [];
                }
                window.imagenesTelaStorage.images.push({
                    previewUrl: urlImg,
                    nombre: `tela_${idx}_${imgIdx}.webp`,
                    tamaño: 0,
                    file: null,
                    urlDesdeDB: true
                });
            }
        });
    }
});
```

**DESPUÉS (✅ Limpio, solo muestra en tabla):**
```javascript
// ⚠️ NO cargar imágenes de telas de BD en window.imagenesTelaStorage
// Las imágenes de telas existentes SOLO se muestran en la tabla (gestion-telas.js)
// El storage debe estar limpio para AGREGAR TELAS NUEVAS
// Esto evita que aparezcan precargadas en el input de agregar
```

---

## 🧬 Lógica de Separación

### ANTES (Problema):
```
BD → cargarTelas() 
  ↓
Imágenes de BD se cargan en window.imagenesTelaStorage
  ↓
Preview "Agregar Tela Nueva" muestra imágenes de BD ❌
  ↓
Input para agregar muestra foto precargada ❌
```

### DESPUÉS (Correcto):
```
BD → cargarTelas() 
  ↓
Imágenes de BD se pasan a prenda.telasAgregadas
  ↓
gestion-telas.js renderiza en TABLA ✅
  ↓
window.imagenesTelaStorage = vacío ✅
  ↓
Input para agregar está limpio ✅
  ↓
Usuario puede agregar NUEVAS telas sin confusión ✅
```

---

## 🎯 Impacto

| Aspecto | Antes | Después |
|--------|-------|---------|
| Imágenes en tabla | ✅ Mostradas | ✅ Mostradas |
| Preview "agregar" | ❌ Precargado | ✅ Limpio |
| Input "agregar" | ❌ Con datos | ✅ Vacío |
| Storage de edición | ❌ Con datos de BD | ✅ Limpio |
| Confusión usuario | ❌ Alto | ✅ Ninguno |

---

## 📊 Diferencia de Almacenamiento

```javascript
// MODO EDICIÓN - ESTRUCTURA CORRECTA:

// 1️⃣ Telas de BD → Se guardan en prenda.telasAgregadas
window.telasAgregadas = [
  {
    nombre_tela: 'drill',
    imagenes: [{url: '/storage/...', previewUrl: '...'}]
  }
]

// 2️⃣ Se renderiza en tabla → gestion-telas.js
[actualizarTablaTelas] → Lee telasAgregadas
[actualizarTablaTelas] → Renderiza tabla con imágenes

// 3️⃣ Storage limpio para nuevas telas
window.imagenesTelaStorage = {
  images: [],  // ← VACÍO para agregar nuevas
  limpiar() { ... }
}

// 4️⃣ Usuario puede agregar nuevas telas sin confusión
```

---

## ✅ Flujo Correcto Ahora

1. **Abrir modal edición** → Telas de BD cargadas
2. **Ver tabla de telas** → Muestra telas con fotos de BD
3. **Input agregar** → Limpio, listo para nuevas telas
4. **Agregar tela nueva** → Storage recibe nueva tela
5. **Tabla actualiza** → Muestra tela nueva + telas de BD

---

## 🧪 Verificación

### En Console (F12):

```javascript
// Antes (problema):
window.imagenesTelaStorage.images.length  // → 1 (precargada)

// Después (correcto):
window.imagenesTelaStorage.images.length  // → 0 (vacío)

// Pero las telas están en la tabla:
window.telasAgregadas[0]  // → {nombre_tela: 'drill', imagenes: [...]}
```

### Visualmente:

**TABLA DE TELAS:**
```
┌─────────────┬──────────┬────────────┬─────────────┬──────────┐
│ TELA        │ COLOR    │ REFERENCIA │ FOTO        │ ACCIONES │
├─────────────┼──────────┼────────────┼─────────────┼──────────┤
│ drill       │ dsf      │            │ [THUMBNAIL] │ [X]      │
└─────────────┴──────────┴────────────┴─────────────┴──────────┘
```

**INPUT AGREGAR TELA NUEVA:**
```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ TELA...      │ COLOR...     │ REF...       │ [FOTO]       │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ (vacío)      │ (vacío)      │ (vacío)      │ (sin foto)   │ ✅
└──────────────┴──────────────┴──────────────┴──────────────┘
```

---

## 🔒 Cambios de Seguridad

- ✅ Input agregar siempre limpio
- ✅ Telas de BD nunca se mezclan con nuevas
- ✅ Storage separado para cada flujo
- ✅ Menor riesgo de editar datos existentes

---

## 📝 Resumen

| Punto | Antes | Después |
|-------|-------|---------|
| **Función afectada** | `cargarTelas()` | Optimizada |
| **Líneas removidas** | ~40 de carga en storage | Limpias |
| **Líneas comentario** | 0 | 4 explicativas |
| **Funcionalidad** | Misma pero confusa | Clara y separada |
| **UX** | Confuso | Claro |

---

**Fecha:** 27 ENE 2026  
**Estado:** ✅ Corregido  
**Probado:** Con Prenda DF (ID 3476), Pedido 2764
