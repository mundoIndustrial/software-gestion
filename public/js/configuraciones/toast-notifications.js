/**
 * Toast Notifications - Global Module
 * Estilo consistente con insumos/materiales
 */

let toastContainer = null;
let activeToasts = [];
const MAX_TOASTS = 5;
const TOAST_DURATION = {
    success: 2000,
    error: 4000,
    warning: 2000,
    info: 2000
};

const TOAST_CONFIG = {
    success: {
        bg: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        title: 'Éxito',
        progressColor: '#6ee7b7'
    },
    error: {
        bg: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>',
        title: 'Error',
        progressColor: '#fca5a5'
    },
    warning: {
        bg: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86l-8.6 14.86A1 1 0 002.54 20h18.92a1 1 0 00.85-1.28l-8.6-14.86a1 1 0 00-1.72 0z"/></svg>',
        title: 'Advertencia',
        progressColor: '#fcd34d'
    },
    info: {
        bg: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
        icon: '<svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>',
        title: 'Información',
        progressColor: '#93c5fd'
    }
};

function initializeToastContainer() {
    if (toastContainer && document.body.contains(toastContainer)) {
        return toastContainer;
    }

    const old = document.getElementById('toast-container');
    if (old) old.remove();

    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    toastContainer.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        z-index: 9999999 !important;
        display: flex;
        flex-direction: column;
        gap: 12px;
        pointer-events: none;
    `;

    document.body.appendChild(toastContainer);
    return toastContainer;
}

window.showToast = function(message, type = 'success') {
    const container = initializeToastContainer();
    const cfg = TOAST_CONFIG[type] || TOAST_CONFIG.success;
    const duration = TOAST_DURATION[type] || 2000;

    // Verificar duplicados
    const duplicate = activeToasts.find(t => t.message === message && t.type === type);
    if (duplicate) {
        clearTimeout(duplicate.autoClose);
        duplicate.autoClose = setTimeout(() => closeToast(duplicate), duration);
        return;
    }

    // Limitar cantidad de toasts
    if (activeToasts.length >= MAX_TOASTS) {
        let toRemove = activeToasts.find(t => t.type !== 'error') || activeToasts[0];
        if (toRemove) closeToast(toRemove);
    }

    const formattedMessage = message.replace(/\n/g, '<br>');

    // Crear toast
    const toast = document.createElement('div');
    toast.setAttribute('data-toast', 'true');
    toast.style.cssText = `
        background: ${cfg.bg};
        color: white;
        padding: 16px 20px 14px 16px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 320px;
        max-width: 420px;
        pointer-events: auto;
        position: relative;
        overflow: hidden;
        animation: toastSlideIn 0.4s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
        opacity: 0;
        transform: translateX(100%);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    `;

    toast.innerHTML = `
        <div style="flex-shrink: 0; width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 1px;">
            ${cfg.icon}
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-weight: 700; font-size: 14px; margin-bottom: 3px; letter-spacing: 0.2px;">${cfg.title}</div>
            <div style="font-size: 13px; line-height: 1.5; opacity: 0.95; white-space: pre-line; word-break: break-word;">${formattedMessage}</div>
        </div>
        <button style="flex-shrink: 0; background: rgba(255,255,255,0.15); border: none; color: white; cursor: pointer; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s; margin-top: 1px;" onmouseenter="this.style.background='rgba(255,255,255,0.3)'" onmouseleave="this.style.background='rgba(255,255,255,0.15)'">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div style="position: absolute; bottom: 0; left: 0; height: 3px; background: ${cfg.progressColor}; border-radius: 0 0 12px 12px; animation: toastProgress ${duration}ms linear forwards; width: 100%;"></div>
    `;

    container.appendChild(toast);

    // Objeto de rastreo
    const toastObj = {
        message,
        type,
        element: toast,
        autoClose: null,
        createdAt: Date.now()
    };

    activeToasts.push(toastObj);

    // Auto-cerrar
    toastObj.autoClose = setTimeout(() => closeToast(toastObj), duration);

    // Botón cerrar
    const closeBtn = toast.querySelector('button');
    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            closeToast(toastObj);
        });
    }

    // Pausar al hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(toastObj.autoClose);
        const progressBar = toast.querySelector('div[style*="toastProgress"]');
        if (progressBar) progressBar.style.animationPlayState = 'paused';
    });

    toast.addEventListener('mouseleave', () => {
        const progressBar = toast.querySelector('div[style*="toastProgress"]');
        if (progressBar) progressBar.style.animationPlayState = 'running';
        toastObj.autoClose = setTimeout(() => closeToast(toastObj), 2000);
    });
};

function closeToast(toastObj) {
    if (!toastObj || !toastObj.element) return;

    const index = activeToasts.indexOf(toastObj);
    if (index > -1) activeToasts.splice(index, 1);

    if (toastObj.autoClose) clearTimeout(toastObj.autoClose);

    if (toastObj.element.parentNode) {
        toastObj.element.style.animation = 'toastSlideOut 0.35s cubic-bezier(0.33, 0, 0.67, 0) forwards';
        setTimeout(() => {
            if (toastObj.element && toastObj.element.parentNode) {
                toastObj.element.remove();
            }
        }, 350);
    }
}

// Estilos de animación
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes toastSlideIn {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes toastSlideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(120%);
                opacity: 0;
            }
        }

        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }
    `;
    document.head.appendChild(style);
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeToastContainer);
} else {
    initializeToastContainer();
}
