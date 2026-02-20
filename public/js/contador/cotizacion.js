// ===== FUNCIONES PARA MODAL DE COTIZACIÓN =====

// Loader interno (sin bundler): carga módulos auxiliares en orden.
// Nota: no modifica vistas; cotizacion.js sigue siendo el entrypoint.
if (typeof window !== 'undefined') {
    window.__cotizacionModalModulesPromise = window.__cotizacionModalModulesPromise || null;

    window.ensureCotizacionModalModules = function ensureCotizacionModalModules() {
        if (window.__cotizacionModalModulesPromise) return window.__cotizacionModalModulesPromise;

        const loadScript = (src) => new Promise((resolve, reject) => {
            // Evitar duplicados
            const existing = document.querySelector(`script[data-cotizacion-module="${src}"]`);
            if (existing) {
                if (existing.dataset.loaded === 'true') return resolve();
                existing.addEventListener('load', () => resolve());
                existing.addEventListener('error', () => reject(new Error('Error cargando ' + src)));
                return;
            }

            const s = document.createElement('script');
            s.src = src;
            s.async = false;
            s.defer = false;
            s.dataset.cotizacionModule = src;
            s.addEventListener('load', () => {
                s.dataset.loaded = 'true';
                resolve();
            });
            s.addEventListener('error', () => reject(new Error('Error cargando ' + src)));
            document.head.appendChild(s);
        });

        const base = '/js/contador/cotizacion';
        window.__cotizacionModalModulesPromise = Promise.resolve()
            .then(() => loadScript(`${base}/helpers.js`))
            .then(() => loadScript(`${base}/type-detector.js`))
            .then(() => loadScript(`${base}/render-prenda.js`))
            .then(() => loadScript(`${base}/render-logo.js`))
            .then(() => loadScript(`${base}/render-epp.js`))
            .then(() => loadScript(`${base}/modal-core.js`))
            .catch((e) => {
                console.error('[cotizacion.js] Error cargando módulos de cotización', e);
                // No romper la app: permitir fallback a lógica legacy.
            });

        return window.__cotizacionModalModulesPromise;
    };
}

/**
 * Abre el modal de detalle de cotización
 * @param {number} cotizacionId - ID de la cotización
 */
