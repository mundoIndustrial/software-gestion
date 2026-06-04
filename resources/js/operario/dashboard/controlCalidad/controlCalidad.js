import { httpJsonBody } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import { asegurarBotonAgregarNovedad } from '../ui/novedadButtons';

function normalizarTallasRecibo(tallasRaw) {
    let tallas = tallasRaw;

    if (typeof tallasRaw === 'string') {
        try {
            tallas = JSON.parse(tallasRaw);
        } catch (error) {
            return [];
        }
    }

    if (!Array.isArray(tallas)) {
        return [];
    }

    return tallas
        .map((talla, index) => {
            const nombreTalla = String(talla?.talla ?? talla?.nombre ?? '').trim();
            const cantidad = Number.parseInt(talla?.cantidad ?? 0, 10);

            return {
                index,
                talla: nombreTalla,
                cantidad: Number.isFinite(cantidad) ? cantidad : 0,
                genero: talla?.genero ?? '',
                color_nombre: talla?.color_nombre ?? (Array.isArray(talla?.colores) ? talla.colores[0] ?? '' : ''),
                historial_envios: Array.isArray(talla?.historial_envios)
                    ? talla.historial_envios.map((envio) => ({
                        cantidad: Number.parseInt(envio?.cantidad ?? 0, 10) || 0,
                        fecha_envio: envio?.fecha_envio ?? '',
                    }))
                    : [],
            };
        })
        .filter((talla) => talla.talla !== '');
}

function obtenerTallasDesdeCard(btn) {
    const card = btn.closest('.orden-card-simple');
    const tallasRaw = card?.dataset?.tallas || btn.dataset.tallas || '[]';
    return normalizarTallasRecibo(tallasRaw);
}

function obtenerClaveTalla(talla) {
    return [
        String(talla?.talla ?? '').trim().toUpperCase(),
        String(talla?.genero ?? '').trim().toUpperCase(),
        String(talla?.color_nombre ?? '').trim().toUpperCase(),
    ].join('|');
}

function acumularTallasControlCalidad(tallasExistentes = [], tallasNuevas = []) {
    const acumuladas = new Map();

    [...(Array.isArray(tallasExistentes) ? tallasExistentes : []), ...(Array.isArray(tallasNuevas) ? tallasNuevas : [])]
        .forEach((talla) => {
            const clave = obtenerClaveTalla(talla);
            if (!clave || clave === '||') {
                return;
            }

            const cantidad = Number.parseInt(talla?.cantidad ?? 0, 10) || 0;
            if (cantidad <= 0) {
                return;
            }

            const historial = Array.isArray(talla?.historial_envios) ? talla.historial_envios : [];

            if (!acumuladas.has(clave)) {
                acumuladas.set(clave, {
                    talla: String(talla?.talla ?? '').trim(),
                    cantidad: 0,
                    genero: String(talla?.genero ?? '').trim(),
                    color_nombre: String(talla?.color_nombre ?? '').trim(),
                    historial_envios: [],
                });
            }

            const actual = acumuladas.get(clave);
            actual.cantidad += cantidad;
            actual.historial_envios.push(...historial);
        });

    return Array.from(acumuladas.values());
}

function obtenerTallasControlCalidadGuardadas(btn) {
    const tallasRaw = btn?.dataset?.tallasControlCalidad || '[]';
    return normalizarTallasRecibo(tallasRaw);
}

function obtenerReciboIdControlCalidad(btn) {
    const directo = Number.parseInt(btn?.dataset?.reciboId || '', 10);
    if (Number.isFinite(directo) && directo > 0) {
        return directo;
    }

    const card = btn?.closest('.orden-card-simple');
    const cardReciboId = Number.parseInt(card?.dataset?.reciboId || '', 10);
    if (Number.isFinite(cardReciboId) && cardReciboId > 0) {
        return cardReciboId;
    }

    const alterno = card?.querySelector('[data-recibo-id]');
    const alternoId = Number.parseInt(alterno?.dataset?.reciboId || '', 10);
    if (Number.isFinite(alternoId) && alternoId > 0) {
        return alternoId;
    }

    return null;
}

