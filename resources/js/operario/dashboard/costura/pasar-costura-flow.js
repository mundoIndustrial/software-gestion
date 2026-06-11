import { mostrarError, mostrarExito } from '../ui/messages';

export function confirmarPasarACostura() {
    const encargado = document.getElementById('costuraEncargado')?.value.trim();

    if (!encargado) {
        mostrarError('Error', 'Debes seleccionar o escribir un encargado de costura');
        return;
    }

    if (!window.datosModalCostura) {
        mostrarError('Error', 'No hay datos de la prenda pendiente');
        return;
    }

    const { pedidoId, prendaId, prendaBodegaId, tipoRecibo, btnId, recibo } = window.datosModalCostura;
    let btn = btnId ? document.getElementById(btnId) : null;
    if (!btn) {
        const tipoNormalizado = String(tipoRecibo || '').toUpperCase();
        const reciboNormalizado = String(recibo || '').trim();
        const prendaNormalizada = String(prendaId || '').trim();
        const pedidoNormalizado = String(pedidoId || '').trim();

        btn = Array.from(document.querySelectorAll('.btn-pasar-costura')).find((el) => {
            const elTipo = String(el.dataset.tipoRecibo || '').toUpperCase();
            const elRecibo = String(el.dataset.recibo || '').trim();
            const elPrenda = String(el.dataset.prendaId || '').trim();
            const elPedido = String(el.dataset.pedidoId || '').trim();
            return elTipo === tipoNormalizado
                && elRecibo === reciboNormalizado
                && elPrenda === prendaNormalizada
                && elPedido === pedidoNormalizado;
        }) || null;
    }
    if (!btn) {
        mostrarError('Error', 'No se encontró el botón de acción');
        console.error('[COSTURA] Botón de acción no encontrado', {
            btnId,
            pedidoId,
            prendaId,
            tipoRecibo,
            recibo,
        });
        return;
    }

    const originalHTML = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Procesando...';

    const action = `/recibos-novedades/${pedidoId}/${recibo}/pasar-a-costura`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('prenda_bodega_id', prendaBodegaId ?? '');
    formData.append('encargado', encargado);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('_method', 'POST');

    fetch(action, {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log('Respuesta del servidor:', data);

            if (data.success) {
                btn.dataset.encargadoCostura = encargado;
                btn.dataset.procesoId = data.data?.proceso_id || '';
                btn.classList.add('btn-deshacer-costura');
                btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER COSTURA';
                window.cerrarModalCostura?.();
                mostrarExito('Exito', data.message || 'Prenda asignada a costura correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error asignando a costura');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexion: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
}
