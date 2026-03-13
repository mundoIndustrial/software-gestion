# Test: Guardar Imágenes en Modo Edición/Borrador

## Flujo Esperado

### 1. Frontend (guardarComoBorrador)
```javascript
// Separar imágenes nuevas de existentes
const eppsProcesados = (datos.epps || []).map((e, eppIndex) => {
    const imagenesExistentes = [];
    
    if (Array.isArray(e.imagenes)) {
        e.imagenes.forEach((img, imgIndex) => {
            // Si es File object → enviar como archivo en FormData
            if (img instanceof File || (img.file && img.file instanceof File)) {
                const file = img instanceof File ? img : img.file;
                const fieldName = `epps.${eppIndex}.imagenes.${imgIndex}`;
                formData.append(fieldName, file);  // ← Archivo real
            }
            // Si es URL → incluir solo en JSON
            else {
                if (typeof img === 'string') imageUrl = img;
                else if (img.url) imageUrl = img.url;
                // ... más propiedades
                
                if (imageUrl) {
                    imagenesExistentes.push(imageUrl);
                }
            }
        });
    }
    
    return {
        epp_id: e.epp_id,
        cantidad: e.cantidad,
        observaciones: e.observaciones,
        imagenes: imagenesExistentes  // ← Solo URLs existentes
    };
});
```

### 2. Backend (actualizarBorrador)
```php
// Para cada EPP
foreach ($eppsCrudos as $eppIndex => $eppData) {
    // Verificar si hay archivos nuevos
    $hayImagenesNuevas = false;
    $imgIdx = 0;
    while (true) {
        $formKey = "epps.{$eppIndex}.imagenes.{$imgIdx}";
        if (!$request->hasFile($formKey)) {
            break;  // No hay más archivos
        }
        $hayImagenesNuevas = true;
        $imgIdx++;
    }
    
    // Si hay archivos nuevos → eliminar antiguas
    if ($hayImagenesNuevas) {
        // Buscar y eliminar: PedidoEppImagen, archivos en Storage
        // Elimina carpeta entera si es necesario
    }
    
    // Ejecutar procesarImagenesDeEpps()
    // Que maneja:
    // - Guardar archivos FormData nuevos
    // - Si no hay archivos, copiar URLs del JSON
}
```

## Casos de Prueba

### Caso 1: Agregar nuevas imágenes
1. Editar EPP existente (sin imágenes)
2. Arrastrar 2 imágenes al modal
3. Guardar Borrador
4. Verificar: 2 imágenes nuevas guardadas en BD

### Caso 2: Reemplazar imágenes
1. Editar EPP con 3 imágenes existentes
2. Eliminar 2 imágenes en el modal
3. Agregar 1 imagen nueva
4. Guardar Borrador
5. Verificar: 1 imagen nueva en BD (que era antigua se eliminó)

### Caso 3: Mantener existentes
1. Editar EPP con 2 imágenes
2. No agregar ni eliminar imágenes
3. Guardar Borrador
4. Verificar: 2 imágenes originales persisten

### Caso 4: Eliminar todas
1. Editar EPP con 3 imágenes
2. Eliminar las 3 en el modal
3. Guardar Borrador
4. Verificar: 0 imágenes en BD

## Logs Esperados (Backend)

```
[ACTUALIZAR-BORRADOR] Eliminando imágenes antiguas de EPP [pedido_epp_id=123, epp_id=5, imagenes_a_eliminar=3]
[CrearPedidoEditableController] Procesando archivos FormData para EPP
[CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP) [webp=/pedidos/150/epp/epp_5_img_0.webp]
```

## Logs Esperados (Frontend)

```
[guardarComoBorrador] Agregado archivo nuevo de EPP 5: photo1.jpg
[guardarComoBorrador] Agregado archivo nuevo de EPP 5: photo2.jpg
[guardarComoBorrador] Datos a enviar: { epps: [{epp_id: 5, cantidad: 10, imagenes: []}] }
```

## Status
- [ ] Caso 1: Agregar nuevas
- [ ] Caso 2: Reemplazar  
- [ ] Caso 3: Mantener
- [ ] Caso 4: Eliminar todas
