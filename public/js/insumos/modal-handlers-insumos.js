/**
 * Modal Handlers for Insumos/Materiales Module
 * Superficie estable minima para modales de materiales.
 */
let openOrderDetailModalHandler = null;
let pedidosRecibosModuleInstance = null;
const DYNAMIC_CLOSE_BUTTON_ID = 'btn-cerrar-modal-dinamico';
let bodegaGaleriaFotos = [];
let bodegaGaleriaTitulo = 'PRENDA';
let isBodegaReciboActive = false;

function ensureDynamicCloseButton() {
    let btnCerrar = document.getElementById(DYNAMIC_CLOSE_BUTTON_ID);
    if (btnCerrar) return btnCerrar;

    btnCerrar = document.createElement('button');
    btnCerrar.id = DYNAMIC_CLOSE_BUTTON_ID;
    btnCerrar.type = 'button';
    btnCerrar.title = 'Cerrar';
    btnCerrar.innerHTML = '<i class="fas fa-times"></i>';
    btnCerrar.style.cssText = [
        'position: fixed',
        'right: 10px',
        'top: 10px',
        'width: 40px',
        'height: 40px',
        'border-radius: 50%',
        'background: rgba(255, 255, 255, 0.95)',
        'border: none',
        'color: #333',
        'cursor: pointer',
        'display: flex',
        'align-items: center',
        'justify-content: center',
        'font-size: 24px',
        'transition: 0.3s',
        'box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2)',
        'z-index: 10001',
        'font-weight: bold',
    ].join(';');

    btnCerrar.addEventListener('click', function () {
        closeModalOverlay();
    });

    document.body.appendChild(btnCerrar);
    return btnCerrar;
}

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

