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
    const registrarDestinoRoute = mainContainer?.dataset?.routeRegistrarDestino || '';

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
        const parcialId = Number(button.dataset.parcialId || 0);
        const prendaBodegaId = Number(button.dataset.prendaBodegaId || 0);
        const esReciboParcial = parcialId > 0 || numeroRecibo.includes('.');

        if (!numeroRecibo || !tipoRecibo) {
            return;
        }

        try {
            const pedidosRecibosModule = await ensurePedidosRecibosModule();

            const esReciboBodega = ['CORTE-PARA-BODEGA', 'COSTURA-BODEGA'].includes(tipoRecibo);

            if (esReciboBodega) {
                if (
                    parcialId > 0 &&
                    typeof window.openReciboCorteBodegaParcialModal === 'function'
                ) {
                    window.openReciboCorteBodegaParcialModal(parcialId, tipoRecibo);
                    return;
                }

                if (prendaBodegaId > 0 && typeof window.openReciboCorteBodegaModal === 'function') {
                    window.openReciboCorteBodegaModal(prendaBodegaId);
                    return;
                }

                throw new Error('No se encontro el identificador del recibo de bodega');
            }

            if (tipoRecibo === 'COSTURA') {
                if (
                    esReciboParcial &&
                    parcialId > 0 &&
                    typeof pedidosRecibosModule?.abrirReciboParcial === 'function' &&
                    pedidoProduccionId > 0 &&
                    prendaId > 0
                ) {
                    await pedidosRecibosModule.abrirReciboParcial(
                        pedidoProduccionId,
                        prendaId,
                        'costura',
                        parcialId,
                        `${tipoRecibo} ANEXO`
                    );
                    await applyReciboFechaToCosturaModal(numeroRecibo, tipoRecibo, apiRoute);
                    normalizeCosturaModalForEntrada();
                    return;
                }

                if (
                    pedidosRecibosModule &&
                    typeof pedidosRecibosModule.abrirRecibo === 'function' &&
                    pedidoProduccionId > 0 &&
                    prendaId > 0
                ) {
                    await pedidosRecibosModule.abrirRecibo(pedidoProduccionId, prendaId, 'costura', null, {
                        targetConsecutivo: numeroRecibo,
                        targetReciboId: reciboId || null,
                        esParcial: esReciboParcial,
                        pedidoParcialId: esReciboParcial ? parcialId : null
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

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('.btn-registrar-destino-entrada');
        if (!button) {
            return;
        }

        const registroId = String(button.dataset.registroId || '').trim();
        const destinoActual = String(button.dataset.destinoCostura || '').trim().toLowerCase();
        if (!registroId) {
            return;
        }

        const resultado = await Swal.fire({
            title: 'Registrar destino',
            text: 'Selecciona el destino de este recibo.',
            input: 'radio',
            inputOptions: {
                logo: 'Logo',
                empacar: 'Empacar',
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Debes seleccionar un destino';
                }
                return null;
            },
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal-wide',
            },
            didOpen: () => {
                if (!destinoActual) {
                    return;
                }

                const radio = document.querySelector(`.swal2-radio input[value="${destinoActual}"]`);
                if (radio) {
                    radio.checked = true;
                }
            },
        });

        if (!resultado.isConfirmed || !resultado.value) {
            return;
        }

        if (!registrarDestinoRoute) {
            Swal.fire('Error', 'No se encontró la ruta para registrar el destino.', 'error');
            return;
        }

        try {
            const url = new URL(registrarDestinoRoute.replace('__REGISTRO__', registroId), window.location.origin);
            const response = await fetch(url.toString(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    destino: resultado.value,
                }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'No se pudo registrar el destino');
            }

            await Swal.fire('Guardado', data.message || 'Destino registrado correctamente', 'success');
            window.location.reload();
        } catch (error) {
            console.error('Error registrando destino:', error);
            Swal.fire('Error', error.message || 'No se pudo registrar el destino', 'error');
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
