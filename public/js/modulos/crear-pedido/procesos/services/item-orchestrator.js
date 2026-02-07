/**
 * ItemOrchestrator - Orquestador Principal
 * 
 * Responsabilidad única: Coordinar todos los servicios (inyección de dependencias)
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo coordina/orquesta
 * - DIP: Inyecta todas las dependencias
 * - OCP: Fácil agregar nuevos servicios
 * - LSP: Todos los servicios son intercambiables
 */
class ItemOrchestrator {
    constructor(opciones = {}) {
        // Inyección de dependencias
        this.apiService = opciones.apiService || new ItemAPIService();
        this.validator = opciones.validator || new ItemValidator();
        this.renderer = opciones.renderer || new ItemRenderer({ 
            apiService: this.apiService 
        });
        this.notificationService = opciones.notificationService || new NotificationService();
        this.formCollector = opciones.formCollector || new ItemFormCollector();
        // ✅ MIGRADO a PrendaEditorOrchestrator (frontend puro, sin lógica de negocio)
        this.prendaEditor = opciones.prendaEditor || (typeof PrendaEditorOrchestrator !== 'undefined' ? new PrendaEditorOrchestrator({
            api: new PrendaAPI(),
            eventBus: new PrendaEventBus(),
            domAdapter: new PrendaDOMAdapter(),
            notificationService: this.notificationService
        }) : null);

        // Estado interno
        this.items = [];
        this.inicializar();
    }

    /**
     * Inicializar el orquestador
     */
    inicializar() {
        this.attachEventListeners();
        this.cargarItems();
    }

    /**
     * Adjuntar event listeners
     * @private
     */
    attachEventListeners() {
        // Agregar ítem desde cotización
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        // Agregar prenda nueva
        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.prendaEditor.abrirModal(false));

        // Vista previa
        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        // Formulario de creación
        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    /**
     * Cargar ítems desde el servidor
     */
    async cargarItems() {
        try {
            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items || [];
            await this.renderer.actualizar(this.items);
        } catch (error) {
            this.notificationService.error('Error al cargar ítems: ' + error.message);

        }
    }

    /**
     * Agregar nuevo ítem
     */
    async agregarItem(itemData) {
        try {
            // Validar item
            const validacion = this.validator.validarItem(itemData);
            if (!validacion.válido) {
                validacion.errores.forEach(err => this.notificationService.error(err));
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
            this.notificationService.error('Error: ' + error.message);
            return false;
        }
    }

    /**
     * Eliminar ítem
     */
    async eliminarItem(index) {
        if (!confirm('¿Eliminar este ítem?')) {
            return;
        }

        try {
            const resultado = await this.apiService.eliminarItem(index);
            
            if (resultado.success) {
                this.items = resultado.items;
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem eliminado');
            }
        } catch (error) {
            this.notificationService.error('Error: ' + error.message);
        }
    }

    /**
     * Manejar submit del formulario
     */
    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            // Validar cliente
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.notificationService.error('El cliente es requerido');
                clienteInput?.focus();
                return;
            }

            // Recolectar datos del pedido
            const pedidoData = this.formCollector.recolectarDatosPedido();
            
            // Validar pedido
            const validacion = this.validator.validarPedido(pedidoData);
            
            if (!validacion.válido) {
                const errores = validacion.errores.join('\n');
                alert('Errores en el pedido:\n' + errores);
                return;
            }

            this.notificationService.info('Validación pasada, enviando pedido...');

            // Crear pedido
            const resultado = await this.apiService.crearPedido(pedidoData);

            if (resultado.success) {
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                
                this.notificationService.exito('¡Pedido creado exitosamente!');
                
                setTimeout(() => {
                    this.mostrarModalExito();
                }, 500);
            }
        } catch (error) {
            this.notificationService.error('Error: ' + error.message);

        }
    }

    /**
     * Mostrar vista previa de factura
     */
    mostrarVistaPreviaFactura() {
        const pedidoData = this.formCollector.recolectarDatosPedido();
        this.renderer.renderizarVistaPreviaFactura(pedidoData);
    }

    /**
     * Mostrar modal de éxito
     * @private
     */
    mostrarModalExito() {
        let modalElement = document.getElementById('modalExitoPedido');
        
        if (!modalElement) {
            document.body.insertAdjacentHTML('beforeend', this.obtenerHTMLModalExito());
            modalElement = document.getElementById('modalExitoPedido');
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        if (btnVolverAPedidos) {
            btnVolverAPedidos.onclick = () => {
                window.location.href = '/asesores/pedidos';
            };
        }

        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Obtener HTML del modal de éxito
     * @private
     */
    obtenerHTMLModalExito() {
        return `
            <div id="modalExitoPedido" style="display: none; position: fixed; top: 0; left: 0; 
                 width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); 
                 display: flex; align-items: center; justify-content: center; z-index: 10000;">
                <div style="background-color: white; border-radius: 8px; padding: 40px; 
                           text-align: center; max-width: 500px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;">✓</div>
                    <h2 style="color: #333; margin: 0 0 10px 0;">¡Pedido Creado Exitosamente!</h2>
                    <p style="color: #666; margin: 0 0 30px 0;">Tu pedido ha sido registrado en el sistema.</p>
                    <button id="btnVolverAPedidos" style="background-color: #4CAF50; color: white; 
                           border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer; 
                           font-size: 14px; font-weight: 600;">
                        Volver a Pedidos
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Abrir modal de selección de prendas (delegación)
     */
    abrirModalSeleccionPrendas() {
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    /**
     * Obtener todos los items
     */
    obtenerItems() {
        return this.items;
    }

    /**
     * Obtener servicio de API (para acceso directo si es necesario)
     */
    obtenerAPIService() {
        return this.apiService;
    }

    /**
     * Obtener servicio de validación
     */
    obtenerValidator() {
        return this.validator;
    }

    /**
     * Obtener editor de prendas
     */
    obtenerPrendaEditor() {
        return this.prendaEditor;
    }

    /**
     * Obtener servicio de notificaciones
     */
    obtenerNotificationService() {
        return this.notificationService;
    }
}

window.ItemOrchestrator = ItemOrchestrator;
