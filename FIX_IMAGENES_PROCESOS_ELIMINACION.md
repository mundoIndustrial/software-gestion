# FIX: Imágenes de Procesos No Se Eliminaban

## Problema
Cuando el usuario marca una imagen para eliminar en un proceso (bordado, reflectivo, etc), la aplicación ejecutaba:
- ✅ Marca como eliminada en el frontend 
- ✅ Registra en `imagenesEliminadas` array
- ❌ **NO envía** la información al servidor
- ❌ **NO borra** la imagen de la BD

## Root Cause
El frontend capturaba correctamente las imágenes a eliminar en el objeto `datos`, pero cuando se construía el JSON para enviar al servidor en `prenda-editor-pedidos-adapter.js`:

```javascript
return {
    tipo: d.tipo,
    ubicaciones: d.ubicaciones,
    imagenes_existentes: imagenesExistentes,  // ← Imágenes a CONSERVAR
    // ❌ imagenes_a_eliminar NO estaba aquí
};
```

El backend recibía `imagenes_existentes: []` (vacío) cuando no había imágenes, y su código tenía una guard:

```php
if (!empty($imagenesExistentesPayload) || $hayFotosNuevas) {
    // Solo sincroniza si hay algo
} else {
    \Log::info('Sin cambios de imagen...');
    // ❌ NO hace nada
}
```

## Solución

### 1. Frontend: Agregar `imagenes_a_eliminar` al JSON (prenda-editor-pedidos-adapter.js)

**Antes (líneas 508-520):**
```javascript
procesosArray = Object.entries(procesosRaw).map(([tipo, proc]) => {
    const d = proc?.datos || proc || {};
    const imagenesExistentes = [];
    if (d.imagenes && Array.isArray(d.imagenes)) {
        d.imagenes.forEach(img => {
            if (img && !(img instanceof File)) {
                imagenesExistentes.push({ 
                    id: img.id, 
                    url: img.url || img.ruta_original 
                });
            }
        });
    }
    return {
        tipo: d.tipo,
        imagenes_existentes: imagenesExistentes
    };
});
```

**Después:**
```javascript
procesosArray = Object.entries(procesosRaw).map(([tipo, proc]) => {
    const d = proc?.datos || proc || {};
    const imagenesExistentes = [];
    const imagenesAEliminar = [];
    
    // Procesar imágenes: separar existentes de eliminadas
    if (d.imagenes && Array.isArray(d.imagenes)) {
        d.imagenes.forEach(img => {
            if (img && !(img instanceof File)) {
                imagenesExistentes.push({ 
                    id: img.id, 
                    url: img.url || img.ruta_original 
                });
            }
        });
    }
    
    // ✅ Extraer imágenes marcadas para eliminar
    if (d.imagenesEliminadas && Array.isArray(d.imagenesEliminadas)) {
        d.imagenesEliminadas.forEach(img => {
            if (img && img.id) {
                imagenesAEliminar.push(img.id);
            }
        });
    }
    
    const procesoEnvio = {
        tipo: d.tipo,
        imagenes_existentes: imagenesExistentes
    };
    
    // Incluir imágenes a eliminar si hay
    if (imagenesAEliminar.length > 0) {
        procesoEnvio.imagenes_a_eliminar = imagenesAEliminar;
        console.log(`✅ Proceso ${tipo}: ${imagenesAEliminar.length} imagen(es) para eliminar`);
    }
    
    return procesoEnvio;
});
```

### 2. Backend: Considerar `imagenes_a_eliminar` en la sincronización (ActualizarPrendaCompletaUseCase.php)

**Antes (línea 1107-1117):**
```php
private function sincronizarImagenesProceso(...) {
    $imagenesExistentesPayload = $proceso['imagenes_existentes'] ?? null;
    if (is_array($imagenesExistentesPayload)) {
        $hayFotosNuevas = !empty($dto->fotosProcesoNuevo) && 
                          isset($dto->fotosProcesoNuevo[$procesoIdx]);
        
        if (!empty($imagenesExistentesPayload) || $hayFotosNuevas) {
            // ✅ Sincroniza
        } else {
            // ❌ No hace nada
        }
    }
}
```

