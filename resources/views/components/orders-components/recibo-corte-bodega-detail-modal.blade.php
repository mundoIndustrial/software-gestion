<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">

<div id="rcb-modal-overlay" class="rcb-modal-overlay" onclick="closeReciboCorteBodegaModal()"></div>

<div id="rcb-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
        <div class="order-detail-card" style="display: block;">
            <img src="{{ asset('images/logo2.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">

            <div id="rcb-date" class="order-date">
                <div class="fec-label">FECHA</div>
                <div class="date-boxes">
                    <div class="date-box day-box" id="rcb-day">13</div>
                    <div class="date-box month-box" id="rcb-month">04</div>
                    <div class="date-box year-box" id="rcb-year">2026</div>
                </div>
            </div>

            <div id="rcb-descripcion-container" class="order-descripcion">
                <div id="rcb-descripcion-text">
                    <strong style="font-size: 13.4px;" id="rcb-prenda-title">PRENDA 1</strong><br>
                    <span id="rcb-prenda-desc" style="display: block; margin-top: 8px; margin-bottom: 12px; color: #212529; font-weight: 600;">Descripción de la prenda</span>
                    <strong>TALLAS</strong><br>
                    <div style="display: inline-block; min-width: 120px; margin-top: 8px;">
                        <div id="rcb-tallas-list" style="margin-bottom: 12px;">
                            <span style="color: red;"><strong>XS: 2</strong></span><br>
                        </div>
                        <div style="border-top: 1.5px solid #1f2937; margin-top: 8px; padding-top: 4px;">
                            <span style="color: #1f2937; font-weight: 700; font-size: 13.4px;">TOTAL: <strong id="rcb-total-qty">2</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="receipt-title">RECIBO DE CORTE<br>PARA BODEGA</h2>
            <div id="rcb-order-pedido" class="pedido-number">#</div>

        </div>
    </div>
</div>

<div id="rcb-floating-buttons" class="rcb-floating-buttons-outside">
    <button id="rcb-btn-close" type="button" title="Cerrar" onclick="closeReciboCorteBodegaModal()">
        <i class="fas fa-times"></i>
    </button>
    <button id="rcb-btn-print" type="button" title="Imprimir" onclick="printReciboCorteBodegaModal()">
        <i class="fas fa-print"></i>
    </button>
</div>

<style>
.rcb-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9997;
    display: none;
    pointer-events: auto;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.rcb-modal-overlay.is-visible {
    display: block;
    opacity: 1;
    visibility: visible;
}

#rcb-modal-wrapper {
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

#rcb-modal-wrapper.is-visible {
    display: block !important;
    opacity: 1;
    visibility: visible;
}

#rcb-modal-wrapper .receipt-title {
    margin-bottom: 16px !important;
}

#rcb-order-pedido.pedido-number {
    position: absolute !important;
    top: 168px !important;
    right: 20px !important;
    margin: 0 !important;
    text-align: right !important;
}

#rcb-btn-close {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 22px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

#rcb-btn-close:hover {
    transform: scale(1.05);
    filter: brightness(0.95);
}

/* Fecha del recibo de corte: igual al diseño de referencia */
#rcb-date.order-date {
    background: #000 !important;
    border-radius: 10px !important;
    padding: 6px !important;
    min-width: 128px !important;
    text-align: center !important;
}

#rcb-date .fec-label {
    color: #fff !important;
    font-weight: 700 !important;
    font-size: 11px !important;
    letter-spacing: .5px !important;
    margin-bottom: 4px !important;
}

#rcb-date .date-boxes {
    display: flex !important;
    justify-content: center !important;
    gap: 4px !important;
}

#rcb-date .date-box {
    background: #fff !important;
    color: #111 !important;
    border: none !important;
    border-radius: 4px !important;
    min-width: 36px !important;
    padding: 4px 4px !important;
    font-size: 12px !important;
    font-weight: 800 !important;
}

