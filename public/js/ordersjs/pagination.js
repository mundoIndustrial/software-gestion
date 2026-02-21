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
    if (typeof initializeDiaEntregaDropdowns === 'function') {
        initializeDiaEntregaDropdowns();
    }
    if (typeof actualizarDiasTabla === 'function') {
        actualizarDiasTabla();
    }
    // Aplicar colores condicionales a las filas
    if (typeof updateRowConditionalColors === 'function') {
        updateRowConditionalColors();

    }
}

function _restoreTableState(tableBody, page, btn) {
    tableBody.style.opacity = '1';
    tableBody.style.pointerEvents = 'auto';
    paginationState.isLoading = false;
    btn.disabled = false;
    
    if (document.querySelector('.table-container')) {
        document.querySelector('.table-container').scrollIntoView({ 
            behavior: 'auto', 
            block: 'start' 
        });
    }

}

function _handlePaginationResponse(html, url, page, tableBody, paginationControls, btn) {

    
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    
    _updateTableContent(tableBody, paginationControls, doc, page);
    window.history.pushState({}, '', url.toString());
    
    setTimeout(() => {

        _initializeControls();
        _restoreTableState(tableBody, page, btn);
    }, 100);
}

function _handlePaginationError(error, tableBody, btn, timeoutId) {
    clearTimeout(timeoutId);

    tableBody.style.opacity = '1';
    tableBody.style.pointerEvents = 'auto';
    btn.disabled = false;
    paginationState.isLoading = false;
    
    // No mostrar alerta si fue un AbortSignal (timeout)
    if (error.name === 'AbortError') {

        return;
    }
    
    const errorMessage = error.message || 'Error desconocido al cargar la página';
    alert(`Error al cargar la página: ${errorMessage}`);
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

// Variable para mantener estado de carga
let paginationState = {
    isLoading: false,
    lastPageLoad: 0,
    MIN_PAGE_LOAD_DELAY: 500
};

/**
 * Inicializar listeners de paginación
 * Usa delegación de eventos en document para que funcione con contenido dinámico
 */
function initializePaginationListeners() {
    document.addEventListener('click', function(e) {
        // Usar delegación de eventos en document (no en contenedor específico)
        const btn = e.target.closest('#paginationControls .pagination-btn');
        
        if (!btn) {
            return; // No es un botón de paginación
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        if (btn.disabled || paginationState.isLoading) {

            return;
        }
        
        const now = Date.now();
        if (now - paginationState.lastPageLoad < paginationState.MIN_PAGE_LOAD_DELAY) {

            return;
        }
        paginationState.lastPageLoad = now;
        
        const page = btn.dataset.page;
        if (!page) {

            return;
        }
        
        paginationState.isLoading = true;
        btn.disabled = true;
        

        
        const tableBody = document.getElementById('tablaOrdenesBody');
        if (!tableBody) {

            paginationState.isLoading = false;
            btn.disabled = false;
            return;
        }
        
        tableBody.style.transition = 'opacity 0.1s';
        tableBody.style.opacity = '0.3';
        tableBody.style.pointerEvents = 'none';
        
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        
        const paginationControls = document.getElementById('paginationControls');
        _loadPaginationPage(page, url, tableBody, paginationControls, btn);
    }, false); // Usar captura de eventos para mayor compatibilidad
}

document.addEventListener('DOMContentLoaded', function() {

    initializePaginationListeners();
});

