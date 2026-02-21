/**
 * Sistema para mostrar historial de procesos de una orden
 */

class HistorialProcesos {
    constructor() {
        this.baseRoute = '/registros';
    }

    /**
     * Obtener todos los procesos y historial de una orden
     */
    async obtenerHistorial(numeroPedido) {
        try {
            const response = await fetch(`/api/procesos/historial/${numeroPedido}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {

            return null;
        }
    }

    /**
     * Mostrar modal con historial de procesos
     */
    async mostrarHistorial(numeroPedido) {
        const datos = await this.obtenerHistorial(numeroPedido);

        if (!datos?.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el historial de procesos',
                confirmButtonColor: '#ef4444',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                }
            });
            return;
        }

        const { procesos_actuales, historial } = datos;

        // Construir HTML del historial
        let html = '<div class="historial-container">';

        // Procesos actuales
        if (procesos_actuales && procesos_actuales.length > 0) {
            html += `
                <div class="procesos-actuales" style="margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #15803d;">📌 Procesos Actuales</h3>
                    <div style="display: grid; gap: 10px;">
            `;

            procesos_actuales.forEach((proceso) => {
                html += `
                    <div style="padding: 12px; background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 4px;">
                        <p style="margin: 0 0 8px 0; font-weight: 600; color: #15803d;">${proceso.proceso}</p>
                        <div style="font-size: 13px; color: #555;">
                            <p style="margin: 3px 0;"><strong>Fecha Inicio:</strong> ${this.formatearFecha(proceso.fecha_inicio)}</p>
                            <p style="margin: 3px 0;"><strong>Encargado:</strong> ${proceso.encargado || 'N/A'}</p>
                            <p style="margin: 3px 0;"><strong>Estado:</strong> <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 3px; font-size: 11px;">${proceso.estado_proceso}</span></p>
                            <p style="margin: 3px 0; color: #999;"><small>Actualizado: ${this.formatearFechaHora(proceso.updated_at)}</small></p>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        // Historial de cambios
        if (historial && historial.length > 0) {
            html += `
                <div class="historial-cambios">
                    <h3 style="margin-top: 0; margin-bottom: 15px; color: #1f2937;"> Historial de Cambios</h3>
                    <div style="max-height: 300px; overflow-y: auto;">
            `;

            historial.forEach((proceso, index) => {
                html += `
                    <div style="margin-bottom: 12px; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <p style="margin: 0 0 8px 0; font-weight: 500; color: #1f2937;">
                            #${index + 1} - ${proceso.proceso}
                        </p>
                        <div style="font-size: 13px; color: #666;">
                            <p style="margin: 3px 0;"><strong>Fecha:</strong> ${this.formatearFechaHora(proceso.created_at)}</p>
                            <p style="margin: 3px 0;"><strong>Duración:</strong> ${this.calcularDiferencia(proceso.created_at, proceso.updated_at)}</p>
                            <p style="margin: 3px 0;"><strong>Encargado:</strong> ${proceso.encargado || 'N/A'}</p>
                            <p style="margin: 3px 0;"><strong>Estado:</strong> <span style="background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 3px; font-size: 12px;">${proceso.estado_proceso}</span></p>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        } else {
            html += '<p style="color: #999; font-style: italic;">No hay cambios previos registrados</p>';
        }

        html += '</div>';

        // Mostrar modal
        Swal.fire({
            title: `Procesos y Historial - Pedido ${numeroPedido}`,
            html: html,
            width: '650px',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Cerrar',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
    }

    /**
     * Formatear fecha
     */
    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (e) {
            return fecha;
        }
    }

    /**
     * Formatear fecha y hora
     */
    formatearFechaHora(fecha) {
        if (!fecha) return 'N/A';
        try {
            return new Date(fecha).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return fecha;
        }
    }

    /**
     * Calcular diferencia entre dos fechas
     */
    calcularDiferencia(fechaInicio, fechaFin) {
        try {
            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);
            const diferencia = fin - inicio;
            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

            if (dias > 0) {
                return `${dias}d ${horas}h`;
            } else if (horas > 0) {
                return `${horas}h`;
            } else {
                return 'Menos de 1h';
            }
        } catch (e) {
            return 'N/A';
        }
    }
}

// Inicializar instancia global
window.historialProcesos = new HistorialProcesos();



