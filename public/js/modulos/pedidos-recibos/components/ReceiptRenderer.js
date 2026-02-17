/**
 * ReceiptRenderer.js
 * Renderiza el contenido del recibo en el modal
 */

import { Formatters } from '../utils/Formatters.js';

export class ReceiptRenderer {
    /**
     * Renderiza un recibo completo en el modal
     */
    static renderizar(modalManager, prendaData, reciboIndice, tipoProceso, datosPedido, recibos) {
        // Obtener el recibo
        const recibo = recibos && recibos[reciboIndice] ? recibos[reciboIndice] : null;
        if (!recibo) {
            return;
        }

        // Actualizar título
        this._actualizarTitulo(tipoProceso, recibo, prendaData);

        // Llenar información básica
        this._llenarInformacionBasica(datosPedido);

        // Llenar descripción de la prenda
        this._llenarDescripcion(prendaData, recibo, tipoProceso);

        // Guardar datos en estado
        modalManager.setState({
            prendaPedidoId: prendaData.prenda_pedido_id || prendaData.id,
            imagenesActuales: recibo.imagenes && Array.isArray(recibo.imagenes) ? recibo.imagenes : [],
            prendaData: prendaData,
            procesoPrendaDetalleId: recibo.id || recibo.proceso_prenda_detalle_id || null
        });


    }

    /**
     * Actualiza el título del modal
     */
    static _actualizarTitulo(tipoProceso, recibo, prendaData) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const nombreRecibo = String(tipoProceso || recibo.tipo_proceso || recibo.nombre_proceso || 'Recibo').toUpperCase();
            
            // Debug: Verificar qué datos llegan
            console.log(' [ReceiptRenderer] Datos recibidos:', {
                tipoProceso,
                prendaData: prendaData,
                recibos: prendaData?.recibos,
                prendaId: prendaData?.id || prendaData?.prenda_pedido_id
            });
            
            // Actualizar solo el título (sin consecutivo)
            titleElement.textContent = 'RECIBO DE ' + nombreRecibo;
            
            // Obtener el consecutivo para este tipo de recibo
            let consecutivo = '';
            let tipoReciboKey = '';

            // Caso general: si el proceso/recibo ya trae numero_recibo, usarlo como fuente de verdad
            if (recibo && (recibo.numero_recibo || recibo.numeroRecibo)) {
                consecutivo = recibo.numero_recibo || recibo.numeroRecibo;
            }
            
            // Definir mapa de tipos de recibo
            const tipoReciboMap = {
                'costura': 'COSTURA',
                'costura-bodega': 'COSTURA-BODEGA',  //  Corregido: usar COSTURA-BODEGA
                'bordado': 'BORDADO',
                'estampado': 'ESTAMPADO',
                'dtf': 'DTF',
                'reflectivo': 'REFLECTIVO',
                'sublimado': 'SUBLIMADO',
                'bordado-punto': 'BORDADO',
                'bordado-plano': 'BORDADO'
            };
            
            if (!consecutivo && prendaData && prendaData.recibos && Object.keys(prendaData.recibos).length > 0) {
                tipoReciboKey = tipoReciboMap[tipoProceso.toLowerCase()] || tipoProceso.toUpperCase();
                consecutivo = prendaData.recibos[tipoReciboKey];
                
                console.log(' [ReceiptRenderer] Buscando consecutivo:', {
                    tipoProceso,
                    tipoReciboKey,
                    consecutivo,
                    recibos: prendaData.recibos
                });
            } else if (!consecutivo) {
                tipoReciboKey = tipoReciboMap[tipoProceso.toLowerCase()] || tipoProceso.toUpperCase();
                console.log(' [ReceiptRenderer] No hay datos de recibos o está vacío:', {
                    tieneRecibos: !!(prendaData && prendaData.recibos),
                    recibosKeys: prendaData?.recibos ? Object.keys(prendaData.recibos) : [],
                    recibosLength: prendaData?.recibos ? Object.keys(prendaData.recibos).length : 0
                });
            }
            
