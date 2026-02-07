/**
 * PrendaEditorOrchestrator - FRONTEND PURO
 * 
 * Responsabilidad ÚNICA: Orquestar la interacción entre:
 * - Usuario (UI)
 * - Backend (API)
 * - Presentación (DOM)
 * 
 * NO CONTIENE lógica de negocio
 * NO CONTIENE validaciones de negocio
 * NO CONTIENE transformaciones de datos
 * 
 * TODO eso está en el Backend con DDD
 */
class PrendaEditorOrchestrator {
    constructor(opciones = {}) {
        // UI
        this.domAdapter = opciones.domAdapter || new PrendaDOMAdapter();
        this.notificationService = opciones.notificationService;

        // Comunicación
        this.api = opciones.api || new PrendaAPI();
        this.eventBus = opciones.eventBus || new PrendaEventBus();

        console.log('[PrendaEditorOrchestrator] Inicializado (Frontend puro - solo orquestación)');
    }

    /**
     * FLUJO: Abrir modal para nueva prenda o edición
     */
    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
        console.log('[Orchestrator] Abriendo modal:', { esEdicion, prendaIndex, tieneCotizacion: !!cotizacionSeleccionada });

        // Preparar modal (si existe limpieza global)
        if (window.ModalCleanup) {
            esEdicion ? window.ModalCleanup.prepararParaEditar(prendaIndex) : window.ModalCleanup.prepararParaNueva();
        }

        // Cargar tipos de manga (si existe función global)
        if (typeof window.cargarTiposMangaDisponibles === 'function') {
            window.cargarTiposMangaDisponibles();
        }

