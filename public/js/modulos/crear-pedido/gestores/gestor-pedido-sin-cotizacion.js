/**
 * GESTOR DE PEDIDO SIN COTIZACIÓN - Crear Pedido Editable (FASE 3b)
 * 
 * Gestiona el flujo completo de un pedido SIN cotización:
 * - Agregar prendas vacías
 * - Validar prendas y cantidades
 * - Recopilar datos del cliente
 * - Envío especial a endpoint sin cotización
 */

class GestorPedidoSinCotizacion {
    /**
     * Constructor
     */
    constructor() {
        this.prendas = [];
        this.cliente = '';
        this.asesora = '';
        this.formaPago = '';
        this.esActivo = false;
        
        this.inicializar();
    }

    /**
     * Inicializar gestor
     */
    inicializar() {
        logWithEmoji('🚀', 'GestorPedidoSinCotizacion inicializado');
    }

    /**
     * Activar modo sin cotización
     * Muestra UI correspondiente, oculta búsqueda de cotización
     */
    activar() {
        this.esActivo = true;
        
        // Ocultar sección de cotización
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        if (seccionCotizacion) {
            seccionCotizacion.style.display = 'none';
        }
        
        // Mostrar sección de información
        const seccionInfo = document.getElementById('seccion-info-prenda');
        if (seccionInfo) {
            seccionInfo.style.display = 'block';
        }
        
        // Mostrar sección de prendas
        const seccionPrendas = document.getElementById('seccion-prendas');
        if (seccionPrendas) {
            seccionPrendas.style.display = 'block';
        }
        
        // Establecer asesora del usuario actual
        document.getElementById('asesora_editable').value = window.asesorActualNombre || 
                                                             document.querySelector('meta[name="user-name"]')?.content || 
                                                             'N/A';
        
        // Ocultar número de cotización si existe
        const numeroCotizacionGroup = document.getElementById('numero_cotizacion_editable')?.closest('.form-group');
        if (numeroCotizacionGroup) {
            numeroCotizacionGroup.style.display = 'none';
        }
        
        // Mostrar botón submit
        const btnSubmit = document.getElementById('btn-submit');
        if (btnSubmit) {
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';
        }
        
        logWithEmoji('📋', 'Modo SIN COTIZACIÓN activado');
    }

    /**
     * Desactivar modo sin cotización
     */
    desactivar() {
        this.esActivo = false;
        this.prendas = [];
        
        // Mostrar sección de cotización
        const seccionCotizacion = document.getElementById('cotizacion_search_editable')?.closest('.form-section');
        if (seccionCotizacion) {
            seccionCotizacion.style.display = 'block';
        }
        
        // Mostrar número de cotización
        const numeroCotizacionGroup = document.getElementById('numero_cotizacion_editable')?.closest('.form-group');
        if (numeroCotizacionGroup) {
            numeroCotizacionGroup.style.display = 'block';
        }
        
        logWithEmoji('🔄', 'Modo SIN COTIZACIÓN desactivado');
    }

    /**
     * Agregar prenda vacía
     * @returns {number} Índice de la nueva prenda
     */
    agregarPrenda() {
        const index = this.prendas.length;
        
        const prenda = {
            index: index,
            nombre_producto: '',
            descripcion: '',
            genero: '',
            cantidadesPorTalla: {},
            fotos: []
        };
        
        this.prendas.push(prenda);
        logWithEmoji('➕', `Prenda ${index + 1} agregada (SIN COTIZACIÓN)`);
        
        return index;
    }

    /**
     * Eliminar prenda por índice
     * @param {number} index - Índice de la prenda
     */
    eliminarPrenda(index) {
        if (index >= 0 && index < this.prendas.length) {
            const eliminada = this.prendas.splice(index, 1)[0];
            logWithEmoji('🗑️', `Prenda "${eliminada.nombre_producto || 'Sin nombre'}" eliminada`);
        }
    }

    /**
     * Obtener todas las prendas
     * @returns {Array} Array de prendas
     */
    obtenerTodas() {
        return this.prendas;
    }

    /**
     * Obtener cantidad de prendas
     * @returns {number} Cantidad
     */
    cantidad() {
        return this.prendas.length;
    }

    /**
     * Establecer cliente
     * @param {string} cliente - Nombre del cliente
     */
    establecerCliente(cliente) {
        this.cliente = cliente;
    }

    /**
     * Obtener cliente
     * @returns {string} Nombre del cliente
     */
    obtenerCliente() {
        return this.cliente;
    }

    /**
     * Establecer forma de pago
     * @param {string} formaPago - Forma de pago
     */
    establecerFormaPago(formaPago) {
        this.formaPago = formaPago;
    }

    /**
     * Obtener forma de pago
     * @returns {string} Forma de pago
     */
    obtenerFormaPago() {
        return this.formaPago;
    }

