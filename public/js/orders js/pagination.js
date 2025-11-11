/**
 * AJAX Pagination Script for Orders Table
 * Handles dynamic pagination without full page reload
 */

document.addEventListener('DOMContentLoaded', function() {
    const paginationControls = document.getElementById('paginationControls');
    let isLoading = false;
    
    if (paginationControls) {
        paginationControls.addEventListener('click', function(e) {
            const btn = e.target.closest('.pagination-btn');
            
            if (!btn || btn.disabled || isLoading) return;
            
            const page = btn.dataset.page;
            if (!page) return;
            
            isLoading = true;
            
            // Indicador de carga rápido
            const tableBody = document.getElementById('tablaOrdenesBody');
            tableBody.style.transition = 'opacity 0.1s';
            tableBody.style.opacity = '0.3';
            tableBody.style.pointerEvents = 'none';
            
            // Construir URL con parámetros actuales
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            
            // Hacer petición AJAX
            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Parsear HTML de forma más eficiente
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar contenido de forma rápida
                const newTableBody = doc.getElementById('tablaOrdenesBody');
                if (newTableBody) {
                    // Limpiar marcas de inicialización antes de reemplazar el HTML
                    const oldDropdowns = tableBody.querySelectorAll('.dia-entrega-dropdown[data-initialized="true"]');
                    oldDropdowns.forEach(d => d.dataset.initialized = 'false');
                    
                    tableBody.innerHTML = newTableBody.innerHTML;
                }
                
                const newPaginationControls = doc.getElementById('paginationControls');
                if (newPaginationControls) {
                    paginationControls.innerHTML = newPaginationControls.innerHTML;
                }
                
                const newPaginationInfo = doc.getElementById('paginationInfo');
                const paginationInfo = document.getElementById('paginationInfo');
                if (newPaginationInfo && paginationInfo) {
                    paginationInfo.innerHTML = newPaginationInfo.innerHTML;
                }
                
                // Actualizar URL
                window.history.pushState({}, '', url.toString());
                
                // RE-INICIALIZAR DROPDOWNS después de actualizar el HTML
                if (typeof initializeStatusDropdowns === 'function') {
                    initializeStatusDropdowns();
                }
                if (typeof initializeAreaDropdowns === 'function') {
                    initializeAreaDropdowns();
                }
                if (typeof initializeDiaEntregaDropdowns === 'function') {
                    initializeDiaEntregaDropdowns();
                    console.log('🔄 Dropdowns re-inicializados después de cambiar de página');
                }
                
                // Restaurar inmediatamente
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
                isLoading = false;
                
                // Scroll instantáneo
                document.querySelector('.table-container').scrollIntoView({ 
                    behavior: 'auto', 
                    block: 'start' 
                });
            })
            .catch(error => {
                console.error('Error al cargar página:', error);
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
                isLoading = false;
            });
        });
    }
});
