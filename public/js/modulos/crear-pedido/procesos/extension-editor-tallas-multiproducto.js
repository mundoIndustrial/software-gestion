/**
 * extension-editor-tallas-multiproducto.js
 * 
 * Extiende el editor de tallas para permitir:
 * - Ubicación por talla
 * - Imagen por talla
 * - Observaciones por talla
 * 
 * Este archivo REEMPLAZA la función abrirEditorTallasEspecificas original
 * con una versión mejorada que incluye estos campos.
 */

// Guardar la función original antes de reemplazarla
const abrirEditorTallasEspecificasOriginal = globalThis.abrirEditorTallasEspecificas;

// Estructura para almacenar datos por talla
globalThis.datosExtendidosTallasProceso = {
    dama: {},
    caballero: {},
    sobremedida: {}
};

/**
 * Nueva función que extiende abrirEditorTallasEspecificas
 */
globalThis.abrirEditorTallasEspecificas = function() {
    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {
        console.error('[extension-editor-tallas] Modal editor no encontrado');
        return;
    }
    
    // Obtener tallas registradas en la prenda
    const tallasPrenda = obtenerTallasDeLaPrenda();
    
    const tallasDamaArray = Object.keys(tallasPrenda.dama || {});
    const tallasCaballeroArray = Object.keys(tallasPrenda.caballero || {});
    const haySobremedida = tallasPrenda.sobremedida !== null;
    
    if (tallasDamaArray.length === 0 && tallasCaballeroArray.length === 0 && !haySobremedida) {
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    // ====== RENDERIZAR TALLAS DAMA ======
    const containerDama = document.getElementById('tallas-dama-container');
    if (containerDama && tallasDamaArray.length > 0) {
        containerDama.innerHTML = '';
        containerDama.style.display = 'grid';
        containerDama.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
        containerDama.style.gap = '1rem';
        
        tallasDamaArray.forEach(tallaKey => {
            const isSelected = globalThis.tallasSeleccionadasProceso.dama.includes(tallaKey);
            const cantidadPrenda = tallasPrenda.dama[tallaKey] || 0;
            const cantidadProceso = globalThis.tallasCantidadesProceso?.dama?.[tallaKey] || 0;

            const parts = String(tallaKey).split('__');
            const tallaDisplay = (parts[0] || tallaKey);
            const colorDisplay = (parts[1] || null);
            const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
            
            const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'dama', procesoActual);
            const cantidadDisponible = cantidadPrenda - totalAsignado;
            
            // Crear contenedor de tarjeta
            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-talla-extendida';
            tarjeta.style.cssText = `
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 1rem;
                border: 2px solid ${colorDisplay ? '#be185d' : '#bfdbfe'};
                border-radius: 10px;
                background: ${colorDisplay ? '#fce7f3' : '#eff6ff'};
                transition: all 0.2s;
            `;
            
            // Header de la tarjeta
            const header = document.createElement('div');
            header.style.cssText = 'display: flex; align-items: flex-start; gap: 0.5rem;';
            header.innerHTML = `
                <input type="checkbox" 
                    value="${tallaKey}" 
                    ${isSelected ? 'checked' : ''} 
                    class="form-checkbox talla-checkbox-editor"
                    data-genero="dama"
                    style="cursor: pointer; margin-top: 0.2rem;">
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                        ${procesosDetalle.length > 0 ? `
                            Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                        ` : `
                            Disponible: <strong>${cantidadDisponible}</strong>
                        `}
                    </div>
                </div>
            `;
            tarjeta.appendChild(header);
            
            // Campo de cantidad
            const cantidadDiv = document.createElement('div');
            cantidadDiv.style.cssText = 'display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; padding: 0.5rem; background: #ffffff; border-radius: 6px;';
            cantidadDiv.innerHTML = `
                <label style="font-size: 0.75rem; color: #9ca3af; font-weight: 600;">Cantidad</label>
                <input type="number" 
                    value="${cantidadProceso}" 
                    data-talla="${tallaKey}"
                    data-genero="dama"
                    data-max="${cantidadDisponible}"
                    onchange="actualizarCantidadTallaProceso(this)"
                    placeholder="0"
                    style="width: 80px; padding: 0.35rem 0.5rem; border: 1px solid #be185d; border-radius: 6px; text-align: center; font-weight: 800; background: #fce7f3; color: #be185d;"
                    min="0"
                    max="${cantidadDisponible}">
            `;
            tarjeta.appendChild(cantidadDiv);
            
            // Campo de ubicación
            const ubicacionDiv = document.createElement('div');
            ubicacionDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            ubicacionDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">📍 Ubicación(es)</label>
                <input type="text" 
                    class="ubicacion-talla-input-extended" 
                    data-genero="dama"
                    data-talla="${tallaKey}"
                    placeholder="Ej: Frente, Espalda..."
                    style="padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem;">
                <button type="button" 
                    class="btn btn-sm"
                    onclick="agregarUbicacionATallaExtendido('dama', '${tallaKey}')"
                    style="padding: 0.35rem 0.75rem; font-size: 0.8rem; background: #dbeafe; color: #1d4ed8; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    + Agregar
                </button>
                <div id="ubicaciones-dama-${tallaKey}" style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                </div>
            `;
            tarjeta.appendChild(ubicacionDiv);
            
            // Campo de observaciones
            const obsDiv = document.createElement('div');
            obsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            obsDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">💬 Observaciones</label>
                <textarea 
                    class="observaciones-talla-input-extended"
                    data-genero="dama"
                    data-talla="${tallaKey}"
                    placeholder="Instrucciones específicas para esta talla..."
                    style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.8rem; min-height: 50px; resize: vertical;"></textarea>
            `;
            tarjeta.appendChild(obsDiv);
            
            // Campo de imagen
            const imagenDiv = document.createElement('div');
            imagenDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            const imagenId = `imagen-extendida-dama-${tallaKey}`;
            const inputFileId = `input-extendida-dama-${tallaKey}`;
            imagenDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">📷 Imagen para esta talla</label>
                <div id="${imagenId}" 
                    class="imagen-preview-extended"
                    style="width: 100%; height: 120px; border: 2px dashed #1d4ed8; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f0f9ff; transition: all 0.2s;"
                    onclick="document.getElementById('${inputFileId}').click()">
                    <div style="text-align: center;">
                        <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                        <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Click para agregar imagen</div>
                    </div>
                </div>
                <input type="file" 
                    id="${inputFileId}"
                    accept="image/*" 
                    style="display: none;"
                    onchange="cargarImagenTallaExtendida('dama', '${tallaKey}', this)">
            `;
            tarjeta.appendChild(imagenDiv);
            
            containerDama.appendChild(tarjeta);
            
            // Renderizar ubicaciones guardadas
            actualizarListaUbicacionesExtendida('dama', tallaKey);
        });
    }
    
    // ====== RENDERIZAR TALLAS CABALLERO ======
    const containerCaballero = document.getElementById('tallas-caballero-container');
    if (containerCaballero && tallasCaballeroArray.length > 0) {
        containerCaballero.innerHTML = '';
        containerCaballero.style.display = 'grid';
        containerCaballero.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
        containerCaballero.style.gap = '1rem';
        
        tallasCaballeroArray.forEach(tallaKey => {
            const isSelected = globalThis.tallasSeleccionadasProceso.caballero.includes(tallaKey);
            const cantidadPrenda = tallasPrenda.caballero[tallaKey] || 0;
            const cantidadProceso = globalThis.tallasCantidadesProceso?.caballero?.[tallaKey] || 0;

            const parts = String(tallaKey).split('__');
            const tallaDisplay = (parts[0] || tallaKey);
            const colorDisplay = (parts[1] || null);
            const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
            
            const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'caballero', procesoActual);
            const cantidadDisponible = cantidadPrenda - totalAsignado;
            
            // Crear contenedor de tarjeta
            const tarjeta = document.createElement('div');
            tarjeta.className = 'tarjeta-talla-extendida';
            tarjeta.style.cssText = `
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 1rem;
                border: 2px solid ${colorDisplay ? '#0284c7' : '#3b82f6'};
                border-radius: 10px;
                background: ${colorDisplay ? '#e0f2fe' : '#eff6ff'};
                transition: all 0.2s;
            `;
            
            // Header de la tarjeta
            const header = document.createElement('div');
            header.style.cssText = 'display: flex; align-items: flex-start; gap: 0.5rem;';
            header.innerHTML = `
                <input type="checkbox" 
                    value="${tallaKey}" 
                    ${isSelected ? 'checked' : ''} 
                    class="form-checkbox talla-checkbox-editor"
                    data-genero="caballero"
                    style="cursor: pointer; margin-top: 0.2rem;">
                <div style="min-width: 0; flex: 1;">
                    <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                        ${procesosDetalle.length > 0 ? `
                            Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                        ` : `
                            Disponible: <strong>${cantidadDisponible}</strong>
                        `}
                    </div>
                </div>
            `;
            tarjeta.appendChild(header);
            
            // Campo de cantidad
            const cantidadDiv = document.createElement('div');
            cantidadDiv.style.cssText = 'display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; padding: 0.5rem; background: #ffffff; border-radius: 6px;';
            cantidadDiv.innerHTML = `
                <label style="font-size: 0.75rem; color: #9ca3af; font-weight: 600;">Cantidad</label>
                <input type="number" 
                    value="${cantidadProceso}" 
                    data-talla="${tallaKey}"
                    data-genero="caballero"
                    data-max="${cantidadDisponible}"
                    onchange="actualizarCantidadTallaProceso(this)"
                    placeholder="0"
                    style="width: 80px; padding: 0.35rem 0.5rem; border: 1px solid #0284c7; border-radius: 6px; text-align: center; font-weight: 800; background: #e0f2fe; color: #0284c7;"
                    min="0"
                    max="${cantidadDisponible}">
            `;
            tarjeta.appendChild(cantidadDiv);
            
            // Campo de ubicación
            const ubicacionDiv = document.createElement('div');
            ubicacionDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            ubicacionDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">📍 Ubicación(es)</label>
                <input type="text" 
                    class="ubicacion-talla-input-extended" 
                    data-genero="caballero"
                    data-talla="${tallaKey}"
                    placeholder="Ej: Frente, Espalda..."
                    style="padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem;">
                <button type="button" 
                    class="btn btn-sm"
                    onclick="agregarUbicacionATallaExtendido('caballero', '${tallaKey}')"
                    style="padding: 0.35rem 0.75rem; font-size: 0.8rem; background: #dbeafe; color: #1d4ed8; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    + Agregar
                </button>
                <div id="ubicaciones-caballero-${tallaKey}" style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                </div>
            `;
            tarjeta.appendChild(ubicacionDiv);
            
            // Campo de observaciones
            const obsDiv = document.createElement('div');
            obsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            obsDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">💬 Observaciones</label>
                <textarea 
                    class="observaciones-talla-input-extended"
                    data-genero="caballero"
                    data-talla="${tallaKey}"
                    placeholder="Instrucciones específicas para esta talla..."
                    style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.8rem; min-height: 50px; resize: vertical;"></textarea>
            `;
            tarjeta.appendChild(obsDiv);
            
            // Campo de imagen
            const imagenDiv = document.createElement('div');
            imagenDiv.style.cssText = 'display: flex; flex-direction: column; gap: 0.35rem;';
            const imagenId = `imagen-extendida-caballero-${tallaKey}`;
            const inputFileId = `input-extendida-caballero-${tallaKey}`;
            imagenDiv.innerHTML = `
                <label style="font-size: 0.75rem; font-weight: 600; color: #374151;">📷 Imagen para esta talla</label>
                <div id="${imagenId}" 
                    class="imagen-preview-extended"
                    style="width: 100%; height: 120px; border: 2px dashed #0284c7; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f0f9ff; transition: all 0.2s;"
                    onclick="document.getElementById('${inputFileId}').click()">
                    <div style="text-align: center;">
                        <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                        <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Click para agregar imagen</div>
                    </div>
                </div>
                <input type="file" 
                    id="${inputFileId}"
                    accept="image/*" 
                    style="display: none;"
                    onchange="cargarImagenTallaExtendida('caballero', '${tallaKey}', this)">
            `;
            tarjeta.appendChild(imagenDiv);
            
            containerCaballero.appendChild(tarjeta);
            
            // Renderizar ubicaciones guardadas
            actualizarListaUbicacionesExtendida('caballero', tallaKey);
        });
    }
    
    // Mostrar modal
    modalEditor.style.display = 'flex';
    modalEditor.style.zIndex = '100002';
};

/**
 * Obtener o crear estructura de datos para una talla
 */
function obtenerDatosTallaExtendida(genero, talla) {
    if (!globalThis.datosExtendidosTallasProceso[genero]) {
        globalThis.datosExtendidosTallasProceso[genero] = {};
    }
    if (!globalThis.datosExtendidosTallasProceso[genero][talla]) {
        globalThis.datosExtendidosTallasProceso[genero][talla] = {
            ubicaciones: [],
            imagen: null,
            observaciones: ''
        };
    }
    return globalThis.datosExtendidosTallasProceso[genero][talla];
}

/**
 * Agregar ubicación a una talla específica
 */
globalThis.agregarUbicacionATallaExtendido = function(genero, talla) {
    const input = document.querySelector(`.ubicacion-talla-input-extended[data-genero="${genero}"][data-talla="${talla}"]`);
    if (!input || !input.value.trim()) {
        return;
    }
    
    const datos = obtenerDatosTallaExtendida(genero, talla);
    
    // Evitar duplicados
    if (!datos.ubicaciones.includes(input.value.trim())) {
        datos.ubicaciones.push(input.value.trim());
    }
    
    input.value = '';
    actualizarListaUbicacionesExtendida(genero, talla);
};

/**
 * Eliminar ubicación de una talla
 */
globalThis.eliminarUbicacionTallaExtendida = function(genero, talla, index) {
    const datos = obtenerDatosTallaExtendida(genero, talla);
    datos.ubicaciones.splice(index, 1);
    actualizarListaUbicacionesExtendida(genero, talla);
};

/**
 * Actualizar la lista visual de ubicaciones
 */
function actualizarListaUbicacionesExtendida(genero, talla) {
    const datos = obtenerDatosTallaExtendida(genero, talla);
    const container = document.getElementById(`ubicaciones-${genero}-${talla}`);
    
    if (!container) return;
    
    container.innerHTML = datos.ubicaciones.map((ub, idx) => `
        <span style="background: #dbeafe; color: #1d4ed8; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 0.25rem;">
            ${ub}
            <button type="button" onclick="eliminarUbicacionTallaExtendida('${genero}', '${talla}', ${idx})" style="background: none; border: none; cursor: pointer; color: #1d4ed8; padding: 0; margin-left: 0.25rem; font-weight: bold;">×</button>
        </span>
    `).join('');
}

/**
 * Cargar imagen para una talla
 */
globalThis.cargarImagenTallaExtendida = function(genero, talla, input) {
    const file = input.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const datos = obtenerDatosTallaExtendida(genero, talla);
        datos.imagen = e.target.result;
        
        const imagenId = `imagen-extendida-${genero}-${talla}`;
        const previewDiv = document.getElementById(imagenId);
        
        if (previewDiv) {
            previewDiv.innerHTML = `
                <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" 
                    onclick="eliminarImagenTallaExtendida('${genero}', '${talla}')"
                    style="position: absolute; top: 5px; right: 5px; background: #dc2626; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                    Eliminar
                </button>
            `;
            previewDiv.style.position = 'relative';
        }
    };
    reader.readAsDataURL(file);
};

/**
 * Eliminar imagen de una talla
 */
globalThis.eliminarImagenTallaExtendida = function(genero, talla) {
    const datos = obtenerDatosTallaExtendida(genero, talla);
    datos.imagen = null;
    
    const inputFileId = `input-extendida-${genero}-${talla}`;
    const input = document.getElementById(inputFileId);
    if (input) input.value = '';
    
    const imagenId = `imagen-extendida-${genero}-${talla}`;
    const previewDiv = document.getElementById(imagenId);
    if (previewDiv) {
        previewDiv.style.position = 'relative';
        previewDiv.innerHTML = `
            <div style="text-align: center;">
                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Click para agregar imagen</div>
            </div>
        `;
    }
};

/**
 * Guardar observaciones (se llama automáticamente cuando el usuario escribe)
 */
globalThis.guardarObservacionesTallaExtendida = function(genero, talla, texto) {
    const datos = obtenerDatosTallaExtendida(genero, talla);
    datos.observaciones = texto;
};

/**
 * Obtener todos los datos extendidos de tallas para guardar
 */
globalThis.obtenerDatosExtendidosTallasProceso = function() {
    return globalThis.datosExtendidosTallasProceso;
};

/**
 * Limpiar datos extendidos cuando se cierra el editor
 */
globalThis.limpiarDatosExtendidosTallasProceso = function() {
    globalThis.datosExtendidosTallasProceso = {
        dama: {},
        caballero: {},
        sobremedida: {}
    };
};

// Agregar event listeners a los textareas de observaciones cuando se renderiza
setTimeout(() => {
    document.querySelectorAll('.observaciones-talla-input-extended').forEach(textarea => {
        textarea.addEventListener('change', function() {
            const genero = this.dataset.genero;
            const talla = this.dataset.talla;
            guardarObservacionesTallaExtendida(genero, talla, this.value);
        });
    });
}, 500);
