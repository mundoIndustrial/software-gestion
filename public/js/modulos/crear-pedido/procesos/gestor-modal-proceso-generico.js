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

// Tallas estandar por genero
const tallasEstandar = {
    dama: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
    caballero: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']
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

    if (typeof limpiarImagenesProceso === 'function') {
        limpiarImagenesProceso();
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
        imagenesProcesoActual = [null, null, null];
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
let imagenesProcesoActual = [null, null, null];

// Manejar upload de imagen individual
globalThis.manejarImagenProceso = function(input, indice) {
    procesoModalDebug(`[manejarImagenProceso]  INICIO - input=${input?.tagName}, indice=${indice}, procesoActualIndex=${globalThis.procesoActualIndex}`);
    
    if (input.files && input.files.length > 0) {
        const file = input.files[0];
        procesoModalDebug(`[manejarImagenProceso]  Archivo detectado: ${file.name} (${file.size} bytes)`);
        
        //  CAMBIO: Guardar el File object directamente, NO convertir a base64
        imagenesProcesoActual[indice - 1] = file;
        
        //  CRITICO: Sincronizar con globalThis.imagenesProcesoActual (usado en PATCH)
        if (!globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual = [null, null, null];
        }
        globalThis.imagenesProcesoActual[indice - 1] = file;
        procesoModalDebug('[manejarImagenProceso]  Imagen guardada en globalThis.imagenesProcesoActual:', {
            indice: indice,
            filename: file.name,
            size: file.size,
            type: file.type
        });
        
        //  NUEVO: Guardar en storage universal de PROCESOS
        if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
            procesoModalDebug(`[manejarImagenProceso]  Guardando en storage universal - procesoActualIndex=${globalThis.procesoActualIndex}`);
            const imagenData = {
                file: file,
                previewUrl: URL.createObjectURL(file),
                nombre: file.name,
                tamano: file.size,
                fileType: file.type,
                fileSize: file.size,
                fechaCreacion: new Date().toISOString()
            };
            
            const resultado = globalThis.universalImagenesStorage.agregarImagen('procesos', globalThis.procesoActualIndex, imagenData);
            procesoModalDebug(`[manejarImagenProceso]  Imagen guardada en storage universal de PROCESOS[${globalThis.procesoActualIndex}] - resultado=${resultado}`);
        } else {
            console.warn('[manejarImagenProceso]  No se pudo guardar en storage universal - no disponible o procesoActualIndex undefined');
            procesoModalDebug('[manejarImagenProceso]  Debug:', {
                universalStorage: !!globalThis.universalImagenesStorage,
                procesoActualIndex: globalThis.procesoActualIndex,
                agregarImagen: typeof globalThis.universalImagenesStorage?.agregarImagen
            });
        }
        
        // Mostrar preview usando URL.createObjectURL (mas eficiente que base64)
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);
        if (preview) {
            const objectUrl = URL.createObjectURL(file);
            preview.style.border = '2px solid #0066cc';
            preview.style.background = 'transparent';
            preview.innerHTML = `
                <img src="${objectUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
            `;
            
            //  Crear boton eliminar con data-indice (event delegation global lo detectar)
            let deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
            if (deleteBtn) deleteBtn.remove();
            deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-eliminar-imagen-proceso';
            deleteBtn.type = 'button';
            deleteBtn.dataset.indice = indice;
            deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
            deleteBtn.textContent = '×';
            preview.appendChild(deleteBtn);
            procesoModalDebug('[manejarImagenProceso]  boton eliminar creado con data-indice:', indice);
            
            // Limpiar URL cuando el elemento se elimine (prevenir memory leaks)
            preview._objectUrl = objectUrl;
        }
        
    } else {
        procesoModalDebug('[manejarImagenProceso]  No hay archivos en el input');
    }
};

//  NUEVO: Variable global para rastrear que imagen se esta eliminando
globalThis._imagenAEliminarIndice = null;

//  EVENT DELEGATION GLOBAL: Detectar clicks en botones .btn-eliminar-imagen-proceso
// Esto funciona incluso despues de que setupDragAndDropProceso clone el preview con cloneNode(true)
// porque cloneNode NO copia event listeners, pero event delegation en document si los detecta
(function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-eliminar-imagen-proceso');
        if (btn) {
            procesoModalDebug('[EVENT-DELEGATION]  Click detectado en boton eliminar imagen');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const indice = Number.parseInt(btn.dataset.indice, 10);
            procesoModalDebug('[EVENT-DELEGATION]  indice del boton:', indice);
            
            if (indice && typeof globalThis.eliminarImagenProceso === 'function') {
                procesoModalDebug('[EVENT-DELEGATION]  Llamando eliminarImagenProceso(' + indice + ')');
                globalThis.eliminarImagenProceso(indice);
            } else {
                console.error('[EVENT-DELEGATION]  indice invalido o funcion no existe:', indice, typeof globalThis.eliminarImagenProceso);
            }
        }
    }, true); // true = capture phase, se ejecuta ANTES que otros handlers
    procesoModalDebug('[EVENT-DELEGATION]  Event delegation global registrado para .btn-eliminar-imagen-proceso');
})();

function _mostrarModalConfirmarEliminarImagen(indice, source = 'mostrarModalConfirmarEliminarImagen') {
    procesoModalDebug('[' + source + ']  INICIANDO - Mostrando modal para imagen:', indice);
    globalThis._imagenAEliminarIndice = indice;
    procesoModalDebug('[' + source + ']  globalThis._imagenAEliminarIndice establecido a:', globalThis._imagenAEliminarIndice);
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    procesoModalDebug('[' + source + ']  Modal encontrado?:', !!modal);
    
    if (modal) {
        procesoModalDebug('[' + source + ']  Modal existe, mostrando...');
        modal.style.display = 'flex';
        modal.style.zIndex = '999999999';
        procesoModalDebug('[' + source + ']  Modal mostrado con z-index:', modal.style.zIndex);
    } else {
        console.error('[' + source + ']  MODAL NO ENCONTRADO - ID: modal-confirmar-eliminar-imagen-proceso');
        procesoModalDebug('[' + source + ']  Elementos en body:', document.body.children.length);
        procesoModalDebug('[' + source + ']  Buscando modales con clase modal-overlay:');
        document.querySelectorAll('.modal-overlay').forEach((m, idx) => {
            procesoModalDebug(`  [${idx}] ID: ${m.id}, Display: ${m.style.display}, Z-index: ${m.style.zIndex}`);
        });
    }
}
procesoModalModules.ui.mostrarModalConfirmarEliminarImagen = _mostrarModalConfirmarEliminarImagen;

// Mostrar modal de confirmacion para eliminar imagen
globalThis.mostrarModalConfirmarEliminarImagen = function(indice) {
    procesoModalModules.ui.mostrarModalConfirmarEliminarImagen(indice, 'mostrarModalConfirmarEliminarImagen');
};

// Cerrar modal de confirmacion
globalThis.cerrarModalConfirmarEliminarImagen = function() {
    procesoModalDebug('[cerrarModalConfirmarEliminarImagen]  Cerrando modal');
    globalThis._imagenAEliminarIndice = null;
    
    const modal = document.getElementById('modal-confirmar-eliminar-imagen-proceso');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Helpers para confirmar eliminacion de imagen
function _confirmarEliminarImagenProceso_revocarURLs(preview) {
    if (!preview) return;

    const imgEl = preview.querySelector('img');
    if (imgEl?.src?.startsWith('blob:')) {
        URL.revokeObjectURL(imgEl.src);
    }
    if (preview._objectUrl) {
        URL.revokeObjectURL(preview._objectUrl);
        preview._objectUrl = null;
    }
}
procesoModalModules.imagenes.revocarURLs = _confirmarEliminarImagenProceso_revocarURLs;

function _confirmarEliminarImagenProceso_resetMocks(indice) {
    if (imagenesProcesoActual !== undefined) {
        imagenesProcesoActual[indice - 1] = null;
    }
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual[indice - 1] = null;
    }
}
procesoModalModules.imagenes.resetMocks = _confirmarEliminarImagenProceso_resetMocks;

