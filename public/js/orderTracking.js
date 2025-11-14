/**
 * Mapeo de áreas a sus campos de fecha, encargado y días
 */
const areaFieldMappings = {
    'Creación Orden': {
        dateField: 'fecha_de_creacion_de_orden',
        chargeField: 'encargado_orden',
        daysField: 'dias_orden',
        icon: '📋',
        displayName: 'Pedido Recibido'
    },
    'Insumos': {
        dateField: 'insumos_y_telas',
        chargeField: 'encargados_insumos',
        daysField: 'dias_insumos',
        icon: '🧵',
        displayName: 'Insumos y Telas'
    },
    'Corte': {
        dateField: 'corte',
        chargeField: 'encargados_de_corte',
        daysField: 'dias_corte',
        icon: '✂️',
        displayName: 'Corte'
    },
    'Bordado': {
        dateField: 'bordado',
        chargeField: null,
        daysField: 'dias_bordado',
        icon: '🎨',
        displayName: 'Bordado'
    },
    'Estampado': {
        dateField: 'estampado',
        chargeField: 'encargados_estampado',
        daysField: 'dias_estampado',
        icon: '🖨️',
        displayName: 'Estampado'
    },
    'Costura': {
        dateField: 'costura',
        chargeField: 'modulo',
        daysField: 'dias_costura',
        icon: '👗',
        displayName: 'Costura'
    },
    'Polos': {
        dateField: 'costura',
        chargeField: 'modulo',
        daysField: 'dias_costura',
        icon: '👕',
        displayName: 'Polos'
    },
    'Taller': {
        dateField: 'costura',
        chargeField: 'modulo',
        daysField: 'dias_costura',
        icon: '🔧',
        displayName: 'Taller'
    },
    'Lavandería': {
        dateField: 'lavanderia',
        chargeField: 'encargado_lavanderia',
        daysField: 'dias_lavanderia',
        icon: '🧺',
        displayName: 'Lavandería'
    },
    'Arreglos': {
        dateField: 'arreglos',
        chargeField: 'encargado_arreglos',
        daysField: 'total_de_dias_arreglos',
        icon: '🪡',
        displayName: 'Arreglos'
    },
    'Control-Calidad': {
        dateField: 'control_de_calidad',
        chargeField: 'encargados_calidad',
        daysField: 'dias_c_c',
        icon: '✅',
        displayName: 'Control de Calidad'
    },
    'Entrega': {
        dateField: 'entrega',
        chargeField: 'encargados_entrega',
        daysField: null,
        icon: '📦',
        displayName: 'Entrega'
    },
    'Despachos': {
        dateField: 'despacho',
        chargeField: null,
        daysField: null,
        icon: '🚚',
        displayName: 'Despachos'
    }
};

/**
 * Festivos de Colombia 2025 (mismo fallback que el backend)
 * Incluye Ley Emiliani (festivos trasladados al lunes)
 */
const FESTIVOS_COLOMBIA_2025 = [
    '2025-01-01', // Año Nuevo
    '2025-01-06', // Reyes Magos (trasladado al lunes)
    '2025-03-24', // San José (trasladado al lunes)
    '2025-04-17', // Jueves Santo
    '2025-04-18', // Viernes Santo
    '2025-05-01', // Día del Trabajo
    '2025-06-02', // Ascensión (trasladado al lunes)
    '2025-06-23', // Corpus Christi (trasladado al lunes)
    '2025-06-30', // Sagrado Corazón (trasladado al lunes)
    '2025-07-07', // San Pedro y San Pablo (trasladado al lunes)
    '2025-07-20', // Día de la Independencia
    '2025-08-07', // Batalla de Boyacá
    '2025-08-18', // Asunción (trasladado al lunes)
    '2025-10-13', // Día de la Raza (trasladado al lunes)
    '2025-11-03', // Todos los Santos (trasladado al lunes)
    '2025-11-17', // Independencia de Cartagena (trasladado al lunes)
    '2025-12-08', // Inmaculada Concepción
    '2025-12-25', // Navidad
];

/**
 * Obtiene los festivos de Colombia
 * Usa la misma lógica que el backend: API pública + fallback hardcodeado
 */
