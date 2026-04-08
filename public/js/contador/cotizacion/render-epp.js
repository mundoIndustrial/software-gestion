(function () {
    if (typeof window === 'undefined') return;

    const H = window.CotizacionModalHelpers;

    function toUpperSafe(v) {
        if (H && typeof H.toUpper === 'function') return H.toUpper(v);
        return (v || '').toString().trim().toUpperCase();
    }

    function normalizarUrlImagen(u) {
        if (!u) return '';
        let url = String(u).trim();
        if (!url) return '';

        // Quitar hash para evitar duplicados
        // Nota: NO quitar querystring porque puede ser parte de una URL firmada.
        try {
            url = url.split('#')[0];
        } catch (_) {
            // no-op
        }

        // Si ya es absoluta, dejarla
        if (url.startsWith('http://') || url.startsWith('https://')) {
            return url;
        }

        // Normalizar rutas locales
        if (url.startsWith('/storage/')) return url;
        if (url.startsWith('storage/')) return '/' + url;
        if (url.startsWith('/')) return url;
        return '/storage/' + url.replace(/^\/+/, '');
    }

    function pickImagenPrincipal(item) {
        const imgs = item && Array.isArray(item.imagenes) ? item.imagenes : [];
        if (!imgs || imgs.length === 0) return '';
        const first = imgs.find(Boolean);
        return normalizarUrlImagen(first);
    }

    function formatearNumero(num) {
        const n = Number(num);
        if (!Number.isFinite(n)) return '';
        if (Number.isInteger(n)) return String(n);
        const s = n.toFixed(2);
        return s.replace(/\.00$/, '').replace(/(\.[0-9])0$/, '$1');
    }

    function formatearMoneda(num) {
        const n = Number(num);
        if (!Number.isFinite(n)) return '$ 0';

        // Formatear sin redondear, mostrando hasta 2 decimales si existen
        const str = n.toFixed(2).replace('.', '.');

        // Eliminar .00 si no hay decimales
        const formatted = str.endsWith('.00') ? str.slice(0, -3) : str;

        return '$ ' + formatted;
    }

    function renderEppItemCard(item) {
        if (!item) return '';

        const nombre = (item.nombre || item.descripcion || 'Sin nombre').toString();
        const cantidad = (item.cantidad !== undefined && item.cantidad !== null) ? item.cantidad : 1;
        const observaciones = (item.observaciones || '').toString().trim();
        const galleryId = `galeria-epp-${item.id}`;
        const imagenesRaw = (item && Array.isArray(item.imagenes)) ? item.imagenes : [];
        const imagenes = imagenesRaw.map(normalizarUrlImagen).filter(Boolean);
        const img = imagenes[0] || '';
        const galleryHiddenImgs = (imagenes.length > 0)
            ? imagenes.map((u, idx) => (`<img src="${u}" data-gallery="${galleryId}" data-index="${idx}" style="display:none" alt="" />`)).join('')
            : '';

        const valorUnitario = (item.valor_unitario !== undefined && item.valor_unitario !== null && String(item.valor_unitario).trim() !== '')
            ? Number(item.valor_unitario)
            : null;
        const total = (valorUnitario !== null)
            ? (valorUnitario * (parseInt(cantidad) || 0))
            : null;

        return `
            <div class="epp-item-card" style="background: #f5f5f5; border-left: 5px solid #0ea5e9; padding: 1rem 1.5rem; border-radius: 4px; margin-bottom: 1rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">
                            <thead>
                                <tr style="background: #fef3c7; color: #000000;">
                                    <th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">ÍTEM</th>
                                    <th style="padding: 10px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">IMAGEN</th>
                                    <th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">DESCRIPCIÓN</th>
                                    <th style="padding: 10px; text-align: center; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">CANTIDAD</th>
                                    <th style="padding: 10px; text-align: left; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">OBSERVACIONES</th>
                                    <th style="padding: 10px; text-align: right; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">V. UNITARIO</th>
                                    <th style="padding: 10px; text-align: right; font-size: 10px; font-weight: bold; border: 1px solid #fbbf24;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px; text-align: left; font-size: 11px; font-weight: 600; border: 1px solid #e5e7eb;">1</td>
                                    <td style="padding: 10px; text-align: center; font-size: 11px; font-weight: 500; border: 1px solid #e5e7eb;">
                                        ${img ? `
                                            <img src="${img}" alt="${nombre}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; cursor: pointer;" 
                                                 onclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${img}'); else if (window.abrirImagenGrande) window.abrirImagenGrande('${img}', '${galleryId}', 0);">
                                        ` : '<span style="color: #9ca3af;">Sin imagen</span>'}
                                    </td>
                                    <td style="padding: 10px; text-align: left; font-size: 11px; font-weight: 500; border: 1px solid #e5e7eb;">${nombre}</td>
                                    <td style="padding: 10px; text-align: center; font-size: 11px; font-weight: 600; border: 1px solid #e5e7eb;">${cantidad}</td>
                                    <td style="padding: 10px; text-align: left; font-size: 11px; font-weight: 500; border: 1px solid #e5e7eb;">${observaciones || '-'}</td>
                                    <td style="padding: 10px; text-align: right; font-size: 11px; font-weight: 600; border: 1px solid #e5e7eb;">${valorUnitario !== null ? formatearNumero(valorUnitario) : 'N/A'}</td>
                                    <td style="padding: 10px; text-align: right; font-size: 11px; font-weight: 700; border: 1px solid #e5e7eb;">${total !== null ? formatearNumero(total) : '0'}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderEppSection(payload) {
        const tipoCodigo = toUpperSafe(payload?.cotizacion?.tipo_codigo || payload?.cotizacion?.tipo);
        if (tipoCodigo !== 'EPP') {
            return '';
        }

        const eppItems = Array.isArray(payload?.epp_items) ? payload.epp_items : [];
        const prendaItems = Array.isArray(payload?.prenda_items) ? payload.prenda_items : [];
        const items = [...eppItems, ...prendaItems];

        const tipoVenta = (
            payload?.epp_cotizacion?.tipo_venta ||
            payload?.cotizacion?.tipo_venta ||
            null
        );

        // Obtener IVA directamente desde el campo de la cotización
        const valorIva = (payload?.cotizacion?.iva !== undefined && payload?.cotizacion?.iva !== null && String(payload?.cotizacion?.iva).trim() !== '')
            ? Number(payload?.cotizacion?.iva)
            : null;

        // Logging para depuración
        let html = '<div class="epp-container" style="display: flex; flex-direction: column; gap: 1.25rem;">';

        if (tipoVenta) {
            html += `
                <div style="display: inline-block; text-align: left; margin-bottom: 0.25rem; padding: 1rem; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 8px;">
                    <span style="color: #000000; font-size: 1.1rem; font-weight: 600;">Por favor cotizar al</span>
                    <span style="color: #dc2626; font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">${tipoVenta}</span>
                </div>
            `;
        }

        if (!items || items.length === 0) {
            html += '<p style="color: #999; text-align: center; padding: 2rem;">No hay ítems de EPP para mostrar</p>';
            html += '</div>';
            return html;
        }

        // Renderizar sección de EPPs si existen
        if (eppItems.length > 0) {
            html += '<div style="margin-bottom: 1.5rem;">';
            html += '<h3 style="color: #0f172a; font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #0ea5e9;">Equipos de Protección Personal (EPP)</h3>';
            eppItems.forEach((it) => {
                html += renderEppItemCard(it);
            });
            html += '</div>';
        }

        // Renderizar sección de prendas si existen
        if (prendaItems.length > 0) {
            html += '<div style="margin-bottom: 1.5rem;">';
            html += '<h3 style="color: #0f172a; font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ec4899;">Prendas</h3>';
            prendaItems.forEach((it) => {
                html += renderEppItemCard(it);
            });
            html += '</div>';
        }

        // Resumen al final: subtotal (suma de items), IVA y total con IVA
        const subtotal = items.reduce((acc, it) => {
            const vu = (it?.valor_unitario !== undefined && it?.valor_unitario !== null && String(it.valor_unitario).trim() !== '')
                ? Number(it.valor_unitario)
                : null;
            const cant = (it?.cantidad !== undefined && it?.cantidad !== null) ? (parseInt(it.cantidad) || 0) : 0;
            const tot = (vu !== null) ? (vu * cant) : 0;
            return acc + (Number.isFinite(tot) ? tot : 0);
        }, 0);

        // Calcular el IVA como porcentaje del subtotal
        const ivaPorcentaje = (valorIva !== null && Number.isFinite(valorIva)) ? Number(valorIva) : 0;
        const ivaValor = (subtotal * ivaPorcentaje) / 100;
        const totalConIva = subtotal + ivaValor;

        // Logging para depuración del cálculo

        html += `
            <div style="margin-top: 0.25rem; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.10); background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);">
                <div style="padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-bottom: 1px solid rgba(148,163,184,0.35);">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 10px; height: 10px; border-radius: 999px; background: #0ea5e9;"></div>
                        <div style="font-size: 0.85rem; font-weight: 900; letter-spacing: 0.8px; color: #0f172a; text-transform: uppercase;">Resumen</div>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Totales de la cotización</div>
                </div>

                <div style="padding: 1.05rem 1.25rem; display: grid; grid-template-columns: 1fr auto; row-gap: 0.75rem; column-gap: 1.5rem; align-items: center;">
                    <div style="font-size: 0.95rem; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.6px;">Subtotal</div>
                    <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">${formatearMoneda(subtotal)}</div>

                    <div style="font-size: 0.95rem; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.6px;">IVA</div>
                    <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">${formatearMoneda(ivaValor)}</div>
                </div>

                <div style="padding: 1rem 1.25rem; background: #0c8cc7ff; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                    <div style="font-size: 1.05rem; font-weight: 900; color: #ffffff; text-transform: uppercase; letter-spacing: 0.8px;">Total</div>
                    <div style="font-size: 1.25rem; font-weight: 950; color: #ffffff; letter-spacing: 0.8px;">${formatearMoneda(totalConIva)}</div>
                </div>
            </div>
        `;

        html += '</div>';
        return html;
    }

    window.CotizacionModalRenderEpp = {
        renderEppSection,
        renderEppItemCard,
    };
})();
