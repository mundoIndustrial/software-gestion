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
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
    margin-bottom: 4px;
}

.asesoras-timeline-date {
    font-size: 0.85rem;
    color: #6b7280;
}

.asesoras-timeline-date strong {
    color: #1e40af;
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
    try {
        console.log('[openAsesorasTrackingModal] Abriendo modal para pedido:', pedido);
        
        // Obtener datos del pedido - intentar varias rutas
        let order = null;
        let response = null;
        
        // Intentar primero con /supervisor-pedidos/{id}/datos
        try {
            response = await fetch(`/supervisor-pedidos/${pedido}/datos`);
            if (response.ok) {
                order = await response.json();
                console.log('[openAsesorasTrackingModal] Datos obtenidos de /supervisor-pedidos/{id}/datos');
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
        document.getElementById('asesorasTrackingOrderNumber').textContent = pedido || '-';
        document.getElementById('asesorasTrackingClient').textContent = order.cliente_nombre || order.cliente || '-';
        
                
        // Estado - Convertir a texto legible
        const estado = (typeof formatAsesorasOrderStatus === 'function' ? formatAsesorasOrderStatus(order.estado) : order.estado) || 'No iniciado';
        document.getElementById('asesorasTrackingStatus').textContent = estado;
        
        // Fecha estimada de entrega
        let fechaEstimada = '-';
        if (order.fecha_estimada_de_entrega) {
            const fecha = new Date(order.fecha_estimada_de_entrega);
            if (!isNaN(fecha.getTime())) {
                fechaEstimada = fecha.toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
        }
        document.getElementById('asesorasTrackingDeliveryDate').textContent = fechaEstimada;
        
        // Llenar timeline de áreas (procesos simplificados)
        await fillAsesorasTimeline(pedido);
        
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
            await fillAsesorasTimeline(pedido);
            
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

/**
 * Llena el timeline con los procesos del pedido
 * @param {number} pedido - Número del pedido
 */
async function fillAsesorasTimeline(pedido) {
    try {
        const container = document.getElementById('asesorasTimelineContainer');
        if (!container) return;
        // Obtener los procesos del pedido (misma lógica que en tracking)
        let responseData = null;
        
        // Intentar primero con /api/ordenes/{id}/procesos
        try {
            const response = await fetch(`/api/ordenes/${pedido}/procesos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                responseData = await response.json();
            }
        } catch (error) {
        }
        
        // Si falla, intentar con /api/tabla-original/{pedido}/procesos
        if (!responseData) {
            try {
                const response = await fetch(`/api/tabla-original/${pedido}/procesos`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    responseData = await response.json();
                }
            } catch (error) {
            }
        }
        
        // Si aún no hay datos, intentar con /api/tabla-original-bodega
        if (!responseData) {
            try {
                const response = await fetch(`/api/tabla-original-bodega/${pedido}/procesos`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    responseData = await response.json();
                }
            } catch (error) {
            }
        }
        
        // Extraer array de procesos del objeto de respuesta
        let procesos = null;
        if (responseData) {
            // El endpoint puede retornar directamente un array o un objeto con propiedad 'procesos'
            if (Array.isArray(responseData)) {
                procesos = responseData;
            } else if (responseData.procesos && Array.isArray(responseData.procesos)) {
                procesos = responseData.procesos;
            }
        }
        
        if (!procesos || procesos.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 16px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"></path>
                    </svg>
                    <p style="margin: 0; font-size: 14px; font-weight: 500;">No hay procesos registrados aún</p>
                    <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">Los procesos se mostrarán conforme avance el pedido</p>
                </div>
            `;
            return;
        }
        // Ordenar procesos por fecha de inicio
        procesos.sort((a, b) => {
            const fechaA = new Date(a.fecha_inicio || a.createdAt || 0);
            const fechaB = new Date(b.fecha_inicio || b.createdAt || 0);
            return fechaA - fechaB;
        });
        
        // Llenar timeline
        container.innerHTML = '';
        procesos.forEach((proceso, index) => {
            const isCompleted = proceso.fecha_finalizacion || proceso.estado_proceso === 'Completado' || proceso.completed === true;
            const isCurrentArea = index === procesos.length - 1 && !isCompleted;
            
            const timelineItem = document.createElement('div');
            timelineItem.className = `asesoras-timeline-item ${isCompleted ? 'completed' : ''} ${isCurrentArea ? 'in-progress' : ''}`;
            
            // Formatear fecha - usar fecha_inicio como referencia de "cuándo llegó"
            const fechaInicio = new Date(proceso.fecha_inicio || proceso.createdAt);
            let fechaFormato = 'No especificada';
            
            if (!isNaN(fechaInicio.getTime())) {
                // Convertir a zona horaria de Bogotá (UTC-5)
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
            
            // Obtener nombre del área - intentar varias propiedades posibles
            let nombreArea = 'Área desconocida';
            if (proceso.proceso) {
                nombreArea = proceso.proceso;
            } else if (proceso.area) {
                nombreArea = proceso.area;
            } else if (proceso.nombre_area) {
                nombreArea = proceso.nombre_area;
            }
            
            timelineItem.innerHTML = `
                <div class="asesoras-timeline-icon">
                    ${isCompleted ? '✓' : (isCurrentArea ? '●' : index + 1)}
                </div>
                <div class="asesoras-timeline-content">
                    <div class="asesoras-timeline-area">${nombreArea}</div>
                    <div class="asesoras-timeline-date">
                        ${isCompleted ? 'Completado: ' : 'Llegó: '}<strong>${fechaFormato}</strong>
                    </div>
                </div>
            `;
            container.appendChild(timelineItem);
        });
        
    } catch (error) {
        const container = document.getElementById('asesorasTimelineContainer');
        if (container) {
            container.innerHTML = '<p style="text-align: center; color: #d32f2f; font-size: 0.9rem;">Error al cargar procesos</p>';
        }
    }
}

/**
 * Convierte el estado del pedido a formato legible
 */
function formatAsesorasOrderStatus(estado) {
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

