(function () {
    function __csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        return '';
    }

    window.__asesoresBadgeTotals = window.__asesoresBadgeTotals || { obs: {}, entregas: {} };

    function __renderTotalBadgeOnEyeButton(pedidoId) {
        const totals = window.__asesoresBadgeTotals || { obs: {}, entregas: {} };
        const obs = parseInt(totals.obs?.[pedidoId] ?? '0', 10) || 0;
        const entregas = parseInt(totals.entregas?.[pedidoId] ?? '0', 10) || 0;
        const total = obs + entregas;

        const btn = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${pedidoId}"]`);
        if (!btn) return;

        btn.querySelectorAll('.total-notif-badge').forEach((b) => b.remove());
        if (total <= 0) return;

        if (btn.style.position !== 'relative') {
            btn.style.position = 'relative';
        }

        const badge = document.createElement('span');
        badge.className = 'total-notif-badge';
        badge.textContent = total > 99 ? '99+' : String(total);
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

    function __setTotalPart(pedidoId, kind, count) {
        if (!window.__asesoresBadgeTotals) {
            window.__asesoresBadgeTotals = { obs: {}, entregas: {} };
        }
        if (!window.__asesoresBadgeTotals[kind]) {
            window.__asesoresBadgeTotals[kind] = {};
        }
        window.__asesoresBadgeTotals[kind][pedidoId] = parseInt(count ?? '0', 10) || 0;
        __renderTotalBadgeOnEyeButton(pedidoId);
    }

    window.__setTotalBadgePartAsesores = __setTotalPart;

    // ==================== BADGE PERSISTENCE ====================
    // Clave para localStorage
    const BADGE_STORAGE_KEY = 'obs_despacho_badges_seen';
    
    // Obtener badges marcados como vistos desde localStorage
    function getSeenBadges() {
        try {
            const data = localStorage.getItem(BADGE_STORAGE_KEY);
            return data ? JSON.parse(data) : {};
        } catch (e) {
            return {};
        }
    }
    
    // Guardar badge como visto en localStorage
    function setBadgeSeen(pedidoId) {
        try {
            const seen = getSeenBadges();
            seen[pedidoId] = Date.now();
            localStorage.setItem(BADGE_STORAGE_KEY, JSON.stringify(seen));
        } catch (e) {
        }
    }
    
    // Verificar si el badge fue visto recientemente (dentro de las últimas 24 horas)
    function isBadgeRecentlySeen(pedidoId) {
        const seen = getSeenBadges();
        const seenTime = seen[pedidoId];
        
        if (!seenTime) {
            return false;
        }
        
        // Considerar como visto si fue marcado en las últimas 24 horas
        const twentyFourHours = 24 * 60 * 60 * 1000;
        const isRecent = (Date.now() - seenTime) < twentyFourHours;
        return isRecent;
    }

    function __clearBadge(pedidoId) {
        const targets = document.querySelectorAll(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);
        targets.forEach((btn) => {
            btn.querySelectorAll('.obs-despacho-badge').forEach((badge) => badge.remove());
        });
    }

    function __renderBadge(pedidoId, count) {
        // Lógica principal: Si hay observaciones no leídas, mostrar badge
        // El localStorage solo se usa para persistencia cuando el usuario hace clic
        if (count > 0) {
        } else {
            // Si no hay observaciones no leídas, verificar localStorage
            const wasRecentlySeen = isBadgeRecentlySeen(pedidoId);

            if (wasRecentlySeen) {
                __clearBadge(pedidoId);
                return;
            }
        }

        const targets = document.querySelectorAll(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);

        if (!targets.length) {
            return;
        }

        __clearBadge(pedidoId);

        if (!count || count <= 0) {
            return;
        }

        targets.forEach((btn) => {
            // Asegura anclaje correcto del badge al botón
            if (btn.style.position !== 'relative') {
                btn.style.position = 'relative';
            }

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
        });
    }

    async function refrescarBadgesObservacionesDespachoAsesores() {
        try {
            const rows = Array.from(document.querySelectorAll('[data-pedido-row][data-pedido-id]'));
            
            const ids = rows
                .map(r => r.getAttribute('data-pedido-id'))
                .filter(Boolean)
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n));

            if (ids.length === 0) {
                return;
            }

            const r = await fetch('/api/asesores/pedidos/observaciones-despacho/resumen', {
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
                __setTotalPart(pedidoId, 'obs', unread);
            });
        } catch (e) {
        }
    }

    window.__asesoresObsDespachoCtx = window.__asesoresObsDespachoCtx || { pedidoId: null, pedidoNumero: null };

    async function __fetchObservaciones(pedidoId) {
        const r = await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho`, {
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

        // Marcar notas de bodega como vistas al abrir el modal
        try {
            await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-bodega-vistas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
                cache: 'no-store',
            });

            // Refrescar badges para que desaparezcan si ya se vieron las notas
            refrescarBadgesObservacionesDespachoAsesores();
        } catch (e) {
        }
    }

    function cerrarModalObservacionesDespachoAsesores() {
        const modal = document.getElementById('modalObservacionesDespachoAsesores');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';
    }

    // Marcar notificaciones como vistas cuando el usuario hace clic
    async function marcarNotificacionesComoVistas(pedidoId) {
        try {
            // Detectar si estamos en despacho o asesores
            const isDespachoPage = window.location.pathname.includes('/despacho');
            const url = isDespachoPage 
                ? `/despacho/${pedidoId}/observaciones/marcar-vistas`
                : `/api/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-leidas`;
            
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
                cache: 'no-store',
            });

            const data = await r.json().catch(() => null);
            
            if (data && data.success) {
                // Ahora sí limpiar el badge
                __clearBadge(pedidoId);
                // Refrescar para confirmar
                refrescarBadgesObservacionesDespachoAsesores();
            }
        } catch (e) {
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
                const source = (item.source || 'despacho');
                const esEditable = source === 'despacho';
                const puedeEditar = esEditable && (String(item.usuario_id) === String(window.__despachoObsUsuarioActualId));
                const botones = puedeEditar ? `
                    <button onclick="editarObservacionDespachoAsesores('${item.id}')" style="border:none;background:#e2e8f0;color:#0f172a;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Editar"></button>
                    <button onclick="eliminarObservacionDespachoAsesores('${item.id}')" style="border:none;background:#fee2e2;color:#991b1b;border-radius:6px;padding:4px  8px;cursor:pointer;font-size:12px;" title="Eliminar"></button>
                ` : '';

                const fecha = item.updated_at || item.created_at || '';
                const rol = item.usuario_rol ? ` <span style="color:#64748b;font-weight:600;">(${item.usuario_rol})</span>` : '';
                const talla = item.talla ? ` <span style="background:#eef2ff;color:#3730a3;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:700;">Talla: ${item.talla}</span>` : '';
                const badgeOrigen = source === 'bodega'
                    ? '<span style="background:#fef3c7;color:#92400e;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:700;">Bodega</span>'
                    : '<span style="background:#dbeafe;color:#1e40af;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:700;">Despacho</span>';

                html += `
                    <div data-obs-id="${item.id}" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:700;color:#0f172a;font-size:13px;">${item.usuario_nombre ?? ''}${rol}</div>
                                <div style="color:#64748b;font-size:12px;">${fecha}</div>
                                <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">${badgeOrigen}${talla}</div>
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
            const r = await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho`, {
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
                const r = await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho/${observacionId}`, {
                    method: 'PUT',
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
            z-index: 1000000; font-family: system-ui, -apple-system, sans-serif;
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
            z-index: 1000001; min-width: 300px; max-width: 400px;
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
                    const r = await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho/${observacionId}`, {
                        method: 'DELETE',
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
    
    // Hacer funciones de localStorage disponibles globalmente
    window.getSeenBadges = getSeenBadges;
    window.setBadgeSeen = setBadgeSeen;
    window.isBadgeRecentlySeen = isBadgeRecentlySeen;

    // ==================== WEBSOCKET / REALTIME ====================
    function setupObservacionesRealtimeAsesores() {
        if (!window.EchoInstance) {
            setTimeout(setupObservacionesRealtimeAsesores, 2000);
            return;
        }

        // Escuchar canal general de asesores
        window.EchoInstance.channel('asesores.observaciones')
            .listen('.observacion.despacho', (e) => {
                const pedidoId = e?.pedido_id;
                if (!pedidoId) return;

                // Actualizar badge para el pedido afectado
                __renderBadge(pedidoId, 1);

                // Si el modal está abierto para este pedido, recargar observaciones
                const currentPedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
                if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                    cargarObservacionesDespachoAsesores();
                    // Marcar como leídas automáticamente
                    fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-leidas`, {
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

        // Escuchar notas de bodega (para refrescar badges en tiempo real)
        window.EchoInstance.channel('asesores.observaciones')
            .listen('.bodega.nota', async (e) => {
                const pedidoId = e?.pedido_id;
                if (!pedidoId) return;

                // Refrescar badges (si no has abierto el modal, debe aparecer)
                refrescarBadgesObservacionesDespachoAsesores();

                // Si el modal está abierto para este pedido, recargar y marcar vistas
                const currentPedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
                if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                    await cargarObservacionesDespachoAsesores();
                    try {
                        await fetch(`/api/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-bodega-vistas`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': __csrfToken(),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({}),
                            cache: 'no-store',
                        });
                        refrescarBadgesObservacionesDespachoAsesores();
                    } catch (err) {
                    }
                }
            });

        // Escuchar canales específicos de cada pedido visible
        const rows = document.querySelectorAll('[data-pedido-id]');
        rows.forEach(row => {
            const pedidoId = row.getAttribute('data-pedido-id');
            if (!pedidoId) return;

            window.EchoInstance.channel(`pedido.${pedidoId}`)
                .listen('.observacion.despacho', (e) => {
                    // Actualizar badge
                    refrescarBadgesObservacionesDespachoAsesores();

                    // Si el modal está abierto, recargar
                    const currentPedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
                    if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                        cargarObservacionesDespachoAsesores();
                    }
                });
        });
    }

    function initObservacionesDespacho() {
        if (window.__observacionesDespachoInitialized) {
            return;
        }
        window.__observacionesDespachoInitialized = true;

        refrescarBadgesObservacionesDespachoAsesores();

        // Setup WebSocket para tiempo real
        setupObservacionesRealtimeAsesores();

        // Refresco por eventos de ciclo de vida (sin polling)
        window.addEventListener('focus', refrescarBadgesObservacionesDespachoAsesores);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                refrescarBadgesObservacionesDespachoAsesores();
            }
        });
        window.addEventListener('asesores:pedido-actualizado', refrescarBadgesObservacionesDespachoAsesores);

        // ==================== EVENT LISTENER PARA BOTONES DE OBSERVACIONES ====================
        // Marcar como visto solo cuando el usuario hace clic en el botón 💬 o dropdown
        document.addEventListener('click', function(e) {
            // Buscar botón directo 💬
            const botonObs = e.target.closest('.despacho-obs-btn[data-pedido-id]');
            if (botonObs) {
                const pedidoId = botonObs.getAttribute('data-pedido-id');
                console.log(`[Asesores] Usuario hizo clic en botón de observaciones - Pedido ${pedidoId}`);
                
                // Marcar como visto en localStorage inmediatamente
                setBadgeSeen(pedidoId);
                
                // También marcar como visto en el backend
                marcarNotificacionesComoVistas(pedidoId);
                
                return;
            }
            
            // Buscar opción en dropdown
            const opcionDropdown = e.target.closest('[onclick*="abrirModalObservacionesDespachoAsesores"]');
            if (opcionDropdown) {
                // Extraer pedidoId del onclick
                const onclickStr = opcionDropdown.getAttribute('onclick') || '';
                const match = onclickStr.match(/abrirModalObservacionesDespachoAsesores\((\d+)/);
                if (match) {
                    const pedidoId = match[1];
                    
                    // Marcar como visto en localStorage inmediatamente
                    setBadgeSeen(pedidoId);
                    
                    // También marcar como visto en el backend
                    marcarNotificacionesComoVistas(pedidoId);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initObservacionesDespacho);
    } else {
        initObservacionesDespacho();
    }

    window.refrescarBadgesObservacionesDespachoAsesores = refrescarBadgesObservacionesDespachoAsesores;
})();

