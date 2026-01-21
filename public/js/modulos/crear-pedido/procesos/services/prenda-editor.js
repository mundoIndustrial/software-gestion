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
        console.log(`🔓 [PrendaEditor] abrirModal() llamado | esEdicion: ${esEdicion} | prendaIndex: ${prendaIndex}`)
        
        if (esEdicion && prendaIndex !== null && prendaIndex !== undefined) {
            this.prendaEditIndex = prendaIndex;
        } else {
            this.prendaEditIndex = null;
        }

        // Preparar modal
        if (window.ModalCleanup) {
            console.log(`    ModalCleanup disponible`)
            if (esEdicion) {
                window.ModalCleanup.prepararParaEditar(prendaIndex);
            } else {
                window.ModalCleanup.prepararParaNueva();
            }
        } else {
            console.warn(`    ModalCleanup NO disponible`)
        }

        // Mostrar modal
        console.log(`    Buscando modal con ID: ${this.modalId}`)
        const modal = document.getElementById(this.modalId);
        if (modal) {
            console.log(`    Modal encontrado`)
            console.log(`     - Tabla tbody: ${!!document.getElementById('tbody-telas')}`)
            console.log(`     - Campo tela: ${!!document.getElementById('nueva-prenda-tela')}`)
            console.log(`     - Campo color: ${!!document.getElementById('nueva-prenda-color')}`)
            console.log(`     - Campo ref: ${!!document.getElementById('nueva-prenda-referencia')}`)
            modal.style.display = 'flex';
            this.mostrarNotificacion('Modal abierto y limpiado', 'info');
        } else {
            console.error(` Modal ${this.modalId} no encontrado en el DOM`)
        }
    }

    /**
     * Cargar prenda en el modal para edición
     */
    cargarPrendaEnModal(prenda, prendaIndex) {
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
            this.cargarTallasYCantidades(prenda);
            this.cargarVariaciones(prenda);
            this.cargarProcesos(prenda);
            this.cambiarBotonAGuardarCambios();

            this.mostrarNotificacion('Prenda cargada para editar', 'success');
        } catch (error) {
            this.mostrarNotificacion(`Error al cargar prenda: ${error.message}`, 'error');
            console.error('Error al cargar prenda en modal:', error);
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

        console.log('[PrendaEditor] llenarCamposBasicos() - origen recibido:', prenda.origen);
        
        if (nombreField) nombreField.value = prenda.nombre_producto || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        if (origenField) {
            console.log('[PrendaEditor] origenField encontrado');
            console.log('[PrendaEditor] Opciones disponibles:', Array.from(origenField.options).map(o => ({ value: o.value, text: o.text })));
            
            // Función para normalizar texto (remover acentos)
            const normalizarTexto = (texto) => {
                return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            };
            
            // Buscar la opción que coincida con el origen (ignorando acentos)
            const origen = prenda.origen || 'bodega';
            const origenNormalizado = normalizarTexto(origen);
            let encontrado = false;
            
            for (let opt of origenField.options) {
                const optTextNormalizado = normalizarTexto(opt.textContent);
                const optValueNormalizado = normalizarTexto(opt.value);
                
                if (optValueNormalizado === origenNormalizado || optTextNormalizado === origenNormalizado) {
                    origenField.value = opt.value;
                    encontrado = true;
                    console.log('[PrendaEditor] Origen seleccionado:', opt.value, '(original:', opt.textContent, ')');
                    break;
                }
            }
            
            if (!encontrado) {
                console.log('[PrendaEditor] Origen no encontrado, usando valor directo:', origen);
                origenField.value = origen;
            }
            
            // Disparar evento de cambio para que se actualice la UI
            origenField.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            console.log('[PrendaEditor] origenField NO encontrado');
        }
    }

    /**
     * Cargar imágenes en el modal
     * @private
     */
    cargarImagenes(prenda) {
        if (!prenda.imagenes || prenda.imagenes.length === 0) {
            return;
        }

        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar();

            prenda.imagenes.forEach((img, idx) => {
                this.procesarImagen(img, idx);
            });

            this.actualizarPreviewImagenes(prenda.imagenes);
        }
    }

    /**
     * Procesar una imagen individual
     * @private
     */
    procesarImagen(img, idx = 0) {
        if (!img) return;

        if (img.file instanceof File) {
            window.imagenesPrendaStorage.agregarImagen(img.file);
        } else if (img.url || img.ruta) {
            const urlImagen = img.url || img.ruta;
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
        } else if (typeof img === 'string') {
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

    /**
     * Actualizar preview de imágenes
     * @private
     */
    actualizarPreviewImagenes(imagenes) {
        if (window.actualizarPreviewPrenda) {
            window.actualizarPreviewPrenda();
            return;
        }

        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');

        if (preview && window.imagenesPrendaStorage.images.length > 0) {
            const primerImg = window.imagenesPrendaStorage.images[0];
            const urlImg = primerImg.previewUrl || primerImg.url;
            preview.style.backgroundImage = `url('${urlImg}')`;
            preview.style.cursor = 'pointer';

            if (contador && window.imagenesPrendaStorage.images.length > 1) {
                contador.textContent = window.imagenesPrendaStorage.images.length;
            }
        }
    }

    /**
     * Cargar telas en el modal
     * @private
     */
    cargarTelas(prenda) {
        console.log('[PrendaEditor] cargarTelas() - Prenda recibida:', prenda);
        
        // Intentar cargar desde telasAgregadas (prendas nuevas Y prendas de BD editadas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {
            console.log('[PrendaEditor] Cargando telas desde telasAgregadas:', prenda.telasAgregadas);
            
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
            
            console.log('[PrendaEditor] Inputs de tela y preview limpiados para edición');
            
            // Cargar cada tela
            prenda.telasAgregadas.forEach((tela, idx) => {
                console.log(`[PrendaEditor] Procesando tela ${idx}:`, tela);
                
                // Cargar imágenes de tela
                if (tela.imagenes && tela.imagenes.length > 0 && window.imagenesTelaStorage) {
                    console.log(`[PrendaEditor] Tela ${idx} tiene ${tela.imagenes.length} imágenes`);
                    
                    tela.imagenes.forEach((img, imgIdx) => {
                        console.log(`[PrendaEditor]   Imagen ${imgIdx}:`, img);
                        
                        if (img.file instanceof File) {
                            console.log(`[PrendaEditor]   Imagen ${imgIdx} es File object`);
                            window.imagenesTelaStorage.agregarImagen(img.file);
                        } else if (img.previewUrl || img.url || img.ruta) {
                            const urlImg = img.previewUrl || img.url || img.ruta;
                            console.log(`[PrendaEditor]   Imagen ${imgIdx} es URL desde BD:`, urlImg);
                            
                            if (!window.imagenesTelaStorage.images) {
                                window.imagenesTelaStorage.images = [];
                            }
                            window.imagenesTelaStorage.images.push({
                                previewUrl: urlImg,
                                nombre: `tela_${idx}_${imgIdx}.webp`,
                                tamaño: 0,
                                file: null,
                                urlDesdeDB: true
                            });
                        }
                    });
                } else {
                    console.log(`[PrendaEditor] Tela ${idx} NO tiene imágenes`);
                }
            });
            
            // Actualizar tabla de telas - Asignar a window.telasAgregadas para que se muestre en la tabla
            window.telasAgregadas = [...prenda.telasAgregadas];
            console.log('[PrendaEditor] window.telasAgregadas actualizado con', window.telasAgregadas.length, 'telas');
            
            // Actualizar tabla de telas
            if (window.actualizarTablaTelas) {
                window.actualizarTablaTelas();
                console.log('[PrendaEditor] Tabla de telas actualizada');
            }
            
            // Actualizar preview de tela
            if (window.actualizarPreviewTela) {
                window.actualizarPreviewTela();
            }
            
            return;
        }

        console.log('[PrendaEditor] No hay telasAgregadas para cargar');
    }

    /**
     * Cargar tallas y cantidades
     * @private
     */
    cargarTallasYCantidades(prenda) {
        window.tallasSeleccionadas = {};
        window.cantidadesTallas = {};

        console.log('[PrendaEditor] cargarTallasYCantidades() - Prenda recibida:', prenda);
        console.log('[PrendaEditor] generosConTallas:', prenda.generosConTallas);
        console.log('[PrendaEditor] tallas:', prenda.tallas);

        // Intentar cargar desde generosConTallas (prendas de BD - NUEVA ESTRUCTURA)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            console.log('[PrendaEditor] Usando generosConTallas');
            window.tallasSeleccionadas = { ...prenda.generosConTallas };
            console.log('[PrendaEditor] Tallas cargadas desde generosConTallas (BD nueva):', window.tallasSeleccionadas);
            
            // Extraer cantidades desde generosConTallas.cantidades
            Object.entries(prenda.generosConTallas).forEach(([genero, generoData]) => {
                if (generoData.tallas && Array.isArray(generoData.tallas)) {
                    generoData.tallas.forEach(talla => {
                        const clave = `${genero}-${talla}`;
                        // Buscar cantidad en generosConTallas.cantidades primero
                        if (generoData.cantidades && generoData.cantidades[talla]) {
                            window.cantidadesTallas[clave] = generoData.cantidades[talla];
                        }
                        // Si no, buscar en prenda.tallas (estructura antigua)
                        else if (prenda.tallas && prenda.tallas[genero] && prenda.tallas[genero][talla]) {
                            window.cantidadesTallas[clave] = prenda.tallas[genero][talla];
                        }
                    });
                }
            });
            console.log('[PrendaEditor] Cantidades extraídas:', window.cantidadesTallas);
        }
        // Intentar cargar desde tallas (prendas de BD - estructura antigua)
        else if (prenda.tallas && typeof prenda.tallas === 'object' && !Array.isArray(prenda.tallas)) {
            console.log('[PrendaEditor] Usando tallas (estructura antigua)');
            window.tallasSeleccionadas = { ...prenda.tallas };
            console.log('[PrendaEditor] Tallas cargadas desde tallas (BD antigua):', window.tallasSeleccionadas);
            
            // Extraer cantidades desde prenda.tallas
            // Estructura puede ser: {dama: {L: 20, M: 20, S: 20}} O {dama: {tallas: [...], tipo: "letra", cantidades: {...}}}
            Object.entries(prenda.tallas).forEach(([genero, generoData]) => {
                if (generoData && typeof generoData === 'object') {
                    // Si tiene estructura nueva {tallas: [...], tipo: "...", cantidades: {...}}
                    if (generoData.cantidades && typeof generoData.cantidades === 'object') {
                        Object.entries(generoData.cantidades).forEach(([talla, cantidad]) => {
                            const clave = `${genero}-${talla}`;
                            window.cantidadesTallas[clave] = cantidad;
                        });
                    }
                    // Si tiene estructura antigua {L: 20, M: 20, S: 20}
                    else {
                        Object.entries(generoData).forEach(([talla, cantidad]) => {
                            // Solo si es número (cantidad), no si es string/array (tallas, tipo, etc.)
                            if (typeof cantidad === 'number') {
                                const clave = `${genero}-${talla}`;
                                window.cantidadesTallas[clave] = cantidad;
                            }
                        });
                    }
                }
            });
            console.log('[PrendaEditor] Cantidades extraídas desde tallas:', window.cantidadesTallas);
        }
        // Intentar cargar desde cantidad_talla (prendas de BD - estructura JSON)
        else if (prenda.cantidad_talla) {
            let cantidadTalla = prenda.cantidad_talla;
            
            // Si es string JSON, parsear
            if (typeof cantidadTalla === 'string') {
                try {
                    cantidadTalla = JSON.parse(cantidadTalla);
                } catch (e) {
                    console.error('[PrendaEditor] Error al parsear cantidad_talla:', e);
                    return;
                }
            }
            
            console.log('[PrendaEditor] cantidad_talla parseada:', cantidadTalla);
            
            // Procesar cantidad_talla para extraer tallas y cantidades
            if (cantidadTalla && typeof cantidadTalla === 'object') {
                const generosMap = {};
                
                // Iterar cada entrada: "dama-S": 10, "dama-L": 20, etc. O "S": 10, "L": 20
                Object.entries(cantidadTalla).forEach(([clave, cantidad]) => {
                    if (clave.includes('-')) {
                        // Formato: "dama-S": 10
                        const [genero, talla] = clave.split('-');
                        if (genero && talla) {
                            window.cantidadesTallas[clave] = cantidad;
                            if (!generosMap[genero]) {
                                generosMap[genero] = [];
                            }
                            if (!generosMap[genero].includes(talla)) {
                                generosMap[genero].push(talla);
                            }
                        }
                    } else {
                        // Formato simple: "S": 10 (asumir dama)
                        const genero = 'dama';
                        const talla = clave;
                        const claveFinal = `${genero}-${talla}`;
                        window.cantidadesTallas[claveFinal] = cantidad;
                        if (!generosMap[genero]) {
                            generosMap[genero] = [];
                        }
                        if (!generosMap[genero].includes(talla)) {
                            generosMap[genero].push(talla);
                        }
                    }
                });
                
                // Convertir a estructura esperada
                Object.entries(generosMap).forEach(([genero, tallas]) => {
                    window.tallasSeleccionadas[genero] = {
                        tallas: tallas,
                        tipo: 'letra' // Asumir tipo letra por defecto
                    };
                });
                
                console.log('[PrendaEditor] Tallas cargadas desde cantidad_talla (BD nueva)');
            }
        }

        // Cargar cantidades por talla (si existen en estructura antigua)
        if (prenda.cantidadesPorTalla) {
            window.cantidadesTallas = { ...prenda.cantidadesPorTalla };
        }
        
        // Asegurar que ambos géneros existan en window.tallasSeleccionadas
        if (!window.tallasSeleccionadas.dama) {
            window.tallasSeleccionadas.dama = { tallas: [], tipo: null };
        }
        if (!window.tallasSeleccionadas.caballero) {
            window.tallasSeleccionadas.caballero = { tallas: [], tipo: null };
        }
        
        console.log('[PrendaEditor] Tallas finales:', window.tallasSeleccionadas);
        console.log('[PrendaEditor] Cantidades finales:', window.cantidadesTallas);
        
        // Renderizar tabla de tallas para el primer género con tallas
        Object.keys(window.tallasSeleccionadas).forEach(genero => {
            const generoData = window.tallasSeleccionadas[genero];
            if (generoData && generoData.tallas && generoData.tallas.length > 0 && generoData.tipo) {
                console.log(`[PrendaEditor] Renderizando tallas para ${genero} tipo ${generoData.tipo}`);
                if (window.mostrarTallasDisponibles) {
                    window.mostrarTallasDisponibles(generoData.tipo);
                }
                
                // Crear tarjeta de género con tallas y cantidades
                setTimeout(() => {
                    console.log(`[PrendaEditor] Creando tarjeta de género para ${genero}...`);
                    
                    // Llamar a la función que crea la tarjeta de género
                    if (window.crearTarjetaGenero) {
                        window.crearTarjetaGenero(genero);
                        console.log(`[PrendaEditor] Tarjeta de género creada para ${genero}`);
                    }
                    
                    // Cargar cantidades en los inputs después de crear la tarjeta
                    setTimeout(() => {
                        console.log(`[PrendaEditor] Cargando cantidades para ${genero}...`);
                        console.log(`[PrendaEditor] Cantidades disponibles:`, window.cantidadesTallas);
                        
                        generoData.tallas.forEach(talla => {
                            const clave = `${genero}-${talla}`;
                            const cantidad = window.cantidadesTallas[clave];
                            
                            console.log(`[PrendaEditor] Buscando input para: ${clave}, cantidad: ${cantidad}`);
                            
                            if (cantidad !== undefined && cantidad !== null) {
                                // Buscar input por data-key (formato: dama-M, dama-L, etc.)
                                const input = document.querySelector(`input[data-key="${clave}"]`);
                                if (input) {
                                    input.value = cantidad;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                    input.dispatchEvent(new Event('input', { bubbles: true }));
                                    console.log(`[PrendaEditor]  Cantidad cargada: ${clave} = ${cantidad}`);
                                } else {
                                    console.log(`[PrendaEditor]  Input no encontrado para: ${clave}`);
                                    // Debug: mostrar todos los inputs disponibles
                                    const allInputs = document.querySelectorAll('input[data-key]');
                                    console.log(`[PrendaEditor] Inputs disponibles:`, allInputs.length);
                                    allInputs.forEach(inp => console.log(`  - data-key: ${inp.dataset.key}`));
                                }
                            }
                        });
                    }, 200);
                }, 150);
            }
        });
        
        // También disparar eventos de cambio en los checkboxes de género
        Object.keys(window.tallasSeleccionadas).forEach(genero => {
            const checkboxGenero = document.querySelector(`input[value="${genero}"]`);
            if (checkboxGenero) {
                checkboxGenero.dispatchEvent(new Event('change', { bubbles: true }));
                console.log(`[PrendaEditor] Evento de cambio disparado para ${genero}`);
            }
        });
    }

    /**
     * Cargar variaciones de la prenda
     * @private
     */
    cargarVariaciones(prenda) {
        const aplicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');

        console.log('[PrendaEditor] Cargando variaciones desde prenda:', prenda);
        console.log('[PrendaEditor] obs_manga:', prenda.obs_manga);
        console.log('[PrendaEditor] obs_bolsillos:', prenda.obs_bolsillos);
        console.log('[PrendaEditor] obs_broche:', prenda.obs_broche);
        console.log('[PrendaEditor] obs_reflectivo:', prenda.obs_reflectivo);
        console.log('[PrendaEditor] tiene_bolsillos:', prenda.tiene_bolsillos);
        console.log('[PrendaEditor] tiene_reflectivo:', prenda.tiene_reflectivo);

        // Cargar manga desde obs_manga o variantes.obs_manga
        if (aplicaManga && (prenda.obs_manga || (prenda.variantes && prenda.variantes.obs_manga))) {
            aplicaManga.checked = true;
            aplicaManga.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Cargar valor en manga-input
            const mangaInput = document.getElementById('manga-input');
            if (mangaInput) {
                mangaInput.value = prenda.obs_manga || prenda.variantes?.obs_manga || '';
                mangaInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // Cargar observaciones en manga-obs
            const mangaObs = document.getElementById('manga-obs');
            if (mangaObs) {
                mangaObs.value = prenda.obs_manga || prenda.variantes?.obs_manga || '';
                mangaObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Cargar bolsillos desde obs_bolsillos o variantes.obs_bolsillos
        if (aplicaBolsillos && (prenda.obs_bolsillos || (prenda.variantes && prenda.variantes.obs_bolsillos) || prenda.tiene_bolsillos)) {
            aplicaBolsillos.checked = true;
            aplicaBolsillos.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Cargar observaciones en bolsillos-obs
            const bolsillosObs = document.getElementById('bolsillos-obs');
            if (bolsillosObs) {
                bolsillosObs.value = prenda.obs_bolsillos || prenda.variantes?.obs_bolsillos || '';
                bolsillosObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Cargar broche desde obs_broche o variantes.obs_broche
        if (aplicaBroche && (prenda.obs_broche || (prenda.variantes && prenda.variantes.obs_broche))) {
            aplicaBroche.checked = true;
            aplicaBroche.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Cargar tipo de broche en dropdown (broche-input)
            const brocheInput = document.getElementById('broche-input');
            console.log('[PrendaEditor] tipo_broche_boton_id recibido:', prenda.tipo_broche_boton_id);
            console.log('[PrendaEditor] brocheInput encontrado:', !!brocheInput);
            
            if (brocheInput && prenda.tipo_broche_boton_id) {
                // Mapear ID a valor del dropdown
                // 1 = Botón, 2 = Broche
                let valorSeleccionar = '';
                if (prenda.tipo_broche_boton_id === 1) {
                    valorSeleccionar = 'boton';
                } else if (prenda.tipo_broche_boton_id === 2) {
                    valorSeleccionar = 'broche';
                }
                
                console.log('[PrendaEditor] Intentando seleccionar broche con valor:', valorSeleccionar);
                console.log('[PrendaEditor] Opciones disponibles:', Array.from(brocheInput.options).map(o => ({ value: o.value, text: o.text })));
                
                // Buscar la opción que coincida
                let encontrado = false;
                for (let opt of brocheInput.options) {
                    if (opt.value.toLowerCase() === valorSeleccionar.toLowerCase()) {
                        brocheInput.value = opt.value;
                        encontrado = true;
                        console.log('[PrendaEditor] Tipo de broche seleccionado:', opt.value, '(original:', opt.textContent, ')');
                        break;
                    }
                }
                
                if (!encontrado) {
                    console.log('[PrendaEditor] Tipo de broche no encontrado con valor:', valorSeleccionar);
                }
                
                brocheInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // Cargar observaciones en broche-obs
            const brocheObs = document.getElementById('broche-obs');
            if (brocheObs) {
                brocheObs.value = prenda.obs_broche || prenda.variantes?.obs_broche || '';
                brocheObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Cargar reflectivo desde obs_reflectivo o variantes.obs_reflectivo
        if (aplicaReflectivo && (prenda.obs_reflectivo || (prenda.variantes && prenda.variantes.obs_reflectivo) || prenda.tiene_reflectivo)) {
            aplicaReflectivo.checked = true;
            aplicaReflectivo.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Cargar observaciones en reflectivo-obs
            const reflectivoObs = document.getElementById('reflectivo-obs');
            if (reflectivoObs) {
                reflectivoObs.value = prenda.obs_reflectivo || prenda.variantes?.obs_reflectivo || '';
                reflectivoObs.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    /**
     * Cargar procesos de la prenda
     * @private
     */
    cargarProcesos(prenda) {
        if (!prenda.procesos || prenda.procesos.length === 0) {
            console.log('[PrendaEditor] Sin procesos para cargar');
            return;
        }

        console.log('[PrendaEditor] Cargando procesos:', prenda.procesos);

        // Copiar procesos completos con todos sus datos
        window.procesosSeleccionados = {};
        
        // Los procesos vienen como array desde el backend
        prenda.procesos.forEach(proceso => {
            if (proceso) {
                // Obtener tipo de proceso desde nombre_proceso o tipo_proceso
                const tipoProceso = (proceso.nombre_proceso || proceso.tipo_proceso || 'proceso').toLowerCase();
                console.log(`[PrendaEditor] Cargando proceso ${tipoProceso}:`, proceso);
                
                // Copiar datos completos del proceso
                console.log(`[PrendaEditor] Estructura de tallas recibida para ${tipoProceso}:`, proceso.tallas);
                console.log(`[PrendaEditor] Tipo de tallas:`, typeof proceso.tallas);
                console.log(`[PrendaEditor] Es array:`, Array.isArray(proceso.tallas));
                
                // Convertir tallas si es necesario
                let tallasFormato = proceso.tallas || { dama: {}, caballero: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {
                    console.log(`[PrendaEditor] Tallas es array vacío, usando estructura vacía`);
                    tallasFormato = { dama: {}, caballero: {} };
                }
                
                window.procesosSeleccionados[tipoProceso] = {
                    datos: {
                        tipo: tipoProceso,
                        nombre: proceso.nombre_proceso || proceso.tipo_proceso || tipoProceso,
                        ubicaciones: proceso.ubicaciones || [],
                        observaciones: proceso.observaciones || '',
                        tallas: tallasFormato,
                        imagenes: proceso.imagenes || []
                    }
                };
                
                console.log(`[PrendaEditor] Proceso ${tipoProceso} guardado en window.procesosSeleccionados`);
                console.log(`[PrendaEditor] Tallas guardadas:`, window.procesosSeleccionados[tipoProceso].datos.tallas);
                
                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`checkbox-${tipoProceso}`);
                if (checkboxProceso) {
                    checkboxProceso.checked = true;
                    // Evitar que el onclick se dispare automáticamente
                    checkboxProceso._ignorarOnclick = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxProceso._ignorarOnclick = false;
                    console.log(`[PrendaEditor] Checkbox ${tipoProceso} marcado`);
                } else {
                    console.warn(`[PrendaEditor] Checkbox #checkbox-${tipoProceso} NO encontrado`);
                }
            }
        });
        
        console.log('[PrendaEditor] Procesos cargados en window:', window.procesosSeleccionados);
        console.log('[PrendaEditor] Función renderizarTarjetasProcesos existe:', typeof window.renderizarTarjetasProcesos);
        
        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {
            console.log('[PrendaEditor] Llamando a renderizarTarjetasProcesos()...');
            window.renderizarTarjetasProcesos();
            console.log('[PrendaEditor] renderizarTarjetasProcesos() completado');
        } else {
            console.error('[PrendaEditor] ERROR: window.renderizarTarjetasProcesos NO existe');
        }
    }

    /**
     * Cambiar botón de "Guardar Prenda" a "Guardar Cambios"
     * @private
     */
    cambiarBotonAGuardarCambios() {
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '💾 Guardar Cambios';
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
            console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
        }
    }
}

window.PrendaEditor = PrendaEditor;
