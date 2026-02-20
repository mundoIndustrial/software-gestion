/**
 * Módulo: TrackingUI
 * Responsabilidad: Renderizar y gestionar la interfaz del tracking
 * Principio SOLID: Single Responsibility
 */

const TrackingUI = (() => {
    /**
     * Obtiene o crea el modal de tracking
     */
    function getModal() {
        let modal = document.getElementById('orderTrackingModal');
        if (!modal) {

        }
        return modal;
    }
    
    /**
     * Llena los datos del header del tracking
     */
    function fillOrderHeader(orderData) {
        console.log('[fillOrderHeader] Datos recibidos:', orderData);
        console.log('[fillOrderHeader] Campos de fecha:', {
            fecha_creacion: orderData.fecha_creacion,
            fecha_de_creacion_de_orden: orderData.fecha_de_creacion_de_orden,
            created_at: orderData.created_at,
            fecha_estimada_de_entrega: orderData.fecha_estimada_de_entrega
        });
        
        const elements = {
            'trackingOrderNumber': `#${orderData.numero_pedido || '-'}`,
            'trackingOrderClient': orderData.cliente || '-',
            'trackingOrderDate': DateUtils.formatDate(orderData.fecha_creacion || orderData.fecha_de_creacion_de_orden || orderData.created_at),
            'trackingEstimatedDate': DateUtils.formatDate(orderData.fecha_estimada_de_entrega),
            'trackingOrderStatus': formatOrderStatus(orderData.estado || '-')
        };
        
        for (const [elementId, value] of Object.entries(elements)) {
            const el = document.getElementById(elementId);
            if (el) el.textContent = value;
        }
    }
    
    /**
     * Formatea el estado del pedido
     */
    function formatOrderStatus(estado) {
        // Mapeo de valores ENUM a labels (por si acaso vienen en ese formato)
        const statusMap = {
            'PENDIENTE_SUPERVISOR': 'Pendiente por Aprobación',
            'APROBADO_SUPERVISOR': 'Aprobado',
            'EN_PRODUCCION': 'En Producción',
            'FINALIZADO': 'Finalizado',
            'En Ejecución': 'En Ejecución',
            'Pendiente': 'Pendiente',
            'Entregado': 'Entregado',
            'No iniciado': 'No iniciado',
            'Anulada': 'Anulada',
            'PENDIENTE_INSUMOS': 'Pendiente de Insumos',
            'RECHAZADO_CARTERA': 'Rechazado por Cartera',
            'pendiente_cartera': 'Pendiente Cartera'
        };
        // Si el estado está en el mapa, usar el mapping; si no, devolver tal como está
        return statusMap[estado] || estado;
    }
    
    /**
     * Renderiza el timeline de procesos
     */
    function renderProcessTimeline(procesos, orderData, festivos) {
        const timelineContainer = document.getElementById('trackingTimelineContainer');
        if (!timelineContainer) return 0;
        
        timelineContainer.innerHTML = '';
        let totalDiasCalculado = 0;
        
        procesos.forEach((proceso, index) => {
            const proximo = procesos[index + 1];
            
            // Calcular días en área
            let diasEnArea = 0;
            const fecha1 = DateUtils.parseLocalDate(proceso.fecha_inicio);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (proximo) {
                const fecha2 = DateUtils.parseLocalDate(proximo.fecha_inicio);
                if (!isNaN(fecha1.getTime()) && !isNaN(fecha2.getTime())) {
                    diasEnArea = DateUtils.calculateBusinessDays(fecha1, fecha2, festivos);
                }
            } else {
                // Si es el último proceso (sin próximo)
                // Si es Despachos/Despacho/Entrega, no contar más días (orden entregada)
                if (proceso.proceso === 'Despachos' || proceso.proceso === 'Despacho' || proceso.proceso === 'Entrega') {
                    diasEnArea = 0;
                } else if (!isNaN(fecha1.getTime())) {
                    // Para otros últimos procesos, contar hasta hoy
                    diasEnArea = DateUtils.calculateBusinessDays(fecha1, today, festivos);
                }
            }
            
            totalDiasCalculado += diasEnArea;
            
            // Crear elemento del timeline
            const timelineItem = document.createElement('div');
            timelineItem.className = `tracking-timeline-item ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}`;
            
            const areaCard = document.createElement('div');
            areaCard.className = `tracking-area-card ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}`;
            
            let detailsHTML = createProcessCard(proceso, diasEnArea, orderData);
            areaCard.innerHTML = detailsHTML;
            timelineItem.appendChild(areaCard);
            timelineContainer.appendChild(timelineItem);
        });
        
        // CORREGIR TOTAL: Calcular desde fecha_de_creacion_de_orden hasta el último proceso (o hoy si no está entregado)
        if (orderData && (orderData.fecha_de_creacion_de_orden || orderData.fecha_inicio) && procesos.length > 0) {
            // Usar fecha_inicio (nombre del backend) o fecha_de_creacion_de_orden como fallback
            const fechaCreacionStr = orderData.fecha_de_creacion_de_orden || orderData.fecha_inicio;
            const fechaCreacion = DateUtils.parseLocalDate(fechaCreacionStr);
            const ultimoProceso = procesos[procesos.length - 1];
            const fechaUltimo = DateUtils.parseLocalDate(ultimoProceso.fecha_inicio);
            
            // Si es un proceso de despacho/entrega, el total es hasta ese punto
            let fechaFin = fechaUltimo;
            if (ultimoProceso.proceso !== 'Despachos' && ultimoProceso.proceso !== 'Despacho' && ultimoProceso.proceso !== 'Entrega') {
                // Si no es despacho, contar hasta hoy
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                fechaFin = today;
            }
            
            if (!isNaN(fechaCreacion.getTime()) && !isNaN(fechaFin.getTime())) {
                totalDiasCalculado = DateUtils.calculateBusinessDays(fechaCreacion, fechaFin, festivos);
            }
        }
        
        return totalDiasCalculado;
    }
    
    /**
     * Crea el HTML de una tarjeta de proceso
     */
    function createProcessCard(proceso, diasEnArea, orderData) {
        // Verificar si el usuario puede editar procesos (admin o produccion)
        const userRole = document.body.getAttribute('data-user-role');
        const canEditProcess = userRole === 'admin' || userRole === 'produccion';
        
        let topRightButtons = '';
        if (canEditProcess) {
            topRightButtons = createAdminButtons(proceso, orderData);
        }
        
        const badgeClass = diasEnArea === 0 ? 'tracking-days-badge-zero' : 'tracking-days-badge';
        
        return `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                <div class="tracking-area-name" style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <span class="material-symbols-rounded" style="font-size: 28px; flex-shrink: 0; color: #3b82f6;">${AreaMapper.getProcessIcon(proceso.proceso)}</span>
                    <span style="font-size: 16px; font-weight: 600; color: inherit;">${proceso.proceso}</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    ${topRightButtons}
                </div>
            </div>
            <div class="tracking-area-details">
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Fecha</span>
                    <span class="tracking-detail-value">${DateUtils.formatDate(proceso.fecha_inicio)}</span>
                </div>
                ${proceso.encargado ? `
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Encargado</span>
                    <span class="tracking-detail-value" style="font-weight: 500; color: #059669;">${proceso.encargado}</span>
                </div>
                ` : ''}
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Días en Área</span>
                    <span class="tracking-detail-value">
                        <span class="${badgeClass}">${diasEnArea} día${diasEnArea !== 1 ? 's' : ''}</span>
                    </span>
                </div>
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Estado</span>
                    <span class="tracking-detail-value" style="font-weight: 500; color: ${getStatusColor(proceso.estado_proceso)};">${proceso.estado_proceso}</span>
                </div>
            </div>
        `;
    }
    
    /**
     * Crea los botones de administrador
     */
    function createAdminButtons(proceso, orderData) {
        return `
            <div style="display: flex; gap: 6px; align-items: center;">
                <button class="btn-editar-proceso" data-orden="${orderData.numero_pedido}"
                        style="background: #3b82f6; color: white; border: none; border-radius: 6px; padding: 8px 10px; cursor: pointer; font-size: 18px; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; hover: background: #2563eb;"
                        title="Editar proceso">
                    <span class="material-symbols-rounded" style="font-size: 20px; color: white;">edit</span>
                </button>
                <button class="btn-eliminar-proceso" data-orden="${orderData.numero_pedido}"
                        style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 8px 10px; cursor: pointer; font-size: 18px; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; hover: background: #dc2626;"
                        title="Eliminar proceso">
                    <span class="material-symbols-rounded" style="font-size: 20px; color: white;">delete</span>
                </button>
            </div>
        `;
    }
    
    /**
     * Obtiene el color según el estado
     */
    function getStatusColor(estado) {
        const colors = {
            'Completado': '#059669',
            'En Progreso': '#d97706',
            'Pendiente': '#6b7280',
            'Pausado': '#1e40af'
        };
        return colors[estado] || '#6b7280';
    }
    
    /**
     * Actualiza el total de días
     */
    function updateTotalDays(totalDias) {
        const element = document.getElementById('trackingTotalDays');
        if (element) {
            element.textContent = totalDias;
        }
    }
    
    /**
     * Muestra el modal
     */
    function showModal() {
        const modal = getModal();
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    /**
     * Cierra el modal
     */
    function hideModal() {
        const modal = getModal();
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // Interfaz pública
    return {
        fillOrderHeader,
        renderProcessTimeline,
        updateTotalDays,
        showModal,
        hideModal,
        getModal
    };
})();

globalThis.TrackingUI = TrackingUI;

