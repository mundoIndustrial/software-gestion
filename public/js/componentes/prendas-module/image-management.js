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
                // 🔴 CRÍTICO: Detectar si estamos en creación o edición
                const modalCreacion = document.getElementById('modal-agregar-prenda-nueva');
                const modalEdicion = document.querySelector('[id*="modal-editar"]') || document.querySelector('[class*="editar"]');
                
                console.log('[manejarImagenesPrenda] 🔄 Actualizando preview:', {
                    enCreacion: !!modalCreacion?.style?.display !== 'none',
                    enEdicion: !!modalEdicion
                });
                
                // En creación: usar actualizarPreviewPrenda()
                if (typeof actualizarPreviewPrenda === 'function') {
                    actualizarPreviewPrenda();
                    console.log('[manejarImagenesPrenda] ✅ Preview actualizado (creación)');
                }
                
                // En edición: usar PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar()
                if (typeof PrendaEditorImagenes !== 'undefined' && typeof PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar === 'function') {
                    PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar();
                    console.log('[manejarImagenesPrenda] ✅ Preview actualizado (edición)');
                }
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
        
        // 🔴 CRÍTICO: Mostrar SOLO UNA imagen a la vez con navegación
        console.log('[actualizarPreviewPrenda] 🖼️ Mostrando primera imagen de ' + imagenes.length);
        preview.innerHTML = '';
        preview.style.cursor = 'pointer';
        
        // Guardar todas las imágenes en el preview para navegación
        preview.dataset.imagenes = JSON.stringify(imagenes);
        preview.dataset.indiceActual = '0';
        
        // Renderizar solo la primera imagen
        const container = document.createElement('div');
        container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
        
        const img = document.createElement('img');
        img.src = imagenes[0].previewUrl;
        img.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
        console.log('[actualizarPreviewPrenda] 🎬 Src de imagen:', img.src);
        
        container.appendChild(img);
        preview.appendChild(container);
        console.log('[actualizarPreviewPrenda]  Imagen agregada al preview');
        
        // 🔴 NOTA: NO agregar controles de navegación en preview
        // El usuario solo quiere ver la primera imagen, navegación en el modal
        
        // Configurar drag & drop también cuando hay imágenes (para reemplazar)
        // Esto ya configura paste, click, drag & drop automáticamente
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

/**
 * 🔴 NUEVO: Agregar controles de navegación para múltiples imágenes de prenda
 * @param {HTMLElement} preview - Elemento preview
 * @param {Array} imagenes - Array de imágenes
 */
window.agregarControlesNavegacionPrenda = function(preview, imagenes) {
    // Crear contenedor de controles
    const controles = document.createElement('div');
    controles.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
        gap: 0.5rem;
    `;
    
    // Botón anterior
    const btnAnterior = document.createElement('button');
    btnAnterior.type = 'button';
    btnAnterior.innerHTML = '◀ Anterior';
    btnAnterior.style.cssText = `
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    `;
    
    // Indicador
    const indicador = document.createElement('span');
    indicador.style.cssText = `
        font-size: 0.75rem;
        color: #666;
        flex: 1;
        text-align: center;
    `;
    indicador.textContent = `1 de ${imagenes.length}`;
    
    // Botón siguiente
    const btnSiguiente = document.createElement('button');
    btnSiguiente.type = 'button';
    btnSiguiente.innerHTML = 'Siguiente ▶';
    btnSiguiente.style.cssText = `
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        background: #0066cc;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    `;
    
    // Función para cambiar imagen
    const cambiarImagen = (nuevoIndice) => {
        if (nuevoIndice < 0 || nuevoIndice >= imagenes.length) return;
        
        preview.dataset.indiceActual = nuevoIndice;
        const img = imagenes[nuevoIndice];
        
        // Encontrar la imagen en el preview y reemplazarla
        const imgEl = preview.querySelector('img');
        if (imgEl) {
            imgEl.src = img.previewUrl;
            imgEl.alt = `Imagen ${nuevoIndice + 1}`;
        }
        
        // Actualizar indicador
        indicador.textContent = `${nuevoIndice + 1} de ${imagenes.length}`;
        
        // Actualizar estado de botones
        btnAnterior.disabled = nuevoIndice === 0;
        btnSiguiente.disabled = nuevoIndice === imagenes.length - 1;
        
        console.log(`[agregarControlesNavegacionPrenda] Navegando a imagen ${nuevoIndice + 1} de ${imagenes.length}`);
    };
    
    // Event listeners
    btnAnterior.addEventListener('click', (e) => {
        e.stopPropagation();
        const indiceActual = parseInt(preview.dataset.indiceActual || '0');
        cambiarImagen(indiceActual - 1);
    });
    
    btnSiguiente.addEventListener('click', (e) => {
        e.stopPropagation();
        const indiceActual = parseInt(preview.dataset.indiceActual || '0');
        cambiarImagen(indiceActual + 1);
    });
    
    // Agregar controles al preview
    controles.appendChild(btnAnterior);
    controles.appendChild(indicador);
    controles.appendChild(btnSiguiente);
    preview.appendChild(controles);
    
    // Inicializar estado de botones
    btnAnterior.disabled = true;
    btnSiguiente.disabled = imagenes.length <= 1;
    
    console.log(`[agregarControlesNavegacionPrenda] Controles de navegación agregados para ${imagenes.length} imágenes`);
}

