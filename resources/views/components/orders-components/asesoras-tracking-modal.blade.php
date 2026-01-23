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

                <!-- Área -->
                <div class="asesoras-info-item">
                    <span class="asesoras-info-label">Área:</span>
                    <span class="asesoras-info-value" id="asesorasTrackingArea">-</span>
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
    console.log('🔵 [ASESORAS TRACKING] Abriendo modal para pedido:', pedido);
    
    try {
        // Obtener datos del pedido en JSON - usar endpoint DDD
        const response = await fetch(`/api/pedidos/${pedido}`);
        if (!response.ok) throw new Error('Error fetching order');
        const result = await response.json();
        const order = result.data || result;
        
        console.log(' [ASESORAS TRACKING] Datos obtenidos:', order);
        
        // Llenar información básica del modal
        document.getElementById('asesorasTrackingOrderNumber').textContent = pedido || '-';
        document.getElementById('asesorasTrackingClient').textContent = order.cliente_nombre || order.cliente || '-';
        
        // Área
        const area = order.area || 'Sin especificar';
        const areaElement = document.getElementById('asesorasTrackingArea');
        if (areaElement) {
            areaElement.textContent = area;
        }
        
        // Estado - Convertir a texto legible
        const estado = formatAsesorasOrderStatus(order.estado) || 'No iniciado';
        document.getElementById('asesorasTrackingStatus').textContent = estado;
        
        // Fecha estimada de entrega
        let fechaEstimada = '-';
        if (order.fecha_estimada_de_entrega) {
            const fecha = new Date(order.fecha_estimada_de_entrega);
            fechaEstimada = fecha.toLocaleDateString('es-ES', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        document.getElementById('asesorasTrackingDeliveryDate').textContent = fechaEstimada;
        
        // Llenar timeline de áreas (procesos simplificados)
        await fillAsesorasTimeline(pedido);
        
        // Mostrar modal
        const modal = document.getElementById('asesorasTrackingModal');
        if (modal) {
            modal.style.display = 'flex';
            console.log(' [ASESORAS TRACKING] Modal mostrado');
        }
    } catch (error) {
        console.error(' [ASESORAS TRACKING] Error:', error);
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
        
        console.log(' [ASESORAS TIMELINE] Obteniendo procesos para pedido:', pedido);
        
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
                console.log(' [ASESORAS TIMELINE] Datos obtenidos de /api/ordenes:', responseData);
            }
        } catch (error) {
            console.log(' [ASESORAS TIMELINE] Error en /api/ordenes, intentando /api/tabla-original');
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
                    console.log(' [ASESORAS TIMELINE] Datos obtenidos de /api/tabla-original:', responseData);
                }
            } catch (error) {
                console.log(' [ASESORAS TIMELINE] Error en /api/tabla-original');
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
                    console.log(' [ASESORAS TIMELINE] Datos obtenidos de /api/tabla-original-bodega:', responseData);
                }
            } catch (error) {
                console.log(' [ASESORAS TIMELINE] Error en /api/tabla-original-bodega');
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
                console.log(' [ASESORAS TIMELINE] Procesos extraídos de responseData.procesos');
            }
        }
        
        if (!procesos || procesos.length === 0) {
            console.log(' [ASESORAS TIMELINE] No hay procesos disponibles');
            container.innerHTML = '<p style="text-align: center; color: #6b7280; font-size: 0.9rem;">Sin procesos registrados</p>';
            return;
        }
        
        console.log(' [ASESORAS TIMELINE] Total de procesos:', procesos.length);
        
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
            
            console.log(` [ASESORAS TIMELINE] Proceso ${index + 1}: ${nombreArea} - ${fechaFormato}`);
            container.appendChild(timelineItem);
        });
        
    } catch (error) {
        console.error(' [ASESORAS TIMELINE] Error al llenar timeline:', error);
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
        'FINALIZADO': 'Finalizado'
    };
    return statusMap[estado] || estado;
}

/**
 * Cierra el modal de seguimiento para asesoras
 */
window.closeAsesorasTrackingModal = function() {
    console.log('🔵 [ASESORAS TRACKING] Cerrando modal');
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
