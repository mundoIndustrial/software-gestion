(function () {
    if (typeof window === 'undefined') return;

    const H = window.CotizacionModalHelpers;

    function getTipoCodigo(payload) {
        const codigoTipoCotizacion = (
            payload?.cotizacion?.tipo ||
            payload?.cotizacion?.tipo_codigo ||
            payload?.cotizacion?.tipo_cotizacion_codigo ||
            payload?.cotizacion?.tipo_cotizacion?.codigo ||
            payload?.cotizacion?.tipoCotizacion?.codigo ||
            ''
        );

        return H ? H.toUpper(codigoTipoCotizacion) : (codigoTipoCotizacion || '').toString().trim().toUpperCase();
    }

    function prendaTieneLogo(payload, prendaObj) {
        const tecnicas = (payload.logo_cotizacion && Array.isArray(payload.logo_cotizacion.tecnicas_prendas))
            ? payload.logo_cotizacion.tecnicas_prendas.filter(tp => tp && tp.prenda_id === prendaObj.id)
            : [];
        return !!(tecnicas && tecnicas.length > 0);
    }

    function compute(payload, viewMode = null, opts = {}) {
        const { hideSelector = false } = opts;

        const prendasAll = Array.isArray(payload.prendas_cotizaciones) ? payload.prendas_cotizaciones : [];
        const tienePrendas = prendasAll.length > 0 ? true : !!payload.tiene_prendas;
        const tieneLogo = !!payload.logo_cotizacion || !!payload.tiene_logo;

        const tipoCodigo = getTipoCodigo(payload);
        const esTipoSoloLogoPorCodigo = ['L', 'LG', 'LOGO', 'B'].includes(tipoCodigo);

        const esCombinadaRaw = !!(tienePrendas && tieneLogo && !esTipoSoloLogoPorCodigo);
        const esSoloLogo = !!(esTipoSoloLogoPorCodigo || (!tienePrendas && tieneLogo));

        let modo = viewMode || (window.__cotizacionModalViewMode || null);

        const esCombinada = esCombinadaRaw;
        const esSoloLogoFinal = esCombinada ? false : esSoloLogo;

        if (!modo && esCombinada) {
            modo = 'prenda';
        }
        if (esSoloLogoFinal) {
            modo = 'logo';
        }
        if (esCombinada && hideSelector) {
            modo = 'logo';
        }
        if (esCombinada) {
            window.__cotizacionModalViewMode = modo;
        }

        return {
            prendasAll,
            tienePrendas,
            tieneLogo,
            tipoCodigo,
            esTipoSoloLogoPorCodigo,
            esCombinada,
            esSoloLogoFinal,
            modo,
            prendaTieneLogo: (prenda) => prendaTieneLogo(payload, prenda),
        };
    }

    window.CotizacionModalTypeDetector = {
        getTipoCodigo,
        compute,
    };
})();
