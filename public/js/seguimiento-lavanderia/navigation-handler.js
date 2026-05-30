/**
 * NAVIGATION HANDLER - Seguimiento Lavandería
 * Maneja la navegación entre vistas y el sidebar
 */

class NavigationHandler {
    constructor() {
        this.body = document.body;
        this.sidebarToggle = document.getElementById('sidebarToggle');
        this.sidebar = document.querySelector('.talleres-sidebar');
        this.viewButtons = document.querySelectorAll('.sidebar-item[data-view]');
        this.views = document.querySelectorAll('.view-container');
        this.currentView = 'viewOrdenes';
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
    }

    /**
     * Alterna el sidebar
     */
    toggleSidebar() {
        this.body.classList.toggle('talleres-sidebar-collapsed');
    }

    /**
     * Activa una vista
     */
    activateView(viewId) {
        this.currentView = viewId;

        this.views.forEach(view => {
            view.style.display = view.id === viewId ? 'block' : 'none';
        });

        this.viewButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === viewId);
        });
    }
}

export { NavigationHandler };
