/**
 * TelaProcessor - Utilidad centralizada para procesamiento de telas
 * 
 * Responsabilidades:
 * - Unificar lógica de procesamiento de telas (3 ubicaciones diferentes)
 * - Crear blob URLs para imágenes de tela
 * - Extraer color/tela de datos agregados
 * - Construir objetos de tela en formato consistente
 * - Cargar telas desde estructura de BD vs estructura de frontend
 * 
 * Ubicaciones originales eliminadas (duplicación -40%):
 * 1. cargarItemEnModal() - líneas 354-380 (26 líneas)
 * 2. agregarPrendaNueva() - líneas 872-890 (18 líneas)
 * 3. recolectarDatosParaEnvio() - líneas 1564-1600 (36 líneas)
 */

class TelaProcessor {
    /**
     * Crea blob URLs para imágenes de tela desde el storage de File objects
     * @param {Array<Object>} telasAgregadas - Array de telas con estructura {color, tela, imagenes: [{file, ...}]}
     * @returns {Array<Object>} Telas con blob URLs creadas
     */
    static crearBlobUrlsParaTelas(telasAgregadas) {
        if (!telasAgregadas || telasAgregadas.length === 0) {
            return [];
        }

        return telasAgregadas.map(tela => ({
            ...tela,
            imagenes: (tela.imagenes || []).map(img => {
                let blobUrl = null;
                if (img.file instanceof File) {
                    blobUrl = URL.createObjectURL(img.file);
                }
                return {
                    ...img,
                    blobUrl: blobUrl
                };
            })
        }));
    }

    /**
     * Extrae color y tela de telasAgregadas para usar en el objeto de prenda
     * @param {Array<Object>} telasConUrls - Array de telas procesadas
     * @returns {Object} {color: string|null, tela: string|null}
     */
    static extraerColorYTela(telasConUrls) {
        let colorPrenda = null;
        let telaPrenda = null;

        if (telasConUrls && telasConUrls.length > 0) {
            colorPrenda = telasConUrls[0].color || null;
            telaPrenda = telasConUrls[0].tela || null;
        }

        return { color: colorPrenda, tela: telaPrenda };
    }

