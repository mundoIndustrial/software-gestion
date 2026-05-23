import { httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import { asegurarBotonAgregarNovedad } from '../ui/novedadButtons';

function mostrarConfirmacionPasarCC() {
    return new Promise((resolve) => {
        const existente = document.getElementById('modal-confirmar-pasar-cc');
        if (existente) existente.remove();

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirmar-pasar-cc';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            width: min(680px, 96vw);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
            overflow: hidden;
            font-family: inherit;
        `;

        modal.innerHTML = `
            <div style="padding: 22px 24px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #dc2626; font-size: 1.45rem;">warning</span>
                    Advertencia
                </h3>
            </div>
            <div style="padding: 24px; line-height: 1.6; color: #111827;">
                <span style="display: inline-block; font-size: 1.35rem; font-weight: 900;">
                    ¿Está seguro de pasar esta orden a Control de Calidad?
                </span>
            </div>
            <div style="padding: 16px 24px 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" data-accion="cancelar" style="border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" data-accion="confirmar" style="border: none; background: #f97316; color: #fff; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Sí, pasar a C.C
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const manejarEscape = (event) => {
            if (event.key === 'Escape') cerrar(false);
        };

        const cerrar = (respuesta) => {
            document.removeEventListener('keydown', manejarEscape);
            document.body.style.overflow = '';
            overlay.remove();
            resolve(respuesta);
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) cerrar(false);
        });

        modal.querySelector('[data-accion="cancelar"]')?.addEventListener('click', () => cerrar(false));
        modal.querySelector('[data-accion="confirmar"]')?.addEventListener('click', () => cerrar(true));
        modal.querySelector('[data-accion="confirmar"]')?.focus();

        document.addEventListener('keydown', manejarEscape);
    });
}

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

export async function pasarAControlCalidad(btn) {
    const pedidoId = btn.dataset.pedidoId;
    const prendaId = btn.dataset.prendaId;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const recibo = btn.dataset.recibo;
    const parcialId = btn.dataset.parcialId;
    const prendaBodegaId = btn.dataset.prendaBodegaId;
    const esParcial = btn.dataset.esParcial === '1';
    const rolActual = (document.querySelector('.operario-dashboard')?.dataset?.userRole || '').toString().trim().toLowerCase();
    const esVistaCosturaReflectivo = rolActual === 'vista-costura' && String(tipoRecibo || '').toUpperCase() === 'REFLECTIVO';

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
            ...(prendaBodegaId ? { prenda_bodega_id: prendaBodegaId } : {}),
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

    const confirmarPasoCC = await mostrarConfirmacionPasarCC();
    if (!confirmarPasoCC) {
        return;
    }

    console.log('Pasando a Control de Calidad:', { pedidoId, prendaId, tipoRecibo, recibo, parcialId, esParcial });

    const action = `/recibos-novedades/${pedidoId}/${recibo}/cambiar-area-control-calidad`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('es_parcial', esParcial ? '1' : '0');
    if (prendaBodegaId) {
        formData.append('prenda_bodega_id', prendaBodegaId);
    }
    if (parcialId) {
        formData.append('parcial_id', parcialId);
    }

    const originalHTML = btn.innerHTML;
    const card = btn.closest('.orden-card-simple');
    const sincronizarBotonesControlCalidad = (procesoId = '') => {
        if (!card) return;
        const botones = card.querySelectorAll('.btn-pasar-cc');
        botones.forEach((boton) => {
            boton.dataset.area = 'Control Calidad';
            boton.dataset.procesoId = procesoId || '';
            boton.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
        });
    };
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
                if (card) {
                    asegurarBotonAgregarNovedad(card, {
                        numeroPedido: btn.dataset.numeroPedido || btn.dataset.numero || card.dataset.numero || '',
                        prendaId: btn.dataset.prendaId || card.dataset.prendaId || '',
                        nombrePrenda: btn.dataset.nombre || card.dataset.prenda || '',
                        numeroRecibo: btn.dataset.recibo || card.dataset.numeroRecibo || '',
                    });
                }

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
                    if (esVistaCosturaReflectivo) {
                        // Regla de UI: en vista-costura/reflectivo, al quedar en CC ya no debe mostrarse el botón.
                        btn.style.display = 'none';
                    } else {
                        sincronizarBotonesControlCalidad(data.data?.proceso_id || '');
                    }
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