function _confirmarEliminarImagenProceso_guardarExistente(indice) {
    if (!(globalThis.imagenesProcesoExistentes && globalThis.imagenesProcesoExistentes.length > (indice - 1))) {
        return false;
    }

    const imagenAeliminarObj = globalThis.imagenesProcesoExistentes[indice - 1];
    if (!globalThis.imagenesEliminadasProcesoStorage) {
        globalThis.imagenesEliminadasProcesoStorage = [];
    }

    if (imagenAeliminarObj && (imagenAeliminarObj.id || imagenAeliminarObj.ruta_original)) {
        const identificador = imagenAeliminarObj.id || imagenAeliminarObj.ruta_original;
        const yaGuardado = globalThis.imagenesEliminadasProcesoStorage.some(img => (img.id || img.ruta_original) === identificador);

        if (!yaGuardado) {
            globalThis.imagenesEliminadasProcesoStorage.push(imagenAeliminarObj);
            procesoModalDebug('[confirmarEliminarImagenProceso]  Imagen GUARDADA en storage de eliminadas:', {
                id: imagenAeliminarObj.id,
                ruta: imagenAeliminarObj.ruta_original,
                identificador,
                totalEliminadas: globalThis.imagenesEliminadasProcesoStorage.length,
                contenidoStorage: globalThis.imagenesEliminadasProcesoStorage
            });
        }
    } else {
        console.warn('[confirmarEliminarImagenProceso]  Imagen sin ID ni ruta_original, no se pudo guardar:', imagenAeliminarObj);
    }

    globalThis.imagenesProcesoExistentes[indice - 1] = null;
    const imagenesParaEnviar = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== '');
    procesoModalDebug('[confirmarEliminarImagenProceso]  Imagen existente marcada como eliminada:', {
        indice: indice - 1,
        imagenesRestantes: imagenesParaEnviar.length,
        storageLlenado: globalThis.imagenesEliminadasProcesoStorage,
        globalThisImagenesProcesoExistentes: globalThis.imagenesProcesoExistentes,
        imagenesParaEnviar
    });

    return true;
}
procesoModalModules.imagenes.guardarExistente = _confirmarEliminarImagenProceso_guardarExistente;

function _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar() {
    const imagenesParaEnviar = [];

    if (globalThis.imagenesProcesoExistentes) {
        imagenesParaEnviar.push(...globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined && img !== ''));
    }

    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual.forEach(img => {
            if (img instanceof File) imagenesParaEnviar.push(img);
        });
    }

    procesoModalDebug('[confirmarEliminarImagenProceso] imagenes restantes:', imagenesParaEnviar.length);
    return imagenesParaEnviar;
}
procesoModalModules.imagenes.obtenerImagenesParaEnviar = _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar;

function _confirmarEliminarImagenProceso_registrarEnEditor() {
    if (!globalThis.procesosEditor) return;

    const imagenesParaRegistrar = _confirmarEliminarImagenProceso_obtenerImagenesParaEnviar();
    globalThis.procesosEditor.registrarCambioImagenes(imagenesParaRegistrar);
}
procesoModalModules.imagenes.registrarEnEditor = _confirmarEliminarImagenProceso_registrarEnEditor;

function _confirmarEliminarImagenProceso_resetPreview(preview, indice) {
    if (!preview) return;

    preview.style.border = '2px dashed #0066cc';
    preview.style.background = '#f9fafb';
    preview.innerHTML = `
        <div class="placeholder-content" style="text-align: center;">
            <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
            <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${indice}</div>
        </div>
    `;

    const deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
    if (deleteBtn) {
        deleteBtn.remove();
    }
}
procesoModalModules.imagenes.resetPreview = _confirmarEliminarImagenProceso_resetPreview;

// Confirmar eliminacion de imagen
globalThis.confirmarEliminarImagenProceso = function() {
    const indice = globalThis._imagenAEliminarIndice;
    if (!indice) return;

    procesoModalDebug('[confirmarEliminarImagenProceso]  Confirmando eliminacion de imagen:', indice);
    cerrarModalConfirmarEliminarImagen();

    const preview = document.getElementById(`proceso-foto-preview-${indice}`);
    procesoModalModules.imagenes.revocarURLs(preview);

    procesoModalModules.imagenes.resetMocks(indice);

    const eliminadoExistente = procesoModalModules.imagenes.guardarExistente(indice);
    if (!eliminadoExistente) {
        procesoModalModules.imagenes.obtenerImagenesParaEnviar();
    }

    procesoModalModules.imagenes.registrarEnEditor();

    procesoModalModules.imagenes.resetPreview(preview, indice);

    const input = document.getElementById(`proceso-foto-input-${indice}`);
    if (input) {
        input.value = '';
    }
};

// Eliminar imagen del proceso (ahora muestra modal de confirmacion)
globalThis.eliminarImagenProceso = function(indice) {
    procesoModalModules.ui.mostrarModalConfirmarEliminarImagen(indice, 'eliminarImagenProceso');
};

