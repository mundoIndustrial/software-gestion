# 🔧 FIX: Imágenes No Se Eliminan al Guardar Prenda - 27 ENE 2026

## 🎯 Problema Identificado
Cuando el usuario elimina una imagen desde la galería modal y luego guarda la prenda, la imagen **NO se elimina en la BD**. 

**Síntomas en los logs:**
```
prendas-wrappers.js:652 🗑️ [mostrarGaleriaImagenesPrenda] Eliminando imagen en índice 0
prendas-wrappers.js:662 ✅ Imagen eliminada del array original
```

Pero luego el servidor recibe:
```
"imagenes_existentes":"[{\"previewUrl\":\"/storage/prendas/prenda_20260127212136_964920.webp\",\"nombre\":\"imagen_1.webp\"}]"
```

La imagen sigue ahí porque el servidor no sabe que fue eliminada.

---

## 🔍 Causa Raíz

### El problema está en la falta de sincronización entre dos sources:

1. **Frontend - Galería Modal** (`prendas-wrappers.js`):
   - Cuando el usuario abre la galería, se crea un array local `imagenes`
   - Cuando elimina, ese array se actualiza correctamente
   - PERO este array no se sincroniza con ningún lugar

2. **Frontend - Modal de Novedad** (`modal-novedad-edicion.js` y `modal-novedad-prenda.js`):
   - Guardan desde `this.prendaData.imagenes` (que es estático, cargado al abrir el modal)
   - NO consultan el estado actualizado del array de la galería
   - Por eso envían las imágenes "sin eliminar" al servidor

### El flujo defectuoso:
```
1. Modal se abre → this.prendaData.imagenes = [img1, img2] ← SNAPSHOT
2. Usuario abre galería → imagenes eliminadas localmente
3. Usuario guarda → this.prendaData.imagenes SIGUE CON [img1, img2] ← SNAPSHOT SIN CAMBIOS
4. Backend recibe imagenes_existentes = [img1, img2]
5. Backend preserva ambas imágenes ❌
```

---

## ✅ Solución Implementada

### 1. **Frontend - Sincronización de Imágenes** 
**Archivos modificados:**
- `public/js/componentes/modal-novedad-edicion.js` (líneas 397-450)
- `public/js/componentes/modal-novedad-prenda.js` (líneas 157-215)

**Cambio:**
En lugar de leer desde `this.prendaData.imagenes` (estático), ahora consultamos `window.imagenesPrendaStorage` (dinámico):

```javascript
// 🔧 FIX: Obtener imágenes ACTUALIZADAS desde window.imagenesPrendaStorage
// NO desde this.prendaData.imagenes que es estático
let imagenesActuales = this.prendaData.imagenes || [];

// Si existen imágenes en el storage (editadas por el usuario), usar esas
if (window.imagenesPrendaStorage && typeof window.imagenesPrendaStorage.obtenerImagenes === 'function') {
    const imagenesDelStorage = window.imagenesPrendaStorage.obtenerImagenes();
    if (imagenesDelStorage && imagenesDelStorage.length > 0) {
        console.log('[modal-novedad-edicion] ✅ Usando imágenes del storage (incluye eliminaciones):', imagenesDelStorage.length);
        imagenesActuales = imagenesDelStorage;
    } else if (imagenesDelStorage && imagenesDelStorage.length === 0) {
        // El usuario eliminó todas las imágenes
        console.log('[modal-novedad-edicion] ⚠️ El usuario eliminó todas las imágenes');
        imagenesActuales = [];
    }
}
```

**Efecto:**
- ✅ Cuando el usuario elimina una imagen, `window.imagenesPrendaStorage` se actualiza
- ✅ Al guardar, se lee desde ese storage actualizado
- ✅ Se envía al servidor el estado ACTUAL, no el inicial

---

### 2. **Backend - Lógica de Merge Correcta**
**Archivo modificado:**
- `app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php` (línea ~145)

**Cambio anterior (defectuoso):**
```php
fotos: (!empty($imagenes) ? array_merge($imagenesExistentes ?? [], $imagenes) : null)
```
Problema: Cuando el usuario elimina todas las imágenes:
- `$imagenes` = [] (vacío)
- `$imagenesExistentes` = [] (el frontend envía array vacío)
- Resultado: `fotos = null` (no hace nada) ❌

