/**
 * Adaptador de edición de procesos en contexto de pedido.
 * Extraído desde gestion-items-pedido.js para reducir acoplamiento.
 */

function _ctxGlobal(key) {
    return globalThis[key];
}

function _setCtxGlobal(key, value) {
    globalThis[key] = value;
}

/**
 * Wrapper para editarProceso que funciona en contexto de edición
 */
globalThis.editarProcesoEdicion = function(tipo) {
    // Asegurar que el modal de proceso esté encima
    const modalProceso = document.getElementById('modal-proceso-generico');
    if (modalProceso) {
        modalProceso.style.zIndex = '10000';
    }

    // Usar la función global si existe
    const editarProceso = _ctxGlobal('editarProceso');
    if (editarProceso && editarProceso !== globalThis.editarProcesoEdicion) {
        editarProceso(tipo);
        return;
    }

    // Si no existe, detectar modo y abrir el modal correcto
    const procesoData = _ctxGlobal('procesosSeleccionados')?.[tipo];
    const modoTallas = procesoData?.datos?.modo_tallas || 'generico';
    if (modoTallas === 'general' || modoTallas === 'especifico') {
        const abrirModalProcesoPorTallas = _ctxGlobal('abrirModalProcesoPorTallas');
        if (abrirModalProcesoPorTallas) {
            abrirModalProcesoPorTallas(tipo);
        }
    } else if (_ctxGlobal('ProcesoModalController')?.abrir) {
        _ctxGlobal('ProcesoModalController').abrir(tipo);
    } else if (_ctxGlobal('abrirModalProcesoGenerico')) {
        _ctxGlobal('abrirModalProcesoGenerico')(tipo);

        // Asegurar nuevamente que el z-index esté alto después de abrir
        if (modalProceso) {
            setTimeout(() => {
                modalProceso.style.zIndex = '10000';
            }, 100);
        }

        // Cargar datos en el modal
        if (procesoData?.datos) {
            cargarDatosProcesoEnModalEdicion(tipo, procesoData.datos);
        }
    }
};

/**
 * Cargar datos de un proceso en el modal para editar (compatibilidad)
 */
function _resetProcesoImagenes() {
    if (_ctxGlobal('imagenesProcesoActual')) {
        _setCtxGlobal('imagenesProcesoActual', [null, null, null]);
    }

    if (!_ctxGlobal('imagenesProcesoExistentes')) {
        _setCtxGlobal('imagenesProcesoExistentes', []);
    }
    _setCtxGlobal('imagenesProcesoExistentes', []);
}

function _renderProcesoImagenes(datos) {
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);

    imagenes.forEach((img, idx) => {
        if (!img || idx >= 3) return;

        const indice = idx + 1;
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);

        if (preview) {
            const imgUrl = img instanceof File ? URL.createObjectURL(img) : img;
            const htmlImg = IMAGEN_PROCESO_EDICION_TEMPLATE
                .replace('{{imgUrl}}', imgUrl)
                .replace('{{indice}}', indice);
            preview.innerHTML = htmlImg;
        }

        if (_ctxGlobal('imagenesProcesoActual')) {
            _ctxGlobal('imagenesProcesoActual')[idx] = img;
        }
    });
}

function _cargarProcesoUbicaciones(datos) {
    if (!datos.ubicaciones || !_ctxGlobal('ubicacionesProcesoSeleccionadas')) return;

    _ctxGlobal('ubicacionesProcesoSeleccionadas').length = 0;
    _ctxGlobal('ubicacionesProcesoSeleccionadas').push(...datos.ubicaciones);

    if (_ctxGlobal('renderizarListaUbicaciones')) {
        _ctxGlobal('renderizarListaUbicaciones')();
    }
}

function _cargarProcesoObservaciones(datos) {
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
}

function _cargarProcesoTallas(datos) {
    if ((!datos.tallas && !Array.isArray(datos.tallasCanonicas)) || !_ctxGlobal('tallasSeleccionadasProceso')) return;

    const helper = _ctxGlobal('ProcesoTallasCanonicas');
    const datosNormalizados = helper ? helper.normalizarDatosProceso(datos) : datos;
    const agrupadas = datosNormalizados.tallas || datos.tallas;

    _ctxGlobal('tallasCanonicasProceso', datosNormalizados.tallasCanonicas || []);
    _ctxGlobal('tallasSeleccionadasProceso').dama = Object.keys(agrupadas.dama || {});
    _ctxGlobal('tallasSeleccionadasProceso').caballero = Object.keys(agrupadas.caballero || {});
    _ctxGlobal('tallasSeleccionadasProceso').unisex = Object.keys(agrupadas.unisex || {});
    _ctxGlobal('tallasSeleccionadasProceso').sobremedida = Object.keys(agrupadas.sobremedida || {}).length > 0 ? { ...agrupadas.sobremedida } : null;

    if (!_ctxGlobal('tallasCantidadesProceso')) {
        _setCtxGlobal('tallasCantidadesProceso', { dama: {}, caballero: {}, unisex: {}, sobremedida: {} });
    }

    _ctxGlobal('tallasCantidadesProceso').dama = { ...(agrupadas.dama || {}) };
    _ctxGlobal('tallasCantidadesProceso').caballero = { ...(agrupadas.caballero || {}) };
    _ctxGlobal('tallasCantidadesProceso').unisex = { ...(agrupadas.unisex || {}) };
    _ctxGlobal('tallasCantidadesProceso').sobremedida = { ...(agrupadas.sobremedida || {}) };

    const actualizarResumen = _ctxGlobal('ProcesoModalController')?.tallas?.actualizarResumen || _ctxGlobal('actualizarResumenTallasProceso');
    if (typeof actualizarResumen === 'function') {
        actualizarResumen();
    }
}

function cargarDatosProcesoEnModalEdicion(tipo, datos) {
    _resetProcesoImagenes();
    _renderProcesoImagenes(datos);
    _cargarProcesoUbicaciones(datos);
    _cargarProcesoObservaciones(datos);
    _cargarProcesoTallas(datos);
}
