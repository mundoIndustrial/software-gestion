/**
 * 🎯 Módulo de Manejo del Modal
 * Responsabilidad: Abrir, cerrar, limpiar y gestionar el modal
 */

class PrendaModalManager {
    /**
     * Abrirmodal
     */
    static abrir(modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Usar UIModalService para manejar el scroll del body
            if (window.UI && typeof window.UI.abrirModal === 'function') {
                window.UI.abrirModal(modalId, {
                    display: 'flex',
                    closeOnClickOutside: false,
                    closeOnEsc: true,
                    preventScroll: true
                });
            } else {
                // Fallback si UIModalService no está disponible
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            modal.dispatchEvent(new CustomEvent('shown.bs.modal', { bubbles: true }));
            console.log(` [Modal] Abierto: ${modalId}`);
        } else {
            console.warn(` [Modal] No encontrado: ${modalId}`);
        }
    }

    /**
     * Cerrar modal
     */
    static cerrar(modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (modal) {
            // Usar UIModalService para manejar el scroll del body
            if (window.UI && typeof window.UI.cerrarModal === 'function') {
                window.UI.cerrarModal(modalId, {
                    animate: false
                });
            } else {
                // Fallback si UIModalService no está disponible
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            modal.dispatchEvent(new CustomEvent('hidden.bs.modal', { bubbles: true }));
            console.log(` [Modal] Cerrado: ${modalId}`);
        }
    }

    /**
     * Limpiar todo el contenido del modal
     */
    static limpiar(modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // Limpiar formulario
        const form = modal.querySelector('form');
        if (form) form.reset();
        
        // Limpiar campos usando los módulos loaders
        try {
            if (typeof PrendaEditorBasicos !== 'undefined') {
                PrendaEditorBasicos.limpiar();
            }
            if (typeof PrendaEditorImagenes !== 'undefined') {
                PrendaEditorImagenes.limpiar();
            }
            if (typeof PrendaEditorTelas !== 'undefined') {
                PrendaEditorTelas.limpiar();
            }
            if (typeof PrendaEditorVariaciones !== 'undefined') {
                PrendaEditorVariaciones.limpiar();
            }
            if (typeof PrendaEditorTallas !== 'undefined') {
                PrendaEditorTallas.limpiar();
            }
            if (typeof PrendaEditorColores !== 'undefined') {
                PrendaEditorColores.limpiar();
            }
            if (typeof PrendaEditorProcesos !== 'undefined') {
                PrendaEditorProcesos.limpiar();
            }
        } catch (error) {
            console.warn('[Modal] Error limpiando módulos:', error);
        }
        
        console.log(' [Modal] Limpiado');
    }

    /**
     * Actualizar título del modal
     */
    static actualizarTitulo(esEdicion, modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const titulo = modal.querySelector('.modal-title, h2, h3');
        if (titulo) {
            titulo.textContent = esEdicion ? 'Editar Prenda' : 'Agregar Nueva Prenda';
        }
    }

    /**
     * Cambiar botón a "Guardar cambios"
     */
    static cambiarBotonAGuardarCambios(modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const btn = modal.querySelector('#btn-guardar-prenda-modal, [name="guardar-prenda"]');
        if (btn) {
            btn.textContent = 'Guardar cambios';
            btn.className = 'btn btn-success btn-block';
        }
    }

    /**
     * Cambiar botón a "Agregar Prenda"
     */
    static cambiarBotonAAgregar(modalId = 'modal-agregar-prenda-nueva') {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const btn = modal.querySelector('#btn-guardar-prenda-modal, [name="guardar-prenda"]');
        if (btn) {
            btn.textContent = 'Agregar Prenda';
            btn.className = 'btn btn-primary btn-block';
        }
    }

    /**
     * Mostrar notificación
     */
    static mostrarNotificacion(mensaje, tipo = 'info') {
        if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
                icon: tipo === 'error' ? 'error' : tipo === 'success' ? 'success' : 'info',
                title: tipo === 'error' ? 'Error' : tipo === 'success' ? 'Éxito' : 'Información',
                text: mensaje,
                timer: 3000
            });
        } else {
            console.log(`[Notificación - ${tipo.toUpperCase()}] ${mensaje}`);
        }
    }

    /**
     * Mostrar error
     */
    static mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    /**
     * Mostrar éxito
     */
    static mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    /**
     * Esperar a que el modal esté visible
     * Usa el evento shown.bs.modal (dispatched por abrir()) con timeout de seguridad
     */
    static async esperarVisible(modalId = 'modal-agregar-prenda-nueva') {
        return new Promise((resolve) => {
            const modal = document.getElementById(modalId);
            if (!modal) {
                resolve();
                return;
            }

            // Si ya está visible, resolver inmediatamente
            if (modal.style.display !== 'none' && modal.style.display !== '') {
                resolve();
                return;
            }

            // Esperar evento shown.bs.modal (una sola vez)
            const handler = () => {
                clearTimeout(timeout);
                resolve();
            };
            modal.addEventListener('shown.bs.modal', handler, { once: true });

            // Timeout de seguridad
            const timeout = setTimeout(() => {
                modal.removeEventListener('shown.bs.modal', handler);
                resolve();
            }, 1000);
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaModalManager;
}
