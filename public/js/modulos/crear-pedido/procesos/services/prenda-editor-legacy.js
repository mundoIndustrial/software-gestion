/**
 * PrendaEditorLegacy - Métodos legados para compatibilidad
 * 
 * Responsabilidad única: Contener métodos legados para edición de prendas
 * sin dependencias de API DDD, usados para edición local de prendas existentes
 * 
 * Arquitectura: Separación de responsabilidades para reducir acoplamiento
 */

class PrendaEditorLegacy {
    constructor() {
        this.notificationService = null;
        this.cotizacionActual = null;
    }

    /**
     * Inicializar el servicio legacy
     */
    init(options = {}) {
        this.notificationService = options.notificationService;
        this.cotizacionActual = options.cotizacionActual || null;
        console.log('[PrendaEditorLegacy]  Servicio legacy inicializado');
    }

    /**
     * Llenar campos básicos del formulario (MÉTODO LEGADO)
     * @private
     */
    llenarCamposBasicos(prenda) {
        console.log(' [llenarCamposBasicos] INICIANDO CARGA DE CAMPOS BÁSICOS ', {
            nombre: prenda.nombre_prenda,
            de_bodega: prenda.de_bodega,
            origen: prenda.origen,
            timestamp: new Date().toLocaleTimeString()
        });
        
        //  ASEGURAR QUE EL DOM ESTÁ LISTO (pequeño delay para rendering)
        setTimeout(() => {
            this._llenarCamposBasicosInternal(prenda);
        }, 50);
    }

    _llenarCamposBasicosInternal(prenda) {
        const nombreField = document.getElementById('nueva-prenda-nombre');
        const descripcionField = document.getElementById('nueva-prenda-descripcion');
        const origenField = document.getElementById('nueva-prenda-origen-select');

        console.log('[llenarCamposBasicos]  DOM LISTO PARA LLENAR (después de timeout)');
        console.log('[llenarCamposBasicos] DEBUG Elementos encontrados:', {
            nombreField: !!nombreField,
            descripcionField: !!descripcionField,
            origenField: !!origenField,
            origenFieldTagName: origenField?.tagName,
            origenFieldOptions: origenField?.options?.length,
            modalVisible: !!document.getElementById('modal-agregar-prenda-nueva')?.offsetParent
        });
        
        if (nombreField) nombreField.value = prenda.nombre_prenda || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        
        if (origenField) {
            console.log('[llenarCamposBasicos] Datos de origen ANTES:', {
                prendaOrigen: prenda.origen,
                prendaDeBodega: prenda.de_bodega,
                tipoDeBodega: typeof prenda.de_bodega
            });
            
            //  APLICAR ORIGEN AUTOMÁTICO AQUÍ TAMBIÉN
            // Si hay cotización, FUERZA el origen antes de llenar el campo
            if (this.cotizacionActual) {
                const tipoCotizacionId = this.cotizacionActual.tipo_cotizacion_id;
                let nombreTipo = null;
                if (this.cotizacionActual.tipo_cotizacion && this.cotizacionActual.tipo_cotizacion.nombre) {
                    nombreTipo = this.cotizacionActual.tipo_cotizacion.nombre;
                } else if (this.cotizacionActual.tipo_nombre) {
                    nombreTipo = this.cotizacionActual.tipo_nombre;
                }
                
                // Buscar opción por nombre o ID
                let opcionEncontrada = null;
                for (let i = 0; i < origenField.options.length; i++) {
                    const option = origenField.options[i];
                    if (option.value === nombreTipo || 
                        option.value === tipoCotizacionId.toString() ||
                        option.text === nombreTipo) {
                        opcionEncontrada = option;
                        break;
                    }
                }
                
                if (opcionEncontrada) {
                    origenField.value = opcionEncontrada.value;
                    console.log('[llenarCamposBasicos]  Origen forzado por cotización:', opcionEncontrada.value);
                } else {
                    // Fallback al origen de la prenda
                    origenField.value = prenda.origen || '';
                    console.log('[llenarCamposBasicos]  Origen fallback a prenda:', prenda.origen);
                }
            } else {
                // Sin cotización, usar origen de la prenda
                origenField.value = prenda.origen || '';
                console.log('[llenarCamposBasicos] 📄 Origen desde prenda (sin cotización):', prenda.origen);
            }
        }
        
        console.log('[llenarCamposBasicos]  Campos básicos llenados');
    }

