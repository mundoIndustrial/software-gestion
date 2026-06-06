const PorTallasModulos = {
    estado: {
        recolectarDatosModoGeneral: _recolectarDatosModoGeneral,
        recolectarDatosModoEspecifico: _recolectarDatosModoEspecifico,
        limpiarEstadoModal: _limpiarEstadoModalPorTallas
    },
    ui: {
        mostrarContenedoresPorModo: _mostrarContenedoresPorModo,
        mostrarModal: _mostrarModalProcesoPorTallas,
        configurarHeader: _configurarHeaderModalPorTallas,
        mostrarEstadoSinTallas: _mostrarEstadoSinTallas
    },
    render: {
        renderizarGaleriaGeneral: renderizarGaleriaFotosGenerales,
        renderizarGaleriaTalla: renderizarGaleriaFotos
    },
    eventos: {
        registrarPasteModal: registrarListenerPasteEnModal,
        desregistrarPasteModal: desregistrarListenerPasteDelModal,
        configurarDragDropGeneral: configurarDragDropPasteGeneral,
        configurarDragDropTalla: configurarDragDropPasteTalla
    },
    persistencia: {
        construirTallasYDatosExtendidos: _construirTallasYDatosExtendidosDesdeTemp,
        asegurarProcesoSeleccionado: _asegurarProcesoSeleccionadoActual,
        construirDatosProceso: _construirDatosProcesoParaGuardar
    }
};
/**
 * Cambiar el modo de configuracion del modal (General <-> Especifico)
 */
/**
 * Abre el modal de proceso por tallas
 */
function abrirModalProcesoPorTallas(tipoProceso) {
    procesoPorTallasActual = tipoProceso;
    PorTallasModulos.estado.limpiarEstadoModal({ resetModo: true, preserveProcesoActual: true });
    _inicializarEventosModalPorTallas();
    
    const inputUbicacion = document.getElementById('ubicacion-general-input');
    if (inputUbicacion) inputUbicacion.value = '';

    const modal = document.getElementById('modal-proceso-por-tallas');
    if (!modal) {
        console.error('[por-tallas] Modal no encontrado');
        return;
    }
    PorTallasModulos.ui.configurarHeader(tipoProceso);

    const tallasPrenda = _cargarTallasDesdeFuentes(tipoProceso);
    const tallasDama = Object.entries(tallasPrenda.dama || {});
    const tallasCaballero = Object.entries(tallasPrenda.caballero || {});
    const tallassobremedida = Object.entries(tallasPrenda.sobremedida || {});

    if (tallasDama.length === 0 && tallasCaballero.length === 0 && tallassobremedida.length === 0) {
        PorTallasModulos.ui.mostrarEstadoSinTallas(modal);
        return;
    }

    const sinTallas = document.getElementById('sin-tallas-por-tallas');
    if (sinTallas) sinTallas.style.display = 'none';

    const datosExistentes = globalThis.procesosSeleccionados?.[tipoProceso]?.datos?.datosExtendidos;
    const datosGenerales = globalThis.procesosSeleccionados?.[tipoProceso]?.datos;

    _limpiarContenedores();
    _restaurarUbicacionGeneral(datosGenerales);
    _restaurarFotosGenerales(datosGenerales);
    PorTallasModulos.render.renderizarGaleriaGeneral();

    const secDama = document.getElementById('seccion-dama-por-tallas');
    const secCab = document.getElementById('seccion-caballero-por-tallas');
    const secSobremedida = document.getElementById('seccion-sobremedida-por-tallas');
    const contDama = document.getElementById('tallas-dama-por-tallas');
    const contCab = document.getElementById('tallas-caballero-por-tallas');
    const contSobremedida = document.getElementById('tallas-sobremedida-por-tallas');
    const contDamaGeneral = document.getElementById('tallas-dama-modo-general');
    const secDamaGeneral = document.getElementById('seccion-dama-modo-general');
    const contCabGeneral = document.getElementById('tallas-caballero-modo-general');
    const secCabGeneral = document.getElementById('seccion-caballero-modo-general');
    const contSobremedidaGeneral = document.getElementById('tallas-sobremedida-modo-general');
    const secSobremedidaGeneral = document.getElementById('seccion-sobremedida-modo-general');

    _renderizarTallasPorGenero('dama', tallasDama, datosExistentes, contDama, secDama, contDamaGeneral, secDamaGeneral);
    _renderizarTallasPorGenero('caballero', tallasCaballero, datosExistentes, contCab, secCab, contCabGeneral, secCabGeneral);
    _renderizarTallasPorGenero('sobremedida', tallassobremedida, datosExistentes, contSobremedida, secSobremedida, contSobremedidaGeneral, secSobremedidaGeneral);

    const modoFinal = _determinarModoFinal(tipoProceso);
    if (modoFinal === 'especifico') {
        cambiarModoModalPorTallas('especifico');
    } else {
        cambiarModoModalPorTallas('general');
    }

    PorTallasModulos.ui.mostrarModal(modal, true);
    _log('[abrirModalProcesoPorTallas]  Modal abierto, listener global de paste activo');
};
function _obtenerImagenesValidasSegunModo(datos) {
    if (modoModalPorTallasActual === 'general') return [];

    return (datos.imagenes || []).filter((img) => {
        if (typeof img === 'string') {
            if (modoModalPorTallasActual === 'especifico') {
                return true;
            }
            return !img.startsWith('blob:') && !img.startsWith('data:');
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta_webp || img.ruta_original || img.ruta || '';
            return typeof url === 'string' && !url.startsWith('blob:') && !url.startsWith('data:');
        }
        return false;
    });
}

