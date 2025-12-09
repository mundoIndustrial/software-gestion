/**
 * Dropdown Menu Management for Pedidos
 * Handles toggle, close, and z-index management for dropdown menus
 */

// Almacenar referencias de menús movidos al body
const movedMenus = new Map();

function toggleDropdown(event) {
    event.stopPropagation();
    const button = event.currentTarget;
    const menuId = button.getAttribute('data-menu-id');
    
    if (!menuId) {
        console.error('El botón no tiene data-menu-id');
        return;
    }
    
    // Obtener el menú por su ID
    let menu = document.getElementById(menuId);
    
    if (!menu) {
        console.error('No se encontró el menú con ID:', menuId);
        return;
    }
    
    // Cerrar todos los otros dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m.id !== menuId) {
            m.style.display = 'none';
        }
    });
    
    // Toggle el dropdown actual
    if (menu.style.display === 'none' || menu.style.display === '') {
        // Primero mover al body si no está ya
        if (menu.parentElement !== document.body) {
            movedMenus.set(menuId, menu.parentElement); // Guardar referencia del padre original
            document.body.appendChild(menu);
        }
        
        // Configurar posicionamiento
        menu.style.position = 'fixed';
        menu.style.display = 'block';
        
        // Calcular posición del botón en la pantalla
        const buttonRect = button.getBoundingClientRect();
        
        // Posicionar el dropdown debajo del botón, alineado a la izquierda
        menu.style.top = (buttonRect.bottom + 8) + 'px'; // 8px de separación
        menu.style.left = buttonRect.left + 'px';
        
    } else {
        menu.style.display = 'none';
    }
}

function closeDropdown() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    const clickedDropdown = event.target.closest('.dropdown-menu');
    const clickedButton = event.target.closest('button[data-menu-id]');
    
    if (!clickedDropdown && !clickedButton) {
        closeDropdown();
    }
});

// Reposicionar dropdown cuando la tabla hace scroll
document.addEventListener('scroll', function() {
    // Encontrar el menú abierto
    const openMenu = Array.from(document.querySelectorAll('.dropdown-menu')).find(m => m.style.display === 'block');
    
    if (openMenu) {
        const menuId = openMenu.id;
        const button = document.querySelector(`button[data-menu-id="${menuId}"]`);
        
        if (button) {
            const buttonRect = button.getBoundingClientRect();
            openMenu.style.top = (buttonRect.bottom + 8) + 'px';
            openMenu.style.left = buttonRect.left + 'px';
        }
    }
}, true); // Usar captura para detectar scroll en elementos internos

