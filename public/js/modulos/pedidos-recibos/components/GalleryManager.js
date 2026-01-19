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
        const { imagenesActuales, prendaPedidoId } = state;
        
        console.log('[GalleryManager] Abriendo galería...');
        
        if (imagenesActuales.length === 0 && !prendaPedidoId) {
            console.log('[GalleryManager] Sin imágenes, usando galería original');
            return false; // Usar galería original
        }

        const modalWrapper = modalManager.getModalWrapper();
        if (!modalWrapper) {
            console.error('[GalleryManager] No se encontró wrapper del modal');
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
            
            // Obtener fotos
            let fotosParaMostrar = [...imagenesActuales];
            if (fotosParaMostrar.length === 0 && prendaPedidoId) {
                try {
                    const response = await fetch(`/asesores/prendas-pedido/${prendaPedidoId}/fotos`);
                    const data = await response.json();
                    if (data.success && data.fotos) {
                        fotosParaMostrar = data.fotos.map(f => f.url).filter(f => f);
                        console.log('[GalleryManager] Fotos obtenidas:', fotosParaMostrar.length);
                    }
                } catch (error) {
                    console.error('[GalleryManager] Error obteniendo fotos:', error);
                }
            }
            
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
        
        console.log('[GalleryManager] Galería renderizada con', fotos.length, 'imágenes');
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
     * Obtiene los botones de toggle (factura/galería)
     */
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
        console.log('[GalleryManager] Galería cerrada');
    }
}
