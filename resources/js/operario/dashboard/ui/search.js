export function initDashboardSearch() {
    const DASHBOARD_DEBUG = false;
    window.__initDashboardSearch = function () {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearFilterBtn');
        const clearTextBtn = document.getElementById('clearSearchTextBtn');
        const searchLoadingState = document.getElementById('searchLoadingState');
        const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
        const esVistaCostura = userRole === 'vista-costura';
        const debounceMs = 350;
        let searchTimer = null;

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
        if (window.__dashboardClearHandler && clearTextBtn) {
            clearTextBtn.removeEventListener('click', window.__dashboardClearHandler);
        }
        if (window.__dashboardSearchHandler) {
            searchInput.removeEventListener('input', window.__dashboardSearchHandler);
        }

        const getBaseUrlWithQuery = (value) => {
            const url = new URL(window.location.href);
            if (value && value.trim() !== '') {
                url.searchParams.set('q', value.trim());
            } else {
                url.searchParams.delete('q');
            }
            url.searchParams.delete('page');
            Array.from(url.searchParams.keys()).forEach((key) => {
                if (key.startsWith('page_vc_')) {
                    url.searchParams.delete(key);
                }
            });
            return url;
        };

        const mostrarCargandoBusqueda = (mostrar) => {
            if (!searchLoadingState) {
                return;
            }

            searchLoadingState.style.display = mostrar ? 'flex' : 'none';
            const dashboard = document.querySelector('.operario-dashboard');
            if (dashboard) {
                dashboard.classList.toggle('is-searching', mostrar);
            }
        };

        const obtenerUrlBusquedaVistaCostura = (value) => {
            return getBaseUrlWithQuery(value);
        };

        const aplicarRespuestaBusquedaVistaCostura = async (url) => {
            mostrarCargandoBusqueda(true);

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nuevaOrdenesSection = doc.querySelector('.operario-dashboard .ordenes-section');
                const ordenesSectionActual = document.querySelector('.operario-dashboard .ordenes-section');

                if (nuevaOrdenesSection && ordenesSectionActual) {
                    ordenesSectionActual.replaceWith(nuevaOrdenesSection);
                } else {
                    throw new Error('No se pudo actualizar el listado');
                }

                window.history.replaceState({}, '', url.toString());
                window.__initDashboardSearch?.();
            } catch (error) {
                console.error('[Dashboard Search] Error actualizando resultados', error);
                window.location.href = url.toString();
                return;
            } finally {
                mostrarCargandoBusqueda(false);
            }
        };

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

        const coincideBusquedaTexto = (valor, busqueda, esNumerica) => {
            const texto = String(valor || '').toLowerCase().trim();
            if (texto === '') {
                return false;
            }

            if (esNumerica) {
                return texto === busqueda;
            }

            return texto.includes(busqueda);
        };

        window.__applyDashboardSearchFilter = function () {
            if (esVistaCostura) {
                return;
            }

            const busqueda = String(searchInput.value || '').toLowerCase().trim();
            const esNumerica = /^\d+$/.test(busqueda);
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
                const textoVisible = String(card.dataset.searchText || card.innerText || '')
                    .toLowerCase()
                    .replace(/\s+/g, ' ')
                    .trim();

                const coincideTexto =
                    busqueda === '' ||
                    coincideBusquedaTexto(numeroRecibo, busqueda, esNumerica) ||
                    coincideBusquedaTexto(numeroPedido, busqueda, esNumerica) ||
                    coincideBusquedaTexto(cliente, busqueda, esNumerica) ||
                    coincideBusquedaTexto(nombrePrenda, busqueda, esNumerica) ||
                    coincideBusquedaTexto(textoVisible, busqueda, esNumerica);

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
            if (clearTextBtn) {
                clearTextBtn.style.display = 'none';
            }
            mostrarCargandoBusqueda(false);
            if (esVistaCostura) {
                const url = obtenerUrlBusquedaVistaCostura('');
                aplicarRespuestaBusquedaVistaCostura(url);
                return;
            }
            window.__applyDashboardSearchFilter?.();
        };

        if (clearBtn) {
            clearBtn.addEventListener('click', window.__dashboardClearHandler);
        }
        if (clearTextBtn) {
            clearTextBtn.addEventListener('click', window.__dashboardClearHandler);
        }

        window.__dashboardSearchHandler = function (e) {
            const busqueda = e.target.value.toLowerCase().trim();
            window.__dashboardSearchQuery = busqueda;

            if (clearBtn) {
                clearBtn.style.display = busqueda ? 'flex' : 'none';
            }
            if (clearTextBtn) {
                clearTextBtn.style.display = busqueda ? 'inline-flex' : 'none';
            }

            if (esVistaCostura) {
                mostrarCargandoBusqueda(true);
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    const url = obtenerUrlBusquedaVistaCostura(busqueda);
                    aplicarRespuestaBusquedaVistaCostura(url);
                }, debounceMs);
                return;
            }

            window.__applyDashboardSearchFilter?.();
        };

        searchInput.addEventListener('input', window.__dashboardSearchHandler);

        if (esVistaCostura) {
            const busquedaQuery = new URL(window.location.href).searchParams.get('q') || '';
            searchInput.value = busquedaQuery;
            if (clearBtn) {
                clearBtn.style.display = busquedaQuery ? 'flex' : 'none';
            }
            if (clearTextBtn) {
                clearTextBtn.style.display = busquedaQuery ? 'inline-flex' : 'none';
            }
            const dashboard = document.querySelector('.operario-dashboard');
            if (dashboard) {
                dashboard.classList.remove('is-searching');
            }
            mostrarCargandoBusqueda(false);
        }

        window.__applyDashboardSearchFilter?.();
    };

    if (typeof window.__initDashboardSearch === 'function') {
        window.__initDashboardSearch();
    }
}
