/**
 * ================================================
 * TELAS MODULE - MANEJO DE IMÁGENES
 * ================================================
 * 
 * Funciones para manejar imágenes de telas
 * Galería, preview, validación y almacenamiento
 * 
 * @module TelasModule
 * @version 2.0.0
 */

/**
 * Manejar imagen de tela
 * @param {HTMLInputElement} input - Input de tipo file
 */
window.manejarImagenTela = function(input) {
    console.log('[manejarImagenTela] 📸 Manejando imagen de tela');
    
    // Si no se pasa input, buscar por ID (intentar primero el ID único del modal)
    if (!input) {
        input = document.getElementById('modal-agregar-prenda-nueva-file-input') || document.getElementById('nueva-prenda-tela-img-input');
        if (!input) {
            console.error('[manejarImagenTela] ❌ No se encontró el elemento de input');
            return;
        }
    }
    
    if (!input.files || input.files.length === 0) {
        console.log('[manejarImagenTela] 📭 No se seleccionaron archivos');
        return;
    }
    
    try {
        const file = input.files[0];
        console.log('[manejarImagenTela] 📄 Archivo recibido:', file.name, file.type);
        
        // Validar que sea una imagen
        if (!file.type.startsWith('image/')) {
            console.warn('[manejarImagenTela] ⚠️ El archivo no es una imagen:', file.type);
            window.mostrarErrorTela('nueva-prenda-tela', 'Por favor selecciona un archivo de imagen válido');
            return;
        }
        
        // Validar tamaño (máximo 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            console.warn('[manejarImagenTela] ⚠️ Archivo demasiado grande:', file.size);
            window.mostrarErrorTela('nueva-prenda-tela', 'El archivo es demasiado grande (máximo 5MB)');
            return;
        }
        
        // Validar límite de imágenes (máximo 3 por tela)
        if (window.imagenesTelaModalNueva.length >= 3) {
            console.warn('[manejarImagenTela] ⚠️ Límite de imágenes alcanzado');
            window.mostrarErrorTela('nueva-prenda-tela', 'Máximo 3 imágenes por tela');
            return;
        }
        
        // Crear preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewUrl = e.target.result;
            console.log('[manejarTela] 🖼️ Preview generado para:', file.name);
            
            // Agregar al array de imágenes temporales
            const imagen = {
                file: file,
                previewUrl: previewUrl,
                name: file.name,
                size: file.size,
                type: file.type,
                fechaCreacion: new Date().toISOString()
            };
            
            window.imagenesTelaModalNueva.push(imagen);
            
            console.log('[manejarImagenTela] 📦 Imagen agregada al array temporal');
            console.log('[maneImagenTela] 📊 Total imágenes temporales:', window.imagenesTelaModalNueva.length);
            
            // Actualizar preview si es necesario
            if (typeof window.actualizarPreviewTelaTemporal === 'function') {
                window.actualizarPreviewTelaTemporal();
            }
        };
        
        reader.onerror = function() {
            console.error('[maneImagenTela] ❌ Error al leer el archivo');
            window.mostrarErrorTela('nueva-prenda-tela', 'Error al leer el archivo de imagen');
        };
        
        reader.readAsDataURL(file);
        
    } catch (error) {
        console.error('[manejarImagenTela] ❌ Error general:', error);
        window.mostrarErrorTela('nueva-prenda-tela', 'Error al procesar la imagen');
    }
    
    // Limpiar input
    input.value = '';
};

/**
 * Mostrar galería de imágenes temporales (antes de guardar tela)
 * @param {Array} imagenes - Array de imágenes
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesTemporales = function(imagenes, indiceInicial = 0) {
    console.log('[mostrarGaleriaImagenesTemporales] 🖼️ Abriendo galería de imágenes temporales');
    
    if (!imagenes || imagenes.length === 0) {
        console.log('[mostrarGaleriaImagenesTemporales] 📭 No hay imágenes para mostrar');
        return;
    }
    
    window.imagenesTelaModalNueva = imagenes;
    
    // Crear modal de galería
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.95); display: flex; flex-direction: column;
        align-items: center; justify-content: center; z-index: 100001;
    `;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        background: white; border-radius: 12px; padding: 2rem; max-width: 600px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center;
    `;
    
    const titulo = document.createElement('h2');
    titulo.textContent = 'Imágenes de Tela';
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937;';
    
    const imageContainer = document.createElement('div');
    imageContainer.style.cssText = 'display: flex; gap: 10px; margin: 1rem 0; justify-content: center; flex-wrap: wrap;';
    
    imagenes.forEach((img, index) => {
        const imgElement = document.createElement('img');
        imgElement.src = img.previewUrl || img.url || img.blobUrl;
        imgElement.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #e5e7eb;';
        imgElement.onclick = () => {
            console.log('[mostrarGaleriaImagenesTemporales] 🖼️ Click en imagen ' + (index + 1) + ': ' + (img.name || 'sin nombre'));
        };
        imageContainer.appendChild(imgElement);
    });
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: #6b7280; font-size: 0.9rem; margin: 1rem 0;';
    contador.textContent = `${imagenes.length} imagen${imagenes.length > 1 ? 's' : ''}`;
    
    const closeButton = document.createElement('button');
    closeButton.textContent = '✕ Cerrar';
    closeButton.style.cssText = 'background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; margin-top: 1rem;';
    closeButton.onclick = () => {
        modal.remove();
        console.log('[mostrarGaleriaImagenesTemporales] ✅ Galería cerrada');
    };
    
    contenido.appendChild(titulo);
    contenido.appendChild(imageContainer);
    contenido.appendChild(contador);
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
    console.log('[mostrarGaleriaImagenesTemporales] ✅ Galería abierta con ' + imagenes.length + ' imágenes');
};

/**
 * Eliminar imagen temporal
 * @param {number} index - Índice de la imagen a eliminar
 */
