/**
 * PrendaCardNormalizers
 * Utilidades de normalizacion para PrendaCardService.
 */
window.PrendaCardNormalizers = {
    escapeJsSingleQuoted(value) {
        return String(value ?? '')
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/\r/g, '\\r')
            .replace(/\n/g, '\\n');
    },

    esTipoProcesoValido(valor) {
        if (valor === null || valor === undefined) return false;
        const texto = String(valor).trim();
        if (!texto) return false;
        if (/^\d+$/.test(texto)) return false;
        if (/^proceso[_-\s]?\d+$/i.test(texto)) return false;
        return true;
    },

    normalizarTipoProceso(...candidatos) {
        for (const candidato of candidatos) {
            if (!this.esTipoProcesoValido(candidato)) continue;
            return String(candidato).toLowerCase().trim().replace(/\s+/g, '-');
        }
        return 'proceso';
    },

    normalizarEntradaProceso(value, fallbackTipo) {
        const datos = value?.datos || value || {};
        const tipo = this.normalizarTipoProceso(
            datos.tipo,
            datos.tipo_proceso,
            datos.nombre,
            datos.nombre_proceso,
            datos.tipoProceso?.nombre,
            fallbackTipo
        );
        const nombre = datos.nombre || datos.tipo_proceso || datos.nombre_proceso || datos.tipoProceso?.nombre || tipo;

        return value?.datos
            ? { ...value, tipo, datos: { ...datos, tipo, nombre } }
            : { tipo, datos: { ...datos, tipo, nombre } };
    },

    normalizarProcesos(procesos) {
        if (!procesos) return {};

        const normalizados = {};
        if (!Array.isArray(procesos) && typeof procesos === 'object') {
            Object.entries(procesos).forEach(([key, value]) => {
                if (!value) return;
                const normalizado = this.normalizarEntradaProceso(value, key);
                normalizados[normalizado.tipo] = normalizado;
            });
            return normalizados;
        }

        procesos.forEach((value, idx) => {
            if (!value) return;
            const normalizado = this.normalizarEntradaProceso(value, `proceso_${idx}`);
            normalizados[normalizado.tipo] = normalizado;
        });
        return normalizados;
    },

    resolverModoProceso({ datos = {}, proceso = {} } = {}) {
        const modoCrudo = datos.modo_tallas || datos.modoTallas || proceso.modo_tallas || proceso.modoTallas || 'generico';
        const modoTallasResuelto = String(modoCrudo || 'generico').toLowerCase().trim();
        const esGeneralMode = modoTallasResuelto === 'general' || modoTallasResuelto === 'generico';
        const tieneDatosExtendidos = !!(datos && datos.datosExtendidos);
        const esPorTallas = !esGeneralMode && tieneDatosExtendidos;
        const tipoRender = esPorTallas ? 'por_tallas' : (esGeneralMode ? 'general' : 'generico');

        return {
            modoTallasResuelto,
            esGeneralMode,
            esPorTallas,
            tipoRender,
            tieneDatosExtendidos
        };
    },

    normalizarUbicaciones(ubicaciones) {
        if (!ubicaciones) return [];
        const lista = Array.isArray(ubicaciones) ? ubicaciones : [ubicaciones];

        return lista
            .map((ubicacion) => {
                if (!ubicacion) return '';
                if (typeof ubicacion === 'string') return ubicacion.trim();
                if (typeof ubicacion === 'object') {
                    return String(
                        ubicacion.ubicacion ||
                        ubicacion.nombre ||
                        ubicacion.descripcion ||
                        ubicacion.label ||
                        ''
                    ).trim();
                }
                return String(ubicacion).trim();
            })
            .filter(Boolean);
    },

    normalizarColeccion(coleccion) {
        if (!coleccion) return [];
        if (Array.isArray(coleccion)) return coleccion;
        if (typeof coleccion === 'object') return Object.values(coleccion);
        return [];
    },

    normalizarTelas(telas) {
        return this.normalizarColeccion(telas);
    },

    normalizarImagenes(imagenes) {
        return this.normalizarColeccion(imagenes);
    },

    escapeHtml(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    normalizarSrcImagen(imagen) {
        if (!imagen) return null;

        if (imagen instanceof File || imagen instanceof Blob) {
            return URL.createObjectURL(imagen);
        }

        if (typeof imagen === 'string') {
            const src = imagen.trim();
            if (!src) return null;
            if (src.startsWith('blob:') || src.startsWith('data:') || src.startsWith('http') || src.startsWith('/')) {
                return src;
            }
            if (src.startsWith('storage/')) {
                return `/${src}`;
            }
            return `/storage/${src}`;
        }

        if (typeof imagen === 'object') {
            if (imagen.file instanceof File || imagen.file instanceof Blob) {
                return URL.createObjectURL(imagen.file);
            }

            const candidata =
                imagen.blobUrl ||
                imagen.previewUrl ||
                imagen.dataURL ||
                imagen.ruta ||
                imagen.ruta_webp ||
                imagen.ruta_original ||
                imagen.url ||
                imagen.src ||
                null;

            return this.normalizarSrcImagen(candidata);
        }

        return null;
    }
};