function formatearFechaEnvioControlCalidad(fechaRaw) {
    if (!fechaRaw) {
        return '';
    }

    const fecha = new Date(String(fechaRaw).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) {
        return String(fechaRaw);
    }

    return fecha.toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function resolverTallasParaModal(btn, tallasOrigen) {
    const tallasGuardadas = obtenerTallasControlCalidadGuardadas(btn);
    if (tallasGuardadas.length === 0) {
        return tallasOrigen.map((talla) => ({
            ...talla,
            original_cantidad: Number.parseInt(talla?.cantidad ?? 0, 10) || 0,
            enviada_cantidad: 0,
        }));
    }

    const cantidadesPorClave = new Map(
        tallasGuardadas.map((talla) => [obtenerClaveTalla(talla), Number.parseInt(talla?.cantidad ?? 0, 10) || 0])
    );

    if (tallasOrigen.length === 0) {
        return tallasGuardadas;
    }

    return tallasOrigen
        .map((talla) => ({
            ...talla,
            original_cantidad: Number.parseInt(talla?.cantidad ?? 0, 10) || 0,
            enviada_cantidad: cantidadesPorClave.get(obtenerClaveTalla(talla)) ?? 0,
            cantidad: cantidadesPorClave.get(obtenerClaveTalla(talla)) ?? 0,
        }))
        .filter((talla) => {
            const original = Number.parseInt(talla?.original_cantidad ?? talla?.cantidad ?? 0, 10) || 0;
            const enviada = Number.parseInt(talla?.enviada_cantidad ?? 0, 10) || 0;
            return enviada < original;
        });
}

function mostrarModalRegistrarTallasCC({ btn, tallas }) {
    return new Promise((resolve) => {
        const existente = document.getElementById('modal-confirmar-pasar-cc');
        if (existente) existente.remove();
        const tallasYaEnviadas = obtenerTallasControlCalidadGuardadas(btn);

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
            width: min(920px, 96vw);
            max-height: min(88vh, 920px);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
            overflow: hidden;
            font-family: inherit;
            display: flex;
            flex-direction: column;
        `;

        const tallasParaMostrar = tallas;
        const filasTallas = tallasParaMostrar
            .map((talla, index) => `
                <div class="cc-talla-row" data-index="${index}" data-talla="${String(talla.talla ?? '').replace(/"/g, '&quot;')}" data-genero="${String(talla.genero ?? '').replace(/"/g, '&quot;')}" data-color-nombre="${String(talla.color_nombre ?? '').replace(/"/g, '&quot;')}" data-original-cantidad="${Number.isFinite(talla.original_cantidad ?? talla.cantidad) ? (talla.original_cantidad ?? talla.cantidad) : 0}" data-enviada-cantidad="${Number.isFinite(talla.enviada_cantidad) ? talla.enviada_cantidad : 0}" style="display: grid; grid-template-columns: 42px minmax(120px, 180px) 110px 1fr; gap: 12px; align-items: center; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc;">
                    <label style="display: flex; align-items: center; justify-content: center; align-self: stretch; cursor: pointer;">
                        <input
                            type="checkbox"
                            class="cc-talla-no-enviar"
                            ${(Number.isFinite(talla.cantidad) ? talla.cantidad : 0) > 0 || (Number.isFinite(talla.enviada_cantidad) ? talla.enviada_cantidad : 0) > 0 ? 'checked' : ''}
                            style="width: 22px; height: 22px; cursor: pointer; accent-color: #2563eb;"
                            title="Marcar si esta talla pasa a Control Calidad"
                        >
                    </label>
                    <div>
                        <div style="font-weight: 800; color: #0f172a; font-size: 14px;">${talla.talla}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            ${talla.genero ? `Genero: ${String(talla.genero).toUpperCase()}` : 'Genero no definido'}
                        </div>
                    </div>
                    <label style="display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: #475569;">
                        Cantidad
                        <input
                            type="number"
                            min="0"
                            step="1"
                            class="cc-talla-cantidad"
                            value="0"
                            style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; font-weight: 700; color: #0f172a; background: #fff;"
                        >
                    </label>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.5;">
                        ${talla.color_nombre ? `<div><strong>Color:</strong> ${String(talla.color_nombre).toUpperCase()}</div>` : ''}
                        <div><strong>Pendientes:</strong> <span class="cc-talla-pendiente">0</span></div>
                        <div>Deja en 0 las tallas que no pasan a Control Calidad.</div>
                    </div>
                </div>
            `)
            .join('');

        modal.innerHTML = `
            <div style="padding: 22px 24px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #f97316; font-size: 1.45rem;">check_circle</span>
                    Registrar tallas para Control Calidad
                </h3>
                <div style="margin-top: 8px; color: #475569; font-size: 0.95rem; line-height: 1.5;">
                    <strong>Recibo:</strong> ${btn.dataset.recibo || '-'} · <strong>Prenda:</strong> ${btn.dataset.nombre || '-'} ·
                    <strong>Tipo:</strong> ${btn.dataset.tipoRecibo || '-'}
                </div>
            </div>
            <div style="padding: 20px 24px; overflow: auto; flex: 1;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px;">
                    <div style="font-weight: 800; color: #111827;">Selecciona o ajusta las cantidades por talla</div>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: flex-end;">
                        ${tallasYaEnviadas.length > 0 ? `
                        <button
                            type="button"
                            data-accion="ver-enviadas"
                            style="border: 1px solid #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;"
                        >
                            <span class="material-symbols-rounded" style="font-size: 18px;">visibility</span>
                            Ver enviadas
                        </button>
                        ` : ''}
                        <button
                            type="button"
                            data-accion="marcar-todas"
                            style="border: 1px solid #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 10px; padding: 8px 12px; font-weight: 700; cursor: pointer;"
                        >
                            Marcar todas
                        </button>
                        <div style="font-size: 13px; color: #475569;">
                            Total: <strong data-total-tallas style="color: #0f172a;">0</strong>
                        </div>
                    </div>
                </div>
                <div data-tallas-container style="display: grid; gap: 12px;">
                    ${filasTallas || '<div style="padding: 14px 16px; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b; text-align: center;">No hay tallas pendientes por enviar a Control Calidad.</div>'}
                </div>
                <div data-error-tallas style="display: none; margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-weight: 600;"></div>
            </div>
            <div style="padding: 16px 24px 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" data-accion="cancelar" style="border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" data-accion="confirmar" style="border: none; background: #f97316; color: #fff; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Pasar a C.C
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const totalTallasElement = modal.querySelector('[data-total-tallas]');
        const errorTallasElement = modal.querySelector('[data-error-tallas]');
        const confirmarButton = modal.querySelector('[data-accion="confirmar"]');
        const marcarTodasButton = modal.querySelector('[data-accion="marcar-todas"]');
        const verEnviadasButton = modal.querySelector('[data-accion="ver-enviadas"]');

        const sincronizarEstadoFila = (row) => {
            const checkbox = row.querySelector('.cc-talla-no-enviar');
            const input = row.querySelector('.cc-talla-cantidad');
            const enviarACC = checkbox?.checked === true;

            if (!input) {
                return;
            }

            if (!enviarACC) {
                input.dataset.valorPrevio = input.value;
                input.value = '0';
                input.disabled = true;
                input.style.opacity = '0.65';
                input.style.cursor = 'not-allowed';
            } else {
                input.disabled = false;
                input.style.opacity = '1';
                input.style.cursor = '';

                const valorPrevio = Number.parseInt(input.dataset.valorPrevio || '', 10);
                if (Number.isFinite(valorPrevio) && valorPrevio > 0 && Number.parseInt(input.value || '0', 10) <= 0) {
                    input.value = String(valorPrevio);
                }
            }

            row.style.background = enviarACC ? '#f8fafc' : '#f1f5f9';
            row.style.borderColor = enviarACC ? '#e5e7eb' : '#cbd5e1';
        };

        const calcularTotal = () => {
            const total = Array.from(modal.querySelectorAll('.cc-talla-cantidad')).reduce((acumulado, input) => {
                const cantidad = Number.parseInt(input.value || '0', 10);
                return acumulado + (Number.isFinite(cantidad) ? cantidad : 0);
            }, 0);

            modal.querySelectorAll('.cc-talla-row').forEach((row) => {
                const input = row.querySelector('.cc-talla-cantidad');
                const pendienteElement = row.querySelector('.cc-talla-pendiente');
                const original = Number.parseInt(row.dataset.originalCantidad || '0', 10);
                const enviada = Number.parseInt(row.dataset.enviadaCantidad || '0', 10);
                const pendiente = Math.max(0, (Number.isFinite(original) ? original : 0) - (Number.isFinite(enviada) ? enviada : 0));

                if (pendienteElement) {
                    pendienteElement.textContent = String(pendiente);
                }
            });

            if (totalTallasElement) {
                totalTallasElement.textContent = String(total);
            }

            if (confirmarButton) {
                confirmarButton.disabled = total <= 0;
                confirmarButton.style.opacity = total > 0 ? '1' : '0.6';
                confirmarButton.style.cursor = total > 0 ? 'pointer' : 'not-allowed';
            }

            return total;
        };

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
        confirmarButton?.addEventListener('click', () => {
            // Validar que ninguna talla exceda el máximo permitido
            const hayErrores = Array.from(modal.querySelectorAll('.cc-talla-cantidad')).some((input) => {
                const row = input.closest('.cc-talla-row');
                const original = Number.parseInt(row.dataset.originalCantidad || '0', 10);
                const enviada = Number.parseInt(row.dataset.enviadaCantidad || '0', 10);
                const maximo = Math.max(0, (Number.isFinite(original) ? original : 0) - (Number.isFinite(enviada) ? enviada : 0));
                const valor = Number.parseInt(input.value || '0', 10);
                return valor > maximo;
            });

            if (hayErrores) {
                if (errorTallasElement) {
                    errorTallasElement.textContent = 'Hay tallas que exceden el máximo permitido. Revisa los valores en rojo.';
                    errorTallasElement.style.display = 'block';
                }
                return;
            }

            const tallasSeleccionadas = Array.from(modal.querySelectorAll('.cc-talla-row'))
                .map((row) => {
                    const talla = row.dataset.talla || '';
                    const input = row.querySelector('.cc-talla-cantidad');
                    const cantidad = Number.parseInt(input?.value || '0', 10);

                    return {
                        talla,
                        cantidad: Number.isFinite(cantidad) ? cantidad : 0,
                        genero: row.dataset.genero || '',
                        color_nombre: row.dataset.colorNombre || '',
                    };
                })
                .filter((item) => item.talla !== '' && item.cantidad > 0);

            if (tallasSeleccionadas.length === 0) {
                if (errorTallasElement) {
                    errorTallasElement.textContent = 'Debes registrar al menos una talla con cantidad mayor a 0.';
                    errorTallasElement.style.display = 'block';
                }
                return;
            }

            cerrar(tallasSeleccionadas);
        });

        document.addEventListener('keydown', manejarEscape);

        modal.querySelectorAll('.cc-talla-cantidad').forEach((input) => {
            input.addEventListener('input', () => {
                const row = input.closest('.cc-talla-row');
                const original = Number.parseInt(row.dataset.originalCantidad || '0', 10);
                const enviada = Number.parseInt(row.dataset.enviadaCantidad || '0', 10);
                const maximo = Math.max(0, (Number.isFinite(original) ? original : 0) - (Number.isFinite(enviada) ? enviada : 0));
                const valor = Number.parseInt(input.value || '0', 10);

                if (valor > maximo) {
                    input.style.borderColor = '#dc2626';
                    input.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
                    if (errorTallasElement) {
                        errorTallasElement.textContent = `La talla ${row.dataset.talla} no puede exceder ${maximo} (Pendientes: ${maximo})`;
                        errorTallasElement.style.display = 'block';
                    }
                } else {
                    input.style.borderColor = '#cbd5e1';
                    input.style.boxShadow = '';
                    if (errorTallasElement) {
                        errorTallasElement.style.display = 'none';
                        errorTallasElement.textContent = '';
                    }
                }

                calcularTotal();
            });
        });

        modal.querySelectorAll('.cc-talla-row').forEach((row) => {
            const checkbox = row.querySelector('.cc-talla-no-enviar');
            checkbox?.addEventListener('change', () => {
                if (errorTallasElement) {
                    errorTallasElement.style.display = 'none';
                    errorTallasElement.textContent = '';
                }

                sincronizarEstadoFila(row);
                calcularTotal();
            });

            sincronizarEstadoFila(row);
        });

        marcarTodasButton?.addEventListener('click', () => {
            if (errorTallasElement) {
                errorTallasElement.style.display = 'none';
                errorTallasElement.textContent = '';
            }

            modal.querySelectorAll('.cc-talla-row').forEach((row) => {
                const checkbox = row.querySelector('.cc-talla-no-enviar');
                if (checkbox) {
                    checkbox.checked = true;
                }
                sincronizarEstadoFila(row);
            });

            calcularTotal();
        });

        verEnviadasButton?.addEventListener('click', () => {
            verTallasControlCalidad(btn, overlay);
        });

        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event.target?.classList?.contains('cc-talla-cantidad')) {
                event.preventDefault();
                confirmarButton?.click();
            }
        });

        calcularTotal();
        modal.querySelector('.cc-talla-cantidad')?.focus();
    });
}

