let allImagesMobile = [];
let currentImageIndexMobile = 0;
let currentPedidoNumeroMobile = null;

// Esta funciÃ³n serÃ¡ llamada desde ver-pedido.blade.php cuando se carguen las fotos
function loadGaleriaMobile(container) {
    // Obtener nÃºmero de pedido
    const pedidoElement = document.getElementById('factura-container-mobile');
    if (!pedidoElement) {
        return;
    }
    
    const pedido = String(pedidoElement.getAttribute('data-numero-pedido') || '').trim();
    if (!pedido) {
        return;
    }
    
    currentPedidoNumeroMobile = pedido;
    
    // Cargar imágenes
    window.OrderDetailMobileService.getGaleria(pedido)
        .then(data => {

            // Construir array de todas las imÃ¡genes para el visor
            allImagesMobile = [];
            let html = '<div style="background: linear-gradient(135deg, #1e40af, #0ea5e9); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERIA</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            // Mostrar prendas con sus imÃ¡genes (separando fotos de prenda/tela de fotos de logo)
            let fotosLogo = [];
            
            if (data.prendas && data.prendas.length > 0) {
                data.prendas.forEach((prenda, idx) => {
                    if (prenda.imagenes && prenda.imagenes.length > 0) {
                        // Separar fotos de logo de las demÃ¡s
                        const fotosPrendaTela = prenda.imagenes.filter(img => img.type !== 'logo');
                        const fotosLogoPrend = prenda.imagenes.filter(img => img.type === 'logo');
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
                            
                            html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                                <div style="border-left: 4px solid #1e40af; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #1e40af; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                        PRENDA ${prenda.numero}:<br>${prenda.nombre.toUpperCase()}
                                    </h3>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                            
                            fotosAMostrar.forEach(image => {
                                const imageIndex = allImagesMobile.length;
                                allImagesMobile.push(image);
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                    onmouseover="this.style.borderColor='#1e40af'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewerMobile(${imageIndex})">
                                    <img src="${image.url}" alt="Foto ${prenda.nombre}" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>`;
                            });
                            
                            if (fotosOcultas > 0) {
                                const firstOccultaIndex = allImagesMobile.length;
                                fotosPrendaTela.slice(4).forEach(image => {
                                    allImagesMobile.push(image);
                                });
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #1e40af; cursor: pointer; border: 2px solid #1e40af; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                    onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(30,64,175,0.2)';"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="openImageViewerMobile(${firstOccultaIndex})">
                                    <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                                </div>`;
                            }
                            
                            html += '</div></div>';
                        }
                    }
                });
                
                // Mostrar fotos de logo al final
                if (fotosLogo.length > 0) {
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
                            const imageIndex = allImagesMobile.length;
                            allImagesMobile.push(image);
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                onmouseover="this.style.borderColor='#dc2626'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerMobile(${imageIndex})">
                                <img src="${image.url}" alt="Foto Logo" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>`;
                        });
                        
                        if (fotosOcultas > 0) {
                            const firstOccultaIndex = allImagesMobile.length;
                            item.fotos.slice(4).forEach(image => {
                                allImagesMobile.push(image);
                            });
                            
                            html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #dc2626; cursor: pointer; border: 2px solid #dc2626; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center;" 
                                onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(220,38,38,0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                onclick="openImageViewerMobile(${firstOccultaIndex})">
                                <span style="color: white; font-weight: bold; font-size: 18px;">+${fotosOcultas}</span>
                            </div>`;
                        }
                        
                        html += '</div></div>';
                    });
                }
            } else {
                html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay imÃ¡genes para este pedido</p>';
            }
            
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<p style="text-align: center; color: #999;">Error al cargar imÃ¡genes</p>';
        });
}

function openImageViewerMobile(index) {
    currentImageIndexMobile = index;
    // Crear modal si no existe
    let modal = document.getElementById('image-viewer-modal-mobile');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'image-viewer-modal-mobile';
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
        z-index: 100001;
        animation: fadeIn 0.3s ease;
    `;
    
    // HTML del visor
    let html = `
        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .image-viewer-container-mobile {
                position: relative;
                width: 90%;
                max-width: 900px;
                height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content-mobile {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .image-viewer-content-mobile img {
                width: 700px;
                height: 700px;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            }
            @media (max-width: 900px) {
                .image-viewer-content-mobile img {
                    width: 500px;
                    height: 500px;
                }
            }
            @media (max-width: 600px) {
                .image-viewer-content-mobile img {
                    width: 350px;
                    height: 350px;
                }
            }
        </style>
        
        <div class="image-viewer-container-mobile">
            <button onclick="closeImageViewerMobile()" style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                Ã—
            </button>
            
            <div class="image-viewer-content-mobile">
                <img src="${allImagesMobile[index].url}" alt="Imagen ampliada">
            </div>
            
            <div style="position: absolute; top: 50%; width: 100%; display: flex; justify-content: space-between; padding: 0 20px; pointer-events: none; transform: translateY(-50%);">
                <button onclick="previousImageMobile()" ${index === 0 ? 'disabled' : ''} style="pointer-events: auto; width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                    â€¹
                </button>
                <button onclick="nextImageMobile()" ${index === allImagesMobile.length - 1 ? 'disabled' : ''} style="pointer-events: auto; width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease;">
                    â€º
                </button>
            </div>
            
            <div style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0, 0, 0, 0.5); padding: 8px 16px; border-radius: 20px; font-size: 14px;">${index + 1} / ${allImagesMobile.length}</div>
        </div>
    `;
    
    modal.innerHTML = html;
    modal.style.display = 'flex';
}

function closeImageViewerMobile() {
    const modal = document.getElementById('image-viewer-modal-mobile');
    if (modal) {
        modal.style.display = 'none';
    }
}

function nextImageMobile() {
    if (currentImageIndexMobile < allImagesMobile.length - 1) {
        openImageViewerMobile(currentImageIndexMobile + 1);
    }
}

function previousImageMobile() {
    if (currentImageIndexMobile > 0) {
        openImageViewerMobile(currentImageIndexMobile - 1);
    }
}

window.loadGaleriaMobile = loadGaleriaMobile;
window.openImageViewerMobile = openImageViewerMobile;
window.closeImageViewerMobile = closeImageViewerMobile;
window.nextImageMobile = nextImageMobile;
window.previousImageMobile = previousImageMobile;