// Limpiar todas las imagenes del proceso
function limpiarImagenesProceso() {
    // Limpiar URLs generadas
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (preview?._objectUrl) {
            URL.revokeObjectURL(preview._objectUrl);
            preview._objectUrl = null;
        }
    }
    
    // Limpiar array local
    imagenesProcesoActual = [null, null, null];
    
    //  CRITICO: Limpiar arrays globales para evitar contaminacion
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
        procesoModalDebug('[limpiarImagenesProceso]  globalThis.imagenesProcesoActual limpiado');
    }
    
    if (globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
        procesoModalDebug('[limpiarImagenesProceso]  globalThis.imagenesProcesoExistentes limpiado');
    }
    
    //  NUEVO: Limpiar tambien el storage universal si hay un indice activo
    if (globalThis.universalImagenesStorage && globalThis.procesoActualIndex !== undefined) {
        procesoModalDebug(`[limpiarImagenesProceso]  Limpiando storage universal de PROCESOS[${globalThis.procesoActualIndex}]`);
        globalThis.universalImagenesStorage.eliminarTodasLasImagenes('procesos', globalThis.procesoActualIndex);
    }
    
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        const input = document.getElementById(`proceso-foto-input-${i}`);
        
        if (preview) {
            preview.style.border = '2px dashed #0066cc';
            preview.style.background = '#f9fafb';
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

// Agregar ubicacion a la lista
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
globalThis.aplicarProcesoParaTodasTallas = function() {

    
    // Obtener las tallas registradas de la prenda actual (con cantidades)
    const tallasPrendaConCantidades = obtenerTallasDeLaPrenda();
    
    // Extraer solo los nombres para UI (como arrays)
    const tallasPrendaArrays = {
        dama: Object.keys(tallasPrendaConCantidades.dama || {}),
        caballero: Object.keys(tallasPrendaConCantidades.caballero || {}),
        sobremedida: tallasPrendaConCantidades.sobremedida || null
    };
    
    // Si hay sobremedida, permitir aplicar
    const hayTallasNormales = tallasPrendaArrays.dama.length > 0 || tallasPrendaArrays.caballero.length > 0;
    const haySobremedida = tallasPrendaArrays.sobremedida !== null;
    
    if (!hayTallasNormales && !haySobremedida) {
        // No hay tallas ni sobremedida - mostrar modal de advertencia
        mostrarModalAdvertenciaTallas();
        return;
    }
    
    // Para UI, usamos arrays (nombres de tallas) o sobremedida
    globalThis.tallasSeleccionadasProceso = {
        dama: tallasPrendaArrays.dama,
        caballero: tallasPrendaArrays.caballero,
        sobremedida: tallasPrendaArrays.sobremedida
    };
    
    //  IMPORTANTE: Copiar TODAS las cantidades de la prenda al proceso
    // Esto hace que "Aplicar para todas" asigne las cantidades completas de la prenda
    globalThis.tallasCantidadesProceso = {
        dama: { ...tallasPrendaConCantidades.dama },
        caballero: { ...tallasPrendaConCantidades.caballero },
        sobremedida: tallasPrendaConCantidades.sobremedida || {}
    };
    
    procesoModalDebug(' [aplicarProcesoParaTodasTallas] Copiadas todas las tallas de la prenda al proceso:', {
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tallasSeleccionadas: globalThis.tallasSeleccionadasProceso
    });

    actualizarResumenTallasProceso();
};

// Obtener tallas registradas en la prenda del modal
function obtenerTallasDeLaPrenda() {
    procesoModalDebug('[obtenerTallasDeLaPrenda]  INICIANDO - buscando FUENTE DE VERDAD');

    const normalizarGenero = (generoRaw) => {
        const g = String(generoRaw || '').trim().toLowerCase();
        if (!g) return null;
        if (g === 'dama' || g.startsWith('dam')) return 'dama';
        if (g === 'caballero' || g.startsWith('cab')) return 'caballero';
        if (g === 'unisex' || g.startsWith('uni')) return 'sobremedida';
        return null;
    };

    const crearEstructuraTallas = () => ({ dama: {}, caballero: {}, sobremedida: null });

    const extraerTallasDesdeTabla = () => {
        const tablaBody = document.getElementById('tabla-resumen-asignaciones-cuerpo');
        if (!tablaBody) return { tallas: null, filas: 0, colores: 0 };

        const tallas = crearEstructuraTallas();
        const filas = Array.from(tablaBody.querySelectorAll('tr[data-tipo="wizard"]'));
        let contadorFilas = 0;
        let contadorColores = 0;

        filas.forEach(fila => {
            const generoRaw = fila.querySelector('[data-field="genero"]')?.textContent.trim();
            const tallaText = fila.querySelector('[data-field="talla"]')?.textContent.trim();
            const cantidadText = fila.querySelector('[data-field="cantidad"]')?.textContent.trim();
            const colorCell = fila.querySelector('[data-field="color"]');

            if (!generoRaw || !tallaText || !cantidadText) return;

            const genero = normalizarGenero(generoRaw);
            if (!genero) return;

            const cantidad = Number(cantidadText, 10) || 0;
            if (cantidad <= 0) return;

            const colors = [];
            if (colorCell) {
                const colorDiv = colorCell.querySelector('div');
                if (colorDiv) {
                    Array.from(colorDiv.querySelectorAll('span')).forEach(span => {
                        let value = String(span.textContent || '').trim().replaceAll(/\s+/g, ' ');
                        if (!value) return;
                        let base = value.split('(')[0].trim();
                        if (!base || base === value) {
                            base = value.replaceAll(/\s*\(\d+\)\s*/g, '').trim();
                        }
                        if (base && !colors.includes(base)) colors.push(base);
                    });
                }
            }

            const asignar = (key) => {
                if (genero === 'dama') {
                    tallas.dama[key] = (tallas.dama[key] || 0) + cantidad;
                } else if (genero === 'caballero') {
                    tallas.caballero[key] = (tallas.caballero[key] || 0) + cantidad;
                } else {
                    if (!tallas.sobremedida) tallas.sobremedida = {};
                    tallas.sobremedida[key] = (tallas.sobremedida[key] || 0) + cantidad;
                }
            };

            if (colors.length > 0) {
                colors.forEach(color => asignar(`${tallaText}__${color}`));
                contadorColores++;
            } else {
                asignar(tallaText);
            }

            contadorFilas++;
        });

        return { tallas, filas: contadorFilas, colores: contadorColores };
    };

    const extraerTallasDesdeStateManager = () => {
        const datosColores = globalThis.ColoresPorTalla?.datos;

        let asignaciones = null;

        if (datosColores && Object.keys(datosColores).length > 0) {
            asignaciones = datosColores;
        } else if (globalThis.StateManager && typeof globalThis.StateManager.getAsignaciones === 'function') {
            asignaciones = globalThis.StateManager.getAsignaciones();
        }

        if (!asignaciones || typeof asignaciones !== 'object' || Object.keys(asignaciones).length === 0) return null;

        const tallas = crearEstructuraTallas();
        Object.values(asignaciones).forEach(asignacion => {
            const genero = normalizarGenero(asignacion?.genero);
            if (!genero) return;

            const talla = String(asignacion?.talla || '').trim().toUpperCase();
            if (!talla) return;

            const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
            colores.forEach(c => {
                const color = String(c?.nombre || '').trim().toUpperCase();
                const cantidad = Number(c?.cantidad, 10) || 0;
                if (!color || cantidad <= 0) return;
                const key = `${talla}__${color}`;
                tallas[genero][key] = (tallas[genero][key] || 0) + cantidad;
            });
        });

        return Object.keys(tallas.dama).length || Object.keys(tallas.caballero).length || (tallas.sobremedida && Object.keys(tallas.sobremedida).length)
            ? tallas
            : null;
    };

    const extraerTallasDesdeTablaSinColor = (tablaBody) => {
        if (!tablaBody) return null;

        const filas = Array.from(tablaBody.querySelectorAll('tr[data-tipo="wizard"]'));
        if (!filas.length) return null;

        const tallas = crearEstructuraTallas();
        filas.forEach(fila => {
            const generoRaw = fila.querySelector('[data-field="genero"]')?.textContent.trim();
            const tallaText = fila.querySelector('[data-field="talla"]')?.textContent.trim();
            const cantidadText = fila.querySelector('[data-field="cantidad"]')?.textContent.trim();
            if (!generoRaw || !tallaText || !cantidadText) return;

            const genero = normalizarGenero(generoRaw);
            if (!genero) return;

            const cantidad = Number(cantidadText, 10) || 0;
            if (cantidad <= 0) return;

            if (genero === 'dama') {
                tallas.dama[tallaText] = (tallas.dama[tallaText] || 0) + cantidad;
            } else if (genero === 'caballero') {
                tallas.caballero[tallaText] = (tallas.caballero[tallaText] || 0) + cantidad;
            } else {
                if (!tallas.sobremedida) tallas.sobremedida = {};
                tallas.sobremedida[tallaText] = (tallas.sobremedida[tallaText] || 0) + cantidad;
            }
        });

        return Object.keys(tallas.dama).length || Object.keys(tallas.caballero).length || (tallas.sobremedida && Object.keys(tallas.sobremedida).length)
            ? tallas
            : null;
    };

    const extraerTallasRelacionales = () => {
        const tallasRelacionales = globalThis.tallasRelacionales || { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        const hayValores = ['DAMA', 'CABALLERO', 'SOBREMEDIDA', 'UNISEX'].some(k =>
            tallasRelacionales[k] && typeof tallasRelacionales[k] === 'object' && Object.keys(tallasRelacionales[k]).length > 0
        );

        if (!hayValores) return null;

        const tallas = crearEstructuraTallas();
        if (tallasRelacionales.DAMA && Object.keys(tallasRelacionales.DAMA).length > 0) tallas.dama = { ...tallasRelacionales.DAMA };
        if (tallasRelacionales.CABALLERO && Object.keys(tallasRelacionales.CABALLERO).length > 0) tallas.caballero = { ...tallasRelacionales.CABALLERO };
        if (tallasRelacionales.SOBREMEDIDA && Object.keys(tallasRelacionales.SOBREMEDIDA).length > 0) tallas.sobremedida = { ...tallasRelacionales.SOBREMEDIDA };
        if (tallasRelacionales.UNISEX && Object.keys(tallasRelacionales.UNISEX).length > 0) {
            tallas.sobremedida = { ...tallas.sobremedida, ...tallasRelacionales.UNISEX };
        }

        return tallas;
    };

    const tablaPrimaria = extraerTallasDesdeTabla();
    if (tablaPrimaria.colores > 0) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 1 USADA: Tabla HTML (CON COLORES - Fuente Definitiva)');
        return tablaPrimaria.tallas;
    }

    const state = extraerTallasDesdeStateManager();
    if (state) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 2 USADA: StateManager/ColoresPorTalla (tabla incompleta)');
        return state;
    }

    if (tablaPrimaria.filas > 0) {
        const tablaSinColor = extraerTallasDesdeTablaSinColor(document.getElementById('tabla-resumen-asignaciones-cuerpo'));
        if (tablaSinColor) {
            procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 3 USADA: Tabla HTML (SIN COLORES - fallback)');
            return tablaSinColor;
        }
    }

    const relacionales = extraerTallasRelacionales();
    if (relacionales) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 4 USADA: tallasRelacionales (legacy)');
        return relacionales;
    }

    if (globalThis.cantidadSoloSeleccionada) {
        procesoModalDebug('[obtenerTallasDeLaPrenda]  FUENTE 5 USADA: cantidadSoloSeleccionada');
        return { dama: {}, caballero: {}, sobremedida: { 'UNISEX': globalThis.cantidadSoloSeleccionada } };
    }

    procesoModalDebug('[obtenerTallasDeLaPrenda]  NINGUNA FUENTE disponible - retornando vacio');
    return crearEstructuraTallas();
}

