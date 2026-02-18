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
                alert('Error: ' + (data?.message || 'No se pudo guardar la observación'));
                return;
            }

            if (textarea) textarea.value = '';
            await cargarObservacionesDespachoAsesores();
            cerrarModalObservacionesDespachoAsesores();
        } catch (e) {
            console.error('Error guardando observación despacho asesores:', e);
            alert('Error al guardar la observación');
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

    async function eliminarObservacionDespachoAsesores(observacionId) {
        const pedidoId = window.__asesoresObsDespachoCtx?.pedidoId;
        if (!pedidoId) return;
        if (!confirm('¿Eliminar esta observación?')) return;

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
                alert('Error: ' + (data?.message || 'No se pudo eliminar la observación'));
                return;
            }

            await cargarObservacionesDespachoAsesores();
        } catch (e) {
            console.error('Error eliminando observación despacho asesores:', e);
            alert('Error al eliminar la observación');
        }
    }

    window.abrirModalObservacionesDespachoAsesores = abrirModalObservacionesDespachoAsesores;
    window.cerrarModalObservacionesDespachoAsesores = cerrarModalObservacionesDespachoAsesores;
    window.guardarObservacionDespachoAsesores = guardarObservacionDespachoAsesores;
    window.editarObservacionDespachoAsesores = editarObservacionDespachoAsesores;
    window.eliminarObservacionDespachoAsesores = eliminarObservacionDespachoAsesores;

    document.addEventListener('DOMContentLoaded', () => {
        refrescarBadgesObservacionesDespachoAsesores();
        setInterval(refrescarBadgesObservacionesDespachoAsesores, 30000);
    });

    window.refrescarBadgesObservacionesDespachoAsesores = refrescarBadgesObservacionesDespachoAsesores;
})();
