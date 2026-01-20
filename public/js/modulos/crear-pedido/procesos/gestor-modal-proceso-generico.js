/**
 * gestor-modal-proceso-generico.js
 * 
 * Maneja la funcionalidad del modal genérico de procesos
 * (Reflectivo, Estampado, Bordado, DTF, Sublimado)
 */

let procesoActual = null;
let tallasSeleccionadasProceso = { dama: [], caballero: [] };
let ubicacionesProcesoSeleccionadas = [];

// Configuración por tipo de proceso
const procesosConfig = {
    reflectivo: {
        titulo: 'Agregar Reflectivo',
        icon: 'light_mode',
        btnTexto: 'Agregar Reflectivo'
    },
    estampado: {
        titulo: 'Agregar Estampado',
        icon: 'format_paint',
        btnTexto: 'Agregar Estampado'
    },
    bordado: {
        titulo: 'Agregar Bordado',
        icon: 'auto_awesome',
        btnTexto: 'Agregar Bordado'
    },
    dtf: {
        titulo: 'Agregar DTF',
        icon: 'print',
        btnTexto: 'Agregar DTF'
    },
    sublimado: {
        titulo: 'Agregar Sublimado',
        icon: 'palette',
        btnTexto: 'Agregar Sublimado'
    }
};

// Tallas estándar por género
const tallasEstandar = {
    dama: ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
    caballero: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
};

// Abrir modal para un tipo específico de proceso
window.abrirModalProcesoGenerico = function(tipoProceso) {
    console.log(`🔓 Intentando abrir modal para: ${tipoProceso}`);
    
    // Verificar que el modal existe
    const modal = document.getElementById('modal-proceso-generico');
    if (!modal) {
        console.error(' ERROR: Modal #modal-proceso-generico no encontrado en el DOM');
        return;
    }
    
    procesoActual = tipoProceso;
    const config = procesosConfig[tipoProceso];
    
    if (!config) {
        console.error(` ERROR: Configuración no encontrada para proceso: ${tipoProceso}`);
        return;
    }
    
    try {
        // Actualizar título e icono
        const titleEl = document.getElementById('modal-proceso-titulo');
        const iconEl = document.getElementById('modal-proceso-icon');
        const btnTextoEl = document.getElementById('modal-btn-texto');
        
        if (titleEl) titleEl.textContent = config.titulo;
        if (iconEl) iconEl.textContent = config.icon;
        if (btnTextoEl) btnTextoEl.textContent = config.btnTexto;
        
        // Limpiar formulario
        const form = document.getElementById('form-proceso-generico');
        if (form) form.reset();
        
        tallasSeleccionadasProceso = { dama: [], caballero: [] };
        
        // Limpiar resumen
        const resumenTallas = document.getElementById('proceso-tallas-resumen');
        if (resumenTallas) resumenTallas.innerHTML = '';
        
        // Limpiar ubicaciones
        ubicacionesProcesoSeleccionadas = [];
        const listaUbicaciones = document.getElementById('lista-ubicaciones-proceso');
        if (listaUbicaciones) listaUbicaciones.innerHTML = '';
        const inputUbicacion = document.getElementById('input-ubicacion-nueva');
        if (inputUbicacion) inputUbicacion.value = '';
        
        // Limpiar imágenes
        if (typeof limpiarImagenesProceso === 'function') {
            limpiarImagenesProceso();
        }
        
        // Mostrar modal
        modal.style.display = 'flex';
        
        console.log(` Modal abierto para proceso: ${tipoProceso}`);
    } catch (error) {
        console.error(` ERROR al abrir modal para ${tipoProceso}:`, error);
    }
};

// Cerrar modal
// @param {boolean} procesoGuardado - Si es true, mantiene el checkbox seleccionado (proceso guardado exitosamente)
window.cerrarModalProcesoGenerico = function(procesoGuardado = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Solo deseleccionar si NO se guardó el proceso (usuario cerró sin guardar)
    if (procesoActual && !procesoGuardado) {
        console.log(`🔴 Cerrando modal SIN guardar para: ${procesoActual}`);
        
        //  PASO 1: Deseleccionar el checkbox visualmente en el HTML
        // IMPORTANTE: Hacemos esto ANTES de llamar a manejarCheckboxProceso
        // para que el .onclick no se dispare automáticamente
        const checkbox = document.getElementById(`checkbox-${procesoActual}`);
        if (checkbox && checkbox.checked) {
            // Usar una bandera temporal para evitar que onclick se dispare
            checkbox._ignorarOnclick = true;
            checkbox.checked = false;
            console.log(` Checkbox ${procesoActual} deseleccionado`);
        }
        
        //  PASO 2: Actualizar el estado del gestor (procesos seleccionados)
        if (window.manejarCheckboxProceso) {
            window.manejarCheckboxProceso(procesoActual, false);
        }
        
        // Limpiar la bandera
        if (checkbox) {
            checkbox._ignorarOnclick = false;
        }
        
        console.log(` Modal cerrado y proceso ${procesoActual} deseleccionado`);
    } else if (procesoActual && procesoGuardado) {
        console.log(` Modal cerrado CON proceso guardado: ${procesoActual} - checkbox mantiene selección`);
    }
    
    procesoActual = null;
};

