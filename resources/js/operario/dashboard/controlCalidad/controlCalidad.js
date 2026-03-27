import { httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

function actualizarInterfazControlCalidadParcial(btn, areaNueva, procesoId = '', esDeshacer = false) {
    const parcialCard = btn.closest('.parcial-card');
    if (!parcialCard) return;

    const areaLabel = parcialCard.querySelector('.parcial-area');
    if (areaLabel) {
        areaLabel.innerHTML = `
            <span class="material-symbols-rounded">location_on</span>
            ${areaNueva || 'SIN ASIGNAR'}
        `;
    }

    btn.dataset.area = areaNueva || '';
    btn.dataset.procesoId = procesoId || '';
    btn.innerHTML = esDeshacer
        ? '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C'
        : '<span class="material-symbols-rounded">undo</span> DESHACER C.C';
}

export function pasarAControlCalidad(btn) {
    const pedidoId = btn.dataset.pedidoId;
    const prendaId = btn.dataset.prendaId;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const recibo = btn.dataset.recibo;
    const parcialId = btn.dataset.parcialId;
    const esParcial = btn.dataset.esParcial === '1';

    const esDeshacer = btn.textContent.includes('DESHACER');

    if (esDeshacer) {
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
            '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">refresh</span> Deshaciendo...';
        btn.style.opacity = '0.6';
        btn.style.pointerEvents = 'none';

        httpJsonBody(`/recibos-novedades/${pedidoId}/${prendaId}/deshacer-control-calidad`, 'DELETE', {
            tipo_recibo: tipoRecibo,
            es_parcial: esParcial,
            parcial_id: parcialId || null,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    const nuevoArea = data.data?.area_nueva || 'Costura';

                    if (esParcial) {
                        actualizarInterfazControlCalidadParcial(btn, nuevoArea, '', true);
                    } else {
                        btn.dataset.area = nuevoArea;
                        btn.dataset.procesoId = '';
                        btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C';
                    }

                    console.log('Control Calidad deshecho. Área restaurada a:', nuevoArea);
                } else {
                    btn.innerHTML = originalHTML;
                    mostrarError('Error', data.message || 'Error deshaciendo control de calidad');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                btn.innerHTML = originalHTML;
                mostrarError('Error', 'Error de conexión');
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = '';
            });

        return;
    }

    console.log('Pasando a Control de Calidad:', { pedidoId, prendaId, tipoRecibo, recibo, parcialId, esParcial });

    const action = `/recibos-novedades/${pedidoId}/${recibo}/cambiar-area-control-calidad`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('es_parcial', esParcial ? '1' : '0');
    if (parcialId) {
        formData.append('parcial_id', parcialId);
    }

    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.style.pointerEvents = 'none';

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
            console.log('Respuesta del servidor (Control Calidad):', data);

            if (data.success) {
                if (esParcial) {
                    actualizarInterfazControlCalidadParcial(
                        btn,
                        data.data?.area_nueva || 'Control Calidad',
                        data.data?.proceso_id || '',
                        false
                    );
                } else {
                    btn.dataset.area = 'Control Calidad';
                    btn.dataset.procesoId = data.data?.proceso_id || '';
                    btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
                }

                mostrarExito('Éxito', data.message || 'Recibo enviado a Control de Calidad correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error enviando a Control de Calidad');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexión: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = '';
        });
}
