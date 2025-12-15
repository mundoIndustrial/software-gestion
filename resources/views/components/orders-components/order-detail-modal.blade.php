<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">

<div class="order-detail-modal-container">
    <div class="order-detail-card">
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        <div id="order-date" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box"></div>
                <div class="date-box month-box"></div>
                <div class="date-box year-box"></div>
            </div>
        </div>
        <div id="order-asesora" class="order-asesora">ASESORA: <span id="asesora-value"></span></div>
        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="forma-pago-value"></span></div>
        <div id="order-cliente" class="order-cliente">CLIENTE: <span id="cliente-value"></span></div>
        <div id="order-descripcion" class="order-descripcion">
            <div id="descripcion-text"></div>
        </div>
        <h2 class="receipt-title">RECIBO DE COSTURA</h2>
        <div class="arrow-container">
            <button id="prev-arrow" class="arrow-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button id="next-arrow" class="arrow-btn" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
        <div id="order-pedido" class="pedido-number"></div>

        <div class="separator-line"></div>

        <div class="signature-section">
            <div class="signature-field">
                <span>ENCARGADO DE ORDEN:</span>
                <span id="encargado-value"></span>
            </div>
            <div class="vertical-separator"></div>
            <div class="signature-field">
                <span>PRENDAS ENTREGADAS:</span>
                <span id="prendas-entregadas-value"></span>
                <a href="#" id="ver-entregas" style="color: red; font-weight: bold;">VER ENTREGAS</a>
            </div>
        </div>
    </div>
</div>

<!-- Botones flotantes para cambiar a galería de fotos -->
<div style="position: fixed; right: 30px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10000;">
    <button id="btn-factura" type="button" title="Ver factura" onclick="toggleFactura()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <i class="fas fa-receipt"></i>
    </button>
    <button id="btn-galeria" type="button" title="Ver galería" onclick="toggleGaleria()" style="width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid #ddd; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <i class="fas fa-images"></i>
    </button>
</div>

<script>
function toggleFactura() {
    // Mostrar factura y ocultar galería
    document.querySelector('.order-detail-card').style.display = 'block';
    const galeria = document.getElementById('galeria-modal');
    if (galeria) galeria.style.display = 'none';
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-factura').style.border = 'none';
    document.getElementById('btn-factura').style.color = 'white';
    document.getElementById('btn-galeria').style.background = 'white';
    document.getElementById('btn-galeria').style.border = '2px solid #ddd';
    document.getElementById('btn-galeria').style.color = '#333';
}

function toggleGaleria() {
    // Ocultar factura y mostrar galería
    document.querySelector('.order-detail-card').style.display = 'none';
    
    // Crear galería si no existe
    let galeria = document.getElementById('galeria-modal');
    if (!galeria) {
        galeria = document.createElement('div');
        galeria.id = 'galeria-modal';
        galeria.style.cssText = 'padding: 20px;';
        document.querySelector('.order-detail-modal-container').appendChild(galeria);
    }
    galeria.style.display = 'block';
    
    // Cargar imágenes
    loadGaleria(galeria);
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura').style.background = 'white';
    document.getElementById('btn-factura').style.border = '2px solid #ddd';
    document.getElementById('btn-factura').style.color = '#333';
    document.getElementById('btn-galeria').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-galeria').style.border = 'none';
    document.getElementById('btn-galeria').style.color = 'white';
}

function loadGaleria(container) {
    // Obtener número de pedido
    const pedidoElement = document.getElementById('order-pedido');
    if (!pedidoElement) return;
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/);
    const pedido = pedidoMatch ? pedidoMatch[0] : null;
    
    if (!pedido) return;
    
    // Cargar imágenes
    fetch(`/registros/${pedido}/images`)
        .then(response => response.json())
        .then(data => {
            let html = '<h2 style="text-align: center; margin: 20px 0;">Galería de Imágenes</h2>';
            html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';
            
            if (data.images && data.images.length > 0) {
                data.images.forEach(image => {
                    html += `<div style="aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #f5f5f5; cursor: pointer;" onclick="window.open('${image.url}', '_blank')">
                        <img src="${image.url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>`;
                });
            } else {
                html += '<p style="grid-column: 1/-1; text-align: center; color: #999;">No hay imágenes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imágenes</p>';
        });
}
</script>
