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



        
        if (!prenda.imagenes || prenda.imagenes.length === 0) {

            return;
        }

        if (window.imagenesPrendaStorage) {

            window.imagenesPrendaStorage.limpiar();


            prenda.imagenes.forEach((img, idx) => {

                this.procesarImagen(img, idx);
            });


            this.actualizarPreviewImagenes(prenda.imagenes);
        } else {

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
        } else {

        }
    }

    /**
     * Cargar telas en el modal
     * @private
     */
    cargarTelas(prenda) {

        
        // Intentar cargar desde telasAgregadas (prendas nuevas Y prendas de BD editadas)
        if (prenda.telasAgregadas && prenda.telasAgregadas.length > 0) {

            
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
            

            
            // Cargar cada tela
            prenda.telasAgregadas.forEach((tela, idx) => {

                
                // Cargar imágenes de tela
                if (tela.imagenes && tela.imagenes.length > 0 && window.imagenesTelaStorage) {

                    
                    tela.imagenes.forEach((img, imgIdx) => {

                        
                        if (img.file instanceof File) {

                            window.imagenesTelaStorage.agregarImagen(img.file);
                        } else if (img.previewUrl || img.url || img.ruta) {
                            const urlImg = img.previewUrl || img.url || img.ruta;

                            
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

                }
            });
            
            // Actualizar tabla de telas - Asignar a window.telasAgregadas para que se muestre en la tabla
            window.telasAgregadas = [...prenda.telasAgregadas];

            
            // Actualizar tabla de telas
            if (window.actualizarTablaTelas) {
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





        // PRIORIDAD 1: Usar array relacional {genero, talla, cantidad} de prenda_pedido_tallas
        if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {

            const generosMap = {};
            
            // Iterar array de objetos {genero, talla, cantidad}
            prenda.tallas.forEach(tallaRecord => {
                const { genero, talla, cantidad } = tallaRecord;
                
                if (genero && talla && cantidad !== undefined) {
                    const generoUp = genero.toUpperCase();
                    if (window.tallasRelacionales[generoUp]) {
                        window.tallasRelacionales[generoUp][talla] = cantidad;
                    }
                    
                    if (!generosMap[genero]) {
                        generosMap[genero] = [];
                    }
                    if (!generosMap[genero].includes(talla)) {
                        generosMap[genero].push(talla);
                    }
                }
            });
            
            // Convertir a estructura esperada

        }
        // PRIORIDAD 2: Fallback a generosConTallas (estructura alternativa)
        else if (prenda.generosConTallas && Object.keys(prenda.generosConTallas).length > 0) {

            
            // Extraer cantidades a estructura relacional
            Object.entries(prenda.generosConTallas).forEach(([genero, generoData]) => {
                const generoUp = genero.toUpperCase();
                if (generoData && typeof generoData === 'object') {
                    if (generoData.cantidades && typeof generoData.cantidades === 'object') {
                        Object.entries(generoData.cantidades).forEach(([talla, cantidad]) => {
                            if (window.tallasRelacionales[generoUp]) {
                                window.tallasRelacionales[generoUp][talla] = cantidad;
                            }
                        });
                    }
                }
            });
        }
        

        
        // Renderizar tallas desde estructura relacional
        Object.entries(window.tallasRelacionales).forEach(([genero, tallasObj]) => {
            const tallasList = Object.keys(tallasObj).filter(t => tallasObj[t] > 0);
            if (tallasList && tallasList.length > 0) {
                const generoLower = genero.toLowerCase();

                if (window.mostrarTallasDisponibles) {
                    window.mostrarTallasDisponibles('letra');
                }
                
                // Crear tarjeta de género con tallas y cantidades
                setTimeout(() => {

                    
                    // Llamar a la función que crea la tarjeta de género
                    if (window.crearTarjetaGenero) {
                        window.crearTarjetaGenero(generoLower);

                    }
                    
                    // Cargar cantidades en los inputs después de crear la tarjeta
                    setTimeout(() => {


                        
                        tallasList.forEach(talla => {
                            const cantidad = window.tallasRelacionales[genero][talla];
                            const dataKey = `${generoLower}-${talla}`;
                            

                            
                            if (cantidad !== undefined && cantidad !== null) {
                                // Buscar input por data-key (formato: dama-M, dama-L, etc.)
                                const input = document.querySelector(`input[data-key="${dataKey}"]`);
                                if (input) {
                                    input.value = cantidad;
                                    input.dispatchEvent(new Event('change', { bubbles: true }));
                                    input.dispatchEvent(new Event('input', { bubbles: true }));

                                } else {

                                    // Debug: mostrar todos los inputs disponibles
                                    const allInputs = document.querySelectorAll('input[data-key]');

                                    allInputs.forEach(inp => console.log(`  - data-key: ${inp.dataset.key}`));
                                }
                            }
                        });
                    }, 200);
                }, 150);
            }
        });
        
        // También disparar eventos de cambio en los checkboxes de género
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
        const aplicaManga = document.getElementById('aplica-manga');
        const aplicaBolsillos = document.getElementById('aplica-bolsillos');
        const aplicaBroche = document.getElementById('aplica-broche');
        const aplicaReflectivo = document.getElementById('aplica-reflectivo');









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


            
            if (brocheInput && prenda.tipo_broche_boton_id) {
                // Mapear ID a valor del dropdown
                // 1 = Botón, 2 = Broche
                let valorSeleccionar = '';
                if (prenda.tipo_broche_boton_id === 1) {
                    valorSeleccionar = 'boton';
                } else if (prenda.tipo_broche_boton_id === 2) {
                    valorSeleccionar = 'broche';
                }
                


                
                // Buscar la opción que coincida
                let encontrado = false;
                for (let opt of brocheInput.options) {
                    if (opt.value.toLowerCase() === valorSeleccionar.toLowerCase()) {
                        brocheInput.value = opt.value;
                        encontrado = true;

                        break;
                    }
                }
                
                if (!encontrado) {

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

            return;
        }



        // Copiar procesos completos con todos sus datos
        window.procesosSeleccionados = {};
        
        // Los procesos vienen como array desde el backend
        prenda.procesos.forEach(proceso => {
            if (proceso) {
                // Obtener tipo de proceso desde nombre_proceso o tipo_proceso
                const tipoProceso = (proceso.nombre_proceso || proceso.tipo_proceso || 'proceso').toLowerCase();

                
                // Copiar datos completos del proceso



                
                // Convertir tallas si es necesario
                let tallasFormato = proceso.tallas || { dama: {}, caballero: {} };
                if (Array.isArray(tallasFormato) && tallasFormato.length === 0) {

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
                


                
                // Marcar checkbox del proceso
                const checkboxProceso = document.getElementById(`checkbox-${tipoProceso}`);
                if (checkboxProceso) {
                    checkboxProceso.checked = true;
                    // Evitar que el onclick se dispare automáticamente
                    checkboxProceso._ignorarOnclick = true;
                    checkboxProceso.dispatchEvent(new Event('change', { bubbles: true }));
                    checkboxProceso._ignorarOnclick = false;

                } else {

                }
            }
        });
        


        
        // Renderizar tarjetas de procesos
        if (window.renderizarTarjetasProcesos) {

            window.renderizarTarjetasProcesos();

        } else {

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

        }
    }
}

window.PrendaEditor = PrendaEditor;
