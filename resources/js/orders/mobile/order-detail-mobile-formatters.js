(() => {
    const normalizarTituloRecibo = (valor, fallback = 'COSTURA') => {
        const titulo = String(valor || '').trim().toUpperCase();
        if (!titulo || titulo === 'PARCIAL') {
            const fallbackUpper = String(fallback || '').trim().toUpperCase();
            return fallbackUpper || 'COSTURA';
        }
        return titulo;
    };

    const normalizarUbicaciones = (raw) => {
        const out = [];
        const pushVal = (v) => {
            if (v === null || v === undefined) return;
            if (typeof v === 'string') {
                const s = v.trim();
                if (!s) return;
                out.push(s);
                return;
            }
            if (typeof v === 'number') {
                out.push(String(v));
                return;
            }
            if (Array.isArray(v)) {
                v.forEach(pushVal);
                return;
            }
            if (typeof v === 'object') {
                if (v.seccion && v.ubicaciones_seleccionadas) {
                    const seccion = String(v.seccion).trim();
                    const ubs = [];
                    if (Array.isArray(v.ubicaciones_seleccionadas)) {
                        v.ubicaciones_seleccionadas.forEach((x) => {
                            if (x === null || x === undefined) return;
                            const s = (typeof x === 'string') ? x.trim() : String(x);
                            if (s) ubs.push(s);
                        });
                    } else {
                        const s = (typeof v.ubicaciones_seleccionadas === 'string')
                            ? v.ubicaciones_seleccionadas.trim()
                            : String(v.ubicaciones_seleccionadas);
                        if (s) ubs.push(s);
                    }
                    if (seccion && ubs.length > 0) {
                        out.push(seccion + ': ' + ubs.join(', '));
                    } else if (ubs.length > 0) {
                        ubs.forEach((x) => out.push(x));
                    }
                    return;
                }
                if (v.ubicacion) {
                    pushVal(v.ubicacion);
                    return;
                }
                if (v.nombre) {
                    pushVal(v.nombre);
                    return;
                }
                try {
                    out.push(JSON.stringify(v));
                } catch (e) {
                    out.push(String(v));
                }
            }
        };

        try {
            if (typeof raw === 'string') {
                const s = raw.trim();
                if (s.startsWith('[') || s.startsWith('{')) {
                    pushVal(JSON.parse(s));
                } else {
                    pushVal(s);
                }
            } else {
                pushVal(raw);
            }
        } catch (e) {
            pushVal(raw);
        }

        return out
            .map((x) => (x || '').toString().trim())
            .filter((x) => x);
    };

    const transformarVariantesAEstructura = (variantesArray) => {
        console.log('[OPERARIO] Transformando variantes:', variantesArray);

        if (!Array.isArray(variantesArray) || variantesArray.length === 0) {
            console.warn('[OPERARIO] Array de variantes vacio o invalido');
            return {};
        }

        const estructura = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };

        variantesArray.forEach((variante, idx) => {
            const genero = (variante.genero || '').toUpperCase();
            const talla = (variante.talla || '').trim().toUpperCase();
            const cantidad = parseInt(variante.cantidad || 0, 10);
            const esSobremedida = variante.es_sobremedida || false;

            console.log(`[OPERARIO] Variante ${idx}: genero=${genero}, talla=${talla}, cant=${cantidad}, es_sobremedida=${esSobremedida}`);

            const tallaFinal = esSobremedida
                ? 'SOBREMEDIDA'
                : (talla || 'SIN_TALLA');

            console.log(`[OPERARIO] Variante ${idx}: tallaFinal=${tallaFinal}`);

            if (!genero || !tallaFinal || cantidad <= 0) {
                console.warn('[OPERARIO] Variante invalida: saltando');
                return;
            }

            if (!Object.prototype.hasOwnProperty.call(estructura, genero)) {
                console.warn(`[OPERARIO] Genero invalido: ${genero}`);
                return;
            }

            estructura[genero][tallaFinal] = cantidad;
            console.log(`[OPERARIO]   Agregado: ${genero} ${tallaFinal} = ${cantidad}`);
        });

        console.log('[OPERARIO] Estructura final de variantes:', JSON.stringify(estructura, null, 2));
        return estructura;
    };

    const derivarTallaColoresDesdeVariantes = (variantesArray) => {
        if (!Array.isArray(variantesArray) || variantesArray.length === 0) {
            return [];
        }

        const out = [];
        variantesArray.forEach((v) => {
            const genero = (v?.genero || '').toString().trim().toUpperCase();
            const talla = (v?.talla || '').toString().trim().toUpperCase();
            if (!genero || !talla) return;

            const detalles = Array.isArray(v?.colores_detalle) ? v.colores_detalle : [];
            if (detalles.length === 0) return;

            detalles.forEach((d) => {
                const color = (d?.color || '').toString().trim().toUpperCase();
                const cantidad = parseInt(d?.cantidad || 0, 10) || 0;
                if (!cantidad) return;
                out.push({ genero, talla, color_nombre: color || 'SIN COLOR', cantidad });
            });
        });

        return out;
    };

    const transformarTallaColoresAEstructura = (tallasColoresArray) => {
        console.log('[OPERARIO] Transformando talla_colores:', tallasColoresArray);

        if (!Array.isArray(tallasColoresArray) || tallasColoresArray.length === 0) {
            console.warn('[OPERARIO] Array de talla_colores vacio o invalido');
            return {};
        }

        const estructura = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };

        tallasColoresArray.forEach((registro, idx) => {
            const genero = (registro.genero || '').toUpperCase();
            const talla = (registro.talla || '').trim().toUpperCase();
            const colorNombre = (registro.color_nombre || '').trim().toUpperCase();
            const cantidad = parseInt(registro.cantidad || 0, 10);

            console.log(`[OPERARIO] Reg ${idx}: genero=${genero}, talla=${talla}, color=${colorNombre}, cant=${cantidad}`);

            if (!genero || !talla || cantidad <= 0) {
                console.warn('[OPERARIO] Registro invalido: saltando');
                return;
            }

            if (!Object.prototype.hasOwnProperty.call(estructura, genero)) {
                console.warn(`[OPERARIO] Genero invalido: ${genero}`);
                return;
            }

            if (!estructura[genero][talla]) {
                estructura[genero][talla] = [];
            }

            if (Array.isArray(estructura[genero][talla])) {
                const colorExistente = estructura[genero][talla].find((c) =>
                    c.color === (colorNombre || 'SIN COLOR')
                );

                if (colorExistente) {
                    colorExistente.cantidad += cantidad;
                    console.log(`[OPERARIO]   Color existente actualizado: ${colorNombre} -> ${colorExistente.cantidad}`);
                } else {
                    estructura[genero][talla].push({
                        color: colorNombre || 'SIN COLOR',
                        cantidad
                    });
                    console.log(`[OPERARIO]   Nuevo color: ${colorNombre || 'SIN COLOR'} = ${cantidad}`);
                }
            }
        });

        console.log('[OPERARIO] Estructura final:', JSON.stringify(estructura, null, 2));
        return estructura;
    };

    const transformarTallasListaParcialAEstructura = (tallasArray) => {
        if (!Array.isArray(tallasArray) || tallasArray.length === 0) {
            return {};
        }

        const registros = tallasArray
            .map((registro) => ({
                genero: (registro?.genero || 'CABALLERO').toString().trim().toUpperCase(),
                talla: (registro?.talla || '').toString().trim().toUpperCase(),
                color_nombre: (registro?.color_nombre || '').toString().trim().toUpperCase(),
                cantidad: parseInt(registro?.cantidad || 0, 10) || 0,
            }))
            .filter((registro) => registro.cantidad > 0);

        if (registros.length === 0) {
            return {};
        }

        const tieneColores = registros.some((registro) => registro.color_nombre !== '');
        if (tieneColores) {
            return transformarTallaColoresAEstructura(registros);
        }

        const estructura = {
            DAMA: {},
            CABALLERO: {},
            UNISEX: {}
        };

        registros.forEach((registro) => {
            if (!estructura[registro.genero]) {
                estructura[registro.genero] = {};
            }
            const tallaFinal = registro.talla !== ''
                ? registro.talla
                : 'SIN_TALLA';
            estructura[registro.genero][tallaFinal] = (estructura[registro.genero][tallaFinal] || 0) + registro.cantidad;
        });

        return estructura;
    };

    window.OrderDetailMobileFormatters = {
        normalizarTituloRecibo,
        normalizarUbicaciones,
        transformarVariantesAEstructura,
        derivarTallaColoresDesdeVariantes,
        transformarTallaColoresAEstructura,
        transformarTallasListaParcialAEstructura
    };
})();