function openCotizacionModal(cotizacionId) {

    const ensureModules = (typeof window !== 'undefined' && typeof window.ensureCotizacionModalModules === 'function')
        ? window.ensureCotizacionModalModules()
        : Promise.resolve();

    ensureModules.then(() => {


    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {


            // Guardar data para poder re-renderizar (cotización combinada: Prenda/Logo)
            window.__cotizacionModalData = data;

            const renderCotizacionModal = (payload, viewMode = null) => {
                // Actualizar header del modal con información de la cotización
                if (payload.cotizacion) {
                    const cot = payload.cotizacion;
                    document.getElementById('modalHeaderNumber').textContent = cot.numero_cotizacion || 'N/A';
                    document.getElementById('modalHeaderDate').textContent = cot.created_at ? new Date(cot.created_at).toLocaleDateString('es-ES') : 'N/A';
                    document.getElementById('modalHeaderClient').textContent = cot.nombre_cliente || 'N/A';
                    document.getElementById('modalHeaderAdvisor').textContent = cot.asesora_nombre || 'N/A';
                }

                // Preferir detector modular si existe
                const typeDetector = (typeof window !== 'undefined') ? window.CotizacionModalTypeDetector : null;

                const tienePrendas = Array.isArray(payload.prendas_cotizaciones)
                    ? payload.prendas_cotizaciones.length > 0
                    : !!payload.tiene_prendas;
                const tieneLogo = !!payload.logo_cotizacion || !!payload.tiene_logo;

                // Fallback legacy (por si el loader no alcanzó a cargar)
                const codigoTipoCotizacion = (
                    payload?.cotizacion?.tipo ||
                    payload?.cotizacion?.tipo_codigo ||
                    payload?.cotizacion?.tipo_cotizacion_codigo ||
                    payload?.cotizacion?.tipo_cotizacion?.codigo ||
                    payload?.cotizacion?.tipoCotizacion?.codigo ||
                    ''
                );
                const codigoTipoUpper = (codigoTipoCotizacion || '').toString().trim().toUpperCase();
                const esTipoSoloLogoPorCodigo = ['L', 'LG', 'LOGO', 'B'].includes(codigoTipoUpper);

                let esCombinadaRaw = !!(tienePrendas && tieneLogo && !esTipoSoloLogoPorCodigo);
                let esSoloLogo = !!(esTipoSoloLogoPorCodigo || (!tienePrendas && tieneLogo));
                const moduloActual = (typeof document !== 'undefined' && document.body && document.body.dataset)
                    ? document.body.dataset.module
                    : '';
                const isVisualizadorLogo = moduloActual === 'visualizador-logo';
                const hideSelector = isVisualizadorLogo || !!window.__cotizacionModalHideSelector;
                // Nota: no referenciar esCombinada aquí porque aún no está inicializada.
                // Se decide el modo final después de detectar combinada vs solo-logo.
                let modo = viewMode || (window.__cotizacionModalViewMode || null);

                if (typeDetector && typeof typeDetector.compute === 'function') {
                    const ctx = typeDetector.compute(payload, viewMode, { hideSelector });
                    esCombinadaRaw = ctx.esCombinada;
                    esSoloLogo = ctx.esSoloLogoFinal;
                    modo = ctx.modo;
                }
                // Helper: identifica si una prenda tiene técnicas de logo
                const prendaTieneLogo = (prendaObj) => {
                    const tecnicas = (payload.logo_cotizacion && Array.isArray(payload.logo_cotizacion.tecnicas_prendas))
                        ? payload.logo_cotizacion.tecnicas_prendas.filter(tp => tp && tp.prenda_id === prendaObj.id)
                        : [];
                    if (!tecnicas || tecnicas.length === 0) return false;
                    // Si existe al menos una técnica asociada a esta prenda, se considera "con logo".
                    return true;
                };

                // En algunos casos (cotización tipo logo) el backend igual envía prendas_cotizaciones,
                // por lo que tiene_prendas puede venir en true. Detectamos "solo logo" por contenido.
                const prendasAll = Array.isArray(payload.prendas_cotizaciones) ? payload.prendas_cotizaciones : [];
                // Si la cotización tiene prendas y logo, es COMBINADA siempre.
                // Aun si todas las prendas tienen técnicas de logo, necesitamos permitir ver ambos modos (Prenda/Logo).
                const esCombinada = esCombinadaRaw;

                // Si es COMBINADA, aunque todas las prendas tengan logo, igual necesitamos permitir
                // ver ambas vistas (Prenda/Logo) sobre la misma prenda.
                const esSoloLogoFinal = esCombinada
                    ? false
                    : esSoloLogo;

                // Modo por defecto cuando no viene explícito:
                // - combinada: recordar selector (default prenda)
                // - no combinada: no aplica selector, pero si es solo logo forzaremos abajo
                if (!modo && esCombinada) {
                    modo = 'prenda';
                }

                if (esSoloLogoFinal) {
                    modo = 'logo';
                }
                if (esCombinada && hideSelector) {
                    modo = 'logo';
                }
                if (esCombinada) {
                    window.__cotizacionModalViewMode = modo;
                }

                // Construir HTML del modal sin el encabezado (que ya está en el layout)
                let html = '';
                
                // Si es cotización EPP, renderizar sección EPP (módulo) y salir temprano
                {
                    const tipoCodigo = (payload?.cotizacion?.tipo_codigo || payload?.cotizacion?.tipo || '').toString().trim().toUpperCase();
                    if (tipoCodigo === 'EPP') {
                        const renderEpp = (typeof window !== 'undefined') ? window.CotizacionModalRenderEpp : null;
                        if (renderEpp && typeof renderEpp.renderEppSection === 'function') {
                            html += renderEpp.renderEppSection(payload);
                        } else {
                            html += '<p style="color: #999; text-align: center; padding: 2rem;">No hay información de EPP para mostrar</p>';
                        }

                        document.getElementById('modalBody').innerHTML = html;
                        document.getElementById('cotizacionModal').style.display = 'flex';
                        return;
                    }
                }

                // Construir contenido de prendas
                let htmlPrendas = '';

                // Sección completa de prendas (módulo) con fallback legacy
                {
                    const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                    if (renderPrenda && typeof renderPrenda.renderPrendas === 'function') {
                        htmlPrendas += renderPrenda.renderPrendas(payload, {
                            esCombinada,
                            esSoloLogoFinal,
                            modo,
                            hideSelector,
                            prendasAll,
                            prendaTieneLogo,
                        });
                    } else {
                        // Contenedor de prendas
                        htmlPrendas += '<div class="prendas-container" style="display: flex; flex-direction: column; gap: 1.5rem;">';

                        // Header de tipo de venta y selector Prenda/Logo (módulo)
                        const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                        const ctxPrendaHeader = {
                            esCombinada,
                            esSoloLogoFinal,
                            modo,
                            hideSelector,
                        };

                        if (renderPrenda && typeof renderPrenda.renderTipoVenta === 'function') {
                            htmlPrendas += renderPrenda.renderTipoVenta(payload, ctxPrendaHeader);
                        } else {
                            // Fallback legacy
                            let tipoVenta = null;
                            if (payload.logo_cotizacion && payload.logo_cotizacion.tipo_venta && ((esCombinada && modo === 'logo') || (!payload.tiene_prendas && payload.tiene_logo))) {
                                tipoVenta = payload.logo_cotizacion.tipo_venta;
                            } else if (payload.cotizacion && payload.cotizacion.tipo_venta) {
                                tipoVenta = payload.cotizacion.tipo_venta;
                            }
                            if (tipoVenta) {
                                htmlPrendas += `
                                    <div style="display: inline-block; text-align: left; margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #ef4444; border-radius: 8px;">
                                        <span style="color: #000000; font-size: 1.1rem; font-weight: 600;">Por favor cotizar al</span>
                                        <span style="color: #dc2626; font-size: 1.4rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;">${tipoVenta}</span>
                                    </div>
                                `;
                            }
                        }

                        if (renderPrenda && typeof renderPrenda.renderSelector === 'function') {
                            htmlPrendas += renderPrenda.renderSelector(ctxPrendaHeader);
                        } else {
                            // Fallback legacy selector
                            if (esCombinada && !hideSelector && !esSoloLogoFinal) {
                                const isPrenda = modo === 'prenda';
                                const isLogo = modo === 'logo';
                                htmlPrendas += `
                                    <div style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem;">
                                        <button type="button" id="cotModalBtnPrenda" style="padding: 0.6rem 0.9rem; border-radius: 8px; border: 2px solid ${isPrenda ? '#1e5ba8' : '#cbd5e1'}; background: ${isPrenda ? '#1e5ba8' : '#ffffff'}; color: ${isPrenda ? '#ffffff' : '#0f172a'}; font-weight: 800; cursor: pointer;">Cotización Prenda</button>
                                        <button type="button" id="cotModalBtnLogo" style="padding: 0.6rem 0.9rem; border-radius: 8px; border: 2px solid ${isLogo ? '#1e5ba8' : '#cbd5e1'}; background: ${isLogo ? '#1e5ba8' : '#ffffff'}; color: ${isLogo ? '#ffffff' : '#0f172a'}; font-weight: 800; cursor: pointer;">Cotización Logo</button>
                                    </div>
                                `;
                            }
                        }
                    }
                }

                // Fallback legacy completo (solo cuando NO existe renderPrendas del módulo)
                {
                    const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                    const usaModuloPrendas = !!(renderPrenda && typeof renderPrenda.renderPrendas === 'function');
                    if (!usaModuloPrendas) {
                        const prendas = prendasAll;
                        const prendasFiltradas = esSoloLogoFinal
                            ? prendas.filter(p => prendaTieneLogo(p))
                            : (!esCombinada
                                ? prendas
                                : (modo === 'logo'
                                    ? prendas.filter(p => prendaTieneLogo(p))
                                    : prendas));

                        if (prendasFiltradas && prendasFiltradas.length > 0) {
                            prendasFiltradas.forEach((prenda, index) => {

                    // Render completo de la card de prenda (módulo) con fallback legacy
                    const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                    if (renderPrenda && typeof renderPrenda.renderPrendaCard === 'function') {
                        htmlPrendas += renderPrenda.renderPrendaCard(payload, prenda, modo);
                        return;
                    }

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
                    if (payload.logo_cotizacion && payload.logo_cotizacion.telas_prendas && payload.logo_cotizacion.telas_prendas.length > 0) {
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
                    let manga = '';
                    if (prenda.variantes && prenda.variantes.length > 0 && prenda.variantes[0].tipo_manga) {
                        manga = prenda.variantes[0].tipo_manga;
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
                    htmlPrendas += `
                        <div class="prenda-card" style="background: #f5f5f5; border-left: 5px solid #1e5ba8; padding: 1rem 1.5rem; border-radius: 4px;">
                            <h3 style="margin: 0 0 0.5rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">
                                ${prenda.nombre_prenda || 'Sin nombre'}
                            </h3>
                            <p style="margin: 0 0 0.75rem 0; color: #666; font-size: 0.9rem; font-weight: 500;">
                                ${atributosLinea.join(' | ') || ''}
                            </p>
                            <div style="margin: 0 0 1rem 0; color: #333; font-size: 0.85rem; line-height: 1.6;">
                                <span style="color: #1e5ba8; font-weight: 700;">DESCRIPCION:</span> ${(() => {
                                    const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                                    if (renderPrenda && typeof renderPrenda.renderDescripcionPrenda === 'function') {
                                        return renderPrenda.renderDescripcionPrenda(payload, prenda, modo);
                                    }
                                    return (prenda.descripcion_formateada || prenda.descripcion || '-').toString().replace(/\n/g, '<br>');
                                })()}
                            </div>
                    `;

                    // En vista LOGO el usuario solo quiere ver:
                    // - Descripción (con ubicaciones ya agregadas arriba)
                    // - Imágenes del logo (si existen)
                    // No mostrar tallas, variantes, reflectivo, telas ni fotos de prenda.
                    if (modo === 'logo') {
                        const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                        if (renderPrenda && typeof renderPrenda.renderSoloLogoPorPrenda === 'function') {
                            htmlPrendas += renderPrenda.renderSoloLogoPorPrenda(payload, prenda);
                        }

                        htmlPrendas += `</div>`;
                        return;
                    }

                    // Mostrar tallas (módulo)
                    {
                        const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                        if (renderPrenda && typeof renderPrenda.renderTallasPrenda === 'function') {
                            htmlPrendas += renderPrenda.renderTallasPrenda(payload, prenda);
                        } else {
                            // Fallback legacy: no renderizar tallas si no está el módulo
                        }
                    }

                    // Variaciones de técnicas prendas (modo LOGO) (módulo)
                    if (modo === 'logo') {
                        const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                        if (renderPrenda && typeof renderPrenda.renderVariacionesTecnicasLogo === 'function') {
                            htmlPrendas += renderPrenda.renderVariacionesTecnicasLogo(payload, prenda);
                        }
                    }

                    // Variaciones específicas + reflectivo (módulo)
                    {
                        const renderPrenda = (typeof window !== 'undefined') ? window.CotizacionModalRenderPrenda : null;
                        if (renderPrenda && typeof renderPrenda.renderVariacionesEspecificas === 'function') {
                            htmlPrendas += renderPrenda.renderVariacionesEspecificas(prenda);
                        }
                        if (renderPrenda && typeof renderPrenda.renderVariacionesReflectivo === 'function') {
                            htmlPrendas += renderPrenda.renderVariacionesReflectivo(prenda);
                        }
                    }

                    // ===== IMÁGENES LADO A LADO: LOGO | PRENDA | REFLECTIVO =====
                    const modalHelpers = (typeof window !== 'undefined') ? window.CotizacionModalHelpers : null;
                    if (modalHelpers && typeof modalHelpers.buildImagenesParaMostrar === 'function' && typeof modalHelpers.renderImagenesParaMostrar === 'function') {
                        const imagenesParaMostrar = modalHelpers.buildImagenesParaMostrar({ payload, prenda, modo, imgTela });
                        htmlPrendas += modalHelpers.renderImagenesParaMostrar({ prendaId: prenda.id, imagenesParaMostrar });
                    } else {
                        // Fallback legacy: no renderizar galería si no está el helper
                    }

                                    htmlPrendas += `</div>`;
                                });
                        } else {
                            const mensaje = esCombinada
                                ? (modo === 'logo'
                                    ? 'No hay prendas con información de logo para mostrar'
                                    : 'No hay prendas para mostrar')
                                : 'No hay prendas para mostrar';
                            htmlPrendas += `<p style="color: #999; text-align: center; padding: 2rem;">${mensaje}</p>`;
                        }

                        htmlPrendas += '</div>';
                    }
                }

            // HELPER FUNCTION: Verificar si hay especificaciones reales
            const verificarEspecificaciones = (especificacionesObj) => {
                if (!especificacionesObj || typeof especificacionesObj !== 'object') {
                    return false;
                }
                const keys = Object.keys(especificacionesObj);
                return keys.length > 0 && keys.some(key => {
                    const valor = especificacionesObj[key];
                    return valor && (Array.isArray(valor) ? valor.length > 0 : true);
                });
            };

            // HELPER FUNCTION: Parsear especificaciones
            const parseEspecificaciones = (especificacionesRaw) => {
                if (!especificacionesRaw) return null;
                
                if (typeof especificacionesRaw === 'string') {
                    try {
                        return JSON.parse(especificacionesRaw);
                    } catch (e) {
                        console.log('Error al parsear especificaciones:', e);
                        return null;
                    }
                }
                
                if (typeof especificacionesRaw === 'object') {
                    return especificacionesRaw;
                }
                
                return null;
            };

            // Parsear especificaciones una sola vez
            const especificacionesObj = parseEspecificaciones(payload.cotizacion?.especificaciones);
            const tieneEspecificacionesReales = verificarEspecificaciones(especificacionesObj);

            console.log('Especificaciones parseadas:', especificacionesObj);
            console.log('Tiene especificaciones reales:', tieneEspecificacionesReales);
            console.log('tiene_prendas:', payload.tiene_prendas);
            console.log('tiene_logo:', payload.tiene_logo);

            // SECCIÓN ESPECIFICACIONES GENERALES
            if (tieneEspecificacionesReales) {
                const especificacionesMap = {
                    'disponibilidad': 'DISPONIBILIDAD',
                    'forma_pago': 'FORMA DE PAGO',
                    'regimen': 'RÉGIMEN',
                    'se_ha_vendido': 'SE HA VENDIDO',
                    'ultima_venta': 'ÚLTIMA VENTA',
                    'flete': 'FLETE DE ENVÍO'
                };

                htmlPrendas += `
                    <div style="margin-top: 2rem;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e5ba8; font-size: 1.1rem; font-weight: 700; text-transform: uppercase;">Especificaciones Generales</h3>
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: #f5f5f5; border-bottom: 2px solid #1e5ba8;">
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Especificación</th>
                                    <th style="padding: 0.75rem 1rem; text-align: left; color: #1e5ba8; font-weight: 700; font-size: 0.9rem;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                for (const [clave, nombreEspec] of Object.entries(especificacionesMap)) {
                    const valor = especificacionesObj[clave];
                    let valorTexto = '-';

                    if (valor) {
                        if (Array.isArray(valor) && valor.length > 0) {
                            // Es un array con objetos {valor, observacion}
                            valorTexto = valor
                                .map(v => {
                                    let texto = v.valor || '';
                                    if (v.observacion && v.observacion.trim()) {
                                        texto += ` (${v.observacion})`;
                                    }
                                    return texto;
                                })
                                .filter(t => t) // Filtrar vacíos
                                .join(', ');
                            
                            // Si resultó vacío, poner '-'
                            if (!valorTexto) {
                                valorTexto = '-';
                            }
                        } else if (typeof valor === 'string') {
                            valorTexto = valor;
                        } else if (typeof valor === 'object') {
                            // Por si acaso es un objeto en lugar de array
                            valorTexto = valor.valor || String(valor);
                        }
                    }

                    htmlPrendas += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 0.75rem 1rem; color: #333; font-weight: 600; font-size: 0.85rem;">${nombreEspec}</td>
                                    <td style="padding: 0.75rem 1rem; color: #666; font-size: 0.85rem;">${valorTexto}</td>
                                </tr>
                    `;
                }

                htmlPrendas += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
                
            // SECCIÓN DE OBSERVACIONES GENERALES (para cotización logo)
            if (payload.logo_cotizacion && payload.logo_cotizacion.observaciones_generales) {
                let observacionesArray = [];
                
                try {
                    if (typeof payload.logo_cotizacion.observaciones_generales === 'string') {
                        observacionesArray = JSON.parse(payload.logo_cotizacion.observaciones_generales);
                    } else if (Array.isArray(payload.logo_cotizacion.observaciones_generales)) {
                        observacionesArray = payload.logo_cotizacion.observaciones_generales;
                    }
                } catch (e) {
                    console.log('Error al parsear observaciones generales:', e);
                }

                if (observacionesArray && observacionesArray.length > 0) {
                    htmlPrendas += `
                        <div style="margin-top: 2rem; padding: 1.5rem; background: #f0f7ff; border-left: 5px solid #0ea5e9; border-radius: 4px;">
                            <h6 style="color: #1e5ba8; font-weight: 700; margin: 0 0 1rem 0; font-size: 1rem; text-transform: uppercase;">OBSERVACIONES GENERALES</h6>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    `;

                    observacionesArray.forEach(obs => {
                        if (obs && obs.texto) {
                            let checkboxHtml = '';
                            let valorExtraHtml = '';
                            
                            if (obs.tipo === 'checkbox') {
                                const valorRaw = obs.valor;
                                const valorStr = (valorRaw === null || typeof valorRaw === 'undefined') ? '' : String(valorRaw).trim();
                                const tieneValor = valorStr !== '';
                                // Compatibilidad:
                                // - Formato antiguo: true/1/'true'
                                // - Formato intermedio: 'on'
                                // - Formato nuevo: string con valor (ej. '200+')
                                const isChecked = (
                                    valorRaw === true ||
                                    valorRaw === 1 ||
                                    valorRaw === '1' ||
                                    valorRaw === 'true' ||
                                    valorStr.toLowerCase() === 'on' ||
                                    tieneValor
                                );
                                checkboxHtml = `<input type="checkbox" ${isChecked ? 'checked' : ''} disabled style="margin-right: 0.75rem; cursor: not-allowed; width: 18px; height: 18px; accent-color: #0ea5e9;" />`;

                                // Mostrar valor adicional si existe y no es el marcador 'on'
                                if (tieneValor && valorStr.toLowerCase() !== 'on') {
                                    valorExtraHtml = `
                                        <span style="margin-left: 0.75rem; padding: 0.2rem 0.5rem; background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; border-radius: 9999px; font-size: 0.85rem; font-weight: 700;">${valorStr}</span>
                                    `;
                                }
                            }
                            
                            htmlPrendas += `
                                <div style="display: flex; align-items: center; padding: 0.75rem; background: white; border-radius: 4px; border-left: 3px solid #0ea5e9;">
                                    ${checkboxHtml}
                                    <span style="color: #0f172a; font-weight: 500; font-size: 0.95rem;">${obs.texto}</span>
                                    ${valorExtraHtml}
                                </div>
                            `;
                        }
                    });

                    htmlPrendas += `
                            </div>
                        </div>
                    `;
                }
            }

            // Construir contenido de logo (módulo)
            let htmlLogo = '';
            if (typeof window !== 'undefined' && window.CotizacionModalRenderLogo && typeof window.CotizacionModalRenderLogo.renderLogoSection === 'function') {
                htmlLogo = window.CotizacionModalRenderLogo.renderLogoSection(payload);
            } else {
                htmlLogo = payload.logo_cotizacion
                    ? '<div class="logo-container"></div>'
                    : '<p style="color: #999; text-align: center; padding: 2rem;">No hay información de logo para mostrar</p>';
            }

            // Insertar contenido en el modal sin tabs
            // El logo ahora se renderiza directamente dentro de cada prenda
            if (payload.tiene_prendas) {
                html += htmlPrendas;
            }
            
            document.getElementById('modalBody').innerHTML = html;

            // Wire events del selector (si es combinada)
            if (esCombinada && !hideSelector) {
                const btnPrenda = document.getElementById('cotModalBtnPrenda');
                const btnLogo = document.getElementById('cotModalBtnLogo');

                if (btnPrenda) {
                    btnPrenda.onclick = () => {
                        window.__cotizacionModalViewMode = 'prenda';
                        renderCotizacionModal(window.__cotizacionModalData, 'prenda');
                    };
                }

                if (btnLogo) {
                    btnLogo.onclick = () => {
                        window.__cotizacionModalViewMode = 'logo';
                        renderCotizacionModal(window.__cotizacionModalData, 'logo');
                    };
                }
            }

            document.getElementById('cotizacionModal').style.display = 'flex';
            };

            renderCotizacionModal(data);
        })
        .catch(error => {

            alert('Error al cargar la cotización: ' + error.message);
        });
    });
}

/**
 * Cierra el modal de cotización
 */
function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

/**
 * Cierra el modal de cotización (alias)
 */
function cerrarModalCotizacion() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

if (typeof window !== 'undefined') {
    window.openCotizacionModalContador = openCotizacionModal;
    window.closeCotizacionModalContador = closeCotizacionModal;
}

/**
 * Cierra el modal al hacer clic fuera del contenido
 */
document.addEventListener('click', function (event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

/**
 * Cierra el modal al presionar ESC
 */
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cotizacionModal');
        if (modal && modal.style.display === 'flex') {
            closeCotizacionModal();
        }
    }
});

