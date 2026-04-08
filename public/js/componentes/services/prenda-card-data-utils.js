/**
 * PrendaCardDataUtils
 * Utilidades de datos para PrendaCardService.
 */
globalThis.PrendaCardDataUtils = {
    resolverAsignacionesColoresPorTalla(prenda) {
        const fuenteDirecta = prenda?.asignacionesColoresPorTalla;
        if (fuenteDirecta && typeof fuenteDirecta === 'object' && Object.keys(fuenteDirecta).length > 0) {
            return fuenteDirecta;
        }
        return {};
    },

    colectarFotosProceso(datos, { incluirImagenesFallback = false, contextoLog = 'PrendaCardService', debugLog } = {}) {
        let fotos = [];

        if (Array.isArray(datos?.fotosGenerales) && datos.fotosGenerales.length > 0) {
            fotos = [...datos.fotosGenerales];
        }

        if (Array.isArray(datos?.fotosGeneralesFiles) && datos.fotosGeneralesFiles.length > 0) {
            if (typeof debugLog === 'function') {
                debugLog(`[${contextoLog}] Agregando fotosGeneralesFiles (${datos.fotosGeneralesFiles.length}) a fotosDisplay`);
            }
            fotos = [...fotos, ...datos.fotosGeneralesFiles];
        } else if (Array.isArray(datos?.imagenesFiles) && datos.imagenesFiles.length > 0) {
            if (typeof debugLog === 'function') {
                debugLog(`[${contextoLog}] Agregando imagenesFiles (${datos.imagenesFiles.length}) a fotosDisplay`);
            }
            fotos = [...fotos, ...datos.imagenesFiles];
        } else if (incluirImagenesFallback && Array.isArray(datos?.imagenes) && datos.imagenes.length > 0 && fotos.length === 0) {
            fotos = [...datos.imagenes];
        }

        return fotos;
    }
};
