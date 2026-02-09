<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">

<!-- Botón de cerrar modal -->
<button type="button" onclick="closeOrderDetailModalLogo()" style="position: absolute; top: 10px; right: 10px; background: #dc2626; color: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; z-index: 10000; transition: all 0.2s ease;" onmouseover="this.style.background='#991b1b'" onmouseout="this.style.background='#dc2626';">
    <span style="font-family: Arial, sans-serif; font-weight: bold;">×</span>
</button>

<div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
    <div class="order-detail-card">
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        <div id="order-date-logo" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box"></div>
                <div class="date-box month-box"></div>
                <div class="date-box year-box"></div>
            </div>
        </div>
        <div id="order-asesora-logo" class="order-asesora">ASESORA: <span id="asesora-value-logo"></span></div>
        <div id="order-forma-pago-logo" class="order-forma-pago" style="display: none;">FORMA DE PAGO: <span id="forma-pago-value-logo"></span></div>
        <div id="order-cliente-logo" class="order-cliente">CLIENTE: <span id="cliente-value-logo"></span></div>
        <div id="order-descripcion-logo" class="order-descripcion" style="margin: 1rem 0;">
            <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem; font-size: 0.875rem;">DESCRIPCIÓN:</label>
            <div id="descripcion-text-logo" style="
                width: 100%;
                min-height: 80px;
                padding: 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 0.875rem;
                font-family: inherit;
                background: #f9fafb;
                white-space: pre-wrap;
                word-break: break-word;
            "></div>
            <div style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
                <div>
                    <div style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; font-size: 0.875rem;">TÉCNICAS:</div>
                    <div id="logo-tecnicas" style="font-size: 0.875rem; color: #111827; white-space: pre-wrap; word-break: break-word;"></div>
                </div>
                <div>
                    <div style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; font-size: 0.875rem;">OBSERVACIONES TÉCNICAS:</div>
                    <div id="logo-observaciones-tecnicas" style="font-size: 0.875rem; color: #111827; white-space: pre-wrap; word-break: break-word;"></div>
                </div>
                <div>
                    <div style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; font-size: 0.875rem;">SECCIONES:</div>
                    <div id="logo-ubicaciones" style="font-size: 0.875rem; color: #111827; white-space: pre-wrap; word-break: break-word;"></div>
                </div>
            </div>
        </div>
        <h2 class="receipt-title">RECIBO DE LOGO</h2>
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
        <div id="order-pedido-logo" class="pedido-number"></div>

        <div class="separator-line"></div>

        <div class="signature-section">
            <div class="signature-field">
                <span>ENCARGADO DE ORDEN:</span>
                <span id="encargado-value-logo"></span>
            </div>
            <div class="vertical-separator"></div>
            <div class="signature-field">
                <span>PRENDAS ENTREGADAS:</span>
                <span id="prendas-entregadas-value-logo"></span>
                <a href="#" id="ver-entregas-logo" style="color: red; font-weight: bold;">VER ENTREGAS</a>
            </div>
        </div>
    </div>
</div>

<!-- Botones flotantes para cambiar a galería de fotos -->
<div id="floating-buttons-container-logo" style="position: fixed; right: 10px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10000;">
    <button id="btn-factura-logo" type="button" title="Ver factura" onclick="toggleFacturaLogo()" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <i class="fas fa-receipt"></i>
    </button>
    <button id="btn-galeria-logo" type="button" title="Ver galería" onclick="toggleGaleriaLogo()" style="width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid #ddd; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <i class="fas fa-images"></i>
    </button>
</div>

<script>
let allImagesLogo = [];
let currentImageIndexLogo = 0;
let currentPedidoNumberLogo = null; // Variable global para guardar el número de pedido

