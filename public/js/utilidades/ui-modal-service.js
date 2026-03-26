/**
 * UIModalService.js - Gestión centralizada de modales y notificaciones
 * 
 * SOLID Principles:
 * - Single Responsibility: Solo UI/modales
 * - Open/Closed: Extensible sin modificar
 * - Dependency Inversion: No depende de Swal internamente, es abstracto
 * 
 * Consolidación de:
 * - helpers-pedido-editable.js (87 líneas)
 * - mostrarNotificacion() de inventario.js
 * - Swal.fire inline en 7+ archivos
 */

'use strict';

/**
 * Función auxiliar para esperar a que Swal esté disponible
 * Soporta tanto callbacks como async/await
 * 
 * MEJORADO: Usar como Promise que se resuelve cuando Swal está listo
 * 
 * @param {Function} callback - (OPCIONAL) Función a ejecutar cuando Swal esté listo (deprecated, usar await)
 * @param {number} maxWaitTime - Tiempo máximo de espera en ms (default 5000)
 * @returns {Promise} Promesa que se resuelve cuando Swal está disponible o timeout
 * @private
 * 
 * @example
 * // Usar con async/await (recomendado)
 * await _ensureSwal();
 * Swal.fire({...});
 * 
 * // Uso legacy con callback (deprecado)
 * _ensureSwal(() => {
 *     Swal.fire({...});
 * });
 */
function _ensureSwal(callback, maxWaitTime = 5000) {
    return new Promise((resolve) => {
        if (typeof Swal !== 'undefined') {
            //  Swal ya está disponible
            if (callback) {
                callback();  // Ejecutar callback si se proporciona (legacy)
            }
            resolve(true);
            return;
        }
        
        //  Swal no está disponible, esperar
        const startTime = Date.now();
        const checkInterval = setInterval(() => {
            if (typeof Swal !== 'undefined') {
                //  Swal ahora está disponible
                clearInterval(checkInterval);
                if (callback) {
                    callback();  // Ejecutar callback si se proporciona (legacy)
                }
                resolve(true);
            } else if (Date.now() - startTime > maxWaitTime) {
                //  Timeout: Swal no se cargó
                clearInterval(checkInterval);
                console.error('[_ensureSwal] Timeout esperando a Swal después de', maxWaitTime, 'ms');
                resolve(false);  // Resolver con false para indicar timeout
            }
        }, 50);
    });
}

class UIModalService {
    // ============================================================
    // CONFIGURACIÓN POR DEFECTO
    // ============================================================
    
    static config = {
        animationDuration: 300,
        toastPosition: 'top-right',
        toastDuration: 3000
    };

    // ============================================================
    // MÉTODOS DE CONFIGURACIÓN
    // ============================================================