    /**
     * Cargar imágenes (MÉTODO LEGADO - RESTAURADO DESDE BACKUP)
     */
    cargarImagenes(prenda) {
        console.log('🖼️ [CARGAR-IMAGENES] Iniciando carga de imágenes:', {
            tieneImagenes: !!prenda.imagenes,
            count: prenda.imagenes?.length || 0,
            tienefotos: !!prenda.fotos,
            countFotos: prenda.fotos?.length || 0,
            tieneProcesos: !!prenda.procesos,
            procesosCount: prenda.procesos ? Object.keys(prenda.procesos).length : 0
        });

        //  VERIFICAR/CREAR SERVICIO SI NO EXISTE
        if (!window.imagenesPrendaStorage) {
            console.log('🔧 [CARGAR-IMAGENES] Creando imagenesPrendaStorage...');
            try {
                if (typeof ImageStorageService !== 'undefined') {
                    window.imagenesPrendaStorage = new ImageStorageService();
                } else {
                    // Crear storage básico si ImageStorageService no existe
                    window.imagenesPrendaStorage = {
                        images: [],
                        limpiar: function() { this.images = []; },
                        agregarImagen: function(img) { 
                            if (img instanceof File) {
                                const previewUrl = URL.createObjectURL(img);
                                this.images.push({ file: img, previewUrl: previewUrl, nombre: img.name });
                            } else {
                                this.images.push(img);
                            }
                        },
                        agregarDesdeURL: function(url, nombre) {
                            this.images.push({ url: url, previewUrl: url, nombre: nombre || 'Imagen' });
                        }
                    };
                }
            } catch (error) {
                console.error(' [CARGAR-IMAGENES] Error creando storage:', error);
                return;
            }
        }

        // PRIORIDAD 0: imagenes (formulario con archivos)
        let imagenesACargar = null;
        let origen = 'desconocido';

        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            const primerItem = prenda.imagenes[0];
            
            if (primerItem instanceof File || primerItem.file instanceof File) {
                console.log(' [CARGAR-IMAGENES] Detectado: imagenes de FORMULARIO (File objects)');
                imagenesACargar = prenda.imagenes;
                origen = 'formulario';
            } else if (typeof primerItem === 'string' || (primerItem && (primerItem.url || primerItem.ruta))) {
                console.log(' [CARGAR-IMAGENES] Detectado: imagenes de BD (URLs)');
                imagenesACargar = prenda.imagenes;
                origen = 'bd-imagenes';
            }
        }

        // PRIORIDAD 1: fotos (BD alternativo)
        if (!imagenesACargar && prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            console.log(' [CARGAR-IMAGENES] Detectado: fotos de BD (alternativo)');
            imagenesACargar = prenda.fotos;
            origen = 'bd-fotos';
        }

        // ✨ PRIORIDAD 2: Imágenes de procesos (reflectivo, logo, etc)
        if (!imagenesACargar && prenda.procesos && typeof prenda.procesos === 'object' && Object.keys(prenda.procesos).length > 0) {
            console.log('✨ [CARGAR-IMAGENES] Detectado: procesos con imágenes');
            for (const [tipoProceso, dataProceso] of Object.entries(prenda.procesos)) {
                if (dataProceso.imagenes && Array.isArray(dataProceso.imagenes) && dataProceso.imagenes.length > 0) {
                    console.log(` [CARGAR-IMAGENES] Usando imágenes del proceso: ${tipoProceso}`);
                    imagenesACargar = dataProceso.imagenes;
                    origen = `proceso-${tipoProceso}`;
                    break;
                }
            }
        }

        if (!imagenesACargar || imagenesACargar.length === 0) {
            console.log(' [CARGAR-IMAGENES] No hay imágenes para cargar');
            return;
        }

