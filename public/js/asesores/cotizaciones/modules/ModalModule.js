/**
 * ModalModule.js - Gestión de modales con SOLID
 * 
 * Maneja la apertura, cierre y eventos de modales
 * Elimina toda lógica de DOM manipulation del HTML
 */

const ModalModule = (() => {
    'use strict';

    // Selectores centralizados
    const SELECTORS = {
        modal: '#modalEspecificaciones',
        btnOpen: '#btnAbrirEspecificaciones',
        btnClose: '#btnCloseEspecificaciones',
        btnCancel: '#btnCancelEspecificaciones',
        btnSave: '#btnSaveEspecificaciones',
        table: '.tabla-control-compacta',
        addRowBtns: '.tabla-control-compacta .btn-add-row',
    };

    // Estado privado
    const state = {
        isOpen: false,
        currentCategoria: null,
    };

    /**
     * Inicializa los event listeners del modal
     */
    const init = () => {
        // Botones del modal
        const btnClose = document.querySelector(SELECTORS.btnClose);
        const btnCancel = document.querySelector(SELECTORS.btnCancel);
        const btnSave = document.querySelector(SELECTORS.btnSave);

        if (btnClose) btnClose.addEventListener('click', closeModal);
        if (btnCancel) btnCancel.addEventListener('click', closeModal);
        if (btnSave) btnSave.addEventListener('click', saveModal);

        // Botones de agregar filas
        setupRowAddButtons();

        // Click fuera del modal
        document.addEventListener('click', (e) => {
            const modal = document.querySelector(SELECTORS.modal);
            if (e.target === modal && state.isOpen) {
                closeModal();
            }
        });
    };

    /**
     * Abre el modal
     */
    const openModal = () => {
        const modal = document.querySelector(SELECTORS.modal);
        if (modal) {
            modal.classList.add('active');
            state.isOpen = true;
        }
    };

    /**
     * Cierra el modal
     */
    const closeModal = () => {
        const modal = document.querySelector(SELECTORS.modal);
        if (modal) {
            modal.classList.remove('active');
            state.isOpen = false;
        }
    };

    /**
     * Guarda datos del modal
     */
    const saveModal = () => {
        const checkboxes = document.querySelectorAll(`${SELECTORS.table} .checkbox-guardar`);
        const inputs = document.querySelectorAll(`${SELECTORS.table} input[type="text"]`);

        // Recolectar datos
        const data = {
            checkboxes: Array.from(checkboxes).map(cb => ({
                checked: cb.checked,
                value: cb.value
            })),
            observations: Array.from(inputs).map(input => ({
                name: input.name,
                value: input.value
            }))
        };

        console.log('Especificaciones guardadas:', data);

        // Llamar a módulo de especificaciones si existe
        if (window.especificacionesModule && typeof window.especificacionesModule.guardarEspecificaciones === 'function') {
            window.especificacionesModule.guardarEspecificaciones();
        }

        closeModal();
    };

    /**
     * Configura botones de agregar filas
     */
    const setupRowAddButtons = () => {
        const buttons = document.querySelectorAll(SELECTORS.addRowBtns);
        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const categoria = btn.getAttribute('data-categoria');
                if (categoria && typeof agregarFilaEspecificacion === 'function') {
                    agregarFilaEspecificacion(categoria);
                }
            });
        });
    };

    /**
     * Getter para estado
     */
    const getState = () => ({ ...state });

    // Inicializar cuando el DOM está listo
    document.addEventListener('DOMContentLoaded', init);

    // Exportar API pública
    return {
        openModal,
        closeModal,
        saveModal,
        getState,
        init
    };
})();

// Hacer disponible globalmente
window.modalModule = ModalModule;
