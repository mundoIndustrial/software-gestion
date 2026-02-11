/**
 * ================================================
 * DRAG & DROP FUNCTIONALITY
 * ================================================
 * 
 * Funciones para manejar drag & drop de imágenes
 * Soporta tanto imágenes de prendas como de telas
 * 
 * @module DragDropHandlers
 */

/**
 * Configura los event listeners para drag & drop en el preview de imágenes
 */
window.setupDragAndDrop = function(previewElement) {
    // Limpiar event listeners anteriores clonando el elemento
    const newPreview = previewElement.cloneNode(true);
    previewElement.parentNode.replaceChild(newPreview, previewElement);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults);
    newPreview.addEventListener('dragenter', preventDefaults);
    newPreview.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        e.preventDefault();
        newPreview.style.background = '#eff6ff';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
    });
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
    });
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(tempInput);
        } else {
            console.error('[setupDragAndDrop]  La función manejarImagenesPrenda no está disponible');
        }
    });
    
    // Evento click como alternativa
    newPreview.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Enfocar el elemento para poder recibir eventos paste
        newPreview.focus();
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            // Input de fotos no encontrado, no hacer nada
        }
    });
    
    // Evento focus para mostrar indicador visual
    newPreview.addEventListener('focus', (e) => {
        newPreview.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        newPreview.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    newPreview.addEventListener('blur', (e) => {
        newPreview.style.boxShadow = '';
        newPreview.style.border = '';
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    newPreview.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            return;
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenesPrenda === 'function') {
                        window.manejarImagenesPrenda(tempInput);
                    } else {
                        console.error('[setupDragAndDrop]  La función manejarImagenesPrenda no está disponible');
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                }
            }
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    newPreview.setAttribute('tabindex', '0');
    newPreview.style.outline = 'none';
};

/**
 * Configura los event listeners para drag & drop cuando ya hay imágenes
 * Permite reemplazar o agregar más imágenes
 */
window.setupDragAndDropConImagen = function(previewElement, imagenesActuales) {
    // Limpiar event listeners anteriores clonando el elemento
    const newPreview = previewElement.cloneNode(true);
    previewElement.parentNode.replaceChild(newPreview, previewElement);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults);
    newPreview.addEventListener('dragenter', preventDefaults);
    newPreview.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        e.preventDefault();
        newPreview.style.background = 'rgba(59, 130, 246, 0.1)';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
    });
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
    });
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        
        console.log('[setupDragAndDropConImagen]  Archivos arrastrados:', e.dataTransfer.files.length);
        console.log('[setupDragAndDropConImagen] 📸 Imágenes actuales:', imagenesActuales.length);
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            console.log('[setupDragAndDropConImagen] 📭 No se arrastraron archivos');
            return;
        }
        
        // Verificar límite de imágenes
        if (imagenesActuales.length >= 3) {
            console.warn('[setupDragAndDropConImagen]  Límite de imágenes alcanzado');
            mostrarModalError('Solo se permiten máximo 3 imágenes por prenda');
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        console.log('[setupDragAndDropConImagen] 📄 Procesando archivo:', file.name, file.type);
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            console.warn('[setupDragAndDropConImagen]  El archivo no es una imagen:', file.type);
            mostrarModalError('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenesPrenda === 'function') {
            window.manejarImagenesPrenda(tempInput);
        } else {
            // La función manejarImagenesPrenda no está disponible, no hacer nada
        }
    });
    
    // Evento click como alternativa
    newPreview.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Enfocar el elemento para poder recibir eventos paste
        newPreview.focus();
        
        // Abrir el selector de archivos
        const inputFotos = document.getElementById('nueva-prenda-foto-input');
        if (inputFotos) {
            inputFotos.click();
        } else {
            // Input de fotos no encontrado, no hacer nada
        }
    });
    
    // Evento focus para mostrar indicador visual
    newPreview.addEventListener('focus', (e) => {
        newPreview.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.3)';
        newPreview.style.border = '2px solid #3b82f6';
    });
    
    // Evento blur para quitar indicador visual
    newPreview.addEventListener('blur', (e) => {
        newPreview.style.boxShadow = '';
        newPreview.style.border = '';
    });
    
    // Evento paste para permitir pegar imágenes desde el portapapeles
    newPreview.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Verificar límite de imágenes
        if (imagenesActuales.length >= 3) {
            console.warn('[setupDragAndDropConImagen]  Límite de imágenes alcanzado');
            mostrarModalError('Solo se permiten máximo 3 imágenes por prenda');
            return;
        }
        
        // Obtener items del portapapeles
        const items = e.clipboardData.items;
        if (items.length === 0) {
            return;
        }
        
        // Buscar imágenes en el portapapeles
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            // Verificar si es una imagen
            if (item.type.startsWith('image/')) {
                // Obtener el archivo
                const file = item.getAsFile();
                if (file) {
                    console.log('[setupDragAndDropConImagen] 📋 Imagen pegada del portapapeles:', file.name, file.type);
                    
                    // Crear un input file temporal para usar la función existente
                    const tempInput = document.createElement('input');
                    tempInput.type = 'file';
                    tempInput.files = new DataTransfer().files;
                    
                    // Agregar el archivo al DataTransfer
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    tempInput.files = dataTransfer.files;
                    
                    // Usar la función existente para manejar la imagen
                    if (typeof window.manejarImagenesPrenda === 'function') {
                        window.manejarImagenesPrenda(tempInput);
                    } else {
                        // La función manejarImagenesPrenda no está disponible, no hacer nada
                    }
                    
                    // Salir después de procesar la primera imagen
                    break;
                }
            }
        }
    });
    
    // Hacer que el elemento sea focusable para recibir eventos paste
    newPreview.setAttribute('tabindex', '0');
    newPreview.style.outline = 'none';
};