window.eliminarImagenTemporal = function(index) {
    console.log('[eliminarImagenTemporal] 🗑️ Eliminando imagen temporal:', index);
    
    if (!window.imagenesTelaModalNueva || index < 0 || index >= window.imagenesTelaModalNueva.length) {
        console.warn('[eliminarImagenTemporal] ❌ Índice inválido:', index);
        return;
    }
    
    const imagenEliminada = window.imagenesTelaModalNueva[index];
    console.log('[eliminarImagenTemporal] 📋 Imagen eliminada:', imagenEliminada.name);
    
    // Eliminar del array
    window.imagenesTelaModalNueva.splice(index, 1);
    
    // Actualizar preview si es necesario
    if (typeof window.actualizarPreviewTelaTemporal === 'function') {
        window.actualizarPreviewTelaTemporal();
    }
    
    console.log('[eliminarImagenTemporal] ✅ Imagen eliminada, total restante:', window.imagenesTelaModalNueva.length);
};

/**
 * Actualizar preview temporal de imágenes
 */
window.actualizarPreviewTelaTemporal = function() {
    console.log('[actualizarPreviewTelaTemporal] 🎬 Actualizando preview temporal de imágenes');
    
    const preview = document.getElementById('nueva-prenda-tela-preview');
    if (!preview) {
        console.warn('[actualizarPreviewTelaTemporal] ⚠️ Preview no encontrado');
        return;
    }
    
    const imagenes = window.imagenesTelaModalNueva;
    
    if (!imagenes || imagenes.length === 0) {
        // Ocultar preview si no hay imágenes
        preview.style.display = 'none';
        return;
    }
    
    // Mostrar preview con la primera imagen
    preview.style.display = 'flex';
    preview.style.flexWrap = 'wrap';
    preview.style.gap = '0.5rem';
    preview.style.justifyContent = 'center';
    preview.style.alignItems = 'flex-start';
    preview.style.marginTop = '0.5rem';
    preview.style.padding = '0.5rem';
    preview.style.background = '#f9fafb';
    preview.style.border = '1px dashed #d1d5db';
    preview.style.borderRadius = '4px';
    
    // Limpiar contenido anterior
    preview.innerHTML = '';
    
    // Agregar imágenes
    imagenes.forEach((img, index) => {
        const container = document.createElement('div');
        container.style.cssText = 'position: relative; width: 60px; height: 60px; flex-shrink: 0;';
        
        const imgElement = document.createElement('img');
        imgElement.src = img.previewUrl || img.url || img.blobUrl;
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
            window.eliminarImagenTemporal(index);
        };
        
        container.appendChild(imgElement);
        container.appendChild(btnEliminar);
        preview.appendChild(container);
    });
    
    console.log('[actualizarTelaTemporal] ✅ Preview actualizado con ' + imagenes.length + ' imágenes');
};

/**
 * Validar imagen de tela
 * @param {File} file - Archivo a validar
 * @returns {Object} Resultado de la validación
 */
window.validarImagenTela = function(file) {
    const resultado = {
        valido: true,
        errores: []
    };
    
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
        resultado.valido = false;
        resultado.errores.push('El archivo debe ser una imagen');
    }
    
    // Validar tamaño (máximo 5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        resultado.valido = false;
        resultado.errores.push('El archivo es demasiado grande (máximo 5MB)');
    }
    
    return resultado;
};

/**
 * Limpiar imágenes temporales
 */
window.limpiarImagenesTemporales = function() {
    console.log('[limpiarImagenesTemporales] 🧹 Limpiando imágenes temporales');
    window.imagenesTelaModalNueva = [];
    
    // Actualizar preview si es necesario
    if (typeof window.actualizarPreviewTelaTemporal === 'function') {
        window.actualizarPreviewTelaTemporal();
    }
    
    console.log('[limpiarImagenesTemporales] ✅ Imágenes temporales limpiadas');
};
