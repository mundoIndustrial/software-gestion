<!-- Modal Simplificado de Seguimiento para Asesoras -->
<div id="asesorasTrackingModal" class="asesoras-tracking-modal" style="display: none;">
    <div class="asesoras-tracking-overlay" id="asesorasTrackingOverlay" onclick="closeAsesorasTrackingModal()"></div>
    <div class="asesoras-tracking-content">
        <!-- Header -->
        <div class="asesoras-tracking-header">
            <h2 class="asesoras-tracking-title">Seguimiento del Pedido</h2>
            <button class="asesoras-tracking-close" id="closeAsesorasTrackingModal" onclick="closeAsesorasTrackingModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="asesoras-tracking-body">
            <!-- Información Básica del Pedido -->
            <div class="asesoras-info-container">
                <!-- Pedido -->
                <div class="asesoras-info-item">
                    <span class="asesoras-info-label">Número de Pedido:</span>
                    <span class="asesoras-info-value" id="asesorasTrackingOrderNumber">-</span>
                </div>

                <!-- Cliente -->
                <div class="asesoras-info-item">
                    <span class="asesoras-info-label">Cliente:</span>
                    <span class="asesoras-info-value" id="asesorasTrackingClient">-</span>
                </div>

                
                <!-- Estado Actual -->
                <div class="asesoras-info-item">
                    <span class="asesoras-info-label">Estado Actual:</span>
                    <span class="asesoras-info-value" id="asesorasTrackingStatus">-</span>
                </div>

                <!-- Fecha Estimada de Entrega -->
                <div class="asesoras-info-item">
                    <span class="asesoras-info-label">Fecha Estimada de Entrega:</span>
                    <span class="asesoras-info-value" id="asesorasTrackingDeliveryDate">-</span>
                </div>
            </div>

            <!-- Timeline Simplificado de Áreas -->
            <div class="asesoras-timeline-section">
                <h3 class="asesoras-timeline-title">Proceso de Producción</h3>
                <div id="asesorasTimelineContainer" class="asesoras-timeline-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

            <!-- Mensaje Informativo -->
            <div class="asesoras-info-message">
                <p>Para información más detallada, contacta con el departamento de producción.</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Container */
.asesoras-tracking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

/* Overlay */
.asesoras-tracking-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    cursor: pointer;
}

/* Content Container */
.asesoras-tracking-content {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Header */
.asesoras-tracking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
    border-radius: 12px 12px 0 0;
    position: sticky;
    top: 0;
}

.asesoras-tracking-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.asesoras-tracking-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.2s;
}

.asesoras-tracking-close:hover {
    transform: scale(1.1);
    opacity: 0.8;
}

.asesoras-tracking-close svg {
    width: 24px;
    height: 24px;
}

/* Body */
.asesoras-tracking-body {
    padding: 24px;
}

/* Info Container */
.asesoras-info-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

/* Info Items */
.asesoras-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #1e40af;
}

.asesoras-info-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.asesoras-info-value {
    font-weight: 500;
    color: #1e40af;
    font-size: 0.95rem;
    text-align: right;
}

/* Timeline Section */
.asesoras-timeline-section {
    margin: 24px 0;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.asesoras-timeline-title {
    margin: 0 0 16px 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.asesoras-timeline-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.asesoras-tracking-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 28px 20px;
    background: #f9fafb;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    color: #475569;
    text-align: center;
}

.asesoras-tracking-spinner {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 3px solid #dbeafe;
    border-top-color: #1e40af;
    animation: asesorasTrackingSpin 0.8s linear infinite;
}

@keyframes asesorasTrackingSpin {
    to {
        transform: rotate(360deg);
    }
}

/* Timeline Item */
.asesoras-timeline-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #d1d5db;
    transition: all 0.2s;
}

.asesoras-timeline-item.completed {
    background: #ecfdf5;
    border-left-color: #10b981;
}

.asesoras-timeline-item.in-progress {
    background: #dbeafe;
    border-left-color: #1e40af;
}

.asesoras-timeline-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Timeline Icon */
.asesoras-timeline-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: white;
    border: 2px solid #d1d5db;
    color: #6b7280;
    font-weight: 600;
    font-size: 0.9rem;
}

