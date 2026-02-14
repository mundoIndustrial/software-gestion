/**
 * 📦 Servicio de Carga de Datos
 * Responsabilidad: Orquestar la carga de todos los módulos loaders en orden
 */

class PrendaEditorDataLoaderService {
    /**
     * Cargar todos los módulos loaders
     * @static
     * @async
     * @param {Object} prenda - Prenda a cargar
     * @returns {Promise<void>}
     */
    static async cargarTodos(prenda) {
        console.log('[DataLoader] 🔄 Iniciando carga de todos los módulos...');

        try {
            // 1. Basicos
            await this._ejecutarLoader('PrendaEditorBasicos', prenda);

            // 2. Imágenes
            await this._ejecutarLoader('PrendaEditorImagenes', prenda);

            // 3. Telas
            await this._ejecutarLoader('PrendaEditorTelas', prenda);

            // 4. Variaciones (manga, bolsillos, broche)
            await this._ejecutarLoader('PrendaEditorVariaciones', prenda);

            // 5. Tallas y cantidades
            await this._ejecutarLoaderConMetodos('PrendaEditorTallas', prenda, [
                { metodo: 'cargar', args: [prenda] },
                { metodo: 'marcarGeneros', args: [prenda] }
            ]);

            // 6. Asignación de colores
            await this._ejecutarLoader('PrendaEditorColores', prenda);

            // 7. Procesos
            await this._ejecutarLoader('PrendaEditorProcesos', prenda);

            console.log(' [DataLoader] Todos los módulos cargados correctamente');
        } catch (error) {
            console.error(' [DataLoader] Error:', error);
            throw error;
        }
    }

    /**
     * Ejecutar método cargar de un loader
     * @static
     * @private
     * @param {string} loaderName - Nombre de la clase loader
     * @param {Object} prenda - Prenda a cargar
     */
    static _ejecutarLoader(loaderName, prenda) {
        const loader = window[loaderName];
        if (typeof loader !== 'undefined' && typeof loader.cargar === 'function') {
            loader.cargar(prenda);
        } else {
            console.warn(`[DataLoader]  ${loaderName} no disponible`);
        }
    }

    /**
     * Ejecutar múltiples métodos de un loader
     * @static
     * @private
     * @param {string} loaderName - Nombre de la clase loader
     * @param {Object} prenda - Prenda a cargar
     * @param {Array} metodos - Array de {metodo, args}
     */
    static _ejecutarLoaderConMetodos(loaderName, prenda, metodos) {
        const loader = window[loaderName];
        if (typeof loader === 'undefined') {
            console.warn(`[DataLoader]  ${loaderName} no disponible`);
            return;
        }

        metodos.forEach(({ metodo, args }) => {
            if (typeof loader[metodo] === 'function') {
                loader[metodo](...args);
            }
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorDataLoaderService;
}
