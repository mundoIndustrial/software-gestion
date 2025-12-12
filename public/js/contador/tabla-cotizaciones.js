/**
 * Gestor de tabla de cotizaciones
 * Maneja acciones, menús y paginación
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeTableActions();
    initializePagination();
});

/**
 * Inicializar acciones de tabla
 */
function initializeTableActions() {
    // Manejar clics en botones de acción
    document.querySelectorAll('.action-view-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const cotizacionId = this.getAttribute('data-cotizacion-id');
            const menu = document.querySelector(`.action-menu[data-cotizacion-id="${cotizacionId}"]`);
            
            // Cerrar otros menús
            document.querySelectorAll('.action-menu.active').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('active');
                }
            });
            
            // Toggle menú actual
            if (menu) {
                menu.classList.toggle('active');
            }
        });
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-view-btn') && !e.target.closest('.action-menu')) {
            document.querySelectorAll('.action-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
    
    // Cerrar menú al hacer clic en un item
    document.querySelectorAll('.action-menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const menu = this.closest('.action-menu');
            if (menu) {
                menu.classList.remove('active');
            }
        });
    });
}

/**
 * Inicializar paginación
 */
function initializePagination() {
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.disabled) {
                const page = this.getAttribute('data-page');
                window.location.href = `?page=${page}`;
            }
        });
    });
}

/**
 * Sincronizar scroll horizontal del header con el contenido
 */
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.table-scroll-container');
    const tableHead = document.querySelector('.table-head');
    
    if (scrollContainer && tableHead) {
        scrollContainer.addEventListener('scroll', function() {
            tableHead.style.transform = 'translateX(' + (-this.scrollLeft) + 'px)';
        });
    }
});
