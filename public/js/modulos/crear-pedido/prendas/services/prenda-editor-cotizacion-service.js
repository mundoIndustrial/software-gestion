/**
 *  Servicio de Lógica de Cotización
 * Responsabilidad: Aplicar lógica específica según tipo de cotización
 */

class PrendaEditorCotizacionService {
    /**
     * Aplicar lógica de origen desde cotización
     * @static
     * @param {Object} prenda - Prenda a procesar
     * @param {Object} cotizacion - Cotización actual
     * @returns {Object} Prenda procesada
     */
    static aplicarLogica(prenda, cotizacion) {
        if (!cotizacion || !prenda) {
            return prenda;
        }

        // Obtener datos de cotización
        const tipoNombre = cotizacion.tipo_nombre || cotizacion.tipo_cotizacion?.nombre || '';
        const tipoId = cotizacion.tipo_cotizacion_id;

        // Si es Reflectivo o Logo → forzar origen = 'bodega'
        if (this._esReflectivoOLogo(tipoNombre, tipoId)) {
            prenda.origen = 'bodega';
            console.log(' [Cotización] Origen forzado a bodega');
        }

        return prenda;
    }

    /**
     * Verificar si es cotización de Reflectivo o Logo
     * @static
     * @private
     * @param {string} tipoNombre - Nombre del tipo
     * @param {number} tipoId - ID del tipo
     * @returns {boolean}
     */
    static _esReflectivoOLogo(tipoNombre, tipoId) {
        return (
            tipoNombre.toLowerCase().includes('reflectivo') ||
            tipoNombre.toLowerCase().includes('logo') ||
            tipoId === 4 ||
            tipoId === 3
        );
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorCotizacionService;
}
