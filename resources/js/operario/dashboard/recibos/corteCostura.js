import { httpJson } from '../api/http';
import { asegurarBadgeCompletado } from '../ui/badges';

function mostrarConfirmacionCompletarCorte() {
    return new Promise((resolve) => {
        const existente = document.getElementById('modal-confirmar-completar-corte');
        if (existente) {
            existente.remove();
        }

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirmar-completar-corte';
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
            width: min(760px, 96vw);
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
                <span style="display: inline-block; font-size: 1.6rem; font-weight: 900; margin-bottom: 12px; color: #111827;">
                    ¿Sí está seguro de que completaste el corte?
                </span>
                <div style="font-size: 1.1rem; margin-bottom: 8px;">
                    La orden pasará a Costura y saldrá de Corte.
                </div>
                <div style="font-size: 1.1rem; margin-bottom: 8px;">
                    Recuerda: registrar la novedad del corte (ej.: piezas x pasadas). Sin ella no habrá trazabilidad.
                </div>
                <div style="font-size: 1.1rem;">
                    Si no lo has hecho, presiona Cancelar.
                </div>
            </div>
            <div style="padding: 16px 24px 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" data-accion="cancelar" style="border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" data-accion="confirmar" style="border: none; background: #f97316; color: #fff; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Sí, completar
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const manejarEscape = (event) => {
            if (event.key === 'Escape') {
                cerrar(false);
            }
        };

        const cerrar = (respuesta) => {
            document.removeEventListener('keydown', manejarEscape);
            document.body.style.overflow = '';
            overlay.remove();
            resolve(respuesta);
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                cerrar(false);
            }
        });

        const btnCancelar = modal.querySelector('[data-accion="cancelar"]');
        const btnConfirmar = modal.querySelector('[data-accion="confirmar"]');

        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => cerrar(false));
        }
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', () => cerrar(true));
            btnConfirmar.focus();
        }

        document.addEventListener('keydown', manejarEscape);
    });
}

function mostrarConfirmacionCompletarCostura() {
    return new Promise((resolve) => {
        const existente = document.getElementById('modal-confirmar-completar-costura');
        if (existente) {
            existente.remove();
        }

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirmar-completar-costura';
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
                    ¿Estás seguro que completaste la orden?
                </span>
            </div>
            <div style="padding: 16px 24px 24px; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" data-accion="cancelar" style="border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" data-accion="confirmar" style="border: none; background: #f97316; color: #fff; border-radius: 10px; padding: 10px 16px; font-weight: 700; cursor: pointer;">
                    Sí, completar
                </button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const manejarEscape = (event) => {
            if (event.key === 'Escape') {
                cerrar(false);
            }
        };

        const cerrar = (respuesta) => {
            document.removeEventListener('keydown', manejarEscape);
            document.body.style.overflow = '';
            overlay.remove();
            resolve(respuesta);
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                cerrar(false);
            }
        });

        const btnCancelar = modal.querySelector('[data-accion="cancelar"]');
        const btnConfirmar = modal.querySelector('[data-accion="confirmar"]');

        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => cerrar(false));
        }
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', () => cerrar(true));
            btnConfirmar.focus();
        }

        document.addEventListener('keydown', manejarEscape);
    });
}