        if (window.imagenesPrendaStorage) {
            console.log(` [CARGAR-IMAGENES] Limpiando y cargando ${imagenesACargar.length} imágenes (origen: ${origen})`);
            window.imagenesPrendaStorage.limpiar();

            imagenesACargar.forEach((img, idx) => {
                this.procesarImagen(img, idx);
            });

            // ACTUALIZAR PREVIEW
            setTimeout(() => {
                console.log('[cargarImagenes] 🎬 Actualizando preview directamente...');
                const preview = document.getElementById('nueva-prenda-foto-preview');
                if (preview && window.imagenesPrendaStorage.images && window.imagenesPrendaStorage.images.length > 0) {
                    const primeraImg = window.imagenesPrendaStorage.images[0];
                    const urlImg = primeraImg.previewUrl || primeraImg.url || primeraImg.ruta;
                    
                    if (urlImg) {
                        preview.style.backgroundImage = `url('${urlImg}')`;
                        preview.style.backgroundSize = 'cover';
                        preview.style.backgroundPosition = 'center';
                        preview.style.cursor = 'pointer';
                        
                        const contador = document.getElementById('nueva-prenda-foto-contador');
                        if (contador && window.imagenesPrendaStorage.images.length > 1) {
                            contador.textContent = window.imagenesPrendaStorage.images.length;
                            contador.style.display = 'flex';
                        }
                        console.log(' [cargarImagenes] Preview actualizado con imagen:', urlImg);
                    }
                }
            }, 100);
            
            console.log(` [CARGAR-IMAGENES] ${imagenesACargar.length} imágenes cargadas desde ${origen}`);
        }
    }

    /**
     * Procesar una imagen individual
     * @private
     */
    procesarImagen(img, idx = 0) {
        if (!img) return;

        // CASO 1: img es un File directamente
        if (img instanceof File) {
            console.log(`  📸 [PROCESAR-IMAGEN] Imagen ${idx}: File object detectado`);
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        // CASO 2: img es objeto con .file que es un File
        else if (img.file instanceof File) {
            console.log(`  📸 [PROCESAR-IMAGEN] Imagen ${idx}: Wrapper con File detectado`);
            window.imagenesPrendaStorage.agregarImagen(img.file);
        }
        // CASO 3: img es objeto con URL (BD)
        else if (img.url || img.ruta || img.ruta_webp || img.ruta_original) {
            const urlFinal = img.ruta_webp || img.ruta || img.url || img.ruta_original;
            console.log(`  📸 [PROCESAR-IMAGEN] Imagen ${idx}: URL de BD detectada:`, urlFinal);
            window.imagenesPrendaStorage.agregarDesdeURL(urlFinal, img.nombre || `Imagen ${idx + 1}`);
        }
        // CASO 4: img es string (URL directa)
        else if (typeof img === 'string') {
            console.log(`  📸 [PROCESAR-IMAGEN] Imagen ${idx}: String URL detectada:`, img);
            window.imagenesPrendaStorage.agregarDesdeURL(img, `Imagen ${idx + 1}`);
        }
        // CASO 5: img tiene previewUrl (ya procesada)
        else if (img.previewUrl) {
            console.log(`  📸 [PROCESAR-IMAGEN] Imagen ${idx}: PreviewUrl detectada:`, img.previewUrl);
            window.imagenesPrendaStorage.images.push(img);
        }
        else {
            console.warn(`   [PROCESAR-IMAGEN] Imagen ${idx}: Formato desconocido:`, img);
        }
    }

    /**
     * Cargar telas (MÉTODO LEGADO - RESTAURADO DESDE BACKUP)
     */
    cargarTelas(prenda) {
        console.log('[cargarTelas] 🧵 Cargando telas:', prenda.telasAgregadas);
        
        // ===== DEBUG: Ver estructura completa de prenda =====
        console.group('[cargarTelas]  ESTRUCTURA COMPLETA DE PRENDA');
        console.log('prenda.telasAgregadas:', prenda.telasAgregadas);
        console.log('prenda.colores_telas:', prenda.colores_telas);
        console.groupEnd();
        
        // ===== TRANSFORMAR colores_telas (BD) a telasAgregadas (frontend) =====
        if (!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) {
            if (prenda.colores_telas && prenda.colores_telas.length > 0) {
                console.log('[cargarTelas]  Transformando colores_telas a telasAgregadas');
                prenda.telasAgregadas = prenda.colores_telas.map((ct, idx) => {
                    return {
                        nombre_tela: ct.tela?.nombre || ct.nombre_tela || 'Sin nombre',
                        tela: ct.tela?.nombre || ct.nombre_tela || 'Sin nombre',
                        color: ct.color?.nombre || ct.nombre_color || ct.color || 'Sin color',
                        referencia: ct.referencia || '',
                        imagenes: ct.imagenes || [],
                        id: ct.id
                    };
                });
            }
        }
        
        // Usar telasAgregadas si existe
        const telasDisponibles = prenda.telasAgregadas || prenda.telas || [];
        
        if (telasDisponibles.length > 0) {
            console.log('[cargarTelas] ✓ Telas disponibles:', telasDisponibles.length);
            
            // Verificar estructura de cada tela y generar previewUrl para File objects
            telasDisponibles.forEach((tela, idx) => {
                if (tela.imagenes && Array.isArray(tela.imagenes)) {
                    tela.imagenes = tela.imagenes.map((img, imgIdx) => {
                        // Si es un File sin previewUrl, generar uno
                        if (img instanceof File) {
                            return {
                                file: img,
                                previewUrl: URL.createObjectURL(img),
                                nombre: img.name
                            };
                        }
                        // Si es un objeto con file pero sin previewUrl
                        if (img.file instanceof File && !img.previewUrl) {
                            return {
                                ...img,
                                previewUrl: URL.createObjectURL(img.file)
                            };
                        }
                        // Si ya tiene previewUrl o url, dejarlo como está
                        return img;
                    });
                }
                
                console.log(`[cargarTelas] Tela ${idx}:`, {
                    nombre: tela.nombre_tela || tela.tela,
                    color: tela.color,
                    imagenes_count: tela.imagenes ? tela.imagenes.length : 0
                });
            });

            // Limpiar storage de telas y inputs
            if (window.imagenesTelaStorage) {
                window.imagenesTelaStorage.limpiar();
            }
            
            // Limpiar inputs de tela
            const inputTela = document.getElementById('nueva-prenda-tela');
            const inputColor = document.getElementById('nueva-prenda-color');
            const inputRef = document.getElementById('nueva-prenda-referencia');
            
            if (inputTela) inputTela.value = '';
            if (inputColor) inputColor.value = '';
            if (inputRef) inputRef.value = '';
            
            // Asignar a window.telasAgregadas para que se muestre en la tabla
            window.telasAgregadas = [...telasDisponibles];
            
            // También asignar a window.telasCreacion si existe
            if (typeof window.telasCreacion !== 'undefined') {
                window.telasCreacion = [...telasDisponibles];
            }
            
            console.log('[cargarTelas]  Telas cargadas en window.telasCreacion:', {
                total: telasDisponibles.length,
                nombres: telasDisponibles.map(t => t.tela || t.nombre_tela)
            });
            
            // Actualizar tabla de telas
            if (typeof window.actualizarTablaTelas === 'function') {
                console.log('[cargarTelas]  Llamando a actualizarTablaTelas()');
                window.actualizarTablaTelas();
            }
            
            // Actualizar preview de tela
            if (typeof window.actualizarPreviewTela === 'function') {
                window.actualizarPreviewTela();
            }
        } else {
            console.log('[cargarTelas]  No hay telas para cargar');
        }
    }

    /**
     * Cargar variaciones de la prenda (MÉTODO LEGADO - RESTAURADO DESDE BACKUP)
     */
    cargarVariaciones(prenda) {
        console.log(' [cargarVariaciones] Cargando variaciones:', {
            tieneVariantes: !!prenda.variantes,
            variantes: prenda.variantes
        });
        
        // Si viene de cotización Logo/Reflectivo, buscar variaciones en los procesos
        let variantes = prenda.variantes || {};
        
        // Si variantes está vacío pero hay procesos, extraer de los procesos
        if ((!variantes || Object.keys(variantes).length === 0) && prenda.procesos) {
            const procesosArray = Array.isArray(prenda.procesos) ? prenda.procesos : Object.values(prenda.procesos);
            if (procesosArray.length > 0 && procesosArray[0].variaciones_prenda) {
                console.log('[cargarVariaciones]  Variaciones extraídas desde procesos');
                variantes = procesosArray[0].variaciones_prenda;
            }
        }
        
        // Si es array (variantes de BD), usar el primer elemento
        if (Array.isArray(variantes) && variantes.length > 0) {
            variantes = variantes[0];
        }
        
        if (!variantes || Object.keys(variantes).length === 0) {
            console.log('[cargarVariaciones]  No hay variaciones para cargar');
            return;
        }
        
        const aplicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');
        
        // CARGAR GÉNERO DESDE VARIANTES (genero_id: 1=DAMA, 2=CABALLERO)
        if (variantes.genero_id) {
            console.log('[cargarVariaciones] 👥 Cargando género:', variantes.genero_id);
            
            const generoMap = { 1: 'DAMA', 2: 'CABALLERO' };
            const generoSeleccionado = generoMap[variantes.genero_id];
            
            if (generoSeleccionado) {
                const checkboxGenero = document.querySelector(`input[value="${generoSeleccionado.toLowerCase()}"]`);
                if (checkboxGenero) {
                    checkboxGenero.checked = true;
                    checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log(`[cargarVariaciones] ✓ Género marcado: ${generoSeleccionado}`);
                }
            }
        }

        // MANGA
        let mangaData = variantes.manga || {};
        let mangaOpcion = '';
        let mangaObs = variantes.obs_manga || '';
        
        if (typeof variantes.tipo_manga === 'string' && variantes.tipo_manga) {
            mangaOpcion = variantes.tipo_manga;
        } else if (typeof mangaData === 'object') {
            mangaOpcion = mangaData.opcion || mangaData.tipo_manga || mangaData.manga || '';
        }
        
        if (aplicaManga && mangaOpcion) {
            aplicaManga.checked = true;
            aplicaManga.dispatchEvent(new Event('change', { bubbles: true }));
            
            const mangaInput = document.getElementById('manga-input');
            if (mangaInput) {
                let valorManga = mangaOpcion.toLowerCase()
                    .replace(/á/g, 'a').replace(/é/g, 'e').replace(/í/g, 'i').replace(/ó/g, 'o').replace(/ú/g, 'u');
                mangaInput.value = valorManga;
                mangaInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('[cargarVariaciones] ✓ Manga cargada:', valorManga);
            }
            
            const mangaObsInput = document.getElementById('manga-obs');
            if (mangaObsInput && mangaObs) {
                mangaObsInput.value = mangaObs;
                mangaObsInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // BOLSILLOS
        const bolsillosData = variantes.bolsillos || {};
        const bolsillosOpcion = bolsillosData.opcion || '';
        const bolsillosObs = bolsillosData.observacion || variantes.obs_bolsillos || '';
        
        if (aplicaBolsillos && (bolsillosOpcion || bolsillosObs)) {
            aplicaBolsillos.checked = true;
            aplicaBolsillos.dispatchEvent(new Event('change', { bubbles: true }));
            
            const bolsillosObsInput = document.getElementById('bolsillos-obs');
            if (bolsillosObsInput && bolsillosObs) {
                bolsillosObsInput.value = bolsillosObs;
                bolsillosObsInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('[cargarVariaciones] ✓ Bolsillos cargado');
            }
        }

        // BROCHE/BOTÓN
        let brocheData = variantes.broche_boton || variantes.broche || {};
        let brocheOpcion = '';
        let brocheObs = variantes.obs_broche || variantes.broche_boton_obs || '';
        
        if (typeof variantes.tipo_broche === 'string' && variantes.tipo_broche) {
            brocheOpcion = variantes.tipo_broche;
        } else if (typeof brocheData === 'object') {
            brocheOpcion = brocheData.opcion || brocheData.tipo_broche || brocheData.broche || '';
        }
        
        if (aplicaBroche && (brocheOpcion || brocheObs)) {
            aplicaBroche.checked = true;
            aplicaBroche.dispatchEvent(new Event('change', { bubbles: true }));
            
            const brocheInput = document.getElementById('broche-input');
            if (brocheInput && brocheOpcion) {
                let valorBroche = brocheOpcion.toLowerCase()
                    .replace(/á/g, 'a').replace(/é/g, 'e').replace(/í/g, 'i').replace(/ó/g, 'o').replace(/ú/g, 'u');
                brocheInput.value = valorBroche;
                brocheInput.dispatchEvent(new Event('change', { bubbles: true }));
                console.log('[cargarVariaciones] ✓ Broche cargado:', valorBroche);
            }
            
            const brocheObsInput = document.getElementById('broche-obs');
            if (brocheObsInput && brocheObs) {
                brocheObsInput.value = brocheObs;
                brocheObsInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // REFLECTIVO
        if (aplicaReflectivo && (variantes.tiene_reflectivo === true || variantes.obs_reflectivo)) {
            aplicaReflectivo.checked = true;
            
            const reflectivoObs = document.getElementById('reflectivo-obs');
            if (reflectivoObs && variantes.obs_reflectivo) {
                reflectivoObs.value = variantes.obs_reflectivo;
            }
        }
        
        console.log('[cargarVariaciones]  Variaciones cargadas');
    }

    /**
     * Cargar tallas y cantidades (MÉTODO LEGADO - RESTAURADO DESDE BACKUP)
     */
    cargarTallasYCantidades(prenda) {
        console.log('📏 [cargarTallasYCantidades] Cargando tallas y cantidades:', {
            cantidad_talla: prenda.cantidad_talla,
            tieneCantidadTalla: !!(prenda.cantidad_talla)
        });
        
        // Inicializar estructura de tallas
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {}, SOBREMEDIDA: {} };
        }
        
        // CASO 1: Cargar desde cantidad_talla (estructura del formulario)
        if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && Object.keys(prenda.cantidad_talla).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando desde cantidad_talla:', prenda.cantidad_talla);
            
            Object.entries(prenda.cantidad_talla).forEach(([generoKey, tallasObj]) => {
                const generoUpper = generoKey.toUpperCase();
                
                if (generoUpper === 'SOBREMEDIDA' && tallasObj && typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    window.tallasRelacionales.SOBREMEDIDA = { ...tallasObj };
                } else if (window.tallasRelacionales[generoUpper] && tallasObj && typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    // Filtrar SOBREMEDIDA anidada
                    const tallasLimpias = {};
                    for (const [talla, valor] of Object.entries(tallasObj)) {
                        if (talla === 'SOBREMEDIDA') {
                            if (typeof valor === 'number') {
                                window.tallasRelacionales.SOBREMEDIDA[generoUpper] = valor;
                            }
                        } else {
                            tallasLimpias[talla] = valor;
                        }
                    }
                    window.tallasRelacionales[generoUpper] = tallasLimpias;
                }
            });
            
            console.log('[cargarTallasYCantidades] ✓ window.tallasRelacionales actualizado:', window.tallasRelacionales);
            
            // Renderizar tarjetas de género
            this.renderizarTarjetasTallas();
            return;
        }
        
        // CASO 2: Cargar desde generosConTallas (datos de BD)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando desde generosConTallas:', prenda.generosConTallas);
            
            Object.entries(prenda.generosConTallas).forEach(([generoKey, tallaData]) => {
                const generoUpper = generoKey.toUpperCase();
                
                if (!tallaData || typeof tallaData !== 'object' || Object.keys(tallaData).length === 0) {
                    return;
                }
                
                if (generoUpper === 'SOBREMEDIDA') {
                    window.tallasRelacionales.SOBREMEDIDA = { ...tallaData };
                } else if (window.tallasRelacionales[generoUpper]) {
                    const tallasLimpias = {};
                    for (const [talla, valor] of Object.entries(tallaData)) {
                        if (talla === 'SOBREMEDIDA') {
                            if (typeof valor === 'number') {
                                window.tallasRelacionales.SOBREMEDIDA[generoUpper] = valor;
                            }
                        } else {
                            tallasLimpias[talla] = valor;
                        }
                    }
                    window.tallasRelacionales[generoUpper] = tallasLimpias;
                }
            });
            
            console.log('[cargarTallasYCantidades] ✓ window.tallasRelacionales actualizado:', window.tallasRelacionales);
            this.renderizarTarjetasTallas();
            return;
        }
        
        console.warn('[cargarTallasYCantidades]  No hay cantidad_talla o función de carga no disponible');
        
        // FALLBACK: Si hay cantidad_talla pero no se procesó arriba
        if (prenda.cantidad_talla && window.tallasRelacionales) {
            console.log('[cargarTallasYCantidades]  Intentando carga manual de tallas');
            window.tallasRelacionales = { 
                DAMA: prenda.cantidad_talla.DAMA || {},
                CABALLERO: prenda.cantidad_talla.CABALLERO || {},
                UNISEX: prenda.cantidad_talla.UNISEX || {},
                SOBREMEDIDA: prenda.cantidad_talla.SOBREMEDIDA || {}
            };
            this.actualizarUIConTallasManual(prenda.cantidad_talla);
        }
    }
    
    /**
     * Renderizar tarjetas de tallas en la UI
     */
    renderizarTarjetasTallas() {
        console.log('[renderizarTarjetasTallas]  Renderizando tarjetas de tallas');
        
        Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
            const tallasList = Object.keys(tallasObj);
            
            if (tallasList && tallasList.length > 0) {
                const generoLower = genero.toLowerCase();
                
                setTimeout(() => {
                    if (genero.toUpperCase() === 'SOBREMEDIDA') {
                        // SOBREMEDIDA
                        for (const [generoSobremedida, cantidad] of Object.entries(tallasObj)) {
                            if (cantidad > 0 && typeof window.crearTarjetaSobremedida === 'function') {
                                window.crearTarjetaSobremedida(generoSobremedida, cantidad);
                            }
                        }
                    } else {
                        // GÉNEROS NORMALES
                        if (typeof window.crearTarjetaGenero === 'function') {
                            window.crearTarjetaGenero(generoLower, tallasObj);
                            console.log(`[renderizarTarjetasTallas] ✓ Tarjeta creada: ${generoLower}`);
                        }
                        
                        // Llenar inputs de cantidad
                        setTimeout(() => {
                            Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                                const inputSelector = `input[data-genero="${genero}"][data-talla="${talla}"]`;
                                const input = document.querySelector(inputSelector);
                                if (input && cantidad > 0) {
                                    input.value = cantidad;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            });
                        }, 100);
                    }
                }, 150);
            }
        });
        
        console.log('[renderizarTarjetasTallas]  UI actualizada manualmente');
    }
    
    /**
     * Actualizar UI con tallas manualmente (fallback)
     */
    actualizarUIConTallasManual(tallas) {
        console.log('[actualizarUIConTallasManual]  Actualizando UI con tallas manuales');
        
        // Limpiar tallas existentes
        const contenedorTallas = document.getElementById('tallas-seleccionadas');
        if (contenedorTallas) {
            contenedorTallas.innerHTML = '';
        }
        
        // Crear tarjetas por género
        Object.entries(tallas).forEach(([genero, tallasGenero]) => {
            if (typeof tallasGenero === 'object' && Object.keys(tallasGenero).length > 0) {
                Object.entries(tallasGenero).forEach(([talla, cantidad]) => {
                    if (cantidad > 0) {
                        this.crearTarjetaTallaManual(genero, talla, cantidad);
                    }
                });
            }
        });
        
        console.log('[actualizarUIConTallasManual]  UI actualizada manualmente');
    }
    
    /**
     * Crear tarjeta de talla manualmente
     */
    crearTarjetaTallaManual(genero, talla, cantidad) {
        const contenedorTallas = document.getElementById('tallas-seleccionadas');
        if (!contenedorTallas) return;
        
        const tarjeta = document.createElement('div');
        tarjeta.className = 'talla-card';
        tarjeta.style.cssText = `
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        `;
        
        tarjeta.innerHTML = `
            <span style="font-weight: 600; color: #374151;">${genero}</span>
            <span style="margin: 0 0.5rem; color: #6b7280;">-</span>
            <span style="font-weight: 600; color: #374151;">${talla}</span>
            <span style="margin: 0 0.5rem; color: #6b7280;">-</span>
            <span style="font-weight: 600; color: #059669;">${cantidad}</span>
        `;
        
        contenedorTallas.appendChild(tarjeta);
    }

    /**
     * Normalizar procesos: convierte objeto a array si es necesario
     * Maneja ambos formatos: objeto {tipo: {...}} y array [{...}]
     * @private
     */
    normalizarProcesos(procesos) {
        if (!procesos) return [];
        
        // Si ya es un array, retornarlo tal cual
        if (Array.isArray(procesos)) {
            return procesos;
        }
        
        // Si es un objeto, convertir a array
        if (typeof procesos === 'object') {
            return Object.values(procesos).filter(p => p !== null && p !== undefined);
        }
        
        return [];
    }

    /**
     * Cargar procesos de la prenda (MÉTODO LEGADO - RESTAURADO DESDE BACKUP)
     */
    cargarProcesos(prenda) {
        // Normalizar procesos: maneja tanto array como objeto
        const procesosNormalizados = this.normalizarProcesos(prenda.procesos);
        
        console.log('📋 [cargarProcesos] Cargando procesos:', {
            cantidad: procesosNormalizados.length
        });
        
        if (!procesosNormalizados || procesosNormalizados.length === 0) {
            console.log('[cargarProcesos]  Sin procesos en la prenda');
            return;
        }

        // Copiar procesos completos
        window.procesosSeleccionados = {};
        
        procesosNormalizados.forEach((proceso, idx) => {
            if (proceso) {
                // Si viene del formulario, proceso es {datos: {...}}
                // Si viene de BD, proceso es {...}
                const datosReales = proceso.datos ? proceso.datos : proceso;
                
                const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${idx}`;
                const slugDirecto = datosReales.slug || null;
                const tipoProceso = slugDirecto || tipoBackend.toLowerCase().replace(/\s+/g, '-');
                
                console.log(`📌 [cargarProcesos] Procesando [${idx}] tipo="${tipoProceso}"`);

                // Procesar ubicaciones
                let ubicacionesFormato = [];
                if (datosReales.ubicaciones) {
                    if (Array.isArray(datosReales.ubicaciones)) {
                        ubicacionesFormato = datosReales.ubicaciones;
                    } else if (typeof datosReales.ubicaciones === 'string') {
                        ubicacionesFormato = datosReales.ubicaciones.split(',').map(u => u.trim()).filter(u => u);
                    }
                }

                // Convertir tallas
                let tallasFormato = datosReales.tallas || { dama: {}, caballero: {}, sobremedida: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {
                    tallasFormato = { dama: {}, caballero: {}, sobremedida: {} };
                }
                
                const datosProces = {
                    id: datosReales.id,
                    tipo: tipoProceso,
                    nombre: tipoBackend,
                    nombre_proceso: datosReales.nombre_proceso,
                    tipo_proceso: datosReales.tipo_proceso,
                    tipo_proceso_id: datosReales.tipo_proceso_id,
                    ubicaciones: ubicacionesFormato,
                    observaciones: datosReales.observaciones || '',
                    tallas: tallasFormato,
                    variaciones_prenda: datosReales.variaciones_prenda || {},
                    talla_cantidad: datosReales.talla_cantidad || {},
                    imagenes: (datosReales.imagenes || []).map(img => {
                        if (typeof img === 'string') {
                            return img.startsWith('/') ? img : '/storage/' + img;
                        }
                        if (img instanceof File) {
                            return img;
                        }
                        let urlExtraida = img.ruta_original || img.ruta || img.ruta_webp || img.url || '';
                        if (urlExtraida && typeof urlExtraida === 'string' && !urlExtraida.startsWith('/')) {
                            urlExtraida = '/storage/' + urlExtraida;
                        }
                        return urlExtraida;
                    }).filter(url => url)
                };
                
                window.procesosSeleccionados[tipoProceso] = { datos: datosProces };
                console.log(`✓ [cargarProcesos] Proceso "${tipoProceso}" cargado`);

                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`checkbox-${tipoProceso}`);
                if (checkboxProceso) {
                    checkboxProceso.checked = true;
                    checkboxProceso._ignorarOnclick = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxProceso._ignorarOnclick = false;
                } else {
                    // Intentar por data-tipo
                    const checkboxPorTipo = document.querySelector(`[data-tipo="${tipoProceso}"]`);
                    if (checkboxPorTipo) {
                        checkboxPorTipo.checked = true;
                        checkboxPorTipo.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        });
        
        console.log('✓ [cargarProcesos] Procesos seleccionados:', window.procesosSeleccionados);

        // Renderizar tarjetas de procesos
        if (typeof window.renderizarTarjetasProcesos === 'function') {
            console.log('[cargarProcesos]  Renderizando tarjetas...');
            window.renderizarTarjetasProcesos();
        }
    }

    /**
     * Cambiar botón a guardar cambios
     */
    cambiarBotonAGuardarCambios() {
        const botonGuardar = document.getElementById('btn-guardar-prenda');
        if (botonGuardar) {
            botonGuardar.textContent = 'Guardar Cambios';
            botonGuardar.classList.remove('btn-success');
            botonGuardar.classList.add('btn-warning');
            
            // Remover listeners anteriores y agregar nuevo
            const nuevoBoton = botonGuardar.cloneNode(true);
            botonGuardar.parentNode.replaceChild(nuevoBoton, botonGuardar);
            
            nuevoBoton.addEventListener('click', () => {
                if (typeof window.guardarCambiosPrenda === 'function') {
                    window.guardarCambiosPrenda();
                }
            });
        }
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificacion(mensaje, tipo) {
        if (typeof window.mostrarNotificacionGlobal === 'function') {
            window.mostrarNotificacionGlobal(mensaje, tipo);
        } else if (typeof window.mostrarNotificacion === 'function') {
            window.mostrarNotificacion(mensaje, tipo);
        } else {
            console.log(`[PrendaEditorLegacy] ${tipo}: ${mensaje}`);
        }
    }

    /**
     * Aplicar origen automático desde cotización
     */
    aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
        if (!this.cotizacionActual) {
            return prenda;
        }
        
        // Lógica para aplicar origen según cotización
        const tipoCotizacionId = this.cotizacionActual.tipo_cotizacion_id;
        let origen = prenda.origen;
        
        // Mapeo de tipos de cotización a orígenes
        const mapeoOrigenes = {
            1: 'Bordado',
            2: 'Bordado', 
            3: 'Logo',
            4: 'Reflectivo'
        };
        
        if (mapeoOrigenes[tipoCotizacionId]) {
            origen = mapeoOrigenes[tipoCotizacionId];
        }
        
        console.log('[PrendaEditorLegacy]  Origen aplicado:', {
            tipoCotizacionId,
            origenAplicado: origen,
            cotizacionNombre: this.cotizacionActual.tipo_nombre
        });
        
        return { ...prenda, origen };
    }

    /**
     * Cargar telas desde cotización (MÉTODO LEGADO)
     */
    async cargarTelasDesdeCtizacion(prenda) {
        if (!prenda.prenda_id || !prenda.cotizacion_id) {
            console.debug('[cargarTelasDesdeCtizacion] No hay prenda_id o cotizacion_id');
            return;
        }

        console.log('[cargarTelasDesdeCtizacion] 🧵 Cargando telas y variaciones de cotización:', {
            prenda_cot_id: prenda.prenda_id,
            cotizacion_id: prenda.cotizacion_id
        });

        try {
            const response = await fetch(`/api/cotizaciones/${prenda.cotizacion_id}/prendas/${prenda.prenda_id}/telas-cotizacion`);
            
            if (!response.ok) {
                console.warn('[cargarTelasDesdeCtizacion] Endpoint no disponible, intentando fallback');
                return;
            }
            
            const result = await response.json();
            
            if (!result.success) {
                console.warn('[cargarTelasDesdeCtizacion] Error en respuesta:', result.message);
                return;
            }
            
            const data = result.data;
            const telas = data.telas || data.data?.telas || [];
            const variaciones = data.variaciones || data.data?.variaciones || [];
            
            console.log('[cargarTelasDesdeCtizacion]  Datos de cotización cargados:', {
                telas_count: telas.length,
                variaciones_count: variaciones.length
            });
            
            // Asignar telas a la prenda
            if (telas.length > 0) {
                prenda.telasAgregadas = telas;
                console.log('[cargarTelasDesdeCtizacion]  Telas procesadas:', {
                    cantidad: telas.length,
                    telas: telas.map(t => `${t.nombre_tela} - ${t.color}`)
                });
                
                // Re-cargar telas en el formulario
                this.cargarTelas(prenda);
            }
            
            // Procesar variaciones si existen
            if (variaciones.length > 0) {
                console.log('[cargarTelasDesdeCtizacion]  Procesando variaciones:', {
                    cantidad: variaciones.length
                });
                prenda.variantes = variaciones;
                this.cargarVariaciones(prenda);
            }
            
        } catch (error) {
            console.warn('[cargarTelasDesdeCtizacion]  Error cargando datos de cotización:', error.message);
        }
    }

    /**
     * Actualizar preview de telas desde cotización
     */
    actualizarPreviewTelasCotizacion() {
        console.log('[actualizarPreviewTelasCotizacion] Preview actualizado');
    }
}

// Exportar para uso global
window.PrendaEditorLegacy = PrendaEditorLegacy;

// Crear instancia global para compatibilidad
window.prendaEditorLegacy = new PrendaEditorLegacy();

console.log('[PrendaEditorLegacy]  Servicio legacy cargado y disponible globalmente');
