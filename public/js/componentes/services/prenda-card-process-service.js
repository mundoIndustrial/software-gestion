/**
 * PrendaCardProcessService
 * Estrategias de render por modo de proceso para PrendaCardService.
 */
globalThis.PrendaCardProcessService = {
    resolverModoProceso(datos, proceso) {
        if (globalThis.PrendaCardNormalizers?.resolverModoProceso) {
            return globalThis.PrendaCardNormalizers.resolverModoProceso({ datos, proceso });
        }

        const modoTallasResuelto = datos?.modo_tallas || datos?.modoTallas || proceso?.modo_tallas || proceso?.modoTallas || 'generico';
        const esGeneralMode = modoTallasResuelto === 'general' || modoTallasResuelto === 'generico';
        const esPorTallas = !esGeneralMode && !!(datos?.datosExtendidos);

        return {
            modoTallasResuelto,
            esGeneralMode,
            esPorTallas,
            tipoRender: esPorTallas ? 'por_tallas' : (esGeneralMode ? 'general' : 'generico'),
            tieneDatosExtendidos: !!(datos?.datosExtendidos)
        };
    },

    iconosProcesos() {
        return {
            reflectivo: '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
            bordado: '<i class="fas fa-gem" style="color: #1e40af;"></i>',
            estampado: '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
            dtf: '<i class="fas fa-print" style="color: #06b6d4;"></i>',
            sublimado: '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
        };
    },

    construirMetaProceso({ tipoProceso, proceso, iconosProcesos }) {
        const datos = proceso?.datos || {};
        const icono = iconosProcesos[tipoProceso] || '<i class="fas fa-cog"></i>';
        const nombreProceso = datos.nombre || tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
        const modoProceso = this.resolverModoProceso(datos, proceso);
        return { datos, icono, nombreProceso, modoProceso };
    },

    renderProcesoPorTallas({ datos, icono, nombreProceso, renderers }) {
        const generos = {
            dama: { label: 'DAMA', icon: '<i class="fas fa-female"></i>', color: '#be185d', bg: '#fdf2f8', border: '#fbcfe8' },
            caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male"></i>', color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' }
        };

        const imagenesProceso = Array.isArray(datos.imagenes) ? datos.imagenes : [];
        const fotosThumb = renderers.renderThumbs({
            items: imagenesProceso,
            imageStyle: 'width: 45px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db;'
        });
        const fotosGeneralesHTML = fotosThumb ? `
            <div style="margin-bottom: 0.75rem;">
                <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                    <i class="fas fa-images"></i>IMAGENES DEL PROCESO (${imagenesProceso.length})
                </strong>
                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                    ${fotosThumb}
                </div>
            </div>
        ` : '';

        const porTallasHTML = renderers.renderProcesoPorTallasItems({ generos, datos });

        return renderers.renderProcesoCard({
            icono,
            nombreProceso,
            badgeHtml: '<span style="background: #7c3aed; color: white; padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">Por Tallas</span>',
            contentHtml: `
                ${fotosGeneralesHTML}
                <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                    ${porTallasHTML}
                </div>
            `
        });
    },

    renderProcesoGeneral({ datos, icono, nombreProceso, deps }) {
        const {
            renderers,
            normalizarUbicaciones,
            obtenerObjetoGenero,
            colectarFotosProceso,
            debugLog,
            construirOnClickMostrarImagenProceso
        } = deps;

        const ubicacionesNormalizadas = normalizarUbicaciones(datos.ubicaciones);
        const ubicacionGeneral = datos.ubicacionGeneral || ubicacionesNormalizadas.join(', ') || '';

        let ubicacionGeneralHTML = '';
        if (ubicacionGeneral) {
            ubicacionGeneralHTML = renderers.renderBloqueUbicaciones({
                ubicacionesHTML: `<span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${ubicacionGeneral}
                </span>`
            });
        }

        let tallasGeneralesHTML = '';
        if (datos.tallas) {
            const damaObj = obtenerObjetoGenero(datos.tallas, 'dama');
            const caballeroObj = obtenerObjetoGenero(datos.tallas, 'caballero');
            const sobremedidaObj = obtenerObjetoGenero(datos.tallas, 'sobremedida');
            tallasGeneralesHTML = renderers.renderTallasGeneralesProceso({ damaObj, caballeroObj, sobremedidaObj });
        }

        const observacionesGeneralesHTML = datos.observaciones
            ? renderers.renderBloqueObservaciones({ texto: datos.observaciones })
            : '';

        const fotosGenerales = colectarFotosProceso(datos, {
            incluirImagenesFallback: true,
            contextoLog: 'PrendaCardService-ReadonlyProceso'
        });

        const fotosGeneralesHTML = renderers.renderProcesoFotosGenerales({
            fotos: fotosGenerales,
            nombreProceso,
            titulo: 'Imágenes',
            toSrc: (img, idx) => {
                const src = renderers.resolverSrcImagen(img);
                if (img instanceof File && src) {
                    debugLog('[PrendaCardService-ReadonlyProceso] File ' + idx + ' convertido a blob: ' + src.substring(0, 50) + '...');
                }
                if (typeof img === 'string' && src && !src.startsWith('blob:') && !src.startsWith('http') && !src.startsWith('/')) {
                    return '/storage/' + src;
                }
                return src;
            },
            onClickAttrBuilder: (src) => construirOnClickMostrarImagenProceso(src)
        });

        const observacionesPorTallaHTML = renderers.renderObservacionesPorTallaProceso({
            datosExtendidos: datos.datosExtendidos || {},
            tallas: datos.tallas || {}
        });

        return renderers.renderProcesoGeneralCard({
            icono,
            nombreProceso,
            ubicacionGeneralHTML,
            tallasGeneralesHTML,
            observacionesGeneralesHTML,
            fotosGeneralesHTML,
            observacionesPorTallaHTML
        });
    },

    renderProcesoGenerico({ datos, icono, nombreProceso, deps }) {
        return deps.renderers.renderProcesoGenericoCard({
            icono,
            nombreProceso,
            datos,
            onClickAttrBuilder: (src) => deps.construirOnClickMostrarImagenProceso(src)
        });
    },

    renderProcesoSegunModo({ meta, deps }) {
        const { datos, icono, nombreProceso, modoProceso } = meta;
        if (modoProceso.esPorTallas && datos.datosExtendidos) {
            return this.renderProcesoPorTallas({ datos, icono, nombreProceso, renderers: deps.renderers });
        }
        if (modoProceso.esGeneralMode) {
            return this.renderProcesoGeneral({ datos, icono, nombreProceso, deps });
        }
        return this.renderProcesoGenerico({ datos, icono, nombreProceso, deps });
    }
};
