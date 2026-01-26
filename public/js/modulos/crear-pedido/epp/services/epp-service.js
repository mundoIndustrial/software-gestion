/**
 * EppService - Servicio principal que orquesta toda la lógica de EPP
 * Patrón: Facade + Orchestrator
 */

class EppService {
    constructor() {
        this.apiService = window.eppApiService;
        this.stateManager = window.eppStateManager;
        this.modalManager = null;
        this.itemManager = window.eppItemManager;
        this.imagenManager = null;
    }

    /**
     * Inicializar servicio
     */
    inicializar() {
        this.modalManager = new EppModalManager(this.stateManager);
        this.imagenManager = new EppImagenManager(this.apiService, this.stateManager, this.modalManager);

        // Exportar globalmente
        window.eppModalManager = this.modalManager;
        window.eppImagenManager = this.imagenManager;


    }

    /**
     * Abrir modal para agregar EPP
     */
    abrirModalAgregar() {
        this.stateManager.resetear();
        this.modalManager.limpiarFormulario();
        this.modalManager.abrirModal();

    }

    /**
     * Seleccionar producto EPP
     */
    seleccionarProducto(producto) {
        console.log('✨ [EppService] seleccionarProducto llamado:', producto);
        this.stateManager.setProductoSeleccionado(producto);
        console.log('✨ [EppService] Producto guardado en state');
        
        this.modalManager.mostrarProductoSeleccionado(producto);
        console.log('✨ [EppService] Mostrado en modal');
        
        this.modalManager.habilitarCampos();
        console.log('✨ [EppService] Campos habilitados');
    }

    /**
     * Editar EPP desde formulario (no guardado en BD)
     */
    editarEPPFormulario(id, nombre, cantidad, observaciones, imagenes) {
        // Asegurar que el modal existe en el DOM
        if (!document.getElementById('modal-agregar-epp')) {

            if (typeof window.EppModalTemplate !== 'undefined') {
                const modalHTML = window.EppModalTemplate.getHTML();
                document.body.insertAdjacentHTML('beforeend', modalHTML);

            }
        }

        this.stateManager.iniciarEdicion(id, false);
        this.stateManager.setProductoSeleccionado({ id, nombre, codigo, categoria });
        this.stateManager.guardarDatosItem(id, { id, nombre, codigo, categoria, cantidad, observaciones, imagenes });

        this.modalManager.mostrarProductoSeleccionado({ nombre, codigo, categoria });
        this.modalManager.cargarValoresFormulario(null, cantidad, observaciones);
        this.modalManager.mostrarImagenes(imagenes);
        this.modalManager.habilitarCampos();
        this.modalManager.abrirModal();


    }

    /**
     * Editar EPP desde BD
     */
    async editarEPPDesdeDB(eppId) {
        try {


            const epp = await this.apiService.obtenerEPP(eppId);

            this.stateManager.iniciarEdicion(eppId, true);
            this.stateManager.setProductoSeleccionado({
                id: epp.id,
                nombre: epp.nombre
            });

            this.modalManager.mostrarProductoSeleccionado({
                nombre: epp.nombre
            });
            this.modalManager.cargarValoresFormulario(null, epp.cantidad, epp.observaciones);
            this.modalManager.mostrarImagenes(epp.imagenes || []);
            this.modalManager.habilitarCampos();
            this.modalManager.abrirModal();


        } catch (error) {

            alert('Error al obtener EPP: ' + error.message);
        }
    }

    /**
     * Guardar EPP (agregar o actualizar)
     */
    async guardarEPP() {
        if (!this.modalManager.validarFormulario()) {
            return;
        }

        const producto = this.stateManager.getProductoSeleccionado();
        const valores = this.modalManager.obtenerValoresFormulario();
        const imagenes = this.stateManager.getImagenesSubidas();

        if (this.stateManager.isEditandoDesdeDB()) {
            await this._guardarEPPDesdeDB(valores);
        } else {
            this._guardarEPPFormulario(producto, valores, imagenes);
        }
    }

    /**
     * Guardar EPP desde BD
     */
    async _guardarEPPDesdeDB(valores) {
        try {
            const eppId = this.stateManager.getEppIdSeleccionado();
            const pedidoId = this.stateManager.getPedidoId();
            const imagenes = this.stateManager.getImagenesSubidas();

            // Agregar EPP al pedido
            const resultado = await this.apiService.agregarEPPAlPedido(
                pedidoId,
                eppId,
                valores.cantidad,
                valores.observaciones,
                imagenes
            );

            alert('EPP agregado al pedido correctamente');
            this.cerrarModal();
            this.stateManager.finalizarEdicion();

            // Recargar página
            location.reload();
        } catch (error) {

            alert('Error al guardar: ' + error.message);
        }
    }