    /**
     * Carga telas desde estructura de BD (propiedades raíz: tela, color, ref, imagenes_tela)
     * Usado en cargarItemEnModal() para prendas guardadas en BD
     * @param {Object} prenda - Objeto de prenda desde BD
     * @returns {Object} {telaObj: Object|null, procesada: boolean}
     */
    static cargarTelaDesdeBaseDatos(prenda) {
        if ((prenda.tela || prenda.color) && window.telasAgregadas) {
            const telaObj = {
                color: prenda.color || '',
                tela: prenda.tela || '',
                referencia: prenda.ref || prenda.referencia || '',  // BD usa 'ref', no 'referencia'
                imagenes: []
            };

            // Agregar imágenes de tela si existen
            // En BD están en 'imagenes_tela' (sin la primera imagen que es imagen_tela de portada)
            if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
                // La segunda imagen es la de tela real (primera es imagen_tela de portada)
                if (prenda.imagenes_tela.length > 1) {
                    telaObj.imagenes = [prenda.imagenes_tela[1]];  // Usar la segunda imagen (foto de tela)
                } else if (prenda.imagenes_tela.length === 1) {
                    // Si solo hay una, usarla
                    telaObj.imagenes = [prenda.imagenes_tela[0]];
                }
            }

            return { telaObj, procesada: true };
        } else {
            return { telaObj: null, procesada: false };
        }
    }

    /**
     * Actualiza telasAgregadas global con una nueva tela desde BD
     * @param {Object} telaObj - Objeto de tela a agregar
     */
    static agregarTelaAlStorage(telaObj) {
        if (!window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        window.telasAgregadas.length = 0;  // Limpiar telas anteriores
        window.telasAgregadas.push(telaObj);

        // Actualizar tabla de telas si existe
        if (window.actualizarTablaTelas) {
            window.actualizarTablaTelas();
        }
    }

    /**
     * Extrae primera imagen de tela para uso en templates
     * @param {Array<Object>} telasConUrls - Array de telas procesadas
     * @returns {string|null} Blob URL o path de imagen, o null
     */
    static extraerImagenTela(telasConUrls) {
        if (!telasConUrls || telasConUrls.length === 0) {
            return null;
        }

        const primeraTela = telasConUrls[0];
        if (!primeraTela.imagenes || primeraTela.imagenes.length === 0) {
            return null;
        }

        const primeraImagen = primeraTela.imagenes[0];

        // Intentar en orden: blobUrl > File > string path
        if (primeraImagen.blobUrl) {
            return primeraImagen.blobUrl;
        } else if (primeraImagen.file instanceof File) {
            const blobUrl = URL.createObjectURL(primeraImagen.file);
            return blobUrl;
        } else if (typeof primeraImagen === 'string') {
            return primeraImagen;
        }

        return null;
    }

    /**
     * Construye item para envío backend desde telas disponibles
     * Usa telasAgregadas (frontend) o estructura BD (tela, color, imagenes_tela)
     * @param {Object} prenda - Objeto de prenda del frontend o BD
     * @returns {Object} {itemSinCot: Object actualizado, imagenTelaUrl: string|null}
     */
    static construirItemDesdeTelas(prenda) {
        const itemSinCot = {};
        let imagenTelaUrl = null;

        // PRIMERA OPCIÓN: Usar telasAgregadas (frontend - usuario agregó telas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            itemSinCot.telas = prenda.telasAgregadas;
            const primeraTela = prenda.telasAgregadas[0];

            if (primeraTela.color) itemSinCot.color = primeraTela.color;
            if (primeraTela.tela) itemSinCot.tela = primeraTela.tela;

            // Extraer imagen de tela
            imagenTelaUrl = this.extraerImagenTela(prenda.telasAgregadas);
            if (imagenTelaUrl) itemSinCot.imagenTela = imagenTelaUrl;

            return { itemSinCot, imagenTelaUrl };
        }

        // SEGUNDA OPCIÓN: Usar estructura BD (tela, color, imagenes_tela en raíz)
        if ((prenda.tela || prenda.color) && prenda.imagenes_tela) {
            itemSinCot.color = prenda.color || null;
            itemSinCot.tela = prenda.tela || null;
            itemSinCot.ref = prenda.ref || null;

            // Agregar imagen de tela desde imagenes_tela
            // La segunda imagen es la de tela real (primera es imagen_tela de portada)
            if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
                if (prenda.imagenes_tela.length > 1) {
                    imagenTelaUrl = prenda.imagenes_tela[1];
                } else if (prenda.imagenes_tela.length === 1) {
                    imagenTelaUrl = prenda.imagenes_tela[0];
                }
            }

            if (imagenTelaUrl) itemSinCot.imagenTela = imagenTelaUrl;
            return { itemSinCot, imagenTelaUrl };
        }

        return { itemSinCot, imagenTelaUrl: null };
    }

    /**
     * Validación: Verifica si una prenda tiene datos válidos de tela
     * @param {Object} prenda - Objeto de prenda
     * @returns {boolean}
     */
    static tieneDatosDeTela(prenda) {
        const tieneTelasAgregadas = prenda.telasAgregadas && prenda.telasAgregadas.length > 0;
        const tienePropiedadesBD = (prenda.tela || prenda.color) && prenda.imagenes_tela;
        return tieneTelasAgregadas || tienePropiedadesBD;
    }

    /**
     * Limpia y restablece storage de telas
     */
    static limpiarStorage() {
        if (window.telasAgregadas) {
            window.telasAgregadas.length = 0;
        }
    }
}

// Exportar globalmente
window.TelaProcessor = TelaProcessor;
