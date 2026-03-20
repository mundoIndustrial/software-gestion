/**
 * =====================================================
 * SUPERVISOR PEDIDOS - LIMPIAR ASIGNACIONES
 * =====================================================
 * Configura el botón de limpiar asignaciones de colores
 * usando jQuery Bootstrap modal para confirmación.
 */

document.addEventListener('DOMContentLoaded', function() {
    const verificarJQuery = setInterval(function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
            clearInterval(verificarJQuery);

            const btnLimpiarAsignaciones = document.getElementById('btn-limpiar-asignaciones');

            if (btnLimpiarAsignaciones) {
                btnLimpiarAsignaciones.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('[Supervisor-Pedidos] Botón limpiar asignaciones clickeado');

                    const supervisorWrapper = document.querySelector('.supervisor-pedidos-container') ||
                                             document.querySelector('#mainContent') ||
                                             document.querySelector('main');
                    if (supervisorWrapper) {
                        supervisorWrapper.removeAttribute('aria-hidden');
                    }

                    const overlayExistente = document.getElementById('overlay-confirmar-limpiar');
                    if (overlayExistente) {
                        overlayExistente.remove();
                    }

                    try {
                        jQuery('#modal-confirmar-limpiar').modal('show');
                        console.log('[Supervisor-Pedidos] Modal de confirmación abierto (Bootstrap 4)');
                    } catch (error) {
                        console.error('[Supervisor-Pedidos] Error al abrir modal:', error);
                        if (confirm('¿Eliminar todas las asignaciones de colores? Esta acción no se puede deshacer.')) {
                            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarTodo === 'function') {
                                window.ColoresPorTalla.limpiarTodo();
                            }
                        }
                    }
                });

                const btnConfirmarLimpiar = document.getElementById('btn-confirmar-limpiar-todo');
                if (btnConfirmarLimpiar) {
                    btnConfirmarLimpiar.addEventListener('click', function() {
                        console.log('[Supervisor-Pedidos] Confirmado: Limpiar todo');

                        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.limpiarTodo === 'function') {
                            window.ColoresPorTalla.limpiarTodo();
                        }

                        if (typeof actualizarTotalPrendas === 'function') {
                            actualizarTotalPrendas();
                        }

                        const modalLimpiar = document.getElementById('modal-confirmar-limpiar');
                        if (modalLimpiar) {
                            jQuery(modalLimpiar).modal('hide');
                        }

                        const ov = document.getElementById('overlay-confirmar-limpiar');
                        if (ov) ov.remove();

                        console.log('[Supervisor-Pedidos] Limpieza completada');
                    });
                }

                jQuery('#modal-confirmar-limpiar').on('hidden.bs.modal', function() {
                    const supervisorWrapper = document.querySelector('.supervisor-pedidos-container') ||
                                             document.querySelector('#mainContent') ||
                                             document.querySelector('main');
                    if (supervisorWrapper) {
                        supervisorWrapper.setAttribute('aria-hidden', 'true');
                    }
                });

                console.log('[Supervisor-Pedidos] ✅ Botón limpiar asignaciones configurado');
            } else {
                console.log('[Supervisor-Pedidos] ⚠️ Botón limpiar asignaciones no encontrado');
            }
        }
    }, 100);
});
