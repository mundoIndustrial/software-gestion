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
 * Calcula los días de demora entre dos fechas usando el backend
 * Tiene fallback si el endpoint no está disponible
 * @param {string} fechaInicio - Fecha en formato YYYY-MM-DD
 * @param {string} fechaFin - Fecha en formato YYYY-MM-DD
 * @param {Function} callback - Callback con el resultado
 * @deprecated Usar calcularDemoraAsync() para operaciones asincrónas
 */
function calcularDiasLaborales(fechaInicio, fechaFin, callback) {
    // Fallback: si no hay fechas, retornar 0
    if (!fechaInicio || !fechaFin) {
        if (callback) callback(0);
        return;
    }

    // Llamada al backend (ver calcularDemoraAsync para async/await)
    fetch('/api/insumos/calcular-demora', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            fecha_pedido: fechaInicio,
            fecha_llegada: fechaFin
        }),
        signal: AbortSignal.timeout(5000) // Timeout de 5 segundos
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (callback) callback(data.dias !== undefined ? data.dias : 0);
        return data.dias || 0;
    })
    .catch(error => {
        console.warn('[calcularDiasLaborales] Fallback: endpoint no disponible', error.message);
        if (callback) callback(0);
    });
}

/**
 * Calcula la demora de forma asincróna (Recomendado)
 * Con fallback seguro si el endpoint no está disponible
 * @param {string} fechaPedido - Fecha en formato YYYY-MM-DD
 * @param {string} fechaLlegada - Fecha en formato YYYY-MM-DD
 * @returns {Promise<Object>} Promesa con información de demora (o valor por defecto si falla)
 */
async function calcularDemoraAsync(fechaPedido, fechaLlegada) {
    // Fallback si no hay fechas
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
        const response = await fetch('/api/insumos/calcular-demora', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                fecha_pedido: fechaPedido,
                fecha_llegada: fechaLlegada
            }),
            signal: AbortSignal.timeout(5000) // Timeout de 5 segundos
        });

        if (!response.ok) {
            console.warn('[calcularDemoraAsync] Response no OK:', response.status);
            return fallback;
        }
        
        const data = await response.json();
        
        // Validar que la respuesta tenga la estructura esperada
        if (data.success && data.dias !== undefined) {
            return {
                dias: data.dias,
                estado: data.estado || 'normal',
                texto: data.texto || `${data.dias} días`,
                clase_bg: data.clase_bg || 'bg-gray-100',
                clase_text: data.clase_text || 'text-gray-700',
                color_hex: data.color_hex || '#6b7280',
                data: data.data
            };
        }
        
        return fallback;
    } catch (error) {
        console.warn('[calcularDemoraAsync] Error (usando fallback):', error.message);
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
    calcularDiasLaborales,
    getColorByDias,
    sanitizeForId,
    formatDate,
    copyToClipboard,
    wait,
    showConfirmDialog
};
