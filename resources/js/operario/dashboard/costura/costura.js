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
    const btnId = btn.id;

    console.log(' Manejar pasar a costura:', {
        pedidoId,
        prendaId,
        nombre,
        tipoRecibo,
        recibo,
        area: btn.dataset.area,
        procesoId: btn.dataset.procesoId,
        encargadoCostura: btn.dataset.encargadoCostura,
        btnId,
    });

    const esDeshacer = btn.classList.contains('btn-deshacer-costura');

    if (esDeshacer) {
        deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId);
    } else {
        abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, btnId, numeroPedido);
    }
}

// Las funciones antiguas se mantienen por compatibilidad pero ya no se usan directamente

export function deshacerCosturaVista(pedidoId, prendaId, tipoRecibo, btnId) {
    console.log('[DESHACER-COSTURA] Iniciando función:', { pedidoId, prendaId, tipoRecibo, btnId });

    const btn = document.getElementById(btnId);
    if (!btn || btn.disabled) return;

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';

    console.log('[DESHACER-COSTURA] Enviando DELETE a:', `/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`);

    httpJsonBody(`/recibos-novedades/${pedidoId}/${prendaId}/deshacer-costura`, 'DELETE', {
        tipo_recibo: tipoRecibo,
    }, {
        headers: {
            Accept: 'application/json',
        },
    })
        .then((response) => {
            console.log('[DESHACER-COSTURA] Respuesta recibida:', response.status, response.statusText);

            if (!response.ok) {
                if (response.redirected) {
                    console.error('[DESHACER-COSTURA] La petición fue redirigida a:', response.url);
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
                mostrarExito('Éxito', 'Asignación a costura deshecha correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error deshaciendo costura');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión');
        })
        .finally(() => {
            btn.disabled = false;
        });
}

