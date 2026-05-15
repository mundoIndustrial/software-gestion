export function initOperarioTabs() {
    const tabs = document.querySelectorAll('.filtros-badges-principales .badge-filtro');

    tabs.forEach((tab) => {
        const text = tab.textContent.toLowerCase().replace(/\s+/g, ' ').trim();
        let tabName = null;

        if (text.includes('pendiente bodega')) {
            tabName = 'pendiente-bodega';
        } else if (text.includes('completado bodega')) {
            tabName = 'completado-bodega';
        } else if (text.includes('completados')) {
            tabName = 'completados';
        } else if (text.includes('pendiente pedidos') || text.includes('pendientes')) {
            tabName = 'pendientes';
        }

        // Solo manejamos AJAX para las pestañas del cortador
        if (!tabName) {
            return;
        }

        // Quitamos el onclick que viene de Blade si existe
        tab.removeAttribute('onclick');

        tab.addEventListener('click', async (e) => {
            e.preventDefault();

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            url.searchParams.delete('page'); // Reset page when changing tab

            // Mostrar overlay de carga
            if (typeof window.showLoadingOverlay === 'function') {
                window.showLoadingOverlay();
            }

            // Actualizar UI de los botones inmediatamente para feedback visual
            tabs.forEach((t) => {
                const tText = t.textContent.toLowerCase().replace(/\s+/g, ' ').trim();
                // Limpiar clase activa de todas las pestañas principales
                if (tText.includes('pendiente') || tText.includes('completado')) {
                    t.classList.remove('badge-filtro-active');
                }
            });
            tab.classList.add('badge-filtro-active');

            try {
                const resp = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
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
                    if (tabName !== 'completados' && tabName !== 'completado-bodega' && window.reaplicarFiltrosDashboard) {
                        window.reaplicarFiltrosDashboard();
                    }

                    if (window.__resetDashboardPagination) window.__resetDashboardPagination();
                }
            } catch (err) {
                console.error(err);
                // Fallback a recarga normal si algo falla
                window.location.href = url.toString();
            } finally {
                // Ocultar overlay de carga
                if (typeof window.hideLoadingOverlay === 'function') {
                    window.hideLoadingOverlay({ minMs: 300 });
                }
            }
        });
    });
}
