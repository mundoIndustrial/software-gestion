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

    // Actualizar placeholder
    searchInput.placeholder = 'Buscar por # Recibo o Cliente...';

    searchInput.addEventListener('input', function(e) {
        const busqueda = e.target.value.trim().toLowerCase();

        // Obtener todas las tarjetas de orden
        const ordenCards = document.querySelectorAll('.orden-card-simple');

        ordenCards.forEach(card => {
            // Obtener número de RECIBO desde .orden-right (lado derecho)
            // Buscar el texto que está después de "RECIBO"
            const reciboElem = card.querySelector('.orden-right .orden-fecha span:not(.orden-fecha-label)');
            const numeroRecibo = reciboElem ? reciboElem.textContent?.toLowerCase().trim() : '';
            
            // Obtener nombre del cliente
            const clienteName = card.querySelector('.cliente-name')?.textContent?.toLowerCase().trim() || '';

            console.log('🔍 Filtro:', {
                busqueda: busqueda,
                numeroRecibo: numeroRecibo,
                clienteName: clienteName,
                coincide: !busqueda || numeroRecibo.includes(busqueda) || clienteName.includes(busqueda)
            });

            // Mostrar si coincide con recibo o cliente (búsqueda vacía muestra todos)
            const coincide = !busqueda || 
                             numeroRecibo.includes(busqueda) || 
                             clienteName.includes(busqueda);

            card.style.display = coincide ? '' : 'none';
        });
    });
}

/**
 * Buscar pedidos (función deprecated - ahora el filtro es client-side)
 */
function buscarPedidos(busqueda) {
    // Ya no se usa - el filtro es client-side más eficiente
    console.log('[BUSCAR] Búsqueda client-side:', busqueda);
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
