/**
 * PrendaDataBuilder - Builder para construcción de objetos de prenda
 * 
 * Responsabilidades:
 * - Centralizar construcción de prendaNueva object
 * - Simplificar generosConTallas building logic (50% reducción legibilidad)
 * - Encapsular lógica compleja de datos en métodos claros
 * - Proporcionar métodos reutilizables para diferentes contextos
 * 
 * Ubicaciones originales eliminadas (duplicación -50%):
 * 1. agregarPrendaNueva() - 200+ líneas de construcción
 * 2. cargarItemEnModal() - 150+ líneas de construcción similar
 * 3. recolectarDatosParaEnvio() - 180+ líneas de construcción de item
 */

class PrendaDataBuilder {
    /**
     * Construye generosConTallas desde tallasPorGenero y cantidadesPorTalla
     * Convierte estructura plana (genero-talla) a anidada (genero -> talla -> cantidad)
     * 
     * @param {Array<Object>} tallasPorGenero - [{genero: 'dama', tallas: ['S', 'M'], tipo: null}, ...]
     * @param {Object} cantidadesPorTalla - {'dama-S': 5, 'dama-M': 3, ...}
     * @returns {Object} {dama: {S: 5, M: 3}, caballero: {...}}
     */
    static construirGenerosConTallas(tallasPorGenero, cantidadesPorTalla) {
        console.log(' PrendaDataBuilder: Construyendo generosConTallas');
        console.log('   tallasPorGenero:', tallasPorGenero);
        console.log('   cantidadesPorTalla:', cantidadesPorTalla);

        const generosConTallas = {};

        tallasPorGenero.forEach(tallaData => {
            const generoKey = tallaData.genero;
            generosConTallas[generoKey] = {};

            if (tallaData.tallas && Array.isArray(tallaData.tallas)) {
                tallaData.tallas.forEach(talla => {
                    const key = `${generoKey}-${talla}`;
                    const cantidad = cantidadesPorTalla[key] || 0;

                    if (cantidad > 0) {
                        generosConTallas[generoKey][talla] = cantidad;
                        console.log(`   ✓ ${generoKey}.${talla} = ${cantidad}`);
                    }
                });
            }
        });

        console.log('   ✓ generosConTallas construido:', generosConTallas);
        return generosConTallas;
    }

    /**
     * Construye objeto completo de prenda nueva para almacenamiento interno
     * 
     * @param {Object} datos - {
     *   nombrePrenda: string,
     *   descripcion: string,
     *   genero: string,
     *   origen: string,
     *   imagenesConUrls: Array,
     *   telasConUrls: Array,
     *   tallasPorGenero: Array,
     *   variacionesConfiguradas: Object,
     *   procesosConfigurables: Object,
     *   cantidadesPorTalla: Object,
     *   colorPrenda: string,
     *   telaPrenda: string
     * }
     * @returns {Object} prendaNueva completamente formada
     */
    static construirPrendaNueva(datos) {
        console.log(' PrendaDataBuilder: Construyendo prendaNueva');

        // Validar datos esenciales
        if (!datos.nombrePrenda) {
            throw new Error('PrendaDataBuilder: nombrePrenda es requerido');
        }
        if (!datos.genero) {
            throw new Error('PrendaDataBuilder: genero es requerido');
        }

        // Construir generosConTallas
        const generosConTallas = this.construirGenerosConTallas(
            datos.tallasPorGenero || [],
            datos.cantidadesPorTalla || {}
        );

        const prendaNueva = {
            nombre_producto: datos.nombrePrenda,
            descripcion: datos.descripcion || '',
            genero: datos.genero,
            origen: datos.origen || 'bodega',
            imagenes: datos.imagenesConUrls || [],
            telas: [],
            telasAgregadas: datos.telasConUrls || [],
            tallas: datos.tallasPorGenero || [],
            variantes: datos.variacionesConfiguradas || {},
            procesos: datos.procesosConfigurables || {},
            cantidadesPorTalla: datos.cantidadesPorTalla || {},
            generosConTallas: generosConTallas,
            color: datos.colorPrenda || null,
            tela: datos.telaPrenda || null
        };

        console.log('   ✓ Prenda nueva construida:', prendaNueva);
        return prendaNueva;
    }

