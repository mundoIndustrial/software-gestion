(function() {
    'use strict';

    function obtenerEstadoItems() {
        const listaPrendas = document.getElementById('prendas-container-editable');
        const listaItems = document.getElementById('lista-items-pedido');

        const tienePrendas = listaPrendas && listaPrendas.querySelector('.prenda-item-card');
        const tieneItems = listaItems && listaItems.children.length > 0;

        return { tienePrendas, tieneItems };
    }

    function mostrarPedidoVacio() {
        return Swal.fire({
            icon: 'warning',
            title: ' Pedido Vacio',
            text: 'Agrega al menos una prenda o item EPP antes de guardar como borrador',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#fb923c'
        });
    }

    function confirmarGuardadoBorrador() {
        return Swal.fire({
            icon: 'question',
            title: ' Guardar Borrador',
            html: `
                <div style="text-align: left;">
                    <p style="margin-bottom: 10px;">
                        <strong>Este pedido se guardara como borrador</strong>
                    </p>
                    <ul style="margin: 10px 0; text-align: left; display: inline-block;">
                        <li>Si, no se asignara numero de pedido</li>
                        <li>Si, estado: Borrador</li>
                        <li>Si, podras editarlo despues</li>
                    </ul>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#fb923c',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Si, guardar borrador',
            cancelButtonText: 'Cancelar'
        });
    }

    function mostrarLoadingGuardado() {
        return Swal.fire({
            title: ' Guardando Borrador...',
            html: '<div style="text-align: center;"><div style="width: 50px; height: 50px; border: 4px solid #e5e7eb; border-top-color: #fb923c; border-radius: 50%; margin: 20px auto; animation: spin 0.8s linear infinite;"></div></div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
    }

    function recopilarDatosPedido() {
        if (typeof window.sincronizarPrendaModalAntesDeGuardarBorrador === 'function') {
            window.sincronizarPrendaModalAntesDeGuardarBorrador();
        }

        const datos = (typeof window.prepararDatosParaEnvio === 'function')
            ? window.prepararDatosParaEnvio({ soloConCantidades: false })
            : null;

        if (!datos) {
            throw new Error('No se pudo recopilar los datos del pedido. Recarga la pagina e intenta de nuevo.');
        }

        datos.observaciones = document.getElementById('observaciones_editable')?.value?.trim() || '';

        return datos;
    }

    function construirPayload(datos) {
        const csrfToken = document.querySelector('input[name="_token"]')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.content;

        if (!window.DraftPedidoBuilder || typeof window.DraftPedidoBuilder.construirFormDataBorrador !== 'function') {
            throw new Error('No se pudo construir el payload del borrador.');
        }

        return window.DraftPedidoBuilder.construirFormDataBorrador(datos, csrfToken);
    }

    async function enviarBorrador(payload) {
        const modoEdicion = window.modoEdicion || false;
        const pedidoId = window.pedidoEditarId || null;

        if (!window.DraftPedidoSaveService || typeof window.DraftPedidoSaveService.enviarBorrador !== 'function') {
            throw new Error('No se pudo enviar el borrador.');
        }

        return window.DraftPedidoSaveService.enviarBorrador(payload.formData, {
            modoEdicion,
            pedidoId,
            endpointCrear: window.routeGuardarBorradorUrl || '/asesores/pedidos-editable/guardar-borrador'
        });
    }

    function mostrarExito(resultado) {
        const modoEdicion = window.modoEdicion || false;
        const pedidoId = window.pedidoEditarId || null;

        if (modoEdicion && pedidoId) {
            return Swal.fire({
                icon: 'success',
                title: 'Cambios Guardados',
                html: `
                    <div style="text-align: left;">
                        <p>El pedido ha sido actualizado correctamente.</p>
                        <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                            <strong>Pedido #${resultado.numero_pedido || pedidoId}</strong>
                        </p>
                    </div>
                `,
                confirmButtonColor: '#0066cc',
                confirmButtonText: 'Aceptar'
            });
        }

        return Swal.fire({
            icon: 'success',
            title: 'Borrador Guardado',
            html: `
                <div style="text-align: left;">
                    <p>Tu pedido ha sido guardado como borrador.</p>
                    <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                        <strong>ID:</strong> #${resultado.pedido_id}
                    </p>
                </div>
            `,
            confirmButtonColor: '#0066cc',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            if (resultado.redirect_url) {
                window.location.href = resultado.redirect_url;
            } else if (window.routePedidosIndexUrl) {
                window.location.href = window.routePedidosIndexUrl;
            }
        });
    }

    function mostrarError(error) {
        console.error('[DraftPedidoOrchestrator] Error:', error);
        return Swal.fire({
            icon: 'error',
            title: ' Error al Guardar Borrador',
            text: error.message || 'No se pudo guardar el borrador. Intenta nuevamente.',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Aceptar'
        });
    }

    async function guardarComoBorrador() {
        try {
            const { tienePrendas, tieneItems } = obtenerEstadoItems();
            if (!tienePrendas && !tieneItems) {
                await mostrarPedidoVacio();
                return;
            }

            const confirmacion = await confirmarGuardadoBorrador();
            if (!confirmacion.isConfirmed) {
                return;
            }

            mostrarLoadingGuardado();

            const datos = recopilarDatosPedido();
            const payload = construirPayload(datos);

            console.debug('[DraftPedidoOrchestrator] Datos a enviar:', payload.pedidoLimpio);

            const { resultado } = await enviarBorrador(payload);

            if (!resultado.success) {
                throw new Error(resultado.message || 'Error desconocido al guardar borrador');
            }

            await mostrarExito(resultado);
        } catch (error) {
            await mostrarError(error);
        }
    }

    function registrarBotonGuardarBorrador() {
        window.guardarComoBorrador = async function() {
            return guardarComoBorrador();
        };

        const btnGuardarBorrador = document.getElementById('btn-guardar-borrador');
        if (!btnGuardarBorrador || btnGuardarBorrador.dataset.draftBound === '1') {
            return;
        }

        btnGuardarBorrador.dataset.draftBound = '1';
        btnGuardarBorrador.addEventListener('click', function(e) {
            e.preventDefault();
            window.guardarComoBorrador();
        });
    }

    window.DraftPedidoOrchestrator = {
        guardarComoBorrador,
        registrarBotonGuardarBorrador
    };
})();
