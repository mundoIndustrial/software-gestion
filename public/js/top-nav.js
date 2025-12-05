// Top Navigation - User Dropdown & Notifications
(function() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationMenu = document.getElementById('notificationMenu');
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebarEl = document.getElementById('sidebar');

    // Nota: El toggle del menú de notificaciones se maneja en notifications.js
    // para asegurar que cargue las notificaciones correctamente

    // Toggle user menu
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('show');
            if (notificationMenu) {
                notificationMenu.classList.remove('show');
            }
        });
    }

    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
        // Cerrar menú de usuario si se hace click fuera
        if (userMenu && userBtn) {
            if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                userMenu.classList.remove('show');
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
})();
