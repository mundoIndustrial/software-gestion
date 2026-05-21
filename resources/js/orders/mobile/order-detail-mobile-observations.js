function escapeHtmlMobile(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function obtenerObservacionReciboProcesoMobile(pedidoId, prendaId, tipoProceso) {
    const urlParams = new URLSearchParams(window.location.search);
    const parcialId = String(urlParams.get('parcial_id') || urlParams.get('pedido_parcial_id') || '').trim();
    const params = new URLSearchParams({
        pedido_id: String(pedidoId),
        prenda_id: String(prendaId),
        tipo_proceso: String(tipoProceso || '').trim().toUpperCase()
    });
    if (parcialId) {
        params.set('parcial_id', parcialId);
    }

    const endpoints = [
        '/operario/api/recibos-procesos/observacion',
        '/api/supervisor-pedidos/recibos-procesos/observacion'
    ];

    for (const endpoint of endpoints) {
        try {
            const { response, payload: result } = await window.OrderDetailMobileService.getObservacionReciboProceso(endpoint, params);
            if (!response.ok) continue;

            if (!result?.success) continue;

            return String(result?.data?.observacion || '').trim();
        } catch (_) {
            // Intentar siguiente endpoint
        }
    }

    return '';
}

window.anexarObservacionReciboProcesoMobile = async function({ pedidoId, tipoProceso, prendasMostradas }) {
    const descripcionContenedor = document.getElementById('mobile-descripcion');
    if (!descripcionContenedor) return;

    descripcionContenedor.querySelectorAll('.observacion-recibo-proceso-extra-mobile').forEach((el) => el.remove());

    const pedidoIdInt = Number(pedidoId || 0);
    const tipoProcesoNorm = String(tipoProceso || '').trim().toUpperCase();
    if (!pedidoIdInt || !tipoProcesoNorm || !Array.isArray(prendasMostradas) || prendasMostradas.length === 0) {
        return;
    }

    const itemsPrenda = descripcionContenedor.querySelectorAll('.prenda-item');
    if (!itemsPrenda.length) return;

    await Promise.all(Array.from(itemsPrenda).map(async (itemPrenda, index) => {
        const prenda = prendasMostradas[index];
        const prendaId = Number(prenda?.id || prenda?.prenda_pedido_id || prenda?.prenda_id || 0);
        if (!prendaId) return;

        const observacion = await obtenerObservacionReciboProcesoMobile(pedidoIdInt, prendaId, tipoProcesoNorm);
        if (!observacion) return;

        const bloque = document.createElement('div');
        bloque.className = 'observacion-recibo-proceso-extra-mobile';
        bloque.style.color = '#dc2626';
        bloque.innerHTML = `<br><br><strong>OBSERVACIÃ“N PROCESO:</strong><br>${escapeHtmlMobile(observacion).replace(/\n/g, '<br>')}`;
        itemPrenda.appendChild(bloque);
    }));
};