.rcb-floating-buttons-outside {
    position: fixed;
    left: calc(50% + 336px + 1rem);
    top: 50%;
    transform: translateY(-50%);
    display: none;
    flex-direction: column;
    gap: 12px;
    z-index: 10000;
    pointer-events: auto;
}

.rcb-floating-buttons-outside.is-visible {
    display: flex;
}

#rcb-btn-print {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #27ae60, #229954);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@media (max-width: 1100px) {
    .rcb-floating-buttons-outside {
        left: auto;
        right: 16px;
    }
}

@media print {
    .rcb-modal-overlay {
        display: none !important;
    }

    #rcb-modal-wrapper {
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        transform: none !important;
        display: block !important;
        z-index: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #rcb-floating-buttons {
        display: none !important;
    }

    #rcb-btn-close {
        display: none !important;
    }

    .order-detail-card {
        box-shadow: none !important;
        page-break-after: always;
    }
}
</style>

<script>
function openReciboCorteBodegaModal(id) {
    console.log('[RCB] Abriendo modal para id:', id);

    fetch(`/api/recibo-corte-bodega/${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar el recibo');
            return response.json();
        })
        .then(data => {
            console.log('[RCB] Datos recibidos:', data);

            if (data.success) {
                // Llenar datos
                document.getElementById('rcb-day').textContent = data.dia;
                document.getElementById('rcb-month').textContent = data.mes;
                document.getElementById('rcb-year').textContent = data.ano;

                document.getElementById('rcb-prenda-title').textContent = 'PRENDA 1';
                document.getElementById('rcb-prenda-desc').textContent = data.descripcion || '';
                document.getElementById('rcb-total-qty').textContent = data.total;
                const numeroRecibo = Number(data.numero_recibo ?? 0);
                document.getElementById('rcb-order-pedido').textContent = numeroRecibo > 0 ? `#${numeroRecibo}` : '#';

                // Llenar tallas
                const tallasList = document.getElementById('rcb-tallas-list');
                tallasList.innerHTML = '';

                const grupos = new Map();
                const sinGenero = [];

                data.tallas.forEach(talla => {
                    const genero = (talla.genero || '').toString().trim().toUpperCase();
                    const tallaValor = (talla.talla || '').toString().trim().toUpperCase();
                    const color = (talla.color || '').toString().trim().toUpperCase();
                    const esSoloCantidad = tallaValor === '' || tallaValor === 'UNICA';
                    const detalle = esSoloCantidad
                        ? `${talla.cantidad}`
                        : (color !== ''
                            ? `COLOR ${color}: ${talla.talla}:${talla.cantidad}`
                            : `${talla.talla}:${talla.cantidad}`);

                    if (genero !== '') {
                        if (!grupos.has(genero)) grupos.set(genero, []);
                        grupos.get(genero).push(detalle);
                    } else {
                        sinGenero.push(detalle);
                    }
                });

                grupos.forEach((detalles, genero) => {
                    const linea = `${genero} - ${detalles.join(', ')}`;
                    const span = document.createElement('div');
                    span.innerHTML = `<span style="color: red;"><strong>${linea}</strong></span><br>`;
                    tallasList.appendChild(span);
                });

                if (sinGenero.length > 0) {
                    const span = document.createElement('div');
                    span.innerHTML = `<span style="color: red;"><strong>${sinGenero.join(', ')}</strong></span><br>`;
                    tallasList.appendChild(span);
                }

                // Mostrar modal
                document.getElementById('rcb-modal-wrapper').classList.add('is-visible');
                document.getElementById('rcb-modal-overlay').classList.add('is-visible');
                document.getElementById('rcb-floating-buttons').classList.add('is-visible');
            }
        })
        .catch(error => {
            console.error('[RCB] Error:', error);
            alert('Error al cargar el recibo: ' + error.message);
        });
}

