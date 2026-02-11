/**
 *  LAZY LOADER: Módulos de Edición de Prendas
 * 
 * Propósito: Cargar bajo demanda todos los módulos necesarios para editar prendas
 * Cuándo: Se carga cuando usuario hace clic en "Editar Pedido"
 * 
 * Incluye:
 * - Constantes de tallas
 * - Image storage service
 * - Gestión de telas, tallas, variaciones
 * - Componentes de prendas (cards, wrappers)
 * - Modales de novedad y edición
 * - Servicios de procesos (item-api, validator, etc)
 * 
 * Tamaño: ~120KB sin minify, ~30KB minificado
 * Tiempo de carga: ~200-500ms en conexión lenta
 */

window.PrendaEditorLoader = (function() {
    let isLoading = false;
    let isLoaded = false;
    let loadError = null;

    // 🔥 SCRIPTS QUE PUEDEN CARGARSE EN PARALELO (sin dependencias críticas)
    // Estos se cargan simultáneamente para optimizar el tiempo total
    const scriptsParallel = [
        '/js/configuraciones/debug-logger.js',  // No es crítico
        '/js/configuraciones/constantes-tallas.js',  // Solo define constantes
        '/js/componentes/prenda-card-editar-simple.js',  // Componente UI
        '/js/componentes/prendas-wrappers.js',  // Componente UI
        '/js/componentes/modal-prenda-dinamico-constantes.js',  // Solo constantes
        '/js/modulos/crear-pedido/telas/gestion-telas.js',  // Gestión independiente
        '/js/modulos/crear-pedido/tallas/gestion-tallas.js',  // Gestión independiente
        '/js/modulos/crear-pedido/procesos/services/notification-service.js?v=' + Date.now(),  // ⚡ SIN DEPENDENCIAS - Carga paralela
        '/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js?v=' + Date.now(),  // ⚡ OPTIMIZADO: Se usa solo al guardar, no al abrir
    ];

    const scriptsToLoad = [
        // ========== SERVICIOS PROCESOS (Orden crítico - dependen entre sí) ==========
        // NOTA: payload-normalizer-v3-definitiva.js se carga en paralelo (no se necesita para abrir modal)
        // NOTA: notification-service.js se carga en paralelo (línea 44)
        '/js/modulos/crear-pedido/procesos/services/item-api-service.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/item-validator.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/item-form-collector.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/item-renderer.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/prenda-editor.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/item-orchestrator.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/proceso-editor.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/gestor-edicion-procesos.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/servicio-procesos.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/services/middleware-guardado-prenda.js?v=' + Date.now(),
        
        // ========== GESTIÓN DE ITEMS Y PROCESOS ==========
        '/js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js',
        '/js/modulos/crear-pedido/procesos/gestion-items-pedido.js?v=' + Date.now(),
        '/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js',
        '/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js',
        '/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js',
        
        // ========== VARIACIONES ==========
        '/js/modulos/crear-pedido/prendas/manejadores-variaciones.js',
        
        // ========== COMPONENTES DE PRENDAS (YA EN PARALELO) ==========
        // '/js/componentes/prenda-card-editar-simple.js',  // En paralelo
        // '/js/componentes/prendas-wrappers.js',  // En paralelo
        '/js/componentes/modal-novedad-prenda.js?v=' + Date.now(),
        '/js/componentes/modal-novedad-edicion.js?v=' + Date.now(),
        '/js/componentes/prenda-form-collector.js?v=' + Date.now(),
        // '/js/componentes/modal-prenda-dinamico-constantes.js',  // En paralelo
        '/js/componentes/modal-prenda-dinamico.js',
        '/js/componentes/prenda-editor-modal.js',
        
        // ========== API Y CONFIGURACIÓN ==========
        '/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js',
    ];

    // ⚡ PERFORMANCE: CSS version for cache-busting (change when CSS is updated)
    const cssVersion = '20260130';
    
    const cssToLoad = [
        `/css/crear-pedido.css?v=${cssVersion}`,
        `/css/crear-pedido-editable.css?v=${cssVersion}`,
        `/css/form-modal-consistency.css?v=${cssVersion}`,
        `/css/swal-z-index-fix.css?v=${cssVersion}`,
        `/css/componentes/prendas.css?v=${cssVersion}`,
        `/css/modales-personalizados.css?v=${cssVersion}`,
    ];

    /**
     * Cargar scripts en paralelo (sin esperar entre ellos)
     * @private
     */
    function loadScriptsParallel() {
        return Promise.all(scriptsParallel.map(src => {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = src;
                script.type = 'text/javascript';
                script.onload = () => resolve();
                script.onerror = () => resolve(); // No fallar si un script paralelo falla
                document.head.appendChild(script);
            });
        }));
    }

    /**
     * Cargar scripts secuencialmente (garantiza orden de dependencias)
     * @private
     */
    function loadScriptsSequentially() {
        return new Promise((resolve, reject) => {
            let loaded = 0;
            const tiemposScripts = {};
            const tiempoInicio = performance.now();
            
            const loadNext = () => {
                if (loaded >= scriptsToLoad.length) {
                    const tiempoTotal = performance.now() - tiempoInicio;
                    const tiemposOrdenados = Object.entries(tiemposScripts).sort((a, b) => b[1] - a[1]).slice(0, 5);
                    tiemposOrdenados.forEach(([nombre, tiempo], idx) => {
                    });
                    resolve();
                    return;
                }
                
                const src = scriptsToLoad[loaded];
                const filename = src.split('/').pop().split('?')[0];
                const tiempoAntes = performance.now();
                
                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                script.type = 'text/javascript';
                
                // Timeout de 30s por script
                const timeout = setTimeout(() => {
                    reject(new Error(`Timeout loading: ${filename}`));
                }, 30000);
                
                script.onload = (function(t, antes, nombre) {
                    return function() {
                        clearTimeout(t);
                        const tiempoScript = performance.now() - antes;
                        tiemposScripts[nombre] = tiempoScript;
                        loaded++;
                        loadNext();
                    };
                })(timeout, tiempoAntes, filename);
                
                script.onerror = (function(t, retryCount) {
                    return function() {
                        clearTimeout(t);
                        
                        // Reintentar con exponential backoff
                        if (retryCount < 3) {
                            const delay = Math.pow(2, retryCount) * 1000; // 1s, 2s, 4s
                            
                            setTimeout(() => {
                                const retryScript = document.createElement('script');
                                retryScript.src = src + '&retry=' + (retryCount + 1);
                                retryScript.defer = true;
                                retryScript.type = 'text/javascript';
                                
                                retryScript.onload = (function(rt, antes, nombre) {
                                    return function() {
                                        clearTimeout(rt);
                                        const tiempoScript = performance.now() - antes;
                                        tiemposScripts[nombre] = tiempoScript;
                                        loaded++;
                                        loadNext();
                                    };
                                })(timeout, tiempoAntes, filename);
                                
                                retryScript.onerror = (function(rt) {
                                    return function() {
                                        clearTimeout(rt);
                                        const error = `Failed to load: ${filename} after ${retryCount + 1} retries`;
                                        reject(new Error(error));
                                    };
                                })(timeout);
                                
                                document.head.appendChild(retryScript);
                            }, delay);
                        } else {
                            const error = `Failed to load: ${filename} after 3 retries`;
                            reject(new Error(error));
                        }
                    };
                })(timeout, 0);
                
                document.head.appendChild(script);
            };
            
            loadNext();
        });
    }

    /**
     * Cargar CSS (en paralelo - más rápido que scripts)
     * @private
     */
    function loadCSS() {
        cssToLoad.forEach(href => {
            // Verificar que no esté cargado ya
            const exists = document.querySelector(`link[href*="${href.split('/').pop()}"]`);
            if (exists) {
                return;
            }
            
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.type = 'text/css';
            document.head.appendChild(link);
        });
    }

    /**
     * Validar que los módulos requeridos estén disponibles (validación rápida)
     * @private
     */
    function validateDependencies() {
        // Validación rápida sin búsquedas repetidas
        if (!window.ImageStorageService || !window.constantes_tallas) {
            // No lanzar error, algunas dependencias pueden ser opcionales
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
                return Promise.resolve();
            }
            
            // Si ya está en progreso, esperar
            if (isLoading) {
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
                // Paso 0: Iniciar scripts en paralelo en BACKGROUND (sin esperar)
                // Estos NO son críticos, así que no usamos await
                loadScriptsParallel().catch(() => {}); // Ignorar errores
                
                // Paso 1: Cargar CSS (no bloquea)
                loadCSS();
                
                // Paso 2: Cargar scripts (en orden - CRÍTICOS)
                await loadScriptsSequentially();
                
                // Paso 3: Validar dependencias (rápido)
                validateDependencies();
                
                // Paso 4: Marcar como cargado
                isLoaded = true;
                isLoading = false;
                
                // Disparar evento personalizado (para otros componentes)
                const event = new CustomEvent('prendaEditorLoaded', {
                    detail: { timestamp: Date.now() }
                });
                window.dispatchEvent(event);
                
                return Promise.resolve();
                
            } catch (error) {
                isLoading = false;
                loadError = error;
                
                // Disparar evento de error
                const errorEvent = new CustomEvent('prendaEditorLoadError', {
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
         * Debug: obtener lista de scripts a cargar
         * @returns {Array}
         */
        debug: function() {
            return {
                isLoaded: isLoaded,
                isLoading: isLoading,
                scriptsCount: scriptsToLoad.length,
                cssCount: cssToLoad.length,
                error: loadError ? loadError.message : null,
                scripts: scriptsToLoad,
                css: cssToLoad
            };
        }
    };
})();

// Exportar para módulos ES6 (si se usa import)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.PrendaEditorLoader;
}
