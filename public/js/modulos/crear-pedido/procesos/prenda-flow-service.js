/**
 * Servicio de flujo de prenda (crear/editar) extraído desde GestionItemsUI.
 */
class PrendaFlowService {
    constructor(options = {}) {
        this.ui = options.ui || null;
    }

    _ctx(key) {
        return this.ui?._ctx?.(key);
    }

    async agregarPrendaNueva() {
        try {
            this._logEstadoInicial();
            this._ensureNotificationService();

            const prendaData = await this._recolectarYHidratarDatos();
            if (!prendaData) return;

            if (!this._validarDatosFormulario(prendaData)) return;

            await this._procesarTypoManga(prendaData);

            const { enPedidoExistente, pedidoId, esNuevaDesdeCotz, vamosAEditar } =
                this._determinarModosYPedido();

            if (vamosAEditar) {
                await this._procesarModoEdicion(prendaData, esNuevaDesdeCotz, enPedidoExistente, pedidoId);
            } else {
                await this._procesarModoCreacion(prendaData, enPedidoExistente, pedidoId);
            }

            this._finalizarYRenderizar();
        } catch (error) {
            this.ui?.notificationService?.error('Error al agregar prenda: ' + error.message);
        }
    }

    _logEstadoInicial() {
        debugLog('\n\n═══════════════════════════════════════════════════════════════');
        debugLog(' [agregarPrendaNueva]  CLICK EN "GUARDAR CAMBIOS" ← PUNTO DE INICIO');
        debugLog('═══════════════════════════════════════════════════════════════');

        const estadoImagenesPrenda = this.ui?._obtenerImagenesPrendaStorage?.() || [];
        const estadoTelasCreacion = this._ctx('telasCreacion') || [];
        const estadoProcesos = this._ctx('procesosSeleccionados') || {};

        debugLog('[agregarPrendaNueva] ESTADO INICIAL DE STORAGES:');
        debugLog('[agregarPrendaNueva]   imagenesPrendaStorage:', estadoImagenesPrenda.length, 'imagenes');
        debugLog('[agregarPrendaNueva]   globalThis.telasCreacion:', estadoTelasCreacion.length, 'telas');
        debugLog('[agregarPrendaNueva]    procesosSeleccionados types:', Object.keys(estadoProcesos));
        debugLog('═══════════════════════════════════════════════════════════════\n');

        debugLog('[agregarPrendaNueva]  INICIO - Estado actual:');
        debugLog('[agregarPrendaNueva]   - this.prendaEditIndex:', this.ui?.prendaEditIndex);
        debugLog('[agregarPrendaNueva]   - this.prendas.length:', this.ui?.prendas?.length || 0);
        debugLog('[agregarPrendaNueva]   - ¿Es edición?:', this.ui?.prendaEditIndex !== null && this.ui?.prendaEditIndex !== undefined);
    }

    _ensureNotificationService() {
        if (this.ui?.notificationService) return;
        console.warn('[GestionItemsUI]  notificationService no disponible, usando servicio alterno temporal');
        if (typeof NotificationService === 'undefined') {
            logDeprecatedFallbackOnce(
                'notification-service-inline-fallback',
                'Se está usando NotificationService inline fallback. Migra a inyección explícita de notificationService.'
            );
            this.ui.notificationService = {
                success: (msg) => debugLog('', msg),
                error: (msg) => console.error('', msg),
                warning: (msg) => console.warn('', msg)
            };
        } else {
            logDeprecatedFallbackOnce(
                'notification-service-late-instantiation',
                'Se está instanciando NotificationService en fallback tardío. Migra a inicialización/inyección en constructor.'
            );
            this.ui.notificationService = new NotificationService();
        }
    }

    async _recolectarYHidratarDatos() {
        const prendaFormCollector = this._ctx('prendaFormCollector');
        prendaFormCollector?.setNotificationService(this.ui?.notificationService);
        const prendaData = prendaFormCollector?.construirPrendaDesdeFormulario(
            this.ui?.prendaEditIndex,
            this.ui?.prendas
        );

        if (!prendaData) {
            this.ui?.notificationService?.error('Por favor completa los datos de la prenda');
            return null;
        }

        this._hidratarAsignacionesConArchivos(prendaData);
        this._agregarImagenesAEliminar(prendaData);
        this._logDatosRecopilados(prendaData);

        return prendaData;
    }