.asesoras-timeline-item.completed .asesoras-timeline-icon {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.asesoras-timeline-item.in-progress .asesoras-timeline-icon {
    background: #1e40af;
    border-color: #1e40af;
    color: white;
}

/* Timeline Content */
.asesoras-timeline-content {
    flex: 1;
}

.asesoras-timeline-area {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 800;
    color: #1e3a8a;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid #93c5fd;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    margin-bottom: 8px;
    box-shadow: 0 1px 0 rgba(30, 58, 138, 0.08);
    width: fit-content;
    max-width: 100%;
    line-height: 1.25;
    word-break: break-word;
}

.asesoras-timeline-item.in-progress .asesoras-timeline-area {
    color: #1e40af;
    border-color: #60a5fa;
    background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
}

.asesoras-timeline-item.completed .asesoras-timeline-area {
    color: #065f46;
    border-color: #6ee7b7;
    background: linear-gradient(180deg, #d1fae5 0%, #a7f3d0 100%);
}

.asesoras-timeline-date {
    font-size: 0.85rem;
    color: #6b7280;
}

.asesoras-timeline-date strong {
    color: #1e40af;
}

.asesoras-timeline-date.asesoras-status-area {
    display: inline-flex;
    align-items: center;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 3px 10px;
    width: fit-content;
    margin: 4px 0 2px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
}

.asesoras-timeline-date.asesoras-status-area.is-pending {
    color: #92400e;
    background: #fef3c7;
    border-color: #f59e0b;
}

/* Info Message */
.asesoras-info-message {
    background: #dbeafe;
    border-left: 4px solid #1e40af;
    padding: 12px;
    border-radius: 8px;
    margin-top: 16px;
}

.asesoras-info-message p {
    margin: 0;
    color: #1e3a8a;
    font-size: 0.85rem;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 640px) {
    .asesoras-tracking-content {
        max-width: 95%;
    }

    .asesoras-info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .asesoras-info-value {
        text-align: left;
    }

    .asesoras-tracking-header {
        padding: 16px;
    }

    .asesoras-tracking-body {
        padding: 16px;
    }
}

/* Scrollbar personalizado */
.asesoras-tracking-content::-webkit-scrollbar {
    width: 6px;
}

.asesoras-tracking-content::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.asesoras-tracking-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.asesoras-tracking-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
/**
 * Abre el modal de seguimiento simplificado para asesoras
 * @param {number} pedido - Número del pedido
 */
window.openAsesorasTrackingModal = async function(pedido) {
    let order = null;
    try {
        console.log('[openAsesorasTrackingModal] Abriendo modal para pedido:', pedido);
        showAsesorasTrackingLoadingState(pedido);
        
        // Obtener datos del pedido - intentar varias rutas
        let response = null;
        
        // Intentar primero con /api/supervisor-pedidos/ordenes/{id}/datos
        try {
            response = await fetch(`/api/supervisor-pedidos/ordenes/${pedido}/datos`);
            if (response.ok) {
                const result = await response.json();
                order = result.data || result;
                console.log('[openAsesorasTrackingModal] Datos obtenidos de /api/supervisor-pedidos/ordenes/{id}/datos');
            }
        } catch (e) {
            console.warn('[openAsesorasTrackingModal] Error con ruta supervisor-pedidos:', e);
        }
        
        // Si falla, intentar con /api/pedidos/{id}
        if (!order) {
            try {
                response = await fetch(`/api/pedidos/${pedido}`);
                if (response.ok) {
                    const result = await response.json();
                    order = result.data || result;
                    console.log('[openAsesorasTrackingModal] Datos obtenidos de /api/pedidos/{id}');
                }
            } catch (e) {
                console.warn('[openAsesorasTrackingModal] Error con ruta /api/pedidos:', e);
            }
        }
        
        // Si aún no tenemos datos, continuar de todas formas
        if (!order) {
            console.warn('[openAsesorasTrackingModal] No se obtuvieron datos del pedido, continuando sin ellos');
            order = { numero_pedido: pedido };
        }
        
        console.log('[openAsesorasTrackingModal] Datos del pedido:', order);
        
        // Llenar información básica del modal
        const numeroPedidoMostrar = order.numero_pedido || order.numeroPedido || pedido || '-';
        document.getElementById('asesorasTrackingOrderNumber').textContent = numeroPedidoMostrar;
        document.getElementById('asesorasTrackingClient').textContent = order.cliente_nombre || order.cliente || '-';
        
                
        // Estado - Convertir a texto legible
        const estado = (typeof formatAsesorasOrderStatus === 'function' ? formatAsesorasOrderStatus(order.estado) : order.estado) || 'No iniciado';
        document.getElementById('asesorasTrackingStatus').textContent = estado;
        
        // Fecha estimada de entrega
        let fechaEstimada = '-';
        const fechaEntrega = getAsesorasEstimatedDeliveryDate(order);
        if (fechaEntrega) {
            fechaEstimada = fechaEntrega.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        document.getElementById('asesorasTrackingDeliveryDate').textContent = fechaEstimada;
        
        // Llenar timeline de áreas (procesos simplificados)
        await fillAsesorasTimeline(pedido, order);
        
        // Mostrar modal
        const modal = document.getElementById('asesorasTrackingModal');
        if (modal) {
            modal.style.display = 'flex';
            console.log('[openAsesorasTrackingModal] Modal mostrado exitosamente');
        }
    } catch (error) {
        console.error('[openAsesorasTrackingModal] Error:', error);
        
        // Aún así intentar llenar el timeline y mostrar el modal con datos parciales
        try {
            console.log('[openAsesorasTrackingModal] Intentando llenar timeline de todas formas...');
            showAsesorasTrackingLoadingState(pedido);
            await fillAsesorasTimeline(pedido, order);
            
            // Mostrar modal
            const modal = document.getElementById('asesorasTrackingModal');
            if (modal) {
                modal.style.display = 'flex';
                console.log('[openAsesorasTrackingModal] Modal mostrado con datos parciales');
            }
        } catch (timelineError) {
            console.error('[openAsesorasTrackingModal] Error en timeline:', timelineError);
            alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
        }
    }
};

function showAsesorasTrackingLoadingState(pedido) {
    const modal = document.getElementById('asesorasTrackingModal');
    const timelineContainer = document.getElementById('asesorasTimelineContainer');
    const orderNumber = document.getElementById('asesorasTrackingOrderNumber');
    const client = document.getElementById('asesorasTrackingClient');
    const status = document.getElementById('asesorasTrackingStatus');
    const deliveryDate = document.getElementById('asesorasTrackingDeliveryDate');

    if (orderNumber) orderNumber.textContent = pedido || '-';
    if (client) client.textContent = 'Cargando...';
    if (status) status.textContent = 'Cargando...';
    if (deliveryDate) deliveryDate.textContent = 'Cargando...';

    if (timelineContainer) {
        timelineContainer.innerHTML = `
            <div class="asesoras-tracking-loading">
                <div class="asesoras-tracking-spinner"></div>
                <div>Cargando seguimiento del pedido...</div>
            </div>
        `;
    }

    if (modal) {
        modal.style.display = 'flex';
    }
}
/**
 * Llena el timeline con los procesos del pedido
 * @param {number} pedido - Número del pedido
 */
async function fillAsesorasTimeline(pedido, order = null) {
    try {
        const container = document.getElementById('asesorasTimelineContainer');
        if (!container) return;
        try {
            if (renderAsesorasRecibosTimeline(container, order)) return;
        } catch (renderError) {
            console.warn('[fillAsesorasTimeline] Error renderizando recibos, se usara fallback por procesos:', renderError);
        }

        let responseData = null;
        try {
            const response = await fetch(`/api/ordenes/${pedido}/procesos`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.ok) responseData = await response.json();
        } catch (error) {}

        if (!responseData) {
            try {
                const response = await fetch(`/api/tabla-original/${pedido}/procesos`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) responseData = await response.json();
            } catch (error) {}
        }

        if (!responseData) {
            try {
                const response = await fetch(`/api/tabla-original-bodega/${pedido}/procesos`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) responseData = await response.json();
            } catch (error) {}
        }

        let procesos = null;
        if (responseData) {
            if (Array.isArray(responseData)) procesos = responseData;
            else if (responseData.procesos && Array.isArray(responseData.procesos)) procesos = responseData.procesos;
        }

        if (!procesos || procesos.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 16px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"></path>
                    </svg>
                    <p style="margin: 0; font-size: 14px; font-weight: 500;">No hay procesos registrados aun</p>
                    <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">Los procesos se mostraran conforme avance el pedido</p>
                </div>
            `;
            return;
        }

        procesos.sort((a, b) => {
            const fechaA = new Date(a.fecha_inicio || a.createdAt || 0);
            const fechaB = new Date(b.fecha_inicio || b.createdAt || 0);
            return fechaA - fechaB;
        });

        container.innerHTML = '';
        procesos.forEach((proceso, index) => {
            const isCompleted = proceso.fecha_finalizacion || proceso.estado_proceso === 'Completado' || proceso.completed === true;
            const isCurrentArea = index === procesos.length - 1 && !isCompleted;

            const timelineItem = document.createElement('div');
            timelineItem.className = `asesoras-timeline-item ${isCompleted ? 'completed' : ''} ${isCurrentArea ? 'in-progress' : ''}`;

            const fechaInicio = new Date(proceso.fecha_inicio || proceso.createdAt);
            let fechaFormato = 'No especificada';
            if (!isNaN(fechaInicio.getTime())) {
                const formatter = new Intl.DateTimeFormat('es-ES', {
                    timeZone: 'America/Bogota',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                fechaFormato = formatter.format(fechaInicio);
            }

            let nombreArea = 'Area desconocida';
            if (proceso.proceso) nombreArea = proceso.proceso;
            else if (proceso.area) nombreArea = proceso.area;
            else if (proceso.nombre_area) nombreArea = proceso.nombre_area;

            const detalle = getAsesorasPrendaDetalle(proceso, order);
            const lineaPrenda = detalle.nombre ? `<div class="asesoras-timeline-date">Prenda: <strong>${detalle.nombre}</strong></div>` : '';
            const lineaDescripcion = detalle.descripcion ? `<div class="asesoras-timeline-date">Descripcion: <strong>${detalle.descripcion}</strong></div>` : '';
            const lineaTallas = detalle.tallas ? `<div class="asesoras-timeline-date">Tallas: <strong>${detalle.tallas}</strong></div>` : '';

            timelineItem.innerHTML = `
                <div class="asesoras-timeline-icon">
                    ${isCompleted ? '✓' : (isCurrentArea ? '●' : index + 1)}
                </div>
                <div class="asesoras-timeline-content">
                    <div class="asesoras-timeline-area">${nombreArea}</div>
                    <div class="asesoras-timeline-date">
                        ${isCompleted ? 'Completado: ' : 'Llego: '}<strong>${fechaFormato}</strong>
                    </div>
                    ${lineaPrenda}
                    ${lineaDescripcion}
                    ${lineaTallas}
                </div>
            `;
            container.appendChild(timelineItem);
        });
    } catch (error) {
        console.error('[fillAsesorasTimeline] Error no controlado:', error);
        const container = document.getElementById('asesorasTimelineContainer');
        if (container) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <p style="margin: 0; font-size: 14px; font-weight: 500;">No hay procesos/recibos para mostrar</p>
                </div>
            `;
        }
    }
}

/**
 * Renderiza la sección de proceso listando cada recibo del pedido
 * con su fecha estimada de entrega.
 */
function renderAsesorasRecibosTimeline(container, order) {
    const recibos = ((order && Array.isArray(order.recibos)) ? order.recibos : [])
        .filter(recibo => String(recibo && recibo.tipo_recibo || '').toUpperCase() !== 'COSTURA-BODEGA');
    if (recibos.length === 0) {
        return false;
    }

    const recibosOrdenados = [...recibos].sort((a, b) => {
        const fechaA = new Date((a && a.fecha_estimada_de_entrega) || 0);
        const fechaB = new Date((b && b.fecha_estimada_de_entrega) || 0);
        const fechaAValida = !isNaN(fechaA.getTime());
        const fechaBValida = !isNaN(fechaB.getTime());

        if (fechaAValida && fechaBValida && fechaA.getTime() !== fechaB.getTime()) {
            return fechaA - fechaB;
        }
        if (fechaAValida && !fechaBValida) return -1;
        if (!fechaAValida && fechaBValida) return 1;

        const consecutivoA = Number((a && a.consecutivo_actual) || 0);
        const consecutivoB = Number((b && b.consecutivo_actual) || 0);
        return consecutivoA - consecutivoB;
    });

    container.innerHTML = '';

    recibosOrdenados.forEach((recibo, index) => {
        const timelineItem = document.createElement('div');
        timelineItem.className = 'asesoras-timeline-item in-progress';

        const tipoRecibo = formatAsesorasReadableText(recibo && recibo.tipo_recibo, 'Recibo');
        const consecutivo = (recibo && recibo.consecutivo_actual) ? recibo.consecutivo_actual : '-';
        const area = formatAsesorasReadableText(recibo && recibo.area, 'Sin area');
        const areaClass = String(area).trim().toUpperCase() === 'PENDIENTE' ? ' is-pending' : '';
        const areaLabel = `AREA: ${area}`;
        const fechaEntrega = formatAsesorasDateOnly(recibo && recibo.fecha_estimada_de_entrega);
        const tituloRecibo = String(tipoRecibo).toUpperCase() === 'COSTURA-BODEGA'
            ? `#${consecutivo}`
            : `${tipoRecibo} #${consecutivo}`;

        const detalle = getAsesorasPrendaDetalle(recibo, order);
        const lineaPrenda = detalle.nombre ? `<div class="asesoras-timeline-date">Prenda: <strong>${detalle.nombre}</strong></div>` : '';
        const lineaDescripcion = detalle.descripcion ? `<div class="asesoras-timeline-date">Descripcion: <strong>${detalle.descripcion}</strong></div>` : '';
        const lineaTallas = detalle.tallas ? `<div class="asesoras-timeline-date">Tallas: <strong>${detalle.tallas}</strong></div>` : '';

        timelineItem.innerHTML = `
            <div class="asesoras-timeline-icon">
                ${index + 1}
            </div>
            <div class="asesoras-timeline-content">
                <div class="asesoras-timeline-area">${tituloRecibo}</div>
                <div class="asesoras-timeline-date">Entrega estimada: <strong>${fechaEntrega}</strong></div>
                <div class="asesoras-timeline-date asesoras-status-area${areaClass}">${areaLabel}</div>
                ${lineaPrenda}
                ${lineaDescripcion}
                ${lineaTallas}
            </div>
        `;

        container.appendChild(timelineItem);
    });

    return true;
}

function getAsesorasPrendaDetalle(source, order = null) {
    const prendaId = Number((source && (source.prenda_id ?? source.prendaId ?? source.prenda_pedido_id ?? source.prendaPedidoId)) || 0);

    let nombre = formatAsesorasReadableText(
        source && (source.prenda_nombre || source.nombre_prenda || source.prenda || source.nombre),
        ''
    );
    let descripcion = formatAsesorasReadableText(
        source && (source.prenda_descripcion || source.descripcion_prenda || source.descripcion),
        ''
    );
    let tallas = formatAsesorasTallasResumen(
        source && (source.tallas_resumen || source.cantidad_talla || source.tallas || source.tallas_detalle || source.tallasDetalle)
    );

    const prendasOrder = (order && Array.isArray(order.prendas)) ? order.prendas : [];
    if (prendasOrder.length > 0 && (!nombre || !descripcion || !tallas)) {
        const prendaMatch = prendasOrder.find(item => {
            const itemId = Number((item && (item.id ?? item.prenda_pedido_id ?? item.prendaPedidoId)) || 0);
            return prendaId > 0 ? itemId === prendaId : false;
        }) || (prendasOrder.length === 1 ? prendasOrder[0] : null);

        if (prendaMatch) {
            if (!nombre) nombre = formatAsesorasReadableText(prendaMatch.nombre || prendaMatch.prenda_nombre || prendaMatch.nombre_prenda || prendaMatch.prenda, '');
            if (!descripcion) descripcion = formatAsesorasReadableText(prendaMatch.descripcion || prendaMatch.prenda_descripcion || prendaMatch.descripcion_prenda, '');
            if (!tallas) tallas = formatAsesorasTallasResumen(prendaMatch.tallas_resumen || prendaMatch.cantidad_talla || prendaMatch.tallas || prendaMatch.tallas_detalle);
        }
    }

    return { nombre: nombre || '', descripcion: descripcion || '', tallas: tallas || '' };
}

function formatAsesorasTallasResumen(rawTallas) {
    if (!rawTallas) return '';

    if (typeof rawTallas === 'string') {
        const valor = rawTallas.trim();
        return (valor && valor !== '-') ? valor : '';
    }

    if (Array.isArray(rawTallas)) {
        const partes = rawTallas.map(item => {
            if (!item || typeof item !== 'object') return '';
            const genero = String(item.genero || '').trim();
            const talla = String(item.talla || '').trim() || 'SOBREMEDIDA';
            const cantidad = Number(item.cantidad || 0);
            if (!Number.isFinite(cantidad) || cantidad <= 0) return '';
            const prefijo = genero ? `${formatAsesorasReadableText(genero, '')} ` : '';
            return `${prefijo}${String(talla).toUpperCase()}: ${cantidad}`;
        }).filter(Boolean);
        return partes.join(', ');
    }

    if (typeof rawTallas === 'object') {
        const partes = [];
        Object.keys(rawTallas).forEach(key => {
            const valor = Number(rawTallas[key] || 0);
            if (!Number.isFinite(valor) || valor <= 0) return;
            partes.push(`${String(key).toUpperCase()}: ${valor}`);
        });
        return partes.join(', ');
    }

    return '';
}

/**
 * Formatea textos de enum/campos para mostrar en UI.
 */
function formatAsesorasReadableText(value, fallback = '-') {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    return String(value)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase());
}

/**
 * Formatea una fecha a texto largo en es-ES.
 */
function formatAsesorasDateOnly(rawDate) {
    if (!rawDate) return '-';

    const fecha = new Date(rawDate);
    if (isNaN(fecha.getTime())) {
        return '-';
    }

    return fecha.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Obtiene la fecha estimada de entrega para el modal de asesoras.
 * Prioriza la fecha mas lejana calculada por recibos.
 */
function getAsesorasEstimatedDeliveryDate(order) {
    if (!order || typeof order !== 'object') {
        return null;
    }

    const candidateDates = [];

    if (order.fecha_mas_lejana_recibos) {
        candidateDates.push(order.fecha_mas_lejana_recibos);
    }

    if (Array.isArray(order.recibos)) {
        order.recibos.forEach(recibo => {
            if (recibo && recibo.fecha_estimada_de_entrega) {
                candidateDates.push(recibo.fecha_estimada_de_entrega);
            }
        });
    }

    if (order.fecha_estimada_de_entrega) {
        candidateDates.push(order.fecha_estimada_de_entrega);
    }

    let fechaMaxima = null;
    candidateDates.forEach(rawDate => {
        const fecha = new Date(rawDate);
        if (isNaN(fecha.getTime())) {
            return;
        }
        if (!fechaMaxima || fecha > fechaMaxima) {
            fechaMaxima = fecha;
        }
    });

    return fechaMaxima;
}

/**
 * Convierte el estado del pedido a formato legible
 */
function formatAsesorasOrderStatus(estado) {
    if (estado === undefined || estado === null || estado === '') {
        return 'No iniciado';
    }

    const statusMap = {
        'PENDIENTE_SUPERVISOR': 'Pendiente por Aprobación',
        'APROBADO_SUPERVISOR': 'Aprobado',
        'EN_PRODUCCION': 'En Producción',
        'FINALIZADO': 'Finalizado',
        'pendiente_cartera': 'Pendiente Cartera',
        'PENDIENTE_INSUMOS': 'Pendiente Insumos',
        'No iniciado': 'No Iniciado',
        'En Ejecución': 'En Ejecución',
        'Anulada': 'Anulada',
        'Pendiente': 'Pendiente'
    };
    
    // Si no está en el mapa, convertir guiones bajos a espacios y capitalizar
    if (!statusMap[estado]) {
        return estado.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    return statusMap[estado];
}

/**
 * Cierra el modal de seguimiento para asesoras
 */
window.closeAsesorasTrackingModal = function() {
    const modal = document.getElementById('asesorasTrackingModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Cerrar al hacer click en la X
document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('closeAsesorasTrackingModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            window.closeAsesorasTrackingModal();
        });
    }
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('asesorasTrackingModal');
            if (modal && modal.style.display !== 'none') {
                window.closeAsesorasTrackingModal();
            }
        }
    });
});
</script>
