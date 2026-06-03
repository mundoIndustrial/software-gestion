/**
 * GalleryManager.js
 * Gestiona la galería de imágenes del recibo
 */

export class GalleryManager {
    static _isOpening = false;
    static _prevWrapperStyles = null;

    static resetGaleria(modalManager) {
        try {
            const galeriaExistente = document.getElementById('galeria-modal-costura');
            if (galeriaExistente) {
                galeriaExistente.remove();
            }

            const wrapperNormal = document.getElementById('order-detail-modal-wrapper');
            const wrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');

            const cardNormal = wrapperNormal ? wrapperNormal.querySelector('.order-detail-card') : null;
            const cardLogo = wrapperLogo ? wrapperLogo.querySelector('.order-detail-card') : null;
            if (cardNormal) cardNormal.style.display = 'block';
            if (cardLogo) cardLogo.style.display = 'block';

            if (wrapperNormal) GalleryManager._restaurarTamanioGaleria(wrapperNormal);
            if (wrapperLogo) GalleryManager._restaurarTamanioGaleria(wrapperLogo);

            const btnFactura = window.btnFacturaGlobal || document.getElementById('close-receipt-btn');
            if (btnFactura) {
                btnFactura.style.display = 'block';
            }

            GalleryManager.actualizarBotonesEstilo(false);

            window.__logoDesignFiles = [];
            window.__logoDesignSaved = [];
            window.__logoDesignToDelete = new Set();
        } catch (e) {
            console.warn('[GalleryManager.resetGaleria] Error reseteando galería:', e);
        }
    }
    
    /**
     * Abre la galería con imágenes del recibo o de la prenda
     */
    static async abrirGaleria(modalManager) {
        // Evitar múltiples aperturas simultáneas
        if (GalleryManager._isOpening) {
            console.warn('[GalleryManager]  Galería ya se está abriendo, evitando duplicado');
            return false;
        }
        
        // Cerrar galería existente si hay una
        const galeriaExistente = document.getElementById('galeria-modal-costura');
        if (galeriaExistente) {
            console.log('[GalleryManager]  Eliminando galería existente');
            galeriaExistente.remove();
        }
        
        GalleryManager._isOpening = true;
        
        try {
            const state = modalManager.getState();
            const { imagenesActuales, prendaPedidoId, prendaData, pedidoId, procesoPrendaDetalleId } = state;
            
            console.log('[GalleryManager.abrirGaleria]  ABRIENDO GALERÍA');
            console.log('  prendaData.de_bodega:', prendaData?.de_bodega);

            
            // Separar imágenes de diseños logo de otras imágenes
            let fotosParaMostrar = [];
            let diseñosLogo = [];
            
            // Usar las imágenes normales del recibo
            if (imagenesActuales && Array.isArray(imagenesActuales) && imagenesActuales.length > 0) {
                imagenesActuales.forEach(img => {
                    let url = '';
                    if (typeof img === 'string') {
                        url = img;
                    } else if (typeof img === 'object' && img !== null) {
                        url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                    }
                    
                    if (url && typeof url === 'string') {
                        if (url.includes('/storage/storage/')) {
                            url = url.replace('/storage/storage/', '/storage/');
                        }
                        fotosParaMostrar.push(url);
                    }
                });
                
                console.log('  ✓ Imágenes del recibo:', fotosParaMostrar.length);
            }
            
            // Obtener diseños logo desde el campo separado de prendaData
            if (prendaData && prendaData.imagenes_disenos_logo && Array.isArray(prendaData.imagenes_disenos_logo)) {
                prendaData.imagenes_disenos_logo.forEach(diseño => {
                    if (diseño && diseño.url) {
                        let url = diseño.url;
                        if (url.includes('/storage/storage/')) {
                            url = url.replace('/storage/storage/', '/storage/');
                        }
                        diseñosLogo.push({
                            id: diseño.id,
                            url: url,
                            observacion: diseño.observacion || null,
                            estado: diseño.estado || null
                        });
                    }
                });
                
                console.log('  ✓ Diseños logo:', diseñosLogo.length);
            }
            
            console.log(' Total imágenes a mostrar:', fotosParaMostrar.length);
            
            // La galería siempre se abre, incluso sin imágenes
            const wrapperNormal = modalManager.getModalWrapper ? modalManager.getModalWrapper() : document.getElementById('order-detail-modal-wrapper');
            const wrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');
            const modalWrapper = (wrapperLogo && window.getComputedStyle(wrapperLogo).display !== 'none')
                ? wrapperLogo
                : wrapperNormal;
            if (!modalWrapper) return false;

            const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
            const puedeAdjuntarDisenoLogo = esVistaVisualizadorLogo && (
                window.__isDisenadorLogosRole === true ||
                window.__isVisualizadorCotizacionesLogoRole === true
            );
            if (puedeAdjuntarDisenoLogo) {
                GalleryManager._aplicarTamanioGaleria(modalWrapper);
            }

            const card = modalWrapper.querySelector('.order-detail-card');
            if (card) card.style.display = 'none';

            let galeria = document.getElementById('galeria-modal-costura');
            const container = modalWrapper.querySelector('.order-detail-modal-container');

            if (!galeria && container) {
                galeria = document.createElement('div');
                galeria.id = 'galeria-modal-costura';
                galeria.style.cssText = `
                    width: 668px;
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 0;
                    display: flex;
                    flex-direction: column;
                    min-height: 520px;
                    max-height: 820px;
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
                        console.log('[GalleryManager.abrirGaleria]  Botón encontrado en overlay:', { btnCerrarFactura, encontrado: !!btnCerrarFactura });
                    }
                    
                    // Si aún no lo encuentra, buscar todos los botones "✕" y tomar el último (más reciente)
                    if (!btnCerrarFactura) {
                        const allXButtons = Array.from(document.querySelectorAll('button')).filter(btn => btn.textContent.includes('✕'));
                        console.log('[GalleryManager.abrirGaleria]  Total botones "✕" encontrados:', allXButtons.length);
                        if (allXButtons.length > 0) {
                            btnCerrarFactura = allXButtons[allXButtons.length - 1]; // Último (más reciente)
                            console.log('[GalleryManager.abrirGaleria]  Usando botón más reciente');
                        }
                    }
                } else {
                    console.log('[GalleryManager.abrirGaleria]  Botón encontrado por ID');
                }
                
                if (btnCerrarFactura) {
                    console.log('[GalleryManager.abrirGaleria]  Botón encontrado, ocultando...');
                    btnCerrarFactura.style.display = 'none';
                    // Guardar referencia global para poder mostrarla después
                    window.btnFacturaGlobal = btnCerrarFactura;
                    console.log('[GalleryManager.abrirGaleria]  Botón oculto. Display:', btnCerrarFactura.style.display);
                } else {
                    console.warn('[GalleryManager.abrirGaleria]  Botón NO encontrado');
                }
                
                // Renderizar galería
                this._renderizarGaleria(galeria, fotosParaMostrar, puedeAdjuntarDisenoLogo, {
                    pedidoId,
                    procesoPrendaDetalleId,
                    prendaPedidoId,
                    prendaData,
                    datosCompletos: state.datosCompletos,
                    diseñosLogo
                });
            }

            return true; // Se mostró la galería custom
            
        } finally {
            GalleryManager._isOpening = false;
        }
    }

    /**
     * Renderiza la galería con HTML
     */
    static _renderizarGaleria(galeria, fotos, puedeAdjuntarDisenoLogo, uploadCtx = {}) {
        const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
        const puedeGestionarDisenoLogo = (typeof puedeAdjuntarDisenoLogo === 'boolean')
            ? puedeAdjuntarDisenoLogo
            : (esVistaVisualizadorLogo && (
                window.__isDisenadorLogosRole === true ||
                window.__isVisualizadorCotizacionesLogoRole === true
            ));

        const puedeVerDisenoLogo = esVistaVisualizadorLogo && (
            window.__isDisenadorLogosRole === true ||
            window.__isBordadorRole === true ||
            window.__isVisualizadorCotizacionesLogoRole === true
        );
        const esSoloLecturaDisenoLogo = puedeVerDisenoLogo && !puedeGestionarDisenoLogo;
        
        // Guardar imágenes indexadas para lightbox
        GalleryManager._imagenesGaleria = [];
        
        let html = `
            <div style="background: #ffffff; display: flex; flex-direction: column; width: 100%; height: 100%; box-sizing: border-box; border-radius: 12px; overflow: hidden;">
                <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 16px 12px; margin: 0; border-radius: 12px 12px 0 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">
                    <h2 style="text-align: center; margin: 0; font-size: 1.4rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERÍA</h2>
                </div>
                <div style="padding: 24px; flex: 1; overflow-y: auto; background: #ffffff;">
        `;
        
        // Detectar si estamos en /asesores/pendientes-logo
        const esModuloAsesores = window.location.pathname.includes('/asesores/pendientes-logo');
        const diseñosLogo = uploadCtx?.diseñosLogo || [];
        if (esModuloAsesores && diseñosLogo.length > 0) {
            html += `
                <div style="margin-bottom: 18px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc;">
                    <div style="font-weight: 800; color: #1e293b; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                        <i class="fas fa-palette" style="color: #2563eb;"></i> DISEÑOS DE LOGO
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
        `;
            
            diseñosLogo.forEach((diseño, idx) => {
                const fotosJSON = JSON.stringify([diseño.url]);
                let estadoBadge = '';
                if (diseño.estado === 'pendiente_por_confirmar') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #fef3c7; color: #d97706; text-transform: uppercase;">Pendiente</span>`;
                } else if (diseño.estado === 'logo_confirmado') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #dcfce7; color: #15803d; text-transform: uppercase;">Confirmado</span>`;
                } else if (diseño.estado === 'devuelto_a_diseño') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #ffe4e6; color: #b91c1c; text-transform: uppercase;">Devuelto</span>`;
                } else {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #e2e8f0; color: #475569; text-transform: uppercase;">${diseño.estado || 'Sin Estado'}</span>`;
                }

