/**
 * JavaScript Helper para gestionar áreas de pedidos logo
 * 
 * Ubicación: public/js/asesores/pedido-logo-area-manager.js
 */

class PedidoLogoAreaManager {
    constructor() {
        this.areas = [
            'Creacion de orden',
            'pendiente_confirmar_diseño',
            'en_diseño',
            'logo',
            'estampado'
        ];
        this.logoPedidoId = null;
        this.csrfToken = document.querySelector('input[name="_token"]')?.value;
    }

    /**
     * Obtener todas las áreas disponibles
     */
    async obtenerAreas() {
        try {
            const response = await fetch('/asesores/pedidos-logo/areas/disponibles', {
                headers: {
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();
            if (data.success) {
                this.areas = data.areas;
            }
            return this.areas;
        } catch (error) {
            console.error('Error obtener áreas:', error);
            return this.areas;
        }
    }

    /**
     * Cambiar el área de un pedido logo
     */
    async cambiarArea(logoPedidoId, nuevaArea, observaciones = '') {
        try {
            const response = await fetch(`/asesores/pedidos-logo/${logoPedidoId}/cambiar-area`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    area: nuevaArea,
                    observaciones: observaciones
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log(' Área cambiada:', data.area);
                
                // Mostrar notificación
                this.mostrarNotificacion('success', `Área actualizada: ${nuevaArea}`);
                
                // Recargar la tabla
                this.recargarTabla();
                
                return data;
            } else {
                this.mostrarNotificacion('error', data.message);
                return null;
            }
        } catch (error) {
            console.error('Error cambiar área:', error);
            this.mostrarNotificacion('error', 'Error al cambiar el área');
            return null;
        }
    }

    /**
     * Obtener el historial de áreas de un pedido logo
     */
    async obtenerHistorial(logoPedidoId) {
        try {
            const response = await fetch(`/asesores/pedidos-logo/${logoPedidoId}/historial`, {
                headers: {
                    'Accept': 'application/json',
                }
            });
            const data = await response.json();

            if (data.success) {
                return {
                    numero_pedido: data.numero_pedido,
                    area_actual: data.area_actual,
                    historial: data.historial
                };
            }
            return null;
        } catch (error) {
            console.error('Error obtener historial:', error);
            return null;
        }
    }

    /**
     * Mostrar modal para cambiar área
     */
    async mostrarModalCambiarArea(logoPedidoId) {
        this.logoPedidoId = logoPedidoId;
        
        // Obtener datos actuales
        const historial = await this.obtenerHistorial(logoPedidoId);
        if (!historial) {
            this.mostrarNotificacion('error', 'No se pudo obtener los datos del pedido');
            return;
        }

        const areas = await this.obtenerAreas();
        
        // Crear HTML del modal
        const html = `
            <div style="padding: 1.5rem;">
                <h3 style="margin-top: 0; color: #1e40af; margin-bottom: 1.5rem;">
                    Cambiar Área - Pedido ${historial.numero_pedido}
                </h3>

                <div style="background: #f0f7ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #3b82f6;">
                    <p style="margin: 0; color: #1e40af; font-weight: 600;">
                        Área Actual: <span style="color: #0066cc;">${historial.area_actual}</span>
                    </p>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                        Seleccionar Nueva Área:
                    </label>
                    <select id="selectArea" style="
                        width: 100%;
                        padding: 0.75rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 1rem;
                        cursor: pointer;
                    ">
                        <option value="">-- Selecciona una área --</option>
                        ${areas.map(area => `<option value="${area}">${this.formatearArea(area)}</option>`).join('')}
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                        Observaciones (Opcional):
                    </label>
                    <textarea id="observacionesArea" placeholder="Escribe observaciones..." style="
                        width: 100%;
                        padding: 0.75rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 0.95rem;
                        min-height: 80px;
                        font-family: inherit;
                        resize: vertical;
                    "></textarea>
                </div>

                <div style="background: #fff7ed; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #f97316;">
                    <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                        <strong> Nota:</strong> Se registrará quién y cuándo hizo el cambio.
                    </p>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button id="btnCancelar" style="
                        padding: 0.75rem 1.5rem;
                        border: 1px solid #d1d5db;
                        background: white;
                        border-radius: 6px;
                        cursor: pointer;
                        font-weight: 600;
                        color: #374151;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                        Cancelar
                    </button>
                    <button id="btnGuardarArea" style="
                        padding: 0.75rem 1.5rem;
                        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                        color: white;
                        border: none;
                        border-radius: 6px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none'; this.style.transform=''">
                        Guardar Cambio
                    </button>
                </div>

                <hr style="margin-top: 1.5rem; border: none; border-top: 1px solid #e5e7eb;">

                <div style="margin-top: 1.5rem;">
                    <h4 style="color: #1e40af; margin-top: 0; margin-bottom: 1rem;">Historial (últimas 5)</h4>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1rem;">
                        ${historial.historial.slice(0, 5).map(h => `
                            <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem;">
                                <p style="margin: 0; color: #374151; font-weight: 600;">${this.formatearArea(h.area)}</p>
                                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.8rem;">${h.fecha_entrada} - ${h.usuario}</p>
                                ${h.observaciones ? `<p style="margin: 0.5rem 0 0 0; color: #6b7280; font-style: italic;">"${h.observaciones}"</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;

        // Usar Swal para el modal
        Swal.fire({
            html: html,
            didOpen: () => {
                document.getElementById('btnCancelar').addEventListener('click', () => Swal.close());
                document.getElementById('btnGuardarArea').addEventListener('click', () => this.guardarCambioArea());
            }
        });
    }

    /**
     * Guardar cambio de área
     */
    async guardarCambioArea() {
        const selectArea = document.getElementById('selectArea');
        const observaciones = document.getElementById('observacionesArea').value;

        if (!selectArea.value) {
            alert('Por favor selecciona un área');
            return;
        }

        const resultado = await this.cambiarArea(this.logoPedidoId, selectArea.value, observaciones);
        
        if (resultado) {
            Swal.close();
        }
    }

    /**
     * Recargar la tabla de pedidos
     */
    recargarTabla() {
        // Recargar la página
        window.location.reload();
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificacion(tipo, mensaje) {
        Swal.fire({
            icon: tipo === 'success' ? 'success' : 'error',
            title: tipo === 'success' ? '¡Éxito!' : 'Error',
            text: mensaje,
            timer: 2000,
            showConfirmButton: false
        });
    }

    /**
     * Formatear el nombre del área para mostrar
     */
    formatearArea(area) {
        const mapa = {
            'Creacion de orden': ' Creación de Orden',
            'pendiente_confirmar_diseño': '⏳ Pendiente Confirmar Diseño',
            'en_diseño': ' En Diseño',
            'logo': ' Logo (Producción)',
            'estampado': '🖨️ Estampado'
        };
        return mapa[area] || area;
    }
}

// Instancia global
const areaManager = new PedidoLogoAreaManager();
