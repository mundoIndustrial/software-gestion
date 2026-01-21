/**
 * Script para modal de seguimiento de bodega
 * Extrae datos de tabla_original_bodega y muestra el timeline de procesos
 * Calcula días hábiles correctamente (sin sabados, domingos y festivos)
 */

let bodegaCurrentTrackingOrder = null;
let bodegaFestivos = [];

/**
 * Cargar festivos colombianos desde API pública
 * Usa la API de festivos de Colombia: https://www.datos.gov.co/
 * Fallback: intenta múltiples APIs disponibles
 */
async function loadBodegaFestivos() {
    console.log('🌐 Intentando cargar festivos desde APIs...\n');
    
    // Intentar con API local primero
    try {
        console.log('    Intentando: GET /api/festivos');
        const response = await fetch('/api/festivos', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            // Manejo flexible del formato de respuesta
            if (data.data && Array.isArray(data.data)) {
                // Formato nuevo: {success: true, data: [...], count: N}
                bodegaFestivos = data.data.map(f => {
                    if (typeof f === 'string') {
                        return f.split('T')[0];
                    } else if (f.fecha) {
                        return f.fecha.split('T')[0];
                    }
                    return f;
                });
            } else if (Array.isArray(data)) {
                // Formato antiguo: array directo
                bodegaFestivos = data.map(f => {
                    if (typeof f === 'string') {
                        return f.split('T')[0];
                    } else if (f.fecha) {
                        return f.fecha.split('T')[0];
                    }
                    return f;
                });
            }
            
            if (bodegaFestivos.length > 0) {
                console.log('   Festivos cargados desde API local: ' + bodegaFestivos.length);
                console.log('     Primer festivo: ' + bodegaFestivos[0]);
                console.log('     Último festivo: ' + bodegaFestivos[bodegaFestivos.length-1] + '\n');
                return;
            }
        }
    } catch (error) {
        console.log('   API local no disponible: ' + error.message);
    }
    
    //  Intentar con API pública de zolv.co
    try {
        console.log('    Intentando: https://api.zolv.co/api/holidays');
        const response = await fetch('https://api.zolv.co/api/holidays', {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            bodegaFestivos = data
                .filter(f => f.country === 'CO')
                .map(f => f.date.split('T')[0]);
            
            if (bodegaFestivos.length > 0) {
                console.log('   Festivos cargados desde zolv.co: ' + bodegaFestivos.length);
                console.log('     Primer festivo: ' + bodegaFestivos[0]);
                console.log('     Último festivo: ' + bodegaFestivos[bodegaFestivos.length-1] + '\n');
                return;
            }
        }
    } catch (error) {
        console.log('   zolv.co no disponible: ' + error.message);
    }
    
    //  Intentar con API Calendarific
    try {
        console.log('    Intentando: https://api.calendarific.com/v2/holidays');
        const year = new Date().getFullYear();
        const response = await fetch(`https://api.calendarific.com/v2/holidays?country=CO&year=${year}&api_key=public`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.response && data.response.holidays) {
                bodegaFestivos = data.response.holidays.map(h => h.date.split('T')[0]);
                
                if (bodegaFestivos.length > 0) {
                    console.log('   Festivos cargados desde Calendarific: ' + bodegaFestivos.length);
                    console.log('     Primer festivo: ' + bodegaFestivos[0]);
                    console.log('     Último festivo: ' + bodegaFestivos[bodegaFestivos.length-1] + '\n');
                    return;
                }
            }
        }
    } catch (error) {
        console.log('   Calendarific no disponible: ' + error.message);
    }
    
    // 4️⃣ Fallback: Usar festivos comunes de Colombia si todas las APIs fallan
    console.log('  4️⃣  Usando festivos por defecto (fallback)');
    const year = new Date().getFullYear();
    
    // Festivos fijos y móviles aproximados para 2024-2026
    const festivosPorAño = {
        2024: [
            '2024-01-01', '2024-01-08', '2024-03-28', '2024-03-29',
            '2024-05-01', '2024-06-03', '2024-06-10', '2024-07-01',
            '2024-07-29', '2024-08-07', '2024-08-15', '2024-11-01',
            '2024-12-08', '2024-12-25'
        ],
        2025: [
            '2025-01-01', '2025-01-08', '2025-04-17', '2025-04-18',
            '2025-05-01', '2025-06-02', '2025-06-09', '2025-06-30',
            '2025-07-01', '2025-08-07', '2025-08-15', '2025-11-01',
            '2025-12-08', '2025-12-25'
        ],
        2026: [
            '2026-01-01', '2026-01-12', '2026-04-09', '2026-04-10',
            '2026-05-01', '2026-05-25', '2026-06-01', '2026-06-22',
            '2026-07-01', '2026-08-07', '2026-08-17', '2026-11-02',
            '2026-12-08', '2026-12-25'
        ]
    };
    
    bodegaFestivos = festivosPorAño[year] || festivosPorAño[2025];
    console.log('📅 Usando festivos por defecto:', bodegaFestivos.length);
}

