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
        
        console.log('[GalleryManager.abrirGaleria] 🖼️ ABRIENDO GALERÍA');
        console.log('  prendaData.de_bodega:', prendaData?.de_bodega);
        console.log('  Mostrar todas las imágenes?', prendaData?.de_bodega === false);

        
        // Combinar imágenes de tela + imágenes del recibo/prenda
        let fotosParaMostrar = [];
        
        // LÓGICA: Si de_bodega es FALSE, mostrar TODAS las imágenes (prendas, tela, procesos)
        // Si de_bodega es TRUE, no mostrar galería (solo recibos)
        
        if (prendaData?.de_bodega === false) {
            console.log('✅ [GalleryManager] de_bodega=FALSE: Mostrando todas las imágenes');
            
            // 1. Agregar imágenes de prenda
            if (prendaData && prendaData.imagenes && Array.isArray(prendaData.imagenes)) {
                const imagenesPrendaLimpias = prendaData.imagenes
                    .map(img => {
                        let url = '';
                        if (typeof img === 'string') {
                            url = img;
                        } else if (typeof img === 'object' && img !== null) {
                            url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                        }
                        if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                            return url.replace('/storage/storage/', '/storage/');
                        }
                        return url;
                    })
                    .filter(url => url);
                
                console.log('  ✓ Imágenes de prenda:', imagenesPrendaLimpias.length);
                fotosParaMostrar = [...imagenesPrendaLimpias];
            }
            
            // 2. Agregar imágenes de tela
            if (prendaData && prendaData.imagenes_tela && Array.isArray(prendaData.imagenes_tela)) {
                const imagenesTelaLimpias = prendaData.imagenes_tela
                    .map(img => {
                        let url = '';
                        if (typeof img === 'string') {
                            url = img;
                        } else if (typeof img === 'object' && img !== null) {
                            url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                        }
                        if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                            return url.replace('/storage/storage/', '/storage/');
                        }
                        return url;
                    })
                    .filter(url => url);
                
                console.log('  ✓ Imágenes de tela:', imagenesTelaLimpias.length);
                fotosParaMostrar = [...fotosParaMostrar, ...imagenesTelaLimpias];
            }
            
            // 3. Agregar imágenes del recibo/proceso
            if (imagenesActuales && Array.isArray(imagenesActuales) && imagenesActuales.length > 0) {
                const imagenesRecibosLimpias = imagenesActuales
                    .map(img => {
                        let url = '';
                        if (typeof img === 'string') {
                            url = img;
                        } else if (typeof img === 'object' && img !== null) {
                            url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                        }
                        if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                            return url.replace('/storage/storage/', '/storage/');
                        }
                        return url;
                    })
                    .filter(url => url);
                
                console.log('  ✓ Imágenes del proceso:', imagenesRecibosLimpias.length);
                fotosParaMostrar = [...fotosParaMostrar, ...imagenesRecibosLimpias];
            }
        } else {
            console.log('⚠️ [GalleryManager] de_bodega=TRUE: Prendas de bodega - galería deshabilitada');
        }
        
        console.log('📊 Total imágenes a mostrar:', fotosParaMostrar.length);
        
        // Si de_bodega es TRUE, no mostrar galería
        if (prendaData?.de_bodega === true) {
            console.log('⛔ [GalleryManager] Prenda de bodega - galería DESHABILITADA');
            return false;
        }
        
        // 4. Si aún no hay imágenes, intentar obtener desde el endpoint (SOLO SI de_bodega=FALSE)
        if (fotosParaMostrar.length === 0 && prendaPedidoId) {
            try {
                console.log('  ℹ️ No hay imágenes locales, intentando obtener del endpoint...');
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
                    
                    console.log('  ✓ Imágenes obtenidas del endpoint:', fotosLimpias.length);
                    fotosParaMostrar = [...fotosParaMostrar, ...fotosLimpias];

                }
            } catch (error) {
                console.warn('  ⚠️ Error al obtener imágenes del endpoint:', error.message);
            }
        }
        
        if (fotosParaMostrar.length === 0) {
            console.log('⚠️ [GalleryManager] Sin imágenes para mostrar');
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
            
            // Ocultar botón X de cierre de factura
            let btnCerrarFactura = document.getElementById('close-receipt-btn');
            
            // Si no existe por ID, buscar por el texto "✕" dentro del modal overlay o el más reciente
            if (!btnCerrarFactura) {
                // Primero buscar dentro del modal-factura-overlay
                const overlay = document.getElementById('modal-factura-overlay');
                if (overlay) {
                    const buttonsInOverlay = overlay.querySelectorAll('button');
                    btnCerrarFactura = Array.from(buttonsInOverlay).find(btn => btn.textContent.includes('✕'));
                    console.log('[GalleryManager.abrirGaleria] 🔍 Botón encontrado en overlay:', { btnCerrarFactura, encontrado: !!btnCerrarFactura });
                }
                
                // Si aún no lo encuentra, buscar todos los botones "✕" y tomar el último (más reciente)
                if (!btnCerrarFactura) {
                    const allXButtons = Array.from(document.querySelectorAll('button')).filter(btn => btn.textContent.includes('✕'));
                    console.log('[GalleryManager.abrirGaleria] 🔍 Total botones "✕" encontrados:', allXButtons.length);
                    if (allXButtons.length > 0) {
                        btnCerrarFactura = allXButtons[allXButtons.length - 1]; // Último (más reciente)
                        console.log('[GalleryManager.abrirGaleria] 🔍 Usando botón más reciente');
                    }
                }
            } else {
                console.log('[GalleryManager.abrirGaleria] ✅ Botón encontrado por ID');
            }
            
            if (btnCerrarFactura) {
                console.log('[GalleryManager.abrirGaleria] ✅ Botón encontrado, ocultando...');
                btnCerrarFactura.style.display = 'none';
                // Guardar referencia global para poder mostrarla después
                window.btnFacturaGlobal = btnCerrarFactura;
                console.log('[GalleryManager.abrirGaleria] ✅ Botón oculto. Display:', btnCerrarFactura.style.display);
            } else {
                console.warn('[GalleryManager.abrirGaleria] ⚠️ Botón NO encontrado');
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
            
            let indiceActual = indice;
            
            // Crear modal
            const modal = document.createElement('div');
            modal.id = 'modal-imagen-proceso-grande';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.95);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                padding: 0;
                margin: 0;
            `;
            
            const mostrarImagen = () => {
                const imgActual = fotos[indiceActual];
                const imgElement = modal.querySelector('img[data-main-image]');
                if (imgElement) {
                    imgElement.src = imgActual;
                    modal.querySelector('.imagen-contador').textContent = `${indiceActual + 1} / ${fotos.length}`;
                }
            };
            
            modal.innerHTML = `
                <div style="position: absolute; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: space-between; gap: 0;">
                    <!-- Contenedor de imagen - ocupa todo el espacio disponible -->
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; overflow: hidden; position: relative;">
                        <img data-main-image src="${fotos[indiceActual]}" alt="Imagen grande" style="width: 90vw; height: 85vh; object-fit: contain;">
                    </div>
                    
                    <!-- Botón cerrar -->
                    <button style="
                        position: absolute;
                        top: 20px;
                        right: 20px;
                        background: rgba(255, 255, 255, 0.95);
                        border: none;
                        border-radius: 50%;
                        width: 44px;
                        height: 44px;
                        cursor: pointer;
                        font-size: 28px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                        transition: all 0.2s ease;
                        z-index: 10001;
                    " onmouseover="this.style.background='white'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.95)'; this.style.transform='scale(1)';" onclick="console.log('[Botón X Galería] Click en cerrar'); document.getElementById('modal-imagen-proceso-grande').remove(); const btnFactura = window.btnFacturaGlobal || document.getElementById('close-receipt-btn'); console.log('[Botón X Galería] Mostrando botón:', { btnFactura, encontrado: !!btnFactura }); if(btnFactura) { btnFactura.style.display = 'block'; console.log('[Botón X Galería] Display actualizado:', btnFactura.style.display); }">✕</button>
                    
                    <!-- Información y navegación - fija en la parte inferior -->
                    <div style="width: 100%; display: flex; align-items: center; justify-content: space-between; background: rgba(0, 0, 0, 0.8); padding: 16px 24px; backdrop-filter: blur(10px); box-sizing: border-box; flex-shrink: 0;">
                        <!-- Botón anterior -->
                        <button style="
                            background: rgba(255, 255, 255, 0.2);
                            border: 2px solid rgba(255, 255, 255, 0.4);
                            border-radius: 8px;
                            width: 44px;
                            height: 44px;
                            cursor: pointer;
                            font-size: 20px;
                            color: white;
                            transition: all 0.2s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                        " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.borderColor='rgba(255, 255, 255, 0.6)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.borderColor='rgba(255, 255, 255, 0.4)';" ${indiceActual === 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} onclick="window.navegarImagenModal(-1, ${fotos.length}); mostrarImagenModal();">◀</button>
                        
                        <!-- Contador -->
                        <div style="color: white; font-size: 16px; font-weight: 600; letter-spacing: 1px; text-align: center; flex: 1;">
                            <span class="imagen-contador">${indiceActual + 1} / ${fotos.length}</span>
                        </div>
                        
                        <!-- Botón siguiente -->
                        <button style="
                            background: rgba(255, 255, 255, 0.2);
                            border: 2px solid rgba(255, 255, 255, 0.4);
                            border-radius: 8px;
                            width: 44px;
                            height: 44px;
                            cursor: pointer;
                            font-size: 20px;
                            color: white;
                            transition: all 0.2s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                        " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'; this.style.borderColor='rgba(255, 255, 255, 0.6)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.borderColor='rgba(255, 255, 255, 0.4)';" ${indiceActual === fotos.length - 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} onclick="window.navegarImagenModal(1, ${fotos.length}); mostrarImagenModal();">▶</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Variables globales para navegación
            window.indiceModalImagenActual = indiceActual;
            window.fotosModalActuales = fotos;
            
            window.navegarImagenModal = (direccion, totalFotos) => {
                window.indiceModalImagenActual += direccion;
                if (window.indiceModalImagenActual < 0) window.indiceModalImagenActual = 0;
                if (window.indiceModalImagenActual >= totalFotos) window.indiceModalImagenActual = totalFotos - 1;
            };
            
            window.mostrarImagenModal = () => {
                const imgActual = window.fotosModalActuales[window.indiceModalImagenActual];
                const imgElement = modal.querySelector('img[data-main-image]');
                const contador = modal.querySelector('.imagen-contador');
                const botones = modal.querySelectorAll('div[style*="justify-content: space-between"] button');
                const btnAnterior = botones.length > 0 ? botones[0] : null;
                const btnSiguiente = botones.length > 1 ? botones[1] : null;
                
                if (imgElement && btnAnterior && btnSiguiente) {
                    imgElement.src = imgActual;
                    contador.textContent = `${window.indiceModalImagenActual + 1} / ${window.fotosModalActuales.length}`;
                    
                    // Actualizar estado de botones
                    if (window.indiceModalImagenActual === 0) {
                        btnAnterior.disabled = true;
                        btnAnterior.style.opacity = '0.5';
                        btnAnterior.style.cursor = 'not-allowed';
                    } else {
                        btnAnterior.disabled = false;
                        btnAnterior.style.opacity = '1';
                        btnAnterior.style.cursor = 'pointer';
                    }
                    
                    if (window.indiceModalImagenActual === window.fotosModalActuales.length - 1) {
                        btnSiguiente.disabled = true;
                        btnSiguiente.style.opacity = '0.5';
                        btnSiguiente.style.cursor = 'not-allowed';
                    } else {
                        btnSiguiente.disabled = false;
                        btnSiguiente.style.opacity = '1';
                        btnSiguiente.style.cursor = 'pointer';
                    }
                }
            };
            
            // Cerrar al hacer click fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    console.log('[Click fuera modal] Cerrando modal de imagen');
                    modal.remove();
                    // Mostrar botón de cierre de factura
                    const btnFactura = window.btnFacturaGlobal || document.getElementById('close-receipt-btn');
                    console.log('[Click fuera modal] Mostrando botón:', { btnFactura, encontrado: !!btnFactura });
                    if(btnFactura) { 
                        btnFactura.style.display = 'block';
                        console.log('[Click fuera modal] Display actualizado:', btnFactura.style.display);
                    }
                }
            });
            
            // Soporte para teclado
            const handleKeyPress = (e) => {
                if (!document.getElementById('modal-imagen-proceso-grande')) {
                    document.removeEventListener('keydown', handleKeyPress);
                    return;
                }
                
                if (e.key === 'ArrowLeft' && window.indiceModalImagenActual > 0) {
                    window.navegarImagenModal(-1, window.fotosModalActuales.length);
                    window.mostrarImagenModal();
                } else if (e.key === 'ArrowRight' && window.indiceModalImagenActual < window.fotosModalActuales.length - 1) {
                    window.navegarImagenModal(1, window.fotosModalActuales.length);
                    window.mostrarImagenModal();
                } else if (e.key === 'Escape') {
                    console.log('[Tecla ESC] Cerrando modal de imagen');
                    modal.remove();
                    // Mostrar botón de cierre de factura
                    const btnFactura = window.btnFacturaGlobal || document.getElementById('close-receipt-btn');
                    console.log('[Tecla ESC] Mostrando botón:', { btnFactura, encontrado: !!btnFactura });
                    if(btnFactura) { 
                        btnFactura.style.display = 'block';
                        console.log('[Tecla ESC] Display actualizado:', btnFactura.style.display);
                    }
                    document.removeEventListener('keydown', handleKeyPress);
                }
            };
            
            document.addEventListener('keydown', handleKeyPress);
            
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
