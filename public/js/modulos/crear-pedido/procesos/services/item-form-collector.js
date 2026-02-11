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
        // Obtener items desde GestionItemsUI (prendas nuevas + EPPs)
        // GestionItemsUI ya incluye tanto prendas como EPPs en orden correcto
        let items = [];
        
        //  Obtener todos los items (prendas + EPPs) en orden desde GestionItemsUI
        if (window.gestionItemsUI && window.gestionItemsUI.obtenerItemsOrdenados) {
            const itemsOrdenados = window.gestionItemsUI.obtenerItemsOrdenados();
            items = items.concat(itemsOrdenados);
            console.log('[ItemFormCollector]  Items recolectados desde gestionItemsUI:', {
                total: itemsOrdenados.length,
                prendas: itemsOrdenados.filter(i => i.tipo !== 'epp').length,
                epps: itemsOrdenados.filter(i => i.tipo === 'epp').length
            });
        }
        
        //  IMPORTANTE: NO agregar EPPs nuevamente desde window.itemsPedido
        // Ya están incluidos en gestionItemsUI.obtenerItemsOrdenados()
        // Esto evita la duplicación de EPPs
        

        const itemsFormato = items.map((item, itemIndex) => {
            // EPPs se manejan separadamente, no aquí
            if (item.tipo === 'epp') {
                return null; // Los EPPs se procesarán después
            }
            
            //  DEBUG: Ver exactamente qué recibe
            console.log('[ItemFormCollector]  Item CRUDO recibido:', {
                itemIndex,
                tipo: item.tipo,
                nombre_prenda: item.nombre_prenda,
                nombre_producto: item.nombre_producto,
                nombre: item.nombre,
                cantidad_talla: item.cantidad_talla,
                telas: item.telas?.length || 0
            });
            
            const baseItem = {
                tipo: item.tipo,
                nombre_prenda: item.nombre_prenda || item.nombre_producto || item.prenda?.nombre || item.nombre || '',
                descripcion: item.descripcion || '',
                origen: item.origen || 'bodega',  // Keep this as fallback for items without origen
                de_bodega: item.de_bodega,  // Preserve de_bodega from PrendaFormCollector
                procesos: item.procesos || {},
                tallas: item.tallas || [],
                cantidad_talla: item.cantidad_talla || {}, //  AGREGAR cantidad_talla aquí
                variaciones: item.variantes || item.variaciones || {},
                telas: item.telas || item.telasAgregadas || [],
            };
            
            console.log('[ItemFormCollector]  baseItem CONSTRUIDO:', {
                itemIndex,
                nombre_prenda: baseItem.nombre_prenda,
                cantidad_talla_keys: Object.keys(baseItem.cantidad_talla || {}).length,
                telas_length: baseItem.telas?.length || 0,
                telas_primer_elemento: baseItem.telas?.[0]
            });
            
            // DEBUG: Log detallado de telasAgregadas vs telas
        // itemeIdx: índice del item (prenda)
            console.log('[ItemFormCollector]  TELAS EN ITEM - DETALLADO:', {
                itemIndex,
                item_keys: Object.keys(item),
                item_telas: item.telas?.length || 0,
                item_telasAgregadas: item.telasAgregadas?.length || 0,
                item_telasAgregadas_content: item.telasAgregadas,
                baseItem_telas_final: baseItem.telas?.length || 0,
                contenido_telas: baseItem.telas
            });
            
            // DEBUG: Log para verificar valores de origen y de_bodega
            console.log('[ItemFormCollector]  Item procesado:', {
                itemIndex,
                nombre_prenda: baseItem.nombre_prenda,
                origen_orig: item.origen,
                origen_final: baseItem.origen,
                de_bodega_orig: item.de_bodega,
                de_bodega_final: baseItem.de_bodega
            });
            
            if (item.pedido_produccion_id) {
                baseItem.pedido_produccion_id = item.pedido_produccion_id;

            }
            
            if (item.imagenes && item.imagenes.length > 0) {
                baseItem.imagenes = item.imagenes;

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

            const prendasSinCot = window.gestorPrendaSinCotizacion.obtenerActivas();
            
            prendasSinCot.forEach((prenda, prendaIndex) => {

                
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
                } else if (window.tallasRelacionales) {
                    // Usar estructura relacional: { DAMA: {T: 10}, CABALLERO: {32: 5} }
                    Object.values(window.tallasRelacionales).forEach(generoTallas => {
                        if (generoTallas && typeof generoTallas === 'object') {
                            Object.assign(cantidadTalla, generoTallas);
                        }
                    });
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
                        const datosProceso = proceso.datos || proceso;
                        
                        // EXTRAER ARCHIVOS FILE DE LAS IMÁGENES DE PROCESOS
                        // MANTENER ESTRUCTURA CON UID PARA POSTERIOR ENRIQUECIMIENTO
                        if (datosProceso.imagenes && Array.isArray(datosProceso.imagenes)) {
                            datosProceso.imagenes = datosProceso.imagenes.map((img, idx) => {
                                let file = null;
                                
                                // Extraer el File object
                                if (img.file instanceof File) {
                                    file = img.file;
                                } else if (img instanceof File) {
                                    file = img;
                                } else if (typeof img === 'string') {
                                    // Ruta existente, marcarla como imagen de cotización
                                    return {
                                        ruta_webp: img,
                                        is_existing_from_cotizacion: true,
                                        uid: `existing-proceso-${key}-${idx}-${Date.now()}`
                                    };
                                } else if (img.data) {
                                    file = img.data;
                                } else if (img && typeof img === 'object' && img.ruta_webp && !img.file) {
                                    // Ya es un objeto con ruta existente, solo asegurar marca
                                    return {
                                        ruta_webp: img.ruta_webp,
                                        is_existing_from_cotizacion: true,
                                        uid: img.uid || `existing-proceso-${key}-${idx}-${Date.now()}`
                                    };
                                }
                                
                                // Si tenemos un File, devolverlo con estructura enriquecida
                                // (similar a prenda e imagenes de telas)
                                if (file) {
                                    return {
                                        file: file,
                                        uid: img.uid || null  // Preservar UID si existe
                                    };
                                }
                                
                                return img;
                            }).filter(Boolean);
                        }
                        
                        procesosParaEnviar[key] = datosProceso;
                    });
                }
                
                const tallas = Object.keys(cantidadTalla);
                
                // EXTRAER ARCHIVOS FILE DE LAS FOTOS DE PRENDA
                let fotosParaEnviar = [];
                if (window.gestorPrendaSinCotizacion?.fotosNuevas?.[prendaIndex]) {
                    const fotosGestor = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
                    fotosParaEnviar = fotosGestor.map(foto => {
                        // Extraer el objeto File real
                        if (foto.file instanceof File) return foto.file;
                        // Si es una foto existente con ruta
                        if (foto.ruta_webp || foto.preview) return foto.ruta_webp || foto.preview;
                        // Fallback
                        return foto;
                    }).filter(Boolean);
                } else if (prenda.imagenes && prenda.imagenes.length > 0) {
                    fotosParaEnviar = prenda.imagenes;
                }
                
                const itemSinCot = {
                    tipo: 'prenda_nueva',
                    nombre_prenda: prenda.nombre_prenda || prenda.nombre_producto || '',
                    descripcion: prenda.descripcion || '',
                    genero: prenda.genero || [],
                    tallas: tallas,
                    cantidad_talla: cantidadTalla,  //  AÑADIDO: Incluir cantidad por talla
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
                    itemSinCot.telas = prenda.telasAgregadas.map((tela, telaIndex) => {
                        // Extraer fotos de esta tela desde el gestor
                        let fotosTela = [];
                        if (window.gestorPrendaSinCotizacion?.telasFotosNuevas?.[prendaIndex]?.[telaIndex]) {
                            const fotosGestor = window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex];
                            fotosTela = fotosGestor.map(foto => {
                                if (foto.file instanceof File) return foto.file;
                                if (foto.ruta_webp || foto.preview) return foto.ruta_webp || foto.preview;
                                return foto;
                            }).filter(Boolean);
                        } else if (tela.fotos && tela.fotos.length > 0) {
                            fotosTela = tela.fotos;
                        } else if (tela.imagenes && tela.imagenes.length > 0) {
                            fotosTela = tela.imagenes;
                        }
                        
                        return {
                            tela: tela.tela || tela.nombre || '',
                            color: tela.color || '',
                            referencia: tela.referencia || '',
                            imagenes: fotosTela
                        };
                    });
                }
                
                itemsFormato.push(itemSinCot);

            });
        }
        
        //  SEPARAR EPPs de prendas
        const prendas = itemsFormato.filter(item => item !== null && item.tipo !== 'epp');
        const epps = items.filter(item => item.tipo === 'epp').map(epp => ({
            uid: epp.uid || null,
            epp_id: epp.epp_id,
            nombre_epp: epp.nombre_epp || epp.nombre_prenda || epp.nombre_completo || epp.nombre || '',
            categoria: epp.categoria || '',
            cantidad: epp.cantidad,
            observaciones: epp.observaciones || null,
            // IMPORTANTE: Pasar archivo File object, no el objeto con preview
            imagenes: Array.isArray(epp.imagenes) ? epp.imagenes.map(img => {
                // Si tiene archivo (File object), devolverlo directamente
                if (img.archivo instanceof File) {
                    return img.archivo;
                }
                // Si es un File directamente, devolverlo
                if (img instanceof File) {
                    return img;
                }
                // Sino, devolver como está (por compatibilidad con otros formatos)
                return img;
            }) : []
        }));
        
        const pedidoFinal = {
            cliente: document.getElementById('cliente_editable')?.value || '',
            asesora: document.getElementById('asesora_editable')?.value || '',
            forma_de_pago: document.getElementById('forma_de_pago_editable')?.value || '',
            prendas: prendas,
            epps: epps
        };
        
        // AGREGAR UIDs a prendas, telas, procesos y EPPs (CRÍTICO)
        this.agregarUIDsAlPedido(pedidoFinal);
        
        // DEBUG: Verificar estructura
        console.group(' ItemFormCollector - Estructura pedidoFinal:');
        console.log(' Prendas:', prendas.length);
        prendas.forEach((item, idx) => {
            console.log(`  Prenda ${idx}:`, {
                uid: item.uid,  // ← NUEVO: Mostrar UID
                tipo: item.tipo,
                nombre: item.nombre_prenda,
                tiene_imagenes: !!item.imagenes,
                imagenes_count: item.imagenes?.length,
                telas_count: item.telas?.length,
            });
        });
        console.log(' EPPs:', epps.length);
        epps.forEach((epp, idx) => {
            console.log(`  EPP ${idx}:`, {
                uid: epp.uid,  // ← NUEVO: Mostrar UID
                epp_id: epp.epp_id,
                nombre: epp.nombre_epp,
                cantidad: epp.cantidad,
                imagenes_count: epp.imagenes?.length,
            });
        });
        console.groupEnd();

        return pedidoFinal;
    }
    
    /**
     * Agregar UIDs únicos a prendas, telas, procesos y EPPs
     * @private
     */
    agregarUIDsAlPedido(pedidoFinal) {
        // Agregar UIDs a prendas
        if (pedidoFinal.prendas && Array.isArray(pedidoFinal.prendas)) {
            pedidoFinal.prendas.forEach(prenda => {
                if (!prenda.uid) {
                    prenda.uid = this.generarUID();
                }
                
                // UIDs a imagenes de prenda
                if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
                    prenda.imagenes.forEach((img, index) => {
                        // Si img es un string, convertirlo a objeto
                        if (typeof img === 'string') {
                            prenda.imagenes[index] = {
                                ruta: img,
                                uid: this.generarUID()
                            };
                        } else if (!img.uid) {
                            img.uid = this.generarUID();
                        }
                    });
                }
                
                // UIDs a telas
                if (prenda.telas && Array.isArray(prenda.telas)) {
                    prenda.telas.forEach(tela => {
                        if (!tela.uid) {
                            tela.uid = this.generarUID();
                        }
                        
                        // UIDs a imagenes de tela
                        if (tela.imagenes && Array.isArray(tela.imagenes)) {
                            tela.imagenes.forEach((img, index) => {
                                // Si img es un string, convertirlo a objeto
                                if (typeof img === 'string') {
                                    tela.imagenes[index] = {
                                        ruta: img,
                                        uid: this.generarUID()
                                    };
                                } else if (!img.uid) {
                                    img.uid = this.generarUID();
                                }
                            });
                        }
                    });
                }
                
                // UIDs a procesos
                if (prenda.procesos && typeof prenda.procesos === 'object') {
                    Object.values(prenda.procesos).forEach(proceso => {
                        if (!proceso.uid) {
                            proceso.uid = this.generarUID();
                        }
                        
                        // UIDs a imagenes dentro de procesos.datos.imagenes
                        if (proceso.datos?.imagenes && Array.isArray(proceso.datos.imagenes)) {
                            proceso.datos.imagenes.forEach((img, index) => {
                                // Si img es un string, convertirlo a objeto
                                if (typeof img === 'string') {
                                    proceso.datos.imagenes[index] = {
                                        ruta: img,
                                        uid: this.generarUID()
                                    };
                                } else if (!img.uid) {
                                    img.uid = this.generarUID();
                                }
                            });
                        }
                        // Fallback si imagenes está directamente en proceso
                        else if (proceso.imagenes && Array.isArray(proceso.imagenes)) {
                            proceso.imagenes.forEach((img, index) => {
                                // Si img es un string, convertirlo a objeto
                                if (typeof img === 'string') {
                                    proceso.imagenes[index] = {
                                        ruta: img,
                                        uid: this.generarUID()
                                    };
                                } else if (!img.uid) {
                                    img.uid = this.generarUID();
                                }
                            });
                        }
                    });
                }
            });
        }
        
        // Agregar UIDs a EPPs
        if (pedidoFinal.epps && Array.isArray(pedidoFinal.epps)) {
            pedidoFinal.epps.forEach(epp => {
                if (!epp.uid) {
                    epp.uid = this.generarUID();
                }
            });
        }
    }
    
    /**
     * Generar UID único
     * @private
     */
    generarUID() {
        return 'uid-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
    }

    /**
     * Procesar items del pedido
     * @private
     */
    procesarItems(items) {
        return items.map((item, itemIndex) => {
            const procesador = this.procesadores[item.tipo];
            
            if (!procesador) {

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
        const telas = this.extraerTelas(prenda, prendaIndex);

        // Extraer color y tela de la primera tela agregada (si existe)
        let colorPrimera = prenda.color || null;
        let telaPrimera = prenda.tela || null;
        
        if (!colorPrimera && !telaPrimera && prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            colorPrimera = prenda.telasAgregadas[0].color || null;
            telaPrimera = prenda.telasAgregadas[0].tela || null;
        }

        return {
            tipo: 'prenda_nueva',
            prenda: prenda.nombre_prenda || prenda.nombre_producto || '',
            nombre_prenda: prenda.nombre_prenda || prenda.nombre_producto || '',
            descripcion: prenda.descripcion || '',
            genero: prenda.genero || [],
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
        const tipoMangaId = prenda.variantes?.tipo_manga_id ?? null;
        const obsMangaRaw = prenda.variantes?.obs_manga ?? '';
        const tieneBolsillosRaw = prenda.variantes?.tiene_bolsillos ?? false;
        const obsBolsillosRaw = prenda.variantes?.obs_bolsillos ?? '';
        const tipoBrocheRaw = prenda.variantes?.tipo_broche ?? 'No aplica';
        const tipoBrocheBotonId = prenda.variantes?.tipo_broche_boton_id ?? null;
        const obsBrocheRaw = prenda.variantes?.obs_broche ?? '';
        const tieneReflectivoRaw = prenda.variantes?.tiene_reflectivo ?? false;
        const obsReflectivoRaw = prenda.variantes?.obs_reflectivo ?? '';

        return {
            tipo_manga: tipoMangaRaw === 'No aplica' ? '' : (tipoMangaRaw || ''),
            tipo_manga_id: tipoMangaId,
            obs_manga: obsMangaRaw?.trim?.() || '',
            tiene_bolsillos: tieneBolsillosRaw === true,
            obs_bolsillos: obsBolsillosRaw?.trim?.() || '',
            tipo_broche: tipoBrocheRaw === 'No aplica' ? '' : (tipoBrocheRaw || ''),
            tipo_broche_boton_id: tipoBrocheBotonId,
            obs_broche: obsBrocheRaw?.trim?.() || '',
            tiene_reflectivo: tieneReflectivoRaw === true,
            obs_reflectivo: obsReflectivoRaw?.trim?.() || '',
            // Mantener estructura antigua para compatibilidad
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
            const fotosGestor = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
            
            // Convertir objetos de foto a File objects o rutas
            fotosParaEnviar = fotosGestor.map((foto, idx) => {
                // Si tiene File object, usarlo
                if (foto.file instanceof File) {
                    return foto.file;
                }
                // Si tiene ruta webp (foto EXISTENTE de cotización), marcarla
                if (foto.ruta_webp || foto.preview || foto.ruta) {
                    // Retornar como objeto marcado para que FormDataBuilder y MapeoImagenesService
                    // sepan que es una ruta existente, no un archivo nuevo
                    return {
                        ruta_webp: foto.ruta_webp || foto.ruta || foto.preview,
                        is_existing_from_cotizacion: true,
                        uid: foto.uid || `existing-${prendaIndex}-${idx}-${Date.now()}`
                    };
                }
                // Fallback
                return foto;
            }).filter(Boolean);
        } else if (prenda.imagenes && prenda.imagenes.length > 0) {
            fotosParaEnviar = prenda.imagenes.map((img, idx) => {
                // Si es un object con ruta_webp o ruta, es imagen existente
                if (img && typeof img === 'object' && (img.ruta_webp || img.ruta)) {
                    return {
                        ruta_webp: img.ruta_webp || img.ruta,
                        is_existing_from_cotizacion: true,
                        uid: img.uid || `existing-prenda-${prendaIndex}-${idx}-${Date.now()}`
                    };
                }
                // Si es string, puede ser ruta o File
                return img;
            });
        } else if (prenda.fotos && prenda.fotos.length > 0) {
            fotosParaEnviar = prenda.fotos.map((foto, idx) => {
                // Si tiene estructura de objeto con ruta o ruta_webp
                if (foto && typeof foto === 'object' && (foto.ruta_webp || foto.ruta)) {
                    return {
                        ruta_webp: foto.ruta_webp || foto.ruta,
                        is_existing_from_cotizacion: true,
                        uid: foto.uid || `existing-fotos-${prendaIndex}-${idx}-${Date.now()}`
                    };
                }
                return foto;
            });
        }

        return fotosParaEnviar;
    }

    /**
     * Extraer telas de prenda con sus imágenes
     * @private
     */
    extraerTelas(prenda, prendaIndex) {
        let telasBase = [];
        
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            telasBase = prenda.telasAgregadas;
        } else if (prenda.telas && prenda.telas.length > 0) {
            telasBase = prenda.telas;
        }

        // Enriquecer cada tela con sus imágenes desde el gestor
        return telasBase.map((tela, telaIndex) => {
            let imagenesTela = [];
            
            // Intentar obtener imágenes del gestor principal
            if (window.gestorPrendaSinCotizacion?.telasFotosNuevas?.[prendaIndex]?.[telaIndex]) {
                const fotosGestor = window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex];
                imagenesTela = fotosGestor.map((foto, idx) => {
                    if (foto.file instanceof File) return foto.file;
                    if (foto.ruta_webp || foto.ruta || foto.preview) {
                        // Imagen existente de cotización
                        return {
                            ruta_webp: foto.ruta_webp || foto.ruta || foto.preview,
                            is_existing_from_cotizacion: true,
                            uid: foto.uid || `existing-tela-${prendaIndex}-${telaIndex}-${idx}-${Date.now()}`
                        };
                    }
                    return foto;
                }).filter(Boolean);
            } else if (tela.imagenes && tela.imagenes.length > 0) {
                imagenesTela = tela.imagenes.map((img, idx) => {
                    // Si es un object con ruta_webp o ruta, es imagen existente
                    if (img && typeof img === 'object' && (img.ruta_webp || img.ruta)) {
                        return {
                            ruta_webp: img.ruta_webp || img.ruta,
                            is_existing_from_cotizacion: true,
                            uid: img.uid || `existing-tela-imagenes-${prendaIndex}-${telaIndex}-${idx}-${Date.now()}`
                        };
                    }
                    return img;
                });
            } else if (tela.fotos && tela.fotos.length > 0) {
                imagenesTela = tela.fotos.map((foto, idx) => {
                    // Si tiene estructura de objeto con ruta o ruta_webp
                    if (foto && typeof foto === 'object' && (foto.ruta_webp || foto.ruta)) {
                        return {
                            ruta_webp: foto.ruta_webp || foto.ruta,
                            is_existing_from_cotizacion: true,
                            uid: foto.uid || `existing-fotos-${prendaIndex}-${telaIndex}-${idx}-${Date.now()}`
                        };
                    }
                    return foto;
                });
            }

            return {
                tela: tela.tela || tela.nombre || '',
                color: tela.color || '',
                referencia: tela.referencia || '',
                imagenes: imagenesTela
            };
        });
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
