/**
 * Utilities for prenda-editor-pedidos-adapter UI flows.
 * Exposes: window.PedidosAdapterUiUtils
 */
(function() {
    'use strict';

    function _toFunction(fn, fallback) {
        return typeof fn === 'function' ? fn : fallback;
    }

    function pedirNovedad(options = {}) {
        const fallbackValue = options.fallbackValue || 'edicion de prenda desde lista de pedidos';
        const ensureOverlayStyle = _toFunction(options.ensureOverlayStyle, () => {});
        const centerOverlay = _toFunction(options.centerOverlay, () => {});

        return new Promise((resolve) => {
            if (typeof Swal === 'undefined') {
                resolve(fallbackValue);
                return;
            }

            ensureOverlayStyle('swal-galeria-zindex-style', 'swal-galeria-container');

            Swal.fire({
                title: 'Novedad del cambio',
                input: 'textarea',
                inputLabel: 'Por que hiciste este cambio?',
                inputPlaceholder: 'Describe brevemente el motivo...',
                inputAttributes: { 'aria-label': 'Novedad del cambio' },
                showCancelButton: true,
                confirmButtonText: ' Guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                customClass: { container: 'swal-galeria-container' },
                didOpen: (modal) => centerOverlay(modal),
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Debes ingresar una novedad del cambio';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    resolve(result.value.trim());
                    return;
                }
                resolve(null);
            });
        });
    }

    function mostrarLoadingEditarPrenda() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Cargando prenda...',
                text: 'Obteniendo datos completos',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
        }
    }

    function cerrarSweetAlertsActivos() {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        document.querySelectorAll('.swal2-container').forEach(el => el.remove());
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        document.body.style.overflow = '';
    }

    function confirmarEliminarPrenda(options = {}) {
        const prendaNombre = (options.prendaNombre || 'esta prenda').toUpperCase();
        const onConfirm = _toFunction(options.onConfirm, () => {});
        const ensureOverlayStyle = _toFunction(options.ensureOverlayStyle, () => {});
        const centerOverlay = _toFunction(options.centerOverlay, () => {});

        if (typeof Swal === 'undefined') {
            return;
        }

        ensureOverlayStyle('swal-eliminar-prenda-style', 'swal-eliminar-prenda-container');

        Swal.fire({
            title: 'Eliminar prenda?',
            html: `<p>Estas seguro de que deseas eliminar <strong>${prendaNombre}</strong>?</p>
                   <p style="color: #ef4444; font-size: 0.9em; margin-top: 1rem;">Esta accion no se puede deshacer.</p>`,
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Motivo de la eliminacion',
            inputPlaceholder: 'Ej: Prenda no requerida, cambio en especificaciones, etc.',
            inputAttributes: { 'aria-label': 'Motivo de eliminacion' },
            showCancelButton: true,
            confirmButtonText: ' Si, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            customClass: { container: 'swal-eliminar-prenda-container' },
            didOpen: (modal) => centerOverlay(modal),
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debes ingresar un motivo de eliminacion';
                }
                if (value.trim().length < 5) {
                    return 'El motivo debe tener al menos 5 caracteres';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                onConfirm(result.value.trim());
            }
        });
    }

    function mostrarLoadingEliminarPrenda(options = {}) {
        const centerOverlay = _toFunction(options.centerOverlay, () => {});
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Eliminando prenda...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => {
                    centerOverlay(modal);
                    Swal.showLoading();
                }
            });
        }
    }

    function mostrarErrorEliminarPrenda(texto, options = {}) {
        const centerOverlay = _toFunction(options.centerOverlay, () => {});
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: texto,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => centerOverlay(modal)
            });
        }
    }

    function mostrarExitoEliminarPrenda(options = {}) {
        const centerOverlay = _toFunction(options.centerOverlay, () => {});
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: ' Prenda eliminada',
                text: 'La prenda se elimino y se registro el motivo en novedades del pedido',
                timer: 1800,
                showConfirmButton: false,
                customClass: { container: 'swal-eliminar-prenda-container' },
                didOpen: (modal) => centerOverlay(modal)
            });
        }
    }

    function abrirModalManual(prenda) {
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) {
            console.error('[PedidosAdapter] Modal #modal-agregar-prenda-nueva no encontrado en DOM');
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'No se encontro el modal de edicion de prenda', 'error');
            }
            return;
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const nombreInput = document.getElementById('nueva-prenda-nombre');
        if (nombreInput) nombreInput.value = prenda.nombre_prenda || prenda.nombre || '';

        const descInput = document.getElementById('nueva-prenda-descripcion');
        if (descInput) descInput.value = prenda.descripcion || '';

        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            const origenValue = prenda.origen || (prenda.de_bodega ? 'bodega' : 'confeccion');
            origenSelect.value = origenValue;

            // Actualizar el atributo selected en el HTML también
            origenSelect.querySelectorAll('option').forEach(option => {
                option.removeAttribute('selected');
            });
            const optionASeleccionar = origenSelect.querySelector(`option[value="${origenValue}"]`);
            if (optionASeleccionar) {
                optionASeleccionar.setAttribute('selected', '');
            }

            console.log('[setPrendaActualValores] Origen pre-cargado:', {
                origen: origenValue,
                value_actual: origenSelect.value,
                option_seleccionada: optionASeleccionar?.textContent
            });
        }

        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.textContent = ' Guardar Cambios';
            btnGuardar.className = 'btn btn-success';
        }

        window.prendaActual = prenda;
    }

    window.PedidosAdapterUiUtils = {
        pedirNovedad,
        mostrarLoadingEditarPrenda,
        cerrarSweetAlertsActivos,
        confirmarEliminarPrenda,
        mostrarLoadingEliminarPrenda,
        mostrarErrorEliminarPrenda,
        mostrarExitoEliminarPrenda,
        abrirModalManual
    };
})();
