document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('sidebarHamburger');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    // Crear overlay si no existe
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Función para abrir/cerrar sidebar
    function toggleSidebar() {
        const isOpen = sidebar.classList.contains('open');
        
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function openSidebar() {
        sidebar.classList.add('open');
        hamburgerBtn.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        hamburgerBtn.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Event listeners
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    // Cerrar sidebar al hacer clic en el overlay
    overlay.addEventListener('click', closeSidebar);

    // Cerrar sidebar al hacer clic en un enlace del menú
    const menuLinks = sidebar.querySelectorAll('.menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Solo cerrar si es un enlace directo (no un submenu-toggle)
            if (!this.classList.contains('submenu-toggle')) {
                closeSidebar();
            }
        });
    });

    // Cerrar sidebar al cambiar el tamaño de la ventana (cuando pasa a desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // Mostrar/ocultar hamburguesa según el tamaño de pantalla
    function updateHamburgerVisibility() {
        if (window.innerWidth <= 768) {
            hamburgerBtn.style.display = 'flex';
        } else {
            hamburgerBtn.style.display = 'none';
            closeSidebar();
        }
    }

    // Ejecutar al cargar y al cambiar tamaño
    updateHamburgerVisibility();
    window.addEventListener('resize', updateHamburgerVisibility);
});
