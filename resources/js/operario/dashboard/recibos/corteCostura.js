import { httpJson } from '../api/http';
import { asegurarBadgeCompletado } from '../ui/badges';

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

export function completarCorte(btn) {
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

export function completarCostura(btn) {
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

