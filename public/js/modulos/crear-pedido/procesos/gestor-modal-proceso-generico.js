/**
 * gestor-modal-proceso-generico.js
 * 
 * Maneja la funcionalidad del modal generico de procesos
 * (Reflectivo, Estampado, Bordado, DTF, Sublimado)
 */

const procesoModalState = globalThis.procesoModalState || {
    procesoActual: null,
    // NUEVO: Flag para diferenciar entre CREACION y EDICION
    modoActual: 'crear',  // 'crear' o 'editar'
    // NUEVO: Buffer temporal para cambios en EDICION (no se sincroniza hasta GUARDAR CAMBIOS final)
    cambiosProceso: null
};
globalThis.procesoModalState = procesoModalState;

if (typeof globalThis.PROCESO_MODAL_DEBUG !== 'boolean') {
    globalThis.PROCESO_MODAL_DEBUG = false;
}
function procesoModalDebug(...args) {
    if (!globalThis.PROCESO_MODAL_DEBUG) {
        return;
    }
    console.info(...args);
}
globalThis.procesoModalDebug = procesoModalDebug;

const procesoModalModules = globalThis.procesoModalModules || {
    ui: {},
    imagenes: {},
    persistencia: {},
    tallas: {}
};
globalThis.procesoModalModules = procesoModalModules;

globalThis.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
globalThis.ubicacionesProcesoSeleccionadas = [];
// ESTRUCTURA INDEPENDIENTE: Cantidades de TALLAS DEL PROCESO (NO de la prenda)
// Estructura: { dama: { S: 5, M: 3 }, caballero: { 32: 2 }, sobremedida: { DAMA: 10, CABALLERO: 5 } }
globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };

// configuracion por tipo de proceso
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

// helpers para abrir modal generico de proceso
function _procesoGenerico_resetImagenesEliminadas() {
    globalThis.imagenesEliminadasProcesoStorage = [];
    procesoModalDebug('[abrirModalProcesoGenerico]  Storage de imagenes eliminadas reseteado');
}

function _procesoGenerico_determinarIndice(tipoProceso, esEdicion) {
    if (esEdicion && globalThis.procesosSeleccionados?.[tipoProceso]?.indiceResultado !== undefined) {
        globalThis.procesoActualIndex = globalThis.procesosSeleccionados[tipoProceso].indiceResultado;
        procesoModalDebug(`[abrirModalProcesoGenerico] EDICION: Usando indice existente ${globalThis.procesoActualIndex} para ${tipoProceso}`);
        return;
    }

    const indicesUsados = new Set();
    Object.values(globalThis.procesosSeleccionados || {}).forEach(proceso => {
        if (proceso.indiceResultado !== undefined) {
            indicesUsados.add(proceso.indiceResultado);
        }
    });

    let indiceDisponible = 1;
    while (indicesUsados.has(indiceDisponible) && indiceDisponible <= 3) {
        indiceDisponible++;
    }

    globalThis.procesoActualIndex = indiceDisponible;
    procesoModalDebug(`[abrirModalProcesoGenerico] CREACION: indice usados=${[...indicesUsados]}, index asignado=${globalThis.procesoActualIndex} para ${tipoProceso}`);
}

function _procesoGenerico_limpiarStorageUniversal(esEdicion) {
    if (globalThis.universalImagenesStorage && !esEdicion && globalThis.procesoActualIndex !== undefined) {
        procesoModalDebug(`[abrirModalProcesoGenerico]  LIMPIEZA FORZADA: Storage de PROCESOS[${globalThis.procesoActualIndex}] para nuevo proceso`);
        globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
    }
}