                const mostrarBotonObservacion = (diseño.estado === 'pendiente_por_confirmar');

                html += `
                    <div class="diseño-card-modern" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); transition: all 0.3s ease;">
                        <div style="position: relative; height: 110px; background: #f1f5f9; cursor: pointer; overflow: hidden;" onclick="GalleryManager.abrirModalImagenProcesoGrande(0, '${fotosJSON.replace(/'/g, "&#39;")}')">
                            <img src="${diseño.url}" alt="Diseño ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;" class="diseño-card-img">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.3s ease; display: flex; align-items: center; justify-content: center;" class="diseño-overlay">
                                <span style="color: white; font-weight: 700; opacity: 0; transition: opacity 0.3s ease; font-size: 1.2rem;" class="diseño-icon">🔍</span>
                            </div>
                        </div>
                        <div style="padding: 10px; display: flex; flex-direction: column; gap: 8px; flex-grow: 1; justify-content: space-between;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 0.8rem; color: #1e293b;">Diseño #${idx + 1}</span>
                                ${estadoBadge}
                            </div>
                            
                            ${diseño.observacion ? `
                                <div style="font-size: 0.75rem; color: #475569; background: #fff5f5; border-radius: 6px; padding: 6px 8px; border-left: 3px solid #ef4444; word-break: break-word; line-height: 1.3;">
                                    <strong>Novedad:</strong> ${diseño.observacion}
                                </div>
                            ` : ''}

                            ${mostrarBotonObservacion ? `
                                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                                    <button onclick="event.stopPropagation(); GalleryManager.enviarObservacionDiseno(${diseño.id})" title="Devolver con Observación" style="width: 32px; height: 32px; border-radius: 50%; border: none; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }
        
        // Intentar renderizar estilo insumos (por prendas categorizadas)
        const datosCompletos = uploadCtx?.datosCompletos;
        if (datosCompletos && datosCompletos.prendas && datosCompletos.prendas.length > 0) {
            html += this._construirGaleriaEstiloInsumos(datosCompletos, uploadCtx);
        } else if (fotos.length > 0) {
            html += this._construirGridImagenes(fotos);
        } else if (!esModuloAsesores || diseñosLogo.length === 0) {
            // Solo mostrar mensaje si NO estamos en asesores O no hay diseños logo
            html += `
                <div style="padding: 3rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;">📷</div>
                    <p style="color: #6b7280; font-size: 1rem; margin-bottom: 1rem;">No hay fotos disponibles para este recibo</p>
                    <p style="color: #9ca3af; font-size: 0.875rem;">Las imágenes se mostrarán aquí cuando estén disponibles</p>
                </div>
            `;
        }

