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
            console.warn(`   ⚠️ ModalCleanup NO disponible`)
        }

        // Mostrar modal
        console.log(`   🔍 Buscando modal con ID: ${this.modalId}`)
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
                this.procesarImagen(img);
            });

            this.actualizarPreviewImagenes(prenda.imagenes);
        }
    }

    /**
     * Procesar una imagen individual
     * @private
     */
    procesarImagen(img) {
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
        if (prenda.tallas && Array.isArray(prenda.tallas)) {
            prenda.tallas.forEach(tallaData => {
                window.tallasSeleccionadas[tallaData.genero] = {
                    tallas: tallaData.tallas,
                    tipo: tallaData.tipo
                };
            });
        }

        window.cantidadesTallas = {};
        if (prenda.cantidadesPorTalla) {
            window.cantidadesTallas = { ...prenda.cantidadesPorTalla };
        }
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
            document.getElementById('manga-input').value = prenda.variantes.tipo_manga;
            document.getElementById('manga-obs').value = prenda.variantes.obs_manga;
        }

        if (aplicaBolsillos && prenda.variantes.tiene_bolsillos) {
            aplicaBolsillos.checked = true;
            document.getElementById('bolsillos-obs').value = prenda.variantes.obs_bolsillos;
        }

        if (aplicaBroche && prenda.variantes.tipo_broche !== 'No aplica') {
            aplicaBroche.checked = true;
            document.getElementById('broche-input').value = prenda.variantes.tipo_broche;
            document.getElementById('broche-obs').value = prenda.variantes.obs_broche;
        }

        if (aplicaReflectivo && prenda.variantes.tiene_reflectivo) {
            aplicaReflectivo.checked = true;
            document.getElementById('reflectivo-obs').value = prenda.variantes.obs_reflectivo;
        }
    }

    /**
     * Cargar procesos de la prenda
     * @private
     */
    cargarProcesos(prenda) {
        if (!prenda.procesos) {
            return;
        }

        window.procesosSeleccionados = { ...prenda.procesos };
        
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