        // Abrir modal
        this.domAdapter.abrirModal();
        this.eventBus.emit(PrendaEventBus.EVENTOS.MODAL_ABIERTO);
    }

    /**
     * FLUJO: Cargar prenda existente para editar
     * 
     * Llamadas al Backend:
     * 1. GET /api/prendas/{id}
     *    → Backend retorna PRENDA COMPLETAMENTE PROCESADA
     *    → Ya con origen aplicado, datos normalizados, etc
     */
    async cargarPrendaEnModal(prendaId, prendaIndex) {
        console.log('[Orchestrator] Cargando prenda para edición:', { prendaId, prendaIndex });

        this.domAdapter.mostrar('[data-loading="prenda"]');

        try {
            // 🔴 PASO 1: Obtener prenda del BACKEND
            // El backend RETORNA todo procesado y listo para presentar
            const datosPrenda = await this.api.obtenerPrendaParaEdicion(prendaId);

            console.log('[Orchestrator] Prenda obtenida del backend:', datosPrenda);

            if (!datosPrenda) {
                this.mostrarNotificacion('Prenda no encontrada', 'error');
                return;
            }

            // 🟡 PASO 2: Validar que existe y tiene estructura mínima
            if (!datosPrenda.nombre_prenda) {
                this.mostrarNotificacion('Datos de prenda inválidos', 'error');
                return;
            }

            // 🟢 PASO 3: SOLO presentar en el formulario (sin procesar)
            this.abrirModal(true, prendaIndex);
            this.llenarFormulario(datosPrenda);
            
            // 🟢 PASO 4: Guardar referencia de qué prenda se está editando
            window.prendaActual = datosPrenda;
            window.prendaEditIndex = prendaIndex;

            this.eventBus.emit(PrendaEventBus.EVENTOS.PRENDA_CARGADA, datosPrenda);
            this.mostrarNotificacion('Prenda cargada', 'success');

        } catch (error) {
            console.error('[Orchestrator] Error cargando prenda:', error);
            this.mostrarNotificacion(`Error: ${error.message}`, 'error');
            this.eventBus.emit(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, {
                mensaje: error.message,
                contexto: 'cargarPrendaEnModal'
            });
        } finally {
            this.domAdapter.mostrar('[data-loading="prenda"]', false);
        }
    }

    /**
     * PASO INTERNO: Llenar formulario con datos ya procesados
     * @private
     */
    llenarFormulario(prenda) {
        console.log('[Orchestrator] Llenando formulario con datos del backend');

        // TODOS estos datos VIENEN YA PROCESADOS DEL BACKEND
        // El frontend NO procesa nada, solo PRESENTA

        // Campos básicos
        this.domAdapter.establecerNombrePrenda(prenda.nombre_prenda || '');
        this.domAdapter.establecerDescripcion(prenda.descripcion || '');
        this.domAdapter.establecerOrigen(prenda.origen || 'confeccion');

        // Telas (ya procesadas)
        this.llenarTelas(prenda.telasAgregadas || []);

        // Imágenes
        this.llenarImagenes(prenda.imagenes || prenda.fotos || []);

        // Variaciones (ya procesadas)
        this.llenarVariaciones(prenda.variacionesActuales || {});

        // Tallas (ya procesadas)
        this.llenarTallas(prenda.tallasRelacionales || {});

        // Procesos (ya procesados)
        this.llenarProcesos(prenda.procesosSeleccionados || {});

        // Botón
        this.establecerBotón(prendaIndex !== null);
    }

    /**
     * PASO INTERNO: Llenar telas
     * @private
     */
    llenarTelas(telasAgregadas) {
        // Las telas VIENEN YA PROCESADAS del backend
        // Solo presentarlas
        
        this.domAdapter.limpiarInputsTela();
        window.telasAgregadas = telasAgregadas;

        if (window.actualizarTablaTelas) {
            window.actualizarTablaTelas();
        }

        if (window.actualizarPreviewTela) {
            window.actualizarPreviewTela();
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.TELAS_CARGADAS, telasAgregadas);
    }

    /**
     * PASO INTERNO: Llenar imágenes
     * @private
     */
    llenarImagenes(imagenes) {
        if (!imagenes || imagenes.length === 0) return;

        if (!window.imagenesPrendaStorage) {
            console.warn('[Orchestrator] window.imagenesPrendaStorage no disponible');
            return;
        }

        window.imagenesPrendaStorage.limpiar();

        imagenes.forEach((img) => {
            if (img instanceof File) {
                window.imagenesPrendaStorage.agregarImagen(img);
            } else if (typeof img === 'string' || img.url) {
                window.imagenesPrendaStorage.agregarDesdeURL(img.url || img);
            }
        });

        if (window.imagenesPrendaStorage.images.length > 0) {
            const primerImg = window.imagenesPrendaStorage.images[0];
            const urlImg = primerImg.previewUrl || primerImg.url;
            this.domAdapter.establecerPreviewImagen(urlImg);
            this.domAdapter.establecerContadorImagenes(window.imagenesPrendaStorage.images.length);
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.IMAGENES_CARGADAS, imagenes);
    }

    /**
     * PASO INTERNO: Llenar variaciones
     * @private
     */
    llenarVariaciones(variaciones) {
        // VIENEN YA PROCESADAS del backend
        // El frontend solo PRESENTA los checkboxes y valores

        if (variaciones.genero_id) {
            const generoMap = { 1: 'DAMA', 2: 'CABALLERO' };
            const genero = generoMap[variaciones.genero_id];
            if (genero) this.domAdapter.marcarGenero(genero, true);
        }

        // Manga
        if (variaciones.tipo_manga) {
            this.domAdapter.marcarVariacion('manga', true);
            this.domAdapter.establecerVariacionInput('manga', variaciones.tipo_manga);
            if (variaciones.obs_manga) {
                this.domAdapter.establecerVariacionObs('manga', variaciones.obs_manga);
            }
        }

        // Bolsillos
        if (variaciones.obs_bolsillos) {
            this.domAdapter.marcarVariacion('bolsillos', true);
            this.domAdapter.establecerVariacionObs('bolsillos', variaciones.obs_bolsillos);
        }

        // Broche
        if (variaciones.tipo_broche) {
            this.domAdapter.marcarVariacion('broche', true);
            this.domAdapter.establecerVariacionInput('broche', variaciones.tipo_broche);
            if (variaciones.obs_broche) {
                this.domAdapter.establecerVariacionObs('broche', variaciones.obs_broche);
            }
        }

        // Reflectivo
        if (variaciones.tiene_reflectivo || variaciones.obs_reflectivo) {
            this.domAdapter.marcarVariacion('reflectivo', true);
            if (variaciones.obs_reflectivo) {
                this.domAdapter.establecerVariacionObs('reflectivo', variaciones.obs_reflectivo);
            }
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.VARIACIONES_CARGADAS, variaciones);
    }

    /**
     * PASO INTERNO: Llenar tallas
     * @private
     */
    llenarTallas(tallasRelacionales) {
        // VIENEN YA FORMATEADAS desde el backend
        // Estructura: { DAMA: {S: 10, M: 20}, CABALLERO: {} }

        window.tallasRelacionales = tallasRelacionales;

        if (window.mostrarTallasDisponibles) {
            window.mostrarTallasDisponibles();
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.TALLAS_CARGADAS, tallasRelacionales);
    }

    /**
     * PASO INTERNO: Llenar procesos
     * @private
     */
    llenarProcesos(procesosSeleccionados) {
        // VIENEN NORMALIZADOS desde el backend
        // El frontend solo PRESENTA

        window.procesosSeleccionados = procesosSeleccionados;

        Object.keys(procesosSeleccionados).forEach(tipoProceso => {
            this.domAdapter.marcarProceso(tipoProceso, true);
        });

        if (window.renderizarTarjetasProcesos) {
            window.renderizarTarjetasProcesos();
        }

        this.eventBus.emit(PrendaEventBus.EVENTOS.PROCESOS_CARGADOS, procesosSeleccionados);
    }

    /**
     * PASO INTERNO: Establecer botón de guardar
     * @private
     */
    establecerBotón(esEdicion) {
        const btn = this.domAdapter.obtenerBotónGuardar();
        if (!btn) return;

        if (esEdicion) {
            this.domAdapter.establecerBotoGuardar(
                '<span class="material-symbols-rounded">save</span>Guardar Cambios',
                { editing: 'true' }
            );
        } else {
            this.domAdapter.establecerBotoGuardar(
                '<span class="material-symbols-rounded">check</span>Agregar Prenda',
                { editing: 'false' }
            );
        }
    }

    /**
     * FLUJO: Guardar prenda
     * 
     * Llamadas al Backend:
     * 1. POST /api/prendas/guardar
     *    Input: { datos del formulario }
     *    → Backend valida TODO
     *    → Backend aplica logica de negocio
     *    → Backend guarda en BD
     */
    async guardarPrenda(datosFormulario) {
        console.log('[Orchestrator] Guardando prenda:', {
            tiene_nombre: !!datosFormulario.nombre_prenda,
            tiene_origen: !!datosFormulario.origen,
            tiene_telas: (datosFormulario.telasAgregadas || []).length > 0
        });

        // 🔴 PASO 1: Validación BÁSICA de UI (solo verificar que hay datos)
        if (!datosFormulario.nombre_prenda || !datosFormulario.nombre_prenda.trim()) {
            this.mostrarNotificacion('Ingrese nombre de prenda', 'error');
            return;
        }

        if (!datosFormulario.origen) {
            this.mostrarNotificacion('Seleccione origen', 'error');
            return;
        }

        // 🟡 PASO 2: Enviar al BACKEND para validación completa y guardado
        this.domAdapter.mostrar('[data-loading="guardar"]');

        try {
            const resultado = await this.api.guardarPrenda(datosFormulario);

            // 🟢 PASO 3: Procesar respuesta del backend
            if (resultado.exito) {
                this.mostrarNotificacion(resultado.mensaje || 'Prenda guardada correctamente', 'success');
                this.resetearFormulario();
                this.eventBus.emit(PrendaEventBus.EVENTOS.PRENDA_GUARDADA, resultado);
            } else {
                // Backend retorna errores de validación de negocio
                if (Array.isArray(resultado.errores)) {
                    resultado.errores.forEach(err => {
                        this.mostrarNotificacion(err, 'error');
                    });
                } else {
                    this.mostrarNotificacion(resultado.mensaje || 'Error guardando prenda', 'error');
                }

                this.eventBus.emit(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, {
                    mensaje: resultado.mensaje,
                    errores: resultado.errores,
                    contexto: 'guardarPrenda'
                });
            }

        } catch (error) {
            console.error('[Orchestrator] Error guardando:', error);
            this.mostrarNotificacion(`Error: ${error.message}`, 'error');
            this.eventBus.emit(PrendaEventBus.EVENTOS.ERROR_OCURRIDO, {
                mensaje: error.message,
                contexto: 'guardarPrenda'
            });
        } finally {
            this.domAdapter.mostrar('[data-loading="guardar"]', false);
        }
    }

    /**
     * FLUJO: Cargar múltiples prendas desde cotización
     * 
     * Llamadas al Backend:
     * 1. GET /api/cotizaciones/{id}/prendas
     *    → Backend retorna prendas CON origen ya aplicado
     *    → Todo listo para presentar
     */
    async cargarPrendasDesdeCotizacion(cotizacionId) {
        console.log('[Orchestrator] Cargando prendas de cotización:', cotizacionId);

        try {
            // El backend retorna las prendas YA CON origen automático aplicado
            const prendas = await this.api.obtenerPrendasDesdeCotizacion(cotizacionId);

            this.eventBus.emit(PrendaEventBus.EVENTOS.COTIZACION_ASIGNADA, {
                cotizacionId,
                prendas
            });

            return prendas;
        } catch (error) {
            console.error('[Orchestrator] Error cargando prendas:', error);
            this.mostrarNotificacion(`Error: ${error.message}`, 'error');
            return [];
        }
    }

    /**
     * FLUJO: Resetear formulario
     */
    resetearFormulario() {
        this.domAdapter.limpiarCache();
        this.domAdapter.limpiarInputsTela();
        this.domAdapter.limpiarPreviewImagen();

        window.prendaActual = null;
        window.prendaEditIndex = null;

        this.domAdapter.cerrarModal();
        this.eventBus.emit(PrendaEventBus.EVENTOS.PRENDA_CERRADA);
    }

    /**
     * Mostrar notificación al usuario
     * @private
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        if (this.notificationService) {
            this.notificationService.mostrar(mensaje, tipo);
        } else {
            console.log(`[Notificación - ${tipo.toUpperCase()}] ${mensaje}`);
        }
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        this.domAdapter.cerrarModal();
        this.eventBus.emit(PrendaEventBus.EVENTOS.MODAL_CERRADO);
    }

    /**
     * Obtener estado actual (para debugging)
     */
    obtenerEstado() {
        return {
            prendaActual: window.prendaActual,
            prendaEditIndex: window.prendaEditIndex,
            telasAgregadas: window.telasAgregadas,
            procesosSeleccionados: window.procesosSeleccionados,
            tallasRelacionales: window.tallasRelacionales
        };
    }
}

window.PrendaEditorOrchestrator = PrendaEditorOrchestrator;
