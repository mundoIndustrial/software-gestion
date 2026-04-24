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
            title: '¿Estas seguro de guardarlo como borrador?',
            position: 'center',
            customClass: {
                container: 'swal-centered-container',
                popup: 'swal-centered-popup'
            },
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

    function validarTamanoTotalImagenes(datos) {
        let totalMB = 0;
        const MAX_TOTAL_MB = 50;

        // Sumar tamaño de todas las imágenes
        if (Array.isArray(datos.prendas)) {
            datos.prendas.forEach(prenda => {
                // Imágenes de prenda
                if (Array.isArray(prenda.imagenes)) {
                    prenda.imagenes.forEach(img => {
                        if (img instanceof File) totalMB += img.size / (1024 * 1024);
                    });
                }

                // Imágenes de telas
                if (Array.isArray(prenda.telas)) {
                    prenda.telas.forEach(tela => {
                        if (Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach(img => {
                                if (img instanceof File) totalMB += img.size / (1024 * 1024);
                            });
                        }
                    });
                }

                // Imágenes de procesos
                if (prenda.procesos && typeof prenda.procesos === 'object') {
                    Object.values(prenda.procesos).forEach(proceso => {
                        const imagenes = proceso?.datos?.imagenes || proceso?.imagenes || [];
                        if (Array.isArray(imagenes)) {
                            imagenes.forEach(img => {
                                if (img instanceof File) totalMB += img.size / (1024 * 1024);
                            });
                        }
                    });
                }
            });
        }

        // Imágenes de EPP
        if (Array.isArray(datos.epps)) {
            datos.epps.forEach(epp => {
                if (Array.isArray(epp.imagenes)) {
                    epp.imagenes.forEach(img => {
                        if (img instanceof File) totalMB += img.size / (1024 * 1024);
                    });
                }
            });
        }

        if (totalMB > MAX_TOTAL_MB) {
            throw new Error(
                `El tamaño total de las imágenes es ${totalMB.toFixed(1)}MB. ` +
                `Máximo permitido: ${MAX_TOTAL_MB}MB. Las imágenes deben haber sido comprimidas automáticamente.`
            );
        }

        return true;
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

        // Validar que haya un cliente seleccionado
        const cliente = (typeof datos.cliente === 'string' && datos.cliente.trim()) ? datos.cliente.trim() : null;
        if (!cliente) {
            // Registrar error de validación
            if (globalThis.ErrorLoggerService?.registrarErrorValidacion) {
                globalThis.ErrorLoggerService.registrarErrorValidacion('cliente', datos.cliente, 'Cliente vacío o no seleccionado');
            }
            throw new Error('Debes seleccionar un cliente antes de guardar el borrador.');
        }

        // Validar tamaño total de imágenes
        try {
            validarTamanoTotalImagenes(datos);
        } catch (error) {
            // Registrar error de validación de imágenes
            if (globalThis.ErrorLoggerService?.registrarErrorValidacion) {
                globalThis.ErrorLoggerService.registrarErrorValidacion('imagenes_total', null, error.message);
            }
            throw error;
        }

        datos.observaciones = document.getElementById('observaciones_editable')?.value?.trim() || '';

        return datos;
    }

    async function construirPayload(datos) {
        const csrfToken = document.querySelector('input[name="_token"]')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.content;

        if (!window.DraftPedidoBuilder || typeof window.DraftPedidoBuilder.construirFormDataBorrador !== 'function') {
            throw new Error('No se pudo construir el payload del borrador.');
        }

        return await window.DraftPedidoBuilder.construirFormDataBorrador(datos, csrfToken);
    }

    async function enviarBorrador(payload) {
        const modoEdicion = window.modoEdicion || false;
        const pedidoId = window.pedidoEditarId || null;

        // 🔧 DEBUG: Log para diagnosticar el flujo
        console.warn('[DraftPedidoOrchestrator] GUARDANDO BORRADOR', {
            modoEdicion,
            pedidoId,
            esValido: !!(modoEdicion && pedidoId && !isNaN(pedidoId) && pedidoId > 0),
            endpoint: modoEdicion && pedidoId ? `/api/asesores/pedidos/${pedidoId}/borrador` : '/api/asesores/pedidos/borrador',
            metodo: 'POST'
        });

        if (!window.DraftPedidoSaveService || typeof window.DraftPedidoSaveService.enviarBorrador !== 'function') {
            throw new Error('No se pudo enviar el borrador.');
        }

        return window.DraftPedidoSaveService.enviarBorrador(payload.formData, {
            modoEdicion,
            pedidoId,
            endpointCrear: window.routeGuardarBorradorUrl || '/asesores/pedidos/guardar-borrador'
        });
    }

    function sincronizarIdsPrendasNuevasEnMemoria(resultado) {
        const mapeadas = Array.isArray(resultado?.nuevas_prendas_mapeadas)
            ? resultado.nuevas_prendas_mapeadas
            : [];

        if (!mapeadas.length || !window.gestionItemsUI || !Array.isArray(window.gestionItemsUI.prendas)) {
            return;
        }

        const mapaPorLocalId = new Map();
        mapeadas.forEach((item) => {
            const localId = typeof item?.local_id === 'string' ? item.local_id.trim() : '';
            const prendaPedidoId = Number(item?.prenda_pedido_id || 0);
            if (!localId || !Number.isInteger(prendaPedidoId) || prendaPedidoId <= 0) {
                return;
            }
            mapaPorLocalId.set(localId, prendaPedidoId);
        });

        if (!mapaPorLocalId.size) {
            return;
        }

        let actualizadas = 0;
        window.gestionItemsUI.prendas.forEach((prenda) => {
            const localId = typeof prenda?._local_id === 'string' ? prenda._local_id.trim() : '';
            if (!localId || !mapaPorLocalId.has(localId)) {
                return;
            }

            const nuevoId = mapaPorLocalId.get(localId);
            prenda.prenda_pedido_id = nuevoId;
            prenda.id = nuevoId;
            actualizadas++;
        });

        if (actualizadas > 0) {
            console.debug('[DraftPedidoOrchestrator] IDs de nuevas prendas sincronizados en memoria', {
                total: actualizadas
            });
        }
    }

    function limpiarMarcasImagenesEliminadas() {
        window.imagenesAEliminar = [];

        if (!window.gestionItemsUI || !Array.isArray(window.gestionItemsUI.prendas)) {
            return;
        }

        window.gestionItemsUI.prendas.forEach((prenda) => {
            if (!prenda || typeof prenda !== 'object') {
                return;
            }
            prenda.imagenes_a_eliminar = [];
        });
    }

    function mostrarExito(resultado, datos) {
        const modoEdicion = window.modoEdicion || false;
        const pedidoId = window.pedidoEditarId || null;
        const nombreCliente = (typeof datos?.cliente === 'string' && datos.cliente.trim())
            ? datos.cliente.trim()
            : (resultado?.cliente_nombre || resultado?.nombre_cliente || resultado?.cliente || '');

        if (modoEdicion && pedidoId) {
            return Swal.fire({
                icon: 'success',
                title: 'Cambios Guardados',
                customClass: {
                    container: 'swal-centered-container',
                    popup: 'swal-centered-popup'
                },
                html: `
                    <div style="text-align: left;">
                        <p>El pedido ha sido actualizado correctamente.</p>
                        <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                            <strong>Cliente:</strong> ${nombreCliente || 'Sin nombre'}
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
            customClass: {
                container: 'swal-centered-container',
                popup: 'swal-centered-popup'
            },
            html: `
                <div style="text-align: left;">
                    <p>Tu pedido ha sido guardado como borrador.</p>
                    <p style="margin-top: 10px; padding: 10px; background: #f0f7ff; border-left: 4px solid #0066cc; border-radius: 4px;">
                        <strong>Cliente:</strong> ${nombreCliente || 'Sin nombre'}
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

    function _esErrorDeConexion(error) {
        const msg = (error?.message || '').toLowerCase();
        return (
            !navigator.onLine ||
            msg.includes('failed to fetch') ||
            msg.includes('network') ||
            msg.includes('error de conexión') ||
            msg.includes('connection') ||
            msg.includes('econnrefused') ||
            msg.includes('econnreset') ||
            msg.includes('etimedout') ||
            msg.includes('ehostunreach')
        );
    }

    function mostrarError(error) {
        console.error('[DraftPedidoOrchestrator] Error:', error);

        if (_esErrorDeConexion(error)) {
            return Swal.fire({
                icon: 'warning',
                title: 'Sin conexión a internet',
                html: `
                    <p>No se pudo guardar el borrador porque no hay conexión a internet.</p>
                    <div style="margin-top: 12px; padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px; text-align: left;">
                        <strong>⚠️ No cierres esta pestaña.</strong><br>
                        Tus datos están guardados en la página. Cuando recuperes el internet, presiona "Guardar Borrador" nuevamente.
                    </div>
                `,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Entendido, no cierro la pestaña',
                allowOutsideClick: false,
                allowEscapeKey: false,
            });
        }

        return Swal.fire({
            icon: 'error',
            title: 'Error al Guardar Borrador',
            text: error.message || 'No se pudo guardar el borrador. Intenta nuevamente.',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Aceptar'
        });
    }

    function _registrarResultadoOperacion(resultado, intentoActual, datos, modoEdicion, pedidoId, esExito) {
        if (esExito) {
            // Registrar éxito
            if (globalThis.DraftPedidoAnalytics?.registrarIntentoEnvio) {
                globalThis.DraftPedidoAnalytics.registrarIntentoEnvio(true, intentoActual > 1);
            }
            if (globalThis.ErrorLoggerService?.registrarExito) {
                globalThis.ErrorLoggerService.registrarExito('guardar_borrador', {
                    modo: modoEdicion ? 'edicion' : 'creacion',
                    intentos: intentoActual,
                    cliente: datos?.cliente || 'unknown'
                });
            }
        } else {
            // Registrar fallo
            if (globalThis.DraftPedidoAnalytics?.registrarIntentoEnvio) {
                globalThis.DraftPedidoAnalytics.registrarIntentoEnvio(false, false);
            }
            if (globalThis.ErrorLoggerService?.registrarErrorRed) {
                const endpoint = modoEdicion ? `/api/asesores/pedidos/${pedidoId}/borrador` : '/api/asesores/pedidos/borrador';
                globalThis.ErrorLoggerService.registrarErrorRed(
                    endpoint,
                    resultado.statusCode || 'UNKNOWN',
                    resultado.message || 'Error desconocido',
                    intentoActual
                );
            }
        }
    }

    let _guardandoBorrador = false;

    async function guardarComoBorrador() {
        if (_guardandoBorrador) {
            console.warn('[DraftPedidoOrchestrator] Guardado ya en progreso, ignorando solicitud duplicada.');
            return;
        }

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

            _guardandoBorrador = true;
            mostrarLoadingGuardado();

            const datos = recopilarDatosPedido();
            const payload = await construirPayload(datos);

            console.debug('[DraftPedidoOrchestrator] Datos a enviar:', payload.pedidoLimpio);

            const { resultado, intentoActual } = await enviarBorrador(payload);

            if (intentoActual > 1) {
                console.warn('[DraftPedidoOrchestrator] Borrador guardado después de reintentos:', {
                    intentoActual,
                    success: resultado.success,
                    timestamp: new Date().toISOString()
                });
            }

            const modoEdicion = globalThis.modoEdicion || false;
            const pedidoId = globalThis.pedidoEditarId || null;

            _registrarResultadoOperacion(resultado, intentoActual, datos, modoEdicion, pedidoId, resultado.success);

            if (!resultado.success) {
                throw new Error(resultado.message || 'Error desconocido al guardar borrador');
            }

            sincronizarIdsPrendasNuevasEnMemoria(resultado);
            limpiarMarcasImagenesEliminadas();

            // Marcar como guardado para la detección de cambios sin guardar
            if (window.DraftPedidoUnsavedChanges && typeof window.DraftPedidoUnsavedChanges.marcarGuardado === 'function') {
                window.DraftPedidoUnsavedChanges.marcarGuardado();
            }

            await mostrarExito(resultado, datos);
        } catch (error) {
            await mostrarError(error);
        } finally {
            _guardandoBorrador = false;
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
