import { httpJson, httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import { abrirModalCostura, cerrarModalCostura } from './modal-asignacion';

export function manejarPasarACostura(btn) {
    const pedidoId = btn.dataset.pedidoId;
    const numeroPedido = btn.dataset.numeroPedido;
    const prendaId = btn.dataset.prendaId;
    const nombre = btn.dataset.nombre;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const recibo = btn.dataset.recibo;
    const parcialId = btn.dataset.parcialId;
    const prendaBodegaId = btn.dataset.prendaBodegaId;
    const btnId = btn.id;

    console.log(' Manejar pasar a costura:', {
        pedidoId,
        prendaId,
        nombre,
        tipoRecibo,
        recibo,
        parcialId,
        area: btn.dataset.area,
        procesoId: btn.dataset.procesoId,
        encargadoCostura: btn.dataset.encargadoCostura,
        prendaBodegaId,
        btnId,
    });

    const esDeshacer = btn.classList.contains('btn-deshacer-costura');

    if (esDeshacer) {
        deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btn, prendaBodegaId);
    } else {
        abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId, numeroPedido, parcialId, prendaBodegaId);
    }
}

// Las funciones antiguas se mantienen por compatibilidad pero ya no se usan directamente

export function deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnOrId, prendaBodegaId = null) {
    const btn = resolverBotonCostura(btnOrId, pedidoId, prendaId, tipoRecibo);
    console.log('[DESHACER-COSTURA] Iniciando funci髇:', {
        pedidoId,
        prendaId,
        tipoRecibo,
        btnId: typeof btnOrId === 'string' ? btnOrId : (btn?.id || ''),
        prendaBodegaId,
    });

    if (!btn || btn.disabled) {
        console.warn('[DESHACER-COSTURA] No se pudo resolver el bot髇 o ya estaba deshabilitado');
        return;
    }

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';

    console.log('[DESHACER-COSTURA] Enviando DELETE a:', `/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`);

    httpJsonBody(`/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`, 'DELETE', {
        tipo_recibo: tipoRecibo,
        ...(prendaBodegaId ? { prenda_bodega_id: prendaBodegaId } : {}),
    }, {
        headers: {
            Accept: 'application/json',
        },
    })
        .then((response) => {
            console.log('[DESHACER-COSTURA] Respuesta recibida:', response.status, response.statusText);

            if (!response.ok) {
                if (response.redirected) {
                    console.error('[DESHACER-COSTURA] La petici贸n fue redirigida a:', response.url);
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        })
        .then((data) => {
            console.log('[DESHACER-COSTURA] Datos recibidos:', data);
            if (data.success) {
                btn.classList.remove('btn-deshacer-costura');
                btn.dataset.encargadoCostura = '';
                btn.dataset.procesoId = '';
                btn.innerHTML = '<span class="material-symbols-rounded">checkroom</span> PASAR A COSTURA';
                
                // Prevenir que la actualizaci贸n autom谩tica del realtime sobrescriba nuestros cambios
                window.__skipNextDashboardUpdate = true;
                setTimeout(() => {
                    window.__skipNextDashboardUpdate = false;
                }, 1000);
                
                mostrarExito('脡xito', 'Asignaci贸n a costura deshecha correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error deshaciendo costura');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexi贸n');
        })
        .finally(() => {
            btn.disabled = false;
        });
}
function resolverBotonCostura(btnOrId, pedidoId, prendaId, tipoRecibo) {
    if (typeof HTMLElement !== 'undefined' && btnOrId instanceof HTMLElement) {
        return btnOrId;
    }

    if (typeof btnOrId === 'string' && btnOrId.trim()) {
        const byId = document.getElementById(btnOrId);
        if (byId) return byId;
    }

    const pedidoNormalizado = String(pedidoId || '').trim();
    const prendaNormalizada = String(prendaId || '').trim();
    const tipoNormalizado = String(tipoRecibo || '').toUpperCase().trim();

    return Array.from(document.querySelectorAll('.btn-deshacer-costura, .btn-completar-costura'))
        .find((el) => {
            const elPedido = String(el.dataset.pedidoId || '').trim();
            const elPrenda = String(el.dataset.prendaId || '').trim();
            const elTipo = String(el.dataset.tipoRecibo || '').toUpperCase().trim();
            return elPedido === pedidoNormalizado
                && elPrenda === prendaNormalizada
                && (!tipoNormalizado || elTipo === tipoNormalizado);
        }) || null;
}
