/**
 * PrendaDataTransformer - Transforma datos de prenda a estructura interna
 * 
 * Responsabilidad: Normalizar datos de diferentes fuentes (BD, localStorage, nuevas)
 * Patrón: Adapter + Factory
 */

console.log('[DEBUG]  PrendaDataTransformer.js cargado correctamente');

class PrendaDataTransformer {
    /**
     * Transformar datos de prenda a formato consistente
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {Object} Prenda transformada
     */
    static transformar(prendaRaw) {
        console.log('[PrendaDataTransformer] 🔍 INICIANDO TRANSFORMACIÓN');
        console.log('[PrendaDataTransformer] 📦 DATOS DE ENTRADA:', prendaRaw);
        
        if (!prendaRaw) {
            console.log('[PrendaDataTransformer] ❌ prendaRaw es null/undefined');
            return null;
        }

        // Convertir estructura relacional (cantidad_talla) a generosConTallas
        let generosConTallas = prendaRaw.generosConTallas || {};
        let cantidadesPorTalla = prendaRaw.cantidadesPorTalla || {};

        // Si viene en formato relacional nuevo (cantidad_talla: { DAMA: {S: 20, M: 20} })
        if (prendaRaw.cantidad_talla && typeof prendaRaw.cantidad_talla === 'object') {
            const relacional = prendaRaw.cantidad_talla;
            
            console.log('[PrendaDataTransformer] 🔄 Transformando cantidad_talla:', relacional);
            
            // Construir generosConTallas y cantidadesPorTalla desde relacional
            generosConTallas = {};
            cantidadesPorTalla = {};
            
            Object.entries(relacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    generosConTallas[genero.toLowerCase()] = {
                        tallas: Object.keys(tallasObj)
                    };
                    
                    // Agregar cantidades
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        cantidadesPorTalla[`${genero.toLowerCase()}-${talla}`] = cantidad;
                    });
                }
            });
            
            console.log('[PrendaDataTransformer]  Resultado:');
            console.log('[PrendaDataTransformer]   - generosConTallas:', generosConTallas);
            console.log('[PrendaDataTransformer]   - cantidadesPorTalla:', cantidadesPorTalla);
        }

        // Extraer datos de cotización con logs
        console.log('[PrendaDataTransformer] 🧵 EXTRAYENDO DATOS DE TELA:');
        const telaExtraida = this._extraerTela(prendaRaw);
        const colorExtraido = this._extraerColor(prendaRaw);
        const referenciaExtraida = this._extraerReferencia(prendaRaw);
        const telasAgregadasExtraidas = this._extraerTelasAgregadas(prendaRaw);
        
        console.log('[PrendaDataTransformer]   - Tela:', telaExtraida);
        console.log('[PrendaDataTransformer]   - Color:', colorExtraido);
        console.log('[PrendaDataTransformer]   - Referencia:', referenciaExtraida);
        console.log('[PrendaDataTransformer]   - Telas Agregadas:', telasAgregadasExtraidas);
        console.log('[PrendaDataTransformer]   - Imágenes:', prendaRaw.imagenes || prendaRaw.fotos || []);
        console.log('[PrendaDataTransformer]   - Variantes:', prendaRaw.variantes || {});
        console.log('[PrendaDataTransformer]   - Procesos:', prendaRaw.procesos || {});

        return {
            // Identidad
            id: prendaRaw.id || null,
            nombre_producto: prendaRaw.nombre_producto || prendaRaw.nombre_prenda || prendaRaw.nombre || '',
            descripcion: prendaRaw.descripcion || '',
            origen: prendaRaw.origen || 'bodega',

            // Imágenes
            imagenes: prendaRaw.imagenes || prendaRaw.fotos || [],
            imagenes_tela: prendaRaw.imagenes_tela || [],

            // Tela - Adaptar para estructura de cotización
            tela: this._extraerTela(prendaRaw),
            color: this._extraerColor(prendaRaw),
            referencia: this._extraerReferencia(prendaRaw),
            imagen_tela: prendaRaw.imagen_tela || null,
            
            // Telas agregadas desde cotización
            telasAgregadas: this._extraerTelasAgregadas(prendaRaw),

            // Tallas
            tallas: prendaRaw.tallas || prendaRaw.tallas_estructura || {},
            generosConTallas: generosConTallas,
            cantidadesPorTalla: cantidadesPorTalla,

            // Variantes/Variaciones
            variantes: prendaRaw.variantes || {},

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
        console.log('[PrendaDataTransformer.obtenerInfoTela] 🔍 INICIANDO OBTENCIÓN DE INFO TELA');
        console.log('[PrendaDataTransformer.obtenerInfoTela] 📦 PRENDA RECIBIDA:', prenda);
        
        if (!prenda) {
            console.log('[PrendaDataTransformer.obtenerInfoTela] ❌ prenda es null/undefined');
            return { tela: 'N/A', color: 'N/A', referencia: 'N/A' };
        }

        // Desde propiedades raíz (BD)
        if (prenda.tela || prenda.color) {
            console.log('[PrendaDataTransformer.obtenerInfoTela] 📋 USANDO PROPIEDADES RAÍZ');
            const resultadoRaiz = {
                tela: prenda.tela || 'N/A',
                color: prenda.color || 'N/A',
                referencia: prenda.referencia || 'N/A'
            };
            console.log('[PrendaDataTransformer.obtenerInfoTela]  RESULTADO RAÍZ:', resultadoRaiz);
            return resultadoRaiz;
        }

        // Desde telasAgregadas (prendas nuevas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            console.log('[PrendaDataTransformer.obtenerInfoTela] 📋 USANDO TELAS AGREGADAS');
            const tela = prenda.telasAgregadas[0];
            console.log('[PrendaDataTransformer.obtenerInfoTela] 📋 PRIMERA TELA:', tela);
            
            const resultadoAgregadas = {
                tela: tela.tela || 'N/A',
                color: tela.color || 'N/A',
                referencia: tela.referencia || 'N/A'
            };
            console.log('[PrendaDataTransformer.obtenerInfoTela]  RESULTADO AGREGADAS:', resultadoAgregadas);
            return resultadoAgregadas;
        }

        console.log('[PrendaDataTransformer.obtenerInfoTela]  NO SE ENCONTRARON DATOS DE TELA');
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

    /**
     * Extraer información de tela desde estructura de cotización
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {string}
     */
    static _extraerTela(prendaRaw) {
        console.log('[PrendaDataTransformer._extraerTela] 🔍 Buscando tela...');
        console.log('[PrendaDataTransformer._extraerTela] 📦 prendaRaw.telas:', prendaRaw.telas);
        
        // Desde telasAgregadas (estructura de cotización)
        if (prendaRaw.telas && Array.isArray(prendaRaw.telas) && prendaRaw.telas.length > 0) {
            const primeraTela = prendaRaw.telas[0];
            console.log('[PrendaDataTransformer._extraerTela] 📋 Primera tela:', primeraTela);
            console.log('[PrendaDataTransformer._extraerTela] 📋 primeraTela.tela:', primeraTela.tela);
            
            const nombreTela = primeraTela.tela ? primeraTela.tela.nombre : '';
            console.log('[PrendaDataTransformer._extraerTela]  Tela extraída:', nombreTela);
            return nombreTela;
        }
        
        console.log('[PrendaDataTransformer._extraerTela]  No hay telas, usando propiedad directa');
        // Desde propiedad directa
        const telaDirecta = prendaRaw.tela || '';
        console.log('[PrendaDataTransformer._extraerTela]  Tela directa:', telaDirecta);
        return telaDirecta;
    }

    /**
     * Extraer color desde estructura de cotización
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {string}
     */
    static _extraerColor(prendaRaw) {
        console.log('[PrendaDataTransformer._extraerColor] 🔍 Buscando color...');
        console.log('[PrendaDataTransformer._extraerColor] 📦 prendaRaw.telas:', prendaRaw.telas);
        
        // Desde telasAgregadas (estructura de cotización)
        if (prendaRaw.telas && Array.isArray(prendaRaw.telas) && prendaRaw.telas.length > 0) {
            const primeraTela = prendaRaw.telas[0];
            console.log('[PrendaDataTransformer._extraerColor] 📋 Primera tela:', primeraTela);
            console.log('[PrendaDataTransformer._extraerColor] 📋 primeraTela.color:', primeraTela.color);
            
            const nombreColor = primeraTela.color ? primeraTela.color.nombre : '';
            console.log('[PrendaDataTransformer._extraerColor]  Color extraído:', nombreColor);
            return nombreColor;
        }
        
        console.log('[PrendaDataTransformer._extraerColor]  No hay telas, usando propiedad directa');
        // Desde propiedad directa
        const colorDirecto = prendaRaw.color || '';
        console.log('[PrendaDataTransformer._extraerColor]  Color directo:', colorDirecto);
        return colorDirecto;
    }

    /**
     * Extraer referencia desde estructura de cotización
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {string}
     */
    static _extraerReferencia(prendaRaw) {
        console.log('[PrendaDataTransformer._extraerReferencia] 🔍 Buscando referencia...');
        console.log('[PrendaDataTransformer._extraerReferencia] 📦 prendaRaw.telas:', prendaRaw.telas);
        
        // Desde telasAgregadas (estructura de cotización)
        if (prendaRaw.telas && Array.isArray(prendaRaw.telas) && prendaRaw.telas.length > 0) {
            const primeraTela = prendaRaw.telas[0];
            console.log('[PrendaDataTransformer._extraerReferencia] 📋 Primera tela:', primeraTela);
            console.log('[PrendaDataTransformer._extraerReferencia] 📋 primeraTela.referencia:', primeraTela.referencia);
            
            const referencia = primeraTela.referencia || '';
            console.log('[PrendaDataTransformer._extraerReferencia]  Referencia extraída:', referencia);
            return referencia;
        }
        
        console.log('[PrendaDataTransformer._extraerReferencia]  No hay telas, usando propiedades directas');
        // Desde propiedad directa
        const referenciaDirecta = prendaRaw.ref || prendaRaw.referencia || '';
        console.log('[PrendaDataTransformer._extraerReferencia]  Referencia directa:', referenciaDirecta);
        return referenciaDirecta;
    }

    /**
     * Extraer telas agregadas desde estructura de cotización
     * @param {Object} prendaRaw - Datos crudos de prenda
     * @returns {Array}
     */
    static _extraerTelasAgregadas(prendaRaw) {
        console.log('[PrendaDataTransformer._extraerTelasAgregadas] 🔍 Buscando telas agregadas...');
        console.log('[PrendaDataTransformer._extraerTelasAgregadas] 📦 prendaRaw.telas:', prendaRaw.telas);
        
        if (prendaRaw.telas && Array.isArray(prendaRaw.telas)) {
            const telasFormateadas = prendaRaw.telas.map(tela => ({
                id: tela.id,
                tela: tela.tela ? tela.tela.nombre : '',
                color: tela.color ? tela.color.nombre : '',
                referencia: tela.referencia || '',
                fotos: tela.fotos || []
            }));
            
            console.log('[PrendaDataTransformer._extraerTelasAgregadas]  Telas formateadas:', telasFormateadas);
            return telasFormateadas;
        }
        
        console.log('[PrendaDataTransformer._extraerTelasAgregadas]  No hay telas para formatear');
        return [];
    }
}

window.PrendaDataTransformer = PrendaDataTransformer;