function abrirModalInsumos(pedido, prendaId, _consecutivo = null, _estado = null, tipoRecibo = 'COSTURA', prendaBodegaId = null, numeroPedido = null) {
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

    const esReciboBodega = String(tipoRecibo || '').toUpperCase() === 'CORTE-PARA-BODEGA';
    const pedidoId = parseInt(pedido, 10) || 0;
    const numeroPedidoFallback = String(numeroPedido || '').trim();
    const pedidoRouteSegment = pedidoId > 0 ? String(pedidoId) : (numeroPedidoFallback || '0');
    modal.dataset.pedidoRouteSegment = pedidoRouteSegment;
    modal.dataset.tipoRecibo = esReciboBodega ? 'CORTE-PARA-BODEGA' : String(tipoRecibo || 'COSTURA');
    modal.dataset.prendaBodegaId = prendaBodegaId ? String(prendaBodegaId) : '';
    modal.dataset.numeroRecibo = _consecutivo ? String(_consecutivo) : '';
    const qs = new URLSearchParams();
    if (prendaId && !esReciboBodega) qs.set('prenda_id', String(prendaId));
    if (prendaBodegaId) qs.set('prenda_bodega_id', String(prendaBodegaId));
    if (esReciboBodega) qs.set('tipo_recibo', 'CORTE-PARA-BODEGA');
    if (esReciboBodega && _consecutivo) qs.set('numero_recibo', String(_consecutivo));
    const query = qs.toString();
    const url = `/insumos/api/materiales/${encodeURIComponent(pedidoRouteSegment)}${query ? `?${query}` : ''}`;

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
        isBodegaReciboActive = true;
        ensureDynamicCloseButton();
        toggleCamposCabeceraRecibo(false);
        ajustarPosicionNumeroPedido(true);
        configurarBotonesFlotantesBodega(true);
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
    isBodegaReciboActive = false;
    configurarBotonesFlotantesBodega(false);
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
        .then(() => {
            ensureDynamicCloseButton();
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

function configurarBotonesFlotantesBodega(esBodega) {
    const btnFactura = document.getElementById('btn-factura');
    const btnGaleria = document.getElementById('btn-galeria');

    if (esBodega) {
        console.log('[BODEGA_UI] configurando botones flotantes en modo bodega');
        const galeriaNativaExiste = Boolean(
            document.getElementById('galeria-modal-costura') ||
            document.getElementById('galeria-modal-costura-rcb')
        );
        if (btnFactura) {
            btnFactura.title = 'Ver galería';
            btnFactura.innerHTML = '<i class="fas fa-images"></i>';
            btnFactura.style.display = 'flex';
            // Solo forzar onclick fallback si NO hay galería nativa en el DOM.
            if (!galeriaNativaExiste) {
                btnFactura.onclick = function (event) {
                    if (event) event.preventDefault();
                    console.log('[BODEGA_UI] click en btn-factura (modo bodega fallback)');
                    toggleFacturaFallback();
                };
            }
            console.log('[BODEGA_UI] btn-factura listo para toggleFacturaFallback');
        } else {
            console.warn('[BODEGA_UI] btn-factura no encontrado');
        }
        if (btnGaleria) {
            // Si ya existe galería nativa, respetar el layout original (2 botones).
            btnGaleria.style.display = galeriaNativaExiste ? 'flex' : 'none';
            if (!galeriaNativaExiste) {
                btnGaleria.onclick = null;
            }
            console.log('[BODEGA_UI] btn-galeria modo bodega, nativa:', galeriaNativaExiste);
        } else {
            console.warn('[BODEGA_UI] btn-galeria no encontrado');
        }
        return;
    }

    if (btnFactura) {
        btnFactura.title = 'Ver factura';
        btnFactura.innerHTML = '<i class="fas fa-receipt"></i>';
        btnFactura.style.display = 'flex';
    }
    if (btnGaleria) {
        btnGaleria.style.display = 'flex';
    }
}

function configurarBotonImpresionRecibo(esBodega) {
    const btnPrint = document.getElementById('btn-print-receipt');
    if (!btnPrint) return;

    if (esBodega) {
        btnPrint.onclick = async function () {
            try {
                if (typeof imprimirReciboBodegaDesdeModal === 'function') {
                    imprimirReciboBodegaDesdeModal();
                    return;
                }
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
  <style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: #111; background: #fff; }
    body.singlepage { width: 100%; min-height: 100vh; display: flex; justify-content: center; }
    .page { width: 100%; max-width: 180mm; margin: 0 auto; }
    .receipt-card { border: 3px solid #111; border-radius: 18px; padding: 14px; }
    .order-logo { width: 150px; height: 56px; object-fit: contain; display: block; margin: 0 auto 8px; }
    .header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
    .order-date { width: 128px; background: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border-radius: 10px; padding: 6px; color: #fff; text-align: center; }
    .fec-label { font-weight: 700; font-size: 11px; margin-bottom: 4px; }
    .date-boxes { display: flex; gap: 4px; justify-content: center; }
    .date-box { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #111; border-radius: 4px; min-width: 36px; padding: 4px; font-size: 12px; font-weight: 800; }
    .header-right { text-align: right; flex: 1; }
    .receipt-title-print { font-weight: 900; text-transform: uppercase; font-size: 16px; line-height: 1.1; margin: 0; }
    .recibo-number-print { font-size: 14px; font-weight: 800; margin-top: 2px; }
    .prenda-info { margin: 8px 0 6px; font-size: 13px; font-weight: 800; text-transform: uppercase; }
    .desc { margin: 0 0 8px; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #374151; }
    .section h4 { margin: 0 0 5px; font-size: 12px; font-weight: 900; text-transform: uppercase; }
    .tallas-resumen { color: #d32f2f; font-weight: 900; white-space: pre-line; font-size: 11px; line-height: 1.3; text-transform: uppercase; }
    .total-line { margin-top: 8px; padding-top: 4px; border-top: 1.5px solid #1f2937; color: #1f2937; font-weight: 800; font-size: 12px; width: 160px; }
  </style>
</head>
<body class="singlepage">
  <div class="page">
    <div class="receipt-card">
      <img src="/images/logo2.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
      <div class="header-row">
        <div class="order-date">
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
      </div>
      <div class="prenda-info">PRENDA 1</div>
      <div class="desc">${esc(descripcion || '-')}</div>
      <div class="section">
        <h4>TALLAS:</h4>
        <div class="tallas-resumen">${tallasHtml}</div>
      </div>
      <div class="total-line">TOTAL: ${esc(total || '0')}</div>
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
        .then(async (data) => {
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
            bodegaGaleriaTitulo = String(data.prenda || data.prenda_nombre || 'PRENDA 1').trim().toUpperCase();
            bodegaGaleriaFotos = (Array.isArray(data.fotos) ? data.fotos : [])
                .map((f) => String(f?.url || '').trim())
                .filter(Boolean);
            console.log('[BODEGA_UI] fotos recibidas para galeria:', bodegaGaleriaFotos.length);

            const tallasPorGenero = tallas.reduce((acc, tallaRow) => {
                const genero = String(tallaRow.genero || 'UNISEX').toUpperCase();
                if (!acc[genero]) acc[genero] = [];
                acc[genero].push(tallaRow);
                return acc;
            }, {});

            const bloquesGenero = Object.entries(tallasPorGenero).map(([genero, items]) => {
                const porColor = items.reduce((acc, item) => {
                    const talla = String(item.talla || '').trim().toUpperCase();
                    const color = String(item.color || 'SIN COLOR').trim().toUpperCase();
                    const cantidad = Number(item.cantidad || 0);
                    if (!talla || cantidad <= 0) return acc;
                    if (!acc[color]) acc[color] = [];
                    acc[color].push(`${escapeHtml(talla)}:${cantidad}`);
                    return acc;
                }, {});

                const lineasColor = Object.entries(porColor).map(([color, tallasColor]) =>
                    `<span style="color: red;"><strong>${escapeHtml(color)}: ${tallasColor.join(', ')}</strong></span><br>`
                ).join('');

                if (!lineasColor) return '';
                return `<div><span style="color: #1f2937;"><strong>${escapeHtml(genero)}</strong></span><br>${lineasColor}</div>`;
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

            if (dayBox) dayBox.textContent = String(data.dia || '--');
            if (monthBox) monthBox.textContent = String(data.mes || '--');
            if (yearBox) yearBox.textContent = String(data.ano || '--');
            if (asesoraValue) asesoraValue.textContent = 'BODEGA';
            if (formaPagoValue) formaPagoValue.textContent = '-';
            if (clienteValue) clienteValue.textContent = 'BODEGA';
            if (descripcionText) descripcionText.innerHTML = descripcionHtml;
            if (title) title.innerHTML = 'RECIBO DE CORTE<br>PARA BODEGA';
            if (pedido) pedido.textContent = numeroRecibo;

            // Cargar y renderizar ancho/metraje para CORTE-PARA-BODEGA
            try {
                const numeroReciboPlano = Number(data.numero_recibo || 0);
                const qs = new URLSearchParams({
                    prenda_bodega_id: String(prendaBodegaId),
                    tipo_recibo: 'CORTE-PARA-BODEGA',
                });
                if (numeroReciboPlano > 0) {
                    qs.set('numero_recibo', String(numeroReciboPlano));
                }
                const endpoint = `/insumos/materiales/${encodeURIComponent(String(prendaBodegaId))}/obtener-ancho-metraje-prenda/${encodeURIComponent(String(prendaBodegaId))}?${qs.toString()}`;
                const anchoResp = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const anchoData = await anchoResp.json().catch(() => ({}));
                if (anchoResp.ok && anchoData?.success) {
                    renderAnchoMetrajeInOrderDetail(anchoData);
                } else {
                    renderAnchoMetrajeInOrderDetail(null);
                }
            } catch (_) {
                renderAnchoMetrajeInOrderDetail(null);
            }

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

function renderAnchoMetrajeInOrderDetail(data) {
    const contenedor = document.getElementById('order-ancho-metraje');
    const vistaNormal = document.getElementById('ancho-metraje-normal');
    const vistaMano = document.getElementById('ancho-metraje-mano');
    const anchoSpan = document.getElementById('ancho-valor');
    const metrajeSpan = document.getElementById('metraje-valor');
    const metrajesContainer = document.getElementById('metrajes-por-color-container');
    const contenidoMano = document.getElementById('contenido-mano');
    const observacionesMano = document.getElementById('observaciones-mano');
    const contenidoObservaciones = document.getElementById('contenido-observaciones');

    if (!contenedor) return;

    if (metrajesContainer) metrajesContainer.innerHTML = '';
    if (contenidoMano) contenidoMano.textContent = '';
    if (observacionesMano) observacionesMano.style.display = 'none';
    if (contenidoObservaciones) contenidoObservaciones.textContent = '';
    if (anchoSpan) anchoSpan.textContent = '--';
    if (metrajeSpan) metrajeSpan.textContent = '--';
    if (metrajeSpan?.closest('span')) metrajeSpan.closest('span').style.display = 'block';

    const sinDatos = !data || !data.tipo_modo || (
        Array.isArray(data.data) && data.data.length === 0 &&
        !data.ancho && !data.metraje && !data.contenido_mano
    );
    if (sinDatos) {
        contenedor.style.display = 'none';
        return;
    }

    contenedor.style.display = 'block';
    const tipoModo = String(data.tipo_modo || '').toLowerCase();

    if (tipoModo === 'mano') {
        if (vistaNormal) vistaNormal.classList.add('hidden-view');
        if (vistaMano) vistaMano.style.display = 'block';
        if (contenidoMano) contenidoMano.textContent = String(data.contenido_mano || '-');
        return;
    }

    if (vistaNormal) vistaNormal.classList.remove('hidden-view');
    if (vistaMano) vistaMano.style.display = 'none';

    if (anchoSpan) {
        anchoSpan.textContent = data.ancho ? `${data.ancho}` : '--';
    }

    const metrajesValidos = (Array.isArray(data.data) ? data.data : [])
        .filter((item) => item && item.color && item.metraje);

    if (tipoModo === 'normal') {
        if (metrajeSpan) metrajeSpan.textContent = data.metraje ? `${data.metraje}` : '--';
        metrajesValidos.forEach((item) => {
            if (!metrajesContainer) return;
            const span = document.createElement('span');
            span.textContent = `${String(item.color).toUpperCase()}: ${item.metraje}`;
            metrajesContainer.appendChild(span);
        });
        return;
    }

    if (metrajeSpan?.closest('span')) {
        metrajeSpan.closest('span').style.display = 'none';
    }
    metrajesValidos.forEach((item) => {
        if (!metrajesContainer) return;
        const span = document.createElement('span');
        span.textContent = `${String(item.color).toUpperCase()}: ${item.metraje}`;
        metrajesContainer.appendChild(span);
    });
}

function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('order-detail-modal-wrapper');
    const btnCerrarDinamico = document.getElementById(DYNAMIC_CLOSE_BUTTON_ID);

    if (overlay) {
        overlay.style.display = 'none';
    }

    if (wrapper) {
        wrapper.style.display = 'none';
    }

    if (btnCerrarDinamico) {
        btnCerrarDinamico.remove();
    }
    isBodegaReciboActive = false;
}

function toggleFacturaFallback() {
    console.log('[BODEGA_UI] toggleFacturaFallback() inicio');
    const wrapper = document.getElementById('order-detail-modal-wrapper');
    if (!wrapper) {
        console.warn('[BODEGA_UI] wrapper no encontrado: #order-detail-modal-wrapper');
        return;
    }

    const card = wrapper.querySelector('.order-detail-card');
    let galeria =
        document.getElementById('galeria-modal-costura') ||
        document.getElementById('galeria-modal-costura-rcb');

    if (!card) {
        console.warn('[BODEGA_UI] card no encontrada: .order-detail-card');
        return;
    }
    if (!galeria) {
        console.log('[BODEGA_UI] galeria nativa no encontrada, creando galeria inline fallback');
        galeria = ensureBodegaInlineGallery(wrapper);
    }

    if (!galeria) {
        console.warn('[BODEGA_UI] no fue posible construir galeria inline');
        return;
    }

    const estaEnGaleria = galeria.style.display === 'flex' || galeria.style.display === 'block';
    console.log('[BODEGA_UI] estado galeria actual:', {
        display: galeria.style.display,
        estaEnGaleria,
    });

    if (estaEnGaleria) {
        console.log('[BODEGA_UI] cerrando galeria para volver al recibo');
        if (!isBodegaReciboActive && window.GalleryManager && typeof window.GalleryManager.cerrarGaleria === 'function') {
            console.log('[BODEGA_UI] usando GalleryManager.cerrarGaleria()');
            window.GalleryManager.cerrarGaleria();
        } else {
            console.log('[BODEGA_UI] fallback manual cerrar galeria');
            galeria.style.display = 'none';
            card.style.display = 'block';
        }
        const btnFactura = document.getElementById('btn-factura');
        if (btnFactura) {
            btnFactura.innerHTML = '<i class="fas fa-images"></i>';
            btnFactura.title = 'Ver galería';
        }
        return;
    }

    console.log('[BODEGA_UI] abriendo galeria');
    if (!isBodegaReciboActive && window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirGaleria === 'function') {
        console.log('[BODEGA_UI] usando pedidosRecibosModule.abrirGaleria()');
        window.pedidosRecibosModule.abrirGaleria();
    } else if (!isBodegaReciboActive && typeof window.toggleGaleria === 'function') {
        console.log('[BODEGA_UI] usando window.toggleGaleria()');
        window.toggleGaleria();
    } else {
        console.log('[BODEGA_UI] fallback manual abrir galeria');
        galeria.style.display = 'flex';
        card.style.display = 'none';
    }

    const btnFactura = document.getElementById('btn-factura');
    if (btnFactura) {
        btnFactura.innerHTML = '<i class="fas fa-receipt"></i>';
        btnFactura.title = 'Ver recibo';
    }
}

function ensureBodegaInlineGallery(wrapper) {
    const fotos = Array.isArray(bodegaGaleriaFotos) ? bodegaGaleriaFotos.filter(Boolean) : [];
    if (fotos.length === 0) {
        if (window.Swal) {
            window.Swal.fire('Sin imágenes', 'Este recibo no tiene imágenes de referencia.', 'info');
        }
        return null;
    }

    window.__galeriaImagenes = fotos;

    let galeria = document.getElementById('galeria-modal-costura');
    if (!galeria) {
        galeria = document.createElement('div');
        galeria.id = 'galeria-modal-costura';
        galeria.style.cssText = 'width:668px;max-width:100%;margin:0 auto;padding:0;display:none;flex-direction:column;min-height:520px;max-height:820px;overflow-y:auto;background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);';
        wrapper.querySelector('.order-detail-modal-container')?.appendChild(galeria);
    }

    const cards = fotos.map((url, i) => `
        <div style="border:2px solid #e5e7eb;border-radius:12px;overflow:hidden;cursor:pointer;transition:all .3s ease;background:white;box-shadow:0 2px 8px rgba(0,0,0,.08);" onclick="if(typeof window.abrirModalImagenProcesoGrande==='function'){window.abrirModalImagenProcesoGrande(${i}, window.__galeriaImagenes||[]);}" onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='scale(1.03)'; this.style.boxShadow='0 8px 16px rgba(59,130,246,.3)';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)';">
            <img src="${url}" alt="${bodegaGaleriaTitulo}" style="width:100%;height:220px;object-fit:cover;display:block;">
            <div style="padding:.75rem;background:#f9fafb;">
                <div style="font-size:.875rem;font-weight:600;color:#1f2937;margin-bottom:.25rem;">${bodegaGaleriaTitulo}</div>
                <div style="font-size:.75rem;color:#6b7280;">Imagen ${i + 1}</div>
            </div>
        </div>
    `).join('');

    galeria.innerHTML = `
        <div style="background:#fff;display:flex;flex-direction:column;width:100%;height:100%;box-sizing:border-box;border-radius:12px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);padding:16px 12px;border-radius:12px 12px 0 0;position:sticky;top:0;z-index:100;">
                <h2 style="text-align:center;margin:0;font-size:1.4rem;font-weight:700;color:#fff;letter-spacing:1px;">GALERÍA</h2>
            </div>
            <div style="padding:24px;flex:1;overflow-y:auto;background:#fff;">
                <div style="padding:1.5rem;border-bottom:1px solid #e5e7eb;">
                    <h3 style="margin:0 0 1rem 0;font-size:1.25rem;font-weight:600;color:#1f2937;">${bodegaGaleriaTitulo}</h3>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
                        ${cards}
                    </div>
                </div>
            </div>
        </div>
    `;

    return galeria;
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

// Compatibilidad para botones inline onclick="toggleFactura()".
if (typeof window.toggleFactura !== 'function') {
    window.toggleFactura = toggleFacturaFallback;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalHandlersInsumos);
} else {
    initModalHandlersInsumos();
}