// Mostrar modal de advertencia cuando no hay tallas seleccionadas
function mostrarModalAdvertenciaTallas() {
    const html = `
        <div style="text-align: center; padding: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h3 style="color: #dc2626; margin-bottom: 1rem;">Sin Tallas Seleccionadas</h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                Debes seleccionar al menos una talla y su cantidad en la prenda 
                antes de aplicar el proceso.
            </p>
            <p style="color: #6b7280; margin-bottom: 2rem; font-size: 0.875rem;">
                Agrega tallas en la seccion "TALLAS Y CANTIDADES" del formulario.
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
    
    procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Creando modal');
    
    // Agregar seguro a getComputedStyle
    const swal2Container = document.querySelector('.swal2-container');
    if (swal2Container) {
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] z-index swal2:', globalThis.getComputedStyle(swal2Container).zIndex);
    } else {
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Sin swal2-container activo');
    }
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    

    setTimeout(() => {
        modal.style.setProperty('z-index', '9999999999', 'important');
        procesoModalDebug('[MODAL-ADVERTENCIA-TALLAS] Modal visible');
    }, 10);
}

// Cerrar modal de advertencia
globalThis.cerrarModalAdvertencia = function() {
    const modal = document.getElementById('modal-advertencia-tallas');
    if (modal) {
        modal.remove();
    }
};

// Abrir editor de tallas especificas
globalThis.abrirEditorTallasEspecificas = function() {

    
    const modalEditor = document.getElementById('modal-editor-tallas');
    if (!modalEditor) {

        return;
    }
    
    // Obtener tallas registradas en la prenda (retorna objetos {talla: cantidad} o {talla__color: cantidad})
    const tallasPrenda = obtenerTallasDeLaPrenda();
    
    // Validar que haya tallas seleccionadas - son OBJETOS, no arrays
    const tallasDamaArray = Object.keys(tallasPrenda.dama || {});
    const tallasCaballeroArray = Object.keys(tallasPrenda.caballero || {});
    const haySobremedida = tallasPrenda.sobremedida !== null;
    
    if (tallasDamaArray.length === 0 && tallasCaballeroArray.length === 0 && !haySobremedida) {
        mostrarModalAdvertenciaTallas();
        return;
    }
    


    
    // Renderizar tallas DAMA (solo las seleccionadas en la prenda)
    const containerDama = document.getElementById('tallas-dama-container');
    if (containerDama) {
        containerDama.innerHTML = '';
        containerDama.style.display = 'grid';
        containerDama.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerDama.style.gap = '0.75rem';
        
        if (tallasDamaArray.length === 0) {
            containerDama.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas DAMA seleccionadas en la prenda</p>';
        } else {
            tallasDamaArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.dama.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.dama[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.dama?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuanto esta asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'dama', procesoModalState.procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="dama" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="dama"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #be185d; border-radius: 8px; text-align: center; font-weight: 800; background: #fce7f3; color: #be185d;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar informacion sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerDama.appendChild(label);
            });
        }
    }
    
    // Renderizar tallas CABALLERO (solo las seleccionadas en la prenda)
    const containerCaballero = document.getElementById('tallas-caballero-container');
    if (containerCaballero) {
        containerCaballero.innerHTML = '';
        containerCaballero.style.display = 'grid';
        containerCaballero.style.gridTemplateColumns = 'repeat(auto-fill, minmax(240px, 1fr))';
        containerCaballero.style.gap = '0.75rem';
        
        if (tallasCaballeroArray.length === 0) {
            containerCaballero.innerHTML = '<p style="color: #9ca3af; font-size: 0.875rem;">No hay tallas CABALLERO seleccionadas en la prenda</p>';
        } else {
            tallasCaballeroArray.forEach(tallaKey => {
                const isSelected = globalThis.tallasSeleccionadasProceso.caballero.includes(tallaKey);
                const cantidadPrenda = tallasPrenda.caballero[tallaKey] || 0;
                const cantidadProceso = globalThis.tallasCantidadesProceso?.caballero?.[tallaKey] || 0;

                const parts = String(tallaKey).split('__');
                const tallaDisplay = (parts[0] || tallaKey);
                const colorDisplay = (parts[1] || null);
                const etiquetaDisplay = colorDisplay ? `${tallaDisplay} - ${colorDisplay}` : tallaDisplay;
                
                // Calcular cuanto esta asignado en otros procesos
                const { totalAsignado, procesosDetalle } = calcularCantidadAsignadaOtrosProcesos(tallaKey, 'caballero', procesoModalState.procesoActual);
                const cantidadDisponible = cantidadPrenda - totalAsignado;
                
                const label = document.createElement('label');
                label.className = 'talla-checkbox-editor';
                label.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <input type="checkbox" value="${tallaKey}" ${isSelected ? 'checked' : ''} class="form-checkbox" data-genero="caballero" style="cursor: pointer; margin-top: 0.2rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 800; color: #111827; line-height: 1.2; word-break: break-word;">${etiquetaDisplay}</div>
                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.2rem;">
                                    ${procesosDetalle.length > 0 ? `
                                        Asignados: <strong style="color: #dc2626;">${totalAsignado}</strong>
                                    ` : `
                                        Disponible: <strong>${cantidadDisponible}</strong>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="font-size: 0.75rem; color: #9ca3af; white-space: nowrap;">Cantidad del proceso</div>
                            <input type="number" 
                                value="${cantidadProceso}" 
                                data-talla="${tallaKey}"
                                data-genero="caballero"
                                data-max="${cantidadDisponible}"
                                onchange="actualizarCantidadTallaProceso(this)"
                                placeholder="0"
                                style="width: 88px; padding: 0.35rem 0.5rem; border: 1px solid #1d4ed8; border-radius: 8px; text-align: center; font-weight: 800; background: #dbeafe; color: #1d4ed8;"
                                min="0"
                                max="${cantidadDisponible}">
                    </div>
                `;
                label.style.cssText = 'display: block; cursor: pointer; user-select: none;';
                
                // Agregar informacion sobre procesos previos si existen
                if (procesosDetalle.length > 0) {
                    const infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'font-size: 0.8rem; color: #6b7280; margin-left: 2.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;';
                    infoDiv.innerHTML = `
                        <strong style="color: #dc2626;"> Ya asignadas:</strong><br>
                        ${procesosDetalle.map(p => `${p.nombre}: <strong>${p.cantidad}</strong>`).join('<br>')}
                    `;
                    label.appendChild(infoDiv);
                }
                
                containerCaballero.appendChild(label);
            });
        }
    }
    
    // Mostrar modal editor
    modalEditor.style.display = 'flex';
    
    //  DIAGNOSTICO Z-INDEX
    procesoModalDebug(' [EDITOR-TALLAS] Abriendo modal de edicion de tallas...');
    procesoModalDebug(' [EDITOR-TALLAS] Z-index INICIAL (style.zIndex):', modalEditor.style.zIndex || 'NO DEFINIDO');
    procesoModalDebug(' [EDITOR-TALLAS] Z-index COMPUTADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Obtener z-index del modal principal
    const modalPrincipal = document.getElementById('modal-proceso-generico');
    if (modalPrincipal) {
        procesoModalDebug(' [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (style):', modalPrincipal.style.zIndex || 'NO DEFINIDO');
        procesoModalDebug(' [EDITOR-TALLAS] Z-index MODAL PRINCIPAL (computed):', globalThis.getComputedStyle(modalPrincipal).zIndex);
    }
    
    // Forzar z-index aún más alto
    const zIndexEditorActual = parseInt(globalThis.getComputedStyle(modalEditor).zIndex) || 100002;
    const zIndexPrincipalActual = parseInt(globalThis.getComputedStyle(modalPrincipal).zIndex) || 999999999;
    const nuevoZIndexEditor = zIndexPrincipalActual + 1;
    
    procesoModalDebug(' [EDITOR-TALLAS] Z-index EDITOR actual:', zIndexEditorActual);
    procesoModalDebug(' [EDITOR-TALLAS] Z-index PRINCIPAL actual:', zIndexPrincipalActual);
    procesoModalDebug(' [EDITOR-TALLAS] ASIGNANDO nuevo Z-index al editor:', nuevoZIndexEditor);
    
    // Aplicar z-index forzado
    modalEditor.style.zIndex = nuevoZIndexEditor.toString();
    procesoModalDebug(' [EDITOR-TALLAS] Z-index FORZADO a:', modalEditor.style.zIndex);
    procesoModalDebug(' [EDITOR-TALLAS] Z-index VERIFICADO (getComputedStyle):', globalThis.getComputedStyle(modalEditor).zIndex);
    
    // Verificar contexto de apilamiento
    procesoModalDebug(' [EDITOR-TALLAS] CONTEXTO DE APILAMIENTO:');
    procesoModalDebug('   - Modal Principal display:', globalThis.getComputedStyle(modalPrincipal).display);
    procesoModalDebug('   - Modal Principal position:', globalThis.getComputedStyle(modalPrincipal).position);
    procesoModalDebug('   - Editor display:', globalThis.getComputedStyle(modalEditor).display);
    procesoModalDebug('   - Editor position:', globalThis.getComputedStyle(modalEditor).position);
    
    // Listar todos los elementos con z-index alto en la pagina
    procesoModalDebug(' [EDITOR-TALLAS] ELEMENTOS CON Z-INDEX ALTO:');
    document.querySelectorAll('[style*="z-index"], [class*="modal"], [class*="overlay"]').forEach((el, idx) => {
        const zIdx = globalThis.getComputedStyle(el).zIndex;
        if (zIdx && zIdx !== 'auto' && parseInt(zIdx) > 100) {
            procesoModalDebug(`   ${idx}. ${el.id || el.className || el.tagName} - Z-index: ${zIdx}, Display: ${globalThis.getComputedStyle(el).display}`);
        }
    });

};

// Calcular cantidad ya asignada en OTROS procesos para una talla
function calcularCantidadAsignadaOtrosProcesos(talla, generoKey, procesoActualExcluir) {
    let totalAsignado = 0;
    const procesosDetalle = [];
    
    // Recorrer TODOS los procesos
    if (globalThis.procesosSeleccionados) {
        Object.entries(globalThis.procesosSeleccionados).forEach(([tipoProceso, datosProc]) => {
            // Excluir el proceso actual
            if (tipoProceso === procesoActualExcluir) {
                return;
            }
            
            if (datosProc?.datos?.tallas) {
                const generoTallas = datosProc.datos.tallas[generoKey] || {};
                const cantidadEnEsteProceso = generoTallas[talla] || 0;
                
                if (cantidadEnEsteProceso > 0) {
                    totalAsignado += cantidadEnEsteProceso;
                    procesosDetalle.push({
                        nombre: tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1),
                        cantidad: cantidadEnEsteProceso
                    });
                }
            }
        });
    }
    
    return { totalAsignado, procesosDetalle };
}

// Actualizar cantidad de talla en el modal de proceso
globalThis.actualizarCantidadTallaProceso = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    
    // Obtener la cantidad maxima disponible en la prenda
    const tallasPrenda = obtenerTallasDeLaPrenda();
    const generoKey = genero.toLowerCase();
    const cantidadDisponibleEnPrenda = tallasPrenda[generoKey]?.[talla] || 0;
    
    //  LOGICA CORREGIDA: Las mismas prendas pueden recibir MULTIPLES procesos
    // NO hay limite entre procesos. Solo validamos contra la cantidad total de la prenda.
    // Ejemplo: 20 camisas talla S pueden tener:
    //   - 10 con Bordado
    //   - 15 con Estampado (son las MISMAS u OTRAS camisas, lo importante es que NO superen 20 total)
    
    procesoModalDebug(` [actualizarCantidadTallaProceso] Validacion para ${talla}/${genero}:`, {
        cantidadIntentada: cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        procesoActual: procesoModalState.procesoActual,
        nota: 'Sin limite entre procesos - mismas prendas pueden recibir multiples procesos'
    });
    
    // VALIDACION: Solo permitir que NO supere la cantidad total de la prenda
    if (cantidad > cantidadDisponibleEnPrenda) {
        console.warn(` [actualizarCantidadTallaProceso] Cantidad ${cantidad} supera disponible en PRENDA ${cantidadDisponibleEnPrenda}`);
        
        // Mostrar error INLINE en rojo debajo del input
        input.style.borderColor = '#dc2626';
        input.style.backgroundColor = '#fee2e2';
        
        // Buscar el label padre que contiene 
        const label = input.closest('label');
        procesoModalDebug(' [ERROR-CSS] Label encontrado:', !!label);
        
        // Buscar o crear wrapper para mantener el grid ordenado
        let wrapper = label?.closest('.talla-error-wrapper');
        procesoModalDebug(' [ERROR-CSS] Wrapper existente:', !!wrapper);
        
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'talla-error-wrapper';
            wrapper.style.cssText = 'display: contents;';
            
            if (label?.parentNode) {
                // Reemplazar label con wrapper en el DOM
                label.parentNode.insertBefore(wrapper, label);
                // Meter label dentro del wrapper
                wrapper.appendChild(label);
                procesoModalDebug(' [ERROR-CSS] Wrapper CREADO y label MOVIDO dentro');
            }
        }
        
        // Buscar o crear elemento de error dentro del wrapper
        let errorDiv = wrapper?.querySelector('.error-cantidad');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-cantidad';
            errorDiv.style.cssText = 'color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; font-weight: 600; padding: 0 0.5rem; width: 100%; display: block;';
            if (wrapper) {
                wrapper.appendChild(errorDiv);
                procesoModalDebug(' [ERROR-CSS] ErrorDiv CREADO dentro del wrapper');
            }
        }
        
        procesoModalDebug(' [ERROR-CSS] ErrorDiv despues de crear:');
        procesoModalDebug('   - Existe:', !!errorDiv);
        procesoModalDebug('   - Display (style):', errorDiv.style.display);
        procesoModalDebug('   - Display (computed):', globalThis.getComputedStyle(errorDiv).display);
        
        errorDiv.textContent = ` Máximo: ${cantidadDisponibleEnPrenda} unidades`;
        errorDiv.style.display = 'block';
        
        procesoModalDebug(' [ERROR-CSS] Mensaje asignado');
        
        // Limpiar el campo (dejar en 0)
        input.value = 0;
        return;
        
      
    } else {
        // Limpiar error si la cantidad es valida
        input.style.borderColor = '#be185d';
        input.style.backgroundColor = '#fce7f3';
        let errorDiv = input.parentNode.querySelector('.error-cantidad');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    // Actualizar SOLO en la estructura de TALLAS DEL PROCESO
    // NO tocar globalThis.tallasRelacionales (que son las tallas de la PRENDA)
    const generoMinuscula = genero.toLowerCase();
    if (!globalThis.tallasCantidadesProceso[generoMinuscula]) {
        globalThis.tallasCantidadesProceso[generoMinuscula] = {};
    }
    
    if (cantidad > 0) {
        globalThis.tallasCantidadesProceso[generoMinuscula][talla] = cantidad;
    } else {
        // Si la cantidad es 0, eliminar la talla de las cantidades del proceso
        delete globalThis.tallasCantidadesProceso[generoMinuscula][talla];
    }
    
    // Limpiar estilos de error si la validación pasó
    input.style.borderColor = '';
    input.style.backgroundColor = '';
    
    procesoModalDebug(' [actualizarCantidadTallaProceso] Actualizado en tallasCantidadesProceso:', {
        genero,
        talla,
        cantidad,
        cantidadDisponibleEnPrenda: cantidadDisponibleEnPrenda,
        estructuraActual: globalThis.tallasCantidadesProceso
    });
};

// Cerrar editor de tallas
globalThis.cerrarEditorTallas = function() {
    const modal = document.getElementById('modal-editor-tallas');
    if (modal) {
        procesoModalDebug(' [EDITOR-TALLAS] Cerrando modal...');
        procesoModalDebug(' [EDITOR-TALLAS] Z-index ANTES de cerrar:', globalThis.getComputedStyle(modal).zIndex);
        modal.style.display = 'none';
        procesoModalDebug(' [EDITOR-TALLAS] Modal cerrado. Display:', globalThis.getComputedStyle(modal).display);
    }

};

// Guardar tallas seleccionadas desde el editor
globalThis.guardarTallasSeleccionadas = function() {

    procesoModalDebug(' [guardarTallasSeleccionadas] INICIANDO guardado de tallas...');
    procesoModalDebug(' [guardarTallasSeleccionadas] Proceso actual:', procesoModalState.procesoActual);
    procesoModalDebug(' [guardarTallasSeleccionadas] Modo:', procesoModalState.modoActual);
    
    // Recopilar tallas DAMA
    const checksDama = document.querySelectorAll('input[data-genero="dama"]:checked');
    globalThis.tallasSeleccionadasProceso.dama = Array.from(checksDama).map(cb => cb.value);
    procesoModalDebug(' [guardarTallasSeleccionadas] Tallas DAMA seleccionadas:', globalThis.tallasSeleccionadasProceso.dama);
    
    // Recopilar tallas CABALLERO
    const checksCaballero = document.querySelectorAll('input[data-genero="caballero"]:checked');
    globalThis.tallasSeleccionadasProceso.caballero = Array.from(checksCaballero).map(cb => cb.value);
    procesoModalDebug(' [guardarTallasSeleccionadas] Tallas CABALLERO seleccionadas:', globalThis.tallasSeleccionadasProceso.caballero);
    procesoModalDebug(' [guardarTallasSeleccionadas] Cantidades por talla (proceso):', globalThis.tallasCantidadesProceso);
    
    // IMPORTANTE: Actualizar el objeto del proceso con las tallas y cantidades
    // para que no pierda los datos cuando se cierre el modal
    if (procesoModalState.procesoActual && globalThis.procesosSeleccionados[procesoModalState.procesoActual]?.datos) {
        globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos.tallas = {
            dama: globalThis.tallasCantidadesProceso.dama || {},
            caballero: globalThis.tallasCantidadesProceso.caballero || {},
            sobremedida: globalThis.tallasCantidadesProceso.sobremedida || {}
        };
        
        procesoModalDebug(` [guardarTallasSeleccionadas] Tallas guardadas en proceso "${procesoModalState.procesoActual}":`, {
            tallas: globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos.tallas,
            tallasCantidadesProceso: globalThis.tallasCantidadesProceso
        });
    } else {
        console.warn(` [guardarTallasSeleccionadas] NO SE PUDO GUARDAR: procesoActual="${procesoModalState.procesoActual}", procesosSeleccionados exists=${!!globalThis.procesosSeleccionados}`);
    }

    procesoModalDebug(' [guardarTallasSeleccionadas] ESTADO ANTES DE CERRAR MODAL:');
    procesoModalDebug('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    procesoModalDebug('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    // Cerrar editor y actualizar resumen
    cerrarEditorTallas();
    
    procesoModalDebug(' [guardarTallasSeleccionadas] ESTADO DESPUÉS DE CERRAR MODAL:');
    procesoModalDebug('   - Modal editor display:', globalThis.getComputedStyle(document.getElementById('modal-editor-tallas')).display);
    procesoModalDebug('   - Modal principal display:', globalThis.getComputedStyle(document.getElementById('modal-proceso-generico')).display);
    
    actualizarResumenTallasProceso();
    procesoModalDebug(' [guardarTallasSeleccionadas] GUARDADO COMPLETADO');
};
procesoModalModules.tallas.guardarTallasSeleccionadas = globalThis.guardarTallasSeleccionadas;

// Actualizar resumen de tallas
globalThis.actualizarResumenTallasProceso = function() {
    procesoModalDebug('[actualizarResumenTallasProceso]  Iniciando renderización de resumen...');
    
    const resumen = document.getElementById('proceso-tallas-resumen');
    procesoModalDebug('[actualizarResumenTallasProceso]  Elemento resumen encontrado?:', !!resumen);
    
    if (!resumen) {
        console.warn('[actualizarResumenTallasProceso]  NO SE ENCONTRÓ elemento #proceso-tallas-resumen');
        return;
    }
    
    procesoModalDebug('[actualizarResumenTallasProceso]  globalThis.tallasSeleccionadasProceso:', globalThis.tallasSeleccionadasProceso);
    procesoModalDebug('[actualizarResumenTallasProceso]  globalThis.tallasCantidadesProceso:', globalThis.tallasCantidadesProceso);
    
    const totalTallas = globalThis.tallasSeleccionadasProceso.dama.length + globalThis.tallasSeleccionadasProceso.caballero.length;
    const haySobremedida = globalThis.tallasSeleccionadasProceso.sobremedida && Object.keys(globalThis.tallasSeleccionadasProceso.sobremedida).length > 0;
    procesoModalDebug('[actualizarResumenTallasProceso] Total de tallas seleccionadas:', totalTallas, ' | Hay sobremedida:', haySobremedida);
    
    if (totalTallas === 0 && !haySobremedida) {
        procesoModalDebug('[actualizarResumenTallasProceso]  No hay tallas ni sobremedida seleccionadas, mostrando placeholder');
        resumen.innerHTML = '<p style="color: #9ca3af;">Selecciona tallas donde aplicar el proceso</p>';
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    
    // Obtener cantidades desde tallasCantidadesProceso (ESTRUCTURA DEL PROCESO, NO DE LA PRENDA)
    const tallasProceso = globalThis.tallasCantidadesProceso || { dama: {}, caballero: {} };
    procesoModalDebug('[actualizarResumenTallasProceso]  tallasProceso para renderizar:', tallasProceso);

    const formatearTallaKey = (tallaKey) => {
        const parts = String(tallaKey).split('__');
        const talla = (parts[0] || tallaKey);
        const color = (parts[1] || null);
        return color ? `${talla} - ${color}` : talla;
    };
    
    if (globalThis.tallasSeleccionadasProceso.dama.length > 0) {
        procesoModalDebug('[actualizarResumenTallasProceso] Renderizando DAMA:', globalThis.tallasSeleccionadasProceso.dama);
        const tallasDamaHTML = globalThis.tallasSeleccionadasProceso.dama.map(t => {
            const cantidad = tallasProceso.dama?.[t] || 0;
            procesoModalDebug(`[actualizarResumenTallasProceso]  DAMA ${t}: cantidad=${cantidad}`);
            return `<span style="background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #be185d; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-female"></i> DAMA (${globalThis.tallasSeleccionadasProceso.dama.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasDamaHTML}
                </div>
            </div>
        `;
    }
    
    if (globalThis.tallasSeleccionadasProceso.caballero.length > 0) {
        procesoModalDebug('[actualizarResumenTallasProceso] Renderizando CABALLERO:', globalThis.tallasSeleccionadasProceso.caballero);
        const tallasCaballeroHTML = globalThis.tallasSeleccionadasProceso.caballero.map(t => {
            const cantidad = tallasProceso.caballero?.[t] || 0;
            procesoModalDebug(`[actualizarResumenTallasProceso]  CABALLERO ${t}: cantidad=${cantidad}`);
            return `<span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${formatearTallaKey(t)}
                <span style="background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #1d4ed8; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-male"></i> CABALLERO (${globalThis.tallasSeleccionadasProceso.caballero.length})
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${tallasCaballeroHTML}
                </div>
            </div>
        `;
    }
    
    // AGREGAR SOBREMEDIDA AL RESUMEN
    if (haySobremedida && globalThis.tallasCantidadesProceso.sobremedida) {
        procesoModalDebug('[actualizarResumenTallasProceso]  Renderizando SOBREMEDIDA:', globalThis.tallasCantidadesProceso.sobremedida);
        
        const sobremedidaHTML = Object.entries(globalThis.tallasCantidadesProceso.sobremedida).map(([genero, cantidad]) => {
            procesoModalDebug(`[actualizarResumenTallasProceso]  SOBREMEDIDA ${genero}: ${cantidad}`);
            const colorMap = {
                'DAMA': { bg: '#fce7f3', text: '#be185d' },
                'CABALLERO': { bg: '#dbeafe', text: '#1d4ed8' },
                'UNISEX': { bg: '#f3e8ff', text: '#7c3aed' }
            };
            const colores = colorMap[genero] || { bg: '#e5e7eb', text: '#374151' };
            
            return `<span style="background: ${colores.bg}; color: ${colores.text}; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem;">
                ${genero}
                <span style="background: ${colores.text}; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;">${cantidad}</span>
            </span>`;
        }).join('');
        
        html += `
            <div>
                <strong style="color: #0066cc; margin-bottom: 0.5rem; display: block;">
                    <span class="material-symbols-rounded" style="font-size: 1rem; vertical-align: middle;">straighten</span> SOBREMEDIDA
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                    ${sobremedidaHTML}
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML generado (length):', html.length);
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML preview:', html.substring(0, 200) + '...');
    
    resumen.innerHTML = html;
    procesoModalDebug('[actualizarResumenTallasProceso]  HTML inyectado en DOM');
    procesoModalDebug('[actualizarResumenTallasProceso]  innerHTML actual:', resumen.innerHTML.substring(0, 200));
};
procesoModalModules.tallas.actualizarResumen = globalThis.actualizarResumenTallasProceso;

function _procesoGenerico_clonarUbicacionesSeleccionadas() {
    return (globalThis.ubicacionesProcesoSeleccionadas || []).map(u => {
        if (u && typeof u === 'object') {
            return { ...u };
        }
        return u;
    });
}

function _procesoGenerico_normalizarImagenesEliminadas() {
    const imagenesEliminadasArray = globalThis.imagenesEliminadasProcesoStorage || [];
    return imagenesEliminadasArray
        .map(img => {
            if (img && img.id) {
                return {
                    id: img.id,
                    ruta_original: img.ruta_original,
                    ruta_webp: img.ruta_webp
                };
            }
            return img;
        })
        .filter(img => img !== null && img !== undefined);
}

// Agregar proceso al pedido
function _procesoGenerico_obtenerImagenesParaGuardar() {
    procesoModalDebug('[agregarProcesoAlPedido]  Buscando imagenes en globalThis.procesosImagenesStorage...');
    procesoModalDebug('[agregarProcesoAlPedido]  procesoActual:', procesoModalState.procesoActual);
    procesoModalDebug('[agregarProcesoAlPedido]  procesoActualIndex:', globalThis.procesoActualIndex);
    procesoModalDebug('[agregarProcesoAlPedido]  globalThis.procesosImagenesStorage.obtenerImagenes:', typeof globalThis.procesosImagenesStorage?.obtenerImagenes);

    const imagenesExistentes = (globalThis.imagenesProcesoExistentes || []).filter(img => img !== null);
    let imagenesDelStorage = [];
    let imagenesNuevasAgregadas = [];

    if (procesoModalState.modoActual !== 'editar') {
        procesoModalDebug('[agregarProcesoAlPedido]  Modo CREACION: Buscando imagenes en storage UNIVERSAL de PROCESOS...');

        if (globalThis.universalImagenesStorage && typeof globalThis.universalImagenesStorage.obtenerImagenes === 'function') {
            if (globalThis.procesoActualIndex !== undefined && globalThis.procesoActualIndex > 0) {
                const imagenesEnIndice = globalThis.universalImagenesStorage.obtenerImagenes('procesos', globalThis.procesoActualIndex);
                procesoModalDebug(`[agregarProcesoAlPedido] Usando INDICE ESPECIFICO: ${globalThis.procesoActualIndex} -> ${imagenesEnIndice?.length || 0} imagenes de PROCESOS`);
                if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                    imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                    procesoModalDebug(`[agregarProcesoAlPedido]  ENCONTRADAS ${imagenesDelStorage.length} imagenes de PROCESOS en indice ${globalThis.procesoActualIndex}`);
                }
            } else {
                console.warn('[agregarProcesoAlPedido]  procesoActualIndex NO definido, buscando en indices 1-3 como fallback...');
                for (let idx = 1; idx <= 3; idx++) {
                    const imagenesEnIndice = globalThis.universalImagenesStorage.obtenerImagenes('procesos', idx);
                    procesoModalDebug(`  [agregarProcesoAlPedido] Fallback: Indice ${idx}: ${imagenesEnIndice?.length || 0} imagenes de PROCESOS`);
                    if (imagenesEnIndice && imagenesEnIndice.length > 0) {
                        imagenesDelStorage = imagenesEnIndice.filter(img => img !== null);
                        procesoModalDebug(`[agregarProcesoAlPedido]  FALLBACK: ENCONTRADAS ${imagenesDelStorage.length} imagenes de PROCESOS en indice ${idx}`);
                        break;
                    }
                }
            }
        }

        if (imagenesDelStorage.length === 0) {
            const imagenesNuevas = (globalThis.imagenesProcesoActual || []).filter(img => img !== null);
            if (imagenesNuevas.length > 0) {
                imagenesDelStorage = imagenesNuevas;
                procesoModalDebug('[agregarProcesoAlPedido]  Fallback: Imagenes obtenidas desde globalThis.imagenesProcesoActual:', imagenesDelStorage.length);
            }
        }
    } else {
        procesoModalDebug('[agregarProcesoAlPedido]  Modo EDICION: Usando imagenesExistentes + imagenes NUEVAS agregadas');
        imagenesNuevasAgregadas = (globalThis.imagenesProcesoActual || []).filter(img => img !== null && img instanceof File);
        procesoModalDebug('[agregarProcesoAlPedido]  Imagenes nuevas agregadas en edicion:', imagenesNuevasAgregadas.length);
    }

    let imagenesFinales = [];

    if (procesoModalState.modoActual === 'editar') {
        procesoModalDebug('[agregarProcesoAlPedido]  DEBUG EDICION - Estado de variables:', {
            'globalThis.imagenesProcesoExistentes': globalThis.imagenesProcesoExistentes,
            length: globalThis.imagenesProcesoExistentes?.length || 0,
            contenido: globalThis.imagenesProcesoExistentes?.map((img, idx) => ({
                idx,
                esNull: img === null,
                esUndefined: img === undefined,
                tipo: typeof img,
                nombre: img?.name || img?.nombre || 'sin-nombre'
            }))
        });

        if (globalThis.imagenesProcesoExistentes && globalThis.imagenesProcesoExistentes.length > 0) {
            imagenesFinales = globalThis.imagenesProcesoExistentes.filter(img => img !== null && img !== undefined);
            procesoModalDebug('[agregarProcesoAlPedido]  MODO EDICION: Usando imagenes del estado actual del modal:', imagenesFinales.length);
        } else {
            const procesoGuardado = globalThis.procesosSeleccionados?.[procesoModalState.procesoActual]?.datos;
            procesoModalDebug('[agregarProcesoAlPedido]  DEBUG - procesoGuardado:', procesoGuardado);
            if (procesoGuardado?.imagenes && procesoGuardado.imagenes.length > 0) {
                imagenesFinales = procesoGuardado.imagenes.filter(img => img !== null && img !== undefined);
                procesoModalDebug('[agregarProcesoAlPedido]  MODO EDICION: Usando imagenes del proceso guardado (fallback):', imagenesFinales.length);
            } else {
                imagenesFinales = imagenesExistentes;
                procesoModalDebug('[agregarProcesoAlPedido]  MODO EDICION: Usando imagenes existentes (ultimo fallback):', imagenesFinales.length);
            }
        }

        if (imagenesNuevasAgregadas.length > 0) {
            const imagenesUnicasNuevas = imagenesNuevasAgregadas.filter(nuevaImg => {
                return nuevaImg instanceof File && !imagenesFinales.some(existingImg =>
                    existingImg instanceof File && existingImg.name === nuevaImg.name && existingImg.size === nuevaImg.size
                );
            });

            if (imagenesUnicasNuevas.length > 0) {
                imagenesFinales = [...imagenesFinales, ...imagenesUnicasNuevas];
                procesoModalDebug('[agregarProcesoAlPedido]  Agregando imagenes realmente nuevas en edicion:', imagenesUnicasNuevas.length);
            } else {
                procesoModalDebug('[agregarProcesoAlPedido]  No hay imagenes nuevas unicas para agregar (todas ya existen)');
            }
        }
    } else {
        if (globalThis.procesoActualIndex !== undefined && globalThis.procesoActualIndex > 0) {
            imagenesFinales = imagenesDelStorage;
            procesoModalDebug(`[agregarProcesoAlPedido]  MODO CREACION: Usando imagenes del storage indice ${globalThis.procesoActualIndex}:`, imagenesFinales.length);
        } else {
            console.warn('[agregarProcesoAlPedido]  Sin indice de proceso definido, no se usaran imagenes');
            imagenesFinales = [];
        }
    }

    const imagenesValidas = imagenesFinales.filter(img => img !== null && img !== undefined && img !== '');

    procesoModalDebug('[agregarProcesoAlPedido]  IMAGENES CAPTURADAS:', {
        modoActual: procesoModalState.modoActual,
        procesoActualIndex: globalThis.procesoActualIndex,
        imagenesFinales: imagenesFinales.length,
        imagenesValidas: imagenesValidas.length,
        fuentes: {
            imagenesDelStorage: imagenesDelStorage.length,
            imagenesExistentes: imagenesExistentes.length,
            imagenesNuevasAgregadas: imagenesNuevasAgregadas.length
        },
        imagenesValidasDetalle: imagenesValidas.map((img, idx) => ({
            index: idx,
            tipo: img instanceof File ? 'File' : 'Object',
            nombre: img?.nombre || img?.name || 'sin-nombre',
            tienePreviewUrl: !!img?.previewUrl,
            tieneDataURL: !!img?.dataURL,
            tieneFile: !!img?.file,
            tieneUrl: !!img?.url,
            tieneRuta: !!img?.ruta_original,
            previewUrlSample: img?.previewUrl?.substring(0, 50) || 'N/A',
            claves: typeof img === 'object' ? Object.keys(img) : 'N/A'
        }))
    });

    return {
        imagenesValidas,
        imagenesExistentes,
        imagenesDelStorage,
        imagenesNuevasAgregadas
    };
}
procesoModalModules.persistencia.obtenerImagenesParaGuardar = _procesoGenerico_obtenerImagenesParaGuardar;

