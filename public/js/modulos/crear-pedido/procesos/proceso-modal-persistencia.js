/**
 * proceso-modal-persistencia.js
 * Extraccion de persistencia/guardado del modal de procesos.
 */
(function initProcesoModalPersistencia(global) {
    const procesoModalModules = global.procesoModalModules || (global.procesoModalModules = { ui: {}, imagenes: {}, persistencia: {}, tallas: {} });
    const procesoModalState = global.procesoModalState || (global.procesoModalState = { procesoActual: null, modoActual: 'crear', cambiosProceso: null });
    const procesoModalDebug = global.procesoModalDebug || function() {};

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

    // En edicion, obtener ID y tipo_proceso_id del proceso existente
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
        procesoModalDebug('[agregarProcesoAlPedido]  WARNING: No se encontro ID del proceso. Modo:', procesoModalState.modoActual);
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

// NUEVO: funcion para obtener el estado actual del buffer (para debugging/validacion)
globalThis.obtenerBufferProcesoActual = function() {
    return procesoModalState.cambiosProceso;
};

// NUEVO: funcion para obtener el modo actual (para debugging)
globalThis.obtenerModoActual = function() {
    return procesoModalState.modoActual;
};

})(globalThis);

