/**
 * Gestión de Ítems - Capa de Presentación (Refactorizado - SOLID)
 * 
 * Esta clase orquesta los servicios especializados que manejan:
 * - ItemFormCollector: Recolecta datos de formularios
 * - PrendaEditor: Gestiona edición de prendas
 * - ItemRenderer: Renderiza UI
 * - NotificationService: Maneja notificaciones
 * - ItemAPIService: Comunica con backend
 */

class GestionItemsUI {
    prendaEditIndex = null;
    items = [];
    itemsState = null;
    notificationService = null;
    apiService = null;
    formCollector = null;
    renderer = null;
    prendaEditor = null;
    submitController = null;
    feedbackUIService = null;
    successModalService = null;
    prendaModalService = null;
    prendaFlowService = null;
    eppFlowService = null;

    constructor(options = {}) {
        // Inicializar servicios con validación de disponibilidad
        try {
            this.notificationService = options.notificationService || (typeof NotificationService === 'undefined' ? null : new NotificationService());
            this.apiService = options.apiService || (typeof ItemAPIService === 'undefined' ? null : new ItemAPIService());
            this.formCollector = options.formCollector || (typeof ItemFormCollector === 'undefined' ? null : new ItemFormCollector());
            this.itemsState = options.itemsState || (typeof PedidoItemsState === 'undefined' ? null : new PedidoItemsState());

            this.renderer = options.renderer || null;
            if (!this.renderer && typeof ItemRenderer !== 'undefined' && this.apiService) {
                this.renderer = new ItemRenderer({ apiService: this.apiService });
            }

            this.prendaEditor = options.prendaEditor || null;
            if (!this.prendaEditor && typeof PrendaEditor !== 'undefined' && this.notificationService) {
                this.prendaEditor = new PrendaEditor({ notificationService: this.notificationService });
            }
            
            // Solo inicializar si los servicios esenciales están disponibles
            if (this.formCollector && this.notificationService) {
                this.inicializar();
            }
        } catch (error) {
            console.error('[GestionItemsUI] Error inicializando servicios:', error);
            throw error;
        }
    }

    get prendas() { return this.itemsState?.prendas || []; }
    set prendas(value) { if (this.itemsState) this.itemsState.prendas = Array.isArray(value) ? value : []; }

    get epps() { return this.itemsState?.epps || []; }
    set epps(value) { if (this.itemsState) this.itemsState.epps = Array.isArray(value) ? value : []; }

    get ordenItems() { return this.itemsState?.ordenItems || []; }
    set ordenItems(value) { if (this.itemsState) this.itemsState.ordenItems = Array.isArray(value) ? value : []; }

    get prendasEliminadas() { return this.itemsState?.prendasEliminadas || []; }
    set prendasEliminadas(value) {
        if (this.itemsState) this.itemsState.prendasEliminadas = Array.isArray(value) ? value : [];
    }

    _stateCollection(tipo) {
        return this.itemsState?.getCollection(tipo) || null;
    }

    _stateAddItem(tipo, item) {
        if (!this.itemsState) return -1;
        return this.itemsState.addItem(tipo, item);
    }

    _stateRemoveItem(tipo, index) {
        if (!this.itemsState) return false;
        return this.itemsState.removeItem(tipo, index);
    }

    _ctx(key) {
        return globalThis[key];
    }

    _setCtx(key, value) {
        globalThis[key] = value;
    }

    _tienePedidoEdicion() {
        const datosEdicionPedido = this._ctx('datosEdicionPedido');
        return !!(datosEdicionPedido && (datosEdicionPedido.id || datosEdicionPedido.numero_pedido));
    }

    _obtenerPedidoEdicionId() {
        return this._ctx('datosEdicionPedido')?.id || null;
    }

    _obtenerImagenesPrendaStorage() {
        return this._ctx('imagenesPrendaStorage')?.obtenerImagenes?.() || [];
    }

