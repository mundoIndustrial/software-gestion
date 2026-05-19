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

    // Esperar a que window.pedidosRecibosModule esta disponible (instancia global singleton)
    let intentos = 0;
    while (!window.pedidosRecibosModule && intentos < 100) {
        await new Promise(resolve => setTimeout(resolve, 10));
        intentos++;
    }

    if (!window.pedidosRecibosModule) {
        console.log('[modal-handlers-insumos] window.pedidosRecibosModule no disponible aun, creando instancia fallback');
        // Fallback: crear nueva instancia si la global no existe
        const { PedidosRecibosModule } = await import('/js/modulos/pedidos-recibos/PedidosRecibosModule.js');
        pedidosRecibosModuleInstance = new PedidosRecibosModule();
        // Actualizar la instancia global para que toggleFactura() la encuentre
        window.pedidosRecibosModule = pedidosRecibosModuleInstance;
        console.log('[modal-handlers-insumos] Instancia fallback asignada a window.pedidosRecibosModule');
    } else {
        // Usar la instancia global singleton
        pedidosRecibosModuleInstance = window.pedidosRecibosModule;
        console.log('[modal-handlers-insumos] Usando instancia global de PedidosRecibosModule');
    }

    // Guardar la instancia globalmente para que otros scripts (como insumos-galeria.js) puedan acceder a ella
    window.PedidosRecibosModuleInstance = pedidosRecibosModuleInstance;

    openOrderDetailModalHandler = (pedidoId, prendaId, tipoRecibo, prendaIndex = null, options = {}) =>
        pedidosRecibosModuleInstance.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex, options);

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

function abrirDetalleRecibo(pedidoId, prendaId, tipoRecibo, esParcial = false, pedidoParcialId = null) {
    const tipoReciboNormalizado = String(tipoRecibo || '').trim().toUpperCase();
    const parsedPedidoId = parseInt(pedidoId, 10) || null;
    const parsedPrendaId = (prendaId === 'null' || prendaId === '' || !prendaId)
        ? null
        : (parseInt(prendaId, 10) || null);
    const parsedPedidoParcialId = pedidoParcialId ? (parseInt(pedidoParcialId, 10) || null) : null;

    if (tipoReciboNormalizado === 'CORTE-PARA-BODEGA') {
        toggleCamposCabeceraRecibo(false);
        ajustarPosicionNumeroPedido(true);
        configurarBotonImpresionRecibo(true);
        if (!parsedPrendaId) {
            if (window.Swal) {
                window.Swal.fire('Error', 'No se pudo resolver el ID del recibo de bodega.', 'error');
            } else {
                alert('Error: No se pudo resolver el ID del recibo de bodega.');
            }
            return;
        }
        abrirDetalleReciboCorteBodega(parsedPrendaId);
        return;
    }

    toggleCamposCabeceraRecibo(true);
    ajustarPosicionNumeroPedido(false);
    configurarBotonImpresionRecibo(false);

    resolveOpenOrderDetailModalHandler()
        .then((handler) => {
            if (esParcial && parsedPedidoParcialId && pedidosRecibosModuleInstance && typeof pedidosRecibosModuleInstance.abrirReciboParcial === 'function') {
                const nombreAnexo = `${String(tipoRecibo || 'COSTURA').toUpperCase()} ANEXO`;
                return pedidosRecibosModuleInstance.abrirReciboParcial(
                    parsedPedidoId,
                    parsedPrendaId,
                    tipoRecibo,
                    parsedPedidoParcialId,
                    nombreAnexo
                );
            }

            return handler(parsedPedidoId, parsedPrendaId, tipoRecibo, null, { esParcial: false });
        })
        .catch((error) => {
            console.error('[modal-handlers-insumos] Error cargando PedidosRecibosModule:', error);
        });
}

function toggleCamposCabeceraRecibo(mostrar) {
    const ids = ['order-asesora', 'order-forma-pago', 'order-cliente'];
    ids.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.style.display = mostrar ? '' : 'none';
    });
}

function ajustarPosicionNumeroPedido(esBodega) {
    const pedido = document.getElementById('order-pedido');
    if (!pedido) return;
    if (esBodega) {
        pedido.style.transform = 'translateY(28px)';
    } else {
        pedido.style.transform = '';
    }
}