function actualizarInterfazCorte(container, accion, btnActual) {
    if (!container) return;

    const prendaId = btnActual.dataset.prendaId;
    const reciboId = btnActual.dataset.reciboId;
    const nombre = btnActual.dataset.nombre;
    const esParcial = btnActual.dataset.esParcial === '1';
    const tipoRecibo = btnActual.dataset.tipoRecibo || '';

    if (accion === 'completado') {
        const nuevoBtn = document.createElement('button');
        nuevoBtn.className = 'btn-deshacer-corte';
        nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
        nuevoBtn.setAttribute('data-prenda-id', prendaId);
        nuevoBtn.setAttribute('data-recibo-id', reciboId);
        nuevoBtn.setAttribute('data-nombre', nombre);
        nuevoBtn.setAttribute('onclick', 'deshacerCorte(this)');
        nuevoBtn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';

        if (btnActual.parentNode) {
            btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
        }

        const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
        badges.forEach((badge) => {
            badge.classList.add('is-on');
            badge.textContent = 'COMPLETADO';
        });
    } else if (accion === 'deshacer') {
        const nuevoBtn = document.createElement('button');
        nuevoBtn.className = 'btn-completar-corte';
        nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
        nuevoBtn.setAttribute('data-prenda-id', prendaId);
        nuevoBtn.setAttribute('data-recibo-id', reciboId);
        nuevoBtn.setAttribute('data-nombre', nombre);
        nuevoBtn.setAttribute('onclick', 'completarCorte(this)');
        nuevoBtn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> MARCAR COMPLETADO';

        if (btnActual.parentNode) {
            btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
        }

        const badges = container.querySelectorAll('.badge-completado-corte, .badge-estado');
        badges.forEach((badge) => {
            badge.classList.remove('is-on');
            badge.textContent = 'PENDIENTE';
        });
    }
}

function actualizarInterfazCostura(container, accion, btnActual) {
    if (!container) return;

    const prendaId = btnActual.dataset.prendaId;
    const reciboId = btnActual.dataset.reciboId;
    const nombre = btnActual.dataset.nombre;
    const esParcial = btnActual.dataset.esParcial === '1';
    const tipoRecibo = btnActual.dataset.tipoRecibo || '';

    if (accion === 'completado') {
        const card = container.closest('.orden-card-simple') || container;
        if (card) {
            card.classList.add('card-completado-costura');
        }

        const nuevoBtn = document.createElement('button');
        nuevoBtn.className = 'btn-deshacer-costura';
        nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
        nuevoBtn.setAttribute('data-prenda-id', prendaId);
        nuevoBtn.setAttribute('data-recibo-id', reciboId);
        nuevoBtn.setAttribute('data-nombre', nombre);
        nuevoBtn.setAttribute('data-es-parcial', esParcial ? '1' : '0');
        if (tipoRecibo) nuevoBtn.setAttribute('data-tipo-recibo', tipoRecibo);
        nuevoBtn.setAttribute('onclick', 'deshacerCostura(this)');
        nuevoBtn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';

        if (btnActual.parentNode) {
            btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
        }

        const badges = container.querySelectorAll('.badge-completado-costura');
        badges.forEach((badge) => {
            badge.classList.add('is-on');
            badge.textContent = 'COMPLETADO';
        });
    } else if (accion === 'deshacer') {
        const card = container.closest('.orden-card-simple') || container;
        if (card) {
            card.classList.remove('card-completado-costura');
        }

        const nuevoBtn = document.createElement('button');
        nuevoBtn.className = 'btn-completar-costura';
        nuevoBtn.setAttribute('data-pedido-id', btnActual.dataset.pedidoId);
        nuevoBtn.setAttribute('data-prenda-id', prendaId);
        nuevoBtn.setAttribute('data-recibo-id', reciboId);
        nuevoBtn.setAttribute('data-nombre', nombre);
        nuevoBtn.setAttribute('data-es-parcial', esParcial ? '1' : '0');
        if (tipoRecibo) nuevoBtn.setAttribute('data-tipo-recibo', tipoRecibo);
        nuevoBtn.setAttribute('onclick', 'completarCostura(this)');
        nuevoBtn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> COMPLETAR';

        if (btnActual.parentNode) {
            btnActual.parentNode.replaceChild(nuevoBtn, btnActual);
        }

        const badges = container.querySelectorAll('.badge-completado-costura');
        badges.forEach((badge) => {
            badge.classList.remove('is-on');
            badge.textContent = 'PENDIENTE';
        });
    }
}

