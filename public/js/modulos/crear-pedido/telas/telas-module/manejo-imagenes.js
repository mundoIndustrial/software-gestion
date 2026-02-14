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
    console.log('[manejarImagenTela] � INICIADO - input:', input);
    
    // 🔑 CRÍTICO: Inicializar el array si no existe (para Ctrl+V y drag-drop)
    if (!window.imagenesTelaModalNueva) {
        window.imagenesTelaModalNueva = [];
        console.log('[manejarImagenTela] ✅ Array imagenesTelaModalNueva inicializado');
    }
    
    // Si no se pasa input, buscar por ID (intentar primero el ID único del modal)
    if (!input) {
        console.log('[manejarImagenTela] ⚠️ Input null, buscando por ID...');
        input = document.getElementById('modal-agregar-prenda-nueva-file-input') || document.getElementById('nueva-prenda-tela-img-input');
        if (!input) {
            console.error('[manejarImagenTela] ❌ No se encontró elemento de input');
            return;
        }
        console.log('[manejarImagenTela] ✅ Input encontrado:', input.id);
    }
    
    console.log('[manejarImagenTela] input.files:', input.files);
    console.log('[manejarImagenTela] input.files.length:', input.files?.length);
    
    if (!input.files || input.files.length === 0) {
        console.log('[manejarImagenTela] ❌ Sin archivos');
        return;
    }
    
    const file = input.files[0];
    console.log('[manejarImagenTela] 📦 Archivo:', file.name, '|', file.type, '|', file.size, 'bytes');
    
    // Validar que sea una imagen
    if (!file.type.startsWith('image/')) {
        console.warn('[manejarImagenTela] ❌ No es imagen');
        return;
    }
    
    // Validar tamaño
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        console.warn('[manejarImagenTela] ❌ Archivo muy grande');
        return;
    }
    
    // Validar límite
    if (window.imagenesTelaModalNueva.length >= 3) {
        console.warn('[manejarImagenTela] ❌ Limite alcanzado');
        return;
    }
    
    console.log('[manejarImagenTela] ✅ Validaciones OK, leyendo archivo...');
    
    try {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            console.log('[manejarImagenTela] ✅ FileReader.onload DISPARADO');
            
            const previewUrl = e.target.result;
            console.log('[manejarImagenTela] 📸 URL generada, longitud:', previewUrl.length);
            
            const imagen = {
                file: file,
                previewUrl: previewUrl,
                name: file.name,
                size: file.size,
                type: file.type,
                fechaCreacion: new Date().toISOString()
            };
            
            window.imagenesTelaModalNueva.push(imagen);
            console.log('[manejarImagenTela] ✅ Imagen agregada al array. Total:', window.imagenesTelaModalNueva.length);
            
            // Actualizar preview
            console.log('[manejarImagenTela] 🔄 Buscando actualizarPreviewTelaTemporal...');
            if (typeof window.actualizarPreviewTelaTemporal === 'function') {
                console.log('[manejarImagenTela] ✅ Función ENCONTRADA, llamando...');
                window.actualizarPreviewTelaTemporal();
                console.log('[manejarImagenTela] ✅ Función COMPLETADA');
            } else {
                console.error('[manejarImagenTela] ❌ actualizarPreviewTelaTemporal NO ES FUNCIÓN');
                console.log('[manejarImagenTela] Tipo:', typeof window.actualizarPreviewTelaTemporal);
            }
        };
        
        reader.onerror = function(error) {
            console.error('[manejarImagenTela] ❌ FileReader error:', error);
        };
        
        console.log('[manejarImagenTela] 📖 Iniciando FileReader.readAsDataURL...');
        reader.readAsDataURL(file);
        
    } catch (error) {
        console.error('[manejarImagenTela] ❌ Excepción:', error);
    }
    
    input.value = '';
    console.log('[manejarImagenTela] 🧹 Input limpiado');
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
        console.log('[mostrarGaleriaImagenesTemporales]  Galería cerrada');
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
    console.log('[mostrarGaleriaImagenesTemporales]  Galería abierta con ' + imagenes.length + ' imágenes');
};

/**
 * Mostrar galería de imágenes de una tela específica
 * @param {Array} imagenes - Array de imágenes de la tela
 * @param {number} telaIndex - Índice de la tela
 * @param {number} indiceInicial - Índice inicial para mostrar
 */
