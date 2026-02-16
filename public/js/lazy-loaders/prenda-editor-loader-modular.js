/**
 * 🎯 LOADER MODULAR - Prendas Editor (Refactorizado)
 * 
 * Responsabilidad: Cargar todos los módulos especializados de edición de prendas
 * 
 * Estructura:
 * - Loaders → Cargan datos en formulario
 * - Services → Obtienen datos del servidor
 * - ModalHandlers → Gesionan el modal
 * - Orquestador → Coordina los módulos
 */

window.PrendaEditorLoader = (function() {
    let isLoading = false;
    let isLoaded = false;
    let loadError = null;

    /**
     * Cargar todos los módulos necesarios
     */
    function load() {
        if (isLoading || isLoaded) {
            return Promise.resolve();
        }

        isLoading = true;

        // 📦 Módulos a cargar (en orden)
        const modulesToLoad = [
            // Servicios especializados (sin dependencias)
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-loader-service.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-normalizer-service.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-cotizacion-service.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-data-loader.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-service.js?v=' + Date.now(),
            
            // Gestión del modal
            '/js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js?v=' + Date.now(),

            // Loaders (sin dependencias mutuas)
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js?v=' + Date.now(),

            // 🚫 NOTA: prenda-editor.js ya se carga directamente en blade.php
            // para evitar duplicación y SyntaxError de redeclaración

            // Módulos dependientes (manejadores de variaciones, etc.)
            '/js/modulos/crear-pedido/prendas/manejadores-variaciones.js?v=' + Date.now(),

            // Procesos: renderizador de tarjetas y manejadores de checkboxes
            '/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js?v=' + Date.now(),
            '/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js?v=' + Date.now(),
        ];

        // ⚡ Cargar scripts en paralelo
        const promises = modulesToLoad.map(url => loadScript(url));

        return Promise.all(promises)
            .then(() => {
                console.log(' [PrendaEditor] Todos los módulos cargados');
                isLoaded = true;
                isLoading = false;
                window.dispatchEvent(new CustomEvent('prendaEditorRefactoredReady'));
            })
            .catch(error => {
                console.error(' [PrendaEditor] Error cargando módulos:', error);
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
            // Extraer path sin query string para comparar
            const urlPath = url.split('?')[0];
            
            // Verificar si el script ya está en el DOM (cargado por <script defer> del blade)
            const existente = document.querySelector(`script[src*="${urlPath}"]`);
            if (existente) {
                console.log(`[PrendaEditor] Script ya existe en DOM, skip: ${urlPath}`);
                return resolve();
            }
            
            const script = document.createElement('script');
            script.src = url;
            script.async = false;
            script.onload = resolve;
            script.onerror = () => reject(new Error(`Error cargando ${url}`));
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
            console.error('[PrendaEditor] Clase PrendaEditor no disponible');
            return null;
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
 * 🚀 AUTO-LOAD EN DOCUMENTO LISTO
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.PrendaEditorRefactoredLoader !== 'undefined') {
        window.PrendaEditorRefactoredLoader.load()
            .then(() => {
                console.log(' [PrendaEditor] Sistema listo para editar prendas');
            })
            .catch(error => {
                console.error(' [PrendaEditor] Error inicializando:', error);
            });
    }
});
