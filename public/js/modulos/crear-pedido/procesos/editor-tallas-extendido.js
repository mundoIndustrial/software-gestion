/**
 * editor-tallas-extendido.js
 * 
 * Extensión para el editor de tallas que permite agregar:
 * - Ubicación por talla
 * - Imagen por talla
 * 
 * Se usa cuando se estén editando procesos en modo "múltiple"
 */

// Estructura para guardar datos por talla: { dama: { 'M': { ubicacion: [], imagen: null }, ... }, caballero: {...} }
window.datosEstructuraTallasProceso = window.datosEstructuraTallasProceso || {
    dama: {},
    caballero: {},
    sobremedida: {}
};

/**
 * Obtiene los datos guardados de una talla específica
 */
function obtenerDatosTalla(genero, talla) {
    if (!window.datosEstructuraTallasProceso[genero]) {
        window.datosEstructuraTallasProceso[genero] = {};
    }
    if (!window.datosEstructuraTallasProceso[genero][talla]) {
        window.datosEstructuraTallasProceso[genero][talla] = {
            ubicaciones: [],
            imagen: null,
            observaciones: ''
        };
    }
    return window.datosEstructuraTallasProceso[genero][talla];
}

/**
 * Renderiza un campo de ubicación para una talla específica
 */
function renderizarUbicacionesTalla(genero, talla, containerElement) {
    const datosTalla = obtenerDatosTalla(genero, talla);
    
    // HTML para agregar ubicaciones
    let html = `
        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 0.5rem 0; border-top: 1px solid #e5e7eb;">
            <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.25rem;">
                <span class="material-symbols-rounded" style="font-size: 1rem;">location_on</span>
                Ubicación(es) para esta talla
            </label>
            
            <div style="display: flex; gap: 0.25rem;">
                <input type="text" 
                    placeholder="Ej: Frente, Espalda..." 
                    class="ubicacion-talla-input"
                    data-genero="${genero}"
                    data-talla="${talla}"
                    style="flex: 1; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.85rem;"
                >
                <button type="button" 
                    class="btn btn-sm btn-primary" 
                    onclick="agregarUbicacionATalla('${genero}', '${talla}')"
                    style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">add</span>
                </button>
            </div>
            
            <!-- Lista de ubicaciones agregadas -->
            <div id="ubicaciones-${genero}-${talla}" style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                ${datosTalla.ubicaciones.map((ub, idx) => `
                    <span style="background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                        ${ub}
                        <button type="button" onclick="eliminarUbicacionDeTalla('${genero}', '${talla}', ${idx})" style="background: none; border: none; cursor: pointer; color: #1d4ed8; padding: 0; margin-left: 0.25rem;">×</button>
                    </span>
                `).join('')}
            </div>
        </div>
    `;
    
    containerElement.innerHTML += html;
}

/**
 * Renderiza un campo de imagen para una talla específica
 */
function renderizarImagenTalla(genero, talla, containerElement) {
    const datosTalla = obtenerDatosTalla(genero, talla);
    const imagenId = `imagen-talla-${genero}-${talla}`;
    const inputId = `input-imagen-talla-${genero}-${talla}`;
    
    let html = `
        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 0.5rem 0; border-top: 1px solid #e5e7eb;">
            <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.25rem;">
                <span class="material-symbols-rounded" style="font-size: 1rem;">photo_camera</span>
                Imagen para esta talla
            </label>
            
            <div id="${imagenId}" 
                class="imagen-talla-preview"
                style="width: 100px; height: 100px; border: 2px dashed #0066cc; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f9fafb; position: relative; overflow: hidden;"
                onclick="document.getElementById('${inputId}').click()">
                ${datosTalla.imagen ? `<img src="${datosTalla.imagen}" style="width: 100%; height: 100%; object-fit: cover;">` : `
                    <div style="text-align: center;">
                        <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                        <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Agregar imagen</div>
                    </div>
                `}
            </div>
            
            <input type="file" 
                id="${inputId}"
                accept="image/*" 
                style="display: none;"
                onchange="cargarImagenTalla('${genero}', '${talla}', this)">
            
            ${datosTalla.imagen ? `
                <button type="button" 
                    class="btn btn-sm btn-danger"
                    onclick="eliminarImagenTalla('${genero}', '${talla}')"
                    style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                    Eliminar imagen
                </button>
            ` : ''}
        </div>
    `;
    
    containerElement.innerHTML += html;
}

/**
 * Renderiza un campo de observaciones para una talla específica
 */
