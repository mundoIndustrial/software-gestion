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
     * PROTECCIÓN: No limpiar si el usuario está escribiendo en un input
     */
    static limpiarFormulario() {
        // PROTECCIÓN: Verificar si hay un elemento con focus
        const elementoEnFoco = document.activeElement;
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

        // Si el usuario está escribiendo en algún input, no limpiar
        if (elementoEnFoco && idsInputs.includes(elementoEnFoco.id)) {

            return;
        }

        idsInputs.forEach(id => {
            const element = DOMUtils.getElement(id);
            if (element) {
                if (element.type === 'select-one') {
                    // 🔴 IMPORTANTE: NO resetear origen-select en modo edición
                    // Se cargará correctamente en llenarCamposBasicos()
                    if (id === 'nueva-prenda-origen-select' && window.prendaEditIndex !== null && window.prendaEditIndex !== undefined) {
                        console.log('🔴🔴🔴 [limpiarFormulario] ✅✅✅ SALTANDO LIMPIAR SELECT ORIGEN (MODO EDICIÓN) 🔴🔴🔴', {
                            prendaEditIndex: window.prendaEditIndex,
                            selectId: id,
                            valorActual: element.value,
                            razon: 'El valor será establecido en llenarCamposBasicos()'
                        });
                        return; // No limpiar en modo edición
                    }
                    element.value = element.querySelector('option')?.value || '';
                } else {
                    element.value = '';
                }
            }
        });


    }

    /**
     * Limpiar todos los storages globales
     */
    static limpiarStorages() {
        // Limpiar storage de imágenes de prenda
        if (window.imagenesPrendaStorage) {
            window.imagenesPrendaStorage.limpiar?.();

        }

        // Limpiar storage de imágenes de tela
        if (window.imagenesTelaStorage) {
            window.imagenesTelaStorage.limpiar?.();

        }

        // Limpiar telas agregadas (AMBOS FLUJOS: CREACIÓN y EDICIÓN - SEPARADOS)
        if (window.telasCreacion) {
            window.telasCreacion.length = 0;
        }
        if (window.telasEdicion) {
            window.telasEdicion.length = 0;
        }

        // Limpiar tallas relacionales (modelo nuevo: {GENERO: {TALLA: CANTIDAD}})
        if (window.tallasRelacionales) {
            window.tallasRelacionales.DAMA = {};
            window.tallasRelacionales.CABALLERO = {};
            window.tallasRelacionales.UNISEX = {};

        }

        // Limpieza de variables
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = { dama: { tallas: [], tipo: null }, caballero: { tallas: [], tipo: null } };
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


        }


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


    }

    /**
     * Limpiar solo géneros y tallas
     */
    static limpiarGenerosYTallas() {
        // Limpiar estructura relacional
        if (window.tallasRelacionales) {
            window.tallasRelacionales.DAMA = {};
            window.tallasRelacionales.CABALLERO = {};
            window.tallasRelacionales.UNISEX = {};
        }

        // Limpiar variables
        if (window.tallasSeleccionadas) {
            window.tallasSeleccionadas = { dama: { tallas: [], tipo: null }, caballero: { tallas: [], tipo: null } };
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


    }

    /**
     * Actualizar dinámicamente el título del modal
     * @param {string} modo - 'agregar' o 'editar'
     */
    static actualizarTituloModal(modo = 'agregar') {
        // Buscar el título en el modal
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (!modal) return;
        
        const modalTitle = modal.querySelector('.modal-title');
        if (!modalTitle) return;
        
        if (modo === 'editar') {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">edit</span>Edición de Prenda';
        } else {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">add_box</span>Agregar Prenda Nueva';
        }
    }

    /**
     * Preparar modal para creación de NUEVA prenda
     */
    static prepararParaNueva() {

        
        // Debug: Verificar si los campos de tela existen ANTES de limpiar
        const telaField = document.getElementById('nueva-prenda-tela');
        const colorField = document.getElementById('nueva-prenda-color');
        const refField = document.getElementById('nueva-prenda-referencia');
        const telaPreview = document.getElementById('nueva-prenda-tela-preview');
        const tbody = document.getElementById('tbody-telas');
        





        
        this.limpiarFormulario();
        this.limpiarStorages();
        this.limpiarCheckboxes(false); // Limpiar TODO
        this.limpiarProcesos(false); // Limpiar TODO
        this.limpiarContenedores();
        
        // Resetear estado de edición
        window.prendaEditIndex = null;
        
        // Cambiar título del modal a "Agregar Prenda Nueva"
        this.actualizarTituloModal('agregar');

    }

    /**
     * Preparar modal para EDITAR prenda existente
     * @param {number} prendaIndex - Índice de la prenda a editar
     */
    static prepararParaEditar(prendaIndex) {

        
        // NO limpiar storages en modo edición - se cargarán los datos de la prenda
        // Solo limpiar formulario e inputs
        this.limpiarFormulario();
        // NO llamar a limpiarStorages() - preservar telas e imágenes
        this.limpiarCheckboxes(true); // Preservar procesos
        // NO limpiar contenedores en modo edición - se cargarán los datos de la prenda
        // this.limpiarContenedores();
        
        // Establecer índice de edición
        window.prendaEditIndex = prendaIndex;
        
        // Cambiar título del modal a "Edición de Prenda"
        this.actualizarTituloModal('editar');

    }

    /**
     * Resetear datos de índice de edición
     */
    static resetearEdicion() {
        window.prendaEditIndex = null;

    }

    /**
     * Limpiar modal completamente (después de guardar)
     */
    static limpiarDespuésDeGuardar() {

        
        this.limpiarTodo();
        this.resetearEdicion();
        
        // Ocultar modal
        const modal = DOMUtils.getElement('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.display = 'none';
        }
        

    }
}

// Hacer disponible globalmente
window.ModalCleanup = ModalCleanup;
