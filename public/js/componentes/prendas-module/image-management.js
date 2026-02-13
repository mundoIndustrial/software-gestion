/**
 * ================================================
 * IMAGE MANAGEMENT
 * ================================================
 * 
 * Funciones para manejar imágenes de prendas y telas
 * Delega a los servicios de almacenamiento correspondientes
 * 
 * @module ImageManagement
 */

/**
 * WRAPPER: Maneja la carga de imágenes para prendas
 * Delega a window.imagenesPrendaStorage (ImageStorageService)
 */
window.manejarImagenesPrenda = function(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    try {
        if (!window.imagenesPrendaStorage) {
            alert('Error: Servicio de almacenamiento de imágenes no inicializado');
            return;
        }
        
        window.imagenesPrendaStorage.agregarImagen(input.files[0])
            .then(() => {
                actualizarPreviewPrenda();
            })
            .catch(err => {
                if (err.message === 'MAX_LIMIT') {
                    mostrarModalLimiteImagenes();
                } else if (err.message === 'INVALID_FILE') {
                    mostrarModalError('El archivo debe ser una imagen válida');
                } else {
                    mostrarModalError('Error al procesar la imagen: ' + err.message);
                }
            });
    } catch (err) {
        mostrarModalError('Error al procesar imagen: ' + err.message);
    }
    
    input.value = '';
};

/**
 * WRAPPER: Actualiza el preview de las imágenes de prenda
 * Usa window.imagenesPrendaStorage para obtener las imágenes
 */