// Array para almacenar los archivos reales del proceso (hasta 3)
// Cambio: Ahora almacenamos File objects en lugar de base64
let imagenesProcesoActual = [null, null, null];

// Manejar upload de imagen individual
window.manejarImagenProceso = function(input, indice) {
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        
        //  CAMBIO: Guardar el File object directamente, NO convertir a base64
        imagenesProcesoActual[indice - 1] = file;
        
        // Mostrar preview usando URL.createObjectURL (más eficiente que base64)
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);
        if (preview) {
            const objectUrl = URL.createObjectURL(file);
            preview.innerHTML = `
                <img src="${objectUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                <button type="button" onclick="eliminarImagenProceso(${indice}); event.stopPropagation();" 
                    style="position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                    ×
                </button>
            `;
            // Limpiar URL cuando el elemento se elimine (prevenir memory leaks)
            preview._objectUrl = objectUrl;
        }
        
        console.log(`📸 Imagen ${indice} agregada al proceso (File: ${file.name}, ${(file.size / 1024).toFixed(2)}KB)`);
    }
};

// Eliminar imagen del proceso
window.eliminarImagenProceso = function(indice) {
    // Limpiar URL.createObjectURL si existe
    const preview = document.getElementById(`proceso-foto-preview-${indice}`);
    if (preview && preview._objectUrl) {
        URL.revokeObjectURL(preview._objectUrl);
        preview._objectUrl = null;
    }
    
    imagenesProcesoActual[indice - 1] = null;
    
    const input = document.getElementById(`proceso-foto-input-${indice}`);
    
    if (preview) {
        preview.innerHTML = `
            <div class="placeholder-content" style="text-align: center;">
                <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${indice}</div>
            </div>
        `;
    }
    
    if (input) {
        input.value = '';
    }
    
    console.log(`🗑️ Imagen ${indice} eliminada del proceso`);
};

