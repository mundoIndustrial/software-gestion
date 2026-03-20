/**
 * =====================================================
 * SUPERVISOR PEDIDOS - SELECCIÓN DE PEDIDOS
 * =====================================================
 * Maneja la selección múltiple de pedidos via checkboxes,
 * persistiendo el estado en el servidor.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cargar selecciones guardadas al iniciar
    setTimeout(() => { cargarSeleccionesGuardadas(); }, 500);

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
                console.log(`✅ Fila marcada como seleccionada para pedido ${pedidoId}`);
            } else {
                console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
            }
        } else {
            deseleccionarPedido(pedidoId);
            if (fila) {
                fila.style.background = 'white';
                fila.style.transition = 'background 0.2s';
                fila.dataset.seleccionado = 'false';
                console.log(`✅ Fila restaurada a color normal para pedido ${pedidoId}`);
            } else {
                console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
            }
        }
    });

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    function seleccionarPedido(pedidoId) {
        fetch(`/supervisor-pedidos/seleccionar/${pedidoId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log(`✅ Respuesta del servidor para pedido ${pedidoId}:`, data);
            if (!data.success) {
                console.error(`❌ Error al seleccionar pedido ${pedidoId}:`, data.message);
                const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                if (checkbox) checkbox.checked = false;
            }
        })
        .catch(error => {
            console.error(`❌ Error de red al seleccionar pedido ${pedidoId}:`, error);
            const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
            if (checkbox) checkbox.checked = false;
        });
    }

    function deseleccionarPedido(pedidoId) {
        fetch(`/supervisor-pedidos/seleccionar/${pedidoId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`✅ Pedido ${pedidoId} deseleccionado correctamente`);
            } else {
                console.error(`❌ Error al deseleccionar pedido ${pedidoId}:`, data.message);
                const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                if (checkbox) checkbox.checked = true;
            }
        })
        .catch(error => {
            console.error(`❌ Error de red al deseleccionar pedido ${pedidoId}:`, error);
            const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }

    function cargarSeleccionesGuardadas() {
        console.log('🔄 Cargando selecciones guardadas...');
        fetch('/supervisor-pedidos/selecciones', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(response => {
            if (response.status === 404) return null;
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (!data) return;
            console.log('📥 Respuesta de selecciones:', data);
            if (data.success && data.selecciones) {
                console.log(`📋 Se encontraron ${data.selecciones.length} selecciones guardadas:`, data.selecciones);
                data.selecciones.forEach(pedidoId => {
                    const checkbox = document.querySelector(`.pedido-checkbox[data-pedido-id="${pedidoId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        const fila = checkbox.closest('div[style*="grid-template-columns"]');
                        if (fila) {
                            fila.style.background = '#d1d5db';
                            fila.style.transition = 'background 0.2s';
                            fila.dataset.seleccionado = 'true';
                        } else {
                            console.log(`⚠️ No se encontró la fila para pedido ${pedidoId}`);
                        }
                    } else {
                        console.log(`⚠️ No se encontró checkbox para pedido ${pedidoId}`);
                    }
                });
                console.log(`📋 Se cargaron ${data.selecciones.length} selecciones guardadas`);
            } else {
                console.log('📭 No hay selecciones guardadas o error en respuesta');
            }
        })
        .catch(error => {
            console.error('❌ Error al cargar selecciones guardadas:', error);
        });
    }

    // Exponer globalmente para uso externo (ej. navegarSupervisorPedidos)
    window.seleccionarPedido = seleccionarPedido;
    window.deseleccionarPedido = deseleccionarPedido;
    window.cargarSeleccionesGuardadas = cargarSeleccionesGuardadas;
});