**Cambio nuevo (correcto):**
```php
fotos: isset($data['imagenes_existentes']) 
    ? array_merge($imagenesExistentes ?? [], $imagenes ?? [])
    : ((!empty($imagenes)) ? $imagenes : null)
```

**Lógica:**
- Si se envió `imagenes_existentes` (explícito): usar MERGE (preservar existentes + agregar nuevas)
- Si está vacío: resulta en array vacío → backend lo interpreta como "eliminar todas" ✅
- Si no se envió: null → no tocar (actualización parcial)

---

### 3. **Backend - Debug Logging**
**Archivo modificado:**
- `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` (línea ~125)

**Agregué logs para verificar:**
```php
\Log::info('[ActualizarPrendaCompletaUseCase] actualizarFotos - Iniciando', [
    'prenda_id' => $prenda->id,
    'dto->fotos' => $dto->fotos,
    'es_null' => is_null($dto->fotos),
    'es_empty' => empty($dto->fotos),
    'cantidad_fotos' => is_array($dto->fotos) ? count($dto->fotos) : 'N/A'
]);

if (empty($dto->fotos)) {
    \Log::info('[ActualizarPrendaCompletaUseCase] fotos es array VACÍO - ELIMINAR todas las imágenes', [
        'prenda_id' => $prenda->id,
        'fotosActuales' => $prenda->fotos()->count()
    ]);
}
```

---

## 🧪 Cómo Verificar que el Fix Funciona

### Paso 1: Abrir prenda con imágenes
```
1. Navegar a editar una prenda existente que tiene imágenes
2. Se abre el modal de novedad
3. Se hace visible la galería de imágenes
```

### Paso 2: Eliminar una imagen
```
1. Click en botón "Eliminar" (🗑️) en la galería
2. Confirmar eliminación
3. Verificar console: debe ver
   ✅ Imagen eliminada del array original
   ✅ Usando imágenes del storage (incluye eliminaciones)
```

### Paso 3: Guardar prenda
```
1. Click en "Guardar cambios"
2. Ingresar novedad
3. Click en "✓ Guardar Cambios"
```

### Paso 4: Verificar en Laravel logs
```
tail storage/logs/laravel.log | grep "actualizarFotos"
```

Esperado:
```json
{
    "prenda_id": 3472,
    "dto->fotos": [],  // ← Vacío significa ELIMINAR
    "es_empty": true,
    "cantidad_fotos": 0,
    "fotosActuales": 2
}

"fotos es array VACÍO - ELIMINAR todas las imágenes"
```

### Paso 5: Recargar página y verificar
La prenda **NO debe tener imágenes** en la BD.

---

## 📋 Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `public/js/componentes/modal-novedad-edicion.js` | Líneas 397-450: Sincronizar con storage de imágenes |
| `public/js/componentes/modal-novedad-prenda.js` | Líneas 157-215: Sincronizar con storage de imágenes |
| `app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php` | Línea ~145: Lógica correcta de merge |
| `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` | Línea ~125: Logs detallados |

---

## 🎓 Lecciones Aprendidas

1. **State Management**: Cuando hay múltiples componentes que modifican arrays, necesitan compartir el mismo source of truth
2. **Snapshot vs Dynamic**: `this.prendaData` era un snapshot inicial, no se actualiza automáticamente
3. **Array vs Null**: La diferencia entre `[]` (vacío) y `null` (no tocar) es crítica en operaciones CRUD
4. **Explicitness**: El backend necesita saber explícitamente que "quiero eliminar todo" vs "no quiero tocar esto"

---

## 🔮 Mejoras Futuras

1. **Reactive UI**: Usar un framework reactivo (Vue, React) para sincronizar automáticamente
2. **Events**: Emitir eventos cuando cambia el estado de imágenes
3. **State Machine**: Implementar máquina de estados para flujos complejos
4. **TypeScript**: Añadir tipos para evitar errores de this vs that

---

**Estado:** ✅ IMPLEMENTADO - LISTO PARA TESTING
**Fecha:** 27-01-2026
**Autor:** GitHub Copilot
