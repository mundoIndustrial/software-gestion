// Paginación AJAX para Balanceo - Similar a tableros pero más simple
(function() {
    'use strict';
    
    // Evitar inicialización múltiple
    if (window.balanceoPaginationInitialized) {
        console.log('⚠️ Paginación de balanceo ya inicializada');
        return;
    }
    window.balanceoPaginationInitialized = true;
    
    let isLoading = false;
    
    // Event listener con delegación de eventos
    document.addEventListener('click', function(e) {
        // Verificar si el click fue en un botón de paginación de balanceo
        const paginationContainer = e.target.closest('#paginationControls');
        if (!paginationContainer) return;
        
        const btn = e.target.closest('.pagination-btn');
        if (!btn) return;
        
        // Prevenir navegación
        e.preventDefault();
        e.stopPropagation();
        
        if (btn.disabled || isLoading) return;
        
        const page = btn.dataset.page;
        if (!page) return;
        
        console.log(`🎯 Cargando página ${page} de balanceo...`);
        
        isLoading = true;
        const startTime = performance.now();
        
        // Indicador de carga
        const prendasGrid = document.getElementById('prendasGrid');
        if (prendasGrid) {
            prendasGrid.style.transition = 'opacity 0.2s';
            prendasGrid.style.opacity = '0.3';
            prendasGrid.style.pointerEvents = 'none';
        }
        
        // Construir URL
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        
        const fetchStart = performance.now();
        
        // Hacer petición AJAX
        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const fetchEnd = performance.now();
            console.log(`⏱️ Petición completada en ${(fetchEnd - fetchStart).toFixed(2)}ms`);
            return response.json();
        })
        .then(data => {
            if (!data.pagination || !data.cards_html) {
                throw new Error('Respuesta inválida del servidor');
            }
            
            console.log(`🔧 Tiempo del servidor: ${data.debug.server_time_ms}ms`);
            
            // Actualizar grid de prendas
            if (prendasGrid) {
                prendasGrid.innerHTML = data.cards_html;
                console.log(`✅ Grid actualizado con ${data.pagination.per_page} prendas`);
            }
            
            // Actualizar controles de paginación
            const paginationControls = document.getElementById('paginationControls');
            if (data.pagination.links_html && paginationControls) {
                paginationControls.innerHTML = data.pagination.links_html;
            }
            
            // Actualizar info de paginación
            const paginationInfo = document.getElementById('paginationInfo');
            if (paginationInfo) {
                paginationInfo.textContent = `Mostrando ${data.pagination.first_item}-${data.pagination.last_item} de ${data.pagination.total} prendas`;
            }
            
            // Actualizar barra de progreso
            const progressFill = document.getElementById('progressFill');
            if (progressFill) {
                const progressPercent = (data.pagination.current_page / data.pagination.last_page) * 100;
                progressFill.style.width = progressPercent + '%';
            }
            
            // Actualizar URL
            window.history.pushState({}, '', url.toString());
            
            // Restaurar
            if (prendasGrid) {
                prendasGrid.style.opacity = '1';
                prendasGrid.style.pointerEvents = 'auto';
            }
            isLoading = false;
            
            // Scroll suave al inicio del grid
            prendasGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            const endTime = performance.now();
            const totalTime = (endTime - startTime).toFixed(2);
            console.log(`✅ Página ${page} cargada en ${totalTime}ms (${(totalTime/1000).toFixed(2)}s)`);
            
            if (totalTime > 1000) {
                console.warn(`⚠️ Carga lenta: ${totalTime}ms`);
            }
        })
        .catch(error => {
            console.error('❌ Error al cargar página:', error);
            if (prendasGrid) {
                prendasGrid.style.opacity = '1';
                prendasGrid.style.pointerEvents = 'auto';
            }
            isLoading = false;
        });
    });
    
    console.log('✅ Paginación de balanceo inicializada');
})();

