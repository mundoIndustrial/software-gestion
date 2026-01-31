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
        
        // Guardar referencia global para detectar si es cotización
        window.prendaActual = prenda;
        console.log('[cargarTallasYCantidades] 📋 window.prendaActual asignado:', {
            cotizacion_id: prenda.cotizacion_id,
            tipo: prenda.tipo,
            nombre: prenda.nombre_prenda
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

        console.log('[llenarCamposBasicos] DEBUG Elementos encontrados:', {
            nombreField: !!nombreField,
            descripcionField: !!descripcionField,
            origenField: !!origenField,
            origenFieldTagName: origenField?.tagName,
            origenFieldOptions: origenField?.options?.length
        });
        
        if (nombreField) nombreField.value = prenda.nombre_prenda || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        
        if (origenField) {
            console.log('[llenarCamposBasicos] Datos de origen:', {
                prendaOrigen: prenda.origen,
                prendaDeBodega: prenda.de_bodega,
                tipoDeBodega: typeof prenda.de_bodega
            });
            
            // Función para normalizar texto (remover acentos)
            const normalizarTexto = (texto) => {
                return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            };
            
            // Determinar origen: prioridad: de_bodega > origen
            let origen = null;
            
            // PRIMERO: verificar de_bodega (campo de la BD)
            if (prenda.de_bodega !== undefined && prenda.de_bodega !== null) {
                if (prenda.de_bodega === true || prenda.de_bodega === 1 || prenda.de_bodega === '1') {
                    origen = 'bodega';
                } else if (prenda.de_bodega === false || prenda.de_bodega === 0 || prenda.de_bodega === '0') {
                    origen = 'confeccion';
                }
            }
            
            // SI NO hay de_bodega, usar origen del servidor
            if (!origen && prenda.origen) {
                origen = prenda.origen;
            }
            
            // SI NO hay ninguno, usar default
            if (!origen) {
                origen = 'confeccion';
            }
            
            console.log('[llenarCamposBasicos] Origen determinado:', {
                origen: origen,
                normalizado: normalizarTexto(origen)
            });
            
            const origenNormalizado = normalizarTexto(origen);
            let encontrado = false;
            
            // DEBUG: Mostrar todas las opciones disponibles
            console.log('[llenarCamposBasicos] Opciones disponibles en SELECT:');
            for (let i = 0; i < origenField.options.length; i++) {
                const opt = origenField.options[i];
                const optTextNormalizado = normalizarTexto(opt.textContent);
                const optValueNormalizado = normalizarTexto(opt.value);
                console.log(`  [${i}] value="${opt.value}" (${optValueNormalizado}) | text="${opt.textContent}" (${optTextNormalizado})`);
            }
            
            for (let opt of origenField.options) {
                const optTextNormalizado = normalizarTexto(opt.textContent);
                const optValueNormalizado = normalizarTexto(opt.value);
                
                if (optValueNormalizado === origenNormalizado || optTextNormalizado === origenNormalizado) {
                    console.log('[llenarCamposBasicos] ✅ Opción encontrada:', {
                        optValue: opt.value,
                        optText: opt.textContent,
                        asignando: opt.value
                    });
                    origenField.value = opt.value;
                    encontrado = true;
                    break;
                }
            }
            
            if (!encontrado) {
                console.log('[llenarCamposBasicos] ❌ Opción NO encontrada, asignando directo:', origen);
                origenField.value = origen;
            }
            
            console.log('[llenarCamposBasicos] SELECT después de asignación:', {
                selectValue: origenField.value,
                selectSelectedIndex: origenField.selectedIndex,
                selectSelectedOption: origenField.options[origenField.selectedIndex]?.value
            });
            
            // Disparar evento de cambio para que se actualice la UI
            origenField.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            console.error('[llenarCamposBasicos] ❌ SELECT #nueva-prenda-origen-select NO encontrado en el DOM');
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

        // 🔧 VERIFICAR/CREAR SERVICIO SI NO EXISTE (para supervisor de pedidos)
        if (!window.imagenesPrendaStorage) {
            console.log('🔧 [CARGAR-IMAGENES] Creando imagenesPrendaStorage para supervisor de pedidos...');
            try {
                // Verificar si ImageStorageService está disponible
                if (typeof ImageStorageService !== 'undefined') {
                    window.imagenesPrendaStorage = new ImageStorageService(3);
                    console.log('✅ [CARGAR-IMAGENES] imagenesPrendaStorage creado exitosamente');
                } else {
                    console.warn('⚠️ [CARGAR-IMAGENES] ImageStorageService no disponible, creando fallback manual');
                    // Crear fallback manual básico
                    window.imagenesPrendaStorage = {
                        images: [],
                        limpiar: function() {
                            this.images = [];
                            console.log('🧹 [CARGAR-IMAGENES] Storage limpiado (fallback)');
                        },
                        agregarImagen: function(file) {
                            if (file instanceof File) {
                                this.images.push({
                                    previewUrl: URL.createObjectURL(file),
                                    nombre: file.name,
                                    tamaño: file.size,
                                    file: file
                                });
                            }
                        },
                        agregarUrl: function(url, nombre = 'imagen') {
                            this.images.push({
                                previewUrl: url,
                                nombre: nombre,
                                tamaño: 0,
                                file: null,
                                urlDesdeDB: true
                            });
                        }
                    };
                    console.log('✅ [CARGAR-IMAGENES] Fallback manual creado');
                }
            } catch (error) {
                console.error('❌ [CARGAR-IMAGENES] Error creando storage:', error);
                return;
            }
        }

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
                    
                    // Agregar evento click para abrir galería
                    preview.onclick = (e) => {
                        e.stopPropagation();
                        if (window.mostrarGaleriaImagenesPrenda) {
                            const imagenes = window.imagenesPrendaStorage.images.map(img => ({
                                ...img,
                                url: img.previewUrl || img.url || img.ruta
                            }));
                            window.mostrarGaleriaImagenesPrenda(imagenes, 0, 0);
                        }
                    };
                    
                    console.log('[cargarImagenes] ✅ Imagen insertada en el DOM con evento click');
                } else {
                    console.warn('[cargarImagenes] ⚠️ Preview no encontrado o sin imágenes');
                }
            }, 100);
            
            console.log(`✅ [CARGAR-IMAGENES] ${imagenesACargar.length} imágenes cargadas desde ${origen}`);
        } else {
            console.error(' [CARGAR-IMAGENES] Aún no hay imagenesPrendaStorage disponible después del intento de creación');
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
            
            // Usar el método agregarUrl si existe (fallback manual) o agregar directamente
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(urlImagen, `imagen_${idx}.webp`);
            } else {
                // Método original para ImageStorageService
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
        }
        // CASO 4: img es string URL (BD alternativo)
        else if (typeof img === 'string') {
            console.log(`  [PROCESAR-IMAGEN] Imagen ${idx}: String URL:`, img);
            
            // Usar el método agregarUrl si existe (fallback manual) o agregar directamente
            if (window.imagenesPrendaStorage.agregarUrl) {
                window.imagenesPrendaStorage.agregarUrl(img, `imagen_${idx}.webp`);
            } else {
                // Método original para ImageStorageService
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
                        referencia: transformed.referencia,
                        fotosCount: transformed.imagenes.length,
                        fotos: transformed.imagenes
                    });
                    return transformed;
                });
                console.log('[cargarTelas] ✅ Transformación completada:', prenda.telasAgregadas);
            } else if (prenda.variantes && Array.isArray(prenda.variantes)) {
                // ===== EXTRAER TELAS DESDE TODAS LAS VARIANTES (solución directa) =====
                console.log('[cargarTelas] 🔄 Extrayendo telas desde TODAS las variantes');
                console.log('[cargarTelas] 📊 Total de variantes a procesar:', prenda.variantes.length);
                
                // Inicializar array para telas
                const telasAgregadasTemp = [];
                
                // Recorremos todas las variantes
                prenda.variantes.forEach((variante, varianteIndex) => {
                    console.log(`[cargarTelas] 📦 [Variante ${varianteIndex}] Procesando variante:`, {
                        tipo_manga: variante.tipo_manga,
                        tiene_bolsillos: variante.tiene_bolsillos,
                        tiene_telas_multiples: !!(variante.telas_multiples),
                        telas_multiples_count: variante.telas_multiples?.length || 0
                    });
                    
                    // Verificar si esta variante tiene telas_multiples
                    if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                        console.log(`[cargarTelas] 🧵 [Variante ${varianteIndex}] Encontradas ${variante.telas_multiples.length} telas`);
                        
                        // Recorrer todas las telas de esta variante
                        variante.telas_multiples.forEach((tela, telaIndex) => {
                            console.log(`[cargarTelas] 🎯 [Tela ${telaIndex}] Extrayendo:`, {
                                tela: tela.tela,
                                color: tela.color,
                                referencia: tela.referencia,
                                descripcion: tela.descripcion,
                                imagenes_count: tela.imagenes?.length || 0
                            });
                            
                            // Extraer y validar la referencia directamente
                            const referenciaExtraida = (tela.referencia !== undefined && tela.referencia !== null && tela.referencia !== '') 
                                ? String(tela.referencia).trim() 
                                : '';
                            
                            // Crear objeto de tela con todas las propiedades
                            const telaCompleta = {
                                id: tela.id || null,
                                nombre_tela: tela.tela || tela.nombre_tela || '',
                                color: tela.color || '',
                                referencia: referenciaExtraida, // <-- AQUÍ SE ASEGURA DE COPIAR LA REFERENCIA
                                descripcion: tela.descripcion || '',
                                grosor: tela.grosor || '',
                                composicion: tela.composicion || '',
                                imagenes: Array.isArray(tela.imagenes) ? tela.imagenes : [],
                                origen: 'variante_directa_modal',
                                variante_index: varianteIndex,
                                tela_index: telaIndex
                            };
                            
                            // Agregar al array de telas
                            telasAgregadasTemp.push(telaCompleta);
                            
                            console.log(`[cargarTelas] ✅ [Tela ${telaIndex}] Agregada correctamente:`, {
                                nombre: telaCompleta.nombre_tela,
                                color: telaCompleta.color,
                                referencia: `"${telaCompleta.referencia}"`,
                                descripcion: telaCompleta.descripcion
                            });
                        });
                    } else {
                        console.log(`[cargarTelas] ⚠️ [Variante ${varianteIndex}] No tiene telas_multiples válido`);
                    }
                });
                
                // Asignar el resultado final
                prenda.telasAgregadas = telasAgregadasTemp;
                
                console.log('[cargarTelas] 🎯 RESULTADO FINAL DE EXTRACCIÓN DIRECTA:');
                console.log(`[cargarTelas] 📊 Total de telas extraídas: ${prenda.telasAgregadas.length}`);
                
                // LOG FINAL: Verificar referencias extraídas
                console.log('[cargarTelas] � RESUMEN DE REFERENCIAS EXTRAÍDAS:');
                prenda.telasAgregadas.forEach((tela, idx) => {
                    console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" | descripción: "${tela.descripcion}"`);
                });
            } else {
                console.warn('[cargarTelas] ⚠️ No hay colores_telas ni telas_multiples para procesar');
                prenda.telasAgregadas = [];
            }
        } else {
            console.log('[cargarTelas] ℹ️ telasAgregadas ya tiene datos, no transformar');
        }
        
        // Intentar cargar desde telasAgregadas (prendas nuevas Y prendas de BD editadas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            console.log('[cargarTelas] ✓ Telas disponibles:', prenda.telasAgregadas.length);
            
            // 🔍 NUEVA LÓGICA: Verificar si las referencias están vacías y buscar en variantes
            const referenciasVacias = prenda.telasAgregadas.some(tela => !tela.referencia || tela.referencia === '');
            
            if (referenciasVacias) {
                console.log('[cargarTelas] 🔄 Referencias vacías detectadas, buscando en variantes para enriquecer');
                console.log('[cargarTelas] 🔍 ESTRUCTURA DE VARIANTES:', {
                    tiene_variantes: !!prenda.variantes,
                    variantes_es_array: Array.isArray(prenda.variantes),
                    variantes_tiene_telas_multiples: !!(prenda.variantes?.telas_multiples),
                    variantes_tipo: typeof prenda.variantes,
                    variantes_keys: prenda.variantes ? Object.keys(prenda.variantes) : [],
                    variantes_completo: prenda.variantes
                });
                
                // DEBUG: Mostrar estructura completa de variantes
                if (prenda.variantes) {
                    console.log('[cargarTelas] 🔍 ESTRUCTURA COMPLETA DE VARIANTES:');
                    console.log(JSON.stringify(prenda.variantes, null, 2));
                }
                
                let variantesParaProcesar = [];
                
                // CASO 1: variantes es un array de objetos
                if (Array.isArray(prenda.variantes)) {
                    variantesParaProcesar = prenda.variantes;
                    console.log('[cargarTelas] 📦 Usando variantes como array');
                }
                // CASO 2: variantes es un objeto con telas_multiples
                else if (prenda.variantes && typeof prenda.variantes === 'object' && !Array.isArray(prenda.variantes)) {
                    // Verificar si tiene telas_multiples directamente o si es un array de variantes
                    if (prenda.variantes.telas_multiples && Array.isArray(prenda.variantes.telas_multiples)) {
                        variantesParaProcesar = [prenda.variantes]; // Envolver en array para procesamiento uniforme
                        console.log('[cargarTelas] 📦 Usando variantes como objeto con telas_multiples');
                    } else if (Array.isArray(prenda.variantes)) {
                        variantesParaProcesar = prenda.variantes;
                        console.log('[cargarTelas] 📦 Usando variantes como array (corrección)');
                    } else {
                        console.log('[cargarTelas] ⚠️ Estructura de variantes no reconocida, mostrando todas las propiedades:');
                        console.log('[cargarTelas] 🔍 Propiedades de variantes:', Object.keys(prenda.variantes));
                        
                        // Buscar telas_multiples en cualquier propiedad
                        for (const [key, value] of Object.entries(prenda.variantes)) {
                            if (key.includes('tela') || key.includes('multiple')) {
                                console.log(`[cargarTelas] 🔍 Propiedad candidata:`, { key, value, isArray: Array.isArray(value) });
                            }
                        }
                    }
                }
                // CASO 3: prenda tiene telas_multiples directamente
                else if (prenda.telas_multiples && Array.isArray(prenda.telas_multiples)) {
                    variantesParaProcesar = [{ telas_multiples: prenda.telas_multiples }]; // Crear estructura artificial
                    console.log('[cargarTelas] 📦 Usando telas_multiples directamente en prenda');
                }
                
                if (variantesParaProcesar.length > 0) {
                    console.log(`[cargarTelas] 🎯 Procesando ${variantesParaProcesar.length} estructuras de variantes`);
                    
                    // Crear mapa de telas existentes para enriquecer
                    const mapaTelasExistentes = new Map();
                    prenda.telasAgregadas.forEach((tela, index) => {
                        const clave = `${tela.nombre_tela}|${tela.color}`;
                        mapaTelasExistentes.set(clave, { index, tela });
                        console.log(`[cargarTelas] 📍 Tela existente registrada: ${clave} (índice ${index})`);
                    });
                    
                    // Recorrer variantes para buscar referencias faltantes
                    variantesParaProcesar.forEach((variante, varianteIndex) => {
                        console.log(`[cargarTelas] 🔍 [Estructura ${varianteIndex}] Buscando referencias faltantes`);
                        
                        if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                            variante.telas_multiples.forEach((tela, telaIndex) => {
                                const nombre_tela = tela.tela || tela.nombre_tela || '';
                                const color = tela.color || '';
                                const referencia = tela.referencia || '';
                                const clave = `${nombre_tela}|${color}`;
                                
                                console.log(`[cargarTelas] 🎯 Analizando tela:`, {
                                    nombre_tela,
                                    color,
                                    referencia: `"${referencia}"`,
                                    clave
                                });
                                
                                // Buscar si existe una tela con esta combinación y sin referencia
                                if (mapaTelasExistentes.has(clave)) {
                                    const telaExistente = mapaTelasExistentes.get(clave);
                                    
                                    if (!telaExistente.tela.referencia || telaExistente.tela.referencia === '') {
                                        if (referencia && referencia !== '') {
                                            // Enriquecer la tela existente con la referencia
                                            const indiceOriginal = telaExistente.index;
                                            prenda.telasAgregadas[indiceOriginal].referencia = String(referencia).trim();
                                            prenda.telasAgregadas[indiceOriginal].origen = 'enriquecido_desde_variantes';
                                            
                                            console.log(`[cargarTelas] ✅ Tela enriquecida:`, {
                                                nombre: nombre_tela,
                                                color: color,
                                                referencia_anterior: '""',
                                                referencia_nueva: `"${referencia}"`,
                                                variante_index: varianteIndex,
                                                tela_index: telaIndex
                                            });
                                        } else {
                                            console.log(`[cargarTelas] ⚠️ Referencia vacía en variante también para:`, {
                                                nombre: nombre_tela,
                                                color: color,
                                                referencia_en_variante: `"${referencia}"`
                                            });
                                        }
                                    } else {
                                        console.log(`[cargarTelas] ℹ️ Tela ya tiene referencia:`, {
                                            nombre: nombre_tela,
                                            color: color,
                                            referencia_existente: `"${telaExistente.tela.referencia}"`
                                        });
                                    }
                                } else {
                                    console.log(`[cargarTelas] ℹ️ Tela no encontrada en existentes: ${clave}`);
                                }
                            });
                        } else {
                            console.log(`[cargarTelas] ⚠️ [Estructura ${varianteIndex}] No tiene telas_multiples válido`);
                        }
                    });
                    
                    // LOG FINAL de enriquecimiento
                    console.log('[cargarTelas] 🎯 RESULTADO DESPUÉS DE ENRIQUECIMIENTO:');
                    prenda.telasAgregadas.forEach((tela, idx) => {
                        console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" (origen: ${tela.origen || 'backend'})`);
                    });
                } else {
                    console.log('[cargarTelas] ⚠️ No se encontró estructura de variantes válida para procesar');
                    
                    // 🚨 SOLUCIÓN DE RESPALDO: Buscar directamente en la estructura del selector
                    console.log('[cargarTelas] 🔄 Intentando solución de respaldo directa...');
                    
                    // Buscar en la estructura original que viene del selector
                    // El selector tiene la estructura correcta con telas_multiples
                    if (window.prendaOriginalDesdeSelector) {
                        console.log('[cargarTelas] 🔍 Usando prenda original desde selector');
                        console.log('[cargarTelas] 🔍 Estructura original:', window.prendaOriginalDesdeSelector);
                        
                        const prendaOriginal = window.prendaOriginalDesdeSelector;
                        
                        // Buscar en variantes array del selector
                        if (prendaOriginal.variantes && Array.isArray(prendaOriginal.variantes)) {
                            prendaOriginal.variantes.forEach((variante, idx) => {
                                if (variante.telas_multiples && Array.isArray(variante.telas_multiples)) {
                                    console.log(`[cargarTelas] 🎯 [Original] Encontradas ${variante.telas_multiples.length} telas en variante ${idx}`);
                                    
                                    variante.telas_multiples.forEach((tela, telaIdx) => {
                                        const nombre_tela = tela.tela || tela.nombre_tela || '';
                                        const color = tela.color || '';
                                        const referencia = tela.referencia || '';
                                        const clave = `${nombre_tela}|${color}`;
                                        
                                        console.log(`[cargarTelas] 🎯 [Original] Analizando tela ${telaIdx}:`, {
                                            nombre_tela,
                                            color,
                                            referencia: `"${referencia}"`,
                                            clave
                                        });
                                        
                                        // Buscar y enriquecer telas existentes
                                        const mapaTelasExistentes = new Map();
                                        prenda.telasAgregadas.forEach((telaExistente, index) => {
                                            const claveExistente = `${telaExistente.nombre_tela}|${telaExistente.color}`;
                                            // Normalizar clave para comparación (ignorar mayúsculas/minúsculas y espacios)
                                            const claveNormalizada = claveExistente.toLowerCase().trim();
                                            mapaTelasExistentes.set(claveNormalizada, { index, tela: telaExistente, claveOriginal: claveExistente });
                                            console.log(`[cargarTelas] 📍 Tela existente registrada: "${claveExistente}" -> normalizada: "${claveNormalizada}" -> índice ${index}, referencia: "${telaExistente.referencia}"`);
                                        });
                                        
                                        // Normalizar clave de búsqueda también
                                        const claveNormalizada = clave.toLowerCase().trim();
                                        
                                        console.log(`[cargarTelas] 🔍 Buscando clave "${clave}" -> normalizada: "${claveNormalizada}" en mapa de telas existentes:`, {
                                            existe: mapaTelasExistentes.has(claveNormalizada),
                                            totalTelas: mapaTelasExistentes.size,
                                            clavesDisponibles: Array.from(mapaTelasExistentes.keys()),
                                            claveBuscada: `"${clave}"`,
                                            claveNormalizadaBuscada: `"${claveNormalizada}"`,
                                            referenciaBuscada: `"${referencia}"`
                                        });
                                        
                                        if (mapaTelasExistentes.has(claveNormalizada)) {
                                            const telaExistente = mapaTelasExistentes.get(claveNormalizada);
                                            console.log(`[cargarTelas] 🎯 Tela coincidente encontrada:`, {
                                                indice: telaExistente.index,
                                                clave_original: telaExistente.claveOriginal,
                                                referencia_actual: `"${telaExistente.tela.referencia}"`,
                                                referencia_vacia: !telaExistente.tela.referencia || telaExistente.tela.referencia === '',
                                                referencia_a_enriquecer: `"${referencia}"`
                                            });
                                            
                                            if (!telaExistente.tela.referencia || telaExistente.tela.referencia === '') {
                                                if (referencia && referencia !== '') {
                                                    const indiceOriginal = telaExistente.index;
                                                    const referenciaAnterior = prenda.telasAgregadas[indiceOriginal].referencia;
                                                    
                                                    prenda.telasAgregadas[indiceOriginal].referencia = String(referencia).trim();
                                                    prenda.telasAgregadas[indiceOriginal].origen = 'enriquecido_desde_selector';
                                                    
                                                    console.log(`[cargarTelas] ✅ [Original] Tela enriquecida:`, {
                                                        nombre: nombre_tela,
                                                        color: color,
                                                        referencia_anterior: `"${referenciaAnterior}"`,
                                                        referencia_nueva: `"${referencia}"`,
                                                        indice: indiceOriginal
                                                    });
                                                    
                                                    // Verificar que se guardó correctamente
                                                    const referenciaVerificada = prenda.telasAgregadas[indiceOriginal].referencia;
                                                    console.log(`[cargarTelas] 🔍 Verificación post-enriquecimiento:`, {
                                                        indice: indiceOriginal,
                                                        referencia_guardada: `"${referenciaVerificada}"`,
                                                        exito: referenciaVerificada === String(referencia).trim()
                                                    });
                                                } else {
                                                    console.log(`[cargarTelas] ⚠️ Referencia a enriquecer está vacía: "${referencia}"`);
                                                }
                                            } else {
                                                console.log(`[cargarTelas] ℹ️ Tela ya tiene referencia, no se enriquece: "${telaExistente.tela.referencia}"`);
                                            }
                                        } else {
                                            console.log(`[cargarTelas] ⚠️ No se encontró tela coincidente para clave: "${clave}"`);
                                        }
                                    });
                                }
                            });
                        }
                    } else {
                        console.log('[cargarTelas] ⚠️ No hay prenda original desde selector disponible');
                        
                        // ÚLTIMO RESPALDO: Buscar en cualquier propiedad que contenga "tela" o "multiple"
                        if (prenda.variantes && typeof prenda.variantes === 'object') {
                            console.log('[cargarTelas] 🔍 Búsqueda de última opción en todas las propiedades...');
                            
                            // Buscar telas_multiples en cualquier propiedad
                            for (const [key, value] of Object.entries(prenda.variantes)) {
                                if (key.includes('tela') || key.includes('multiple')) {
                                    console.log(`[cargarTelas] 🔍 Propiedad candidata:`, { key, value, isArray: Array.isArray(value) });
                                    
                                    if (Array.isArray(value) && value.length > 0) {
                                        value.forEach((tela, idx) => {
                                            if (tela.referencia) {
                                                console.log(`[cargarTelas] 🎯 [Último] Referencia encontrada en ${key}[${idx}]:`, tela.referencia);
                                                
                                                // Intentar enriquecer con esta referencia
                                                const nombre_tela = tela.tela || tela.nombre_tela || '';
                                                const color = tela.color || '';
                                                const clave = `${nombre_tela}|${color}`;
                                                
                                                prenda.telasAgregadas.forEach((telaExistente, index) => {
                                                    const claveExistente = `${telaExistente.nombre_tela}|${telaExistente.color}`;
                                                    if (claveExistente === clave && (!telaExistente.referencia || telaExistente.referencia === '')) {
                                                        prenda.telasAgregadas[index].referencia = String(tela.referencia).trim();
                                                        prenda.telasAgregadas[index].origen = 'enriquecido_ultimo_respaldo';
                                                        
                                                        console.log(`[cargarTelas] ✅ [Último] Tela enriquecida:`, {
                                                            nombre: nombre_tela,
                                                            color: color,
                                                            referencia_anterior: '""',
                                                            referencia_nueva: `"${tela.referencia}"`
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                console.log('[cargarTelas] ℹ️ Todas las telas ya tienen referencias o no hay variantes para enriquecer');
            }
                
            // LOG FINAL: Mostrar estado final de telasAgregadas
            console.log('[cargarTelas] 🎯 ESTADO FINAL DE telasAgregadas:');
            prenda.telasAgregadas.forEach((tela, idx) => {
                console.log(`  [${idx}] "${tela.nombre_tela}" - "${tela.color}" -> referencia: "${tela.referencia}" (origen: ${tela.origen || 'backend'})`);
            });          
            
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
            tiene_cantidad_talla: !!prenda.cantidad_talla,
            cantidad_talla: prenda.cantidad_talla,
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

        // CARGAR TALLAS DESDE:
        // 1. generosConTallas (edición de BD)
        // 2. cantidad_talla (prendas nuevas creadas en formulario)
        // 3. tallas (array)
        // 4. tallas_disponibles (prendas nuevas sin cantidades)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde generosConTallas:', prenda.generosConTallas);
            
            Object.entries(prenda.generosConTallas).forEach(([generoKey, tallaData]) => {
                const generoUpper = generoKey.toUpperCase();
                if (tallaData.cantidades && typeof tallaData.cantidades === 'object') {
                    window.tallasRelacionales[generoUpper] = { ...tallaData.cantidades };
                }
            });
        } else if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && Object.keys(prenda.cantidad_talla).length > 0) {
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde cantidad_talla (prendas nuevas):', prenda.cantidad_talla);
            
            // cantidad_talla tiene estructura: { DAMA: {S: 20, M: 20}, CABALLERO: {}, UNISEX: {} }
            Object.entries(prenda.cantidad_talla).forEach(([generoKey, tallasObj]) => {
                const generoUpper = generoKey.toUpperCase();
                if (tallasObj && typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                    window.tallasRelacionales[generoUpper] = { ...tallasObj };
                }
            });
        } else if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0 && prenda.cotizacion_id) {
            // SOLO para cotizaciones: Pre-seleccionar tallas que vienen de la base de datos
            console.log('[cargarTallasYCantidades] ✓ Cargando tallas desde cotización:', prenda.tallas);
            console.log('[cargarTallasYCantizacion] 📋 Género de la prenda:', prenda.genero);
            console.log('[cargarTallasYCantidades] 🏷️ ID Cotización:', prenda.cotizacion_id);
            
            // Convertir array de tallas a objeto por género usando el género de la prenda
            // Manejar ambos casos: genero como objeto con nombre o como string directo
            let generoPrenda = 'DAMA'; // valor por defecto
            if (prenda.genero) {
                if (typeof prenda.genero === 'string') {
                    generoPrenda = prenda.genero.toUpperCase();
                } else if (prenda.genero.nombre) {
                    generoPrenda = prenda.genero.nombre.toUpperCase();
                }
            }
            console.log(`[cargarTallasYCantidades] 👤 Usando género de la prenda: ${generoPrenda}`);
            
            // Guardar tallas de la cotización para pre-selección (solo para cotizaciones)
            window.tallasDesdeCotizacion = window.tallasDesdeCotizacion || {};
            window.tallasDesdeCotizacion[generoPrenda] = new Set();
            
            prenda.tallas.forEach(tallaObj => {
                const talla = tallaObj.talla;
                const cantidad = tallaObj.cantidad || 0;
                console.log(`[cargarTallasYCantidades] 📏 Agregando ${generoPrenda} - ${talla}: ${cantidad}`);
                window.tallasRelacionales[generoPrenda][talla] = cantidad;
                window.tallasDesdeCotizacion[generoPrenda].add(talla);
            });
            
            console.log('[cargarTallasYCantidades] 📋 Tallas de cotización para pre-selección:', window.tallasDesdeCotizacion);
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
                    
                    // PRE-CARGAR CANTIDADES Y PRE-SELECCIONAR TALLAS (SOLO para cotizaciones)
                    const tieneCantidades = Object.values(tallasObj).some(cantidad => cantidad > 0);
                    const esDesdeCotizacion = window.tallasDesdeCotizacion && window.tallasDesdeCotizacion[genero.toUpperCase()];
                    
                    if (esDesdeCotizacion) {
                        console.log('[cargarTallasYCantidades] 📋 Pre-cargando cantidades y pre-seleccionando tallas desde cotización');
                        
                        // Esperar a que se renderice la tarjeta y luego llenar cantidades y seleccionar checkboxes
                        setTimeout(() => {
                            Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                                // Pre-cargar cantidad
                                const input = document.querySelector(`input[data-talla="${talla}"][data-genero="${generoLower}"]`);
                                if (input) {
                                    if (cantidad > 0) {
                                        input.value = cantidad;
                                        console.log(`[cargarTallasYCantidades] ✏️ Pre-cargada ${generoLower} - ${talla}: ${cantidad}`);
                                    }
                                    
                                    // Pre-seleccionar checkbox si viene de cotización
                                    if (esDesdeCotizacion && window.tallasDesdeCotizacion[genero.toUpperCase()].has(talla)) {
                                        const checkbox = document.querySelector(`input[type="checkbox"][value="${talla}"][data-genero="${generoLower}"]`);
                                        if (checkbox && !checkbox.checked) {
                                            checkbox.checked = true;
                                            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                                            console.log(`[cargarTallasYCantidades] ☑️ Pre-seleccionado ${generoLower} - ${talla}`);
                                        }
                                    }
                                }
                            });
                        }, 300);
                    } else {
                        console.log('[cargarTallasYCantidades] 📋 Tallas mostradas sin pre-carga (no es cotización)');
                    }
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
                
                // El backend retorna 'tipo' directamente (ej: 'Bordado')
                // También puede traer 'slug' para facilitar la identificación
                const tipoBackend = datosReales.tipo || datosReales.tipo_proceso || datosReales.nombre || `Proceso ${idx}`;
                const slugDirecto = datosReales.slug || null;  // Si viene el slug directamente
                const tipoProceso = slugDirecto || tipoBackend.toLowerCase().replace(/\s+/g, '-');
                
                console.log(`📌 [CARGAR-PROCESOS] Procesando [${idx}] tipo="${tipoProceso}"`, {
                    tipoBackend: tipoBackend,
                    slug: slugDirecto,
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
                    // NUEVO: Variaciones de prenda (ej: manga, bolsillos, broche)
                    variaciones_prenda: datosReales.variaciones_prenda || {},
                    // NUEVO: Talla cantidad desde técnicas de logo
                    talla_cantidad: datosReales.talla_cantidad || {},
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
            console.log(' [CARGAR-PROCESOS] Renderizando tarjetas...');
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
            // Si viene de una cotización, mantener "Agregar Prenda"
            if (window.prendaActual && window.prendaActual.cotizacion_id) {
                console.log('[cargarTallasYCantidades] 🏷️ Viene de cotización, manteniendo "Agregar Prenda"');
                // No cambiar el texto, mantener "Agregar Prenda"
                btnGuardar.setAttribute('data-editing', 'false');
            } else {
                // Si es edición normal, cambiar a "Guardar Cambios"
                console.log('[cargarTallasYCantidades] 📝 Es edición normal, cambiando a "Guardar Cambios"');
                btnGuardar.innerHTML = '<span class="material-symbols-rounded">save</span>Guardar Cambios';
                btnGuardar.setAttribute('data-editing', 'true');
            }
        }
    }

    /**
     * Resetear estado de edición
     */
    resetearEdicion() {
        this.prendaEditIndex = null;
        
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
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
