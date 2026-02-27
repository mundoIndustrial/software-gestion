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
            console.log('[GalleryManager] 🗑️ Eliminando galería existente');
            galeriaExistente.remove();
        }
        
        GalleryManager._isOpening = true;
        
        try {
            const state = modalManager.getState();
            const { imagenesActuales, prendaPedidoId, prendaData, pedidoId, procesoPrendaDetalleId } = state;
            
            console.log('[GalleryManager.abrirGaleria] 🖼️ ABRIENDO GALERÍA');
            console.log('  prendaData.de_bodega:', prendaData?.de_bodega);

            
            // Combinar imágenes de tela + imágenes del recibo/prenda
            let fotosParaMostrar = [];
            
            // LÓGICA SIMPLIFICADA: Usar solo las imágenes del recibo (que ya incluyen prenda + tela + proceso)
            
            // El recibo ya contiene todas las imágenes necesarias (prenda + tela + proceso)
            if (imagenesActuales && Array.isArray(imagenesActuales) && imagenesActuales.length > 0) {
                const imagenesLimpias = imagenesActuales
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
                
                console.log('  ✓ Imágenes del recibo (todas):', imagenesLimpias.length);
                fotosParaMostrar = [...imagenesLimpias];
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
            const puedeAdjuntarDisenoLogo = esVistaVisualizadorLogo && window.__isDisenadorLogosRole === true;
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
                    datosCompletos: state.datosCompletos
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
            : (esVistaVisualizadorLogo && window.__isDisenadorLogosRole === true);

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
        
        // Intentar renderizar estilo insumos (por prendas categorizadas)
        const datosCompletos = uploadCtx?.datosCompletos;
        if (datosCompletos && datosCompletos.prendas && datosCompletos.prendas.length > 0) {
            html += this._construirGaleriaEstiloInsumos(datosCompletos);
        } else if (fotos.length > 0) {
            html += this._construirGridImagenes(fotos);
        } else {
            html += `
                <div style="padding: 3rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; color: #9ca3af;">📷</div>
                    <p style="color: #6b7280; font-size: 1rem; margin-bottom: 1rem;">No hay fotos disponibles para este recibo</p>
                    <p style="color: #9ca3af; font-size: 0.875rem;">Las imágenes se mostrarán aquí cuando estén disponibles</p>
                </div>
            `;
        }

        if (puedeVerDisenoLogo) {
            const titulo = esSoloLecturaDisenoLogo ? 'DISEÑOS DE LOGO' : 'ADJUNTAR DISEÑO DE LOGO';
            html += `
                <div style="margin-top: 18px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc;">
                    <div style="font-weight: 800; color: #111827; font-size: 0.9rem; margin-bottom: 10px;">${titulo}</div>
                    ${esSoloLecturaDisenoLogo ? '' : `
                    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <input id="logo-design-upload-input" type="file" accept="image/*" multiple style="flex: 1; min-width: 240px;" />
                        <div id="logo-design-upload-hint" style="font-size: 0.85rem; color: #475569;">Máximo 3 imágenes</div>
                    </div>
                    <div style="margin-top: 10px; display: flex; align-items: center; justify-content: flex-end;">
                        <button id="logo-design-upload-save" type="button" style="padding: 10px 14px; border-radius: 10px; border: none; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 800; cursor: pointer; box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);">Guardar cambios</button>
                    </div>
                    `}
                    <div id="logo-design-upload-error" style="display:none; margin-top: 8px; font-size: 0.85rem; color: #dc2626; font-weight: 700;"></div>
                    <div id="logo-design-upload-preview" style="margin-top: 12px; display: flex; flex-direction: row; gap: 10px; overflow-x: auto; overflow-y: hidden; padding-bottom: 6px; align-items: center;"></div>
                </div>
            `;
        }
        
        html += '</div></div>';
        galeria.innerHTML = html;

        if (puedeVerDisenoLogo) {
            const input = galeria.querySelector('#logo-design-upload-input');
            const preview = galeria.querySelector('#logo-design-upload-preview');
            const error = galeria.querySelector('#logo-design-upload-error');
            const btnSave = galeria.querySelector('#logo-design-upload-save');
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
                    const toDelete = window.__logoDesignToDelete instanceof Set ? window.__logoDesignToDelete : new Set();

                    const guardadosActivos = guardados.filter((it) => !toDelete.has(String(it.id)));
                    const totalSeleccionado = guardadosActivos.length + nuevos.length;

                    const noHaySlots = totalSeleccionado >= 3;
                    if (input) {
                        input.disabled = noHaySlots || modoSoloLectura;
                        input.style.opacity = (noHaySlots || modoSoloLectura) ? '0.6' : '1';
                        input.style.cursor = (noHaySlots || modoSoloLectura) ? 'not-allowed' : 'pointer';
                    }

                    if (btnSave) {
                        const hasChanges = !modoSoloLectura && (nuevos.length > 0 || toDelete.size > 0);
                        btnSave.disabled = !hasChanges;
                        btnSave.style.opacity = hasChanges ? '1' : '0.6';
                        btnSave.style.cursor = hasChanges ? 'pointer' : 'not-allowed';
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
                    const toDelete = window.__logoDesignToDelete instanceof Set ? window.__logoDesignToDelete : new Set();
                    const selected = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                    preview.innerHTML = '';

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
                    guardados.forEach((item) => {
                        const itemId = String(item.id);
                        const marcado = !modoSoloLectura && toDelete.has(itemId);

                        const wrap = document.createElement('div');
                        wrap.style.cssText = 'position: relative; width: 96px; height: 96px; flex: 0 0 auto; border: 2px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: white; display:flex; align-items:center; justify-content:center;';
                        if (marcado) {
                            wrap.style.opacity = '0.45';
                            wrap.style.borderColor = '#dc2626';
                        }

                        const img = document.createElement('img');
                        img.src = item.url;
                        img.alt = `Diseño ${item.id}`;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display:block;';

                        // Doble click para ver en fullscreen
                        img.addEventListener('dblclick', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            abrirFullscreen(item.url);
                        });

                        if (!modoSoloLectura) {
                            const btnRemove = document.createElement('button');
                            btnRemove.type = 'button';
                            btnRemove.textContent = '×';
                            btnRemove.title = marcado ? 'Deshacer eliminación' : 'Eliminar';
                            btnRemove.style.cssText = 'position:absolute; top:6px; right:6px; width:22px; height:22px; border-radius:999px; border:none; background: rgba(220,38,38,0.95); color:white; font-weight:900; line-height:22px; text-align:center; cursor:pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.25);';
                            btnRemove.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                const set = window.__logoDesignToDelete instanceof Set ? window.__logoDesignToDelete : new Set();
                                if (set.has(itemId)) {
                                    set.delete(itemId);
                                } else {
                                    set.add(itemId);
                                }
                                window.__logoDesignToDelete = set;
                                renderPreview();
                                syncInputState();
                            });
                            wrap.appendChild(btnRemove);
                        }

                        wrap.appendChild(img);
                        preview.appendChild(wrap);
                    });

                    selected.forEach((file, idx) => {
                        const url = URL.createObjectURL(file);
                        const item = document.createElement('div');
                        item.style.cssText = 'position: relative; width: 96px; height: 96px; flex: 0 0 auto; border: 2px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: white; display:flex; align-items:center; justify-content:center;';

                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = file.name;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; display:block;';

                        // Doble click para ver en fullscreen
                        img.addEventListener('dblclick', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            abrirFullscreen(url);
                        });

                        const btnRemove = document.createElement('button');
                        btnRemove.type = 'button';
                        btnRemove.textContent = '×';
                        btnRemove.style.cssText = 'position:absolute; top:6px; right:6px; width:22px; height:22px; border-radius:999px; border:none; background: rgba(220,38,38,0.95); color:white; font-weight:900; line-height:22px; text-align:center; cursor:pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.25);';
                        btnRemove.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const current = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                            window.__logoDesignFiles = current.filter((_, i) => i !== idx);
                            renderPreview();
                            syncInputState();
                        });

                        item.appendChild(img);
                        item.appendChild(btnRemove);
                        preview.appendChild(item);
                    });
                };

                if (input && !modoSoloLectura) input.addEventListener('change', () => {
                    const files = Array.from(input.files || []);
                    error.style.display = 'none';
                    error.textContent = '';

                    const current = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                    const guardados = Array.isArray(window.__logoDesignSaved) ? window.__logoDesignSaved : [];
                    const toDelete = window.__logoDesignToDelete instanceof Set ? window.__logoDesignToDelete : new Set();
                    const guardadosActivos = guardados.filter((it) => !toDelete.has(String(it.id)));
                    const remainingSlots = Math.max(0, 3 - (guardadosActivos.length + current.length));
                    if (remainingSlots === 0) {
                        syncInputState();
                        input.value = '';
                        return;
                    }

                    const toAdd = files.slice(0, remainingSlots);
                    if (files.length > remainingSlots) {
                        error.textContent = 'Solo puedes adjuntar máximo 3 imágenes.';
                        error.style.display = 'block';
                    }

                    // Acumular sin duplicados (por fingerprint básico)
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

                    window.__logoDesignFiles = merged;
                    renderPreview();
                    syncInputState();

                    // Permite seleccionar la misma imagen de nuevo (si se eliminó) y dispara change
                    input.value = '';
                });

                if (btnSave && !modoSoloLectura) {
                    btnSave.addEventListener('click', async () => {
                        const current = Array.isArray(window.__logoDesignFiles) ? window.__logoDesignFiles : [];
                        const toDelete = window.__logoDesignToDelete instanceof Set ? window.__logoDesignToDelete : new Set();
                        if (current.length === 0 && toDelete.size === 0) return;

                        error.style.display = 'none';
                        error.textContent = '';

                        const prevSaveDisabled = btnSave.disabled;
                        btnSave.disabled = true;
                        btnSave.style.opacity = '0.6';
                        btnSave.style.cursor = 'not-allowed';
                        input.disabled = true;
                        input.style.opacity = '0.6';
                        input.style.cursor = 'not-allowed';

                        try {
                            // 1) aplicar eliminaciones
                            const ids = Array.from(toDelete);
                            for (const id of ids) {
                                await eliminarDiseno(id);
                            }

                            // 2) refrescar guardados (para recalcular slots)
                            const refreshed = await fetchExistentes();
                            window.__logoDesignSaved = refreshed;
                            window.__logoDesignToDelete = new Set();

                            // 3) subir nuevos si existen
                            const slots = Math.max(0, 3 - refreshed.length);
                            const filesToUpload = current.slice(0, slots);
                            if (filesToUpload.length > 0) {
                                await subirDisenos(filesToUpload);
                            }

                            // 4) refrescar de nuevo para mostrar lo que quedó guardado
                            const finalList = await fetchExistentes();
                            window.__logoDesignSaved = finalList;
                            window.__logoDesignFiles = [];
                            window.__logoDesignToDelete = new Set();
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
                        } catch (err) {
                            error.textContent = err?.message || 'Error al guardar diseños.';
                            error.style.color = '#dc2626';
                            error.style.display = 'block';
                            btnSave.disabled = prevSaveDisabled;
                            btnSave.style.opacity = prevSaveDisabled ? '0.6' : '1';
                            btnSave.style.cursor = prevSaveDisabled ? 'not-allowed' : 'pointer';
                        } finally {
                            syncInputState();
                        }
                    });
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
    static _construirGaleriaEstiloInsumos(datosCompletos) {
        let html = '';
        let tieneImagenes = false;
        GalleryManager._imagenesGaleria = [];

        datosCompletos.prendas.forEach((prenda, prendaIndex) => {
            const nombrePrenda = prenda.nombre || prenda.nombre_prenda || `Prenda ${prendaIndex + 1}`;

            // Imágenes de la prenda
            if (prenda.imagenes && prenda.imagenes.length > 0) {
                tieneImagenes = true;
                html += `
                    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${nombrePrenda}</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                `;
                prenda.imagenes.forEach((imagen, index) => {
                    const rutaImg = imagen.ruta_webp || imagen.ruta_original || '';
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
                    const rutaImg = imagen.ruta_webp || imagen.ruta_original || '';
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
                        tieneImagenes = true;
                        html += `
                            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">${nombrePrenda} - ${proceso.tipo_proceso}</h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        `;
                        proceso.imagenes.forEach((imagen, index) => {
                            const rutaImg = imagen.ruta_webp || imagen.ruta_original || '';
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
                " onerror="this.style.display='none'; this.parentElement.style.background='#fee2e2'; this.parentElement.innerHTML='<div style=\'display: flex; align-items: center; justify-content: center; height: 100%; color: #dc2626; font-size: 0.8rem; text-align: center; padding: 4px;\'> Error al cargar imagen</div>';">
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
                         onerror="this.style.display='none'; this.parentElement.style.background='#fee2e2'; this.parentElement.innerHTML='<div style=\\'display: flex; align-items: center; justify-content: center; height: 100%; color: #dc2626; font-size: 0.8rem; text-align: center; padding: 4px;\\'> Error al cargar imagen</div>';">
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
}
