/**
 * AJAX Pagination Script for Orders Table
 * Handles dynamic pagination without full page reload
 */

document.addEventListener('DOMContentLoaded', function() {
    const paginationControls = document.getElementById('paginationControls');
    let isLoading = false;
    let lastPageLoad = 0;
    const MIN_PAGE_LOAD_DELAY = 500; // Mínimo 500ms entre cambios de página
    
    if (paginationControls) {
        paginationControls.addEventListener('click', function(e) {
            const btn = e.target.closest('.pagination-btn');
            
            if (!btn || btn.disabled || isLoading) {
                console.log('⏭️ Botón deshabilitado o ya cargando, ignorando click');
                return;
            }
            
            // ANTI-SPAM: Evitar múltiples clicks rápidos
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
            
            // Indicador de carga rápido
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
            
            // Construir URL con parámetros actuales
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            
            // Timeout de 10 segundos para la request
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);
            
            // Hacer petición AJAX
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
            .then(html => {
                console.log(`✅ HTML recibido para página ${page}`);
                
                // Parsear HTML de forma más eficiente
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar contenido de forma rápida
                const newTableBody = doc.getElementById('tablaOrdenesBody');
                if (newTableBody) {
                    // CRÍTICO: Limpiar todos los event listeners antes de reemplazar el HTML
                    const oldDropdowns = tableBody.querySelectorAll('.dia-entrega-dropdown');
                    oldDropdowns.forEach(dropdown => {
                        // Remover el handler guardado
                        if (dropdown._diaEntregaHandler) {
                            dropdown.removeEventListener('change', dropdown._diaEntregaHandler);
                            delete dropdown._diaEntregaHandler;
                        }
                        dropdown.dataset.initialized = 'false';
                    });
                    
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
                
                // Actualizar URL
                window.history.pushState({}, '', url.toString());
                
                // OPTIMIZACIÓN: Usar setTimeout corto para no bloquear
                setTimeout(() => {
                    console.log('🔄 Inicializando dropdowns...');
                    
                    // RE-INICIALIZAR DROPDOWNS después de actualizar el HTML
                    if (typeof initializeStatusDropdowns === 'function') {
                        initializeStatusDropdowns();
                    }
                    if (typeof initializeAreaDropdowns === 'function') {
                        initializeAreaDropdowns();
                    }
                    if (typeof initializeDiaEntregaDropdowns === 'function') {
                        initializeDiaEntregaDropdowns();
                    }
                    
                    console.log('✅ Dropdowns re-inicializados después de cambiar de página');
                    
                    // Restaurar inmediatamente
                    tableBody.style.opacity = '1';
                    tableBody.style.pointerEvents = 'auto';
                    isLoading = false;
                    btn.disabled = false;
                    
                    // Scroll instantáneo
                    if (document.querySelector('.table-container')) {
                        document.querySelector('.table-container').scrollIntoView({ 
                            behavior: 'auto', 
                            block: 'start' 
                        });
                    }
                    
                    console.log(`✅ Página ${page} cargada completamente`);
                }, 100);
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.error('❌ Error al cargar página:', error);
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
                isLoading = false;
                btn.disabled = false;
                
                // Mostrar error al usuario
                alert(`Error al cargar la página: ${error.message}`);
            });
        });
    }
});
