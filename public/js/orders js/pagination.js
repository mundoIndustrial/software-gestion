/**
 * AJAX Pagination Script for Orders Table
 * Handles dynamic pagination without full page reload
 */

// Helper functions to reduce nesting depth
function _cleanupOldDropdowns(tableBody) {
    const oldDropdowns = tableBody.querySelectorAll('.dia-entrega-dropdown');
    for (const dropdown of oldDropdowns) {
        if (dropdown._diaEntregaHandler) {
            dropdown.removeEventListener('change', dropdown._diaEntregaHandler);
            delete dropdown._diaEntregaHandler;
        }
        dropdown.dataset.initialized = 'false';
    }
}

function _updateTableContent(tableBody, paginationControls, doc, page) {
    const newTableBody = doc.getElementById('tablaOrdenesBody');
    if (newTableBody) {
        _cleanupOldDropdowns(tableBody);
        tableBody.innerHTML = newTableBody.innerHTML;
        console.log(`✅ Tabla actualizada con ${newTableBody.querySelectorAll('tr').length} filas`);
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
}

function _initializeControls() {
    if (typeof initializeStatusDropdowns === 'function') {
        initializeStatusDropdowns();
    }
    if (typeof initializeAreaDropdowns === 'function') {
        initializeAreaDropdowns();
    }
    if (typeof initializeDiaEntregaDropdowns === 'function') {
        initializeDiaEntregaDropdowns();
    }
    if (typeof actualizarDiasTabla === 'function') {
        actualizarDiasTabla();
    }
}

function _restoreTableState(tableBody, page, isLoading, btn) {
    tableBody.style.opacity = '1';
    tableBody.style.pointerEvents = 'auto';
    isLoading = false;
    btn.disabled = false;
    
    if (document.querySelector('.table-container')) {
        document.querySelector('.table-container').scrollIntoView({ 
            behavior: 'auto', 
            block: 'start' 
        });
    }
    console.log(`✅ Página ${page} cargada completamente`);
}

function _handlePaginationResponse(html, url, page, tableBody, paginationControls, btn) {
    console.log(`✅ HTML recibido para página ${page}`);
    
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    
    _updateTableContent(tableBody, paginationControls, doc, page);
    window.history.pushState({}, '', url.toString());
    
    setTimeout(() => {
        console.log('🔄 Inicializando dropdowns y actualizando días...');
        _initializeControls();
        _restoreTableState(tableBody, page, false, btn);
    }, 100);
}

function _handlePaginationError(error, tableBody, btn, timeoutId) {
    clearTimeout(timeoutId);
    console.error('❌ Error al cargar página:', error);
    tableBody.style.opacity = '1';
    tableBody.style.pointerEvents = 'auto';
    btn.disabled = false;
    alert(`Error al cargar la página: ${error.message}`);
}

function _loadPaginationPage(page, url, tableBody, paginationControls, btn) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.text();
    })
    .then(html => _handlePaginationResponse(html, url, page, tableBody, paginationControls, btn))
    .catch(error => _handlePaginationError(error, tableBody, btn, timeoutId));
}

document.addEventListener('DOMContentLoaded', function() {
    const paginationControls = document.getElementById('paginationControls');
    let isLoading = false;
    let lastPageLoad = 0;
    const MIN_PAGE_LOAD_DELAY = 500;
    
    if (paginationControls) {
        paginationControls.addEventListener('click', function(e) {
            const btn = e.target.closest('.pagination-btn');
            
            if (!btn || btn.disabled || isLoading) {
                console.log('⏭️ Botón deshabilitado o ya cargando, ignorando click');
                return;
            }
            
            const now = Date.now();
            if (now - lastPageLoad < MIN_PAGE_LOAD_DELAY) {
                console.log('⏱️ Click demasiado rápido, ignorando');
                return;
            }
            lastPageLoad = now;
            
            const page = btn.dataset.page;
            if (!page) {
                console.log('❌ No se encontró número de página');
                return;
            }
            
            isLoading = true;
            btn.disabled = true;
            
            console.log(`📄 Cargando página ${page}...`);
            
            const tableBody = document.getElementById('tablaOrdenesBody');
            if (!tableBody) {
                console.error('❌ tablaOrdenesBody no encontrado');
                isLoading = false;
                btn.disabled = false;
                return;
            }
            
            tableBody.style.transition = 'opacity 0.1s';
            tableBody.style.opacity = '0.3';
            tableBody.style.pointerEvents = 'none';
            
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            
            _loadPaginationPage(page, url, tableBody, paginationControls, btn);
        });
    }
});

