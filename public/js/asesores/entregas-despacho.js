(function () {
    window.__asesoresEntregasResumenCache = window.__asesoresEntregasResumenCache || {};

    function __notifyNotificationsRefresh() {
        window.dispatchEvent(new CustomEvent('asesores:notificaciones:refrescar'));
    }

    function __displayTalla(value) {
        const talla = (value || '').toString().trim();
        if (!talla) return '-';
        // Ocultar hashes técnicos (ej: md5 de 32 chars hex)
        if (/^[a-f0-9]{32}$/i.test(talla)) return '-';
        return talla;
    }

    function __formatDateTime(value) {
        if (!value) return '-';
        const normalized = String(value).replace(' ', 'T');
        const dt = new Date(normalized);
        if (Number.isNaN(dt.getTime())) return String(value);
        return dt.toLocaleString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    }

    function __csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        return '';
    }

    function __clearBadge(pedidoId) {
        const targets = document.querySelectorAll(`.entregas-despacho-btn[data-pedido-id="${pedidoId}"]`);
        targets.forEach((btn) => {
            btn.querySelectorAll('.entregas-despacho-badge').forEach((badge) => badge.remove());
        });
    }

    function __renderBadge(pedidoId, count) {
        const targets = document.querySelectorAll(`.entregas-despacho-btn[data-pedido-id="${pedidoId}"]`);
        if (!targets.length) return;

        __clearBadge(pedidoId);
        if (!count || count <= 0) return;

        targets.forEach((btn) => {
            if (btn.style.position !== 'relative') {
                btn.style.position = 'relative';
            }

            const badge = document.createElement('span');
            badge.className = 'entregas-despacho-badge';
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.cssText = [
                'position:absolute',
                'top:-6px',
                'right:-6px',
                'min-width:18px',
                'height:18px',
                'padding:0 5px',
                'border-radius:999px',
                'background:#2563eb',
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

    async function refrescarBadgesEntregasDespachoAsesores() {
        try {
            const rows = Array.from(document.querySelectorAll('[data-pedido-row][data-pedido-id]'));
            const ids = rows
                .map((r) => r.getAttribute('data-pedido-id'))
                .filter(Boolean)
                .map((v) => parseInt(v, 10))
                .filter((n) => Number.isFinite(n));

            if (!ids.length) return;

            const r = await fetch('/api/asesores/pedidos/entregas-despacho/resumen', {
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
            let huboCambio = false;

            ids.forEach((pedidoId) => {
                const count = parseInt(map?.[pedidoId]?.pendientes_despacho ?? '0', 10) || 0;
                const previo = parseInt(window.__asesoresEntregasResumenCache?.[pedidoId] ?? '0', 10) || 0;
                if (previo !== count) {
                    huboCambio = true;
                }
                window.__asesoresEntregasResumenCache[pedidoId] = count;
                __renderBadge(pedidoId, count);
                if (typeof window.__setTotalBadgePartAsesores === 'function') {
                    window.__setTotalBadgePartAsesores(pedidoId, 'entregas', count);
                }
            });

            if (huboCambio) {
                __notifyNotificationsRefresh();
            }
        } catch (e) {
        }
    }

    window.__asesoresEntregasCtx = window.__asesoresEntregasCtx || { pedidoId: null, pedidoNumero: null };

    async function cargarEntregasDespachoAsesores() {
        const pedidoId = window.__asesoresEntregasCtx?.pedidoId;
        const content = document.getElementById('entregasDespachoAsesoresContent');
        if (!pedidoId || !content) return;

        content.innerHTML = '<div class="text-center text-slate-500 py-6">Cargando entregas...</div>';

        try {
            const r = await fetch(`/api/asesores/pedidos/${pedidoId}/entregas-despacho`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __csrfToken(),
                    'Accept': 'application/json',
                },
                cache: 'no-store',
            });

            const data = await r.json().catch(() => null);
            const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];

            if (!r.ok) {
                content.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar entregas</div>';
                return;
            }

            if (!items.length) {
                content.innerHTML = '<div class="text-center text-slate-500 py-6">No hay ítems entregados pendientes de despacho.</div>';
                return;
            }

            const rows = items.map((item) => {
                const accionHtml = `<span class="px-3 py-2 bg-blue-100 text-blue-700 text-xs font-semibold rounded border border-blue-200">
                    Ya está en despacho
                </span>`;

                return `
                <div class="border border-slate-200 rounded-lg p-3 flex flex-col gap-3 bg-white">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">${item.prenda_nombre || '-'}</div>
                            <div class="text-xs text-slate-600 mt-1">
                                Talla: <b>${__displayTalla(item.talla)}</b> · Género: <b>${item.genero || '-'}</b> · Cantidad: <b>${item.cantidad || 0}</b> · Área: <b>${item.area || '-'}</b>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">Fecha entrega bodega: ${__formatDateTime(item.fecha_entrega_bodega || item.fecha_entrega)}</div>
                            ${item.fecha_entrega_despacho ? `<div class="text-xs text-blue-600 mt-1">En despacho: ${__formatDateTime(item.fecha_entrega_despacho)}</div>` : ''}
                        </div>
                        ${accionHtml}
                    </div>
                </div>
            `;
            }).join('');

            content.innerHTML = `<div class="space-y-3">${rows}</div>`;
        } catch (e) {
            content.innerHTML = '<div class="text-center text-red-600 py-6">Error inesperado al cargar entregas</div>';
        }
    }

    async function marcarEntregaDespachoAsesores(detalleId, triggerEl = null) {
        const pedidoId = window.__asesoresEntregasCtx?.pedidoId;
        if (!pedidoId || !detalleId) return;
        const originalButtonHtml = triggerEl ? triggerEl.outerHTML : null;

        if (triggerEl) {
            triggerEl.disabled = true;
            triggerEl.classList.remove('hover:bg-emerald-700');
            triggerEl.classList.add('opacity-70', 'cursor-not-allowed');
            triggerEl.textContent = 'Marcando...';
        }

        try {
            const r = await fetch(`/api/asesores/pedidos/${pedidoId}/entregas-despacho/${detalleId}/marcar`, {
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
            if (!r.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo marcar en despacho');
            }

            if (triggerEl) {
                triggerEl.outerHTML = `
                    <span class="px-3 py-2 bg-blue-100 text-blue-700 text-xs font-semibold rounded border border-blue-200">
                        Ya está en despacho
                    </span>
                `;
            }
            refrescarBadgesEntregasDespachoAsesores();
            __notifyNotificationsRefresh();
        } catch (e) {
            if (triggerEl && originalButtonHtml) {
                triggerEl.outerHTML = originalButtonHtml;
            }
            if (typeof window.Swal !== 'undefined') {
                window.Swal.fire('Error', 'No se pudo marcar en despacho. Intenta nuevamente.', 'error');
            } else {
                alert('No se pudo marcar en despacho. Intenta nuevamente.');
            }
        }
    }

    async function abrirModalEntregasDespachoAsesores(pedidoId, numeroPedido) {
        window.__asesoresEntregasCtx = { pedidoId, pedidoNumero: numeroPedido };

        const modal = document.getElementById('modalEntregasDespachoAsesores');
        const title = document.getElementById('modalEntregasDespachoAsesoresTitle');
        if (title) title.textContent = `Entregas para despacho - Pedido ${numeroPedido || ''}`;
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';

        await cargarEntregasDespachoAsesores();
    }

    function cerrarModalEntregasDespachoAsesores() {
        const modal = document.getElementById('modalEntregasDespachoAsesores');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';
    }

    function initEntregasDespachoAsesores() {
        if (window.__entregasDespachoAsesoresInitialized) {
            return;
        }
        window.__entregasDespachoAsesoresInitialized = true;

        refrescarBadgesEntregasDespachoAsesores();

        window.addEventListener('focus', refrescarBadgesEntregasDespachoAsesores);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                refrescarBadgesEntregasDespachoAsesores();
            }
        });
        window.addEventListener('asesores:pedido-actualizado', refrescarBadgesEntregasDespachoAsesores);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEntregasDespachoAsesores);
    } else {
        initEntregasDespachoAsesores();
    }

    window.abrirModalEntregasDespachoAsesores = abrirModalEntregasDespachoAsesores;
    window.cerrarModalEntregasDespachoAsesores = cerrarModalEntregasDespachoAsesores;
    window.cargarEntregasDespachoAsesores = cargarEntregasDespachoAsesores;
    window.marcarEntregaDespachoAsesores = marcarEntregaDespachoAsesores;
    window.refrescarBadgesEntregasDespachoAsesores = refrescarBadgesEntregasDespachoAsesores;
})();