function _procesoGenerico_limpiarCreacion() {
    globalThis.tallasSeleccionadasProceso = { dama: [], caballero: [], sobremedida: null };
    globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };

    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
        procesoModalDebug('[abrirModalProcesoGenerico]  globalThis.imagenesProcesoActual limpiado para nuevo proceso');
    }

    if (globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
        procesoModalDebug('[abrirModalProcesoGenerico]  globalThis.imagenesProcesoExistentes limpiado para nuevo proceso');
    }

    const resumenTallas = document.getElementById('proceso-tallas-resumen');
    if (resumenTallas) resumenTallas.innerHTML = '';

    globalThis.ubicacionesProcesoSeleccionadas = [];
    const listaUbicaciones = document.getElementById('lista-ubicaciones-proceso');
    if (listaUbicaciones) listaUbicaciones.innerHTML = '';
    const inputUbicacion = document.getElementById('input-ubicacion-nueva');
    if (inputUbicacion) inputUbicacion.value = '';

    if (typeof globalThis.limpiarImagenesProceso === 'function') {
        globalThis.limpiarImagenesProceso();
    }
}

function _procesoGenerico_cargarEdicion(tipoProceso) {
    const procesoDatos = globalThis.procesosSeleccionados?.[tipoProceso]?.datos;
    if (!procesoDatos?.tallas) {
        return;
    }

    globalThis.tallasCantidadesProceso = {
        dama: procesoDatos.tallas.dama ?? {},
        caballero: procesoDatos.tallas.caballero ?? {},
        sobremedida: procesoDatos.tallas.sobremedida ?? {}
    };

    const sobremedidaSel = Object.keys(procesoDatos.tallas.sobremedida ?? {}).length > 0
        ? { ...procesoDatos.tallas.sobremedida }
        : null;

    globalThis.tallasSeleccionadasProceso = {
        dama: Object.keys(procesoDatos.tallas.dama ?? {}),
        caballero: Object.keys(procesoDatos.tallas.caballero ?? {}),
        sobremedida: sobremedidaSel
    };

    procesoModalDebug(' [EDICION] Tallas del proceso cargadas en tallasCantidadesProceso y tallasSeleccionadasProceso:', {
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tallasSeleccionadasProceso: globalThis.tallasSeleccionadasProceso
    });

    if (globalThis.renderizarListaUbicaciones) {
        globalThis.renderizarListaUbicaciones();
    }
    if (globalThis.actualizarResumenTallasProceso) {
        globalThis.actualizarResumenTallasProceso();
    }
}

function _procesoGenerico_actualizarTextos(config, tipoProceso, esEdicion) {
    const titleEl = document.getElementById('modal-proceso-titulo');
    const iconEl = document.getElementById('modal-proceso-icon');
    const btnTextoEl = document.getElementById('modal-btn-texto');

    const textoTitulo = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.titulo;
    const textoBtnTexto = esEdicion ? `Editar ${nombresProcesos[tipoProceso] || tipoProceso}` : config.btnTexto;

    if (titleEl) titleEl.textContent = textoTitulo;
    if (iconEl) iconEl.textContent = config.icon;
    if (btnTextoEl) btnTextoEl.textContent = textoBtnTexto;

    if (!esEdicion) {
        const form = document.getElementById('form-proceso-generico');
        if (form) form.reset();
    }
}

function _procesoGenerico_abrirModal(modal) {
    modal.style.display = 'flex';
    modal.style.zIndex = '999999999';
    procesoModalDebug(' [MODAL-PROCESO] Z-index forzado a:', modal.style.zIndex);
    modal.removeAttribute('aria-hidden');
}

function _procesoGenerico_postOpen(esEdicion) {
    procesoModalDebug('[abrirModalProcesoGenerico]  Sincronizando tallas de la prenda con el proceso...');
    globalThis.sincronizarTallasConModalProceso?.();
    procesoModalDebug(' [MODAL-PROCESO] aria-hidden removido para accesibilidad');

    if (!esEdicion) {
        globalThis.aplicarProcesoParaTodasTallas();
        procesoModalDebug('[abrirModalProcesoGenerico]  Tallas aplicadas automaticamente al abrir modal');
    }
}

