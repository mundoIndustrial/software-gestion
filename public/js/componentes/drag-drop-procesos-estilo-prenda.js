// DRAG & DROP PARA PROCESOS - EXACTAMENTE COMO PRENDAS
// Sin logs para una experiencia limpia

window.setupDragAndDropProceso = function(previewElement, procesoIndex) {
    console.log(`[setupDragAndDropProceso]  INICIO - Configurando para preview ${procesoIndex}`);
    console.log(`[setupDragAndDropProceso]Timestamp:`, new Date().toISOString());
    console.log(`[setupDragAndDropProceso]  Stack trace:`, new Error().stack);
    
    //  IMPORTANTE: NO clonar el elemento para evitar acumular listeners
    // En su lugar, remover listeners anteriores y agregar nuevos
    const preview = previewElement;
    console.log(`[setupDragAndDropProceso]  Preview original:`, preview);
    
    // Remover todos los listeners anteriores clonando y reemplazando
    console.log(`[setupDragAndDropProceso]  Clonando preview ${procesoIndex} para eliminar listeners`);
    const newPreview = preview.cloneNode(true);
    console.log(`[setupDragAndDropProceso]  Preview ${procesoIndex} clonado`);
    preview.parentNode.replaceChild(newPreview, preview);
    console.log(`[setupDragAndDropProceso]  Preview ${procesoIndex} reemplazado en DOM`);
    
    // Prevenir comportamiento por defecto para todos los eventos
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    
    // Eventos de drag
    newPreview.addEventListener('dragover', preventDefaults, false);
    newPreview.addEventListener('dragenter', preventDefaults, false);
    newPreview.addEventListener('dragleave', preventDefaults, false);
    
    // Evento dragover con feedback visual
    newPreview.addEventListener('dragover', (e) => {
        console.log(`[setupDragAndDropProceso]  DRAGOVER en preview ${procesoIndex}`);
        e.preventDefault();
        newPreview.style.background = '#eff6ff';
        newPreview.style.border = '2px dashed #3b82f6';
        newPreview.style.opacity = '0.8';
    }, false);
    
    // Evento dragleave para restaurar estilos
    newPreview.addEventListener('dragleave', (e) => {
        console.log(`[setupDragAndDropProceso]  DRAGLEAVE en preview ${procesoIndex}`);
        e.preventDefault();
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
    }, false);
    
    // Evento drop - manejar archivos arrastrados
    newPreview.addEventListener('drop', (e) => {
        console.log(`[setupDragAndDropProceso]  DROP en preview ${procesoIndex}`);
        e.preventDefault();
        e.stopPropagation();
        
        // Restaurar estilos
        newPreview.style.background = '';
        newPreview.style.border = '';
        newPreview.style.opacity = '1';
        
        // Verificar si hay archivos
        const files = e.dataTransfer.files;
        if (files.length === 0) {
            console.log(`[setupDragAndDropProceso]  No hay archivos en drop ${procesoIndex}`);
            return;
        }
        
        console.log(`[setupDragAndDropProceso] 📁 Files recibidos en drop ${procesoIndex}:`, files.length);
        
        // Procesar el primer archivo (solo imágenes)
        const file = files[0];
        
        // Verificar que sea una imagen
        if (!file.type.startsWith('image/')) {
            console.warn(`[setupDragAndDropProceso]  Archivo no es imagen en preview ${procesoIndex}:`, file.type);
            mostrarModalError('Por favor arrastra solo archivos de imagen');
            return;
        }
        
        // Crear un input file temporal para usar la función existente
        const tempInput = document.createElement('input');
        tempInput.type = 'file';
        tempInput.files = files;
        
        // Usar la función existente para manejar la imagen
        if (typeof window.manejarImagenProceso === 'function') {
            console.log(`[setupDragAndDropProceso]  Llamando manejarImagenProceso desde drop ${procesoIndex}`);
            window.manejarImagenProceso(tempInput, procesoIndex);
        } else {
            console.error(`[setupDragAndDropProceso]  manejarImagenProceso no disponible`);
        }
    }, false);
    
    //  NUEVO: Usar event delegation a nivel del contenedor padre para evitar múltiples listeners
    // Esto se ejecuta UNA sola vez por contenedor, no por cada preview
    const fotoPanelContainer = newPreview.closest('.foto-panel');
    console.log(`[setupDragAndDropProceso]  Foto panel container:`, fotoPanelContainer);
    
    //  DESACTIVADO: Event delegation causa conflicto con configurarDragDropProcesos
    // Ya no necesitamos event delegation porque configurarDragDropProcesos maneja los listeners directamente
    console.log(`[setupDragAndDropProceso]  Event delegation DESACTIVADO para evitar conflicto`);
    
    if (false && fotoPanelContainer && !fotoPanelContainer._dragDropConfigured) { // DESACTIVADO
        console.log(`[setupDragAndDropProceso]  Configurando event delegation en contenedor`);
        fotoPanelContainer._dragDropConfigured = true;
        
        fotoPanelContainer.addEventListener('click', (e) => {
            console.log(`[setupDragAndDropProceso] 🖱️ CLICK detectado en contenedor`);
            console.log(`[setupDragAndDropProceso]Event target:`, e.target);
            
            // Detectar si el click es en un preview (pero no en botón eliminar ni en img)
            const preview = e.target.closest('.foto-preview-proceso');
            if (!preview) {
                console.log(`[setupDragAndDropProceso]  Click no es en preview`);
                return;
            }
            
            console.log(`[setupDragAndDropProceso]  Preview detectado:`, preview.id);
            
            // NO interceptar clicks en el botón eliminar
            if (e.target.closest('.btn-eliminar-imagen-proceso')) {
                console.log(`[setupDragAndDropProceso]  Click en botón eliminar, IGNORANDO`);
                return;
            }
            
            // NO interceptar si el click es en la imagen misma
            if (e.target.tagName === 'IMG') {
                console.log(`[setupDragAndDropProceso]  Click en imagen, IGNORANDO`);
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            console.log(`[setupDragAndDropProceso]  Click válido en preview, procesando`);
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener el índice del preview (1, 2, 3)
            const previewId = preview.id; // proceso-foto-preview-1
            const index = previewId.replace('proceso-foto-preview-', '');
            console.log(`[setupDragAndDropProceso] 🔢 Índice extraído: ${index}`);
            
            // Abrir el selector de archivos
            const inputProceso = document.getElementById(`proceso-foto-input-${index}`);
            console.log(`[setupDragAndDropProceso] 📁 Input encontrado:`, inputProceso);
            
            if (inputProceso) {
                console.log(`[setupDragAndDropProceso]  Intentando abrir input ${index}`);
                console.log(`[setupDragAndDropProceso]Input state:`, {
                    files: inputProceso.files?.length || 0,
                    value: inputProceso.value,
                    disabled: inputProceso.disabled,
                    _abiendoAhora: inputProceso._abiendoAhora
                });
                
                //  Flag para evitar doble apertura
                if (!inputProceso._abiendoAhora) {
                    console.log(`[setupDragAndDropProceso]  Input ${index} no está siendo abierto, procediendo`);
                    inputProceso._abiendoAhora = true;
                    inputProceso.click();
                    console.log(`[setupDragAndDropProceso]  Click ejecutado en input ${index}`);
                    
                    setTimeout(() => {
                        inputProceso._abiendoAhora = false;
                        console.log(`[setupDragAndDropProceso] 🔓 Bandera liberada para input ${index}`);
                    }, 300);
                } else {
                    console.warn(`[setupDragAndDropProceso]  Input ${index} ya está siendo abierto, IGNORANDO`);
                }
            } else {
                console.error(`[setupDragAndDropProceso]  Input ${index} NO encontrado`);
            }
        }, false);
        
        console.log(`[setupDragAndDropProceso]  Event delegation configurado en contenedor`);
    } else {
        console.log(`[setupDragAndDropProceso]  Contenedor ya configurado o no encontrado`);
    }
    
    console.log(`[setupDragAndDropProceso]  FIN - Configuración completada para preview ${procesoIndex}`);
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
        
        //  MEJORADO: Detectar usando getBoundingClientRect
        // Encontrar qué preview está bajo el cursor revisando todos
        let procesoIndex = null;
        for (let i = 1; i <= 3; i++) {
            const preview = seccionImagenes.querySelector(`#proceso-foto-preview-${i}`);
            if (!preview) continue;
            
            const rect = preview.getBoundingClientRect();
            const x = e.clientX;
            const y = e.clientY;
            
            // Verificar si el drop está dentro de este preview
            if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom) {
                procesoIndex = i.toString();
                console.log(`[Drop Fallback] Detectado en preview ${procesoIndex} usando posición`);
                break;
            }
        }
        
        // Si aún no detectamos, usar dragIndex (guardado en dragover)
        if (!procesoIndex && window.dragIndex) {
            procesoIndex = window.dragIndex;
            console.log(`[Drop Fallback] Usando dragIndex: ${procesoIndex}`);
        }
        
        //  CRÍTICO: NO caer al preview 1 como fallback
        // Si no se detectó correctamente, es mejor abortar
        if (!procesoIndex) {
            console.warn('[Drop Fallback]  No se pudo detectar preview destino');
            return;
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
console.log('[drag-drop-procesos-estilo-prenda]  INICIO - Inicialización automática de previews');
console.log('[drag-drop-procesos-estilo-prenda]Timestamp:', new Date().toISOString());

for (let i = 1; i <= 3; i++) {
    const preview = document.getElementById(`proceso-foto-preview-${i}`);
    console.log(`[drag-drop-procesos-estilo-prenda]  Buscando preview ${i}:`, !!preview);
    if (preview) {
        console.log(`[drag-drop-procesos-estilo-prenda]  Preview ${i} encontrado, configurando setupDragAndDropProceso`);
        window.setupDragAndDropProceso(preview, i);
    } else {
        console.log(`[drag-drop-procesos-estilo-prenda]  Preview ${i} NO encontrado`);
    }
}

console.log('[drag-drop-procesos-estilo-prenda]  FIN - Inicialización automática completada');

// También configurar la sección completa
console.log('[drag-drop-procesos-estilo-prenda]  Configurando sección completa');
window.setupDragAndDropSeccionCompleta();
