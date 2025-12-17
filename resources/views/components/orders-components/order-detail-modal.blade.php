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
let allImages = [];
let currentImageIndex = 0;

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
    console.log('🖼️ [GALERIA] Elemento pedido:', pedidoElement);
    if (!pedidoElement) {
        console.error('❌ [GALERIA] No se encontró elemento order-pedido');
        return;
    }
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/);
    const pedido = pedidoMatch ? pedidoMatch[0] : null;
    
    console.log('🖼️ [GALERIA] Número de pedido extraído:', pedido);
    if (!pedido) {
        console.error('❌ [GALERIA] No se pudo extraer número de pedido');
        return;
    }
    
    // Cargar imágenes
    const url = `/registros/${pedido}/images`;
    console.log('🖼️ [GALERIA] Haciendo fetch a:', url);
    
    fetch(url)
        .then(response => {
            console.log('🖼️ [GALERIA] Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🖼️ [GALERIA] Datos recibidos:', data);
            console.log('🖼️ [GALERIA] Total de imágenes:', data.images?.length || 0);
            
            allImages = data.images || [];
            let html = '<h2 style="text-align: center; margin: 20px 0;">Galería de Imágenes</h2>';
            html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; padding: 0 20px;">';
            
            if (allImages && allImages.length > 0) {
                allImages.forEach((image, index) => {
                    console.log(`🖼️ [GALERIA] Imagen ${index + 1}:`, image.url);
                    html += `<div style="aspect-ratio: 1; border-radius: 6px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid transparent; transition: all 0.2s ease;" 
                        onmouseover="this.style.borderColor='#1e40af'; this.style.transform='scale(1.05)';"
                        onmouseout="this.style.borderColor='transparent'; this.style.transform='scale(1)';"
                        onclick="openImageViewer(${index})">
                        <img src="${image.url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>`;
                });
            } else {
                console.warn('⚠️ [GALERIA] No hay imágenes para mostrar');
                html += '<p style="grid-column: 1/-1; text-align: center; color: #999;">No hay imágenes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
            console.log('✅ [GALERIA] HTML de galería generado');
        })
        .catch(error => {
            console.error('❌ [GALERIA] Error al cargar imágenes:', error);
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imágenes</p>';
        });
}

function openImageViewer(index) {
    currentImageIndex = index;
    console.log('🖼️ [VIEWER] Abriendo imagen:', index);
    
    // Crear modal si no existe
    let modal = document.getElementById('image-viewer-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'image-viewer-modal';
        document.body.appendChild(modal);
    }
    
    // Estilos del modal
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        animation: fadeIn 0.3s ease;
    `;
    
    // HTML del visor
    let html = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .image-viewer-container {
                position: relative;
                width: 90%;
                max-width: 900px;
                height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content img {
                width: 700px;
                height: 700px;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            @media (max-width: 900px) {
                .image-viewer-content img {
                    width: 500px;
                    height: 500px;
                }
            }
            @media (max-width: 600px) {
                .image-viewer-content img {
                    width: 350px;
                    height: 350px;
                }
            }
            .image-viewer-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 100%;
                display: flex;
                justify-content: space-between;
                padding: 0 20px;
                pointer-events: none;
            }
            .image-viewer-nav button {
                pointer-events: auto;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid white;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                transition: all 0.3s ease;
            }
            .image-viewer-nav button:hover {
                background: rgba(255, 255, 255, 0.4);
                transform: scale(1.1);
            }
            .image-viewer-nav button:disabled {
                opacity: 0.3;
                cursor: not-allowed;
            }
            .image-viewer-close {
                position: absolute;
                top: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid white;
                color: white;
                cursor: pointer;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                transition: all 0.3s ease;
            }
            .image-viewer-close:hover {
                background: rgba(255, 255, 255, 0.4);
                transform: rotate(90deg);
            }
            .image-viewer-counter {
                position: absolute;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                color: white;
                background: rgba(0, 0, 0, 0.5);
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
            }
        </style>
        
        <div class="image-viewer-container">
            <button class="image-viewer-close" onclick="closeImageViewer()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="image-viewer-content">
                <img id="viewer-image" src="${allImages[index].url}" alt="Imagen ampliada">
            </div>
            
            <div class="image-viewer-nav">
                <button onclick="previousImage()" ${index === 0 ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button onclick="nextImage()" ${index === allImages.length - 1 ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            
            <div class="image-viewer-counter">${index + 1} / ${allImages.length}</div>
        </div>
    `;
    
    modal.innerHTML = html;
    modal.style.display = 'flex';
    
    // Agregar listeners de teclado
    document.addEventListener('keydown', handleImageViewerKeyboard);
}

function closeImageViewer() {
    const modal = document.getElementById('image-viewer-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.removeEventListener('keydown', handleImageViewerKeyboard);
}

function nextImage() {
    if (currentImageIndex < allImages.length - 1) {
        openImageViewer(currentImageIndex + 1);
    }
}

function previousImage() {
    if (currentImageIndex > 0) {
        openImageViewer(currentImageIndex - 1);
    }
}

function handleImageViewerKeyboard(e) {
    if (document.getElementById('image-viewer-modal').style.display === 'none') return;
    
    if (e.key === 'ArrowRight') {
        nextImage();
    } else if (e.key === 'ArrowLeft') {
        previousImage();
    } else if (e.key === 'Escape') {
        closeImageViewer();
    }
}
</script>
