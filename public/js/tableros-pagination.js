// Paginación AJAX simple estilo pedidos
(function() {
    'use strict';
    
    console.log('🚀 Inicializando paginación AJAX para Tableros...');
    
    // Evitar inicialización múltiple
    if (window.tablerosPaginationInitialized) {
        console.log('⚠️ Paginación ya inicializada, omitiendo...');
        return;
    }
    window.tablerosPaginationInitialized = true;
    
    const loadingStates = {
        produccion: false,
        polos: false,
        corte: false
    };
    
    // Usar un solo event listener para todas las secciones con CAPTURA
    document.addEventListener('click', function(e) {
        // Verificar si el click fue en un botón de paginación
        const btn = e.target.closest('.pagination-btn');
        
        if (!btn) return;
        
        // IMPORTANTE: Prevenir navegación INMEDIATAMENTE
        e.preventDefault();
        e.stopPropagation();
        
        // Determinar la sección
        let section = null;
        ['produccion', 'polos', 'corte'].forEach(s => {
            if (btn.closest(`#paginationControls-${s}`)) {
                section = s;
            }
        });
        
        if (!section) return;
        if (btn.disabled || loadingStates[section]) return;
        
        const page = btn.dataset.page;
        if (!page) return;
        
        loadingStates[section] = true;
        
        // Indicador de carga
        const tableBody = document.querySelector(`table[data-section="${section}"] tbody`);
        if (tableBody) {
            tableBody.style.transition = 'opacity 0.1s';
            tableBody.style.opacity = '0.3';
            tableBody.style.pointerEvents = 'none';
        }
        
        // Construir URL con parámetro de sección y preservar filtros
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        url.searchParams.set('section', section);
        
        // Preservar filtros existentes si los hay
        const existingFilters = url.searchParams.get('filters');
        if (existingFilters) {
            url.searchParams.set('filters', existingFilters);
        }
        
        // Hacer petición AJAX con JSON
        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('📦 Respuesta recibida:', data);
            
            // Verificar si hay error
            if (data.error || !data.pagination) {
                throw new Error(data.error || 'Respuesta inválida del servidor');
            }
            
            // Actualizar tabla con el HTML del servidor
            if (data.table_html && tableBody) {
                tableBody.innerHTML = data.table_html;
                console.log('✅ Tabla actualizada');
            }
            
            // Actualizar controles de paginación usando el HTML del servidor
            const paginationControls = document.getElementById(`paginationControls-${section}`);
            if (data.pagination && data.pagination.links_html && paginationControls) {
                paginationControls.innerHTML = data.pagination.links_html;
                // Los listeners se mantendrán activos gracias a la delegación de eventos en document
                console.log(`✅ Controles de paginación actualizados para ${section}`);
            } else {
                console.warn(`⚠️ No se pudieron actualizar controles de paginación:`, {
                    hasPagination: !!data.pagination,
                    hasLinksHtml: data.pagination ? !!data.pagination.links_html : false,
                    hasPaginationControls: !!paginationControls
                });
            }
            
            // Actualizar info de paginación
            const paginationInfo = document.getElementById(`paginationInfo-${section}`);
            if (data.pagination && paginationInfo) {
                paginationInfo.textContent = `Mostrando ${data.pagination.first_item}-${data.pagination.last_item} de ${data.pagination.total} registros`;
            }
            
            // Actualizar barra de progreso
            const progressFill = document.querySelector(`#pagination-${section} .progress-fill`);
            if (data.pagination && progressFill) {
                const progressPercent = (data.pagination.current_page / data.pagination.last_page) * 100;
                progressFill.style.width = progressPercent + '%';
            }
            
            // Actualizar URL
            window.history.pushState({}, '', url.toString());
            
            // Restaurar
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
            loadingStates[section] = false;
            
            // Scroll
            const tableContainer = document.querySelector(`#pagination-${section}`);
            if (tableContainer) {
                const placeholder = tableContainer.closest('.chart-placeholder');
                if (placeholder) {
                    placeholder.scrollIntoView({ behavior: 'auto', block: 'start' });
                }
            }
        })
        .catch(error => {
            console.error('❌ Error en paginación:', error);
            if (tableBody) {
                tableBody.style.opacity = '1';
                tableBody.style.pointerEvents = 'auto';
            }
            loadingStates[section] = false;
        });
    });
})();

