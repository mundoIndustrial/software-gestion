/**
 * =====================================================
 * SUPERVISOR PEDIDOS - NOVEDADES Y GALERÍA
 * =====================================================
 * - toggleFactura
 * - abrirModalImagenProcesoGrande (lazy gallery)
 * - abrirNovedades / cerrarModalNovedades
 */

window.toggleFactura = function() {
    if (typeof Galeria !== 'undefined' && Galeria.toggleFactura) {
        Galeria.toggleFactura('order-detail-modal-wrapper', 'btn-factura', 'btn-galeria');
    }
};

window.abrirModalImagenProcesoGrande = (function() {
    let galleryManagerLoaded = false;
    let GalleryManager = null;

    return async function(indice, fotosJSON) {
        console.log('[GalleryManager] Intentando abrir imagen:', indice);

        if (galleryManagerLoaded && GalleryManager) {
            return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
        }

        try {
            console.log('[GalleryManager] Cargando módulo...');
            try {
                const module = await import('./js/modulos/pedidos-recibos/components/GalleryManager.js');
                GalleryManager = module.GalleryManager;
                galleryManagerLoaded = true;
                console.log('[GalleryManager] Módulo cargado correctamente');
            } catch (importError) {
                console.warn('[GalleryManager] Error con ruta relativa, intentando ruta absoluta:', importError);
                if (typeof window.GalleryManager !== 'undefined') {
                    GalleryManager = window.GalleryManager;
                    galleryManagerLoaded = true;
                    console.log('[GalleryManager] Usando GalleryManager global');
                } else {
                    throw new Error('No se pudo cargar GalleryManager');
                }
            }

            if (GalleryManager) {
                return GalleryManager.abrirModalImagenProcesoGrande(indice, fotosJSON);
            }
        } catch (err) {
            console.error('[GalleryManager] Error cargando GalleryManager:', err);
            galleryManagerLoaded = false;

            console.log('[GalleryManager] Usando implementación fallback');
            try {
                let fotos = typeof fotosJSON === 'string' ? JSON.parse(fotosJSON) : fotosJSON;
                if (!fotos || !fotos[indice]) {
                    console.error('Imagen no encontrada:', indice);
                    return;
                }

                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.9); z-index: 9999; display: flex;
                    align-items: center; justify-content: center;
                `;
                modal.innerHTML = `
                    <div style="position: relative; max-width: 90%; max-height: 90%;">
                        <img src="${fotos[indice]}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <button onclick="this.parentElement.parentElement.remove()" style="
                            position: absolute; top: 10px; right: 10px;
                            background: white; border: none; border-radius: 50%;
                            width: 40px; height: 40px; cursor: pointer; font-size: 20px;
                        ">×</button>
                    </div>
                `;
                document.body.appendChild(modal);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.remove();
                });
            } catch (fallbackErr) {
                console.error('[GalleryManager] Error en fallback:', fallbackErr);
            }
        }
    };
})();

// ===== MODAL DE NOVEDADES =====

function _escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.abrirNovedades = function(ordenId, novedades) {
    console.log('[Novedades] Abriendo modal con ID:', ordenId);
    const modal = document.getElementById('modalNovedades');
    const contenido = document.getElementById('modalNovedadesContent');

    if (modal && contenido) {
        const procesado = novedades.replace(/\\n/g, '\n');
        const novedadesArray = procesado.split('\n\n').filter(n => n.trim());

        let html = '';
        novedadesArray.forEach((novedad) => {
            const match = novedad.match(/\[(.*?)\]\s(.*)/);

            if (match) {
                const header = match[1];
                const mensaje = match[2];
                html += `
                    <div style="
                        background: white;
                        border-left: 4px solid #1e40af;
                        padding: 1.2rem;
                        margin-bottom: 1.5rem;
                        border-radius: 4px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                            margin-bottom: 0.8rem;
                            font-weight: 600;
                            color: #1e40af;
                            font-size: 0.85rem;
                        ">
                            <span style="color: #3b82f6;">✓</span>
                            <span>${_escapeHtml(header)}</span>
                        </div>
                        <div style="
                            color: #374151;
                            font-size: 0.95rem;
                            line-height: 1.6;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                        ">${_escapeHtml(mensaje)}</div>
                    </div>
                `;
            } else {
                html += `
                    <div style="
                        background: white;
                        border-left: 4px solid #6b7280;
                        padding: 1.2rem;
                        margin-bottom: 1.5rem;
                        border-radius: 4px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            color: #374151;
                            font-size: 0.95rem;
                            line-height: 1.6;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                        ">${_escapeHtml(novedad)}</div>
                    </div>
                `;
            }
        });

        contenido.innerHTML = html;
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        console.log('[Novedades] Modal abierto');
    }
};

window.cerrarModalNovedades = function() {
    console.log('[Novedades] Cerrando modal');
    const modal = document.getElementById('modalNovedades');
    if (modal) {
        modal.style.display = 'none';
    }
};

document.getElementById('modalNovedades')?.addEventListener('click', function(e) {
    if (e.target === this) {
        window.cerrarModalNovedades();
    }
});
