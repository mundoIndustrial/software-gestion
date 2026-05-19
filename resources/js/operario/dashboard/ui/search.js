export function initDashboardSearch() {
    const DASHBOARD_DEBUG = false;
    window.__initDashboardSearch = function () {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearFilterBtn');

        const ordenesList = document.getElementById('ordenesList');
        const ordenCards = ordenesList ? ordenesList.querySelectorAll('.orden-card-simple') : [];

        if (DASHBOARD_DEBUG) {
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
        }

        if (!searchInput) {
            return;
        }

        if (window.__dashboardClearHandler && clearBtn) {
            clearBtn.removeEventListener('click', window.__dashboardClearHandler);
        }
        if (window.__dashboardSearchHandler) {
            searchInput.removeEventListener('input', window.__dashboardSearchHandler);
        }

        const obtenerFiltroPrincipalActivo = () => {
            const filtroActivo = window.__dashboardFiltroPrincipalActivo
                || document.querySelector('.badge-filtro[data-filtro].badge-filtro-active')?.dataset?.filtro;
            
            if (filtroActivo) return filtroActivo;
            
            // Si no hay filtro activo pero existen botones con data-filtro, el default es costura
            if (document.querySelector('.badge-filtro[data-filtro]')) {
                return 'costura';
            }
            
            // Si no hay botones de filtro por tipo, mostrar todos
            return 'todos';
        };

        const obtenerFiltroEncargadoActivo = () => {
            return window.__vistaCosturaEncargadoFiltro || 'todos';
        };

        const coincideConFiltroPrincipal = (card, filtroPrincipal) => {
            if (filtroPrincipal === 'todos') {
                return true;
            }

            const tipos = String(card.dataset.tipoRecibo || '')
                .split(',')
                .map((valor) => valor.trim().toLowerCase())
                .filter(Boolean);

            return tipos.includes(String(filtroPrincipal || '').toLowerCase());
        };

        const coincideConFiltroEncargado = (card, filtroPrincipal) => {
            if (filtroPrincipal !== 'costura' && filtroPrincipal !== 'reflectivo') {
                return true;
            }

            const filtroEncargado = obtenerFiltroEncargadoActivo();
            if (filtroEncargado !== 'sin-encargado') {
                return true;
            }

            const atributoSinEncargado =
                filtroPrincipal === 'reflectivo' ? 'sinEncargadoReflectivo' : 'sinEncargadoCostura';

            return String(card.dataset[atributoSinEncargado] || '0') === '1';
        };

        window.__applyDashboardSearchFilter = function () {
            const busqueda = String(searchInput.value || '').toLowerCase().trim();
            const filtroPrincipal = obtenerFiltroPrincipalActivo();
            const enModoControlCalidad = window.__enModoControlCalidad === true;
            const ordenesListActual = document.getElementById('ordenesList');
            const cardsActuales = ordenesListActual ? ordenesListActual.querySelectorAll('.orden-card-simple') : [];

            cardsActuales.forEach((card) => {
                const coincideTipo = enModoControlCalidad ? true : coincideConFiltroPrincipal(card, filtroPrincipal);
                const coincideEncargado = enModoControlCalidad ? true : coincideConFiltroEncargado(card, filtroPrincipal);
                const numeroRecibo = String(card.dataset.numeroRecibo || '').toLowerCase();
                const cliente = String(card.dataset.cliente || '').toLowerCase();
                const nombrePrenda = String(card.dataset.prenda || '').toLowerCase();
                const numeroPedido = String(card.dataset.numero || '').toLowerCase();

                const coincideTexto =
                    busqueda === '' ||
                    numeroRecibo.includes(busqueda) ||
                    numeroPedido.includes(busqueda) ||
                    cliente.includes(busqueda) ||
                    nombrePrenda.includes(busqueda);

                const mostrar = coincideTipo && coincideEncargado && coincideTexto;
                card.style.display = mostrar ? '' : 'none';
            });

            // Actualizar paginación después de filtrar por búsqueda
            window.__resetDashboardPagination?.();
        };

        window.__dashboardClearHandler = function () {
            searchInput.value = '';
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
            window.__applyDashboardSearchFilter?.();
        };

        if (clearBtn) {
            clearBtn.addEventListener('click', window.__dashboardClearHandler);
        }

        window.__dashboardSearchHandler = function (e) {
            const busqueda = e.target.value.toLowerCase().trim();
            window.__dashboardSearchQuery = busqueda;

            if (clearBtn) {
                clearBtn.style.display = busqueda ? 'flex' : 'none';
            }
            window.__applyDashboardSearchFilter?.();
        };

        searchInput.addEventListener('input', window.__dashboardSearchHandler);
        window.__applyDashboardSearchFilter?.();
    };

    if (typeof window.__initDashboardSearch === 'function') {
        window.__initDashboardSearch();
    }
}
