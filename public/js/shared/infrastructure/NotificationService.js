/**
 * =====================================================
 * SHARED INFRASTRUCTURE - NOTIFICATION SERVICE
 * =====================================================
 * Centraliza TODAS las notificaciones visuales (toasts, alertas).
 * Elimina duplicación de lógica de toasts en múltiples archivos.
 *
 * Soporta:
 * - SweetAlert2 (si disponible)
 * - Toasts nativos (fallback sin dependencias)
 *
 * Uso:
 *   SharedNotification.success('Pedido guardado');
 *   SharedNotification.error('No se pudo guardar');
 *   SharedNotification.warning('Faltan campos');
 *   SharedNotification.info('Procesando...');
 *   SharedNotification.confirm('¿Eliminar?', () => { ... });
 */

const SharedNotification = (() => {
    'use strict';

    const TOAST_CONTAINER_ID = 'shared-toast-container';

    const COLORS = {
        success: { bg: '#16a34a', icon: '✓' },
        error:   { bg: '#dc2626', icon: '✕' },
        warning: { bg: '#f59e0b', icon: '⚠' },
        info:    { bg: '#2563eb', icon: 'ℹ' },
    };

    function _getOrCreateContainer() {
        let container = document.getElementById(TOAST_CONTAINER_ID);
        if (!container) {
            container = document.createElement('div');
            container.id = TOAST_CONTAINER_ID;
            container.style.cssText =
                'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(container);
        }
        return container;
    }

    function _showNativeToast(message, type = 'info', duration = 3500) {
        const container = _getOrCreateContainer();
        const colors = COLORS[type] || COLORS.info;

        const toast = document.createElement('div');
        toast.style.cssText = `
            background:${colors.bg};color:white;padding:12px 16px;
            border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.18);
            font-size:13px;font-weight:600;max-width:380px;
            display:flex;align-items:center;gap:8px;
            transform:translateX(120%);transition:transform 0.25s ease;
            pointer-events:auto;
        `;
        toast.innerHTML = `<span>${colors.icon}</span><span>${_escapeHtml(message)}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    function _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function _hasSwal() {
        return typeof window.Swal !== 'undefined';
    }

    // --- API Pública ---

    function toast(message, type = 'info', duration = 3500) {
        if (_hasSwal()) {
            const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                icon: iconMap[type] || 'info',
                title: message,
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
            });
        } else {
            _showNativeToast(message, type, duration);
        }
    }

    function success(message, duration) { toast(message, 'success', duration); }
    function error(message, duration)   { toast(message, 'error', duration); }
    function warning(message, duration) { toast(message, 'warning', duration); }
    function info(message, duration)    { toast(message, 'info', duration); }

    /**
     * Confirmación con callback.
     * @param {string} message - Texto de la pregunta
     * @param {Function} onConfirm - Callback si acepta
     * @param {Object} [opts] - Opciones extra (title, confirmText, cancelText)
     */
    async function confirm(message, onConfirm, opts = {}) {
        if (_hasSwal()) {
            const result = await window.Swal.fire({
                title: opts.title || '¿Estás seguro?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: opts.confirmText || 'Sí, confirmar',
                cancelButtonText: opts.cancelText || 'Cancelar',
            });
            if (result.isConfirmed && onConfirm) {
                onConfirm();
            }
            return result.isConfirmed;
        } else {
            const accepted = window.confirm(message);
            if (accepted && onConfirm) onConfirm();
            return accepted;
        }
    }

    return { toast, success, error, warning, info, confirm };
})();

window.SharedNotification = SharedNotification;
