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

console.log('🚀 Manejo de Imágenes de Procesos cargado...');

/**
 * Manejar imagen de proceso individual
 * @param {HTMLInputElement} input - Input de tipo file
 * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
 */
window.manejarImagenProceso = function(input, procesoIndex) {
    console.log(`[manejarImagenProceso] 📸 Manejando imagen de proceso ${procesoIndex}`);
    
    if (!input.files || input.files.length === 0) {
        console.log(`[manejarImagenProceso] 📭 No se seleccionaron archivos para proceso ${procesoIndex}`);
        return;
    }
    
    try {
        const file = input.files[0];
        console.log(`[manejarImagenProceso] 📄 Archivo recibido para proceso ${procesoIndex}:`, file.name, file.type);
        
        // Validar que sea una imagen
        if (!file.type.startsWith('image/')) {
            console.warn(`[manejarImagenProceso] ⚠️ El archivo no es una imagen:`, file.type);
            mostrarModalError('Por favor selecciona un archivo de imagen válido');
            return;
        }
        
        // Validar tamaño (máximo 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            console.warn(`[manejarImagenProceso] ⚠️ Archivo demasiado grande para proceso ${procesoIndex}:`, file.size);
            mostrarModalError('El archivo es demasiado grande (máximo 5MB)');
            return;
        }
        
        // Crear preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewUrl = e.target.result;
            console.log(`[manejarImagenProceso] 🖼️ Preview generado para proceso ${procesoIndex}:`, file.name);
            
            // Actualizar el preview específico
            const previewElement = document.getElementById(`proceso-foto-preview-${procesoIndex}`);
            if (previewElement) {
                // Limpiar contenido anterior
                previewElement.innerHTML = '';
                previewElement.style.background = '';
                previewElement.style.border = '2px solid #0066cc';
                
                // Crear imagen
                const imgElement = document.createElement('img');
                imgElement.src = previewUrl;
                imgElement.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 6px; cursor: pointer; transition: opacity 0.2s;';
                imgElement.onclick = () => {
                    console.log(`[manejarImagenProceso] 🖼️ Click en imagen del proceso ${procesoIndex}`);
                    // Aquí podrías abrir una galería si hay múltiples imágenes
                    mostrarImagenAmpliada(previewUrl, file.name, procesoIndex);
                };
                imgElement.onmouseover = () => imgElement.style.opacity = '0.8';
                imgElement.onmouseout = imgElement.style.opacity = '1';
                
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
                    eliminarImagenProceso(procesoIndex);
                };
                
                // Posicionar elementos
                previewElement.style.position = 'relative';
                previewElement.appendChild(imgElement);
                previewElement.appendChild(btnEliminar);
                
                console.log(`[manejarImagenProceso] ✅ Preview actualizado para proceso ${procesoIndex}`);
                
                // Actualizar drag & drop si es necesario
                if (typeof window.actualizarDragDropProceso === 'function') {
                    window.actualizarDragDropProceso(procesoIndex);
                }
            } else {
                console.warn(`[manejarImagenProceso] ⚠️ Preview del proceso ${procesoIndex} no encontrado`);
            }
            
            // Guardar la imagen en el storage si está disponible
            if (window.procesosImagenesStorage) {
                window.procesosImagenesStorage.agregarImagen(procesoIndex, {
                    file: file,
                    previewUrl: previewUrl,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    fechaCreacion: new Date().toISOString()
                });
                console.log(`[manejarImagenProceso] 📦 Imagen guardada en storage para proceso ${procesoIndex}`);
            }
        };
        
        reader.onerror = function() {
            console.error(`[manejarImagenProceso] ❌ Error al leer el archivo para proceso ${procesoIndex}`);
            mostrarModalError('Error al leer el archivo de imagen');
        };
        
        reader.readAsDataURL(file);
        
    } catch (error) {
        console.error(`[manejarImagenProceso] ❌ Error general al procesar imagen del proceso ${procesoIndex}:`, error);
        mostrarModalError('Error al procesar la imagen');
    }
    
    // Limpiar input
    input.value = '';
};

/**
 * Eliminar imagen de un proceso específico
 * @param {number} procesoIndex - Índice del proceso (1, 2, 3)
 */
window.eliminarImagenProceso = function(procesoIndex) {
    console.log(`[eliminarImagenProceso] 🗑️ Eliminando imagen del proceso ${procesoIndex}`);
    
    try {
        const previewElement = document.getElementById(`proceso-foto-preview-${procesoIndex}`);
        if (previewElement) {
            // Restaurar el placeholder
            previewElement.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${procesoIndex}</div>
                </div>
            `;
            
            // Restaurar estilos
            previewElement.style.background = '#f9fafb';
            previewElement.style.border = '2px dashed #0066cc';
            previewElement.style.transform = '';
            previewElement.style.boxShadow = '';
            
            console.log(`[eliminarImagenProceso] ✅ Preview del proceso ${procesoIndex} restaurado`);
        }
        
        // Eliminar del storage si está disponible
        if (window.procesosImagenesStorage) {
            window.procesosImagenesStorage.eliminarImagen(procesoIndex);
            console.log(`[eliminarImagenProceso] 📦 Imagen eliminada del storage para proceso ${procesoIndex}`);
        }
        
        // Reconfigurar drag & drop
        if (typeof window.setupDragDropProceso === 'function') {
            window.setupDragDropProceso(previewElement, procesoIndex);
        }
        
    } catch (error) {
        console.error(`[eliminarImagenProceso] ❌ Error al eliminar imagen del proceso ${procesoIndex}:`, error);
    }
};

/**
 * Mostrar imagen ampliada en modal
 * @param {string} previewUrl - URL de la imagen
 * @param {string} nombre - Nombre del archivo
 * @param {number} procesoIndex - Índice del proceso
 */
window.mostrarImagenAmpliada = function(previewUrl, nombre, procesoIndex) {
    console.log(`[mostrarImagenAmpliada] 🖼️ Abriendo imagen ampliada del proceso ${procesoIndex}`);
    
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
        console.log(`[mostrarImagenAmpliada] ✅ Modal cerrado para proceso ${procesoIndex}`);
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
    console.log(`[mostrarImagenAmpliada] ✅ Modal abierto para proceso ${procesoIndex}`);
};

/**
 * Limpiar todas las imágenes de proceso
 */
window.limpiarImagenesProcesos = function() {
    console.log('[limpiarImagenesProcesos] 🧹 Limpiando todas las imágenes de proceso');
    
    for (let i = 1; i <= 3; i++) {
        window.eliminarImagenProceso(i);
    }
    
    // Limpiar storage si está disponible
    if (window.procesosImagenesStorage) {
        window.procesosImagenesStorage.limpiar();
        console.log('[limpiarImagenesProcesos] 📦 Storage de imágenes de procesos limpiado');
    }
    
    console.log('[limpiarImagenesProcesos] ✅ Todas las imágenes de procesos limpiadas');
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
    
    console.log('[obtenerImagenesProcesos] 📊 Imágenes encontradas:', imagenes.length);
    return imagenes;
};
