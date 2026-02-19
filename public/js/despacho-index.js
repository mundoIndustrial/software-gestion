(function () {
    function __despachoObsCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        return '';
    }

    window.__despachoObsCtx = window.__despachoObsCtx || { pedidoId: null };

    // ==================== BADGE FUNCTIONS ====================
    function __clearBadge(pedidoId) {
        console.log(`[DEBUG-BADGE] __clearBadge llamado para pedidoId: ${pedidoId}`);
        
        // Buscar en botones de despacho
        let btn = document.querySelector(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);
        if (!btn) {
            // Buscar en botones de asesores/pedidos
            btn = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${pedidoId}"]`);
        }
        
        console.log(`[DEBUG-BADGE] Botón encontrado para clear:`, btn);
        
        if (!btn) {
            console.warn(`[DEBUG-BADGE] No se encontró botón para limpiar badge del pedido ${pedidoId}`);
            return;
        }
        
        const badge = btn.querySelector('.obs-despacho-badge');
        console.log(`[DEBUG-BADGE] Badge encontrado:`, badge);
        
        if (badge) {
            console.log(`[DEBUG-BADGE] Eliminando badge del pedido ${pedidoId}`);
            badge.remove();
        } else {
            console.log(`[DEBUG-BADGE] No hay badge para eliminar en el pedido ${pedidoId}`);
        }
    }

    function __renderBadge(pedidoId, count) {
        console.log(`[DEBUG-BADGE] __renderBadge llamado - pedidoId: ${pedidoId}, count: ${count}`);
        
        // Buscar en botones de despacho primero
        let btn = document.querySelector(`.despacho-obs-btn[data-pedido-id="${pedidoId}"]`);
        if (!btn) {
            // Buscar en botones de asesores/pedidos
            btn = document.querySelector(`.btn-ver-dropdown[data-pedido-id="${pedidoId}"]`);
        }
        console.log(`[DEBUG-BADGE] Botón encontrado para render:`, btn);
        
        if (!btn) {
            console.warn(`[DEBUG-BADGE] No se encontró botón para pedido ${pedidoId}`);
            // Buscar todos los botones para debugging
            const allDespachoBtns = document.querySelectorAll('.despacho-obs-btn');
            const allAsesoresBtns = document.querySelectorAll('.btn-ver-dropdown');
            console.log(`[DEBUG-BADGE] Botones despacho encontrados:`, allDespachoBtns.length);
            console.log(`[DEBUG-BADGE] Botones asesores encontrados:`, allAsesoresBtns.length);
            
            // Mostrar botones de despacho
            allDespachoBtns.forEach((b, i) => {
                console.log(`[DEBUG-BADGE] Botón despacho ${i}:`, {
                    'data-pedido-id': b.getAttribute('data-pedido-id'),
                    'onclick': b.getAttribute('onclick'),
                    'class': b.className
                });
            });
            
            // Mostrar botones de asesores
            allAsesoresBtns.forEach((b, i) => {
                console.log(`[DEBUG-BADGE] Botón asesores ${i}:`, {
                    'data-pedido-id': b.getAttribute('data-pedido-id'),
                    'class': b.className
                });
            });
            return;
        }
        
        console.log(`[DEBUG-BADGE] Limpiando badge existente antes de renderizar`);
        __clearBadge(pedidoId);
        
        if (!count || count <= 0) {
            console.log(`[DEBUG-BADGE] No hay badge para mostrar - count: ${count}`);
            return;
        }

        console.log(`[DEBUG-BADGE] Creando badge para pedido ${pedidoId} con count: ${count}`);
        
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
        
        console.log(`[DEBUG-BADGE] Badge agregado exitosamente para pedido ${pedidoId}`);
        console.log(`[DEBUG-BADGE] Badge HTML:`, badge.outerHTML);
    }

    async function refrescarBadgesObservacionesDespacho() {
        try {
            const rows = Array.from(document.querySelectorAll('tr[data-pedido-id]'));
            const ids = rows
                .map(r => r.getAttribute('data-pedido-id'))
                .filter(Boolean)
                .map(v => parseInt(v, 10))
                .filter(n => Number.isFinite(n));

            console.log(`[DEBUG] refrescarBadges - IDs encontrados:`, ids);

            if (ids.length === 0) return;

            // Detectar si estamos en despacho o asesores
            const isDespachoPage = window.location.pathname.includes('/despacho');
            const url = isDespachoPage 
                ? '/despacho/observaciones/resumen'
                : '/asesores/pedidos/observaciones-despacho/resumen';
                
            console.log(`[DEBUG] Refrescando badges desde: ${url}`);

            const r = await fetch(url, {
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
            console.log(`[DEBUG] Respuesta del servidor:`, data);
            
            const map = (data && data.success && data.data) ? data.data : {};
            console.log(`[DEBUG] Map de unread counts:`, map);

            ids.forEach((pedidoId) => {
                const unread = parseInt(map?.[pedidoId]?.unread ?? '0', 10) || 0;
                console.log(`[DEBUG] Pedido ${pedidoId} - unread: ${unread}`);
                __renderBadge(pedidoId, unread);
            });
        } catch (e) {
            console.error('Error refrescando badges de observaciones despacho:', e);
        }
    }

    // Clear badge when modal opens - SOLO si el usuario hace clic
    function clearBadgeOnOpen(pedidoId) {
        // NO limpiar automáticamente - esperar a que el usuario haga clic
        // __clearBadge(pedidoId);
        console.log(`[Despacho] Badge mantenido para pedido ${pedidoId} - esperando acción del usuario`);
    }

    // Marcar notificaciones como vistas cuando el usuario hace clic
    async function marcarNotificacionesComoVistas(pedidoId) {
        console.log(`[DEBUG-BADGE] marcarNotificacionesComoVistas llamado para pedidoId: ${pedidoId}`);
        
        try {
            // Detectar si estamos en despacho o asesores
            const isDespachoPage = window.location.pathname.includes('/despacho');
            const url = isDespachoPage 
                ? `/despacho/${pedidoId}/observaciones/marcar-vistas`
                : `/asesores/pedidos/${pedidoId}/observaciones-despacho/marcar-leidas`;
                
            console.log(`[DEBUG-BADGE] Marcando notificaciones como vistas en: ${url}`);
            
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __despachoObsCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
                cache: 'no-store',
            });

            const data = await r.json().catch(() => null);
            console.log(`[DEBUG-BADGE] Respuesta del servidor al marcar como vistas:`, data);
            
            if (data && data.success) {
                console.log(`[DEBUG-BADGE] Notificaciones marcadas como vistas para pedido ${pedidoId}`);
                console.log(`[DEBUG-BADGE] Llamando a __clearBadge para pedido ${pedidoId}`);
                // Ahora sí limpiar el badge
                __clearBadge(pedidoId);
                // Refrescar para confirmar
                console.log(`[DEBUG-BADGE] Refrescando badges después de marcar como vistas`);
                refrescarBadgesObservacionesDespacho();
            } else {
                console.warn(`[DEBUG-BADGE] Error marcando notificaciones como vistas para pedido ${pedidoId}:`, data);
            }
        } catch (e) {
            console.error(`[DEBUG-BADGE] Error en marcarNotificacionesComoVistas:`, e);
        }
    }
    // =========================================================

    async function __fetchObservacionesDespacho(pedidoId) {
        // Detectar si estamos en despacho o asesores
        const isDespachoPage = window.location.pathname.includes('/despacho');
        const url = isDespachoPage 
            ? `/despacho/${pedidoId}/observaciones`
            : `/asesores/pedidos/${pedidoId}/observaciones-despacho`;
            
        console.log(`[DEBUG] Obteniendo observaciones desde: ${url}`);
        
        const r = await fetch(url, {
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
        console.log(`[DEBUG-BADGE] abrirModalObservacionesDespachoIndex llamado - pedidoId: ${pedidoId}, numeroPedido: ${numeroPedido}`);
        
        window.__despachoObsCtx = { pedidoId };
        const modal = document.getElementById('modalObservacionesDespachoIndex');
        const title = document.getElementById('modalObsDespachoTitle');
        if (title) title.textContent = `Observaciones - Pedido ${numeroPedido || ''}`;
        if (!modal) {
            console.warn(`[DEBUG-BADGE] No se encontró el modal modalObservacionesDespachoIndex`);
            return;
        }

        console.log(`[DEBUG-BADGE] Abriendo modal y cargando observaciones`);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';

        const nueva = document.getElementById('observacionesDespachoIndexNueva');
        if (nueva) nueva.value = '';

        await cargarObservacionesDespachoIndex();

        console.log(`[DEBUG-BADGE] Llamando a marcarNotificacionesComoVistas desde abrirModal`);
        // Marcar notificaciones como vistas cuando el usuario abre el modal
        await marcarNotificacionesComoVistas(pedidoId);
        console.log(`[DEBUG-BADGE] Modal abierto y notificaciones marcadas como vistas`);
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
                mostrarNotificacion(data?.message || 'No se pudo guardar la observación', 'error');
                return;
            }
            mostrarNotificacion('Observación guardada exitosamente', 'success');
            if (textarea) textarea.value = '';
            await cargarObservacionesDespachoIndex();
            cerrarModalObservacionesDespachoIndex();
        } catch (e) {
            console.error('Error guardando observación despacho index:', e);
            mostrarNotificacion('Error al guardar la observación', 'error');
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

    // ==================== MODALES PERSONALIZADOS ====================
    function mostrarModalConfirmacion(titulo, mensaje, onConfirmar, onCancelar) {
        const modalId = 'modalConfirmacionDespacho';
        let modal = document.getElementById(modalId);
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 10001; font-family: system-ui, -apple-system, sans-serif;
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
        const notifId = 'notificacionDespacho' + Date.now();
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

    async function eliminarObservacionDespachoIndex(observacionId) {
        const pedidoId = window.__despachoObsCtx?.pedidoId;
        if (!pedidoId) return;
        mostrarModalConfirmacion(
            'Eliminar observación',
            '¿Estás seguro de que deseas eliminar esta observación? Esta acción no se puede deshacer.',
            async () => {
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
                        mostrarNotificacion(data?.message || 'No se pudo eliminar la observación', 'error');
                        return;
                    }
                    mostrarNotificacion('Observación eliminada correctamente', 'success');
                    await cargarObservacionesDespachoIndex();
                } catch (e) {
                    console.error('Error eliminando observación despacho index:', e);
                    mostrarNotificacion('Error al eliminar la observación', 'error');
                }
            }
        );
    }

    window.abrirModalObservacionesDespachoIndex = abrirModalObservacionesDespachoIndex;
    window.cerrarModalObservacionesDespachoIndex = cerrarModalObservacionesDespachoIndex;
    window.guardarObservacionDespachoIndex = guardarObservacionDespachoIndex;
    window.editarObservacionDespachoIndex = editarObservacionDespachoIndex;
    window.eliminarObservacionDespachoIndex = eliminarObservacionDespachoIndex;

    // ==================== INSERTAR PEDIDO EN TIEMPO REAL ====================
    function getEstadoBadgeClass(estado) {
        const classes = {
            'PENDIENTE_SUPERVISOR': 'bg-blue-100 text-blue-800',
            'APROBADO_SUPERVISOR': 'bg-yellow-100 text-yellow-800',
            'EN_PRODUCCION': 'bg-orange-100 text-orange-800',
            'FINALIZADO': 'bg-green-100 text-green-800',
            'En Ejecución': 'bg-orange-100 text-orange-800',
            'Entregado': 'bg-green-100 text-green-800',
            'Pendiente': 'bg-blue-100 text-blue-800',
            'No iniciado': 'bg-slate-100 text-slate-800',
            'Anulada': 'bg-red-100 text-red-800',
            'PENDIENTE_INSUMOS': 'bg-purple-100 text-purple-800'
        };
        return classes[estado] || 'bg-slate-100 text-slate-800';
    }

    function insertarPedidoEnTabla(orden) {
        const tbody = document.querySelector('tbody.divide-y');
        if (!tbody) {
            console.warn('[Despacho] No se encontró tbody para insertar pedido');
            return;
        }

        const pedidoId = orden.id;
        const numeroPedido = orden.numero_pedido || orden.pedido || orden.id;
        const cliente = orden.cliente || orden.cliente_nombre || '—';
        const estado = orden.estado || orden.state || 'Pendiente';
        const estadoDisplay = estado.replace(/_/g, ' ');
        const fechaCreacion = orden.fecha_de_creacion_de_orden || orden.created_at || '—';
        const fechaEntrega = orden.fecha_estimada_de_entrega || orden.fecha_estimada || '—';

        // Formatear fechas
        const formatDate = (dateStr) => {
            if (!dateStr || dateStr === '—') return '—';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            } catch (e) { return '—'; }
        };

        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50 transition-colors';
        row.setAttribute('data-pedido-id', pedidoId);
        row.innerHTML = `
            <td class="px-6 py-4 text-center">
                <a href="/despacho/${pedidoId}"
                   class="inline-block px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition-colors">
                    Ver
                </a>
            </td>
            <td class="px-6 py-4 font-medium text-slate-900">
                ${numeroPedido}
            </td>
            <td class="px-6 py-4 text-slate-600">
                ${cliente}
            </td>
            <td class="px-6 py-4">
                <div class="flex gap-2 items-start">
                    <textarea
                        class="despacho-observaciones-preview w-56 px-2 py-1 border border-slate-300 rounded text-xs bg-slate-50 resize-none"
                        rows="2"
                        readonly
                        data-pedido-id="${pedidoId}"
                    ></textarea>
                    <button
                        type="button"
                        onclick="abrirModalObservacionesDespachoIndex(${pedidoId}, '${String(numeroPedido).replace(/'/g, "\\'")}')"
                        class="despacho-obs-btn px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors"
                        data-pedido-id="${pedidoId}"
                        style="position:relative"
                        title="Ver/agregar observaciones"
                    >
                        💬
                    </button>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="inline-block px-2 py-1 rounded text-xs font-medium ${getEstadoBadgeClass(estado)}">
                    ${estadoDisplay}
                </span>
            </td>
            <td class="px-6 py-4 text-center text-slate-600 text-xs">
                ${formatDate(fechaCreacion)}
            </td>
            <td class="px-6 py-4 text-center text-slate-600 text-xs">
                ${formatDate(fechaEntrega)}
            </td>
        `;

        // Insertar al principio del tbody
        tbody.insertBefore(row, tbody.firstChild);

        // Cargar preview de observaciones
        cargarPreviewObservacionesDespacho(pedidoId);

        console.log(`[Despacho] Pedido ${pedidoId} insertado en la tabla`);
    }

    // ==================== WEBSOCKET / REALTIME ====================
    function setupObservacionesRealtime() {
        if (!window.EchoInstance) {
            console.warn('[Despacho] EchoInstance no disponible, reintentando en 2s...');
            setTimeout(setupObservacionesRealtime, 2000);
            return;
        }

        // Escuchar canal general de despacho (observaciones)
        window.EchoInstance.channel('despacho.observaciones')
            .listen('.observacion.despacho', (e) => {
                console.log(`[DEBUG-BADGE] 🚨 Evento WebSocket recibido en canal despacho.observaciones:`, e);
                console.log(`[DEBUG-BADGE] Pedido ID: ${e?.pedido_id}, Action: ${e?.action}`);
                
                const pedidoId = e?.pedido_id;
                if (!pedidoId) {
                    console.warn(`[DEBUG-BADGE] Evento sin pedido_id, ignorando`);
                    return;
                }

                console.log(`[DEBUG-BADGE] 📢 Llamando a __renderBadge con count=1 para pedido ${pedidoId}`);
                // Actualizar badge para el pedido afectado
                __renderBadge(pedidoId, 1); // Mostrar al menos 1

                // Si el modal está abierto para este pedido, recargar observaciones
                const currentPedidoId = window.__despachoObsCtx?.pedidoId;
                if (currentPedidoId && String(currentPedidoId) === String(pedidoId)) {
                    console.log(`[DEBUG-BADGE] Modal abierto para este pedido, recargando observaciones`);
                    cargarObservacionesDespachoIndex();
                    // NO marcar automáticamente como leídas - esperar acción del usuario
                }

                // Recargar el preview del textarea
                console.log(`[DEBUG-BADGE] Recargando preview de observaciones para pedido ${pedidoId}`);
                cargarPreviewObservacionesDespacho(pedidoId);
            });

        // Escuchar canal de ordenes para nuevos pedidos aprobados
        window.EchoInstance.channel('ordenes')
            .listen('.orden.updated', (e) => {
                console.log('[Despacho] Evento orden.updated recibido:', e);

                // Solo procesar si es un pedido nuevo (created) o actualizado
                if (e?.action === 'created' || e?.action === 'updated') {
                    const orden = e?.orden;
                    if (!orden || !orden.id) return;

                    // Verificar si el pedido está en estado que debe mostrarse en despacho
                    const estadosDespacho = ['PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'No iniciado', 'En Ejecución', 'Pendiente'];
                    const estado = orden.estado || orden.state;

                    if (estadosDespacho.includes(estado)) {
                        // Verificar si el pedido ya existe en la tabla
                        const existingRow = document.querySelector(`tr[data-pedido-id="${orden.id}"]`);

                        if (existingRow) {
                            // El pedido ya existe, actualizar datos si es necesario
                            console.log(`[Despacho] Pedido ${orden.id} ya existe en la tabla`);
                        } else {
                            // Es un pedido nuevo, insertar dinámicamente en la tabla
                            insertarPedidoEnTabla(orden);
                            mostrarNotificacion(`Nuevo pedido aprobado: ${orden.numero_pedido || orden.pedido || orden.id}`, 'success');
                        }
                    }
                }
            });

        // Escuchar canales específicos de cada pedido visible en la página
        const rows = document.querySelectorAll('tr[data-pedido-id]');
        rows.forEach(row => {
            const pedidoId = row.getAttribute('data-pedido-id');
            if (!pedidoId) return;

            window.EchoInstance.channel(`pedido.${pedidoId}`)
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
