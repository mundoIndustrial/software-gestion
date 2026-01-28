/**
 * 🚀 PRENDA EDITOR PRELOADER - Precarga en Background
 * 
 * Propósito: Cargar módulos de edición de prendas en background
 * después del DOMContentLoaded para evitar delays en primera apertura
 * 
 * Características:
 * - Carga en background cuando el navegador está idle
 * - Cache en memoria de scripts ya cargados
 * - Compatible con SweetAlert2
 * - Vanilla JS, sin dependencias
 */

window.PrendaEditorPreloader = (function() {
    const config = {
        // Estado de precarguía
        isPreloading: false,
        isPreloaded: false,
        preloadError: null,
        
        // Timing
        preloadDelay: 2000,  // Esperar 2s después de DOMContentLoaded
        idleThreshold: 100,   // Considerar idle si CPU < 100ms
        
        // Cache de scripts
        scriptCache: new Map(),  // { src: code }
        moduleCache: new Map(),  // { moduleName: { loaded: true/false, time: ms } }
    };

    /**
     * Iniciar precarguía automática (llamar en DOMContentLoaded)
     * @returns {void}
     */
    function startAutoPreload() {
        // ⚡ ESPERAR A QUE PrendaEditorLoader esté disponible
        // El loader se carga dinámicamente, así que necesitamos polling
        if (!window.PrendaEditorLoader) {
            console.log('[PrendaEditorPreloader] ⏳ Esperando PrendaEditorLoader...');
            
            // Polling: intentar cada 100ms hasta que esté disponible
            const checkLoader = setInterval(() => {
                if (window.PrendaEditorLoader && window.PrendaEditorLoader.load) {
                    clearInterval(checkLoader);
                    console.log('[PrendaEditorPreloader] ✅ PrendaEditorLoader detectado, iniciando precarguía');
                    initPreload();
                }
            }, 100);
            
            // Timeout de seguridad: si no está disponible en 10s, intentar igual
            setTimeout(() => {
                clearInterval(checkLoader);
                if (!window.PrendaEditorLoader) {
                    console.warn('[PrendaEditorPreloader] ⚠️ PrendaEditorLoader no disponible después de 10s, intentando igual');
                }
                initPreload();
            }, 10000);
            
            return;
        }

        // Si ya está disponible, proceder directamente
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPreload);
        } else {
            initPreload();
        }
    }

    /**
     * Inicializar precarguía con delay
     * @private
     */
    function initPreload() {
        console.log('[PrendaEditorPreloader] 🔄 Precarguía iniciada...');
        
        // Esperar a que la página esté visualmente estable
        setTimeout(() => {
            // Usar requestIdleCallback si está disponible (mejor practice)
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    performPreload();
                }, { timeout: 5000 });
            } else {
                // Fallback para navegadores sin soporte
                setTimeout(performPreload, 1000);
            }
        }, config.preloadDelay);
    }

    /**
     * Ejecutar precarguía real
     * @private
     */
    function performPreload() {
        if (config.isPreloading || config.isPreloaded) {
            return;
        }

        // Verificar que el loader está disponible
        if (!window.PrendaEditorLoader || !window.PrendaEditorLoader.load) {
            console.warn('[PrendaEditorPreloader] ⚠️ PrendaEditorLoader aún no disponible, reintentando...');
            setTimeout(performPreload, 500);
            return;
        }

        config.isPreloading = true;
        const startTime = performance.now();

        window.PrendaEditorLoader.load()
            .then(() => {
                const elapsed = performance.now() - startTime;
                config.isPreloaded = true;
                config.isPreloading = false;
                
                console.log(`[PrendaEditorPreloader] ✅ Precarguía completada en ${elapsed.toFixed(0)}ms`);
                
                // Disparar evento (para debugging)
                window.dispatchEvent(new CustomEvent('prendaEditorPreloaded', {
                    detail: { elapsed, cached: true }
                }));
            })
            .catch((error) => {
                config.isPreloading = false;
                config.preloadError = error;
                
                console.error('[PrendaEditorPreloader] ❌ Error en precarguía:', error.message);
                
                // Disparar evento de error
                window.dispatchEvent(new CustomEvent('prendaEditorPreloadError', {
                    detail: { error: error.message }
                }));
            });
    }

    /**
     * Cargar módulos con cache y loader visual
     * @param {Object} options
     * @param {string} options.title - Título del modal
     * @param {string} options.message - Mensaje del modal
     * @param {Function} options.onComplete - Callback después de cargar
     * @returns {Promise<void>}
     */
    function loadWithVisualLoader(options = {}) {
        const defaults = {
            title: 'Cargando módulos...',
            message: 'Por favor espera',
            onComplete: null
        };
        const opts = { ...defaults, ...options };

        return new Promise((resolve, reject) => {
            // Verificar que el loader está disponible
            if (!window.PrendaEditorLoader || !window.PrendaEditorLoader.load) {
                console.error('[PrendaEditorPreloader] ❌ PrendaEditorLoader no disponible');
                reject(new Error('PrendaEditorLoader no está disponible'));
                return;
            }

            // Verificar si ya está precargado (fast path)
            if (config.isPreloaded) {
                console.log('[PrendaEditorPreloader] 💾 ¡Ya precargado! Abriendo inmediatamente...');
                resolve();
                return;
            }

            // Si ya está cargando, esperar
            if (config.isPreloading) {
                console.log('[PrendaEditorPreloader] ⏳ Esperando precarguía en background...');
                
                // Mostrar Swal con loader
                showLoaderModal(opts);
                
                // Esperar a que termine
                const checkInterval = setInterval(() => {
                    if (config.isPreloaded) {
                        clearInterval(checkInterval);
                        Swal.close();
                        resolve();
                    } else if (config.preloadError) {
                        clearInterval(checkInterval);
                        Swal.close();
                        reject(config.preloadError);
                    }
                }, 100);

                // Timeout de seguridad
                setTimeout(() => {
                    if (!config.isPreloaded) {
                        clearInterval(checkInterval);
                        Swal.close();
                        reject(new Error('Timeout en carga de módulos'));
                    }
                }, 30000);
                
                return;
            }

            // Caso: Aún no se ha iniciado precarguía (primera vez)
            console.log('[PrendaEditorPreloader] 🔄 Iniciando carga inmediata...');
            showLoaderModal(opts);

            config.isPreloading = true;
            window.PrendaEditorLoader.load()
                .then(() => {
                    config.isPreloaded = true;
                    config.isPreloading = false;
                    Swal.close();
                    resolve();
                })
                .catch((error) => {
                    config.isPreloading = false;
                    config.preloadError = error;
                    Swal.close();
                    reject(error);
                });
        });
    }

    /**
     * Mostrar modal de carga con spinner (Swal2)
     * @private
     */
    function showLoaderModal(options) {
        Swal.fire({
            title: options.title,
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div class="spinner-border" role="status" style="
                        width: 3rem;
                        height: 3rem;
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid #3498db;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                        margin: 0 auto 15px;
                    ">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p style="margin: 0; color: #666;">${options.message}</p>
                </div>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: (modal) => {
                // Inyectar CSS si no existe
                if (!document.getElementById('swal-loader-styles')) {
                    const style = document.createElement('style');
                    style.id = 'swal-loader-styles';
                    style.textContent = `
                        @keyframes spin {
                            0% { transform: rotate(0deg); }
                            100% { transform: rotate(360deg); }
                        }
                        .spinner-border {
                            display: inline-block;
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        });
    }

    /**
     * Obtener estado del preloader (para debugging)
     * @returns {Object}
     */
    function getStatus() {
        return {
            isPreloading: config.isPreloading,
            isPreloaded: config.isPreloaded,
            preloadError: config.preloadError ? config.preloadError.message : null,
            scriptCacheSize: config.scriptCache.size,
            moduleCacheSize: config.moduleCache.size,
            config: {
                preloadDelay: config.preloadDelay,
                idleThreshold: config.idleThreshold
            }
        };
    }

    /**
     * Limpiar cache (si es necesario)
     * @returns {void}
     */
    function clearCache() {
        config.scriptCache.clear();
        config.moduleCache.clear();
        console.log('[PrendaEditorPreloader] 🗑️ Cache limpiado');
    }

    /**
     * Reiniciar precarguía forzada
     * @returns {Promise<void>}
     */
    function forceReload() {
        config.isPreloading = false;
        config.isPreloaded = false;
        config.preloadError = null;
        clearCache();
        
        console.log('[PrendaEditorPreloader] 🔄 Reiniciando precarguía forzada...');
        return performPreload();
    }

    // ========================================================================
    // API PÚBLICA
    // ========================================================================
    return {
        /**
         * Iniciar precarguía automática (llamar en DOMContentLoaded)
         */
        start: startAutoPreload,

        /**
         * Cargar con loader visual
         */
        loadWithLoader: loadWithVisualLoader,

        /**
         * Obtener estado
         */
        getStatus: getStatus,

        /**
         * Limpiar cache
         */
        clearCache: clearCache,

        /**
         * Forzar recarga
         */
        forceReload: forceReload,

        /**
         * Precargar ahora mismo (sin esperar a DOMContentLoaded)
         */
        preloadNow: performPreload,

        /**
         * Verificar si está completamente precargado
         */
        isReady: function() {
            return config.isPreloaded;
        }
    };
})();

// ============================================================================
// INICIALIZACIÓN AUTOMÁTICA
// ============================================================================

// Iniciar precarguía cuando la página esté lista
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.PrendaEditorPreloader.start();
    });
} else {
    // Si el script se carga después de DOMContentLoaded
    window.PrendaEditorPreloader.start();
}