    /**
     * Actualizar configuración global
     * @param {Object} newConfig - Nueva configuración
     */
    static setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }

    /**
     * Obtener configuración actual
     */
    static getConfig() {
        return { ...this.config };
    }

    // ============================================================
    // MODALES GENÉRICOS
    // ============================================================

    /**
     * Abrir modal genérico (DOM manipulation)
     * @param {string} id - ID del elemento modal
     * @param {Object} options - Opciones adicionales
     */
    static abrirModal(id, options = {}) {
        const modal = document.getElementById(id);
        
        if (!modal) {
            return false;
        }

        // Limpiar listeners previos para evitar acumulación entre aperturas
        if (modal.__uiModalOutsideClickHandler) {
            modal.removeEventListener('click', modal.__uiModalOutsideClickHandler);
            modal.__uiModalOutsideClickHandler = null;
        }
        if (modal.__uiModalEscHandler) {
            document.removeEventListener('keydown', modal.__uiModalEscHandler);
            modal.__uiModalEscHandler = null;
        }

        modal.style.display = options.display || 'flex';
        
        if (options.class) {
            modal.classList.add(options.class);
        }

        // Prevenir scroll del body
        if (options.preventScroll !== false) {
            document.body.style.overflow = 'hidden';
        }

        // Event listener para cerrar al hacer click fuera
        if (options.closeOnClickOutside !== false) {
            modal.__uiModalOutsideClickHandler = (e) => {
                if (e.target === modal) {
                    this.cerrarModal(id, options);
                }
            };
            modal.addEventListener('click', modal.__uiModalOutsideClickHandler);
        }

        // Event listener para ESC
        if (options.closeOnEsc !== false) {
            const handleEsc = (e) => {
                if (e.key === 'Escape') {
                    this.cerrarModal(id, options);
                    document.removeEventListener('keydown', handleEsc);
                    modal.__uiModalEscHandler = null;
                }
            };
            modal.__uiModalEscHandler = handleEsc;
            document.addEventListener('keydown', handleEsc);
        }

        return true;
    }

    /**
     * Cerrar modal genérico
     * @param {string} id - ID del elemento modal
     * @param {Object} options - Opciones adicionales
     */
    static cerrarModal(id, options = {}) {
        const modal = document.getElementById(id);
        
        if (!modal) {
            return false;
        }

        // Limpiar listeners (independiente de si hay animación)
        if (modal.__uiModalOutsideClickHandler) {
            modal.removeEventListener('click', modal.__uiModalOutsideClickHandler);
            modal.__uiModalOutsideClickHandler = null;
        }
        if (modal.__uiModalEscHandler) {
            document.removeEventListener('keydown', modal.__uiModalEscHandler);
            modal.__uiModalEscHandler = null;
        }

        // Aplicar animación de salida
        if (options.animate !== false) {
            modal.style.animation = `fadeIn ${this.config.animationDuration}ms ease reverse`;
            setTimeout(() => {
                modal.style.display = 'none';
                // Restaurar scroll
                document.body.style.overflow = '';
            }, this.config.animationDuration);
        } else {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        return true;
    }

    /**
     * Cerrar todos los modales
     */
    static cerrarTodos() {
        document.querySelectorAll('[id*="modal"]').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = '';
    }

    // ============================================================
    // CONFIRMACIONES CON SWAL
    // ============================================================

    /**
     * Modal de confirmación genérico
     * @param {Object} config - Configuración del modal
     * @returns {Promise} Resultado de Swal.fire
     */
    static async confirmar(config = {}) {
        if (typeof Swal === 'undefined') {
            return new Promise((resolve) => {
                _ensureSwal(() => {
                    this.confirmar(config).then(resolve);
                });
            });
        }

        const {
            titulo = 'Confirmar',
            mensaje = '¿Estás seguro?',
            icono = 'question',
            confirmText = 'Aceptar',
            cancelText = 'Cancelar',
            dangerMode = false,
            showCancelButton = true
        } = config;

        return Swal.fire({
            title: titulo,
            text: mensaje,
            icon: icono,
            showCancelButton: showCancelButton,
            confirmButtonColor: dangerMode ? '#dc3545' : '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            didOpen: () => {
                document.body.style.overflow = 'hidden';
            },
            willClose: () => {
                document.body.style.overflow = '';
            },
            ...config
        });
    }

    /**
     * Modal de confirmación para eliminación
     * @param {string} resourceName - Nombre del recurso
     * @param {string} identifier - Identificador del recurso
     * @returns {Promise<boolean>} true si confirma, false si cancela
     */
    static async confirmarEliminacion(resourceName, identifier = '') {
        const resultado = await this.confirmar({
            titulo: `🗑️ Eliminar ${resourceName}`,
            mensaje: identifier 
                ? `¿Estás seguro de que deseas eliminar ${resourceName} #${identifier}? Esta acción no se puede deshacer.`
                : `¿Estás seguro de que deseas eliminar este ${resourceName}? Esta acción no se puede deshacer.`,
            icono: 'warning',
            confirmText: 'Sí, eliminar',
            dangerMode: true
        });

        return resultado.isConfirmed;
    }

    // ============================================================
    // NOTIFICACIONES SWAL (CON TIMER)
    // ============================================================

    /**
     * Modal de éxito con timer
     * @param {string} titulo - Título
     * @param {string} mensaje - Mensaje
     * @param {number} duracion - Duración en ms (default 2000)
     */
    static exito(titulo, mensaje, duracion = 2000) {
        if (typeof Swal === 'undefined') {
            _ensureSwal(() => this.exito(titulo, mensaje, duracion));
            return;
        }
        
        return Swal.fire({
            icon: 'success',
            title: titulo,
            text: mensaje,
            timer: duracion,
            timerProgressBar: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            didOpen: () => {
                document.body.style.overflow = 'hidden';
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }

    /**
     * Modal de error
     * @param {string} titulo - Título
     * @param {string} mensaje - Mensaje de error
     */
    static error(titulo, mensaje) {
        if (typeof Swal === 'undefined') {
            _ensureSwal(() => this.error(titulo, mensaje));
            return;
        }
        
        return Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido',
            didOpen: () => {
                document.body.style.overflow = 'hidden';
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }

    /**
     * Modal de advertencia con timer
     * @param {string} titulo - Título
     * @param {string} mensaje - Mensaje
     * @param {number} duracion - Duración en ms (default 2000)
     */
    static advertencia(titulo, mensaje, duracion = 2000) {
        if (typeof Swal === 'undefined') {
            _ensureSwal(() => this.advertencia(titulo, mensaje, duracion));
            return;
        }
        
        return Swal.fire({
            icon: 'warning',
            title: titulo,
            text: mensaje,
            timer: duracion,
            timerProgressBar: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            didOpen: () => {
                document.body.style.overflow = 'hidden';
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }

    /**
     * Modal de información con timer
     * @param {string} titulo - Título
     * @param {string} mensaje - Mensaje
     * @param {number} duracion - Duración en ms (default 3000)
     */
    static info(titulo, mensaje, duracion = 3000) {
        if (typeof Swal === 'undefined') {
            _ensureSwal(() => this.info(titulo, mensaje, duracion));
            return;
        }
        
        return Swal.fire({
            icon: 'info',
            title: titulo,
            text: mensaje,
            timer: duracion,
            timerProgressBar: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            didOpen: () => {
                document.body.style.overflow = 'hidden';
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }

    /**
     * Modal de carga/procesando
     * @param {string} titulo - Título
     * @param {string} mensaje - Mensaje
     */
    static cargando(titulo = 'Procesando...', mensaje = 'Por favor espera') {
        // Validar que Swal esté disponible
        if (typeof Swal === 'undefined') {
            
            // Esperar a que Swal esté disponible
            const checkSwal = setInterval(() => {
                if (typeof Swal !== 'undefined') {
                    clearInterval(checkSwal);
                    this.cargando(titulo, mensaje);
                }
            }, 50);
            
            return;
        }
        
        Swal.fire({
            title: titulo,
            html: mensaje,
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                document.body.style.overflow = 'hidden';
                Swal.showLoading();
            },
            willClose: () => {
                document.body.style.overflow = '';
            }
        });
    }

    /**
     * Modal con contenido HTML personalizado
     * @param {Object} config - Configuración
     */
    static async contenido(config = {}) {
        if (typeof Swal === 'undefined') {
            return new Promise((resolve) => {
                _ensureSwal(() => {
                    this.contenido(config).then(resolve);
                });
            });
        }

        const {
            titulo = '',
            html = '',
            ancho = '600px',
            showCancel = false,
            confirmText = 'Aceptar',
            cancelText = 'Cancelar'
        } = config;

        // Filtrar config para evitar parámetros no válidos de SweetAlert2
        const swalConfig = { ...config };
        delete swalConfig.titulo;
        delete swalConfig.ancho;
        
        // Guardar callbacks personalizados si existen
        const customDidOpen = swalConfig.didOpen;
        const customWillClose = swalConfig.willClose;
        delete swalConfig.didOpen;
        delete swalConfig.willClose;

        return Swal.fire({
            title: titulo,
            html: html,
            width: ancho,
            showCancelButton: showCancel,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            customClass: {
                container: 'swal-centered-container',
                popup: 'swal-centered-popup'
            },
            didOpen: (modal) => {
                // Centrar el modal verticalmente en la pantalla
                const container = modal.closest('.swal2-container');
                if (container) {
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    container.style.height = '100vh';
                    container.style.zIndex = '999998';
                }
                modal.style.marginTop = '0';
                modal.style.marginBottom = '0';
                // Prevenir scroll del body cuando se abre el modal
                document.body.style.overflow = 'hidden';
                
                // Ejecutar callback personalizado si existe
                if (typeof customDidOpen === 'function') {
                    customDidOpen(modal);
                }
            },
            willClose: () => {
                // Restaurar scroll del body cuando se cierra el modal
                document.body.style.overflow = '';
                
                // Ejecutar callback personalizado si existe
                if (typeof customWillClose === 'function') {
                    customWillClose();
                }
            },
            ...swalConfig
        });
    }

    // ============================================================
    // TOASTS (NOTIFICACIONES FLOTANTES)
    // ============================================================

    /**
     * Toast de éxito
     * @param {string} mensaje - Mensaje a mostrar
     */
    static toastExito(mensaje) {
        return this._mostrarToast(mensaje, 'success', '');
    }

    /**
     * Toast de error
     * @param {string} mensaje - Mensaje a mostrar
     */
    static toastError(mensaje) {
        return this._mostrarToast(mensaje, 'error');
    }

    /**
     * Toast de información
     * @param {string} mensaje - Mensaje a mostrar
     */
    static toastInfo(mensaje) {
        return this._mostrarToast(mensaje, 'info', '');
    }

    /**
     * Toast de advertencia
     * @param {string} mensaje - Mensaje a mostrar
     */
    static toastAdvertencia(mensaje) {
        return this._mostrarToast(mensaje, 'warning', '');
    }

    /**
     * Método privado para mostrar toast
     * @private
     */
    static _mostrarToast(mensaje, tipo = 'info', icono = '') {
        const colores = {
            success: { bg: '#10b981', border: '#059669' },
            error: { bg: '#ef4444', border: '#dc2626' },
            info: { bg: '#3b82f6', border: '#1d4ed8' },
            warning: { bg: '#f59e0b', border: '#d97706' }
        };

        const color = colores[tipo] || colores.info;

        const toast = document.createElement('div');
        toast.className = 'ui-toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${color.bg};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 999999;
            animation: slideIn 0.3s ease;
            font-weight: 500;
            border-left: 4px solid ${color.border};
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 400px;
        `;

        toast.innerHTML = `
            <span style="font-size: 1.25rem; flex-shrink: 0;">${icono}</span>
            <span style="flex: 1;">${mensaje}</span>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 0;
                font-size: 1.5rem;
                line-height: 1;
                opacity: 0.8;
                transition: opacity 0.2s;
            " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                ×
            </button>
        `;

        document.body.appendChild(toast);

        // Auto-remover después del tiempo configurado
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, this.config.toastDuration);

        return toast;
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Obtener token CSRF
     * @returns {string} Token CSRF
     */
    static getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Escape HTML para prevenir XSS
     * @param {string} text - Texto a escapar
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Verificar si hay modales abiertos
     */
    static hayModalesAbiertos() {
        const modales = document.querySelectorAll('[id*="modal"]');
        return Array.from(modales).some(m => m.style.display !== 'none');
    }

    /**
     * Obtener modal abierto actual
     */
    static obtenerModalAbierto() {
        const modales = document.querySelectorAll('[id*="modal"]');
        return Array.from(modales).find(m => m.style.display !== 'none') || null;
    }
}

// ============================================================
// ESTILOS GLOBALES PARA ANIMACIONES
// ============================================================

if (!document.getElementById('ui-modal-service-styles')) {
    const style = document.createElement('style');
    style.id = 'ui-modal-service-styles';
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                transform: translateX(400px);
                opacity: 0;
            }
            to { 
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from { 
                transform: translateX(0);
                opacity: 1;
            }
            to { 
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .ui-toast {
            animation: slideDown 0.3s ease !important;
        }
    `;
    document.head.appendChild(style);
}

// ============================================================
// EXPONER GLOBALMENTE
// ============================================================

window.UI = UIModalService;

