(function () {
    if (typeof window === 'undefined') return;

    function wireSelectorEvents(renderFn, ctx) {
        if (!ctx.esCombinada || ctx.hideSelector) return;

        const btnPrenda = document.getElementById('cotModalBtnPrenda');
        const btnLogo = document.getElementById('cotModalBtnLogo');

        if (btnPrenda) {
            btnPrenda.onclick = () => {
                window.__cotizacionModalViewMode = 'prenda';
                renderFn(window.__cotizacionModalData, 'prenda');
            };
        }

        if (btnLogo) {
            btnLogo.onclick = () => {
                window.__cotizacionModalViewMode = 'logo';
                renderFn(window.__cotizacionModalData, 'logo');
            };
        }
    }

    function updateHeader(payload) {
        if (!payload || !payload.cotizacion) return;
        const cot = payload.cotizacion;

        const elNumber = document.getElementById('modalHeaderNumber');
        const elDate = document.getElementById('modalHeaderDate');
        const elClient = document.getElementById('modalHeaderClient');
        const elAdvisor = document.getElementById('modalHeaderAdvisor');

        if (elNumber) elNumber.textContent = cot.numero_cotizacion || 'N/A';
        if (elDate) elDate.textContent = cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A';
        if (elClient) elClient.textContent = cot.nombre_cliente || 'N/A';
        if (elAdvisor) elAdvisor.textContent = cot.asesora_nombre || 'N/A';
    }

    async function openCotizacionModal(cotizacionId) {
        fetch(`/contador/cotizacion/${cotizacionId}`)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                window.__cotizacionModalData = data;

                const renderCotizacionModal = (payload, viewMode = null) => {
                    updateHeader(payload);

                    const moduloActual = (typeof document !== 'undefined' && document.body && document.body.dataset)
                        ? document.body.dataset.module
                        : '';
                    const isVisualizadorLogo = moduloActual === 'visualizador-logo';
                    const hideSelector = isVisualizadorLogo || !!window.__cotizacionModalHideSelector;

                    const ctx = window.CotizacionModalTypeDetector.compute(payload, viewMode, { hideSelector });
                    ctx.hideSelector = hideSelector;

                    // TODO: Migrar render completo. Por ahora usamos renderer legacy en cotizacion.js principal.
                    const html = (typeof window.__cotizacionModalLegacyRender === 'function')
                        ? window.__cotizacionModalLegacyRender(payload, ctx, renderCotizacionModal)
                        : (window.CotizacionModalRenderPrenda
                            ? window.CotizacionModalRenderPrenda.renderPrendas(payload, ctx)
                            : '');

                    const body = document.getElementById('modalBody');
                    if (body) body.innerHTML = html;

                    wireSelectorEvents(renderCotizacionModal, ctx);

                    const modal = document.getElementById('cotizacionModal');
                    if (modal) modal.style.display = 'flex';
                };

                renderCotizacionModal(data);
            })
            .catch(error => {
                alert('Error al cargar la cotización: ' + error.message);
            });
    }

    function closeCotizacionModal() {
        const modal = document.getElementById('cotizacionModal');
        if (modal) modal.style.display = 'none';
    }

    // Exports
    window.CotizacionModalCore = {
        openCotizacionModal,
        closeCotizacionModal,
    };
})();