            // Si no hay consecutivo, dejar vacío (no usar consecutivos de prueba)
            if (!consecutivo) {
                console.log(' [ReceiptRenderer] No hay consecutivo, dejando vacío:', {
                    tipoProceso,
                    tipoReciboKey
                });
                consecutivo = '';
            }
            
            // Actualizar el número de pedido/consecutivo (elemento order-pedido)
            const pedidoNumberElement = document.querySelector('#order-pedido');
            console.log(' [ReceiptRenderer] Buscando elemento #order-pedido:', {
                encontrado: !!pedidoNumberElement,
                elemento: pedidoNumberElement,
                todosLosPedidoNumber: document.querySelectorAll('.pedido-number'),
                todosLosOrderPedido: document.querySelectorAll('#order-pedido')
            });
            
            if (pedidoNumberElement) {
                if (consecutivo) {
                    pedidoNumberElement.textContent = '#' + consecutivo;
                    console.log(' [ReceiptRenderer] Número de pedido actualizado con consecutivo:', '#' + consecutivo);
                } else {
                    const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
                    if (esVistaVisualizadorLogo) {
                        // En visualizador-logo el número esperado es el de recibo, no el del pedido
                        pedidoNumberElement.textContent = '';
                        console.log(' [ReceiptRenderer] Visualizador-logo: sin consecutivo, no se muestra fallback de pedido');
                    } else {
                        // Mantener el número de pedido original si no hay consecutivo
                        pedidoNumberElement.textContent = '#' + (prendaData?.numero_pedido || prendaData?.numero || '-');
                        console.log(' [ReceiptRenderer] Número de pedido mantenido sin consecutivo');
                    }
                }
            } else {
                console.warn(' [ReceiptRenderer] Elemento #order-pedido no encontrado');
                
                // Intentar con .pedido-number como fallback
                const fallbackElement = document.querySelector('.pedido-number');
                if (fallbackElement) {
                    console.log(' [ReceiptRenderer] Usando fallback .pedido-number');
                    if (consecutivo) {
                        fallbackElement.textContent = '#' + consecutivo;
                        console.log(' [ReceiptRenderer] Número actualizado con fallback:', '#' + consecutivo);
                    } else {
                        const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
                        if (esVistaVisualizadorLogo) {
                            fallbackElement.textContent = '';
                            console.log(' [ReceiptRenderer] Visualizador-logo: sin consecutivo, no se muestra fallback de pedido (fallbackElement)');
                        }
                    }
                } else {
                    console.error(' [ReceiptRenderer] Ni #order-pedido ni .pedido-number encontrados');
                }
            }
            