function _procesoGenerico_construirDatosProceso(imagenesValidas) {
    const cantidadOriginales = (globalThis.imagenesProcesoExistentes || []).length;
    const imagenesEliminadasNormalizadas = _procesoGenerico_normalizarImagenesEliminadas();
    const ubicacionesClonadas = _procesoGenerico_clonarUbicacionesSeleccionadas();

    // En edición, obtener ID y tipo_proceso_id del proceso existente
    const procesoExistente = globalThis.procesosSeleccionados?.[procesoModalState.procesoActual];
    const datosDelProceso = procesoExistente?.datos || procesoExistente;
    const idProceso = datosDelProceso?.id || procesoExistente?.id;
    const tipoProceso = datosDelProceso?.tipo_proceso_id || procesoExistente?.tipo_proceso_id;

    procesoModalDebug('[agregarProcesoAlPedido]  BUSQUEDA DE ID:', {
        procesoActual: procesoModalState.procesoActual,
        existeProceso: !!procesoExistente,
        existeDatos: !!datosDelProceso,
        idEncontrado: !!idProceso,
        idValor: idProceso,
        tipoProcesoEncontrado: !!tipoProceso,
        tipoValor: tipoProceso,
        procesoExistenteKeys: procesoExistente ? Object.keys(procesoExistente) : [],
        datosDelProcesoKeys: datosDelProceso ? Object.keys(datosDelProceso) : []
    });

    const datos = {
        tipo: procesoModalState.procesoActual,
        modo_tallas: globalThis.procesosSeleccionados?.[procesoModalState.procesoActual]?.datos?.modo_tallas || 'generico',
        ubicaciones: ubicacionesClonadas,
        observaciones: document.getElementById('proceso-observaciones')?.value || '',
        tallas: {
            dama: { ...globalThis.tallasCantidadesProceso?.dama } || {},
            caballero: { ...globalThis.tallasCantidadesProceso?.caballero } || {},
            sobremedida: { ...globalThis.tallasCantidadesProceso?.sobremedida } || {}
        },
        imagenes: imagenesValidas,
        imagenesEliminadas: imagenesEliminadasNormalizadas
    };

    // Siempre incluir ID del proceso si existe (determina UPDATE vs INSERT en backend)
    if (idProceso) {
        datos.id = idProceso;
        procesoModalDebug('[agregarProcesoAlPedido]  ID del proceso inclido para UPDATE:', idProceso);
    } else {
        procesoModalDebug('[agregarProcesoAlPedido]  WARNING: No se encontró ID del proceso. Modo:', procesoModalState.modoActual);
    }
    
    if (tipoProceso) {
        datos.tipo_proceso_id = tipoProceso;
    }

    procesoModalDebug('[agregarProcesoAlPedido]  DEBUG imagenesEliminadas:', {
        cantidadOriginales,
        storageLength: (globalThis.imagenesEliminadasProcesoStorage || []).length,
        storageContent: globalThis.imagenesEliminadasProcesoStorage,
        imagenesValidas: imagenesValidas.map((img, idx) => ({
            idx,
            esFile: img instanceof File,
            tipo: typeof img,
            nombre: img?.name || img?.nombre || 'sin-nombre'
        })),
        imagenesEliminadasArray: imagenesEliminadasNormalizadas.map((img, idx) => ({
            idx,
            esNull: img === null,
            esFile: img instanceof File,
            tipo: typeof img
        }))
    });

    procesoModalDebug('[agregarProcesoAlPedido] Datos capturados:', {
        tipo: procesoModalState.procesoActual,
        tallas: datos.tallas,
        tallasCantidadesProceso: globalThis.tallasCantidadesProceso,
        tieneUbicaciones: (globalThis.ubicacionesProcesoSeleccionadas || []).length > 0
    });

    return { datos, ubicacionesClonadas };
}
procesoModalModules.persistencia.construirDatosProceso = _procesoGenerico_construirDatosProceso;

