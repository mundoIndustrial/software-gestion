/**
 *  LOADER MODULAR - Prendas Editor (Refactorizado)
 * 
 * Responsabilidad: Cargar todos los módulos especializados de edición de prendas
 * 
 * Estructura:
 * - Loaders → Cargan datos en formulario
 * - Services → Obtienen datos del servidor
 * - ModalHandlers → Gesionan el modal
 * - Orquestador → Coordina los módulos
 */

globalThis.PrendaEditorLoader = (function() {
    let isLoading = false;
    let isLoaded = false;
    let loadError = null;

    function getScriptPath(src) {
        try {
            return new URL(src, globalThis.location.origin).pathname;
        } catch (error) {
            return String(src || '').split('?')[0];
        }
    }

    function getGlobalLoadedScriptsRegistry() {
        if (!(globalThis.__spLoadedScriptPaths instanceof Set)) {
            globalThis.__spLoadedScriptPaths = new Set();
        }
        return globalThis.__spLoadedScriptPaths;
    }

    /**
     * Cargar todos los módulos necesarios
     */
    function load() {
        if (isLoading || isLoaded) {
            return Promise.resolve();
        }

        isLoading = true;

        //  Módulos a cargar (en orden)
        const modulesToLoad = [
            // Servicios especializados (sin dependencias)
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-loader-service.js',
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-normalizer-service.js',
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-cotizacion-service.js',
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-data-loader.js',
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-service.js',
            
            // Gestión del modal
            '/js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js',

            // Loaders (sin dependencias mutuas)
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js',

            //  NOTA: prenda-editor.js ya se carga directamente en blade.php
            // para evitar duplicación y SyntaxError de redeclaración

            // Módulos dependientes (manejadores de variaciones, etc.)
            '/js/modulos/crear-pedido/prendas/manejadores-variaciones.js',

            // Procesos: servicios desacoplados + renderizador + manejadores
            '/js/modulos/crear-pedido/procesos/proceso-galeria-service.js',
            '/js/modulos/crear-pedido/procesos/proceso-delete-service.js',
            '/js/modulos/crear-pedido/procesos/proceso-modal-loader-service.js',
            '/js/modulos/crear-pedido/procesos/proceso-card-renderer-service.js',
            '/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js',
            '/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js',
        ];

        //  Cargar scripts en paralelo
        const promises = modulesToLoad.map(url => loadScript(url));

        return Promise.all(promises)
            .then(() => {
                isLoaded = true;
                isLoading = false;
                globalThis.dispatchEvent(new CustomEvent('prendaEditorRefactoredReady'));
            })
            .catch(error => {
                loadError = error;
                isLoading = false;
                throw error;
            });
    }

    /**
     * Cargar un script individual (con deduplicación)
     */
    function loadScript(url) {
        return new Promise((resolve, reject) => {
            const scriptPath = getScriptPath(url);
            const registry = getGlobalLoadedScriptsRegistry();

            if (registry.has(scriptPath)) {
                return resolve();
            }

            // Verificar si el script ya está en el DOM (cargado por entry/blade)
            const existente = Array.from(document.scripts || []).find((scriptTag) => {
                const rawSrc = scriptTag.getAttribute('src') || '';
                const normalizedSrc = scriptTag.src || '';
                const currentPath = getScriptPath(rawSrc || normalizedSrc);
                return currentPath === scriptPath;
            });
            if (existente) {
                registry.add(scriptPath);
                return resolve();
            }

            // Reserva para evitar carreras con otros loaders.
            registry.add(scriptPath);

            const script = document.createElement('script');
            script.src = url;
            script.async = false;
            script.onload = resolve;
            script.onerror = () => {
                registry.delete(scriptPath);
                reject(new Error(`Error cargando ${url}`));
            };
            document.head.appendChild(script);
        });
    }

    /**
     * Obtener instancia de PrendaEditor
     */
    function getPrendaEditor(options = {}) {
        if (!isLoaded) {
            console.warn('[PrendaEditor] Módulos aún no cargados');
            return null;
        }

        if (typeof PrendaEditor === 'undefined') {
        }

        return new PrendaEditor(options);
    }

    /**
     * API pública
     */
    return {
        load,
        isLoaded: () => isLoaded,
        isLoading: () => isLoading,
        getError: () => loadError,
        getPrendaEditor
    };
})();

/**
 *  AUTO-LOAD EN DOCUMENTO LISTO
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof globalThis.PrendaEditorRefactoredLoader !== 'undefined') {
        globalThis.PrendaEditorRefactoredLoader.load()
            .then(() => {
                console.log(' [PrendaEditor] Sistema listo para editar prendas');
            })
            .catch(error => {
                console.error('[PrendaEditor] Error al cargar:', error);
            });
    }
});
