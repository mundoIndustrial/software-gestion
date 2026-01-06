/**
 * VALIDACIÓN Y ENVÍO DE DATOS - FASE 3
 * 
 * Utiliza los gestores para validar y estructurar datos antes de envío
 * Se carga DESPUÉS de init-gestores-fase2.js pero ANTES de crear-pedido-editable.js
 * 
 * Proporciona funciones reutilizables para validar y obtener datos
 */

(function() {
    'use strict';

    /**
     * Validar formulario antes de enviar
     * Usa validación de gestores
     * 
     * @returns {Object} {valido: boolean, errores: Array}
     */
    window.validarFormularioConGestores = function() {
        const errores = [];

        // Validar cliente
        const cliente = document.getElementById('cliente_editable')?.value;
        if (!cliente || cliente.trim() === '') {
            errores.push('Cliente es requerido');
        }

        // Validar selección de cotización o modo sin cotización
        const cotizacionId = document.getElementById('cotizacion_id_editable')?.value;
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        const esSinCotizacion = seccionCotizacion && seccionCotizacion.style.display === 'none';

        if (!esSinCotizacion && !cotizacionId) {
            errores.push('Selecciona una cotización o crea un pedido sin cotización');
        }

        // Validar prendas
        const prendasContainer = document.getElementById('prendas-container-editable');
        if (!prendasContainer || prendasContainer.querySelectorAll('.prenda-card-editable').length === 0) {
            errores.push('Debe haber al menos una prenda');
        }

        // Validar usando gestores si están disponibles
        if (window.gestorPrendas) {
            const validacionPrendas = window.gestorPrendas.validar();
            if (!validacionPrendas.valido) {
                errores.push(...validacionPrendas.errores);
            }
        }

        if (window.currentEsLogo && window.gestorLogo) {
            const validacionLogo = window.gestorLogo.validar();
            if (!validacionLogo.valido) {
                errores.push(...validacionLogo.errores);
            }
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    };

    /**
     * Mostrar errores de validación al usuario
     * 
     * @param {Array} errores - Array de mensajes de error
     */
    window.mostrarErroresValidacion = function(errores) {
        if (!errores || errores.length === 0) return;

        const listaErrores = errores
            .map(err => `<li style="text-align: left; margin-bottom: 0.5rem;">• ${err}</li>`)
            .join('');

        mostrarError(
            '⚠️ Validación fallida',
            `<ul style="margin: 1rem 0; padding-left: 1.5rem;">${listaErrores}</ul>`,
            10000  // Duración más larga para leer errores
        );
    };

    /**
     * Preparar datos completos del pedido para envío
     * Estructura consistente con o sin cotización
     * 
     * @returns {Object} Datos formateados para servidor
     */
    window.prepararDatosParaEnvio = function() {
        const cotizacionId = document.getElementById('cotizacion_id_editable')?.value;
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        const esSinCotizacion = seccionCotizacion && seccionCotizacion.style.display === 'none';

        const datos = {
            // Datos básicos
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            numero_cotizacion: esSinCotizacion ? null : cotizacionId,
            es_sin_cotizacion: esSinCotizacion,
            
            // Datos de prendas
            prendas: [],
            fotos_nuevas: {},
            
            // Datos de logo (si aplica)
            logo: null,
            
            // Datos reflectivos (si aplica)
            reflectivo: null
        };

        // Recopilación de prendas del DOM
        const prendasContainer = document.getElementById('prendas-container-editable');
        const prendaCards = prendasContainer?.querySelectorAll('.prenda-card-editable') || [];

        prendaCards.forEach((card, index) => {
            // Saltar si fue eliminada
            if (window.prendasEliminadas && window.prendasEliminadas.has(index)) {
                return;
            }

            const prenda = {
                index: index,
                nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
                descripcion: card.querySelector('.prenda-descripcion')?.value || '',
                genero: card.querySelector(`select[name="genero[${index}]"]`)?.value || '',
                cantidades: {}
            };

            // Recopilar cantidades por talla
            card.querySelectorAll('.talla-cantidad').forEach(input => {
                const talla = input.getAttribute('data-talla');
                const cantidad = parseInt(input.value) || 0;
                if (talla && cantidad > 0) {
                    prenda.cantidades[talla] = cantidad;
                }
            });

            // Solo agregar si tiene cantidades
            if (Object.keys(prenda.cantidades).length > 0) {
                datos.prendas.push(prenda);
            }
        });

        // Agregar datos de logo si existen
        if (window.gestorLogo) {
            const datosLogo = window.gestorLogo.obtenerDatosFormato();
            if (datosLogo.tecnicas.length > 0 || datosLogo.ubicaciones.length > 0) {
                datos.logo = datosLogo;
            }
        }

        // Agregar datos de tipo de cotización
        if (!esSinCotizacion) {
            const tipoCotizacionElement = document.querySelector('[data-tipo-cotizacion]');
            datos.tipo_cotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
        }

        return datos;
    };

    /**
     * Enviar datos al servidor
     * Maneja el fetch y muestra resultados
     * 
     * @param {Object} datos - Datos a enviar
     * @param {string} endpoint - URL del servidor
     * @returns {Promise} Promesa del fetch
     */
    window.enviarDatosAlServidor = function(datos, endpoint = '/asesores/pedidos-produccion') {
        return new Promise((resolve, reject) => {
            const csrfToken = document.querySelector('input[name="_token"]')?.value ||
                            document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                console.error('❌ Token CSRF no encontrado');
                mostrarError('Error', 'Token de seguridad no encontrado');
                reject(new Error('CSRF token missing'));
                return;
            }

            console.log('📤 Enviando datos al servidor:', datos);

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(datos)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Respuesta del servidor:', data);

                if (data.success) {
                    mostrarExito(
                        '¡Éxito!',
                        `Pedido creado exitosamente${data.numero_pedido ? '\nNúmero: ' + data.numero_pedido : ''}`
                    );
                    resolve(data);
                } else {
                    throw new Error(data.message || 'Error desconocido al crear pedido');
                }
            })
            .catch(error => {
                console.error('❌ Error al enviar datos:', error);
                mostrarError(
                    'Error al crear pedido',
                    error.message || 'Ocurrió un error inesperado'
                );
                reject(error);
            });
        });
    };

    /**
     * Procesar submit del formulario de forma segura
     * Válida → Prepara → Envía
     * 
     * @param {string} endpoint - URL para enviar datos
     * @returns {Promise}
     */
    window.procesarSubmitFormulario = function(endpoint = '/asesores/pedidos-produccion/crear-pedido') {
        // 1. VALIDAR
        const validacion = window.validarFormularioConGestores();

        if (!validacion.valido) {
            console.warn('⚠️ Validación fallida:', validacion.errores);
            window.mostrarErroresValidacion(validacion.errores);
            return Promise.reject('Validación fallida');
        }

        // 2. PREPARAR DATOS
        const datos = window.prepararDatosParaEnvio();
        console.log('📦 Datos preparados para envío:', datos);

        // 3. ENVIAR
        return window.enviarDatosAlServidor(datos, endpoint)
            .then(response => {
                // Redirigir a lista de pedidos después de 2 segundos
                setTimeout(() => {
                    window.location.href = '/asesores/pedidos';
                }, 2000);
                return response;
            });
    };

    /**
     * Obtener resumen de pedido para vista previa
     * 
     * @returns {Object} Resumen con prendas, logo, totales
     */
    window.obtenerResumenPedido = function() {
        const datos = window.prepararDatosParaEnvio();
        
        return {
            cliente: datos.cliente,
            asesora: datos.asesora,
            numero_cotizacion: datos.numero_cotizacion,
            cantidad_prendas: datos.prendas.length,
            cantidad_total_prendas: datos.prendas.reduce((sum, p) => {
                return sum + Object.values(p.cantidades).reduce((a, b) => a + b, 0);
            }, 0),
            tiene_logo: datos.logo !== null,
            tiene_fotos: Object.keys(datos.fotos_nuevas).length > 0,
            datos_completos: datos
        };
    };

    // Log de disponibilidad
    logWithEmoji('✅', 'Funciones de validación y envío FASE 3 cargadas');

})();