window.actualizarPreviewPrenda = function() {
    console.log('[actualizarPreviewPrenda] 🎬 Iniciando actualización del preview');
    
    try {
        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        const btn = document.getElementById('nueva-prenda-foto-btn');
        console.log('[actualizarPreviewPrenda]  Elementos DOM:', {
            preview: preview ? 'ENCONTRADO' : 'NO ENCONTRADO',
            contador: contador ? 'ENCONTRADO' : 'NO ENCONTRADO',
            btn: btn ? 'ENCONTRADO' : 'NO ENCONTRADO'
        });
        
        if (!preview) {
            console.warn('[actualizarPreviewPrenda]  Preview element no encontrado');
            return;
        }
        
        if (!window.imagenesPrendaStorage) {
            console.warn('[actualizarPreviewPrenda]  imagenesPrendaStorage no disponible');
            return;
        }
        
        const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
        console.log('[actualizarPreviewPrenda]  Imágenes cargadas:', imagenes.length);
        
        // Si no hay imágenes, mostrar placeholder con drag & drop
        if (imagenes.length === 0) {
            console.log('[actualizarPreviewPrenda] 📭 Sin imágenes, mostrando placeholder con drag & drop');
            preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click o arrastra para agregar</div></div>';
            preview.style.cursor = 'pointer';
            if (contador) contador.textContent = '';
            if (btn) btn.style.display = 'block';
            
            // Agregar event listeners para drag & drop
            setupDragAndDrop(preview);
            return;
        }
        
        // Mostrar primera imagen
        console.log('[actualizarPreviewPrenda] 🖼️ Mostrando primera imagen');
        preview.innerHTML = '';
        preview.style.cursor = 'pointer';
        
        const img = document.createElement('img');
        img.src = imagenes[0].previewUrl;
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
        console.log('[actualizarPreviewPrenda] 🎬 Src de imagen:', img.src);
        
        // NO asignar click handler aquí - PrendaDragDropHandler ya maneja los clicks
        // El handler en PrendaDragDropHandler._onClickConImagenes() se encargará de abrir la galería
        
        preview.appendChild(img);
        console.log('[actualizarPreviewPrenda]  Imagen agregada al preview');
        
        // Configurar drag & drop también cuando hay imágenes (para reemplazar)
        setupDragAndDropConImagen(preview, imagenes);
        
        if (contador) {
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
        }
        
        if (btn) {
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        // 🔄 IMPORTANTE: Notificar al DragDropManager que las imágenes han cambiado
        // Esto hará que el handler se reconfigure si es necesario
        if (window.dragDropManager && typeof window.dragDropManager.actualizarImagenesPrenda === 'function') {
            window.dragDropManager.actualizarImagenesPrenda(imagenes);
            console.log('[actualizarPreviewPrenda] ✅ DragDropManager notificado de cambios en imágenes');
        } else {
            console.log('[actualizarPreviewPrenda] ⚠️ DragDropManager no disponible para notificación');
        }
        
    } catch (e) {
        console.error('[actualizarPreviewPrenda]  Error:', e);
    }
};

/**
 * WRAPPER: Maneja la carga de imágenes para telas
 */
window.manejarImagenTela = function(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    try {
        if (!window.imagenesTelaStorage) {
            alert('Error: Servicio de almacenamiento de imágenes de tela no inicializado');
            return;
        }
        
        const promesa = window.imagenesTelaStorage.agregarImagen(input.files[0]);
        
        // Soporte para ambos tipos de storage (Promise y callback)
        if (promesa && typeof promesa.then === 'function') {
            // Versión Promise
            promesa
                .then((resultado) => {
                    if (typeof actualizarPreviewTela === 'function') {
                        actualizarPreviewTela();
                    } else {
                        console.warn('actualizarPreviewTela no disponible');
                    }
                })
                .catch(err => {
                    if (err.message === 'MAX_LIMIT') {
                        if (typeof mostrarModalLimiteImagenes === 'function') {
                            mostrarModalLimiteImagenes();
                        }
                    } else {
                        alert('Error al procesar imagen: ' + err.message);
                    }
                });
        } else if (promesa && promesa.success === true) {
            // Versión callback
            if (typeof actualizarPreviewTela === 'function') {
                actualizarPreviewTela();
            }
        } else if (promesa && promesa.reason === 'MAX_LIMIT') {
            if (typeof mostrarModalLimiteImagenes === 'function') {
                mostrarModalLimiteImagenes();
            }
        }
        
    } catch (err) {
        alert('Error al procesar imagen de tela: ' + err.message);
    }
    
    input.value = '';
};

/**
 * WRAPPER: Actualiza el preview temporal de imágenes de tela
 * Renderiza DENTRO de la celda de imagen de la fila de inputs
 */
window.actualizarPreviewTela = function() {
    try {
        const preview = document.getElementById('nueva-prenda-tela-preview');
        
        if (!preview) {
            return;
        }
        
        // Verificar que el servicio existe
        if (!window.imagenesTelaStorage) {
            return;
        }
        
        // Obtener imágenes del storage temporal
        const imagenes = window.imagenesTelaStorage.obtenerImagenes();
        
        // Limpiar preview anterior
        preview.innerHTML = '';
        
        // Si hay imágenes, mostrarlas como miniaturas dentro de la celda
        if (imagenes.length > 0) {
            // Hacer visible el preview cuando hay imágenes (dentro de la celda)
            preview.style.display = 'flex';
            preview.style.visibility = 'visible';
            preview.style.opacity = '1';
            preview.style.height = 'auto';
            preview.style.overflow = 'visible';
            
            imagenes.forEach((img, index) => {
                const container = document.createElement('div');
                container.style.cssText = 'position: relative; width: 60px; height: 60px; flex-shrink: 0;';
                
                const imgElement = document.createElement('img');
                imgElement.src = img.previewUrl;
                imgElement.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid #0066cc; cursor: pointer; transition: opacity 0.2s;';
                imgElement.onclick = () => window.mostrarGaleriaImagenesTemporales(imagenes, index);
                imgElement.onmouseover = () => imgElement.style.opacity = '0.7';
                imgElement.onmouseout = () => imgElement.style.opacity = '1';
                
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.innerHTML = '×';
                btnEliminar.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: background 0.2s;';
                btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
                btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
                btnEliminar.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    window.imagenesTelaStorage.eliminarImagen(index);
                    actualizarPreviewTela(); // Actualizar el preview después de eliminar
                };
                
                container.appendChild(imgElement);
                container.appendChild(btnEliminar);
                preview.appendChild(container);
            });
            
            // 🔥 IMPORTANTE: Configurar drag & drop en el preview cuando hay imágenes
            if (typeof window.setupDragDropTelaPreview === 'function') {
                window.setupDragDropTelaPreview(preview);
                console.log('[actualizarPreviewTela]  Drag & drop configurado en preview con imágenes');
            }
            
        } else {
            // Ocultar preview si no hay imágenes
            preview.style.display = 'none';
        }
    } catch (e) {
        console.error('[actualizarPreviewTela] Error:', e);
    }
}
