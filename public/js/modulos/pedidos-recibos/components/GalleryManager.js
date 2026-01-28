/**
 * GalleryManager.js
 * Gestiona la galería de imágenes del recibo
 */

export class GalleryManager {
    /**
     * Abre la galería con imágenes del recibo o de la prenda
     */
    static async abrirGaleria(modalManager) {
        const state = modalManager.getState();
        const { imagenesActuales, prendaPedidoId, prendaData } = state;
        

        
        // Combinar imágenes de tela + imágenes del recibo/prenda
        let fotosParaMostrar = [];
        
        // 1. Agregar imágenes de prenda (desde prendaData.imagenes)
        if (prendaData && prendaData.imagenes && Array.isArray(prendaData.imagenes)) {
            const imagenesPrendaLimpias = prendaData.imagenes
                .map(img => {
                    // Extraer URL dependiendo del formato
                    let url = '';
                    if (typeof img === 'string') {
                        url = img;
                    } else if (typeof img === 'object' && img !== null) {
                        url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                    }
                    // Limpiar rutas duplicadas /storage/storage
                    if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                        return url.replace('/storage/storage/', '/storage/');
                    }
                    return url;
                })
                .filter(url => url);
            
            fotosParaMostrar = [...imagenesPrendaLimpias];

        }
        
        // 2. Agregar imágenes de tela (desde prendaData.imagenes_tela)
        if (prendaData && prendaData.imagenes_tela && Array.isArray(prendaData.imagenes_tela)) {
            const imagenesTelaLimpias = prendaData.imagenes_tela
                .map(img => {
                    // Extraer URL dependiendo del formato
                    let url = '';
                    if (typeof img === 'string') {
                        url = img;
                    } else if (typeof img === 'object' && img !== null) {
                        url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                    }
                    // Limpiar rutas duplicadas /storage/storage
                    if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                        return url.replace('/storage/storage/', '/storage/');
                    }
                    return url;
                })
                .filter(url => url);
            
