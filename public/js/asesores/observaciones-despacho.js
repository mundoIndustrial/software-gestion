(function () {
    function __csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        return '';
    }

    function __clearBadge(pedidoId) {
        const btn = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${pedidoId}"]`);
        if (!btn) return;
        const badge = btn.querySelector('.obs-despacho-badge');
        if (badge) badge.remove();
    }

    function __renderBadge(pedidoId, count) {
        const btn = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${pedidoId}"]`);
        if (!btn) return;
        __clearBadge(pedidoId);
        if (!count || count <= 0) return;

        const badge = document.createElement('span');
        badge.className = 'obs-despacho-badge';
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.style.cssText = [
            'position:absolute',
            'top:-6px',
            'right:-6px',
            'min-width:18px',
            'height:18px',
            'padding:0 5px',
            'border-radius:999px',
            'background:#ef4444',
            'color:#ffffff',
            'font-size:11px',
            'font-weight:700',
            'line-height:18px',
            'text-align:center',
            'box-shadow:0 2px 6px rgba(0,0,0,0.25)'
        ].join(';');
        btn.appendChild(badge);
    }

    async function refrescarBadgesObservacionesDespachoAsesores() {
        try {
            const rows = Array.from(document.querySelectorAll('[data-pedido-row][data-pedido-id]'));
            const ids = rows
                .map(r => r.getAttribute('data-pedido-id'))
                .filter(Boolean)
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n));

            if (ids.length === 0) return;

            const r = await fetch('/asesores/pedidos/observaciones-despacho/resumen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ pedido_ids: ids }),
                cache: 'no-store',
            });

            const data = await r.json().catch(() => null);
            const map = (data && data.success && data.data) ? data.data : {};

            ids.forEach((pedidoId) => {
                const unread = parseInt(map?.[pedidoId]?.unread ?? '0', 10) || 0;
                __renderBadge(pedidoId, unread);
            });
        } catch (e) {
            console.error('Error refrescando badges de observaciones despacho:', e);
        }
    }

    window.__asesoresObsDespachoCtx = window.__asesoresObsDespachoCtx || { pedidoId: null, pedidoNumero: null };

    async function __fetchObservaciones(pedidoId) {
        const r = await fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __csrfToken(),
            },
            cache: 'no-store',
        });
        const data = await r.json().catch(() => null);
        const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
        return { ok: r.ok, data, items };
    }

    async function abrirModalObservacionesDespachoAsesores(pedidoId, numeroPedido) {
        window.__asesoresObsDespachoCtx = { pedidoId, pedidoNumero: numeroPedido };

        const modal = document.getElementById('modalObservacionesDespachoAsesores');
        const title = document.getElementById('modalObsDespachoAsesoresTitle');
        if (title) title.textContent = `Observaciones despacho - Pedido ${numeroPedido || ''}`;
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';

        const nueva = document.getElementById('observacionesDespachoAsesoresNueva');
        if (nueva) nueva.value = '';

        await cargarObservacionesDespachoAsesores();

        try {
            await fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-leidas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Accept': 'application/json',
                },
            });
        } catch (_) {
            // ignore
        }

        __clearBadge(pedidoId);
        refrescarBadgesObservacionesDespachoAsesores();
    }

    function cerrarModalObservacionesDespachoAsesores() {
        const modal = document.getElementById('modalObservacionesDespachoAsesores');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';

        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        if (pedidoId) {
            __clearBadge(pedidoId);
            refrescarBadgesObservacionesDespachoAsesores();
        }
    }

    async function cargarObservacionesDespachoAsesores() {
        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        const historial = document.getElementById('observacionesDespachoAsesoresHistorial');
        if (!pedidoId || !historial) return;

        historial.innerHTML = '<div class="text-center text-slate-500 py-6">Cargando...</div>';

        try {
            const { ok, items } = await __fetchObservaciones(pedidoId);
            if (!ok) {
                historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar</div>';
                return;
            }

            if (items.length === 0) {
                historial.innerHTML = '<div class="text-center text-slate-500 py-6">No hay observaciones</div>';
                return;
            }

            let html = '<div class="space-y-3">';
            items.forEach(item => {
                const puedeEditar = (String(item.usuario_id) === String(window.__despachoObsUsuarioActualId));
                const botones = puedeEditar ? `
                    <button onclick="editarObservacionDespachoAsesores('${item.id}')" style="border:none;background:#e2e8f0;color:#0f172a;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Editar">✏️</button>
                    <button onclick="eliminarObservacionDespachoAsesores('${item.id}')" style="border:none;background:#fee2e2;color:#991b1b;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Eliminar">🗑️</button>
                ` : '';

                const fecha = item.updated_at || item.created_at || '';

                html += `
                    <div data-obs-id="${item.id}" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:700;color:#0f172a;font-size:13px;">${item.usuario_nombre ?? ''}</div>
                                <div style="color:#64748b;font-size:12px;">${fecha}</div>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;">${botones}</div>
                        </div>
                        <div class="obs-contenido" style="margin-top:6px;color:#1e293b;font-size:13px;white-space:pre-wrap;">${item.contenido ?? ''}</div>
                    </div>
                `;
            });
            html += '</div>';
            historial.innerHTML = html;
        } catch (e) {
            console.error('Error cargando observaciones despacho asesores:', e);
            historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar</div>';
        }
    }

    async function guardarObservacionDespachoAsesores() {
        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        if (!pedidoId) return;

        const textarea = document.getElementById('observacionesDespachoAsesoresNueva');
        const contenido = (textarea?.value || '').trim();
        if (!contenido) {
            alert('Por favor, escribe una observación antes de guardar');
            return;
        }

        const btn = document.getElementById('btnGuardarObservacionDespachoAsesores');
        if (btn) btn.disabled = true;

        try {
            const r = await fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                },
                body: JSON.stringify({ contenido })
            });
            const data = await r.json().catch(() => null);
            if (!r.ok || !data || data.success === false) {
                mostrarNotificacion(data?.message || 'No se pudo guardar la observación', 'error');
                return;
            }

            mostrarNotificacion('Observación guardada exitosamente', 'success');
            if (textarea) textarea.value = '';
            await cargarObservacionesDespachoAsesores();
            cerrarModalObservacionesDespachoAsesores();
        } catch (e) {
            console.error('Error guardando observación despacho asesores:', e);
            mostrarNotificacion('Error al guardar la observación', 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function editarObservacionDespachoAsesores(observacionId) {
        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        if (!pedidoId) return;

        const card = document.querySelector(`#modalObservacionesDespachoAsesores [data-obs-id="${observacionId}"]`);
        if (!card) return;
        if (card.getAttribute('data-editing') === '1') return;

        const contentEl = card.querySelector('.obs-contenido');
        if (!contentEl) return;

        const original = (contentEl.textContent ?? '').toString();
        card.setAttribute('data-editing', '1');
        card.setAttribute('data-original', original);

        const textarea = document.createElement('textarea');
        textarea.value = original;
        textarea.rows = 3;
        textarea.style.width = '100%';
        textarea.style.minHeight = '60px';
        textarea.style.resize = 'vertical';
        textarea.style.border = '1px solid #cbd5e1';
        textarea.style.borderRadius = '8px';
        textarea.style.padding = '8px';
        textarea.style.fontSize = '13px';
        textarea.style.color = '#0f172a';
        textarea.style.background = '#ffffff';

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.gap = '8px';
        actions.style.marginTop = '8px';
        actions.style.justifyContent = 'flex-end';

        const btnCancelar = document.createElement('button');
        btnCancelar.type = 'button';
        btnCancelar.textContent = 'Cancelar';
        btnCancelar.style.border = 'none';
        btnCancelar.style.background = '#e2e8f0';
        btnCancelar.style.color = '#0f172a';
        btnCancelar.style.borderRadius = '8px';
        btnCancelar.style.padding = '6px 10px';
        btnCancelar.style.cursor = 'pointer';
        btnCancelar.style.fontSize = '12px';

        const btnGuardar = document.createElement('button');
        btnGuardar.type = 'button';
        btnGuardar.textContent = 'Guardar';
        btnGuardar.style.border = 'none';
        btnGuardar.style.background = '#0ea5e9';
        btnGuardar.style.color = '#ffffff';
        btnGuardar.style.borderRadius = '8px';
        btnGuardar.style.padding = '6px 10px';
        btnGuardar.style.cursor = 'pointer';
        btnGuardar.style.fontSize = '12px';

        actions.appendChild(btnCancelar);
        actions.appendChild(btnGuardar);

        contentEl.replaceWith(textarea);
        textarea.insertAdjacentElement('afterend', actions);

        const cancelar = () => {
            const restored = document.createElement('div');
            restored.className = 'obs-contenido';
            restored.style.marginTop = '6px';
            restored.style.color = '#1e293b';
            restored.style.fontSize = '13px';
            restored.style.whiteSpace = 'pre-wrap';
            restored.textContent = card.getAttribute('data-original') || '';
            actions.remove();
            textarea.replaceWith(restored);
            card.setAttribute('data-editing', '0');
        };
        btnCancelar.addEventListener('click', cancelar);

        btnGuardar.addEventListener('click', async () => {
            const contenido = (textarea.value || '').trim();
            if (!contenido) {
                alert('La observación no puede estar vacía');
                textarea.focus();
                return;
            }

            btnGuardar.disabled = true;
            btnCancelar.disabled = true;

            try {
                const r = await fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho/${observacionId}/actualizar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': __csrfToken(),
                    },
                    body: JSON.stringify({ contenido })
                });
                const data = await r.json().catch(() => null);
                if (!r.ok || !data || data.success === false) {
                    alert('Error: ' + (data?.message || 'No se pudo actualizar la observación'));
                    btnGuardar.disabled = false;
                    btnCancelar.disabled = false;
                    return;
                }

                await cargarObservacionesDespachoAsesores();
            } catch (e) {
                console.error('Error actualizando observación despacho asesores:', e);
                alert('Error al actualizar la observación');
                btnGuardar.disabled = false;
                btnCancelar.disabled = false;
            }
        });
    }

    // ==================== MODALES PERSONALIZADOS ====================
    function mostrarModalConfirmacion(titulo, mensaje, onConfirmar, onCancelar) {
        const modalId = 'modalConfirmacionAsesores';
        let modal = document.getElementById(modalId);
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999; font-family: system-ui, -apple-system, sans-serif;
        `;

        modal.innerHTML = `
            <div style="
                background: #ffffff; border-radius: 16px; padding: 28px 32px;
                max-width: 400px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
                animation: modalSlideIn 0.2s ease-out;
            ">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="
                        width: 44px; height: 44px; border-radius: 12px;
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        display: flex; align-items: center; justify-content: center;
                    ">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">${titulo}</h3>
                </div>
                <p style="margin: 0 0 24px 0; font-size: 14px; color: #475569; line-height: 1.6; padding-left: 56px;">${mensaje}</p>
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding-left: 56px;">
                    <button id="btnCancelarModal" style="
                        padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 10px;
                        background: #f8fafc; color: #475569; font-size: 14px; font-weight: 500;
                        cursor: pointer; transition: all 0.15s;
                    ">Cancelar</button>
                    <button id="btnConfirmarModal" style="
                        padding: 10px 18px; border: none; border-radius: 10px;
                        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                        color: #fff; font-size: 14px; font-weight: 500;
                        cursor: pointer; transition: all 0.15s; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
                    ">Eliminar</button>
                </div>
            </div>
            <style>@keyframes modalSlideIn { from { opacity: 0; transform: scale(0.95) translateY(-10px); } to { opacity: 1; transform: scale(1) translateY(0); } }</style>
        `;

        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
                if (onCancelar) onCancelar();
            }
        });

        document.getElementById('btnCancelarModal').addEventListener('click', () => {
            modal.remove();
            if (onCancelar) onCancelar();
        });

        document.getElementById('btnConfirmarModal').addEventListener('click', () => {
            modal.remove();
            if (onConfirmar) onConfirmar();
        });
    }

    function mostrarNotificacion(mensaje, tipo = 'success', duracion = 3000) {
        const notifId = 'notificacionAsesores' + Date.now();
        const notif = document.createElement('div');
        notif.id = notifId;

        const colores = {
            success: { bg: '#10b981', icon: '#22c55e', shadow: 'rgba(16, 185, 129, 0.3)' },
            error: { bg: '#dc2626', icon: '#ef4444', shadow: 'rgba(220, 38, 38, 0.3)' },
            warning: { bg: '#f59e0b', icon: '#fbbf24', shadow: 'rgba(245, 158, 11, 0.3)' }
        };
        const c = colores[tipo] || colores.success;

        const iconos = {
            success: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
            error: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
            warning: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`
        };

        notif.style.cssText = `
            position: fixed; top: 24px; right: 24px;
            background: white; border-radius: 12px; padding: 16px 20px;
            box-shadow: 0 20px 25px -5px ${c.shadow}, 0 10px 10px -5px rgba(0,0,0,0.04);
            display: flex; align-items: center; gap: 14px;
            z-index: 10000; min-width: 300px; max-width: 400px;
            border-left: 4px solid ${c.icon};
            animation: notifSlideIn 0.3s ease-out;
            font-family: system-ui, -apple-system, sans-serif;
        `;

        notif.innerHTML = `
            <div style="
                width: 36px; height: 36px; border-radius: 10px;
                background: ${c.bg}; display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            ">${iconos[tipo]}</div>
            <div style="flex: 1;">
                <p style="margin: 0; font-size: 14px; font-weight: 500; color: #0f172a;">${tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Atención'}</p>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">${mensaje}</p>
            </div>
            <button id="btnCerrarNotif" style="
                background: none; border: none; padding: 4px; cursor: pointer;
                color: #94a3b8; transition: color 0.15s;
            ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
            <style>@keyframes notifSlideIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }</style>
        `;

        document.body.appendChild(notif);

        const cerrar = () => {
            notif.style.animation = 'notifSlideIn 0.2s ease-out reverse';
            setTimeout(() => notif.remove(), 200);
        };

        document.getElementById('btnCerrarNotif').addEventListener('click', cerrar);
        setTimeout(cerrar, duracion);
    }

    async function eliminarObservacionDespachoAsesores(observacionId) {
        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        if (!pedidoId) return;

        mostrarModalConfirmacion(
            'Eliminar observación',
            '¿Estás seguro de que deseas eliminar esta observación? Esta acción no se puede deshacer.',
            async () => {
                try {
                    const r = await fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho/${observacionId}/eliminar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': __csrfToken(),
                        },
                    });
                    const data = await r.json().catch(() => null);
                    if (!r.ok || !data || data.success === false) {
                        mostrarNotificacion(data?.message || 'No se pudo eliminar la observación', 'error');
                        return;
                    }
                    mostrarNotificacion('Observación eliminada correctamente', 'success');
                    await cargarObservacionesDespachoAsesores();
                } catch (e) {
                    console.error('Error eliminando observación despacho asesores:', e);
                    mostrarNotificacion('Error al eliminar la observación', 'error');
                }
            }
        );
    }

    window.abrirModalObservacionesDespachoAsesores = abrirModalObservacionesDespachoAsesores;
    window.cerrarModalObservacionesDespachoAsesores = cerrarModalObservacionesDespachoAsesores;
    window.guardarObservacionDespachoAsesores = guardarObservacionDespachoAsesores;
    window.editarObservacionDespachoAsesores = editarObservacionDespachoAsesores;
    window.eliminarObservacionDespachoAsesores = eliminarObservacionDespachoAsesores;

    // ==================== WEBSOCKET / REALTIME ====================
    function setupObservacionesRealtimeAsesores() {
        if (!window.EchoInstance) {
            console.warn('[Asesores] EchoInstance no disponible, reintentando en 2s...');
            setTimeout(setupObservacionesRealtimeAsesores, 2000);
            return;
        }

        // Escuchar canal general de asesores
        window.EchoInstance.channel('asesores.observaciones')
            .listen('.observacion.despacho', (e) => {
                console.log('[Asesores] Evento recibido:', e);
                const pedidoId = e?.pedido_id;
                if (!pedidoId) return;

                // Actualizar badge para el pedido afectado
                __renderBadge(pedidoId, 1);

                // Si el modal está abierto para este pedido, recargar observaciones
                const currentPedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
                if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                    cargarObservacionesDespachoAsesores();
                    // Marcar como leídas automáticamente
                    fetch(`/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-leidas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': __csrfToken(),
                            'Accept': 'application/json',
                        },
                    }).catch(() => {});
                    __clearBadge(pedidoId);
                }

                // Refrescar todos los badges
                setTimeout(refrescarBadgesObservacionesDespachoAsesores, 500);
            });

        // Escuchar canales específicos de cada pedido visible
        const rows = document.querySelectorAll('[data-pedido-id]');
        rows.forEach(row => {
            const pedidoId = row.getAttribute('data-pedido-id');
            if (!pedidoId) return;

            window.EchoInstance.channel(`pedido.${pedidoId}`)
                .listen('.observacion.despacho', (e) => {
                    console.log(`[Asesores] Evento en pedido ${pedidoId}:`, e);

                    // Actualizar badge
                    refrescarBadgesObservacionesDespachoAsesores();

                    // Si el modal está abierto, recargar
                    const currentPedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
                    if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                        cargarObservacionesDespachoAsesores();
                    }
                });
        });

        console.log('[Asesores] WebSocket configurado para observaciones');
    }

    document.addEventListener('DOMContentLoaded', () => {
        refrescarBadgesObservacionesDespachoAsesores();
        setInterval(refrescarBadgesObservacionesDespachoAsesores, 30000);

        // Setup WebSocket para tiempo real
        setupObservacionesRealtimeAsesores();
    });

    window.refrescarBadgesObservacionesDespachoAsesores = refrescarBadgesObservacionesDespachoAsesores;
})();