let festivosCache = null;
async function obtenerFestivos() {
    if (festivosCache) {
        return festivosCache;
    }
    
    try {
        const year = new Date().getFullYear();
        // Intentar obtener desde la API pública (nager.at)
        const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/CO`);
        if (response.ok) {
            const data = await response.json();
            festivosCache = data.map(h => h.date);
            console.log(`✅ Festivos obtenidos de API para ${year}:`, festivosCache);
            return festivosCache;
        }
    } catch (error) {
        console.log('API de festivos no disponible, usando fallback');
    }
    
    // Usar fallback si la API falla
    festivosCache = FESTIVOS_COLOMBIA_2025;
    console.log('✅ Usando festivos fallback:', festivosCache);
    return festivosCache;
}

/**
 * Parsea una fecha string (YYYY-MM-DD) a Date sin problemas de zona horaria
 */
function parseLocalDate(dateString) {
    if (!dateString) return null;
    const [year, month, day] = dateString.split('T')[0].split('-');
    const date = new Date(year, month - 1, day);
    date.setHours(0, 0, 0, 0);
    return date;
}

/**
 * Calcula los días entre dos fechas (excluyendo fines de semana y festivos)
 * Lógica: Si entra y sale el mismo día = 0 días
 * Si entra el 20 y sale el 25 = 3 días (21, 22, 23, 24 no se cuentan, solo los días completos después del primero)
 */
function calculateBusinessDays(startDate, endDate, festivos = []) {
    if (!startDate || !endDate) return 0;

    // Si es string, parsear como local; si es Date, usar directamente
    const start = typeof startDate === 'string' ? parseLocalDate(startDate) : new Date(startDate);
    const end = typeof endDate === 'string' ? parseLocalDate(endDate) : new Date(endDate);

    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);

    if (start.getTime() === end.getTime()) {
        return 0;
    }

    const festivosSet = new Set(festivos.map(f => {
        if (typeof f === 'string') {
            return f.split('T')[0];
        }
        return f;
    }));

    let days = 0;
    const current = new Date(start);
    current.setDate(current.getDate() + 1);

    while (current <= end) {
        const dayOfWeek = current.getDay();
        const dateString = current.toISOString().split('T')[0];
        const isFestivo = festivosSet.has(dateString);
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        
        if (!isWeekend && !isFestivo) {
            days++;
        }
        
        current.setDate(current.getDate() + 1);
    }

    return Math.max(0, days);
}

/**
 * Obtiene el recorrido del pedido por las áreas
 * Calcula los días que pasó en cada área hasta la siguiente
 * Para el área actual, cuenta hasta hoy
 * Excluye sábados, domingos y festivos (igual que total_de_dias)
 */
async function getOrderTrackingPath(order) {
    const path = [];
    
    // Obtener festivos
    const festivos = await obtenerFestivos();
    
    // Orden específica de áreas según el flujo típico
    const areaOrder = [
        'Creación Orden',
        'Insumos',
        'Corte',
        'Bordado',
        'Estampado',
        'Costura',
        'Polos',
        'Taller',
        'Lavandería',
        'Arreglos',
        'Control-Calidad',
        'Entrega',
        'Despachos'
    ];
    
    // Obtener todas las áreas con fechas
    const areasWithDates = [];
    for (const area of areaOrder) {
        const mapping = areaFieldMappings[area];
        if (!mapping) continue;
        
        const dateValue = order[mapping.dateField];
        if (dateValue) {
            const dateObj = parseLocalDate(dateValue);
            
            areasWithDates.push({
                area: area,
                mapping: mapping,
                dateValue: dateValue,
                date: dateObj
            });
        }
    }
    
    // IMPORTANTE: Ordenar las áreas por fecha (cronológicamente)
    // Esto asegura que el conteo de días sea correcto según la secuencia real
    areasWithDates.sort((a, b) => a.date.getTime() - b.date.getTime());
    
    // Calcular días en cada área
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let totalDiasModal = 0;
    
    // Encontrar el índice del área "Despachos" si existe
    const despachosIndex = areasWithDates.findIndex(a => a.area === 'Despachos');
    
    for (let i = 0; i < areasWithDates.length; i++) {
        const current = areasWithDates[i];
        const next = areasWithDates[i + 1];
        
        let daysInArea = 0;
        
        if (next) {
            // Si hay siguiente área, contar días hasta esa fecha (excluyendo festivos)
            daysInArea = calculateBusinessDays(current.date, next.date, festivos);
        } else {
            // Si es la última área
            // IMPORTANTE: Si la última área es "Despachos", contar hasta esa fecha (no hasta hoy)
            // Esto detiene el contador cuando llega a despachos
            if (current.area === 'Despachos') {
                // Despachos es el final, no contar más allá
                daysInArea = 0;
            } else if (despachosIndex !== -1 && i < despachosIndex) {
                // Si hay despachos después de esta área, contar hasta despachos
                const despachosDate = areasWithDates[despachosIndex].date;
                daysInArea = calculateBusinessDays(current.date, despachosDate, festivos);
            } else {
                // Si no hay despachos o es la última área sin despachos, contar hasta hoy
                daysInArea = calculateBusinessDays(current.date, today, festivos);
            }
        }
        
        totalDiasModal += daysInArea;
        const chargeValue = current.mapping.chargeField ? order[current.mapping.chargeField] : null;
        
        path.push({
            area: current.area,
            displayName: current.mapping.displayName,
            icon: current.mapping.icon,
            date: current.dateValue,
            charge: chargeValue,
            daysInArea: daysInArea,
            isCompleted: true
        });
    }
    
    // IMPORTANTE: El backend resta 1 al final porque no cuenta el día de creación como día 0
    // El modal debe hacer lo mismo para coincidir
    path.totalDiasCalculado = totalDiasModal > 0 ? totalDiasModal - 1 : 0;
    
    return path;
}

/**
 * Abre el modal de seguimiento del pedido
 */
function openOrderTracking(orderId) {
    // Obtener datos de la orden
    fetch(`${window.fetchUrl}/${orderId}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.pedido) {
            displayOrderTracking(data);
        } else {
            console.error('No se encontró la orden');
        }
    })
    .catch(error => {
        console.error('Error al obtener datos de la orden:', error);
    });
}

