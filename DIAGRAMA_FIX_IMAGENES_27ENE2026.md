# 📊 DIAGRAMA DEL FIX - Flujo de Eliminación de Imágenes

## ❌ ANTES (DEFECTUOSO)

```
┌─────────────────────────────────────────────────────────┐
│                   USUARIO ACTÚA                          │
└──────────────────────┬──────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
   ABRE MODAL                   ABRE GALERÍA
   (La prenda se                (Copia local
    carga en                     de imágenes)
    this.prendaData)
        │                             │
        │                      usuario elimina
        │                      img → array actualizado
        │                             │
        │                    ❌ array local NO
        │                       se sincroniza
        │                             │
   USUARIO GUARDA                     │
        │                             │
        ├─────────────────────────────┘
        │
        ▼
   Lee this.prendaData.imagenes ← SNAPSHOT INICIAL
   (SIN LAS ELIMINACIONES)
        │
        ▼
   Construye FormData con:
   imagenes_existentes = [img1, img2] ← ❌ INCORRECTO
        │
        ▼
   ┌──────────────────┐
   │  SERVIDOR        │
   │                  │
   │ Recibe array     │
   │ con 2 imágenes   │
   │                  │
   │ Preserva ambas   │ ❌ ERROR
   │ ❌ NO ELIMINA    │
   └──────────────────┘
```

---

## ✅ DESPUÉS (CORRECTO)

```
┌─────────────────────────────────────────────────────────┐
│                   USUARIO ACTÚA                          │
└──────────────────────┬──────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
   ABRE MODAL                   ABRE GALERÍA
   (this.prendaData             (window.imagenesPrendaStorage
    cargado)                     ACTUALIZADO EN TIEMPO REAL)
        │                             │
        │                      usuario elimina
        │                      img → STORAGE
        │                         actualizado
        │                             │
        │                    ✅ array dinámico
        │                       SE SINCRONIZA
        │                             │
   USUARIO GUARDA                     │
        │                             │
        ├─────────────────────────────┘
        │
        ▼
   Lee window.imagenesPrendaStorage ← ESTADO ACTUAL
   (CON LAS ELIMINACIONES)
        │
        ▼
   Construye FormData con:
   imagenes_existentes = [] ← ✅ CORRECTO (vacío)
        │
        ▼
   ┌──────────────────┐
   │  SERVIDOR        │
   │                  │
   │ Recibe array     │
   │ vacío            │
   │                  │
   │ Interpreta como  │
   │ "eliminar todas" │ ✅ CORRECTO
   │ .fotos().delete()│
   │ ✅ ELIMINA OK    │
   └──────────────────┘
```

---

## 🔄 COMPARACIÓN DE CÓDIGO

### ANTES (Línea 405 en modal-novedad-edicion.js)
```javascript
// ❌ Lee snapshot estático
if (this.prendaData.imagenes && this.prendaData.imagenes.length > 0) {
    this.prendaData.imagenes.forEach((img, idx) => {
        // ... guarda como está
    });
}

formData.append('imagenes_existentes', JSON.stringify(imagenesDB));
// imageneDB tiene todos los registros iniciales
// aunque el usuario los haya eliminado de la galería
```

### DESPUÉS (Línea 405 en modal-novedad-edicion.js)
```javascript
// ✅ Lee storage dinámico
let imagenesActuales = this.prendaData.imagenes || [];

if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
    const imagenesDelStorage = window.imagenesPrendaStorage.obtenerImagenes();
    if (imagenesDelStorage && imagenesDelStorage.length > 0) {
        // ✅ Usar estado ACTUAL del storage
        imagenesActuales = imagenesDelStorage;
    } else if (imagenesDelStorage && imagenesDelStorage.length === 0) {
        // ✅ El usuario eliminó todas
        imagenesActuales = [];
    }
}

// Procesar imagenesActuales (que refleja cambios reales)
imagenesActuales.forEach((img, idx) => {
    // ...
});

// ✅ Si no hay imágenes, enviar array vacío
if (imagenesDB.length === 0 && imagenesActuales.length === 0) {
    formData.append('imagenes_existentes', JSON.stringify([]));
}
```

---

## 📥 EJEMPLO DE REQUEST