function _construirTallasYDatosExtendidosDesdeTemp() {
    const tallas = { dama: {}, caballero: {}, sobremedida: {} };
    const datosExtendidos = { dama: {}, caballero: {}, sobremedida: {} };

    Object.entries(datosPorTallaTemp).forEach(([key, datos]) => {
        if (!datos.seleccionada) return;

        const sepIdx = key.indexOf('__');
        const genero = key.substring(0, sepIdx);
        const tallaKey = key.substring(sepIdx + 2);

        tallas[genero][tallaKey] = datos.cantidadSeleccionada;

        _log('[GUARDAR-POR-TALLAS] Guardando datosExtendidos para:', key, {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: datos.ubicaciones,
            observaciones: datos.observaciones,
            imagenesCount: datos.imagenes?.length,
            imagenesFilesCount: datos.imagenesFiles?.length,
            imagenesSample: datos.imagenes?.slice(0, 2).map((img) => typeof img === 'string' ? img.substring(0, 60) : typeof img),
            imagenesFilesSample: datos.imagenesFiles?.slice(0, 2).map((file) => file instanceof File ? `File: ${file.name}` : typeof file)
        });

        datosExtendidos[genero][tallaKey] = {
            cantidadSeleccionada: datos.cantidadSeleccionada,
            ubicaciones: modoModalPorTallasActual === 'general' ? [] : (datos.ubicaciones || []),
            observaciones: datos.observaciones || '',
            imagenes: _obtenerImagenesValidasSegunModo(datos),
            imagenesFiles: modoModalPorTallasActual === 'general' ? [] : (datos.imagenesFiles || [])
        };
    });

    return { tallas, datosExtendidos };
}

function _asegurarProcesoSeleccionadoActual() {
    if (!globalThis.procesosSeleccionados) {
        globalThis.procesosSeleccionados = {};
    }

    if (!globalThis.procesosSeleccionados[procesoPorTallasActual]) {
        globalThis.procesosSeleccionados[procesoPorTallasActual] = {
            tipo: procesoPorTallasActual,
            datos: null
        };
    }
}

function _construirDatosProcesoParaGuardar(tallas, datosExtendidos) {
    const tallasCanonicas = globalThis.ProcesoTallasCanonicas
        ? globalThis.ProcesoTallasCanonicas.desdeAgrupadas(tallas)
        : [];

    return {
        tipo: procesoPorTallasActual,
        ubicaciones: modoModalPorTallasActual === 'general' ? [ubicacionGeneralTemp] : [],
        observaciones: '',
        tallas: tallas,
        tallasCanonicas: tallasCanonicas,
        //  FIX: Copiar File objects de imagenesFiles a imagenes para que FormData los encuentre
        imagenes: modoModalPorTallasActual === 'general' ? fotosGeneralesFilesTemp : [],
        imagenesFiles: fotosGeneralesFilesTemp,
        fotosGeneralesFiles: fotosGeneralesFilesTemp,
        imagenes_existentes: fotosGeneralesExistentes,
        imagenes_a_eliminar: fotosGeneralesEliminadas,
        imagenesEliminadas: [],
        datosExtendidos: datosExtendidos,
        modo_tallas: modoModalPorTallasActual,
        ubicacionGeneral: modoModalPorTallasActual === 'general' ? ubicacionGeneralTemp : '',
        fotosGenerales: modoModalPorTallasActual === 'general' ? [...fotosGeneralesExistentes] : [],
        imagenes_por_talla: construirImagenesPorTalla(datosExtendidos)
    };
}

