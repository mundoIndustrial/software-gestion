/**
 * MODAL CLEANUP - Gestor de limpieza de modales y formularios
 * 
 * Centraliza toda la lógica de limpieza de inputs, storages y estados
 * para mantener los modales limpios entre usos
 * 
 * @module ModalCleanup
 */

class ModalCleanup {
    /**
     * Limpiar completo: inputs, checkboxes, storages y estados
     */
    static limpiarTodo() {
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes();
        this.limpiarProcesos();
        this.limpiarContenedores();
    }

    /**
     * Limpiar todos los inputs y textareas del modal de prenda
     */
    static limpiarFormulario() {
        const idsInputs = [
            'nueva-prenda-nombre',
            'nueva-prenda-descripcion',
            'nueva-prenda-origen-select',
            'manga-input',
            'manga-obs',
            'bolsillos-obs',
            'broche-input',
            'broche-obs',
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia',
            'nueva-prenda-foto-contador'
        ];

        idsInputs.forEach(id => {
            const element = DOMUtils.getElement(id);
            if (element) {
                if (element.type === 'select-one') {
                    element.value = element.querySelector('option')?.value || '';
                } else {
                    element.value = '';
                }
            }
        });

        console.log('🧹 [ModalCleanup] Formulario limpiado');
    }

    /**
     * Limpiar todos los storages globales
     */
    static limpiarStorages() {
        // Limpiar storage de imágenes
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar?.();
            console.log('🧹 [ModalCleanup] Storage de imágenes limpiado');
        }

        // Limpiar telas agregadas
        if (window.telasAgregadas) {
            window.telasAgregadas.length = 0;
            console.log('🧹 [ModalCleanup] Telas agregadas limpiadas');
        }

        // Limpiar cantidades por talla
        if (window.cantidadesTallas) {
            Object.keys(window.cantidadesTallas).forEach(key => {
                delete window.cantidadesTallas[key];
            });
            console.log('🧹 [ModalCleanup] Cantidades de tallas limpiadas');
        }

