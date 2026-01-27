/**
 * PrendaEditor - Gestor de Edición de Prendas
 * 
 * Responsabilidad única: Gestionar carga, edición y guardado de prendas en modal
 */
class PrendaEditor {
    constructor(options = {}) {
        this.notificationService = options.notificationService;
        this.modalId = options.modalId || 'modal-agregar-prenda-nueva';
        this.prendaEditIndex = null;
    }

    /**
     * Abrir modal para agregar o editar prenda
     */
    abrirModal(esEdicion = false, prendaIndex = null) {

        
        if (esEdicion && prendaIndex !== null && prendaIndex !== undefined) {
            this.prendaEditIndex = prendaIndex;
        } else {
            this.prendaEditIndex = null;
        }

        // Preparar modal
        if (window.ModalCleanup) {

            if (esEdicion) {
                window.ModalCleanup.prepararParaEditar(prendaIndex);
            } else {
                window.ModalCleanup.prepararParaNueva();
            }
        } else {

        }

        // Cargar tipos de manga disponibles desde la BD
        if (typeof window.cargarTiposMangaDisponibles === 'function') {
            window.cargarTiposMangaDisponibles();
        }

        // Mostrar modal

        const modal = document.getElementById(this.modalId);
        if (modal) {





            modal.style.display = 'flex';
        } else {

        }
    }

    /**
     * Cargar prenda en el modal para edición
     */
    cargarPrendaEnModal(prenda, prendaIndex) {
        console.log('🔄 [CARGAR-PRENDA] Iniciando carga de prenda en modal:', {
            prendaIndex,
            nombre: prenda.nombre_prenda,
            tieneProcesos: !!prenda.procesos,
            countProcesos: prenda.procesos?.length || 0
        });
        
        if (!prenda) {
            this.mostrarNotificacion('Prenda no válida', 'error');
            return;
        }

        try {
            this.prendaEditIndex = prendaIndex;
            this.abrirModal(true, prendaIndex);
            this.llenarCamposBasicos(prenda);
            this.cargarImagenes(prenda);
            this.cargarTelas(prenda);
            this.cargarVariaciones(prenda);  // CARGAR PRIMERO para que genero_id esté seleccionado antes de las tallas
            this.cargarTallasYCantidades(prenda);
            
            console.log(' [CARGAR-PRENDA] Sobre de cargar procesos...');
            this.cargarProcesos(prenda);
            
            this.cambiarBotonAGuardarCambios();
            console.log('✅ [CARGAR-PRENDA] Prenda cargada completamente');
            this.mostrarNotificacion('Prenda cargada para editar', 'success');
        } catch (error) {
            console.error(' [CARGAR-PRENDA] Error:', error);
            this.mostrarNotificacion(`Error al cargar prenda: ${error.message}`, 'error');
        }
    }

    /**
     * Llenar campos básicos del formulario
     * @private
     */
    llenarCamposBasicos(prenda) {
        const nombreField = document.getElementById('nueva-prenda-nombre');
        const descripcionField = document.getElementById('nueva-prenda-descripcion');
        const origenField = document.getElementById('nueva-prenda-origen-select');


        
        if (nombreField) nombreField.value = prenda.nombre_prenda || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        if (origenField) {


            
            // Función para normalizar texto (remover acentos)
            const normalizarTexto = (texto) => {
                return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            };
            
            // Determinar origen desde de_bodega o desde el campo origen
            let origen = prenda.origen || 'bodega';
            
            // Si viene de_bodega (integer desde BD), convertir a string
            if (prenda.de_bodega !== undefined && prenda.origen === undefined) {
                origen = prenda.de_bodega === 1 ? 'bodega' : 'confeccion';
            }
            
            const origenNormalizado = normalizarTexto(origen);
            let encontrado = false;
            
            for (let opt of origenField.options) {
                const optTextNormalizado = normalizarTexto(opt.textContent);
                const optValueNormalizado = normalizarTexto(opt.value);
                
                if (optValueNormalizado === origenNormalizado || optTextNormalizado === origenNormalizado) {
                    origenField.value = opt.value;
                    encontrado = true;

                    break;
                }
            }
            
            if (!encontrado) {

                origenField.value = origen;
            }
            
            // Disparar evento de cambio para que se actualice la UI
            origenField.dispatchEvent(new Event('change', { bubbles: true }));
        } else {

        }
    }

