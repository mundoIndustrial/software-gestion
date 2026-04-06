/**
 * Paginación para Materiales del Rol Insumos
 * Usa delegación de eventos para funcionar incluso después de AJAX updates
 * Usa AJAX para cambiar de página sin recargar
 */

function initInsumosPagination() {
    // Usar delegación de eventos en el document para que funcione con AJAX updates
    // Remover listeners previos si existen para evitar duplicados
    document.removeEventListener('click', handlePaginationClick);
    document.addEventListener('click', handlePaginationClick);
    
    console.log('[Pagination] Inicializado con delegación de eventos');
}

function handlePaginationClick(e) {
    const btn = e.target.closest('.pagination-btn');
    
    if (!btn || btn.disabled) return;
    
    e.preventDefault();
    
    const page = btn.dataset.page;
    if (!page) return;
    
    // Construir URL con parámetros, preservando filtros y búsqueda
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    
    console.log('[Pagination] Navegando a página:', page);
    console.log('[Pagination] URL:', url.toString());
    
    // Usar AJAX para cambiar de página sin recargar
    goToPaginatedPageAjax(url.toString());
}

function goToPaginatedPageAjax(url) {
    try {
        console.log('[Pagination] Cargando página via AJAX:', url);
        
        // Enviar petición AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.text();
        })
        .then(html => {
            // Usar la misma función que los filtros para actualizar la tabla
            if (typeof updateTableFromHtml === 'function') {
                updateTableFromHtml(html);
            } else {
                // Fallback: hacer full page reload
                window.location.href = url;
            }
            
            // Actualizar URL sin recargar
            window.history.pushState({ page: url }, '', url);
            
            console.log('[Pagination] Página cargada exitosamente');
        })
        .catch(error => {
            console.error('[Pagination] Error:', error);
            // Fallback: full page reload
            window.location.href = url;
        });
        
    } catch (error) {
        console.error('[Pagination] Error inesperado:', error);
        // Fallback: full page reload
        window.location.href = url;
    }
}

// Inicializar cuando se carga el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInsumosPagination);
} else {
    initInsumosPagination();
}

// Reinicializar paginación cuando se actualiza la tabla via AJAX (filtros)
document.addEventListener('insumosTableUpdated', function(e) {
    console.log('[Pagination] Reinicializando después de actualización AJAX');
    // Los listeners ya están en el document, no necesita hacer nada
});
