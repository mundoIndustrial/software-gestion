/**
 * PrendaDataTransformer - Transforma datos de prenda a estructura interna
 * 
 * Responsabilidad: Normalizar datos de diferentes fuentes (BD, localStorage, nuevas)
 * Patrón: Adapter + Factory
 */

class PrendaDataTransformer {
    /**
     * Transformar datos de prenda a formato consistente
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {Object} Prenda transformada
     */
    static transformar(prendaRaw) {
        if (!prendaRaw) return null;

        return {
            // Identidad
            id: prendaRaw.id || null,
            nombre_producto: prendaRaw.nombre_producto || prendaRaw.nombre || '',
            descripcion: prendaRaw.descripcion || '',
            origen: prendaRaw.origen || 'bodega',

            // Imágenes
            imagenes: prendaRaw.imagenes || prendaRaw.fotos || [],
            imagenes_tela: prendaRaw.imagenes_tela || [],

            // Tela
            tela: prendaRaw.tela || '',
            color: prendaRaw.color || '',
            referencia: prendaRaw.ref || prendaRaw.referencia || '',
            imagen_tela: prendaRaw.imagen_tela || null,

            // Tallas
            tallas: prendaRaw.tallas || prendaRaw.tallas_estructura || {},
            generosConTallas: prendaRaw.generosConTallas || {},
            cantidadesPorTalla: prendaRaw.cantidadesPorTalla || {},

            // Variantes/Variaciones
            variantes: prendaRaw.variantes || {},
            telasAgregadas: prendaRaw.telasAgregadas || [],

            // Procesos
            procesos: prendaRaw.procesos || {}
        };
    }

    /**
     * Obtener foto principal de prenda
     * @param {Object} prenda - Prenda transformada
     * @returns {string|null}
     */
    static obtenerFotoPrincipal(prenda) {
        if (!prenda) return null;
        const imagenes = prenda.imagenes || [];
        return ImageProcessor.obtenerFotoPrincipal(imagenes);
    }

    /**
     * Obtener foto de tela
     * @param {Object} prenda - Prenda transformada
     * @returns {string|null}
     */
    static obtenerFotoTela(prenda) {
        if (!prenda) return null;

        // Desde imagenes_tela (estructura BD)
        if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            // Segunda imagen es la tela real (primera es portada)
            if (prenda.imagenes_tela.length > 1) {
                return ImageProcessor.procesarImagen(prenda.imagenes_tela[1]);
            }
            if (prenda.imagenes_tela.length > 0) {
                return ImageProcessor.procesarImagen(prenda.imagenes_tela[0]);
            }
        }

        // Desde telasAgregadas (prendas nuevas)
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
            const tela = prenda.telasAgregadas[0];
            if (tela.imagenes && Array.isArray(tela.imagenes) && tela.imagenes.length > 0) {
                return ImageProcessor.procesarImagen(tela.imagenes[0]);
            }
        }

        // Imagen_tela (campo individual)
        if (prenda.imagen_tela) {
            return ImageProcessor.procesarImagen(prenda.imagen_tela);
        }

        return null;
    }

    /**
     * Obtener información de tela
     * @param {Object} prenda - Prenda transformada
     * @returns {Object} {tela, color, referencia}
     */
    static obtenerInfoTela(prenda) {
        if (!prenda) return { tela: 'N/A', color: 'N/A', referencia: 'N/A' };

        // Desde propiedades raíz (BD)
        if (prenda.tela || prenda.color) {
            return {
                tela: prenda.tela || 'N/A',
                color: prenda.color || 'N/A',
                referencia: prenda.referencia || 'N/A'
            };
        }

        // Desde telasAgregadas (prendas nuevas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            const tela = prenda.telasAgregadas[0];
            return {
                tela: tela.tela || 'N/A',
                color: tela.color || 'N/A',
                referencia: tela.referencia || 'N/A'
            };
        }

        return { tela: 'N/A', color: 'N/A', referencia: 'N/A' };
    }

    /**
     * Contar variaciones aplicadas
     * @param {Object} prenda - Prenda transformada
     * @returns {number}
     */
    static contarVariaciones(prenda) {
        if (!prenda || !prenda.variantes) return 0;

        const variacionesMapeo = [
            'tipo_manga',
            'tiene_bolsillos',
            'tipo_broche',
            'tiene_reflectivo'
        ];

        return variacionesMapeo.filter(key => {
            const valor = prenda.variantes[key];
            return valor && valor !== 'No aplica' && valor !== false;
        }).length;
    }

    /**
     * Contar procesos configurados
     * @param {Object} prenda - Prenda transformada
     * @returns {number}
     */
    static contarProcesos(prenda) {
        if (!prenda || !prenda.procesos) return 0;

        return Object.entries(prenda.procesos).filter(
            ([_, proc]) => proc && (proc.datos !== null || proc.tipo)
        ).length;
    }

    /**
     * Obtener total de tallas
     * @param {Object} prenda - Prenda transformada
     * @returns {number}
     */
    static contarTallas(prenda) {
        if (!prenda) return 0;

        // Desde generosConTallas
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            return Object.values(prenda.generosConTallas).reduce((total, data) => {
                return total + (data.tallas ? data.tallas.length : 0);
            }, 0);
        }

        // Desde tallas (array)
        if (Array.isArray(prenda.tallas)) {
            return prenda.tallas.length;
        }

        // Desde tallas (object)
        if (typeof prenda.tallas === 'object') {
            return Object.keys(prenda.tallas).length;
        }

        return 0;
    }
}

window.PrendaDataTransformer = PrendaDataTransformer;
console.log('✓ [PRENDA-DATA-TRANSFORMER] Cargado correctamente');
