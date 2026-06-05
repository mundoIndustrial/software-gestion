<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
<link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}" media="print">

<div id="rcb-modal-overlay" class="rcb-modal-overlay" onclick="closeReciboCorteBodegaModal()"></div>

<div id="rcb-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
        <div id="rcb-order-card" class="order-detail-card" style="display: block;">
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
        <div id="galeria-modal-costura-rcb" class="rcb-galeria-modal" style="display:none;">
            <div class="rcb-galeria-header">
                <h2>GALERÍA</h2>
            </div>
            <div id="rcb-galeria-body" class="rcb-galeria-body"></div>
        </div>
    </div>
</div>

<div id="rcb-floating-buttons" class="rcb-floating-buttons-outside">
    <button id="btn-factura" type="button" title="Ver galería" onclick="toggleFactura()">
        <i class="fas fa-images"></i>
    </button>
    <button id="rcb-btn-print" type="button" title="Imprimir" onclick="printReciboCorteBodegaModal()">
        <i class="fas fa-print"></i>
    </button>
    <button id="rcb-btn-zoom" type="button" title="Zoom recibo (100%)" onclick="toggleReciboCorteBodegaZoom()">
        <i class="fas fa-search-plus"></i>
    </button>
</div>
<button id="btn-cerrar-modal-dinamico-rcb" type="button" title="Cerrar" onclick="closeReciboCorteBodegaModal()">
    <i class="fas fa-times"></i>
</button>

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

#btn-cerrar-modal-dinamico-rcb {
    position: fixed;
    right: 10px;
    top: 10px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    color: #333;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 10001;
    font-weight: bold;
}

#btn-cerrar-modal-dinamico-rcb.is-visible {
    display: flex;
}

#btn-cerrar-modal-dinamico-rcb:hover {
    transform: scale(1.04);
    background: #fff;
}

.rcb-fotos-section {
    margin: 10px 0 8px;
}

.rcb-fotos-grid {
    margin-top: 6px;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 6px;
}

.rcb-foto-thumb {
    display: block;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dbeafe;
    background: #fff;
}

.rcb-foto-thumb img {
    display: block;
    width: 100%;
    height: 72px;
    object-fit: contain;
    background: #f8fafc;
}

.rcb-galeria-modal {
    width: 668px;
    max-width: 100%;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    min-height: 520px;
    max-height: 820px;
    overflow-y: auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.rcb-galeria-header {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    padding: 16px 12px;
    border-radius: 12px 12px 0 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.rcb-galeria-header h2 {
    text-align: center;
    margin: 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 1px;
}

.rcb-galeria-body {
    padding: 24px;
    background: #fff;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.rcb-galeria-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all .3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.rcb-galeria-card:hover {
    border-color: #3b82f6;
    transform: scale(1.03);
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

.rcb-galeria-card img {
    width: 100%;
    height: 220px;
    object-fit: contain;
    display: block;
    background: #f8fafc;
}

.rcb-galeria-card-footer {
    padding: 0.75rem;
    background: #f9fafb;
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

#btn-factura {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e40af, #0ea5e9);
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

#btn-factura:hover {
    transform: scale(1.05);
    filter: brightness(0.95);
}

#rcb-btn-zoom {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
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

#rcb-btn-zoom.is-zoomed {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

#rcb-btn-print:hover,
#rcb-btn-zoom:hover {
    transform: scale(1.05);
    filter: brightness(0.95);
}

#rcb-modal-wrapper.zoomed {
    max-width: 900px !important;
    width: 96% !important;
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

    #rcb-btn-zoom {
        display: none !important;
    }

    #btn-cerrar-modal-dinamico-rcb {
        display: none !important;
    }

    .order-detail-card {
        box-shadow: none !important;
        page-break-after: always;
    }
}
</style>

<script>
const rcbReceiptZoomState = {
    current: 1,
    levels: [1, 1.2, 1.4, 1.6],
};

function applyReciboCorteBodegaZoom(zoomLevel = 1) {
    const wrapper = document.getElementById('rcb-modal-wrapper');
    const card = wrapper?.querySelector('.order-detail-card');
    const zoomBtn = document.getElementById('rcb-btn-zoom');
    if (!wrapper || !card) return;

    card.style.transformOrigin = 'center top';
    card.style.transition = 'transform 0.2s ease';

    if (typeof card.style.zoom !== 'undefined') {
        card.style.zoom = String(zoomLevel);
        card.style.transform = 'none';
    } else {
        card.style.zoom = '';
        card.style.transform = `scale(${zoomLevel})`;
    }

    if (zoomBtn) {
        const percent = Math.round(zoomLevel * 100);
        zoomBtn.title = `Zoom recibo (${percent}%)`;
        zoomBtn.classList.toggle('is-zoomed', zoomLevel > 1);
        zoomBtn.innerHTML = zoomLevel > 1
            ? '<i class="fas fa-search-minus"></i>'
            : '<i class="fas fa-search-plus"></i>';
    }
}

