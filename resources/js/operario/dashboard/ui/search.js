export function initDashboardSearch() {
    window.__initDashboardSearch = function () {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearFilterBtn');

        const ordenesList = document.getElementById('ordenesList');
        const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

        console.log('=== TARJETAS CARGADAS EN DASHBOARD ===');
        console.log('Total de tarjetas:', ordenCards.length);
        ordenCards.forEach((card, index) => {
            console.log(`Tarjeta ${index + 1}:`, {
                numero: card.dataset.numero,
                prenda: card.dataset.prenda,
                cliente: card.dataset.cliente,
                'data-tipo-recibo': card.dataset.tipoRecibo,
            });
        });
        console.log('=====================================\n');

        if (!searchInput) {
            return;
        }

        if (window.__dashboardClearHandler && clearBtn) {
            clearBtn.removeEventListener('click', window.__dashboardClearHandler);
        }
        if (window.__dashboardSearchHandler) {
            searchInput.removeEventListener('input', window.__dashboardSearchHandler);
        }

        window.__dashboardClearHandler = function () {
            searchInput.value = '';
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }

            const event = new Event('input', { bubbles: true });
            searchInput.dispatchEvent(event);
        };

        if (clearBtn) {
            clearBtn.addEventListener('click', window.__dashboardClearHandler);
        }

        window.__dashboardSearchHandler = function (e) {
            const busqueda = e.target.value.toLowerCase().trim();

            if (clearBtn) {
                clearBtn.style.display = busqueda ? 'flex' : 'none';
            }

            const ordenesListActual = document.getElementById('ordenesList');
            const cardsActuales = ordenesListActual ? ordenesListActual.querySelectorAll('.orden-card-simple') : [];

            cardsActuales.forEach((card) => {
                const numeroRecibo = String(card.dataset.numeroRecibo || '').toLowerCase();
                const cliente = String(card.dataset.cliente || '').toLowerCase();
                const nombrePrenda = String(card.dataset.prenda || '').toLowerCase();

                const coincide =
                    numeroRecibo.includes(busqueda) ||
                    cliente.includes(busqueda) ||
                    nombrePrenda.includes(busqueda) ||
                    busqueda === '';

                card.style.display = coincide ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', window.__dashboardSearchHandler);
    };

    if (typeof window.__initDashboardSearch === 'function') {
        window.__initDashboardSearch();
    }
}
