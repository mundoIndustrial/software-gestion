/**
 * Shared helper for asking a required "novedad" before saving changes.
 * Exposes: window.PedidosNovedadHelper
 */
(function() {
    'use strict';

    function _toFunction(fn, fallback) {
        return typeof fn === 'function' ? fn : fallback;
    }

    function _ensureSwalReady(showModal, timeoutMs = 5500) {
        if (typeof _ensureSwal === 'function') {
            _ensureSwal(showModal);
            return timeoutMs;
        }

        showModal();
        return null;
    }

    function pedirNovedad(options = {}) {
        const fallbackValue = options.fallbackValue || 'edicion de prenda desde lista de pedidos';
        const ensureOverlayStyle = _toFunction(options.ensureOverlayStyle, () => {});
        const centerOverlay = _toFunction(options.centerOverlay, () => {});

        return new Promise((resolve) => {
            let resuelto = false;
            let timeoutId = null;

            const finalizar = (valor) => {
                if (resuelto) {
                    return;
                }
                resuelto = true;
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                resolve(valor);
            };

            const mostrarModal = () => {
                if (resuelto) {
                    return;
                }

                if (typeof Swal === 'undefined') {
                    finalizar(null);
                    return;
                }

                ensureOverlayStyle('swal-galeria-zindex-style', 'swal-galeria-container');

                Swal.fire({
                    title: 'Novedad del cambio',
                    input: 'textarea',
                    inputLabel: options.inputLabel || 'Por que hiciste este cambio?',
                    inputPlaceholder: options.inputPlaceholder || 'Describe brevemente el motivo...',
                    inputAttributes: { 'aria-label': options.ariaLabel || 'Novedad del cambio' },
                    showCancelButton: true,
                    confirmButtonText: options.confirmButtonText || ' Guardar',
                    cancelButtonText: options.cancelButtonText || 'Cancelar',
                    confirmButtonColor: options.confirmButtonColor || '#10b981',
                    customClass: {
                        container: 'swal-galeria-container swal-centered-container swal-modal-novedad',
                        popup: 'swal-centered-popup swal-popup-top'
                    },
                    didOpen: (modal) => centerOverlay(modal),
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return options.validationMessage || 'Debes ingresar una novedad del cambio';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        finalizar(result.value.trim());
                        return;
                    }
                    finalizar(null);
                });
            };

            if (typeof Swal === 'undefined') {
                const timeoutMs = _ensureSwalReady(mostrarModal);
                if (timeoutMs !== null) {
                    timeoutId = setTimeout(() => finalizar(null), timeoutMs);
                }
                return;
            }

            mostrarModal();
        });
    }

    window.PedidosNovedadHelper = {
        pedirNovedad,
    };
})();
