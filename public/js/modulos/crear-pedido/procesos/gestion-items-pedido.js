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
    prendaModalMode = 'create';
    prendaEditKey = null;
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
    itemRemovalService = null;
    itemsSyncService = null;

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

    _normalizarIdentificador(valor) {
        if (valor === null || valor === undefined || valor === '') return null;
        if (typeof valor === 'number' && !Number.isNaN(valor)) return String(valor);

        const texto = String(valor).trim();
        if (!texto) return null;

        const matchTarjeta = texto.match(/^epp[-:](.+)$/i);
        if (matchTarjeta && matchTarjeta[1]) {
            return String(matchTarjeta[1]).trim();
        }

        return texto;
    }

    _asegurarTarjetaIdEpp(epp) {
        if (!epp || typeof epp !== 'object') return epp;

        if (!epp.tarjetaId) {
            const baseId = epp.pedido_epp_id || epp.epp_id || epp.id || `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
            epp.tarjetaId = `epp-${baseId}`;
        }

        return epp;
    }

    _generarPrendaLocalId(prenda = {}) {
        const baseId = prenda.prenda_pedido_id || prenda.id || null;
        if (baseId !== null && baseId !== undefined && baseId !== '') {
            return `prenda-${baseId}`;
        }

        return `prenda-local-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    }

    _asegurarIdentidadPrenda(prenda) {
        if (!prenda || typeof prenda !== 'object') return prenda;

        if (!prenda.prenda_pedido_id && prenda.id) {
            prenda.prenda_pedido_id = prenda.id;
        }

        if (!prenda._local_id) {
            prenda._local_id = this._generarPrendaLocalId(prenda);
        }

        return prenda;
    }

    _obtenerClavePrenda(prenda) {
        if (!prenda || typeof prenda !== 'object') return null;
        const id = prenda.prenda_pedido_id || prenda.id || prenda._local_id || null;
        if (id === null || id === undefined || id === '') return null;
        return `prenda:${id}`;
    }

    _setModoModalPrendaEdicion(prendaIndex, prenda = null) {
        this.prendaModalMode = 'edit';
        this.prendaEditIndex = Number.isInteger(prendaIndex) ? prendaIndex : this.prendaEditIndex;
        const prendaBase = prenda || this.prendas?.[this.prendaEditIndex] || null;
        this.prendaEditKey = this._obtenerClavePrenda(prendaBase);
    }

    _setModoModalPrendaCreacion() {
        this.prendaModalMode = 'create';
        this.prendaEditIndex = null;
        this.prendaEditKey = null;
    }

    setModoModalPrendaEdicion(prendaIndex, prenda = null) {
        this._setModoModalPrendaEdicion(prendaIndex, prenda);
    }

    setModoModalPrendaCreacion() {
        this._setModoModalPrendaCreacion();
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

    obtenerItemPorIndiceVisual(indiceVisual) {
        const indice = Number.parseInt(indiceVisual, 10);
        if (!Number.isInteger(indice) || indice < 0) {
            return null;
        }

        const orden = this.ordenItems?.[indice];
        if (!orden) {
            return null;
        }

        const collection = this._stateCollection(orden.tipo);
        return collection?.[orden.index] || null;
    }

    obtenerPrendaPorIndiceVisual(indiceVisual) {
        const indice = Number.parseInt(indiceVisual, 10);
        if (!Number.isInteger(indice) || indice < 0) {
            return null;
        }

        const orden = this.ordenItems?.[indice];
        if (!orden || orden.tipo !== 'prenda') {
            return null;
        }

        return this.prendas?.[orden.index] || null;
    }

    buscarEPPEnEstado(referencia = {}) {
        const tarjetaId = this._normalizarIdentificador(referencia.tarjetaId);
        const pedidoEppId = this._normalizarIdentificador(referencia.pedidoEppId || referencia.pedido_epp_id);
        const eppId = this._normalizarIdentificador(referencia.eppId || referencia.epp_id || referencia.id);

        const index = this.epps.findIndex((epp) => {
            const tarjetaActual = this._normalizarIdentificador(epp?.tarjetaId || epp?.tarjeta_id || epp?.id);
            const pedidoActual = this._normalizarIdentificador(epp?.pedido_epp_id || epp?.pedidoEppId);
            const eppActual = this._normalizarIdentificador(epp?.epp_id || epp?.eppId || epp?.id);

            if (tarjetaId && tarjetaActual === tarjetaId) return true;
            if (pedidoEppId && pedidoActual === pedidoEppId) return true;
            if (eppId && (eppActual === eppId || pedidoActual === eppId)) return true;
            if (tarjetaId && (eppActual === tarjetaId || pedidoActual === tarjetaId)) return true;

            return false;
        });

        if (index === -1) {
            return { epp: null, index: -1 };
        }

        return {
            epp: this.epps[index],
            index
        };
    }

    actualizarEPPEnEstado(referencia = {}, cambios = {}) {
        const { epp, index } = this.buscarEPPEnEstado(referencia);
        if (!epp || index < 0) {
            return null;
        }

        Object.assign(epp, cambios);
        if (!Array.isArray(epp._imagenes_originales)) {
            const imagenesOriginales = Array.isArray(epp.imagenes) ? epp.imagenes : [];
            epp._imagenes_originales = imagenesOriginales.map((img) => (
                img && typeof img === 'object'
                    ? { ...img }
                    : img
            ));
        }
        this._asegurarTarjetaIdEpp(epp);

        return {
            epp,
            index
        };
    }

    /**
     * Agregar prenda y registrar en orden
     */
    agregarPrendaAlOrden(prenda) {
        if (typeof window.asegurarLocalId === 'function') {
            window.asegurarLocalId(prenda, 'prenda');
        }
        const prendaNormalizada = this._asegurarIdentidadPrenda(prenda);
        const clavePrenda = this._obtenerClavePrenda(prendaNormalizada);

        if (clavePrenda) {
            const indiceExistente = this.prendas.findIndex((actual) => this._obtenerClavePrenda(actual) === clavePrenda);
            if (indiceExistente !== -1) {
                const yaEnOrden = this.ordenItems.some((entrada) => entrada.tipo === 'prenda' && entrada.index === indiceExistente);
                if (yaEnOrden) {
                    debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Registro duplicado omitido:', clavePrenda);
                    return indiceExistente;
                }
            }
        }

        const index = this._stateAddItem('prenda', prendaNormalizada);
        
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - PRENDA agregada:', prendaNormalizada.nombre_prenda);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Nuevo index PRENDA:', index);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Total PRENDAS:', this.prendas.length);
        debugLog('[gestionItemsUI]  agregarPrendaAlOrden() - Total EPPs:', this.epps.length);
        return index;
    }

    /**
     * Agregar EPP y registrar en orden
     */
    agregarEPPAlOrden(epp) {
        if (typeof window.asegurarLocalId === 'function') {
            window.asegurarLocalId(epp, 'epp');
        }
        this._asegurarTarjetaIdEpp(epp);
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



    _getItemRemovalService() {
        if (!this.itemRemovalService) {
            this.itemRemovalService = new ItemRemovalService({ ui: this });
        } else {
            this.itemRemovalService.ui = this;
        }
        return this.itemRemovalService;
    }

    _getItemsSyncService() {
        if (!this.itemsSyncService) {
            this.itemsSyncService = new ItemsSyncService({ ui: this });
        } else {
            this.itemsSyncService.ui = this;
        }
        return this.itemsSyncService;
    }

    async cargarItems() {
        return this._getItemsSyncService().cargarItems();
    }

    async agregarItem(itemData) {
        return this._getItemsSyncService().agregarItem(itemData);
    }

    async eliminarItem(index) {
        return this._getItemRemovalService().eliminarItem(index);
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
        this._setModoModalPrendaEdicion(this.prendaEditIndex, prendaAEditar);
        
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
        // Forzar modo CREACIÓN (evita heredar estado de edición)
        this._setModoModalPrendaCreacion();
        this.prendaEnModoEdicion = false;

        // Resetear select de origen al placeholder vacío
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) origenSelect.value = '';

        // Limpiar estado UNISEX persistente
        globalThis.cantidadSoloSeleccionada = null;
        if (typeof globalThis.eliminarUnisex === 'function') {
            try {
                globalThis.eliminarUnisex();
            } catch (e) {
                console.warn('[abrirModalAgregarPrendaNueva] No se pudo ejecutar eliminarUnisex:', e);
            }
        }

        if (typeof ModalCleanup !== 'undefined' && typeof ModalCleanup.prepararParaNueva === 'function') {
            ModalCleanup.prepararParaNueva();
        }

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

        // ✅ NUEVO: Establecer contexto de prenda nueva en IndexedImageStorageService
        // Genera un ID temporal consistente para toda la sesión de creación
        const nuevaPrendaId = `new-prenda-${Date.now()}`;
        if (globalThis.imagenesPrendaStorage && typeof globalThis.imagenesPrendaStorage.setPrendaActual === 'function') {
            globalThis.imagenesPrendaStorage.setPrendaActual(nuevaPrendaId);
            console.log('[gestion-items-pedido] Contexto de nueva prenda establecido:', nuevaPrendaId);
        }
        this._setCtx('nuevaPrendaId', nuevaPrendaId);

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
        const esEdicion = this.prendaModalMode === 'edit';

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
        this._setModoModalPrendaCreacion();
        if (typeof ModalCleanup !== 'undefined') {
            ModalCleanup.limpiarDespuésDeGuardar();
            return;
        }
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
        this._setModoModalPrendaEdicion(prendaIndex, prenda);
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
 
