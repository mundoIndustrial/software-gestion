(function() {
    'use strict';

    let hayDatosModificados = false;
    let bloqueadoParaGuardado = false;

    function marcarModificado() {
        if (!bloqueadoParaGuardado) {
            hayDatosModificados = true;
        }
    }

    function marcarNoModificado() {
        hayDatosModificados = false;
    }

    function bloquearDeteccionTemporalmente() {
        bloqueadoParaGuardado = true;
        setTimeout(() => {
            bloqueadoParaGuardado = false;
        }, 500);
    }

    function mostrarAdvertenciaNavegacion(callback) {
        return Swal.fire({
            icon: 'warning',
            title: '¿Abandonar sin guardar?',
            text: 'Tienes cambios sin guardar. ¿Estás seguro de que quieres abandonar esta página?',
            customClass: {
                container: 'swal-centered-container',
                popup: 'swal-centered-popup'
            },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, abandonar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (typeof callback === 'function') {
                callback(result.isConfirmed);
            }
            return result;
        });
    }

    function configurarDeteccionCambios() {
        const form = document.getElementById('formCrearPedidoEditable');
        if (!form) {
            console.warn('[DraftPedidoUnsavedChanges] Formulario no encontrado');
            return;
        }

        // Detectar cambios en inputs, selects y textareas
        form.addEventListener('input', marcarModificado);
        form.addEventListener('change', marcarModificado);

        // Detectar cambios en prendas (eventos del contenedor)
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (prendasContainer) {
            prendasContainer.addEventListener('change', marcarModificado);
            prendasContainer.addEventListener('input', marcarModificado);
        }

        // Detectar cambios en items de EPP
        const listaItems = document.getElementById('lista-items-pedido');
        if (listaItems) {
            listaItems.addEventListener('change', marcarModificado);
            listaItems.addEventListener('input', marcarModificado);
        }
    }

    function configurarBotonCancelar() {
        const btnCancelar = document.querySelector('a[href*="asesores/pedidos"]');
        if (!btnCancelar) {
            console.warn('[DraftPedidoUnsavedChanges] Botón cancelar no encontrado');
            return;
        }

        btnCancelar.addEventListener('click', function(e) {
            if (!hayDatosModificados) {
                return;
            }

            e.preventDefault();
            const href = this.getAttribute('href');

            mostrarAdvertenciaNavegacion((confirmado) => {
                if (confirmado) {
                    window.location.href = href;
                }
            });
        });
    }

    function configurarEventoBeforeUnload() {
        window.addEventListener('beforeunload', function(e) {
            if (!hayDatosModificados) {
                return;
            }

            e.preventDefault();
            e.returnValue = '';
            return '';
        });
    }

    function detectarDragDropEnPestana() {
        document.addEventListener('dragover', function(e) {
            if (!hayDatosModificados) {
                return;
            }

            // Si es una imagen la que se arrastra
            if (e.dataTransfer && e.dataTransfer.types && e.dataTransfer.types.includes('Files')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'none';
            }
        });

        document.addEventListener('drop', function(e) {
            if (!hayDatosModificados) {
                return;
            }

            // Si hay archivos siendo soltados en la página
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                e.preventDefault();

                // Mostrar modal de advertencia
                mostrarAdvertenciaNavegacion();
            }
        });
    }

    function marcarGuardado() {
        bloquearDeteccionTemporalmente();
        marcarNoModificado();
    }

    function limpiar() {
        hayDatosModificados = false;
        bloqueadoParaGuardado = false;
    }

    function inicializar() {
        // Esperar a que el DOM esté completamente listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    configurarDeteccionCambios();
                    configurarBotonCancelar();
                    configurarEventoBeforeUnload();
                    detectarDragDropEnPestana();
                }, 100);
            });
        } else {
            setTimeout(() => {
                configurarDeteccionCambios();
                configurarBotonCancelar();
                configurarEventoBeforeUnload();
                detectarDragDropEnPestana();
            }, 100);
        }
    }

    // API pública
    window.DraftPedidoUnsavedChanges = {
        inicializar,
        marcarModificado,
        marcarGuardado,
        marcarNoModificado,
        limpiar,
        hayDatosModificados: () => hayDatosModificados,
        mostrarAdvertenciaNavegacion
    };

    // Integración con el guardado de borrador
    const observer = new MutationObserver(() => {
        if (window.DraftPedidoOrchestrator && typeof window.DraftPedidoOrchestrator.registrarBotonGuardarBorrador === 'function') {
            const originalGuardar = window.guardarComoBorrador;
            if (originalGuardar) {
                window.guardarComoBorrador = async function() {
                    marcarGuardado();
                    return originalGuardar.apply(this, arguments);
                };
            }
            observer.disconnect();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Iniciar automáticamente
    inicializar();
})();