    _obtenerTelasFuente() {
        const telasAgregadas = this._ctx('telasAgregadas') || [];
        const telasCreacion = this._ctx('telasCreacion') || [];
        return telasAgregadas.length > 0 ? telasAgregadas : telasCreacion;
    }

    _sincronizarPrendasEnDatosEdicion() {
        const datosEdicionPedido = this._ctx('datosEdicionPedido');
        if (datosEdicionPedido) {
            datosEdicionPedido.prendas = this.prendas;
        }
    }

    /**
     * Obtener todos los items en orden de inserción
     */
    obtenerItemsOrdenados() {
        const itemsOrdenados = this.itemsState?.obtenerItemsOrdenados() || [];
        debugLog('[gestionItemsUI]  obtenerItemsOrdenados() - this.ordenItems:', JSON.stringify(this.ordenItems));
        debugLog('[gestionItemsUI]  obtenerItemsOrdenados() - this.prendas:', this.prendas.length, 'items');
        debugLog('[gestionItemsUI]  obtenerItemsOrdenados() - this.epps:', this.epps.length, 'items');
        debugLog('[gestionItemsUI]  Total items a renderizar:', itemsOrdenados.length);
        return itemsOrdenados;
    }

    /**
     * Agregar prenda y registrar en orden
     */
    agregarPrendaAlOrden(prenda) {
        const index = this._stateAddItem('prenda', prenda);
        
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - PRENDA agregada:', prenda.nombre_prenda);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Nuevo index PRENDA:', index);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Total PRENDAS:', this.prendas.length);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Total EPPs:', this.epps.length);
    }

    /**
     * Agregar EPP y registrar en orden
     */
    agregarEPPAlOrden(epp) {
        return this._getEppFlowService().agregarEPPAlOrden(epp);
    }

    /**
     * Eliminar EPP por posición visual de tarjeta en el DOM
     * Se llama desde epp-item-manager-nuevo.js al eliminar una tarjeta EPP
     */
    eliminarEPPPorTarjetaId(tarjetaId) {
        return this._getEppFlowService().eliminarEPPPorTarjetaId(tarjetaId);
    }

    _rebuildOrdenIndices() {
        this.itemsState?.rebuildOrdenIndices?.();
    }

    /**
     * Método público para agregar EPP desde modal externo
     */
    async agregarEPPDesdeModal(eppData) {
        return this._getEppFlowService().agregarEPPDesdeModal(eppData);
    }

    inicializar() {
        this.attachEventListeners();
        
        if (document.getElementById('btn-agregar-item-cotizacion') || 
            document.getElementById('btn-agregar-item-tipo')) {
            this.cargarItems();
        }
    }

    attachEventListeners() {
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    _tieneServiciosBase() {
        return !!(this.apiService && this.renderer && this.notificationService);
    }

    async _actualizarRenderItemsOrdenados() {
        if (!this.renderer) return;
        const itemsOrdenados = this.obtenerItemsOrdenados();
        await this.renderer.actualizar(itemsOrdenados);
    }

    _actualizarRenderItemsOrdenadosSinBloquear() {
        this._actualizarRenderItemsOrdenados().catch(err => {
            console.error('[gestionItemsUI] Error renderizando:', err);
        });
    }

    _getSubmitController() {
        if (!this.submitController) {
            this.submitController = new PedidoSubmitController();
        }

        this.submitController.formCollector = this.formCollector;
        this.submitController.apiService = this.apiService;
        this.submitController.notificationService = this.notificationService;
        this.submitController.ui = {
            mostrarCargando: (mensaje) => this.mostrarCargando(mensaje),
            ocultarCargando: () => this.ocultarCargando(),
            mostrarModalExito: () => this.mostrarModalExito(),
            setDatosPedidoCreado: (datos) => {
                this.datosPedidoCreado = datos;
            }
        };

        return this.submitController;
    }

    _getFeedbackUIService() {
        if (!this.feedbackUIService) {
            this.feedbackUIService = new PedidoFeedbackUIService();
        }
        return this.feedbackUIService;
    }

    _getSuccessModalService() {
        if (!this.successModalService) {
            this.successModalService = new PedidoSuccessModalService();
        }
        return this.successModalService;
    }

    _getPrendaModalService() {
        if (!this.prendaModalService) {
            this.prendaModalService = new PrendaModalService();
        }
        return this.prendaModalService;
    }

    _getPrendaFlowService() {
        if (!this.prendaFlowService) {
            this.prendaFlowService = new PrendaFlowService({ ui: this });
        } else {
            this.prendaFlowService.ui = this;
        }
        return this.prendaFlowService;
    }

    _getEppFlowService() {
        if (!this.eppFlowService) {
            this.eppFlowService = new EppFlowService({ ui: this });
        } else {
            this.eppFlowService.ui = this;
        }
        return this.eppFlowService;
    }

    async cargarItems() {
        try {
            if (!this._tieneServiciosBase()) {
                return;
            }

            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items;

            //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
            await this._actualizarRenderItemsOrdenados();
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error al cargar ítems');
            }
            throw error;  // Re-throw para que el caller pueda tomar acción adicional
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this._tieneServiciosBase()) {

                return false;
            }
            const resultado = await this.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.items = resultado.items;
                //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
                await this._actualizarRenderItemsOrdenados();
                this.notificationService.exito('Ítem agregado correctamente');
                return true;
            }
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
            return false;
        }
    }

    async eliminarItem(index) {
        // Mostrar confirmación con SweetAlert
        const result = await Swal.fire({
            title: '¿Eliminar este ítem?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        });

        if (!result.isConfirmed) return;

        try {
            if (!this._tieneServiciosBase()) {
                return;
            }

            const itemInfo = this._getItemInfoByPosition(index);
            if (!itemInfo) {
                console.warn('[eliminarItem] Ítem no encontrado en posición:', index);
                return;
            }

            this._removeItemByInfo(itemInfo, index);

            await this._actualizarRenderItemsOrdenados();
            this.notificationService.exito('Ítem eliminado');
        } catch (error) {
            this.notificationService?.error('Error: ' + error.message);
        }
    }

    _getItemInfoByPosition(index) {
        const itemsOrdenados = this.obtenerItemsOrdenados();
        if (index < 0 || index >= itemsOrdenados.length) {
            return null;
        }

        const item = itemsOrdenados[index];
        let tipo = null;
        let indice = -1;

        if (item?.nombre_prenda) {
            tipo = 'prenda';
            indice = this.prendas.indexOf(item);
        } else if (item?.nombre_completo || item?.nombre) {
            tipo = 'epp';
            indice = this.epps.indexOf(item);
        }

        if (!tipo || indice < 0) {
            return null;
        }

        return { tipo, indice, item };
    }

    _removeItemByInfo(itemInfo, ordenIndex) {
        const { tipo, indice } = itemInfo;

        if (tipo === 'prenda') {
            const prendaAEliminar = this.prendas[indice] || {};
            const prendaIdExistente = prendaAEliminar.prenda_pedido_id || prendaAEliminar.id || null;

            if (prendaIdExistente) {
                this.prendasEliminadas.push({
                    prenda_id: Number(prendaIdExistente),
                    nombre_prenda: prendaAEliminar.nombre_prenda || prendaAEliminar.nombre_producto || 'PRENDA',
                    motivo: 'Eliminada desde edicion de borrador'
                });
                debugLog('[eliminarItem]  Prenda existente marcada para eliminar en backend:', {
                    prenda_id: prendaIdExistente,
                    total_prendas_eliminadas: this.prendasEliminadas.length
                });
            }
        }

        if (tipo === 'prenda' && indice >= 0) {
            this._stateRemoveItem('prenda', indice);
            debugLog(`[eliminarItem]  Prenda eliminada del array. Quedan: ${this.prendas.length}`);
        } else if (tipo === 'epp' && indice >= 0) {
            this._stateRemoveItem('epp', indice);
            debugLog(`[eliminarItem]  EPP eliminado del array. Quedan: ${this.epps.length}`);
        }

        this.ordenItems.splice(ordenIndex, 1);
        this._rebuildOrdenIndices();

        debugLog(`[eliminarItem]  ordenItems actualizado:`, JSON.stringify(this.ordenItems));

        if (tipo === 'prenda' && this._ctx('gestorPrendaSinCotizacion')?.eliminar) {
            this._ctx('gestorPrendaSinCotizacion').eliminar(indice);
        }
    }

    abrirModalSeleccionPrendas() {
        const abrirModalSeleccionPrendas = this._ctx('abrirModalSeleccionPrendas');
        if (abrirModalSeleccionPrendas) {
            abrirModalSeleccionPrendas();
        }
    }

    _actualizarBtnGuardarPrenda(esEdicion) {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (!btnGuardar) return;

        const spanCheck = btnGuardar.querySelector('.material-symbols-rounded');
        const textoBase = esEdicion ? 'Guardar Cambios' : 'Agregar Prenda';

        if (spanCheck) {
            btnGuardar.innerHTML = `<span class="material-symbols-rounded">check</span>${textoBase}`;
        } else {
            btnGuardar.textContent = textoBase;
        }
    }

    async _cargarCatalogosModal() {
        const cargarCatalogosModal = this._ctx('cargarCatalogosModal');
        if (typeof cargarCatalogosModal === 'function') {
            await cargarCatalogosModal();
        }
    }

    _cargarModoEdicion() {
        const prendaAEditar = this.prendas[this.prendaEditIndex];
        
        //  DIAGNOSTIC: Verificar que procesos están en this.prendas[index]
        debugLog('[_cargarModoEdicion]  SEGUNDA EDICIÓN - Verificando estado:');
        debugLog('[_cargarModoEdicion]   this.prendaEditIndex:', this.prendaEditIndex);
        debugLog('[_cargarModoEdicion]   prendaAEditar.nombre_prenda:', prendaAEditar?.nombre_prenda);
        debugLog('[_cargarModoEdicion]   prendaAEditar.procesos EXISTS:', !!prendaAEditar?.procesos);
        debugLog('[_cargarModoEdicion]   prendaAEditar.procesos TYPE:', typeof prendaAEditar?.procesos);
        debugLog('[_cargarModoEdicion]   prendaAEditar.procesos KEYS:', Object.keys(prendaAEditar?.procesos || {}));
        debugLog('[_cargarModoEdicion]   COMPLETO:', prendaAEditar?.procesos);
        
        if (prendaAEditar && this.prendaEditor) {
            this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
        }
    }

    _cargarModoCreacion() {
        debugLog('[abrirModalAgregarPrendaNueva]  RESET - Limpiando globalThis.telasCreacion para NUEVA prenda');
        debugLog('[abrirModalAgregarPrendaNueva]   ANTES:', this._ctx('telasCreacion'));

        this._setCtx('telasCreacion', []);
        this._setCtx('telasAgregadas', []);
        this._setCtx('imagenesTelaModalNueva', []);

        debugLog('[abrirModalAgregarPrendaNueva]   DESPUÉS:', this._ctx('telasCreacion'));

        const renderizarTelasChips = this._ctx('renderizarTelasChips');
        if (typeof renderizarTelasChips === 'function') {
            renderizarTelasChips();
        }

        if (this.prendaEditor) {
            this.prendaEditor.abrirModal(false, null);
        }
    }

    /**
     * Abrir modal de prenda (crear o editar)
     * 
     * Flujo: FSM guard → OPENING → catálogos → shown.bs.modal({once}) → DragDrop → OPEN
     */
    async abrirModalAgregarPrendaNueva() {
        const fsm = this._ctx('__MODAL_FSM__');
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;

        return this._getPrendaModalService().abrirModal({
            fsm,
            puedeAbrir: () => !fsm || fsm.puedeAbrir(),
            cargarCatalogos: async () => this._cargarCatalogosModal(),
            actualizarBotonGuardar: (flagEsEdicion) => this._actualizarBtnGuardarPrenda(flagEsEdicion),
            esEdicion,
            cargarModoEdicion: () => this._cargarModoEdicion(),
            cargarModoCreacion: () => this._cargarModoCreacion(),
            onModalShown: () => {
                if (this._ctx('DragDropManager')) {
                    this._ctx('DragDropManager').inicializar();
                }
            },
            onError: (error) => {
                if (typeof NotificationService !== 'undefined' && NotificationService) {
                    NotificationService.error('Error abriendo modal: ' + error.message);
                }
            }
        });
    }



    _limpiarEditorFlags() {
        if (this.prendaEditor) {
            this.prendaEditor.esNuevaPrendaDesdeCotizacion = false;
        }
    }

    _limpiarModalUI() {
        if (typeof ModalCleanup !== 'undefined') {
            ModalCleanup.limpiarDespuésDeGuardar();
            return;
        }

        this.prendaEditIndex = null;
        if (this.prendaEditor) {
            this.prendaEditor.prendaEditIndex = null;
        }
        this._setCtx('prendaEditIndex', null);

        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    _limpiarComponentes() {
        if (this.prendaEditor) {
            this.prendaEditor.resetearEdicion();
        }

        if (this._ctx('DragDropManager')) {
            this._ctx('DragDropManager').destruir();
        }
    }

    /**
     * Cerrar modal de agregar/editar prenda
     */
    cerrarModalAgregarPrendaNueva() {
        const fsm = this._ctx('__MODAL_FSM__');
        return this._getPrendaModalService().cerrarModal({
            fsm,
            limpiarEditorFlags: () => this._limpiarEditorFlags(),
            limpiarModalUI: () => this._limpiarModalUI(),
            limpiarComponentes: () => this._limpiarComponentes()
        });
    }



    /**
     * Cargar datos de prenda en el modal para editar
     */
    cargarItemEnModal(prenda, prendaIndex) {
        this.prendaEditIndex = prendaIndex;
        this.prendaEditor.cargarPrendaEnModal(prenda, prendaIndex);
    }

    /**
     * Agregar prenda nueva - Recolectar datos del formulario modal y guardar
     */
    async agregarPrendaNueva() {
        return this._getPrendaFlowService().agregarPrendaNueva();
    }

    async actualizarPrendaExistente() {
        await this.prendaEditor.actualizarPrendaExistente();
        this.prendaEditIndex = null;
    }

    async manejarSubmitFormulario(e) {
        return this._getSubmitController().manejarSubmitFormulario(e);
    }

    mostrarCargando(mensaje = 'Cargando...') {
        this._getFeedbackUIService().mostrarCargando(mensaje);
    }

    ocultarCargando() {
        this._getFeedbackUIService().ocultarCargando();
    }

    mostrarExito(mensaje) {
        this._getFeedbackUIService().mostrarExito(mensaje);
    }

    recolectarDatosPedido() {
        return this.formCollector.recolectarDatosPedido();
    }

    mostrarVistaPreviaFactura() {
        if (!this.renderer) {

            return;
        }
        this.renderer.mostrarVistaPreviaFactura();
    }

    mostrarModalExito() {
        this._getSuccessModalService().mostrarModalExito({
            datosPedidoCreado: this.datosPedidoCreado,
            ctx: (key) => this._ctx(key)
        });
    }


    renderizarProcesosDirectos() {
        this.renderer.renderizarProcesosDirectos();
    }
}


// Inicializar cuando el DOM esté listo O inmediatamente si el script se carga dinámicamente

function initializeGestionItemsUI() {
    if (globalThis.gestionItemsUI) return;
    const notificationService = typeof NotificationService === 'undefined' ? null : new NotificationService();
    globalThis.gestionItemsUI = new GestionItemsUI({ notificationService });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGestionItemsUI);
} else {
    initializeGestionItemsUI();
}
 