    _hidratarAsignacionesConArchivos(prendaData) {
        if (prendaData?.asignacionesColoresPorTalla && typeof prendaData.asignacionesColoresPorTalla === 'object') {
            const coloresPorTalla = this._ctx('ColoresPorTalla');
            const getImageWizard = (coloresPorTalla && typeof coloresPorTalla.getImage === 'function')
                ? coloresPorTalla.getImage.bind(coloresPorTalla)
                : null;

            let conImagenId = 0;
            let conImagenFile = 0;
            const resultado = {};

            Object.entries(prendaData.asignacionesColoresPorTalla).forEach(([clave, asignacion]) => {
                const copiaAsignacion = { ...asignacion, colores: [] };
                const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];

                copiaAsignacion.colores = colores.map(color => {
                    const colorCopia = { ...color };
                    if (colorCopia.imagen_id) conImagenId++;

                    if (colorCopia?.imagen?.file instanceof File) {
                        conImagenFile++;
                        return colorCopia;
                    }

                    if (getImageWizard && colorCopia.imagen_id) {
                        const imagenWizard = getImageWizard(colorCopia.imagen_id);
                        if (imagenWizard?.file instanceof File) {
                            colorCopia.imagen = {
                                file: imagenWizard.file,
                                nombre: imagenWizard.nombre || imagenWizard.file.name || '',
                                blobUrl: imagenWizard.blobUrl || null
                            };
                            conImagenFile++;
                        }
                    }
                    return colorCopia;
                });
                resultado[clave] = copiaAsignacion;
            });

            debugLog('[agregarPrendaNueva]  Hidratar asignaciones:', { grupos: Object.keys(resultado).length, conImagenId, conImagenFile });
            prendaData.asignacionesColoresPorTalla = resultado;
            prendaData.asignacionesColores = resultado;
        }
    }

    _agregarImagenesAEliminar(prendaData) {
        const imagenesAEliminar = this._ctx('imagenesAEliminar');
        if (imagenesAEliminar && imagenesAEliminar.length > 0) {
            prendaData.imagenes_a_eliminar = imagenesAEliminar;
            debugLog('[agregarPrendaNueva]  Imágenes marcadas para eliminación:', {
                cantidad: imagenesAEliminar.length,
                ids: imagenesAEliminar
            });
        }
    }

    _logDatosRecopilados(prendaData) {
        debugLog('\n═══════════════════════════════════════════════════════════════');
        debugLog('[agregarPrendaNueva]  DATOS RECOPILADOS POR prendaFormCollector:');
        debugLog('[agregarPrendaNueva]    prendaData.imagenes:', prendaData?.imagenes?.length || 0);
        debugLog('[agregarPrendaNueva]    prendaData.telasAgregadas:', prendaData?.telasAgregadas?.length || 0);
        debugLog('[agregarPrendaNueva]    prendaData.procesos types:', Object.keys(prendaData?.procesos || {}));
        debugLog('═══════════════════════════════════════════════════════════════\n');
    }

    _validarDatosFormulario(prendaData) {
        const tieneTallas = prendaData.cantidad_talla &&
            Object.values(prendaData.cantidad_talla).some(genero => Object.keys(genero).length > 0);

        const cantidadSoloSeleccionada = this._ctx('cantidadSoloSeleccionada');
        const tieneSoloCantidad = cantidadSoloSeleccionada && cantidadSoloSeleccionada > 0;

        debugLog('[gestion-items-pedido]  Validación de tallas:', {
            tieneTallas,
            tieneSoloCantidad
        });

        if (!tieneTallas && !tieneSoloCantidad) {
            this.ui?.notificationService?.advertencia('Por favor selecciona al menos una talla o utiliza la opción "UNISEX"');
            debugLog('[gestion-items-pedido]  Validación FALLIDA: No hay tallas ni cantidad');
            return false;
        }

        if (tieneSoloCantidad) {
            prendaData.cantidad_solo = cantidadSoloSeleccionada;
            debugLog('[gestion-items-pedido] Cantidad sin talla agregada:', cantidadSoloSeleccionada);
        }

        debugLog('[gestion-items-pedido]  Validación EXITOSA: Hay tallas o cantidad, procediendo a guardar');
        return true;
    }

    async _procesarTypoManga(prendaData) {
        if (!prendaData.variantes?.tipo_manga_crear || !prendaData.variantes?.tipo_manga) return;

        debugLog('[gestion-items-pedido]  Creando tipo de manga:', prendaData.variantes.tipo_manga);

        try {
            const response = await fetch('/api/asesores/tipos-manga', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ nombre: prendaData.variantes.tipo_manga })
            });

            const result = await response.json();

            if (result.success && result.data) {
                prendaData.variantes.tipo_manga_id = result.data.id;
                const datalist = document.getElementById('opciones-manga');
                if (datalist) {
                    const newOption = document.createElement('option');
                    newOption.value = result.data.nombre;
                    newOption.dataset.id = result.data.id;
                    datalist.appendChild(newOption);
                }
                debugLog('[gestion-items-pedido] Tipo de manga creado:', { id: result.data.id, nombre: result.data.nombre });
                delete prendaData.variantes.tipo_manga_crear;
            } else {
                console.warn('[gestion-items-pedido]  No se pudo crear tipo de manga:', result);
                this.ui?.notificationService?.advertencia('No se pudo crear el tipo de manga, se guardará solo el nombre');
            }
        } catch (error) {
            console.error('[gestion-items-pedido]  Error creando tipo de manga:', error);
            this.ui?.notificationService?.advertencia('Error al crear tipo de manga, se guardará solo el nombre');
        }
    }

    _determinarModosYPedido() {
        const enPedidoExistente = this.ui?._tienePedidoEdicion?.();
        const pedidoId = enPedidoExistente ? this.ui?._obtenerPedidoEdicionId?.() : null;
        const esNuevaDesdeCotz = this.ui?.prendaEditor?.esNuevaPrendaDesdeCotizacion === true;
        const esEdicionReal = this.ui?.prendaEditIndex !== null && this.ui?.prendaEditIndex !== undefined;
        const vamosAEditar = esEdicionReal && !esNuevaDesdeCotz;

        debugLog('[guardarPrenda]  DETECCIÓN CRÍTICA:', {
            esNuevaDesdeCotz,
            esEdicionReal,
            prendaEditIndex: this.ui?.prendaEditIndex,
            vamosAEditar
        });
        debugLog('[guardarPrenda]  ACCIÓN A EJECUTAR:', vamosAEditar ? 'EDITAR' : 'AGREGAR NUEVA');

        return { enPedidoExistente, pedidoId, esNuevaDesdeCotz, vamosAEditar };
    }

    async _procesarModoEdicion(prendaData, esNuevaDesdeCotz, enPedidoExistente, pedidoId) {
        if (enPedidoExistente) {
            await this._procesarEditacionConPedido(prendaData, pedidoId);
        } else {
            this._procesarEditacionEnMemoria(prendaData);
        }
    }

    async _procesarEditacionConPedido(prendaData, pedidoId) {
        const prendaOriginal = this._ctx('prendaEnEdicion')?.prendaOriginal;
        prendaData.prenda_pedido_id = prendaOriginal?.prenda_pedido_id || prendaOriginal?.id;

        const telasFuente = this.ui?._obtenerTelasFuente?.() || [];
        if (telasFuente.length > 0) {
            prendaData.telasAgregadas = telasFuente.map(tela => ({
                id: tela.id || tela._original_id || tela.prenda_pedido_colores_telas_id || null,
                _original_id: tela._original_id || tela.id || null,
                prenda_pedido_colores_telas_id: tela.prenda_pedido_colores_telas_id || tela.id || tela._original_id || null,
                tela: tela.nombre_tela || tela.tela || '',
                color: tela.color || tela.color_nombre || '',
                referencia: tela.referencia || '',
                observaciones: tela.observaciones || '',
                color_id: tela.color_id || 0,
                tela_id: tela.tela_id || 0,
                imagenes: []
            }));
        }

        await this._ctx('modalNovedadEditacion').mostrarModalYActualizar(pedidoId, prendaData, this.ui?.prendaEditIndex);
    }

    _procesarEditacionEnMemoria(prendaData) {
        debugLog('[guardarPrenda]  MODO CREACIÓN: Actualizando prenda en memoria');

        if (!this.ui?.prendas?.[this.ui.prendaEditIndex]) {
            console.error('[guardarPrenda]  ERROR: No existe prenda en index', this.ui?.prendaEditIndex);
            return;
        }

        const esModoCreate = !this.ui?._tienePedidoEdicion?.();
        const imagenesStorage = this.ui?._obtenerImagenesPrendaStorage?.() || [];

        if (esModoCreate && imagenesStorage.length === 0) {
            prendaData.imagenes = [];
            this.ui.prendas[this.ui.prendaEditIndex].imagenes = [];
        }

        const prendaAnterior = structuredClone(this.ui.prendas[this.ui.prendaEditIndex]);
        this.ui.prendas[this.ui.prendaEditIndex] = { ...this.ui.prendas[this.ui.prendaEditIndex], ...prendaData };

        const procesosGuardados = this.ui.prendas[this.ui.prendaEditIndex].procesos || {};
        debugLog('[_procesarEditacionEnMemoria] 📦 ESTRUCTURA GUARDADA:');
        Object.entries(procesosGuardados).forEach(([tipo, proc]) => {
            debugLog(`  ${tipo}:`, {
                'tiene.datos': !!proc?.datos,
                'tiene.ubicaciones (aquí)': !!proc?.ubicaciones,
                'tiene.ubicaciones (en datos)': !!proc?.datos?.ubicaciones,
                'ubicaciones_count': proc?.datos?.ubicaciones?.length || proc?.ubicaciones?.length || 0,
                'tallas_count': Object.keys(proc?.datos?.tallas || proc?.tallas || {}).length
            });
        });

        debugLog('[guardarPrenda] PRENDA ACTUALIZADA:', {
            'Nombre ANTES': prendaAnterior.nombre_prenda,
            'Nombre DESPUES': this.ui.prendas[this.ui.prendaEditIndex].nombre_prenda
        });

        // FIX DUPLICADOS: No re-renderizar TODO, solo actualizar la tarjeta específica
        // Para evitar duplicados al editar, solo re-renderizamos la tarjeta editada
        if (typeof globalThis.reRenderizarTarjetaPrendaEditada === 'function') {
            globalThis.reRenderizarTarjetaPrendaEditada(this.ui.prendaEditIndex);
        } else {
            // Fallback si la función no está disponible
            this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
        }
        
        this.ui?.notificationService?.exito('Prenda actualizada correctamente');
        this.ui?.cerrarModalAgregarPrendaNueva?.();
    }

    async _procesarModoCreacion(prendaData, enPedidoExistente, pedidoId) {
        if (enPedidoExistente) {
            await this._ctx('modalNovedadPrenda').mostrarModalYGuardar(pedidoId, prendaData);
        } else {
            this._agregarPrendaNuevaEnMemoria(prendaData);
        }
    }

    _agregarPrendaNuevaEnMemoria(prendaData) {
        this.ui?.notificationService?.exito('Prenda agregada correctamente');

        if (prendaData.tipo === 'prenda_nueva') {
            prendaData.tipo = 'prenda';
        }

        debugLog('[gestionItemsUI]  Prenda normalizada antes de agregar:', {
            tipo: prendaData.tipo,
            nombre_prenda: prendaData.nombre_prenda,
            cantidad_talla: prendaData.cantidad_talla,
            telasAgregadas: prendaData.telasAgregadas?.length || 0
        });

        this.ui?.agregarPrendaAlOrden?.(prendaData);
    }

    _finalizarYRenderizar() {
        this.ui?.cerrarModalAgregarPrendaNueva?.();

        debugLog('[gestionItemsUI]  PUNTO CRÍTICO: Después de agregar prenda');
        debugLog('[gestionItemsUI]  this.prendas:', this.ui?.prendas?.length || 0);
        debugLog('[gestionItemsUI]  this.epps:', this.ui?.epps?.length || 0);
        debugLog('[gestionItemsUI]  this.ordenItems:', JSON.stringify(this.ui?.ordenItems || []));

        if (this.ui?.renderer) {
            const itemsOrdenados = this.ui.obtenerItemsOrdenados();
            debugLog('[gestionItemsUI]  Llamando renderer.actualizar() con', itemsOrdenados.length, 'items');
        }
        this.ui?._actualizarRenderItemsOrdenadosSinBloquear?.();
        this.ui?._sincronizarPrendasEnDatosEdicion?.();
    }
}