### ANTES ❌
```
POST /asesores/pedidos/2760/actualizar-prenda

FormData:
- prenda_id: 3472
- nombre_prenda: CAMISA DRILL
- imagenes_existentes: [
    {previewUrl: "/storage/prendas/prenda_20260127212136_964920.webp", nombre: "imagen_1.webp"}
  ]
- procesos: []
```

### DESPUÉS ✅
```
POST /asesores/pedidos/2760/actualizar-prenda

FormData:
- prenda_id: 3472
- nombre_prenda: CAMISA DRILL
- imagenes_existentes: []  ← VACÍO porque se eliminaron
- procesos: []
```

---

## 🎯 FLUJO DETALLADO DEL FIX

```
1️⃣  Usuario abre modal de edición
    → this.prendaData = snapshot de prenda actual
    → Incluye: imagenes: [{id: 295, url: "..."}, {id: 296, url: "..."}]

2️⃣  Usuario abre galería de imágenes
    → Se crea window.imagenesPrendaStorage con copia
    → Usuario elimina imagen en índice 0
    → window.imagenesPrendaStorage.eliminarImagen(0)
    → Storage se actualiza: [{id: 296, url: "..."}]
    → this.prendaData.imagenes SIGUE IGUAL (snapshot)

3️⃣  Usuario hace click en "Guardar"
    → Se llama actualizarPrendaConNovedad()
    → Se consulta window.imagenesPrendaStorage
    → Se obtiene estado ACTUAL: [{id: 296, url: "..."}]
    → Se construye imagenesDB CON ESTADO ACTUAL

4️⃣  Se envía FormData al servidor
    → imagenes_existentes = JSON.stringify([{...}])
    → El servidor recibe array con 1 imagen (no 2)
    → Automáticamente elimina la que NO está en la lista

5️⃣  Backend procesa
    → Lee imagenes_existentes: [{id: 296, ...}]
    → Compara con fotos actuales en BD: [{id: 295}, {id: 296}]
    → ID 295 NO está en lista → ELIMINAR
    → ID 296 ESTÁ en lista → PRESERVAR
    → Result: Solo queda id: 296 ✅
```

---

## 🔍 LOGS ESPERADOS EN laravel.log

```json
[2026-01-28 HH:MM:SS] local.DEBUG: [modal-novedad-edicion] ✅ Usando imágenes del storage (incluye eliminaciones): 1

[2026-01-28 HH:MM:SS] local.DEBUG: [modal-novedad-edicion] 📊 Resumen de imágenes a guardar: {
  "imagenesNuevas": 0,
  "imagenesExistentes": 1,
  "total": 1
}

[2026-01-28 HH:MM:SS] local.INFO: [ActualizarPrendaCompletaUseCase] actualizarFotos - Iniciando: {
  "prenda_id": 3472,
  "dto->fotos": [{...imagen 296...}],
  "es_null": false,
  "es_empty": false,
  "cantidad_fotos": 1
}

[2026-01-28 HH:MM:SS] local.DEBUG: [ActualizarPrendaCompletaUseCase] Foto preservada: {
  "ruta_original": "pedidos/2760/prenda/...",
  "id": 296
}

[2026-01-28 HH:MM:SS] local.DEBUG: [ActualizarPrendaCompletaUseCase] Eliminando fotos no preservadas: {
  "fotos_a_eliminar": [295]  ← ID eliminado
}
```

---

## ✔️ VALIDACIÓN DEL FIX

### Checklist de Prueba

- [ ] Abrir prenda con 2 imágenes
- [ ] Abrir galería
- [ ] Eliminar imagen #1
- [ ] Ver en console: ✅ "Usando imágenes del storage"
- [ ] Ver en console: ✅ "Resumen: 0 nuevas, 1 existentes, total 1"
- [ ] Guardar prenda
- [ ] Ingresar novedad
- [ ] Confirmar guardado
- [ ] Verificar laravel.log:
  - [ ] "cantidad_fotos": 1 (no 2)
  - [ ] "Eliminando fotos no preservadas: [295]"
- [ ] Recargar página
- [ ] Verificar prenda: SOLO DEBE TENER 1 IMAGEN

---

**Fix Status: ✅ READY FOR TESTING**