    /**
     * Cargar imágenes en el modal
     * @private
     */
    cargarImagenes(prenda) {
        console.log('🖼️ [CARGAR-IMAGENES] Iniciando carga de imágenes:', {
            tieneImagenes: !!prenda.imagenes,
            count: prenda.imagenes?.length || 0,
            tienefotos: !!prenda.fotos,
            countFotos: prenda.fotos?.length || 0
        });

        // PRIORIDAD 0: imagenes (formulario con archivos)
        let imagenesACargar = null;
        let origen = 'desconocido';

        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            // Verificar si son File/Blob o URL (formulario vs BD)
            const primerItem = prenda.imagenes[0];
            
            if (primerItem instanceof File || primerItem.file instanceof File) {
                // Formulario: archivos File
                console.log('✅ [CARGAR-IMAGENES] Detectado: imagenes de FORMULARIO (File objects)');
                imagenesACargar = prenda.imagenes;
                origen = 'formulario';
            } else if (typeof primerItem === 'string' || (primerItem && (primerItem.url || primerItem.ruta))) {
                // BD: URLs/strings
                console.log('✅ [CARGAR-IMAGENES] Detectado: imagenes de BD (URLs)');
                imagenesACargar = prenda.imagenes;
                origen = 'bd-urls';
            }
        }

        // PRIORIDAD 1: fotos (BD alternativo)
        if (!imagenesACargar && prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            console.log('✅ [CARGAR-IMAGENES] Detectado: fotos de BD (alternativo)');
            imagenesACargar = prenda.fotos;
            origen = 'bd-fotos';
        }

        // Si no hay imágenes, retornar
        if (!imagenesACargar || imagenesACargar.length === 0) {
            console.log(' [CARGAR-IMAGENES] No hay imágenes para cargar');
            return;
        }

