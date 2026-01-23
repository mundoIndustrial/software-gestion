/**
 * Script: layout.js
 * Gestiona interactividad del layout de operarios
 */

document.addEventListener('DOMContentLoaded', function() {
    setupUserDropdown();
    setupSearch();
});

/**
 * Configurar dropdown de usuario
 */
function setupUserDropdown() {
    const userBtn = document.getElementById('userBtn');
    const userMenu = document.getElementById('userMenu');

    if (!userBtn || !userMenu) return;

    userBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
        if (!userBtn.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });
}

/**
 * Configurar búsqueda de pedidos
 */
function setupSearch() {
    const searchInput = document.getElementById('searchInput');

    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const busqueda = e.target.value.trim();

        if (busqueda.length < 2) {
            return;
        }

        buscarPedidos(busqueda);
    });
}

/**
 * Buscar pedidos
 */
function buscarPedidos(busqueda) {
    fetch('{{ route("operario.buscar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            busqueda: busqueda
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            // Aquí puedes mostrar los resultados en un dropdown o modal
        }
    })
    .catch(error => console.error('Error en búsqueda:', error));
}

/**
 * Agregar estilos dinámicos para dropdown
 */
const style = document.createElement('style');
style.textContent = `
    .user-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        min-width: 250px;
        z-index: 1000;
        margin-top: 0.5rem;
    }

    .user-menu.active {
        display: block;
    }

    .user-dropdown {
        position: relative;
    }
`;
document.head.appendChild(style);