/**
 * Abrir modal de seguimiento de bodega
 */
function openBodegaTrackingModal(pedido) {
    console.log(' Abriendo tracking de bodega para pedido:', pedido);
    bodegaCurrentTrackingOrder = pedido;
    
    // Cargar festivos primero, luego datos
    loadBodegaFestivos().then(() => {
        return loadBodegaTrackingData(pedido);
    }).then(() => {
        const modal = document.getElementById('bodegaTrackingModal');
        if (modal) {
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
        }
    }).catch(error => {
        console.error(' Error al cargar tracking:', error);
        alert('Error al cargar los datos del seguimiento');
    });
}

/**
 * Cerrar modal de seguimiento de bodega
 */
function closeBodegaTrackingModal() {
    const modal = document.getElementById('bodegaTrackingModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
    }
    bodegaCurrentTrackingOrder = null;
}

/**
 * Cargar datos de seguimiento desde tabla_original_bodega
 */
async function loadBodegaTrackingData(pedido) {
    try {
        const response = await fetch(`/bodega/${pedido}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar datos del pedido');
        }
        
        const orden = await response.json();
        console.log(' Orden completa:', orden);
        
        // Actualizar información básica
        document.getElementById('bodegaTrackingOrderNumber').textContent = `#${orden.pedido || '-'}`;
        document.getElementById('bodegaTrackingOrderClient').textContent = orden.cliente || '-';
        document.getElementById('bodegaTrackingOrderStatus').textContent = orden.estado || '-';
        document.getElementById('bodegaTrackingOrderArea').textContent = orden.area || '-';
        
        // Calcular suma total de días de todas las áreas
        const totalDias = calculateTotalDias(orden);
        document.getElementById('bodegaTrackingTotalDays').textContent = totalDias;
        
        // Construir timeline de procesos
        buildBodegaProcessTimeline(orden);
        
    } catch (error) {
        console.error('Error cargando datos:', error);
        throw error;
    }
}

/**
 * Calcular la suma total de días hábiles de todas las áreas
 * Excluyendo sábados, domingos y festivos colombianos
 * 
 * IMPORTANTE: Busca la SIGUIENTE fecha válida (puede no ser consecutiva)
 * Ejemplo: Si Creación=19/05 e Inventario=null, busca la próxima fecha válida (Corte=13/06)
 */
function calculateTotalDias(orden) {
    // Mapeo correcto de los 14 procesos en orden secuencial
    const procesos = [
        { nombre: 'Creación de Orden', fecha: 'fecha_de_creacion_de_orden' },
        { nombre: 'Inventario', fecha: 'inventario' },
        { nombre: 'Insumos y Telas', fecha: 'insumos_y_telas' },
        { nombre: 'Corte', fecha: 'corte' },
        { nombre: 'Bordado', fecha: 'bordado' },
        { nombre: 'Estampado', fecha: 'estampado' },
        { nombre: 'Costura', fecha: 'costura' },
        { nombre: 'Reflectivo', fecha: 'reflectivo' },
        { nombre: 'Lavandería', fecha: 'lavanderia' },
        { nombre: 'Arreglos', fecha: 'arreglos' },
        { nombre: 'Marras', fecha: 'marras' },
        { nombre: 'Control de Calidad', fecha: 'control_de_calidad' },
        { nombre: 'Entrega', fecha: 'entrega' },
        { nombre: 'Despachos', fecha: 'despachos' }
    ];
    
    let totalDiasHabiles = 0;
    
    // Iterar cada proceso y buscar la siguiente fecha válida
    for (let i = 0; i < procesos.length; i++) {
        const procesoActual = procesos[i];
        const fechaInicio = orden[procesoActual.fecha];
        
        // Solo si el proceso actual tiene fecha
        if (fechaInicio) {
            // Buscar la siguiente fecha válida (puede no ser el siguiente en la lista)
            for (let j = i + 1; j < procesos.length; j++) {
                const procesoSiguiente = procesos[j];
                const fechaFin = orden[procesoSiguiente.fecha];
                
                // Si encontramos una fecha válida, calcular días y saltar al siguiente proceso con fecha
                if (fechaFin) {
                    const diasHabiles = calculateBusinessDays(fechaInicio, fechaFin, bodegaFestivos);
                    totalDiasHabiles += diasHabiles;
                    console.log(` ${procesoActual.nombre} → ${procesoSiguiente.nombre}: ${diasHabiles} días hábiles`);
                    break;  // Salir del loop interno después de encontrar la siguiente fecha
                }
            }
        }
    }
    
    
    // Si no se calculó nada, usar fallback: sumar campos de días registrados
    if (totalDiasHabiles === 0) {
        console.log('  No hay pares de fechas con ambas válidas, usando campos de días registrados');
        
        const diasFields = [
            'dias_orden',
            'dias_inventario',
            'dias_insumos',
            'dias_corte',
            'dias_bordado',
            'dias_estampado',
            'dias_costura',
            'total_de_dias_reflectivo',
            'dias_lavanderia',
            'total_de_dias_arreglos',
            'total_de_dias_marras',
            'dias_c_c',
            'dias_entrega',
            'dias_despachos'
        ];
        
        diasFields.forEach(field => {
            const value = orden[field];
            if (value) {
                const numValue = parseInt(value, 10);
                if (!isNaN(numValue)) {
                    totalDiasHabiles += numValue;
                    console.log(`  ${field}: ${numValue} días (valor guardado)`);
                }
            }
        });
    }
    
    console.log(` TOTAL DE DÍAS HÁBILES: ${totalDiasHabiles}`);
    return totalDiasHabiles;
}

/**
 * Calcula días hábiles entre dos fechas (excluyendo fines de semana y festivos)
 * Incluye ambas fechas (inicio y fin)
 * 
 * IMPORTANTE: Esta función cuenta TODOS los días hábiles entre las dos fechas inclusive.
 * NO resta 1 al final.
 */
function calculateBusinessDays(startDate, endDate, festivos = []) {
    if (!startDate || !endDate) return 0;

    const start = typeof startDate === 'string' ? new Date(startDate + 'T00:00:00') : new Date(startDate);
    const end = typeof endDate === 'string' ? new Date(endDate + 'T00:00:00') : new Date(endDate);

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

    // Contar desde la fecha de inicio (inclusive)
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
 * Construir el timeline de procesos desde tabla_original_bodega
 * Agrupa por área con sus fechas y detalles
 * Solo muestra áreas que tienen fecha registrada
 * Calcula correctamente los días hábiles entre procesos
 */
function buildBodegaProcessTimeline(orden) {
    const container = document.getElementById('bodegaTrackingTimelineContainer');
    container.innerHTML = '';
    
    console.log('🏗️ Construyendo timeline...');
    console.log(`📅 Festivos disponibles: ${bodegaFestivos.length}`);
    
    // Mapeo de los 14 procesos en orden secuencial
    const procesos = [
        { nombre: 'Creación Orden', fecha: 'fecha_de_creacion_de_orden', encargado: 'encargado_orden' },
        { nombre: 'Inventario', fecha: 'inventario', encargado: 'encargados_inventario' },
        { nombre: 'Insumos', fecha: 'insumos_y_telas', encargado: 'encargados_insumos' },
        { nombre: 'Corte', fecha: 'corte', encargado: 'encargados_de_corte' },
        { nombre: 'Bordado', fecha: 'bordado', encargado: 'bordado' },
        { nombre: 'Estampado', fecha: 'estampado', encargado: 'encargados_estampado' },
        { nombre: 'Costura', fecha: 'costura', encargado: 'modulo' },
        { nombre: 'Reflectivo', fecha: 'reflectivo', encargado: 'encargado_reflectivo' },
        { nombre: 'Lavandería', fecha: 'lavanderia', encargado: 'encargado_lavanderia' },
        { nombre: 'Arreglos', fecha: 'arreglos', encargado: 'encargado_arreglos' },
        { nombre: 'Marras', fecha: 'marras', encargado: 'encargados_marras' },
        { nombre: 'Control-Calidad', fecha: 'control_de_calidad', encargado: 'encargados_calidad' },
        { nombre: 'Entrega', fecha: 'entrega', encargado: 'encargados_entrega' },
        { nombre: 'Despachos', fecha: 'despacho', encargado: null }
    ];
    
    // Filtrar solo procesos que tienen fecha registrada
    const procesosConFecha = procesos.filter(item => {
        const fechaValue = orden[item.fecha];
        return fechaValue !== null && fechaValue !== undefined && fechaValue !== '';
    });
    
    console.log(`✓ Procesos con fecha: ${procesosConFecha.length}`);
    
    // Si no hay procesos con fecha, mostrar mensaje
    if (procesosConFecha.length === 0) {
        container.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #9ca3af; font-size: 14px;">
                No hay procesos registrados para este pedido
            </div>
        `;
        return;
    }
    
    // Construir timeline mostrando cada proceso y calculando días hasta el siguiente
    procesosConFecha.forEach((proceso, index) => {
        const fechaInicio = orden[proceso.fecha];
        const encargadoValue = proceso.encargado ? (orden[proceso.encargado] || '-') : '-';
        
        // Calcular días hábiles hasta el siguiente proceso con fecha
        let diasHabiles = 0;
        let siguienteProceso = null;
        
        for (let j = procesos.indexOf(proceso) + 1; j < procesos.length; j++) {
            const procesoSiguiente = procesos[j];
            const fechaFin = orden[procesoSiguiente.fecha];
            
            if (fechaFin) {
                diasHabiles = calculateBusinessDays(fechaInicio, fechaFin, bodegaFestivos);
                siguienteProceso = procesoSiguiente.nombre;
                console.log(` ${proceso.nombre} → ${siguienteProceso}: ${diasHabiles} días hábiles`);
                break;
            }
        }
        
        const timelineItem = createBodegaTimelineItem(
            index + 1,
            proceso.nombre,
            true, // Siempre completado porque tiene fecha
            fechaInicio,
            encargadoValue,
            diasHabiles > 0 ? diasHabiles : '-'
        );
        
        container.appendChild(timelineItem);
    });
}

/**
 * Crear elemento de timeline para un proceso
 */
function createBodegaTimelineItem(number, processName, isCompleted, fecha, encargado, dias) {
    const item = document.createElement('div');
    item.className = 'bodega-tracking-timeline-item';
    
    // Formatear fecha si existe
    let fechaFormato = '-';
    if (fecha) {
        try {
            const dateObj = new Date(fecha);
            if (!isNaN(dateObj)) {
                fechaFormato = dateObj.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
            }
        } catch (e) {
            fechaFormato = fecha || '-';
        }
    }
    
    const dotClass = isCompleted ? 'completed' : 'pending';
    const checkIcon = isCompleted ? 
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"></polyline></svg>' :
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>';
    
    item.innerHTML = `
        <div class="bodega-tracking-timeline-dot ${dotClass}">
            ${checkIcon}
        </div>
        <div class="bodega-tracking-area-card">
            <div class="bodega-tracking-area-name">
                ${number}. ${processName}
            </div>
            <div class="bodega-tracking-area-details">
                <div class="bodega-tracking-detail-row">
                    <span class="bodega-tracking-detail-label">Fecha</span>
                    <span class="bodega-tracking-detail-value">${fechaFormato}</span>
                </div>
                <div class="bodega-tracking-detail-row">
                    <span class="bodega-tracking-detail-label">Encargado</span>
                    <span class="bodega-tracking-detail-value">${encargado || '-'}</span>
                </div>
                <div class="bodega-tracking-detail-row">
                    <span class="bodega-tracking-detail-label">Días</span>
                    <span class="bodega-tracking-detail-value">${dias || '-'}</span>
                </div>
            </div>
        </div>
    `;
    
    return item;
}

/**
 * Inicializar event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log(' Bodega Tracking Script Initialized');
    
    // Cerrar modal con botón
    const closeBtn = document.getElementById('closeBodegaTrackingModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeBodegaTrackingModal);
    }
    
    // Cerrar modal con overlay
    const overlay = document.getElementById('bodegaTrackingModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeBodegaTrackingModal);
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('bodegaTrackingModal');
            if (modal && modal.style.display === 'flex') {
                closeBodegaTrackingModal();
            }
        }
    });
});

// Hacer funciones globales
globalThis.openBodegaTrackingModal = openBodegaTrackingModal;
globalThis.closeBodegaTrackingModal = closeBodegaTrackingModal;
