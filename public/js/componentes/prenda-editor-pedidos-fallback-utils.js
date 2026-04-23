/**
 * Utilities for prenda-editor-pedidos-adapter fallbacks.
 * Exposes: window.PedidosAdapterFallbackUtils
 */
(function() {
    'use strict';

    function _registerManejarImagenesPrendaFallback() {
        if (typeof window.manejarImagenesPrenda === 'function') return;
        const obtenerLimite = () => Number(window.imagenesPrendaStorage?.maxImages || 6);

        window.manejarImagenesPrenda = function(input) {
            if (!input.files || input.files.length === 0) return;
            try {
                if (!window.imagenesPrendaStorage) {
                    console.warn('[PedidosAdapter] imagenesPrendaStorage no disponible');
                    return;
                }
                window.imagenesPrendaStorage.agregarImagen(input.files[0])
                    .then(() => {
                        if (typeof window.actualizarPreviewPrenda === 'function') {
                            window.actualizarPreviewPrenda();
                        }
                    })
                    .catch(err => {
                        console.error('[PedidosAdapter] Error al agregar imagen:', err.message);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: err.message === 'MAX_LIMIT'
                                    ? `Maximo ${obtenerLimite()} imagenes por prenda`
                                    : 'Error al procesar imagen: ' + err.message
                            });
                        }
                    });
            } catch (err) {
                console.error('[PedidosAdapter] Error procesando imagen:', err);
            }
            input.value = '';
        };
        console.log('[PedidosAdapter]  manejarImagenesPrenda definida (fallback)');
    }

    function _registerActualizarPreviewPrendaFallback() {
        if (typeof window.actualizarPreviewPrenda === 'function') return;
        const obtenerLimite = () => Number(window.imagenesPrendaStorage?.maxImages || 6);

        window.actualizarPreviewPrenda = function() {
            const preview = document.getElementById('nueva-prenda-foto-preview');
            if (!preview || !window.imagenesPrendaStorage) return;

            const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
            if (typeof PrendaEditorImagenes !== 'undefined') {
                PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar();
                if (imagenes.length > 0) {
                    PrendaEditorImagenes._configurarClickGaleria(preview, imagenes);
                }
            } else {
                preview.innerHTML = '';
                imagenes.forEach((img, idx) => {
                    const container = document.createElement('div');
                    container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
                    const imgEl = document.createElement('img');
                    imgEl.src = img.previewUrl || img.url || img.ruta || '';
                    imgEl.alt = `Imagen ${idx + 1}`;
                    imgEl.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
                    container.appendChild(imgEl);
                    preview.appendChild(container);
                });
                if (imagenes.length === 0) {
                    preview.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div></div>';
                }
            }

            const contador = document.getElementById('nueva-prenda-foto-contador');
            if (contador) {
                contador.textContent = imagenes.length > 0
                    ? (imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos')
                    : '';
            }

            const btn = document.getElementById('nueva-prenda-foto-btn');
            if (btn) btn.style.display = imagenes.length < obtenerLimite() ? 'block' : 'none';
        };
        console.log('[PedidosAdapter]  actualizarPreviewPrenda definida (fallback)');
    }

    function initImageFallbacks() {
        _registerManejarImagenesPrendaFallback();
        _registerActualizarPreviewPrendaFallback();
    }

    window.PedidosAdapterFallbackUtils = {
        initImageFallbacks
    };
})();
