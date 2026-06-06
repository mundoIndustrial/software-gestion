/**
 * Utilities for prenda-editor-pedidos-adapter data transforms.
 * Exposes: window.PedidosAdapterDataUtils
 */
(function() {
    'use strict';

    function _normalizarRutasStorage(ruta) {
        if (!ruta) return '';
        if (ruta.startsWith('/storage/') || ruta.startsWith('http') || ruta.startsWith('blob:') || ruta.startsWith('data:')) return ruta;
        if (ruta.startsWith('/')) return '/storage' + ruta;
        return '/storage/' + ruta;
    }

    function _extraerImagenesExistentesProceso(d, tipo) {
        const imagenesExistentes = [];
        if (d.imagenes_existentes && Array.isArray(d.imagenes_existentes)) {
            console.log(`[PedidosAdapter]  Usando imagenes_existentes explicitas para ${tipo}:`, d.imagenes_existentes.length, 'imagenes');
            d.imagenes_existentes.forEach((img) => {
                const url = img.url || img.ruta_original || img.ruta_webp || img.ruta || img.previewUrl || '';
                const id = img.id || null;
                if (url || id) {
                    imagenesExistentes.push({ id: id, url: url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                }
            });
            return imagenesExistentes;
        }

        if (d.imagenes && Array.isArray(d.imagenes)) {
            d.imagenes.forEach((img) => {
                if (img && !(img instanceof File) && !(img?.file instanceof File)) {
                    const url = img.url || img.ruta_original || img.ruta_webp || img.ruta || img.previewUrl || '';
                    const id = img.id || null;
                    if (url || id) {
                        imagenesExistentes.push({ id: id, url: url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                    }
                }
            });
        }
        return imagenesExistentes;
    }

    function _extraerImagenesAEliminarProceso(d, tipo) {
        const imagenesAEliminar = [];

        if (d.imagenes_a_eliminar && Array.isArray(d.imagenes_a_eliminar)) {
            console.log(`[PedidosAdapter]  Imagenes a eliminar para ${tipo}:`, d.imagenes_a_eliminar.length);
            d.imagenes_a_eliminar.forEach((img) => {
                if (img) imagenesAEliminar.push(img);
            });
        }

        if (d.imagenes_eliminadas && Array.isArray(d.imagenes_eliminadas)) {
            console.log(`[PedidosAdapter]  Imagenes a eliminar para ${tipo}:`, d.imagenes_eliminadas.length);
            d.imagenes_eliminadas.forEach((imgUrl) => {
                if (imgUrl && typeof imgUrl === 'string') {
                    imagenesAEliminar.push({ url: imgUrl, ruta_original: imgUrl });
                }
            });
        }

        if (d.imagenesEliminadas && Array.isArray(d.imagenesEliminadas)) {
            console.log(`[PedidosAdapter]  DEBUGGING imagenesEliminadas para ${tipo}:`, {
                length: d.imagenesEliminadas.length,
                items: d.imagenesEliminadas.map((img, idx) => ({
                    idx,
                    esNull: img === null,
                    tipo: typeof img,
                    tieneId: !!img?.id,
                    id: img?.id,
                    ruta: img?.ruta_original,
                    claves: typeof img === 'object' ? Object.keys(img || {}) : 'N/A'
                }))
            });
            d.imagenesEliminadas.forEach((img) => {
                const tieneIdentificador = img && (img.id || img.ruta_original);
                if (tieneIdentificador) {
                    const imagenAEliminar = {
                        id: img.id || undefined,
                        ruta_original: img.ruta_original || img.url || '',
                        ruta_webp: img.ruta_webp || ''
                    };
                    imagenesAEliminar.push(imagenAEliminar);
                    console.log('[PedidosAdapter]  Objeto AGREGADO a imagenesAEliminar:', imagenAEliminar);
                } else {
                    console.log('[PedidosAdapter]  IMAGEN RECHAZADA - sin ID ni ruta:', img);
                }
            });
        }

        return imagenesAEliminar;
    }

    function _construirProcesoEnvio(tipo, proc) {
        const d = proc?.datos || proc || {};
        console.log(`[PedidosAdapter]  DIAGNOSTICO Proceso ${tipo}:`, {
            tieneImagenes: !!d.imagenes,
            tieneImagenesEliminadas: !!d.imagenesEliminadas,
            cantidadImagenes: d.imagenes?.length || 0,
            cantidadEliminadas: d.imagenesEliminadas?.length || 0,
            datosKeys: Object.keys(d)
        });

        const imagenesExistentes = _extraerImagenesExistentesProceso(d, tipo);
        const imagenesAEliminar = _extraerImagenesAEliminarProceso(d, tipo);

        const procesoEnvio = {
            id: d.id || undefined,
            tipo_proceso_id: d.tipo_proceso_id || undefined,
            tipo: d.tipo || tipo,
            nombre: d.nombre || tipo,
            ubicaciones: d.ubicaciones || [],
            observaciones: d.observaciones || '',
            modoTallas: d.modoTallas || d.modo_tallas || proc?.modoTallas || 'generico',
            tallas: d.tallas || {},
            datosExtendidos: d.datosExtendidos || {},
            estado: d.estado || 'PENDIENTE',
            imagenes_existentes: imagenesExistentes
        };

        if (imagenesAEliminar.length > 0) {
            procesoEnvio.imagenes_a_eliminar = imagenesAEliminar;
            console.log(`[PedidosAdapter]  Proceso ${tipo}: ${imagenesAEliminar.length} imagen(es) marcada(s) para eliminar:`, imagenesAEliminar);
        }
        return procesoEnvio;
    }

    function _extraerFilesDeProcesosRaw(procesosRaw) {
        const filesPorProceso = {};
        const filesPorTalla = {};

        Object.entries(procesosRaw).forEach(([tipo, proc], idx) => {
            const d = proc?.datos || proc || {};
            const imagenes = d.imagenes || [];
            filesPorProceso[idx] = [];

            if (d.imagenesFiles && Array.isArray(d.imagenesFiles)) {
                d.imagenesFiles.forEach((file) => {
                    if (file instanceof File) {
                        filesPorProceso[idx].push(file);
                        console.log(`[PedidosAdapter]  File encontrado en imagenesFiles para ${tipo}`);
                    }
                });
            }

            if (Array.isArray(imagenes)) {
                imagenes.forEach((img) => {
                    if (img instanceof File) {
                        filesPorProceso[idx].push(img);
                    } else if (img?.file instanceof File) {
                        filesPorProceso[idx].push(img.file);
                    }
                });
            }

            if (d.datosExtendidos && typeof d.datosExtendidos === 'object') {
                Object.entries(d.datosExtendidos).forEach(([genero, tallasDatos]) => {
                    if (tallasDatos && typeof tallasDatos === 'object') {
                        Object.entries(tallasDatos).forEach(([talla, tallaData]) => {
                            if (tallaData?.imagenesFiles && Array.isArray(tallaData.imagenesFiles)) {
                                const keyTalla = `${idx}_${genero}_${talla}`;
                                filesPorTalla[keyTalla] = filesPorTalla[keyTalla] || [];
                                tallaData.imagenesFiles.forEach((img) => {
                                    if (img instanceof File) {
                                        filesPorTalla[keyTalla].push(img);
                                        console.log(`[PedidosAdapter]  Imagen File encontrada para talla ${genero}/${talla}`);
                                    }
                                });
                            }
                        });
                    }
                });
            }
        });

        return { filesPorProceso, filesPorTalla };
    }

    function _appendFilesDeProcesoEnFormData(formData, filesPorProceso, filesPorTalla) {
        Object.entries(filesPorProceso).forEach(([idx, files]) => {
            files.forEach((file, fileIdx) => {
                formData.append(`fotosProcesoNuevo_${idx}[]`, file);
                console.log(`[PedidosAdapter]  Foto proceso[${idx}][${fileIdx}]: ${file.name}`);
            });
        });

        Object.entries(filesPorTalla).forEach(([keyTalla, files]) => {
            files.forEach((file, fileIdx) => {
                formData.append(`fotosProcesoTallasNuevo_${keyTalla}[]`, file);
                console.log(`[PedidosAdapter]  Foto talla[${keyTalla}][${fileIdx}]: ${file.name}`);
            });
        });
    }

    function _appendImagenesProcesoEliminadasEnFormData(formData, procesosArray) {
        const todasLasImagenesAEliminar = [];
        procesosArray.forEach((proceso, idx) => {
            if (proceso.imagenes_a_eliminar && Array.isArray(proceso.imagenes_a_eliminar)) {
                proceso.imagenes_a_eliminar.forEach((img) => {
                    todasLasImagenesAEliminar.push(img);
                    console.log(`[PedidosAdapter]  Imagen a eliminar de proceso ${idx}:`, img);
                });
            }
        });

        if (todasLasImagenesAEliminar.length > 0) {
            formData.append('imagenes_a_eliminar', JSON.stringify(todasLasImagenesAEliminar));
            console.log('[PedidosAdapter]  Total imagenes a eliminar:', todasLasImagenesAEliminar.length);
        }
    }

    function _separarImagenesPrenda(imgs) {
        const imagenesNuevas = [];
        const imagenesExistentes = [];

        imgs.forEach((img) => {
            if (img instanceof File) {
                imagenesNuevas.push(img);
            } else if (img?.file instanceof File) {
                imagenesNuevas.push(img.file);
            } else if (img?.urlDesdeDB || img?.id || img?.url?.startsWith('/') || img?.ruta || img?.ruta_original || img?.ruta_webp || img?.previewUrl?.startsWith('/storage/')) {
                imagenesExistentes.push({
                    id: img.id,
                    url: img.url || img.ruta || img.ruta_webp || img.ruta_original || img.previewUrl || ''
                });
            }
        });

        return { imagenesNuevas, imagenesExistentes };
    }

    function _appendEliminacionesGlobalesPrenda(formData) {
        if (window.imagenesAEliminar && window.imagenesAEliminar.length > 0) {
            formData.append('imagenes_a_eliminar', JSON.stringify(window.imagenesAEliminar));
            console.log('[PedidosAdapter]  Imagenes marcadas para eliminacion:', window.imagenesAEliminar);
        }

        if (window.procesosParaEliminarIds && window.procesosParaEliminarIds.size > 0) {
            const procesosAEliminar = Array.from(window.procesosParaEliminarIds);
            formData.append('procesos_a_eliminar', JSON.stringify(procesosAEliminar));
            console.log('[PedidosAdapter]  Procesos marcados para eliminacion:', procesosAEliminar);
        }
    }

    function appendProcesosPrenda(formData, datos) {
        if (!datos.procesos) return;
        const procesosRaw = datos.procesos;
        let procesosArray = [];

        if (!Array.isArray(procesosRaw) && typeof procesosRaw === 'object') {
            const { filesPorProceso, filesPorTalla } = _extraerFilesDeProcesosRaw(procesosRaw);
            procesosArray = Object.entries(procesosRaw).map(([tipo, proc]) => _construirProcesoEnvio(tipo, proc));
            _appendFilesDeProcesoEnFormData(formData, filesPorProceso, filesPorTalla);
        } else if (Array.isArray(procesosRaw)) {
            procesosArray = procesosRaw;
        }

        _appendImagenesProcesoEliminadasEnFormData(formData, procesosArray);
        formData.append('procesos', JSON.stringify(procesosArray));
        console.log('[PedidosAdapter]  Procesos enviados:', procesosArray.length, 'procesos');
    }

    function appendImagenesPrendaYEliminaciones(formData, datos) {
        const imgs = datos.imagenes || [];
        console.log('[PedidosAdapter]  DIAGNOSTICO DE IMAGENES:', {
            'datos.imagenes': datos.imagenes,
            'typeof datos.imagenes': typeof datos.imagenes,
            'imgs.length': imgs.length,
            'imgs': imgs,
            'window.imagenesAEliminar': window.imagenesAEliminar
        });

        const { imagenesNuevas, imagenesExistentes } = _separarImagenesPrenda(imgs);
        console.log('[PedidosAdapter]RESULTADO IMAGENES:', {
            'imagenesNuevas': imagenesNuevas.length,
            'imagenesExistentes': imagenesExistentes.length,
            'imagenesExistentes_content': imagenesExistentes
        });

        imagenesNuevas.forEach((file) => formData.append('imagenes[]', file));
        formData.append('imagenes_existentes', JSON.stringify(imagenesExistentes));
        _appendEliminacionesGlobalesPrenda(formData);
    }

    function _normalizarCantidadTallaCanonica(cantidadTalla) {
        if (!cantidadTalla || typeof cantidadTalla !== 'object' || Array.isArray(cantidadTalla)) {
            return {};
        }

        console.debug('[PedidosAdapter][Tallas] Normalizando cantidad_talla canonica', {
            keys: Object.keys(cantidadTalla || {}),
            data: cantidadTalla
        });

        const normalizado = {};
        const sobremedida = {};

        Object.entries(cantidadTalla).forEach(([generoRaw, tallasRaw]) => {
            const genero = String(generoRaw || '').toUpperCase().trim();
            if (!genero) return;

            if (!tallasRaw || typeof tallasRaw !== 'object' || Array.isArray(tallasRaw)) {
                return;
            }

            const claves = Object.keys(tallasRaw);
            const esWrapperTallas = claves.length === 1 && String(claves[0]).toLowerCase() === 'tallas';

            if (genero === 'SOBREMEDIDA') {
                Object.entries(tallasRaw).forEach(([subGeneroRaw, cantidadRaw]) => {
                    const subGenero = String(subGeneroRaw || '').toUpperCase().trim();
                    const cantidad = parseInt(cantidadRaw, 10) || 0;
                    if (['DAMA', 'CABALLERO', 'UNISEX'].includes(subGenero) && cantidad > 0) {
                        sobremedida[subGenero] = cantidad;
                    }
                });
                return;
            }

            if (esWrapperTallas) {
                const cantidad = parseInt(tallasRaw.tallas, 10) || 0;
                if (cantidad > 0 && ['DAMA', 'CABALLERO', 'UNISEX'].includes(genero)) {
                    console.debug('[PedidosAdapter][Tallas] Wrapper sobremedida detectado', {
                        genero,
                        cantidad
                    });
                    sobremedida[genero] = cantidad;
                }
                return;
            }

            const tallasGenero = {};
            Object.entries(tallasRaw).forEach(([tallaRaw, cantidadRaw]) => {
                const talla = String(tallaRaw || '').trim().toUpperCase();
                const cantidad = parseInt(cantidadRaw, 10) || 0;
                if (!talla || talla === 'NULL' || cantidad <= 0) return;
                tallasGenero[talla] = cantidad;
            });

            if (Object.keys(tallasGenero).length > 0) {
                normalizado[genero] = tallasGenero;
            }
        });

        if (Object.keys(sobremedida).length > 0) {
            normalizado.SOBREMEDIDA = sobremedida;
        }

        console.debug('[PedidosAdapter][Tallas] Resultado normalizado', normalizado);

        return normalizado;
    }

    function _normalizarTallasBD(prenda) {
        const hayTallas = (prenda.tallas_dama && prenda.tallas_dama.length > 0) ||
                          (prenda.tallas_caballero && prenda.tallas_caballero.length > 0) ||
                          (prenda.tallas_unisex && prenda.tallas_unisex.length > 0) ||
                          (prenda.tallas_sobremedida && prenda.tallas_sobremedida.length > 0) ||
                          (prenda.tallas_generico && prenda.tallas_generico.length > 0);

        if (hayTallas) {
            console.debug('[PedidosAdapter][Tallas] Normalizando desde tablas relacionales', {
                prenda_id: prenda.id || prenda.prenda_pedido_id || null,
                fuentes: {
                    tallas_dama: prenda.tallas_dama?.length || 0,
                    tallas_caballero: prenda.tallas_caballero?.length || 0,
                    tallas_unisex: prenda.tallas_unisex?.length || 0,
                    tallas_sobremedida: prenda.tallas_sobremedida?.length || 0,
                    tallas_generico: prenda.tallas_generico?.length || 0
                }
            });

            const cantidadTalla = {};
            const generosMap = {
                tallas_dama: 'DAMA',
                tallas_caballero: 'CABALLERO',
                tallas_unisex: 'UNISEX',
                tallas_sobremedida: 'SOBREMEDIDA',
                tallas_generico: 'GENERICO'
            };
            Object.entries(generosMap).forEach(([prop, genero]) => {
                if (prenda[prop] && prenda[prop].length > 0) {
                    cantidadTalla[genero] = {};
                    prenda[prop].forEach((t) => {
                        const cantidad = parseInt(t.cantidad) || 0;
                        if (cantidad <= 0) {
                            return;
                        }

                        if (t.es_sobremedida) {
                            // El genero real está en t.genero (DAMA, CABALLERO, etc)
                            const generoReal = String(t.genero || '').trim().toUpperCase();
                            if (!cantidadTalla['SOBREMEDIDA']) {
                                cantidadTalla['SOBREMEDIDA'] = {};
                            }
                            cantidadTalla['SOBREMEDIDA'][generoReal] = cantidad;
                            return;
                        }

                        const talla = String(t.talla || '').trim().toUpperCase();
                        if (!talla) {
                            return;
                        }

                        cantidadTalla[genero][talla] = cantidad;
                    });
                }
            });
            const cantidadTallaNormalizada = _normalizarCantidadTallaCanonica(cantidadTalla);
            prenda.cantidad_talla = cantidadTallaNormalizada;
            prenda.tallasRelacionales = cantidadTallaNormalizada;
            console.log('[PedidosAdapter]  Tallas normalizadas:', cantidadTallaNormalizada);
            return;
        }

        if (prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && !Array.isArray(prenda.cantidad_talla)) {
            console.debug('[PedidosAdapter][Tallas] Normalizando cantidad_talla existente', {
                prenda_id: prenda.id || prenda.prenda_pedido_id || null,
                cantidad_talla: prenda.cantidad_talla
            });
            const cantidadTallaNormalizada = _normalizarCantidadTallaCanonica(prenda.cantidad_talla);
            prenda.cantidad_talla = cantidadTallaNormalizada;
            prenda.tallasRelacionales = cantidadTallaNormalizada;
            console.log('[PedidosAdapter]  cantidad_talla normalizada desde BD:', cantidadTallaNormalizada);
            return;
        }
        if (Array.isArray(prenda.cantidad_talla) && prenda.cantidad_talla.length === 0) {
            prenda.cantidad_talla = {};
            return;
        }
        if (!prenda.cantidad_talla) {
            prenda.cantidad_talla = {};
        }
    }

    function _normalizarGenerosConTallas(prenda) {
        const fuenteBase = (prenda.generosConTallas && typeof prenda.generosConTallas === 'object' && !Array.isArray(prenda.generosConTallas))
            ? prenda.generosConTallas
            : ((prenda.cantidad_talla && typeof prenda.cantidad_talla === 'object' && !Array.isArray(prenda.cantidad_talla))
                ? prenda.cantidad_talla
                : null);

        if (!fuenteBase) return;

        const generosNormalizados = _normalizarCantidadTallaCanonica(fuenteBase);
        prenda.generosConTallas = {};

        Object.keys(generosNormalizados).forEach((genero) => {
            const tallasGenero = generosNormalizados[genero];
            if (tallasGenero && typeof tallasGenero === 'object' && !Array.isArray(tallasGenero)) {
                prenda.generosConTallas[genero] = { ...tallasGenero };
            }
        });

        if (!prenda.cantidad_talla || typeof prenda.cantidad_talla !== 'object' || Array.isArray(prenda.cantidad_talla)) {
            prenda.cantidad_talla = { ...generosNormalizados };
        }

        console.log('[PedidosAdapter]  generosConTallas normalizado:', prenda.generosConTallas);
    }

    function _normalizarAsignacionesDesdeTallaColores(prenda) {
        console.log('[PedidosAdapter] DEBUG: Verificando talla_colores ANTES de procesar', {
            'existe': 'talla_colores' in prenda,
            'es_array': Array.isArray(prenda.talla_colores),
            'longitud': prenda.talla_colores?.length || 0,
            'contenido': prenda.talla_colores
        });

        if (Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
            console.log('[PedidosAdapter] Construyendo asignaciones desde talla_colores:', prenda.talla_colores.length, 'registros');
            prenda.asignaciones = prenda.talla_colores.map((tc) => ({
                tela: tc.tela_nombre || '',
                genero: tc.genero || '',
                talla: tc.talla || '',
                color: tc.color_nombre || '',
                cantidad: parseInt(tc.cantidad) || 0
            }));

            const coloresPorTalla = {};
            prenda.talla_colores.forEach((tc) => {
                const genero = (tc.genero || '').toLowerCase();
                const talla = tc.talla || '';
                const tela = tc.tela_nombre || '';
                const tipoTalla = /^\d+$/.test(talla) ? 'Número' : 'Letra';
                const key = `${genero}-${tipoTalla}-${talla}`;

                if (!coloresPorTalla[key]) {
                    coloresPorTalla[key] = { genero, tela, tipo: tipoTalla, talla, colores: [] };
                }

                coloresPorTalla[key].colores.push({
                    nombre: tc.color_nombre || '',
                    cantidad: parseInt(tc.cantidad) || 0,
                    referencia: tc.referencia || '',
                    observaciones: tc.observaciones || '',
                    imagen_ruta: tc.imagen_ruta || null
                });
            });

            prenda.asignacionesColoresPorTalla = coloresPorTalla;
            console.log('[PedidosAdapter] Asignaciones construidas:', prenda.asignaciones.length, 'filas');
            console.log('[PedidosAdapter] ColoresPorTalla:', Object.keys(coloresPorTalla).length, 'grupos');
        } else {
            console.log('[PedidosAdapter]  talla_colores esta vacio o no es array');
        }

        console.log('[PedidosAdapter] DEBUG: Estado FINAL de talla_colores', {
            'existe': 'talla_colores' in prenda,
            'es_array': Array.isArray(prenda.talla_colores),
            'longitud': prenda.talla_colores?.length || 0,
            'asignaciones_existe': 'asignaciones' in prenda,
            'asignaciones_longitud': prenda.asignaciones?.length || 0,
            'asignacionesColoresPorTalla_existe': 'asignacionesColoresPorTalla' in prenda,
            'asignacionesColoresPorTalla_keys': Object.keys(prenda.asignacionesColoresPorTalla || {}).length
        });
    }

    function _normalizarVariantesBD(prenda) {
        if (Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const v = prenda.variantes[0];
            prenda.variantes = {
                tipo_manga: v.tipo_manga !== 'Sin especificar' ? v.tipo_manga : null,
                tipo_manga_id: v.tipo_manga_id || null,
                obs_manga: v.manga_obs || '',
                tipo_broche: v.tipo_broche_boton !== 'Sin especificar' ? v.tipo_broche_boton : null,
                tipo_broche_id: v.tipo_broche_boton_id || null,
                obs_broche: v.broche_boton_obs || '',
                tiene_bolsillos: v.tiene_bolsillos || false,
                obs_bolsillos: v.bolsillos_obs || ''
            };
            console.log('[PedidosAdapter]  Variantes normalizadas:', prenda.variantes);
        } else if (Array.isArray(prenda.variantes) && prenda.variantes.length === 0) {
            prenda.variantes = {};
        }
    }

    function _normalizarTelasBD(prenda) {
        if (Array.isArray(prenda.colores_telas) && prenda.colores_telas.length > 0) {
            prenda.telasAgregadas = prenda.colores_telas.map((ct) => {
                let imagenes = [];
                if (ct.fotos_tela && Array.isArray(ct.fotos_tela)) {
                    imagenes = ct.fotos_tela.map((f) => _normalizarRutasStorage(f.url || f.ruta_webp || f.ruta_original || '')).filter((r) => r !== '');
                }
                return {
                    tela: ct.tela_nombre || '',
                    tela_id: ct.tela_id || null,
                    color: ct.color_nombre || '',
                    color_id: ct.color_id || null,
                    referencia: ct.tela_referencia || '',
                    imagenes,
                    _original_id: ct.id
                };
            });
            console.log('[PedidosAdapter]  Telas normalizadas:', prenda.telasAgregadas.length, 'telas');
        }

        if ((!prenda.telasAgregadas || prenda.telasAgregadas.length === 0) && Array.isArray(prenda.talla_colores) && prenda.talla_colores.length > 0) {
            const telasUnicas = new Map();
            prenda.talla_colores.forEach((tc) => {
                const telaKey = `${tc.tela_id || ''}_${tc.tela_nombre || ''}`;
                if (!telasUnicas.has(telaKey)) {
                    telasUnicas.set(telaKey, {
                        tela: tc.tela_nombre || '',
                        tela_id: tc.tela_id || null,
                        color: tc.color_nombre || '',
                        color_id: tc.color_id || null,
                        referencia: '',
                        imagenes: []
                    });
                }
            });
            prenda.telasAgregadas = Array.from(telasUnicas.values());
            console.log('[PedidosAdapter]  Telas extraidas desde talla_colores (fallback):', prenda.telasAgregadas.length, 'telas');
        }
    }

    function _normalizarImagenPrendaItem(img) {
        if (typeof img === 'object' && img !== null) {
            const url = img.url || img.ruta_webp || img.ruta_original || img.previewUrl || '';
            const normalizedUrl = (url && typeof url === 'string') ? _normalizarRutasStorage(url) : url;
            return {
                id: img.id,
                url: normalizedUrl,
                previewUrl: normalizedUrl,
                ruta_original: normalizedUrl,
                ruta_webp: normalizedUrl,
                nombre: img.nombre || `imagen-${img.id}`,
                tamano: img.tamano || 0
            };
        }

        if (typeof img === 'string') {
            const normalizedUrl = _normalizarRutasStorage(img);
            return {
                id: null,
                url: normalizedUrl,
                previewUrl: normalizedUrl,
                ruta_original: normalizedUrl,
                ruta_webp: normalizedUrl,
                nombre: 'imagen-sin-id',
                tamano: 0
            };
        }

        return img;
    }

    function _normalizarImagenesBD(prenda) {
        if (!Array.isArray(prenda.imagenes)) return;
        prenda.imagenes = prenda.imagenes.map(_normalizarImagenPrendaItem);
        console.log('[PedidosAdapter]  Imagenes de prenda normalizadas:', prenda.imagenes.length);
        console.log('[PedidosAdapter]  Detalle imagenes:', prenda.imagenes);
    }

    function _normalizarNombrePrenda(prenda) {
        if (prenda.nombre_prenda && !prenda.nombre) {
            prenda.nombre = prenda.nombre_prenda;
        }
    }

    function normalizarDatosBD(prenda) {
        if (!prenda) return prenda;
        _normalizarTallasBD(prenda);
        _normalizarGenerosConTallas(prenda);
        _normalizarAsignacionesDesdeTallaColores(prenda);
        _normalizarVariantesBD(prenda);
        _normalizarTelasBD(prenda);
        _normalizarImagenesBD(prenda);
        _normalizarNombrePrenda(prenda);
        return prenda;
    }

    window.PedidosAdapterDataUtils = {
        appendProcesosPrenda,
        appendImagenesPrendaYEliminaciones,
        normalizarDatosBD
    };
})();
