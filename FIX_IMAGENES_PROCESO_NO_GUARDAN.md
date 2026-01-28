# 🖼️ FIX: Imágenes del Proceso No Se Guardan

**Problema:**
Las imágenes del proceso NO se están guardando cuando se edita un proceso.

**Log observado:**
```
[PROCESOS-ACTUALIZAR] Imágenes actualizadas: {"eliminadas":0,"agregadas":0,"total_final":0}
```

---

## 🔍 Análisis del Flujo

### Frontend (proceso-editor.js)
1. Usuario abre modal para editar proceso
2. `cargarDatosProcesoEnModal()` carga las imágenes en `window.imagenesProcesoActual`
3. Usuario guarda cambios
4. `registrarCambioImagenes(window.imagenesProcesoActual)` se llama
5. Array de imágenes se envía en PATCH

### Backend (PrendaPedidoEditController.php)
1. Recibe array `$validated['imagenes']`
2. Obtiene imágenes actuales de BD
3. Calcula diferencias (a agregar/eliminar)
4. **PROBLEMA**: `$imagenesNuevas` llega vacío

---

## 🛠️ Soluciones Implementadas

### 1. Frontend: proceso-editor.js

**Agregado método `_normalizarImagenes()`:**
```javascript
_normalizarImagenes(imagenes) {
    if (!Array.isArray(imagenes)) {
        return [];
    }

    return imagenes
        .map(img => {
            if (typeof img === 'string') {
                return img.trim();
            }
            return null;
        })
        .filter(img => img && img !== 'null' && img.length > 0);
}
```

**Llamado desde `obtenerCambios()`:**
```javascript
if (this.cambios.imagenes !== null) {
    cambiosFinales.imagenes = this._normalizarImagenes(this.cambios.imagenes);
}
```

**Beneficios:**
- Elimina strings "null"
- Elimina valores vacíos
- Trim de espacios

### 2. Backend: PrendaPedidoEditController.php

**Mejorado el filtrado de imágenes:**
```php
$imagenesNuevas = array_values(array_filter($validated['imagenes'], function($img) {
    return !empty($img) && $img !== 'null' && is_string($img) && trim($img) !== '';
}));
```

**Agregado logging detallado:**
```php
\Log::info('[PROCESOS-ACTUALIZAR] Procesando imágenes:', [
    'raw_imagenes' => $validated['imagenes'],
    'total_recibidas' => count($validated['imagenes'])
]);

\Log::info('[PROCESOS-ACTUALIZAR] Imágenes después de filtrado:', [
    'actuales' => $imagenesActuales,
    'nuevas' => $imagenesNuevas,
    'total_nuevas' => count($imagenesNuevas)
]);
```

**Trimming de rutas antes de guardar:**
```php
'ruta_webp' => trim($ruta),
```

---

## 📋 Causas Posibles del Problema

### 1. Arrays vacíos desde el frontend
- Las imágenes no se están capturando correctamente en `imagenesProcesoActual`
- El array tiene elementos `null` que se filtran en el backend

**Solución:** Logging mejorado ayudará a detectar esto

### 2. Imágenes como File objects en lugar de strings
- Si el usuario carga una imagen nueva, es un `File` object, no una URL string
- El backend espera strings (URLs)

**Solución:** El frontend debe convertir Files a URLs o subirlas primero

### 3. Falta de validación en validación de Laravel
- Las imágenes podrían no pasar la validación `'imagenes' => 'nullable|array'`

**Revisar:** La rule validation actual es muy permisiva, permitiría cualquier array

---

## ✅ Testing y Verificación

### Paso 1: Verificar logging
Después de editar un proceso con imágenes:
```bash
tail -f storage/logs/laravel.log | grep "PROCESOS-ACTUALIZAR"
```

Deberías ver:
```
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"raw_imagenes":[...], "total_recibidas":X}
[PROCESOS-ACTUALIZAR] Imágenes después de filtrado: {"actuales":[...], "nuevas":[...]}
[PROCESOS-ACTUALIZAR] Resumen imágenes: {"eliminadas":X,"agregadas":X,"total_final":X}
```

### Paso 2: Verificar BD
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113 
ORDER BY orden;
```

Deberías ver las imágenes insertadas

### Paso 3: Verificar frontend
En la consola del navegador:
```javascript
console.log(window.imagenesProcesoActual)
// Debe mostrar array de URLs, no nulls
```

---

## 🚀 Mejoras Futuras Recomendadas

1. **Cargar archivos nuevos al servidor**
   - Si el usuario agrega una imagen nueva, subirla a `/storage/procesos/`
   - Retornar URL para guardar en BD
   - Actualmente solo se soportan URLs existentes

2. **Validación más estricta**
   ```php
   'imagenes' => 'nullable|array',
   'imagenes.*' => 'nullable|url|max:2048',
   ```

3. **Mejor manejo de imágenes del lado del cliente**
   - Detectar si es File o string
   - Convertir Files a URLs (blob o uploadear)

4. **Usar FormData para archivos**
   - El PATCH actual usa JSON
   - Si hay archivos nuevos, usar multipart/form-data

---

## 📝 Archivo

s Modificados

1. **[proceso-editor.js](../public/js/modulos/crear-pedido/procesos/services/proceso-editor.js)**
   - Línea ~205: Agregado método `_normalizarImagenes()`
   - Línea ~195: Llamado en `obtenerCambios()`

2. **[PrendaPedidoEditController.php](../app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php)**
   - Línea ~515: Mejorado filtrado de imágenes
   - Línea ~515-575: Agregado logging detallado
   - Línea ~545: Trimming de rutas

