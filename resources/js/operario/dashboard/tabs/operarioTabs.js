export function initOperarioTabs() {
    const tabs = document.querySelectorAll('.filtros-badges-principales .badge-filtro');
    
    tabs.forEach(tab => {
        // Quitamos el onclick que viene de Blade si existe
        tab.removeAttribute('onclick');
        
        tab.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const isPendientes = tab.textContent.toLowerCase().includes('pendientes');
            const tabName = isPendientes ? 'pendientes' : 'completados';
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            url.searchParams.delete('page'); // Reset page when changing tab
            
            // Actualizar UI de los botones inmediatamente para feedback visual
            tabs.forEach(t => t.classList.remove('badge-filtro-active'));
            tab.classList.add('badge-filtro-active');
            
            try {
                const resp = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!resp.ok) throw new Error('Error al cargar la pestaña');
                
                const html = await resp.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const nuevoContenido = doc.getElementById('ordenesList');
                const actualContenido = document.getElementById('ordenesList');
                
                if (nuevoContenido && actualContenido) {
                    actualContenido.innerHTML = nuevoContenido.innerHTML;
                    
                    // Actualizar URL sin recargar
                    window.history.pushState({}, '', url.toString());
                    
                    // Re-inicializar módulos necesarios
                    if (window.__initDashboardSearch) window.__initDashboardSearch();
                    if (window.reaplicarFiltrosDashboard) window.reaplicarFiltrosDashboard();
                    
                    // NOTA: __initDashboardSearch ya llama a resetPagination, 
                    // pero por si acaso forzamos actualización
                    if (window.__resetDashboardPagination) window.__resetDashboardPagination();
                }
            } catch (err) {
                console.error(err);
                // Fallback a recarga normal si algo falla
                window.location.href = url.toString();
            }
        });
    });
}
