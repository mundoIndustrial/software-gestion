/**
 * PrendaCardRenderers
 * Renderizadores de HTML para PrendaCardService.
 */
window.PrendaCardRenderers = {
    renderTarjeta(datos) {
        const {
            prenda,
            indice,
            numeroItem,
            fotoPrincipal,
            imagenes,
            descripcion,
            tablaTelasHTML,
            variacionesHTML,
            tallasYCantidadesHTML,
            procesosHTML
        } = datos;

        return `
            <div class="prenda-card-readonly" data-prenda-index="${indice}" data-prenda-id="${prenda.id || ''}">
                <div class="prenda-card-header">
                    <div class="prenda-card-title-section">
                        <span class="prenda-label">Prenda ${numeroItem}</span>
                        <h3 class="prenda-name">${prenda.nombre_prenda || prenda.nombre_producto || 'Sin nombre'}</h3>
                    </div>
                    
                    <div class="prenda-menu-contextual">
                        <button class="btn-menu-tres-puntos" type="button" data-prenda-index="${indice}">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="submenu-prenda" style="display: none;">
                            <button class="submenu-option btn-editar-prenda" type="button" data-prenda-index="${indice}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="submenu-option btn-eliminar-prenda" type="button" data-prenda-index="${indice}">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="prenda-card-content">
                    <div class="foto-prenda-izquierda">
                        ${fotoPrincipal ? `
                            <div style="position: relative; display: inline-block;">
                                <img 
                                    src="${fotoPrincipal}" 
                                    alt="${prenda.nombre_prenda || prenda.nombre_producto || 'Prenda'}" 
                                    class="foto-principal-readonly"
                                    data-prenda-index="${indice}"
                                    data-foto-index="0"
                                    style="cursor: pointer; width: 120px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                    onmouseover="this.style.boxShadow='0 4px 16px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9'; this.parentElement.querySelector('.foto-overlay-icon').style.opacity='1';"
                                    onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'; this.style.borderColor='#e5e7eb'; this.parentElement.querySelector('.foto-overlay-icon').style.opacity='0';"
                                />
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0; transition: opacity 0.2s; pointer-events: none; background: rgba(0,0,0,0.4); width: 100%; height: 100%; border-radius: 8px; display: flex; align-items: center; justify-content: center;" class="foto-overlay-icon">
                                    <i class="fas fa-search-plus" style="font-size: 2rem; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"></i>
                                </div>
                                ${imagenes && imagenes.length > 1 ? `<span style="position: absolute; top: 5px; right: 5px; background: rgba(14,165,233,0.9); color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><i class="fas fa-images"></i> ${imagenes.length}</span>` : ''}
                            </div>
                        ` : `
                            <div style="width: 120px; height: 150px; background: #f3f4f6; border-radius: 8px; border: 2px dashed #d1d5db; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af; gap: 0.5rem;">
                                <i class="fas fa-image" style="font-size: 2rem;"></i>
                                <small>Sin foto</small>
                            </div>
                        `}
                    </div>

                    <div class="prenda-card-info">
                        ${descripcion ? `<p class="prenda-descripcion">${descripcion}</p>` : ''}

                        ${tablaTelasHTML}

                        ${variacionesHTML}
                        ${tallasYCantidadesHTML}
                        ${procesosHTML}
                    </div>
                </div>
            </div>
        `;
    },

    renderTablaTelas({ prenda, normalizarTelas, imageConverter }) {
        let telas = [];

        if (prenda.telasAgregadas) {
            telas = normalizarTelas(prenda.telasAgregadas);
        } else if (prenda.telas) {
            telas = normalizarTelas(prenda.telas);
        } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            telas = prenda.imagenes_tela.map((img) => ({
                tela: prenda.tela || 'N/A',
                color: prenda.color || 'N/A',
                referencia: prenda.referencia || prenda.ref || 'N/A',
                imagenes: [img]
            }));
        }

        if (telas.length === 0) {
            return '';
        }

        const tablaTelasHTML = telas.map((telaItem, telaIndex) => {
            const nombreTela = telaItem.tela || telaItem.nombre_tela || 'N/A';
            const color = telaItem.color || 'N/A';
            const referencia = telaItem.referencia || telaItem.ref || 'N/A';
            const telaFoto = imageConverter ? imageConverter.obtenerImagenTela(telaItem) : null;

            return `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${nombreTela}</td>
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${color}</td>
                    <td style="padding: 0.75rem; color: #1f2937; font-weight: 500;">${referencia}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        ${telaFoto ? `
                            <img 
                                src="${telaFoto}" 
                                alt="Tela ${telaIndex}" 
                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; cursor: pointer;"
                                onmouseover="this.style.boxShadow='0 2px 8px rgba(14,165,233,0.3)'; this.style.borderColor='#0ea5e9';"
                                onmouseout="this.style.boxShadow='none'; this.style.borderColor='#e5e7eb';"
                            />
                        ` : `
                            <div style="width: 50px; height: 50px; background: #f3f4f6; border-radius: 4px; border: 1px dashed #d1d5db; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                <i class="fas fa-image" style="font-size: 0.8rem;"></i>
                            </div>
                        `}
                    </td>
                </tr>
            `;
        }).join('');

        return `
            <div class="prenda-specs-horizontal" style="margin-top: 1rem; margin-bottom: 1rem;">
                <div style="width: 100%; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">TELA</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">COLOR</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">REF</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.75rem;">IMAGEN</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tablaTelasHTML}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    renderProcesoCard({ icono, nombreProceso, badgeHtml = '', contentHtml = '' }) {
        return `
            <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                    <span style="font-size: 1.5rem;">${icono}</span>
                    <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                    ${badgeHtml}
                </div>
                ${contentHtml}
            </div>
        `;
    },

    renderProcesosSection({ indice, procesosCount, procesosItemsHTML }) {
        return `
            <div class="seccion-expandible procesos-section">
                <button class="seccion-expandible-header" type="button" data-section="procesos" data-prenda-index="${indice}">
                    <h4>Procesos <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="procesos-count">${procesosCount}</span>)</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content procesos-content">
                    <div style="padding: 1rem;">
                        ${procesosItemsHTML}
                    </div>
                </div>
            </div>
        `;
    },

    resolverSrcImagen(raw) {
        if (!raw) return '';
        if (raw instanceof File) return URL.createObjectURL(raw);
        if (typeof raw === 'string') return raw;
        if (typeof raw === 'object') {
            return raw.previewUrl || raw.dataURL || raw.src || raw.url || raw.blobUrl || raw.ruta_webp || raw.ruta_original || raw.ruta || raw.path || '';
        }
        return '';
    },

    renderThumbs({ items = [], imageStyle, wrapperStyle = '', toSrc, imageTagBuilder }) {
        const mapToSrc = typeof toSrc === 'function' ? toSrc : (item) => this.resolverSrcImagen(item);
        return items.map((item, idx) => {
            const src = mapToSrc(item, idx);
            if (!src) return '';
            if (typeof imageTagBuilder === 'function') {
                return imageTagBuilder(src, idx, item);
            }
            return `<img src="${src}" style="${imageStyle}">`;
        }).filter(Boolean).join('');
    },

    renderBloqueUbicaciones({ ubicacionesHTML = '' }) {
        if (!ubicacionesHTML) return '';
        return `
            <div style="margin-bottom: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                </strong>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    ${ubicacionesHTML}
                </div>
            </div>
        `;
    },

    renderUbicacionesChips(ubicaciones = []) {
        if (!Array.isArray(ubicaciones) || ubicaciones.length === 0) return '';
        return ubicaciones
            .map((ubi) => {
                const texto = typeof ubi === 'object' && ubi !== null && ubi.ubicacion
                    ? ubi.ubicacion
                    : (typeof ubi === 'string' ? ubi : '');
                if (!texto) return '';
                return `<span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${texto}
                </span>`;
            })
            .filter(Boolean)
            .join('');
    },

    renderBloqueObservaciones({ texto = '' }) {
        if (!texto) return '';
        return `
            <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">
                    <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                </strong>
                <span style="color: #78350f; font-size: 0.9rem;">${texto}</span>
            </div>
        `;
    },

    renderBloqueImagenes({ titulo = 'Imágenes', total = 0, thumbsHTML = '' }) {
        if (!thumbsHTML) return '';
        return `
            <div style="margin-top: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>${titulo} (${total}):
                </strong>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    ${thumbsHTML}
                </div>
            </div>
        `;
    },

    renderTallasGeneralesProceso({ damaObj = {}, caballeroObj = {}, sobremedidaObj = {} }) {
        const damaHasTallas = Object.keys(damaObj).length > 0;
        const caballeroHasTallas = Object.keys(caballeroObj).length > 0;
        const sobremedidaHasTallas = Object.keys(sobremedidaObj).length > 0;

        if (!damaHasTallas && !caballeroHasTallas && !sobremedidaHasTallas) return '';

        const generosConfig = {
            dama: { label: 'DAMA', icon: '<i class="fas fa-female" style="color: #be185d;"></i>', color: '#be185d' },
            caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male" style="color: #1d4ed8;"></i>', color: '#1d4ed8' },
            sobremedida: { label: 'SOBREMEDIDA', icon: '<i class="fas fa-ruler" style="color: #92400e;"></i>', color: '#92400e' }
        };

        let tallasGeneralesHTML = '<div style="margin-top: 0.75rem;">';

        [
            { genero: 'dama', obj: damaObj },
            { genero: 'caballero', obj: caballeroObj },
            { genero: 'sobremedida', obj: sobremedidaObj }
        ].forEach(({ genero, obj }) => {
            if (Object.keys(obj).length === 0) return;
            const cfg = generosConfig[genero];

            let tallaCards = '';
            Object.entries(obj).forEach(([tallaKey, cantidad]) => {
                const parts = String(tallaKey).split('__');
                const talla = parts[0] || tallaKey;

                tallaCards += `
                    <div style="min-width: 140px; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.6rem; background: #ffffff;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="font-weight: 900; color: #0f172a;">${talla}</span>
                            </div>
                            <span style="background: ${cfg.color}; color: white; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 900; font-size: 0.75rem;">${cantidad}</span>
                        </div>
                    </div>
                `;
            });

            if (tallaCards) {
                tallasGeneralesHTML += `
                    <div style="margin-top: 0.6rem;">
                        <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 900; color: ${cfg.color}; margin-bottom: 0.4rem;">
                            ${cfg.icon}
                            <span>${cfg.label}</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                            ${tallaCards}
                        </div>
                    </div>
                `;
            }
        });

        tallasGeneralesHTML += '</div>';
        return tallasGeneralesHTML;
    },

    _agruparTallasConColores(obj = {}) {
        const agrupado = {};
        Object.entries(obj || {}).forEach(([tallaKey, cantidad]) => {
            const parts = String(tallaKey).split('__');
            const talla = String(parts[0] || '').trim().toUpperCase();
            const color = parts[1] ? String(parts[1]).trim().toUpperCase() : null;
            const qty = parseInt(cantidad, 10) || 0;
            if (!talla || qty <= 0) return;

            if (!agrupado[talla]) {
                agrupado[talla] = { total: 0, colores: [] };
            }
            agrupado[talla].total += qty;
            if (color) {
                const existente = agrupado[talla].colores.find((c) => c.color === color);
                if (existente) {
                    existente.cantidad += qty;
                } else {
                    agrupado[talla].colores.push({ color, cantidad: qty });
                }
            }
        });
        return agrupado;
    },

    _renderGeneroTallasConColores({ titulo, iconHtml, colorTitulo, bgBadge, colorBadge, tallasObjAgrupado }) {
        const tallasEntries = Object.entries(tallasObjAgrupado || {});
        if (tallasEntries.length === 0) return '';

        const cards = tallasEntries.map(([talla, info]) => {
            const coloresHTML = (info.colores && info.colores.length > 0)
                ? info.colores.map((c) => {
                    return `
                        <div style="display: flex; align-items: center; gap: 0.35rem; color: #1f2937; font-size: 0.75rem; font-weight: 600;">
                            <span style="width: 6px; height: 6px; border-radius: 999px; background: ${colorBadge}; display: inline-block;"></span>
                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">${c.color}</span>
                            <span style="color: #6b7280; font-weight: 700;">×${c.cantidad}</span>
                        </div>
                    `;
                }).join('')
                : '';

            return `
                <div style="min-width: 140px; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.6rem; background: #ffffff;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <span style="font-weight: 900; color: #0f172a;">${talla}</span>
                        </div>
                        <span style="background: ${bgBadge}; color: white; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 900; font-size: 0.75rem;">${info.total}</span>
                    </div>
                    ${coloresHTML ? `
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.25rem; padding-top: 0.35rem; border-top: 1px solid #e5e7eb;">
                            ${coloresHTML}
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        return `
            <div style="margin-top: 0.6rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 900; color: ${colorTitulo}; margin-bottom: 0.4rem;">
                    ${iconHtml}
                    <span>${titulo}</span>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                    ${cards}
                </div>
            </div>
        `;
    },

    renderTallasGenericoProceso({ damaObj = {}, caballeroObj = {} }) {
        const damaHasTallas = Object.keys(damaObj).length > 0;
        const caballeroHasTallas = Object.keys(caballeroObj).length > 0;
        if (!damaHasTallas && !caballeroHasTallas) return '';

        const damaAgr = this._agruparTallasConColores(damaObj);
        const cabAgr = this._agruparTallasConColores(caballeroObj);

        let tallasHTML = '<div style="margin-top: 0.75rem;">';
        tallasHTML += this._renderGeneroTallasConColores({
            titulo: 'DAMA',
            iconHtml: '<i class="fas fa-female" style="color: #be185d;"></i>',
            colorTitulo: '#be185d',
            bgBadge: '#be185d',
            colorBadge: '#be185d',
            tallasObjAgrupado: damaAgr
        });
        tallasHTML += this._renderGeneroTallasConColores({
            titulo: 'CABALLERO',
            iconHtml: '<i class="fas fa-male" style="color: #1d4ed8;"></i>',
            colorTitulo: '#1d4ed8',
            bgBadge: '#1d4ed8',
            colorBadge: '#1d4ed8',
            tallasObjAgrupado: cabAgr
        });
        tallasHTML += '</div>';
        return tallasHTML;
    },

    renderProcesoPorTallasItems({ generos = {}, datos = {} }) {
        let porTallasHTML = '';

        Object.entries(generos).forEach(([genero, cfg]) => {
            const tallasGenero = datos.datosExtendidos?.[genero];
            if (!tallasGenero || Object.keys(tallasGenero).length === 0) return;

            Object.entries(tallasGenero).forEach(([tallaKey, datosTalla]) => {
                const parts = String(tallaKey).split('__');
                const tallaDisplay = parts[0] || tallaKey;
                const cantidad = datos.tallas?.[genero]?.[tallaKey] ?? '';

                let ubicHTML = '';
                if (datosTalla.ubicaciones && datosTalla.ubicaciones.length > 0) {
                    const chips = datosTalla.ubicaciones.map((u) => {
                        const texto = typeof u === 'string' ? u : (u?.ubicacion || '');
                        return texto ? `<span style="background: ${cfg.bg}; color: ${cfg.color}; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; display: inline-block;">${texto}</span>` : '';
                    }).filter(Boolean).join('');
                    if (chips) {
                        ubicHTML = `<div style="margin-top: 0.35rem;"><span style="font-size: 0.75rem; color: #6b7280; font-weight: 600;"><i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>Ubicaciones:</span><div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.2rem;">${chips}</div></div>`;
                    }
                }

                let obsHTML = '';
                if (datosTalla.observaciones) {
                    obsHTML = `<div style="margin-top: 0.35rem; font-size: 0.8rem; color: #374151; background: #f9fafb; padding: 0.35rem 0.5rem; border-radius: 4px; border-left: 2px solid ${cfg.color};"><i class="fas fa-sticky-note" style="margin-right: 0.25rem; color: ${cfg.color};"></i>${datosTalla.observaciones}</div>`;
                }

                let imgHTML = '';
                const imgs = datosTalla.imagenes || [];
                if (imgs.length > 0) {
                    const thumbs = this.renderThumbs({
                        items: imgs,
                        toSrc: (img) => this.resolverSrcImagen(img),
                        imageTagBuilder: (src) => `<img src="${src}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid ${cfg.border};">`
                    });
                    if (thumbs) {
                        imgHTML = `<div style="display: flex; gap: 0.3rem; margin-top: 0.35rem;">${thumbs}</div>`;
                    }
                }

                porTallasHTML += `
                    <div style="border: 1px solid ${cfg.border}; border-radius: 8px; padding: 0.6rem; background: ${cfg.bg}; min-width: 180px; flex: 1;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem;">
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <span style="color: ${cfg.color};">${cfg.icon}</span>
                                <span style="font-weight: 900; color: #0f172a;">${tallaDisplay}</span>
                            </div>
                            ${cantidad ? `<span style="background: ${cfg.color}; color: white; padding: 0.1rem 0.45rem; border-radius: 6px; font-weight: 900; font-size: 0.75rem;">${cantidad}</span>` : ''}
                        </div>
                        ${ubicHTML}
                        ${obsHTML}
                        ${imgHTML}
                    </div>
                `;
            });
        });

        return porTallasHTML;
    },

    renderObservacionesPorTallaProceso({ datosExtendidos = {}, tallas = {} }) {
        const tieneObservacionesPorTalla = Object.keys(datosExtendidos).some((genero) =>
            datosExtendidos[genero] && Object.values(datosExtendidos[genero]).some((d) => d.observaciones)
        );
        if (!tieneObservacionesPorTalla) return '';

        const generosConfigObs = {
            dama: { label: 'DAMA', color: '#be185d' },
            caballero: { label: 'CABALLERO', color: '#1d4ed8' },
            sobremedida: { label: 'SOBREMEDIDA', color: '#92400e' }
        };

        let tarjetasObs = '';
        Object.entries(generosConfigObs).forEach(([genero, cfg]) => {
            const extendidosGenero = datosExtendidos[genero] || {};
            const tallasGenero = tallas[genero] || {};

            Object.entries(extendidosGenero).forEach(([tallaKey, detalle]) => {
                const observacion = detalle?.observaciones || '';
                if (!observacion) return;

                const parts = String(tallaKey).split('__');
                const talla = parts[0] || tallaKey;
                const cantidad = tallasGenero[tallaKey] || 0;

                tarjetasObs += `
                    <div style="border: 1px solid #fce7f3; border-radius: 6px; background: white; padding: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem;">
                        <div style="display: flex; align-items: center; gap: 0.3rem;">
                            <span style="font-weight: 700; font-size: 0.8rem; color: ${cfg.color};">${cfg.label} - ${talla}</span>
                            <span style="font-size: 0.65rem; background: ${cfg.color}; color: white; padding: 0.1rem 0.3rem; border-radius: 9999px; font-weight: 700;">${cantidad}</span>
                        </div>
                        <div style="padding: 0.3rem 0.5rem; background: #fef3c7; border-left: 2px solid #f59e0b; border-radius: 4px; font-size: 0.7rem; color: #78350f;">${observacion}</div>
                    </div>
                `;
            });
        });

        if (!tarjetasObs) return '';

        return `
            <div style="margin-top: 0.75rem;">
                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                    <i class="fas fa-sticky-note" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Observaciones por Talla:
                </strong>
                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    ${tarjetasObs}
                </div>
            </div>
        `;
    },

    renderProcesoFotosGenerales({
        fotos = [],
        nombreProceso = '',
        titulo = 'Imágenes',
        total = null,
        toSrc,
        onClickAttrBuilder
    }) {
        if (!Array.isArray(fotos) || fotos.length === 0) return '';

        const thumbsHTML = this.renderThumbs({
            items: fotos,
            toSrc: typeof toSrc === 'function' ? toSrc : (img) => this.resolverSrcImagen(img),
            imageTagBuilder: (src) => {
                const onClickAttr = typeof onClickAttrBuilder === 'function' ? onClickAttrBuilder(src) : '';
                return `<img src="${src}" alt="Imagen ${nombreProceso}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;" ${onClickAttr}>`;
            }
        });

        if (!thumbsHTML) return '';

        return this.renderBloqueImagenes({
            titulo,
            total: total ?? fotos.length,
            thumbsHTML
        });
    },

    renderProcesoGeneralCard({
        icono,
        nombreProceso,
        ubicacionGeneralHTML = '',
        tallasGeneralesHTML = '',
        observacionesGeneralesHTML = '',
        fotosGeneralesHTML = '',
        observacionesPorTallaHTML = ''
    }) {
        return this.renderProcesoCard({
            icono,
            nombreProceso,
            contentHtml: `
                ${ubicacionGeneralHTML}
                ${tallasGeneralesHTML}
                ${observacionesGeneralesHTML}
                ${fotosGeneralesHTML}
                ${observacionesPorTallaHTML}
            `
        });
    },

    renderProcesoGenericoCard({
        icono,
        nombreProceso,
        datos = {},
        onClickAttrBuilder
    }) {
        const ubicacionesHTML = this.renderUbicacionesChips(datos.ubicaciones || []);

        let tallasHTML = '';
        if (datos.tallas) {
            const damaObj = datos.tallas.dama || {};
            const caballeroObj = datos.tallas.caballero || {};
            tallasHTML = this.renderTallasGenericoProceso({
                damaObj,
                caballeroObj
            });
        }

        let observacionesHTML = '';
        if (datos.observaciones) {
            observacionesHTML = this.renderBloqueObservaciones({
                texto: datos.observaciones
            });
        }

        let imagenHTML = '';
        const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
        if (imagenes.length > 0) {
            const thumbsHTML = this.renderThumbs({
                items: imagenes,
                toSrc: (img) => {
                    let imgSrc = this.resolverSrcImagen(img);
                    if (imgSrc && !imgSrc.startsWith('/') && !imgSrc.startsWith('http') && !imgSrc.startsWith('blob:') && !imgSrc.startsWith('data:')) {
                        imgSrc = '/storage/' + imgSrc;
                    }
                    return imgSrc;
                },
                imageTagBuilder: (imgSrc) => {
                    const onClickAttr = typeof onClickAttrBuilder === 'function' ? onClickAttrBuilder(imgSrc) : '';
                    return `<img src="${imgSrc}" alt="Imagen ${nombreProceso}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;" ${onClickAttr}>`;
                }
            });

            imagenHTML = this.renderBloqueImagenes({
                titulo: 'Imágenes',
                total: imagenes.length,
                thumbsHTML
            });
        }

        return this.renderProcesoCard({
            icono,
            nombreProceso,
            contentHtml: `
                ${this.renderBloqueUbicaciones({ ubicacionesHTML })}
                ${tallasHTML}
                ${observacionesHTML}
                ${imagenHTML}
            `
        });
    }
};