function renderizarObservacionesTalla(genero, talla, containerElement) {
    const datosTalla = obtenerDatosTalla(genero, talla);
    
    let html = `
        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 0.5rem 0; border-top: 1px solid #e5e7eb;">
            <label style="font-size: 0.75rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.25rem;">
                <span class="material-symbols-rounded" style="font-size: 1rem;">description</span>
                Observaciones para esta talla
            </label>
            
            <textarea 
                class="observaciones-talla-input"
                data-genero="${genero}"
                data-talla="${talla}"
                placeholder="Ej: Color específico, instrucciones especiales..."
                style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.85rem; min-height: 60px; resize: vertical;"
            >${datosTalla.observaciones || ''}</textarea>
        </div>
    `;
    
    containerElement.innerHTML += html;
}

/**
 * Agregar una ubicación a una talla
 */
window.agregarUbicacionATalla = function(genero, talla) {
    const input = document.querySelector(`.ubicacion-talla-input[data-genero="${genero}"][data-talla="${talla}"]`);
    if (!input || !input.value.trim()) {
        alert('Por favor ingresa una ubicación');
        return;
    }
    
    const datosTalla = obtenerDatosTalla(genero, talla);
    datosTalla.ubicaciones.push(input.value.trim());
    input.value = '';
    
    // Actualizar el UI
    const containerUbicaciones = document.getElementById(`ubicaciones-${genero}-${talla}`);
    if (containerUbicaciones) {
        containerUbicaciones.innerHTML = datosTalla.ubicaciones.map((ub, idx) => `
            <span style="background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                ${ub}
                <button type="button" onclick="eliminarUbicacionDeTalla('${genero}', '${talla}', ${idx})" style="background: none; border: none; cursor: pointer; color: #1d4ed8; padding: 0; margin-left: 0.25rem;">×</button>
            </span>
        `).join('');
    }
};

/**
 * Eliminar una ubicación de una talla
 */
window.eliminarUbicacionDeTalla = function(genero, talla, index) {
    const datosTalla = obtenerDatosTalla(genero, talla);
    datosTalla.ubicaciones.splice(index, 1);
    
    // Actualizar el UI
    const containerUbicaciones = document.getElementById(`ubicaciones-${genero}-${talla}`);
    if (containerUbicaciones) {
        containerUbicaciones.innerHTML = datosTalla.ubicaciones.map((ub, idx) => `
            <span style="background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
                ${ub}
                <button type="button" onclick="eliminarUbicacionDeTalla('${genero}', '${talla}', ${idx})" style="background: none; border: none; cursor: pointer; color: #1d4ed8; padding: 0; margin-left: 0.25rem;">×</button>
            </span>
        `).join('');
    }
};

/**
 * Cargar imagen para una talla
 */
window.cargarImagenTalla = function(genero, talla, input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    // Leer archivo como Data URL
    const reader = new FileReader();
    reader.onload = function(e) {
        const imagenData = e.target.result;
        const datosTalla = obtenerDatosTalla(genero, talla);
        datosTalla.imagen = imagenData;
        
        // Actualizar el preview
        const imagenId = `imagen-talla-${genero}-${talla}`;
        const previewDiv = document.getElementById(imagenId);
        if (previewDiv) {
            previewDiv.innerHTML = `
                <img src="${imagenData}" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" 
                    class="btn btn-sm btn-danger"
                    onclick="eliminarImagenTalla('${genero}', '${talla}')"
                    style="padding: 0.4rem 0.75rem; font-size: 0.85rem; position: absolute; bottom: 0.5rem; right: 0.5rem;">
                    <span class="material-symbols-rounded" style="font-size: 0.9rem;">delete</span>
                </button>
            `;
        }
    };
    reader.readAsDataURL(file);
};

/**
 * Eliminar imagen de una talla
 */
window.eliminarImagenTalla = function(genero, talla) {
    const datosTalla = obtenerDatosTalla(genero, talla);
    datosTalla.imagen = null;
    
    // Resetear input file
    const inputId = `input-imagen-talla-${genero}-${talla}`;
    const input = document.getElementById(inputId);
    if (input) input.value = '';
    
    // Restaurar preview
    const imagenId = `imagen-talla-${genero}-${talla}`;
    const previewDiv = document.getElementById(imagenId);
    if (previewDiv) {
        previewDiv.innerHTML = `
            <div style="text-align: center;">
                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Agregar imagen</div>
            </div>
        `;
    }
};

/**
 * Guardar observaciones de una talla
 */
window.guardarObservacionesTalla = function(genero, talla) {
    const input = document.querySelector(`.observaciones-talla-input[data-genero="${genero}"][data-talla="${talla}"]`);
    if (input) {
        const datosTalla = obtenerDatosTalla(genero, talla);
        datosTalla.observaciones = input.value;
    }
};

/**
 * Obtener todos los datos de tallas (para guardar en el proceso)
 */
window.obtenerTodosDatosTallasProceso = function() {
    return window.datosEstructuraTallasProceso;
};

/**
 * Limpiar datos de tallas cuando se cierra el editor
 */
window.limpiarDatosTallasProceso = function() {
    window.datosEstructuraTallasProceso = {
        dama: {},
        caballero: {},
        sobremedida: {}
    };
};
