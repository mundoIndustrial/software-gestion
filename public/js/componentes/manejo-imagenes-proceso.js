/**
 * ================================================
 * MANEJO DE IMÁGENES DE PROCESOS
 * ================================================
 * 
 * Funciones para manejar imágenes de procesos individuales
 * Compatible con el sistema de drag & drop y el HTML existente
 * 
 * @module ManejoImagenesProceso
 * @version 2.0.0
 */

/**
 * Manejar imagen de proceso individual
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
 */
window.manejarImagenProceso = function(input, procesoIndex) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    try {
        const file = input.files[0];
        
        // Validar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor selecciona un archivo de imagen válido');
            return;
        }
        
        // Validar tamaño (máximo 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            mostrarModalError('El archivo es demasiado grande (máximo 5MB)');
            return;
        }
        
        // ✅ CAMBIO: Usar URL.createObjectURL en lugar de FileReader.readAsDataURL (NO base64)
        const objectUrl = URL.createObjectURL(file);
        
        // Determinar el índice del cuadro visual vs el índice de storage
        const previewIndex = window._procesoQuadroIndex || procesoIndex;
        
        // Actualizar el preview específico
        const previewElement = document.getElementById(`proceso-foto-preview-${previewIndex}`);
        if (previewElement) {
            // Limpiar objectURL anterior si existe (prevenir memory leaks)
            if (previewElement._objectUrl) {
                URL.revokeObjectURL(previewElement._objectUrl);
            }
            previewElement._objectUrl = objectUrl;
            
            // Limpiar contenido anterior
            previewElement.innerHTML = '';
            previewElement.style.background = '';
            previewElement.style.border = '2px solid #0066cc';
            
            // Crear imagen con blob URL
            const imgElement = document.createElement('img');
            imgElement.src = objectUrl;
            imgElement.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 6px; cursor: pointer; transition: opacity 0.2s;';
            imgElement.onclick = () => {
                const functionNamePascal = `abrirGaleriaProceso${procesoIndex}`;
                const functionNameLower = `abrirGaleriaproceso${procesoIndex}`;
                const galeriaFunction = window[functionNamePascal] || window[functionNameLower];
                
                if (typeof galeriaFunction === 'function') {
                    const imagenes = window.procesosImagenesStorage ? 
                        window.procesosImagenesStorage.obtenerImagenes(procesoIndex).map(img => img.previewUrl || (img.file ? URL.createObjectURL(img.file) : '')) : 
                        [objectUrl];
                    galeriaFunction(imagenes);
                }
            };
            imgElement.onmouseover = () => imgElement.style.opacity = '0.8';
            imgElement.onmouseout = () => imgElement.style.opacity = '1';
            
            // Botón para eliminar imagen
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.innerHTML = '×';
            btnEliminar.style.cssText = `
                position: absolute;
                top: -8px;
                right: -8px;
                width: 24px;
                height: 24px;
                background: #ef4444;
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                font-size: 16px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                transition: background 0.2s;
                z-index: 10;
            `;
            btnEliminar.onmouseover = () => btnEliminar.style.background = '#dc2626';
            btnEliminar.onmouseout = () => btnEliminar.style.background = '#ef4444';
            btnEliminar.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                eliminarImagenProceso(previewIndex, procesoIndex);
            };
            
            // Posicionar elementos
            previewElement.style.position = 'relative';
            previewElement.appendChild(imgElement);
            previewElement.appendChild(btnEliminar);
            
            // Actualizar drag & drop si es necesario
            if (typeof window.actualizarDragDropProceso === 'function') {
                window.actualizarDragDropProceso(procesoIndex);
            }
        }
        
        // ✅ CAMBIO: Guardar File object en storage (NO base64)
        if (window.procesosImagenesStorage) {
            window.procesosImagenesStorage.agregarImagen(procesoIndex, {
                file: file,
                previewUrl: objectUrl,
                name: file.name,
                size: file.size,
                type: file.type,
                fechaCreacion: new Date().toISOString()
            });
        }
        
        // ✅ NUEVO: Sincronizar con window.imagenesProcesoActual (usado por agregarProcesoAlPedido)
        if (!window.imagenesProcesoActual) {
            window.imagenesProcesoActual = [null, null, null];
        }
        window.imagenesProcesoActual[procesoIndex - 1] = file;
        
    } catch (error) {
        mostrarModalError('Error al procesar la imagen');
    }
    
    // Limpiar input
    input.value = '';
};

