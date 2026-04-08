/**
 * TrackingModalController
 * Controlador para abrir y gestionar el modal de seguimiento de pedidos
 * 
 * Responsabilidades:
 * - Ver detalles de un recibo específico
 * - Abrir el modal de seguimiento con datos del pedido
 * - Obtener el consecutivo de costura
 * - Inicializar visualización del seguimiento
 * 
 * @class TrackingModalController
 * @example
 * const controller = TrackingModalController.getInstance();
 * controller.viewDetails(reciboId);
 * controller.open(pedidoId, prendaIdTarget);
 */

class TrackingModalController {
    constructor() {
        this.currentPedidoId = null;
        this.currentPrendaId = null;
    }

    /**
     * Obtener instancia singleton del controlador
     * @static
     * @returns {TrackingModalController} Instancia única
     */
    static getInstance() {
        if (!window.trackingModalControllerInstance) {
            window.trackingModalControllerInstance = new TrackingModalController();
        }
        return window.trackingModalControllerInstance;
    }

    /**
     * Ver detalles de un recibo - abre el recibo completo
     * @public
     * @param {number} reciboId - ID del recibo a visualizar
     */
    async viewDetails(reciboId) {
        try {
            // Buscar la fila del recibo para obtener el pedido_produccion_id
            const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
            if (!fila) {
                alert('No se encontró el recibo');
                return;
            }

            console.log(`[TrackingModalController]  Fila encontrada para recibo ${reciboId}`);

            const pedidoId = this._extractPedidoId(fila, reciboId);
            
            if (!pedidoId) {
                console.error(`[TrackingModalController]  No se pudo encontrar el ID del pedido para el recibo: ${reciboId}`);
                alert('No se encontró información del pedido asociada a este recibo. El recibo puede no estar correctamente vinculado a un pedido.');
                return;
            }

            console.log(`[TrackingModalController]  Pedido ID confirmado: ${pedidoId}`);

            // Para recibos de costura, necesitamos encontrar la primera prenda del pedido
            const prendas = await this._fetchPrendasData(pedidoId);

            if (!prendas || prendas.length === 0) {
                console.warn('[TrackingModalController] No se encontraron prendas para el pedido:', pedidoId);
                alert('No se encontraron prendas para este pedido. No se puede generar el recibo.');
                return;
            }

            // Obtener la primera prenda (asumimos que los recibos de costura son para la primera prenda)
            const primeraPrenda = prendas[0];
            const prendaId = primeraPrenda.id;

            console.log(`[TrackingModalController]  Prenda encontrada: ${prendaId}`);

            // Abrir el recibo de costura usando el módulo
            if (window.pedidosRecibosModule) {
                window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'costura');
            } else {
                console.error('[TrackingModalController] Módulo de recibos no disponible');
                alert('Módulo de recibos no disponible. Por favor recargue la página.');
            }
        } catch (error) {
            console.error('[TrackingModalController] Error en viewDetails:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        }
    }

    /**
     * Abrir el modal de seguimiento
     * @public
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaIdTarget - ID de la prenda a mostrar (opcional)
     */
    async open(pedidoId, prendaIdTarget) {
        try {
            // Cerrar cualquier dropdown abierto
            if (typeof closeDropdownRecibos === 'function') {
                closeDropdownRecibos();
            }

            console.log('[TrackingModalController] Abriendo seguimiento para pedido:', pedidoId, 'prenda:', prendaIdTarget);

            // Inicializar datos del pedido para el tracking modal
            if (typeof openOrderTracking === 'function') {
                console.log('[TrackingModalController] Llamando a openOrderTracking para inicializar datos');
                
                await openOrderTracking(pedidoId, false);
                console.log('[TrackingModalController] Datos inicializados desde openOrderTracking, buscando prenda específica:', prendaIdTarget);

                // Obtener prendas de los datos globales (poblados por openOrderTracking)
                let prendas = this._getPrendasFromGlobalData();

                // Si aún no hay prendas, obtenerlas de la API como fallback
                if (!prendas || prendas.length === 0) {
                    console.warn('[TrackingModalController] Prendas no encontradas en datos globales, obteniendo de API...');
                    prendas = await this._fetchPrendasData(pedidoId);
                    
                    // Guardar en datos globales para futuros accesos
                    if (prendas && prendas.length > 0) {
                        if (!window.currentOrderData) {
                            window.currentOrderData = {};
                        }
                        window.currentOrderData.prendas = prendas;
                        console.log('[TrackingModalController] Prendas guardadas en window.currentOrderData desde API');
                    }
                }

                if (!prendas || prendas.length === 0) {
                    console.warn('[TrackingModalController] No hay prendas disponibles', {prendas, hasPrendas: !!prendas});
                    if (typeof showPrendasSelector === 'function') {
                        showPrendasSelector();
                    } else {
                        alert('No hay prendas disponibles para este pedido');
                    }
                    return;
                }

                // Buscar la prenda específica por ID, si se proporcionó
                let prendaSeleccionada = null;
                if (prendaIdTarget) {
                    prendaSeleccionada = prendas.find(p => 
                        String(p.id) === String(prendaIdTarget) || 
                        String(p.prenda_pedido_id) === String(prendaIdTarget)
                    );
                    console.log('[TrackingModalController] Prenda encontrada por ID:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
                }

                // Fallback: usar la primera prenda si no se encontró la específica
                if (!prendaSeleccionada) {
                    prendaSeleccionada = prendas[0];
                    console.log('[TrackingModalController] Usando primera prenda como fallback:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
                }

                // Inicializar currentPrendaData
                window.currentPrendaData = prendaSeleccionada;

                // Abrir directamente el modal de seguimiento
                this._openTrackingModal(pedidoId, prendaIdTarget);
            } else {
                console.warn('[TrackingModalController] openOrderTracking no disponible');
                alert('Sistema de seguimiento no disponible');
            }
        } catch (error) {
            console.error('[TrackingModalController] Error al abrir seguimiento:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        }
    }

    /**
     * PRIVADO: Extraer el ID del pedido desde la fila
     * Intenta obtenerlo de múltiples fuentes
     * @private
     * @param {HTMLElement} fila - Fila del recibo
     * @param {number} reciboId - ID del recibo para logs
     * @returns {number|null} ID del pedido encontrado o null
     */
    _extractPedidoId(fila, reciboId) {
        let pedidoId = null;

        // Intentar obtener el enlace del pedido para extraer el pedido_produccion_id
        const enlacePedido = fila.querySelector('a[href*="/registros/"]');
        if (enlacePedido) {
            const href = enlacePedido.getAttribute('href');
            const pedidoIdMatch = href.match(/\/registros\/(\d+)/);
            if (pedidoIdMatch) {
                pedidoId = parseInt(pedidoIdMatch[1]);
                console.log(`[TrackingModalController] Pedido ID encontrado desde enlace: ${pedidoId}`);
                return pedidoId;
            }
        }

        // Si no se encontró, intentar obtenerlo del data-pedido-id
        const pedidoIdAttr = fila.getAttribute('data-pedido-id');
        if (pedidoIdAttr) {
            pedidoId = parseInt(pedidoIdAttr);
            console.log(`[TrackingModalController] Pedido ID encontrado desde data-pedido-id: ${pedidoId}`);
            return pedidoId;
        }

        // Si todavía no hay pedidoId, intentar obtenerlo del dropdown de día de entrega
        const dropdownDiaEntrega = fila.querySelector('.dia-entrega-dropdown');
        if (dropdownDiaEntrega) {
            const dropdownIdAttr = dropdownDiaEntrega.getAttribute('data-orden-id');
            if (dropdownIdAttr) {
                pedidoId = parseInt(dropdownIdAttr);
                console.log(`[TrackingModalController] Pedido ID encontrado desde dropdown día entrega: ${pedidoId}`);
                return pedidoId;
            }
        }

        console.error(`[TrackingModalController]  No se pudo encontrar el ID del pedido. Contenido: ${fila.innerHTML}`);
        return null;
    }

    /**
     * PRIVADO: Obtener prendas desde los datos globales
     * @private
     * @returns {Array|null} Array de prendas o null
     */
    _getPrendasFromGlobalData() {
        // Intentar encontrar prendas en diferentes estructuras posibles
        if (window.currentOrderData?.prendas) {
            return window.currentOrderData.prendas;
        }
        if (window.currentOrderData?.data?.prendas) {
            return window.currentOrderData.data.prendas;
        }
        if (window.prendasData?.length > 0) {
            return window.prendasData;
        }
        return null;
    }

    /**
     * PRIVADO: Obtener datos de prendas desde API
     * @private
     * @param {number} pedidoId - ID del pedido
     * @returns {Promise<Array>} Array de prendas
     */
    async _fetchPrendasData(pedidoId) {
        try {
            const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            let datos = await response.json();

            // Normalizar respuesta
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            return datos.prendas || null;
        } catch (error) {
            console.error('[TrackingModalController] Error al obtener datos de prendas:', error);
            throw error;
        }
    }

    /**
     * PRIVADO: Abrir el modal de seguimiento directamente
     * @private
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaIdTarget - ID de la prenda objetivo
     */
    async _openTrackingModal(pedidoId, prendaIdTarget) {
        // Abrir el overlay del modal de seguimiento
        const trackingOverlay = document.getElementById('trackingModalOverlay');
        if (trackingOverlay) {
            trackingOverlay.style.display = 'block';
        } else {
            console.warn('[TrackingModalController] Modal de seguimiento overlay no encontrado');
            alert('Modal de seguimiento no disponible');
            return;
        }

        // Abrir el contenido del modal
        const trackingModal = document.getElementById('orderTrackingModal');
        if (!trackingModal) {
            console.warn('[TrackingModalController] Contenido del modal de seguimiento no encontrado');
            return;
        }

        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');

        try {
            // Enriquecer prenda con seguimientos_por_area SIEMPRE
            if (window.currentPrendaData && !window.currentPrendaData.seguimientos_por_area) {
                try {
                    console.log('[TrackingModalController] Obteniendo seguimientos_por_area para prenda:', {
                        prendaId: window.currentPrendaData.id,
                        prendaNombre: window.currentPrendaData.nombre_prenda,
                        prendaPedidoId: window.currentPrendaData.prenda_pedido_id
                    });
                    const trackingResponse = await fetch(`/registros/${pedidoId}/seguimiento-prenda`);
                    console.log('[TrackingModalController] GET /registros/' + pedidoId + '/seguimiento-prenda - HTTP ' + trackingResponse.status);
                    
                    if (trackingResponse.ok) {
                        const trackingData = await trackingResponse.json();
                        console.log('[TrackingModalController] ✓ Respuesta seguimiento-prenda:', trackingData);
                        
                        // Buscar la prenda específica en la respuesta
                        if (trackingData.prendas && Array.isArray(trackingData.prendas)) {
                            console.log('[TrackingModalController] Prendas en respuesta:', trackingData.prendas.length);
                            trackingData.prendas.forEach((p, idx) => {
                                const seguimientosCount = p.seguimientos_por_area ? Object.keys(p.seguimientos_por_area).length : 0;
                                console.log(`[TrackingModalController] Prenda ${idx}:`, {
                                    id: p.id,
                                    prenda_pedido_id: p.prenda_pedido_id,
                                    nombre: p.nombre_prenda,
                                    hasSeguimientos: !!p.seguimientos_por_area,
                                    seguimientosCount: seguimientosCount,
                                    seguimientosKeys: p.seguimientos_por_area ? Object.keys(p.seguimientos_por_area) : [],
                                    seguimientosData: p.seguimientos_por_area || {}
                                });
                            });
                            
                            const prendaConTracking = trackingData.prendas.find(p => 
                                String(p.id) === String(window.currentPrendaData.id) || 
                                String(p.prenda_pedido_id) === String(window.currentPrendaData.id)
                            );
                            
                            if (prendaConTracking) {
                                console.log('[TrackingModalController] ✓ Prenda encontrada en respuesta:', prendaConTracking.nombre_prenda);
                                if (prendaConTracking.seguimientos_por_area) {
                                    console.log('[TrackingModalController] ✓ Asignando seguimientos_por_area:', Object.keys(prendaConTracking.seguimientos_por_area));
                                    console.log('[TrackingModalController] Contenido seguimientos_por_area:', JSON.stringify(prendaConTracking.seguimientos_por_area, null, 2));
                                    window.currentPrendaData.seguimientos_por_area = prendaConTracking.seguimientos_por_area;
                                    console.log('[TrackingModalController] Seguimientos_por_area asignados exitosamente');
                                } else {
                                    console.warn('[TrackingModalController] ⚠ Prenda encontrada pero sin seguimientos_por_area (undefined/null)');
                                }
                            } else {
                                console.warn('[TrackingModalController] ✗ Prenda NO encontrada en respuesta', {
                                    idBuscado: window.currentPrendaData.id,
                                    prendaPedidoIdBuscado: window.currentPrendaData.prenda_pedido_id,
                                    prendaIds: trackingData.prendas.map(p => ({id: p.id, prenda_pedido_id: p.prenda_pedido_id}))
                                });
                            }
                        } else {
                            console.warn('[TrackingModalController] ✗ Respuesta sin array de prendas');
                        }
                    } else {
                        console.warn('[TrackingModalController] ✗ Error HTTP en seguimiento-prenda:', trackingResponse.status);
                    }
                } catch (error) {
                    console.warn('[TrackingModalController] ✗ Error al obtener seguimientos_por_area:', error);
                }
            } else {
                console.log('[TrackingModalController] Prenda ya tiene seguimientos_por_area:', {
                    hasSeguimientos: !!window.currentPrendaData?.seguimientos_por_area,
                    isEmpty: window.currentPrendaData?.seguimientos_por_area && Object.keys(window.currentPrendaData.seguimientos_por_area).length === 0,
                    keys: window.currentPrendaData?.seguimientos_por_area ? Object.keys(window.currentPrendaData.seguimientos_por_area) : []
                });
            }

            // Mostrar seguimiento de la prenda seleccionada
            // Asegurar que seguimientos_por_area existe aunque sea objeto vacío
            if (!window.currentPrendaData.seguimientos_por_area) {
                window.currentPrendaData.seguimientos_por_area = {};
            }
            
            if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                showPrendaTracking(window.currentPrendaData);
            }
        } catch (error) {
            console.error('[TrackingModalController] Error general en _openTrackingModal:', error);
            
            // Intentar mostrar seguimiento sin consecutivo ni tracking data
            // Asegurar estructura mínima
            if (window.currentPrendaData && !window.currentPrendaData.seguimientos_por_area) {
                window.currentPrendaData.seguimientos_por_area = {};
            }
            
            if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                showPrendaTracking(window.currentPrendaData);
            }
        }
    }

    /**
     * PRIVADO: Actualizar la UI del modal de seguimiento con datos del consecutivo
     * @private
     * @param {Object} data - Datos del consecutivo de costura
     */
    _updateTrackingModalUI(data) {
        if (data.success && data.consecutivo) {
            const reciboElement = document.getElementById('trackingOrderRecibo');
            if (reciboElement) {
                reciboElement.textContent = data.consecutivo;
            }

            const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
            if (headerSubtitleElement) {
                const area = data.area ? String(data.area) : '';
                headerSubtitleElement.textContent = area
                    ? `COSTURA #${data.consecutivo} - ${area}`
                    : `COSTURA #${data.consecutivo}`;
            }
        } else {
            const reciboElement = document.getElementById('trackingOrderRecibo');
            if (reciboElement) {
                reciboElement.textContent = '-';
            }

            const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
            if (headerSubtitleElement) {
                const area = data?.area ? String(data.area) : '';
                headerSubtitleElement.textContent = area
                    ? `COSTURA #? - ${area}`
                    : 'COSTURA #?';
            }
        }

        if (data.fecha_creacion) {
            const fechaElement = document.getElementById('trackingOrderDate');
            if (fechaElement) {
                const fecha = new Date(data.fecha_creacion);
                fechaElement.textContent = fecha.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }
        }
    }
}