function construirResumenTallasControlCalidad(tallas) {
    if (!Array.isArray(tallas) || tallas.length === 0) {
        return '<div style="padding: 14px 16px; border: 1px dashed #cbd5e1; border-radius: 12px; color: #64748b; text-align: center;">No hay tallas registradas en Control Calidad.</div>';
    }

    return tallas.map((talla) => {
        const tallaNombre = String(talla?.talla ?? 'SIN TALLA').toUpperCase();
        const genero = String(talla?.genero ?? '').toUpperCase();
        const color = String(talla?.color_nombre ?? '').toUpperCase();
        const cantidad = Number.parseInt(talla?.cantidad ?? 0, 10) || 0;
        const historial = Array.isArray(talla?.historial_envios) ? talla.historial_envios : [];
        const historialHtml = historial.length > 0
            ? `
                <div style="margin-top: 10px; display: grid; gap: 6px;">
                    ${historial.map((envio) => `
                        <div style="font-size: 12px; color: #475569; display: flex; justify-content: space-between; gap: 8px;">
                            <span>${formatearFechaEnvioControlCalidad(envio?.fecha_envio)}</span>
                            <strong style="color: #1d4ed8;">+${Number.parseInt(envio?.cantidad ?? 0, 10) || 0}</strong>
                        </div>
                    `).join('')}
                </div>
            `
            : '';

        return `
            <div style="display: grid; grid-template-columns: minmax(120px, 1fr) 110px; gap: 12px; align-items: center; padding: 12px 14px; border: 1px solid #dbeafe; border-radius: 12px; background: #f8fbff;">
                <div>
                    <div style="font-weight: 800; color: #0f172a; font-size: 14px;">${tallaNombre}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        ${genero ? `Genero: ${genero}` : 'Genero no definido'}
                        ${color ? ` · Color: ${color}` : ''}
                    </div>
                    ${historialHtml}
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 12px; color: #64748b;">Cantidad enviada</div>
                    <div style="font-size: 18px; font-weight: 800; color: #1d4ed8;">${cantidad}</div>
                </div>
            </div>
        `;
    }).join('');
}