        // Mostrar sección de Diseños Logo DESPUÉS si NO estamos en módulo de asesores
        if (!esModuloAsesores && diseñosLogo.length > 0) {
            html += `
                <div style="margin-top: 18px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc;">
                    <div style="font-weight: 800; color: #1e293b; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                        <i class="fas fa-palette" style="color: #2563eb;"></i> DISEÑOS DE LOGO
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px;">
        `;
            
            diseñosLogo.forEach((diseño, idx) => {
                const fotosJSON = JSON.stringify([diseño.url]);
                let estadoBadge = '';
                if (diseño.estado === 'pendiente_por_confirmar') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #fef3c7; color: #d97706; text-transform: uppercase;">Pendiente</span>`;
                } else if (diseño.estado === 'logo_confirmado') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #dcfce7; color: #15803d; text-transform: uppercase;">Confirmado</span>`;
                } else if (diseño.estado === 'devuelto_a_diseño') {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #ffe4e6; color: #b91c1c; text-transform: uppercase;">Devuelto</span>`;
                } else {
                    estadoBadge = `<span style="display: inline-block; padding: 2px 6px; font-size: 0.65rem; font-weight: 700; border-radius: 999px; background-color: #e2e8f0; color: #475569; text-transform: uppercase;">${diseño.estado || 'Sin Estado'}</span>`;
                }

                html += `
                    <div class="diseño-card-modern" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); transition: all 0.3s ease;">
                        <div style="position: relative; height: 110px; background: #f1f5f9; cursor: pointer; overflow: hidden;" onclick="GalleryManager.abrirModalImagenProcesoGrande(0, '${fotosJSON.replace(/'/g, "&#39;")}')">
                            <img src="${diseño.url}" alt="Diseño ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;" class="diseño-card-img">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.3s ease; display: flex; align-items: center; justify-content: center;" class="diseño-overlay">
                                <span style="color: white; font-weight: 700; opacity: 0; transition: opacity 0.3s ease; font-size: 1.2rem;" class="diseño-icon">🔍</span>
                            </div>
                        </div>
                        <div style="padding: 10px; display: flex; flex-direction: column; gap: 8px; flex-grow: 1; justify-content: space-between;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 0.8rem; color: #1e293b;">Diseño #${idx + 1}</span>
                                ${estadoBadge}
                            </div>
                            ${diseño.observacion ? `
                                <div style="font-size: 0.75rem; color: #475569; background: #f8fafc; border-radius: 6px; padding: 6px 8px; border-left: 3px solid #0ea5e9; word-break: break-word; line-height: 1.3;">
                                    <strong>Obs:</strong> ${diseño.observacion}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        }

        if (puedeVerDisenoLogo) {
            const titulo = esSoloLecturaDisenoLogo ? 'DISEÑOS DE LOGO' : 'ADJUNTAR DISEÑO DE LOGO';
            html += `
                <div style="margin-top: 18px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc;">
                    <div style="font-weight: 800; color: #111827; font-size: 0.9rem; margin-bottom: 10px;">${titulo}</div>
                    ${esSoloLecturaDisenoLogo ? '' : `
                    <div style="display: flex; justify-content: flex-end;">
                        <button id="logo-design-open-modal" type="button" style="padding: 10px 14px; border-radius: 10px; border: none; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 900; cursor: pointer; box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);">Adjuntar diseño</button>
                    </div>
                    `}
                    <div id="logo-design-upload-observacion-view" style="margin-top: 10px; font-size: 0.85rem; color: #111827; display:none;"></div>
                    <div id="logo-design-upload-error" style="display:none; margin-top: 8px; font-size: 0.85rem; color: #dc2626; font-weight: 700;"></div>
                    <div id="logo-design-upload-preview" style="margin-top: 12px; display: flex; flex-direction: row; gap: 10px; overflow-x: auto; overflow-y: hidden; padding-bottom: 6px; align-items: center;"></div>
                </div>
            `;
        }
        
        html += '</div></div>';
        galeria.innerHTML = html;

        // Manejar hover en diseños adjuntados
        const diseñosAdjuntados = galeria.querySelectorAll('[class*="diseño-overlay"]');
        diseñosAdjuntados.forEach(overlay => {
            const container = overlay.parentElement;
            const icon = container.querySelector('[class*="diseño-icon"]');
            
            if (container && icon) {
                container.addEventListener('mouseenter', () => {
                    overlay.style.background = 'rgba(0, 0, 0, 0.3)';
                    icon.style.opacity = '1';
                });
                
                container.addEventListener('mouseleave', () => {
                    overlay.style.background = 'rgba(0, 0, 0, 0)';
                    icon.style.opacity = '0';
                });
            }
        });

        if (puedeVerDisenoLogo) {
            const btnOpenModal = galeria.querySelector('#logo-design-open-modal');
            const obsView = galeria.querySelector('#logo-design-upload-observacion-view');
            const preview = galeria.querySelector('#logo-design-upload-preview');
            const error = galeria.querySelector('#logo-design-upload-error');
            const modoSoloLectura = esSoloLecturaDisenoLogo;
            if (preview && error) {
                const pedidoId = uploadCtx?.pedidoId;
                const procesoPrendaDetalleId = uploadCtx?.procesoPrendaDetalleId;

                const getCsrf = () => {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    return meta ? meta.getAttribute('content') : '';
                };

                const fetchExistentes = async () => {
                    if (!pedidoId || !procesoPrendaDetalleId) {
                        return [];
                    }
                    const params = new URLSearchParams({
                        pedido_id: String(pedidoId),
                        proceso_prenda_detalle_id: String(procesoPrendaDetalleId)
                    });
                    const response = await fetch(`/visualizador-logo/pedidos-logo/disenos?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || json.success === false) {
                        const msg = json?.message || 'Error al cargar diseños guardados.';
                        throw new Error(msg);
                    }
                    const items = json?.data?.items;
                    return Array.isArray(items) ? items : [];
                };

                const eliminarDiseno = async (disenoId) => {
                    if (!pedidoId || !procesoPrendaDetalleId) {
                        throw new Error('No se encontró el contexto del recibo (pedido/proceso) para eliminar diseños.');
                    }
                    const response = await fetch(`/visualizador-logo/pedidos-logo/disenos/${disenoId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCsrf(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            pedido_id: pedidoId,
                            proceso_prenda_detalle_id: procesoPrendaDetalleId
                        })
                    });

                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || json.success === false) {
                        const msg = json?.message || 'Error al eliminar diseño.';
                        throw new Error(msg);
                    }
                    return json;
                };

