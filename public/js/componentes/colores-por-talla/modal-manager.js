/**
 * jQuery Modal Wrapper
 * Proporciona una interfaz consistente para abrir/cerrar modales
 * Compatible con Bootstrap 4
 */

window.ModalManager = (function() {
    'use strict';

    // Verificar que jQuery esté disponible
    const getJQuery = () => window.jQuery || window.$;
    
    // Esperar a que jQuery esté disponible
    function ensureJQuery() {
        return new Promise(resolve => {
            if (getJQuery()) {
                resolve();
                return;
            }
            
            const maxWait = 30; // 3 segundos
            let waited = 0;
            
            const checkInterval = setInterval(() => {
                waited++;
                if (getJQuery() || waited >= maxWait) {
                    clearInterval(checkInterval);
                    if (waited >= maxWait && !getJQuery()) {
                        console.warn('[ModalManager] jQuery no disponible después de esperar');
                    }
                    resolve();
                }
            }, 100);
        });
    }

    /**
     * Abrir un modal
     * @param {string} modalId - ID del modal sin el #
     */
    async function open(modalId) {
        try {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`[ModalManager] Modal "${modalId}" no encontrado`);
                return false;
            }

            await ensureJQuery();
            const $ = getJQuery();
            
            if ($) {
                $(modalElement).modal('show');
                console.log(`[ModalManager] Modal "${modalId}" abierto`);
                return true;
            } else {
                console.error('[ModalManager] jQuery no disponible para abrir modal');
                return false;
            }
        } catch (error) {
            console.error(`[ModalManager] Error abriendo modal "${modalId}":`, error);
            return false;
        }
    }

    /**
     * Cerrar un modal
     * @param {string} modalId - ID del modal sin el #
     */
    async function close(modalId) {
        try {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`[ModalManager] Modal "${modalId}" no encontrado`);
                return false;
            }

            await ensureJQuery();
            const $ = getJQuery();
            
            if ($) {
                $(modalElement).modal('hide');
                console.log(`[ModalManager] Modal "${modalId}" cerrado`);
                return true;
            } else {
                console.error('[ModalManager] jQuery no disponible para cerrar modal');
                return false;
            }
        } catch (error) {
            console.error(`[ModalManager] Error cerrando modal "${modalId}":`, error);
            return false;
        }
    }

    /**
     * Verificar si un modal está abierto
     * @param {string} modalId - ID del modal sin el #
     */
    function isOpen(modalId) {
        try {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) return false;

            const $ = getJQuery();
            if ($) {
                return $(modalElement).hasClass('show');
            }
            return false;
        } catch (error) {
            console.error(`[ModalManager] Error verificando estado de modal "${modalId}":`, error);
            return false;
        }
    }

    /**
     * API Pública
     */
    return {
        open,
        close,
        isOpen,
        // Aliases para use case específico del wizard (async-aware)
        openWizard: async () => await open('modal-asignar-colores-por-talla'),
        closeWizard: async () => await close('modal-asignar-colores-por-talla'),
        isWizardOpen: () => isOpen('modal-asignar-colores-por-talla')
    };
})();
