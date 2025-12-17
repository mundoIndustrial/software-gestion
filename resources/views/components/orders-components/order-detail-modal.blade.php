<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">

<div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
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
<div style="position: fixed; right: 10px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10000;">
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
    const container = document.querySelector('.order-detail-modal-container');
    container.style.padding = '1.5cm';  // Restaurar padding original
    container.style.alignItems = 'center';  // Restaurar center
    container.style.justifyContent = 'center';  // Restaurar center
    
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
    
    // Configurar el contenedor para la galería
    const container = document.querySelector('.order-detail-modal-container');
    container.style.padding = '0';
    container.style.alignItems = 'stretch';  // Cambiar de center a stretch
    container.style.justifyContent = 'flex-start';  // Cambiar de center a flex-start
    
    // Crear galería si no existe
    let galeria = document.getElementById('galeria-modal');
    if (!galeria) {
        galeria = document.createElement('div');
        galeria.id = 'galeria-modal';
        galeria.style.cssText = 'width: 100%; margin: 0; padding: 0; display: flex; flex-direction: column; height: 100%;';
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
            console.log('📊 [GALERIA] Total prendas:', data.prendas?.length || 0);
            
            // Construir array de todas las imágenes para el visor
            allImages = [];
            let html = '<div style="background: linear-gradient(135deg, #1e40af, #0ea5e9); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERIA</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            
            console.log('📦 [GALERIA] Iniciando construcción de galería...');
            
            // Mostrar prendas con sus imágenes (separando fotos de prenda/tela de fotos de logo)
            let fotosLogo = [];
            
            if (data.prendas && data.prendas.length > 0) {
                data.prendas.forEach((prenda, idx) => {
                    if (prenda.imagenes && prenda.imagenes.length > 0) {
                        // Separar fotos de logo de las demás
                        const fotosPrendaTela = prenda.imagenes.filter(img => img.type !== 'logo');
                        const fotosLogoPrend = prenda.imagenes.filter(img => img.type === 'logo');
                        
                        console.log(`📍 [GALERIA] PRENDA ${idx + 1}:`, {
                            nombre: prenda.nombre,
                            total_imagenes: prenda.imagenes.length,
                            fotos_prenda_tela: fotosPrendaTela.length,
                            fotos_logo: fotosLogoPrend.length,
                            mostrara_en_galeria: fotosPrendaTela.length > 0 ? '✅ SÍ' : '❌ NO'
                        });
                        
                        // Guardar fotos de logo para mostrar al final
                        if (fotosLogoPrend.length > 0) {
                            fotosLogo.push({
                                prenda: prenda,
                                fotos: fotosLogoPrend
                            });
                        }
                        
                        // Mostrar solo fotos de prenda y tela
                        if (fotosPrendaTela.length > 0) {
                            const fotosAMostrar = fotosPrendaTela.slice(0, 4);
                            const fotosOcultas = Math.max(0, fotosPrendaTela.length - 4);
                            
                            console.log(`📸 [GALERIA] Renderizando prenda ${prenda.numero}:`, {
                                fotos_a_mostrar: fotosAMostrar.length,
                                fotos_ocultas: fotosOcultas,
                                mostrara_plus: fotosOcultas > 0 ? '✅ Sí (+' + fotosOcultas + ')' : '❌ No'
                            });
                            
                            html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                                <div style="border-left: 4px solid #1e40af; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #1e40af; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                        PRENDA ${prenda.numero}:<br>${prenda.nombre.toUpperCase()}
                                    </h3>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                            
                            fotosAMostrar.forEach(image => {
                                const imageIndex = allImages.length;
                                allImages.push(image);
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                    onmouseover="this.style.borderColor='#1e40af'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewer(${imageIndex})">
                                    <img src="${image.url}" alt="Foto ${prenda.nombre}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>`;
                            });
                            
                            if (fotosOcultas > 0) {
                                const firstOccultaIndex = allImages.length;
                                // Agregar las fotos ocultas al array
                                fotosPrendaTela.slice(4).forEach(image => {
                                    allImages.push(image);
                                });
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #1e40af; cursor: pointer; border: 2px solid #1e40af; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                    onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewer(${firstOccultaIndex})">
                                    <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                                </div>`;
                            }
                            
                            html += '</div></div>';
                        }
                    }
                });
                
                // Mostrar fotos de logo al final
                if (fotosLogo.length > 0) {
                    console.log('🖼️ [GALERIA] Mostrando fotos de logo. Total grupos:', fotosLogo.length);
                    
                    fotosLogo.forEach(item => {
                        const fotosAMostrar = item.fotos.slice(0, 4);
                        const fotosOcultas = Math.max(0, item.fotos.length - 4);
                        
                        html += `<div style="margin-top: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                            <div style="border-left: 4px solid #dc2626; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                <h3 style="font-size: 0.65rem; font-weight: 700; color: #dc2626; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                    LOGO/BORDADO
                                </h3>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                        
                        fotosAMostrar.forEach(image => {
                            const imageIndex = allImages.length;
                            allImages.push(image);
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                onmouseover="this.style.borderColor='#dc2626'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewer(${imageIndex})">
                                <img src="${image.url}" alt="Foto Logo" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>`;
                        });
                        
                        if (fotosOcultas > 0) {
                            const firstOccultaIndex = allImages.length;
                            // Agregar las fotos ocultas al array
                            item.fotos.slice(4).forEach(image => {
                                allImages.push(image);
                            });
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #dc2626; cursor: pointer; border: 2px solid #dc2626; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewer(${firstOccultaIndex})">
                                <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                            </div>`;
                        }
                        
                        html += '</div></div>';
                    });
                } else {
                    console.log('⚠️ [GALERIA] No hay fotos de logo para mostrar');
                }
                
                console.log('✅ [GALERIA] Total de imágenes cargadas:', allImages.length);
                console.log('📋 [GALERIA] Estructura de diseño:', {
                    total_prendas: data.prendas.length,
                    total_imagenes_cargadas: allImages.length,
                    tiene_fotos_logo: fotosLogo.length > 0,
                    secciones_logo: fotosLogo.length
                });
            } else {
                console.warn('⚠️ [GALERIA] No hay imágenes para mostrar');
                html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay imágenes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
            console.log('✅ [GALERIA] HTML de galería generado y renderizado en el DOM');
            console.log('📊 [GALERIA] Resumen final del diseño:');
            console.table({
                'Header': '✅ Azul con texto GALERIA',
                'Contenedor': '✅ Flex con gap 12px',
                'Prendas': data.prendas?.length || 0 + ' prendas mostradas',
                'Fotos por prenda': '4 principales + botón si hay más',
                'Total imágenes cargadas': allImages.length,
                'Logos': fotosLogo.length > 0 ? '✅ Sí' : '❌ No'
            });
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
