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
        this._actualizarTitulo(tipoProceso, recibo, prendaData, recibos);

        // Llenar información básica
        this._llenarInformacionBasica(datosPedido, recibo);

        // Llenar descripción de la prenda
        this._llenarDescripcion(prendaData, recibo, tipoProceso, datosPedido);

        // Actualizar ancho y metraje para esta prenda
        this._actualizarAnchoMetraje(prendaData, tipoProceso, datosPedido, recibo);

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
    static _actualizarAnchoMetraje(prendaData, tipoProceso = '', datosPedido = null, recibo = null) {
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
    static _actualizarTitulo(tipoProceso, recibo, prendaData, recibos = []) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();

            // Caso especial: anexos de costura en prenda de bodega se renderizan sobre "costura-bodega"
            // pero el título debe mostrarse como COSTURA.
            const nombreRecibo = String(
                ((tipoProcesoLower === 'costura-bodega') || ((tipoProcesoLower === 'costura-bodega') && (esParcial || esVistaInsumosMateriales)))
                    ? 'costura'
                    : (tipoProceso || recibo.tipo_proceso || recibo.nombre_proceso || 'Recibo')
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

            // Caso general: usar consecutivo directo del recibo (incluye base y parciales)
            if (recibo) {
                consecutivo =
                    recibo.numero_recibo ||
                    recibo.numeroRecibo ||
                    recibo.consecutivo_actual ||
                    recibo.consecutivo_parcial ||
                    '';
            }
            
            // Definir mapa de tipos de recibo
            const tipoReciboMap = {
                'costura': 'COSTURA',
                'costura-bodega': 'COSTURA',
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

            // Fallback adicional: buscar en prendaData.consecutivos (array u objeto)
            if (!consecutivo && prendaData?.consecutivos) {
                const tipoReciboBuscado = tipoReciboMap[tipoProceso.toLowerCase()] || tipoProceso.toUpperCase();
                const consecutivosArray = Array.isArray(prendaData.consecutivos)
                    ? prendaData.consecutivos
                    : Object.values(prendaData.consecutivos || {});

                const encontrado = consecutivosArray.find((c) =>
                    String(c?.tipo_recibo || '').toUpperCase() === String(tipoReciboBuscado).toUpperCase()
                );

                if (encontrado) {
                    consecutivo = encontrado.consecutivo_actual || encontrado.consecutivo_parcial || '';
                }
            }
            
            // Fallback adicional para Costura:
            // cuando el recibo actual es un anexo no activo puede no traer consecutivo;
            // en ese caso mostrar el consecutivo del recibo base activo/aprobado.
            if (!consecutivo && Array.isArray(recibos) && recibos.length > 0) {
                const tipoActual = String(tipoProceso || '').toLowerCase();
                const esFlujoCostura = tipoActual === 'costura' || tipoActual === 'costura-bodega';

                if (esFlujoCostura) {
                    const baseCostura = recibos.find((r) => {
                        const tipoR = String(r?.tipo || r?.tipo_proceso || r?.tipo_recibo || '').toLowerCase();
                        const esBaseCostura = tipoR === 'costura' || tipoR === 'costura-bodega';
                        const estaAprobadoOActivo = Number(r?.activo) === 1 || String(r?.estado || '').toUpperCase() === 'APROBADO';
                        return esBaseCostura && estaAprobadoOActivo;
                    });

                    if (baseCostura) {
                        consecutivo =
                            baseCostura.numero_recibo ||
                            baseCostura.numeroRecibo ||
                            baseCostura.consecutivo_actual ||
                            baseCostura.consecutivo_parcial ||
                            '';
                    }
                }
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
        // IMPORTANTE: Buscar dentro del modal de costura, no en todo el documento
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        const dayBox = modalWrapper ? modalWrapper.querySelector('.day-box') : document.querySelector('.day-box');
        const monthBox = modalWrapper ? modalWrapper.querySelector('.month-box') : document.querySelector('.month-box');
        const yearBox = modalWrapper ? modalWrapper.querySelector('.year-box') : document.querySelector('.year-box');

        if (dayBox && monthBox && yearBox) {
            // Determinar si aplicar lógica de estado para recibos de costura
            const esReciboCostura = recibo && (
                recibo.tipo_recibo === 'COSTURA' || 
                recibo.tipo_recibo === 'COSTURA-BODEGA'
            );
            
            // Para recibos parciales (anexos) de costura, aplicar lógica de estado
            // Para recibos base de costura, usar fecha del pedido
            const esReciboParcial = recibo && (recibo.es_parcial || recibo.origen === 'PARCIAL' || recibo._esParcial);
            const aplicarLogicaEstado = (recibo && recibo.tipo_recibo && !esReciboCostura) || (esReciboCostura && esReciboParcial);
            
            console.log('[ReceiptRenderer._llenarInformacionBasica] Detectando tipo de recibo:', {
                tieneRecibo: !!recibo,
                esReciboCostura,
                esReciboParcial,
                aplicarLogicaEstado,
                recibo: recibo ? {
                    tipo_recibo: recibo.tipo_recibo,
                    es_parcial: recibo.es_parcial,
                    origen: recibo.origen,
                    _esParcial: recibo._esParcial,
                    fecha_activacion: recibo.fecha_activacion,
                    created_at: recibo.created_at,
                    tipo_proceso: recibo.tipo_proceso,
                    nombre_proceso: recibo.nombre_proceso
                } : null,
                logicaDetallada: {
                    'recibo && recibo.tipo_recibo': !!(recibo && recibo.tipo_recibo),
                    '!esReciboCostura': !esReciboCostura,
                    'esReciboCostura && esReciboParcial': esReciboCostura && esReciboParcial,
                    'rama1': (recibo && recibo.tipo_recibo && !esReciboCostura),
                    'rama2': (esReciboCostura && esReciboParcial)
                }
            });
            
            if (aplicarLogicaEstado) {
                console.log('[ReceiptRenderer] Verificando estado de recibo:', {
                    tipo_recibo: recibo.tipo_recibo,
                    es_parcial: esReciboParcial,
                    origen: recibo.origen,
                    activo: recibo.activo,
                    created_at: recibo.created_at,
                    tieneDatosRecibo: !!(recibo.activo !== undefined || recibo.created_at !== undefined),
                    reciboCompleto: recibo
                });

                // Para parciales: usar fecha_activacion si existe
                const tieneFechaActivacion = recibo.fecha_activacion && String(recibo.fecha_activacion).trim() !== '';
                
                console.log('[ReceiptRenderer._llenarInformacionBasica] Verificando fecha_activacion:', {
                    tieneFechaActivacion,
                    fecha_activacion: recibo.fecha_activacion,
                    esReciboParcial,
                    activo: recibo.activo,
                    created_at: recibo.created_at
                });
                
                if (tieneFechaActivacion) {
                    // Parcial con fecha_activacion: mostrar esa fecha
                    const fecha = Formatters.parsearFecha(recibo.fecha_activacion);
                    const { day, month, year } = Formatters.formatearFecha(fecha);
                    
                    // DEBUG: Verificar que estamos llenando los elementos correctos
                    console.log('[ReceiptRenderer] ANTES de establecer fecha:', {
                        dayBox_id: dayBox.id,
                        dayBox_class: dayBox.className,
                        dayBox_parent: dayBox.parentElement?.className,
                        dayBox_visible: window.getComputedStyle(dayBox).display !== 'none',
                        dayBox_color: window.getComputedStyle(dayBox).color,
                        dayBox_backgroundColor: window.getComputedStyle(dayBox).backgroundColor
                    });
                    
                    dayBox.textContent = day;
                    monthBox.textContent = month;
                    yearBox.textContent = year;
                    
                    // DEBUG: Verificar que se estableció correctamente
                    console.log('[ReceiptRenderer] DESPUES de establecer fecha:', {
                        dayBox_textContent: dayBox.textContent,
                        monthBox_textContent: monthBox.textContent,
                        yearBox_textContent: yearBox.textContent,
                        dayBox_visible: window.getComputedStyle(dayBox).display !== 'none',
                        dayBox_color: window.getComputedStyle(dayBox).color,
                        dayBox_backgroundColor: window.getComputedStyle(dayBox).backgroundColor
                    });
                    
                    console.log('[ReceiptRenderer] Fecha de activación del parcial establecida:', { 
                        day, 
                        month, 
                        year,
                        fecha_activacion: recibo.fecha_activacion
                    });
                } else if (esReciboParcial && (recibo.created_at || recibo.fecha_aprobacion)) {
                    // Parciales legacy pueden no tener activo/fecha_activacion.
                    // En ese caso usar created_at/fecha_aprobacion del parcial.
                    const fechaAUsar = recibo.fecha_aprobacion || recibo.created_at;
                    const fecha = Formatters.parsearFecha(fechaAUsar);
                    const { day, month, year } = Formatters.formatearFecha(fecha);

                    dayBox.textContent = day;
                    monthBox.textContent = month;
                    yearBox.textContent = year;

                    console.log('[ReceiptRenderer] Fecha parcial (fallback) establecida:', {
                        day,
                        month,
                        year,
                        fechaAprobacion: recibo.fecha_aprobacion,
                        created_at: recibo.created_at
                    });
                } else if (recibo.activo === 1 && (recibo.fecha_aprobacion || recibo.created_at)) {
                    // Recibo activo sin fecha_activacion: usar fecha de aprobación SI existe, si no usar fecha de creación del recibo
                    const fechaAUsar = recibo.fecha_aprobacion || recibo.created_at;
                    const fecha = Formatters.parsearFecha(fechaAUsar);
                    const { day, month, year } = Formatters.formatearFecha(fecha);
                    
                    dayBox.textContent = day;
                    monthBox.textContent = month;
                    yearBox.textContent = year;
                    
                    console.log('[ReceiptRenderer] Fecha de recibo activo establecida:', { 
                        day, 
                        month, 
                        year,
                        usandoFechaAprobacion: !!recibo.fecha_aprobacion,
                        fechaAprobacion: recibo.fecha_aprobacion,
                        created_at: recibo.created_at
                    });
                } else {
                    // Recibo no activo o sin fechas: mostrar fecha vacía
                    dayBox.textContent = '--';
                    monthBox.textContent = '--';
                    yearBox.textContent = '----';
                    
                    console.log('[ReceiptRenderer] Recibo no activo o sin fecha_activacion - Fecha vacía:', {
                        motivo: !recibo.activo ? 'activo es false/undefined' : tieneFechaActivacion ? 'fecha_activacion' : 'sin fechas',
                        activo: recibo.activo,
                        fecha_activacion: recibo.fecha_activacion,
                        created_at: recibo.created_at
                    });
                }
            } else {
                // Para costura base (no parciales) o si no hay recibo: usar fecha del pedido (comportamiento original)
                console.log('[ReceiptRenderer] Usando fecha del pedido (costura base o sin recibo):', {
                    tieneRecibo: !!recibo,
                    tipo_recibo: recibo?.tipo_recibo,
                    es_parcial: esReciboParcial,
                    aplicarLogicaEstado
                });
                
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
            console.log('[ReceiptRenderer._llenarDescripcion] Enriqueciendo recibo con colores por talla');
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

        this._anexarObservacionReciboProceso(prendaData, tipoProceso, datosPedido);
        
        // Cargar metrajes por color desde la API
        if (prendaData.prenda_pedido_id && datosPedido) {
            // Para recibos parciales el consecutivo puede venir en distintas llaves
            // Si no viene, no mostrar datos (por seguridad)
            const numeroRecibo =
                recibo?.consecutivo_actual ||
                recibo?.numero_recibo ||
                recibo?.numeroRecibo ||
                null;
            const consecutivoReciboId =
                recibo?.id ||
                recibo?.consecutivo_recibo_id ||
                null;
            this._cargarYAgregarMetrajesPorColor(prendaData, datosPedido, numeroRecibo, consecutivoReciboId);
        }
    }

    static async _anexarObservacionReciboProceso(prendaData, tipoProceso, datosPedido) {
        try {
            const descripcionEl = document.getElementById('descripcion-text');
            if (!descripcionEl) return;

            const pedidoId = Number(prendaData?.pedido_produccion_id || datosPedido?.pedido_id || datosPedido?.id || 0);
            const prendaId = Number(prendaData?.id || prendaData?.prenda_pedido_id || 0);
            const tipo = String(tipoProceso || '').trim().toUpperCase();

            if (!pedidoId || !prendaId || !tipo) return;

            const params = new URLSearchParams({
                pedido_id: String(pedidoId),
                prenda_id: String(prendaId),
                tipo_proceso: tipo
            });

            const endpoints = this._getObservacionProcesoEndpoints();
            let observacion = '';

            for (const endpoint of endpoints) {
                try {
                    const response = await fetch(`${endpoint}?${params.toString()}`);
                    if (!response.ok) continue;

                    const result = await response.json();
                    if (!result?.success) continue;

                    observacion = String(result?.data?.observacion || '').trim();
                    break;
                } catch (_) {
                    // Intentar siguiente endpoint
                }
            }

            if (!observacion) return;

            const observacionId = 'observacion-recibo-proceso-extra';
            const existente = descripcionEl.querySelector(`#${observacionId}`);
            if (existente) existente.remove();

            const bloque = document.createElement('div');
            bloque.id = observacionId;
            bloque.style.color = '#dc2626';
            bloque.innerHTML = `<br><br><strong>OBSERVACIÓN PROCESO:</strong><br>${this._escapeHtml(observacion).replace(/\n/g, '<br>')}`;

            descripcionEl.appendChild(bloque);
        } catch (error) {
            console.warn('[ReceiptRenderer._anexarObservacionReciboProceso] Error:', error);
        }
    }

    static _getObservacionProcesoEndpoints() {
        const path = String(window?.location?.pathname || '').toLowerCase();

        if (path.includes('/visualizador-logo/pedidos-logo')) {
            return [
                '/visualizador-logo/pedidos-logo/recibos-procesos/observacion',
                '/api/supervisor-pedidos/recibos-procesos/observacion'
            ];
        }

        if (path.includes('/insumos/')) {
            return [
                '/insumos/api/recibos-procesos/observacion',
                '/api/supervisor-pedidos/recibos-procesos/observacion'
            ];
        }

        if (path.includes('/operario/')) {
            return [
                '/operario/api/recibos-procesos/observacion',
                '/api/supervisor-pedidos/recibos-procesos/observacion'
            ];
        }

        if (path.includes('/control-calidad/')) {
            return [
                '/operario/api/recibos-procesos/observacion',
                '/api/supervisor-pedidos/recibos-procesos/observacion'
            ];
        }

        return ['/api/supervisor-pedidos/recibos-procesos/observacion'];
    }

    static _escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Cargar metrajes por color desde la API y renderizar según tipo_modo
     * - normal: Muestra Ancho + Metraje en barra inferior, NO en descripción
     * - color: Muestra solo Ancho en barra inferior, metraje en descripción por color
     * - pieza: Muestra Ancho en barra inferior + lista metraje por color abajo, NO en descripción
     */
    static _cargarYAgregarMetrajesPorColor(prendaData, datosPedido, numeroRecibo = null, consecutivoReciboId = null) {
        // Obtener ID de pedido desde prendaData (más confiable) o datosPedido
        let pedidoId = prendaData?.pedido_produccion_id || datosPedido?.pedido_id || datosPedido?.id;
        
        // Si no hay numeroRecibo, no mostrar nada
        if (!numeroRecibo || numeroRecibo <= 0) {
            console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Sin numero_recibo (consecutivo_actual), ocultando contenedor');
            const contenedorInicial = document.getElementById('order-ancho-metraje');
            if (contenedorInicial) {
                contenedorInicial.style.display = 'none';
            }
            return;
        }
        
        if (!pedidoId || !prendaData.prenda_pedido_id) {
            console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Sin ID de pedido o ID de prenda:', {
                pedidoId,
                pedidoProduccionId: prendaData?.pedido_produccion_id,
                prendaId: prendaData.prenda_pedido_id,
                numeroRecibo
            });
            return;
        }

        console.log('[ReceiptRenderer._cargarYAgregarMetrajesPorColor] Iniciando carga de metrajes:', {
            pedidoId,
            prendaId: prendaData.prenda_pedido_id,
            prendaNombre: prendaData.nombre_prenda,
            numeroRecibo
        });

        // Limpiar y ocultar contenedor de ancho/metraje inicialmente
        const contenedorInicial = document.getElementById('order-ancho-metraje');
        if (contenedorInicial) {
            contenedorInicial.style.display = 'none';
            const metrajesContainer = document.getElementById('metrajes-por-color-container');
            if (metrajesContainer) metrajesContainer.innerHTML = '';
        }

        // Fetch async para obtener metrajes
        // Siempre filtrar por numero_recibo (consecutivo_actual) 
        const queryParams = new URLSearchParams();
        queryParams.set('numero_recibo', String(numeroRecibo));
        if (consecutivoReciboId) {
            queryParams.set('consecutivo_recibo_id', String(consecutivoReciboId));
        }
        const publicEndpoint = `/pedidos-public/${pedidoId}/ancho-metraje-prenda/${prendaData.prenda_pedido_id}?${queryParams.toString()}`;
        const insumosEndpoint = `/insumos/materiales/${pedidoId}/obtener-ancho-metraje-prenda/${prendaData.prenda_pedido_id}?${queryParams.toString()}`;
        
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
                    anchoSpan.textContent = String(data.ancho);
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
                        metrajeSpan.textContent = metrajeGeneral ? String(metrajeGeneral) : '--';
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
                            span.textContent = `${item.color.toUpperCase()}: ${item.metraje}`;
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
                                    span.textContent = `${item.color.toUpperCase()}: ${item.metraje}`;
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
                                    span.textContent = `${item.color.toUpperCase()}: ${item.metraje}`;
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
                                span.textContent = `${item.color.toUpperCase()}: ${item.metraje}`;
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
                    return `${contenido.trim()} - Metraje: ${metraje}${cierre}`;
                }
                return match;
            });
        });

        descripcionEl.innerHTML = html;
    }
}
