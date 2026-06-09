/**
 * Supervisor Pedidos - Lazy loader para modulos pesados de edicion (Prenda/EPP).
 * Carga bajo demanda para reducir el costo inicial de /supervisor-pedidos.
 */
(function () {
    'use strict';

    const state = {
        loadingPromise: null,
        loaded: false,
    };

    const editorScripts = [
        '/js/utilidades/validation-service.js',
        '/js/utilidades/ui-modal-service.js',
        '/js/utilidades/deletion-service.js',
        '/js/utilidades/galeria-service.js',
        '/js/componentes/epp-agregar-pedido.js',
        '/js/componentes/prenda-form-collector.js',
        '/js/componentes/swal-utils.js',
        '/js/componentes/pedidos-novedad-helper.js',
        '/js/componentes/prenda-editor-pedidos-data-utils.js',
        '/js/componentes/prenda-editor-pedidos-fallback-utils.js',
        '/js/componentes/prenda-editor-pedidos-ui-utils.js',
        '/js/componentes/prenda-editor-pedidos-delete-utils.js',
        '/js/componentes/prenda-editor-pedidos-save-utils.js',
        '/js/componentes/prenda-editor-pedidos-edit-utils.js',
        '/js/componentes/prenda-editor-pedidos-adapter.js',
        '/js/componentes/prenda-agregar-pedido.js',
    ];

    function normalizePath(src) {
        try {
            return new URL(src, window.location.origin).pathname;
        } catch (_) {
            return String(src || '').split('?')[0];
        }
    }

    function ensureRegistry() {
        if (!(window.__spLoadedScriptPaths instanceof Set)) {
            window.__spLoadedScriptPaths = new Set();
        }
        return window.__spLoadedScriptPaths;
    }

    function scriptAlreadyPresent(pathname) {
        return Array.from(document.scripts || []).some((scriptTag) => {
            const rawSrc = scriptTag.getAttribute('src') || scriptTag.src || '';
            return normalizePath(rawSrc) === pathname;
        });
    }

    function loadScriptSequentially(src) {
        const pathname = normalizePath(src);
        const registry = ensureRegistry();

        if (registry.has(pathname) || scriptAlreadyPresent(pathname)) {
            registry.add(pathname);
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false;

            const timeoutId = setTimeout(() => {
                reject(new Error(`Timeout cargando ${pathname}`));
            }, 30000);

            script.onload = () => {
                clearTimeout(timeoutId);
                registry.add(pathname);
                resolve();
            };

            script.onerror = () => {
                clearTimeout(timeoutId);
                reject(new Error(`Error cargando ${pathname}`));
            };

            document.head.appendChild(script);
        });
    }

    async function ensureLoaded() {
        if (state.loaded) return;
        if (state.loadingPromise) return state.loadingPromise;

        state.loadingPromise = (async () => {
            for (const src of editorScripts) {
                await loadScriptSequentially(src);
            }
            state.loaded = true;
            window.dispatchEvent(new CustomEvent('supervisor-pedidos:editor-modules-ready'));
        })()
            .catch((error) => {
                state.loadingPromise = null;
                throw error;
            });

        return state.loadingPromise;
    }

    function installLazyBridge(functionName) {
        if (typeof window[functionName] === 'function') return;

        const bridge = async function (...args) {
            await ensureLoaded();
            const realFn = window[functionName];

            if (typeof realFn !== 'function' || realFn === bridge) {
                throw new Error(`Funcion no disponible tras lazy load: ${functionName}`);
            }

            return realFn(...args);
        };

        window[functionName] = bridge;
    }

    window.supervisorPedidosLazyEditorLoader = Object.freeze({
        ensureLoaded,
        isLoaded: () => state.loaded,
    });

    window.ensureSupervisorEditorModulesLoaded = ensureLoaded;

    // Puentes para onclick heredados en modales compartidos.
    installLazyBridge('agregarNuevaPrendaAPedido');
    installLazyBridge('editarPrendaDePedido');
    installLazyBridge('agregarNuevoEPPAPedido');
})();