function toggleFacturaLogo() {
    console.trace(' [TOGGLE FACTURA LOGO] Stack trace de quién llamó esta función');
    
    //  IMPORTANTE: Buscar SOLO dentro del modal de logo
    const modalWrapper = document.getElementById('order-detail-modal-wrapper-logo');
    if (!modalWrapper) {
        return;
    }
    
    // Mostrar factura y ocultar galería
    const container = modalWrapper.querySelector('.order-detail-modal-container');
    if (container) {
        container.style.padding = '1.5cm';  // Restaurar padding original
        container.style.alignItems = 'center';  // Restaurar center
        container.style.justifyContent = 'center';  // Restaurar center
        container.style.height = 'auto';  //  Restaurar altura automática
        container.style.width = '100%';
    }
    
    //  RESTAURAR el tamaño original del wrapper
    modalWrapper.style.maxWidth = '672px';
    modalWrapper.style.width = '90%';
    modalWrapper.style.height = 'auto';
    const card = modalWrapper.querySelector('.order-detail-card');
    if (card) card.style.display = 'block';
    
    const galeria = document.getElementById('galeria-modal-logo');
    if (galeria) galeria.style.display = 'none';
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura-logo').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-factura-logo').style.border = 'none';
    document.getElementById('btn-factura-logo').style.color = 'white';
    document.getElementById('btn-galeria-logo').style.background = 'white';
    document.getElementById('btn-galeria-logo').style.border = '2px solid #ddd';
    document.getElementById('btn-galeria-logo').style.color = '#333';
}

