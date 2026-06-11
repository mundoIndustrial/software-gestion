import { httpJson } from '../api/http';

export function cargarTallasSegunOrigen({
    prendaId,
    tipoRecibo,
    numeroPedido = null,
    parcialId = null,
    recibo = null,
    prendaBodegaId = null,
}) {
    if (!prendaId && !prendaBodegaId) {
        return Promise.resolve([]);
    }

    if (prendaBodegaId) {
        return httpJson(`/operario/api/prenda-bodega/${prendaBodegaId}`, {
            method: 'GET',
        })
            .then((response) => response.json())
            .then((data) => {
                console.log('[CARGAR TALLAS BODEGA] Datos recibidos:', data);

                if (!data?.success) {
                    throw new Error(data?.message || 'Error cargando prenda de bodega');
                }

                return data?.data?.tallas || [];
            });
    }

    const numeroPedidoCandidatos = [];
    if (numeroPedido !== undefined && numeroPedido !== null) {
        numeroPedidoCandidatos.push(String(numeroPedido));
    }

    const tr = String(tipoRecibo || '').trim();
    const params = new URLSearchParams();
    params.set('prenda_id', String(prendaId));
    if (tr) params.set('tipo_recibo', tr);
    if (parcialId) params.set('parcial_id', String(parcialId));
    if (recibo) params.set('recibo', String(recibo));

    const intentar = (idx) => {
        if (idx >= numeroPedidoCandidatos.length) {
            return Promise.resolve([]);
        }

        const numeroPedidoActual = numeroPedidoCandidatos[idx];
        return httpJson(`/operario/api/pedido/${numeroPedidoActual}?${params.toString()}`, {
            method: 'GET',
        })
            .then((response) => response.json())
            .then((data) => {
                console.log('[CARGAR TALLAS] Datos recibidos:', data);

                if (!data?.success) {
                    throw new Error(data?.message || 'Error cargando pedido');
                }

                const prendas = data?.data?.prendas || [];
                const prenda = prendas.find((item) => String(item.id) === String(prendaId) || String(item.prenda_pedido_id) === String(prendaId));
                const variantes = prenda?.variantes || [];

                console.log('[CARGAR TALLAS] Prenda encontrada:', prenda);
                console.log('[CARGAR TALLAS] Variantes:', variantes);

                variantes.forEach((variante, index) => {
                    console.log(`[CARGAR TALLAS] Variante ${index}:`, {
                        talla: variante.talla,
                        genero: variante.genero,
                        cantidad: variante.cantidad,
                        colores_detalle: variante.colores_detalle,
                        color_info: variante.color_info,
                    });
                });

                return variantes;
            })
            .catch(() => intentar(idx + 1));
    };

    return intentar(0);
}

export function cargarTallasPrendaDesdeModal() {
    const datos = window.datosModalCostura || {};
    return cargarTallasSegunOrigen(datos);
}

window.cargarTallasPrenda = cargarTallasPrendaDesdeModal;
