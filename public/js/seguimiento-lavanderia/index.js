/**
 * SEGUIMIENTO LAVANDERÍA - Index
 * Orquesta todos los módulos de seguimiento de lavandería
 */

import { debounce } from './utilities.js';
import { OrdenesHandler } from './ordenes-handler.js';
import { DetallesModalHandler } from './detalles-modal-handler.js';
import { NavigationHandler } from './navigation-handler.js';

class SeguimientoLavanderiaManager {
    constructor() {
        const root = document.querySelector('.seguimiento-lavanderia-main');
        this.apiOrdenes = root?.dataset?.apiOrdenes || '';

        if (!this.apiOrdenes) {
            console.error('[SeguimientoLavanderiaManager] apiOrdenes no está definido');
            return;
        }

        this.ordenesHandler = new OrdenesHandler(this.apiOrdenes);
        this.detallesModalHandler = new DetallesModalHandler();
        this.navigationHandler = new NavigationHandler();

        this.init();
    }

    /**
     * Inicializa el módulo
     */
    init() {
        try {
            this.setupEventListeners();
            this.navigationHandler.setupEventListeners();
            this.detallesModalHandler.setupEventListeners();
            this.ordenesHandler.loadOrdenes(1);
        } catch (error) {
            console.error('[SeguimientoLavanderiaManager] Error en init:', error);
        }
    }

    /**
     * Configura los event listeners
     */
    setupEventListeners() {
        const { ordenesSearchInput, ordenesSearchClear } = this.ordenesHandler.elements;

        // Búsqueda de órdenes
        if (ordenesSearchInput) {
            ordenesSearchInput.addEventListener('input', debounce((e) => {
                this.ordenesHandler.handleSearchInput(e.target.value);
            }, 300));
        }

        // Limpiar búsqueda
        if (ordenesSearchClear) {
            ordenesSearchClear.addEventListener('click', () => {
                this.ordenesHandler.clearSearch();
            });
        }
    }
}

/**
 * Inicializa el módulo cuando el DOM esté listo
 */
function initSeguimientoLavanderia() {
    console.log('[SeguimientoLavandería] Inicializando módulo...');
    window.seguimientoLavanderiaManager = new SeguimientoLavanderiaManager();
    
    // Exponer funciones globales para el HTML
    window.abrirDetallesModal = (reciboId, numeroRecibo) => {
        window.seguimientoLavanderiaManager.detallesModalHandler.abrirModal(reciboId, numeroRecibo);
    };

    window.cerrarDetallesModal = () => {
        window.seguimientoLavanderiaManager.detallesModalHandler.cerrarModal();
    };

    console.log('[SeguimientoLavandería] Módulo inicializado');
}

// Intentar inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSeguimientoLavanderia);
} else {
    // El DOM ya está listo
    initSeguimientoLavanderia();
}

export { SeguimientoLavanderiaManager };