function toggleGaleriaLogo() {
    //  IMPORTANTE: Buscar SOLO dentro del modal de logo
    const modalWrapper = document.getElementById('order-detail-modal-wrapper-logo');
    if (!modalWrapper) {
        return;
    }
    
    // Ocultar factura y mostrar galería
    const card = modalWrapper.querySelector('.order-detail-card');
    if (card) {
        card.style.display = 'none';
    }
    
    // Configurar el contenedor para la galería
    const container = modalWrapper.querySelector('.order-detail-modal-container');
    if (container) {
        //  Remover padding para que el header quede pegado arriba
        container.style.padding = '0';
        container.style.alignItems = 'stretch';
        container.style.justifyContent = 'flex-start';
        container.style.height = 'auto';
        container.style.width = '100%';
    }
    
    // Crear galería si no existe
    let galeria = document.getElementById('galeria-modal-logo');
    if (!galeria) {
        galeria = document.createElement('div');
        galeria.id = 'galeria-modal-logo';
        galeria.style.cssText = 'width: 100%; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 400px; max-height: 600px; overflow-y: auto;';
        //  IMPORTANTE: Agregar al container del modal de LOGO, no al de costura
        if (container) {
            container.appendChild(galeria);
        } else {
            return;
        }
    }
    
    galeria.style.display = 'flex';

    //  Obtener número de pedido directamente del DOM (usando ID único del modal logo)
    const pedidoElement = document.getElementById('order-pedido-logo');
    if (!pedidoElement) {
        galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/); // Buscar solo dígitos (ahora es 00120)
    const pedido = pedidoMatch ? pedidoMatch[0] : null;

    if (!pedido) {
        galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    
    // Cargar imágenes de logo
    loadGaleriaLogo(galeria, pedido);
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura-logo').style.background = 'white';
    document.getElementById('btn-factura-logo').style.border = '2px solid #ddd';
    document.getElementById('btn-factura-logo').style.color = '#333';
    document.getElementById('btn-galeria-logo').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-galeria-logo').style.border = 'none';
    document.getElementById('btn-galeria-logo').style.color = 'white';
    console.log('  - Card display:', document.querySelector('.order-detail-card')?.style.display);


    console.log('  - Container height:', document.querySelector('.order-detail-modal-container')?.style.height);
    console.log('  - Wrapper height:', document.getElementById('order-detail-modal-wrapper-logo')?.style.height);
}

function loadGaleriaLogo(container, pedido) {
    // Validar que tenemos el número de pedido
    if (!pedido) {
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    //  Remover el # del número de pedido si existe
    const pedidoLimpio = pedido.replace('#', '');
    
    // Cargar imágenes de logo
    const url = `/registros/${pedidoLimpio}/images?tipo=logo`;
    fetch(url)
        .then(response => {
            return response.json();
        })
        .then(data => {
            // Construir array de todas las imágenes para el visor
            allImagesLogo = [];
            let html = '<div style="background: linear-gradient(135deg, #dc2626, #991b1b); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERIA DE BORDADOS</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            // Mostrar solo fotos de logo
            if (data.logos && data.logos.length > 0) {
                data.logos.forEach((logo, idx) => {
                    if (logo.imagenes && logo.imagenes.length > 0) {
                        const fotosAMostrar = logo.imagenes.slice(0, 4);
                        const fotosOcultas = Math.max(0, logo.imagenes.length - 4);
                        html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                            <div style="border-left: 4px solid #dc2626; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                <h3 style="font-size: 0.65rem; font-weight: 700; color: #dc2626; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                    BORDADO${logo.ubicacion ? ' - ' + logo.ubicacion.toUpperCase() : ''}
                                </h3>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                        
                        fotosAMostrar.forEach(image => {
                            const imageIndex = allImagesLogo.length;
                            allImagesLogo.push(image);
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                onmouseover="this.style.borderColor='#dc2626'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerLogo(${imageIndex})">
                                <img src="${image.url}" alt="Foto Bordado" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>`;
                        });
                        
                        if (fotosOcultas > 0) {
                            const firstOccultaIndex = allImagesLogo.length;
                            // Agregar las fotos ocultas al array
                            logo.imagenes.slice(4).forEach(image => {
                                allImagesLogo.push(image);
                            });
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #dc2626; cursor: pointer; border: 2px solid #dc2626; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerLogo(${firstOccultaIndex})">
                                <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                            </div>`;
                        }
                        
                        html += '</div></div>';
                    }
                });
            } else {
                html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay imágenes de bordado para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
            // DEBUG: Verificar que el HTML está en el DOM y es visible




        })
        .catch(error => {
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imágenes de bordado</p>';
        });
}

function openImageViewerLogo(index) {
    currentImageIndexLogo = index;
    // Crear modal si no existe
    let modal = document.getElementById('image-viewer-modal-logo');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'image-viewer-modal-logo';
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
        z-index: 100000;
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
            <button class="image-viewer-close" onclick="closeImageViewerLogo()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="image-viewer-content">
                <img id="viewer-image" src="${allImagesLogo[index].url}" alt="Imagen ampliada">
            </div>
            
            <div class="image-viewer-nav">
                <button onclick="previousImageLogo()" ${index === 0 ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button onclick="nextImageLogo()" ${index === allImagesLogo.length - 1 ? 'disabled' : ''}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            
            <div class="image-viewer-counter">${index + 1} / ${allImagesLogo.length}</div>
        </div>
    `;
    
    modal.innerHTML = html;
    modal.style.display = 'flex';
    
    // Agregar listeners de teclado
    document.addEventListener('keydown', handleImageViewerKeyboardLogo);
}

function closeImageViewerLogo() {
    const modal = document.getElementById('image-viewer-modal-logo');
    if (modal) {
        modal.style.display = 'none';
    }
    document.removeEventListener('keydown', handleImageViewerKeyboardLogo);
}

function nextImageLogo() {
    if (currentImageIndexLogo < allImagesLogo.length - 1) {
        openImageViewerLogo(currentImageIndexLogo + 1);
    }
}

function previousImageLogo() {
    if (currentImageIndexLogo > 0) {
        openImageViewerLogo(currentImageIndexLogo - 1);
    }
}

function handleImageViewerKeyboardLogo(e) {
    if (document.getElementById('image-viewer-modal-logo').style.display === 'none') return;
    
    if (e.key === 'ArrowRight') {
        nextImageLogo();
    } else if (e.key === 'ArrowLeft') {
        previousImageLogo();
    } else if (e.key === 'Escape') {
        closeImageViewerLogo();
    }
}

// DEBUG: Verificar si el evento se dispara
document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('load-order-detail-logo', function(e) {
    });
});
</script>

