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
        this._llenarDescripcion(prendaData, recibo, tipoProceso, datosPedido);

        // Actualizar ancho y metraje para esta prenda
        this._actualizarAnchoMetraje(prendaData, tipoProceso);

        // Guardar datos en estado
        modalManager.setState({
            prendaPedidoId: prendaData.prenda_pedido_id || prendaData.id,
            imagenesActuales: recibo.imagenes && Array.isArray(recibo.imagenes) ? recibo.imagenes : [],
            prendaData: prendaData,
            procesoPrendaDetalleId: recibo.id || recibo.proceso_prenda_detalle_id || null
        });


    }

    /**
     * Actualiza los valores de ancho y metraje para la prenda actual
     * Comportamiento según tipo_modo:
     * - normal: Muestra Ancho + Metraje en barra inferior
     * - color: Muestra solo Ancho en barra inferior (metraje va en descripción por color)
     * - pieza: Muestra Ancho + metraje por color en barra inferior (NO en descripción)
     */
    static _actualizarAnchoMetraje(prendaData, tipoProceso = '') {
        const contenedor = document.getElementById('order-ancho-metraje');
        const anchoSpan = document.getElementById('ancho-valor');
        const metrajeSpan = document.getElementById('metraje-valor');

        // Solo mostrar ancho/metraje para costura y costura-bodega
        const tipoBajo = String(tipoProceso || '').toLowerCase();
        const esCostura = tipoBajo === 'costura' || tipoBajo === 'costura-bodega';

        if (contenedor) {
            contenedor.style.display = esCostura ? '' : 'none';
        }

        if (anchoSpan && metrajeSpan && esCostura) {
            if (prendaData.ancho_metraje && (prendaData.ancho_metraje.ancho || prendaData.ancho_metraje.metraje)) {
                anchoSpan.textContent = prendaData.ancho_metraje.ancho + ' m';
                metrajeSpan.textContent = prendaData.ancho_metraje.metraje + ' m';
                console.log(' [ReceiptRenderer] Ancho/Metraje actualizado:', {
                    prenda: prendaData.nombre,
                    ancho: prendaData.ancho_metraje.ancho,
                    metraje: prendaData.ancho_metraje.metraje
                });
            } else {
                anchoSpan.textContent = '--';
                metrajeSpan.textContent = '--';
                console.log(' [ReceiptRenderer] Sin datos de ancho/metraje para prenda:', prendaData.nombre);
            }
        }
    }

    /**
     * Actualiza el título del modal
     */
    static _actualizarTitulo(tipoProceso, recibo, prendaData) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();
            const esParcial = !!(recibo && recibo._esParcial);

            // Caso especial: anexos de costura en prenda de bodega se renderizan sobre "costura-bodega"
            // pero el título debe mostrarse como COSTURA.
            const nombreRecibo = String(
                (esParcial && tipoProcesoLower === 'costura-bodega') ? 'costura' : (tipoProceso || recibo.tipo_proceso || recibo.nombre_proceso || 'Recibo')
            ).toUpperCase();
            
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
    static _llenarDescripcion(prendaData, recibo, tipoProceso, datosPedido) {
        const descripcionText = document.getElementById('descripcion-text');
        
        console.log('[ReceiptRenderer._llenarDescripcion] prendaData completo:', prendaData);
        console.log('[ReceiptRenderer._llenarDescripcion] prendaData.talla_colores:', prendaData.talla_colores);
        console.log('[ReceiptRenderer._llenarDescripcion] prendaData.tallas:', prendaData.tallas);
        console.log('[ReceiptRenderer._llenarDescripcion] typeof prendaData.tallas:', typeof prendaData.tallas);
        console.log('[ReceiptRenderer._llenarDescripcion] Array.isArray(prendaData.tallas):', Array.isArray(prendaData.tallas));

        // DEBUG: Verificar tallas del recibo
        console.log(' [ReceiptRenderer._llenarDescripcion] RECIBO COMPLETO:', {
            tipo_proceso: recibo.tipo_proceso,
            tallas: recibo.tallas,
            tallas_keys: recibo.tallas ? Object.keys(recibo.tallas) : 'SIN TALLAS',
            tallas_caballero: recibo.tallas ? recibo.tallas.caballero : 'NO ENCONTRADO'
        });

        let html = '';
        const tipoProcesoBajo = String(tipoProceso || '').toLowerCase();

        // Enriquecer recibo con colores por talla si disponibles
        if (prendaData.talla_colores && Array.isArray(prendaData.talla_colores) && prendaData.talla_colores.length > 0) {
            console.log('[ReceiptRenderer._llenarDescripcion] 🎨 Enriqueciendo recibo con colores por talla');
            recibo.talla_colores = prendaData.talla_colores;
        }

        // Determinar si es costura
        if (tipoProcesoBajo === 'costura' || tipoProcesoBajo === 'costura-bodega') {
            // Para parciales/anexos: usar el formateador de proceso para que tome las tallas inyectadas en el recibo
            if (recibo && recibo._esParcial) {
                html = Formatters.construirDescripcionProceso(prendaData, recibo);
                console.log(' [ReceiptRenderer._llenarDescripcion] HTML de costura-parcial (proceso) generado:', html);
            } else {
                // Recibo base de costura: usar formateador de costura (toma datos de la prenda)
                html = Formatters.construirDescripcionCostura(prendaData);
                console.log(' [ReceiptRenderer._llenarDescripcion] HTML de costura generado:', html);
            }
        } else {
            // Para otros procesos - pasar prendaData para acceder a colores
            html = Formatters.construirDescripcionProceso(prendaData, recibo);
            console.log(' [ReceiptRenderer._llenarDescripcion] HTML de proceso generado:', html);
        }

        descripcionText.innerHTML = html;
        console.log(' [ReceiptRenderer._llenarDescripcion] Descripción actualizada en el DOM');
        
        // Cargar metrajes por color desde la API
        if (prendaData.prenda_pedido_id && datosPedido) {
            this._cargarYAgregarMetrajesPorColor(prendaData, datosPedido);
        }
    }

    /**
     * Cargar metrajes por color desde la API y renderizar según tipo_modo
     * - normal: Muestra Ancho + Metraje en barra inferior, NO en descripción
     * - color: Muestra solo Ancho en barra inferior, metraje en descripción por color
     * - pieza: Muestra Ancho en barra inferior + lista metraje por color abajo, NO en descripción
     */
    static _cargarYAgregarMetrajesPorColor(prendaData, datosPedido) {
        // Obtener ID de pedido desde prendaData (más confiable) o datosPedido
        let pedidoId = prendaData?.pedido_produccion_id || datosPedido?.pedido_id || datosPedido?.id;
        
        if (!pedidoId || !prendaData.prenda_pedido_id) {
            console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Sin ID de pedido o ID de prenda:', {
                pedidoId,
                pedidoProduccionId: prendaData?.pedido_produccion_id,
                prendaId: prendaData.prenda_pedido_id
            });
            return;
        }

        console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Iniciando carga de metrajes:', {
            pedidoId,
            prendaId: prendaData.prenda_pedido_id,
            prendaNombre: prendaData.nombre_prenda
        });

        // Fetch async para obtener metrajes
        fetch(`/insumos/materiales/${pedidoId}/obtener-ancho-metraje-prenda/${prendaData.prenda_pedido_id}`)
            .then(response => {
                console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Response recibido:', {
                    success: data.success,
                    tipo_modo: data.tipo_modo,
                    dataLength: data.data?.length,
                    dataContent: data.data,
                    ancho: data.ancho
                });

                if (!data.success) {
                    console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Success es false');
                    return;
                }

                const tipoModo = data.tipo_modo || 'normal';
                const contenedor = document.getElementById('order-ancho-metraje');
                const anchoSpan = document.getElementById('ancho-valor');
                const metrajeSpan = document.getElementById('metraje-valor');
                
                // Actualizar ancho en la barra inferior (aplica a todos los modos)
                if (anchoSpan && data.ancho) {
                    anchoSpan.textContent = data.ancho + ' m';
                }

                if (tipoModo === 'normal') {
                    // MODO NORMAL: Ancho + Metraje general en barra inferior
                    // Metraje viene directo de pedido_ancho_general (top-level en response)
                    if (contenedor) contenedor.style.display = '';
                    
                    if (metrajeSpan) {
                        const metrajeGeneral = data.metraje || null;
                        metrajeSpan.textContent = metrajeGeneral ? metrajeGeneral + ' m' : '--';
                    }
                    
                    console.log('[ReceiptRenderer] Modo NORMAL: Ancho + Metraje en barra inferior');
                    
                } else if (tipoModo === 'color') {
                    // MODO POR COLOR: Solo Ancho en barra inferior, metraje en descripción por color
                    if (contenedor) {
                        contenedor.style.display = '';
                        // Ocultar metraje de la barra inferior
                        if (metrajeSpan) {
                            metrajeSpan.closest('span').style.display = 'none';
                        }
                    }
                    
                    // Inyectar metrajes en la descripción (junto a cada color)
                    this._inyectarMetrajesEnDescripcion(data.data);
                    
                    console.log('[ReceiptRenderer] Modo COLOR: Metraje inyectado en descripción por color');
                    
                } else if (tipoModo === 'pieza') {
                    // MODO POR PIEZA: Ancho en barra inferior + lista de metrajes por color abajo
                    if (contenedor) {
                        contenedor.style.display = '';
                        // Ocultar metraje general
                        if (metrajeSpan) {
                            metrajeSpan.closest('span').style.display = 'none';
                        }
                        
                        // Agregar lista de metrajes por color debajo del ancho
                        const metrajesValidos = (data.data || []).filter(item => item.color && item.metraje);
                        if (metrajesValidos.length > 0) {
                            // Eliminar lista anterior si existe
                            const existente = contenedor.querySelector('.metrajes-pieza-list');
                            if (existente) existente.remove();
                            
                            const listaDiv = document.createElement('div');
                            listaDiv.className = 'metrajes-pieza-list';
                            listaDiv.style.cssText = 'margin-top: 8px; text-align: left; font-size: 0.85rem;';
                            
                            let listaHTML = '<strong style="display: block; margin-bottom: 4px;">Metraje por Color:</strong>';
                            metrajesValidos.forEach(item => {
                                listaHTML += `<span style="display: block; color: red; font-weight: bold;">${item.color.toUpperCase()}: Metraje: ${item.metraje} m</span>`;
                            });
                            
                            listaDiv.innerHTML = listaHTML;
                            contenedor.appendChild(listaDiv);
                        }
                    }
                    
                    // NO inyectar metrajes en la descripción para modo pieza
                    console.log('[ReceiptRenderer] Modo PIEZA: Metrajes listados en barra inferior');
                }
            })
            .catch(error => {
                console.warn('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Error al cargar metrajes:', error);
            });
    }

    /**
     * Inyecta metrajes junto a cada color en la descripción (usado en modo "color")
     */
    static _inyectarMetrajesEnDescripcion(dataArray) {
        if (!Array.isArray(dataArray) || dataArray.length === 0) return;

        // Agrupar metrajes por color
        const metrajesPorColor = {};
        dataArray.forEach(item => {
            if (item.color && item.metraje) {
                metrajesPorColor[item.color] = item.metraje;
            }
        });

        if (Object.keys(metrajesPorColor).length === 0) return;

        const descripcionEl = document.getElementById('descripcion-text');
        if (!descripcionEl) return;

        let html = descripcionEl.innerHTML;

        // Para cada color, buscar en el HTML y agregar el metraje
        Object.entries(metrajesPorColor).forEach(([color, metraje]) => {
            if (!metraje) return;
            
            const colorUpperCase = color.toUpperCase();
            
            const regex = new RegExp(
                `(<strong>${colorUpperCase}:</strong>\\s*[^<]*?\\d[^<]*)(<br|<\\/span>|<\\/div>|$)`,
                'gi'
            );
            
            html = html.replace(regex, (match, contenido, cierre) => {
                if (!contenido.includes('Metraje:')) {
                    return `${contenido.trim()} - Metraje: ${metraje} m${cierre}`;
                }
                return match;
            });
        });

        descripcionEl.innerHTML = html;
    }
}
