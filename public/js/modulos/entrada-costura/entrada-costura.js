document.addEventListener('DOMContentLoaded', function () {
    initEntradaCosturaSidebar();
    initEntradaCosturaRecibos();
});

function initEntradaCosturaSidebar() {
    document.body.classList.remove('talleres-sidebar-collapsed');

    const talleresGroup = document.getElementById('navTalleresGroup');
    const talleresSubmenu = document.getElementById('talleresSubmenu');
    const prestamosGroup = document.getElementById('navPrestamosGroup');
    const prestamosSubmenu = document.getElementById('prestamosSubmenu');

    const toggleGroup = (button, submenu, storageKey, expandedByDefault = true) => {
        if (!button || !submenu) {
            return;
        }

        const storedValue = localStorage.getItem(storageKey);
        const isExpanded = storedValue !== null ? storedValue === '1' : expandedByDefault;

        button.classList.toggle('expanded', isExpanded);
        submenu.classList.toggle('collapsed', !isExpanded);

        button.addEventListener('click', function (event) {
            event.preventDefault();
            const expanded = button.classList.toggle('expanded');
            submenu.classList.toggle('collapsed', !expanded);
            localStorage.setItem(storageKey, expanded ? '1' : '0');
        });
    };

    toggleGroup(talleresGroup, talleresSubmenu, 'entrada.sidebar.talleres.expanded', true);
    toggleGroup(prestamosGroup, prestamosSubmenu, 'entrada.sidebar.prestamos.expanded', true);
}

function initEntradaCosturaRecibos() {
    const mainContainer = document.querySelector('.main-container');
    const apiRoute = mainContainer?.dataset?.routeApiReciboCompleto || '';

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('.btn-ver-recibo-completo');
        if (!button) {
            return;
        }

        const reciboId = String(button.dataset.reciboId || '').trim();
        const numeroRecibo = String(button.dataset.numeroRecibo || '').trim();
        const tipoRecibo = String(button.dataset.tipoRecibo || '').trim().toUpperCase();
        const pedidoProduccionId = Number(button.dataset.pedidoProduccionId || 0);
        const prendaId = Number(button.dataset.prendaId || 0);

        if (!numeroRecibo || !tipoRecibo) {
            return;
        }

        try {
            const pedidosRecibosModule = await ensurePedidosRecibosModule();

            if (tipoRecibo === 'COSTURA') {
                if (
                    pedidosRecibosModule &&
                    typeof pedidosRecibosModule.abrirRecibo === 'function' &&
                    pedidoProduccionId > 0 &&
                    prendaId > 0
                ) {
                    await pedidosRecibosModule.abrirRecibo(pedidoProduccionId, prendaId, 'costura', null, {
                        targetConsecutivo: numeroRecibo,
                        targetReciboId: reciboId || null,
                        esParcial: false
                    });
                    await applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute);
                    normalizeCosturaModalForEntrada();
                    return;
                }

                throw new Error('No se pudo inicializar el visor de recibos de costura');
            }

            if (!reciboId) {
                throw new Error('No se encontro el identificador del recibo');
            }

            if (tipoRecibo === 'CORTE-PARA-BODEGA' && typeof window.openReciboCorteBodegaParcialModal === 'function') {
                window.openReciboCorteBodegaParcialModal(reciboId, tipoRecibo);
                return;
            }

            if (typeof window.openReciboCorteBodegaModal === 'function') {
                window.openReciboCorteBodegaModal(reciboId);
                return;
            }

            if (!apiRoute) {
                throw new Error('Ruta de recibo completo no disponible');
            }

            const url = new URL(apiRoute, window.location.origin);
            url.searchParams.set('recibo_id', reciboId);
            url.searchParams.set('numero_recibo', numeroRecibo);
            url.searchParams.set('tipo_recibo', tipoRecibo);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'No se pudo obtener el recibo');
            }

            if (typeof window.renderReciboCorteBodegaData === 'function') {
                window.renderReciboCorteBodegaData(data);
            } else {
                throw new Error('Modal no disponible');
            }
        } catch (error) {
            console.error('Error abriendo recibo completo:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', error.message || 'No se pudo abrir el recibo', 'error');
            }
        }
    });
}