/**
 * Muestra el modal de seguimiento con los datos del pedido
 */
async function displayOrderTracking(order) {
    const modal = document.getElementById('orderTrackingModal');
    if (!modal) {
        console.error('Modal de seguimiento no encontrado');
        return;
    }
    
    // Llenar información del pedido
    document.getElementById('trackingOrderNumber').textContent = `#${order.pedido}`;
    
    // Usar parseLocalDate para evitar problemas de zona horaria
    let fechaCreacion = order.fecha_de_creacion_de_orden;
    if (fechaCreacion) {
        document.getElementById('trackingOrderDate').textContent = formatDate(fechaCreacion);
    } else {
        document.getElementById('trackingOrderDate').textContent = '-';
    }
    
    // Calcular y mostrar fecha estimada de entrega
    let fechaEstimada = order.fecha_estimada_de_entrega;
    if (fechaEstimada) {
        document.getElementById('trackingEstimatedDate').textContent = formatDate(fechaEstimada);
    } else {
        document.getElementById('trackingEstimatedDate').textContent = '-';
    }
    
    document.getElementById('trackingOrderClient').textContent = order.cliente || '-';
    
    // Obtener recorrido del pedido (ahora es async)
    const trackingPath = await getOrderTrackingPath(order);

    // Mostrar total de días
    const totalDiasElement = document.getElementById('trackingTotalDays');
    if (totalDiasElement && trackingPath.totalDiasCalculado !== undefined) {
        totalDiasElement.textContent = trackingPath.totalDiasCalculado;
    }

    // Llenar timeline de áreas
    const timelineContainer = document.getElementById('trackingTimelineContainer');
    timelineContainer.innerHTML = '';
    
    trackingPath.forEach(item => {
        const timelineItem = document.createElement('div');
        timelineItem.className = `tracking-timeline-item ${item.isCompleted ? 'completed' : 'pending'}`;
        
        const areaCard = document.createElement('div');
        areaCard.className = `tracking-area-card ${item.isCompleted ? 'completed' : 'pending'}`;
        
        let detailsHTML = `
            <div class="tracking-area-name">
                <span>${item.icon}</span>
                <span>${item.displayName}</span>
            </div>
            <div class="tracking-area-details">
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Fecha</span>
                    <span class="tracking-detail-value">${formatDate(item.date)}</span>
                </div>
        `;
        
        if (item.charge) {
            detailsHTML += `
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Encargado</span>
                    <span class="tracking-detail-value">${item.charge}</span>
                </div>
            `;
        }
        
        // Siempre mostrar días en área, incluso si es 0
        const badgeClass = item.daysInArea === 0 ? 'tracking-days-badge-zero' : 'tracking-days-badge';
        detailsHTML += `
            <div class="tracking-detail-row">
                <span class="tracking-detail-label">Días en Área</span>
                <span class="tracking-detail-value">
                    <span class="${badgeClass}">${item.daysInArea} día${item.daysInArea !== 1 ? 's' : ''}</span>
                </span>
            </div>
        `;
        
        detailsHTML += '</div>';
        
        areaCard.innerHTML = detailsHTML;
        timelineItem.appendChild(areaCard);
        timelineContainer.appendChild(timelineItem);
    });
    
    // Mostrar modal
    modal.style.display = 'flex';
}