function resetReciboCorteBodegaZoom() {
    rcbReceiptZoomState.current = 1;
    applyReciboCorteBodegaZoom(1);
}

function abrirVisorImagenRcb(indice, fotos) {
    const images = Array.isArray(fotos) ? fotos.filter(Boolean) : [];
    if (images.length === 0) return;

    if (typeof window.abrirModalImagenProcesoGrande === 'function') {
        window.abrirModalImagenProcesoGrande(indice, images);
        return;
    }

    let current = Math.max(0, Math.min(Number(indice || 0), images.length - 1));
    const existing = document.getElementById('rcb-image-viewer-fallback');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'rcb-image-viewer-fallback';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:10020;display:flex;align-items:center;justify-content:center;';

    const render = () => {
        modal.innerHTML = `
            <button type="button" data-close="1" style="position:absolute;top:16px;right:16px;width:42px;height:42px;border-radius:50%;border:none;background:#fff;color:#111;font-size:24px;cursor:pointer;">×</button>
            <button type="button" data-prev="1" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;border:none;background:rgba(255,255,255,.85);font-size:22px;cursor:pointer;" ${current === 0 ? 'disabled' : ''}>‹</button>
            <img src="${images[current]}" alt="Imagen ${current + 1}" style="max-width:92vw;max-height:86vh;object-fit:contain;background:#0b1220;border-radius:10px;">
            <button type="button" data-next="1" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);width:42px;height:42px;border-radius:50%;border:none;background:rgba(255,255,255,.85);font-size:22px;cursor:pointer;" ${current === images.length - 1 ? 'disabled' : ''}>›</button>
            <div style="position:absolute;bottom:16px;left:50%;transform:translateX(-50%);color:#fff;font-weight:700;">${current + 1} / ${images.length}</div>
        `;
    };

    render();
    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.closest('[data-close="1"]')) {
            modal.remove();
            return;
        }
        if (e.target.closest('[data-prev="1"]') && current > 0) {
            current -= 1;
            render();
            return;
        }
        if (e.target.closest('[data-next="1"]') && current < images.length - 1) {
            current += 1;
            render();
        }
    });
    document.body.appendChild(modal);
}

function toggleFactura() {
    console.log('[RCB] toggleFactura() click');
    const card = document.getElementById('rcb-order-card');
    const galeria = document.getElementById('galeria-modal-costura-rcb');
    const galeriaBody = document.getElementById('rcb-galeria-body');
    if (!card || !galeria || !galeriaBody) {
        console.warn('[RCB] toggleFactura abortado: faltan nodos', {
            card: !!card,
            galeria: !!galeria,
            galeriaBody: !!galeriaBody,
        });
        return;
    }
    if (galeriaBody.children.length === 0) {
        console.warn('[RCB] toggleFactura abortado: galeria sin items');
        return;
    }

    const galeriaVisible = galeria.style.display !== 'none';
    console.log('[RCB] toggleFactura estado', {
        galeriaVisible,
        items: galeriaBody.children.length,
    });
    card.style.display = galeriaVisible ? 'block' : 'none';
    galeria.style.display = galeriaVisible ? 'none' : 'flex';
}

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
                renderReciboCorteBodegaData(data);
            }
        })
        .catch(error => {
            console.error('[RCB] Error:', error);
            alert('Error al cargar el recibo: ' + error.message);
        });
}

function aplicarTituloReciboCorteBodega(tipoRecibo = '') {
    const tipoNormalizado = String(tipoRecibo || '').trim().toUpperCase();
    const esReciboCostura = ['COSTURA', 'COSTURA-BODEGA'].includes(tipoNormalizado);
    const titleNode = document.querySelector('#rcb-modal-wrapper .receipt-title');
    if (!titleNode) return;

    titleNode.innerHTML = esReciboCostura
        ? 'RECIBO DE COSTURA'
        : 'RECIBO DE CORTE<br>PARA BODEGA';
}

