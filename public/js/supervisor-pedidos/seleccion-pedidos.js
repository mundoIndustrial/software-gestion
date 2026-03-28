/**
 * =====================================================
 * SUPERVISOR PEDIDOS - SELECCIÓN DE PEDIDOS
 * =====================================================
 * Maneja la selección múltiple de pedidos via checkboxes,
 * persistiendo el estado en el servidor.
 *
 * Requiere: supervisor-pedidos/core/bootstrap.js → window.supervisorPedidos
 */

if (!window.supervisorPedidos?.isReady) {
    throw new Error('[seleccion-pedidos] window.supervisorPedidos no está disponible. Carga core/bootstrap.js ANTES.');
}

const _selService = window.supervisorPedidos.selectionService;

document.addEventListener('DOMContentLoaded', function() {
    // Nota: La carga inicial de selecciones la dispara index.js
    // después de hidratar la tabla por AJAX. Evitamos doble request aquí.

    // Checkbox "Seleccionar todos"
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.pedido-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
                const pedidoId = checkbox.getAttribute('data-pedido-id');
                const fila = checkbox.closest('div[style*="grid-template-columns"]');
                if (e.target.checked) {
                    seleccionarPedido(pedidoId);
                    if (fila) {
                        fila.style.background = '#d1d5db';
                        fila.style.transition = 'background 0.2s';
                        fila.dataset.seleccionado = 'true';
                    }
                } else {
                    deseleccionarPedido(pedidoId);
                    if (fila) {
                        fila.style.background = 'white';
                        fila.style.transition = 'background 0.2s';
                        fila.dataset.seleccionado = 'false';
                    }
                }
            });
        });
    }

    // Event listener delegado para checkboxes individuales
    document.addEventListener('change', function(e) {
        if (!e.target?.classList?.contains('pedido-checkbox')) return;

        const pedidoId = e.target.getAttribute('data-pedido-id');
        const fila = e.target.closest('div[style*="grid-template-columns"]');

        if (e.target.checked) {
            seleccionarPedido(pedidoId);
            if (fila) {
                fila.style.background = '#d1d5db';
                fila.style.transition = 'background 0.2s';
                fila.dataset.seleccionado = 'true';
            }
        } else {
            deseleccionarPedido(pedidoId);
            if (fila) {
                fila.style.background = 'white';
                fila.style.transition = 'background 0.2s';
                fila.dataset.seleccionado = 'false';
            }
        }
    });

    async function seleccionarPedido(pedidoId) {
        try {
            const ok = await _selService.select(pedidoId);
            if (!ok) {
                console.error(`[Selección] Error al seleccionar pedido ${pedidoId}`);
                _revertCheckbox(pedidoId, false);
            }
        } catch (error) {
            console.error(`[Selección] Error de red al seleccionar pedido ${pedidoId}:`, error);
            _revertCheckbox(pedidoId, false);
        }
    }

    async function deseleccionarPedido(pedidoId) {
        try {
            const ok = await _selService.deselect(pedidoId);
            if (!ok) {
                console.error(`[Selección] Error al deseleccionar pedido ${pedidoId}`);
                _revertCheckbox(pedidoId, true);
            }
        } catch (error) {
            console.error(`[Selección] Error de red al deseleccionar pedido ${pedidoId}:`, error);
            _revertCheckbox(pedidoId, true);
        }
    }

    async function cargarSeleccionesGuardadas() {
        try {
            const selecciones = await _selService.loadSavedSelections();

            selecciones.forEach(pedidoId => {
                const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    const fila = checkbox.closest('div[style*="grid-template-columns"]');
                    if (fila) {
                        fila.style.background = '#d1d5db';
                        fila.style.transition = 'background 0.2s';
                        fila.dataset.seleccionado = 'true';
                    }
                }
            });

            if (selecciones.length > 0) {
                console.log(`[Selección] ${selecciones.length} selecciones cargadas`);
            }
        } catch (error) {
            console.error('[Selección] Error al cargar selecciones guardadas:', error);
        }
    }

    function _revertCheckbox(pedidoId, checked) {
        const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
        if (checkbox) checkbox.checked = checked;
    }

    // Exponer globalmente para uso externo (ej. navegarSupervisorPedidos)
    window.seleccionarPedido = seleccionarPedido;
    window.deseleccionarPedido = deseleccionarPedido;
    window.cargarSeleccionesGuardadas = cargarSeleccionesGuardadas;
});
