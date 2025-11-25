/**
 * Dropdown Menu Management for Pedidos
 * Handles toggle, close, and z-index management for dropdown menus
 */

function toggleDropdown(event) {
    event.stopPropagation();
    const button = event.currentTarget;
    const menu = button.nextElementSibling;
    
    // Cerrar todos los otros dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    // Toggle el dropdown actual
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
        
        // Aumentar z-index del dropdown
        menu.style.zIndex = '99999';
        
        // Obtener el card padre y aumentar su z-index
        const card = button.closest('div[style*="grid-template-columns"]');
        if (card) {
            card.style.zIndex = '1000';
            card.style.position = 'relative';
        }
    } else {
        menu.style.display = 'none';
        
        // Restaurar z-index del card
        const card = button.closest('div[style*="grid-template-columns"]');
        if (card) {
            card.style.zIndex = 'auto';
        }
    }
}

function closeDropdown() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
    
    // Restaurar z-index de todos los cards
    document.querySelectorAll('div[style*="grid-template-columns"]').forEach(card => {
        card.style.zIndex = 'auto';
    });
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-menu') && !event.target.closest('button[onclick*="toggleDropdown"]')) {
        closeDropdown();
    }
});