            console.log(' [ReceiptRenderer] Título actualizado:', 'RECIBO DE ' + nombreRecibo);
        }
    }

    /**
     * Llena la información básica del pedido
     */
    static _llenarInformacionBasica(datosPedido) {
        // Fecha
        const dayBox = document.querySelector('.day-box');
        const monthBox = document.querySelector('.month-box');
        const yearBox = document.querySelector('.year-box');

        if (dayBox && monthBox && yearBox) {
            const fecha = Formatters.parsearFecha(datosPedido.fecha);
            const { day, month, year } = Formatters.formatearFecha(fecha);
            
            dayBox.textContent = day;
            monthBox.textContent = month;
            yearBox.textContent = year;
        }

        // Cliente
        const clienteValue = document.getElementById('cliente-value');
        if (clienteValue) {
            const valor = datosPedido.cliente || '-';
            clienteValue.textContent = valor;
            console.log(' [ReceiptRenderer] Cliente actualizado:', valor);
        } else {
            console.warn(' [ReceiptRenderer] Elemento #cliente-value NO encontrado');
        }

        // Asesor
        const asesorValue = document.getElementById('asesora-value');
        if (asesorValue) {
            const valor = datosPedido.asesor || datosPedido.asesora || '-';
            asesorValue.textContent = valor;
            console.log(' [ReceiptRenderer] Asesor actualizado:', valor);
        } else {
            console.warn(' [ReceiptRenderer] Elemento #asesora-value NO encontrado');
        }

        // Forma de pago
        const formaPagoValue = document.getElementById('forma-pago-value');
        if (formaPagoValue) {
            const valor = datosPedido.forma_de_pago || '-';
            formaPagoValue.textContent = valor;
            console.log(' [ReceiptRenderer] Forma de pago actualizada:', valor);
        } else {
            console.warn(' [ReceiptRenderer] Elemento #forma-pago-value NO encontrado');
        }

        // Número de pedido
        const pedidoNumber = document.querySelector('.pedido-number');
        if (pedidoNumber) {
            // Verificar si ya tiene un consecutivo (no sobreescribir)
            const contenidoActual = pedidoNumber.textContent.trim();
            const yaTieneConsecutivo = contenidoActual.match(/^#\d+$/);
            
            if (!yaTieneConsecutivo) {
                // En supervisor-pedidos, mostrar vacío hasta que se apruebe
                let numero = '';
                if (!window.location.href.includes('supervisor-pedidos')) {
                    numero = datosPedido.numero_pedido || datosPedido.numero || '';
                }
                pedidoNumber.textContent = '#' + numero;
                console.log(' [ReceiptRenderer] Número de pedido actualizado (sin consecutivo):', '#' + numero);
            } else {
                console.log(' [ReceiptRenderer] Número de pedido mantenido (ya tiene consecutivo):', contenidoActual);
            }
        }

        // Encargado
        const encargadoValue = document.getElementById('encargado-value');
        if (encargadoValue) encargadoValue.textContent = '-';

        // Prendas entregadas
        const prendasValue = document.getElementById('prendas-entregadas-value');
        if (prendasValue) prendasValue.textContent = '0/0';
    }

    /**
     * Llena la descripción de la prenda
     */
    static _llenarDescripcion(prendaData, recibo, tipoProceso) {
        const descripcionText = document.getElementById('descripcion-text');
        if (!descripcionText) {
            console.warn(' [ReceiptRenderer] Elemento #descripcion-text NO encontrado');
            return;
        }

        console.log(' [ReceiptRenderer._llenarDescripcion] prendaData completo:', {
            nombre: prendaData.nombre,
            numero: prendaData.numero,
            tela: prendaData.tela,
            color: prendaData.color,
            ref: prendaData.ref,
            variantes: prendaData.variantes,
            descripcion: prendaData.descripcion,
            tallas: prendaData.tallas,
            genero: prendaData.genero
        });

        // DEBUG: Verificar tallas del recibo
        console.log(' [ReceiptRenderer._llenarDescripcion] RECIBO COMPLETO:', {
            tipo_proceso: recibo.tipo_proceso,
            tallas: recibo.tallas,
            tallas_keys: recibo.tallas ? Object.keys(recibo.tallas) : 'SIN TALLAS',
            tallas_caballero: recibo.tallas ? recibo.tallas.caballero : 'NO ENCONTRADO'
        });

        let html = '';
        const tipoProcesoBajo = String(tipoProceso || '').toLowerCase();

        // Determinar si es costura
        if (tipoProcesoBajo === 'costura' || tipoProcesoBajo === 'costura-bodega') {
            // Usar formateador directamente
            html = Formatters.construirDescripcionCostura(prendaData);
            console.log(' [ReceiptRenderer._llenarDescripcion] HTML de costura generado:', html);
        } else {
            // Para otros procesos
            html = Formatters.construirDescripcionProceso(prendaData, recibo);
            console.log(' [ReceiptRenderer._llenarDescripcion] HTML de proceso generado:', html);
        }

        descripcionText.innerHTML = html;
        console.log(' [ReceiptRenderer._llenarDescripcion] Descripción actualizada en el DOM');
    }
}

