/**
 * PrendaCardService - Generación de HTML para tarjetas de prenda
 * Servicio centralizado para construir tarjetas en modo solo lectura
 */

globalThis.PrendaCardService = {
    configurarContextoBase(contexto = {}) {
        globalThis.PrendaCardContextService?.configurarBase(contexto);
    },

    _crearContexto(overrides = {}) {
        if (!globalThis.PrendaCardContextService) {
            throw new Error('[PrendaCardService] Falta PrendaCardContextService.');
        }
        return globalThis.PrendaCardContextService.crear(overrides);
    },

    _validarContexto(ctx) {
        if (!globalThis.PrendaCardContextService) {
            throw new Error('[PrendaCardService] Falta PrendaCardContextService.');
        }
        globalThis.PrendaCardContextService.validar(ctx);
    },

    _debugEnabled() {
        return Boolean(globalThis.PRENDA_CARD_DEBUG);
    },

    _debugLog(...args) {
        if (!this._debugEnabled()) return;
        console.log(...args);
    },

    _debugWarn(...args) {
        if (!this._debugEnabled()) return;
        console.warn(...args);
    },

    _escapeJsSingleQuoted(value) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.escapeJsSingleQuoted(value)
            : String(value ?? '');
    },

    _construirOnClickMostrarImagenProceso(src) {
        const srcSafe = this._escapeJsSingleQuoted(src);
        return `onclick="globalThis.PrendaCardService._handleProcessImageClick('${srcSafe}')"`;
    },

    _handleProcessImageClick(src) {
        const callback = this._runtimeContext?.showProcessImage;
        if (typeof callback === 'function') {
            callback(src);
            return;
        }
        this._debugWarn('[PrendaCardService] showProcessImage no está configurado en el contexto.');
    },

    _esTipoProcesoValido(valor) {
        if (!globalThis.PrendaCardNormalizers) return false;
        return globalThis.PrendaCardNormalizers.esTipoProcesoValido(valor);
    },

    _normalizarTipoProceso(...candidatos) {
        if (!globalThis.PrendaCardNormalizers) return 'proceso';
        return globalThis.PrendaCardNormalizers.normalizarTipoProceso(...candidatos);
    },

    _obtenerObjetoGenero(obj, genero) {
        if (!obj || typeof obj !== 'object') return {};
        const resultado = obj[genero] || obj[genero.toUpperCase()] || obj[genero.toLowerCase()] || {};
        console.log('[PrendaCardService][_obtenerObjetoGenero]', {
            generoPedido: genero,
            keysDisponibles: Object.keys(obj || {}),
            resultadoKeys: Object.keys(resultado || {}),
            resultado
        });
        return resultado;
    },

    _normalizarUbicaciones(ubicaciones) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarUbicaciones(ubicaciones)
            : [];
    },

    _normalizarColeccion(coleccion) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarColeccion(coleccion)
            : [];
    },

    _normalizarTelas(telas) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarTelas(telas)
            : this._normalizarColeccion(telas);
    },

    _deduplicarTelasParaVista(telas) {
        if (!Array.isArray(telas) || telas.length === 0) return [];

        const mapa = new Map();

        telas.forEach((telaItem, idx) => {
            const nombre = String(telaItem?.tela || telaItem?.nombre_tela || telaItem?.nombre || '').trim();
            const referencia = String(telaItem?.referencia || '').trim();
            const key = `${nombre.toUpperCase()}|${referencia.toUpperCase()}`;

            if (!mapa.has(key)) {
                mapa.set(key, {
                    ...telaItem,
                    _displayCount: 1,
                    _displayIndices: [idx],
                    _displayColores: telaItem?.color || telaItem?.color_nombre ? [String(telaItem.color || telaItem.color_nombre).trim()] : []
                });
                return;
            }

            const existente = mapa.get(key);
            existente._displayCount += 1;
            existente._displayIndices.push(idx);

            const colorActual = String(telaItem?.color || telaItem?.color_nombre || '').trim();
            if (colorActual && !existente._displayColores.some((c) => c.toUpperCase() === colorActual.toUpperCase())) {
                existente._displayColores.push(colorActual);
            }

            if (!existente.id && (telaItem?.id || telaItem?._original_id || telaItem?.prenda_pedido_colores_telas_id)) {
                existente.id = telaItem?.id || telaItem?._original_id || telaItem?.prenda_pedido_colores_telas_id;
            }

            if ((!existente.imagenes || existente.imagenes.length === 0) && Array.isArray(telaItem?.imagenes) && telaItem.imagenes.length > 0) {
                existente.imagenes = telaItem.imagenes;
            }
        });

        return Array.from(mapa.values());
    },

    _normalizarImagenes(imagenes) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarImagenes(imagenes)
            : this._normalizarColeccion(imagenes);
    },

    _obtenerDataUtils() {
        if (!globalThis.PrendaCardDataUtils) {
            throw new Error('[PrendaCardService] Falta PrendaCardDataUtils.');
        }
        return globalThis.PrendaCardDataUtils;
    },

    _normalizarProcesos(procesos) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarProcesos(procesos)
            : {};
    },

    _escapeHtml(valor) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.escapeHtml(valor)
            : String(valor ?? '');
    },

    _normalizarSrcImagen(imagen) {
        return globalThis.PrendaCardNormalizers
            ? globalThis.PrendaCardNormalizers.normalizarSrcImagen(imagen)
            : null;
    },

    _obtenerImagenDesdeStore(imagenId, ctx = this._crearContexto()) {
        const store = ctx.coloresPorTallaStore;
        if (!imagenId || !store || typeof store.getImage !== 'function') {
            return null;
        }

        try {
            return this._normalizarSrcImagen(store.getImage(imagenId));
        } catch (error) {
            this._debugWarn('[PrendaCardService] No se pudo resolver imagen del store:', imagenId, error);
            return null;
        }
    },

    _obtenerImagenColorAsignacion(color, imagenFallback = null, ctx = this._crearContexto()) {
        if (!color || typeof color !== 'object') {
            return imagenFallback;
        }

        return (
            this._obtenerImagenDesdeStore(color.imagen_id, ctx) ||
            this._normalizarSrcImagen(
                color.imagen ||
                color.imagen_ruta ||
                color.ruta ||
                color.ruta_webp ||
                color.ruta_original ||
                color.url ||
                color.src ||
                color.previewUrl
            ) ||
            imagenFallback
        );
    },
    _calcularNumeroItem(indice, ctx = this._crearContexto()) {
        let numeroItem = indice + 1;
        const ordenItems = ctx.gestionItemsUI?.ordenItems;
        if (Array.isArray(ordenItems)) {
            let prendaCount = 0;
            for (const item of ordenItems) {
                if (item.tipo === 'prenda' && item.index < indice) {
                    prendaCount++;
                }
            }
            numeroItem = prendaCount + 1;
        }
        return numeroItem;
    },

    _construirHtmlTarjeta(datos) {
        if (!globalThis.PrendaCardRenderers) {
            throw new Error('[PrendaCardService] Falta PrendaCardRenderers.');
        }
        return globalThis.PrendaCardRenderers.renderTarjeta(datos);
    },

    generar(prenda, indice, contextOverrides = {}) {
        const ctx = this._crearContexto(contextOverrides);
        this._validarContexto(ctx);
        this._runtimeContext = ctx;
        const imagenes = this._normalizarImagenes(prenda.imagenes || prenda.fotos || prenda.fotos_prenda || []);
        const fotoPrincipal = ctx.imageConverter ?
            ctx.imageConverter.obtenerPrimeraImagen(imagenes) :
            null;
        const descripcion = prenda.descripcion || '';

        const asignacionesColoresPorTalla = this._obtenerDataUtils().resolverAsignacionesColoresPorTalla(prenda);
        const tipoFlujoTallas = String(prenda?.tipo_flujo_tallas || '').toLowerCase();
        const esFlujoTallaColor = tipoFlujoTallas === 'talla_color';
        const prendaParaRender = {
            ...prenda,
            asignacionesColoresPorTalla,
            tipo_flujo_tallas: tipoFlujoTallas,
        };

        const variacionesHTML = this._construirVariaciones(prendaParaRender, indice);
        const procesosHTML = this._construirProcesos(prendaParaRender, indice);
        const usarSeccionCombinada = esFlujoTallaColor;

        const tablaTelasHTML = usarSeccionCombinada
            ? ''
            : this._construirTablaTelas(prendaParaRender, indice, ctx);
        const tallasYCantidadesHTML = usarSeccionCombinada
            ? this._construirSeccionCombinada(prendaParaRender, indice, ctx)
            : this._construirTallasYCantidades(prendaParaRender, indice, ctx);

        const numeroItem = this._calcularNumeroItem(indice, ctx);

        return this._construirHtmlTarjeta({
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
        });
    },

    _construirVariaciones(prenda, indice) {
        if (!globalThis.PrendaCardVariationsService) {
            throw new Error('[PrendaCardService] Falta PrendaCardVariationsService.');
        }
        return globalThis.PrendaCardVariationsService.construirVariaciones(prenda, indice);
    },
    _construirTallasYCantidades(prenda, indice, ctx = this._crearContexto()) {
        if (!globalThis.PrendaCardSizingService) {
            throw new Error('[PrendaCardService] Falta PrendaCardSizingService.');
        }
        return globalThis.PrendaCardSizingService.construirTallasYCantidades(this, prenda, indice, ctx);
    },
    _obtenerFotoTelaDesdeRelacion(prenda, telaItem, ctx = this._crearContexto()) {
        const imageConverter = ctx.imageConverter;
        if (!Array.isArray(prenda?.colores_telas) || prenda.colores_telas.length === 0 || !imageConverter) {
            return null;
        }

        const relacionId = telaItem?.prenda_pedido_colores_telas_id ?? telaItem?.id ?? telaItem?._original_id ?? null;
        const colorId = Number(telaItem?.color_id || 0);
        const telaId = Number(telaItem?.tela_id || 0);

        const match = prenda.colores_telas.find((ct) => {
            if (!ct) return false;
            if (relacionId && (ct.id === relacionId || ct.prenda_pedido_colores_telas_id === relacionId)) {
                return true;
            }

            const ctColorId = Number(ct.color_id || 0);
            const ctTelaId = Number(ct.tela_id || 0);
            return colorId > 0 && telaId > 0 && ctColorId === colorId && ctTelaId === telaId;
        });

        if (!match) return null;

        const fotosTela = Array.isArray(match.fotos_tela) ? match.fotos_tela : [];
        if (fotosTela.length === 0) return null;

        return imageConverter.obtenerPrimeraImagen(fotosTela);
    },

    /**
     * Sección combinada: Tela + Tallas + Colores en un solo expandible
     * Se usa cuando hay asignaciones de colores (flujo wizard)
     */
    _construirSeccionCombinada(prenda, indice, ctx = this._crearContexto()) {
        if (!globalThis.PrendaCardSizingService) {
            throw new Error('[PrendaCardService] Falta PrendaCardSizingService.');
        }
        return globalThis.PrendaCardSizingService.construirSeccionCombinada(this, prenda, indice, ctx);
    },
    _construirProcesos(prenda, indice) {
        if (!globalThis.PrendaCardProcessService) {
            throw new Error('[PrendaCardService] Falta PrendaCardProcessService.');
        }

        const procesos = this._normalizarProcesos(prenda.procesos || {});
        const procesosConDatos = Object.entries(procesos).filter(([_, proc]) => proc && (proc.datos !== null || proc.tipo));
        if (procesosConDatos.length === 0) {
            return '';
        }

        const processService = globalThis.PrendaCardProcessService;
        const renderers = globalThis.PrendaCardRenderers;
        const iconosProcesos = processService.iconosProcesos();
        const deps = {
            renderers,
            normalizarUbicaciones: this._normalizarUbicaciones.bind(this),
            obtenerObjetoGenero: this._obtenerObjetoGenero.bind(this),
            colectarFotosProceso: (datos, opciones = {}) => this._obtenerDataUtils().colectarFotosProceso(datos, { ...opciones, debugLog: this._debugLog.bind(this) }),
            debugLog: this._debugLog.bind(this),
            construirOnClickMostrarImagenProceso: this._construirOnClickMostrarImagenProceso.bind(this)
        };

        let procesosItemsHTML = '';
        procesosConDatos.forEach(([tipoProceso, proceso]) => {
            const meta = processService.construirMetaProceso({
                tipoProceso,
                proceso,
                iconosProcesos
            });
            procesosItemsHTML += processService.renderProcesoSegunModo({ meta, deps });
        });

        return renderers.renderProcesosSection({
            indice,
            procesosCount: procesosConDatos.length,
            procesosItemsHTML
        });
    },

    /**
     * Construir tabla de telas con todas las variaciones
     */
    _construirTablaTelas(prenda, indice, ctx = this._crearContexto()) {
        if (!globalThis.PrendaCardRenderers) {
            throw new Error('[PrendaCardService] Falta PrendaCardRenderers.');
        }
        return globalThis.PrendaCardRenderers.renderTablaTelas({
            prenda,
            normalizarTelas: this._normalizarTelas.bind(this),
            imageConverter: ctx.imageConverter
        });
    }
};