    /**
     * Guardar EPP en formulario
     */
    _guardarEPPFormulario(producto, valores, imagenes) {
        try {
            const eppId = this.stateManager.getEditandoId();

            // Si estamos editando, eliminar item anterior
            if (eppId) {
                this.itemManager.eliminarItem(eppId);

                // Eliminar de window.itemsPedido
                if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
                    const index = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
                    if (index !== -1) {
                        window.itemsPedido.splice(index, 1);
                    }
                }
            }

            // Crear nuevo item
            this.itemManager.crearItem(
                producto.id,
                producto.nombre,
                producto.categoria,
                valores.cantidad,
                valores.observaciones,
                imagenes
            );

            // Configurar event listeners para el item
            if (typeof configurarEventListenersItem === 'function') {
                configurarEventListenersItem(producto.id);
            }

            // Crear objeto EPP
            const eppData = {
                tipo: 'epp',
                epp_id: producto.id,
                nombre: producto.nombre,
                categoria: producto.categoria,
                cantidad: valores.cantidad,
                observaciones: valores.observaciones,
                imagenes: imagenes
            };

            // Agregar a GestionItemsUI si está disponible (mantiene sincronización)
            if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPDesdeModal === 'function') {
                window.gestionItemsUI.agregarEPPDesdeModal(eppData);

            } else {
                // Fallback: agregar a window.itemsPedido si GestionItemsUI no está disponible
                if (!window.itemsPedido) {
                    window.itemsPedido = [];
                }
                window.itemsPedido.push(eppData);

            }



            this.cerrarModal();
            this.stateManager.finalizarEdicion();
        } catch (error) {

            alert('Error: ' + error.message);
        }
    }

    /**
     * Eliminar EPP
     */
    eliminarEPP(eppId) {
        if (!confirm('¿Eliminar este EPP?')) return;

        this.itemManager.eliminarItem(eppId);

        // Eliminar de window.itemsPedido
        if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
            const index = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
            if (index !== -1) {
                window.itemsPedido.splice(index, 1);
            }
        }


    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        this.modalManager.cerrarModal();
    }

    /**
     * Actualizar estado del botón
     */
    actualizarBoton() {
        this.modalManager.actualizarBoton();
    }

    /**
     * Filtrar EPP por término de búsqueda
     */
    async filtrarEPP(valor) {
        console.log('🔎 [EppService] filtrarEPP iniciado con valor:', valor);
        const container = document.getElementById('resultadosBuscadorEPP');
        console.log('🔎 [EppService] Contenedor encontrado:', !!container);
        
        if (!container) {
            console.warn('⚠️ [EppService] No se encontró el contenedor resultadosBuscadorEPP');
            return;
        }

        if (!valor || valor.trim() === '') {
            console.log('🔎 [EppService] Valor vacío, ocultando resultados');
            container.style.display = 'none';
            return;
        }

        try {
            console.log('🔎 [EppService] Llamando a _buscarEPPDesdeDB');
            // Buscar EPP desde la base de datos
            const epps = await this._buscarEPPDesdeDB(valor);
            console.log('🔎 [EppService] EPPs retornados:', epps.length);

            if (epps.length === 0) {
                container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron resultados para "${valor}"</div>`;
            } else {
                container.innerHTML = epps.map(epp => `
                    <div onclick="window.eppService.seleccionarProducto({id: ${epp.id}, nombre: '${epp.nombre}', imagen: '${epp.imagen || 'https://via.placeholder.com/80?text=EPP'}'}); document.getElementById('resultadosBuscadorEPP').style.display = 'none'; document.getElementById('inputBuscadorEPP').value = '';" 
                         style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s ease;"
                         onmouseover="this.style.background = '#f3f4f6';"
                         onmouseout="this.style.background = 'white';">
                        <div style="font-weight: 500; color: #1f2937;">${epp.nombre}</div>
                    </div>
                `).join('');
            }

            container.style.display = 'block';
        } catch (error) {

            container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #dc2626;">Error al buscar EPP</div>`;
            container.style.display = 'block';
        }
    }

    /**
     * Búsqueda de EPP desde la base de datos
     */
    async _buscarEPPDesdeDB(valor) {
        console.log('🔍 [EppService] _buscarEPPDesdeDB iniciado con término:', valor);
        try {
            const url = `/api/epp?q=${encodeURIComponent(valor)}`;
            console.log('🔍 [EppService] Realizando fetch a:', url);
            
            const response = await fetch(url);
            console.log('🔍 [EppService] Response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ [EppService] Error HTTP:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('✅ [EppService] Resultado JSON recibido:', result);
            console.log('✅ [EppService] Total EPPs encontrados:', result.data?.length || 0);
            
            return result.data && Array.isArray(result.data) ? result.data : [];
        } catch (error) {
            console.error('❌ [EppService] Error en _buscarEPPDesdeDB:', error.message);
            console.error('❌ [EppService] Stack:', error.stack);
            return [];
        }
    }

    /**
     * Cargar EPP disponibles
     */
    cargarEPP() {

        // Implementar carga de EPPs desde API
    }

    /**
     * Cargar categorías
     */
    cargarCategorias() {

        // Implementar carga de categorías desde API
    }

    /**
     * Limpiar modal
     */
    limpiarModal() {
        this.stateManager.resetear();
        this.modalManager.limpiarFormulario();

    }

    /**
     * Agregar EPP al pedido
     */
    agregarEPPAlPedido() {
        this.guardarEPP();
    }

    /**
     * Obtener estado actual (para debugging)
     */
    obtenerEstado() {
        return {
            state: this.stateManager.getEstado(),
            itemsCount: this.itemManager.contarItems()
        };
    }
}

// Exportar instancia global
window.eppService = new EppService();