/**
 * Formatea una fecha al formato d/m/Y
 * Usa parseLocalDate para evitar problemas de zona horaria
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    
    try {
        // Si es string en formato YYYY-MM-DD, usar parseLocalDate
        if (typeof dateString === 'string' && dateString.includes('-')) {
            const date = parseLocalDate(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }
        
        // Si es un objeto Date, usar directamente
        const date = typeof dateString === 'string' ? parseLocalDate(dateString) : dateString;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        return dateString;
    }
}

/**
 * Cierra el modal de seguimiento
 */
function closeOrderTracking() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Inicializa los event listeners del modal de seguimiento
 */
function initializeTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    const overlay = document.getElementById('trackingModalOverlay');
    const closeBtn = document.getElementById('closeTrackingModal');
    const closeFooterBtn = document.getElementById('closeTrackingModalBtn');
    
    if (!modal) return;
    
    // Cerrar con botón X
    if (closeBtn) {
        closeBtn.addEventListener('click', closeOrderTracking);
    }
    
    // Cerrar con botón de footer
    if (closeFooterBtn) {
        closeFooterBtn.addEventListener('click', closeOrderTracking);
    }
    
    // Cerrar con overlay
    if (overlay) {
        overlay.addEventListener('click', closeOrderTracking);
    }
    
    // Prevenir cierre al hacer click dentro del modal
    const modalContent = document.querySelector('.tracking-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
}

/**
 * Crea un dropdown para el botón Ver
 */
function createViewButtonDropdown(orderId) {
    console.log('🔧 Creando dropdown para orden:', orderId);
    
    // Verificar si ya existe un dropdown
    const existingDropdown = document.querySelector(`.view-button-dropdown[data-order-id="${orderId}"]`);
    if (existingDropdown) {
        existingDropdown.remove();
        return;
    }
    
    // Crear dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'view-button-dropdown';
    dropdown.dataset.orderId = orderId;
    dropdown.innerHTML = `
        <button class="dropdown-option detail-option" onclick="viewDetail(${orderId}); closeViewDropdown(${orderId})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <span>Detalle</span>
        </button>
        <button class="dropdown-option tracking-option" onclick="openOrderTracking(${orderId}); closeViewDropdown(${orderId})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Seguimiento</span>
        </button>
    `;
    
    // Posicionar el dropdown cerca del botón Ver
    const viewButton = document.querySelector(`.detail-btn[onclick*="createViewButtonDropdown(${orderId})"]`);
    if (viewButton) {
        const rect = viewButton.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.zIndex = '9999';
        document.body.appendChild(dropdown);
        
        console.log('✅ Dropdown creado en posición:', {top: rect.bottom + 5, left: rect.left});
        
        // Cerrar dropdown al hacer click fuera
        setTimeout(() => {
            document.addEventListener('click', function closeDropdown(e) {
                if (!dropdown.contains(e.target) && !viewButton.contains(e.target)) {
                    dropdown.remove();
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }, 0);
    } else {
        console.warn('⚠️ No se encontró el botón Ver para la orden:', orderId);
    }
}

/**
 * Cierra el dropdown del botón Ver
 */
function closeViewDropdown(orderId) {
    const dropdown = document.querySelector(`.view-button-dropdown[data-order-id="${orderId}"]`);
    if (dropdown) {
        dropdown.remove();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Order Tracking inicializado');
    initializeTrackingModal();
});