        // Limpiar tallas seleccionadas
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas.dama = { tallas: [], tipo: null };
            window.tallasSeleccionadas.caballero = { tallas: [], tipo: null };
            console.log('🧹 [ModalCleanup] Tallas seleccionadas limpias');
        }
    }

    /**
     * Limpiar todos los checkboxes del modal
     * @param {boolean} preservarProcesos - Si true, no limpiar checkboxes de procesos
     */
    static limpiarCheckboxes(preservarProcesos = false) {
        const checkboxesBásicos = [
            'aplica-manga',
            'aplica-bolsillos',
            'aplica-broche',
            'checkbox-reflectivo'
        ];

        checkboxesBásicos.forEach(id => {
            DOMUtils.setChecked(id, false);
        });

        // Limpiar procesos solo si no se especifica preservarlos
        if (!preservarProcesos) {
            const checkboxesProcesos = [
                'checkbox-bordado',
                'checkbox-estampado',
                'checkbox-dtf',
                'checkbox-sublimado'
            ];

            checkboxesProcesos.forEach(id => {
                DOMUtils.setChecked(id, false);
            });

            console.log('🧹 [ModalCleanup] Checkboxes de procesos limpiados');
        }

        console.log('🧹 [ModalCleanup] Checkboxes limpios');
    }

    /**
     * Limpiar procesos seleccionados
     * @param {boolean} preservar - Si true, no limpiar procesos
     */
    static limpiarProcesos(preservar = false) {
        if (!preservar && window.procesosSeleccionados) {
            Object.keys(window.procesosSeleccionados).forEach(key => {
                delete window.procesosSeleccionados[key];
            });
            console.log('🧹 [ModalCleanup] Procesos seleccionados limpiados');
        }
    }

    /**
     * Limpiar contenedores visuales (tablas, galerías, etc)
     */
    static limpiarContenedores() {
        // Limpiar tabla de telas - PERO MANTENER LA FILA BASE CON INPUTS
        const tbodyTelas = DOMUtils.getElement('tbody-telas');
        if (tbodyTelas) {
            const filas = tbodyTelas.querySelectorAll('tr');
            let filasEliminadas = 0;
            
            // Eliminar SOLO las filas agregadas (aquellas que no contienen los inputs principales)
            filas.forEach(fila => {
                const telaInput = fila.querySelector('#nueva-prenda-tela');
                // Si NO tiene el input #nueva-prenda-tela, es una fila agregada y se elimina
                if (!telaInput) {
                    fila.remove();
                    filasEliminadas++;
                }
            });
            
            console.log(`🧹 [ModalCleanup] ${filasEliminadas} filas de telas agregadas eliminadas (fila base preservada)`);
        }

        // Limpiar preview de tela
        const telaPreview = DOMUtils.getElement('nueva-prenda-tela-preview');
        if (telaPreview) {
            telaPreview.innerHTML = '';
        }

        // Limpiar galería de fotos
        const fotosPreview = DOMUtils.getElement('nueva-prenda-foto-preview');
        if (fotosPreview) {
            fotosPreview.innerHTML = FOTOS_PREVIEW_VACIO_HTML;
        }

        // Limpiar contenedor de géneros
        const tarjetasGenerosContainer = DOMUtils.getElement('tarjetas-generos-container');
        if (tarjetasGenerosContainer) {
            tarjetasGenerosContainer.innerHTML = '';
        }

        // Limpiar contenedor de procesos
        const contenedorTarjetas = DOMUtils.getElement('contenedor-tarjetas-procesos');
        if (contenedorTarjetas) {
            contenedorTarjetas.innerHTML = '';
        }

        console.log('🧹 [ModalCleanup] Contenedores limpiados');
    }

    /**
     * Limpiar solo inputs específicos de tela
     */
    static limpiarTela() {
        const idsTelaInputs = [
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia'
        ];

        DOMUtils.clearValues(idsTelaInputs);
        
        const telaPreview = DOMUtils.getElement('nueva-prenda-tela-preview');
        if (telaPreview) {
            telaPreview.innerHTML = '';
        }

        console.log('🧹 [ModalCleanup] Datos de tela limpios');
    }

    /**
     * Limpiar solo fotos
     */
    static limpiarFotos() {
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar?.();
        }

        const fotosPreview = DOMUtils.getElement('nueva-prenda-foto-preview');
        if (fotosPreview) {
            fotosPreview.innerHTML = FOTOS_PREVIEW_VACIO_HTML;
        }

        DOMUtils.clearValue('nueva-prenda-foto-input');
        DOMUtils.clearValue('nueva-prenda-foto-contador');

        console.log('🧹 [ModalCleanup] Fotos limpias');
    }

    /**
     * Limpiar solo géneros y tallas
     */
    static limpiarGenerosYTallas() {
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas.dama = { tallas: [], tipo: null };
            window.tallasSeleccionadas.caballero = { tallas: [], tipo: null };
        }

        if (window.cantidadesTallas) {
            Object.keys(window.cantidadesTallas).forEach(key => {
                delete window.cantidadesTallas[key];
            });
        }

        const tarjetasGenerosContainer = DOMUtils.getElement('tarjetas-generos-container');
        if (tarjetasGenerosContainer) {
            tarjetasGenerosContainer.innerHTML = '';
        }

        // Resetear botones de género
        const btnDama = DOMUtils.getElement('btn-genero-dama');
        const btnCaballero = DOMUtils.getElement('btn-genero-caballero');
        if (btnDama) btnDama.classList.remove('active', 'bg-pink-100', 'border-pink-500');
        if (btnCaballero) btnCaballero.classList.remove('active', 'bg-blue-100', 'border-blue-500');

        const totalPrendas = DOMUtils.getElement('total-prendas');
        if (totalPrendas) {
            totalPrendas.textContent = '0';
        }

        console.log('🧹 [ModalCleanup] Géneros y tallas limpios');
    }

    /**
     * Limpiar solo variaciones
     */
    static limpiarVariaciones() {
        const idsVariaciones = [
            'manga-input',
            'manga-obs',
            'bolsillos-obs',
            'broche-input',
            'broche-obs'
        ];

        DOMUtils.clearValues(idsVariaciones);

        const checkboxesVariaciones = [
            'aplica-manga',
            'aplica-bolsillos',
            'aplica-broche',
            'checkbox-reflectivo'
        ];

        DOMUtils.setCheckedAll(checkboxesVariaciones, false);

        console.log('🧹 [ModalCleanup] Variaciones limpias');
    }

    /**
     * Preparar modal para creación de NUEVA prenda
     */
    static prepararParaNueva() {
        console.log('🎯 [ModalCleanup] Preparando modal para crear NUEVA prenda');
        
        // Debug: Verificar si los campos de tela existen ANTES de limpiar
        const telaField = document.getElementById('nueva-prenda-tela');
        const colorField = document.getElementById('nueva-prenda-color');
        const refField = document.getElementById('nueva-prenda-referencia');
        const telaPreview = document.getElementById('nueva-prenda-tela-preview');
        const tbody = document.getElementById('tbody-telas');
        
        console.log('    DEBUG - Verificando campos ANTES de limpiar:');
        console.log(`     - nueva-prenda-tela existe: ${!!telaField} ${telaField ? `| display: ${window.getComputedStyle(telaField).display} | offsetHeight: ${telaField.offsetHeight}` : ''}`)
        console.log(`     - nueva-prenda-color existe: ${!!colorField} ${colorField ? `| display: ${window.getComputedStyle(colorField).display} | offsetHeight: ${colorField.offsetHeight}` : ''}`)
        console.log(`     - nueva-prenda-referencia existe: ${!!refField} ${refField ? `| display: ${window.getComputedStyle(refField).display} | offsetHeight: ${refField.offsetHeight}` : ''}`)
        console.log(`     - tbody-telas existe: ${!!tbody}`)
        
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes(false); // Limpiar TODO
        this.limpiarProcesos(false); // Limpiar TODO
        this.limpiarContenedores();
        
        // Resetear estado de edición
        window.prendaEditIndex = null;
        
        console.log(' [ModalCleanup] Modal listo para crear nueva prenda');
    }

    /**
     * Preparar modal para EDITAR prenda existente
     * @param {number} prendaIndex - Índice de la prenda a editar
     */
    static prepararParaEditar(prendaIndex) {
        console.log(`🎯 [ModalCleanup] Preparando modal para EDITAR prenda (índice: ${prendaIndex})`);
        
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes(true); // Preservar procesos
        this.limpiarContenedores();
        
        // Preservar procesos existentes
        // this.limpiarProcesos(true) - mantener para que se carguen desde prenda
        
        // Establecer índice de edición
        window.prendaEditIndex = prendaIndex;
        
        console.log(` [ModalCleanup] Modal listo para editar prenda (índice: ${prendaIndex})`);
    }

    /**
     * Resetear datos de índice de edición
     */
    static resetearEdicion() {
        window.prendaEditIndex = null;
        console.log('🔄 [ModalCleanup] Índice de edición reseteado');
    }

    /**
     * Limpiar modal completamente (después de guardar)
     */
    static limpiarDespuésDeGuardar() {
        console.log('🧹 [ModalCleanup] Limpiando después de guardar');
        
        this.limpiarTodo();
        this.resetearEdicion();
        
        // Ocultar modal
        const modal = DOMUtils.getElement('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
        
        console.log(' [ModalCleanup] Modal limpiado completamente');
    }
}

// Hacer disponible globalmente
window.ModalCleanup = ModalCleanup;
