/**
 * INTEGRACIÓN: Prenda Sin Cotización - Validación y Envío
 * 
 * Este módulo se encarga de integrar el módulo de prenda sin cotización
 * con el flujo general de validación y envío del formulario.
 */

/**
 * Validar datos de prendas tipo PRENDA antes de envío
 * @returns {boolean} true si pasa validación
 */
window.validarPrendasTipoPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        console.warn('⚠️ GestorPrendaSinCotizacion no inicializado');
        return false;
    }

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    if (prendas.length === 0) {
        Swal.fire('Error', 'Debe agregar al menos una prenda', 'error');
        return false;
    }

    let todasValidas = true;
    const errores = [];

    prendas.forEach((prenda, index) => {
        const validacion = window.gestorPrendaSinCotizacion.validar(index);
        if (!validacion.valido) {
            todasValidas = false;
            errores.push(...validacion.errores);
        }
    });

    if (!todasValidas) {
        const mensajeErrores = errores.map(e => `• ${e}`).join('\n');
        Swal.fire({
            title: 'Errores de validación',
            text: 'Corrija los siguientes errores:\n\n' + mensajeErrores,
            icon: 'error',
            html: '<p style="text-align: left;">' + mensajeErrores.replace(/\n/g, '<br>') + '</p>'
        });
        return false;
    }

    logWithEmoji('✅', 'Validación de prendas PRENDA completada correctamente');
    return true;
};

/**
 * Obtener datos de prendas PRENDA para envío
 * @returns {Object} Datos formateados para envío
 */
window.obtenerDatosPrendasTipoPrendaSinCotizacion = function() {
    if (!window.gestorPrendaSinCotizacion) {
        return {
            prendas: [],
            fotosNuevas: {},
            telasFotosNuevas: {},
            prendasEliminadas: []
        };
    }

    return window.gestorPrendaSinCotizacion.obtenerDatosFormato();
};

/**
 * Limpiar el módulo de prenda sin cotización
 */
window.limpiarPrendasTipoPrendaSinCotizacion = function() {
    if (window.gestorPrendaSinCotizacion) {
        window.gestorPrendaSinCotizacion.limpiar();
        logWithEmoji('🗑️', 'Módulo de prendas PRENZA sin cotización limpiado');
    }
};

/**
 * Obtener resumen de prendas para mostrar en confirmación de envío
 * @returns {string} HTML del resumen
 */
