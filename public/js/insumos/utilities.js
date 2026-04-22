/**
 * UTILIDADES COMPARTIDAS - Insumos Materiales
 * Funciones reutilizables para toasts, helpers, etc.
 */

/**
 * Muestra una notificación Toast mejorada
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de toast: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duración en ms (default: 6000)
 */
function showToast(message, type = 'success', duration = 6000) {
    const toastContainer = document.getElementById('toastContainer');
    
    // Configuración de estilos por tipo
    const config = {
        success: {
            bgColor: '#10b981',
            textColor: '#fff',
            icon: '✓',
            progressColor: '#059669'
        },
        error: {
            bgColor: '#ef4444',
            textColor: '#fff',
            icon: '⚠',
            progressColor: '#dc2626'
        },
        warning: {
            bgColor: '#f59e0b',
            textColor: '#fff',
            icon: '!',
            progressColor: '#d97706'
        },
        info: {
            bgColor: '#3b82f6',
            textColor: '#fff',
            icon: 'ℹ',
            progressColor: '#2563eb'
        }
    };
    
    const cfg = config[type] || config.success;
    
    // Crear elemento de toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${cfg.bgColor};
        color: ${cfg.textColor};
        padding: 16px 20px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
        max-width: 400px;
        animation: toastSlideIn 0.4s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    `;
    
    // Convertir saltos de línea
    const formattedMessage = message.replace(/\n/g, '<br>');
    
    toast.innerHTML = `
        <span style="font-size: 20px; flex-shrink: 0;">${cfg.icon}</span>
        <div style="flex: 1; font-size: 14px; line-height: 1.4;">${formattedMessage}</div>
        <div style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${cfg.progressColor}; border-radius: 0 0 12px 12px; animation: toastProgress ${duration}ms linear forwards; width: 100%;"></div>
    `;
    
    toast.setAttribute('data-toast', 'true');
    
    // Función para cerrar el toast
    function closeToast() {
        toast.style.animation = 'toastSlideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }
    
    toast.addEventListener('close', closeToast);
    
    toastContainer.appendChild(toast);
    
    // Auto-remover después del tiempo
    const autoClose = setTimeout(closeToast, duration);
    
    // Pausar al hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(autoClose);
        const progressBar = toast.querySelector('div:last-child');
        if (progressBar) progressBar.style.animationPlayState = 'paused';
    });
    
    toast.addEventListener('mouseleave', () => {
        const remaining = duration - (Date.now() - startTime);
        const progressBar = toast.querySelector('div:last-child');
        if (progressBar) progressBar.style.animationPlayState = 'running';
        setTimeout(closeToast, remaining);
    });
}

/**
 * Función debounce para evitar múltiples llamadas rápidas
 * @param {Function} func - Función a ejecutar
 * @param {number} wait - Tiempo de espera en ms
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Calcula los días de demora entre dos fechas (VERSIÓN MEJORADA)
 * Utiliza cálculo local sin dependencia de endpoint
 * 
 * @param {string} fechaInicio - Fecha en formato YYYY-MM-DD
 * @param {string} fechaFin - Fecha en formato YYYY-MM-DD
 * @param {Function} callback - Callback con el resultado en días
 */
function calcularDiasLaborales(fechaInicio, fechaFin, callback) {
    if (!fechaInicio || !fechaFin) {
        if (callback) callback(0);
        return;
    }

    try {
        const dias = calcularDiasHabiles(fechaInicio, fechaFin);
        if (callback) callback(dias);
        return dias;
    } catch (error) {
        console.error('[calcularDiasLaborales] Error:', error);
        if (callback) callback(0);
    }
}

/**
 * Festivos Colombianos - Sistema de dos alternativas
 * 1. Intenta obtener de API externa (más actualizado)
 * 2. Fallback a festivos hardcodeados locales
 * 3. Cachea resultados en localStorage
 */
const FESTIVOS_COLOMBIA = {
    /**
     * Obtener festivos con preferencia: API externa → Local
     * @param {number} anio - Año a consultar
     * @returns {Promise<Array>} Array de fechas en formato YYYY-MM-DD
     */
    obtenerConFallback: async function(anio) {
        const cacheKey = `festivos_${anio}`;
        
        // 1. Verificar si está en cache local
        const cached = localStorage.getItem(cacheKey);
        if (cached) {
            return JSON.parse(cached);
        }

        try {
            // 2. Intentar API externa (Nager.Date - Gratuita, sin API key)
            const festivos = await this.obtenerDelServicioExterno(anio);

            // Cachear por 30 días
            localStorage.setItem(cacheKey, JSON.stringify(festivos));

            return festivos;
        } catch (error) {
            
            // 3. Fallback a festivos locales
            const festivosLocales = this.obtenerLocales(anio);
            localStorage.setItem(cacheKey, JSON.stringify(festivosLocales));
            
            return festivosLocales;
        }
    },

    /**
     * Obteniendo festivos de API externa (Nager.Date)
     * Incluye festivos colombianos públicos
     * @param {number} anio
     * @returns {Promise<Array>}
     */
    obtenerDelServicioExterno: async function(anio) {
        const response = await fetch(`https://date.nager.at/api/v3/publicholidays/${anio}/CO`, {
            method: 'GET',
            signal: AbortSignal.timeout(3000) // Timeout 3 segundos
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status} - API no disponible`);
        }

        const data = await response.json();

        // Mapear respuesta de API a formato YYYY-MM-DD
        return data
            .map(holiday => holiday.date)
            .sort();
    },

    /**
     * Festivos colombianos locales (7 fijos + movibles)
     * Usados como fallback si la API falla
     * @param {number} anio
     * @returns {Array} Array de fechas en formato YYYY-MM-DD
     */
    obtenerLocales: function(anio) {
        const festivos = [
            // Festivos fijos
            `${anio}-01-01`, // Año Nuevo
            `${anio}-05-01`, // Día del Trabajo
            `${anio}-07-01`, // Día de la Independencia
            `${anio}-07-20`, // Grito de Independencia
            `${anio}-08-07`, // Batalla de Boyacá
            `${anio}-12-08`, // Inmaculada Concepción
            `${anio}-12-25`, // Navidad
        ];

        // TODO: Agregar festivos movibles si es necesario:
        // - Viernes Santo
        // - Ascensión
        // - Corpus Christi
        // - Sagrado Corazón

        return festivos.sort();
    },

    /**
     * Wrapper sincrónico para compatibilidad (uso en calcularDiasHabiles)
     * Nota: Retorna solo locales (no espera API)
     * @param {number} anio
     * @returns {Array}
     */
    obtener: function(anio) {
        return this.obtenerLocales(anio);
    },

    /**
     * Limpiar cache (útil para debugging)
     */
    limpiarCache: function() {
        const keys = Object.keys(localStorage).filter(k => k.startsWith('festivos_'));
        keys.forEach(k => localStorage.removeItem(k));
    }
};

/**
 * Inicializar festivos desde API (ejecutar al cargar la página)
 * Precarga festivos del año actual y siguiente
 */
async function inicializarFestivos() {
    const ahora = new Date();
    const anoActual = ahora.getFullYear();
    const anoProximo = anoActual + 1;

    try {
        // Precargar ambos años en paralelo
        await Promise.all([
            FESTIVOS_COLOMBIA.obtenerConFallback(anoActual),
            FESTIVOS_COLOMBIA.obtenerConFallback(anoProximo)
        ]);
    } catch (error) {
        // Error en inicialización (usando locales)
    }
}

/**
 * Calcula días hábiles entre dos fechas (VERSIÓN SINCRÓNA - Rápida)
 * Usa festivos locales (no espera API)
 * Excluye sábados, domingos y festivos colombianos
 * 
 * @param {string} fechaInicio - Formato YYYY-MM-DD
 * @param {string} fechaFin - Formato YYYY-MM-DD
 * @returns {number} Días hábiles calculados
 */
function calcularDiasHabiles(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    if (inicio.toDateString() === fin.toDateString()) return 0;

    // Obtener festivos de ambos años si es necesario
    const festivos = new Set([
        ...FESTIVOS_COLOMBIA.obtener(inicio.getFullYear()),
        ...(inicio.getFullYear() !== fin.getFullYear() ? 
            FESTIVOS_COLOMBIA.obtener(fin.getFullYear()) : [])
    ]);

    let diasHabiles = 0;
    const actual = new Date(inicio);

    // Iterar desde inicio hasta fin
    while (actual <= fin) {
        const dayOfWeek = actual.getDay();
        const dateString = actual.toISOString().split('T')[0]; // YYYY-MM-DD

        // No es sábado (6) ni domingo (0) y no es festivo
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !festivos.has(dateString)) {
            diasHabiles++;
        }

        actual.setDate(actual.getDate() + 1);
    }

    // Restar 1 porque no se cuenta el día de inicio (como en backend)
    return Math.max(0, diasHabiles - 1);
}

/**
 * Calcula días hábiles entre dos fechas (VERSIÓN ASINCRÓNA - Precisa)
 * Intenta obtener festivos de API externa, fallback a locales
 * 
 * @param {string} fechaInicio - Formato YYYY-MM-DD
 * @param {string} fechaFin - Formato YYYY-MM-DD
 * @returns {Promise<number>} Días hábiles calculados
 */
async function calcularDiasHabilesAsync(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    if (inicio.toDateString() === fin.toDateString()) return 0;

    // Obtener festivos con fallback (intenta API externa primero)
    const festivosInicio = await FESTIVOS_COLOMBIA.obtenerConFallback(inicio.getFullYear());
    const festivosFin = inicio.getFullYear() !== fin.getFullYear() ? 
        await FESTIVOS_COLOMBIA.obtenerConFallback(fin.getFullYear()) : [];
    
    const festivos = new Set([...festivosInicio, ...festivosFin]);

    let diasHabiles = 0;
    const actual = new Date(inicio);

    // Iterar desde inicio hasta fin
    while (actual <= fin) {
        const dayOfWeek = actual.getDay();
        const dateString = actual.toISOString().split('T')[0]; // YYYY-MM-DD

        // No es sábado (6) ni domingo (0) y no es festivo
        if (dayOfWeek !== 0 && dayOfWeek !== 6 && !festivos.has(dateString)) {
            diasHabiles++;
        }

        actual.setDate(actual.getDate() + 1);
    }

    // Restar 1 porque no se cuenta el día de inicio (como en backend)
    return Math.max(0, diasHabiles - 1);
}

/**
 * Obtiene el estado y color basado en días de demora
 * @param {number} dias
 * @returns {Object} {estado, clase_bg, clase_text, color_hex, texto}
 */
function getEstadoDemora(dias) {
    if (dias <= 5) {
        return {
            estado: 'rapido',
            clase_bg: 'bg-green-100',
            clase_text: 'text-green-700',
            color_hex: '#10b981'
        };
    } else if (dias <= 10) {
        return {
            estado: 'normal',
            clase_bg: 'bg-blue-100',
            clase_text: 'text-blue-700',
            color_hex: '#3b82f6'
        };
    } else if (dias <= 20) {
        return {
            estado: 'lento',
            clase_bg: 'bg-orange-100',
            clase_text: 'text-orange-700',
            color_hex: '#f59e0b'
        };
    } else {
        return {
            estado: 'critico',
            clase_bg: 'bg-red-100',
            clase_text: 'text-red-700',
            color_hex: '#ef4444'
        };
    }
}

/**
 * Calcula la demora de forma asincróna (RECOMENDADO)
 * Usa API externa para festivos si está disponible
 * 
 * @param {string} fechaPedido - Fecha en formato YYYY-MM-DD
 * @param {string} fechaLlegada - Fecha en formato YYYY-MM-DD
 * @returns {Promise<Object>} Promesa con información de demora
 */
async function calcularDemoraAsync(fechaPedido, fechaLlegada) {
    const fallback = {
        dias: 0,
        estado: 'sin_datos',
        texto: 'Sin datos',
        clase_bg: 'bg-gray-100',
        clase_text: 'text-gray-700',
        color_hex: '#6b7280'
    };

    if (!fechaPedido || !fechaLlegada) {
        return fallback;
    }

    try {
        // Calcular días hábiles usando API externa (con fallback a locales)
        const days = await calcularDiasHabilesAsync(fechaPedido, fechaLlegada);
        const estadoDemora = getEstadoDemora(days);

        return {
            dias: days,
            estado: estadoDemora.estado,
            texto: days === 1 ? '1 día' : `${days} días`,
            clase_bg: estadoDemora.clase_bg,
            clase_text: estadoDemora.clase_text,
            color_hex: estadoDemora.color_hex
        };
    } catch (error) {
        return fallback;
    }
}

/**
 * Obtiene el color de badge basado en días de demora (DEPRECATED)
 * NOTA: La lógica ahora está en el backend (DiasDemora ValueObject)
 * @param {number} dias - Número de días
 * @returns {Object} Objeto con bgColor y textColor
 */
function getColorByDias(dias) {
    // Mantener para compatibilidad con código existente
    // pero la fuente de verdad es el backend
    if (dias <= 5) {
        return { bgColor: 'bg-green-100', textColor: 'text-green-700' };
    } else if (dias <= 10) {
        return { bgColor: 'bg-yellow-100', textColor: 'text-yellow-700' };
    } else if (dias <= 20) {
        return { bgColor: 'bg-orange-100', textColor: 'text-orange-700' };
    } else {
        return { bgColor: 'bg-red-100', textColor: 'text-red-700' };
    }
}

/**
 * Sanitiza un string para usarlo como ID HTML
 * @param {string} str - String a sanitizar
 * @returns {string} String sanitizado
 */
function sanitizeForId(str) {
    return str.replace(/\s+/g, '_').toLowerCase()
        .replace(/[^a-z0-9_-]/g, '')
        .slice(0, 50);
}

/**
 * Formatea una fecha a string legible
 * @param {string} dateString - Fecha en formato YYYY-MM-DD
 * @returns {string} Fecha formateada
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

/**
 * Copia texto al portapapeles
 * @param {string} text - Texto a copiar
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('¡Copiado al portapapeles!', 'success', 2000);
    } catch (err) {
        console.error('Error al copiar:', err);
        showToast('Error al copiar', 'error');
    }
}

/**
 * Espera un tiempo determinado
 * @param {number} ms - Milisegundos a esperar
 * @returns {Promise} Promise que se resuelve después de ms
 */
function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Muestra un diálogo de confirmación
 * @param {string} title - Título del diálogo
 * @param {string} message - Mensaje
 * @param {Object} options - Opciones adicionales
 */
function showConfirmDialog(title, message, options = {}) {
    return Swal.fire({
        title: title,
        html: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#ef4444',
        confirmButtonText: options.confirmText || 'Confirmé',
        cancelButtonText: options.cancelText || 'Cancelar',
        ...options
    });
}

export {
    showToast,
    debounce,
    inicializarFestivos,
    calcularDemoraAsync,
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog
};