                const subirDisenos = async (files) => {
                    if (!pedidoId || !procesoPrendaDetalleId) {
                        throw new Error('No se encontró el contexto del recibo (pedido/proceso) para subir diseños.');
                    }

                    const form = new FormData();
                    form.append('pedido_id', String(pedidoId));
                    form.append('proceso_prenda_detalle_id', String(procesoPrendaDetalleId));
                    const obs = (typeof window.__logoDesignObs === 'string') ? window.__logoDesignObs.trim() : '';
                    if (obs) form.append('observacio_diseño', obs);
                    files.forEach((f) => form.append('images[]', f));

                    const response = await fetch('/visualizador-logo/pedidos-logo/disenos', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrf(),
                            'Accept': 'application/json'
                        },
                        body: form
                    });

                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || json.success === false) {
                        const msg = json?.message || 'Error al subir diseños.';
                        throw new Error(msg);
                    }
                    return json;
                };

                const syncInputState = () => {
                    const nuevos = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                    const guardados = Array.isArray(window.__logoDesignSaved) ? window.__logoDesignSaved : [];

                    const totalSeleccionado = guardados.length + nuevos.length;

                    const noHaySlots = totalSeleccionado >= 3;
                    if (btnOpenModal) {
                        btnOpenModal.disabled = noHaySlots || modoSoloLectura;
                        btnOpenModal.style.opacity = (noHaySlots || modoSoloLectura) ? '0.6' : '1';
                        btnOpenModal.style.cursor = (noHaySlots || modoSoloLectura) ? 'not-allowed' : 'pointer';
                    }

                    if (noHaySlots && error.textContent === '') {
                        error.textContent = 'Ya adjuntaste 3 imágenes. Elimina alguna para adjuntar otra.';
                        error.style.display = 'block';
                    }

                    if (!noHaySlots && error.textContent === 'Ya adjuntaste 3 imágenes. Elimina alguna para adjuntar otra.') {
                        error.textContent = '';
                        error.style.display = 'none';
                    }
                };

                const renderPreview = () => {
                    const guardados = Array.isArray(window.__logoDesignSaved) ? window.__logoDesignSaved : [];
                    preview.innerHTML = '';

                    if (obsView) {
                        const obs = guardados.map((it) => it?.observacio_diseño).find((t) => typeof t === 'string' && t.trim() !== '');
                        if (typeof obs === 'string' && obs.trim() !== '') {
                            obsView.innerHTML = `<span style="font-weight: 800;">Observaciones:</span> <span style="font-weight: 400;">${obs.trim()}</span>`;
                            obsView.style.display = 'block';
                        } else {
                            obsView.textContent = '';
                            obsView.style.display = 'none';
                        }
                    }

                    const abrirFullscreen = (url) => {
                        const fotosJSON = JSON.stringify([url]);
                        if (typeof window.abrirModalImagenProcesoGrande === 'function') {
                            window.abrirModalImagenProcesoGrande(0, fotosJSON);
                        } else {
                            // Fallback: llamar directamente al método estático si existe
                            if (typeof GalleryManager.abrirModalImagenProcesoGrande === 'function') {
                                GalleryManager.abrirModalImagenProcesoGrande(0, fotosJSON);
                            }
                        }
                    };

                    // Render: guardados primero
                    const list = document.createElement('div');
                    list.style.cssText = 'display:flex; flex-direction: column; gap: 10px; width: 100%;';

                    guardados.forEach((item) => {
                        const row = document.createElement('div');
                        row.style.cssText = 'display:flex; align-items:center; gap: 10px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 12px; background: white;';

                        const thumbWrap = document.createElement('div');
                        thumbWrap.style.cssText = 'width: 72px; height: 72px; border-radius: 10px; overflow:hidden; border: 1px solid #e5e7eb; flex: 0 0 auto; cursor: pointer; background: #f8fafc; display:flex; align-items:center; justify-content:center;';

                        const img = document.createElement('img');
                        img.src = item.url;
                        img.alt = `Diseño ${item.id}`;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display:block;';
                        img.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            abrirFullscreen(item.url);
                        });
                        thumbWrap.appendChild(img);

                        const text = document.createElement('div');
                        text.style.cssText = 'flex: 1; display:flex; flex-direction: column; gap: 4px; min-width: 0;';

                        const title = document.createElement('div');
                        title.style.cssText = 'font-weight: 900; color: #111827; font-size: 0.85rem;';
                        title.textContent = `Diseño #${item.id}`;

                        const obs = document.createElement('div');
                        obs.style.cssText = 'font-size: 0.82rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
                        const obsTxt = (typeof item?.observacio_diseño === 'string' && item.observacio_diseño.trim() !== '')
                            ? item.observacio_diseño.trim()
                            : 'Sin observación';
                        obs.textContent = obsTxt;

                        text.appendChild(title);
                        text.appendChild(obs);

                        row.appendChild(thumbWrap);
                        row.appendChild(text);

                        if (!modoSoloLectura) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.textContent = 'Eliminar';
                            btn.style.cssText = 'padding: 8px 10px; border-radius: 10px; border: none; background: #dc2626; color: white; font-weight: 900; cursor: pointer; flex: 0 0 auto;';
                            btn.addEventListener('click', async (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                btn.disabled = true;
                                btn.style.opacity = '0.6';
                                btn.style.cursor = 'not-allowed';
                                try {
                                    await eliminarDiseno(item.id);
                                    const refreshed = await fetchExistentes();
                                    window.__logoDesignSaved = refreshed;
                                    renderPreview();
                                    syncInputState();
                                } catch (err) {
                                    error.textContent = err?.message || 'Error al eliminar diseño.';
                                    error.style.color = '#dc2626';
                                    error.style.display = 'block';
                                    btn.disabled = false;
                                    btn.style.opacity = '1';
                                    btn.style.cursor = 'pointer';
                                }
                            });
                            row.appendChild(btn);
                        }

                        list.appendChild(row);
                    });

                    preview.appendChild(list);
                };

                const agregarArchivos = (files, currentFiles = []) => {
                    const list = Array.isArray(files) ? files : [];
                    const current = Array.isArray(currentFiles) ? currentFiles : [];
                    const guardados = Array.isArray(window.__logoDesignSaved) ? window.__logoDesignSaved : [];
                    const remainingSlots = Math.max(0, 3 - (guardados.length + current.length));
                    if (remainingSlots === 0) {
                        return current;
                    }

                    const toAdd = list.slice(0, remainingSlots);
                    if (list.length > remainingSlots) {
                        error.textContent = 'Solo puedes adjuntar máximo 3 imágenes.';
                        error.style.display = 'block';
                    }

                    const fingerprint = (f) => `${f.name}__${f.size}__${f.lastModified}`;
                    const currentMap = new Set(current.map(fingerprint));
                    const merged = [...current];
                    toAdd.forEach((f) => {
                        const key = fingerprint(f);
                        if (!currentMap.has(key) && merged.length < 3) {
                            merged.push(f);
                            currentMap.add(key);
                        }
                    });

                    return merged;
                };

                const guardarCambios = async (btnGuardar, btnCancelar, inputFile) => {
                    const current = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                    if (current.length === 0) return;

                    error.style.display = 'none';
                    error.textContent = '';

                    const prevSaveDisabled = btnGuardar ? btnGuardar.disabled : false;
                    if (btnGuardar) {
                        btnGuardar.disabled = true;
                        btnGuardar.style.opacity = '0.6';
                        btnGuardar.style.cursor = 'not-allowed';
                    }
                    if (btnCancelar) {
                        btnCancelar.disabled = true;
                        btnCancelar.style.opacity = '0.6';
                        btnCancelar.style.cursor = 'not-allowed';
                    }
                    if (inputFile) {
                        inputFile.disabled = true;
                        inputFile.style.opacity = '0.6';
                        inputFile.style.cursor = 'not-allowed';
                    }

                    try {
                        const refreshed = await fetchExistentes();
                        window.__logoDesignSaved = refreshed;

                        const slots = Math.max(0, 3 - refreshed.length);
                        const filesToUpload = current.slice(0, slots);
                        if (filesToUpload.length > 0) {
                            await subirDisenos(filesToUpload);
                        }

                        const finalList = await fetchExistentes();
                        window.__logoDesignSaved = finalList;
                        window.__logoDesignFiles = [];
                        renderPreview();
                        syncInputState();
                        error.textContent = 'Guardado correctamente.';
                        error.style.color = '#16a34a';
                        error.style.display = 'block';
                        setTimeout(() => {
                            if (error.textContent === 'Guardado correctamente.') {
                                error.textContent = '';
                                error.style.display = 'none';
                                error.style.color = '#dc2626';
                            }
                        }, 1800);

                        const overlay = document.getElementById('logo-design-modal-overlay');
                        if (overlay) overlay.remove();
                        window.__logoDesignObs = '';
                    } catch (err) {
                        error.textContent = err?.message || 'Error al guardar diseños.';
                        error.style.color = '#dc2626';
                        error.style.display = 'block';
                        if (btnGuardar) {
                            btnGuardar.disabled = prevSaveDisabled;
                            btnGuardar.style.opacity = prevSaveDisabled ? '0.6' : '1';
                            btnGuardar.style.cursor = prevSaveDisabled ? 'not-allowed' : 'pointer';
                        }
                        if (btnCancelar) {
                            btnCancelar.disabled = false;
                            btnCancelar.style.opacity = '1';
                            btnCancelar.style.cursor = 'pointer';
                        }
                        if (inputFile) {
                            inputFile.disabled = false;
                            inputFile.style.opacity = '1';
                            inputFile.style.cursor = 'pointer';
                        }
                    } finally {
                        syncInputState();
                    }
                };

                const abrirModalAdjuntar = () => {
                    if (modoSoloLectura) return;
                    const existing = document.getElementById('logo-design-modal-overlay');
                    if (existing) existing.remove();

                    let modalFiles = [];

                    const overlay = document.createElement('div');
                    overlay.id = 'logo-design-modal-overlay';
                    overlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999999; display:flex; align-items:center; justify-content:center; padding: 16px;';

                    const modal = document.createElement('div');
                    modal.style.cssText = 'width: 520px; max-width: 100%; background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden;';

                    modal.innerHTML = `
                        <div style="padding: 14px 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center;">
                            <div>ADJUNTAR DISEÑO</div>
                            <button id="logo-design-modal-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:34px; height:34px; border-radius: 10px; cursor:pointer;">×</button>
                        </div>
                        <div style="padding: 14px; display:flex; flex-direction: column; gap: 10px;">
                            <div id="logo-design-dropzone" tabindex="0" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 14px; background: #f8fafc; outline: none;">
                                <div style="font-weight: 900; color: #0f172a; margin-bottom: 6px;">Pega (Ctrl+V) o arrastra aquí la imagen</div>
                                <div style="font-size: 0.85rem; color: #475569;">También puedes seleccionarla desde tu equipo</div>
                                <div id="logo-design-dropzone-indicator" style="display:none; margin-top: 10px; padding: 10px 12px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; font-weight: 900; text-align: center;">Suelta para adjuntar</div>
                                <div style="margin-top: 10px; display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
                                    <input id="logo-design-modal-file" type="file" accept="image/*" multiple style="flex:1; min-width: 240px;" />
                                    <div style="font-size: 0.85rem; color: #475569;">Máximo 3 imágenes</div>
                                </div>
                            </div>
                            <div style="display:flex; flex-direction: column; gap: 6px;">
                                <label for="logo-design-modal-obs" style="font-size: 0.85rem; color: #111827; font-weight: 900;">Observaciones</label>
                                <textarea id="logo-design-modal-obs" rows="2" maxlength="200" style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #e5e7eb; font-size: 0.9rem; resize: vertical; background: white;" placeholder="Escribe una observación (máx 200 caracteres)"></textarea>
                            </div>
                            <div style="display:flex; gap: 10px; justify-content: flex-end;">
                                <button id="logo-design-modal-cancel" type="button" style="padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; background: #ffffff; color:#111827; font-weight: 900; cursor:pointer;">Cancelar</button>
                                <button id="logo-design-modal-save" type="button" style="padding: 10px 14px; border-radius: 10px; border: none; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 900; cursor: pointer;">Enviar a confirmar</button>
                            </div>
                        </div>
                    `;

                    overlay.appendChild(modal);
                    document.body.appendChild(overlay);

                    const btnClose = overlay.querySelector('#logo-design-modal-close');
                    const btnCancel = overlay.querySelector('#logo-design-modal-cancel');
                    const btnSave = overlay.querySelector('#logo-design-modal-save');
                    const inputFile = overlay.querySelector('#logo-design-modal-file');
                    const inputObs = overlay.querySelector('#logo-design-modal-obs');
                    const dropzone = overlay.querySelector('#logo-design-dropzone');
                    const indicator = overlay.querySelector('#logo-design-dropzone-indicator');

                    const extraerImagenesClipboard = (clipboardEvent) => {
                        const items = clipboardEvent?.clipboardData?.items;
                        if (!items) return [];
                        const pasted = [];
                        for (const it of items) {
                            if (it && it.type && it.type.startsWith('image/')) {
                                const f = it.getAsFile();
                                if (f) pasted.push(f);
                            }
                        }
                        return pasted;
                    };

                    const renderModalPreview = () => {
                        if (!dropzone) return;
                        const prev = dropzone.querySelector('#logo-design-modal-preview');
                        if (prev) prev.remove();

                        const wrap = document.createElement('div');
                        wrap.id = 'logo-design-modal-preview';
                        wrap.style.cssText = 'margin-top: 10px; display:flex; gap: 10px; overflow-x:auto; padding-bottom: 6px;';

                        modalFiles.forEach((file, idx) => {
                            const url = URL.createObjectURL(file);
                            const item = document.createElement('div');
                            item.style.cssText = 'position: relative; width: 88px; height: 88px; flex: 0 0 auto; border: 2px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: white; display:flex; align-items:center; justify-content:center;';

                            const img = document.createElement('img');
                            img.src = url;
                            img.alt = file.name;
                            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display:block;';
                            img.addEventListener('dblclick', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                const fotosJSON = JSON.stringify([url]);
                                if (typeof window.abrirModalImagenProcesoGrande === 'function') {
                                    window.abrirModalImagenProcesoGrande(0, fotosJSON);
                                } else if (typeof GalleryManager.abrirModalImagenProcesoGrande === 'function') {
                                    GalleryManager.abrirModalImagenProcesoGrande(0, fotosJSON);
                                }
                            });

                            const btnRemove = document.createElement('button');
                            btnRemove.type = 'button';
                            btnRemove.textContent = '×';
                            btnRemove.style.cssText = 'position:absolute; top:6px; right:6px; width:22px; height:22px; border-radius:999px; border:none; background: rgba(220,38,38,0.95); color:white; font-weight:900; line-height:22px; text-align:center; cursor:pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.25);';
                            btnRemove.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                modalFiles = modalFiles.filter((_, i) => i !== idx);
                                renderModalPreview();
                            });

                            item.appendChild(img);
                            item.appendChild(btnRemove);
                            wrap.appendChild(item);
                        });

                        dropzone.appendChild(wrap);
                    };

                    const cerrar = () => {
                        const ov = document.getElementById('logo-design-modal-overlay');
                        if (ov) ov.remove();
                        window.__logoDesignObs = '';

                        if (window.__logoDesignOnPasteGlobal) {
                            document.removeEventListener('paste', window.__logoDesignOnPasteGlobal);
                            window.__logoDesignOnPasteGlobal = null;
                        }
                    };

                    if (btnClose) btnClose.addEventListener('click', cerrar);
                    if (btnCancel) btnCancel.addEventListener('click', cerrar);
                    overlay.addEventListener('click', (e) => {
                        if (e.target === overlay) cerrar();
                    });

                    if (inputObs) {
                        inputObs.value = (typeof window.__logoDesignObs === 'string') ? window.__logoDesignObs : '';
                        inputObs.addEventListener('input', () => {
                            window.__logoDesignObs = inputObs.value;
                        });
                    }

                    const onFiles = (files) => {
                        const list = Array.from(files || []).filter((f) => f && f.type && f.type.startsWith('image/'));
                        if (list.length === 0) return;
                        const before = Array.isArray(modalFiles) ? modalFiles : [];
                        modalFiles = agregarArchivos(list, before);
                        renderModalPreview();
                    };

                    if (inputFile) {
                        inputFile.addEventListener('change', () => {
                            onFiles(inputFile.files);
                            inputFile.value = '';
                        });
                    }

                    if (dropzone) {
                        if (indicator) indicator.style.display = 'none';
                        dropzone.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            dropzone.style.borderColor = '#2563eb';
                            dropzone.style.background = '#eff6ff';
                            if (indicator) indicator.style.display = 'block';
                        });
                        dropzone.addEventListener('dragleave', (e) => {
                            e.preventDefault();
                            dropzone.style.borderColor = '#cbd5e1';
                            dropzone.style.background = '#f8fafc';
                            if (indicator) indicator.style.display = 'none';
                        });
                        dropzone.addEventListener('drop', (e) => {
                            e.preventDefault();
                            dropzone.style.borderColor = '#cbd5e1';
                            dropzone.style.background = '#f8fafc';
                            if (indicator) indicator.style.display = 'none';
                            onFiles(e.dataTransfer?.files);
                        });
                        dropzone.addEventListener('paste', (e) => {
                            const pasted = extraerImagenesClipboard(e);
                            if (pasted.length > 0) {
                                e.preventDefault();
                                e.stopPropagation();
                                const before = Array.isArray(modalFiles) ? modalFiles : [];
                                modalFiles = agregarArchivos(pasted, before);
                                renderModalPreview();
                            }
                        });
                        dropzone.focus();
                    }

                    window.__logoDesignOnPasteGlobal = (e) => {
                        const ov = document.getElementById('logo-design-modal-overlay');
                        if (!ov) return;
                        if (e.defaultPrevented) return;
                        const pasted = extraerImagenesClipboard(e);
                        if (pasted.length === 0) return;
                        e.preventDefault();
                        const before = Array.isArray(modalFiles) ? modalFiles : [];
                        modalFiles = agregarArchivos(pasted, before);
                        renderModalPreview();
                    };
                    document.addEventListener('paste', window.__logoDesignOnPasteGlobal);

                    if (btnSave) {
                        btnSave.addEventListener('click', async () => {
                            window.__logoDesignObs = (inputObs && typeof inputObs.value === 'string') ? inputObs.value.trim() : '';
                            window.__logoDesignFiles = Array.isArray(modalFiles) ? modalFiles : [];
                            await guardarCambios(btnSave, btnCancel, inputFile);
                        });
                    }

                    renderModalPreview();
                };

                if (btnOpenModal && !modoSoloLectura) {
                    btnOpenModal.addEventListener('click', abrirModalAdjuntar);
                }

                if (!Array.isArray(window.__logoDesignFiles)) {
                    window.__logoDesignFiles = [];
                }
                if (!Array.isArray(window.__logoDesignSaved)) {
                    window.__logoDesignSaved = [];
                }
                if (!(window.__logoDesignToDelete instanceof Set)) {
                    window.__logoDesignToDelete = new Set();
                }
                if (typeof window.__logoDesignObs !== 'string') {
                    window.__logoDesignObs = '';
                }

                // Cargar existentes al abrir la galería
                fetchExistentes()
                    .then((items) => {
                        window.__logoDesignSaved = items;
                        renderPreview();
                        syncInputState();
                    })
                    .catch((err) => {
                        error.textContent = err?.message || 'Error al cargar diseños guardados.';
                        error.style.color = '#dc2626';
                        error.style.display = 'block';
                        renderPreview();
                        syncInputState();
                    });
                renderPreview();
                syncInputState();
            }
        }
        
        // Agregar event listeners para los thumbnails
        const thumbnails = galeria.querySelectorAll('.gallery-thumbnail');
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const indice = this.getAttribute('data-indice');
                const fotosJSON = this.getAttribute('data-fotos');
                console.log('[GalleryManager] Click en imagen:', { indice, fotosJSON });
                
                // Intentar usar la función global si está disponible
                if (typeof window.abrirModalImagenProcesoGrande === 'function') {
                    window.abrirModalImagenProcesoGrande(parseInt(indice), fotosJSON);
                } else {
                    // Fallback: llamar directamente al método estático
                    GalleryManager.abrirModalImagenProcesoGrande(parseInt(indice), fotosJSON);
                }
            });
        });
        

    }

    /**
     * Construye galería estilo insumos: categorizada por prenda, telas y procesos
     */
    static _construirGaleriaEstiloInsumos(datosCompletos, uploadCtx = {}) {
        let html = '';
        let tieneImagenes = false;
        GalleryManager._imagenesGaleria = [];

        const prendaPedidoIdActual = uploadCtx?.prendaPedidoId ?? uploadCtx?.prendaData?.prenda_pedido_id ?? uploadCtx?.prendaData?.id ?? null;
        let prendas = Array.isArray(datosCompletos.prendas) ? datosCompletos.prendas : [];
        if (prendaPedidoIdActual) {
            const filtradas = prendas.filter((p) => String(p?.prenda_pedido_id ?? p?.id ?? '') === String(prendaPedidoIdActual));
            if (filtradas.length > 0) {
                prendas = filtradas;
            }
        }

        prendas.forEach((prenda, prendaIndex) => {
            const nombrePrenda = prenda.nombre || prenda.nombre_prenda || `Prenda ${prendaIndex + 1}`;

            const normalizarRuta = (img) => {
                let url = '';
                if (typeof img === 'string') url = img;
                else if (img && typeof img === 'object') url = img.url || img.ruta_webp || img.ruta || img.ruta_original || '';
                if (url && typeof url === 'string' && url.includes('/storage/storage/')) {
                    url = url.replace('/storage/storage/', '/storage/');
                }
                return url;
            };

            const setImagenesBase = new Set();
            if (Array.isArray(prenda.imagenes)) {
                prenda.imagenes.forEach((im) => {
                    const u = normalizarRuta(im);
                    if (u) setImagenesBase.add(u);
                });
            }
            if (Array.isArray(prenda.imagenes_tela)) {
                prenda.imagenes_tela.forEach((im) => {
                    const u = normalizarRuta(im);
                    if (u) setImagenesBase.add(u);
                });
            }

            // Imágenes de la prenda
            if (prenda.imagenes && prenda.imagenes.length > 0) {
                tieneImagenes = true;
                html += `
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${nombrePrenda}</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                `;
                prenda.imagenes.forEach((imagen, index) => {
                    const rutaImg = normalizarRuta(imagen);
                    const globalIdx = GalleryManager._imagenesGaleria.length;
                    GalleryManager._imagenesGaleria.push(rutaImg);
                    html += GalleryManager._crearTarjetaImagen(rutaImg, nombrePrenda, `Imagen ${index + 1}`, globalIdx);
                });
                html += `</div></div>`;
            }

            // Imágenes de telas
            if (prenda.imagenes_tela && prenda.imagenes_tela.length > 0) {
                tieneImagenes = true;
                html += `
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${nombrePrenda} - Telas</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                `;
                prenda.imagenes_tela.forEach((imagen, index) => {
                    const rutaImg = normalizarRuta(imagen);
                    const globalIdx = GalleryManager._imagenesGaleria.length;
                    GalleryManager._imagenesGaleria.push(rutaImg);
                    html += GalleryManager._crearTarjetaImagen(rutaImg, `Tela ${index + 1}`, 'Click para ver grande', globalIdx);
                });
                html += `</div></div>`;
            }

            // Imágenes de procesos
            if (prenda.procesos && prenda.procesos.length > 0) {
                prenda.procesos.forEach(proceso => {
                    if (proceso.imagenes && proceso.imagenes.length > 0) {
                        const seen = new Set();
                        const imagenesProceso = proceso.imagenes
                            .map((im) => normalizarRuta(im))
                            .filter((u) => {
                                if (!u) return false;
                                if (setImagenesBase.has(u)) return false;
                                if (seen.has(u)) return false;
                                seen.add(u);
                                return true;
                            });

                        if (imagenesProceso.length === 0) {
                            return;
                        }

                        tieneImagenes = true;
                        html += `
                            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${nombrePrenda} - ${proceso.tipo_proceso}</h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        `;
                        imagenesProceso.forEach((rutaImg, index) => {
                            const globalIdx = GalleryManager._imagenesGaleria.length;
                            GalleryManager._imagenesGaleria.push(rutaImg);
                            html += GalleryManager._crearTarjetaImagen(rutaImg, proceso.tipo_proceso, 'Click para ver grande', globalIdx);
                        });
                        html += `</div></div>`;
                    }
                });
            }
        });

        if (!tieneImagenes) {
            html = `
                <div style="padding: 3rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;">📷</div>
                    <p style="color: #6b7280; font-size: 1rem; margin-bottom: 1rem;">No hay fotos disponibles para este pedido</p>
                    <p style="color: #9ca3af; font-size: 0.875rem;">Las imágenes se mostrarán aquí cuando estén disponibles</p>
                </div>
            `;
        }

        return html;
    }

    /**
     * Crea una tarjeta de imagen individual estilo insumos
     */
    static _crearTarjetaImagen(rutaImg, titulo, subtitulo, globalIdx) {
        // Exponer imágenes en window para acceso desde onclick inline
        window.__galeriaImagenes = GalleryManager._imagenesGaleria;
        return `
            <div style="
                border: 2px solid #e5e7eb; 
                border-radius: 12px; 
                overflow: hidden; 
                cursor: pointer; 
                transition: all 0.3s ease;
                background: white;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            " onclick="if(typeof window.abrirModalImagenProcesoGrande==='function'){window.abrirModalImagenProcesoGrande(${globalIdx}, window.__galeriaImagenes||[]);}"
            onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 16px rgba(59, 130, 246, 0.3)';"
            onmouseout="this.style.borderColor='#e5e7eb'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.08)';">
                <img src="${rutaImg}" alt="${titulo}" style="
                    width: 100%;
                    height: 220px;
                    object-fit: cover;
                    display: block;
                    transition: all 0.3s ease;
                " onerror=\"this.style.display='none'; this.parentElement.style.background='#fee2e2'; this.parentElement.style.display='flex'; this.parentElement.style.alignItems='center'; this.parentElement.style.justifyContent='center'; this.parentElement.style.color='#dc2626'; this.parentElement.style.fontSize='0.8rem'; this.parentElement.style.textAlign='center'; this.parentElement.style.padding='4px'; this.parentElement.textContent='Error al cargar imagen';\">
                <div style="padding: 0.75rem; background: #f9fafb;">
                    <div style="font-size: 0.875rem; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem;">${titulo}</div>
                    <div style="font-size: 0.75rem; color: #6b7280;">${subtitulo}</div>
                </div>
            </div>
        `;
    }

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
                <div class="gallery-thumbnail" 
                    style="cursor: pointer; border-radius: 8px; overflow: hidden; 
                    border: 2px solid #e5e5e5;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.08);"
                    onmouseover="this.style.borderColor='#2563eb'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.2)';"
                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                    data-indice="${idx}"
                    data-fotos='${fotosJSON}'>
                    <img src="${img}" alt="Imagen ${idx + 1}" style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.parentElement.style.background='#fee2e2'; this.parentElement.style.display='flex'; this.parentElement.style.alignItems='center'; this.parentElement.style.justifyContent='center'; this.parentElement.style.color='#dc2626'; this.parentElement.style.fontSize='0.8rem'; this.parentElement.style.textAlign='center'; this.parentElement.style.padding='4px'; this.parentElement.textContent='Error al cargar imagen';">
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

            const btnCerrarPrincipal = document.getElementById('btn-cerrar-modal-dinamico');
            const prevDisplayCerrarPrincipal = btnCerrarPrincipal ? btnCerrarPrincipal.style.display : null;
            if (btnCerrarPrincipal) {
                btnCerrarPrincipal.style.display = 'none';
            }

            const restaurarBotonCerrarPrincipal = () => {
                const btn = document.getElementById('btn-cerrar-modal-dinamico');
                if (!btn) return;
                btn.style.display = prevDisplayCerrarPrincipal ?? 'block';
            };
            
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
                    " onmouseover="this.style.background='white'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.95)'; this.style.transform='scale(1)';" data-close-fullscreen="1">✕</button>
                    
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

            const cerrarFullscreen = () => {
                const currentModal = document.getElementById('modal-imagen-proceso-grande');
                if (currentModal) currentModal.remove();
                const btnFactura = window.btnFacturaGlobal || document.getElementById('close-receipt-btn');
                if (btnFactura) {
                    btnFactura.style.display = 'block';
                }
                restaurarBotonCerrarPrincipal();
            };

            const btnCerrarFullscreen = modal.querySelector('button[data-close-fullscreen="1"]');
            if (btnCerrarFullscreen) {
                btnCerrarFullscreen.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    cerrarFullscreen();
                });
            }
            
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
                    cerrarFullscreen();
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
                    cerrarFullscreen();
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
        const wrapperNormal = document.getElementById('order-detail-modal-wrapper');
        const wrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');

        const wrapperActivo = (wrapperLogo && window.getComputedStyle(wrapperLogo).display !== 'none')
            ? wrapperLogo
            : wrapperNormal;

        const modalCard = wrapperActivo ? wrapperActivo.querySelector('.order-detail-card') : null;
        
        if (galeria) galeria.style.display = 'none';
        if (modalCard) modalCard.style.display = 'block';

        if (wrapperActivo) GalleryManager._restaurarTamanioGaleria(wrapperActivo);
        
        this.actualizarBotonesEstilo(false);

    }

    static _aplicarTamanioGaleria(modalWrapper) {
        if (!modalWrapper || GalleryManager._prevWrapperStyles) return;
        GalleryManager._prevWrapperStyles = {
            maxWidth: modalWrapper.style.maxWidth,
            width: modalWrapper.style.width,
            top: modalWrapper.style.top,
            height: modalWrapper.style.height,
            maxHeight: modalWrapper.style.maxHeight
        };
        modalWrapper.style.top = '30%';
        modalWrapper.style.height = '96vh';
        modalWrapper.style.maxHeight = '96vh';
    }

    static _restaurarTamanioGaleria(modalWrapper) {
        if (!modalWrapper || !GalleryManager._prevWrapperStyles) return;
        modalWrapper.style.maxWidth = GalleryManager._prevWrapperStyles.maxWidth || '';
        modalWrapper.style.width = GalleryManager._prevWrapperStyles.width || '';
        modalWrapper.style.top = GalleryManager._prevWrapperStyles.top || '';
        modalWrapper.style.height = GalleryManager._prevWrapperStyles.height || '';
        modalWrapper.style.maxHeight = GalleryManager._prevWrapperStyles.maxHeight || '';
        GalleryManager._prevWrapperStyles = null;
    }

    static async enviarObservacionDiseno(disenoId) {
        if (!window.Swal) {
            console.error('SweetAlert not loaded');
            alert('Error: SweetAlert no está cargado.');
            return;
        }

        // Asegurar que SweetAlert se muestre encima de la galería modal (z-index: 9998)
        if (!document.getElementById('swal-z-index-override')) {
            const style = document.createElement('style');
            style.id = 'swal-z-index-override';
            style.innerHTML = `
                .swal2-container {
                    z-index: 99999999 !important;
                }
                .modern-swal-popup {
                    border-radius: 16px !important;
                    padding: 24px !important;
                    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                }
                .modern-swal-title {
                    font-size: 1.4rem !important;
                    font-weight: 800 !important;
                    color: #1e293b !important;
                    margin-bottom: 12px !important;
                }
                .modern-swal-html {
                    font-size: 0.95rem !important;
                    color: #475569 !important;
                    margin-bottom: 16px !important;
                }
                .modern-swal-input {
                    border: 1px solid #cbd5e1 !important;
                    border-radius: 10px !important;
                    padding: 12px !important;
                    font-size: 0.95rem !important;
                    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important;
                    transition: border-color 0.2s, box-shadow 0.2s !important;
                }
                .modern-swal-input:focus {
                    border-color: #ef4444 !important;
                    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15), inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important;
                }
                .modern-swal-confirm {
                    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
                    color: white !important;
                    font-weight: 700 !important;
                    border-radius: 10px !important;
                    padding: 12px 24px !important;
                    font-size: 0.95rem !important;
                    box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2) !important;
                    transition: all 0.2s !important;
                }
                .modern-swal-confirm:hover {
                    transform: translateY(-1px) !important;
                    box-shadow: 0 6px 12px -1px rgba(239, 68, 68, 0.3) !important;
                }
                .modern-swal-cancel {
                    background: #f1f5f9 !important;
                    color: #475569 !important;
                    font-weight: 700 !important;
                    border-radius: 10px !important;
                    padding: 12px 24px !important;
                    font-size: 0.95rem !important;
                    transition: all 0.2s !important;
                }
                .modern-swal-cancel:hover {
                    background: #e2e8f0 !important;
                    color: #1e293b !important;
                }
            `;
            document.head.appendChild(style);
        }

        const { value: observacion } = await window.Swal.fire({
            title: 'Devolver a Diseño',
            html: '<div style="text-align: left; font-weight: 600; color: #475569; margin-bottom: 8px;">Por favor detalla la novedad o corrección requerida:</div>',
            input: 'textarea',
            inputPlaceholder: 'Escribe tu observación aquí (máx. 200 caracteres)...',
            inputAttributes: {
                maxlength: 200
            },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-undo" style="margin-right: 6px;"></i> Devolver',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'modern-swal-popup',
                title: 'modern-swal-title',
                htmlContainer: 'modern-swal-html',
                input: 'modern-swal-input',
                confirmButton: 'modern-swal-confirm',
                cancelButton: 'modern-swal-cancel'
            },
            buttonsStyling: false,
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return '¡Debes ingresar una observación!';
                }
            }
        });

        if (observacion) {
            try {
                window.Swal.showLoading();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch('/api/asesores/devolver-diseño-logo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        diseño_id: disenoId,
                        observacion: observacion.trim()
                    })
                });

                const result = await response.json();
                if (result.success) {
                    await window.Swal.fire({
                        icon: 'success',
                        title: 'Devuelto',
                        text: 'El diseño ha sido devuelto correctamente.',
                        timer: 1500,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'modern-swal-popup',
                            title: 'modern-swal-title'
                        }
                    });
                    location.reload();
                } else {
                    window.Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'No se pudo devolver el diseño.',
                        customClass: {
                            popup: 'modern-swal-popup',
                            title: 'modern-swal-title'
                        }
                    });
                }
            } catch (error) {
                console.error(error);
                window.Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud.',
                    customClass: {
                        popup: 'modern-swal-popup',
                        title: 'modern-swal-title'
                    }
                });
            }
        }
    }
}

window.GalleryManager = GalleryManager;
