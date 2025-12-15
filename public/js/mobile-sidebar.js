// Script SOLO para móvil - Hamburguesa sidebar
(function() {
    'use strict';

    // Solo ejecutar en móvil
    if (window.innerWidth > 768) {
        return;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerBtn = document.getElementById('sidebarHamburger');
        const sidebar = document.getElementById('sidebar');

        if (!hamburgerBtn || !sidebar) {
            console.warn('Hamburguesa o sidebar no encontrados');
            return;
        }

        // Crear overlay si no existe
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        // Función para abrir sidebar
        function openSidebar() {
            sidebar.classList.add('open');
            hamburgerBtn.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Función para cerrar sidebar
        function closeSidebar() {
            sidebar.classList.remove('open');
            hamburgerBtn.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Función para toggle
        function toggleSidebar() {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        // Click en hamburguesa
        hamburgerBtn.addEventListener('click', toggleSidebar);

        // Click en overlay
        overlay.addEventListener('click', closeSidebar);

        // Click en enlaces del menú
        const menuLinks = sidebar.querySelectorAll('.menu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Solo cerrar si es un enlace directo (no submenu-toggle)
                if (!this.classList.contains('submenu-toggle')) {
                    closeSidebar();
                }
            });
        });

        // Cerrar al cambiar tamaño de ventana a desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    });
})();