function _procesoGenerico_persistirDatosProceso(datos, ubicacionesClonadas) {
    if (procesoModalState.modoActual === 'crear') {
        if (!globalThis.procesosSeleccionados) {
            globalThis.procesosSeleccionados = {};
        }

        if (!globalThis.procesosSeleccionados[procesoModalState.procesoActual]) {
            globalThis.procesosSeleccionados[procesoModalState.procesoActual] = {
                tipo: procesoModalState.procesoActual,
                indiceResultado: globalThis.procesoActualIndex,
                datos: null
            };
        }

        globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos = {
            ...datos,
            ubicaciones: [...ubicacionesClonadas]
        };
        globalThis.procesosSeleccionados[procesoModalState.procesoActual].indiceResultado = globalThis.procesoActualIndex;

        procesoModalDebug('[agregarProcesoAlPedido-GUARDADO] Proceso guardado en globalThis.procesosSeleccionados:', {
            tipo: procesoModalState.procesoActual,
            indice: globalThis.procesoActualIndex,
            datosGuardados: globalThis.procesosSeleccionados[procesoModalState.procesoActual].datos
        });
        return;
    }

    if (procesoModalState.modoActual === 'editar') {
        procesoModalDebug(' [EDICION] Guardando cambios del proceso');

        if (!globalThis.procesosSeleccionados) {
            globalThis.procesosSeleccionados = {};
        }

        const procesoExistente = globalThis.procesosSeleccionados[procesoModalState.procesoActual];
        const idExistente = procesoExistente?.datos?.id;
        const tipoProcesoId = procesoExistente?.datos?.tipo_proceso_id;

        if (idExistente) {
            datos.id = idExistente;
        }
        if (tipoProcesoId) {
            datos.tipo_proceso_id = tipoProcesoId;
        }

        globalThis.procesosSeleccionados[procesoModalState.procesoActual] = {
            tipo: procesoModalState.procesoActual,
            indiceResultado: globalThis.procesoActualIndex,
            datos: {
                ...datos,
                ubicaciones: [...ubicacionesClonadas]
            }
        };

        procesoModalDebug(' [EDICION] Datos actualizados en globalThis.procesosSeleccionados:', {
            tipo: procesoModalState.procesoActual,
            id: datos.id,
            ubicaciones: datos.ubicaciones?.length || 0,
            imagenes: datos.imagenes?.length || 0,
            tallas: datos.tallas
        });

        if (globalThis.procesosEditor) {
            globalThis.procesosEditor.registrarCambioUbicaciones(datos.ubicaciones);
            globalThis.procesosEditor.registrarCambioImagenes(datos.imagenes);
            globalThis.procesosEditor.registrarCambioObservaciones(datos.observaciones);
            globalThis.procesosEditor.registrarCambioTallas(datos.tallas);
        }

        procesoModalState.cambiosProceso = datos;
    }
}
procesoModalModules.persistencia.persistirDatosProceso = _procesoGenerico_persistirDatosProceso;