// Abrir modal para un tipo especifico de proceso
globalThis.abrirModalProcesoGenerico = function(tipoProceso, esEdicion = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (!modal) {
        return;
    }

    _procesoGenerico_resetImagenesEliminadas();

    procesoModalState.procesoActual = tipoProceso;
    procesoModalState.modoActual = esEdicion ? 'editar' : 'crear';

    _procesoGenerico_determinarIndice(tipoProceso, esEdicion);
    _procesoGenerico_limpiarStorageUniversal(esEdicion);

    const config = procesosConfig[tipoProceso];
    if (!config) {
        return;
    }

    try {
        _procesoGenerico_actualizarTextos(config, tipoProceso, esEdicion);

        if (esEdicion) {
            _procesoGenerico_cargarEdicion(tipoProceso);
        } else {
            _procesoGenerico_limpiarCreacion();
        }

        _procesoGenerico_abrirModal(modal);
        _procesoGenerico_postOpen(esEdicion);
    } catch (error) {
        console.error('[abrirModalProcesoGenerico] Error:', error);
    }
};

// Cerrar modal
// @param {boolean} procesoGuardado - Si es true, mantiene el checkbox seleccionado (proceso guardado exitosamente)
function _cerrarModalProcesoGenerico_crear(procesoGuardado) {
    if (procesoModalState.modoActual !== 'crear' || !procesoModalState.procesoActual || procesoGuardado) return;

    const checkbox = document.getElementById(`checkbox-${procesoModalState.procesoActual}`);
    if (checkbox?.checked) {
        checkbox._ignorarOnclick = true;
        checkbox.checked = false;
    }

    globalThis.manejarCheckboxProceso?.(procesoModalState.procesoActual, false);

    if (checkbox) {
        checkbox._ignorarOnclick = false;
    }

    globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
}

function _cerrarModalProcesoGenerico_editar(procesoGuardado) {
    if (procesoModalState.modoActual !== 'editar' || !procesoGuardado) return;

    procesoModalDebug('[EDICION] Modal cerrada - cambios en buffer temporal, esperando GUARDAR CAMBIOS final');
    if (globalThis.gestorEditacionProcesos) {
        procesoModalDebug('[EDICION]  Guardando cambios en gestorEditacionProcesos...');
        globalThis.gestorEditacionProcesos.guardarCambiosActuales();
        procesoModalDebug('[EDICION]  Cambios guardados en gestorEditacionProcesos');
    }
}

function _cerrarModalProcesoGenerico_storage() {
    // Esta rama esta deshabilitada pero se conserva por referencia futura.
    // Se usa una bandera para evitar constantes inalcanzables que disparen linter.
    const storageDeshabilitado = false;

    if (storageDeshabilitado && procesoModalState.modoActual === 'crear' && globalThis.procesoActualIndex !== undefined) {
        procesoModalDebug('[cerrarModalProcesoGenerico]  NO se limpia storage - imagenes necesarias para tarjetas');
        procesoModalDebug(`[cerrarModalProcesoGenerico]  Limpiando storage UNIVERSAL de PROCESOS del indice ${globalThis.procesoActualIndex}`);

        if (globalThis.universalImagenesStorage && typeof globalThis.universalImagenesStorage.eliminarTodasLasImagenes === 'function') {
            globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
            procesoModalDebug(`[cerrarModalProcesoGenerico]  Storage UNIVERSAL de PROCESOS limpiado para indice ${globalThis.procesoActualIndex}`);
        }
        globalThis.imagenesProcesoActual = [null, null, null];
        if (globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual = [null, null, null];
        }
        procesoModalDebug('[cerrarModalProcesoGenerico]  Arrays locales de imagenes limpiados');
    } else {
        procesoModalDebug('[cerrarModalProcesoGenerico]  Storage conservado - imagenes necesarias para renderizado');
    }
}

globalThis.cerrarModalProcesoGenerico = function(procesoGuardado = false) {
    const modal = document.getElementById('modal-proceso-generico');
    if (modal) {
        modal.style.display = 'none';
    }

    _cerrarModalProcesoGenerico_crear(procesoGuardado);
    _cerrarModalProcesoGenerico_editar(procesoGuardado);
    _cerrarModalProcesoGenerico_storage();

    procesoModalState.procesoActual = null;
    globalThis.procesoActualIndex = undefined;
    procesoModalState.modoActual = 'crear';
};