    /**
     * Extrae y procesa datos básicos del formulario modal
     * Centraliza acceso a inputs con validación
     * 
     * @returns {Object} {nombrePrenda, origen, descripcion}
     */
    static extraerDatosFormularioBasico() {
        console.log(' PrendaDataBuilder: Extrayendo datos básicos del formulario');

        const nombrePrenda = document.getElementById('nueva-prenda-nombre')?.value?.trim();
        const origen = document.getElementById('nueva-prenda-origen-select')?.value;
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();

        console.log('   Datos extraídos:', { nombrePrenda, origen, descripcion });

        return { nombrePrenda, origen, descripcion };
    }

    /**
     * Determina género basándose en tallas seleccionadas
     * 
     * @param {Object} tallasSeleccionadas - window.tallasSeleccionadas state
     * @returns {string|null} 'dama', 'caballero', 'unisex', o null
     */
    static determinarGenero(tallasSeleccionadas) {
        console.log('👗 PrendaDataBuilder: Determinando género desde tallas');

        const tienetallasDama = tallasSeleccionadas?.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas?.caballero?.tallas?.length > 0;

        let genero = null;
        if (tienetallasDama && !tieneTallasCaballero) {
            genero = 'dama';
        } else if (tieneTallasCaballero && !tienetallasDama) {
            genero = 'caballero';
        } else if (tienetallasDama && tieneTallasCaballero) {
            genero = 'unisex';
        }

        console.log(`   ✓ Género determinado: ${genero}`);
        return genero;
    }

    /**
     * Construye array de tallas por género desde state
     * 
     * @param {Object} tallasSeleccionadas - window.tallasSeleccionadas state
     * @returns {Array<Object>} [{genero: 'dama', tallas: [...], tipo: null}, ...]
     */
    static construirTallasPorGenero(tallasSeleccionadas) {
        console.log('📏 PrendaDataBuilder: Construyendo tallasPorGenero');

        const tallasPorGenero = [];
        const tienetallasDama = tallasSeleccionadas?.dama?.tallas?.length > 0;
        const tieneTallasCaballero = tallasSeleccionadas?.caballero?.tallas?.length > 0;

        if (tienetallasDama) {
            tallasPorGenero.push({
                genero: 'dama',
                tallas: tallasSeleccionadas.dama.tallas,
                tipo: tallasSeleccionadas.dama.tipo
            });
        }

        if (tieneTallasCaballero) {
            tallasPorGenero.push({
                genero: 'caballero',
                tallas: tallasSeleccionadas.caballero.tallas,
                tipo: tallasSeleccionadas.caballero.tipo
            });
        }

        console.log('   ✓ Tallas por género construidas:', tallasPorGenero);
        return tallasPorGenero;
    }

    /**
     * Procesa imágenes: crea blob URLs desde File objects
     * Reutilizado en múltiples lugares
     * 
     * @param {Array<Object>} imagenesPrenda - Array de {file: File, nombre: string, ...}
     * @returns {Array<Object>} Imágenes con blobUrl agregado
     */
    static procesarImagenes(imagenesPrenda) {
        console.log(`📸 PrendaDataBuilder: Procesando ${imagenesPrenda?.length || 0} imágenes`);

        if (!imagenesPrenda || imagenesPrenda.length === 0) {
            return [];
        }

        return imagenesPrenda.map(img => {
            let blobUrl = null;
            if (img.file instanceof File) {
                blobUrl = URL.createObjectURL(img.file);
                console.log(`   📸 Blob URL creado: ${blobUrl}`);
            }
            return {
                ...img,
                blobUrl: blobUrl
            };
        });
    }

    /**
     * Obtiene procesos configurables filtrando vacíos
     * Previene incluir procesos sin datos
     * 
     * @returns {Object} Procesos configurables válidos
     */
    static obtenerProcesosConfigurablesValidos() {
        console.log(' PrendaDataBuilder: Obteniendo procesos configurables válidos');

        let procesosConfigurables = window.obtenerProcesosConfigurables?.() || {};

        // Filtrar procesos vacíos (datos: null)
        procesosConfigurables = Object.keys(procesosConfigurables).reduce((acc, tipoProceso) => {
            const proceso = procesosConfigurables[tipoProceso];
            if (proceso && (proceso.datos !== null || proceso.tipo)) {
                acc[tipoProceso] = proceso;
            }
            return acc;
        }, {});

        console.log('   ✓ Procesos válidos:', Object.keys(procesosConfigurables));
        return procesosConfigurables;
    }