        if (window.imagenesPrendaStorage) {
            console.log(`🔄 [CARGAR-IMAGENES] Limpiando y cargando ${imagenesACargar.length} imágenes (origen: ${origen})`);
            window.imagenesPrendaStorage.limpiar();

            imagenesACargar.forEach((img, idx) => {
                this.procesarImagen(img, idx);
            });

            this.actualizarPreviewImagenes(imagenesACargar);
            
            // ACTUALIZAR PREVIEW DIRECTAMENTE SIN DEPENDER DE actualizarPreviewPrenda
            setTimeout(() => {
                console.log('[cargarImagenes] 🎬 Actualizando preview directamente...');
                const preview = document.getElementById('nueva-prenda-foto-preview');
                if (preview && window.imagenesPrendaStorage.images && window.imagenesPrendaStorage.images.length > 0) {
                    const primerImg = window.imagenesPrendaStorage.images[0];
                    const urlImg = primerImg.previewUrl;
                    
                    console.log('[cargarImagenes] 🖼️ URL imagen:', urlImg);
                    
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = urlImg;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
                    preview.appendChild(img);
                    
                    console.log('[cargarImagenes] ✅ Imagen insertada en el DOM');
                } else {
                    console.warn('[cargarImagenes] ⚠️ Preview no encontrado o sin imágenes');
                }
            }, 100);
            
            console.log(`✅ [CARGAR-IMAGENES] ${imagenesACargar.length} imágenes cargadas desde ${origen}`);
        } else {
            console.warn(' [CARGAR-IMAGENES] imagenesPrendaStorage no disponible');
        }
    }

    /**
     * Procesar una imagen individual
     * @private
     */
    procesarImagen(img, idx = 0) {
        if (!img) {
            console.log(`   [PROCESAR-IMAGEN] Imagen ${idx} es null/undefined`);
            return;
        }

        // CASO 1: img es un File directamente (formulario)
        if (img instanceof File) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: File object detectado`);
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        // CASO 2: img es objeto con .file que es un File (wrapper)
        else if (img.file instanceof File) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: Wrapper con File detectado`);
            window.imagenesPrendaStorage.agregarImagen(img.file);
        }
        // CASO 3: img es objeto con URL (BD)
        else if (img.url || img.ruta || img.ruta_webp || img.ruta_original) {
            const urlImagen = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: URL de BD:`, urlImagen);
            if (!window.imagenesPrendaStorage.images) {
                window.imagenesPrendaStorage.images = [];
            }
            window.imagenesPrendaStorage.images.push({
                previewUrl: urlImagen,
                nombre: `imagen_${idx}.webp`,
                tamaño: 0,
                file: null,
                urlDesdeDB: true
            });
        }
        // CASO 4: img es string URL (BD alternativo)
        else if (typeof img === 'string') {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: String URL:`, img);
            if (!window.imagenesPrendaStorage.images) {
                window.imagenesPrendaStorage.images = [];
            }
            window.imagenesPrendaStorage.images.push({
                previewUrl: img,
                nombre: `imagen_${idx}.webp`,
                tamaño: 0,
                file: null,
                urlDesdeDB: true
            });
        }
        // CASO 5: Blob (también formulario)
        else if (img instanceof Blob) {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: Blob object detectado`);
            window.imagenesPrendaStorage.agregarImagen(img);
        }
        else {
            console.warn(`   [PROCESAR-IMAGEN] Imagen ${idx}: Formato desconocido:`, typeof img, img);
        }
    }

    /**
     * Actualizar preview de imágenes
     * @private
     */
    actualizarPreviewImagenes(imagenes) {
        console.log('[actualizarPreviewImagenes] 📸 Actualizando preview con', imagenes?.length || 0, 'imágenes');
        console.log('[actualizarPreviewImagenes] 📦 Storage tiene:', window.imagenesPrendaStorage?.images?.length || 0, 'imágenes');
        
        if (window.actualizarPreviewPrenda) {
            console.log('[actualizarPreviewImagenes] ✅ Llamando a window.actualizarPreviewPrenda()');
            window.actualizarPreviewPrenda();
            return;
        }

        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        
        console.log('[actualizarPreviewImagenes] 🔍 Preview element:', preview ? 'ENCONTRADO' : 'NO ENCONTRADO');

        if (preview && window.imagenesPrendaStorage.images.length > 0) {
            const primerImg = window.imagenesPrendaStorage.images[0];
            const urlImg = primerImg.previewUrl || primerImg.url;
            
            console.log('[actualizarPreviewImagenes] 🖼️ Mostrando imagen:', urlImg);

            preview.style.backgroundImage = `url('${urlImg}')`;
            preview.style.cursor = 'pointer';

            if (contador && window.imagenesPrendaStorage.images.length > 1) {
                contador.textContent = window.imagenesPrendaStorage.images.length;
            }
        } else {
            console.log('[actualizarPreviewImagenes] ⚠️ No hay imágenes para mostrar');
        }
    }

    /**
     * Cargar telas en el modal
     * @private
     */
    cargarTelas(prenda) {
        console.log('[cargarTelas] 📊 Cargando telas:', prenda.telasAgregadas);
        
        // ===== DEBUG: Ver estructura completa de prenda =====
        console.group('[cargarTelas] 🔍 ESTRUCTURA COMPLETA DE PRENDA');
        console.log('prenda.telasAgregadas:', prenda.telasAgregadas);
        console.log('prenda.colores_telas:', prenda.colores_telas);
        console.log('prenda keys:', Object.keys(prenda));
        console.groupEnd();
        
        // ===== TRANSFORMAR colores_telas (BD) a telasAgregadas (frontend) =====
        if (!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) {
            if (prenda.colores_telas && prenda.colores_telas.length > 0) {
                console.log('[cargarTelas] 🔄 Transformando colores_telas a telasAgregadas');
                console.log('[cargarTelas] colores_telas ANTES:', JSON.stringify(prenda.colores_telas, null, 2));
                console.log('[cargarTelas] Total de colores_telas:', prenda.colores_telas.length);
                
                prenda.telasAgregadas = prenda.colores_telas.map((ct, idx) => {
                    console.log(`[cargarTelas] Procesando colorTela ${idx}:`, {
                        id: ct.id,
                        color: ct.color_nombre,
                        tela: ct.tela_nombre,
                        fotos_count: ct.fotos ? ct.fotos.length : (ct.fotos_tela ? ct.fotos_tela.length : 0),
                    });
                    
                    // Obtener fotos: puede venir en ct.fotos (nuevo) o ct.fotos_tela (antiguo)
                    let fotosArray = [];
                    if (ct.fotos && Array.isArray(ct.fotos) && ct.fotos.length > 0) {
                        fotosArray = ct.fotos;
                        console.log(`[cargarTelas] CT ${idx}: Usando ct.fotos (${ct.fotos.length} items)`);
                    } else if (ct.fotos_tela && Array.isArray(ct.fotos_tela) && ct.fotos_tela.length > 0) {
                        fotosArray = ct.fotos_tela;
                        console.log(`[cargarTelas] CT ${idx}: Usando ct.fotos_tela (${ct.fotos_tela.length} items)`);
                    } else {
                        console.log(`[cargarTelas] CT ${idx}: SIN FOTOS`);
                    }
                    
                    const transformed = {
                        id: ct.id,
                        nombre_tela: ct.tela_nombre || '(Sin nombre)',
                        color: ct.color_nombre || '(Sin color)',
                        referencia: ct.referencia || ct.tela_referencia || '',
                        imagenes: fotosArray.map(foto => ({
                            url: foto.ruta_webp || foto.ruta_original || foto.url || '',
                            ruta_webp: foto.ruta_webp || '',
                            ruta_original: foto.ruta_original || '',
                            previewUrl: foto.ruta_webp || foto.ruta_original || foto.url || ''
                        }))
                    };
                    console.log(`[cargarTelas] Tela ${idx} transformada:`, {
                        nombre: transformed.nombre_tela,
                        color: transformed.color,
                        fotosCount: transformed.imagenes.length,
                        fotos: transformed.imagenes
                    });
                    return transformed;
                });
                console.log('[cargarTelas] ✅ Transformación completada:', prenda.telasAgregadas);
            } else {
                console.warn('[cargarTelas] ⚠️ No hay colores_telas para transformar');
                prenda.telasAgregadas = [];
            }
        } else {
            console.log('[cargarTelas] ℹ️ telasAgregadas ya tiene datos, no transformar');
        }
        
        // Intentar cargar desde telasAgregadas (prendas nuevas Y prendas de BD editadas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            console.log('[cargarTelas] ✓ Telas disponibles:', prenda.telasAgregadas.length);
            
            // Verificar estructura de cada tela
            prenda.telasAgregadas.forEach((tela, idx) => {
                console.log(`[cargarTelas] Tela ${idx}:`, {
                    nombre: tela.nombre_tela,
                    color: tela.color,
                    imagenes_count: tela.imagenes ? tela.imagenes.length : 0,
                    imagenes: tela.imagenes
                });
            });

            
            // Limpiar storage de telas y inputs
            if (window.imagenesTelaStorage) {
                window.imagenesTelaStorage.limpiar();
            }
            
            // Limpiar inputs de tela para que estén vacíos (no precargados)
            const inputTela = document.getElementById('nueva-prenda-tela');
            const inputColor = document.getElementById('nueva-prenda-color');
            const inputRef = document.getElementById('nueva-prenda-referencia');
            
            if (inputTela) inputTela.value = '';
            if (inputColor) inputColor.value = '';
            if (inputRef) inputRef.value = '';
            
            // Limpiar preview temporal de imágenes de tela
            const previewTemporal = document.getElementById('nueva-prenda-tela-preview');
            if (previewTemporal) {
                previewTemporal.innerHTML = '';
                previewTemporal.style.display = 'none';
            }
            
            // ⚠️ NO cargar imágenes de telas de BD en window.imagenesTelaStorage
            // Las imágenes de telas existentes SOLO se muestran en la tabla (gestion-telas.js)
            // El storage debe estar limpio para AGREGAR TELAS NUEVAS
            // Esto evita que aparezcan precargadas en el input de agregar
            
            // Actualizar tabla de telas - Asignar a window.telasAgregadas para que se muestre en la tabla
            window.telasAgregadas = [...prenda.telasAgregadas];
            console.log('[cargarTelas] ✅ window.telasAgregadas asignadas:', window.telasAgregadas);

            
            // Actualizar tabla de telas
            if (window.actualizarTablaTelas) {
                console.log('[cargarTelas] 🔄 Llamando a actualizarTablaTelas()');
                window.actualizarTablaTelas();

            }
            
            // Actualizar preview de tela
            if (window.actualizarPreviewTela) {
                window.actualizarPreviewTela();
            }
            
            return;
        }


    }

    /**
     * Cargar tallas y cantidades
     * @private
     */
    cargarTallasYCantidades(prenda) {
        if (!window.tallasRelacionales) {
            window.tallasRelacionales = { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        }
        window.tallasRelacionales.DAMA = {};
        window.tallasRelacionales.CABALLERO = {};
        window.tallasRelacionales.UNISEX = {};

        console.log('[cargarTallasYCantidades] 🔍 Analizando prenda:', {
            tiene_tallas: !!prenda.tallas,
            tallas: prenda.tallas,
            tiene_generosConTallas: !!prenda.generosConTallas,
            generosConTallas: prenda.generosConTallas,
            tiene_tallas_disponibles: !!prenda.tallas_disponibles,
            tallas_disponibles: prenda.tallas_disponibles,
            tiene_genero_id: !!prenda.variantes?.genero_id,
            genero_id: prenda.variantes?.genero_id
        });

        // MAPEO de genero_id a nombre
        const generoMap = {
            1: 'DAMA',
            2: 'CABALLERO'
        };

        // Determinar género de la prenda desde genero_id
        const generoActual = prenda.variantes?.genero_id ? generoMap[prenda.variantes.genero_id] : null;
        
        console.log('[cargarTallasYCantidades] 👥 Género seleccionado:', generoActual);

        // CARGAR TALLAS DESDE generosConTallas (edición de BD)
        // O desde tallas_disponibles (prendas nuevas)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde generosConTallas:', prenda.generosConTallas);
            
            Object.entries(prenda.generosConTallas).forEach(([generoKey, tallaData]) => {
                const generoUpper = generoKey.toUpperCase();
                if (tallaData.cantidades && typeof tallaData.cantidades === 'object') {
                    window.tallasRelacionales[generoUpper] = { ...tallaData.cantidades };
                }
            });
        } else if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde array:', prenda.tallas);
            
            // Convertir array de tallas a objeto por género
            prenda.tallas.forEach(tallaObj => {
                const genero = tallaObj.genero || 'DAMA';
                const talla = tallaObj.talla;
                const cantidad = tallaObj.cantidad || 0;
                window.tallasRelacionales[genero][talla] = cantidad;
            });
        } else if (prenda.tallas_disponibles && Array.isArray(prenda.tallas_disponibles) && prenda.tallas_disponibles.length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas disponibles:', prenda.tallas_disponibles);
            
            // Cargar tallas en el género actual SIN cantidades (dejar vacío para que user digitee)
            if (generoActual) {
                prenda.tallas_disponibles.forEach(talla => {
                    window.tallasRelacionales[generoActual][talla] = 0;  // 0 = no pre-llenado
                });
            }
        } else {
            console.log('[cargarTallasYCantidades] ⚠️ No hay tallas disponibles en la prenda');
            return;
        }

        console.log('[cargarTallasYCantidades] 📊 window.tallasRelacionales:', window.tallasRelacionales);

        // Renderizar tallas desde estructura relacional
        Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
            const tallasList = Object.keys(tallasObj);  // TODOS los que están en el objeto, incluso con valor 0
            
            if (tallasList && tallasList.length > 0) {
                const generoLower = genero.toLowerCase();

                if (window.mostrarTallasDisponibles) {
                    window.mostrarTallasDisponibles('letra');
                }
                
                // Crear tarjeta de género con tallas
                setTimeout(() => {
                    // Llamar a la función que crea la tarjeta de género
                    if (window.crearTarjetaGenero) {
                        window.crearTarjetaGenero(generoLower);
                        console.log(`[cargarTallasYCantidades] ✓ Tarjeta creada para género: ${generoLower}`);
                    }
                    
                    // NO pre-llenar cantidades - dejar inputs vacíos
                    // El usuario digitará las cantidades manualmente
                    setTimeout(() => {
                        console.log('[cargarTallasYCantidades] 📋 Tallas mostradas sin cantidades pre-cargadas');
                    }, 200);
                }, 150);
            }
        });
        
        // Disparar eventos de cambio en los checkboxes de género
        ['dama', 'caballero', 'unisex'].forEach(genero => {
            const checkboxGenero = document.querySelector(`input[value="${genero}"]`);
            if (checkboxGenero) {
                checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    /**
     * Cargar variaciones de la prenda
     * @private
     */
    cargarVariaciones(prenda) {
        const variantes = prenda.variantes || {};
        const aplicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');
        
        // CARGAR GÉNERO DESDE VARIANTES (genero_id: 1=DAMA, 2=CABALLERO)
        if (variantes.genero_id) {
            console.log('[cargarVariaciones] 👥 Cargando género desde variantes:', {
                genero_id: variantes.genero_id,
                genero_nombre: variantes.genero
            });
            
            const generoMap = {
                1: 'DAMA',
                2: 'CABALLERO'
            };
            
            const generoSeleccionado = generoMap[variantes.genero_id];
            
            if (generoSeleccionado) {
                // Marcar checkbox del género
                const checkboxGenero = document.querySelector(`input[value="${generoSeleccionado.toLowerCase()}"]`);
                if (checkboxGenero) {
                    console.log(`[cargarVariaciones] ✓ Marcando checkbox género: ${generoSeleccionado}`);
                    checkboxGenero.checked = true;
                    checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    console.warn(`[cargarVariaciones] ⚠️ No encontré checkbox para género: ${generoSeleccionado}`);
                }
            }
        }

        // MANGA
        if (aplicaManga && (variantes.tipo_manga || variantes.manga)) {
            aplicaManga.checked = true;
            aplicaManga.dispatchEvent(new Event('change', { bubbles: true }));
            
            const mangaInput = document.getElementById('manga-input');
            if (mangaInput) {
                // Normalizar el valor: convertir a minúscula y sin acentos
                let valorManga = variantes.tipo_manga || variantes.manga || '';
                valorManga = valorManga.toLowerCase()
                    .replace(/á/g, 'a')
                    .replace(/é/g, 'e')
                    .replace(/í/g, 'i')
                    .replace(/ó/g, 'o')
                    .replace(/ú/g, 'u');
                
                mangaInput.value = valorManga;
                mangaInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const mangaObs = document.getElementById('manga-obs');
            if (mangaObs) {
                mangaObs.value = variantes.obs_manga || '';
                mangaObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // BOLSILLOS
        if (aplicaBolsillos && (variantes.tiene_bolsillos === true || variantes.obs_bolsillos)) {
            aplicaBolsillos.checked = true;
            aplicaBolsillos.dispatchEvent(new Event('change', { bubbles: true }));
            
            const bolsillosObs = document.getElementById('bolsillos-obs');
            if (bolsillosObs) {
                bolsillosObs.value = variantes.obs_bolsillos || '';
                bolsillosObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // BROCHE/BOTÓN
        if (aplicaBroche && (variantes.tipo_broche || variantes.broche || variantes.obs_broche)) {
            aplicaBroche.checked = true;
            aplicaBroche.dispatchEvent(new Event('change', { bubbles: true }));
            
            console.log('[cargarVariaciones] 🔗 Broche/Botón encontrado:', {
                tipo_broche: variantes.tipo_broche,
                obs_broche: variantes.obs_broche,
                tipo_broche_id: variantes.tipo_broche_id
            });
            
            const brocheInput = document.getElementById('broche-input');
            if (brocheInput) {
                // Normalizar el valor: convertir a minúscula y sin acentos
                let valorBroche = variantes.tipo_broche || variantes.broche || '';
                valorBroche = valorBroche.toLowerCase()
                    .replace(/á/g, 'a')
                    .replace(/é/g, 'e')
                    .replace(/í/g, 'i')
                    .replace(/ó/g, 'o')
                    .replace(/ú/g, 'u');
                
                brocheInput.value = valorBroche;
                console.log('[cargarVariaciones] ✓ broche-input asignado:', brocheInput.value);
                brocheInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            const brocheObs = document.getElementById('broche-obs');
            if (brocheObs) {
                brocheObs.value = variantes.obs_broche || '';
                console.log('[cargarVariaciones] ✓ broche-obs asignado:', brocheObs.value);
                brocheObs.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                console.warn('[cargarVariaciones] ⚠️ No encontré elemento #broche-obs');
            }
        } else {
            console.log('[cargarVariaciones] ⚠️ Broche/Botón no aplica o sin datos');
        }

        // REFLECTIVO
        if (aplicaReflectivo && (variantes.tiene_reflectivo === true || variantes.obs_reflectivo)) {
            aplicaReflectivo.checked = true;
            // No disparar evento change para evitar abrir el modal automáticamente
            
            const reflectivoObs = document.getElementById('reflectivo-obs');
            if (reflectivoObs) {
                reflectivoObs.value = variantes.obs_reflectivo || '';
                // No disparar evento change aquí tampoco
            }
        }
    }

    /**
     * Normalizar procesos: convierte objeto a array si es necesario
     * Maneja ambos formatos: objeto {tipo: {...}} y array [{...}]
     * @private
     */
    normalizarProcesos(procesos) {
        if (!procesos) return [];
        
        // Si ya es un array (viene de BD), retornarlo tal cual
        if (Array.isArray(procesos)) {
            return procesos;
        }
        
        // Si es un objeto (viene de frontend nuevo), convertir a array
        if (typeof procesos === 'object') {
            return Object.values(procesos).filter(p => p !== null && p !== undefined);
        }
        
        return [];
    }

    /**
     * Cargar procesos de la prenda
     * IMPORTANTE: Maneja tanto procesos del formulario como de BD
     * Formulario: estructura puede tener ubicaciones como array
     * BD: puede venir con ubicaciones como array o string
     * @private
     */
    cargarProcesos(prenda) {
        // Normalizar procesos: maneja tanto array como objeto
        const procesosNormalizados = this.normalizarProcesos(prenda.procesos);
        
        if (!procesosNormalizados || procesosNormalizados.length === 0) {
            console.log(' [CARGAR-PROCESOS] Sin procesos en la prenda');
            return;
        }

        console.log('📋 [CARGAR-PROCESOS] Cargando procesos:', {
            total: procesosNormalizados.length,
            procesos: procesosNormalizados.map(p => ({
                id: p.id,
                tipo: p.tipo_proceso,
                nombre: p.nombre,
                nombre_proceso: p.nombre_proceso,
                tieneImagenes: !!p.imagenes,
                countImagenes: p.imagenes?.length || 0,
                tieneUbicaciones: !!p.ubicaciones,
                countUbicaciones: Array.isArray(p.ubicaciones) ? p.ubicaciones.length : 0
            }))
        });

        // Copiar procesos completos con todos sus datos
        window.procesosSeleccionados = {};
        
        // Los procesos vienen como array desde el backend
        procesosNormalizados.forEach((proceso, idx) => {
            if (proceso) {
                // IMPORTANTE: Si viene del formulario, proceso es {datos: {...}}
                // Si viene de BD, proceso es {...}
                const datosReales = proceso.datos ? proceso.datos : proceso;
                
                // El backend retorna 'tipo' directamente (ej: 'Reflectivo')
                const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${idx}`;
                const tipoProceso = tipoBackend.toLowerCase().replace(/\s+/g, '-');
                
                console.log(`📌 [CARGAR-PROCESOS] Procesando [${idx}] tipo="${tipoProceso}"`, {
                    tipoBackend: tipoBackend,
                    nombreProceso: datosReales.nombre_proceso,
                    tipoProceso: datosReales.tipo_proceso,
                    nombre: datosReales.nombre,
                    tieneImagenes: !!datosReales.imagenes,
                    countImagenes: datosReales.imagenes?.length || 0,
                    tieneUbicaciones: !!datosReales.ubicaciones,
                    procesoId: datosReales.id,
                    tipo_proceso_id: datosReales.tipo_proceso_id || 'N/A'
                });

                // Detectar y cargar ubicaciones de forma adaptativa
                let ubicacionesFormato = [];
                
                if (datosReales.ubicaciones) {
                    if (Array.isArray(datosReales.ubicaciones)) {
                        // Ya es array, usarlo directamente (puede venir del formulario o BD)
                        ubicacionesFormato = datosReales.ubicaciones;
                        console.log(`   [UBICACIONES] Detectado ARRAY:`, ubicacionesFormato);
                    } else if (typeof datosReales.ubicaciones === 'string') {
                        // String separado por comas, convertir a array
                        ubicacionesFormato = datosReales.ubicaciones
                            .split(',')
                            .map(u => u.trim())
                            .filter(u => u && u.length > 0);
                        console.log(`   [UBICACIONES] Detectado STRING, convertido a ARRAY:`, ubicacionesFormato);
                    } else if (typeof datosReales.ubicaciones === 'object') {
                        // Objeto (unlikely but defensive), extraer valores
                        ubicacionesFormato = Object.values(datosReales.ubicaciones)
                            .filter(u => u && (typeof u === 'string' || typeof u === 'object'));
                        console.log(`   [UBICACIONES] Detectado OBJETO, extraído:`, ubicacionesFormato);
                    }
                }

                // Convertir tallas si es necesario
                let tallasFormato = datosReales.tallas || { dama: {}, caballero: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {
                    tallasFormato = { dama: {}, caballero: {} };
                }
                
                const datosProces = {
                    id: datosReales.id,
                    tipo: tipoProceso,
                    nombre: tipoBackend,
                    nombre_proceso: datosReales.nombre_proceso,
                    tipo_proceso: datosReales.tipo_proceso,
                    tipo_proceso_id: datosReales.tipo_proceso_id, // 🔴 IMPORTANTE: Guardar el ID para enviar al servidor
                    ubicaciones: ubicacionesFormato,
                    observaciones: datosReales.observaciones || '',
                    tallas: tallasFormato,
                    imagenes: (datosReales.imagenes || []).map(img => {
                        // Extraer URL de imagen, manejando distintos formatos
                        if (typeof img === 'string') {
                            return img;
                        }
                        if (img instanceof File) {
                            return img;
                        }
                        // Si es objeto, obtener la URL correcta
                        const urlExtraida = img.url || img.ruta || img.ruta_webp || img.ruta_original || '';
                        console.log(`    🖼️ Imagen procesada:`, {
                            original: img,
                            urlExtraida: urlExtraida
                        });
                        return urlExtraida;
                    })
                };
                
                window.procesosSeleccionados[tipoProceso] = {
                    datos: datosProces
                };
                
                console.log(`✅ [CARGAR-PROCESOS] Proceso "${tipoProceso}" cargado:`, datosProces);

                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`checkbox-${tipoProceso}`);
                if (checkboxProceso) {
                    console.log(`☑️ [CARGAR-PROCESOS] Marcando checkbox para "${tipoProceso}"`);
                    checkboxProceso.checked = true;
                    // Evitar que el onclick se dispare automáticamente
                    checkboxProceso._ignorarOnclick = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxProceso._ignorarOnclick = false;
                } else {
                    console.warn(` [CARGAR-PROCESOS] No se encontró checkbox para "${tipoProceso}". Buscando por data-tipo...`);
                    // Intentar encontrar por data-tipo
                    const checkboxPorTipo = document.querySelector(`[data-tipo="${tipoProceso}"]`);
                    if (checkboxPorTipo) {
                        console.log(`☑️ [CARGAR-PROCESOS] Encontrado por data-tipo, marcando...`);
                        checkboxPorTipo.checked = true;
                        checkboxPorTipo._ignorarOnclick = true;
                        checkboxPorTipo.dispatchEvent(new Event('change', { bubbles: true }));
                        checkboxPorTipo._ignorarOnclick = false;
                    } else {
                        console.warn(` [CARGAR-PROCESOS] Tampoco encontrado checkbox por data-tipo="${tipoProceso}"`);
                    }
                }
            }
        });
        
        console.log('📊 [CARGAR-PROCESOS] Procesos seleccionados finales:', window.procesosSeleccionados);

        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {
            console.log('🎨 [CARGAR-PROCESOS] Renderizando tarjetas...');
            window.renderizarTarjetasProcesos();
        } else {
            console.error(' [CARGAR-PROCESOS] window.renderizarTarjetasProcesos no existe');
        }
    }

    /**
     * Cambiar botón de "Guardar Prenda" a "Guardar Cambios"
     * @private
     */
    cambiarBotonAGuardarCambios() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = ' Guardar Cambios';
            btnGuardar.setAttribute('data-editing', 'true');
        }
    }

    /**
     * Resetear estado de edición
     */
    resetearEdicion() {
        this.prendaEditIndex = null;
        
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '➕ Guardar Prenda';
            btnGuardar.removeAttribute('data-editing');
        }
    }

    /**
     * Obtener índice de prenda siendo editada
     */
    obtenerPrendaEditIndex() {
        return this.prendaEditIndex;
    }

    /**
     * Verificar si está en modo edición
     */
    estaEditando() {
        return this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
    }

    /**
     * Mostrar notificación
     * @private
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        if (this.notificationService) {
            this.notificationService.mostrar(mensaje, tipo);
        } else {

        }
    }
}

window.PrendaEditor = PrendaEditor;
