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
    
    // Soportar múltiples formatos de fecha
    let parts;
    
    // Formato YYYY-MM-DD (ISO)
    if (dateString.includes('-') && dateString.split('-')[0].length === 4) {
        parts = dateString.split('T')[0].split('-');
        const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        date.setHours(0, 0, 0, 0);
        return date;
    }
    
    // Formato DD/MM/YYYY
    if (dateString.includes('/')) {
        parts = dateString.split('/');
        if (parts.length === 3) {
            const date = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
            date.setHours(0, 0, 0, 0);
            return date;
        }
    }
    
    // Fallback: intentar parseo automático
    const date = new Date(dateString);
    date.setHours(0, 0, 0, 0);
    return date;
}

/**
 * Calcula los días entre dos fechas (excluyendo fines de semana y festivos)
 * Lógica: El contador inicia desde el PRIMER DÍA HÁBIL DESPUÉS de la fecha de inicio
 * Si creación es sábado 22/11, el contador empieza lunes 24/11 (día 1)
 * Si creación es lunes 24/11, el contador empieza martes 25/11 (día 1)
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
    
    // Saltar al próximo día (contador inicia DESPUÉS de la fecha de creación)
    current.setDate(current.getDate() + 1);

    // Contar desde el próximo día hasta el final
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
    
    // IMPORTANTE: El contador inicia un día DESPUÉS de la creación
    // Por eso restamos 1 al final: no cuenta el día de creación
    path.totalDiasCalculado = totalDiasModal > 0 ? totalDiasModal - 1 : 0;
    
    return path;
}

/**
 * Abre el modal de seguimiento del pedido
 */
function openOrderTracking(orderId) {
    // Obtener datos de los procesos directamente
    fetch(`/api/ordenes/${orderId}/procesos`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('No se encontraron los procesos de la orden');
        }
        return response.json();
    })
    .then(data => {
        displayOrderTrackingWithProcesos(data);
    })
    .catch(error => {
        console.error('Error al obtener procesos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar el seguimiento de la orden',
            confirmButtonColor: '#ef4444',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
    });
}

/**
 * Mapeo de procesos a sus iconos emoji
 */
const processoIconMap = {
    'Pedido Recibido': '📋',
    'Creación Orden': '📋',
    'Insumos': '🧵',
    'Insumos y Telas': '🧵',
    'Corte': '✂️',
    'Bordado': '🎨',
    'Estampado': '🖨️',
    'Costura': '👗',
    'Polos': '👕',
    'Taller': '🔧',
    'Lavandería': '🧺',
    'Lavanderia': '🧺',
    'Arreglos': '🪡',
    'Control de Calidad': '✅',
    'Control-Calidad': '✅',
    'Entrega': '📦',
    'Despacho': '🚚',
    'Despachos': '🚚',
    'Reflectivo': '✨',
    'Marras': '🔍'
};

function getProcessIcon(proceso) {
    return processoIconMap[proceso] || '⚙️';
}

/**
 * Muestra el modal de seguimiento con la nueva estructura de procesos
 */