export async function verTallasControlCalidad(btn, modalOrigen = null) {
    let tallas = obtenerTallasControlCalidadGuardadas(btn);
    const reciboId = obtenerReciboIdControlCalidad(btn);
    const existente = document.getElementById('modal-ver-tallas-cc');
    if (existente) existente.remove();
    if (modalOrigen) {
        modalOrigen.style.visibility = 'hidden';
    }

    if (reciboId) {
        try {
            const response = await fetch(`/operario/api/recibos/${reciboId}/tallas-control-calidad`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (response.ok && data?.success && Array.isArray(data?.tallas)) {
                tallas = normalizarTallasRecibo(data.tallas);
            }
        } catch (error) {
            console.warn('[verTallasControlCalidad] No se pudo refrescar desde backend:', error);
        }
    }

    const overlay = document.createElement('div');
    overlay.id = 'modal-ver-tallas-cc';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.58);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 48px 24px 24px;
        z-index: 1000001;
        backdrop-filter: blur(2px);
    `;

    const modal = document.createElement('div');
    modal.style.cssText = `
        width: min(560px, 100%);
        max-height: min(80vh, 720px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        overflow: hidden;
        font-family: inherit;
        display: flex;
        flex-direction: column;
    `;

    modal.innerHTML = `
        <div style="padding: 22px 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: #2563eb; font-size: 1.4rem;">inventory_2</span>
                Tallas enviadas a C.C
            </h3>
            <div style="margin-top: 8px; color: #475569; font-size: 0.95rem; line-height: 1.5;">
                <strong>Prenda:</strong> ${btn.dataset.nombre || '-'}<br>
                <strong>Tipo:</strong> ${btn.dataset.tipoRecibo || '-'}
            </div>
        </div>
        <div style="padding: 20px 24px; overflow: auto; display: grid; gap: 12px;">
            ${construirResumenTallasControlCalidad(tallas)}
        </div>
        <div style="padding: 16px 24px 24px; display: flex; justify-content: flex-end;">
            <button type="button" data-accion="cerrar" style="border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                Cerrar
            </button>
        </div>
    `;

    const cerrar = () => {
        document.body.style.overflow = '';
        if (modalOrigen) {
            modalOrigen.style.visibility = '';
        }
        overlay.remove();
    };

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) cerrar();
    });

    modal.querySelector('[data-accion="cerrar"]')?.addEventListener('click', cerrar);

    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
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

function actualizarEstadoBotonControlCalidad(boton, { area, procesoId = '', estado = 'pendiente', tallasControlCalidad = null }) {
    boton.dataset.area = area || '';
    boton.dataset.procesoId = procesoId || '';
    boton.dataset.estadoControlCalidad = estado;

    if (Array.isArray(tallasControlCalidad)) {
        boton.dataset.tallasControlCalidad = JSON.stringify(tallasControlCalidad);
    } else if (estado === 'pendiente') {
        delete boton.dataset.tallasControlCalidad;
    }

    if (estado === 'parcial') {
        boton.innerHTML = '<span class="material-symbols-rounded">edit</span> EDITAR C.C';
        return;
    }

    if (estado === 'completo') {
        boton.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
        return;
    }

    boton.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PASAR A C.C';
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

                    console.log('Control Calidad deshecho. Area restaurada a:', nuevoArea);
                } else {
                    btn.innerHTML = originalHTML;
                    mostrarError('Error', data.message || 'Error deshaciendo control de calidad');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                btn.innerHTML = originalHTML;
                mostrarError('Error', 'Error de conexion');
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = '';
            });

        return;
    }

    const tallasRecibo = obtenerTallasDesdeCard(btn);
    const tallasParaModal = resolverTallasParaModal(btn, tallasRecibo);
    const tallasControlCalidad = await mostrarModalRegistrarTallasCC({ btn, tallas: tallasParaModal });
    if (!tallasControlCalidad) {
        return;
    }

    console.log('Pasando a Control de Calidad:', { pedidoId, prendaId, tipoRecibo, recibo, parcialId, esParcial, tallasControlCalidad });

    const action = `/recibos-novedades/${pedidoId}/${recibo}/cambiar-area-control-calidad`;

    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('prenda_id', prendaId);
    formData.append('tipo_recibo', tipoRecibo);
    formData.append('es_parcial', esParcial ? '1' : '0');
    formData.append('tallas_control_calidad', JSON.stringify(tallasControlCalidad));
    if (prendaBodegaId) {
        formData.append('prenda_bodega_id', prendaBodegaId);
    }
    if (parcialId) {
        formData.append('parcial_id', parcialId);
    }

    const originalHTML = btn.innerHTML;
    const card = btn.closest('.orden-card-simple');
    const totalTallasOrigen = tallasRecibo.reduce((acumulado, talla) => {
        const cantidad = Number.parseInt(talla?.cantidad ?? 0, 10);
        return acumulado + (Number.isFinite(cantidad) ? cantidad : 0);
    }, 0);
    const tallasGuardadas = obtenerTallasControlCalidadGuardadas(btn);
    const tallasControlCalidadAcumuladas = acumularTallasControlCalidad(
        tallasGuardadas,
        tallasControlCalidad.map((talla) => ({
            ...talla,
            historial_envios: [{
                cantidad: Number.parseInt(talla?.cantidad ?? 0, 10) || 0,
                fecha_envio: new Date().toISOString(),
            }],
        }))
    );
    const totalTallasSeleccionadas = tallasControlCalidadAcumuladas.reduce((acumulado, talla) => {
        const cantidad = Number.parseInt(talla?.cantidad ?? 0, 10);
        return acumulado + (Number.isFinite(cantidad) ? cantidad : 0);
    }, 0);
    const esControlCalidadParcial = totalTallasOrigen > 0 && totalTallasSeleccionadas < totalTallasOrigen;
    const estadoControlCalidad = esControlCalidadParcial ? 'parcial' : 'completo';
    const sincronizarBotonesControlCalidad = (areaNueva = 'Control Calidad', procesoId = '', estado = estadoControlCalidad) => {
        if (!card) return;
        const botones = card.querySelectorAll('.btn-pasar-cc');
        botones.forEach((boton) => {
            actualizarEstadoBotonControlCalidad(boton, {
                area: areaNueva,
                procesoId,
                estado,
                tallasControlCalidad: tallasControlCalidadAcumuladas,
            });
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
                    const areaNueva = data.data?.area_nueva || 'Control Calidad';
                    const estadoNuevo = data.data?.estado_control_calidad || estadoControlCalidad;
                    if (esVistaCosturaReflectivo) {
                        btn.style.display = 'none';
                    } else {
                        sincronizarBotonesControlCalidad(areaNueva, data.data?.proceso_id || '', estadoNuevo);
                    }
                }

                mostrarExito('Exito', data.message || 'Recibo enviado a Control de Calidad correctamente');
            } else {
                btn.innerHTML = originalHTML;
                mostrarError('Error', data.message || 'Error enviando a Control de Calidad');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            btn.innerHTML = originalHTML;
            mostrarError('Error', 'Error de conexion: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = '';
        });
}
