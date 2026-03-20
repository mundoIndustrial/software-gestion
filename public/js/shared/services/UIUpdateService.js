/**
 * UIUpdateService - DDD Service Layer
 * Maneja todas las actualizaciones visuales de tablas y filas
 * Responsabilidad única: Actualizar DOM
 * 
 * Uso: window.shared.uiUpdate.updateRow(fila, pedido)
 */

class UIUpdateService {
    constructor(debug = false) {
        this.debug = debug;
    }

    /**
     * Actualizar una fila con datos de pedido
     */
    updateRow(fila, pedido) {
        if (!fila) return;
        
        const celdas = fila.querySelectorAll('[style*="display: flex"]');
        if (celdas.length >= 8) {
            // Estado (índice 1)
            const celdaEstado = celdas[1];
            const estadoActual = celdaEstado.textContent.trim();
            if (estadoActual !== pedido.estado) {
                celdaEstado.textContent = pedido.estado;
                this.highlightChange(fila);
            }
            
            // Novedades (índice 5)
            if (celdas.length > 5) {
                const celdaNovedades = celdas[5];
                if (pedido.novedades) {
                    const conteo = (pedido.novedades.match(/\n/g) || []).length + 1;
                    celdaNovedades.textContent = conteo > 0 ? `${conteo} novedades` : 'Sin novedades';
                } else {
                    celdaNovedades.textContent = 'Sin novedades';
                }
            }
        }
    }

    /**
     * Resaltar cambios en la fila
     */
    highlightChange(fila) {
        fila.style.background = '#fef3c7';
        fila.style.transition = 'all 0.3s ease';
        setTimeout(() => {
            fila.style.background = '';
            fila.style.transition = 'background-color 0.5s ease-out';
        }, 2000);
    }

    /**
     * Mostrar indicador de conexión
     */
    showConnectionIndicator(type, status) {
        let indicator = document.querySelector('.realtime-connection-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'realtime-connection-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: bold;
                z-index: 9999;
                transition: all 0.3s ease;
            `;
            document.body.appendChild(indicator);
        }
        
        indicator.textContent = type;
        indicator.className = `realtime-connection-indicator ${status}`;
        
        const colors = {
            success: { bg: '#22c55e', color: 'white' },
            warning: { bg: '#f59e0b', color: 'white' },
            error: { bg: '#ef4444', color: 'white' }
        };
        
        const style = colors[status] || colors.error;
        indicator.style.background = style.bg;
        indicator.style.color = style.color;
        
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => indicator.remove(), 300);
        }, 3000);
    }

    /**
     * Mostrar toast de notificación
     */
    showRealtimeToast(message, type = 'info') {
        try {
            const colors = {
                success: '#16a34a',
                error: '#dc2626',
                warning: '#f59e0b',
                info: '#2563eb'
            };
            
            const bg = colors[type] || colors.info;
            
            const container = this.getToastContainer();
            const toast = document.createElement('div');
            toast.style.cssText = `
                background: ${bg};
                color: white;
                padding: 12px 14px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.18);
                font-size: 13px;
                font-weight: 600;
                max-width: 360px;
                transform: translateX(120%);
                transition: transform 0.25s ease;
            `;
            toast.textContent = message;
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.transform = 'translateX(0)';
            });

            setTimeout(() => {
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => toast.remove(), 250);
            }, 3500);
        } catch (e) {
            console.error('[UIUpdateService] Toast error:', e);
        }
    }

    /**
     * Obtener o crear contenedor de toasts
     */
    getToastContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 10px;';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Ocultar indicador de conexión
     */
    hideConnectionIndicator() {
        const indicator = document.querySelector('.realtime-connection-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
}

// Exportar como servicio global
if (!window.shared) window.shared = {};
window.shared.uiUpdate = new UIUpdateService();
