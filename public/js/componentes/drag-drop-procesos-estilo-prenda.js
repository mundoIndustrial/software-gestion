// DRAG & DROP PARA PROCESOS - EXACTAMENTE COMO PRENDAS
// Sin logs para una experiencia limpia

window.setupDragAndDropProceso = function(previewElement, procesoIndex) {
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
        if (typeof window.manejarImagenProceso === 'function') {
            window.manejarImagenProceso(tempInput, procesoIndex);
        }
    });
    
    // Evento click como alternativa
    newPreview.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Abrir el selector de archivos específico del proceso
        const inputProceso = document.getElementById(`proceso-foto-input-${procesoIndex}`);
        if (inputProceso) {
            inputProceso.click();
        }
    });
};

// Configurar drag & drop para toda la sección
window.setupDragAndDropSeccionCompleta = function() {
    // Buscar la sección completa
    const secciones = document.querySelectorAll('.form-section');
    let seccionImagenes = null;
    
    // Encontrar la sección que contiene "IMÁGENES"
    for (const seccion of secciones) {
        const label = seccion.querySelector('.form-label-primary');
        if (label && label.textContent.includes('IMÁGENES')) {
            seccionImagenes = seccion;
            break;
        }
    }
    
    if (!seccionImagenes) {
        return;
    }
    
    // Variables globales para tracking
    window.dragTarget = null;
    window.dragIndex = null;
    
    // Evento dragover en toda la sección
    seccionImagenes.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Encontrar el preview bajo el cursor
        const preview = e.target.closest('.foto-preview-proceso');
        if (preview && preview.id) {
            const index = preview.id.replace('proceso-foto-preview-', '');
            
            // Si es un preview diferente, actualizar
            if (window.dragTarget !== preview) {
                // Restaurar el anterior
                if (window.dragTarget) {
                    window.dragTarget.style.background = '';
                    window.dragTarget.style.border = '2px dashed #0066cc';
                    window.dragTarget.style.opacity = '1';
                }
                
                // Resaltar el nuevo
                window.dragTarget = preview;
                window.dragIndex = index;
                preview.style.background = '#eff6ff';
                preview.style.border = '2px dashed #3b82f6';
                preview.style.opacity = '0.8';
            }
        }
    });
    
    // Evento dragleave
    seccionImagenes.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar si salimos de la sección
        if (!e.relatedTarget || !seccionImagenes.contains(e.relatedTarget)) {
            if (window.dragTarget) {
                window.dragTarget.style.background = '';
                window.dragTarget.style.border = '2px dashed #0066cc';
                window.dragTarget.style.opacity = '1';
                window.dragTarget = null;
                window.dragIndex = null;
            }
        }
    });
    
    // Evento drop en toda la sección
    seccionImagenes.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        if (window.dragTarget) {
            window.dragTarget.style.background = '';
            window.dragTarget.style.border = '2px dashed #0066cc';
            window.dragTarget.style.opacity = '1';
        }
        
        const files = e.dataTransfer.files;
        if (!files || files.length === 0) {
            return;
        }
        
        // Determinar el proceso
        let procesoIndex = window.dragIndex;
        if (!procesoIndex) {
            // Buscar el primer preview vacío
            for (let i = 1; i <= 3; i++) {
                const preview = seccionImagenes.querySelector(`#proceso-foto-preview-${i}`);
                if (preview && !preview.querySelector('img')) {
                    procesoIndex = i.toString();
                    break;
                }
            }
            
            // Si no hay vacíos, usar el primero
            if (!procesoIndex) {
                procesoIndex = '1';
            }
        }
        
        const file = files[0];
        
        if (!file.type.startsWith('image/')) {
            alert('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear input temporal
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente
        if (typeof window.manejarImagenProceso === 'function') {
            window.manejarImagenProceso(tempInput, parseInt(procesoIndex));
        }
        
        // Reset variables
        window.dragTarget = null;
        window.dragIndex = null;
    });
};

// Inicializar para los 3 previews individuales
for (let i = 1; i <= 3; i++) {
    const preview = document.getElementById(`proceso-foto-preview-${i}`);
    if (preview) {
        window.setupDragAndDropProceso(preview, i);
    }
}

// También configurar la sección completa
window.setupDragAndDropSeccionCompleta();