window.mostrarGaleriaImagenesTela = function(imagenes, telaIndex, indiceInicial = 0) {
    console.log('[mostrarGaleriaImagenesTela] 🖼️ Abriendo galería de imágenes de tela', { 
        telaIndex: telaIndex, 
        totalImagenes: imagenes.length, 
        indiceInicial: indiceInicial 
    });
    
    if (!imagenes || imagenes.length === 0) {
        console.warn('[mostrarGaleriaImagenesTela]  No hay imágenes para mostrar');
        return;
    }
    
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    const contenido = document.createElement('div');
    contenido.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 8px;
        max-width: 90%;
        max-height: 90%;
        overflow-y: auto;
        position: relative;
    `;
    
    const titulo = document.createElement('h2');
    titulo.textContent = `Imágenes de Tela #${telaIndex + 1}`;
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937;';
    
    const imageContainer = document.createElement('div');
    imageContainer.style.cssText = 'display: flex; gap: 10px; margin: 1rem 0; justify-content: center; flex-wrap: wrap;';
    
    // Mostrar imagen actual destacada
    const imagenActual = imagenes[indiceInicial];
    const imgPrincipal = document.createElement('img');
    imgPrincipal.src = imagenActual.previewUrl || imagenActual.url || imagenActual.blobUrl;
    imgPrincipal.style.cssText = 'width: 300px; height: 300px; object-fit: cover; border-radius: 8px; border: 2px solid #3b82f6;';
    imageContainer.appendChild(imgPrincipal);
    
    // Mostrar miniaturas
    if (imagenes.length > 1) {
        const miniaturasContainer = document.createElement('div');
        miniaturasContainer.style.cssText = 'display: flex; gap: 5px; margin-top: 1rem; justify-content: center; flex-wrap: wrap;';
        
        imagenes.forEach((img, index) => {
            const miniatura = document.createElement('img');
            miniatura.src = img.previewUrl || img.url || img.blobUrl;
            miniatura.style.cssText = `width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid ${index === indiceInicial ? '#3b82f6' : '#e5e7eb'};`;
            miniatura.title = `${img.name || 'Imagen'} (${index + 1}/${imagenes.length})`;
            
            miniatura.onclick = () => {
                // Actualizar imagen principal
                imgPrincipal.src = img.previewUrl || img.url || img.blobUrl;
                
                // Actualizar bordes de miniaturas
                miniaturasContainer.querySelectorAll('img').forEach((mini, i) => {
                    mini.style.border = `2px solid ${i === index ? '#3b82f6' : '#e5e7eb'}`;
                });
                
                console.log(`[mostrarGaleriaImagenesTela] 🖼️ Cambiando a imagen ${index + 1}: ${img.name || 'sin nombre'}`);
            };
            
            miniaturasContainer.appendChild(miniatura);
        });
        
        contenido.appendChild(miniaturasContainer);
    }
    
    const infoImagen = document.createElement('div');
    infoImagen.style.cssText = 'color: #6b7280; font-size: 0.9rem; margin: 1rem 0; text-align: center;';
    infoImagen.innerHTML = `
        <div>Imagen ${indiceInicial + 1} de ${imagenes.length}</div>
        <div>${imagenActual.name || 'Sin nombre'}</div>
        <div>Tamaño: ${(imagenActual.size || 0)} bytes</div>
    `;
    
    const closeButton = document.createElement('button');
    closeButton.textContent = '✕ Cerrar';
    closeButton.style.cssText = 'background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; margin-top: 1rem;';
    closeButton.onclick = () => {
        modal.remove();
        console.log('[mostrarGaleriaImagenesTela]  Galería cerrada');
    };
    
    contenido.appendChild(titulo);
    contenido.appendChild(imageContainer);
    contenido.appendChild(infoImagen);
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
    console.log('[mostrarGaleriaImagenesTela]  Galería abierta con ' + imagenes.length + ' imágenes');
};

/**
 * Eliminar imagen temporal
 * @param {number} index - Índice de la imagen a eliminar
 */
