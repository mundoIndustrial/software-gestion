<!-- Script para calcular dinámicamente los días en procesos -->
<script>
// Flag para determinar si el usuario actual es admin
window.isAdmin = @json(auth()->check() && auth()->user()->role && auth()->user()->role->name === 'admin');

// Mapeo de procesos a iconos emoji
const processoIconMap = {
    'Pedido Recibido': '',
    'Creación Orden': '',
    'Insumos': '',
    'Insumos y Telas': '',
    'Corte': '✂️',
    'Bordado': '',
    'Estampado': '',
    'Costura': '👗',
    'Polos': '',
    'Taller': '',
    'Lavandería': '🧺',
    'Lavanderia': '🧺',
    'Arreglos': '🪡',
    'Control de Calidad': '',
    'Control-Calidad': '',
    'Entrega': '',
    'Despacho': '🚚',
    'Despachos': '🚚',
    'Reflectivo': '',
    'Marras': ''
};

function getProcessIcon(proceso) {
    return processoIconMap[proceso] || '';
}

// Función para calcular días entre dos fechas
function calcularDiasEnArea(fechaAnterior, fechaActual) {
    if (!fechaAnterior || !fechaActual) return 0;
    
    const f1 = new Date(fechaAnterior);
    const f2 = new Date(fechaActual);
    
    if (isNaN(f1.getTime()) || isNaN(f2.getTime())) return 0;
    
    const diferencia = Math.ceil((f2 - f1) / (1000 * 60 * 60 * 24));
    return Math.max(0, diferencia);
}

// Función para formatear fecha
function formatearFecha(fecha, formato = 'dd/mm/yyyy') {
    if (!fecha) return '-';
    
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return '-';

    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const año = date.getFullYear();

    if (formato === 'dd/mm/yyyy') {
        return `${dia}/${mes}/${año}`;
    }
    return fecha.toString();
}

// Función para renderizar timeline con cálculo dinámico
function renderizarTimeline(procesos) {
    if (!Array.isArray(procesos) || procesos.length === 0) {
        return `
            <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                <svg style="width: 48px; height: 48px; margin: 0 auto 16px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"></path>
                </svg>
                <p style="margin: 0; font-size: 14px;">No hay procesos registrados aún para este pedido.</p>
                <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">Los procesos se registrarán conforme el pedido avance en producción.</p>
            </div>
        `;
    }

    // Ordenar procesos por fecha
    const procesosOrdenados = [...procesos].sort((a, b) => {
        const fechaA = new Date(a.fecha_inicio || '0000-00-00');
        const fechaB = new Date(b.fecha_inicio || '0000-00-00');
        return fechaA - fechaB;
    });

    let html = '';
    let fechaAnterior = null;

    procesosOrdenados.forEach((proceso, index) => {
        const diasEnArea = calcularDiasEnArea(fechaAnterior, proceso.fecha_inicio);
        const fechaFormato = formatearFecha(proceso.fecha_inicio);
        const esPrimero = index === 0;
        const esUltimo = index === procesosOrdenados.length - 1;
        const icono = getProcessIcon(proceso.proceso);

        html += `
            <div class="tracking-timeline-item ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}">
                <div class="tracking-area-card ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}">
                    <div class="tracking-area-name">
                        <span style="font-size: 20px; flex-shrink: 0;">${icono}</span>
                        ${proceso.proceso}
                    </div>
                    <div class="tracking-area-details">
                        <div class="tracking-detail-row">
                            <span class="tracking-detail-label">Fecha</span>
                            <span class="tracking-detail-value">${fechaFormato}</span>
                        </div>
                        <div class="tracking-detail-row">
                            <span class="tracking-detail-label">Encargado</span>
                            <span class="tracking-detail-value">${proceso.encargado || '-'}</span>
                        </div>
                        <div class="tracking-detail-row">
                            <span class="tracking-detail-label">Días en Área</span>
                            <span class="tracking-detail-value">
                                ${diasEnArea === 0 ? '<span class="tracking-days-badge-zero">0 días</span>' : `<span class="tracking-days-badge">${diasEnArea} ${diasEnArea === 1 ? 'día' : 'días'}</span>`}
                            </span>
                        </div>
                        <div class="tracking-detail-row">
                            <span class="tracking-detail-label">Estado</span>
                            <span class="tracking-detail-value">${proceso.estado_proceso}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        fechaAnterior = proceso.fecha_inicio;
    });

    return html;
}

// Función para calcular total de días del pedido
function calcularTotalDias(procesos) {
    if (!Array.isArray(procesos) || procesos.length === 0) return 0;

    const procesosOrdenados = [...procesos].sort((a, b) => {
        const fechaA = new Date(a.fecha_inicio || '0000-00-00');
        const fechaB = new Date(b.fecha_inicio || '0000-00-00');
        return fechaA - fechaB;
    });

    const fechaInicio = new Date(procesosOrdenados[0].fecha_inicio);
    const fechaFin = new Date(procesosOrdenados[procesosOrdenados.length - 1].fecha_inicio);

    if (isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) return 0;

    const diferencia = Math.ceil((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24));
    return Math.max(0, diferencia);
}

// Función para mostrar el modal de tracking
function mostrarTrackingModal(pedidoData) {
    const modal = document.getElementById('orderTrackingModal');
    if (!modal) return;

    // Llenar información del pedido
    document.getElementById('trackingOrderNumber').textContent = `#${pedidoData.numero_pedido || '-'}`;
    document.getElementById('trackingOrderClient').textContent = pedidoData.cliente || '-';
    // Reemplazar guiones bajos con espacios en el estado
    const estadoFormato = (pedidoData.estado || '-').replace(/_/g, ' ');
    document.getElementById('trackingOrderStatus').textContent = estadoFormato;
    document.getElementById('trackingOrderDate').textContent = formatearFecha(pedidoData.fecha_inicio || pedidoData.fecha_de_creacion_de_orden);
    document.getElementById('trackingEstimatedDate').textContent = formatearFecha(pedidoData.fecha_estimada_de_entrega);

    // Calcular y mostrar total de días
    const totalDias = calcularTotalDias(pedidoData.procesos || []);
    document.getElementById('trackingTotalDays').textContent = totalDias;

    // Renderizar timeline
    const timelineContainer = document.getElementById('trackingTimelineContainer');
    if (timelineContainer) {
        timelineContainer.innerHTML = renderizarTimeline(pedidoData.procesos || []);
    }

    // Mostrar modal
    modal.style.display = 'flex';
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.getElementById('closeTrackingModal');
    const overlay = document.getElementById('trackingModalOverlay');
    const modal = document.getElementById('orderTrackingModal');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            if (modal) modal.style.display = 'none';
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            if (modal) modal.style.display = 'none';
        });
    }

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
            modal.style.display = 'none';
        }
    });
});
</script>