async function ensurePedidosRecibosModule() {
    if (window.pedidosRecibosModule && typeof window.pedidosRecibosModule.abrirRecibo === 'function') {
        return window.pedidosRecibosModule;
    }

    if (window.PedidosRecibosModule && typeof window.PedidosRecibosModule === 'function') {
        window.pedidosRecibosModule = new window.PedidosRecibosModule();
        return window.pedidosRecibosModule;
    }

    try {
        const modulo = await import('/js/modulos/pedidos-recibos/PedidosRecibosModule.js');
        if (modulo && typeof modulo.PedidosRecibosModule === 'function') {
            window.PedidosRecibosModule = modulo.PedidosRecibosModule;
            window.pedidosRecibosModule = new modulo.PedidosRecibosModule();
            return window.pedidosRecibosModule;
        }
    } catch (error) {
        console.warn('No se pudo cargar PedidosRecibosModule dinámicamente:', error);
    }

    return null;
}

async function applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute) {
    if (!apiRoute) {
        return;
    }

    if (String(numeroRecibo || '').includes('.')) {
        return;
    }

    try {
        const url = new URL(apiRoute, window.location.origin);
        url.searchParams.set('numero_recibo', String(numeroRecibo || '').trim());
        url.searchParams.set('tipo_recibo', String(tipoRecibo || '').trim().toUpperCase());

        const response = await fetch(url.toString());
        const data = await response.json();
        if (!response.ok || !data?.success) {
            return;
        }

        const dia = String(data.dia || '').padStart(2, '0');
        const mes = String(data.mes || '').padStart(2, '0');
        const ano = String(data.ano || '');
        if (!dia || !mes || !ano) {
            return;
        }

        const paintFecha = () => {
            const wrapper = document.getElementById('order-detail-modal-wrapper');
            if (!wrapper) {
                return false;
            }

            const dayBox = wrapper.querySelector('.day-box');
            const monthBox = wrapper.querySelector('.month-box');
            const yearBox = wrapper.querySelector('.year-box');
            if (!dayBox || !monthBox || !yearBox) {
                return false;
            }

            dayBox.textContent = dia;
            monthBox.textContent = mes;
            yearBox.textContent = ano;
            return true;
        };

        if (paintFecha()) {
            return;
        }

        setTimeout(paintFecha, 80);
        setTimeout(paintFecha, 220);
        setTimeout(paintFecha, 500);
        setTimeout(paintFecha, 900);
    } catch (error) {
        console.warn('No se pudo aplicar la fecha del recibo en modal de costura:', error);
    }
}

function normalizeCosturaModalForEntrada() {
    const rcbFloating = document.getElementById('rcb-floating-buttons');
    if (rcbFloating) {
        rcbFloating.classList.remove('is-visible');
    }

    const wrapper = document.getElementById('order-detail-modal-wrapper');
    if (wrapper) {
        wrapper.style.top = '50%';
        wrapper.style.maxHeight = '';
        wrapper.style.overflowY = 'visible';
        wrapper.style.overflowX = 'visible';
        wrapper.style.paddingRight = '0';
    }

    const btnFactura = document.getElementById('btn-factura');
    const btnGaleria = document.getElementById('btn-galeria');
    if (btnFactura) {
        btnFactura.title = 'Ver galeria';
        btnFactura.innerHTML = '<i class="fas fa-images"></i>';
    }
    if (btnGaleria) {
        btnGaleria.style.display = 'none';
        btnGaleria.style.visibility = 'hidden';
        btnGaleria.style.zIndex = '-1';
    }
}
