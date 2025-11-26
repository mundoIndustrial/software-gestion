/**
 * Utilidades para el cálculo dinámico de días en procesos de producción
 * 
 * Lógica:
 * - Cada proceso solo tiene fecha_inicio
 * - Los días se calculan como: fecha_actual - fecha_anterior
 * - El primer proceso siempre tiene 0 días
 */

/**
 * Calcula los días en área para cada proceso
 * @param {Array} procesos - Array de procesos ordenados por fecha
 * @returns {Array} Array de procesos con dias_en_area calculado
 */
export function calcularDiasEnArea(procesos) {
    if (!Array.isArray(procesos) || procesos.length === 0) {
        return [];
    }

    return procesos.map((proceso, index) => {
        let diasEnArea = 0;

        if (index > 0) {
            const fechaAnterior = new Date(procesos[index - 1].fecha_inicio);
            const fechaActual = new Date(proceso.fecha_inicio);
            
            // Calcular diferencia en días
            const diferenciaMilisegundos = fechaActual - fechaAnterior;
            diasEnArea = Math.ceil(diferenciaMilisegundos / (1000 * 60 * 60 * 24));
            diasEnArea = Math.max(0, diasEnArea); // Evitar números negativos
        }

        return {
            ...proceso,
            dias_en_area: diasEnArea
        };
    });
}

/**
 * Ordena procesos por fecha de inicio
 * @param {Array} procesos - Array de procesos
 * @returns {Array} Array ordenado
 */
export function ordenarProcesosporFecha(procesos) {
    if (!Array.isArray(procesos)) {
        return [];
    }

    return [...procesos].sort((a, b) => {
        const fechaA = new Date(a.fecha_inicio);
        const fechaB = new Date(b.fecha_inicio);
        return fechaA - fechaB;
    });
}

/**
 * Obtiene procesos con días calculados
 * @param {Array} procesos - Array de procesos sin ordenar
 * @returns {Array} Array ordenado y con días calculados
 */
export function obtenerProcesosConDias(procesos) {
    const procesosOrdenados = ordenarProcesosporFecha(procesos);
    return calcularDiasEnArea(procesosOrdenados);
}

/**
 * Calcula el total de días del pedido
 * @param {Array} procesos - Array de procesos con dias_en_area
 * @returns {number} Total de días
 */
export function calcularTotalDias(procesos) {
    if (!Array.isArray(procesos) || procesos.length === 0) {
        return 0;
    }

    const fechaInicio = new Date(procesos[0].fecha_inicio);
    const fechaFin = new Date(procesos[procesos.length - 1].fecha_inicio);
    
    const diferenciaMilisegundos = fechaFin - fechaInicio;
    const totalDias = Math.ceil(diferenciaMilisegundos / (1000 * 60 * 60 * 24));
    
    return Math.max(0, totalDias);
}

/**
 * Formatea una fecha
 * @param {string|Date} fecha - Fecha a formatear
 * @param {string} formato - Formato (dd/mm/yyyy, yyyy-mm-dd, etc)
 * @returns {string} Fecha formateada
 */
export function formatearFecha(fecha, formato = 'dd/mm/yyyy') {
    if (!fecha) return '-';
    
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return '-';

    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const año = date.getFullYear();

    switch (formato) {
        case 'dd/mm/yyyy':
            return `${dia}/${mes}/${año}`;
        case 'yyyy-mm-dd':
            return `${año}-${mes}-${dia}`;
        default:
            return fecha.toString();
    }
}

/**
 * Obtiene el estado visual de un proceso basado en fechas
 * @param {Object} proceso - Objeto del proceso
 * @param {Date} fechaHoy - Fecha actual (default: hoy)
 * @returns {string} Estado: 'completado', 'en_progreso', 'pendiente'
 */
export function obtenerEstadoVisual(proceso, fechaHoy = new Date()) {
    if (!proceso.fecha_inicio) return 'pendiente';
    
    const fechaProceso = new Date(proceso.fecha_inicio);
    
    if (proceso.estado_proceso === 'Completado' || proceso.estado_proceso === 'Entregado') {
        return 'completado';
    }
    
    if (fechaProceso <= fechaHoy && (proceso.estado_proceso === 'En Progreso' || proceso.estado_proceso === 'En Ejecución')) {
        return 'en_progreso';
    }
    
    if (fechaProceso > fechaHoy) {
        return 'pendiente';
    }
    
    return 'completado';
}