function closeReciboCorteBodegaModal() {
    document.getElementById('rcb-modal-wrapper').classList.remove('is-visible');
    document.getElementById('rcb-modal-overlay').classList.remove('is-visible');
    document.getElementById('rcb-floating-buttons').classList.remove('is-visible');
}

function printReciboCorteBodegaModal() {
    const day = (document.getElementById('rcb-day')?.textContent || '').trim();
    const month = (document.getElementById('rcb-month')?.textContent || '').trim();
    const year = (document.getElementById('rcb-year')?.textContent || '').trim();
    const prendaTitle = (document.getElementById('rcb-prenda-title')?.textContent || '').trim();
    const descripcion = (document.getElementById('rcb-prenda-desc')?.textContent || '').trim();
    const total = (document.getElementById('rcb-total-qty')?.textContent || '').trim();
    const tallasText = (document.getElementById('rcb-tallas-list')?.innerText || '').trim();

    const esc = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const tallasHtml = esc(tallasText).replace(/\r?\n/g, '<br>');

    const printHtml = `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recibo Corte Bodega</title>
  <style>
    @page { size: A4; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: #111; background: #ffffff; }
    body.singlepage { width: 100%; min-height: 100vh; display: flex; justify-content: center; }
    .page { width: 210mm; min-height: 277mm; padding: 0; display: flex; }
    .receipt-shell { width: 100%; background: #dcdcdc; border: 3px solid #111; border-radius: 20px; padding: 12px 14px 24px; }
    .receipt-card { width: 100%; min-height: 100%; border: none; border-radius: 0; padding: 0; }
    .logo-row { text-align: center; margin-bottom: 8px; }
    .order-logo { width: 200px; height: 64px; object-fit: contain; }
    .header-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 8px; }
    .order-date { width: 128px; background-color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border-radius: 10px; padding: 6px; color: #fff; text-align: center; }
    .fec-label { font-weight: 700; font-size: 11px; letter-spacing: .5px; margin-bottom: 4px; }
    .date-boxes { display: flex; gap: 4px; justify-content: center; }
    .date-box { background-color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color: #111; border-radius: 4px; min-width: 36px; padding: 4px 4px; font-size: 12px; font-weight: 800; }
    .title { flex: 1; text-align: right; font-size: 24px; line-height: 1.05; font-weight: 900; letter-spacing: .2px; }
    .content { margin-top: 20px; max-width: 68%; }
    .prenda { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
    .desc { font-size: 17px; color: #646464; margin-bottom: 14px; text-transform: uppercase; }
    .section-title { font-size: 18px; font-weight: 800; margin-bottom: 6px; }
    .tallas { font-size: 19px; font-weight: 800; color: #d00; line-height: 1.35; text-transform: uppercase; }
    .total { margin-top: 8px; padding-top: 6px; border-top: 2px solid #9ca3af; font-size: 20px; font-weight: 800; color: #1f2937; width: 180px; }
  </style>
</head>
<body class="singlepage">
  <div class="page">
    <div class="receipt-shell">
      <div class="receipt-card">
        <div class="logo-row">
          <img src="/images/logo2.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        </div>
        <div class="header-row">
          <div class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
              <div class="date-box">${esc(day)}</div>
              <div class="date-box">${esc(month)}</div>
              <div class="date-box">${esc(year)}</div>
            </div>
          </div>
          <div class="title">RECIBO DE CORTE<br>PARA BODEGA</div>
        </div>

        <div class="content">
          <div class="prenda">${esc(prendaTitle)}</div>
          <div class="desc">${esc(descripcion)}</div>
          <div class="section-title">TALLAS</div>
          <div class="tallas">${tallasHtml}</div>
          <div class="total">TOTAL: ${esc(total)}</div>
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

// Cerrar modal con Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('rcb-modal-wrapper').classList.contains('is-visible')) {
        closeReciboCorteBodegaModal();
    }
});
</script>