/**
 * Configura los event listeners para drag & drop en imágenes de tela
 */
window.setupDragDropTela = function(dropZone) {
    if (!dropZone) {
        return;
    }
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    dropZone.addEventListener('dragover', preventDefaults);
    dropZone.addEventListener('dragenter', preventDefaults);
    dropZone.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.background = 'rgba(59, 130, 246, 0.1)';
        dropZone.style.border = '2px dashed #3b82f6';
        dropZone.style.borderRadius = '6px';
        dropZone.style.transform = 'scale(1.02)';
        dropZone.style.padding = '8px';
        
        // Cambiar el botón para indicar que está activo
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '#2563eb';
            button.style.transform = 'scale(1.05)';
            button.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
        }
        
        // Resaltar el texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#3b82f6';
            helpText.style.fontWeight = '500';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '1';
            }
        }
        
        // Feedback visual durante drag over
    });
    
    // Evento dragleave para restaurar estilos
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.style.background = '';
        dropZone.style.border = '';
        dropZone.style.borderRadius = '';
        dropZone.style.padding = '';
        dropZone.style.transform = '';
        
        // Restaurar botón
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '';
            button.style.transform = '';
            button.style.boxShadow = '';
        }
        
        // Restaurar texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#6b7280';
            helpText.style.fontWeight = 'normal';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '0.5';
            }
        }
        
    });
    
    // Evento drop - manejar archivos arrastrados
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        dropZone.style.background = '';
        dropZone.style.border = '';
        dropZone.style.borderRadius = '';
        dropZone.style.padding = '';
        dropZone.style.transform = '';
        
        // Restaurar botón
        const button = dropZone.querySelector('button');
        if (button) {
            button.style.background = '';
            button.style.transform = '';
            button.style.boxShadow = '';
        }
        
        // Restaurar texto de ayuda
        const helpText = dropZone.querySelector('div[style*="color: #6b7280"]');
        if (helpText) {
            helpText.style.color = '#6b7280';
            helpText.style.fontWeight = 'normal';
            const icon = helpText.querySelector('.material-symbols-rounded');
            if (icon) {
                icon.style.opacity = '0.5';
            }
        }
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen para la tela');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenTela === 'function') {
            window.manejarImagenTela(tempInput);
        } else {
            // La función manejarImagenTela no está disponible, no hacer nada
        }
    });
};

/**
 * Configura los event listeners para drag & drop en el preview de imágenes de tela
 * Permite arrastrar más imágenes directamente sobre el área donde ya se muestran
 */
window.setupDragDropTelaPreview = function(previewElement) {
    if (!previewElement) {
        return;
    }
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    previewElement.addEventListener('dragover', preventDefaults);
    previewElement.addEventListener('dragenter', preventDefaults);
    previewElement.addEventListener('dragleave', preventDefaults);
    
    // Evento dragover con feedback visual
    previewElement.addEventListener('dragover', (e) => {
        e.preventDefault();
        previewElement.style.background = 'rgba(59, 130, 246, 0.15)';
        previewElement.style.border = '2px dashed #3b82f6';
        previewElement.style.opacity = '0.8';
        previewElement.style.transform = 'scale(1.02)';
    });
    
    // Evento dragleave para restaurar estilos
    previewElement.addEventListener('dragleave', (e) => {
        e.preventDefault();
        previewElement.style.background = '';
        previewElement.style.border = '';
        previewElement.style.opacity = '1';
        previewElement.style.transform = '';
    });
    
    // Evento drop - manejar archivos arrastrados
    previewElement.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        previewElement.style.background = '';
        previewElement.style.border = '';
        previewElement.style.opacity = '1';
        previewElement.style.transform = '';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            return;
        }
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            mostrarModalError('Por favor arrastra solo archivos de imagen para la tela');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenTela === 'function') {
            window.manejarImagenTela(tempInput);
        } else {
            // La función manejarImagenTela no está disponible, no hacer nada
        }
    });
};

/**
 * Inicialización del drag & drop cuando el DOM está listo
 */
window.inicializarDragDropPrenda = function() {
    const preview = document.getElementById('nueva-prenda-foto-preview');
    if (preview) {
        // Verificar si ya hay imágenes
        if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes().length > 0) {
            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            window.setupDragAndDropConImagen(preview, imagenes);
        } else {
            window.setupDragAndDrop(preview);
        }
    } else {
        // Preview no encontrado, no hacer nada
    }
};

/**
 * Inicialización automática del drag & drop para imágenes de tela
 */
window.inicializarDragDropTela = function() {
    // Configurar drag & drop en el botón
    const dropZone = document.getElementById('nueva-prenda-tela-drop-zone');
    if (dropZone) {
        window.setupDragDropTela(dropZone);
    }
    
    // Configurar drag & drop en el preview si ya hay imágenes
    const preview = document.getElementById('nueva-prenda-tela-preview');
    if (preview && preview.style.display !== 'none') {
        if (typeof window.setupDragDropTelaPreview === 'function') {
            window.setupDragDropTelaPreview(preview);
        }
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.inicializarDragDropPrenda);
    document.addEventListener('DOMContentLoaded', window.inicializarDragDropTela);
} else {
    // El DOM ya está cargado
    window.inicializarDragDropPrenda();
    window.inicializarDragDropTela();
}
