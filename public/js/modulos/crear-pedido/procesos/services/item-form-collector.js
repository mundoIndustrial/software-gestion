/**
 * ItemFormCollector - Recolector de Datos de Formularios
 * 
 * Responsabilidad única: Extraer y transformar datos desde formularios
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo recolecta datos
 * - DIP: Inyecta servicios para procesamiento
 * - OCP: Fácil agregar nuevos tipos de ítems
 */
class ItemFormCollector {
    constructor(options = {}) {
        this.formId = options.formId || 'formCrearPedidoEditable';
        this.procesadores = options.procesadores || this.obtenerProcesadoresDefault();
    }

    /**
     * Recolectar datos completos del pedido
     */
    recolectarDatosPedido() {
        // Obtener items desde GestionItemsUI (prendas nuevas) y window.itemsPedido (EPPs)
        let items = [];
        
        // Obtener prendas desde GestionItemsUI
        if (window.gestionItemsUI && window.gestionItemsUI.obtenerItemsOrdenados) {
            const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
            items = items.concat(itemsOrdenados);
            console.log(' [recolectarDatosPedido] Items desde GestionItemsUI:', itemsOrdenados.length);
        }
        
        // Obtener EPPs desde window.itemsPedido (si no están ya en GestionItemsUI)
        if (window.itemsPedido && window.itemsPedido.length > 0) {
            const eppsDirectos = window.itemsPedido.filter(item => item.tipo === 'epp');
            if (eppsDirectos.length > 0) {
                items = items.concat(eppsDirectos);
                console.log(' [recolectarDatosPedido] EPPs desde window.itemsPedido:', eppsDirectos.length);
            }
        }
        
        console.log(' [recolectarDatosPedido] Items totales recibidos:', items.length);
        
        const itemsFormato = items.map((item, itemIndex) => {
            if (item.tipo === 'epp') {
                const epp = {
                    tipo: 'epp',
                    epp_id: item.epp_id,
                    nombre: item.nombre,
                    codigo: item.codigo,
                    categoria: item.categoria,
                    talla: item.talla,
                    cantidad: item.cantidad,
                    observaciones: item.observaciones || null,
                    tallas_medidas: item.tallas_medidas,
                };
                
                if (item.imagenes && item.imagenes.length > 0) {
                    epp.imagenes = item.imagenes;
                }
                
                console.log(` [Item ${itemIndex}] EPP procesado:`, epp);
                return epp;
            }
            
            const baseItem = {
                tipo: item.tipo,
                nombre_producto: item.nombre_producto || item.prenda?.nombre || item.nombre || '',
                descripcion: item.descripcion || '',
                origen: item.origen || 'bodega',
                procesos: item.procesos || {},
                cantidad_talla: item.cantidad_talla || {},
                variaciones: item.variaciones || {},
                telas: item.telas || item.telasAgregadas || [],
            };
            
            if (item.pedido_produccion_id) {
                baseItem.pedido_produccion_id = item.pedido_produccion_id;
                console.log(` [Item ${itemIndex}] Incluido pedido_produccion_id: ${item.pedido_produccion_id}`);
            }
            
            if (item.imagenes && item.imagenes.length > 0) {
                baseItem.imagenes = item.imagenes;
                console.log(` [Item ${itemIndex}] Imágenes: ${item.imagenes.length}`);
            }
            
            if (item.tipo === 'cotizacion') {
                baseItem.cotizacion_id = item.id;
                baseItem.numero_cotizacion = item.numero;
                baseItem.cliente = item.cliente;
            }
            
            return baseItem;
        });
        
        // AGREGAR PRENDAS SIN COTIZACIÓN
        if (window.gestorPrendaSinCotizacion && window.gestorPrendaSinCotizacion.obtenerActivas().length > 0) {
            console.log(' Integrando prendas sin cotización (tipo PRENDA)...');
            const prendasSinCot = window.gestorPrendaSinCotizacion.obtenerActivas();
            
            prendasSinCot.forEach((prenda, prendaIndex) => {
                console.log(`Procesando prenda sin cotización ${prendaIndex}:`, prenda);
                
                const cantidadTalla = {};
                if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
                    Object.entries(prenda.generosConTallas).forEach(([genero, tallas]) => {
                        if (tallas && typeof tallas === 'object') {
                            Object.entries(tallas).forEach(([talla, cantidad]) => {
                                if (cantidad > 0) {
                                    cantidadTalla[talla] = cantidad;
                                }
                            });
                        }
                    });
                } else if (window.cantidadesTallas) {
                    Object.assign(cantidadTalla, window.cantidadesTallas);
                }
                
                const tipoMangaRaw = prenda.variantes?.tipo_manga ?? 'No aplica';
                const obsMangaRaw = prenda.variantes?.obs_manga ?? '';
                const tieneBolsillosRaw = prenda.variantes?.tiene_bolsillos ?? false;
                const obsBolsillosRaw = prenda.variantes?.obs_bolsillos ?? '';
                const tipoBrocheRaw = prenda.variantes?.tipo_broche ?? 'No aplica';
                const obsBrocheRaw = prenda.variantes?.obs_broche ?? '';
                const tieneReflectivoRaw = prenda.variantes?.tiene_reflectivo ?? false;
                const obsReflectivoRaw = prenda.variantes?.obs_reflectivo ?? '';
                
                const variaciones = {
                    manga: {
                        tipo: tipoMangaRaw === 'No aplica' ? 'No aplica' : (tipoMangaRaw || 'No aplica'),
                        observacion: obsMangaRaw?.trim?.() || ''
                    },
                    bolsillos: {
                        tiene: tieneBolsillosRaw === true,
                        observacion: obsBolsillosRaw?.trim?.() || ''
                    },
                    broche: {
                        tipo: tipoBrocheRaw === 'No aplica' ? 'No aplica' : (tipoBrocheRaw || 'No aplica'),
                        observacion: obsBrocheRaw?.trim?.() || ''
                    },
                    reflectivo: {
                        tiene: tieneReflectivoRaw === true,
                        observacion: obsReflectivoRaw?.trim?.() || ''
                    }
                };
                
                let procesosParaEnviar = {};
                if (prenda.procesos && typeof prenda.procesos === 'object') {
                    Object.entries(prenda.procesos).forEach(([key, proceso]) => {
                        procesosParaEnviar[key] = proceso.datos || proceso;
                    });
                }
                
                const tallas = Object.keys(cantidadTalla);
                
                let fotosParaEnviar = [];
                if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
                    fotosParaEnviar = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
                } else if (prenda.imagenes && prenda.imagenes.length > 0) {
                    fotosParaEnviar = prenda.imagenes;
                }
                
                const itemSinCot = {
                    tipo: 'prenda_nueva',
                    prenda: prenda.nombre_producto || '',
                    descripcion: prenda.descripcion || '',
                    genero: prenda.genero || [],
                    cantidad_talla: cantidadTalla,
                    tallas: tallas,
                    variaciones: variaciones,
                    origen: prenda.origen || 'bodega',
                    de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
                    color: prenda.color || null,
                    tela: prenda.tela || null,
                    color_id: null,
                    tela_id: null,
                    tipo_manga_id: prenda.tipo_manga_id || null,
                    tipo_broche_boton_id: prenda.tipo_broche_boton_id || null,
                    procesos: procesosParaEnviar,
                    imagenes: fotosParaEnviar
                };
                
                if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
                    itemSinCot.telas = prenda.telasAgregadas.map(tela => ({
                        nombre: tela.nombre || '',
                        foto: tela.foto || null
                    }));
                }
                
                itemsFormato.push(itemSinCot);
                console.log(' Prenda sin cotización agregada:', itemSinCot);
            });
        }
        
        const pedidoFinal = {
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            items: itemsFormato
        };
        
        console.log(' Objeto pedido final a enviar:', pedidoFinal);
        return pedidoFinal;
    }

    /**
     * Procesar items del pedido
     * @private
     */
    procesarItems(items) {
        return items.map((item, itemIndex) => {
            const procesador = this.procesadores[item.tipo];
            
            if (!procesador) {
                console.warn(`No hay procesador para tipo: ${item.tipo}`);
                return item;
            }

            return procesador(item, itemIndex);
        });
    }

    /**
     * Agregar prendas sin cotización al formato final
     * @private
     */
    agregarPrendasSinCotizacion(itemsFormato, prendas) {
        prendas.forEach((prenda, prendaIndex) => {
            const itemSinCot = this.construirItemPrendaSinCotizacion(prenda, prendaIndex);
            itemsFormato.push(itemSinCot);
        });
    }

    /**
     * Construir ítem de prenda sin cotización
     * @private
     */
    construirItemPrendaSinCotizacion(prenda, prendaIndex) {
        const cantidadTalla = this.construirCantidadTalla(prenda);
        const variaciones = this.construirVariaciones(prenda);
        const procesosParaEnviar = this.extraerProcesos(prenda);
        const telas = this.extraerTelas(prenda);

        // Extraer color y tela de la primera tela agregada (si existe)
        let colorPrimera = prenda.color || null;
        let telaPrimera = prenda.tela || null;
        
        if (!colorPrimera && !telaPrimera && prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            colorPrimera = prenda.telasAgregadas[0].color || null;
            telaPrimera = prenda.telasAgregadas[0].tela || null;
        }

        return {
            tipo: 'prenda_nueva',
            prenda: prenda.nombre_producto || '',
            nombre_producto: prenda.nombre_producto || '',
            descripcion: prenda.descripcion || '',
            genero: prenda.genero || [],
            cantidad_talla: cantidadTalla,
            tallas: Object.keys(cantidadTalla),
            variaciones: variaciones,
            origen: prenda.origen || 'bodega',
            de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
            color: colorPrimera,
            tela: telaPrimera,
            color_id: null,
            tela_id: null,
            tipo_manga_id: prenda.tipo_manga_id || null,
            tipo_broche_boton_id: prenda.tipo_broche_boton_id || null,
            procesos: procesosParaEnviar,
            imagenes: this.extraerImagenes(prenda, prendaIndex),
            telas: telas
        };
    }

    /**
     * Construir cantidad por talla
     * @private
     */
    construirCantidadTalla(prenda) {
        const cantidadTalla = {};

        if (prenda.generosConTallas && typeof prenda.generosConTallas === 'object') {
            Object.keys(prenda.generosConTallas).forEach(genero => {
                const tallaDelGenero = prenda.generosConTallas[genero];
                Object.keys(tallaDelGenero).forEach(talla => {
                    const cantidad = parseInt(tallaDelGenero[talla]) || 0;
                    if (cantidad > 0) {
                        const key = `${genero}-${talla}`;
                        cantidadTalla[key] = cantidad;
                    }
                });
            });
        } else if (prenda.cantidadesPorTalla && typeof prenda.cantidadesPorTalla === 'object') {
            const generoFallback = prenda.genero && Array.isArray(prenda.genero) && prenda.genero.length > 0 
                ? prenda.genero[0] 
                : 'mixto';
            
            Object.keys(prenda.cantidadesPorTalla).forEach(talla => {
                const cantidad = parseInt(prenda.cantidadesPorTalla[talla]) || 0;
                if (cantidad > 0) {
                    cantidadTalla[`${generoFallback}-${talla}`] = cantidad;
                }
            });
        }

        return cantidadTalla;
    }

    /**
     * Construir variaciones de prenda
     * @private
     */
    construirVariaciones(prenda) {
        const tipoMangaRaw = prenda.variantes?.tipo_manga ?? 'No aplica';
        const obsMangaRaw = prenda.variantes?.obs_manga ?? '';
        const tieneBolsillosRaw = prenda.variantes?.tiene_bolsillos ?? false;
        const obsBolsillosRaw = prenda.variantes?.obs_bolsillos ?? '';
        const tipoBrocheRaw = prenda.variantes?.tipo_broche ?? 'No aplica';
        const obsBrocheRaw = prenda.variantes?.obs_broche ?? '';
        const tieneReflectivoRaw = prenda.variantes?.tiene_reflectivo ?? false;
        const obsReflectivoRaw = prenda.variantes?.obs_reflectivo ?? '';

        return {
            manga: {
                tipo: tipoMangaRaw === 'No aplica' ? 'No aplica' : (tipoMangaRaw || 'No aplica'),
                observacion: obsMangaRaw?.trim?.() || ''
            },
            bolsillos: {
                tiene: tieneBolsillosRaw === true,
                observacion: obsBolsillosRaw?.trim?.() || ''
            },
            broche: {
                tipo: tipoBrocheRaw === 'No aplica' ? 'No aplica' : (tipoBrocheRaw || 'No aplica'),
                observacion: obsBrocheRaw?.trim?.() || ''
            },
            reflectivo: {
                tiene: tieneReflectivoRaw === true,
                observacion: obsReflectivoRaw?.trim?.() || ''
            }
        };
    }

    /**
     * Extraer procesos del objeto de prenda
     * @private
     */
    extraerProcesos(prenda) {
        const procesosParaEnviar = {};
        
        if (prenda.procesos && typeof prenda.procesos === 'object') {
            Object.entries(prenda.procesos).forEach(([tipoProceso, procesoData]) => {
                if (procesoData?.datos) {
                    procesosParaEnviar[tipoProceso] = procesoData.datos;
                } else {
                    procesosParaEnviar[tipoProceso] = procesoData;
                }
            });
        }

        return procesosParaEnviar;
    }

    /**
     * Extraer imágenes de prenda
     * @private
     */
    extraerImagenes(prenda, prendaIndex) {
        let fotosParaEnviar = [];
        
        if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
            fotosParaEnviar = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
        } else if (prenda.imagenes && prenda.imagenes.length > 0) {
            fotosParaEnviar = prenda.imagenes;
        } else if (prenda.fotos && prenda.fotos.length > 0) {
            fotosParaEnviar = prenda.fotos;
        }

        return fotosParaEnviar;
    }

    /**
     * Extraer telas de prenda
     * @private
     */
    extraerTelas(prenda) {
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            return prenda.telasAgregadas;
        }

        if (prenda.telas && prenda.telas.length > 0) {
            return prenda.telas;
        }

        return [];
    }

    /**
     * Obtener valor de campo del formulario
     * @private
     */
    obtenerValorCampo(fieldId) {
        return document.getElementById(fieldId)?.value || '';
    }

    /**
     * Obtener procesadores por defecto
     * @private
     */
    obtenerProcesadoresDefault() {
        return {
            'epp': this.procesarItemEPP.bind(this),
            'cotizacion': this.procesarItemCotizacion.bind(this),
            'prenda': this.procesarItemPrenda.bind(this)
        };
    }

    /**
     * Procesador para items EPP
     * @private
     */
    procesarItemEPP(item, itemIndex) {
        return {
            tipo: 'epp',
            epp_id: item.epp_id,
            nombre: item.nombre,
            codigo: item.codigo,
            categoria: item.categoria,
            talla: item.talla,
            cantidad: item.cantidad,
            observaciones: item.observaciones || null,
            tallas_medidas: item.tallas_medidas,
            imagenes: item.imagenes && item.imagenes.length > 0 ? item.imagenes : undefined
        };
    }

    /**
     * Procesador para items de cotización
     * @private
     */
    procesarItemCotizacion(item, itemIndex) {
        return {
            tipo: 'cotizacion',
            prenda: item.prenda?.nombre || item.nombre || '',
            origen: item.origen || 'bodega',
            procesos: item.procesos || [],
            tallas: item.tallas || [],
            variaciones: item.variaciones || {},
            cotizacion_id: item.id,
            numero_cotizacion: item.numero,
            cliente: item.cliente,
            imagenes: item.imagenes && item.imagenes.length > 0 ? item.imagenes : undefined,
            pedido_produccion_id: item.pedido_produccion_id
        };
    }

    /**
     * Procesador para items de prenda regular
     * @private
     */
    procesarItemPrenda(item, itemIndex) {
        return {
            tipo: 'prenda',
            prenda: item.prenda?.nombre || item.nombre || '',
            origen: item.origen || 'bodega',
            procesos: item.procesos || [],
            tallas: item.tallas || [],
            variaciones: item.variaciones || {},
            imagenes: item.imagenes && item.imagenes.length > 0 ? item.imagenes : undefined,
            pedido_produccion_id: item.pedido_produccion_id
        };
    }

    /**
     * Agregar procesador personalizado para nuevo tipo
     */
    agregarProcesador(tipo, fn) {
        this.procesadores[tipo] = fn;
    }
}

window.ItemFormCollector = ItemFormCollector;
