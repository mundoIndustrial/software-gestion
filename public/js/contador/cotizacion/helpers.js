(function () {
    if (typeof window === 'undefined') return;

    const toUpper = (v) => (v ?? '').toString().trim().toUpperCase();

    const normalizarUrlLogo = (u) => {
        if (!u) return '';
        let url = String(u).trim();
        if (!url) return '';

        // Quitar querystring/hash para evitar duplicados del mismo archivo
        try {
            url = url.split('#')[0];
            url = url.split('?')[0];
        } catch (_) {
            // no-op
        }

        // Estandarizar rutas locales a /storage/...
        if (!url.startsWith('http')) {
            if (url.startsWith('storage/')) {
                url = '/' + url;
            }
            if (!url.startsWith('/storage/')) {
                url = '/storage/' + url.replace(/^\/+/, '').replace(/^storage\//, '');
            }
        }

        return url;
    };

    window.CotizacionModalHelpers = {
        toUpper,
        safeArray(value) {
            return Array.isArray(value) ? value : [];
        },
        normalizarUrlLogo,
        buildImagenesParaMostrar({ payload, prenda, modo, imgTela }) {
            const imagenesParaMostrar = [];

            // Recolectar imágenes de logo para esta prenda (solo en vista logo)
            const logosPorUrl = new Map();

            if (modo === 'logo' && payload && payload.logo_cotizacion && Array.isArray(payload.logo_cotizacion.tecnicas_prendas)) {
                payload.logo_cotizacion.tecnicas_prendas.forEach(tp => {
                    if (tp.prenda_id === prenda.id && tp.fotos && tp.fotos.length > 0) {
                        const tecnicaNombre = (tp.tipo_logo_nombre || (tp.tipoLogo && tp.tipoLogo.nombre) || 'Logo');
                        tp.fotos.forEach((foto) => {
                            if (!foto || !foto.url) return;
                            const urlOriginal = String(foto.url);
                            const urlKey = normalizarUrlLogo(urlOriginal) || urlOriginal;

                            if (!logosPorUrl.has(urlKey)) {
                                logosPorUrl.set(urlKey, {
                                    url: urlKey,
                                    tecnicas: new Set(),
                                });
                            }

                            logosPorUrl.get(urlKey).tecnicas.add(tecnicaNombre);
                        });
                    }
                });
            }

            if (modo === 'logo' && logosPorUrl.size > 0) {
                Array.from(logosPorUrl.values()).forEach((logo) => {
                    const tecnicas = Array.from(logo.tecnicas).filter(Boolean);
                    const textoTecnicas = tecnicas.join(', ');
                    const label = tecnicas.length <= 1
                        ? `Logo - ${textoTecnicas || 'Logo'}`
                        : 'Logo compartido';

                    imagenesParaMostrar.push({
                        grupo: label,
                        url: logo.url,
                        titulo: label,
                        color: '#1e5ba8'
                    });
                });
            }

            // Recolectar imágenes de tela para esta prenda
            if (prenda && Array.isArray(prenda.tela_fotos) && prenda.tela_fotos.length > 0) {
                prenda.tela_fotos.forEach((foto, idx) => {
                    if (foto) {
                        imagenesParaMostrar.push({
                            grupo: 'Tela',
                            url: foto,
                            titulo: `Tela ${idx + 1}`,
                            color: '#1e5ba8'
                        });
                    }
                });
            }

            // Imagen de tela de logo_cotizacion.telas_prendas (solo en vista LOGO)
            if (modo === 'logo' && imgTela) {
                imagenesParaMostrar.push({
                    grupo: 'Tela',
                    url: imgTela,
                    titulo: 'Tela Logo',
                    color: '#1e5ba8'
                });
            }

            // Recolectar imágenes de prenda
            if (prenda && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
                prenda.fotos.forEach((foto, idx) => {
                    imagenesParaMostrar.push({
                        grupo: 'Prenda',
                        url: foto,
                        titulo: `${prenda.nombre_prenda || 'Prenda'} ${idx + 1}`,
                        color: '#1e5ba8'
                    });
                });
            }

            return imagenesParaMostrar;
        },
        renderImagenesParaMostrar({ prendaId, imagenesParaMostrar }) {
            if (!imagenesParaMostrar || imagenesParaMostrar.length === 0) return '';

            let html = `
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-start;">
            `;

            imagenesParaMostrar.forEach((img, idx) => {
                html += `
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <img src="${img.url}"
                             alt="${img.titulo}"
                             data-gallery="galeria-${prendaId}"
                             data-index="${idx}"
                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid ${img.color}; cursor: pointer; transition: all 0.3s;"
                             onclick="abrirImagenGrande('${img.url}', 'galeria-${prendaId}', ${idx})"
                             onmouseover="this.style.boxShadow='0 4px 12px rgba(30, 91, 168, 0.4)'; this.style.transform='scale(1.05)';"
                             onmouseout="this.style.boxShadow='none'; this.style.transform='scale(1)';"/>
                        <div style="margin-top: 0.5rem; background: linear-gradient(to right, ${img.color}, ${img.color}); padding: 0.5rem 0.75rem; border-radius: 4px; color: white; text-align: center; font-weight: 600; font-size: 0.7rem; white-space: nowrap;">
                            ${img.grupo}
                        </div>
                    </div>
                `;
            });

            html += `
                </div>
            `;

            return html;
        },
        get(obj, path, fallback = undefined) {
            try {
                const parts = path.split('.');
                let cur = obj;
                for (const p of parts) {
                    if (cur == null) return fallback;
                    cur = cur[p];
                }
                return cur ?? fallback;
            } catch (e) {
                return fallback;
            }
        },
    };
})();
