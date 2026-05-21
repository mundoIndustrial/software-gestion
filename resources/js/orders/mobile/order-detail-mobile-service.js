(() => {
    const fetchJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        const payload = isJson ? await response.json() : null;
        return { response, payload };
    };

    const getGaleria = async (pedido) => {
        const url = `/registros/${pedido}/images`;
        const { response, payload } = await fetchJson(url);
        if (!response.ok) {
            throw new Error(`Error galeria: ${response.status}`);
        }
        return payload;
    };

    const getPedidoDinamico = async (url) => {
        const { response, payload } = await fetchJson(url, {
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        return { response, payload };
    };

    const getObservacionReciboProceso = async (endpoint, params) => {
        const { response, payload } = await fetchJson(`${endpoint}?${params.toString()}`);
        return { response, payload };
    };

    const getReciboParcial = async (parcialIdNum) => {
        const { response, payload } = await fetchJson(`/api/recibos-parciales/${parcialIdNum}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) return null;
        return payload;
    };

    const getAnchoMetraje = async (publicEndpoint, insumosEndpoint) => {
        let first = await fetch(publicEndpoint);
        if (!first.ok && first.status === 404) {
            first = await fetch(insumosEndpoint);
        }
        if (!first.ok) {
            throw new Error(`Error ancho/metraje: ${first.status}`);
        }
        return await first.json();
    };

    window.OrderDetailMobileService = {
        getGaleria,
        getPedidoDinamico,
        getObservacionReciboProceso,
        getReciboParcial,
        getAnchoMetraje
    };
})();