// Limpiar todas las imágenes del proceso
function limpiarImagenesProceso() {
    // Limpiar URLs generadas
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview && preview._objectUrl) {
            URL.revokeObjectURL(preview._objectUrl);
            preview._objectUrl = null;
        }
    }
    
    imagenesProcesoActual = [null, null, null];
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        const input = document.getElementById(`proceso-foto-input-${i}`);
        
        if (preview) {
            preview.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${i}</div>
                </div>
            `;
        }
        
        if (input) {
            input.value = '';
        }
    }
}

// Agregar ubicación a la lista
window.agregarUbicacionProceso = function() {
    const input = document.getElementById('input-ubicacion-nueva');
    const ubicacion = input?.value?.trim();
    
    if (!ubicacion) {
        console.warn('⚠️ Campo de ubicación vacío');
        return;
    }
    
    // Evitar duplicados
    if (ubicacionesProcesoSeleccionadas.includes(ubicacion)) {
        console.warn(`⚠️ Ubicación "${ubicacion}" ya existe`);
        return;
    }
    
    // Agregar a la lista
    ubicacionesProcesoSeleccionadas.push(ubicacion);
    console.log(` Ubicación agregada: ${ubicacion}`);
    
    // Limpiar input
    input.value = '';
    
    // Renderizar lista
    renderizarListaUbicaciones();
};

// Remover ubicación de la lista
window.removerUbicacionProceso = function(ubicacion) {
    ubicacionesProcesoSeleccionadas = ubicacionesProcesoSeleccionadas.filter(u => u !== ubicacion);
    console.log(` Ubicación removida: ${ubicacion}`);
    renderizarListaUbicaciones();
};

// Renderizar la lista de ubicaciones
function renderizarListaUbicaciones() {
    const container = document.getElementById('lista-ubicaciones-proceso');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (ubicacionesProcesoSeleccionadas.length === 0) {
        container.innerHTML = '<small style="color: #9ca3af;">Escribe una ubicación y haz click en "+" para agregarla</small>';
        return;
    }
    
    ubicacionesProcesoSeleccionadas.forEach(ubicacion => {
        const tag = document.createElement('div');
        tag.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;';
        tag.innerHTML = `
            <span>${ubicacion}</span>
            <button type="button" onclick="removerUbicacionProceso('${ubicacion}')" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center;">
                <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
            </button>
        `;
        container.appendChild(tag);
    });
}

// Aplicar proceso para TODAS las tallas (de la prenda)
window.aplicarProcesoParaTodasTallas = function() {
    console.log(' Intentando aplicar proceso para todas las tallas de la prenda');
    
    // Obtener las tallas registradas de la prenda actual (con cantidades)
    const tallasPrendaConCantidades = obtenerTallasDeLaPrenda();
    
    // Extraer solo los nombres para UI (como arrays)
    const tallasPrendaArrays = {
        dama: Object.keys(tallasPrendaConCantidades.dama || {}),
        caballero: Object.keys(tallasPrendaConCantidades.caballero || {})
    };
    
    if (tallasPrendaArrays.dama.length === 0 && tallasPrendaArrays.caballero.length === 0) {
        // No hay tallas seleccionadas - mostrar modal de advertencia
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    // Para UI, usamos arrays (nombres de tallas)
    tallasSeleccionadasProceso = {
        dama: tallasPrendaArrays.dama,
        caballero: tallasPrendaArrays.caballero
    };
    
    // Guardar cantidades en variable global para acceso posterior
    window._tallasCantidadesProceso = tallasPrendaConCantidades;
    
    console.log(' Tallas aplicadas:', tallasSeleccionadasProceso);
    console.log('📊 Cantidades guardadas:', window._tallasCantidadesProceso);
    actualizarResumenTallasProceso();
};

// Obtener tallas registradas en la prenda del modal
function obtenerTallasDeLaPrenda() {
    //  Leer directamente de window.tallasSeleccionadas (fuente de verdad)
    const tallasGlobales = window.tallasSeleccionadas || {};
    const tallas = { dama: {}, caballero: {} };
    
    // Obtener cantidades desde backup permanente o global
    const cantidadesDisponibles = window._TALLAS_BACKUP_PERMANENTE || window.cantidadesTallas || {};
    
    console.log('📊 [obtenerTallasDeLaPrenda] window.tallasSeleccionadas:', tallasGlobales);
    console.log('📊 [obtenerTallasDeLaPrenda] Cantidades disponibles:', cantidadesDisponibles);
    
    // Obtener tallas de dama CON CANTIDADES
    if (tallasGlobales.dama && tallasGlobales.dama.tallas && Array.isArray(tallasGlobales.dama.tallas)) {
        tallas.dama = {};
        tallasGlobales.dama.tallas.forEach(talla => {
            const key = `dama-${talla}`;
            const cantidad = cantidadesDisponibles[key] || 0;
            if (cantidad > 0) {
                tallas.dama[talla] = cantidad;
            }
        });
        console.log(` Tallas dama encontradas con cantidades:`, tallas.dama);
    }
    
    // Obtener tallas de caballero CON CANTIDADES
    if (tallasGlobales.caballero && tallasGlobales.caballero.tallas && Array.isArray(tallasGlobales.caballero.tallas)) {
        tallas.caballero = {};
        tallasGlobales.caballero.tallas.forEach(talla => {
            const key = `caballero-${talla}`;
            const cantidad = cantidadesDisponibles[key] || 0;
            if (cantidad > 0) {
                tallas.caballero[talla] = cantidad;
            }
        });
        console.log(` Tallas caballero encontradas con cantidades:`, tallas.caballero);
    }
    
    console.log('📊 [obtenerTallasDeLaPrenda] Tallas finales:', tallas);
    return tallas;
}

// Mostrar modal de advertencia cuando no hay tallas seleccionadas
function mostrarModalAdvertenciaTallas() {
    const html = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
            <h3 style="color: #dc2626; margin-bottom: 1rem;">Sin Tallas Seleccionadas</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                Debes seleccionar al menos una talla y su cantidad en la prenda 
                antes de aplicar el proceso.
            </p>
            <p style="color: #6b7280; margin-bottom: 2rem; font-size: 0.875rem;">
                Agrega tallas en la sección "TALLAS Y CANTIDADES" del formulario.
            </p>
            <button type="button" class="btn btn-primary" onclick="cerrarModalAdvertencia()" style="padding: 0.75rem 2rem;">
                <span class="material-symbols-rounded">check</span>Entendido
            </button>
        </div>
    `;
    
    // Crear modal temporal
    const modal = document.createElement('div');
    modal.id = 'modal-advertencia-tallas';
    modal.className = 'modal-overlay';
    modal.style.zIndex = '100002';
    modal.innerHTML = `
        <div class="modal-container modal-sm">
            <div class="modal-header" style="background: #fef2f2; border-bottom: 2px solid #fecaca;">
                <h3 class="modal-title" style="color: #dc2626;">
                    <span class="material-symbols-rounded">warning</span>Advertencia
                </h3>
                <button class="modal-close-btn" onclick="cerrarModalAdvertencia()">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="modal-body">
                ${html}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

// Cerrar modal de advertencia
window.cerrarModalAdvertencia = function() {
    const modal = document.getElementById('modal-advertencia-tallas');
    if (modal) {
        modal.remove();
    }
};

// Abrir editor de tallas específicas
window.abrirEditorTallasEspecificas = function() {
    console.log(' Abriendo editor de tallas específicas de la prenda');
    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {
        console.error(' ERROR: Modal editor de tallas no encontrado');
        return;
    }
    
    // Obtener tallas registradas en la prenda (retorna objetos {talla: cantidad})
    const tallasPrenda = obtenerTallasDeLaPrenda();
    
    // Validar que haya tallas seleccionadas - son OBJETOS, no arrays
    const tallasDamaArray = Object.keys(tallasPrenda.dama || {});
    const tallasCaballeroArray = Object.keys(tallasPrenda.caballero || {});
    
    if (tallasDamaArray.length === 0 && tallasCaballeroArray.length === 0) {
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    console.log('📊 Tallas dama encontradas:', tallasDamaArray);
    console.log('📊 Tallas caballero encontradas:', tallasCaballeroArray);
    
    // Renderizar tallas DAMA (solo las seleccionadas en la prenda)
    const containerDama = document.getElementById('tallas-dama-container');
    if (containerDama) {
        containerDama.innerHTML = '';
        
        if (tallasDamaArray.length === 0) {
            containerDama.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas DAMA seleccionadas en la prenda</p>';
        } else {
            tallasDamaArray.forEach(talla => {
                const isSelected = tallasSeleccionadasProceso.dama.includes(talla);
                const cantidad = tallasPrenda.dama[talla] || 0;
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <input type="checkbox" value="${talla}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="dama">
                    <span style="font-weight: 600; min-width: 30px;">${talla}</span>
                    <input type="number" 
                        value="${cantidad}" 
                        data-talla="${talla}"
                        data-genero="dama"
                        onchange="actualizarCantidadTallaProceso(this)"
                        style="width: 70px; padding: 0.25rem 0.5rem; border: 1px solid #be185d; border-radius: 4px; text-align: center; font-weight: 700; margin-left: auto; background: #fce7f3; color: #be185d;"
                        min="0">
                `;
                label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;';
                containerDama.appendChild(label);
            });
        }
    }
    
    // Renderizar tallas CABALLERO (solo las seleccionadas en la prenda)
    const containerCaballero = document.getElementById('tallas-caballero-container');
    if (containerCaballero) {
        containerCaballero.innerHTML = '';
        
        if (tallasCaballeroArray.length === 0) {
            containerCaballero.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas CABALLERO seleccionadas en la prenda</p>';
        } else {
            tallasCaballeroArray.forEach(talla => {
                const isSelected = tallasSeleccionadasProceso.caballero.includes(talla);
                const cantidad = tallasPrenda.caballero[talla] || 0;
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <input type="checkbox" value="${talla}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="caballero">
                    <span style="font-weight: 600; min-width: 30px;">${talla}</span>
                    <input type="number" 
                        value="${cantidad}" 
                        data-talla="${talla}"
                        data-genero="caballero"
                        onchange="actualizarCantidadTallaProceso(this)"
                        style="width: 70px; padding: 0.25rem 0.5rem; border: 1px solid #1d4ed8; border-radius: 4px; text-align: center; font-weight: 700; margin-left: auto; background: #dbeafe; color: #1d4ed8;"
                        min="0">
                `;
                label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;';
                containerCaballero.appendChild(label);
            });
        }
    }
    
    // Mostrar modal editor
    modalEditor.style.display = 'flex';
    console.log(' Editor de tallas abierto');
};

// Actualizar cantidad de talla en el modal de proceso
window.actualizarCantidadTallaProceso = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    
    // Actualizar en cantidadesTallas global
    if (!window.cantidadesTallas) {
        window.cantidadesTallas = {};
    }
    
    window.cantidadesTallas[`${genero}-${talla}`] = cantidad;
    
    console.log(`📊 Cantidad actualizada: ${genero}-${talla} = ${cantidad}`);
};

// Cerrar editor de tallas
window.cerrarEditorTallas = function() {
    const modal = document.getElementById('modal-editor-tallas');
    if (modal) {
        modal.style.display = 'none';
    }
    console.log(' Editor de tallas cerrado');
};

// Guardar tallas seleccionadas desde el editor
window.guardarTallasSeleccionadas = function() {
    console.log('💾 Guardando tallas seleccionadas');
    
    // Recopilar tallas DAMA
    const checksDama = document.querySelectorAll('input[data-genero="dama"]:checked');
    tallasSeleccionadasProceso.dama = Array.from(checksDama).map(cb => cb.value);
    
    // Recopilar tallas CABALLERO
    const checksCaballero = document.querySelectorAll('input[data-genero="caballero"]:checked');
    tallasSeleccionadasProceso.caballero = Array.from(checksCaballero).map(cb => cb.value);
    
    console.log(' Tallas seleccionadas:', tallasSeleccionadasProceso);
    
    // Cerrar editor y actualizar resumen
    cerrarEditorTallas();
    actualizarResumenTallasProceso();
};

// Actualizar resumen de tallas
window.actualizarResumenTallasProceso = function() {
    const resumen = document.getElementById('proceso-tallas-resumen');
    if (!resumen) return;
    
    const totalTallas = tallasSeleccionadasProceso.dama.length + tallasSeleccionadasProceso.caballero.length;
    
    if (totalTallas === 0) {
        resumen.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    
    const cantidades = window.cantidadesTallas || {};
    
    if (tallasSeleccionadasProceso.dama.length > 0) {
        const tallasDamaHTML = tallasSeleccionadasProceso.dama.map(t => {
            const cantidad = cantidades[`dama-${t}`] || 0;
            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${t}
                <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #be185d; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-female"></i> DAMA (${tallasSeleccionadasProceso.dama.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasDamaHTML}
                </div>
            </div>
        `;
    }
    
    if (tallasSeleccionadasProceso.caballero.length > 0) {
        const tallasCaballeroHTML = tallasSeleccionadasProceso.caballero.map(t => {
            const cantidad = cantidades[`caballero-${t}`] || 0;
            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${t}
                <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #1d4ed8; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-male"></i> CABALLERO (${tallasSeleccionadasProceso.caballero.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasCaballeroHTML}
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    resumen.innerHTML = html;
};

// Agregar proceso al pedido
window.agregarProcesoAlPedido = function() {
    if (!procesoActual) {
        alert('Error: no hay proceso seleccionado');
        return;
    }
    
    try {
        // Recolectar datos
        const imagenesValidas = imagenesProcesoActual.filter(img => img !== null);
        
        const datos = {
            tipo: procesoActual,
            ubicaciones: ubicacionesProcesoSeleccionadas,
            observaciones: document.getElementById('proceso-observaciones')?.value || '',
            tallas: window._tallasCantidadesProceso || tallasSeleccionadasProceso, // Usar cantidades si están disponibles
            imagenes: imagenesValidas // Array de imágenes
        };
        
        console.log(`💾 Guardando proceso con ${imagenesValidas.length} imágenes:`, datos);
        
        //  CRÍTICO: Guardar en procesosSeleccionados CON SINCRONIZACIÓN
        if (!window.procesosSeleccionados) {
            window.procesosSeleccionados = {};
            console.warn('⚠️ window.procesosSeleccionados no existía, creado ahora');
        }
        
        // Si el proceso NO existe todavía, crearlo
        if (!window.procesosSeleccionados[procesoActual]) {
            window.procesosSeleccionados[procesoActual] = {
                tipo: procesoActual,
                datos: null
            };
            console.log(`📝 Proceso ${procesoActual} creado en window.procesosSeleccionados`);
        }
        
        // Asignar los datos capturados
        window.procesosSeleccionados[procesoActual].datos = datos;
        console.log(` Datos asignados a ${procesoActual}:`, datos);
        
        //  NUEVO: Renderizar tarjetas de procesos en el modal de prenda
        if (window.renderizarTarjetasProcesos) {
            window.renderizarTarjetasProcesos();
            console.log('🎨 Tarjetas de procesos renderizadas');
        }
        
        // Cerrar modal indicando que el proceso fue guardado exitosamente
        cerrarModalProcesoGenerico(true);
        
        // Actualizar resumen en prenda modal
        if (window.actualizarResumenProcesos) {
            window.actualizarResumenProcesos();
        }
        
        console.log(` Proceso ${procesoActual} agregado`);
    } catch (error) {
        console.error(` ERROR al agregar proceso:`, error);
    }
};

// Confirmar que el módulo se cargó correctamente
console.log(' Módulo gestor-modal-proceso-generico.js cargado correctamente');
