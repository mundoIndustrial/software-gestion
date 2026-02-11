/**
 *  LAZY LOADER: Módulos de Gestión EPP
 * 
 * Propósito: Cargar bajo demanda todos los módulos necesarios para editar EPP
 * Cuándo: Se carga cuando usuario hace clic en "Editar EPP"
 * 
 * Incluye:
 * - Servicios EPP (API, state, modal, items, imágenes)
 * - Templates e interfaces
 * - Manejadores de menús y formularios
 * - Modales de agregar EPP
 * 
 * Tamaño: ~90KB sin minify, ~25KB minificado
 * Tiempo de carga: ~150-300ms en conexión lenta
 */

window.EPPManagerLoader = (function() {
    let isLoading = false;
    let isLoaded = false;
    let loadError = null;

    const scriptsToLoad = [
        // ========== SERVICIOS BASE (Orden crítico) ==========
        '/js/modulos/crear-pedido/epp/services/epp-api-service.js',
        '/js/modulos/crear-pedido/epp/services/epp-state-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-notification-service.js',
        
        // ========== SERVICIOS ESPECIALIZADOS ==========
        '/js/modulos/crear-pedido/epp/services/epp-modal-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-item-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-imagen-manager.js',
        
        // ========== SERVICIOS DE NEGOCIO ==========
        '/js/modulos/crear-pedido/epp/services/epp-creation-service.js',
        '/js/modulos/crear-pedido/epp/services/epp-form-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-menu-handlers.js',
        '/js/modulos/crear-pedido/epp/services/epp-service.js',
        
        // ========== TEMPLATES E INTERFACES ==========
        '/js/modulos/crear-pedido/epp/templates/epp-modal-template.js',
        '/js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js',
        
        // ========== INICIALIZACIÓN ==========
        '/js/modulos/crear-pedido/epp/epp-init.js',
        
        // ========== MODALES ==========
        '/js/modulos/crear-pedido/modales/modal-agregar-epp.js',
    ];

    /**
     * Cargar scripts secuencialmente (garantiza orden de dependencias)
     * @private
     */
    function loadScriptsSequentially() {
        return new Promise((resolve, reject) => {
            let loaded = 0;
            
            const loadNext = () => {
                if (loaded >= scriptsToLoad.length) {
                    resolve();
                    return;
                }
                
                const src = scriptsToLoad[loaded];
                const filename = src.split('/').pop();
                
                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                script.type = 'text/javascript';
                
                // Timeout por script
                const timeout = setTimeout(() => {
                    reject(new Error(`Timeout loading: ${filename}`));
                }, 30000);
                
                script.onload = () => {
                    clearTimeout(timeout);
                    console.log(`[EPPManagerLoader]  ${filename}`);
                    loaded++;
                    loadNext();
                };
                
                script.onerror = () => {
                    clearTimeout(timeout);
                    const error = `Failed to load: ${filename}`;
                    console.error(`[EPPManagerLoader]  ${error}`);
                    reject(new Error(error));
                };
                
                document.head.appendChild(script);
            };
            
            loadNext();
        });
    }

    /**
     * Validar que los módulos requeridos estén disponibles
     * @private
     */
    function validateDependencies() {
        const required = [
            { name: 'window.EPPService', type: 'object' },
            { name: 'window.EPPModalInterface', type: 'object' },
        ];
        
        const missing = [];
        required.forEach(req => {
            try {
                const parts = req.name.split('.');
                let obj = window;
                parts.forEach(part => {
                    obj = obj[part];
                });
                
                if (!obj) {
                    missing.push(req.name);
                }
            } catch (e) {
                missing.push(req.name);
            }
        });
        
        if (missing.length > 0) {
            console.warn(`[EPPManagerLoader]  Dependencias faltantes:`, missing);
            // Las dependencias pueden ser opcionales
        }
    }

    return {
        /**
         * Cargar todos los módulos bajo demanda
         * @returns {Promise<void>}
         */
        load: async function() {
            // Si ya está cargado, retornar inmediatamente
            if (isLoaded) {
                console.log('[EPPManagerLoader] ⏭️ Módulos ya cargados, usando cache');
                return Promise.resolve();
            }
            
            // Si ya está en progreso, esperar
            if (isLoading) {
                console.log('[EPPManagerLoader] ⏳ Carga en progreso, esperando...');
                return new Promise((resolve, reject) => {
                    const checkInterval = setInterval(() => {
                        if (isLoaded) {
                            clearInterval(checkInterval);
                            resolve();
                        } else if (loadError) {
                            clearInterval(checkInterval);
                            reject(loadError);
                        }
                    }, 100);
                    
                    // Timeout de 60 segundos
                    setTimeout(() => {
                        clearInterval(checkInterval);
                        reject(new Error('Load timeout'));
                    }, 60000);
                });
            }
            
            isLoading = true;
            loadError = null;
            
            try {
                console.log('[EPPManagerLoader]  Iniciando carga de módulos de gestión EPP');
                console.log(`[EPPManagerLoader]  ${scriptsToLoad.length} scripts a cargar`);
                
                // Cargar scripts en orden
                await loadScriptsSequentially();
                
                // Validar dependencias
                validateDependencies();
                
                // Marcar como cargado
                isLoaded = true;
                isLoading = false;
                
                console.log('[EPPManagerLoader]  TODOS LOS MÓDULOS CARGADOS EXITOSAMENTE');
                console.log('[EPPManagerLoader]  Tamaño cargado: ~25KB (minificado)');
                
                // Disparar evento personalizado
                const event = new CustomEvent('eppManagerLoaded', {
                    detail: { timestamp: Date.now() }
                });
                window.dispatchEvent(event);
                
                return Promise.resolve();
                
            } catch (error) {
                isLoading = false;
                loadError = error;
                
                console.error('[EPPManagerLoader]  ERROR CARGANDO MÓDULOS:', error.message);
                console.error('[EPPManagerLoader] Stack:', error.stack);
                
                // Disparar evento de error
                const errorEvent = new CustomEvent('eppManagerLoadError', {
                    detail: { error: error.message }
                });
                window.dispatchEvent(errorEvent);
                
                return Promise.reject(error);
            }
        },
        
        /**
         * Verificar si está cargado
         * @returns {boolean}
         */
        isLoaded: function() {
            return isLoaded;
        },
        
        /**
         * Verificar si está cargando
         * @returns {boolean}
         */
        isLoading: function() {
            return isLoading;
        },
        
        /**
         * Obtener último error (si lo hay)
         * @returns {Error|null}
         */
        getLastError: function() {
            return loadError;
        },
        
        /**
         * Debug: obtener información del loader
         * @returns {Object}
         */
        debug: function() {
            return {
                isLoaded: isLoaded,
                isLoading: isLoading,
                scriptsCount: scriptsToLoad.length,
                error: loadError ? loadError.message : null,
                scripts: scriptsToLoad
            };
        }
    };
})();

// Exportar para módulos ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.EPPManagerLoader;
}