function configurarBotonImpresionRecibo(esBodega) {
    const btnPrint = document.getElementById('btn-print-receipt');
    if (!btnPrint) return;

    if (esBodega) {
        btnPrint.onclick = async function () {
            try {
                await resolveOpenOrderDetailModalHandler();
                if (typeof window.printReceiptModal === 'function') {
                    window.printReceiptModal();
                }
            } catch (error) {
                if (window.Swal) {
                    window.Swal.fire('Error', 'No se pudo cargar el motor de impresión.', 'error');
                } else {
                    alert('No se pudo cargar el motor de impresión.');
                }
            }
        };
        return;
    }

    btnPrint.onclick = function () {
        if (typeof window.printReceiptModal === 'function') {
            window.printReceiptModal();
        }
    };
}

function imprimirReciboBodegaDesdeModal() {
    const day = (document.querySelector('#order-detail-modal-wrapper .day-box')?.textContent || '').trim();
    const month = (document.querySelector('#order-detail-modal-wrapper .month-box')?.textContent || '').trim();
    const year = (document.querySelector('#order-detail-modal-wrapper .year-box')?.textContent || '').trim();
    const descripcionTextEl = document.querySelector('#descripcion-text');
    const descripcion = (document.querySelector('#descripcion-text span')?.textContent || '').trim();
    const numeroRecibo = (document.getElementById('order-pedido')?.textContent || '#').trim();
    const total = (descripcionTextEl?.innerText.match(/TOTAL:\s*([0-9]+)/i)?.[1] || '').trim();
    const tallasHtmlRaw = (descripcionTextEl?.innerHTML || '')
        .replace(/^[\s\S]*?<strong>TALLAS<\/strong><br>/i, '')
        .replace(/<div style="border-top:[\s\S]*$/i, '')
        .trim();

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const tallasHtml = tallasHtmlRaw || '<span style="color:#d32f2f;font-weight:bold;">-</span>';

    const printHtml = `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recibo Corte Bodega</title>
  <link rel="stylesheet" href="/css/order-detail-modal.css">
  <link rel="stylesheet" href="/css/print-order-detail-modal.css" media="print">
</head>
<body class="singlepage">
  <div class="page">
    <div class="receipt-card">
      <img src="/images/logo2.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
      <div id="order-date" class="order-date">
        <div class="fec-label">FECHA</div>
        <div class="date-boxes">
          <div class="date-box day-box">${esc(day)}</div>
          <div class="date-box month-box">${esc(month)}</div>
          <div class="date-box year-box">${esc(year)}</div>
        </div>
      </div>

      <div class="header-right">
        <div class="receipt-title-print">RECIBO DE CORTE<br>PARA BODEGA</div>
        <div class="recibo-number-print">${esc(numeroRecibo)}</div>
      </div>

      <div class="prenda-info">
        <div class="prenda-name">PRENDA 1</div>
        <div style="margin-top: 6px; color: #212529; font-weight: 700;">${esc(descripcion || '-')}</div>
      </div>

      <div class="section">
        <h4>TALLAS:</h4>
        <div class="tallas-resumen" style="color: inherit; font-weight: 900; white-space: pre-line;">
          <div style="display: inline-block; min-width: 100px; margin-bottom: 18px;">
            ${tallasHtml}
            <div style="border-top: 1px solid #9ca3af; margin-top: 1px; padding-top: 1px;">
              <span style="color: black; font-weight: 900;">TOTAL: ${esc(total || '0')}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<script>
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 50); });
  <\/script>
</body>
</html>`;

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        window.print();
        return;
    }
    printWindow.document.open();
    printWindow.document.write(printHtml);
    printWindow.document.close();
}

