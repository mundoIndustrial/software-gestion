export function initDashboardSearch() {
    const DASHBOARD_DEBUG = false;
    window.__initDashboardSearch = function () {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearFilterBtn');
        const clearTextBtn = document.getElementById('clearSearchTextBtn');
        const searchLoadingState = document.getElementById('searchLoadingState');
        const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
        const esVistaCostura = userRole === 'vista-costura';
        const usaBusquedaServidor = esVistaCostura || userRole === 'lider-reflectivo';
        const debounceMs = 350;
        let searchTimer = null;
        let searchAbortController = null;
        let searchRequestSeq = 0;

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

        const obtenerFiltroActivoParaBusqueda = () => {
            return window.__dashboardFiltroPrincipalActivo
                || document.querySelector('.badge-filtro[data-filtro].badge-filtro-active')?.dataset?.filtro
                || 'costura';
        };

        const getBaseUrlWithQuery = (value) => {
            const url = new URL(window.location.href);
            const filtroActivo = obtenerFiltroActivoParaBusqueda();

            if (filtroActivo) {
                url.searchParams.set('filtro', filtroActivo);
            }

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
            searchRequestSeq += 1;
            const currentRequestSeq = searchRequestSeq;

            if (searchAbortController) {
                searchAbortController.abort();
            }
            searchAbortController = new AbortController();

            mostrarCargandoBusqueda(true);

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: searchAbortController.signal,
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
                    if (currentRequestSeq !== searchRequestSeq) {
                        return;
                    }
                    ordenesSectionActual.replaceWith(nuevaOrdenesSection);
                } else {
                    throw new Error('No se pudo actualizar el listado');
                }

                window.history.replaceState({}, '', url.toString());
                window.__initDashboardSearch?.();
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
                console.error('[Dashboard Search] Error actualizando resultados', error);
                // No recargar la página, solo mostrar que no hay resultados
                // Preservar el valor del input de búsqueda
                mostrarCargandoBusqueda(false);
                return;
            } finally {
                if (currentRequestSeq === searchRequestSeq) {
                    mostrarCargandoBusqueda(false);
                }
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
            if (filtroPrincipal !== 'costura' && filtroPrincipal !== 'reflectivo' && filtroPrincipal !== 'bodega') {
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
                const tokensNumericos = texto
                    .replace(/[^\d]+/g, ' ')
                    .trim()
                    .split(/\s+/)
                    .filter(Boolean);

                return tokensNumericos.some((token) => token.includes(busqueda));
            }

            return texto.includes(busqueda);
        };

        window.__applyDashboardSearchFilter = function () {
            if (usaBusquedaServidor) {
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
                const tiposCard = String(card.dataset.tipoRecibo || '')
                    .split(',')
                    .map((valor) => valor.trim().toLowerCase())
                    .filter(Boolean);
                const tieneReflectivo = tiposCard.includes('reflectivo');
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
                const coincideFiltrosNormales = coincideTipo && coincideEncargado;
                const buscarEnReflectivo = filtroPrincipal === 'reflectivo' && busqueda !== '';
                const mostrarPorBusquedaReflectivo = buscarEnReflectivo && tieneReflectivo && coincideTexto;
                const forzarPorBusqueda = mostrarPorBusquedaReflectivo && !coincideFiltrosNormales;
                const mostrar = mostrarPorBusquedaReflectivo || (coincideTexto && coincideFiltrosNormales);
                card.style.display = mostrar ? '' : 'none';

                const areaHint = card.querySelector('.dashboard-search-area-hint');
                if (areaHint) {
                    areaHint.style.display = forzarPorBusqueda ? 'inline-flex' : 'none';
                }
            });

            // Actualizar paginación después de filtrar por búsqueda
            window.__resetDashboardPagination?.();
        };

        window.__dashboardClearHandler = function () {
            searchInput.value = '';
            window.__dashboardSearchQuery = '';
            if (clearBtn) {
                clearBtn.style.display = 'none';
            }
            if (clearTextBtn) {
                clearTextBtn.style.display = 'none';
            }
            mostrarCargandoBusqueda(false);
            if (usaBusquedaServidor) {
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

            if (usaBusquedaServidor) {
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

        if (usaBusquedaServidor) {
            const busquedaQuery = new URL(window.location.href).searchParams.get('q') || '';
            searchInput.value = busquedaQuery;
            window.__dashboardSearchQuery = busquedaQuery.toLowerCase().trim();
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
        } else {
            window.__dashboardSearchQuery = String(searchInput.value || '').toLowerCase().trim();
        }

        window.__applyDashboardSearchFilter?.();
    };

    if (typeof window.__initDashboardSearch === 'function') {
        window.__initDashboardSearch();
    }
}