/**
 * Elimina una cotización con confirmación
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} cliente - Nombre del cliente
 */
function eliminarCotizacion(cotizacionId, cliente) {
    // Mostrar confirmación con SweetAlert
    Swal.fire({
        title: '¿Eliminar cotización completamente?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ¿Estás seguro de que deseas eliminar la cotización del cliente <strong>${cliente}</strong>?
                </p>
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #92400e; font-weight: 600;">
                         Se eliminarán PERMANENTEMENTE:
                    </p>
                    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.85rem; color: #92400e;">
                        <li><strong>Base de datos:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Registro de cotización</li>
                                <li>Todas las prendas relacionadas</li>
                                <li>Información de LOGO</li>
                                <li>Pedidos de producción asociados</li>
                                <li>Historial de cambios</li>
                            </ul>
                        </li>
                        <li style="margin-top: 0.5rem;"><strong>Servidor:</strong>
                            <ul style="margin: 0.25rem 0 0 0; padding-left: 1.25rem;">
                                <li>Carpeta: <code style="background: #fff3cd; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                <li>Todas las imágenes de prendas</li>
                                <li>Todas las imágenes de telas</li>
                                <li>Todas las imágenes de LOGO</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #ef4444; font-weight: 600;">
                     Esta acción NO se puede deshacer. Se eliminarán todos los datos y archivos.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar TODO',
        cancelButtonText: 'Cancelar',
        width: '550px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                html: `
                    <div style="text-align: left; color: #666;">
                        <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Por favor espera mientras se elimina:</p>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                            <li>Registros de la base de datos</li>
                            <li>Carpeta de imágenes del servidor</li>
                            <li>Todos los archivos relacionados</li>
                        </ul>
                    </div>
                `,
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Proceder con la eliminación
            fetch(`/contador/cotizacion/${cotizacionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '✓ Eliminado Completamente',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-weight: 600;"> Se eliminaron:</p>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                                    <li>Cotización de la base de datos</li>
                                    <li>Todas las prendas relacionadas</li>
                                    <li>Información de LOGO</li>
                                    <li>Pedidos de producción</li>
                                    <li>Historial de cambios</li>
                                    <li>Carpeta <code style="background: #f0f0f0; padding: 0.2rem 0.4rem; border-radius: 2px;">/storage/cotizaciones/${cotizacionId}</code></li>
                                    <li>Todas las imágenes almacenadas</li>
                                </ul>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8'
                        }).then(() => {
                            // Recargar la página
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Aprueba la cotización directamente desde la tabla (sin abrir modal)
 * @param {number} cotizacionId - ID de la cotización
 * @param {string} estadoActual - Estado actual de la cotización (opcional)
 */
function aprobarCotizacionEnLinea(cotizacionId, estadoActual = null) {
    // Determinar el mensaje y la ruta según el estado
    let mensaje = '¿Estás seguro de que deseas aprobar esta cotización?';
    let infoAdicional = 'La cotización será enviada al área de Aprobación de Cotizaciones';
    let ruta = `/cotizaciones/${cotizacionId}/aprobar-contador`;
    
    // Si el estado es APROBADA_POR_APROBADOR, usar la ruta para aprobar para pedido
    if (estadoActual === 'APROBADA_POR_APROBADOR') {
        infoAdicional = 'La cotización cambiará a estado APROBADO PARA PEDIDO';
        ruta = `/cotizaciones/${cotizacionId}/aprobar-para-pedido`;
    }
    
    // Mostrar confirmación
    Swal.fire({
        title: '¿Aprobar cotización?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    ${mensaje}
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         ${infoAdicional}
                    </p>
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar',
        width: '450px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Aprobando cotización...',
                html: 'Por favor espera mientras se procesa la aprobación',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación
            fetch(ruta, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar todas las filas en la tabla de Pendientes
                        const filas = document.querySelectorAll('#pedidos-section tbody tr');

                        filas.forEach(fila => {
                            // Buscar si esta fila contiene el botón de aprobar para esta cotización
                            const boton = fila.querySelector(`button[onclick*="aprobarCotizacionEnLinea(${cotizacionId})"]`);

                            if (boton) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#pedidos-section tbody');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 2rem; color: #999;">No hay cotizaciones pendientes</td></tr>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Cotización Aprobada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada correctamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha enviado notificación al área de Aprobación de Cotizaciones
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Enviado a Aprobador
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo aprobar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al aprobar la cotización. Por favor intenta de nuevo.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

/**
 * Abre una imagen en grande en un modal
 * @param {string} imagenUrl - URL de la imagen
 */
if (typeof imagenGaleraActual === 'undefined') {
    var imagenGaleraActual = [];
    var imagenIndiceActualGaleria = 0;
    var imagenGaleriaIdActual = null;
}

function abrirImagenGrande(imagenUrl, galleryId = null, index = 0) {
    // Preparar galería si viene un grupo
    if (galleryId) {
        imagenGaleriaIdActual = galleryId;
        const imgs = document.querySelectorAll(`img[data-gallery="${galleryId}"]`);
        imagenGaleraActual = Array.from(imgs)
            .map(img => img.getAttribute('src'))
            .filter(Boolean);
        imagenIndiceActualGaleria = Number(index) || 0;

        // Fallback: si no hay imágenes en DOM para esa galería, usar la URL recibida
        if (!imagenGaleraActual || imagenGaleraActual.length === 0) {
            const fallback = (imagenUrl || '').toString().trim();
            imagenGaleraActual = fallback ? [fallback] : [];
            imagenIndiceActualGaleria = 0;
            imagenGaleriaIdActual = null;
        }
    } else {
        imagenGaleriaIdActual = null;
        const single = (imagenUrl || '').toString().trim();
        imagenGaleraActual = single ? [single] : [];
        imagenIndiceActualGaleria = 0;
    }

    // Garantizar índice válido
    if (imagenIndiceActualGaleria < 0) imagenIndiceActualGaleria = 0;
    if (imagenIndiceActualGaleria >= imagenGaleraActual.length) imagenIndiceActualGaleria = 0;

    // Crear modal dinámicamente si no existe
    let modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) {
        modalImagen = document.createElement('div');
        modalImagen.id = 'modalImagenGrande';
        modalImagen.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        `;
        modalImagen.innerHTML = `
            <div style="position: relative; width: 90vw; height: 90vh; max-width: 1200px; max-height: 800px; display: flex; align-items: center; justify-content: center;">
                <button id="cerrarImagenGrandeBtn" aria-label="Cerrar" style="position: absolute; top: -50px; right: 0; background: #fff; border: none; font-size: 1.4rem; cursor: pointer; color: #111; z-index: 10001; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
                    ✕
                </button>
                <button id="imagenAnteriorBtn" aria-label="Anterior" style="position: absolute; left: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">◀</button>
                <img id="imagenGrandeContent" src="" alt="Imagen ampliada" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <button id="imagenSiguienteBtn" aria-label="Siguiente" style="position: absolute; right: -60px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; width: 44px; height: 44px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 1.3rem; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); color: #111;">▶</button>
            </div>
        `;
        document.body.appendChild(modalImagen);

        // Eventos de botones
        modalImagen.querySelector('#cerrarImagenGrandeBtn').addEventListener('click', cerrarImagenGrande);
        modalImagen.querySelector('#imagenAnteriorBtn').addEventListener('click', mostrarAnteriorImagen);
        modalImagen.querySelector('#imagenSiguienteBtn').addEventListener('click', mostrarSiguienteImagen);
    }

    actualizarImagenGrande();
    modalImagen.style.display = 'flex';
}

function actualizarImagenGrande() {
    const modalImagen = document.getElementById('modalImagenGrande');
    if (!modalImagen) return;

    const img = modalImagen.querySelector('#imagenGrandeContent');
    img.src = imagenGaleraActual[imagenIndiceActualGaleria] || '';

    const btnPrev = modalImagen.querySelector('#imagenAnteriorBtn');
    const btnNext = modalImagen.querySelector('#imagenSiguienteBtn');

    if (imagenGaleraActual.length > 1) {
        btnPrev.style.display = 'flex';
        btnNext.style.display = 'flex';
    } else {
        btnPrev.style.display = 'none';
        btnNext.style.display = 'none';
    }
}

function mostrarAnteriorImagen() {
    if (!imagenGaleraActual.length) return;
    imagenIndiceActualGaleria = (imagenIndiceActualGaleria - 1 + imagenGaleraActual.length) % imagenGaleraActual.length;
    actualizarImagenGrande();
}

function mostrarSiguienteImagen() {
    if (!imagenGaleraActual.length) return;
    imagenIndiceActualGaleria = (imagenIndiceActualGaleria + 1) % imagenGaleraActual.length;
    actualizarImagenGrande();
}

/**
 * Cierra el modal de imagen grande
 */
function cerrarImagenGrande() {
    const modal = document.getElementById('modalImagenGrande');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función para aprobar cotización al aprobador (desde vista aprobadas)
function aprobarAlAprobador(cotizacionId) {
    // Mostrar confirmación
    Swal.fire({
        title: '¿Enviar al Asesor?',
        html: `
            <div style="text-align: left; margin: 1rem 0;">
                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #4b5563;">
                    Esta es la aprobación final del proceso. La cotización será enviada de vuelta al asesor para que pueda proceder con la venta.
                </p>
                <div style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                    <p style="margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600;">
                         Una vez aprobada, la cotización estará lista para presentarse al cliente
                    </p>
                </div>
                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                    <strong>¿Estás seguro de que deseas proceder?</strong>
                </p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, Enviar al Asesor',
        cancelButtonText: 'Cancelar',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando al Asesor...',
                html: 'Por favor espera mientras se procesa',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación al aprobador
            fetch(`/cotizaciones/${cotizacionId}/aprobar-aprobador`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Encontrar la fila en la tabla de Aprobadas
                        const filas = document.querySelectorAll('#aprobadas-section .table-row');

                        filas.forEach(fila => {
                            const rowId = fila.getAttribute('data-cotizacion-id');
                            if (rowId == cotizacionId) {
                                // Animar la desaparición de la fila
                                fila.style.transition = 'all 0.3s ease-out';
                                fila.style.opacity = '0';
                                fila.style.transform = 'translateX(-20px)';

                                setTimeout(() => {
                                    fila.remove();

                                    // Verificar si la tabla está vacía
                                    const tbody = document.querySelector('#aprobadas-section .table-body');
                                    if (tbody && tbody.children.length === 0) {
                                        // Si está vacía, mostrar mensaje
                                        tbody.innerHTML = '<div style="padding: 40px; text-align: center; color: #9ca3af;"><p>No hay cotizaciones aprobadas</p></div>';
                                    }
                                }, 300);
                            }
                        });

                        Swal.fire({
                            title: '✓ Aprobación Completada',
                            html: `
                            <div style="text-align: left; color: #4b5563;">
                                <p style="margin: 0 0 0.75rem 0; font-size: 0.95rem;">
                                     La cotización ha sido aprobada exitosamente.
                                </p>
                                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 0.75rem; border-radius: 4px; margin: 0.75rem 0;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #065f46; font-weight: 600;">
                                        📧 Se ha notificado al asesor
                                    </p>
                                </div>
                                <p style="margin: 0.75rem 0 0 0; font-size: 0.85rem; color: #666;">
                                    <strong>Estado actual:</strong> Lista para hacer pedido
                                </p>
                            </div>
                        `,
                            icon: 'success',
                            confirmButtonColor: '#1e5ba8',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo enviar la cotización',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {

                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Error al procesar la solicitud',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }
    });
}

// Cerrar modal de imagen al hacer clic fuera
document.addEventListener('click', function (event) {
    const modal = document.getElementById('modalImagenGrande');
    if (modal && event.target === modal) {
        cerrarImagenGrande();
    }
});

// Cerrar modal de imagen al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarImagenGrande();
    }
});
/**
 * Función para editar tallas con paréntesis
 */
function editarTallasConParentesis(element) {
    // Evitar editar si ya está en modo edición
    if (element.querySelector('input')) {
        return;
    }

    const tallasTexto = element.textContent.trim();
    const prendasId = element.getAttribute('data-prenda-id');
    const cotizacionId = element.getAttribute('data-cotizacion-id');

    // Extraer el texto dentro de los paréntesis si existe
    const matches = tallasTexto.match(/^(.*?)\s*\((.*?)\)$/);
    const tallasParte = matches ? matches[1].trim() : tallasTexto.replace(' ()', '').trim();
    const textoDentroParentesis = matches ? matches[2] : '';

    // Crear input editable
    const input = document.createElement('input');
    input.type = 'text';
    input.value = textoDentroParentesis;
    input.style.cssText = `
        width: 200px;
        padding: 0.5rem;
        border: 2px solid #dc2626;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 700;
        color: #dc2626;
    `;

    // Reemplazar el span con el input
    element.textContent = `${tallasParte} (`;
    element.appendChild(input);
    
    // Crear el cierre de paréntesis
    const closeSpan = document.createElement('span');
    closeSpan.textContent = ')';
    element.appendChild(closeSpan);

    // Focus en el input
    input.focus();
    input.select();

    // Guardar al presionar Enter
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            guardarTallasContosCot(prendasId, cotizacionId, input.value, tallasParte);
            
            // Restaurar el elemento
            element.textContent = `${tallasParte} (${input.value})`;
        } else if (e.key === 'Escape') {
            // Cancelar edición
            element.textContent = tallasTexto;
        }
    });

    // Cancelar si pierde el focus
    input.addEventListener('blur', function() {
        if (element.querySelector('input')) {
            element.textContent = tallasTexto;
        }
    });
}

/**
 * Guardar tallas costos en la base de datos
 */
function guardarTallasContosCot(prendasId, cotizacionId, descripcion, tallasParte) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        console.error('CSRF token no encontrado');
        alert('Error: Token de seguridad no encontrado');
        return;
    }

    // Mostrar que está guardando
    const tallaElement = document.getElementById(`tallas-texto-${prendasId}`);
    if (tallaElement) {
        const originalOpacity = tallaElement.style.opacity;
        tallaElement.style.opacity = '0.6';
    }

    fetch('/contador/tallas-costos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            cotizacion_id: parseInt(cotizacionId),
            prenda_cot_id: parseInt(prendasId),
            descripcion: descripcion
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Tallas costos guardado exitosamente', data);
            
            // Actualizar el elemento en la UI
            if (tallaElement) {
                tallaElement.textContent = `${tallasParte} (${descripcion})`;
                tallaElement.style.opacity = '1';
                // Feedback visual
                tallaElement.style.backgroundColor = '#dcfce7';
                setTimeout(() => {
                    tallaElement.style.backgroundColor = 'transparent';
                }, 1500);
            }
            
            // Mostrar notificación de éxito
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Tallas guardadas correctamente', 'success');
            }
        } else {
            console.error('Error al guardar:', data.message);
            if (tallaElement) {
                tallaElement.style.opacity = '1';
            }
            
            alert('Error al guardar: ' + data.message);
            if (window.mostrarNotificacion) {
                window.mostrarNotificacion('Error: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        if (tallaElement) {
            tallaElement.style.opacity = '1';
        }
        
        alert('Error al guardar tallas: ' + error.message);
        if (window.mostrarNotificacion) {
            window.mostrarNotificacion('Error al guardar: ' + error.message, 'error');
        }
    });
}