// Array para almacenar los archivos reales del proceso (hasta 3)
// Cambio: Ahora almacenamos File objects en lugar de base64
// [EXTRAIDO] Manejo de imagenes movido a proceso-modal-imagenes.js
globalThis.agregarUbicacionProceso = function() {
    const input = document.getElementById('input-ubicacion-nueva');
    const ubicacion = input?.value?.trim();
    
    if (!ubicacion) {

        return;
    }
    
    // Evitar duplicados
    if (globalThis.ubicacionesProcesoSeleccionadas.includes(ubicacion)) {

        return;
    }
    
    // Agregar a la lista
    globalThis.ubicacionesProcesoSeleccionadas.push(ubicacion);

    
    // Limpiar input
    input.value = '';
    
    // Renderizar lista
    globalThis.renderizarListaUbicaciones();
};

// Remover ubicacion de la lista
globalThis.removerUbicacionProceso = function(ubicacion) {
    // Si es objeto, comparar por ubicacion.ubicacion
    if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
        globalThis.ubicacionesProcesoSeleccionadas = globalThis.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion.ubicacion;
            return u !== ubicacion.ubicacion;
        });
    } else {
        // Si es string
        globalThis.ubicacionesProcesoSeleccionadas = globalThis.ubicacionesProcesoSeleccionadas.filter(u => {
            if (typeof u === 'object') return u.ubicacion !== ubicacion;
            return u !== ubicacion;
        });
    }

    globalThis.renderizarListaUbicaciones();
};

// Renderizar la lista de ubicaciones
globalThis.renderizarListaUbicaciones = function() {
    const container = document.getElementById('lista-ubicaciones-proceso');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (globalThis.ubicacionesProcesoSeleccionadas.length === 0) {
        container.innerHTML = '<small style="color: #9ca3af;">Escribe una ubicacion y haz click en "+" para agregarla</small>';
        return;
    }
    
    globalThis.ubicacionesProcesoSeleccionadas.forEach((ubicacion, idx) => {
        const tag = document.createElement('div');
        
        // Determinar si es objeto con descripcion o solo string
        let ubicacionTexto = '';
        let ubicacionKey = '';
        
        if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
            ubicacionTexto = ubicacion.ubicacion;
            ubicacionKey = JSON.stringify(ubicacion); // Para comparacion en remover
            
            // Si tiene descripcion, mostrar expandido
            if (ubicacion.descripcion) {
                const desc = ubicacion.descripcion.substring(0, 80) + (ubicacion.descripcion.length > 80 ? '...' : '');
                tag.style.cssText = 'display: flex; gap: 0.75rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.875rem; flex-direction: column;';
                tag.innerHTML = `
                    <div style="display: flex; align-items: flex-start; gap: 0.5rem; justify-content: space-between;">
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: 0.25rem;">${ubicacionTexto}</strong>
                            <small style="color: #4b7c0f;">${desc}</small>
                        </div>
                        <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center; flex-shrink: 0;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                        </button>
                    </div>
                `;
                // Agregar evento sin usar onclick inline
                const btnClose = tag.querySelector('button');
                btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
            } else {
                tag.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;';
                tag.innerHTML = `
                    <span>${ubicacionTexto}</span>
                    <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center;">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                    </button>
                `;
                // Agregar evento sin usar onclick inline
                const btnClose = tag.querySelector('button');
                btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
            }
        } else {
            // Es un string simple
            ubicacionTexto = ubicacion;
            ubicacionKey = ubicacion;
            tag.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;';
            tag.innerHTML = `
                <span>${ubicacionTexto}</span>
                <button type="button" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; font-size: 1rem; display: flex; align-items: center;">
                    <span class="material-symbols-rounded" style="font-size: 1.2rem;">close</span>
                </button>
            `;
            // Agregar evento sin usar onclick inline
            const btnClose = tag.querySelector('button');
            btnClose.addEventListener('click', () => removerUbicacionProceso(ubicacionKey));
        }
        
        container.appendChild(tag);
    });
};

// Aplicar proceso para TODAS las tallas (de la prenda)
// [EXTRAIDO] Tallas/editor/resumen movido a proceso-modal-tallas.js

// [EXTRAIDO] Persistencia/buffer movido a proceso-modal-persistencia.js


// Confirmar que el módulo se cargó correctamente
console.log('Gestor de modal de proceso genérico cargado correctamente');