/**
 * Eliminar imagen de un proceso específico
 * @param {number} previewIndex - Índice del preview HTML (1, 2, 3) - del cuadro en el modal
 * @param {number} procesoIndex - Índice del proceso en el storage (1, 2, 3)
 */
window.eliminarImagenProceso = function(previewIndex, procesoIndex) {
    // Soportar llamadas antiguas con un solo parámetro (backward compatibility)
    if (procesoIndex === undefined) {
        procesoIndex = previewIndex;
    }
    
    try {
        const previewElement = document.getElementById(`proceso-foto-preview-${previewIndex}`);
        if (previewElement) {
            // ✅ Limpiar objectURL si existe (prevenir memory leaks)
            if (previewElement._objectUrl) {
                URL.revokeObjectURL(previewElement._objectUrl);
                previewElement._objectUrl = null;
            }
            
            // Restaurar el placeholder
            previewElement.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${previewIndex}</div>
                </div>
            `;
            
            // Restaurar estilos
            previewElement.style.background = '#f9fafb';
            previewElement.style.border = '2px dashed #0066cc';
            previewElement.style.transform = '';
            previewElement.style.boxShadow = '';
        }
        
        // ✅ Sincronizar con window.imagenesProcesoActual
        if (window.imagenesProcesoActual && window.imagenesProcesoActual[procesoIndex - 1]) {
            window.imagenesProcesoActual[procesoIndex - 1] = null;
        }
        
        // NOTA: NO eliminar del storage aquí - ya se eliminó desde _eliminarDelStorage()
        // Solo restaurar el preview visual
        
        // Reconfigurar drag & drop
        if (typeof window.setupDragDropProceso === 'function') {
            window.setupDragDropProceso(previewElement, procesoIndex);
        }
        
    } catch (error) {
    }
};

/**
 * Mostrar imagen ampliada en modal
 * @param {string} previewUrl - URL de la imagen
 * @param {string} nombre - Nombre del archivo
 * @param {number} procesoIndex - Índice del proceso
 */
window.mostrarImagenAmpliada = function(previewUrl, nombre, procesoIndex) {
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.95); display: flex; flex-direction: column;
        align-items: center; justify-content: center; z-index: 100001;
    `;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        background: white; border-radius: 12px; padding: 2rem; max-width: 90%;
        max-height: 90vh; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;
    `;
    
    const titulo = document.createElement('h2');
    titulo.textContent = `Imagen del Proceso ${procesoIndex}`;
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937;';
    
    const nombreArchivo = document.createElement('div');
    nombreArchivo.textContent = nombre;
    nombreArchivo.style.cssText = 'color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;';
    
    const imgElement = document.createElement('img');
    imgElement.src = previewUrl;
    imgElement.style.cssText = 'max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: 8px;';
    
    const closeButton = document.createElement('button');
    closeButton.textContent = '✕ Cerrar';
    closeButton.style.cssText = 'background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; margin-top: 1.5rem;';
    closeButton.onclick = () => {
        modal.remove();
    };
    
    contenido.appendChild(titulo);
    contenido.appendChild(nombreArchivo);
    contenido.appendChild(imgElement);
    contenido.appendChild(closeButton);
    modal.appendChild(contenido);
    
    // Cerrar al hacer click fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con Escape
    const cerrarConEsc = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', cerrarConEsc);
        }
    };
    document.addEventListener('keydown', cerrarConEsc);
    
    document.body.appendChild(modal);
};

/**
 * Limpiar todas las imágenes de proceso
 */
window.limpiarImagenesProcesos = function() {
    for (let i = 1; i <= 3; i++) {
        window.eliminarImagenProceso(i);
    }
    
    // Limpiar storage si está disponible
    if (window.procesosImagenesStorage) {
        window.procesosImagenesStorage.limpiar();
    }
};

/**
 * Obtener todas las imágenes de proceso
 * @returns {Array} Array con las imágenes de todos los procesos
 */
window.obtenerImagenesProcesos = function() {
    const imagenes = [];
    
    for (let i = 1; i <= 3; i++) {
        const previewElement = document.getElementById(`proceso-foto-preview-${i}`);
        if (previewElement) {
            const imgElement = previewElement.querySelector('img');
            if (imgElement) {
                imagenes.push({
                    procesoIndex: i,
                    src: imgElement.src,
                    elemento: previewElement
                });
            }
        }
    }
    
    return imagenes;
};
