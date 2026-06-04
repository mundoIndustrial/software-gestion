(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showMessage(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        alert(message);
    }

    function obtenerContextoDesdeModal() {
        const modal = document.getElementById('modalAnchoMetraje');
        if (!modal) {
            return null;
        }

        const numeroReciboTexto = (document.getElementById('anchoMetrajeRecibo')?.textContent || '').replace('#', '').trim();
        const numeroRecibo = numeroReciboTexto && !Number.isNaN(Number(numeroReciboTexto)) ? Number(numeroReciboTexto) : null;

        return {
            pedido: modal.dataset.pedido || modal.dataset.numeroPedido || window.insumosAnchoMetrajeContext?.pedido || null,
            numeroPedido: modal.dataset.pedido || modal.dataset.numeroPedido || window.insumosAnchoMetrajeContext?.numeroPedido || null,
            prendaId: modal.dataset.prendaId || window.insumosAnchoMetrajeContext?.prendaId || null,
            prendaBodegaId: modal.dataset.prendaBodegaId || window.insumosAnchoMetrajeContext?.prendaBodegaId || null,
            tipoRecibo: modal.dataset.tipoRecibo || window.insumosAnchoMetrajeContext?.tipoRecibo || null,
            numeroRecibo: modal.dataset.numeroRecibo || numeroRecibo || window.insumosAnchoMetrajeContext?.numeroRecibo || null,
            reciboId: modal.dataset.reciboId || window.insumosAnchoMetrajeContext?.reciboId || null,
        };
    }

    function obtenerContextoDesdeReciboActivo() {
        const estado = window.pedidosRecibosModule?.getEstado?.() || null;
        if (!estado || !estado.pedidoId) {
            return null;
        }

        return {
            pedido: estado.numeroPedido || estado.pedidoId || null,
            numeroPedido: estado.numeroPedido || estado.pedidoId || null,
            prendaId: estado.prendaId || null,
            prendaBodegaId: estado.prendaBodegaId || null,
            tipoRecibo: estado.tipoRecibo || estado.tipo || null,
            numeroRecibo: estado.objetivoConsecutivo || estado.numeroRecibo || estado.consecutivoActual || null,
            reciboId: estado.consecutivoReciboId || estado.reciboId || null,
        };
    }

    function obtenerContextoHistorial() {
        const contexto = obtenerContextoDesdeReciboActivo()
            || window.insumosAnchoMetrajeContext
            || obtenerContextoDesdeModal();
        if (!contexto) {
            return null;
        }

        return {
            pedido: contexto.pedido || contexto.numeroPedido || null,
            numeroPedido: contexto.numeroPedido || contexto.pedido || null,
            prendaId: contexto.prendaId || null,
            prendaBodegaId: contexto.prendaBodegaId || null,
            tipoRecibo: contexto.tipoRecibo || null,
            numeroRecibo: contexto.numeroRecibo || null,
            reciboId: contexto.reciboId || null,
        };
    }

    function getModalElements() {
        return {
            modal: document.getElementById('modalHistorialAnchoMetraje'),
            loading: document.getElementById('historialAnchoMetrajeLoading'),
            empty: document.getElementById('historialAnchoMetrajeEmpty'),
            list: document.getElementById('historialAnchoMetrajeList'),
            pedido: document.getElementById('historialAnchoMetrajePedido'),
            recibo: document.getElementById('historialAnchoMetrajeRecibo'),
        };
    }

    function setLoadingState(isLoading) {
        const { loading, empty, list } = getModalElements();
        if (loading) {
            loading.style.display = isLoading ? 'block' : 'none';
        }
        if (empty && !isLoading) {
            empty.classList.add('hidden');
        }
        if (list && isLoading) {
            list.classList.add('hidden');
            list.innerHTML = '';
        }
    }

    function renderizarHistorial(historial) {
        const { loading, empty, list } = getModalElements();
        if (!list || !empty || !loading) {
            return;
        }

        loading.style.display = 'none';
        list.innerHTML = '';

        if (!Array.isArray(historial) || historial.length === 0) {
            empty.classList.remove('hidden');
            list.classList.add('hidden');
            return;
        }

        const actionLabels = {
            creado: 'Creado',
            actualizado: 'Actualizado',
            ancho_modificado: 'Ancho modificado',
            metraje_modificado: 'Metraje modificado',
            manual_modificado: 'Manual modificado',
            sobrescrito: 'Sobrescrito',
            eliminado: 'Eliminado',
            estado_cambiado: 'Estado cambiado',
        };

        const eventLabels = {
            ancho_metraje: 'Ancho / Metraje',
            estado_recibo: 'Estado recibo',
        };

        const actionColors = {
            creado: 'background:#dcfce7;color:#166534;border-color:#86efac;',
            actualizado: 'background:#dbeafe;color:#1d4ed8;border-color:#93c5fd;',
            ancho_modificado: 'background:#ecfeff;color:#155e75;border-color:#67e8f9;',
            metraje_modificado: 'background:#ecfeff;color:#155e75;border-color:#67e8f9;',
            manual_modificado: 'background:#ecfeff;color:#155e75;border-color:#67e8f9;',
            sobrescrito: 'background:#fef3c7;color:#92400e;border-color:#fcd34d;',
            eliminado: 'background:#fee2e2;color:#991b1b;border-color:#fecaca;',
            estado_cambiado: 'background:#ede9fe;color:#6b21a8;border-color:#c4b5fd;',
        };

        const actionDescriptions = {
            ancho_modificado: 'Se modificó solo el ancho.',
            metraje_modificado: 'Se modificó solo el metraje.',
            manual_modificado: 'Se modificó solo el contenido manual.',
        };

        function formatearMetrajesPorColor(detalles, accion, tipoModo) {
            if ((tipoModo || '').toLowerCase() !== 'color') {
                return '';
            }

            const dataNueva = Array.isArray(detalles?.data_nuevo) ? detalles.data_nuevo : [];
            const dataAnterior = Array.isArray(detalles?.data_anterior) ? detalles.data_anterior : [];
            const fuente = accion === 'eliminado'
                ? dataAnterior
                : (dataNueva.length > 0 ? dataNueva : dataAnterior);

            const metrajes = fuente
                .map((item) => {
                    const color = String(item?.color || '').trim();
                    const metraje = item?.metraje ?? null;
                    if (!color || metraje === null || metraje === undefined || String(metraje).trim() === '') {
                        return '';
                    }

                    return `${escapeHtml(color)}: ${escapeHtml(metraje)}`;
                })
                .filter(Boolean);

            if (metrajes.length === 0) {
                return '';
            }

            return metrajes.join(' | ');
        }

        function getModoEtiqueta(modo) {
            const etiquetas = {
                normal: 'metraje normal',
                color: 'metraje por color',
                pieza: 'metraje por pieza',
                mano: 'manual',
            };

            return etiquetas[String(modo || '').toLowerCase()] || String(modo || '').trim() || 'modo anterior';
        }

        function formatoAntesDespues(valorAntes, valorDespues) {
            const antes = valorAntes === null || valorAntes === undefined || String(valorAntes).trim() === ''
                ? '-'
                : String(valorAntes);
            const despues = valorDespues === null || valorDespues === undefined || String(valorDespues).trim() === ''
                ? '-'
                : String(valorDespues);

            return `Antes: ${escapeHtml(antes)} | Despues: ${escapeHtml(despues)}`;
        }

        function resumirReemplazo(detalles, accion) {
            if (accion !== 'sobrescrito') {
                return '';
            }

            const modoAnterior = String(detalles?.modo_anterior || '').toLowerCase();
            const modoNuevo = String(detalles?.modo_nuevo || '').toLowerCase();
            const contenidoManoNuevo = String(detalles?.contenido_mano_nuevo || '').trim();

            if (modoAnterior && modoNuevo && modoAnterior !== modoNuevo) {
                if (modoAnterior === 'color' && modoNuevo === 'normal') {
                    return 'Se eliminó el metraje por color y se cambió a metraje normal';
                }

                if (modoAnterior === 'normal' && modoNuevo === 'color') {
                    return 'Se eliminó el metraje normal y se cambió a metraje por color';
                }

                const base = `Se eliminó ${escapeHtml(getModoEtiqueta(modoAnterior))} y se cambió a ${escapeHtml(getModoEtiqueta(modoNuevo))}`;
                if (modoNuevo === 'mano' && contenidoManoNuevo) {
                    return `${base}. Texto guardado: ${escapeHtml(contenidoManoNuevo)}`;
                }

                return base;
            }

            if (modoNuevo === 'mano' && contenidoManoNuevo) {
                return `Se eliminaron datos previos de ancho/metraje y se reemplazaron. Texto guardado: ${escapeHtml(contenidoManoNuevo)}`;
            }

            return 'Se eliminaron datos previos de ancho/metraje y se reemplazaron';
        }

        list.classList.remove('hidden');
        empty.classList.add('hidden');
        list.innerHTML = historial.map((item) => {
            const accion = item.accion || 'actualizado';
            const estado = item.tipo_evento === 'estado_recibo'
                ? `${escapeHtml(item.estado_anterior || '-')} → ${escapeHtml(item.estado_nuevo || '-')}`
                : '';
            const ancho = item.tipo_evento === 'ancho_metraje'
                ? `${item.ancho_anterior ?? '-'} → ${item.ancho_nuevo ?? '-'}`
                : '';
            const metrajeGeneral = item.tipo_evento === 'ancho_metraje'
                ? `${item.metraje_anterior ?? '-'} → ${item.metraje_nuevo ?? '-'}`
                : '';
            const detalles = item.detalles || {};
            const modo = item.modo ? `Modo: ${escapeHtml(getModoEtiqueta(item.modo))}` : '';
            const color = item.color ? `Color: ${escapeHtml(item.color)}` : '';
            const metrajePorColor = formatearMetrajesPorColor(detalles, accion, item.modo);
            const esModoColor = String(item.modo || '').toLowerCase() === 'color';
            const reemplazo = resumirReemplazo(detalles, accion);
            const contenidoManoNuevo = String(detalles?.contenido_mano_nuevo || '').trim();
            const descripcionAccion = actionDescriptions[accion] || '';
            const anchoAntes = item.tipo_evento === 'ancho_metraje' ? item.ancho_anterior : null;
            const anchoDespues = item.tipo_evento === 'ancho_metraje' ? item.ancho_nuevo : null;
            const metrajeAntes = item.tipo_evento === 'ancho_metraje' ? item.metraje_anterior : null;
            const metrajeDespues = item.tipo_evento === 'ancho_metraje' ? item.metraje_nuevo : null;
            const contenidoManoAntes = String(detalles?.contenido_mano_anterior || '').trim();
            const contenidoManoDespues = String(detalles?.contenido_mano_nuevo || '').trim();
            const mostrarManual = String(item.modo || '').toLowerCase() === 'mano'
                || contenidoManoAntes !== ''
                || contenidoManoDespues !== '';

            return `
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border" style="${actionColors[accion] || 'background:#f3f4f6;color:#374151;border-color:#e5e7eb;'}">
                                    ${escapeHtml(actionLabels[accion] || accion)}
                                </span>
                                <span class="text-sm font-semibold text-slate-900">${escapeHtml(eventLabels[item.tipo_evento] || item.tipo_evento || 'Ancho / Metraje')}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                ${escapeHtml(item.fecha || '')}
                                ${item.usuario_nombre ? ` • ${escapeHtml(item.usuario_nombre)}` : ''}
                            </p>
                        </div>
                        <div class="text-right text-xs text-slate-500">
                            ${item.tipo_recibo ? `<div>Recibo: <strong>${escapeHtml(item.tipo_recibo)}</strong></div>` : ''}
                            ${item.numero_recibo ? `<div>N°: <strong>${escapeHtml(item.numero_recibo)}</strong></div>` : ''}
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-slate-700">
                        ${estado ? `<div><strong>Estado:</strong> ${estado}</div>` : ''}
                        ${item.tipo_evento === 'ancho_metraje' ? `<div><strong>Ancho:</strong> Antes: ${escapeHtml(anchoAntes ?? '-')} | Despues: ${escapeHtml(anchoDespues ?? '-')}</div>` : ''}
                        ${item.tipo_evento === 'ancho_metraje' && !esModoColor ? `<div><strong>Metraje:</strong> Antes: ${escapeHtml(metrajeAntes ?? '-')} | Despues: ${escapeHtml(metrajeDespues ?? '-')}</div>` : ''}
                        ${metrajePorColor ? `<div class="md:col-span-2"><strong>Metraje por color:</strong> ${metrajePorColor}</div>` : ''}
                        ${mostrarManual ? `<div class="md:col-span-2"><strong>Manual:</strong> Antes: ${escapeHtml(contenidoManoAntes || '-')} | Despues: ${escapeHtml(contenidoManoDespues || '-')}</div>` : ''}
                        ${descripcionAccion ? `<div class="md:col-span-2 text-xs text-slate-500">${escapeHtml(descripcionAccion)}</div>` : ''}
                        ${reemplazo ? `<div class="md:col-span-2 text-xs text-amber-700"><strong>${escapeHtml(reemplazo)}</strong></div>` : ''}
                        ${modo ? `<div><strong>${escapeHtml(modo)}</strong></div>` : ''}
                        ${color ? `<div><strong>${escapeHtml(color)}</strong></div>` : ''}
                    </div>
                    ${detalles?.estado_solicitado ? `<div class="mt-2 text-xs text-slate-500">Estado solicitado: ${escapeHtml(detalles.estado_solicitado)}</div>` : ''}
                </div>
            `;
        }).join('');
    }

    async function cargarHistorial(modo = 'global') {
        const contexto = modo === 'global' ? null : obtenerContextoHistorial();
        const { modal, pedido, recibo } = getModalElements();

        const tieneContextoEspecifico = modo !== 'global' && !!(contexto?.numeroPedido || contexto?.pedido || contexto?.prendaId || contexto?.prendaBodegaId);

        if (pedido) {
            pedido.textContent = tieneContextoEspecifico
                ? String(contexto.numeroPedido || contexto.pedido || '-')
                : 'Todos';
        }
        if (recibo) {
            recibo.textContent = tieneContextoEspecifico
                ? String(contexto.numeroRecibo || contexto.reciboId || '-')
                : 'Todos';
        }

        setLoadingState(true);

        const params = new URLSearchParams();
        if (tieneContextoEspecifico && contexto?.prendaId) params.set('prenda_id', contexto.prendaId);
        if (tieneContextoEspecifico && contexto?.prendaBodegaId) params.set('prenda_bodega_id', contexto.prendaBodegaId);
        if (tieneContextoEspecifico && contexto?.numeroRecibo) params.set('numero_recibo', contexto.numeroRecibo);
        if (tieneContextoEspecifico && contexto?.reciboId) params.set('consecutivo_recibo_id', contexto.reciboId);
        if (tieneContextoEspecifico && contexto?.tipoRecibo) params.set('tipo_recibo', contexto.tipoRecibo);
        params.set('limit', '100');

        try {
            const endpointBase = tieneContextoEspecifico
                ? `/insumos/materiales/${encodeURIComponent(String(contexto.numeroPedido || contexto.pedido))}/historial-ancho-metraje`
                : '/insumos/materiales/historial-ancho-metraje';

            const queryString = params.toString();
            const response = await fetch(queryString ? `${endpointBase}?${queryString}` : endpointBase, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'No se pudo cargar el historial');
            }

            renderizarHistorial(Array.isArray(data.data) ? data.data : []);
        } catch (error) {
            const { loading, empty, list } = getModalElements();
            if (loading) loading.style.display = 'none';
            if (empty) {
                empty.classList.remove('hidden');
                empty.textContent = error.message || 'Error al cargar historial';
            }
            if (list) {
                list.classList.add('hidden');
            }
        }
    }

    function abrirModalHistorialAnchoMetraje() {
        const { modal } = getModalElements();
        if (!modal) {
            showMessage('No se encontró el modal de historial.', 'error');
            return;
        }

        window.insumosAnchoMetrajeHistorialModo = 'global';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        cargarHistorial('global');
    }

    function cerrarModalHistorialAnchoMetraje() {
        const { modal } = getModalElements();
        if (!modal) return;

        modal.style.display = 'none';
        document.body.style.overflow = '';
        window.insumosAnchoMetrajeHistorialModo = null;
    }

    function coincidenContextos(a, b) {
        return String(a?.numeroPedido || a?.pedido || '') === String(b?.numeroPedido || b?.pedido || '')
            && String(a?.prendaId || '') === String(b?.prendaId || '')
            && String(a?.prendaBodegaId || '') === String(b?.prendaBodegaId || '')
            && String(a?.numeroRecibo || '') === String(b?.numeroRecibo || '')
            && String(a?.reciboId || '') === String(b?.reciboId || '')
            && String(a?.tipoRecibo || '') === String(b?.tipoRecibo || '');
    }

    function registrarListeners() {
        if (document.documentElement.dataset.historialAnchoMetrajeBound === '1') {
            return;
        }
        document.documentElement.dataset.historialAnchoMetrajeBound = '1';

        window.addEventListener('historialInsumosActualizado', () => {
            const { modal } = getModalElements();
            if (!modal || modal.style.display === 'none') {
                return;
            }

            cargarHistorial();
        });
    }

    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.historialAnchoMetraje = {
        abrirModalHistorialAnchoMetraje,
        cerrarModalHistorialAnchoMetraje,
        cargarHistorial,
        obtenerContextoHistorial,
        coincidenContextos,
    };

    registrarListeners();
})();
