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

        if (nombreField) nombreField.value = prenda.nombre_producto || '';
        if (descripcionField) descripcionField.value = prenda.descripcion || '';
        if (origenField) origenField.value = prenda.origen || 'bodega';
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
        // Intentar cargar desde telasAgregadas (prendas nuevas)
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
                // Cargar imágenes de tela
                if (tela.imagenes && tela.imagenes.length > 0 && window.imagenesTelaStorage) {
                    tela.imagenes.forEach((img) => {
                        if (img.file instanceof File) {
                            window.imagenesTelaStorage.agregarImagen(img.file);
                        } else if (img.previewUrl || img.url) {
                            const urlImg = img.previewUrl || img.url;
                            if (!window.imagenesTelaStorage.images) {
                                window.imagenesTelaStorage.images = [];
                            }
                            window.imagenesTelaStorage.images.push({
                                previewUrl: urlImg,
                                nombre: `tela_${idx}.webp`,
                                tamaño: 0,
                                file: null,
                                urlDesdeDB: true
                            });
                        }
                    });
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

        // Intentar cargar desde telas (prendas de BD)
        if (!prenda.telas || prenda.telas.length === 0) {
            return;
        }

        if (window.TelaProcessor) {
            const telaResult = window.TelaProcessor.cargarTelaDesdeBaseDatos(prenda);
            if (telaResult.procesada && telaResult.telaObj) {
                window.TelaProcessor.agregarTelaAlStorage(telaResult.telaObj);
            }
        }
    }

    /**
     * Cargar tallas y cantidades
     * @private
     */
    cargarTallasYCantidades(prenda) {
        window.tallasSeleccionadas = {};
        window.cantidadesTallas = {};

        // Intentar cargar desde generosConTallas (prendas nuevas)
        if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {
            window.tallasSeleccionadas = { ...prenda.generosConTallas };
            console.log('[PrendaEditor] Tallas cargadas desde generosConTallas');
        }
        // Intentar cargar desde tallas (prendas de BD - estructura antigua)
        else if (prenda.tallas && Array.isArray(prenda.tallas)) {
            prenda.tallas.forEach(tallaData => {
                window.tallasSeleccionadas[tallaData.genero] = {
                    tallas: tallaData.tallas,
                    tipo: tallaData.tipo
                };
            });
            console.log('[PrendaEditor] Tallas cargadas desde tallas (BD antigua)');
        }
        // Intentar cargar desde cantidad_talla (prendas de BD - estructura nueva JSON)
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
                
                // Iterar cada entrada: "dama-S": 10, "dama-L": 20, etc.
                Object.entries(cantidadTalla).forEach(([clave, cantidad]) => {
                    const [genero, talla] = clave.split('-');
                    
                    if (genero && talla) {
                        // Agregar a cantidades
                        window.cantidadesTallas[clave] = cantidad;
                        
                        // Agregar a tallas por género
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
        if (!prenda.variantes) {
            return;
        }

        const plicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');

        if (plicaManga && prenda.variantes.tipo_manga !== 'No aplica') {
            plicaManga.checked = true;
            plicaManga.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('manga-input').value = prenda.variantes.tipo_manga;
            document.getElementById('manga-obs').value = prenda.variantes.obs_manga;
        }

        if (aplicaBolsillos && prenda.variantes.tiene_bolsillos) {
            aplicaBolsillos.checked = true;
            aplicaBolsillos.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('bolsillos-obs').value = prenda.variantes.obs_bolsillos;
        }

        if (aplicaBroche && prenda.variantes.tipo_broche !== 'No aplica') {
            aplicaBroche.checked = true;
            aplicaBroche.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('broche-input').value = prenda.variantes.tipo_broche;
            document.getElementById('broche-obs').value = prenda.variantes.obs_broche;
        }

        if (aplicaReflectivo && prenda.variantes.tiene_reflectivo) {
            aplicaReflectivo.checked = true;
            aplicaReflectivo.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('reflectivo-obs').value = prenda.variantes.obs_reflectivo;
        }
    }

    /**
     * Cargar procesos de la prenda
     * @private
     */
    cargarProcesos(prenda) {
        if (!prenda.procesos || Object.keys(prenda.procesos).length === 0) {
            return;
        }

        console.log('[PrendaEditor] Cargando procesos:', prenda.procesos);

        // Copiar procesos completos con todos sus datos
        window.procesosSeleccionados = {};
        
        Object.entries(prenda.procesos).forEach(([tipoProceso, procesoData]) => {
            if (procesoData && procesoData.datos) {
                console.log(`[PrendaEditor] Cargando proceso ${tipoProceso}:`, procesoData.datos);
                
                // Copiar datos completos del proceso
                window.procesosSeleccionados[tipoProceso] = {
                    datos: {
                        tipo: procesoData.datos.tipo || tipoProceso,
                        ubicaciones: procesoData.datos.ubicaciones || [],
                        observaciones: procesoData.datos.observaciones || '',
                        tallas: procesoData.datos.tallas || { dama: {}, caballero: {} },
                        imagenes: procesoData.datos.imagenes || []
                    }
                };
                
                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`aplica-${tipoProceso}`);
                if (checkboxProceso) {
                    checkboxProceso.checked = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log(`[PrendaEditor] Checkbox ${tipoProceso} marcado`);
                }
            }
        });
        
        console.log('[PrendaEditor] Procesos cargados:', window.procesosSeleccionados);
        
        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {
            window.renderizarTarjetasProcesos();
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