async function displayOrderTrackingWithProcesos(orderData) {
    const modal = document.getElementById('orderTrackingModal');
    if (!modal) {
        console.error('Modal de seguimiento no encontrado');
        return;
    }
    
    // Llenar información del pedido
    document.getElementById('trackingOrderNumber').textContent = `#${orderData.numero_pedido || '-'}`;
    document.getElementById('trackingOrderClient').textContent = orderData.cliente || '-';
    document.getElementById('trackingOrderDate').textContent = formatDate(orderData.fecha_inicio);
    document.getElementById('trackingEstimatedDate').textContent = formatDate(orderData.fecha_estimada_de_entrega);
    
    // Procesos del API
    const procesos = orderData.procesos || [];
    
    if (!procesos || procesos.length === 0) {
        document.getElementById('trackingTotalDays').textContent = 0;
        document.getElementById('trackingTimelineContainer').innerHTML = '<p class="text-center text-gray-500">No hay procesos registrados</p>';
        modal.style.display = 'flex';
        return;
    }
    
    // Usar el total de días hábiles calculado por el backend
    const totalDias = orderData.total_dias_habiles || 0;
    const festivos = orderData.festivos || [];
    
    document.getElementById('trackingTotalDays').textContent = totalDias;
    
    // Llenar timeline de procesos
    const timelineContainer = document.getElementById('trackingTimelineContainer');
    timelineContainer.innerHTML = '';
    
    let fechaAnterior = null;
    
    procesos.forEach((proceso, index) => {
        const timelineItem = document.createElement('div');
        timelineItem.className = `tracking-timeline-item ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}`;
        
        const areaCard = document.createElement('div');
        areaCard.className = `tracking-area-card ${proceso.estado_proceso === 'Completado' ? 'completed' : 'pending'}`;
        
        // Calcular días en esta área
        let diasEnArea = 0;
        if (index === procesos.length - 1) {
            // Es el último proceso: contar hasta hoy
            const fecha1 = parseLocalDate(proceso.fecha_inicio);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (!isNaN(fecha1.getTime())) {
                diasEnArea = calculateBusinessDays(fecha1, today, festivos);
            }
        } else if (fechaAnterior) {
            // No es el primer proceso: contar desde el anterior
            diasEnArea = calculateBusinessDays(fechaAnterior, proceso.fecha_inicio, festivos);
        } else {
            // Es el primer proceso: contar desde él hasta hoy
            const fecha1 = parseLocalDate(proceso.fecha_inicio);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (!isNaN(fecha1.getTime())) {
                diasEnArea = calculateBusinessDays(fecha1, today, festivos);
            }
        }
        
        // Agregar botones de editar y eliminar solo para admin
        const isAdmin = document.body.getAttribute('data-is-admin') === 'true';
        let topRightButtons = '';
        if (isAdmin) {
            // Guardar datos en un atributo data- seguro
            const procesoId = `proceso-${orderData.numero_pedido}-${index}`;
            
            topRightButtons = `
                <div style="display: flex; gap: 6px; align-items: center;">
                    <button class="btn-editar-proceso" data-index="${index}" data-orden="${orderData.numero_pedido}"
                            style="background: #3b82f6; color: white; border: none; border-radius: 5px; padding: 7px 12px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);"
                            onmouseover="this.style.background='#2563eb'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.5)';"
                            onmouseout="this.style.background='#3b82f6'; this.style.boxShadow='0 2px 4px rgba(59, 130, 246, 0.3)';"
                            title="Editar proceso">
                        ✏️ Editar
                    </button>
                    <button class="btn-eliminar-proceso" data-index="${index}" data-orden="${orderData.numero_pedido}"
                            style="background: #ef4444; color: white; border: none; border-radius: 5px; padding: 7px 12px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);"
                            onmouseover="this.style.background='#dc2626'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.5)';"
                            onmouseout="this.style.background='#ef4444'; this.style.boxShadow='0 2px 4px rgba(239, 68, 68, 0.3)';"
                            title="Eliminar proceso">
                        🗑️ Eliminar
                    </button>
                </div>
            `;
        }
        
        let detailsHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
                <div class="tracking-area-name" style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <span style="font-size: 28px; flex-shrink: 0;">${getProcessIcon(proceso.proceso)}</span>
                    <span style="font-size: 16px; font-weight: 600; color: #1f2937;">${proceso.proceso}</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    ${topRightButtons}
                </div>            </div>
            <div class="tracking-area-details">
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Fecha</span>
                    <span class="tracking-detail-value">${formatDate(proceso.fecha_inicio)}</span>
                </div>
        `;
        
        if (proceso.encargado) {
            detailsHTML += `
                <div class="tracking-detail-row">
                    <span class="tracking-detail-label">Encargado</span>
                    <span class="tracking-detail-value" style="font-weight: 500; color: #059669;">${proceso.encargado}</span>
                </div>
            `;
        }
        
        // Mostrar días en área
        const badgeClass = diasEnArea === 0 ? 'tracking-days-badge-zero' : 'tracking-days-badge';
        detailsHTML += `
            <div class="tracking-detail-row">
                <span class="tracking-detail-label">Días en Área</span>
                <span class="tracking-detail-value">
                    <span class="${badgeClass}">${diasEnArea} día${diasEnArea !== 1 ? 's' : ''}</span>
                </span>
            </div>
        `;
        
        detailsHTML += `
            <div class="tracking-detail-row">
                <span class="tracking-detail-label">Estado</span>
                <span class="tracking-detail-value" style="font-weight: 500; color: ${proceso.estado_proceso === 'Completado' ? '#059669' : proceso.estado_proceso === 'En Progreso' ? '#d97706' : '#dc2626'};">${proceso.estado_proceso}</span>
            </div>
            </div>
        `;
        
        areaCard.innerHTML = detailsHTML;
        timelineItem.appendChild(areaCard);
        timelineContainer.appendChild(timelineItem);
        
        fechaAnterior = proceso.fecha_inicio;
    });
    
    // Agregar event listeners a los botones
    document.querySelectorAll('.btn-editar-proceso').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            const procesoData = procesos[index];
            editarProceso(JSON.stringify({
                numero_pedido: orderData.numero_pedido,
                proceso: procesoData.proceso,
                fecha_inicio: procesoData.fecha_inicio,
                encargado: procesoData.encargado,
                estado_proceso: procesoData.estado_proceso
            }));
        });
    });
    
    document.querySelectorAll('.btn-eliminar-proceso').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            const procesoData = procesos[index];
            eliminarProceso(JSON.stringify({
                numero_pedido: orderData.numero_pedido,
                proceso: procesoData.proceso,
                fecha_inicio: procesoData.fecha_inicio,
                encargado: procesoData.encargado,
                estado_proceso: procesoData.estado_proceso
            }));
        });
    });
    
    // Mostrar modal
    modal.style.display = 'flex';
}

/**
 * Muestra el modal de seguimiento con los datos del pedido (función antigua, mantener para compatibilidad)
 */
async function displayOrderTracking(order) {
    const modal = document.getElementById('orderTrackingModal');
    if (!modal) {
        console.error('Modal de seguimiento no encontrado');
        return;
    }
    
    // Llenar información del pedido
    document.getElementById('trackingOrderNumber').textContent = `#${order.numero_pedido || order.pedido}`;
    
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

    // Calcular total de días sumando los días de cada área
    let totalDiasReal = 0;
    trackingPath.forEach(item => {
        totalDiasReal += item.daysInArea;
    });

    // Mostrar total de días
    const totalDiasElement = document.getElementById('trackingTotalDays');
    if (totalDiasElement) {
        totalDiasElement.textContent = totalDiasReal;
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
 * Editar un proceso (solo admin)
 */
async function editarProceso(procesoJsonStr) {
    try {
        // Desencriptar el JSON
        const proceso = JSON.parse(procesoJsonStr);
        
        // Convertir fecha a formato yyyy-mm-dd
        const fechaParts = proceso.fecha_inicio.split('-');
        const fechaISO = fechaParts.length === 3 ? proceso.fecha_inicio : new Date(proceso.fecha_inicio).toISOString().split('T')[0];
        
        // Crear modal de edición
        const modalHTML = `
            <div id="editProcesoModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                <div style="background: white; border-radius: 8px; padding: 24px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <h2 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 600; color: #1f2937;">Editar Proceso</h2>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Nombre del Proceso</label>
                        <input type="text" id="editProceso" value="${proceso.proceso}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Fecha Inicio</label>
                        <input type="date" id="editFecha" value="${fechaISO}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Encargado</label>
                        <input type="text" id="editEncargado" value="${proceso.encargado || ''}" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #374151;">Estado</label>
                        <select id="editEstado" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                            <option value="Pendiente" ${proceso.estado_proceso === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="En Progreso" ${proceso.estado_proceso === 'En Progreso' ? 'selected' : ''}>En Progreso</option>
                            <option value="Completado" ${proceso.estado_proceso === 'Completado' ? 'selected' : ''}>Completado</option>
                            <option value="Pausado" ${proceso.estado_proceso === 'Pausado' ? 'selected' : ''}>Pausado</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button id="btnCancelarProceso"
                                style="padding: 10px 20px; background: #e5e7eb; color: #374151; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            Cancelar
                        </button>
                        <button id="btnGuardarProceso"
                                style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Agregar event listeners
        document.getElementById('btnCancelarProceso').addEventListener('click', function() {
            const editModal = document.getElementById('editProcesoModal');
            if (editModal) {
                editModal.remove();
            }
        });
        
        document.getElementById('btnGuardarProceso').addEventListener('click', function() {
            guardarProcesoEditado(procesoJsonStr);
        });
    } catch (error) {
        console.error('Error al editar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al abrir el formulario de edición',
            confirmButtonColor: '#ef4444',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
    }
}

/**
 * Guardar cambios del proceso editado
 */
async function guardarProcesoEditado(procesoJsonStr) {
    console.log('⏱️ [1] Iniciando guardarProcesoEditado...');
    const inicio = performance.now();
    
    // Prevenir clicks múltiples
    const btnGuardar = document.getElementById('btnGuardarProceso');
    if (btnGuardar.disabled || btnGuardar.dataset.saving === 'true') {
        console.log('❌ Botón ya estaba guardando, bloqueando click duplicado');
        return;
    }
    
    // Marcar como guardando
    btnGuardar.disabled = true;
    btnGuardar.dataset.saving = 'true';
    const textOriginal = btnGuardar.textContent;
    btnGuardar.textContent = 'Guardando...';
    console.log('⏱️ [2] Botón deshabilitado, estado "guardando"');
    
    const procesoOriginal = JSON.parse(procesoJsonStr);
    
    const proceso = document.getElementById('editProceso').value;
    const fecha_inicio = document.getElementById('editFecha').value;
    const encargado = document.getElementById('editEncargado').value;
    const estado_proceso = document.getElementById('editEstado').value;
    console.log('⏱️ [3] Validando campos:', {proceso, fecha_inicio, encargado, estado_proceso});
    
    if (!proceso || !fecha_inicio) {
        console.log('❌ Campos requeridos incompletos');
        Swal.fire({
            icon: 'warning',
            title: 'Campos requeridos',
            text: 'Por favor completa todos los campos',
            confirmButtonColor: '#3b82f6',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
        // Restaurar estado del botón
        btnGuardar.disabled = false;
        btnGuardar.dataset.saving = 'false';
        btnGuardar.textContent = textOriginal;
        return;
    }
    
    try {
        // Buscar el ID del proceso
        console.log('⏱️ [4] Buscando ID del proceso...');
        const buscarResponse = await fetch(`/api/procesos/buscar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: procesoOriginal.numero_pedido,
                proceso: procesoOriginal.proceso
            })
        });
        console.log('⏱️ [5] Respuesta búsqueda recibida:', buscarResponse.status);
        
        if (!buscarResponse.ok) {
            console.log('❌ Proceso no encontrado en búsqueda');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Proceso no encontrado',
                confirmButtonColor: '#ef4444',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                }
            });
            return;
        }
        
        const buscarData = await buscarResponse.json();
        const procesoId = buscarData.id;
        console.log('⏱️ [6] ID del proceso obtenido:', procesoId);
        
        // Actualizar el proceso
        console.log('⏱️ [7] Enviando actualización...');
        const updateResponse = await fetch(`/api/procesos/${procesoId}/editar`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: procesoOriginal.numero_pedido,
                proceso,
                fecha_inicio,
                encargado,
                estado_proceso
            })
        });
        console.log('⏱️ [8] Respuesta actualización recibida:', updateResponse.status);
        
        const result = await updateResponse.json();
        console.log('⏱️ [9] Resultado:', result);
        
        if (result.success) {
            console.log('✅ [10] Proceso actualizado correctamente');
            // Cerrar modal de edición inmediatamente
            console.log('⏱️ [11] Cerrando modal de edición...');
            const editModal = document.getElementById('editProcesoModal');
            if (editModal) {
                editModal.remove();
            }
            
            // Mostrar notificación breve
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'Proceso actualizado correctamente',
                timer: 1500,
                timerProgressBar: true,
                confirmButtonColor: '#10b981',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                },
                didClose: async () => {
                    // Recargar datos en background
                    const modal = document.getElementById('orderTrackingModal');
                    if (modal && modal.style.display === 'flex') {
                        const numeroOrden = document.getElementById('trackingOrderNumber').textContent.replace('#', '');
                        console.log('⏱️ [12] Recargando tracking para orden:', numeroOrden);
                        try {
                            const response = await fetch(`/api/ordenes/${numeroOrden}/procesos`);
                            const data = await response.json();
                            console.log('⏱️ [13] Datos de tracking recibidos, actualizando modal...');
                            displayOrderTrackingWithProcesos(data);
                            const fin = performance.now();
                            console.log(`⏱️ [14] ✅ COMPLETADO EN ${(fin - inicio).toFixed(2)}ms`);
                        } catch (e) {
                            console.error('Error recargando tracking:', e);
                        }
                    }
                }
            });
        } else {
            console.log('❌ [10] Error en respuesta:', result.message);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message,
                confirmButtonColor: '#ef4444',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                }
            });
            // Restaurar estado del botón en caso de error
            btnGuardar.disabled = false;
            btnGuardar.dataset.saving = 'false';
            btnGuardar.textContent = textOriginal;
        }
    } catch (error) {
        console.error('❌ [ERROR]', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar el proceso',
            confirmButtonColor: '#ef4444',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
        // Restaurar estado del botón en caso de error
        btnGuardar.disabled = false;
        btnGuardar.dataset.saving = 'false';
        btnGuardar.textContent = textOriginal;
    }
}

