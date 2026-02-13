/**
 * 📦 Servicio de Carga de Módulos
 * Responsabilidad: Garantizar que todos los módulos estén disponibles globalmente
 */

class PrendaEditorLoaderService {
    static _modulosEnCarga = false;
    static _modulosCargados = false;

    /**
     * Garantizar módulos disponibles
     * @static
     */
    static garantizar() {
        // Si ya está cargando o ya cargó, no hacer nada
        if (this._modulosCargados || this._modulosEnCarga) {
            return;
        }

        // Si los módulos ya están disponibles, marcar como cargados
        if (typeof PrendaModalManager !== 'undefined') {
            console.log('[LoaderService] ✅ Módulos ya disponibles globalmente');
            this._modulosCargados = true;
            return;
        }

        // Si el loader está disponible, usarlo
        if (typeof window.PrendaEditorLoader !== 'undefined') {
            this._modulosEnCarga = true;
            console.log('[LoaderService] ⚙️ Usando PrendaEditorLoader...');
            
            window.PrendaEditorLoader.load()
                .then(() => {
                    console.log('[LoaderService] ✅ Módulos cargados vía loader');
                    this._modulosCargados = true;
                    this._modulosEnCarga = false;
                })
                .catch(error => {
                    console.error('[LoaderService] ❌ Error en loader:', error);
                    this._modulosEnCarga = false;
                });
            return;
        }

        // 🆘 Fallback: Cargar módulos manualmente
        this._cargarManualmente();
    }

    /**
     * Cargar módulos manualmente como fallback
     * @private
     * @static
     */
    static _cargarManualmente() {
        this._modulosEnCarga = true;
        console.warn('[LoaderService] ⚠️ Cargando módulos manualmente...');
        
        const modulesToLoad = [
            '/js/modulos/crear-pedido/prendas/services/prenda-editor-service.js',
            '/js/modulos/crear-pedido/prendas/modalHandlers/prenda-modal-manager.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-basicos.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-imagenes.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-telas.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-variaciones.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-tallas.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-colores.js',
            '/js/modulos/crear-pedido/prendas/loaders/prenda-editor-procesos.js',
        ];

        let cargados = 0;
        modulesToLoad.forEach(url => {
            const script = document.createElement('script');
            script.src = url + '?v=' + Date.now();
            script.async = true;
            script.onload = () => {
                cargados++;
                if (cargados === modulesToLoad.length) {
                    console.log('[LoaderService] ✅ Todos los módulos cargados');
                    this._modulosCargados = true;
                    this._modulosEnCarga = false;
                }
            };
            script.onerror = () => {
                console.error('[LoaderService] ❌ Error cargando:', url);
                cargados++;
                if (cargados === modulesToLoad.length) {
                    this._modulosEnCarga = false;
                }
            };
            document.head.appendChild(script);
        });

        console.log('[LoaderService] 📦 Carga solicitada para ' + modulesToLoad.length + ' módulos');
    }

    /**
     * Esperar a que los módulos estén cargados
     * @static
     * @returns {Promise<void>}
     */
    static async esperar() {
        if (this._modulosCargados) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const check = setInterval(() => {
                if (this._modulosCargados) {
                    clearInterval(check);
                    resolve();
                }
            }, 100);

            // Timeout de seguridad
            setTimeout(() => {
                clearInterval(check);
                resolve();
            }, 5000);
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorLoaderService;
}
