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

        console.log('[EppService] Servicio inicializado');
    }

    /**
     * Abrir modal para agregar EPP
     */
    abrirModalAgregar() {
        this.stateManager.resetear();
        this.modalManager.limpiarFormulario();
        this.modalManager.abrirModal();
        console.log('[EppService] Modal abierto para agregar');
    }

    /**
     * Seleccionar producto EPP
     */
    seleccionarProducto(producto) {
        this.stateManager.setProductoSeleccionado(producto);
        this.modalManager.mostrarProductoSeleccionado(producto);
        this.modalManager.habilitarCampos();
    }

    /**
     * Editar EPP desde formulario (no guardado en BD)
     */
    editarEPPFormulario(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
        // Asegurar que el modal existe en el DOM
        if (!document.getElementById('modal-agregar-epp')) {
            console.log('[EppService] Modal no encontrado, inyectando...');
            if (typeof window.EppModalTemplate !== 'undefined') {
                const modalHTML = window.EppModalTemplate.getHTML();
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                console.log('[EppService] Modal inyectado al DOM');
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

        console.log('[EppService] EPP formulario abierto para editar:', id);
    }

    /**
     * Editar EPP desde BD
     */
    async editarEPPDesdeDB(eppId) {
        try {
            console.log('[EppService] Obteniendo EPP desde BD:', eppId);

            const epp = await this.apiService.obtenerEPP(eppId);

            this.stateManager.iniciarEdicion(eppId, true);
            this.stateManager.setProductoSeleccionado({
                id: epp.id,
                nombre: epp.nombre,
                codigo: epp.codigo,
                categoria: epp.categoria
            });

            this.modalManager.mostrarProductoSeleccionado({
                nombre: epp.nombre,
                codigo: epp.codigo,
                categoria: epp.categoria
            });
            this.modalManager.cargarValoresFormulario(null, epp.cantidad, epp.observaciones);
            this.modalManager.mostrarImagenes(epp.imagenes || []);
            this.modalManager.habilitarCampos();
            this.modalManager.abrirModal();

            console.log('[EppService] EPP BD abierto para editar:', eppId);
        } catch (error) {
            console.error('[EppService] Error editando EPP desde BD:', error);
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
            const eppId = this.stateManager.getEditandoId();
            console.log('[EppService] Guardando EPP en BD:', eppId);

            await this.apiService.actualizarEPP(eppId, {
                cantidad: valores.cantidad,
                observaciones: valores.observaciones
            });

            alert('EPP actualizado correctamente');
            this.cerrarModal();
            this.stateManager.finalizarEdicion();

            // Recargar página
            location.reload();
        } catch (error) {
            console.error('[EppService] Error guardando EPP en BD:', error);
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
                producto.codigo,
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
                codigo: producto.codigo,
                categoria: producto.categoria,
                cantidad: valores.cantidad,
                observaciones: valores.observaciones,
                imagenes: imagenes
            };

            // Agregar a GestionItemsUI si está disponible (mantiene sincronización)
            if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarEPPDesdeModal === 'function') {
                window.gestionItemsUI.agregarEPPDesdeModal(eppData);
                console.log('[EppService] EPP agregado a GestionItemsUI');
            } else {
                // Fallback: agregar a window.itemsPedido si GestionItemsUI no está disponible
                if (!window.itemsPedido) {
                    window.itemsPedido = [];
                }
                window.itemsPedido.push(eppData);
                console.log('[EppService] EPP guardado en window.itemsPedido (fallback)');
            }

            console.log('[EppService] EPP guardado en formulario');

            this.cerrarModal();
            this.stateManager.finalizarEdicion();
        } catch (error) {
            console.error('[EppService] Error guardando EPP:', error);
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

        console.log('[EppService] EPP eliminado:', eppId);
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
        const container = document.getElementById('resultadosBuscadorEPP');
        if (!container) return;

        if (!valor || valor.trim() === '') {
            container.style.display = 'none';
            return;
        }

        try {
            // Buscar EPP desde la base de datos
            const epps = await this._buscarEPPDesdeDB(valor);

            if (epps.length === 0) {
                container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #6b7280;">No se encontraron resultados para "${valor}"</div>`;
            } else {
                container.innerHTML = epps.map(epp => `
                    <div onclick="window.eppService.seleccionarProducto({id: ${epp.id}, nombre: '${epp.nombre}', codigo: '${epp.codigo}', categoria: '${epp.categoria}'}); document.getElementById('resultadosBuscadorEPP').style.display = 'none'; document.getElementById('inputBuscadorEPP').value = '';" 
                         style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; transition: background 0.2s ease;"
                         onmouseover="this.style.background = '#f3f4f6';"
                         onmouseout="this.style.background = 'white';">
                        <div style="font-weight: 500; color: #1f2937;">${epp.nombre}</div>
                        <div style="font-size: 0.8rem; color: #6b7280;">${epp.codigo} - ${epp.categoria}</div>
                    </div>
                `).join('');
            }

            container.style.display = 'block';
        } catch (error) {
            console.error('[EppService] Error filtrando EPP:', error);
            container.innerHTML = `<div style="padding: 1rem; text-align: center; color: #dc2626;">Error al buscar EPP</div>`;
            container.style.display = 'block';
        }
    }

    /**
     * Búsqueda de EPP desde la base de datos
     */
    async _buscarEPPDesdeDB(valor) {
        try {
            const response = await fetch(`/api/epp?q=${encodeURIComponent(valor)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            return result.data && Array.isArray(result.data) ? result.data : [];
        } catch (error) {
            console.error('[EppService] Error en búsqueda desde BD:', error);
            return [];
        }
    }

    /**
     * Cargar EPP disponibles
     */
    cargarEPP() {
        console.log('[EppService] Cargando EPPs disponibles');
        // Implementar carga de EPPs desde API
    }

    /**
     * Cargar categorías
     */
    cargarCategorias() {
        console.log('[EppService] Cargando categorías');
        // Implementar carga de categorías desde API
    }

    /**
     * Limpiar modal
     */
    limpiarModal() {
        this.stateManager.resetear();
        this.modalManager.limpiarFormulario();
        console.log('[EppService] Modal limpiado');
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