function abrirDetalleReciboCorteBodega(prendaBodegaId) {
    fetch(`/api/recibo-corte-bodega/${encodeURIComponent(prendaBodegaId)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(async (response) => {
            let payload = null;
            try {
                payload = await response.json();
            } catch (_) {
                payload = null;
            }

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || `HTTP ${response.status}`);
            }

            return payload;
        })
        .then((data) => {
            const overlay = document.getElementById('modal-overlay');
            const wrapper = document.getElementById('order-detail-modal-wrapper');
            const floatingButtons = document.getElementById('floating-buttons-container');
            if (!overlay || !wrapper) {
                throw new Error('No se encontro el contenedor del modal de recibo');
            }

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const numeroRecibo = data.numero_recibo ? `#${data.numero_recibo}` : '#';
            const descripcion = (data.descripcion || '').trim() || 'Sin descripcion';
            const total = Number(data.total || 0);
            const tallas = Array.isArray(data.tallas) ? data.tallas : [];

            const tallasPorGenero = tallas.reduce((acc, tallaRow) => {
                const genero = String(tallaRow.genero || 'UNISEX').toUpperCase();
                if (!acc[genero]) acc[genero] = [];
                acc[genero].push(tallaRow);
                return acc;
            }, {});

            const bloquesGenero = Object.entries(tallasPorGenero).map(([genero, items]) => {
                const partes = items.map((item) => {
                    const talla = String(item.talla || '').trim();
                    const color = String(item.color || '').trim();
                    const cantidad = Number(item.cantidad || 0);
                    if (!talla) return `${cantidad}`;
                    if (!color) return `${escapeHtml(talla)}:${cantidad}`;
                    return `${escapeHtml(color)} ${escapeHtml(talla)}:${cantidad}`;
                }).filter(Boolean);

                if (partes.length === 0) return '';
                return `<div><span style="color: red;"><strong>${escapeHtml(genero)} - ${partes.join(', ')}</strong></span><br></div>`;
            }).join('');

            const descripcionHtml = `
                <strong style="font-size: 13.4px;">PRENDA 1</strong><br>
                <span style="display: block; margin-top: 8px; margin-bottom: 12px; color: #212529; font-weight: 600;">${escapeHtml(descripcion)}</span>
                <strong>TALLAS</strong><br>
                <div style="display: inline-block; min-width: 120px; margin-top: 8px;">
                    <div style="margin-bottom: 12px;">${bloquesGenero}</div>
                    <div style="border-top: 1.5px solid #1f2937; margin-top: 8px; padding-top: 4px;">
                        <span style="color: #1f2937; font-weight: 700; font-size: 13.4px;">TOTAL: <strong>${total}</strong></span>
                    </div>
                </div>
            `;

            const dayBox = wrapper.querySelector('.day-box');
            const monthBox = wrapper.querySelector('.month-box');
            const yearBox = wrapper.querySelector('.year-box');
            const asesoraValue = document.getElementById('asesora-value');
            const formaPagoValue = document.getElementById('forma-pago-value');
            const clienteValue = document.getElementById('cliente-value');
            const descripcionText = document.getElementById('descripcion-text');
            const title = document.getElementById('receipt-title');
            const pedido = document.getElementById('order-pedido');
            const anchoMetraje = document.getElementById('order-ancho-metraje');

            if (dayBox) dayBox.textContent = String(data.dia || '--');
            if (monthBox) monthBox.textContent = String(data.mes || '--');
            if (yearBox) yearBox.textContent = String(data.ano || '--');
            if (asesoraValue) asesoraValue.textContent = 'BODEGA';
            if (formaPagoValue) formaPagoValue.textContent = '-';
            if (clienteValue) clienteValue.textContent = 'BODEGA';
            if (descripcionText) descripcionText.innerHTML = descripcionHtml;
            if (title) title.innerHTML = 'RECIBO DE CORTE<br>PARA BODEGA';
            if (pedido) pedido.textContent = numeroRecibo;
            if (anchoMetraje) anchoMetraje.style.display = 'none';

            // Refuerzo visual para CORTE-PARA-BODEGA
            toggleCamposCabeceraRecibo(false);

            overlay.style.display = 'block';
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
            overlay.style.pointerEvents = 'auto';

            wrapper.style.display = 'block';
            wrapper.style.opacity = '1';
            wrapper.style.visibility = 'visible';
            wrapper.style.pointerEvents = 'auto';

            if (floatingButtons) {
                floatingButtons.style.display = 'flex';
                floatingButtons.style.visibility = 'visible';
            }
        })
        .catch((error) => {
            if (window.Swal) {
                window.Swal.fire('Error', `No fue posible abrir el recibo: ${error.message}`, 'error');
            } else {
                alert(`No fue posible abrir el recibo: ${error.message}`);
            }
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
