(function () {
    function __despachoObsCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        return '';
    }

    window.__despachoObsCtx = window.__despachoObsCtx || { pedidoId: null };

    // ==================== BADGE FUNCTIONS ====================
    function __clearBadge(pedidoId) {
        const btn = document.querySelector(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);
        if (!btn) return;
        const badge = btn.querySelector('.obs-despacho-badge');
        if (badge) badge.remove();
    }

    function __renderBadge(pedidoId, count) {
        const btn = document.querySelector(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);
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

    async function refrescarBadgesObservacionesDespacho() {
        try {
            const rows = Array.from(document.querySelectorAll('tr[data-pedido-id]'));
            const ids = rows
                .map(r => r.getAttribute('data-pedido-id'))
                .filter(Boolean)
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n));

            if (ids.length === 0) return;

            const r = await fetch('/despacho/observaciones/resumen', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __despachoObsCsrfToken(),
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

    // Clear badge when modal opens
    function clearBadgeOnOpen(pedidoId) {
        __clearBadge(pedidoId);
    }
    // =========================================================

    async function __fetchObservacionesDespacho(pedidoId) {
        const r = await fetch(`/despacho/${pedidoId}/observaciones`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __despachoObsCsrfToken(),
            },
            cache: 'no-store',
        });
        const data = await r.json().catch(() => null);
        const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
        return { ok: r.ok, data, items };
    }

    function __renderPreviewObservaciones(pedidoId, items) {
        const el = document.querySelector(`.despacho-observaciones-preview[data-pedido-id="${pedidoId}"]`);
        if (!el) return;
        const text = items.map(i => `${i.usuario_nombre ?? ''} - ${i.contenido ?? ''}`).join('\n');
        el.value = text;
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight) + 'px';
    }

    async function cargarPreviewObservacionesDespacho(pedidoId) {
        try {
            const { ok, items } = await __fetchObservacionesDespacho(pedidoId);
            if (!ok) return;
            __renderPreviewObservaciones(pedidoId, items);
        } catch (e) {
            console.error('Error cargando preview observaciones despacho:', e);
        }
    }

    async function cargarObservacionesDespachoIndex() {
        const pedidoId = window.__despachoObsCtx?.pedidoId;
        const historial = document.getElementById('observacionesDespachoIndexHistorial');
        if (!pedidoId || !historial) return;

        historial.innerHTML = '<div class="text-center text-slate-500 py-6">Cargando...</div>';

        try {
            const { ok, items } = await __fetchObservacionesDespacho(pedidoId);
            if (!ok) {
                historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar</div>';
                return;
            }

            __renderPreviewObservaciones(pedidoId, items);

            if (items.length === 0) {
                historial.innerHTML = '<div class="text-center text-slate-500 py-6">No hay observaciones</div>';
                return;
            }

            let html = '<div class="space-y-3">';
            items.forEach(item => {
                const puedeEditar = (String(item.usuario_id) === String(window.__despachoObsUsuarioActualId));
                const botones = puedeEditar ? `
                    <button onclick="editarObservacionDespachoIndex('${item.id}')" style="border:none;background:#e2e8f0;color:#0f172a;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Editar">✏️</button>
                    <button onclick="eliminarObservacionDespachoIndex('${item.id}')" style="border:none;background:#fee2e2;color:#991b1b;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Eliminar">🗑️</button>
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
            console.error('Error cargando observaciones despacho index:', e);
            historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar</div>';
        }
    }

    async function abrirModalObservacionesDespachoIndex(pedidoId, numeroPedido) {
        window.__despachoObsCtx = { pedidoId };
        const modal = document.getElementById('modalObservacionesDespachoIndex');
        const title = document.getElementById('modalObsDespachoTitle');
        if (title) title.textContent = `Observaciones - Pedido ${numeroPedido || ''}`;
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';

        const nueva = document.getElementById('observacionesDespachoIndexNueva');
        if (nueva) nueva.value = '';

        await cargarObservacionesDespachoIndex();

        // Marcar como leídas y limpiar badge
        try {
            await fetch(`/despacho/${pedidoId}/observaciones/marcar-leidas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __despachoObsCsrfToken(),
                    'Accept': 'application/json',
                },
            });
        } catch (_) {
            // ignore
        }
        __clearBadge(pedidoId);
        refrescarBadgesObservacionesDespacho();
    }

    function cerrarModalObservacionesDespachoIndex() {
        const modal = document.getElementById('modalObservacionesDespachoIndex');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';
    }

    async function guardarObservacionDespachoIndex() {
        const pedidoId = window.__despachoObsCtx?.pedidoId;
        if (!pedidoId) return;
        const textarea = document.getElementById('observacionesDespachoIndexNueva');
        const contenido = (textarea?.value || '').trim();
        if (!contenido) {
            alert('Por favor, escribe una observación antes de guardar');
            return;
        }
        const btn = document.getElementById('btnGuardarObservacionDespachoIndex');
        if (btn) btn.disabled = true;
        try {
            const r = await fetch(`/despacho/${pedidoId}/observaciones/guardar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __despachoObsCsrfToken(),
                },
                body: JSON.stringify({ contenido })
            });
            const data = await r.json().catch(() => null);
            if (!r.ok || !data || data.success === false) {
                alert('Error: ' + (data?.message || 'No se pudo guardar la observación'));
                return;
            }
            if (textarea) textarea.value = '';
            await cargarObservacionesDespachoIndex();
            cerrarModalObservacionesDespachoIndex();
        } catch (e) {
            console.error('Error guardando observación despacho index:', e);
            alert('Error al guardar la observación');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function editarObservacionDespachoIndex(observacionId) {
        const pedidoId = window.__despachoObsCtx?.pedidoId;
        if (!pedidoId) return;
        const card = document.querySelector(`#modalObservacionesDespachoIndex [data-obs-id="${observacionId}"]`);
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
                const r = await fetch(`/despacho/${pedidoId}/observaciones/${observacionId}/actualizar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': __despachoObsCsrfToken(),
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
                await cargarObservacionesDespachoIndex();
            } catch (e) {
                console.error('Error actualizando observación despacho index:', e);
                alert('Error al actualizar la observación');
                btnGuardar.disabled = false;
                btnCancelar.disabled = false;
            }
        });
    }

    async function eliminarObservacionDespachoIndex(observacionId) {
        const pedidoId = window.__despachoObsCtx?.pedidoId;
        if (!pedidoId) return;
        if (!confirm('¿Eliminar esta observación?')) return;
        try {
            const r = await fetch(`/despacho/${pedidoId}/observaciones/${observacionId}/eliminar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __despachoObsCsrfToken(),
                },
            });
            const data = await r.json().catch(() => null);
            if (!r.ok || !data || data.success === false) {
                alert('Error: ' + (data?.message || 'No se pudo eliminar la observación'));
                return;
            }
            await cargarObservacionesDespachoIndex();
        } catch (e) {
            console.error('Error eliminando observación despacho index:', e);
            alert('Error al eliminar la observación');
        }
    }

    window.abrirModalObservacionesDespachoIndex = abrirModalObservacionesDespachoIndex;
    window.cerrarModalObservacionesDespachoIndex = cerrarModalObservacionesDespachoIndex;
    window.guardarObservacionDespachoIndex = guardarObservacionDespachoIndex;
    window.editarObservacionDespachoIndex = editarObservacionDespachoIndex;
    window.eliminarObservacionDespachoIndex = eliminarObservacionDespachoIndex;

    // ==================== WEBSOCKET / REALTIME ====================
    function setupObservacionesRealtime() {
        if (!window.Echo) {
            console.warn('[Despacho] Echo no disponible, reintentando en 2s...');
            setTimeout(setupObservacionesRealtime, 2000);
            return;
        }

        // Escuchar canal general de despacho
        window.Echo.channel('despacho.observaciones')
            .listen('.observacion.despacho', (e) => {
                console.log('[Despacho] Evento recibido:', e);
                const pedidoId = e?.pedido_id;
                if (!pedidoId) return;

                // Actualizar badge para el pedido afectado
                __renderBadge(pedidoId, 1); // Mostrar al menos 1

                // Si el modal está abierto para este pedido, recargar observaciones
                const currentPedidoId = window.__despachoObsCtx?.pedidoId;
                if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                    cargarObservacionesDespachoIndex();
                    // Marcar como leídas automáticamente
                    fetch(`/despacho/${pedidoId}/observaciones/marcar-leidas`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': __despachoObsCsrfToken(),
                            'Accept': 'application/json',
                        },
                    }).catch(() => {});
                    __clearBadge(pedidoId);
                }

                // Recargar el preview del textarea
                cargarPreviewObservacionesDespacho(pedidoId);
            });

        // Escuchar canales específicos de cada pedido visible en la página
        const rows = document.querySelectorAll('tr[data-pedido-id]');
        rows.forEach(row => {
            const pedidoId = row.getAttribute('data-pedido-id');
            if (!pedidoId) return;

            window.Echo.channel(`pedido.${pedidoId}`)
                .listen('.observacion.despacho', (e) => {
                    console.log(`[Despacho] Evento en pedido ${pedidoId}:`, e);

                    // Actualizar badge
                    refrescarBadgesObservacionesDespacho();

                    // Si el modal está abierto, recargar
                    const currentPedidoId = window.__despachoObsCtx?.pedidoId;
                    if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                        cargarObservacionesDespachoIndex();
                    }

                    // Recargar preview
                    cargarPreviewObservacionesDespacho(pedidoId);
                });
        });

        console.log('[Despacho] WebSocket configurado para observaciones');
    }

    document.addEventListener('DOMContentLoaded', () => {
        try {
            const rows = Array.from(document.querySelectorAll('tr[data-pedido-id]'));
            const ids = rows.map(r => r.getAttribute('data-pedido-id')).filter(Boolean);
            const ejecutar = async () => {
                for (const id of ids) {
                    await cargarPreviewObservacionesDespacho(id);
                    await new Promise(r => setTimeout(r, 25));
                }
                // Refresh badges after loading previews
                await refrescarBadgesObservacionesDespacho();

                // Setup WebSocket para tiempo real
                setupObservacionesRealtime();
            };
            ejecutar();
        } catch (e) {
            console.error('Error inicializando previews de observaciones despacho:', e);
        }
    });
})();
