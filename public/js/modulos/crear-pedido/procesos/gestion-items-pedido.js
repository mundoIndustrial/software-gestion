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
    prendas = [];      // Array separado para prendas
    epps = [];         // Array separado para EPPs
    ordenItems = [];   // Orden de inserción: [{tipo: 'prenda', index: 0}, {tipo: 'epp', index: 0}, ...]
    prendasEliminadas = []; // [{ prenda_id, nombre_prenda, motivo }]
    notificationService = null;
    apiService = null;
    formCollector = null;
    renderer = null;
    prendaEditor = null;

    constructor(options = {}) {
        // Inicializar servicios con validación de disponibilidad
        try {
            this.notificationService = options.notificationService || (typeof NotificationService === 'undefined' ? null : new NotificationService());
            this.apiService = options.apiService || (typeof ItemAPIService === 'undefined' ? null : new ItemAPIService());
            this.formCollector = options.formCollector || (typeof ItemFormCollector === 'undefined' ? null : new ItemFormCollector());

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

    /**
     * Obtener todos los items en orden de inserción
     */
    obtenerItemsOrdenados() {
        const itemsOrdenados = [];
        console.log('[gestionItemsUI]  obtenerItemsOrdenados() - this.ordenItems:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI]  obtenerItemsOrdenados() - this.prendas:', this.prendas.length, 'items');
        console.log('[gestionItemsUI]  obtenerItemsOrdenados() - this.epps:', this.epps.length, 'items');
        
        this.ordenItems.forEach(({ tipo, index }) => {
            if (tipo === 'prenda' && this.prendas[index]) {
                itemsOrdenados.push(this.prendas[index]);
                console.log('[gestionItemsUI]  Agregado PRENDA index:', index);
            } else if (tipo === 'epp' && this.epps[index]) {
                itemsOrdenados.push(this.epps[index]);
                console.log('[gestionItemsUI]  Agregado EPP index:', index);
            } else {
                console.log('[gestionItemsUI]  ITEM NO ENCONTRADO - tipo:', tipo, 'index:', index);
            }
        });
        
        console.log('[gestionItemsUI]  Total items a renderizar:', itemsOrdenados.length);
        return itemsOrdenados;
    }

    /**
     * Agregar prenda y registrar en orden
     */
    agregarPrendaAlOrden(prenda) {
        const index = this.prendas.length;
        
        this.prendas.push(prenda);
        this.ordenItems.push({ tipo: 'prenda', index });
        
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - PRENDA agregada:', prenda.nombre_prenda);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Nuevo index PRENDA:', index);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Total PRENDAS:', this.prendas.length);
        console.log('[gestionItemsUI]  agregarPrendaAlOrden() - Total EPPs:', this.epps.length);
    }

    /**
     * Agregar EPP y registrar en orden
     */
    agregarEPPAlOrden(epp) {
        const index = this.epps.length;
        this.epps.push(epp);
        this.ordenItems.push({ tipo: 'epp', index });
        
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - EPP agregado:', epp.nombre_completo || epp.nombre);
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - Nuevo index EPP:', index);
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - this.ordenItems ahora:', JSON.stringify(this.ordenItems));
        console.log('[gestionItemsUI]  agregarEPPAlOrden() - Total EPPs:', this.epps.length);

        return index;
    }

    /**
     * Eliminar EPP por posición visual de tarjeta en el DOM
     * Se llama desde epp-item-manager-nuevo.js al eliminar una tarjeta EPP
     */
    eliminarEPPPorTarjetaId(tarjetaId) {
        try {
            const posicionVisual = this._getEppPositionFromDom(tarjetaId);
            const eppIdx = posicionVisual >= 0 ? posicionVisual : this._getLastEppIndex();

            if (!this._isPosicionValidaEpp(eppIdx)) {
                console.warn('[gestionItemsUI] No se pudo eliminar EPP - posición inválida:', eppIdx);
                return false;
            }

            this.epps.splice(eppIdx, 1);
            console.log(`[gestionItemsUI]  EPP eliminado del array. Quedan: ${this.epps.length}`);

            const ordenIdx = this._findOrdenItemIndexForEpp(eppIdx);
            if (ordenIdx >= 0) {
                this.ordenItems.splice(ordenIdx, 1);
            }

            this._rebuildOrdenIndices();

            console.log(`[gestionItemsUI]  ordenItems actualizado:`, JSON.stringify(this.ordenItems));
            console.log(`[gestionItemsUI]  EPPs restantes: ${this.epps.length}, Prendas: ${this.prendas.length}`);
            return true;
        } catch (error) {
            console.error('[gestionItemsUI] Error eliminando EPP:', error);
            return false;
        }
    }

    _getEppPositionFromDom(tarjetaId) {
        const tarjetas = document.querySelectorAll('.item-epp-card-nuevo');
        for (let i = 0; i < tarjetas.length; i++) {
            if (tarjetas[i].dataset.eppId === tarjetaId) {
                return i;
            }
        }
        return -1;
    }

    _getLastEppIndex() {
        const eppPositions = this.ordenItems
            .map(item => (item.tipo === 'epp' ? item : null))
            .filter(Boolean);

        if (eppPositions.length === 0) {
            return -1;
        }

        return eppPositions.length - 1;
    }

    _isPosicionValidaEpp(index) {
        return Number.isInteger(index) && index >= 0 && index < this.epps.length;
    }

    _findOrdenItemIndexForEpp(eppIndex) {
        let eppCount = 0;
        for (let i = 0; i < this.ordenItems.length; i++) {
            if (this.ordenItems[i].tipo === 'epp') {
                if (eppCount === eppIndex) {
                    return i;
                }
                eppCount++;
            }
        }
        return -1;
    }

    _rebuildOrdenIndices() {
        let prendaIdx = 0;
        let eppIdx = 0;

        this.ordenItems.forEach(item => {
            if (item.tipo === 'prenda') {
                item.index = prendaIdx++;
            } else if (item.tipo === 'epp') {
                item.index = eppIdx++;
            }
        });
    }

    /**
     * Método público para agregar EPP desde modal externo
     */
    async agregarEPPDesdeModal(eppData) {
        try {

            console.log('[gestionItemsUI] 📥 agregarEPPDesdeModal() iniciado con EPP:', eppData.nombre_completo || eppData.nombre);
            
            // Agregar al orden
            this.agregarEPPAlOrden(eppData);
            
            console.log('[gestionItemsUI] 📥 Después de agregarEPPAlOrden()');
            console.log('[gestionItemsUI] 📥 this.epps:', this.epps.length);
            console.log('[gestionItemsUI] 📥 this.ordenItems:', JSON.stringify(this.ordenItems));
            
            // Notificar éxito
            this.notificationService?.exito('EPP agregado correctamente');
            
            // Actualizar visualización en orden
            if (this.renderer) {
                const itemsOrdenados = this.obtenerItemsOrdenados();
                console.log('[gestionItemsUI] 📥 Renderizando', itemsOrdenados.length, 'items');
                await this.renderer.actualizar(itemsOrdenados);
            }
            
            return true;
        } catch (error) {

            this.notificationService?.error('Error al agregar EPP: ' + error.message);
            return false;
        }
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

    async cargarItems() {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                return;
            }

            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items;

            //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
            const itemsOrdenados = this.obtenerItemsOrdenados();
            await this.renderer.actualizar(itemsOrdenados);
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error al cargar ítems');
            }
            throw error;  // Re-throw para que el caller pueda tomar acción adicional
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {

                return false;
            }
            const resultado = await this.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.items = resultado.items;
                //  Usar obtenerItemsOrdenados() para preservar prendas y EPPs en orden
                const itemsOrdenados = this.obtenerItemsOrdenados();
                await this.renderer.actualizar(itemsOrdenados);
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
            if (!this.apiService || !this.renderer || !this.notificationService) {
                return;
            }

            const itemInfo = this._getItemInfoByPosition(index);
            if (!itemInfo) {
                console.warn('[eliminarItem] Ítem no encontrado en posición:', index);
                return;
            }

            this._removeItemByInfo(itemInfo, index);

            const itemsActualizados = this.obtenerItemsOrdenados();
            await this.renderer.actualizar(itemsActualizados);
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
                console.log('[eliminarItem]  Prenda existente marcada para eliminar en backend:', {
                    prenda_id: prendaIdExistente,
                    total_prendas_eliminadas: this.prendasEliminadas.length
                });
            }
        }

        if (tipo === 'prenda' && indice >= 0) {
            this.prendas.splice(indice, 1);
            console.log(`[eliminarItem]  Prenda eliminada del array. Quedan: ${this.prendas.length}`);
        } else if (tipo === 'epp' && indice >= 0) {
            this.epps.splice(indice, 1);
            console.log(`[eliminarItem]  EPP eliminado del array. Quedan: ${this.epps.length}`);
        }

        this.ordenItems.splice(ordenIndex, 1);
        this._rebuildOrdenIndices();

        console.log(`[eliminarItem]  ordenItems actualizado:`, JSON.stringify(this.ordenItems));

        if (tipo === 'prenda' && globalThis.gestorPrendaSinCotizacion?.eliminar) {
            globalThis.gestorPrendaSinCotizacion.eliminar(indice);
        }
    }

    abrirModalSeleccionPrendas() {
        if (globalThis.abrirModalSeleccionPrendas) {
            globalThis.abrirModalSeleccionPrendas();
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

    _inicializarFsm(fsm) {
        if (!fsm) return;

        fsm.cambiarEstado('OPENING', { origen: 'abrirModalAgregarPrendaNueva' });

        clearTimeout(fsm._openingTimeout);
        fsm._openingTimeout = setTimeout(() => {
            if (fsm.obtenerEstado() === 'OPENING') {
                console.warn('[abrirModal] Timeout: FSM stuck en OPENING, forzando CLOSED');
                fsm.estado = 'CLOSED';
            }
        }, 5000);
    }

    _registrarListenerModal(fsm) {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) return;

        modal.addEventListener('shown.bs.modal', () => {
            if (globalThis.DragDropManager) {
                globalThis.DragDropManager.inicializar();
            }
            if (fsm) {
                fsm.cambiarEstado('OPEN', { origen: 'shown.bs.modal' });
            }
            console.log('[abrirModal]  Modal OPEN — DragDrop inicializado');
        }, { once: true });
    }

    async _cargarCatalogosModal() {
        if (typeof globalThis.cargarCatalogosModal === 'function') {
            await globalThis.cargarCatalogosModal();
        }
    }

    _cargarModoEdicion() {
        const prendaAEditar = this.prendas[this.prendaEditIndex];
        if (prendaAEditar && this.prendaEditor) {
            this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
        }
    }

    _cargarModoCreacion() {
        console.log('[abrirModalAgregarPrendaNueva] 💣 RESET - Limpiando globalThis.telasCreacion para NUEVA prenda');
        console.log('[abrirModalAgregarPrendaNueva]   ANTES:', globalThis.telasCreacion);

        globalThis.telasCreacion = [];
        globalThis.telasAgregadas = [];
        globalThis.imagenesTelaModalNueva = [];

        console.log('[abrirModalAgregarPrendaNueva]   DESPUÉS:', globalThis.telasCreacion);

        if (typeof globalThis.renderizarTelasChips === 'function') {
            globalThis.renderizarTelasChips();
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
        const fsm = globalThis.__MODAL_FSM__;

        // Guard: FSM previene doble apertura
        if (fsm && !fsm.puedeAbrir()) {
            console.warn('[abrirModal] Bloqueado por FSM (estado:', fsm.obtenerEstado(), ')');
            return;
        }

        try {
            this._inicializarFsm(fsm);
            await this._cargarCatalogosModal();
            this._registrarListenerModal(fsm);

            const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
            this._actualizarBtnGuardarPrenda(esEdicion);

            if (esEdicion) {
                this._cargarModoEdicion();
            } else {
                this._cargarModoCreacion();
            }
        } catch (error) {
            console.error('[abrirModalAgregarPrendaNueva] ERROR:', error);
            if (fsm) {
                fsm.cambiarEstado('CLOSED', { error: error.message });
            }

            if (typeof NotificationService !== 'undefined' && NotificationService) {
                NotificationService.error('Error abriendo modal: ' + error.message);
            }
        }
    }

    _gestionarEstadoFsm(fsm) {
        if (!fsm) return;

        clearTimeout(fsm._openingTimeout);

        const estadoActual = fsm.obtenerEstado();
        if (estadoActual === 'CLOSED') return;

        if (estadoActual === 'OPEN') {
            fsm.cambiarEstado('CLOSING', { origen: 'cerrarModalAgregarPrendaNueva' });
        } else {
            fsm.estado = 'CLOSED';
            console.log('[cerrarModal] FSM forzada a CLOSED desde:', estadoActual);
        }
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
        globalThis.prendaEditIndex = null;

        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    _limpiarComponentes() {
        if (this.prendaEditor) {
            this.prendaEditor.resetearEdicion();
        }

        if (globalThis.DragDropManager) {
            globalThis.DragDropManager.destruir();
        }
    }

    /**
     * Cerrar modal de agregar/editar prenda
     */
    cerrarModalAgregarPrendaNueva() {
        const fsm = globalThis.__MODAL_FSM__;

        try {
            this._gestionarEstadoFsm(fsm);
            this._limpiarEditorFlags();
            this._limpiarModalUI();
            this._limpiarComponentes();

            if (fsm) {
                fsm.cambiarEstado('CLOSED', { origen: 'cerrarModalAgregarPrendaNueva' });
            }
        } catch (error) {
            console.error('[cerrarModal] ERROR:', error);
            if (fsm) {
                fsm.cambiarEstado('CLOSED', { error: error.message });
            }
        }
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
            this.notificationService?.error('Error al agregar prenda: ' + error.message);
        }
    }

    _logEstadoInicial() {
        console.log('\n\n═══════════════════════════════════════════════════════════════');
        console.log(' [agregarPrendaNueva] ⏱️ CLICK EN "GUARDAR CAMBIOS" ← PUNTO DE INICIO');
        console.log('═══════════════════════════════════════════════════════════════');
        
        const estadoImagenesPrenda = globalThis.imagenesPrendaStorage?.obtenerImagenes?.() || [];
        const estadoTelasCreacion = globalThis.telasCreacion || [];
        const estadoProcesos = globalThis.procesosSeleccionados || {};
        
        console.log('[agregarPrendaNueva] ESTADO INICIAL DE STORAGES:');
        console.log('[agregarPrendaNueva]   imagenesPrendaStorage:', estadoImagenesPrenda.length, 'imagenes');
        console.log('[agregarPrendaNueva]   globalThis.telasCreacion:', estadoTelasCreacion.length, 'telas');
        console.log('[agregarPrendaNueva]    procesosSeleccionados types:', Object.keys(estadoProcesos));
        console.log('═══════════════════════════════════════════════════════════════\n');
        
        console.log('[agregarPrendaNueva]  INICIO - Estado actual:');
        console.log('[agregarPrendaNueva]   - this.prendaEditIndex:', this.prendaEditIndex);
        console.log('[agregarPrendaNueva]   - this.prendas.length:', this.prendas.length);
        console.log('[agregarPrendaNueva]   - ¿Es edición?:', this.prendaEditIndex !== null && this.prendaEditIndex !== undefined);
    }

    _ensureNotificationService() {
        if (this.notificationService) return;
        console.warn('[GestionItemsUI]  notificationService no disponible, usando servicio alterno temporal');
        if (typeof NotificationService === 'undefined') {
            this.notificationService = {
                success: (msg) => console.log('', msg),
                error: (msg) => console.error('', msg),
                warning: (msg) => console.warn('', msg)
            };
        } else {
            this.notificationService = new NotificationService();
        }
    }

    async _recolectarYHidratarDatos() {
        globalThis.prendaFormCollector.setNotificationService(this.notificationService);
        const prendaData = globalThis.prendaFormCollector.construirPrendaDesdeFormulario(
            this.prendaEditIndex,
            this.prendas
        );

        if (!prendaData) {
            this.notificationService?.error('Por favor completa los datos de la prenda');
            return null;
        }

        this._hidratarAsignacionesConArchivos(prendaData);
        this._agregarImagenesAEliminar(prendaData);
        this._logDatosRecopilados(prendaData);

        return prendaData;
    }

    _hidratarAsignacionesConArchivos(prendaData) {
        if (prendaData?.asignacionesColoresPorTalla && typeof prendaData.asignacionesColoresPorTalla === 'object') {
            const getImageWizard = (globalThis.ColoresPorTalla && typeof globalThis.ColoresPorTalla.getImage === 'function')
                ? globalThis.ColoresPorTalla.getImage.bind(globalThis.ColoresPorTalla)
                : null;

            let conImagenId = 0, conImagenFile = 0;
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

            console.log('[agregarPrendaNueva]  Hidratar asignaciones:', { grupos: Object.keys(resultado).length, conImagenId, conImagenFile });
            prendaData.asignacionesColoresPorTalla = resultado;
            prendaData.asignacionesColores = resultado;
        }
    }

    _agregarImagenesAEliminar(prendaData) {
        if (globalThis.imagenesAEliminar && globalThis.imagenesAEliminar.length > 0) {
            prendaData.imagenes_a_eliminar = globalThis.imagenesAEliminar;
            console.log('[agregarPrendaNueva]  Imágenes marcadas para eliminación:', {
                cantidad: globalThis.imagenesAEliminar.length,
                ids: globalThis.imagenesAEliminar
            });
        }
    }

    _logDatosRecopilados(prendaData) {
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('[agregarPrendaNueva]  DATOS RECOPILADOS POR prendaFormCollector:');
        console.log('[agregarPrendaNueva]    prendaData.imagenes:', prendaData?.imagenes?.length || 0);
        console.log('[agregarPrendaNueva]    prendaData.telasAgregadas:', prendaData?.telasAgregadas?.length || 0);
        console.log('[agregarPrendaNueva]    prendaData.procesos types:', Object.keys(prendaData?.procesos || {}));
        console.log('═══════════════════════════════════════════════════════════════\n');
    }

    _validarDatosFormulario(prendaData) {
        const tieneTallas = prendaData.cantidad_talla && 
            Object.values(prendaData.cantidad_talla).some(genero => Object.keys(genero).length > 0);
        
        const tieneSoloCantidad = globalThis.cantidadSoloSeleccionada && globalThis.cantidadSoloSeleccionada > 0;

        console.log('[gestion-items-pedido]  Validación de tallas:', {
            tieneTallas,
            tieneSoloCantidad
        });

        if (!tieneTallas && !tieneSoloCantidad) {
            this.notificationService?.advertencia('Por favor selecciona al menos una talla o utiliza la opción "UNISEX"');
            console.log('[gestion-items-pedido]  Validación FALLIDA: No hay tallas ni cantidad');
            return false;
        }

        if (tieneSoloCantidad) {
            prendaData.cantidad_solo = globalThis.cantidadSoloSeleccionada;
            console.log('[gestion-items-pedido] Cantidad sin talla agregada:', globalThis.cantidadSoloSeleccionada);
        }

        console.log('[gestion-items-pedido]  Validación EXITOSA: Hay tallas o cantidad, procediendo a guardar');
        return true;
    }

    async _procesarTypoManga(prendaData) {
        if (!prendaData.variantes?.tipo_manga_crear || !prendaData.variantes?.tipo_manga) return;

        console.log('[gestion-items-pedido]  Creando tipo de manga:', prendaData.variantes.tipo_manga);
        
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
                console.log('[gestion-items-pedido] Tipo de manga creado:', { id: result.data.id, nombre: result.data.nombre });
                delete prendaData.variantes.tipo_manga_crear;
            } else {
                console.warn('[gestion-items-pedido]  No se pudo crear tipo de manga:', result);
                this.notificationService?.advertencia('No se pudo crear el tipo de manga, se guardará solo el nombre');
            }
        } catch (error) {
            console.error('[gestion-items-pedido]  Error creando tipo de manga:', error);
            this.notificationService?.advertencia('Error al crear tipo de manga, se guardará solo el nombre');
        }
    }

    _determinarModosYPedido() {
        const enPedidoExistente = globalThis.datosEdicionPedido && (globalThis.datosEdicionPedido.id || globalThis.datosEdicionPedido.numero_pedido);
        const pedidoId = enPedidoExistente ? globalThis.datosEdicionPedido.id : null;
        const esNuevaDesdeCotz = this.prendaEditor?.esNuevaPrendaDesdeCotizacion === true;
        const esEdicionReal = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        const vamosAEditar = esEdicionReal && !esNuevaDesdeCotz;

        console.log('[guardarPrenda]  DETECCIÓN CRÍTICA:', {
            esNuevaDesdeCotz,
            esEdicionReal,
            prendaEditIndex: this.prendaEditIndex,
            vamosAEditar
        });
        console.log('[guardarPrenda]  ACCIÓN A EJECUTAR:', vamosAEditar ? 'EDITAR' : 'AGREGAR NUEVA');

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
        const prendaOriginal = globalThis.prendaEnEdicion?.prendaOriginal;
        prendaData.prenda_pedido_id = prendaOriginal?.prenda_pedido_id || prendaOriginal?.id;

        if ((globalThis.telasAgregadas?.length > 0) || (globalThis.telasCreacion?.length > 0)) {
            const telasFuente = globalThis.telasAgregadas?.length > 0 ? globalThis.telasAgregadas : globalThis.telasCreacion;
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

        await globalThis.modalNovedadEditacion.mostrarModalYActualizar(pedidoId, prendaData, this.prendaEditIndex);
    }

    _procesarEditacionEnMemoria(prendaData) {
        console.log('[guardarPrenda]  MODO CREACIÓN: Actualizando prenda en memoria');

        if (!this.prendas[this.prendaEditIndex]) {
            console.error('[guardarPrenda]  ERROR: No existe prenda en index', this.prendaEditIndex);
            return;
        }

        const esModoCreate = !globalThis.datosEdicionPedido || (!globalThis.datosEdicionPedido.id && !globalThis.datosEdicionPedido.numero_pedido);
        const imagenesStorage = globalThis.imagenesPrendaStorage?.obtenerImagenes?.() || [];
        
        if (esModoCreate && imagenesStorage.length === 0) {
            prendaData.imagenes = [];
            this.prendas[this.prendaEditIndex].imagenes = [];
        }

        const prendaAnterior = structuredClone(this.prendas[this.prendaEditIndex]);
        this.prendas[this.prendaEditIndex] = { ...this.prendas[this.prendaEditIndex], ...prendaData };

        console.log('[guardarPrenda] PRENDA ACTUALIZADA:', {
            'Nombre ANTES': prendaAnterior.nombre_prenda,
            'Nombre DESPUES': this.prendas[this.prendaEditIndex].nombre_prenda
        });

        if (this.renderer) {
            const itemsOrdenados = this.obtenerItemsOrdenados();
            this.renderer.actualizar(itemsOrdenados).catch(err => {
                console.error('[gestionItemsUI] Error renderizando:', err);
            });
        }

        this.notificationService?.exito('Prenda actualizada correctamente');
        this.cerrarModalAgregarPrendaNueva();
    }

    async _procesarModoCreacion(prendaData, enPedidoExistente, pedidoId) {
        if (enPedidoExistente) {
            await globalThis.modalNovedadPrenda.mostrarModalYGuardar(pedidoId, prendaData);
        } else {
            this._agregarPrendaNuevaEnMemoria(prendaData);
        }
    }

    _agregarPrendaNuevaEnMemoria(prendaData) {
        this.notificationService?.exito('Prenda agregada correctamente');

        if (prendaData.tipo === 'prenda_nueva') {
            prendaData.tipo = 'prenda';
        }

        console.log('[gestionItemsUI]  Prenda normalizada antes de agregar:', {
            tipo: prendaData.tipo,
            nombre_prenda: prendaData.nombre_prenda,
            cantidad_talla: prendaData.cantidad_talla,
            telasAgregadas: prendaData.telasAgregadas?.length || 0
        });

        this.agregarPrendaAlOrden(prendaData);
    }

    _finalizarYRenderizar() {
        this.cerrarModalAgregarPrendaNueva();

        console.log('[gestionItemsUI]  PUNTO CRÍTICO: Después de agregar prenda');
        console.log('[gestionItemsUI]  this.prendas:', this.prendas.length);
        console.log('[gestionItemsUI]  this.epps:', this.epps.length);
        console.log('[gestionItemsUI]  this.ordenItems:', JSON.stringify(this.ordenItems));

        if (this.renderer) {
            const itemsOrdenados = this.obtenerItemsOrdenados();
            console.log('[gestionItemsUI]  Llamando renderer.actualizar() con', itemsOrdenados.length, 'items');
            this.renderer.actualizar(itemsOrdenados).catch(err => {
                console.error('[gestionItemsUI] Error renderizando:', err);
            });
        }

        if (globalThis.datosEdicionPedido) {
            globalThis.datosEdicionPedido.prendas = this.prendas;
        }
    }

    /**
     * Construir objeto de prenda desde el formulario modal
     * Recolecta: nombre, descripción, origen, imágenes, telas, tallas, variaciones, procesos
     * @private
     */
    /**
     * Actualizar prenda existente
     */
    async actualizarPrendaExistente() {
        await this.prendaEditor.actualizarPrendaExistente();
        this.prendaEditIndex = null;
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();
        try {
            if (!this.formCollector || !this.apiService || !this.notificationService) return;

            const clienteInput = document.getElementById('cliente_editable');
            if (!this._validarCliente(clienteInput)) return;

            const pedidoData = this.formCollector.recolectarDatosPedido();
            this._logPedidoData(pedidoData);

            if (!this._validarItemsPedido(pedidoData)) return;

            this.mostrarCargando('Validando pedido...');
            const validacion = await this.apiService.validarPedido(pedidoData);
            console.log('[gestion-items-pedido]  Validación recibida:', validacion);

            if (!validacion.success) {
                this.ocultarCargando();
                this._mostrarErroresValidacion(validacion);
                return;
            }

            console.log('[gestion-items-pedido] Validación exitosa, procediendo a crear pedido');
            this.mostrarCargando('Creando pedido...');
            const resultado = await this.apiService.crearPedido(pedidoData);
            this._logResultadoCreacion(resultado);

            if (resultado.success) {
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                this.ocultarCargando();
                setTimeout(() => {
                    this.mostrarModalExito();
                }, 300);
            } else {
                console.warn('[gestion-items-pedido]  resultado.success es FALSE o undefined');
            }
        } catch (error) {
            this._manejarErrorSubmit(error);
        }
    }

    _validarCliente(clienteInput) {
        if (!clienteInput?.value || clienteInput.value.trim() === '') {
            this.notificationService.error('El cliente es requerido');
            clienteInput?.focus();
            return false;
        }
        return true;
    }

    _validarItemsPedido(pedidoData) {
        const tienePrendas = pedidoData.prendas && pedidoData.prendas.length > 0;
        const tieneEpps = pedidoData.epps && pedidoData.epps.length > 0;
        const tieneItemsLegacy = pedidoData.items && pedidoData.items.length > 0;
        if (!tienePrendas && !tieneEpps && !tieneItemsLegacy) {
            this.notificationService.error('Debe agregar al menos una prenda o un EPP');
            return false;
        }
        return true;
    }

    _mostrarErroresValidacion(validacion) {
        console.log('[gestion-items-pedido]  Validación falló:', validacion.errores);
        const errores = validacion.errores || [];
        if (Array.isArray(errores) && errores.length > 0) {
            alert('Errores en el pedido:\n' + errores.join('\n'));
        } else {
            alert('Error en validación: ' + (validacion.message || JSON.stringify(validacion)));
        }
    }

    _logPedidoData(pedidoData) {
        console.log('[gestion-items-pedido]  PEDIDO DATA RECOLECTADA:', {
            prendas_total: pedidoData.prendas?.length || 0,
            epps_total: pedidoData.epps?.length || 0,
            primer_prenda_telas: pedidoData.prendas?.[0]?.telas?.length || 0,
            primer_prenda_procesos: pedidoData.prendas?.[0]?.procesos ? Object.keys(pedidoData.prendas[0].procesos) : [],
            primer_prenda_contenido: pedidoData.prendas?.[0]
        });
        if (pedidoData.prendas?.[0]?.procesos) {
            Object.entries(pedidoData.prendas[0].procesos).forEach(([procesoKey, proceso]) => {
                if (proceso.datos?.datosExtendidos) {
                    console.log(`[gestion-items-pedido]Proceso "${procesoKey}" TIENE datosExtendidos:`, {
                        generos: Object.keys(proceso.datos.datosExtendidos),
                        datosExtendidos: proceso.datos.datosExtendidos
                    });
                } else {
                    console.log(`[gestion-items-pedido]  Proceso "${procesoKey}" NO tiene datosExtendidos`);
                }
            });
        }
    }

    _logResultadoCreacion(resultado) {
        console.log('[gestion-items-pedido]  Resultado recibido:', resultado);
        console.log('[gestion-items-pedido] ¿resultado.success?', resultado.success);
        console.log('[gestion-items-pedido] typeof resultado.success:', typeof resultado.success);
        if (resultado.success) {
            console.log('[gestion-items-pedido]  ENTRANDO AL IF - Pedido creado exitosamente');
            console.log('[gestion-items-pedido] 📌 datosPedidoCreado:', {
                pedido_id: resultado.pedido_id,
                numero_pedido: resultado.numero_pedido
            });
        }
    }

    _manejarErrorSubmit(error) {
        console.error('[gestion-items-pedido]  ERROR CAPTURADO:', error);
        console.error('[gestion-items-pedido]  Stack:', error.stack);
        console.error('[gestion-items-pedido]  Message:', error.message);
        this.ocultarCargando();
        if (this.notificationService) {
            const mensajeError = error.message || 'Error desconocido al crear el pedido';
            this.notificationService.error('Error: ' + mensajeError);
        }
    }

    mostrarCargando(mensaje = 'Cargando...') {
        // Remover loader anterior si existe
        this.ocultarCargando();
        
        const loader = document.createElement('div');
        loader.id = 'pedido-loader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        const contenido = document.createElement('div');
        contenido.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        
        const spinner = document.createElement('div');
        spinner.style.cssText = `
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        `;
        
        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #333;
            font-size: 16px;
            font-weight: 500;
        `;
        
        // Agregar animación CSS
        if (!document.getElementById('pedido-loader-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-loader-style';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        contenido.appendChild(spinner);
        contenido.appendChild(texto);
        loader.appendChild(contenido);
        document.body.appendChild(loader);
    }

    ocultarCargando() {
        const loader = document.getElementById('pedido-loader');
        if (loader) {
            loader.remove();
        }
    }

    mostrarExito(mensaje) {
        const exito = document.createElement('div');
        exito.id = 'pedido-exito';
        exito.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #27ae60;
            font-size: 18px;
            font-weight: 600;
        `;
        
        // Agregar animación CSS si no existe
        if (!document.getElementById('pedido-exito-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-exito-style';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translate(-50%, -60%);
                    }
                    to {
                        opacity: 1;
                        transform: translate(-50%, -50%);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        exito.appendChild(texto);
        document.body.appendChild(exito);
        
        // Remover después de 2 segundos
        setTimeout(() => {
            exito.remove();
        }, 2000);
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
        console.log('[mostrarModalExito] 🎬 INICIANDO');
        console.log('[mostrarModalExito] ¿Existe MODAL_EXITO_PEDIDO_HTML?', typeof MODAL_EXITO_PEDIDO_HTML);
        console.log('[mostrarModalExito] ¿datosPedidoCreado?', this.datosPedidoCreado);
        
        // LIMPIAR asignaciones de colores tras crear el pedido
        console.log('[mostrarModalExito]  LIMPIANDO asignaciones de colores tras creación exitosa...');
        if (typeof limpiarAsignacionesColores === 'function') {
            limpiarAsignacionesColores();
            console.log('[mostrarModalExito] ✓ Asignaciones limpiadas');
        } else if (globalThis.StateManager && typeof globalThis.StateManager.limpiarAsignaciones === 'function') {
            globalThis.StateManager.limpiarAsignaciones();
            console.log('[mostrarModalExito] ✓ Asignaciones limpiadas (StateManager)');
        }
        
        let modalElement = document.getElementById('modalExitoPedido');
        console.log('[mostrarModalExito] ¿modalElement existe?', !!modalElement);
        
        if (!modalElement) {
            console.log('[mostrarModalExito]  Creando modal desde HTML...');
            if (typeof MODAL_EXITO_PEDIDO_HTML === 'undefined') {
                console.error('[mostrarModalExito]  CRÍTICO: MODAL_EXITO_PEDIDO_HTML no está definido');
                throw new Error('MODAL_EXITO_PEDIDO_HTML no está disponible');
            }
            document.body.insertAdjacentHTML('beforeend', MODAL_EXITO_PEDIDO_HTML);
            modalElement = document.getElementById('modalExitoPedido');
            console.log('[mostrarModalExito]  Modal creado, elemento encontrado?', !!modalElement);
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        console.log('[mostrarModalExito] ¿btnVolverAPedidos encontrado?', !!btnVolverAPedidos);
        
        if (btnVolverAPedidos) {
            console.log('[mostrarModalExito]  Asignando onclick');
            btnVolverAPedidos.onclick = () => {
                console.log('[mostrarModalExito] 👉 Botón presionado, redirigiendo...');
                globalThis.location.href = '/asesores/pedidos';
            };
        }

        console.log('[mostrarModalExito]  Mostrando modal');
        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        console.log('[mostrarModalExito]  COMPLETADO');
    }

    renderizarProcesosDirectos() {
        this.renderer.renderizarProcesosDirectos();
    }
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
    if (globalThis.editarProceso && globalThis.editarProceso !== globalThis.editarProcesoEdicion) {
        globalThis.editarProceso(tipo);
        return;
    }
    
    // Si no existe, detectar modo y abrir el modal correcto
    const procesoData = globalThis.procesosSeleccionados?.[tipo];
    const modoTallas = procesoData?.datos?.modo_tallas || 'generico';
    if (modoTallas === 'general' || modoTallas === 'especifico') {
        if (globalThis.abrirModalProcesoPorTallas) {
            globalThis.abrirModalProcesoPorTallas(tipo);
        }
    } else if (globalThis.abrirModalProcesoGenerico) {
        globalThis.abrirModalProcesoGenerico(tipo);
        
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
    if (globalThis.imagenesProcesoActual) {
        globalThis.imagenesProcesoActual = [null, null, null];
    }

    if (!globalThis.imagenesProcesoExistentes) {
        globalThis.imagenesProcesoExistentes = [];
    }
    globalThis.imagenesProcesoExistentes = [];
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

        if (globalThis.imagenesProcesoActual) {
            globalThis.imagenesProcesoActual[idx] = img;
        }
    });
}

function _cargarProcesoUbicaciones(datos) {
    if (!datos.ubicaciones || !globalThis.ubicacionesProcesoSeleccionadas) return;

    globalThis.ubicacionesProcesoSeleccionadas.length = 0;
    globalThis.ubicacionesProcesoSeleccionadas.push(...datos.ubicaciones);

    if (globalThis.renderizarListaUbicaciones) {
        globalThis.renderizarListaUbicaciones();
    }
}

function _cargarProcesoObservaciones(datos) {
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
}

function _extraerSobremedida(tallas, sobremedidaTallas) {
    const tallasLimpias = {};

    for (const [talla, valor] of Object.entries(tallas || {})) {
        if (talla !== 'SOBREMEDIDA') {
            tallasLimpias[talla] = valor;
            continue;
        }

        if (typeof valor === 'number') {
            sobremedidaTallas['DAMA'] = (sobremedidaTallas['DAMA'] || 0) + valor;
        } else if (typeof valor === 'object' && valor !== null) {
            for (const [genero, cantidad] of Object.entries(valor)) {
                sobremedidaTallas[genero] = (sobremedidaTallas[genero] || 0) + cantidad;
            }
        }
    }

    return tallasLimpias;
}

function _cargarProcesoTallas(datos) {
    if (!datos.tallas || !globalThis.tallasSeleccionadasProceso) return;

    let damaTallas = datos.tallas.dama || {};
    let caballeroTallas = datos.tallas.caballero || {};
    const sobremedidaTallas = datos.tallas.sobremedida ? { ...datos.tallas.sobremedida } : {};

    damaTallas = _extraerSobremedida(damaTallas, sobremedidaTallas);
    caballeroTallas = _extraerSobremedida(caballeroTallas, sobremedidaTallas);

    globalThis.tallasSeleccionadasProceso.dama = Object.keys(damaTallas);
    globalThis.tallasSeleccionadasProceso.caballero = Object.keys(caballeroTallas);
    globalThis.tallasSeleccionadasProceso.sobremedida = sobremedidaTallas;

    if (!globalThis.tallasCantidadesProceso) {
        globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
    }

    globalThis.tallasCantidadesProceso.dama = damaTallas;
    globalThis.tallasCantidadesProceso.caballero = caballeroTallas;
    globalThis.tallasCantidadesProceso.sobremedida = sobremedidaTallas;

    if (globalThis.actualizarResumenTallasProceso) {
        globalThis.actualizarResumenTallasProceso();
    }
}

function cargarDatosProcesoEnModalEdicion(tipo, datos) {
    _resetProcesoImagenes();
    _renderProcesoImagenes(datos);
    _cargarProcesoUbicaciones(datos);
    _cargarProcesoObservaciones(datos);
    _cargarProcesoTallas(datos);
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
 