export async function completarCorte(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');
    const esParcial = btn.dataset.esParcial === '1';
    const confirmado = await mostrarConfirmacionCompletarCorte();

    if (!confirmado || !reciboId) {
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/completar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ es_parcial: esParcial }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const rolActual = String(window.USUARIO_ACTUAL?.rol || '').toLowerCase();
                
                // Si es cortador, animar y quitar de la vista (no le interesan los de Costura)
                if (rolActual === 'cortador') {
                    if (card) {
                        card.classList.add('card-animate-remove');
                        setTimeout(() => {
                            card.remove();
                            if (typeof window.actualizarContadorTarjetas === 'function') {
                                window.actualizarContadorTarjetas();
                            }
                            if (typeof window.incrementarContadorCompletados === 'function') {
                                window.incrementarContadorCompletados();
                            }

                        }, 650);
                    }
                    
                    // Cerrar el drawer si está abierto en mobile
                    const drawer = document.querySelector(`#mobile-drawer-${prendaId}`);
                    if (drawer && typeof window.toggleMobileActions === 'function') {
                        // toggleMobileActions usualmente alterna, así que solo si está activo
                        if (drawer.classList.contains('active')) {
                            window.toggleMobileActions(prendaId);
                        }
                    }
                } else {
                    // Si no es cortador (ej. admin o visor), solo actualizar botones
                    actualizarInterfazCorte(card, 'completado', btn);

                    const drawerBtn = document.querySelector(
                        `#mobile-drawer-${prendaId} .btn-completar-corte[data-recibo-id="${reciboId}"]`
                    );
                    if (drawerBtn) {
                        actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'completado', drawerBtn);
                    }
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch((error) => {
            console.error('Error completando corte:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

export function deshacerCorte(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');
    const esParcial = btn.dataset.esParcial === '1';

    if (!reciboId) {
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/deshacer`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ es_parcial: esParcial }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                actualizarInterfazCorte(card, 'deshacer', btn);

                const drawerBtn = document.querySelector(
                    `#mobile-drawer-${prendaId} .btn-deshacer-corte[data-recibo-id="${reciboId}"]`
                );
                if (drawerBtn) {
                    actualizarInterfazCorte(drawerBtn.closest('.mobile-actions-drawer'), 'deshacer', drawerBtn);
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch((error) => {
            console.error('Error deshaciendo corte:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

export async function completarCostura(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');
    const esParcial = btn.dataset.esParcial === '1';
    const confirmado = await mostrarConfirmacionCompletarCostura();

    if (!confirmado || !reciboId) {
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/completar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ es_parcial: esParcial }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                actualizarInterfazCostura(card, 'completado', btn);

                const drawerBtn = document.querySelector(
                    `#mobile-drawer-${prendaId} .btn-completar-costura[data-recibo-id="${reciboId}"]`
                );
                if (drawerBtn) {
                    actualizarInterfazCostura(drawerBtn.closest('.mobile-actions-drawer'), 'completado', drawerBtn);
                }

                asegurarBadgeCompletado(card, true);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch((error) => {
            console.error('Error completando costura:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

export function deshacerCostura(btn) {
    if (!btn || !btn.dataset) {
        console.error('[DESHACER-COSTURA] Botón inválido o sin dataset');
        return;
    }

    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');
    const esParcial = btn.dataset.esParcial === '1';

    if (!reciboId) {
        console.error('[DESHACER-COSTURA] No se encontró reciboId en el botón');
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/deshacer`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ es_parcial: esParcial }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                actualizarInterfazCostura(card, 'deshacer', btn);

                const drawerBtn = document.querySelector(
                    `#mobile-drawer-${prendaId} .btn-deshacer-costura[data-recibo-id="${reciboId}"]`
                );
                if (drawerBtn) {
                    actualizarInterfazCostura(drawerBtn.closest('.mobile-actions-drawer'), 'deshacer', drawerBtn);
                }

                asegurarBadgeCompletado(card, false);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch((error) => {
            console.error('Error deshaciendo costura:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}
