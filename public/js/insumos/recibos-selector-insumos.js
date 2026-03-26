/**
 * Recibos Selector & Tracking - Insumos
 */

function initRecibosSelectorInsumos() {
    let currentPrendaData = null;

    function cerrarSelectorPrendas() {
        const modal = document.getElementById('selector-prendas-modal');
        if (modal) {
            modal.remove();
        }
    }

    function mostrarSelectorDePrendas(datos, pedidoId) {
        const modal = document.createElement('div');
        modal.id = 'selector-prendas-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 1rem;
        `;

        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        `;

        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: bold; color: #1f2937;">
                    Seleccionar Prenda - Pedido ${datos.numero_pedido}
                </h2>
                <button data-insumos-action="selector-close-prendas" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6b7280;
                    padding: 0.5rem;
                    border-radius: 0.375rem;
                    transition: all 0.2s ease;
                "></button>
            </div>

            <div style="margin-bottom: 1.5rem; color: #6b7280;">
                Cliente: ${datos.cliente || 'N/A'} | Asesor: ${datos.asesor || datos.asesora || 'N/A'}
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                ${datos.prendas.map((prenda, index) => `
                    <button data-insumos-action="selector-seleccionar-prenda" data-pedido-id="${pedidoId}" data-prenda-index="${index}" style="
                        background: white;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                        padding: 1.5rem;
                        text-align: left;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div>
                            <div style="font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; font-size: 1.125rem;">
                                ${prenda.nombre || 'Prenda sin nombre'}
                            </div>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">
                                ${prenda.descripcion || 'Sin descripcion'}
                            </div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">
                                Cantidad: ${prenda.cantidad || 'N/A'}
                            </div>
                        </div>
                        <div style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700;">
                            Ver Recibo
                        </div>
                    </button>
                `).join('')}
            </div>
        `;

        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarSelectorPrendas();
            }
        });
    }

    function abrirSelectorRecibos(pedidoId) {
        fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((datos) => {
                const datosReales = datos.data || datos;
                if (datosReales.prendas && datosReales.prendas.length > 0) {
                    mostrarSelectorDePrendas(datosReales, pedidoId);
                }
            })
            .catch((error) => {
                console.error('[abrirRecibos] Error al cargar datos:', error);
            });
    }

    function seleccionarPrendaRecibo(pedidoId, prendaIndex) {
        cerrarSelectorPrendas();

        if (typeof verRecibosDelPedido === 'function') {
            verRecibosDelPedido(null, pedidoId, prendaIndex);
        } else {
            console.error('[seleccionarPrendaRecibo] verRecibosDelPedido no esta disponible');
        }
    }

    function abrirModalSeguimientoDirectoInsumos(pedidoId, prendaIdTarget, prendaData = currentPrendaData) {
        const trackingOverlay = document.getElementById('trackingModalOverlay');
        if (trackingOverlay) {
            trackingOverlay.style.display = 'block';
        } else {
            alert('Modal de seguimiento no disponible');
            return;
        }

        const trackingModal = document.getElementById('orderTrackingModal');
        if (!trackingModal) return;

        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');

        let urlConsecutivo = `/registros/${pedidoId}/consecutivo-costura`;
        if (prendaIdTarget) {
            urlConsecutivo += `?prenda_id=${prendaIdTarget}`;
        }

        fetch(urlConsecutivo)
            .then((response) => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then((data) => {
                const reciboEl = document.getElementById('trackingOrderRecibo');
                const headerEl = document.getElementById('trackingPrendaReciboHeader');

                if (data.success && data.consecutivo) {
                    if (reciboEl) reciboEl.textContent = data.consecutivo;
                    if (headerEl) headerEl.textContent = `COSTURA #${data.consecutivo}`;
                } else {
                    if (reciboEl) reciboEl.textContent = '-';
                    if (headerEl) headerEl.textContent = 'COSTURA #?';
                }

                if (data.fecha_creacion) {
                    const fechaEl = document.getElementById('trackingOrderDate');
                    if (fechaEl) {
                        const fecha = new Date(data.fecha_creacion);
                        fechaEl.textContent = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                        });
                    }
                }

                if (typeof showPrendaTracking === 'function' && prendaData) {
                    if (window.isInsumos) {
                        prendaData.readonly = true;
                    }
                    showPrendaTracking(prendaData);
                }
            })
            .catch((error) => {
                console.error('[Insumos] Error al obtener consecutivo:', error);
                if (typeof showPrendaTracking === 'function' && prendaData) {
                    if (window.isInsumos) {
                        prendaData.readonly = true;
                    }
                    showPrendaTracking(prendaData);
                }
            });
    }

    function verSeguimiento(pedidoId, prendaIdTarget) {
        if (typeof openOrderTracking !== 'function') {
            alert('Sistema de seguimiento no disponible');
            return;
        }

        openOrderTracking(pedidoId, false)
            .then(() => {
                let prendas = null;
                if (window.currentOrderData && window.currentOrderData.prendas) {
                    prendas = window.currentOrderData.prendas;
                } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
                    prendas = window.currentOrderData.data.prendas;
                } else if (window.prendasData && window.prendasData.length > 0) {
                    prendas = window.prendasData;
                }

                if (!(prendas && prendas.length > 0)) {
                    if (typeof showPrendasSelector === 'function') {
                        showPrendasSelector();
                    } else {
                        alert('No hay prendas disponibles para este pedido');
                    }
                    return;
                }

                let prendaSeleccionada = null;
                if (prendaIdTarget) {
                    prendaSeleccionada = prendas.find(
                        (p) => String(p.id) === String(prendaIdTarget) || String(p.prenda_pedido_id) === String(prendaIdTarget),
                    );
                }

                if (!prendaSeleccionada) {
                    prendaSeleccionada = prendas[0];
                }

                currentPrendaData = prendaSeleccionada;
                abrirModalSeguimientoDirectoInsumos(pedidoId, prendaIdTarget, prendaSeleccionada);
            })
            .catch((error) => {
                console.error('[Insumos verSeguimiento] Error:', error);
                alert('Error al cargar los datos del pedido: ' + error.message);
            });
    }

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.recibosSelector = {
        abrirSelectorRecibos,
        mostrarSelectorDePrendas,
        seleccionarPrendaRecibo,
        cerrarSelectorPrendas,
        verSeguimiento,
        abrirModalSeguimientoDirectoInsumos,
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRecibosSelectorInsumos);
} else {
    initRecibosSelectorInsumos();
}