window.obtenerResumenPrendasTipoPrendaSinCotizacion = function() {
    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    if (prendas.length === 0) {
        return '<p style="color: #999;">No hay prendas agregadas</p>';
    }

    let html = `
        <div style="text-align: left; max-height: 300px; overflow-y: auto;">
            <h4 style="margin-top: 0; color: #0066cc;">Resumen de Prendas:</h4>
    `;

    prendas.forEach((prenda, index) => {
        const tallas = prenda.tallas?.join(', ') || 'Sin tallas';
        const telas = prenda.variantes?.telas_multiples?.length || 0;
        const fotos = prenda.fotos?.length || 0;

        html += `
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f0f9ff; border-left: 3px solid #0066cc; border-radius: 4px;">
                <strong style="color: #0052a3;">Prenda ${index + 1}: ${prenda.nombre_producto || 'Sin nombre'}</strong>
                <div style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                    <div>📏 Tallas: ${tallas}</div>
                    <div>🎨 Telas: ${telas}</div>
                    <div>📸 Fotos: ${fotos}</div>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
};

/**
 * Hook para ejecutarse ANTES de validar el formulario completo
 * Verifica si estamos en modo PRENDA sin cotización
 */
window.hookPreValidacionPrendaSinCotizacion = function() {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log('🔍 Pre-validación: Modo PRENDA sin cotización detectado');
        return window.validarPrendasTipoPrendaSinCotizacion();
    }
    
    return true;
};

/**
 * Hook para ejecutarse ANTES de serializar datos para envío
 * Agrega datos de prendas PRENDA al objeto de envío
 */
window.hookSerializacionPrendaSinCotizacion = function(datosEnvio) {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log('📤 Serializando datos PRENDA sin cotización');
        const datosPrenda = window.obtenerDatosPrendasTipoPrendaSinCotizacion();
        
        // Agregar datos de prendas al objeto de envío
        datosEnvio.prendas = datosPrenda.prendas;
        datosEnvio.fotosNuevas = datosPrenda.fotosNuevas;
        datosEnvio.telasFotosNuevas = datosPrenda.telasFotosNuevas;
        datosEnvio.prendasEliminadas = datosPrenda.prendasEliminadas;
        datosEnvio.tipoPedidoNuevo = 'P'; // PRENDA
        
        console.log('📦 Datos PRENDA agregados:', datosEnvio);
    }
    
    return datosEnvio;
};

/**
 * Enviar pedido PRENDA sin cotización al servidor
 * @returns {Promise} Promise que resuelve cuando el pedido se guarda
 */
window.enviarPrendaSinCotizacion = function() {
    return new Promise(async (resolve, reject) => {
        try {
            // Validar datos
            if (!window.validarPrendasTipoPrendaSinCotizacion()) {
                reject(new Error('Validación fallida'));
                return;
            }

            // Obtener datos
            const datosPrenda = window.obtenerDatosPrendasTipoPrendaSinCotizacion();
            
            // Obtener cliente
            const cliente = document.getElementById('cliente_editable')?.value;
            const formaPago = document.getElementById('forma_de_pago_editable')?.value || '';

            if (!cliente) {
                Swal.fire('Error', 'El cliente es requerido', 'error');
                reject(new Error('Cliente requerido'));
                return;
            }

            logWithEmoji('📤', 'Enviando pedido PRENDA sin cotización', datosPrenda);

            // 🔴 USAR FormData PARA ENVIAR ARCHIVOS
            const formData = new FormData();
            formData.append('cliente', cliente);
            formData.append('forma_de_pago', formaPago);
            formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');

            // Agregar prendas como JSON
            datosPrenda.prendas.forEach((prenda, index) => {
                formData.append(`prendas[${index}][nombre_producto]`, prenda.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, prenda.descripcion || '');
                formData.append(`prendas[${index}][genero]`, prenda.genero || '');

                // Cantidades por talla
                if (prenda.cantidadesPorTalla) {
                    Object.entries(prenda.cantidadesPorTalla).forEach(([talla, cantidad]) => {
                        if (cantidad > 0) {
                            formData.append(`prendas[${index}][cantidades][${talla}]`, cantidad);
                        }
                    });
                }

                // Tallas seleccionadas
                if (prenda.tallas && Array.isArray(prenda.tallas)) {
                    prenda.tallas.forEach(talla => {
                        formData.append(`prendas[${index}][tallas][]`, talla);
                    });
                }

                // Variantes (telas, etc)
                if (prenda.variantes) {
                    formData.append(`prendas[${index}][variantes]`, JSON.stringify(prenda.variantes));
                }
            });

            // 📸 AGREGAR IMÁGENES DE PRENDAS
            logWithEmoji('📸', 'Procesando imágenes de prendas...');
            Object.entries(datosPrenda.fotosNuevas || {}).forEach(([prendaIndex, fotos]) => {
                if (Array.isArray(fotos)) {
                    fotos.forEach((foto, fotoIndex) => {
                        if (foto instanceof File) {
                            formData.append(`prendas[${prendaIndex}][fotos][]`, foto);
                            logWithEmoji('✅', `Imagen de prenda ${prendaIndex + 1} agregada: ${foto.name}`);
                        } else if (typeof foto === 'string') {
                            // Si es una URL existente, guardarla igual
                            formData.append(`prendas[${prendaIndex}][fotos_existentes][]`, foto);
                        }
                    });
                }
            });

            // 📸 AGREGAR IMÁGENES DE TELAS
            logWithEmoji('📸', 'Procesando imágenes de telas...');
            Object.entries(datosPrenda.telasFotosNuevas || {}).forEach(([prendaIndex, telas]) => {
                Object.entries(telas).forEach(([telaIndex, fotos]) => {
                    if (Array.isArray(fotos)) {
                        fotos.forEach((foto, fotoIndex) => {
                            if (foto instanceof File) {
                                formData.append(`prendas[${prendaIndex}][telas][${telaIndex}][fotos][]`, foto);
                                logWithEmoji('✅', `Imagen de tela de prenda ${prendaIndex + 1} agregada: ${foto.name}`);
                            } else if (typeof foto === 'string') {
                                // Si es una URL existente
                                formData.append(`prendas[${prendaIndex}][telas][${telaIndex}][fotos_existentes][]`, foto);
                            }
                        });
                    }
                });
            });

            // Enviar al servidor
            const response = await fetch('/asesores/pedidos-produccion/crear-prenda-sin-cotizacion', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error al crear el pedido');
            }

            logWithEmoji('✅', 'Pedido PRENDA creado exitosamente', result);

            // Mostrar éxito
            Swal.fire({
                title: '✅ Pedido creado',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Número de Pedido:</strong> ${result.numero_pedido}</p>
                        <p><strong>Total de prendas:</strong> ${datosPrenda.prendas.length}</p>
                        <p><strong>Cantidad total:</strong> ${result.cantidad_total}</p>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Ver Pedido',
                cancelButtonText: 'Volver al inicio'
            }).then((res) => {
                if (res.isConfirmed) {
                    // Redirigir a ver el pedido
                    window.location.href = `/asesores/pedidos-produccion/${result.pedido_id}`;
                } else {
                    // Limpiar y volver
                    window.location.href = '/asesores/pedidos-produccion';
                }
            });

            resolve(result);

        } catch (error) {
            logWithEmoji('❌', 'Error al enviar pedido PRENDA', error.message);
            Swal.fire('Error', error.message, 'error');
            reject(error);
        }
    });
};

/**
 * Hook para ejecutarse DESPUÉS de envío exitoso
 * Limpia el módulo
 */
window.hookPostEnvioPrendaSinCotizacion = function(response) {
    const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
    const tipoNuevo = tipoPedido === 'nuevo';
    const tipoPrendaSelect = document.getElementById('tipo_pedido_nuevo')?.value;
    
    if (tipoNuevo && tipoPrendaSelect === 'P') {
        console.log('✅ Post-envío: Limpiando módulo PRENDA sin cotización');
        window.limpiarPrendasTipoPrendaSinCotizacion();
    }
};

logWithEmoji('✅', 'Integración de validación y envío para PRENDA sin cotización cargada');
