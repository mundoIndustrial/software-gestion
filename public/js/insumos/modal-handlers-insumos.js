/**
 * Modal Handlers for Insumos/Materiales Module
 * Superficie estable minima para modales de materiales.
 */
let openOrderDetailModalHandler = null;
let pedidosRecibosModuleInstance = null;

async function resolveOpenOrderDetailModalHandler() {
    if (typeof openOrderDetailModalHandler === 'function') {
        return openOrderDetailModalHandler;
    }

    const { PedidosRecibosModule } = await import('/js/modulos/pedidos-recibos/PedidosRecibosModule.js');
    pedidosRecibosModuleInstance = new PedidosRecibosModule();
    
    // Guardar la instancia globalmente para que otros scripts (como insumos-galeria.js) puedan acceder a ella
    window.PedidosRecibosModuleInstance = pedidosRecibosModuleInstance;
    
    openOrderDetailModalHandler = (pedidoId, prendaId, tipoRecibo, prendaIndex = null) =>
        pedidosRecibosModuleInstance.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);

    return openOrderDetailModalHandler;
}

function abrirModalInsumos(pedido, prendaId) {
    const modal = document.getElementById('insumosModal');
    if (!modal) {
        console.error('[modal-handlers-insumos] Modal insumos no encontrado');
        return;
    }

    modal.style.display = 'flex';

    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.removeAttribute('aria-hidden');
    }

    const pedidoLabel = document.getElementById('modalPedido');
    const prendaInput = document.getElementById('modalPrendaId');
    const prendaNombre = document.getElementById('modalPrendaNombre');

    if (pedidoLabel) pedidoLabel.textContent = pedido;
    if (prendaInput) prendaInput.value = prendaId || '';
    if (prendaNombre) prendaNombre.textContent = prendaId ? 'Cargando...' : 'General';

    let url = `/insumos/api/materiales/${pedido}`;
    if (prendaId) {
        url += `?prenda_id=${prendaId}`;
    }

    fetch(url)
        .then((response) => response.json())
        .then((data) => {
            if (prendaNombre) {
                if (data.nombre_prenda) {
                    prendaNombre.textContent = data.nombre_prenda;
                } else if (prendaId) {
                    prendaNombre.textContent = `Prenda #${prendaId}`;
                }
            }

            const llenarTablaInsumos = window.insumosHandlers?.insumosModalManagement?.llenarTablaInsumos;
            if (typeof llenarTablaInsumos === 'function') {
                llenarTablaInsumos(data.materiales || []);
            } else {
                console.warn('[modal-handlers-insumos] llenarTablaInsumos no disponible');
            }
        })
        .catch((error) => {
            console.error('[modal-handlers-insumos] Error cargando insumos:', error);
            const showToast = window.insumosHandlers?.utilities?.showToast;
            if (typeof showToast === 'function') {
                showToast('Error al cargar los insumos', 'error');
            }
        });
}

function cerrarModalInsumos() {
    const modal = document.getElementById('insumosModal');
    if (modal) {
        modal.style.display = 'none';
    }

    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.setAttribute('aria-hidden', 'false');
    }
}

function abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo) {
    const parsedPedidoId = parseInt(pedidoId, 10) || null;
    const parsedPrendaId = (prendaId === 'null' || prendaId === '' || !prendaId)
        ? null
        : (parseInt(prendaId, 10) || null);

    resolveOpenOrderDetailModalHandler()
        .then((handler) => handler(parsedPedidoId, parsedPrendaId, tipoRecibo))
        .catch((error) => {
            console.error('[modal-handlers-insumos] Error cargando PedidosRecibosModule:', error);
        });
}

function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('order-detail-modal-wrapper');

    if (overlay) {
        overlay.style.display = 'none';
    }

    if (wrapper) {
        wrapper.style.display = 'none';
    }
}

function initModalHandlersInsumos() {
    const insumosModal = document.getElementById('insumosModal');
    if (!insumosModal || insumosModal.dataset.insumosOverlayBound === '1') {
        return;
    }

    insumosModal.dataset.insumosOverlayBound = '1';
    insumosModal.addEventListener('click', function (event) {
        if (event.target === insumosModal) {
            cerrarModalInsumos();
        }
    });
}

window.insumosHandlers = window.insumosHandlers || {};
window.insumosHandlers.modalHandlers = {
    abrirModalInsumos,
    cerrarModalInsumos,
    abrirDetalleRecibo,
    closeModalOverlay,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalHandlersInsumos);
} else {
    initModalHandlersInsumos();
}
