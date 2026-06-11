/**
 * SEGUIMIENTO LAVANDERÍA - Index
 * Orquesta todos los módulos de seguimiento de lavandería
 */

import { debounce } from './utilities.js';
import { OrdenesHandler } from './ordenes-handler.js';
import { DetallesModalHandler } from './detalles-modal-handler.js';
import { HistorialHandler } from './historial-handler.js';
import { FirmaModalHandler } from './firma-modal-handler.js';
import { NavigationHandler } from './navigation-handler.js';

class SeguimientoLavanderiaManager {
    constructor() {
        const root = document.querySelector('.seguimiento-lavanderia-main');
        this.apiOrdenes = root?.dataset?.apiOrdenes || '';
        this.apiHistorial = '/seguimiento-lavanderia/api/historial-movimientos';

        if (!this.apiOrdenes) {
            console.error('[SeguimientoLavanderiaManager] apiOrdenes no está definido');
            return;
        }

        this.ordenesHandler = new OrdenesHandler(this.apiOrdenes);
        this.detallesModalHandler = new DetallesModalHandler();
        this.historialHandler = new HistorialHandler(this.apiHistorial);
        this.firmaModalHandler = new FirmaModalHandler();
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
            this.firmaModalHandler.setupEventListeners();
            
            // Cargar datos según la vista actual
            this.loadDataForCurrentView();
        } catch (error) {
            console.error('[SeguimientoLavanderiaManager] Error en init:', error);
        }
    }

    /**
     * Carga los datos según la vista actual
     */
    loadDataForCurrentView() {
        const currentView = this.navigationHandler.currentView;
        
        if (currentView === 'viewOrdenes') {
            this.ordenesHandler.loadOrdenes(1);
        } else if (currentView === 'viewHistorialMovimientos') {
            this.historialHandler.loadMovimientos(1);
        }
    }

    /**
     * Configura los event listeners
     */
    setupEventListeners() {
        const { ordenesSearchInput, ordenesSearchClear } = this.ordenesHandler.elements;
        const { historialSearchInput, historialSearchClear } = this.historialHandler.elements;

        // Búsqueda de órdenes
        if (ordenesSearchInput) {
            ordenesSearchInput.addEventListener('input', debounce((e) => {
                this.ordenesHandler.handleSearchInput(e.target.value);
            }, 300));
        }

        // Limpiar búsqueda de órdenes
        if (ordenesSearchClear) {
            ordenesSearchClear.addEventListener('click', () => {
                this.ordenesHandler.clearSearch();
            });
        }

        // Búsqueda de movimientos en historial
        if (historialSearchInput) {
            historialSearchInput.addEventListener('input', debounce((e) => {
                this.historialHandler.handleSearchInput(e.target.value);
            }, 300));
        }

        // Limpiar búsqueda de historial
        if (historialSearchClear) {
            historialSearchClear.addEventListener('click', () => {
                this.historialHandler.clearSearch();
            });
        }

        // Escuchar cambios de vista
        const viewButtons = document.querySelectorAll('.sidebar-item[data-view]');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                setTimeout(() => {
                    this.loadDataForCurrentView();
                }, 100);
            });
        });
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

    window.abrirFirmaModal = (movimientoId, fecha, numeroMovimiento = null) => {
        window.seguimientoLavanderiaManager.firmaModalHandler.abrirModal(movimientoId, fecha, numeroMovimiento);
    };

    window.cerrarFirmaModal = () => {
        window.seguimientoLavanderiaManager.firmaModalHandler.cerrarModal();
    };

    window.abrirNovedadesModal = (movimientoId, novedad, fecha, numeroMovimiento = null) => {
        const modal = document.getElementById('novedadesModal');
        const title = document.getElementById('novedadesModalTitle');
        const fechaEl = document.getElementById('novedadesModalFecha');
        const body = document.getElementById('novedadesModalBody');
        
        const displayNum = numeroMovimiento || movimientoId;
        title.textContent = `Novedades del Movimiento #${displayNum}`;
        fechaEl.textContent = `Fecha: ${fecha}`;
        body.innerHTML = `<div style="padding: 20px; color: #1e293b; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">${novedad}</div>`;
        
        modal.classList.add('active');
    };

    window.cerrarNovedadesModal = () => {
        const modal = document.getElementById('novedadesModal');
        modal.classList.remove('active');
    };

    // Event listener para cerrar modales al hacer clic fuera
    document.addEventListener('click', (e) => {
        const novedadesModal = document.getElementById('novedadesModal');
        if (e.target === novedadesModal) {
            window.cerrarNovedadesModal();
        }
    });

    window.abrirDetallesMovimiento = (movimientoId, numeroMovimiento) => {
        // Por ahora, mostrar un mensaje
        console.log('Abriendo detalles del movimiento:', movimientoId);
        // En una implementación futura, se podría abrir un modal con detalles del movimiento
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
