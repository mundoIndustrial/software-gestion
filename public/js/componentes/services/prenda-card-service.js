/**
 * PrendaCardService - Generación de HTML para tarjetas de prenda
 * Servicio centralizado para construir tarjetas en modo solo lectura
 */

window.PrendaCardService = {
    _esTipoProcesoValido(valor) {
        if (valor === null || valor === undefined) return false;
        const texto = String(valor).trim();
        if (!texto) return false;
        if (/^\d+$/.test(texto)) return false;
        if (/^proceso[_-\s]?\d+$/i.test(texto)) return false;
        return true;
    },

    _normalizarTipoProceso(...candidatos) {
        for (const candidato of candidatos) {
            if (!this._esTipoProcesoValido(candidato)) continue;
            return String(candidato).toLowerCase().trim().replace(/\s+/g, '-');
        }

        return 'proceso';
    },

    _obtenerObjetoGenero(obj, genero) {
        if (!obj || typeof obj !== 'object') return {};
        return obj[genero] || obj[genero.toUpperCase()] || obj[genero.toLowerCase()] || {};
    },

    _normalizarUbicaciones(ubicaciones) {
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

    _normalizarTelas(telas) {
        if (!telas) return [];
        if (Array.isArray(telas)) return telas;
        if (typeof telas === 'object') return Object.values(telas);
        return [];
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
        if (!imagenes) return [];
        if (Array.isArray(imagenes)) return imagenes;
        if (typeof imagenes === 'object') return Object.values(imagenes);
        return [];
    },

    _normalizarProcesos(procesos) {
        if (!procesos) return {};
        if (!Array.isArray(procesos) && typeof procesos === 'object') {
            const normalizados = {};
            Object.entries(procesos).forEach(([key, value]) => {
                if (!value) return;
                const datos = value.datos || value;
                const tipo = this._normalizarTipoProceso(
                    datos.tipo,
                    datos.tipo_proceso,
                    datos.nombre,
                    datos.nombre_proceso,
                    datos.tipoProceso?.nombre,
                    key
                );
                const nombre = datos.nombre || datos.tipo_proceso || datos.nombre_proceso || datos.tipoProceso?.nombre || tipo;
                normalizados[tipo] = value.datos
                    ? { ...value, tipo, datos: { ...datos, tipo, nombre } }
                    : { tipo, datos: { ...datos, tipo, nombre } };
            });
            return normalizados;
        }

        const normalizados = {};
        procesos.forEach((value, idx) => {
            if (!value) return;
            const datos = value.datos || value;
            const tipo = this._normalizarTipoProceso(
                datos.tipo,
                datos.tipo_proceso,
                datos.nombre,
                datos.nombre_proceso,
                datos.tipoProceso?.nombre,
                `proceso_${idx}`
            );
            const nombre = datos.nombre || datos.tipo_proceso || datos.nombre_proceso || datos.tipoProceso?.nombre || tipo;
            normalizados[tipo] = value.datos
                ? { ...value, tipo, datos: { ...datos, tipo, nombre } }
                : { tipo, datos: { ...datos, tipo, nombre } };
        });
        return normalizados;
    },

    _resolverAsignacionesColoresPorTalla(prenda) {
        const fuenteDirecta = prenda?.asignacionesColoresPorTalla;
        if (fuenteDirecta && typeof fuenteDirecta === 'object' && Object.keys(fuenteDirecta).length > 0) {
            return fuenteDirecta;
        }

        return {};
    },

    _escapeHtml(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    _normalizarSrcImagen(imagen) {
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

            return this._normalizarSrcImagen(candidata);
        }

        return null;
    },

    _obtenerImagenDesdeStore(imagenId) {
        if (!imagenId || !window.ColoresPorTalla || typeof window.ColoresPorTalla.getImage !== 'function') {
            return null;
        }

        try {
            return this._normalizarSrcImagen(window.ColoresPorTalla.getImage(imagenId));
        } catch (error) {
            console.warn('[PrendaCardService] No se pudo resolver imagen del store:', imagenId, error);
            return null;
        }
    },

    _obtenerImagenColorAsignacion(color, imagenFallback = null) {
        if (!color || typeof color !== 'object') {
            return imagenFallback;
        }

        return (
            this._obtenerImagenDesdeStore(color.imagen_id) ||
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

    generar(prenda, indice) {

        
        // Usar las propiedades correctas
        const imagenes = this._normalizarImagenes(prenda.imagenes || prenda.fotos || prenda.fotos_prenda || []);
        
        // Usar servicio centralizado para convertir imágenes
        const fotoPrincipal = window.ImageConverterService ? 
            window.ImageConverterService.obtenerPrimeraImagen(imagenes) : 
            null;
        
        const descripcion = prenda.descripcion || '';
        
        // Obtener información de tela
        let tela = 'N/A';
        let color = 'N/A';
        let referencia = 'N/A';
        let telaFoto = null;
        
        // Obtener información de tela desde múltiples fuentes
        const telasAgregadasNormalizadas = this._normalizarTelas(prenda.telasAgregadas);
        const telasNormalizadas = this._normalizarTelas(prenda.telas);
        
        if (telasAgregadasNormalizadas.length > 0) {
            const telaPrincipal = telasAgregadasNormalizadas[0];
            tela = telaPrincipal.tela || 'N/A';
            color = telaPrincipal.color || 'N/A';
            referencia = telaPrincipal.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaPrincipal) : 
                null;

        }
        else if ((prenda.tela || prenda.color) && prenda.imagenes_tela) {
            tela = prenda.tela || 'N/A';
            color = prenda.color || 'N/A';
            referencia = prenda.ref || prenda.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerPrimeraImagen(prenda.imagenes_tela) : 
                null;

        }
        else if (telasNormalizadas.length > 0) {
            const telaPrincipal = telasNormalizadas[0];
            tela = telaPrincipal.nombre_tela || telaPrincipal.tela || 'N/A';
            color = telaPrincipal.color || 'N/A';
            referencia = telaPrincipal.referencia || 'N/A';
            telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaPrincipal) : 
                null;

        }
        else {
            tela = prenda.variantes?.tela || prenda.tela || 'N/A';
            color = prenda.variantes?.color || prenda.color || 'N/A';
            referencia = prenda.variantes?.referencia || prenda.referencia || prenda.ref || 'N/A';

        }







        const asignacionesColoresPorTalla = this._resolverAsignacionesColoresPorTalla(prenda);
        const tipoFlujoTallas = String(prenda?.tipo_flujo_tallas || '').toLowerCase();
        const esFlujoTallaColor = tipoFlujoTallas === 'talla_color';
        const prendaParaRender = {
            ...prenda,
            asignacionesColoresPorTalla,
            tipo_flujo_tallas: tipoFlujoTallas,
        };

        // Construir secciones
        const variacionesHTML = this._construirVariaciones(prendaParaRender, indice);
        const procesosHTML = this._construirProcesos(prendaParaRender, indice);
        
        // Detectar si hay asignaciones de colores → combinar tela + tallas en una sola sección
        const usarSeccionCombinada = esFlujoTallaColor;
        
        let tablaTelasHTML = '';
        let tallasYCantidadesHTML = '';
        
        if (usarSeccionCombinada) {
            // Sección combinada: Tela, Tallas y Colores en un solo expandible
            tablaTelasHTML = ''; // No mostrar tabla telas por separado
            tallasYCantidadesHTML = this._construirSeccionCombinada(prendaParaRender, indice);
        } else {
            // Flujo normal: tabla telas + tallas separadas
            tablaTelasHTML = this._construirTablaTelas(prendaParaRender, indice);
            tallasYCantidadesHTML = this._construirTallasYCantidades(prendaParaRender, indice);
        }

        // Calcular número de item global (considerando prendas y EPPs)
        let numeroItem = indice + 1;
        if (window.gestionItemsUI && window.gestionItemsUI.ordenItems) {
            // Contar cuántas prendas hay antes de esta
            let prendaCount = 0;
            for (let i = 0; i < window.gestionItemsUI.ordenItems.length; i++) {
                const item = window.gestionItemsUI.ordenItems[i];
                if (item.tipo === 'prenda' && item.index < indice) {
                    prendaCount++;
                }
            }
            numeroItem = prendaCount + 1;
        }

        const html = `
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
                                    onload="console.log(' Imagen de prenda cargada:', '${fotoPrincipal}')"
                                    onerror="console.error(' Error al cargar imagen de prenda:', '${fotoPrincipal}')"
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


        return html;
    },

    _construirVariaciones(prenda, indice) {

        const variantes = prenda.variantes || {};

        
        const variacionesMapeo = [
            { label: 'Manga', valKey: 'tipo_manga', obsKey: 'obs_manga' },
            { label: 'Bolsillos', valKey: 'tiene_bolsillos', obsKey: 'obs_bolsillos' },
            { label: 'Broche/Botón', valKey: 'tipo_broche', obsKey: 'obs_broche' },
            { label: 'Reflectivo', valKey: 'tiene_reflectivo', obsKey: 'obs_reflectivo' }
        ];
        
        const variacionesAplicadas = variacionesMapeo.filter(({ valKey, obsKey }) => {
            const valor = variantes[valKey];
            return valor && valor !== 'No aplica' && valor !== false;
        });
        

        
        if (variacionesAplicadas.length === 0) {

            return '';
        }

        let tablasFilasHTML = '';
        variacionesAplicadas.forEach(({ label, valKey, obsKey }) => {
            const valor = variantes[valKey];
            const observaciones = variantes[obsKey] || '';
            

            
            if (label === 'Bolsillos') {



            }
            
            // Detectar si debe mostrar "-" en especificación
            // Para campos booleanos (tiene_bolsillos, tiene_reflectivo) nunca mostrar valor numérico
            const esBooleano = typeof valor === 'boolean' || valKey.startsWith('tiene_');
            
            tablasFilasHTML += `
                <tr>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: center;">
                        <i class="fas fa-check" style="color: #10b981; font-weight: bold;"></i>
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #0369a1; font-weight: 500;">
                        ${label}
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #374151;">
                        ${esBooleano ? '-' : valor}
                    </td>
                    <td style="padding: 0.75rem; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 0.9rem;">
                        ${observaciones || '-'}
                    </td>
                </tr>
            `;
        });

        return `
            <div class="seccion-expandible variaciones-section">
                <button class="seccion-expandible-header" type="button" data-section="variaciones" data-prenda-index="${indice}">
                    <h4>Variaciones <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="variaciones-count">${variacionesAplicadas.length}</span>)</span></h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content variaciones-content">
                    <table style="width: 100%; border-collapse: collapse; margin: 0;">
                        <thead>
                            <tr style="background: #0ea5e9; color: white;">
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; font-size: 0.85rem;">APLICA</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">VARIACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">ESPECIFICACIÓN</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; font-size: 0.85rem;">OBSERVACIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tablasFilasHTML}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    _construirTallasYCantidades(prenda, indice) {

        
        let tallas = prenda.tallas;
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla; // { DAMA: { S: 20, M: 20 } }
        
        // Intentar obtener cantidades desde cantidad_talla (nuevo formato relacional)
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};
        
        //  Si viene formato relacional, convertirlo
        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            // Convertir { DAMA: { S: 20, M: 20 } } → { 'dama-S': 20, 'dama-M': 20 }
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        cantidadesPorTalla[`${genero.toLowerCase()}-${talla}`] = cantidad;
                    });
                }
            });
            
            // Construir generosConTallas si no existe
            if (!generosConTallas || Object.keys(generosConTallas).length === 0) {
                generosConTallas = {};
                Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                        generosConTallas[genero.toLowerCase()] = {
                            tallas: Object.keys(tallasObj)
                        };
                    }
                });
            }
        }

        
        let tallasByGeneroMap = {};
        let cantidadesPorGenero = {};
        let totalTallas = 0;
        
        const generoKeys = Object.keys(generosConTallas || {});
        if (generoKeys.length > 0) {
            Object.entries(generosConTallas).forEach(([genero, data]) => {
                if (data && data.tallas && Array.isArray(data.tallas) && data.tallas.length > 0) {
                    tallasByGeneroMap[genero] = data.tallas;
                    totalTallas += data.tallas.length;
                }
            });
        } else if (Array.isArray(tallas) && tallas.length > 0) {
            tallas.forEach(t => {
                const genero = t.genero || 'general';
                const tallasList = t.tallas || [];
                if (tallasList.length > 0) {
                    tallasByGeneroMap[genero] = tallasList;
                    totalTallas += tallasList.length;
                }
            });
        }
        
        // Si no hay generosConTallas pero sí hay cantidades, extraer géneros de las cantidades
        if (totalTallas === 0 && Object.keys(cantidadesPorTalla).length > 0) {

            const generosMap = {};
            Object.keys(cantidadesPorTalla).forEach(clave => {
                const [genero, talla] = clave.split('-');
                if (genero && talla) {
                    if (!generosMap[genero]) {
                        generosMap[genero] = [];
                    }
                    if (!generosMap[genero].includes(talla)) {
                        generosMap[genero].push(talla);
                    }
                }
            });
            tallasByGeneroMap = generosMap;
            totalTallas = Object.values(generosMap).reduce((sum, tallas) => sum + tallas.length, 0);
        }
        
        Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
            const [genero, talla] = clave.split('-');
            if (genero && talla) {
                if (!cantidadesPorGenero[genero]) {
                    cantidadesPorGenero[genero] = {};
                }
                cantidadesPorGenero[genero][talla] = cantidad;
            }
        });
        
        const totalCantidades = Object.keys(cantidadesPorTalla).length;

        
        if (totalTallas === 0) {

            return '';
        }

        let generoHTML = '';
        
        // ── Detectar si SOLO hay GENERICO (SOLO CANTIDAD) ──
        const tieneGenerico = Object.keys(tallasByGeneroMap).some(g => g.toUpperCase() === 'GENERICO');
        const soloGenerico = tieneGenerico && Object.keys(tallasByGeneroMap).length === 1;
        
        // Si SOLO hay GENERICO, mostrar la cantidad de forma simple
        if (soloGenerico) {
            const cantidadKey = Object.keys(tallasByGeneroMap).find(g => g.toUpperCase() === 'GENERICO');
            const cantidadSolo = cantidadesPorGenero[cantidadKey]?.['SIN_ESPECIFICAR'] || 
                                 cantidadesPorGenero[cantidadKey]?.['sin_especificar'] ||
                                 Object.values(cantidadesPorGenero[cantidadKey] || {})[0] || 0;
            
            generoHTML = `
                <div style="display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f0f9ff; border-radius: 8px; border: 2px solid #0ea5e9;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; color: #475569; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                            Cantidad
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #0369a1; line-height: 1;">
                            ${cantidadSolo}
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Flujo normal: iterar sobre géneros pero saltar GENERICO
            Object.keys(tallasByGeneroMap).forEach((genero, idx) => {
                //  NUEVO: NO RENDERIZAR GÉNERO "GENERICO" (SOLO CANTIDAD)
                if (genero.toUpperCase() === 'GENERICO') return;
                
                const tallasList = tallasByGeneroMap[genero] || [];
                const cantidadesGen = cantidadesPorGenero[genero] || {};
                
                if (tallasList.length === 0) return;
                
                // Obtener asignaciones de colores
                const asignacionesColores = this._resolverAsignacionesColoresPorTalla(prenda);
                
                const tallasConCantidad = tallasList.map(talla => {
                    const cantidad = cantidadesGen[talla] || 0;
                    
                    // Buscar colores asignados para esta talla-género
                    let coloresHTML = '';
                    const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, talla);
                    if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
                        const imagenesAsignacion = this._normalizarImagenes(asignacion.imagenes)
                            .map((img) => this._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColores = asignacion.colores.length;
                        const coloresItems = asignacion.colores.map(c => {
                            const nombre = c.nombre || c.color || 'Sin nombre';
                            const cant = c.cantidad || 0;
                            return `<div style="font-size: 0.65rem; color: #475569; padding: 0.15rem 0.4rem; background: rgba(255,255,255,0.8); border-radius: 3px; display: flex; align-items: center; gap: 0.25rem;">
                                <span style="display: inline-block; width: 6px; height: 6px; background: #0ea5e9; border-radius: 50%;"></span>
                                <span>${nombre}</span>
                                <span style="color: #6b7280; font-weight: 600;">×${cant}</span>
                            </div>`;
                        }).join('');
                        coloresHTML = `<div style="margin-top: 0.4rem; border-top: 1px solid rgba(203,213,225,0.4); padding-top: 0.3rem;">${coloresItems}</div>`;

                        const imagenesAsignacionDet = this._normalizarImagenes(asignacion.imagenes)
                            .map((img) => this._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColoresDet = asignacion.colores.length;
                        const coloresDetallados = asignacion.colores.map((color) => {
                            const nombreColor = color.nombre || color.color || 'Sin nombre';
                            const cantidadColor = parseInt(color.cantidad, 10) || 0;
                            const referenciaColor = color.referencia || asignacion.referencia || '';
                            const imagenColor = this._obtenerImagenColorAsignacion(color, imagenesAsignacionDet[0] || null);
                            const mostrarCantidad = totalColoresDet > 1 || cantidadColor !== cantidad;

                            return `
                                <div style="display: flex; align-items: center; gap: 0.55rem; padding: 0.45rem 0.5rem; background: rgba(255,255,255,0.92); border: 1px solid rgba(125,211,252,0.45); border-radius: 8px;">
                                    ${imagenColor ? `
                                        <img src="${imagenColor}" alt="${this._escapeHtml(nombreColor)}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 7px; border: 1px solid rgba(14,165,233,0.25); flex-shrink: 0;" />
                                    ` : `
                                        <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 7px; background: #e0f2fe; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <span style="display: inline-block; width: 10px; height: 10px; background: #0ea5e9; border-radius: 999px;"></span>
                                        </span>
                                    `}
                                    <div style="min-width: 0; display: flex; flex-direction: column; gap: 0.1rem;">
                                        <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${this._escapeHtml(nombreColor)}</span>
                                        ${referenciaColor ? `<span style="font-size: 0.64rem; color: #64748b; line-height: 1.1;">Ref: ${this._escapeHtml(referenciaColor)}</span>` : ''}
                                    </div>
                                    ${mostrarCantidad ? `<span style="margin-left: auto; background: #e0f2fe; color: #0369a1; min-width: 28px; text-align: center; padding: 0.18rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">${cantidadColor}</span>` : ''}
                                </div>
                            `;
                        }).join('');

                        coloresHTML = `
                            <div style="margin-top: 0.55rem; border-top: 1px solid rgba(125,211,252,0.35); padding-top: 0.45rem; display: grid; gap: 0.4rem;">
                                ${coloresDetallados}
                            </div>
                        `;
                    }
                    
                    return `
                        <div style="background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%); padding: 0.7rem 0.8rem; border-radius: 10px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 220px; flex: 1 1 220px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.45);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                                    <span style="display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255,255,255,0.8); color: #0284c7; flex-shrink: 0;">
                                        <i class="fas fa-ruler" style="font-size: 0.8rem;"></i>
                                    </span>
                                    <span style="font-size: 0.92rem; font-weight: 800; color: #075985; letter-spacing: 0.02em;">${this._escapeHtml(talla)}</span>
                                </div>
                                <span style="background: #0369a1; color: white; padding: 0.24rem 0.65rem; border-radius: 999px; font-size: 0.82rem; font-weight: 800; line-height: 1;">${cantidad}</span>
                            </div>
                            ${asignacion && asignacion.colores && asignacion.colores.length > 0 ? `
                            <div style="margin-top: 0.35rem; font-size: 0.68rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                                Colores asignados
                            </div>
                            ${coloresHTML}
                            ` : ''}
                        </div>
                    `;
                }).join('');
                
                generoHTML += `
                    <div style="margin-bottom: 1rem;">
                        <div style="font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-users" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                            ${genero}
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                            ${tallasConCantidad}
                        </div>
                    </div>
                `;
            });
        }
        
        return `
            <div class="seccion-expandible tallas-y-cantidades-section">
                <button class="seccion-expandible-header" type="button" data-section="tallas-y-cantidades" data-prenda-index="${indice}">
                    <h4 style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-th" style="color: #0ea5e9;"></i>
                        Tallas & Cantidades
                        <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280; font-weight: 500;">(<span class="tallas-cantidades-count">${totalTallas}</span>)</span>
                    </h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content tallas-y-cantidades-content">
                    <div style="padding: 1.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; border-left: 4px solid #0ea5e9;">
                        ${generoHTML}
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Buscar asignación de color para un género y talla específicos
     * Soporta claves: "genero-tipo-talla" (ej: "dama-Letra-M") o "genero-talla" (ej: "dama-M")
     */
    _buscarAsignacionColor(asignacionesColores, genero, talla) {
        if (!asignacionesColores || Object.keys(asignacionesColores).length === 0) {
            return null;
        }
        
        // Método 1: Buscar por objeto con genero y talla 
        const clavePorObjeto = Object.keys(asignacionesColores).find(clave => {
            const asig = asignacionesColores[clave];
            return asig && asig.genero && asig.genero.toLowerCase() === genero.toLowerCase() && asig.talla === talla;
        });
        if (clavePorObjeto) return asignacionesColores[clavePorObjeto];
        
        // Método 2: Buscar por clave "genero-...-talla" (última parte es la talla)
        const clavePorFormato = Object.keys(asignacionesColores).find(clave => {
            const partes = clave.split('-');
            if (partes.length >= 2) {
                return partes[0].toLowerCase() === genero.toLowerCase() && partes[partes.length - 1] === talla;
            }
            return false;
        });
        if (clavePorFormato) {
            const valor = asignacionesColores[clavePorFormato];
            return valor.genero ? valor : { genero, talla, colores: Array.isArray(valor) ? valor : [valor] };
        }
        
        return null;
    },

    _obtenerFotoTelaDesdeRelacion(prenda, telaItem) {
        if (!Array.isArray(prenda?.colores_telas) || prenda.colores_telas.length === 0 || !window.ImageConverterService) {
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

        return window.ImageConverterService.obtenerPrimeraImagen(fotosTela);
    },

    /**
     * Sección combinada: Tela + Tallas + Colores en un solo expandible
     * Se usa cuando hay asignaciones de colores (flujo wizard)
     */
    _construirSeccionCombinada(prenda, indice) {
        // ── Obtener telas ──
        let telas = [];
        if (prenda.telasAgregadas) {
            telas = this._normalizarTelas(prenda.telasAgregadas);
        } else if (prenda.telas) {
            telas = this._normalizarTelas(prenda.telas);
        }
        const telasParaVista = this._deduplicarTelasParaVista(telas);

        // ── Obtener tallas (misma lógica de _construirTallasYCantidades) ──
        let generosConTallas = prenda.generosConTallas;
        let cantidadTallaRelacional = prenda.cantidad_talla;
        let cantidadesPorTalla = prenda.cantidadesPorTalla || {};

        if (cantidadTallaRelacional && typeof cantidadTallaRelacional === 'object' && !Array.isArray(cantidadTallaRelacional)) {
            Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                if (typeof tallasObj === 'object') {
                    Object.entries(tallasObj).forEach(([talla, cantidad]) => {
                        cantidadesPorTalla[`${genero.toLowerCase()}-${talla}`] = cantidad;
                    });
                }
            });
            if (!generosConTallas || Object.keys(generosConTallas).length === 0) {
                generosConTallas = {};
                Object.entries(cantidadTallaRelacional).forEach(([genero, tallasObj]) => {
                    if (typeof tallasObj === 'object' && Object.keys(tallasObj).length > 0) {
                        generosConTallas[genero.toLowerCase()] = { tallas: Object.keys(tallasObj) };
                    }
                });
            }
        }

        let tallasByGeneroMap = {};
        let cantidadesPorGenero = {};
        let totalTallas = 0;

        const generoKeys = Object.keys(generosConTallas || {});
        if (generoKeys.length > 0) {
            Object.entries(generosConTallas).forEach(([genero, data]) => {
                if (data && data.tallas && Array.isArray(data.tallas) && data.tallas.length > 0) {
                    tallasByGeneroMap[genero] = data.tallas;
                    totalTallas += data.tallas.length;
                }
            });
        }

        if (totalTallas === 0 && Object.keys(cantidadesPorTalla).length > 0) {
            const generosMap = {};
            Object.keys(cantidadesPorTalla).forEach(clave => {
                const [genero, talla] = clave.split('-');
                if (genero && talla) {
                    if (!generosMap[genero]) generosMap[genero] = [];
                    if (!generosMap[genero].includes(talla)) generosMap[genero].push(talla);
                }
            });
            tallasByGeneroMap = generosMap;
            totalTallas = Object.values(generosMap).reduce((s, t) => s + t.length, 0);
        }

        Object.entries(cantidadesPorTalla).forEach(([clave, cantidad]) => {
            const [genero, talla] = clave.split('-');
            if (genero && talla) {
                if (!cantidadesPorGenero[genero]) cantidadesPorGenero[genero] = {};
                cantidadesPorGenero[genero][talla] = cantidad;
            }
        });

        if (totalTallas === 0 && telasParaVista.length === 0) return '';

        const asignacionesColores = this._resolverAsignacionesColoresPorTalla(prenda);

        // ── Construir HTML de telas (mini-badges en vez de tabla) ──
        let telasInfoHTML = '';
        if (telasParaVista.length > 0) {
            const telasBadges = telasParaVista.map((t, idx) => {
                const nombre = t.tela || t.nombre_tela || 'N/A';
                const telaFoto = this._obtenerFotoTelaDesdeRelacion(prenda, t);

                const referencias = [t.referencia].filter(Boolean);
                const colores = Array.isArray(t._displayColores) ? t._displayColores.filter(Boolean) : [];
                const partesDetalle = [];
                if (colores.length > 0) {
                    partesDetalle.push(`<span style="color: #64748b;">Colores: <b>${this._escapeHtml(colores.join(', '))}</b></span>`);
                }
                let detalles = partesDetalle.join(' · ');
                const ref = referencias.length > 0 ? this._escapeHtml(referencias.join(', ')) : '';
                const obs = t._displayCount > 1 ? `Registros: ${t._displayCount}` : '';
                if (ref && ref !== 'N/A' && ref !== '') detalles += `${detalles ? ' · ' : ''}<span style="color: #64748b;">Ref: <b>${ref}</b></span>`;
                if (obs) detalles += `${detalles ? ' · ' : ''}<span style="color: #64748b;">Obs: <b>${obs}</b></span>`;

                return `
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.85rem; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;">
                        ${telaFoto ? `
                            <img src="${telaFoto}" alt="${nombre}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 5px; border: 1px solid #e0e7ff; flex-shrink: 0;" />
                        ` : `
                            <div style="width: 36px; height: 36px; background: #e0f2fe; border-radius: 5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-scroll" style="color: #0284c7; font-size: 0.85rem;"></i>
                            </div>
                        `}
                        <div>
                            <div style="font-weight: 700; color: #0369a1; font-size: 0.9rem;">${nombre}</div>
                            ${detalles ? `<div style="font-size: 0.73rem; margin-top: 0.15rem; color: #64748b;">${detalles}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            telasInfoHTML = `
                <div style="margin-bottom: 1rem;">
                    <div style="font-weight: 600; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fas fa-scroll" style="color: #0ea5e9; font-size: 0.75rem;"></i> Tela${telasParaVista.length > 1 ? 's' : ''}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        ${telasBadges}
                    </div>
                </div>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0;">
            `;
        }

        // ── Detectar si SOLO hay GENERICO (SOLO CANTIDAD) ──
        const tieneGenerico = Object.keys(tallasByGeneroMap).some(g => g.toUpperCase() === 'GENERICO');
        const soloGenerico = tieneGenerico && Object.keys(tallasByGeneroMap).length === 1;
        
        let generoHTML = '';
        
        // Si SOLO hay GENERICO, mostrar la cantidad de forma simple
        if (soloGenerico) {
            const cantidadKey = Object.keys(tallasByGeneroMap).find(g => g.toUpperCase() === 'GENERICO');
            const cantidadSolo = cantidadesPorGenero[cantidadKey]?.['SIN_ESPECIFICAR'] || 
                                 cantidadesPorGenero[cantidadKey]?.['sin_especificar'] ||
                                 Object.values(cantidadesPorGenero[cantidadKey] || {})[0] || 0;
            
            generoHTML = `
                <div style="display: flex; align-items: center; justify-content: center; padding: 1rem; background: #f0f9ff; border-radius: 8px; border: 2px solid #0ea5e9;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; color: #475569; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                            Cantidad
                        </div>
                        <div style="font-size: 2.5rem; font-weight: 700; color: #0369a1; line-height: 1;">
                            ${cantidadSolo}
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Flujo normal: iterar sobre géneros pero saltar GENERICO
            Object.keys(tallasByGeneroMap).forEach(genero => {
                //  NUEVO: NO RENDERIZAR GÉNERO "GENERICO" (SOLO CANTIDAD)
                if (genero.toUpperCase() === 'GENERICO') return;
                
                const tallasList = tallasByGeneroMap[genero] || [];
                const cantidadesGen = cantidadesPorGenero[genero] || {};
                if (tallasList.length === 0) return;

                const tallasConCantidad = tallasList.map(talla => {
                    const cantidad = cantidadesGen[talla] || 0;
                    let coloresHTML = '';
                    const asignacion = this._buscarAsignacionColor(asignacionesColores, genero, talla);
                    if (asignacion && asignacion.colores && asignacion.colores.length > 0) {
                        const coloresItems = asignacion.colores.map(c => {
                            const nombre = c.nombre || c.color || 'Sin nombre';
                            const cant = c.cantidad || 0;
                            return `<div style="font-size: 0.65rem; color: #475569; padding: 0.15rem 0.4rem; background: rgba(255,255,255,0.8); border-radius: 3px; display: flex; align-items: center; gap: 0.25rem;">
                                <span style="display: inline-block; width: 6px; height: 6px; background: #0ea5e9; border-radius: 50%;"></span>
                                <span>${nombre}</span>
                                <span style="color: #6b7280; font-weight: 600;">×${cant}</span>
                            </div>`;
                        }).join('');
                        coloresHTML = `<div style="margin-top: 0.4rem; border-top: 1px solid rgba(203,213,225,0.4); padding-top: 0.3rem;">${coloresItems}</div>`;

                        const imagenesAsignacionDet = this._normalizarImagenes(asignacion.imagenes)
                            .map((img) => this._normalizarSrcImagen(img))
                            .filter(Boolean);
                        const totalColoresDet = asignacion.colores.length;
                        const coloresDetallados = asignacion.colores.map((color) => {
                            const nombreColor = color.nombre || color.color || 'Sin nombre';
                            const cantidadColor = parseInt(color.cantidad, 10) || 0;
                            const referenciaColor = color.referencia || asignacion.referencia || '';
                            const imagenColor = this._obtenerImagenColorAsignacion(color, imagenesAsignacionDet[0] || null);
                            const mostrarCantidad = totalColoresDet > 1 || cantidadColor !== cantidad;

                            return `
                                <div style="display: flex; align-items: center; gap: 0.55rem; padding: 0.45rem 0.5rem; background: rgba(255,255,255,0.92); border: 1px solid rgba(125,211,252,0.45); border-radius: 8px;">
                                    ${imagenColor ? `
                                        <img src="${imagenColor}" alt="${this._escapeHtml(nombreColor)}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 7px; border: 1px solid rgba(14,165,233,0.25); flex-shrink: 0;" />
                                    ` : `
                                        <span style="display: inline-flex; width: 38px; height: 38px; border-radius: 7px; background: #e0f2fe; align-items: center; justify-content: center; flex-shrink: 0;">
                                            <span style="display: inline-block; width: 10px; height: 10px; background: #0ea5e9; border-radius: 999px;"></span>
                                        </span>
                                    `}
                                    <div style="min-width: 0; display: flex; flex-direction: column; gap: 0.1rem;">
                                        <span style="font-size: 0.72rem; font-weight: 700; color: #0f172a; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${this._escapeHtml(nombreColor)}</span>
                                        ${referenciaColor ? `<span style="font-size: 0.64rem; color: #64748b; line-height: 1.1;">Ref: ${this._escapeHtml(referenciaColor)}</span>` : ''}
                                    </div>
                                    ${mostrarCantidad ? `<span style="margin-left: auto; background: #e0f2fe; color: #0369a1; min-width: 28px; text-align: center; padding: 0.18rem 0.45rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800;">${cantidadColor}</span>` : ''}
                                </div>
                            `;
                        }).join('');

                        coloresHTML = `
                            <div style="margin-top: 0.55rem; border-top: 1px solid rgba(125,211,252,0.35); padding-top: 0.45rem; display: grid; gap: 0.4rem;">
                                ${coloresDetallados}
                            </div>
                        `;
                    }

                    return `
                        <div style="background: linear-gradient(180deg, #dbeafe 0%, #eff6ff 100%); padding: 0.7rem 0.8rem; border-radius: 10px; font-weight: 600; color: #0369a1; border: 1px solid #7dd3fc; min-width: 220px; flex: 1 1 220px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.45);">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
                                    <span style="display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 999px; background: rgba(255,255,255,0.8); color: #0284c7; flex-shrink: 0;">
                                        <i class="fas fa-ruler" style="font-size: 0.8rem;"></i>
                                    </span>
                                    <span style="font-size: 0.92rem; font-weight: 800; color: #075985; letter-spacing: 0.02em;">${this._escapeHtml(talla)}</span>
                                </div>
                                <span style="background: #0369a1; color: white; padding: 0.24rem 0.65rem; border-radius: 999px; font-size: 0.82rem; font-weight: 800; line-height: 1;">${cantidad}</span>
                            </div>
                            ${asignacion && asignacion.colores && asignacion.colores.length > 0 ? `
                            <div style="margin-top: 0.35rem; font-size: 0.68rem; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                                Colores asignados
                            </div>
                            ${coloresHTML}
                            ` : ''}
                        </div>
                    `;
                }).join('');

                generoHTML += `
                    <div style="margin-bottom: 0.75rem;">
                        <div style="font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-users" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                            ${genero}
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                            ${tallasConCantidad}
                        </div>
                    </div>
                `;
            });
        }

        // ── Sección expandible combinada ──
        return `
            <div class="seccion-expandible tallas-y-cantidades-section">
                <button class="seccion-expandible-header" type="button" data-section="tallas-y-cantidades" data-prenda-index="${indice}">
                    <h4 style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-layer-group" style="color: #0ea5e9;"></i>
                        Tela, Tallas & Colores
                        <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280; font-weight: 500;">(<span class="tallas-cantidades-count">${totalTallas}</span>)</span>
                    </h4>
                    <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                </button>
                <div class="seccion-expandible-content tallas-y-cantidades-content">
                    <div style="padding: 1.25rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; border-left: 4px solid #0ea5e9;">
                        ${telasInfoHTML}
                        ${generoHTML}
                    </div>
                </div>
            </div>
        `;
    },

    _construirProcesos(prenda, indice) {

        const procesos = this._normalizarProcesos(prenda.procesos || {});

        
        const procesosConDatos = Object.entries(procesos).filter(([_, proc]) => proc && (proc.datos !== null || proc.tipo));

        
        if (procesosConDatos.length === 0) {

            return '';
        }

        const iconosProcesos = {
            'reflectivo': '<i class="fas fa-lightbulb" style="color: #f59e0b;"></i>',
            'bordado': '<i class="fas fa-gem" style="color: #1e40af;"></i>',
            'estampado': '<i class="fas fa-paint-brush" style="color: #ec4899;"></i>',
            'dtf': '<i class="fas fa-print" style="color: #06b6d4;"></i>',
            'sublimado': '<i class="fas fa-tint" style="color: #3b82f6;"></i>'
        };

        let procesosItemsHTML = '';
        procesosConDatos.forEach(([tipoProceso, proceso]) => {
            const datos = proceso.datos || {};
            const icono = iconosProcesos[tipoProceso] || '<i class="fas fa-cog"></i>';
            const nombreProceso = datos.nombre || tipoProceso.charAt(0).toUpperCase() + tipoProceso.slice(1);
            const modoTallasResuelto = datos.modoTallas || proceso.modoTallas || 'generico';
            const esGeneralMode = modoTallasResuelto === 'general' || modoTallasResuelto === 'generico';
            const esPorTallas = !esGeneralMode && !!(datos.datosExtendidos);

            // ─── Modo POR TALLAS: renderizar sub-tarjetas por talla ───
            if (esPorTallas && datos.datosExtendidos) {
                let porTallasHTML = '';
                const generos = { dama: { label: 'DAMA', icon: '<i class="fas fa-female"></i>', color: '#be185d', bg: '#fdf2f8', border: '#fbcfe8' }, caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male"></i>', color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe' } };

                // Detectar modo general
                const esGeneral = modoTallasResuelto === 'general' || modoTallasResuelto === 'generico';
                let ubicacionGeneralHTML = '';
                let fotosGeneralesHTML = '';

                // Si es general, renderizar ubicación general y fotos generales al inicio
                if (esGeneral) {
                    if (datos.ubicacionGeneral) {
                        ubicacionGeneralHTML = `
                            <div style="background: #f3f4f6; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; border: 1px solid #d1d5db;">
                                <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                                    <i class="fas fa-map-marker-alt"></i>UBICACIÓN GENERAL
                                </strong>
                                <span style="color: #6b7280; font-size: 0.8rem;">${datos.ubicacionGeneral}</span>
                            </div>
                        `;
                    }

                    //  PRIORIDAD: Combinar fotosGenerales (URLs) con fotosGeneralesFiles (Files temporales)
                    let fotosDisplay = [];
                    
                    // 1. Agregar fotos existentes del servidor
                    if (datos.fotosGenerales && Array.isArray(datos.fotosGenerales) && datos.fotosGenerales.length > 0) {
                        fotosDisplay = [...datos.fotosGenerales];
                    }
                    
                    // 2. Agregar Files nuevos (tienen prioridad para renderizado)
                    if (datos.fotosGeneralesFiles && Array.isArray(datos.fotosGeneralesFiles) && datos.fotosGeneralesFiles.length > 0) {
                        console.log(`[PrendaCardService]  Agregando fotosGeneralesFiles (${datos.fotosGeneralesFiles.length}) a fotosDisplay`);
                        fotosDisplay = [...fotosDisplay, ...datos.fotosGeneralesFiles];
                    } else if (datos.imagenesFiles && Array.isArray(datos.imagenesFiles) && datos.imagenesFiles.length > 0) {
                        console.log(`[PrendaCardService]  Agregando imagenesFiles (${datos.imagenesFiles.length}) a fotosDisplay`);
                        fotosDisplay = [...fotosDisplay, ...datos.imagenesFiles];
                    }
                    
                    if (fotosDisplay.length > 0) {
                        const fotosThumb = fotosDisplay.map((src, idx) => {
                            let imgSrc = src;
                            if (src instanceof File) {
                                imgSrc = URL.createObjectURL(src);
                            } else if (typeof src === 'string') {
                                imgSrc = src.startsWith('blob:') ? src : src;
                            }
                            return `<img src="${imgSrc}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db;">`;
                        }).join('');
                        
                        fotosGeneralesHTML = `
                            <div style="margin-bottom: 0.75rem;">
                                <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                                    <i class="fas fa-images"></i>FOTOS GENERALES (${fotosDisplay.length})
                                </strong>
                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                    ${fotosThumb}
                                </div>
                            </div>
                        `;
                    }
                }

                if (!esGeneral && !fotosGeneralesHTML) {
                    const imagenesProceso = Array.isArray(datos.imagenes) ? datos.imagenes : [];
                    if (imagenesProceso.length > 0) {
                        const fotosThumb = imagenesProceso.map((src) => {
                            let imgSrc = src;
                            if (src instanceof File) {
                                imgSrc = URL.createObjectURL(src);
                            } else if (src && typeof src === 'object') {
                                imgSrc = src.url || src.ruta_webp || src.ruta_original || src.path || src.previewUrl || src.blobUrl || '';
                            }
                            return imgSrc
                                ? `<img src="${imgSrc}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db;">`
                                : '';
                        }).filter(Boolean).join('');

                        if (fotosThumb) {
                            fotosGeneralesHTML = `
                                <div style="margin-bottom: 0.75rem;">
                                    <strong style="font-size: 0.8rem; color: #333; display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem;">
                                        <i class="fas fa-images"></i>IMAGENES DEL PROCESO (${imagenesProceso.length})
                                    </strong>
                                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                        ${fotosThumb}
                                    </div>
                                </div>
                            `;
                        }
                    }
                }

                Object.entries(generos).forEach(([genero, cfg]) => {
                    const tallasGenero = datos.datosExtendidos[genero];
                    if (!tallasGenero || Object.keys(tallasGenero).length === 0) return;

                    Object.entries(tallasGenero).forEach(([tallaKey, datosTalla]) => {
                        const parts = String(tallaKey).split('__');
                        const tallaDisplay = parts[0] || tallaKey;
                        const cantidad = datos.tallas?.[genero]?.[tallaKey] ?? '';

                        // Ubicaciones - NO mostrar si es modo general
                        let ubicHTML = '';
                        if (!esGeneral && datosTalla.ubicaciones && datosTalla.ubicaciones.length > 0) {
                            const chips = datosTalla.ubicaciones.map(u => {
                                const texto = typeof u === 'string' ? u : (u?.ubicacion || '');
                                return texto ? `<span style="background: ${cfg.bg}; color: ${cfg.color}; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; display: inline-block;">${texto}</span>` : '';
                            }).filter(Boolean).join('');
                            if (chips) {
                                ubicHTML = `<div style="margin-top: 0.35rem;"><span style="font-size: 0.75rem; color: #6b7280; font-weight: 600;"><i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>Ubicaciones:</span><div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.2rem;">${chips}</div></div>`;
                            }
                        }

                        // Observaciones
                        let obsHTML = '';
                        if (datosTalla.observaciones) {
                            obsHTML = `<div style="margin-top: 0.35rem; font-size: 0.8rem; color: #374151; background: #f9fafb; padding: 0.35rem 0.5rem; border-radius: 4px; border-left: 2px solid ${cfg.color};"><i class="fas fa-sticky-note" style="margin-right: 0.25rem; color: ${cfg.color};"></i>${datosTalla.observaciones}</div>`;
                        }

                        // Imágenes - NO mostrar si es modo general
                        let imgHTML = '';
                        if (!esGeneral) {
                            const imgs = datosTalla.imagenes || [];
                            if (imgs.length > 0) {
                                const thumbs = imgs.map(img => {
                                    let src = '';
                                    if (typeof img === 'string') src = img;
                                    else if (img instanceof File) src = URL.createObjectURL(img);
                                    else if (img?.url || img?.previewUrl || img?.blobUrl) src = img.url || img.previewUrl || img.blobUrl;
                                    return src ? `<img src="${src}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid ${cfg.border};">` : '';
                                }).filter(Boolean).join('');
                                if (thumbs) {
                                    imgHTML = `<div style="display: flex; gap: 0.3rem; margin-top: 0.35rem;">${thumbs}</div>`;
                                }
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

                procesosItemsHTML += `
                    <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                            <span style="font-size: 1.5rem;">${icono}</span>
                            <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                            <span style="background: #7c3aed; color: white; padding: 0.15rem 0.5rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700;">Por Tallas</span>
                        </div>
                        ${ubicacionGeneralHTML}
                        ${fotosGeneralesHTML}
                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                            ${porTallasHTML}
                        </div>
                    </div>
                `;
                return; // skip normal rendering for this proceso
            }
            
            // ─── Modo GENERAL (renderizado especial) ───
            if (esGeneralMode) {
                // Ubicación General
                const ubicacionesNormalizadas = this._normalizarUbicaciones(datos.ubicaciones);
                const ubicacionGeneral = datos.ubicacionGeneral || ubicacionesNormalizadas.join(', ') || '';
                
                let ubicacionGeneralHTML = '';
                if (ubicacionGeneral) {
                    ubicacionGeneralHTML = `
                        <div style="margin-bottom: 0.75rem;">
                            <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                                <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                            </strong>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                                    <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${ubicacionGeneral}
                                </span>
                            </div>
                        </div>
                    `;
                }

                // Tallas agrupadas por género
                let tallasGeneralesHTML = '';
                if (datos.tallas) {
                    const damaObj = this._obtenerObjetoGenero(datos.tallas, 'dama');
                    const caballeroObj = this._obtenerObjetoGenero(datos.tallas, 'caballero');
                    const sobremedidaObj = this._obtenerObjetoGenero(datos.tallas, 'sobremedida');
                    const damaHasTallas = Object.keys(damaObj).length > 0;
                    const caballeroHasTallas = Object.keys(caballeroObj).length > 0;
                    const sobremedidaHasTallas = Object.keys(sobremedidaObj).length > 0;
                    
                    if (damaHasTallas || caballeroHasTallas || sobremedidaHasTallas) {
                        tallasGeneralesHTML = '<div style="margin-top: 0.75rem;">';
                        
                        const generosConfig = {
                            dama: { label: 'DAMA', icon: '<i class="fas fa-female" style="color: #be185d;"></i>', color: '#be185d' },
                            caballero: { label: 'CABALLERO', icon: '<i class="fas fa-male" style="color: #1d4ed8;"></i>', color: '#1d4ed8' },
                            sobremedida: { label: 'SOBREMEDIDA', icon: '<i class="fas fa-ruler" style="color: #92400e;"></i>', color: '#92400e' }
                        };
                        
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
                    }
                }

                // Observaciones generales
                let observacionesGeneralesHTML = '';
                if (datos.observaciones) {
                    observacionesGeneralesHTML = `
                        <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                            <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">
                                <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                            </strong>
                            <span style="color: #78350f; font-size: 0.9rem;">${datos.observaciones}</span>
                        </div>
                    `;
                }

                // Fotos Generales - Combinar URLs con Files temporales
                let fotosGenerales = [];
                
                // 1. Agregar fotos existentes del servidor (URLs)
                if (datos.fotosGenerales && Array.isArray(datos.fotosGenerales) && datos.fotosGenerales.length > 0) {
                    fotosGenerales = [...datos.fotosGenerales];
                }
                
                // 2. Agregar Files nuevos (tienen prioridad para renderizado)
                if (datos.fotosGeneralesFiles && Array.isArray(datos.fotosGeneralesFiles) && datos.fotosGeneralesFiles.length > 0) {
                    console.log(`[PrendaCardService-ReadonlyProceso]  Agregando fotosGeneralesFiles (${datos.fotosGeneralesFiles.length}) a fotosDisplay`);
                    fotosGenerales = [...fotosGenerales, ...datos.fotosGeneralesFiles];
                } else if (datos.imagenesFiles && Array.isArray(datos.imagenesFiles) && datos.imagenesFiles.length > 0) {
                    console.log(`[PrendaCardService-ReadonlyProceso]  Agregando imagenesFiles (${datos.imagenesFiles.length}) a fotosDisplay`);
                    fotosGenerales = [...fotosGenerales, ...datos.imagenesFiles];
                } else if (datos.imagenes && Array.isArray(datos.imagenes) && datos.imagenes.length > 0 && fotosGenerales.length === 0) {
                    fotosGenerales = [...datos.imagenes];
                }
                
                let fotosGeneralesHTML = '';
                if (fotosGenerales && fotosGenerales.length > 0) {
                    const fotosHTML = fotosGenerales.map((img, idx) => {
                        let src = '';
                        if (img instanceof File) {
                            src = URL.createObjectURL(img);
                            console.log(`[PrendaCardService-ReadonlyProceso]  File ${idx} convertido a blob: ${src.substring(0, 50)}...`);
                        } else if (typeof img === 'object' && img !== null) {
                            src = img.previewUrl || img.dataURL || img.src || img.url || img.blobUrl || img.ruta_webp || img.ruta_original || img.ruta || '';
                        } else if (typeof img === 'string') {
                            src = img.startsWith('blob:') || img.startsWith('http') || img.startsWith('/') ? img : '/storage/' + img;
                        }
                        
                        return src ? `<img src="${src}" alt="Imagen ${nombreProceso}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;" onclick="window.mostrarImagenProcesoGrande('${src}')">` : '';
                    }).filter(Boolean).join('');
                    
                    if (fotosHTML) {
                        fotosGeneralesHTML = `
                            <div style="margin-top: 0.75rem;">
                                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes (${fotosGenerales.length}):
                                </strong>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    ${fotosHTML}
                                </div>
                            </div>
                        `;
                    }
                }

                // Observaciones por talla (si existen)
                const datosExtendidos = datos.datosExtendidos || {};
                let observacionesPorTallaHTML = '';
                
                const tieneObservacionesPorTalla = Object.keys(datosExtendidos).some(genero => 
                    datosExtendidos[genero] && Object.values(datosExtendidos[genero]).some(d => d.observaciones)
                );
                
                if (tieneObservacionesPorTalla) {
                    const generosConfigObs = {
                        dama: { label: 'DAMA', color: '#be185d' },
                        caballero: { label: 'CABALLERO', color: '#1d4ed8' },
                        sobremedida: { label: 'SOBREMEDIDA', color: '#92400e' }
                    };
                    
                    let tarjetasObs = '';
                    Object.entries(generosConfigObs).forEach(([genero, cfg]) => {
                        const extendidosGenero = datosExtendidos[genero] || {};
                        const tallasGenero = datos.tallas?.[genero] || {};
                        
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
                    
                    if (tarjetasObs) {
                        observacionesPorTallaHTML = `
                            <div style="margin-top: 0.75rem;">
                                <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-sticky-note" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Observaciones por Talla:
                                </strong>
                                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                                    ${tarjetasObs}
                                </div>
                            </div>
                        `;
                    }
                }

                procesosItemsHTML += `
                    <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                            <span style="font-size: 1.5rem;">${icono}</span>
                            <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                        </div>
                        
                        ${ubicacionGeneralHTML}
                        ${tallasGeneralesHTML}
                        ${observacionesGeneralesHTML}
                        ${fotosGeneralesHTML}
                        ${observacionesPorTallaHTML}
                    </div>
                `;
                return; // skip normal rendering for this proceso
            }
            
            // ─── Modo GENÉRICO (compatibilidad para formatos antiguos) ───
            let ubicacionesHTML = '';
            if (datos.ubicaciones && datos.ubicaciones.length > 0) {
                ubicacionesHTML = datos.ubicaciones
                    .map(ubi => {
                        // Extraer texto según el tipo de dato
                        const texto = typeof ubi === 'object' && ubi !== null && ubi.ubicacion 
                            ? ubi.ubicacion 
                            : (typeof ubi === 'string' ? ubi : '');
                        
                        // Si no hay texto válido, no renderizar
                        if (!texto) return '';
                        
                        return `<span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin: 0.25rem;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i>${texto}
                        </span>`;
                    })
                    .filter(html => html) // Eliminar spans vacíos
                    .join('');
            }
            
            let tallasHTML = '';
            if (datos.tallas) {
                const damaObj = datos.tallas.dama || {};
                const caballeroObj = datos.tallas.caballero || {};
                const damaHasTallas = Object.keys(damaObj).length > 0;
                const caballeroHasTallas = Object.keys(caballeroObj).length > 0;
                
                if (damaHasTallas || caballeroHasTallas) {
                    const parseTallas = (obj) => {
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
                                const existente = agrupado[talla].colores.find(c => c.color === color);
                                if (existente) {
                                    existente.cantidad += qty;
                                } else {
                                    agrupado[talla].colores.push({ color, cantidad: qty });
                                }
                            }
                        });
                        return agrupado;
                    };

                    const renderGenero = (titulo, iconHtml, colorTitulo, bgBadge, colorBadge, tallasObjAgrupado) => {
                        const tallasEntries = Object.entries(tallasObjAgrupado || {});
                        if (tallasEntries.length === 0) return '';

                        const cards = tallasEntries.map(([talla, info]) => {
                            const coloresHTML = (info.colores && info.colores.length > 0)
                                ? info.colores.map(c => {
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
                    };

                    const damaAgr = parseTallas(damaObj);
                    const cabAgr = parseTallas(caballeroObj);

                    tallasHTML = '<div style="margin-top: 0.75rem;">';
                    tallasHTML += renderGenero('DAMA', '<i class="fas fa-female" style="color: #be185d;"></i>', '#be185d', '#be185d', '#be185d', damaAgr);
                    tallasHTML += renderGenero('CABALLERO', '<i class="fas fa-male" style="color: #1d4ed8;"></i>', '#1d4ed8', '#1d4ed8', '#1d4ed8', cabAgr);
                    tallasHTML += '</div>';
                }
            }
            
            let observacionesHTML = '';
            if (datos.observaciones) {
                observacionesHTML = `
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                        <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">
                            <i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Observaciones:
                        </strong>
                        <span style="color: #78350f; font-size: 0.9rem;">${datos.observaciones}</span>
                    </div>
                `;
            }
            
            let imagenHTML = '';
            const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
            if (imagenes.length > 0) {
                imagenHTML = `
                    <div style="margin-top: 0.75rem;">
                        <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                            <i class="fas fa-images" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Imágenes (${imagenes.length}):
                        </strong>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            ${imagenes.map(img => {
                                // Extraer URL según el tipo de imagen
                                let imgSrc = '';
                                
                                if (img instanceof File) {
                                    imgSrc = URL.createObjectURL(img);
                                } else if (typeof img === 'string') {
                                    imgSrc = img;
                                } else if (img && typeof img === 'object') {
                                    // Intentar extraer URL de múltiples propiedades
                                    imgSrc = img.previewUrl || img.dataURL || img.src || img.url || img.blobUrl || img.ruta_original || '';
                                }
                                
                                // Agregar /storage/ si es una ruta relativa
                                if (imgSrc && !imgSrc.startsWith('/') && !imgSrc.startsWith('http') && !imgSrc.startsWith('blob:') && !imgSrc.startsWith('data:')) {
                                    imgSrc = '/storage/' + imgSrc;
                                }
                                
                                return imgSrc ? `
                                <img src="${imgSrc}" 
                                     alt="Imagen ${nombreProceso}" 
                                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer;"
                                     onclick="window.mostrarImagenProcesoGrande('${imgSrc}')">
                            ` : '';
                            }).join('')}
                        </div>
                  
                    </div>
                `;
            }
            
            procesosItemsHTML += `
                <div style="background: white; border: 2px solid #e5e7eb; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 2px solid #0ea5e9;">
                        <span style="font-size: 1.5rem;">${icono}</span>
                        <h4 style="margin: 0; color: #0369a1; font-size: 1.1rem; font-weight: 700;">${nombreProceso}</h4>
                    </div>
                    
                    ${ubicacionesHTML ? `
                        <div style="margin-bottom: 0.75rem;">
                            <strong style="color: #374151; display: block; margin-bottom: 0.5rem;">
                                <i class="fas fa-location-arrow" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Ubicaciones:
                            </strong>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                ${ubicacionesHTML}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${tallasHTML}
                    ${observacionesHTML}
                    ${imagenHTML}
                </div>
            `;
        });

        return `
            <div class="seccion-expandible procesos-section">
                <button class="seccion-expandible-header" type="button" data-section="procesos" data-prenda-index="${indice}">
                    <h4>Procesos <span style="margin-left: 0.5rem; font-size: 0.8rem; color: #6b7280;">(<span class="procesos-count">${procesosConDatos.length}</span>)</span></h4>
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

    /**
     * Construir tabla de telas con todas las variaciones
     */
    _construirTablaTelas(prenda, indice) {
        let telas = [];

        // Obtener telas de diferentes fuentes
        if (prenda.telasAgregadas) {
            telas = this._normalizarTelas(prenda.telasAgregadas);
        } else if (prenda.telas) {
            telas = this._normalizarTelas(prenda.telas);
        } else if (prenda.imagenes_tela && Array.isArray(prenda.imagenes_tela)) {
            // Convertir imagenes_tela a formato de telas
            telas = prenda.imagenes_tela.map(img => ({
                tela: prenda.tela || 'N/A',
                color: prenda.color || 'N/A',
                referencia: prenda.referencia || prenda.ref || 'N/A',
                imagenes: [img]
            }));
        }

        if (telas.length === 0) {

            return '';
        }



        // Construir tabla de telas
        const tablaTelasHTML = telas.map((telaItem, telaIndex) => {
            const nombreTela = telaItem.tela || telaItem.nombre_tela || 'N/A';
            const color = telaItem.color || 'N/A';
            const referencia = telaItem.referencia || telaItem.ref || 'N/A';
            const observaciones = telaItem.observaciones || '';
            
            // Usar servicio centralizado para obtener imagen de tela
            const telaFoto = window.ImageConverterService ? 
                window.ImageConverterService.obtenerImagenTela(telaItem) : 
                null;
            

            
            if (!telaFoto) {

            }

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
                                onload="console.log(' Imagen de tela ${telaIndex} cargada:', '${telaFoto.substring(0, 50)}')"
                                onerror="console.error(' Error cargando imagen de tela ${telaIndex}:', '${telaFoto.substring(0, 50)}')"
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
    }
};