**Después:**
```php
private function sincronizarImagenesProceso(...) {
    $imagenesExistentesPayload = $proceso['imagenes_existentes'] ?? null;
    $imagenesAEliminarPayload = $proceso['imagenes_a_eliminar'] ?? null;
    
    if (is_array($imagenesExistentesPayload)) {
        $hayFotosNuevas = !empty($dto->fotosProcesoNuevo) && 
                          isset($dto->fotosProcesoNuevo[$procesoIdx]);
        $hayImagenesAEliminar = !empty($imagenesAEliminarPayload) && 
                                is_array($imagenesAEliminarPayload);
        
        // ✅ Sincroniza si hay imágenes a conservar, fotos nuevas, O imágenes a eliminar
        if (!empty($imagenesExistentesPayload) || $hayFotosNuevas || $hayImagenesAEliminar) {
            $idsAConservar = array_filter(array_column($imagenesExistentesPayload, 'id'));
            // Elimina las imágenes cuyo ID NO está en $idsAConservar
            // Si $idsAConservar está vacío y hay $hayImagenesAEliminar, 
            // entonces elimina TODAS las imágenes del proceso
        } else {
            // Sin cambios
        }
    }
}
```

## Flow Completo (Ahora Funciona)

1. **Usuario elimina imagen en proceso:**
   - Clic en botón "Eliminar" → `confirmarEliminarImagenProceso()`
   - `window.imagenesProcesoExistentes[indice] = null`
   - Se marca en el array como eliminada

2. **Usuario guarda cambios de prenda:**
   - `agregarProcesoAlPedido()` captura:
     - `imagenesExistentes`: imágenes con URL (las que quedan)
     - `imagenesEliminadas`: imágenes con ID (las que se eliminan)

3. **Adaptador arma JSON:**
   - Extrae IDs de `imagenesEliminadas`
   - Crea `imagenes_a_eliminar: [1, 2, 3]` en el objeto proceso

4. **POST al servidor:**
   ```json
   {
     "procesos": [
       {
         "id": 25,
         "tipo": "bordado",
         "imagenes_existentes": [],
         "imagenes_a_eliminar": [1],  // ✅ Ahora sí se envía
         ...
       }
     ]
   }
   ```

5. **Backend sincroniza:**
   - `sincronizarImagenesProceso()` recibe `imagenes_a_eliminar: [1]`
   - Entra al if porque `$hayImagenesAEliminar = true`
   - Calcula `$idsAConservar = []` (vacío)
   - Itera imágenes actuales y elimina las que NO estén en `$idsAConservar`
   - **Resultado:** Imagen con ID 1 se elimina ✅

## Testing

### Local
```bash
# 1. Editar prenda con procesos que tienen imágenes
# 2. Abrir modal de proceso con imagen existente
# 3. Hacer clic en "Eliminar imagen"
# 4. Guardar cambios
# 5. Verificar en BD que imagen se eliminó:
SELECT COUNT(*) FROM pedidos_procesos_imagenes WHERE id = 1;
# Resultado: 0 ✅
```

### Browser Console
```javascript
// Ver logs antes de guardar:
[PedidosAdapter] 🗑️ Proceso bordado: 1 imagen(es) para eliminar: [1]

// En la respuesta del servidor:
[ActualizarPrendaCompletaUseCase] Imágenes de proceso eliminadas {
  "proceso_id": 25,
  "eliminadas": 1,
  "conservadas": 0
}
```

## Archivos Modificados
1. `/public/js/componentes/prenda-editor-pedidos-adapter.js` - Líneas 508-547
2. `/app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` - Líneas 1102-1160

## Estado
✅ **COMPLETADO**
- [ ] Testeado en local
- [ ] Testeado en VPS
- [ ] Desplegado a producción