function _procesoGenerico_postGuardar(modoAntesDeCerrar) {
    globalThis.imagenesEliminadasProcesoStorage = [];
    procesoModalDebug('[agregarProcesoAlPedido]  Storage de eliminadas reseteado despues de guardar');

    cerrarModalProcesoGenerico(true);

    if (globalThis.renderizarTarjetasProcesos) {
        setTimeout(() => {
            procesoModalDebug(` [agregarProcesoAlPedido] Renderizando tarjetas (modo: ${modoAntesDeCerrar})...`);
            globalThis.renderizarTarjetasProcesos();

            setTimeout(() => {
                const container = document.getElementById('contenedor-tarjetas-procesos');
                if (container) {
                    const tarjetas = container.querySelectorAll('[data-tipo-proceso]');
                    procesoModalDebug(` [agregarProcesoAlPedido-VERIFY] Tarjetas renderizadas: ${tarjetas.length}`);
                    if (tarjetas.length === 0) {
                        console.warn(' [agregarProcesoAlPedido-VERIFY]  NO se encontraron tarjetas. Re-renderizando...');
                        globalThis.renderizarTarjetasProcesos();
                    }
                }
            }, 100);
        }, 50);
    }

    if (globalThis.actualizarResumenProcesos) {
        globalThis.actualizarResumenProcesos();
    }
}
procesoModalModules.persistencia.postGuardar = _procesoGenerico_postGuardar;