    /**
     * Extrae y construye variaciones desde checkboxes del modal
     * Centraliza lógica de captura de variaciones
     * 
     * @returns {Object} {tipo_manga, obs_manga, tipo_broche, obs_broche, tiene_bolsillos, ...}
     */
    static construirVariacionesConfiguradas() {
        console.log(' PrendaDataBuilder: Construyendo variaciones configuradas');

        const variaciones = {
            tipo_manga: 'No aplica',
            obs_manga: '',
            tipo_broche: 'No aplica',
            obs_broche: '',
            tiene_bolsillos: false,
            obs_bolsillos: '',
            tiene_reflectivo: false,
            obs_reflectivo: ''
        };

        // MANGA
        if (document.getElementById('aplica-manga')?.checked) {
            variaciones.tipo_manga = document.getElementById('manga-input')?.value?.trim() || 'No aplica';
            variaciones.obs_manga = document.getElementById('manga-obs')?.value?.trim() || '';
            console.log('   ✓ Manga capturada:', variaciones.tipo_manga);
        }

        // BOLSILLOS
        if (document.getElementById('aplica-bolsillos')?.checked) {
            variaciones.tiene_bolsillos = true;
            variaciones.obs_bolsillos = document.getElementById('bolsillos-obs')?.value?.trim() || '';
            console.log('   ✓ Bolsillos capturados');
        }

        // BROCHE
        if (document.getElementById('aplica-broche')?.checked) {
            variaciones.tipo_broche = document.getElementById('broche-input')?.value?.trim() || 'No aplica';
            variaciones.obs_broche = document.getElementById('broche-obs')?.value?.trim() || '';
            console.log('   ✓ Broche capturado:', variaciones.tipo_broche);
        }

        // REFLECTIVO
        if (document.getElementById('aplica-reflectivo')?.checked) {
            variaciones.tiene_reflectivo = true;
            variaciones.obs_reflectivo = document.getElementById('reflectivo-obs')?.value?.trim() || '';
            console.log('   ✓ Reflectivo capturado');
        }

        console.log('   ✓ Variaciones construidas:', variaciones);
        return variaciones;
    }

    /**
     * Construye item para envío backend completo
     * Combina múltiples fuentes de datos
     * 
     * @param {Object} prenda - Datos de prenda
     * @param {number} prendaIndex - Índice en lista
     * @param {Array} fotosNuevas - Fotos recién agregadas
     * @returns {Object} Item formateado para backend
     */
    static construirItemParaEnvio(prenda, prendaIndex, fotosNuevas) {
        console.log(` PrendaDataBuilder: Construyendo item para envío (índice: ${prendaIndex})`);

        const itemSinCot = {
            nombre_producto: prenda.nombre_producto,
            descripcion: prenda.descripcion,
            genero: prenda.genero,
            origen: prenda.origen,
            variantes: prenda.variantes,
            procesos: prenda.procesos,
            cantidadesPorTalla: prenda.cantidadesPorTalla,
            tallas: prenda.tallas
        };

        // Procesar fotos
        let fotosParaEnviar = [];
        if (fotosNuevas?.[prendaIndex]) {
            fotosParaEnviar = fotosNuevas[prendaIndex];
            console.log(`   📸 Fotos de fotosNuevas: ${fotosParaEnviar.length}`);
        } else if (prenda.imagenes && prenda.imagenes.length > 0) {
            fotosParaEnviar = prenda.imagenes;
            console.log(`   📸 Fotos de prenda.imagenes: ${fotosParaEnviar.length}`);
        }

        if (fotosParaEnviar.length > 0) {
            itemSinCot.imagenes = fotosParaEnviar;
        }

        // Procesar telas usando TelaProcessor
        const telaResult = window.TelaProcessor?.construirItemDesdeTelas(prenda);
        if (telaResult?.itemSinCot) {
            Object.assign(itemSinCot, telaResult.itemSinCot);
            if (telaResult.imagenTelaUrl) {
                itemSinCot.imagenTela = telaResult.imagenTelaUrl;
            }
        }

        console.log('   ✓ Item para envío construido');
        return itemSinCot;
    }
}

// Exportar globalmente
window.PrendaDataBuilder = PrendaDataBuilder;