/**
 * Eliminar un proceso (solo admin)
 */
async function eliminarProceso(procesoJsonStr) {
    console.log('🗑️ [1] Iniciando eliminarProceso...');
    const proceso = JSON.parse(procesoJsonStr);
    
    // Mostrar confirmación
    console.log('🗑️ [2] Mostrando modal de confirmación...');
    const resultado = await Swal.fire({
        icon: 'warning',
        title: 'Confirmar eliminación',
        text: `¿Está seguro de que desea eliminar el proceso "${proceso.proceso}"?`,
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        didOpen: (modal) => {
            const backdrop = document.querySelector('.swal2-container');
            if (backdrop) backdrop.style.zIndex = '100000';
            modal.style.zIndex = '100001';
        }
    });
    
    console.log('🗑️ [3] Resultado confirmación:', resultado.isConfirmed);
    if (!resultado.isConfirmed) {
        console.log('❌ Usuario canceló la eliminación');
        return;
    }
    
    try {
        // Primero buscar el ID del proceso
        console.log('🗑️ [4] Buscando ID del proceso...');
        const buscarResponse = await fetch(`/api/procesos/buscar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: proceso.numero_pedido,
                proceso: proceso.proceso
            })
        });
        console.log('🗑️ [5] Respuesta búsqueda:', buscarResponse.status);
        
        if (!buscarResponse.ok) {
            console.log('❌ Proceso no encontrado');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Proceso no encontrado',
                confirmButtonColor: '#ef4444',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                }
            });
            return;
        }
        
        const buscarData = await buscarResponse.json();
        const procesoId = buscarData.id;
        console.log('🗑️ [6] ID del proceso:', procesoId);
        
        // Eliminar el proceso
        console.log('🗑️ [7] Enviando solicitud de eliminación...');
        const deleteResponse = await fetch(`/api/procesos/${procesoId}/eliminar`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                numero_pedido: proceso.numero_pedido
            })
        });
        
        const result = await deleteResponse.json();
        console.log('🗑️ [9] Resultado:', result);
        
        if (result.success) {
            console.log('✅ [10] Proceso eliminado correctamente');
            // Cerrar modal y mostrar notificación breve
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'Proceso eliminado correctamente',
                timer: 1500,
                timerProgressBar: true,
                confirmButtonColor: '#10b981',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                },
                didClose: async () => {
                    console.log('⏱️ [11] Recargando tracking...');
                    // Recargar en background
                    const modal = document.getElementById('orderTrackingModal');
                    if (modal && modal.style.display === 'flex') {
                        // Obtener el número de orden del modal
                        const numeroOrden = document.getElementById('trackingOrderNumber').textContent.replace('#', '');
                        console.log('⏱️ [12] Recargando para orden:', numeroOrden);
                        try {
                            const response = await fetch(`/api/ordenes/${numeroOrden}/procesos`);
                            const data = await response.json();
                            displayOrderTrackingWithProcesos(data);
                            console.log('✅ [13] Tracking actualizado');
                        } catch (e) {
                            console.error('Error recargando tracking:', e);
                        }
                    }
                }
            });
        } else {
            console.log('❌ [10] Error en respuesta:', result.message);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message,
                confirmButtonColor: '#ef4444',
                didOpen: (modal) => {
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) backdrop.style.zIndex = '100000';
                    modal.style.zIndex = '100001';
                }
            });
        }
    } catch (error) {
        console.error('❌ [ERROR]', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al eliminar el proceso',
            confirmButtonColor: '#ef4444',
            didOpen: (modal) => {
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) backdrop.style.zIndex = '100000';
                modal.style.zIndex = '100001';
            }
        });
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