function openReciboCorteBodegaParcialModal(id, tipoRecibo = '') {
    console.log('[RCB] Abriendo modal parcial para id:', id, 'tipo:', tipoRecibo);

    aplicarTituloReciboCorteBodega(tipoRecibo);

    fetch(`/api/recibo-corte-bodega/parcial/${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Error al cargar el recibo parcial');
            return response.json();
        })
        .then(data => {
            console.log('[RCB] Datos parcial recibidos:', data);
            if (data.success) {
                renderReciboCorteBodegaData(data);
            }
        })
        .catch(error => {
            console.error('[RCB] Error parcial:', error);
            alert('Error al cargar el recibo parcial: ' + error.message);
        });
}

function renderReciboCorteBodegaData(data) {
    aplicarTituloReciboCorteBodega(data.tipo_recibo);

    document.getElementById('rcb-day').textContent = data.dia;
    document.getElementById('rcb-month').textContent = data.mes;
    document.getElementById('rcb-year').textContent = data.ano;

    document.getElementById('rcb-prenda-title').textContent = 'PRENDA 1';
    document.getElementById('rcb-prenda-desc').textContent = data.descripcion || '';
    document.getElementById('rcb-total-qty').textContent = data.total;
    const numeroRecibo = Number(data.numero_recibo ?? 0);
    document.getElementById('rcb-order-pedido').textContent = numeroRecibo > 0 ? `#${numeroRecibo}` : '#';

    const tallasList = document.getElementById('rcb-tallas-list');
    tallasList.innerHTML = '';

    const grupos = new Map();
    const sinGenero = [];

    data.tallas.forEach((item) => {
        const genero = (item.genero || '').toString().trim().toUpperCase();
        const tallaValor = (item.talla || '').toString().trim().toUpperCase();
        const color = (item.color || '').toString().trim().toUpperCase();
        const cantidad = parseInt(item.cantidad || '0', 10);
        if (cantidad <= 0) return;

        const esSoloCantidad = tallaValor === '' || tallaValor === 'UNICA';
        if (genero === '') {
            const detalle = esSoloCantidad ? `${cantidad}` : `${tallaValor}:${cantidad}`;
            sinGenero.push(detalle);
            return;
        }

        if (!grupos.has(genero)) grupos.set(genero, new Map());
        const porColor = grupos.get(genero);
        const colorKey = color !== '' ? color : 'SIN COLOR';
        if (!porColor.has(colorKey)) porColor.set(colorKey, []);

        if (esSoloCantidad) {
            porColor.get(colorKey).push(`${cantidad}`);
        } else {
            porColor.get(colorKey).push(`${tallaValor}:${cantidad}`);
        }
    });

    grupos.forEach((porColor, genero) => {
        const bloque = document.createElement('div');
        let html = `<span style="color: #1f2937;"><strong>${genero}</strong></span><br>`;
        porColor.forEach((detalles, color) => {
            const lineaColor = color === 'SIN COLOR'
                ? `${detalles.join(', ')}`
                : `${color}: ${detalles.join(', ')}`;
            html += `<span style="color: red;"><strong>${lineaColor}</strong></span><br>`;
        });
        bloque.innerHTML = html;
        tallasList.appendChild(bloque);
    });

    if (sinGenero.length > 0) {
        const span = document.createElement('div');
        span.innerHTML = `<span style="color: red;"><strong>${sinGenero.join(', ')}</strong></span><br>`;
        tallasList.appendChild(span);
    }

    const fotosSection = document.getElementById('rcb-fotos-section');
    const fotosGrid = document.getElementById('rcb-fotos-grid');
    const galeriaBody = document.getElementById('rcb-galeria-body');
    const card = document.getElementById('rcb-order-card');
    const galeria = document.getElementById('galeria-modal-costura-rcb');
    const fotos = Array.isArray(data.fotos) ? data.fotos : [];
    const urlsGaleria = [];
    if (fotosGrid) fotosGrid.innerHTML = '';
    if (galeriaBody) galeriaBody.innerHTML = '';

    fotos.forEach((foto, index) => {
        const rawUrl = foto?.ruta || foto?.url || foto?.ruta_webp || foto?.ruta_original || foto?.path || foto?.imagen || '';
        const urlNormalizada = String(rawUrl || '').trim();
        const url = urlNormalizada && !urlNormalizada.startsWith('http') && !urlNormalizada.startsWith('/')
            ? `/storage-serve/${urlNormalizada.replace(/^\/+/, '')}`
            : (urlNormalizada.startsWith('/storage/bodega/')
                ? urlNormalizada.replace('/storage/', '/storage-serve/')
                : urlNormalizada);
        if (!url) return;
        urlsGaleria.push(url);

        if (fotosGrid) {
            const item = document.createElement('a');
            item.className = 'rcb-foto-thumb';
            item.href = 'javascript:void(0)';
            item.title = `Imagen ${index + 1}`;
            item.innerHTML = `<img src="${url}" alt="Imagen recibo ${index + 1}" loading="lazy">`;
            item.addEventListener('click', (event) => {
                event.preventDefault();
                abrirVisorImagenRcb(index, window.__galeriaImagenes || urlsGaleria);
            });
            fotosGrid.appendChild(item);
        }

        if (galeriaBody) {
            const cardGaleria = document.createElement('div');
            cardGaleria.className = 'rcb-galeria-card';
            cardGaleria.innerHTML = `
                <img src="${url}" alt="Imagen ${index + 1}" loading="lazy">
                <div class="rcb-galeria-card-footer">
                    <div style="font-size: .75rem; color: #6b7280;">Imagen ${index + 1}</div>
                </div>
            `;
            cardGaleria.addEventListener('click', () => {
                abrirVisorImagenRcb(index, window.__galeriaImagenes || urlsGaleria);
            });
            galeriaBody.appendChild(cardGaleria);
        }
    });

    console.log('[RCB] fotos detectadas para galeria:', urlsGaleria.length);
    window.__galeriaImagenes = urlsGaleria;
    if (fotosSection) fotosSection.style.display = 'none';

    if (card) card.style.display = 'block';
    if (galeria) galeria.style.display = 'none';

    document.getElementById('rcb-modal-wrapper').classList.add('is-visible');
    document.getElementById('rcb-modal-overlay').classList.add('is-visible');
    document.getElementById('rcb-floating-buttons').classList.add('is-visible');
    document.getElementById('btn-cerrar-modal-dinamico-rcb')?.classList.add('is-visible');
    resetReciboCorteBodegaZoom();
}

window.renderReciboCorteBodegaData = renderReciboCorteBodegaData;
window.openReciboCorteBodegaParcialModal = openReciboCorteBodegaParcialModal;

function closeReciboCorteBodegaModal() {
    document.getElementById('rcb-modal-wrapper').classList.remove('is-visible');
    document.getElementById('rcb-modal-overlay').classList.remove('is-visible');
    document.getElementById('rcb-floating-buttons').classList.remove('is-visible');
    document.getElementById('btn-cerrar-modal-dinamico-rcb')?.classList.remove('is-visible');
    const card = document.getElementById('rcb-order-card');
    const galeria = document.getElementById('galeria-modal-costura-rcb');
    if (card) card.style.display = 'block';
    if (galeria) galeria.style.display = 'none';
    resetReciboCorteBodegaZoom();
}

function toggleReciboCorteBodegaZoom() {
    const { levels, current } = rcbReceiptZoomState;
    const currentIndex = levels.findIndex((level) => level === current);
    const nextIndex = currentIndex >= 0 ? (currentIndex + 1) % levels.length : 1;
    const nextLevel = levels[nextIndex];
    rcbReceiptZoomState.current = nextLevel;
    applyReciboCorteBodegaZoom(nextLevel);
}

function printReciboCorteBodegaModal() {
    const day = (document.getElementById('rcb-day')?.textContent || '').trim();
    const month = (document.getElementById('rcb-month')?.textContent || '').trim();
    const year = (document.getElementById('rcb-year')?.textContent || '').trim();
    const prendaTitle = (document.getElementById('rcb-prenda-title')?.textContent || '').trim();
    const descripcion = (document.getElementById('rcb-prenda-desc')?.textContent || '').trim();
    const total = (document.getElementById('rcb-total-qty')?.textContent || '').trim();
    const tallasText = (document.getElementById('rcb-tallas-list')?.innerText || '').trim();
    const numeroRecibo = (document.getElementById('rcb-order-pedido')?.textContent || '').trim();
    const receiptTitle = (document.querySelector('#rcb-modal-wrapper .receipt-title')?.innerHTML || 'RECIBO DE CORTE<br>PARA BODEGA').trim();

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
    body { margin: 0; font-family: Arial, sans-serif; color: #111; background: #fff; }
    body.singlepage { width: 100%; min-height: 100vh; display: flex; justify-content: center; }
    .page { width: 100%; max-width: 180mm; margin: 0 auto; }
    .receipt-card { border: 3px solid #111; border-radius: 18px; padding: 14px; }
    .order-logo { width: 150px; height: 56px; object-fit: contain; display: block; margin: 0 auto 8px; }
    .header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
    .order-date {
      width: 128px;
      background: #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      border-radius: 10px;
      padding: 6px;
      color: #fff;
      text-align: center;
    }
    .fec-label { font-weight: 700; font-size: 11px; margin-bottom: 4px; }
    .date-boxes { display: flex; gap: 4px; justify-content: center; }
    .date-box {
      background: #fff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color: #111;
      border-radius: 4px;
      min-width: 36px;
      padding: 4px;
      font-size: 12px;
      font-weight: 800;
    }
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
            <div class="date-box">${esc(day)}</div>
            <div class="date-box">${esc(month)}</div>
            <div class="date-box">${esc(year)}</div>
          </div>
        </div>
        <div class="header-right">
          <div class="receipt-title-print">${receiptTitle}</div>
          <div class="recibo-number-print">${esc(numeroRecibo)}</div>
        </div>
      </div>
      <div class="prenda-info">${esc(prendaTitle)}</div>
      <div class="desc">${esc(descripcion)}</div>
      <div class="section">
        <h4>TALLAS:</h4>
        <div class="tallas-resumen">${tallasHtml}</div>
      </div>
      <div class="total-line">TOTAL: ${esc(total)}</div>
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