    /**
     * Recopilar datos del DOM
     * Obtiene información de campos del formulario
     */
    recopilarDatosDelDOM() {
        this.cliente = document.getElementById('cliente_editable')?.value || '';
        this.asesora = document.getElementById('asesora_editable')?.value || '';
        this.formaPago = document.getElementById('forma_de_pago_editable')?.value || '';
        
        // Recopilar prendas del DOM
        const prendasContainer = document.getElementById('prendas-container-editable');
        const prendaCards = prendasContainer?.querySelectorAll('.prenda-card-editable') || [];
        
        const prendasDelDOM = [];
        
        prendaCards.forEach((card, index) => {
            const prenda = {
                index: index,
                nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
                descripcion: card.querySelector('.prenda-descripcion')?.value || '',
                genero: card.querySelector(`select[name="genero[${index}]"]`)?.value || '',
                cantidadesPorTalla: {}
            };
            
            // Recopilar cantidades
            card.querySelectorAll('.talla-cantidad').forEach(input => {
                const talla = input.getAttribute('data-talla');
                const cantidad = parseInt(input.value) || 0;
                if (talla && cantidad > 0) {
                    prenda.cantidadesPorTalla[talla] = cantidad;
                }
            });
            
            prendasDelDOM.push(prenda);
        });
        
        this.prendas = prendasDelDOM;
    }

    /**
     * Validar antes de envío
     * @returns {Object} {valido: boolean, errores: Array}
     */
    validar() {
        const errores = [];

        // Validar cliente
        if (!this.cliente || this.cliente.trim() === '') {
            errores.push('Cliente es requerido');
        }

        // Validar prendas
        if (this.prendas.length === 0) {
            errores.push('Debe haber al menos una prenda');
        }

        // Validar que al menos una prenda tenga cantidades
        let tieneCantidades = false;
        this.prendas.forEach((prenda, index) => {
            if (!prenda.nombre_producto || prenda.nombre_producto.trim() === '') {
                errores.push(`Prenda ${index + 1}: Nombre de producto requerido`);
            }

            if (Object.keys(prenda.cantidadesPorTalla).length === 0) {
                errores.push(`Prenda ${index + 1}: Debe tener al menos una cantidad de talla`);
            } else {
                tieneCantidades = true;
            }
        });

        if (!tieneCantidades) {
            errores.push('Al menos una prenda debe tener cantidades asignadas');
        }

        return {
            valido: errores.length === 0,
            errores: errores
        };
    }

    /**
     * Obtener datos formateados para envío
     * @returns {Object} Datos estructurados
     */
    obtenerDatosParaEnvio() {
        return {
            cliente: this.cliente,
            asesora: this.asesora,
            forma_de_pago: this.formaPago,
            prendas: this.prendas,
            es_sin_cotizacion: true
        };
    }

    /**
     * Enviar pedido al servidor
     * Endpoint especial para pedidos sin cotización
     * 
     * @returns {Promise}
     */
    async enviarAlServidor() {
        // Recopilar datos del DOM
        this.recopilarDatosDelDOM();

        // Validar
        const validacion = this.validar();
        if (!validacion.valido) {
            console.error('❌ Validación fallida:', validacion.errores);
            window.mostrarErroresValidacion(validacion.errores);
            return Promise.reject('Validación fallida');
        }

        const datos = this.obtenerDatosParaEnvio();
        
        return new Promise((resolve, reject) => {
            const csrfToken = document.querySelector('input[name="_token"]')?.value ||
                            document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                console.error('❌ Token CSRF no encontrado');
                mostrarError('Error', 'Token de seguridad no encontrado');
                reject(new Error('CSRF token missing'));
                return;
            }

            console.log('📤 [SIN COTIZACIÓN] Enviando datos:', datos);

            fetch('/asesores/pedidos-produccion/crear-sin-cotizacion', {
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
                console.log('✅ [SIN COTIZACIÓN] Respuesta del servidor:', data);

                if (data.success) {
                    mostrarExito(
                        '¡Éxito!',
                        `Pedido creado exitosamente${data.numero_pedido ? '\nNúmero: ' + data.numero_pedido : ''}`
                    );
                    logWithEmoji('✅', `Pedido SIN COTIZACIÓN creado: ${data.numero_pedido}`);
                    resolve(data);
                } else {
                    throw new Error(data.message || 'Error desconocido al crear pedido');
                }
            })
            .catch(error => {
                console.error('❌ [SIN COTIZACIÓN] Error:', error);
                mostrarError(
                    'Error al crear pedido',
                    error.message || 'Ocurrió un error inesperado'
                );
                reject(error);
            });
        });
    }

    /**
     * Limpiar todo
     */
    limpiar() {
        this.prendas = [];
        this.cliente = '';
        this.asesora = '';
        this.formaPago = '';
        this.esActivo = false;
        logWithEmoji('🗑️', 'GestorPedidoSinCotizacion limpiado');
    }
}

/**
 * INSTANCIA GLOBAL
 */
window.gestorPedidoSinCotizacion = null;

// Exportar para ES6 modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GestorPedidoSinCotizacion };
}
