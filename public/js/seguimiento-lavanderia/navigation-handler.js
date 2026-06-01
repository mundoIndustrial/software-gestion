/**
 * NAVIGATION HANDLER - Seguimiento Lavandería
 * Maneja la navegación entre vistas, sidebar y URL
 */

class NavigationHandler {
    constructor() {
        this.body = document.body;
        this.sidebarToggle = document.getElementById('sidebarToggle');
        this.sidebar = document.querySelector('.talleres-sidebar');
        this.viewButtons = document.querySelectorAll('.sidebar-item[data-view]');
        this.views = document.querySelectorAll('.view-container');
        this.currentView = 'viewOrdenes';
        
        // Mapeo de vistas a parámetros de URL
        this.viewToTab = {
            'viewOrdenes': 'ordenes',
            'viewHistorialMovimientos': 'historial'
        };
        
        this.tabToView = {
            'ordenes': 'viewOrdenes',
            'historial': 'viewHistorialMovimientos'
        };
    }

    /**
     * Configura los event listeners de navegación
     */
    setupEventListeners() {
        if (this.sidebarToggle && this.sidebar) {
            this.sidebarToggle.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }

        this.viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.activateView(btn.dataset.view || 'viewOrdenes');
            });
        });

        // Manejar el botón atrás del navegador
        window.addEventListener('popstate', () => {
            this.loadViewFromUrl();
        });

        // Cargar vista inicial desde URL
        this.loadViewFromUrl();
    }

    /**
     * Carga la vista desde la URL
     */
    loadViewFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab') || 'ordenes';
        const viewId = this.tabToView[tab] || 'viewOrdenes';
        
        this.activateView(viewId, false);
    }

    /**
     * Alterna el sidebar
     */
    toggleSidebar() {
        this.body.classList.toggle('talleres-sidebar-collapsed');
    }

    /**
     * Activa una vista y actualiza la URL
     */
    activateView(viewId, updateUrl = true) {
        this.currentView = viewId;

        this.views.forEach(view => {
            view.style.display = view.id === viewId ? 'block' : 'none';
        });

        this.viewButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === viewId);
        });

        // Actualizar URL si es necesario
        if (updateUrl) {
            const tab = this.viewToTab[viewId] || 'ordenes';
            const newUrl = `${window.location.pathname}?tab=${tab}`;
            window.history.pushState({ tab }, '', newUrl);
        }
    }
}

export { NavigationHandler };
