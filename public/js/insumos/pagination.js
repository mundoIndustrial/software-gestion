/**
 * Paginación para Materiales del Rol Insumos
 */
document.addEventListener('DOMContentLoaded', function() {
    const paginationControls = document.getElementById('paginationControls');
    
    if (paginationControls) {
        paginationControls.addEventListener('click', function(e) {
            const btn = e.target.closest('.pagination-btn');
            
            if (!btn || btn.disabled) return;
            
            const page = btn.dataset.page;
            if (!page) return;
            
            // Construir URL con parámetros
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            
            // Ir a la página (preservando parámetro de búsqueda)
            window.location.href = url.toString();
        });
    }
});