            fotosParaMostrar = [...fotosParaMostrar, ...imagenesTelaLimpias];

        }
        
        // 3. Agregar imágenes del recibo/proceso (si existen)
        if (imagenesActuales && Array.isArray(imagenesActuales) && imagenesActuales.length > 0) {
            const imagenesRecibosLimpias = imagenesActuales
                .map(img => {
                    // Extraer URL dependiendo del formato
                    let url = '';
                    if (typeof img === 'string') {
                        url = img;
                    } else if (typeof img === 'object' && img !== null) {
                        url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                    }
                    // Limpiar rutas duplicadas /storage/storage
                    if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                        return url.replace('/storage/storage/', '/storage/');
                    }
                    return url;
                })
                .filter(url => url);
            
            fotosParaMostrar = [...fotosParaMostrar, ...imagenesRecibosLimpias];
        }
        
        // 4. Si aún no hay imágenes, intentar obtener desde el endpoint
        if (fotosParaMostrar.length === 0 && prendaPedidoId) {
            try {
                const response = await fetch(`/asesores/prendas-pedido/${prendaPedidoId}/fotos`);
                const data = await response.json();
                if (data.success && data.fotos) {
                    const fotosLimpias = data.fotos
                        .map(f => {
                            if (f.url && f.url.includes('/storage/storage/')) {
                                return f.url.replace('/storage/storage/', '/storage/');
                            }
                            return f.url;
                        })
                        .filter(f => f);
                    
                    fotosParaMostrar = [...fotosParaMostrar, ...fotosLimpias];

                }
            } catch (error) {

            }
        }
        
        if (fotosParaMostrar.length === 0) {

            return false; // Usar galería original
        }

        const modalWrapper = modalManager.getModalWrapper();
        if (!modalWrapper) {

            return false;
        }

        const card = modalWrapper.querySelector('.order-detail-card');
        if (card) card.style.display = 'none';

        let galeria = document.getElementById('galeria-modal-costura');
        const container = modalManager.getModalContainer();

        if (!galeria && container) {
            galeria = document.createElement('div');
            galeria.id = 'galeria-modal-costura';
            galeria.style.cssText = `
                width: 100%;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                min-height: 400px;
                max-height: 600px;
                overflow-y: auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            `;
            container.appendChild(galeria);
        }

        if (galeria) {
            galeria.style.display = 'flex';
            
            // Renderizar galería
            this._renderizarGaleria(galeria, fotosParaMostrar);
        }

        return true; // Se mostró la galería custom
    }

    /**
     * Renderiza la galería con HTML
     */
    static _renderizarGaleria(galeria, fotos) {
        let html = `
            <div style="background: #ffffff; display: flex; flex-direction: column; width: 100%; height: 100%; box-sizing: border-box; border-radius: 12px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 16px 12px; margin: 0; border-radius: 12px 12px 0 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">
                    <h2 style="text-align: center; margin: 0; font-size: 1.4rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERÍA</h2>
                </div>
                <div style="padding: 24px; flex: 1; overflow-y: auto; background: #ffffff;">
        `;
        
        if (fotos.length > 0) {
            html += this._construirGridImagenes(fotos);
        } else {
            html += '<p style="text-align: center; color: #999; padding: 2rem;">No hay fotos disponibles para este recibo</p>';
        }
        
        html += '</div></div>';
        galeria.innerHTML = html;
        

    }

    /**
     * Construye el grid de imágenes
     */
    static _construirGridImagenes(fotos) {
        let html = `
            <div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                <div style="border-left: 4px solid #2563eb; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #2563eb; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">RECIBO ACTUAL</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">
        `;
        
        fotos.forEach((img, idx) => {
            const fotosJSON = JSON.stringify(fotos).replace(/"/g, '&quot;');
            html += `
                <div style="
                    aspect-ratio: 1;
                    border-radius: 4px;
                    overflow: hidden;
                    background: #f5f5f5;
                    cursor: pointer;
                    border: 2px solid #e5e5e5;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.08);"
                    onmouseover="this.style.borderColor='#2563eb'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.2)';"
                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                    onclick="abrirModalImagenProcesoGrande(${idx}, ${fotosJSON})">
                    <img src="${img}" alt="Imagen ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            `;
        });
        
        html += '</div></div>';
        return html;
    }

    /**
     * Abre una modal con la imagen en grande
     */
    static abrirModalImagenProcesoGrande(indice, fotosJSON) {
        try {
            // Parsear JSON si viene como string
            let fotos = typeof fotosJSON === 'string' ? JSON.parse(fotosJSON) : fotosJSON;
            
            if (!fotos || !fotos[indice]) {
                console.error('Imagen no encontrada:', indice);
                return;
            }
            
            const imgActual = fotos[indice];
            
            // Crear modal
            const modal = document.createElement('div');
            modal.id = 'modal-imagen-proceso-grande';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            `;
            
            modal.innerHTML = `
                <div style="position: relative; max-width: 90%; max-height: 90%; display: flex; flex-direction: column; align-items: center;">
                    <button onclick="document.getElementById('modal-imagen-proceso-grande').remove()" style="
                        position: absolute;
                        top: -40px;
                        right: 0;
                        background: white;
                        border: none;
                        border-radius: 50%;
                        width: 36px;
                        height: 36px;
                        cursor: pointer;
                        font-size: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                    ">✕</button>
                    <img src="${imgActual}" alt="Imagen grande" style="max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px;">
                    <div style="color: white; margin-top: 10px; font-size: 14px;">${indice + 1} / ${fotos.length}</div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Cerrar al hacer click fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
        } catch (error) {
            console.error('Error al abrir imagen:', error);
        }
    }
    static obtenerBotones() {
        return {
            factura: document.getElementById('btn-factura'),
            galeria: document.getElementById('btn-galeria')
        };
    }

    /**
     * Actualiza los estilos de los botones
     */
    static actualizarBotonesEstilo(mostrarGaleria = true) {
        const { factura, galeria } = this.obtenerBotones();
        
        if (mostrarGaleria) {
            if (factura) {
                factura.style.background = 'white';
                factura.style.border = '2px solid #ddd';
                factura.style.color = '#333';
            }
            if (galeria) {
                galeria.style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
                galeria.style.border = 'none';
                galeria.style.color = 'white';
            }
        } else {
            if (factura) {
                factura.style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
                factura.style.border = 'none';
                factura.style.color = 'white';
            }
            if (galeria) {
                galeria.style.background = 'white';
                galeria.style.border = '2px solid #ddd';
                galeria.style.color = '#333';
            }
        }
    }

    /**
     * Cierra la galería y muestra la factura
     */
    static cerrarGaleria() {
        const galeria = document.getElementById('galeria-modal-costura');
        const modalWrapper = document.querySelector('#order-detail-modal-wrapper .order-detail-card');
        
        if (galeria) galeria.style.display = 'none';
        if (modalWrapper) modalWrapper.style.display = 'block';
        
        this.actualizarBotonesEstilo(false);

    }
}