window.eliminarImagenTemporal = function(index) {
    console.log('[eliminarImagenTemporal] 🗑️ Eliminando imagen temporal:', index);
    
    if (!window.imagenesTelaModalNueva || index < 0 || index >= window.imagenesTelaModalNueva.length) {
        console.warn('[eliminarImagenTemporal]  Índice inválido:', index);
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
    
    console.log('[eliminarImagenTemporal]  Imagen eliminada, total restante:', window.imagenesTelaModalNueva.length);
};

/**
 * Actualizar preview temporal de imágenes
 */
window.actualizarPreviewTelaTemporal = function() {
    console.log('[actualizarPreviewTelaTemporal] 🚀 INICIADO');
    console.log('[actualizarPreviewTelaTemporal] window.imagenesTelaModalNueva:', window.imagenesTelaModalNueva);
    
    const preview = document.getElementById('nueva-prenda-tela-preview');
    console.log('[actualizarPreviewTelaTemporal] Preview buscado con ID "nueva-prenda-tela-preview"');
    console.log('[actualizarPreviewTelaTemporal] ✓ Preview encontrado:', !!preview);
    
    if (!preview) {
        console.error('[actualizarPreviewTelaTemporal] ❌ FALLO: Preview esNull');
        console.log('[actualizarPreviewTelaTemporal] IDs disponibles en DOM:', Array.from(document.querySelectorAll('[id]')).map(el => el.id).slice(0, 20));
        return;
    }
    
    console.log('[actualizarPreviewTelaTemporal] ✅ Preview elemento:', preview);
    console.log('[actualizarPreviewTelaTemporal] Preview display:', preview.style.display);
    console.log('[actualizarPreviewTelaTemporal] Preview parent:', preview.parentElement);
    
    const imagenes = window.imagenesTelaModalNueva || [];
    console.log('[actualizarPreviewTelaTemporal] Imágenes en array:', imagenes.length);
    
    if (imagenes.length === 0) {
        console.log('[actualizarPreviewTelaTemporal] ⚠️ Sin imágenes, ocultando preview');
        preview.style.display = 'none';
        return;
    }
    
    console.log('[actualizarPreviewTelaTemporal] 📸 Procesando', imagenes.length, 'imágenes');
    
    // Limpiar HTML anterior
    preview.innerHTML = '';
    console.log('[actualizarPreviewTelaTemporal] ✅ HTML limpiado');
    
    // Aplicar estilos
    preview.style.cssText =
        'display: flex !important; ' +
        'flex-wrap: wrap !important; ' +
        'gap: 0.5rem !important; ' +
        'justify-content: center !important; ' +
        'align-items: flex-start !important; ' +
        'margin-top: 0.5rem !important; ' +
        'padding: 0.5rem !important; ' +
        'background: #f9fafb !important; ' +
        'border: 1px dashed #d1d5db !important; ' +
        'border-radius: 4px !important; ' +
        'width: 100% !important; ' +
        'margin-left: 0 !important; ' +
        'margin-right: 0 !important; ' +
        'box-sizing: border-box !important; ' +
        'min-height: 50px !important;';
    
    console.log('[actualizarPreviewTelaTemporal] ✅ Estilos aplicados');
    console.log('[actualizarPreviewTelaTemporal] Preview display NOW:', preview.style.display);
    
    // Agregar imágenes
    imagenes.forEach((img, index) => {
        console.log(`[actualizarPreviewTelaTemporal] 📦 Imagen ${index}:`, img.name);
        
        const container = document.createElement('div');
        container.style.cssText = 'position: relative; width: 60px; height: 60px; flex-shrink: 0; border: 1px solid red;';
        
        const imgElement = document.createElement('img');
        imgElement.src = img.previewUrl || img.url || img.blobUrl;
        imgElement.alt = img.name;
        imgElement.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 2px solid #0066cc;';
        
        console.log(`[actualizarPreviewTelaTemporal] 🖼️ Img src set to:`, imgElement.src ? imgElement.src.substring(0, 50) : 'NULL');
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.innerHTML = '×';
        btnEliminar.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; line-height: 1; padding: 0;';
        
        // 🔑 Agregarclic handler para eliminar
        btnEliminar.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log(`[actualizarPreviewTelaTemporal] 🗑️ Eliminando imagen ${index}`);
            window.imagenesTelaModalNueva.splice(index, 1);
            console.log(`[actualizarPreviewTelaTemporal] ✅ Imagen eliminada. Quedan: ${window.imagenesTelaModalNueva.length}`);
            window.actualizarPreviewTelaTemporal();
        };
        
        container.appendChild(imgElement);
        container.appendChild(btnEliminar);
        preview.appendChild(container);
        
        console.log(`[actualizarPreviewTelaTemporal] ✅ Elemento ${index} agregado al DOM`);
    });
    
    console.log('[actualizarPreviewTelaTemporal] ✅ COMPLETADO - Hijos en preview:', preview.children.length);
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
    
    console.log('[limpiarImagenesTemporales]  Imágenes temporales limpiadas');
};
