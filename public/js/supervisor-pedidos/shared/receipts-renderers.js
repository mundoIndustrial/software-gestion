(function () {
    function formatDateShort(fechaRaw) {
        if (!fechaRaw) return '-';
        const d = new Date(fechaRaw);
        if (Number.isNaN(d.getTime())) return String(fechaRaw);
        return d.toLocaleDateString('es-CO');
    }

    function formatDateTime(fechaRaw) {
        if (!fechaRaw) return '';
        const d = new Date(fechaRaw);
        if (Number.isNaN(d.getTime())) return '';
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
    }

    function formatDatetimeLocal(fechaRaw) {
        if (!fechaRaw) return '';
        const d = new Date(fechaRaw);
        if (Number.isNaN(d.getTime())) return '';
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
    }

    function emptyStateHtml() {
        return `
            <div style="padding: 3rem 2rem; text-align: center; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                <p style="font-size: 1rem; margin: 0;">No hay pendientes</p>
            </div>
        `;
    }

    function normalizeGarments(prendas) {
        const grouped = new Map();
        (Array.isArray(prendas) ? prendas : []).forEach((prenda) => {
            const nombre = prenda?.nombre_prenda || '';
            const color = prenda?.color_nombre || '';
            const cantidadColor = Number(prenda?.cantidad_color || 0);
            const cantidadTalla = Number(prenda?.cantidad_talla || 0);
            const tela = prenda?.tela ? ` ${prenda.tela}` : '';

            if (color && cantidadColor > 0) {
                const key = `${nombre}|${color}`;
                grouped.set(key, (grouped.get(key) || 0) + cantidadColor);
                return;
            }

            if (!color && cantidadTalla > 0) {
                const key = `${nombre}${tela}|sin-color`;
                grouped.set(key, (grouped.get(key) || 0) + cantidadTalla);
            }
        });

        const lines = [];
        grouped.forEach((cantidad, key) => {
            const partes = key.split('|');
            const nombre = partes[0] || '';
            const tipo = partes[1] || 'sin-color';
            lines.push(tipo === 'sin-color'
                ? `${cantidad} ${nombre}`
                : `${cantidad} ${nombre} color ${tipo}`);
        });

        return lines;
    }

    function renderSewingRow(proceso, escapeHtml, options = {}) {
        const gridTemplate = options.gridTemplate || '110px 170px 110px 200px 120px 200px 160px 130px 100px';
        const showActions = options.showActions === true;
        const showReceiptType = options.showReceiptType === true;
        const actionMode = options.actionMode || 'link';
        const actionHandlerName = String(options.actionHandlerName || 'openReciboCosturaModalFromRow');
        const getReceiptUrl = typeof options.getReceiptUrl === 'function' ? options.getReceiptUrl : null;
        const color = proceso?.color_control_calidad || proceso?.color_reflectivo || proceso?.color_costura || '';
        const rowBaseColor = color || '#ffffff';
        const area = proceso?.area || '';
        const numeroRecibo = String(proceso?.numero_recibo || '');
        const pedidoId = String(proceso?.pedido_id || '');
        const prendaId = String(proceso?.prenda_id || '');
        const esParcial = Boolean(proceso?.es_parcial);
        const pedidoParcialId = String(proceso?.pedido_parcial_id || '');
        const prendas = normalizeGarments(proceso?.prendas || []);
        const prendasHtml = prendas.length
            ? prendas.map((linea) => `<div style="margin-bottom: 0.25rem;">${escapeHtml(linea)}</div>`).join('')
            : '<div>-</div>';
        const receiptUrlRaw = showActions && getReceiptUrl ? getReceiptUrl(proceso) : '';
        const receiptUrl = typeof receiptUrlRaw === 'string' ? receiptUrlRaw : '';
        const actionsCellHtml = showActions
            ? `
                <div style="display: flex; align-items: center; justify-content: center;">
                    ${actionMode === 'modal'
                        ? `<button type="button" data-pedido-id="${escapeHtml(pedidoId)}" data-prenda-id="${escapeHtml(prendaId)}" data-numero-recibo="${escapeHtml(numeroRecibo)}" data-es-parcial="${esParcial ? 'true' : 'false'}" data-pedido-parcial-id="${escapeHtml(pedidoParcialId)}" data-tipo-recibo="${escapeHtml(String(proceso?.tipo_recibo || ''))}" onclick="event.stopPropagation(); ${actionHandlerName}(this)" style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;background:#1d4ed8;color:#fff;border:0;border-radius:8px;font-size:0.8rem;font-weight:600;cursor:pointer;">Ver</button>`
                        : receiptUrl
                        ? `<a href="${escapeHtml(receiptUrl)}" onclick="event.stopPropagation();" style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:0.8rem;font-weight:600;text-decoration:none;">Ver</a>`
                        : '<span style="color:#9ca3af;font-size:0.8rem;">-</span>'}
                </div>
            `
            : '';

        const areaHtml = area
            ? `<span style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">${escapeHtml(area)}</span>`
            : `<span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">Sin area</span>`;
        const tipoRecibo = String(proceso?.tipo_recibo || '').trim().toUpperCase();
        const tipoReciboHtml = !showReceiptType
            ? ''
            : tipoRecibo === 'COSTURA'
                ? '<span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">Costura</span>'
                : tipoRecibo === 'REFLECTIVO'
                    ? '<span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #fde68a; display: inline-block;">Reflectivo</span>'
                    : `<span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">${escapeHtml(tipoRecibo || 'Sin tipo')}</span>`;

        return `
            <div data-row="processo" data-pedido-id="${escapeHtml(pedidoId)}" data-prenda-id="${escapeHtml(prendaId)}" data-numero-recibo="${escapeHtml(numeroRecibo)}" data-es-parcial="${esParcial ? 'true' : 'false'}" data-pedido-parcial-id="${escapeHtml(pedidoParcialId)}" data-color-stored="${escapeHtml(color)}" style="
                --row-bg-color: ${escapeHtml(rowBaseColor)};
                display: grid;
                grid-template-columns: ${escapeHtml(gridTemplate)};
                gap: 0.15rem;
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
                align-items: start;
                min-width: min-content;
                transition: background 0.2s ease;
            ">
                ${actionsCellHtml}
                <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">${escapeHtml(formatDateShort(proceso?.fecha_creacion))}</div>
                <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151; font-weight: 500;">${escapeHtml(numeroRecibo)}</div>
                ${showReceiptType ? `<div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">${tipoReciboHtml}</div>` : ''}
                <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">${escapeHtml(String(proceso?.cliente || '-'))}</div>
                <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">${areaHtml}</div>
                <div style="display: flex; align-items: start; font-size: 0.9rem; color: #374151;"><div class="prenda-list">${prendasHtml}</div></div>
                <div style="display: flex; align-items: center; font-size: 0.85rem; color: #374151;">
                    <button
                        type="button"
                        data-pedido-id="${escapeHtml(String(proceso?.pedido_id || ''))}"
                        data-numero-recibo="${escapeHtml(String(proceso?.numero_recibo || ''))}"
                        data-novedades=""
                        onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                        title="Ver novedades del recibo"
                        style="width: 100%; text-align: left; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; transition: background 0.2s ease;"
                        onmouseover="this.style.background='#f3f4f6'"
                        onmouseout="this.style.background='#f9fafb'"
                    >
                        <span style="color:#9ca3af;">Sin novedades</span>
                        <i class="fas fa-edit" style="color:#6b7280;"></i>
                    </button>
                </div>
                <div style="display: flex; align-items: center; font-size: 0.9rem; color: #374151;">${escapeHtml(String(proceso?.asesor || '-'))}</div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="color-selector-wrapper" data-recibo-id="${escapeHtml(numeroRecibo)}" style="position: relative; display: flex; gap: 0.3rem; align-items: center;">
                        <button type="button" class="color-btn" data-color="#e0f2fe" title="Azul claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #e0f2fe; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="color-btn" data-color="#fef08a" title="Amarillo" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fef08a; cursor: pointer; transition: all 0.2s;"></button>
                        <button type="button" class="color-btn" data-color="#fecaca" title="Rojo claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fecaca; cursor: pointer; transition: all 0.2s;"></button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderReceiptTypeBadge(tipoRecibo, escapeHtml) {
        const tipo = String(tipoRecibo || '').toUpperCase();
        if (tipo === 'BORDADO') return '<span style="background: #f3e8ff; color: #6b21a8; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">Bordado</span>';
        if (tipo === 'ESTAMPADO') return '<span style="background: #ffedd5; color: #9a3412; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">Estampado</span>';
        if (tipo === 'SUBLIMADO') return '<span style="background: #cffafe; color: #155e75; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">Sublimado</span>';
        if (tipo === 'DTF') return '<span style="background: #fce7f3; color: #9d174d; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">DTF</span>';
        return `<span style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">${escapeHtml(String(tipoRecibo || ''))}</span>`;
    }

    function renderEmbroideryRow(proceso, escapeHtml, options = {}) {
        const showActions = options.showActions === true;
        const actionHandlerName = String(options.actionHandlerName || 'openReceiptFromLogoPendingRow');
        const gridTemplate = options.gridTemplate || '170px 110px 200px 150px 140px 130px 160px 130px 100px';
        const fechaCreacion = formatDateTime(proceso?.fecha_creacion);
        const numeroRecibo = proceso?.numero_recibo || 'Sin asignar';
        const cliente = proceso?.cliente || '';
        const cantidad = proceso?.cantidad_total_prendas ?? 0;
        const nombrePrenda = proceso?.nombre_prenda || '';
        const asesor = proceso?.asesor || '';
        const pedidoId = String(proceso?.pedido_id || '');
        const tipoRecibo = renderReceiptTypeBadge(proceso?.tipo_recibo || '', escapeHtml);
        const tipoReciboRaw = proceso?.tipo_recibo || '';
        const fechaAprobacion = formatDateTime(proceso?.fecha_aprobacion);
        const colorBordadoEstampado = proceso?.color_bordado_estampado || '';
        const actionsCellHtml = showActions
            ? `
                <div style="display: flex; align-items: center; justify-content: center;">
                    <button type="button" data-pedido-id="${escapeHtml(pedidoId)}" data-prenda-id="${escapeHtml(String(proceso?.prenda_id || ''))}" data-tipo-recibo="${escapeHtml(String(proceso?.tipo_recibo || ''))}" onclick="event.stopPropagation(); ${actionHandlerName}(this)" style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;background:#1d4ed8;color:#fff;border:0;border-radius:8px;font-size:0.8rem;font-weight:600;cursor:pointer;">Ver</button>
                </div>
            `
            : '';

        if (!tipoReciboRaw) {
            console.warn('[RENDER-EMBROIDERY] Falta tipo_recibo en proceso:', proceso);
        }

        return `
            <div data-row="proceso" data-color-guardado="${colorBordadoEstampado}" style="
                display: grid;
                grid-template-columns: ${escapeHtml(gridTemplate)};
                gap: 0.15rem;
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
                align-items: center;
                min-width: min-content;
                background: ${colorBordadoEstampado || 'white'};
                transition: background 0.2s ease;
            " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=this.getAttribute('data-color-guardado') || 'white'">
                ${actionsCellHtml}
                <div><span>${escapeHtml(fechaCreacion)}</span></div>
                <div><span style="font-weight: 600; color: #1e5ba8;">${escapeHtml(String(numeroRecibo))}</span></div>
                <div><span>${escapeHtml(String(cliente))}</span></div>
                <div><span style="background: #e8f3ff; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; border: 1px solid #bfdbfe; display: inline-block;">${escapeHtml(String(cantidad))} ${escapeHtml(String(nombrePrenda))}</span></div>
                <div><span>${escapeHtml(String(asesor))}</span></div>
                <div>${tipoRecibo}</div>
                <div>${fechaAprobacion ? `<span>${escapeHtml(fechaAprobacion)}</span>` : `<span style="background: #f3f4f6; color: #9ca3af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; white-space: nowrap; display: inline-block;">--</span>`}</div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div class="color-selector-wrapper" data-recibo-id="${escapeHtml(String(numeroRecibo))}" data-tipo-recibo="${escapeHtml(tipoReciboRaw)}" style="position: relative; display: flex; gap: 0.3rem; align-items: center;">
                        <button type="button" class="color-btn" data-color="#e0f2fe" title="Azul claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #e0f2fe; cursor: pointer; transition: all 0.2s; ${colorBordadoEstampado === '#e0f2fe' ? 'box-shadow: 0 0 0 2px #1e40af;' : ''}"></button>
                        <button type="button" class="color-btn" data-color="#fef08a" title="Amarillo" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fef08a; cursor: pointer; transition: all 0.2s; ${colorBordadoEstampado === '#fef08a' ? 'box-shadow: 0 0 0 2px #1e40af;' : ''}"></button>
                        <button type="button" class="color-btn" data-color="#fecaca" title="Rojo claro" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid #cbd5e1; background: #fecaca; cursor: pointer; transition: all 0.2s; ${colorBordadoEstampado === '#fecaca' ? 'box-shadow: 0 0 0 2px #1e40af;' : ''}"></button>
                    </div>
                </div>
            </div>
        `;
    }

    window.SupervisorReceiptsRenderers = {
        emptyStateHtml,
        renderSewingRow,
        renderEmbroideryRow,
    };
})();
