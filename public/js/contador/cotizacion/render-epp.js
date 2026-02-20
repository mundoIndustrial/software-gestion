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
        const entero = Math.round(n);
        return '$ ' + entero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function renderEppItemCard(item) {
        if (!item) return '';

        const nombre = (item.nombre || 'Sin nombre').toString();
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
            <div class="epp-item-card" style="background: #f5f5f5; border-left: 5px solid #0ea5e9; padding: 1rem 1.5rem; border-radius: 4px;">
                <div style="display: flex; gap: 0.9rem; align-items: flex-start; justify-content: flex-start;">
                    <div style="flex: 0 1 auto; min-width: 0;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #0f172a; font-size: 1.05rem; font-weight: 800; text-transform: uppercase;">
                            ${nombre}
                        </h3>

                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; align-items: start;">
                            <div>
                                <p style="margin: 0 0 0.15rem 0; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Cantidad</p>
                                <p style="margin: 0; font-size: 0.95rem; font-weight: 700; color: #0f172a;">${cantidad}</p>
                            </div>
                            <div>
                                <p style="margin: 0 0 0.15rem 0; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Observaciones</p>
                                <p style="margin: 0; font-size: 0.95rem; font-weight: 500; color: #0f172a;">${observaciones || 'N/A'}</p>
                            </div>
                        </div>

                        ${(valorUnitario !== null) ? `
                            <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; align-items: start; margin-top: 0.75rem;">
                                <div>
                                    <p style="margin: 0 0 0.15rem 0; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Valor Unitario</p>
                                    <p style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a;">${formatearNumero(valorUnitario) || 'N/A'}</p>
                                </div>
                                <div>
                                    <p style="margin: 0 0 0.15rem 0; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Total</p>
                                    <p style="margin: 0; font-size: 1rem; font-weight: 900; color: #0f172a;">${formatearNumero(total) || '0'}</p>
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    ${img ? `
                        <div style="width: 140px; height: 140px; border-radius: 14px; overflow: hidden; border: 1px solid #e5e7eb; background: #f3f4f6; flex-shrink: 0; margin-left: 0.25rem;">
                            <img src="${img}" data-gallery="${galleryId}" data-index="0" alt="${nombre}" style="width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer;" ondblclick="event.preventDefault(); event.stopPropagation(); if (window.mostrarImagenProcesoGrande) window.mostrarImagenProcesoGrande('${img}'); else if (window.abrirImagenGrande) window.abrirImagenGrande('${img}', '${galleryId}', 0);" />
                            ${galleryHiddenImgs}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    function renderEppSection(payload) {
        const tipoCodigo = toUpperSafe(payload?.cotizacion?.tipo_codigo || payload?.cotizacion?.tipo);
        if (tipoCodigo !== 'EPP') {
            return '';
        }

        const items = Array.isArray(payload?.epp_items) ? payload.epp_items : [];

        const tipoVenta = (
            payload?.epp_cotizacion?.tipo_venta ||
            payload?.cotizacion?.tipo_venta ||
            null
        );

        let obsGenerales = payload?.epp_cotizacion?.observaciones_generales ?? null;
        if (typeof obsGenerales === 'string') {
            try {
                obsGenerales = JSON.parse(obsGenerales);
            } catch (_) {
                obsGenerales = null;
            }
        }
        const valorIva = (obsGenerales && (obsGenerales.valor_iva !== undefined) && obsGenerales.valor_iva !== null && String(obsGenerales.valor_iva).trim() !== '')
            ? Number(obsGenerales.valor_iva)
            : null;

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

        items.forEach((it) => {
            html += renderEppItemCard(it);
        });

        // Resumen al final: subtotal (suma de items), IVA y total con IVA
        const subtotal = items.reduce((acc, it) => {
            const vu = (it?.valor_unitario !== undefined && it?.valor_unitario !== null && String(it.valor_unitario).trim() !== '')
                ? Number(it.valor_unitario)
                : null;
            const cant = (it?.cantidad !== undefined && it?.cantidad !== null) ? (parseInt(it.cantidad) || 0) : 0;
            const tot = (vu !== null) ? (vu * cant) : 0;
            return acc + (Number.isFinite(tot) ? tot : 0);
        }, 0);
        const iva = (valorIva !== null && Number.isFinite(valorIva)) ? Number(valorIva) : 0;
        const totalConIva = subtotal + iva;

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
                    <div style="font-size: 1.05rem; font-weight: 900; color: #0f172a;">${formatearMoneda(iva)}</div>
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
