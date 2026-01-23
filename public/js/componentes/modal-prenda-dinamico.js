/**
 * MODAL PRENDA DINÁMICO
 * Carga el modal del formulario de prendas dinámicamente en el DOM
 * Evita conflictos CSS al inyectarlo directamente en el body
 * 
 *  HTML IMPORTADO: desde modal-prenda-dinamico-constantes.js
 */

class ModalPrendaDinamico {
    constructor() {
        this.modalId = 'modal-agregar-prenda-nueva';
        this.modalHTML = MODAL_PRENDA_DINAMICO_HTML; //  Usar la constante importada
        this.inicializarDependencias();
    }

    /**
     * Inicializa las dependencias necesarias que podrían no estar disponibles
     * en algunos contextos (como edición de pedidos)
     */
    inicializarDependencias() {
        //  FALLBACK: manejarCheckboxProceso si no existe
        if (!window.manejarCheckboxProceso) {

            window.manejarCheckboxProceso = (tipoProceso, estaChecked) => {

                // Fallback simple: solo registrar en consola
                // El comportamiento real vendría de manejadores-procesos-prenda.js
            };
        } else {

        }

        //  FALLBACK: window.imagenesTelaStorage si no existe
        if (!window.imagenesTelaStorage) {

            window.imagenesTelaStorage = {
                obtenerImagenes: () => [],
                agregarImagen: (file) => {

                    // Retornar objeto con estructura esperada, no Promise
                    return { success: true, reason: 'FALLBACK' };
                },
                limpiar: () => {

                    return { success: true };
                },
                obtenerBlob: (index) => null
            };
        }

        //  FALLBACK: window.pedidosAPI si no existe
        if (!window.pedidosAPI) {

            window.pedidosAPI = {
                obtenerItems: () => Promise.resolve({ items: [] }),
                agregarItem: (data) => Promise.resolve({ success: true, items: [] })
            };
        }
    }

    /**
     * Obtiene el HTML del modal (desde constantes)
     */
    getModalHTML() {
        return this.modalHTML; //  Retorna la constante importada
    }

    /**
     * Inyecta el modal en el DOM
     *  DESHABILITADO: Usar el modal incluido en Blade en su lugar
     */
    inyectar() {

        const existsInDOM = !!document.getElementById(this.modalId);

        // El modal ya debe estar en el DOM desde el Blade template (modal-agregar-prenda-nueva.blade.php)
        return existsInDOM;
    }

    /**
     * Abre el modal
     */
    abrir() {

        this.inyectar();
        
        const modal = document.getElementById(this.modalId);
        if (!modal) {

            return false;
        }





        
        modal.style.display = 'flex';

        return true;
    }

    /**
     * Cierra el modal
     */
    cerrar() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Remueve el modal del DOM
     */
    remover() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.remove();
        }
    }
}

window.modalPrendaDinamico = new ModalPrendaDinamico();
