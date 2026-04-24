(function() {
    'use strict';

    async function obtenerArchivoDesdeImagenAsync(imagen) {
        if (!imagen) return null;
        if (imagen instanceof File) return imagen;
        if (imagen.file instanceof File) return imagen.file;
        if (imagen.archivo instanceof File) return imagen.archivo;
        if (typeof imagen === 'string' && imagen.startsWith('blob:')) {
            return await convertirBlobUrlAArchivoAsync(imagen);
        }
        if (typeof imagen === 'string' && imagen.startsWith('data:image/')) {
            return convertirDataUrlAArchivoAsync(imagen);
        }
        if (typeof imagen === 'object') {
            const blobUrl =
                (typeof imagen.blobUrl === 'string' && imagen.blobUrl.startsWith('blob:') && imagen.blobUrl) ||
                (typeof imagen.previewUrl === 'string' && imagen.previewUrl.startsWith('blob:') && imagen.previewUrl) ||
                (typeof imagen.url === 'string' && imagen.url.startsWith('blob:') && imagen.url) ||
                (typeof imagen.ruta === 'string' && imagen.ruta.startsWith('blob:') && imagen.ruta) ||
                (typeof imagen.ruta_original === 'string' && imagen.ruta_original.startsWith('blob:') && imagen.ruta_original) ||
                (typeof imagen.imagen_ruta === 'string' && imagen.imagen_ruta.startsWith('blob:') && imagen.imagen_ruta) ||
                null;
            if (blobUrl) {
                return await convertirBlobUrlAArchivoAsync(blobUrl, imagen.nombre || imagen.imagen_nombre || null);
            }

            const dataUrl =
                (typeof imagen.previewUrl === 'string' && imagen.previewUrl.startsWith('data:image/') && imagen.previewUrl) ||
                (typeof imagen.url === 'string' && imagen.url.startsWith('data:image/') && imagen.url) ||
                (typeof imagen.ruta === 'string' && imagen.ruta.startsWith('data:image/') && imagen.ruta) ||
                (typeof imagen.ruta_original === 'string' && imagen.ruta_original.startsWith('data:image/') && imagen.ruta_original) ||
                (typeof imagen.imagen_ruta === 'string' && imagen.imagen_ruta.startsWith('data:image/') && imagen.imagen_ruta) ||
                null;
            if (dataUrl) {
                return convertirDataUrlAArchivoAsync(dataUrl, imagen.nombre || imagen.imagen_nombre || null);
            }
        }
        return null;
    }

    async function convertirBlobUrlAArchivoAsync(blobUrl, nombreSugerido = null) {
        if (typeof blobUrl !== 'string' || !blobUrl.startsWith('blob:')) return null;

        try {
            const response = await fetch(blobUrl);
            if (!response.ok) return null;
            const blob = await response.blob();
            if (!(blob instanceof Blob)) return null;

            const extension = blob.type === 'image/png'
                ? 'png'
                : blob.type === 'image/webp'
                    ? 'webp'
                    : 'jpg';
            const nombreArchivo = nombreSugerido || `color_wizard_${Date.now()}_${Math.random().toString(36).slice(2, 7)}.${extension}`;

            return new File([blob], nombreArchivo, { type: blob.type || 'image/jpeg' });
        } catch (error) {
            console.warn('[DraftPedidoBuilder] No se pudo convertir blob URL a File:', error);
            return null;
        }
    }

    function convertirDataUrlAArchivoAsync(dataUrl, nombreSugerido = null) {
        if (typeof dataUrl !== 'string' || !dataUrl.startsWith('data:image/')) return null;

        try {
            const partes = dataUrl.split(',');
            if (partes.length < 2) return null;

            const metadata = partes[0];
            const contenido = partes.slice(1).join(',');
            const mimeMatch = metadata.match(/^data:(image\/[^;]+);base64$/i);
            if (!mimeMatch?.[1]) return null;

            const mimeType = mimeMatch[1].toLowerCase();
            const extension = mimeType === 'image/png'
                ? 'png'
                : mimeType === 'image/webp'
                    ? 'webp'
                    : mimeType === 'image/gif'
                        ? 'gif'
                        : 'jpg';
            const nombreArchivo = nombreSugerido || `color_wizard_${Date.now()}_${Math.random().toString(36).slice(2, 7)}.${extension}`;

            const binario = atob(contenido);
            const bytes = new Uint8Array(binario.length);
            for (let i = 0; i < binario.length; i++) {
                bytes[i] = binario.charCodeAt(i);
            }

            return new File([bytes], nombreArchivo, { type: mimeType });
        } catch (error) {
            console.warn('[DraftPedidoBuilder] No se pudo convertir data URL a File:', error);
            return null;
        }
    }

    async function obtenerArchivoDesdeColorWizardAsync(color) {
        if (!color || typeof color !== 'object') {
            return null;
        }

        const archivoDirecto = await obtenerArchivoDesdeImagenAsync(color.imagen);
        if (archivoDirecto) {
            return archivoDirecto;
        }

        const archivoPorRutaBlob = await obtenerArchivoDesdeImagenAsync(
            color.imagen_ruta || color.ruta_original || color.ruta_webp || color.url || color.ruta || null
        );
        if (archivoPorRutaBlob) {
            return archivoPorRutaBlob;
        }

        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.getImage === 'function' && color.imagen_id) {
            const imagenStore = window.ColoresPorTalla.getImage(color.imagen_id);
            return await obtenerArchivoDesdeImagenAsync(imagenStore);
        }

        return null;
    }

    function construirMapaIndicesTelas(telasArr) {
        const mapa = new Map();
        (telasArr || []).forEach((tela, idx) => {
            const nombreTela = String(tela?.nombre_tela || tela?.tela || tela?.nombre || '')
                .trim()
                .toUpperCase();
            if (nombreTela && !mapa.has(nombreTela)) {
                mapa.set(nombreTela, idx);
            }
        });
        return mapa;
    }

    function normalizarUrlImagenEpp(imagen) {
        if (!imagen) return '';
        if (typeof imagen === 'string') return imagen.trim();
        return String(
            imagen.url ||
            imagen.previewUrl ||
            imagen.preview ||
            imagen.ruta_web ||
            imagen.ruta_webp ||
            imagen.ruta_original ||
            imagen.ruta ||
            ''
        ).trim();
    }

    function obtenerClaveImagenEpp(imagen) {
        if (!imagen) return null;

        const id = Number(imagen.id || imagen.imagen_id || 0);
        if (Number.isInteger(id) && id > 0) {
            return `id:${id}`;
        }

        const url = normalizarUrlImagenEpp(imagen);
        return url ? `url:${url}` : null;
    }

    function eppTieneImagenesEditadas(epp, imagenesExistentesActuales, tieneArchivosNuevos) {
        if (tieneArchivosNuevos) {
            return true;
        }

        const originales = Array.isArray(epp?._imagenes_originales) ? epp._imagenes_originales : [];
        const clavesOriginales = new Set(
            originales
                .map((img) => obtenerClaveImagenEpp(img))
                .filter(Boolean)
        );
        const clavesActuales = new Set(
            (imagenesExistentesActuales || [])
                .map((url) => (typeof url === 'string' && url.trim() ? `url:${url.trim()}` : null))
                .filter(Boolean)
        );

        if (clavesOriginales.size !== clavesActuales.size) {
            return true;
        }

        for (const clave of clavesOriginales) {
            if (!clavesActuales.has(clave)) {
                return true;
            }
        }

        return epp?.imagenes_editadas === true;
    }

    async function adjuntarImagenesWizardATelasAsync(formData, p, nuevaPrendaIdx, telasArr, contadoresPorTela) {
        const asignaciones = p?.asignacionesColoresPorTalla;
        if (!asignaciones || typeof asignaciones !== 'object') {
            return;
        }

        const mapaIndicesTelas = construirMapaIndicesTelas(telasArr);
        const imagenesYaAgregadas = new Set();

        for (const asignacion of Object.values(asignaciones)) {
            const colores = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
            if (colores.length === 0) continue;

            const telaNombre = String(asignacion?.tela || '').trim().toUpperCase();
            let telaIdx = mapaIndicesTelas.has(telaNombre) ? mapaIndicesTelas.get(telaNombre) : null;
            if (telaIdx === null && telasArr.length > 0) {
                telaIdx = 0;
            }
            if (telaIdx === null || telaIdx === undefined) continue;

            for (const color of colores) {
                const file = await obtenerArchivoDesdeColorWizardAsync(color);
                if (!file) continue;

                const idUnico = color?.imagen_id || `${telaIdx}::${file.name}::${file.size}::${file.lastModified}`;
                if (imagenesYaAgregadas.has(idUnico)) continue;
                imagenesYaAgregadas.add(idUnico);

                const imgIdx = contadoresPorTela[telaIdx] || 0;
                formData.append(`nuevas_prendas[${nuevaPrendaIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`, file);
                contadoresPorTela[telaIdx] = imgIdx + 1;
            }
        }
    }

    function construirEppsProcesados(datos, formData) {
        return (datos.epps || []).map((e, eppIndex) => {
            const imagenesExistentes = [];
            let tieneArchivosNuevos = false;

            // Identificador único para cada EPP: existentes usan pedido_epp_id, nuevos generan temporal
            const eppId = e.pedido_epp_id || (e._local_epp_id = e._local_epp_id || `epp-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`);

            if (Array.isArray(e.imagenes)) {
                e.imagenes.forEach((img, imgIndex) => {
                    if (!img) return;

                    if (img instanceof File || (img.file && img.file instanceof File) || (img.archivo && img.archivo instanceof File)) {
                        const file = img instanceof File ? img : (img.file instanceof File ? img.file : img.archivo);
                        const fieldName = `epps_${eppId}_imagenes_${imgIndex}`;
                        formData.append(fieldName, file);
                        tieneArchivosNuevos = true;
                        return;
                    }

                    let imageUrl = null;
                    if (typeof img === 'string') imageUrl = img;
                    else if (img.url) imageUrl = img.url;
                    else if (img.previewUrl) imageUrl = img.previewUrl;
                    else if (img.preview) imageUrl = img.preview;
                    else if (img.ruta_webp) imageUrl = img.ruta_webp;
                    else if (img.ruta) imageUrl = img.ruta;

                    if (imageUrl) {
                        imagenesExistentes.push(imageUrl);
                    }
                });
            }

            const eppPayload = {
                epp_id: e.epp_id,
                pedido_epp_id: e.pedido_epp_id || null,
                cantidad: e.cantidad,
                observaciones: e.observaciones,
                imagenes: imagenesExistentes,
                _epp_form_identifier: eppId // Identificador único para matching de archivos (pedido_epp_id o temporal)
            };

            // Si hay archivos nuevos, indicar modo "upload"
            if (tieneArchivosNuevos) {
                eppPayload.modo_imagenes = 'upload';
            } else if (eppTieneImagenesEditadas(e, imagenesExistentes, tieneArchivosNuevos)) {
                // Señal explícita: el usuario editó imágenes (incluye caso de dejar en 0).
                eppPayload.modo_imagenes = 'reuse';
            }

            return eppPayload;
        });
    }

    async function construirNuevasPrendasYExistentes(formData) {
        const prendasExistentesJson = [];
        const nuevasPrendasJson = [];

        if (!window.gestionItemsUI || !Array.isArray(window.gestionItemsUI.prendas)) {
            return { prendasExistentesJson, nuevasPrendasJson };
        }

        let nuevaPrendaIdx = 0; // Contador independiente para nuevas prendas (el backend itera desde 0)

        const extraerArchivoImagen = (imagen) => {
            if (!imagen) return null;
            if (imagen instanceof File) return imagen;
            if (imagen?.file instanceof File) return imagen.file;
            return null;
        };

        const adjuntarImagenesProcesosNuevaPrenda = (prendaData, prendaFormDataIdx) => {
            const procesos = (prendaData?.procesos && typeof prendaData.procesos === 'object')
                ? prendaData.procesos
                : {};

            Object.entries(procesos).forEach(([procesoKey, procesoValue]) => {
                const datosProceso = procesoValue?.datos || procesoValue || {};
                const procesoKeyFormData = /^\d+$/.test(String(procesoKey))
                    ? String(datosProceso.tipo || procesoValue?.tipo || procesoKey)
                    : String(procesoKey);
                const fuentes = [
                    ...(Array.isArray(datosProceso.imagenesFiles) ? datosProceso.imagenesFiles : []),
                    ...(Array.isArray(datosProceso.fotosGeneralesFiles) ? datosProceso.fotosGeneralesFiles : []),
                    ...(Array.isArray(datosProceso.imagenes) ? datosProceso.imagenes : [])
                ];

                const vistos = new Set();
                let procesoImgIdx = 0;

                fuentes.forEach((img) => {
                    const file = extraerArchivoImagen(img);
                    if (!(file instanceof File)) return;

                    const keyDedupe = `${file.name}::${file.size}::${file.lastModified}`;
                    if (vistos.has(keyDedupe)) return;
                    vistos.add(keyDedupe);

                    formData.append(
                        `nuevas_prendas[${prendaFormDataIdx}][procesos][${procesoKeyFormData}][imagenes][${procesoImgIdx}]`,
                        file
                    );
                    procesoImgIdx++;
                });

                // Modo especifico: adjuntar archivos por talla/color en la ruta esperada por backend.
                // Se envian tambien bajo "prendas[...]" para compatibilidad con ProcesoImagenService.
                const datosExtendidos = datosProceso?.datosExtendidos || datosProceso?.datos_extendidos || null;
                if (datosExtendidos && typeof datosExtendidos === 'object') {
                    Object.entries(datosExtendidos).forEach(([generoKey, tallasData]) => {
                        if (!tallasData || typeof tallasData !== 'object') return;

                        Object.entries(tallasData).forEach(([tallaKey, tallaDetalle]) => {
                            const imagenesTalla = Array.isArray(tallaDetalle?.imagenesFiles) ? tallaDetalle.imagenesFiles : [];
                            if (imagenesTalla.length === 0) return;

                            let imgTallaIdx = 0;
                            imagenesTalla.forEach((imgTalla) => {
                                const file = extraerArchivoImagen(imgTalla);
                                if (!(file instanceof File)) return;

                                // Para que Laravel detecte en request->hasFile('prendas.i.procesos.x.datosExtendidos.g.t.imagenes.j')
                                formData.append(
                                    `prendas[${prendaFormDataIdx}][procesos][${procesoKeyFormData}][datosExtendidos][${generoKey}][${tallaKey}][imagenes][${imgTallaIdx}]`,
                                    file
                                );
                                imgTallaIdx++;
                            });
                        });
                    });
                }
            });
        };

        await Promise.all(window.gestionItemsUI.prendas.map(async (p, prendaIdx) => {
            // Solo es "existente" si tiene prenda_pedido_id que sea un número > 0 (ID real de BD)
            // NO cuenta los IDs locales como "prenda-local-xxx" que son strings
            const tieneIdRealBD = p?.prenda_pedido_id && Number.isInteger(p.prenda_pedido_id) && p.prenda_pedido_id > 0;
            const esPrendaExistente = !!tieneIdRealBD;
            if (esPrendaExistente) {
                const payloadPrendaExistente = typeof window.serializarPrendaExistenteParaBorrador === 'function'
                    ? await window.serializarPrendaExistenteParaBorrador(p, p.prenda_pedido_id, formData)
                    : null;

                if (payloadPrendaExistente) {
                    prendasExistentesJson.push(payloadPrendaExistente);
                }
                return;
            }

            // Para nuevas prendas, obtener imágenes del IndexedImageStorageService
            let imagenesArr = [];
            if (typeof window.imagenesPrendaStorage !== 'undefined' && window.imagenesPrendaStorage.obtenerImagenesDe) {
                // Intentar obtener imágenes usando varios identificadores posibles
                // 1. _local_id (usado para nuevas prendas)
                // 2. prenda_pedido_id (usado para prendas existentes)
                // 3. prendaIdx (índice en el array)
                let prendaId = null;
                if (p._local_id) {
                    prendaId = p._local_id;
                } else if (p.prenda_pedido_id && Number.isInteger(p.prenda_pedido_id)) {
                    prendaId = p.prenda_pedido_id;
                } else {
                    prendaId = prendaIdx;
                }

                const imagenesFromStorage = window.imagenesPrendaStorage.obtenerImagenesDe(prendaId);
                imagenesArr = imagenesFromStorage && Array.isArray(imagenesFromStorage) ? imagenesFromStorage : [];

                if (imagenesArr.length > 0) {
                    console.log('[draft-pedido-builder] Imágenes obtenidas del storage para prenda:', prendaId, 'Total:', imagenesArr.length);
                }
            }
            // Fallback a p.imagenes si no hay almacenamiento disponible
            if (imagenesArr.length === 0 && Array.isArray(p.imagenes)) {
                imagenesArr = p.imagenes;
            }
            let imgFileIdx = 0;
            imagenesArr.forEach((img) => {
                const file = (img instanceof File) ? img : (img && img.file instanceof File ? img.file : null);
                if (file) {
                    // Bracket notation so PHP builds nested $_FILES['nuevas_prendas'][i]['imagenes'][j]
                    // which Laravel can find via dot-notation: hasFile('nuevas_prendas.i.imagenes.j')
                    formData.append(`nuevas_prendas[${nuevaPrendaIdx}][imagenes][${imgFileIdx}]`, file);
                    imgFileIdx++;
                }
            });

            const telasArr = Array.isArray(p.telasAgregadas) ? p.telasAgregadas : (Array.isArray(p.telas) ? p.telas : []);
            const contadoresPorTela = {};
            for (let telaIdx = 0; telaIdx < telasArr.length; telaIdx++) {
                const tela = telasArr[telaIdx];
                let telaImgFileIdx = 0;
                const imagenesTelaArr = Array.isArray(tela.imagenes) ? tela.imagenes : [];
                for (const imgTela of imagenesTelaArr) {
                    const file = await obtenerArchivoDesdeImagenAsync(imgTela);
                    if (file) {
                        formData.append(`nuevas_prendas[${nuevaPrendaIdx}][telas][${telaIdx}][imagenes][${telaImgFileIdx}]`, file);
                        telaImgFileIdx++;
                    }
                }
                contadoresPorTela[telaIdx] = telaImgFileIdx;
            }

            // Fallback para wizard: imágenes por color/talla (imagen_id en asignaciones)
            await adjuntarImagenesWizardATelasAsync(formData, p, nuevaPrendaIdx, telasArr, contadoresPorTela);
            // Adjuntar imagenes de procesos para nuevas prendas
            adjuntarImagenesProcesosNuevaPrenda(p, nuevaPrendaIdx);

            nuevasPrendasJson.push({
                tipo: 'prenda',
                local_id: p._local_id || null,
                nombre_prenda: p.nombre_prenda || p.nombre_producto || '',
                nombre_producto: p.nombre_producto || p.nombre_prenda || '',
                descripcion: p.descripcion || '',
                de_bodega: p.de_bodega !== undefined ? p.de_bodega : 1,
                genero: p.genero || '',
                cantidad_talla: p.cantidad_talla || p.cantidades || {},
                telas: telasArr.map(t => ({
                    tela: t.nombre_tela || t.tela || '',
                    color: t.color || t.color_nombre || '',
                    referencia: t.referencia || ''
                })),
                procesos: (typeof p.procesos === 'object' && p.procesos) ? p.procesos : {},
                asignacionesColoresPorTalla: p.asignacionesColoresPorTalla || {}
            });
            nuevaPrendaIdx++;
        }));

        return { prendasExistentesJson, nuevasPrendasJson };
    }

    async function construirFormDataBorrador(datos, csrfToken) {
        const formData = new FormData();
        const eppsProcesados = construirEppsProcesados(datos, formData);
        const { prendasExistentesJson, nuevasPrendasJson } = await construirNuevasPrendasYExistentes(formData);
        const prendasEliminadas = Array.isArray(window.gestionItemsUI?.prendasEliminadas)
            ? window.gestionItemsUI.prendasEliminadas
                .map((p) => ({
                    prenda_id: Number(p?.prenda_id || 0),
                    nombre_prenda: p?.nombre_prenda || '',
                    motivo: p?.motivo || 'Eliminada desde edicion de borrador'
                }))
                .filter((p) => p.prenda_id > 0)
            : [];

        const pedidoLimpio = {
            cliente: datos.cliente || '',
            asesora: datos.asesora || '',
            forma_de_pago: datos.forma_de_pago || '',
            observaciones: datos.observaciones || '',
            orden_compra: datos.orden_compra || document.getElementById('orden_compra_editable')?.value?.trim() || '',
            numero_cotizacion: datos.numero_cotizacion,
            es_sin_cotizacion: datos.es_sin_cotizacion,
            tipo_cotizacion: datos.tipo_cotizacion || null,
            logo: datos.logo || null,
            reflectivo: datos.reflectivo || null,
            prendas: [],
            prendas_existentes: prendasExistentesJson,
            nuevas_prendas: nuevasPrendasJson,
            prendas_eliminadas: prendasEliminadas,
            epps: eppsProcesados
        };

        formData.append('pedido', JSON.stringify(pedidoLimpio));
        formData.append('_token', csrfToken);

        return {
            formData,
            pedidoLimpio
        };
    }

    window.DraftPedidoBuilder = {
        construirFormDataBorrador
    };
})();
