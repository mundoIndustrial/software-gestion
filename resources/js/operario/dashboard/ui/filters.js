export function initReciboFilters() {
    function aplicarTemaDashboard(filtroPrincipal) {
        const body = document.body;
        const titleText = document.getElementById('dashboardPageTitleText');
        const titleIcon = document.getElementById('dashboardPageTitleIcon');
        const theme = filtroPrincipal === 'reflectivo' ? 'reflectivo' : 'costura';

        if (body) {
            body.setAttribute('data-dashboard-theme', theme);
        }

        if (titleText) {
            titleText.textContent = theme === 'reflectivo' ? 'RECIBOS DE REFLECTIVO' : 'RECIBOS DE COSTURA';
        }

        if (titleIcon) {
            titleIcon.textContent = theme === 'reflectivo' ? 'auto_awesome' : 'checkroom';
        }
    }

    function obtenerFiltroPrincipalActivo() {
        const btnActivo = document.querySelector('.badge-filtro[data-filtro].badge-filtro-active');
        return btnActivo?.dataset?.filtro || 'costura';
    }

    function obtenerFiltroEncargadoActivo() {
        return window.__vistaCosturaEncargadoFiltro || 'todos';
    }

    function actualizarBadgeSinEncargado(filtroPrincipal) {
        const badgeCount = document.getElementById('badgeSinEncargadoCount');
        if (!badgeCount) return;

        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            badgeCount.textContent = '0';
            return;
        }

        const cards = Array.from(ordenesList.querySelectorAll('.orden-card-simple'));
        const atributo = filtroPrincipal === 'reflectivo' ? 'sinEncargadoReflectivo' : 'sinEncargadoCostura';

        const totalSinEncargado = cards.filter((card) => {
            const tipos = String(card.dataset.tipoRecibo || '')
                .split(',')
                .map((valor) => valor.trim())
                .filter(Boolean);

            if (!tipos.includes(filtroPrincipal)) {
                return false;
            }

            return String(card.dataset[atributo] || '0') === '1';
        }).length;

        badgeCount.textContent = String(totalSinEncargado);
    }

    function aplicarFiltrosDashboard(filtroPrincipal) {
        console.log(' [FILTRO] Iniciando filtro:', filtroPrincipal);

        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            console.error(' ordenesList no encontrado');
            return;
        }

        const filtroEncargado = obtenerFiltroEncargadoActivo();
        const ordenCards = ordenesList.querySelectorAll('.orden-card-simple');
        let mostradas = 0;
        let ocultadas = 0;

        actualizarBadgeSinEncargado(filtroPrincipal);

        ordenCards.forEach((card, index) => {
            const tipoRecibo = card.dataset.tipoRecibo;
            const numeroPedido = card.dataset.numero;
            const nombrePrenda = card.dataset.prenda;

            console.log(
                `Tarjeta ${index + 1}: Pedido=${numeroPedido}, Prenda=${nombrePrenda}, data-tipo-recibo="${tipoRecibo}"`
            );

            if (filtroPrincipal === 'todos') {
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    elemento.style.display = '';
                });
                mostradas++;
                return;
            }

            const tipos = tipoRecibo ? tipoRecibo.split(',').map((t) => t.trim()) : [];
            const coincideFiltroPrincipal = tipos.includes(filtroPrincipal);
            const atributoSinEncargado =
                filtroPrincipal === 'reflectivo' ? 'sinEncargadoReflectivo' : 'sinEncargadoCostura';
            const coincideFiltroEncargado =
                filtroEncargado !== 'sin-encargado' || String(card.dataset[atributoSinEncargado] || '0') === '1';

            if (coincideFiltroPrincipal && coincideFiltroEncargado) {
                console.log(`  Mostrando (contiene "${filtroPrincipal}" en [${tipos.join(', ')}])`);
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    const filtrosElemento = (elemento.dataset.visibleFiltro || '')
                        .split(',')
                        .map((valor) => valor.trim())
                        .filter(Boolean);

                    elemento.style.display = filtrosElemento.includes(filtroPrincipal) ? '' : 'none';
                });
                mostradas++;
            } else {
                console.log(`  Ocultando (no coincide con filtros activos)`);
                card.style.display = 'none';
                ocultadas++;
            }
        });

        console.log(` [FILTRO] Filtro completado: ${mostradas} mostradas, ${ocultadas} ocultadas`);
    }

    window.filtrarPrendasPorRecibo = function (filtro) {
        window.__dashboardFiltroPrincipalActivo = filtro;
        document.querySelectorAll('.badge-filtro[data-filtro]').forEach((btn) => {
            btn.classList.remove('badge-filtro-active');
        });
        const btnFiltro = document.querySelector(`[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('badge-filtro-active');
        }

        aplicarTemaDashboard(filtro);
        aplicarFiltrosDashboard(filtro);
        window.__applyDashboardSearchFilter?.();
    };

    window.filtrarVistaCosturaEncargados = function (modo = 'todos') {
        window.__vistaCosturaEncargadoFiltro = modo;

        document.querySelectorAll('.badge-filtro[data-encargado-filtro]').forEach((btn) => {
            btn.classList.toggle('badge-filtro-active', btn.dataset.encargadoFiltro === modo);
        });

        aplicarFiltrosDashboard(obtenerFiltroPrincipalActivo());
        window.__applyDashboardSearchFilter?.();
    };

    if (document.getElementById('vistaCosturaEncargadoFilters')) {
        window.__vistaCosturaEncargadoFiltro = window.__vistaCosturaEncargadoFiltro || 'todos';
        window.filtrarVistaCosturaEncargados(window.__vistaCosturaEncargadoFiltro);
    } else {
        actualizarBadgeSinEncargado(obtenerFiltroPrincipalActivo());
    }

    window.__dashboardFiltroPrincipalActivo = obtenerFiltroPrincipalActivo();
    aplicarTemaDashboard(obtenerFiltroPrincipalActivo());
}