/**
 * Guardar proceso con datos por talla
 */
function guardarProcesoPorTallas() {
    if (!procesoPorTallasActual) return;

    _log('[GUARDAR-POR-TALLAS] Iniciando guardado del proceso:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual);
    _log('[GUARDAR-POR-TALLAS] datosPorTallaTemp ANTES de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    // ─── RECOGER DATOS SEGÚN EL MODO ACTUAL ───
    if (modoModalPorTallasActual === 'general') {
        PorTallasModulos.estado.recolectarDatosModoGeneral();
    } else {
        PorTallasModulos.estado.recolectarDatosModoEspecifico();
    }

    _log('[GUARDAR-POR-TALLAS] datosPorTallaTemp DESPUÉS de recolectar:', JSON.stringify(datosPorTallaTemp, null, 2));

    const { tallas, datosExtendidos } = PorTallasModulos.persistencia.construirTallasYDatosExtendidos();

    PorTallasModulos.persistencia.asegurarProcesoSeleccionado();
    globalThis.procesosSeleccionados[procesoPorTallasActual].datos = PorTallasModulos.persistencia.construirDatosProceso(tallas, datosExtendidos);

    _log('[GUARDAR-POR-TALLAS]  DATOS GUARDADOS EN SESIÓN - modo_tallas:', modoModalPorTallasActual, {
        tallasTotales: Object.keys(tallas.dama).length + Object.keys(tallas.caballero).length + Object.keys(tallas.sobremedida).length,
        datosExtendidosTotales: Object.values(datosExtendidos).reduce((acc, g) => acc + Object.keys(g).length, 0),
        modoGuardado: modoModalPorTallasActual
    });

    _log('[por-tallas] Proceso guardado:', procesoPorTallasActual, 'Modo:', modoModalPorTallasActual, {
        tipo: procesoPorTallasActual,
        fotosExistentes: fotosGeneralesExistentes.length,
        fotosNuevas: fotosGeneralesTemp.length,
        fotosEliminadas: fotosGeneralesEliminadas.length,
        archivosNuevos: fotosGeneralesFilesTemp.length,
        proceso: globalThis.procesosSeleccionados[procesoPorTallasActual]
    });

    // Guardar en procesosGuardados si existe
    if (globalThis.procesosGuardados) {
        globalThis.procesosGuardados[procesoPorTallasActual] = { ...globalThis.procesosSeleccionados[procesoPorTallasActual] };
    }

    // Actualizar resumen visual
    if (typeof actualizarResumenProcesos === 'function') {
        actualizarResumenProcesos();
    }
    
    // Renderizar tarjeta del proceso si existe la función
    if (typeof globalThis.renderizarTarjetasProcesos === 'function') {
        globalThis.renderizarTarjetasProcesos();
    }


    // Cerrar modal
    const modal = document.getElementById('modal-proceso-por-tallas');
    PorTallasModulos.ui.mostrarModal(modal, false);

    PorTallasModulos.estado.limpiarEstadoModal();
};

/**
 * Cerrar modal sin guardar
 */
function cerrarModalProcesoPorTallas() {
    const modal = document.getElementById('modal-proceso-por-tallas');
    PorTallasModulos.ui.mostrarModal(modal, false);

    // Desmarcar checkbox si no tiene datos guardados
    if (procesoPorTallasActual) {
        const yaGuardado = globalThis.procesosSeleccionados?.[procesoPorTallasActual]?.datos;
        if (!yaGuardado) {
            const checkbox = document.getElementById(`checkbox-${procesoPorTallasActual}`);
            if (checkbox) {
                checkbox._ignorarOnclick = true;
                checkbox.checked = false;
                checkbox._ignorarOnclick = false;
            }
            delete globalThis.procesosSeleccionados[procesoPorTallasActual];
            if (typeof actualizarResumenProcesos === 'function') {
                actualizarResumenProcesos();
            }
        }
    }

    PorTallasModulos.estado.limpiarEstadoModal();
};

/**
 * Elimina una foto general (compartida)
 */
// ────────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS - eliminarFotoGeneral refactoring
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Convierte una foto (string u objeto) a estructura normalizada
 */
// API pública mínima para integración con Blade y otros módulos.
const ProcesoPorTallasController = {
    abrirModalProcesoPorTallas,
    cerrarModalProcesoPorTallas,
    guardarProcesoPorTallas,
    cambiarModoModalPorTallas,
    cargarFotosGenerales,
    cargarFotosPorTalla,
    eliminarFotoPorTalla,
    eliminarFotoGeneral
};

globalThis.ProcesoPorTallasController = ProcesoPorTallasController;