globalThis.agregarProcesoAlPedido = function() {
    if (!procesoModalState.procesoActual) {
        alert('Error: no hay proceso seleccionado');
        return;
    }

    try {
        const { imagenesValidas } = procesoModalModules.persistencia.obtenerImagenesParaGuardar();
        const { datos, ubicacionesClonadas } = procesoModalModules.persistencia.construirDatosProceso(imagenesValidas);

        procesoModalModules.persistencia.persistirDatosProceso(datos, ubicacionesClonadas);

        const modoAntesDeCerrar = procesoModalState.modoActual;
        procesoModalDebug('[agregarProcesoAlPedido] Modo capturado antes de cerrar:', modoAntesDeCerrar);

        procesoModalModules.persistencia.postGuardar(modoAntesDeCerrar);
    } catch (error) {
        console.error('[agregarProcesoAlPedido] Error:', error);
    }
};

// NUEVO: funcion para aplicar cambios del buffer cuando se hace GUARDAR CAMBIOS de la prenda
// Esta funcion es llamada ANTES de hacer el PATCH final
globalThis.aplicarCambiosProcesosDesdeBuffer = function() {
    if (procesoModalState.cambiosProceso) {
        procesoModalDebug('[APLICAR-BUFFER] Aplicando cambios del proceso al procesosSeleccionados:', procesoModalState.cambiosProceso);
        
        // Si no existe, crear
        if (!globalThis.procesosSeleccionados) {
            globalThis.procesosSeleccionados = {};
        }
        
        // Crear o actualizar el proceso con los cambios del buffer
        globalThis.procesosSeleccionados[procesoModalState.cambiosProceso.tipo] = {
            tipo: procesoModalState.cambiosProceso.tipo,
            datos: procesoModalState.cambiosProceso
        };
        
        procesoModalDebug('[APLICAR-BUFFER]  Cambios aplicados a procesosSeleccionados');
        
        // Limpiar buffer
        procesoModalState.cambiosProceso = null;
    }
};

// NUEVO: funcion para obtener el estado actual del buffer (para debugging/validación)
globalThis.obtenerBufferProcesoActual = function() {
    return procesoModalState.cambiosProceso;
};

// NUEVO: funcion para obtener el modo actual (para debugging)
globalThis.obtenerModoActual = function() {
    return procesoModalState.modoActual;
};

// Confirmar que el módulo se cargó correctamente
