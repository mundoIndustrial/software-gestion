/**
 * Toast Notifications - Global Module
 * Centralizes all toast notifications with proper z-index for modals
 */

let toastContainer = null;
let activeToasts = [];
const MAX_TOASTS = 5;
const TOAST_DURATION = {
    success: 3000,   // 3 segundos
    error: 5000,     // 5 segundos (más tiempo para leer)
    info: 4000       // 4 segundos
};

function initializeToastContainer() {
    if (toastContainer && document.body.contains(toastContainer)) {
        return toastContainer;
    }
    
    // Remover el contenedor anterior si existe
    const old = document.getElementById('toast-container');
    if (old) old.remove();
    
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999 !important;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    `;
    
    // Agregar DESPUÉS del body para que sea el último elemento
    document.body.appendChild(toastContainer);
    
    return toastContainer;
}

window.showToast = function(message, type = 'success') {
    // Inicializar contenedor si no existe
    const container = initializeToastContainer();
    
    // Verificar si ya existe un toast igual
    const duplicate = activeToasts.find(t => t.message === message && t.type === type);
    if (duplicate) {
        // Si existe, solo actualizar su tiempo de vida
        clearTimeout(duplicate.timeout);
        const duration = TOAST_DURATION[type] || 3000;
        duplicate.timeout = setTimeout(() => removeToast(duplicate), duration);
        return;
    }
    
    // Si hay demasiados toasts, remover el más antiguo (pero no si es error)
    if (activeToasts.length >= MAX_TOASTS) {
        // Buscar el primer toast que NO sea error
        let toastToRemove = activeToasts.find(t => t.type !== 'error');
        
        // Si todos son errores, entonces remover el más antiguo
        if (!toastToRemove) {
            toastToRemove = activeToasts[0];
        }
        
        if (toastToRemove) {
            const index = activeToasts.indexOf(toastToRemove);
            activeToasts.splice(index, 1);
            
            if (toastToRemove.element && toastToRemove.element.parentNode) {
                toastToRemove.element.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toastToRemove.element.parentNode) {
                        toastToRemove.element.remove();
                    }
                }, 300);
            }
            clearTimeout(toastToRemove.timeout);
        }
    }
    
    // Crear toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        font-size: 0.875rem;
        font-weight: 500;
        animation: slideInRight 0.3s ease-out;
        min-width: 300px;
        max-width: 400px;
        pointer-events: all;
    `;
    
    // Agregar icono
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 1.2rem; font-weight: bold;">${icon}</span>
            <span>${message}</span>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Asegurar que el contenedor esté siempre visible y al frente
    container.style.zIndex = '999999';
    container.style.position = 'fixed';
    
    // Crear objeto para rastrear el toast
    const toastObj = {
        message,
        type,
        element: toast,
        timeout: null,
        createdAt: Date.now()
    };
    
    // Agregar a la lista de toasts activos
    activeToasts.push(toastObj);
    
    // Programar eliminación con duración según tipo
    const duration = TOAST_DURATION[type] || 3000;
    toastObj.timeout = setTimeout(() => removeToast(toastObj), duration);
    
    // Permitir cerrar haciendo clic
    toast.style.cursor = 'pointer';
    toast.addEventListener('click', () => removeToast(toastObj));
};

function removeToast(toastObj) {
    if (!toastObj || !toastObj.element) return;
    
    // Remover de la lista activa
    const index = activeToasts.indexOf(toastObj);
    if (index > -1) {
        activeToasts.splice(index, 1);
    }
    
    // Limpiar timeout
    if (toastObj.timeout) {
        clearTimeout(toastObj.timeout);
    }
    
    // Animar salida
    if (toastObj.element.parentNode) {
        toastObj.element.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (toastObj.element.parentNode) {
                toastObj.element.remove();
            }
        }, 300);
    }
}

// Agregar estilos de animación si no existen
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Inicializar contenedor cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeToastContainer);
} else {
    initializeToastContainer();
}
