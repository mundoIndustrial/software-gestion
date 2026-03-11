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
        this._llenarInformacionBasica(datosPedido, recibo);

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
        const metrajesContainer = document.getElementById('metrajes-por-color-container');

        // Limpiar datos residuales del recibo anterior y ocultar por defecto
        if (metrajesContainer) metrajesContainer.innerHTML = '';
        if (contenedor) {
            contenedor.style.display = 'none';
        }
        
        // La lógica de mostrar/llenar datos la maneja _cargarYAgregarMetrajesPorColor
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
                
                // Nuevo formato: los recibos ahora son objetos con datos completos
                const datosRecibo = prendaData.recibos[tipoReciboKey];
                if (datosRecibo && typeof datosRecibo === 'object') {
                    consecutivo = datosRecibo.consecutivo_actual;
                } else {
                    // Formato antiguo (fallback por si acaso)
                    consecutivo = datosRecibo;
                }
                
                console.log(' [ReceiptRenderer] Buscando consecutivo:', {
                    tipoProceso,
                    tipoReciboKey,
                    consecutivo,
                    datosRecibo,
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
    static _llenarInformacionBasica(datosPedido, recibo = null) {
        // Fecha - Lógica para recibos de procesos
        const dayBox = document.querySelector('.day-box');
        const monthBox = document.querySelector('.month-box');
        const yearBox = document.querySelector('.year-box');

        if (dayBox && monthBox && yearBox) {
            // Para recibos de procesos (no costura), verificar si está activo
            if (recibo && recibo.tipo_recibo && recibo.tipo_recibo !== 'COSTURA') {
                console.log('[ReceiptRenderer] Verificando estado de recibo de proceso:', {
                    tipo_recibo: recibo.tipo_recibo,
                    activo: recibo.activo,
                    created_at: recibo.created_at
                });

                if (recibo.activo === 1 && recibo.created_at) {
                    // Recibo activo: usar fecha de creación del recibo
                    const fecha = Formatters.parsearFecha(recibo.created_at);
                    const { day, month, year } = Formatters.formatearFecha(fecha);
                    
                    dayBox.textContent = day;
                    monthBox.textContent = month;
                    yearBox.textContent = year;
                    
                    console.log('[ReceiptRenderer] Fecha de recibo activo establecida:', { day, month, year });
                } else {
                    // Recibo no activo: mostrar fecha vacía
                    dayBox.textContent = '--';
                    monthBox.textContent = '--';
                    yearBox.textContent = '----';
                    
                    console.log('[ReceiptRenderer] Recibo no activo - Fecha vacía');
                }
            } else {
                // Para costura o si no hay recibo: usar fecha del pedido (comportamiento original)
                const fecha = Formatters.parsearFecha(datosPedido.fecha);
                const { day, month, year } = Formatters.formatearFecha(fecha);
                
                dayBox.textContent = day;
                monthBox.textContent = month;
                yearBox.textContent = year;
                
                console.log('[ReceiptRenderer] Fecha de pedido (costura o default) establecida:', { day, month, year });
            }
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

        // Limpiar y ocultar contenedor de ancho/metraje inicialmente
        const contenedorInicial = document.getElementById('order-ancho-metraje');
        if (contenedorInicial) {
            contenedorInicial.style.display = 'none';
            const metrajesContainer = document.getElementById('metrajes-por-color-container');
            if (metrajesContainer) metrajesContainer.innerHTML = '';
        }

        // Fetch async para obtener metrajes
        // Intentar primero endpoint público, si falla intentar ruta de insumos
        const publicEndpoint = `/pedidos-public/${pedidoId}/ancho-metraje-prenda/${prendaData.prenda_pedido_id}`;
        const insumosEndpoint = `/insumos/materiales/${pedidoId}/obtener-ancho-metraje-prenda/${prendaData.prenda_pedido_id}`;
        
        fetch(publicEndpoint)
            .then(response => {
                console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Response status (public):', response.status);
                if (!response.ok && response.status === 404) {
                    // Si no existe endpoint público, intentar insumos
                    return fetch(insumosEndpoint).then(r => {
                        console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Intentando endpoint de insumos');
                        return r;
                    });
                }
                return response;
            })
            .then(response => response.json())
            .catch(error => {
                console.error('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Error fetching:', error);
                return null;
            })
            .then(data => {
                // Si data es null (error), retornar
                if (!data) {
                    console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Sin datos respuesta');
                    return;
                }

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

                const contenedor = document.getElementById('order-ancho-metraje');
                
                // Si no hay tipo_modo ni datos, ocultar contenedor
                // Considerar: data.data (array metrajes), ancho, metraje, y contenido_mano
                if (!data.tipo_modo || (Array.isArray(data.data) && data.data.length === 0 && !data.ancho && !data.metraje && !data.contenido_mano)) {
                    if (contenedor) {
                        contenedor.style.display = 'none';
                    }
                    console.log('[ReceiptRenderer] Sin datos de ancho/metraje para mostrar');
                    return;
                }

                const tipoModo = data.tipo_modo;
                const anchoSpan = document.getElementById('ancho-valor');
                const metrajeSpan = document.getElementById('metraje-valor');
                
                // Actualizar ancho en la barra inferior (aplica a todos los modos)
                if (anchoSpan && data.ancho) {
                    anchoSpan.textContent = data.ancho + ' m';
                }

                if (tipoModo === 'normal') {
                    // MODO NORMAL: Ancho + Metraje general en barra inferior
                    // Metraje viene directo de pedido_ancho_general (top-level en response)
                    if (contenedor) {
                        contenedor.style.display = 'block';
                        
                        // Asegurar que solo la vista normal está visible
                        const vistaNormal = document.getElementById('ancho-metraje-normal');
                        const vistaMano = document.getElementById('ancho-metraje-mano');
                        if (vistaNormal) {
                            vistaNormal.classList.remove('hidden-view');
                            vistaNormal.style.display = 'block';
                        }
                        if (vistaMano) vistaMano.style.display = 'none';
                    }
                    
                    if (metrajeSpan) {
                        const metrajeGeneral = data.metraje || null;
                        metrajeSpan.textContent = metrajeGeneral ? metrajeGeneral + ' m' : '--';
                        // Mostrar metraje en modo normal
                        metrajeSpan.closest('span').style.display = 'block';
                    }
                    
                    // Mostrar metrajes por color si existen
                    const metrajesValidos = (data.data || []).filter(item => item.color && item.metraje);
                    const contenedorMetrajes = document.getElementById('metrajes-por-color-container');
                    
                    if (contenedorMetrajes && metrajesValidos.length > 0) {
                        contenedorMetrajes.innerHTML = '';
                        metrajesValidos.forEach(item => {
                            const span = document.createElement('span');
                            span.textContent = `${item.color.toUpperCase()}: ${item.metraje} m`;
                            contenedorMetrajes.appendChild(span);
                        });
                    }
                    
                    console.log('[ReceiptRenderer] Modo NORMAL: Ancho + Metraje en barra inferior + Metrajes por color');
                    
                } else if (tipoModo === 'color') {
                    // MODO POR COLOR: Solo Ancho en barra inferior, metraje en contenedor por color
                    if (contenedor) {
                        contenedor.style.display = 'block';
                        
                        // Asegurar que solo la vista normal está visible
                        const vistaNormal = document.getElementById('ancho-metraje-normal');
                        const vistaMano = document.getElementById('ancho-metraje-mano');
                        if (vistaNormal) {
                            vistaNormal.classList.remove('hidden-view');
                            vistaNormal.style.display = 'block';
                        }
                        if (vistaMano) vistaMano.style.display = 'none';
                        
                        // Ocultar metraje de la barra inferior
                        if (metrajeSpan) {
                            metrajeSpan.closest('span').style.display = 'none';
                        }
                        
                        // Agregar lista de metrajes por color en el contenedor dedicado
                        const metrajesValidos = (data.data || []).filter(item => item.color && item.metraje);
                        const contenedorMetrajes = document.getElementById('metrajes-por-color-container');
                        
                        if (contenedorMetrajes) {
                            contenedorMetrajes.innerHTML = '';
                            
                            if (metrajesValidos.length > 0) {
                                metrajesValidos.forEach(item => {
                                    const span = document.createElement('span');
                                    span.textContent = `${item.color.toUpperCase()}: ${item.metraje} m`;
                                    contenedorMetrajes.appendChild(span);
                                });
                            }
                        }
                    }
                    
                    console.log('[ReceiptRenderer] Modo COLOR: Metrajes en contenedor por color');
                    
                } else if (tipoModo === 'pieza') {
                    // MODO POR PIEZA: Ancho en columna izquierda + metrajes por color en columna derecha
                    if (contenedor) {
                        contenedor.style.display = 'block';
                        
                        // Asegurar que solo la vista normal está visible
                        const vistaNormal = document.getElementById('ancho-metraje-normal');
                        const vistaMano = document.getElementById('ancho-metraje-mano');
                        if (vistaNormal) {
                            vistaNormal.classList.remove('hidden-view');
                            vistaNormal.style.display = 'block';
                        }
                        if (vistaMano) vistaMano.style.display = 'none';
                        
                        // Ocultar metraje general (está escondido en el HTML)
                        if (metrajeSpan) {
                            metrajeSpan.closest('span').style.display = 'none';
                        }
                        
                        // Agregar lista de metrajes por color en el contenedor dedicado
                        const metrajesValidos = (data.data || []).filter(item => item.color && item.metraje);
                        const contenedorMetrajes = document.getElementById('metrajes-por-color-container');
                        
                        if (contenedorMetrajes) {
                            contenedorMetrajes.innerHTML = '';
                            
                            if (metrajesValidos.length > 0) {
                                metrajesValidos.forEach(item => {
                                    const span = document.createElement('span');
                                    span.textContent = `${item.color.toUpperCase()}: ${item.metraje} m`;
                                    contenedorMetrajes.appendChild(span);
                                });
                            }
                        }
                    }
                    
                    // NO inyectar metrajes en la descripción para modo pieza
                    console.log('[ReceiptRenderer] Modo PIEZA: Metrajes en columna derecha');
                    
                } else if (tipoModo === 'mano') {
                    // MODO A MANO: Mostrar contenido de texto libre
                    if (contenedor) {
                        contenedor.style.display = 'block';
                        
                        // Ocultar la vista normal agregando la clase hidden-view
                        const vistaNormal = document.getElementById('ancho-metraje-normal');
                        if (vistaNormal) {
                            vistaNormal.classList.add('hidden-view');
                            vistaNormal.style.display = 'none';
                        }
                        
                        // Mostrar la vista a mano
                        const vistaMano = document.getElementById('ancho-metraje-mano');
                        if (vistaMano) {
                            vistaMano.style.display = 'block';
                            
                            // Llenar el contenido
                            const contenidoMano = document.getElementById('contenido-mano');
                            if (contenidoMano) {
                                contenidoMano.textContent = data.contenido_mano || '';
                            }
                            
                            // Ocultar observaciones (no se usa)
                            const observacionesContainer = document.getElementById('observaciones-mano');
                            if (observacionesContainer) {
                                observacionesContainer.style.display = 'none';
                            }
                        }
                    }
                    
                    console.log('[ReceiptRenderer] Modo A MANO: Contenido de texto libre');
                    
                } else {
                    // SIN TIPO_MODO: Si hay metrajes por color, mostrarlos en el contenedor
                    const metrajesValidos = (data.data || []).filter(item => item.color && item.metraje);
                    
                    if (metrajesValidos.length > 0 && contenedor) {
                        contenedor.style.display = 'block';
                        
                        const vistaNormal = document.getElementById('ancho-metraje-normal');
                        const vistaMano = document.getElementById('ancho-metraje-mano');
                        if (vistaNormal) {
                            vistaNormal.classList.remove('hidden-view');
                            vistaNormal.style.display = 'block';
                        }
                        if (vistaMano) vistaMano.style.display = 'none';
                        
                        // Ocultar metraje de la barra inferior
                        if (metrajeSpan) {
                            metrajeSpan.closest('span').style.display = 'none';
                        }
                        
                        // Mostrar metrajes por color en el contenedor
                        const contenedorMetrajes = document.getElementById('metrajes-por-color-container');
                        if (contenedorMetrajes) {
                            contenedorMetrajes.innerHTML = '';
                            metrajesValidos.forEach(item => {
                                const span = document.createElement('span');
                                span.textContent = `${item.color.toUpperCase()}: ${item.metraje} m`;
                                contenedorMetrajes.appendChild(span);
                            });
                        }
                        
                        console.log('[ReceiptRenderer] Sin tipo_modo pero con metrajes por color: mostrados en contenedor');
                    }
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
