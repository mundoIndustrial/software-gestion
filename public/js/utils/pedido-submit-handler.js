/**
 * Manejador de Envío de Pedidos (Refactorizado)
 * Simplifica el envío de pedidos usando los servicios creados
 */

class PedidoSubmitHandler {
    /**
     * Manejar envío de pedido con cotización
     * @param {Object} options - Opciones de configuración
     */
    static async handleSubmitConCotizacion(options = {}) {
        const {
            cotizacionId,
            prendasCargadas,
            prendasEliminadas,
            currentLogoCotizacion,
            logoTecnicasSeleccionadas,
            logoSeccionesSeleccionadas,
            logoFotosSeleccionadas,
            formaPagoInput,
            clienteInput
        } = options;

        console.log('🚀 [SUBMIT] Iniciando envío de pedido con cotización');

        // ============================================================
        // 1. VALIDACIÓN INICIAL
        // ============================================================
        if (!cotizacionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una cotización',
                text: 'Por favor selecciona una cotización antes de continuar',
                confirmButtonText: 'OK'
            });
            return;
        }

        // ============================================================
        // 2. DETECTAR TIPO DE COTIZACIÓN
        // ============================================================
        const tipoInfo = window.FormDataCollector.detectarTipoCotizacion();
        const esLogo = logoTecnicasSeleccionadas?.length > 0 || 
                       logoSeccionesSeleccionadas?.length > 0 || 
                       logoFotosSeleccionadas?.length > 0;

        console.log('🎯 Tipo de cotización:', {
            tipo: tipoInfo.tipo,
            esCombinada: tipoInfo.esCombinada,
            esLogoSolo: tipoInfo.esLogoSolo,
            esLogo: esLogo
        });

        // ============================================================
        // 3. RECOPILAR DATOS
        // ============================================================
        let prendasParaEnviar = [];
        let datosLogo = null;

        // Recopilar prendas (solo para COMBINADA)
        if (tipoInfo.esCombinada) {
            prendasParaEnviar = window.FormDataCollector.recopilarPrendas(
                prendasCargadas,
                prendasEliminadas
            );
            console.log('📦 Prendas a enviar:', prendasParaEnviar);
        }

        // Recopilar datos de logo (para LOGO SOLO o COMBINADA)
        if (tipoInfo.esLogoSolo || tipoInfo.esCombinada) {
            datosLogo = window.FormDataCollector.recopilarDatosLogo(currentLogoCotizacion);
            console.log('🎨 Datos de logo:', datosLogo);
        }

        // ============================================================
        // 4. PREPARAR BODY PARA PRIMER REQUEST
        // ============================================================
        const bodyCrearPedido = {
            cotizacion_id: cotizacionId,
            forma_de_pago: formaPagoInput.value,
            prendas: prendasParaEnviar
        };

        console.log('📤 Enviando creación de pedido...', bodyCrearPedido);

        try {
            // ============================================================
            // 5. ENVIAR PRIMER REQUEST (Crear Pedido)
            // ============================================================
            const resultadoPedido = await window.ApiService.withLoading(
                window.ApiService.crearPedidoDesdeCotizacion(cotizacionId, bodyCrearPedido),
                'Creando pedido...'
            );

            console.log('✅ Pedido creado:', resultadoPedido);

            if (!resultadoPedido.success) {
                throw new Error(resultadoPedido.message || 'Error al crear pedido');
            }

            // ============================================================
            // 6. ENVIAR DATOS DE LOGO (si aplica)
            // ============================================================
            let resultadoLogo = null;

            if (tipoInfo.esCombinada && datosLogo && this.tieneDataLogo(datosLogo)) {
                console.log('📍 [COMBINADA] Enviando datos de logo...');
                
                resultadoLogo = await this.enviarDatosLogo({
                    pedidoId: resultadoPedido.logo_pedido_id,
                    logoCotizacionId: resultadoPedido.logo_cotizacion_id,
                    cotizacionId: cotizacionId,
                    cliente: clienteInput.value,
                    formaPago: formaPagoInput.value,
                    datosLogo: datosLogo,
                    logoFotos: logoFotosSeleccionadas
                });

                console.log('✅ Logo guardado:', resultadoLogo);
            }

            // ============================================================
            // 7. MOSTRAR ÉXITO Y REDIRIGIR
            // ============================================================
            this.mostrarExito({
                tipoInfo,
                resultadoPedido,
                resultadoLogo,
                datosLogo
            });

        } catch (error) {
            console.error('❌ Error al crear pedido:', error);
            window.ApiService.handleError(error, 'Crear pedido');
        }
    }

    /**
     * Verificar si hay datos de logo para guardar
     * @param {Object} datosLogo - Datos del logo
     * @returns {boolean}
     */
    static tieneDataLogo(datosLogo) {
        if (!datosLogo) return false;

        const tieneDescripcion = datosLogo.descripcion && datosLogo.descripcion.trim().length > 0;
        const tieneTecnicas = datosLogo.tecnicas && datosLogo.tecnicas.length > 0;
        const tieneUbicaciones = datosLogo.secciones && datosLogo.secciones.length > 0;
        const tieneCantidad = datosLogo.cantidadTotal > 0;

        return tieneDescripcion || tieneTecnicas || tieneUbicaciones || tieneCantidad;
    }

    /**
     * Enviar datos de logo al backend
     * @param {Object} params - Parámetros
     * @returns {Promise}
     */
    static async enviarDatosLogo(params) {
        const {
            pedidoId,
            logoCotizacionId,
            cotizacionId,
            cliente,
            formaPago,
            datosLogo,
            logoFotos
        } = params;

        const bodyLogoPedido = {
            pedido_id: pedidoId,
            logo_cotizacion_id: logoCotizacionId,
            cotizacion_id: cotizacionId,
            cliente: cliente,
            forma_de_pago: formaPago,
            descripcion: datosLogo.descripcion,
            cantidad: datosLogo.cantidadTotal,
            tecnicas: datosLogo.tecnicas,
            observaciones_tecnicas: datosLogo.observacionesTecnicas,
            ubicaciones: datosLogo.secciones,
            fotos: logoFotos
        };

        console.log('🎨 Datos del logo a guardar:', bodyLogoPedido);

        const response = await fetch('/asesores/pedidos/guardar-logo-pedido', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(bodyLogoPedido)
        });

        if (!response.ok) {
            throw new Error('Error al guardar datos del logo');
        }

        return await response.json();
    }

    /**
     * Mostrar mensaje de éxito y redirigir
     * @param {Object} params - Parámetros
     */
    static mostrarExito(params) {
        const { tipoInfo, resultadoPedido, resultadoLogo, datosLogo } = params;

        let titulo = '¡Éxito!';
        let mensaje = '';

        if (tipoInfo.esLogoSolo) {
            // LOGO SOLO
            const numeroLogo = resultadoPedido.numero_pedido || 'LOGO-PENDIENTE';
            mensaje = `Pedido de LOGO creado exitosamente<br><br><strong>🎨 Logo:</strong> ${numeroLogo}`;
            
        } else if (tipoInfo.esCombinada) {
            // COMBINADA
            const numeroPrendas = resultadoPedido.pedido_numero || 'N/A';
            const hayDataLogo = this.tieneDataLogo(datosLogo);

            if (!hayDataLogo) {
                // Solo prendas
                mensaje = `Pedido de PRENDAS creado exitosamente<br><br><strong>📦 Pedido:</strong> ${numeroPrendas}`;
            } else {
                // Prendas + Logo
                const numeroLogo = resultadoLogo?.numero_pedido_logo || resultadoLogo?.logo_pedido?.numero_pedido || 'N/A';
                mensaje = `Pedidos creados exitosamente<br><br>` +
                         `<strong>📦 Pedido Producción:</strong> ${numeroPrendas}<br>` +
                         `<strong>🎨 Pedido Logo:</strong> ${numeroLogo}`;
            }
            
        } else {
            // PRENDA NORMAL
            const numeroPedido = resultadoPedido.numero_pedido || resultadoPedido.pedido_numero || 'N/A';
            mensaje = `Pedido creado exitosamente<br><br><strong>📦 Pedido:</strong> ${numeroPedido}`;
        }

        Swal.fire({
            icon: 'success',
            title: titulo,
            html: `<p style="font-size: 16px; line-height: 1.8;">${mensaje}</p>`,
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = '/asesores/pedidos';
        });
    }
}

// Exportar globalmente
window.PedidoSubmitHandler = PedidoSubmitHandler;
