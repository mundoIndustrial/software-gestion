// Top Navigation - User Dropdown & Notifications
(function() {
    function initializeTopNav() {
        const userBtn = document.getElementById('userBtn');
        const userMenu = document.getElementById('userMenu');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebarEl = document.getElementById('sidebar');

        // Nota: El toggle del menú de notificaciones se maneja en notifications.js
        // para asegurar que cargue las notificaciones correctamente

        // Función para posicionar el menú del usuario correctamente usando position: fixed
        function positionUserMenu() {
            if (userBtn && userMenu) {
                const rect = userBtn.getBoundingClientRect();
                const menuWidth = 280;
                const padding = 20; // padding desde el borde derecho
                
                // Calcular posición: intentar alinear a la derecha del botón
                let leftPos = rect.right - menuWidth;
                
                // Si se sale del lado izquierdo, ajustar
                if (leftPos < padding) {
                    leftPos = padding;
                }
                
                // Si se sale del lado derecho, restar del lado derecho
                if (leftPos + menuWidth + padding > window.innerWidth) {
                    leftPos = window.innerWidth - menuWidth - padding;
                }
                
                userMenu.style.position = 'fixed';
                userMenu.style.top = (rect.bottom + 8) + 'px';
                userMenu.style.left = leftPos + 'px';
                userMenu.style.right = 'auto';
                
                // Debugging info
                const menuRect = userMenu.getBoundingClientRect();
                console.log('Posicionando menú en:', { 
                    top: rect.bottom + 8, 
                    left: leftPos,
                    menuWidth: userMenu.offsetWidth,
                    menuHeight: userMenu.offsetHeight,
                    menuVisibility: window.getComputedStyle(userMenu).visibility,
                    menuOpacity: window.getComputedStyle(userMenu).opacity,
                    menuRect: {
                        top: menuRect.top,
                        bottom: menuRect.bottom,
                        left: menuRect.left,
                        right: menuRect.right,
                        width: menuRect.width,
                        height: menuRect.height
                    }
                });
            }
        }

        // Toggle user menu
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                

                
                const isShowing = userMenu.classList.contains('show');
                
                if (!isShowing) {
                    positionUserMenu();
                }
                
                // Toggle 'show' class - CSS manejará los estilos de visibilidad
                userMenu.classList.toggle('show');
                userMenu.classList.toggle('active');
                

                
                // Cerrar notificaciones si se abre el menú de usuario
                if (notificationMenu && userMenu.classList.contains('show')) {
                    notificationMenu.classList.remove('show');
                    notificationMenu.classList.remove('active');
                }
            });
        } else {

        }

        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            // Cerrar menú de usuario si se hace click fuera
            if (userMenu && userBtn) {
                if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                    userMenu.classList.remove('show');
                    userMenu.classList.remove('active');
                }
            }
            
            // Cerrar menú de notificaciones si se hace click fuera
            if (notificationMenu && notificationBtn) {
                if (!notificationMenu.contains(e.target) && !notificationBtn.contains(e.target)) {
                    notificationMenu.classList.remove('show');
                }
            }
        }, true); // Usar captura para asegurar que se ejecute primero

        // Mobile toggle
        if (mobileToggle && sidebarEl) {
            mobileToggle.addEventListener('click', () => {
                sidebarEl.classList.toggle('show');
                const overlay = document.querySelector('.sidebar-overlay');
                if (overlay) {
                    overlay.classList.toggle('active');
                }
            });
        }

        // Close sidebar when clicking overlay
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        if (sidebarOverlay && sidebarEl) {
            sidebarOverlay.addEventListener('click', () => {
                sidebarEl.classList.remove('show');
                sidebarOverlay.classList.remove('active');
            });
        }
    }

    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTopNav);
    } else {
        // Si ya está cargado, ejecutar inmediatamente
        initializeTopNav();
    }
})();
