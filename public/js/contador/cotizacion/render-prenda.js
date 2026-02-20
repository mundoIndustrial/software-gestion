(function () {
    if (typeof window === 'undefined') return;

    const H = window.CotizacionModalHelpers;

    function renderSelector({ esCombinada, esSoloLogoFinal, modo, hideSelector }) {
        if (!esCombinada || hideSelector || esSoloLogoFinal) return '';

        const isPrenda = modo === 'prenda';
        const isLogo = modo === 'logo';

        return `
            <div style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem;">
                <button type="button" id="cotModalBtnPrenda" style="padding: 0.6rem 0.9rem; border-radius: 8px; border: 2px solid ${isPrenda ? '#1e5ba8' : '#cbd5e1'}; background: ${isPrenda ? '#1e5ba8' : '#ffffff'}; color: ${isPrenda ? '#ffffff' : '#0f172a'}; font-weight: 800; cursor: pointer;">
                    Cotización Prenda
                </button>
                <button type="button" id="cotModalBtnLogo" style="padding: 0.6rem 0.9rem; border-radius: 8px; border: 2px solid ${isLogo ? '#1e5ba8' : '#cbd5e1'}; background: ${isLogo ? '#1e5ba8' : '#ffffff'}; color: ${isLogo ? '#ffffff' : '#0f172a'}; font-weight: 800; cursor: pointer;">
                    Cotización Logo
                </button>
            </div>
        `;
    }

    function renderTipoVenta(payload, { esCombinada, modo }) {
        let tipoVenta = null;

        if (payload.logo_cotizacion && payload.logo_cotizacion.tipo_venta && ((esCombinada && modo === 'logo') || (!payload.tiene_prendas && payload.tiene_logo))) {
            tipoVenta = payload.logo_cotizacion.tipo_venta;
        } else if (payload.cotizacion && payload.cotizacion.tipo_venta) {
            tipoVenta = payload.cotizacion.tipo_venta;
        }

        if (!tipoVenta) return '';

        return `
            <div style="display: inline-block; text-align: left; margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 8px;">
                <span style="color: #000000; font-size: 1.1rem; font-weight: 600;">Por favor cotizar al</span>
                <span style="color: #dc2626; font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">${tipoVenta}</span>
            </div>
        `;
    }

    /**
     * Vista LOGO por prenda:
     * Solo renderiza imágenes de logo asociadas a esta prenda (sin telas/tallas/variaciones).
     * Nota: NO cierra el contenedor de la card; el caller debe cerrar su wrapper.
     */
    function renderSoloLogoPorPrenda(payload, prenda) {
        const imagenesLogo = [];
        const logosPorUrl = new Map();

        const normalizarUrlLogo = (H && typeof H.normalizarUrlLogo === 'function')
            ? H.normalizarUrlLogo
            : ((u) => (u ? String(u).trim() : ''));

        if (payload && payload.logo_cotizacion && Array.isArray(payload.logo_cotizacion.tecnicas_prendas)) {
            payload.logo_cotizacion.tecnicas_prendas.forEach(tp => {
                if (tp && tp.prenda_id === prenda.id && tp.fotos && tp.fotos.length > 0) {
                    tp.fotos.forEach((foto) => {
                        if (!foto || !foto.url) return;
                        const urlKey = normalizarUrlLogo(foto.url) || String(foto.url);
                        if (!urlKey) return;
                        if (!logosPorUrl.has(urlKey)) {
                            logosPorUrl.set(urlKey, urlKey);
                        }
                    });
                }
            });
        }

        if (logosPorUrl.size > 0) {
            Array.from(logosPorUrl.values()).forEach((url) => {
                imagenesLogo.push(url);
            });
        }

        if (imagenesLogo.length === 0) {
            return '';
        }

        let html = `
            <div style="margin-top: 1.25rem; display: flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-start;">
        `;

        imagenesLogo.forEach((url, idx) => {
            html += `
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <img src="${url}"
                         alt="Logo"
                         data-gallery="galeria-logo-${prenda.id}"
                         data-index="${idx}"
                         style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #1e5ba8; cursor: pointer; transition: all 0.3s;"
                         onclick="abrirImagenGrande('${url}', 'galeria-logo-${prenda.id}', ${idx})"
                         onmouseover="this.style.boxShadow='0 4px 12px rgba(30, 91, 168, 0.4)'; this.style.transform='scale(1.05)';"
                         onmouseout="this.style.boxShadow='none'; this.style.transform='scale(1)';"/>
                    <div style="margin-top: 0.5rem; background: linear-gradient(to right, #1e5ba8, #1e5ba8); padding: 0.5rem 0.75rem; border-radius: 4px; color: white; text-align: center; font-weight: 600; font-size: 0.7rem; white-space: nowrap;">
                        Logo
                    </div>
                </div>
            `;
        });

        html += `
            </div>
        `;

        return html;
    }

    function renderVariacionesTecnicasLogo(payload, prenda) {
        if (!payload || !payload.logo_cotizacion || !Array.isArray(payload.logo_cotizacion.tecnicas_prendas)) {
            return '';
        }

        const tecnicasPrendaArray = payload.logo_cotizacion.tecnicas_prendas.filter(tp => tp && tp.prenda_id === prenda.id);
        if (!tecnicasPrendaArray || tecnicasPrendaArray.length === 0) {
            return '';
        }

        let html = '';

        // Consolidar variaciones
        const variacionesFormateadas = {};
        tecnicasPrendaArray.forEach(tp => {
            if (tp.variaciones_prenda && typeof tp.variaciones_prenda === 'object') {
                for (const [opcionNombre, detalles] of Object.entries(tp.variaciones_prenda)) {
                    if (typeof detalles === 'object' && detalles && detalles.opcion) {
                        const nombreFormato = opcionNombre.charAt(0).toUpperCase() + opcionNombre.slice(1).replace(/_/g, ' ');
                        if (!variacionesFormateadas[nombreFormato]) {
                            variacionesFormateadas[nombreFormato] = detalles;
                        }
                    }
                }
            }
        });

        if (Object.keys(variacionesFormateadas).length > 0) {
            html += `
                <div style="margin-top: 1rem;">
                    <h6 style="color: #1e5ba8; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES</h6>
                    <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 200px; border-right: 1px solid rgba(255,255,255,0.2);">Tipo</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 250px; border-right: 1px solid rgba(255,255,255,0.2);">Valor</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; min-width: 200px;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            for (const [tipo, datos] of Object.entries(variacionesFormateadas)) {
                const opcion = datos.opcion || '-';
                const observacion = datos.observacion || '-';
                html += `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600; color: #0f172a;">${tipo}</td>
                        <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; color: #0ea5e9; font-weight: 500;">${opcion}</td>
                        <td style="padding: 0.75rem; color: #64748b;">${observacion}</td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        // TALLAS Y CANTIDADES
        const tieneTallasCantidad = tecnicasPrendaArray.some(tp => tp.talla_cantidad && (Array.isArray(tp.talla_cantidad) ? tp.talla_cantidad.length > 0 : Object.keys(tp.talla_cantidad).length > 0));
        if (tieneTallasCantidad) {
            const tallasSet = new Set();
            tecnicasPrendaArray.forEach(tp => {
                if (tp.talla_cantidad) {
                    let tallaArray = [];
                    if (Array.isArray(tp.talla_cantidad)) {
                        tallaArray = tp.talla_cantidad;
                    } else if (typeof tp.talla_cantidad === 'object') {
                        tallaArray = Object.values(tp.talla_cantidad);
                    }
                    tallaArray.forEach(item => {
                        if (item && item.talla) {
                            tallasSet.add(item.talla);
                        }
                    });
                }
            });

            if (tallasSet.size > 0) {
                const tallasTexto = Array.from(tallasSet).join(',');
                html += `
                    <div style="margin-top: 1rem;">
                        <span style="color: #1e5ba8; font-weight: 600; font-size: 0.95rem;">TALLAS </span>
                        <span id="tallas-texto-${prenda.id}"
                              data-prenda-id="${prenda.id}"
                              data-cotizacion-id="${payload.cotizacion.id}"
                              ondblclick="editarTallasConParentesis(this)"
                              style="color: #dc2626; font-weight: 700; font-size: 1rem; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 4px; transition: all 0.2s; display: inline-block;"
                              onmouseover="this.style.backgroundColor='#fee2e2'"
                              onmouseout="this.style.backgroundColor='transparent'"
                              title="Doble click para editar">
                            ${tallasTexto} ()
                        </span>
                    </div>
                `;
            }
        }

        return html;
    }

    function renderVariacionesEspecificas(prenda) {
        if (!prenda || !prenda.variantes || prenda.variantes.length === 0) {
            return '';
        }

        let html = `
            <div style="margin-top: 1.5rem;">
                <h6 style="color: #1e5ba8; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES ESPECIFICAS</h6>
                <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem; min-width: 150px;">Variación</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem; min-width: 200px;">Tipo</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        const variante = prenda.variantes[0];

        const filas = [];
        if (variante.tipo_prenda) filas.push({ variacion: 'Tipo Prenda', tipo: variante.tipo_prenda, obs: '' });
        if (variante.tipo_jean_pantalon) filas.push({ variacion: 'Tipo Jean/Pantalón', tipo: variante.tipo_jean_pantalon, obs: '' });

        if (variante.tipo_manga_id || variante.tipo_manga) {
            let tipo = variante.tipo_manga_nombre || variante.tipo_manga || 'Sin especificar';
            filas.push({ variacion: 'Tipo Manga', tipo: tipo, obs: variante.obs_manga || '' });
        }

        if (variante.tiene_bolsillos !== null && variante.tiene_bolsillos) {
            let obs = variante.obs_bolsillos || '';
            filas.push({ variacion: 'Bolsillos', tipo: 'Sí', obs: obs });
        } else if (variante.obs_bolsillos) {
            filas.push({ variacion: 'Bolsillos', tipo: 'Sí', obs: variante.obs_bolsillos });
        }

        if (variante.aplica_broche !== null && variante.aplica_broche) {
            let tipo = variante.tipo_broche_nombre || variante.tipo_broche || 'Sí';
            let obs = variante.obs_broche || '';
            filas.push({ variacion: 'Broche', tipo: tipo, obs: obs });
        } else if (variante.obs_broche) {
            let tipo = variante.tipo_broche_nombre || variante.tipo_broche || 'Sí';
            filas.push({ variacion: 'Broche', tipo: tipo, obs: variante.obs_broche });
        }

        if (variante.tiene_reflectivo !== null && variante.tiene_reflectivo) {
            let obs = variante.obs_reflectivo || '';
            filas.push({ variacion: 'Reflectivo', tipo: 'Sí', obs: obs });
        } else if (variante.obs_reflectivo) {
            filas.push({ variacion: 'Reflectivo', tipo: 'Sí', obs: variante.obs_reflectivo });
        }

        filas.forEach((fila, idx) => {
            html += `
                <tr style="border-bottom: 1px solid #e2e8f0; ${idx % 2 === 0 ? 'background: #f9fafb;' : ''}">
                    <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; font-weight: 600; color: #0f172a; font-size: 0.85rem;">${fila.variacion}</td>
                    <td style="padding: 0.75rem; border-right: 1px solid #e2e8f0; color: #0ea5e9; font-weight: 500; font-size: 0.85rem;">${fila.tipo}</td>
                    <td style="padding: 0.75rem; color: #64748b; font-size: 0.85rem;">${fila.obs}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        return html;
    }

    function renderVariacionesReflectivo(prenda) {
        if (!prenda || !prenda.reflectivo) {
            return '';
        }

        const reflectivo = prenda.reflectivo;
        if (!reflectivo.variaciones || Object.keys(reflectivo.variaciones).length === 0) {
            return '';
        }

        let html = `
            <div style="margin-top: 1.5rem;">
                <h6 style="color: #ef4444; font-weight: 600; margin: 0 0 0.75rem 0; font-size: 0.95rem;">VARIACIONES REFLECTIVO</h6>
                <table style="border-collapse: collapse; table-layout: auto; width: 100%; border: 1px solid #fecaca; border-radius: 4px; overflow: hidden;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white;">
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2); font-size: 0.85rem;">Propiedad</th>
                            <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        for (const [key, value] of Object.entries(reflectivo.variaciones)) {
            let displayValue = '-';
            if (typeof value === 'string') {
                displayValue = value;
            } else if (typeof value === 'object' && value !== null) {
                displayValue = Object.values(value).filter(v => v).join(', ');
            }

            html += `
                <tr style="border-bottom: 1px solid #fecaca;">
                    <td style="padding: 0.75rem; border-right: 1px solid #fecaca; font-weight: 600; color: #7f1d1d; font-size: 0.85rem;">${key}</td>
                    <td style="padding: 0.75rem; color: #991b1b; font-weight: 500; font-size: 0.85rem;">${displayValue}</td>
                </tr>
            `;
        }

        html += `
                    </tbody>
                </table>
            </div>
        `;

        return html;
    }

    function renderDescripcionPrenda(payload, prenda, modo) {
        // En vista LOGO: mostrar SOLO las ubicaciones/técnicas de logo.
        // No usar la descripción concatenada de la prenda porque puede incluir reflectivo u otros textos.
        if (modo === 'logo') {
            const tecnicasPrendaArray = (payload.logo_cotizacion && Array.isArray(payload.logo_cotizacion.tecnicas_prendas))
                ? payload.logo_cotizacion.tecnicas_prendas.filter(tp => tp && tp.prenda_id === prenda.id)
                : [];

            if (!tecnicasPrendaArray || tecnicasPrendaArray.length === 0) {
                return '-';
            }

            // Consolidar ubicaciones por técnica
            const ubicacionesPorTecnica = {};
            tecnicasPrendaArray.forEach(tp => {
                const tecnicaNombre = (tp && tp.tipo_logo_nombre) ? tp.tipo_logo_nombre : 'Logo';
                if (!tp || !tp.ubicaciones) {
                    return;
                }
                let ubicacionesArray = Array.isArray(tp.ubicaciones)
                    ? tp.ubicaciones
                    : [String(tp.ubicaciones)];
                ubicacionesArray = ubicacionesArray
                    .map(u => String(u).replace(/[\[\]"']/g, '').trim())
                    .filter(u => u);
                if (ubicacionesArray.length === 0) {
                    return;
                }
                if (!ubicacionesPorTecnica[tecnicaNombre]) {
                    ubicacionesPorTecnica[tecnicaNombre] = [];
                }
                ubicacionesPorTecnica[tecnicaNombre] = ubicacionesPorTecnica[tecnicaNombre].concat(ubicacionesArray);
            });

            const ubicacionesTexto = Object.entries(ubicacionesPorTecnica)
                .map(([_, ubicaciones]) => ubicaciones.join(', '))
                .join(', ');

            return (ubicacionesTexto || '-').replace(/\n/g, '<br>');
        }

        let descripcionCompleta = prenda.descripcion_formateada || prenda.descripcion || '';

        // LIMPIEZA: Remover Bolsillos y Broche/Botón de la descripción concatenada
        // Patrón 1: Bolsillos: ... (sin viñeta, hasta siguiente palabra clave)
        descripcionCompleta = descripcionCompleta.replace(/Bolsillos:\s*[^,]*(?:,\s*)?/gi, '');
        // Patrón 2: Botón: ... o Broche: ... (sin viñeta, hasta siguiente palabra clave)
        descripcionCompleta = descripcionCompleta.replace(/(Botón|Broche):\s*[^,]*(?:,\s*)?/gi, '');
        // Patrón 3: • BOLSILLOS: ... (si tiene viñeta)
        descripcionCompleta = descripcionCompleta.replace(/\s*•\s*BOLSILLOS:.*?(?=•|$)/gi, '');
        // Patrón 4: • BROCHE: ... o • BOTÓN: ... (si tiene viñeta)
        descripcionCompleta = descripcionCompleta.replace(/\s*•\s*(BROCHE|BOTÓN):.*?(?=•|$)/gi, '');
        // Limpiar espacios múltiples y comas al inicio/final
        descripcionCompleta = descripcionCompleta.replace(/\s+/g, ' ').replace(/^,\s*|,\s*$/g, '').trim();

        // Agregar descripción y ubicaciones de prenda_cot_reflectivo SOLO en vista PRENDA.
        if (prenda && prenda.prenda_cot_reflectivo) {
            const pcrRef = prenda.prenda_cot_reflectivo;

            // Agregar descripción del reflectivo
            if (pcrRef.descripcion) {
                if (descripcionCompleta) {
                    descripcionCompleta += ', ';
                }
                descripcionCompleta += pcrRef.descripcion;
            }

            // Agregar ubicaciones del reflectivo SIN negrita
            if (pcrRef.ubicaciones && Array.isArray(pcrRef.ubicaciones)) {
                if (descripcionCompleta) {
                    descripcionCompleta += ', ';
                }
                const ubicacionesReflectivo = pcrRef.ubicaciones
                    .map(u => u.ubicacion ? u.ubicacion + (u.descripcion ? ': ' + u.descripcion : '') : '')
                    .filter(u => u)
                    .join(', ');
                descripcionCompleta += ubicacionesReflectivo;
            }
        }

        return descripcionCompleta.replace(/\n/g, '<br>') || '-';
    }

    function renderTallasPrenda(payload, prenda) {
        if (!prenda || !prenda.tallas || prenda.tallas.length === 0) {
            return '';
        }

        const tieneColorEnTallas = (Array.isArray(prenda.tallas) ? prenda.tallas : []).some(t => t && t.color);
        const tieneSobremedida = (Array.isArray(prenda.tallas) ? prenda.tallas : []).some(t => {
            if (!t) return false;
            const talla = String(t.talla || '').toLowerCase();
            const esSobremedida = (talla === 'sobremedida' || talla === 'cantidad');
            const sinGenero = (t.genero_id === null || t.genero_id === undefined || t.genero_id === '');
            return esSobremedida && sinGenero;
        });

        const parseTextoPersonalizadoTallasMapLocal = (texto) => {
            if (!texto || typeof texto !== 'string') return {};
            const t = texto.trim();
            if (!t) return {};
            if (t.startsWith('{') && t.endsWith('}')) {
                try {
                    const parsed = JSON.parse(t);
                    if (parsed && typeof parsed === 'object') return parsed;
                } catch (e) {
                    return {};
                }
            }
            const matchParen = t.match(/^\((.*)\)$/);
            if (matchParen) return { global: matchParen[1] };
            return { global: t };
        };

        const rawTextoPersonalizado = prenda.texto_personalizado_tallas || '';
        const textoMap = parseTextoPersonalizadoTallasMapLocal(rawTextoPersonalizado);

        const getTextoPersonalizadoValor = (colorKey, generoKey) => {
            if (textoMap && colorKey && typeof textoMap[colorKey] === 'object' && textoMap[colorKey] !== null) {
                const nested = textoMap[colorKey];
                if (nested && (nested[generoKey] !== undefined && nested[generoKey] !== null)) {
                    return String(nested[generoKey]);
                }
            }
            if (textoMap && colorKey) {
                const flatKey = `${colorKey}||${generoKey}`;
                if (textoMap[flatKey] !== undefined && textoMap[flatKey] !== null) {
                    return String(textoMap[flatKey]);
                }
            }
            if (textoMap && (textoMap[generoKey] !== undefined && textoMap[generoKey] !== null)) {
                return String(textoMap[generoKey]);
            }
            if (textoMap && textoMap.global) return String(textoMap.global);
            return '';
        };

        const formatearGrupoTallas = (arr) => {
            const items = (Array.isArray(arr) ? arr : [])
                .map(x => {
                    const talla = (x && x.talla) ? String(x.talla).trim() : '';
                    const cantidad = (x && (x.cantidad !== undefined && x.cantidad !== null)) ? x.cantidad : '';
                    if (!talla) return '';
                    return (cantidad !== '' && cantidad !== 1) ? `${talla} (${cantidad})` : talla;
                })
                .filter(Boolean);
            return items.join(', ');
        };

        let html = '';

        // NUEVO: agrupar por color cuando exista campo color o haya sobremedida
        if (tieneColorEnTallas || tieneSobremedida) {
            const gruposPorColor = {};
            (Array.isArray(prenda.tallas) ? prenda.tallas : []).forEach((t) => {
                if (!t) return;
                const key = (t.color && String(t.color).trim()) ? String(t.color).trim() : 'Sin color';
                if (!gruposPorColor[key]) gruposPorColor[key] = [];
                gruposPorColor[key].push(t);
            });

            const ordenarKeys = (obj) => Object.keys(obj).sort((a, b) => a.localeCompare(b, 'es'));
            let gruposHtml = '';

            ordenarKeys(gruposPorColor).forEach((colorKey) => {
                const group = gruposPorColor[colorKey] || [];
                const tallasCaballero = [];
                const tallasDama = [];
                const tallasSinGenero = [];
                const tallasSobremedida = [];

                group.forEach((t) => {
                    if (!t) return;
                    const talla = String(t.talla || '').toLowerCase();
                    const esSobremedida = (talla === 'sobremedida' || talla === 'cantidad') && (t.genero_id === null || t.genero_id === undefined || t.genero_id === '');
                    if (esSobremedida) {
                        tallasSobremedida.push(t);
                        return;
                    }

                    if (t.genero_id === 1 || t.genero_id === '1') {
                        tallasCaballero.push(t);
                    } else if (t.genero_id === 2 || t.genero_id === '2') {
                        tallasDama.push(t);
                    } else {
                        tallasSinGenero.push(t);
                    }
                });

                const cabTxt = formatearGrupoTallas(tallasCaballero);
                const damaTxt = formatearGrupoTallas(tallasDama);
                const sinTxt = formatearGrupoTallas(tallasSinGenero);
                const tieneSobremedidaEnGrupo = tallasSobremedida.length > 0;

                const lineEditable = (titulo, generoKey, valorBase) => {
                    if (!valorBase) return '';
                    const valorParen = getTextoPersonalizadoValor(colorKey, generoKey);
                    return `
                        <div style="margin-top: 0.25rem;">
                            <span style="color: #1e5ba8; font-size: 0.85rem; font-weight: 800;">${titulo}: </span>
                            <span
                                class="tallas-genero-edit"
                                data-prenda-id="${prenda.id}"
                                data-genero-key="${generoKey}"
                                data-color-key="${String(colorKey).replace(/\"/g, '&quot;')}"
                                data-tallas-base="${valorBase.replace(/\"/g, '&quot;')}"
                                data-texto-personalizado="${String(rawTextoPersonalizado).replace(/\"/g, '&quot;')}"
                                style="color: #ef4444; font-weight: 800; font-size: 0.85rem; cursor: pointer;"
                                title="Doble click para editar el texto dentro de paréntesis"
                            >${valorBase} (${valorParen || ''})</span>
                        </div>
                    `;
                };

                const sobremedidaLine = tieneSobremedidaEnGrupo
                    ? `
                        <div style="margin-top: 0.25rem;">
                            <span style="color: #ef4444; font-weight: 900; font-size: 0.85rem;">Sobremedida</span>
                        </div>
                    `
                    : '';

                const colorHeader = `
                    <div style="margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px dashed rgba(30,91,168,0.25);">
                        <div style="color:#0f172a; font-weight: 900; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">${colorKey}</div>
                    </div>
                `;

                gruposHtml +=
                    colorHeader +
                    lineEditable('Caballero', 'caballero', cabTxt) +
                    lineEditable('Dama', 'dama', damaTxt) +
                    sobremedidaLine +
                    lineEditable('Sin género', 'sin_genero', sinTxt);
            });

            html += `
                <div style="margin: 0 0 0.5rem 0;" data-tallas-prenda-container="1">
                    <span style="color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">Tallas:</span>
                    <div id="tallas-prenda-${prenda.id}" style="padding: 0.25rem 0.5rem; border-radius: 4px;">
                        ${gruposHtml}
                    </div>
                </div>
            `;
        } else {
            // LEGACY: render anterior (sin color)
            const tallasCaballero = [];
            const tallasDama = [];
            const tallasSinGenero = [];

            prenda.tallas.forEach((t) => {
                if (!t) return;
                if (t.genero_id === 1 || t.genero_id === '1') {
                    tallasCaballero.push(t);
                } else if (t.genero_id === 2 || t.genero_id === '2') {
                    tallasDama.push(t);
                } else {
                    tallasSinGenero.push(t);
                }
            });

            const cabTxt = formatearGrupoTallas(tallasCaballero);
            const damaTxt = formatearGrupoTallas(tallasDama);
            const sinTxt = formatearGrupoTallas(tallasSinGenero);

            const parseTextoMap = (texto) => {
                if (!texto || typeof texto !== 'string') return {};
                const t = texto.trim();
                if (!t) return {};
                if (t.startsWith('{') && t.endsWith('}')) {
                    try {
                        const parsed = JSON.parse(t);
                        if (parsed && typeof parsed === 'object') return parsed;
                    } catch (e) {
                        return {};
                    }
                }
                const matchParen = t.match(/^\((.*)\)$/);
                if (matchParen) return { global: matchParen[1] };
                return { global: t };
            };

            const textoMapLegacy = parseTextoMap(prenda.texto_personalizado_tallas || '');
            const rawTextoPersonalizadoLegacy = prenda.texto_personalizado_tallas || '';

            const construirLinea = (titulo, key, valorBase) => {
                if (!valorBase) return '';
                const valorParen = (textoMapLegacy && (textoMapLegacy[key] !== undefined && textoMapLegacy[key] !== null))
                    ? String(textoMapLegacy[key])
                    : (textoMapLegacy && textoMapLegacy.global ? String(textoMapLegacy.global) : '');
                return `
                    <div style="margin-top: 0.25rem;">
                        <span style="color: #1e5ba8; font-size: 0.85rem; font-weight: 800;">${titulo}: </span>
                        <span
                            class="tallas-genero-edit"
                            data-prenda-id="${prenda.id}"
                            data-genero-key="${key}"
                            data-tallas-base="${valorBase.replace(/\"/g, '&quot;')}"
                            data-texto-personalizado="${String(rawTextoPersonalizadoLegacy).replace(/\"/g, '&quot;')}"
                            style="color: #ef4444; font-weight: 800; font-size: 0.85rem; cursor: pointer;"
                            title="Doble click para editar el texto dentro de paréntesis"
                        >${valorBase} (${valorParen || ''})</span>
                    </div>
                `;
            };

            const gruposHtml =
                construirLinea('Caballero', 'caballero', cabTxt) +
                construirLinea('Dama', 'dama', damaTxt) +
                construirLinea('Sin género', 'sin_genero', sinTxt);

            html += `
                <div style="margin: 0 0 0.5rem 0;" data-tallas-prenda-container="1">
                    <span style="color: #1e5ba8; font-size: 0.9rem; font-weight: 700;">Tallas:</span>
                    <div id="tallas-prenda-${prenda.id}" style="padding: 0.25rem 0.5rem; border-radius: 4px;">
                        ${gruposHtml}
                    </div>
                </div>
            `;
        }

        return html;
    }

    function renderPrendaCard(payload, prenda, modo) {
        if (!prenda) return '';

        // Construir atributos principales
        let atributosLinea = [];

        // Obtener color de variantes o telas
        let color = '';
        if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].color) {
            color = prenda.variantes[0].color;
        }

        // Obtener tela de telas o de logo_cotizacion.telas_prendas
        let telaInfo = '';
        let imgTela = '';

        // Si es cotización logo, buscar en telas_prendas
        if (payload && payload.logo_cotizacion && payload.logo_cotizacion.telas_prendas && payload.logo_cotizacion.telas_prendas.length > 0) {
            const telaPrenda = payload.logo_cotizacion.telas_prendas.find(tp => tp.prenda_cot_id === prenda.id);
            if (telaPrenda) {
                telaInfo = telaPrenda.tela || '';
                if (telaPrenda.color) {
                    telaInfo += telaPrenda.tela ? ` | ${telaPrenda.color}` : telaPrenda.color;
                }
                if (telaPrenda.ref) {
                    telaInfo += ` REF:${telaPrenda.ref}`;
                }
                // Obtener la imagen de la tela
                if (telaPrenda.img) {
                    imgTela = telaPrenda.img;
                }
            }
        }
        // Si no es logo, usar telas combinadas
        else if (prenda.telas && prenda.telas.length > 0) {
            const tela = prenda.telas[0];
            telaInfo = tela.nombre_tela || '';
            if (tela.referencia) {
                telaInfo += ` REF:${tela.referencia}`;
            }
        }

        // Obtener manga de variantes
        let manguaInfo = '';
        if (prenda.variantes && prenda.variantes.length > 0) {
            const variante = prenda.variantes[0];
            if (variante.manga && variante.manga.nombre) {
                manguaInfo = variante.manga.nombre;
            }
        }

        if (color) atributosLinea.push(`Color: ${color}`);
        if (telaInfo) atributosLinea.push(`Tela: ${telaInfo}`);
        if (manguaInfo) atributosLinea.push(`Manga: ${manguaInfo}`);

        // Construir HTML de la prenda
        let html = `
                        <div class="prenda-card" style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                ${prenda.nombre_prenda || 'Sin nombre'}
                            </h3>
                            <p style="margin: 0 0 0.75rem 0; color: #666; font-size: 0.9rem; font-weight: 500;">
                                ${atributosLinea.join(' | ') || ''}
                            </p>
                            <div style="margin: 0 0 1rem 0; color: #333; font-size: 0.85rem; line-height: 1.6;">
                                <span style="color: #1e5ba8; font-weight: 700;">DESCRIPCION:</span> ${renderDescripcionPrenda(payload, prenda, modo)}
                            </div>
                    `;

        // En vista LOGO el usuario solo quiere ver:
        // - Descripción (con ubicaciones ya agregadas arriba)
        // - Imágenes del logo (si existen)
        // No mostrar tallas, variantes, reflectivo, telas ni fotos de prenda.
        if (modo === 'logo') {
            html += renderSoloLogoPorPrenda(payload, prenda);
            html += `</div>`;
            return html;
        }

        // Mostrar tallas
        html += renderTallasPrenda(payload, prenda);

        // Variaciones de técnicas prendas (modo LOGO)
        if (modo === 'logo') {
            html += renderVariacionesTecnicasLogo(payload, prenda);
        }

        // Variaciones específicas + reflectivo
        html += renderVariacionesEspecificas(prenda);
        html += renderVariacionesReflectivo(prenda);

        // ===== IMÁGENES LADO A LADO: LOGO | PRENDA | REFLECTIVO =====
        if (H && typeof H.buildImagenesParaMostrar === 'function' && typeof H.renderImagenesParaMostrar === 'function') {
            const imagenesParaMostrar = H.buildImagenesParaMostrar({ payload, prenda, modo, imgTela });
            html += H.renderImagenesParaMostrar({ prendaId: prenda.id, imagenesParaMostrar });
        }

        html += `</div>`;
        return html;
    }

    function renderPrendas(payload, ctx) {
        const prendas = ctx.prendasAll;

        const prendasFiltradas = ctx.esSoloLogoFinal
            ? prendas.filter(p => ctx.prendaTieneLogo(p))
            : (!ctx.esCombinada
                ? prendas
                : (ctx.modo === 'logo'
                    ? prendas.filter(p => ctx.prendaTieneLogo(p))
                    : prendas));

        let html = '<div class="prendas-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';

        html += renderTipoVenta(payload, ctx);
        html += renderSelector(ctx);

        if (!prendasFiltradas || prendasFiltradas.length === 0) {
            const mensaje = ctx.esCombinada
                ? (ctx.modo === 'logo' ? 'No hay prendas con información de logo para mostrar' : 'No hay prendas para mostrar')
                : 'No hay prendas para mostrar';

            html += `<p style="color: #999; text-align: center; padding: 2rem;">${mensaje}</p>`;
            html += '</div>';
            return html;
        }

        prendasFiltradas.forEach((prenda) => {
            html += renderPrendaCard(payload, prenda, ctx.modo);
        });

        html += '</div>';
        return html;
    }

    window.CotizacionModalRenderPrenda = {
        renderPrendas,
        renderPrendaCard,
        renderTipoVenta,
        renderSelector,
        renderSoloLogoPorPrenda,
        renderDescripcionPrenda,
        renderTallasPrenda,
        renderVariacionesEspecificas,
        renderVariacionesReflectivo,
        renderVariacionesTecnicasLogo,
    };
})();
