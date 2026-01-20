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
    constructor(options = {}) {
        this.prendaEditIndex = null;
        this.items = [];
        
        // Inicializar servicios con validación de disponibilidad
        try {
            this.notificationService = options.notificationService || (typeof NotificationService !== 'undefined' ? new NotificationService() : null);
            this.apiService = options.apiService || (typeof ItemAPIService !== 'undefined' ? new ItemAPIService() : null);
            this.formCollector = options.formCollector || (typeof ItemFormCollector !== 'undefined' ? new ItemFormCollector() : null);
            this.renderer = options.renderer || (typeof ItemRenderer !== 'undefined' && this.apiService ? new ItemRenderer({ apiService: this.apiService }) : null);
            this.prendaEditor = options.prendaEditor || (typeof PrendaEditor !== 'undefined' && this.notificationService ? new PrendaEditor({ notificationService: this.notificationService }) : null);
            
            // Solo inicializar si los servicios esenciales están disponibles
            if (this.formCollector && this.notificationService) {
                this.inicializar();
            } else {
                console.warn('[GestionItemsUI] Servicios esenciales no disponibles. Inicialización diferida.');
            }
        } catch (error) {
            console.error('[GestionItemsUI] Error en inicialización de servicios:', error);
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
                console.warn('[GestionItemsUI.cargarItems] Servicios no disponibles');
                return;
            }
            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items;
            await this.renderer.actualizar(this.items);
        } catch (error) {
            console.error('Error al cargar ítems:', error);
            if (this.notificationService) {
                this.notificationService.error('Error al cargar ítems');
            }
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                console.warn('[GestionItemsUI.agregarItem] Servicios no disponibles');
                return false;
            }
            const resultado = await this.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.items = resultado.items;
                await this.renderer.actualizar(this.items);
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
        if (!confirm('¿Eliminar este ítem?')) return;

        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                console.warn('[GestionItemsUI.eliminarItem] Servicios no disponibles');
                return;
            }
            const resultado = await this.apiService.eliminarItem(index);
            if (resultado.success) {
                this.items = resultado.items;
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem eliminado');
            }
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    abrirModalSeleccionPrendas() {
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        this.prendaEditor.abrirModal(esEdicion, this.prendaEditIndex);
    }

    /**
     * Cargar datos de prenda en el modal para editar
     */
    cargarItemEnModal(prenda, prendaIndex) {
        this.prendaEditIndex = prendaIndex;
        this.prendaEditor.cargarPrendaEnModal(prenda, prendaIndex);
    }

    /**
     * Agregar prenda nueva
     */
    async agregarPrendaNueva() {
        await this.prendaEditor.agregarPrendaNueva();
        this.prendaEditIndex = this.prendaEditor.obtenerPrendaEditIndex();
    }

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
            if (!this.formCollector || !this.apiService || !this.notificationService) {
                console.warn('[GestionItemsUI.manejarSubmitFormulario] Servicios no disponibles');
                return;
            }
            
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.notificationService.error('El cliente es requerido');
                clienteInput?.focus();
                return;
            }

            const pedidoData = this.formCollector.recolectarDatosPedido();
            
            console.log('[manejarSubmitFormulario] Datos recolectados:', pedidoData);

            if (!pedidoData.items || pedidoData.items.length === 0) {
                this.notificationService.error('Debe agregar al menos un item al pedido');
                return;
            }

            const validacion = await this.apiService.validarPedido(pedidoData);
            if (!validacion.valid) {
                alert('Errores en el pedido:\n' + validacion.errores.join('\n'));
                return;
            }

            const resultado = await this.apiService.crearPedido(pedidoData);

            if (resultado.success) {
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                setTimeout(() => this.mostrarModalExito(), 500);
            }
        } catch (error) {
            console.error('[manejarSubmitFormulario] ERROR:', error);
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    recolectarDatosPedido() {
        return this.formCollector.recolectarDatosPedido();
    }

    mostrarVistaPreviaFactura() {
        if (!this.renderer) {
            console.warn('[GestionItemsUI.mostrarVistaPreviaFactura] Servicio renderer no disponible');
            return;
        }
        this.renderer.mostrarVistaPreviaFactura();
    }

    mostrarModalExito() {
        let modalElement = document.getElementById('modalExitoPedido');
        if (!modalElement) {
            document.body.insertAdjacentHTML('beforeend', MODAL_EXITO_PEDIDO_HTML);
            modalElement = document.getElementById('modalExitoPedido');
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        if (btnVolverAPedidos) {
            btnVolverAPedidos.onclick = () => window.location.href = '/asesores/pedidos';
        }

        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    renderizarProcesosDirectos() {
        this.renderer.renderizarProcesosDirectos();
    }
}

/**
 * Wrapper para editarProceso que funciona en contexto de edición
 */
window.editarProcesoEdicion = function(tipo) {
    console.log(` Editando proceso en contexto de edición: ${tipo}`);
    
    // Asegurar que el modal de proceso esté encima
    const modalProceso = document.getElementById('modal-proceso-generico');
    if (modalProceso) {
        modalProceso.style.zIndex = '10000';
        console.log('    Z-index del modal de proceso establecido a 10000');
    }
    
    // Usar la función global si existe
    if (window.editarProceso && window.editarProceso !== window.editarProcesoEdicion) {
        window.editarProceso(tipo);
        return;
    }
    
    // Si no existe, intentar abrir el modal genérico
    if (window.abrirModalProcesoGenerico) {
        window.abrirModalProcesoGenerico(tipo);
        
        // Asegurar nuevamente que el z-index esté alto después de abrir
        if (modalProceso) {
            setTimeout(() => {
                modalProceso.style.zIndex = '10000';
            }, 100);
        }
        
        // Cargar datos en el modal
        const proceso = window.procesosSeleccionados?.[tipo];
        if (proceso?.datos) {
            cargarDatosProcesoEnModalEdicion(tipo, proceso.datos);
        }
    } else {
        console.error(' No se pudo abrir el modal de proceso');
    }
};

/**
 * Cargar datos de un proceso en el modal para editar (fallback)
 */
function cargarDatosProcesoEnModalEdicion(tipo, datos) {
    console.log(` Cargando datos en modal para editar:`, datos);
    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];
    }
    
    // Cargar imágenes
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                const imgUrl = img instanceof File ? URL.createObjectURL(img) : img;
                const htmlImg = IMAGEN_PROCESO_EDICION_TEMPLATE
                    .replace('{{imgUrl}}', imgUrl)
                    .replace('{{indice}}', indice);
                preview.innerHTML = htmlImg;
            }
            
            if (window.imagenesProcesoActual) {
                window.imagenesProcesoActual[idx] = img;
            }
        }
    });
    
    // Cargar ubicaciones
    if (datos.ubicaciones && window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas.length = 0;
        window.ubicacionesProcesoSeleccionadas.push(...datos.ubicaciones);
        if (window.renderizarListaUbicaciones) {
            window.renderizarListaUbicaciones();
        }
    }
    
    // Cargar observaciones
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
    
    // Cargar tallas
    if (datos.tallas && window.tallasSeleccionadasProceso) {
        window.tallasSeleccionadasProceso.dama = datos.tallas.dama || [];
        window.tallasSeleccionadasProceso.caballero = datos.tallas.caballero || [];
        if (window.actualizarResumenTallasProceso) {
            window.actualizarResumenTallasProceso();
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionItemsUI = new GestionItemsUI();
});
 