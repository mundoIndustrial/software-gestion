/**
 * Gestión de Ítems - Capa de Presentación
 * Solo maneja eventos UI y actualización de vistas
 * Toda la lógica de negocio está en el backend
 */

class GestionItemsUI {
    constructor() {
        this.api = window.pedidosAPI;
        this.items = [];
        this.inicializar();
    }

    inicializar() {
        this.attachEventListeners();
        this.cargarItems();
    }

    attachEventListeners() {
        // Agregar ítem desde cotización
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        // Agregar ítem nuevo
        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        // Formulario de creación
        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    async cargarItems() {
        try {
            const resultado = await this.api.obtenerItems();
            this.items = resultado.items;
            this.actualizarVistaItems();
        } catch (error) {
            console.error('Error al cargar ítems:', error);
        }
    }

    async agregarItem(itemData) {
        try {
            const resultado = await this.api.agregarItem(itemData);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem agregado correctamente', 'success');
                return true;
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
            return false;
        }
    }

    async eliminarItem(index) {
        if (!confirm('¿Eliminar este ítem?')) {
            return;
        }

        try {
            const resultado = await this.api.eliminarItem(index);
            
            if (resultado.success) {
                this.items = resultado.items;
                this.actualizarVistaItems();
                this.mostrarNotificacion('Ítem eliminado', 'success');
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    actualizarVistaItems() {
        const container = document.getElementById('lista-items-pedido');
        const mensajeSinItems = document.getElementById('mensaje-sin-items');

        if (!container) return;

        if (this.items.length === 0) {
            container.innerHTML = '';
            if (mensajeSinItems) mensajeSinItems.style.display = 'block';
            return;
        }

        if (mensajeSinItems) mensajeSinItems.style.display = 'none';

        container.innerHTML = this.items.map((item, index) => this.renderizarItem(item, index)).join('');

        // Reattach event listeners
        document.querySelectorAll('.btn-eliminar-item').forEach((btn, idx) => {
            btn.addEventListener('click', () => this.eliminarItem(idx));
        });
    }

    renderizarItem(item, index) {
        const prenda = item.prenda?.nombre || 'Sin nombre';
        const origen = item.origen || 'bodega';
        const procesos = item.procesos?.join(', ') || 'Ninguno';

        return `
            <div class="item-pedido" style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h4 style="margin: 0 0 0.5rem 0; font-weight: 600; color: #1e40af;">${prenda}</h4>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem; color: #6b7280;">
                            <strong>Origen:</strong> ${origen}
                        </p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem; color: #6b7280;">
                            <strong>Procesos:</strong> ${procesos}
                        </p>
                    </div>
                    <button type="button" class="btn-eliminar-item" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                        Eliminar
                    </button>
                </div>
            </div>
        `;
    }

    abrirModalSeleccionPrendas() {
        // Delegar a modal-seleccion-prendas.js
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        // Delegar a modal correspondiente
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            // Validar pedido
            const validacion = await this.api.validarPedido();
            
            if (!validacion.valid) {
                const errores = validacion.errores.join('\n');
                alert('Errores en el pedido:\n' + errores);
                return;
            }

            // Recolectar datos del formulario
            const pedidoData = this.recolectarDatosPedido();

            // Crear pedido
            const resultado = await this.api.crearPedido(pedidoData);

            if (resultado.success) {
                this.mostrarNotificacion('Pedido creado correctamente', 'success');
                setTimeout(() => {
                    window.location.href = '/asesores/pedidos-produccion';
                }, 1500);
            }
        } catch (error) {
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        }
    }

    recolectarDatosPedido() {
        return {
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
        };
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        const clase = tipo === 'error' ? 'alert-danger' : tipo === 'success' ? 'alert-success' : 'alert-info';
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert ${clase}`;
        notificacion.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 6px;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notificacion.textContent = mensaje;

        document.body.appendChild(notificacion);

        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionItemsUI = new GestionItemsUI();
});
