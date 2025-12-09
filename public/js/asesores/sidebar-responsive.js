// ============================================================
// SIDEBAR RESPONSIVO - CONTROL DE MENÚ DESPLEGABLE
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en la página de cotizaciones
    const isQuotationPage = document.body.classList.contains('cotizaciones-prenda-create');
    if (!isQuotationPage) {
        return;
    }

    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    let sidebarOverlay = document.querySelector('.sidebar-overlay');

    // Crear overlay si no existe
    if (!sidebarOverlay) {
        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        document.body.appendChild(sidebarOverlay);
    }

    // Toggle sidebar al hacer click en botón hamburguesa
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Cerrar sidebar al hacer click en overlay
    sidebarOverlay.addEventListener('click', function() {
        closeSidebar();
    });

    // Cerrar sidebar al hacer click en un link
    const sidebarLinks = sidebar.querySelectorAll('.menu-link:not(.submenu-toggle)');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // No cerrar si es un submenu-toggle
            if (!this.classList.contains('submenu-toggle')) {
                closeSidebar();
            }
        });
    });

    // Controlar submenús
    const submenuToggles = sidebar.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSubmenu(this);
        });
    });

    // Cerrar sidebar al redimensionar a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // Funciones
    function toggleSidebar() {
        sidebar.classList.toggle('mobile-open');
        sidebarOverlay.classList.toggle('active');
        mainContent.classList.toggle('sidebar-open');
    }

    function closeSidebar() {
        sidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
        mainContent.classList.remove('sidebar-open');
    }

    function toggleSubmenu(toggle) {
        const submenu = toggle.nextElementSibling;
        if (submenu && submenu.classList.contains('submenu')) {
            submenu.classList.toggle('open');
            toggle.classList.toggle('active');
        }
    }

    // Exponer funciones globalmente si es necesario
    window.toggleSidebar = toggleSidebar;
    window.closeSidebar = closeSidebar;
});

// Prevenir scroll en body cuando sidebar está abierto
function preventScroll(e) {
    e.preventDefault();
}

const sidebar = document.getElementById('sidebar');
if (sidebar) {
    const sidebarObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (sidebar.classList.contains('mobile-open')) {
                    document.body.style.overflow = 'hidden';
                    window.addEventListener('wheel', preventScroll, { passive: false });
                } else {
                    document.body.style.overflow = '';
                    window.removeEventListener('wheel', preventScroll);
                }
            }
        });
    });

    sidebarObserver.observe(sidebar, { attributes: true });
}
