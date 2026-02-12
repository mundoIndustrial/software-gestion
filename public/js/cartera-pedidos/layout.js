/**
 * =========================================
 * CARTERA PEDIDOS - LAYOUT JS
 * Funcionalidad básica del layout
 * =========================================
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const menuToggle = document.getElementById('menuToggle');
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');
    
    // SIDEBAR TOGGLE
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
    
    // MOBILE MENU TOGGLE
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // USER MENU DROPDOWN
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });
        
        // Cerrar menu cuando click afuera
        document.addEventListener('click', function(e) {
            if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
            }
        });
    }
    
    // Cerrar sidebar en mobile cuando navegan
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });
});
