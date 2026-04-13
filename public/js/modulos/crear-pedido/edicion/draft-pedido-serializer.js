(function() {
    'use strict';

    function sincronizarPrendaModalAntesDeGuardarBorrador() {
        const modalPrenda = document.getElementById('modal-agregar-prenda-nueva');
        const gestionItems = window.gestionItemsUI;

        if (!modalPrenda || !gestionItems) {
            return true;
        }

        const estiloModal = window.getComputedStyle ? window.getComputedStyle(modalPrenda) : null;
        const modalVisible = modalPrenda.style.display === 'flex'
            || modalPrenda.classList.contains('show')
            || (estiloModal && estiloModal.display !== 'none');

        if (!modalVisible) {
            return true;
        }

        const prendaEditIndex = gestionItems.prendaEditIndex;
        const estaEditandoPrenda = prendaEditIndex !== null && prendaEditIndex !== undefined;

        if (!estaEditandoPrenda) {
            console.debug('[DraftPedidoSerializer] Modal de prenda abierto sin índice de edición; se omite sincronización previa.');
            return true;
        }

        if (!window.prendaFormCollector && typeof PrendaFormCollector !== 'undefined') {
            window.prendaFormCollector = new PrendaFormCollector();
        }

        if (!window.prendaFormCollector || typeof window.prendaFormCollector.construirPrendaDesdeFormulario !== 'function') {
            throw new Error('No se pudo sincronizar la prenda en edición antes de guardar el borrador.');
        }

        if (typeof window.prendaFormCollector.setNotificationService === 'function') {
            window.prendaFormCollector.setNotificationService(gestionItems.notificationService || null);
        }

        const prendaActual = gestionItems.prendas?.[prendaEditIndex] || {};
        const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
            prendaEditIndex,
            Array.isArray(gestionItems.prendas) ? gestionItems.prendas : []
        );

        if (!prendaData) {
            throw new Error('No se pudo recopilar la prenda abierta en edición. Guarda o cierra el modal e intenta nuevamente.');
        }

        gestionItems.prendas[prendaEditIndex] = {
            ...prendaActual,
            ...prendaData,
            id: prendaActual?.id ?? prendaData?.id ?? null,
            prenda_pedido_id: prendaActual?.prenda_pedido_id ?? prendaData?.prenda_pedido_id ?? prendaActual?.id ?? null,
            imagenes: Array.isArray(prendaData?.imagenes) ? prendaData.imagenes : [],
            telasAgregadas: Array.isArray(prendaData?.telasAgregadas) ? prendaData.telasAgregadas : [],
            procesos: (prendaData?.procesos && typeof prendaData.procesos === 'object') ? prendaData.procesos : {},
            asignacionesColoresPorTalla: prendaData?.asignacionesColoresPorTalla || {},
            cantidad_talla: prendaData?.cantidad_talla || {},
            procesos_a_eliminar: Array.isArray(prendaData?.procesos_a_eliminar) ? prendaData.procesos_a_eliminar : (prendaActual?.procesos_a_eliminar || []),
            imagenes_a_eliminar: Array.isArray(prendaData?.imagenes_a_eliminar) ? prendaData.imagenes_a_eliminar : (prendaActual?.imagenes_a_eliminar || [])
        };

        console.debug('[DraftPedidoSerializer] Prenda en edición sincronizada antes de guardar borrador:', {
            prendaEditIndex,
            nombre: gestionItems.prendas[prendaEditIndex]?.nombre_prenda || gestionItems.prendas[prendaEditIndex]?.nombre_producto,
            imagenes: gestionItems.prendas[prendaEditIndex]?.imagenes?.length || 0,
            telas: gestionItems.prendas[prendaEditIndex]?.telasAgregadas?.length || gestionItems.prendas[prendaEditIndex]?.telas?.length || 0,
            procesos: Object.keys(gestionItems.prendas[prendaEditIndex]?.procesos || {}).length
        });

        return true;
    }

    function serializarPrendaExistenteParaBorrador(prenda, prendaIndex, formData) {
        if (!prenda || !formData) {
            return null;
        }

        const prendaId = prenda.prenda_pedido_id || prenda.id || null;
        if (!prendaId) {
            return null;
        }

        const prefijo = `prenda_existente_${prendaIndex}_`;
        const estaPrendaEnEdicion = window.gestionItemsUI?.prendaEditIndex === prendaIndex;

        const agregarArchivo = (clave, archivo) => {
            if (!archivo) return;
            formData.append(`${prefijo}${clave}`, archivo);
        };

        // FIX: prenda.tallas es [] (truthy) de crearPrendaBase() — usar cantidad_talla (fuente canónica relacional)
        const tallasData = (Array.isArray(prenda.cantidad_talla) ? {} : prenda.cantidad_talla) || {};
        const variantes = prenda.variantes && Object.keys(prenda.variantes).length > 0 ? prenda.variantes : null;
        const asignacionesColores = prenda.asignacionesColoresPorTalla !== undefined && prenda.asignacionesColoresPorTalla !== null
            ? prenda.asignacionesColoresPorTalla
            : null;

        const obtenerArchivoDesdeImagen = (imagen) => {
            if (!imagen) return null;
            if (imagen instanceof File) return imagen;
            if (imagen?.file instanceof File) return imagen.file;
            if (typeof imagen === 'string' && imagen.startsWith('blob:')) {
                return convertirBlobUrlAFileSincrono(imagen);
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
                    return convertirBlobUrlAFileSincrono(blobUrl, imagen.nombre || imagen.imagen_nombre || null);
                }
            }
            return null;
        };

        const convertirBlobUrlAFileSincrono = (blobUrl, nombreSugerido = null) => {
            if (typeof blobUrl !== 'string' || !blobUrl.startsWith('blob:')) return null;

            try {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', blobUrl, false);
                xhr.responseType = 'blob';
                xhr.send();

                if (xhr.status !== 200 && xhr.status !== 0) return null;
                const blob = xhr.response;
                if (!(blob instanceof Blob)) return null;

                const extension = blob.type === 'image/png'
                    ? 'png'
                    : blob.type === 'image/webp'
                        ? 'webp'
                        : 'jpg';
                const nombreArchivo = nombreSugerido || `color_wizard_${Date.now()}_${Math.random().toString(36).slice(2, 7)}.${extension}`;

                return new File([blob], nombreArchivo, { type: blob.type || 'image/jpeg' });
            } catch (error) {
                console.warn('[DraftPedidoSerializer] No se pudo convertir blob URL a File:', error);
                return null;
            }
        };

        const obtenerArchivoDesdeColorWizard = (color) => {
            if (!color || typeof color !== 'object') return null;

            const archivoDirecto = obtenerArchivoDesdeImagen(color.imagen);
            if (archivoDirecto) return archivoDirecto;

            const archivoPorRutaBlob = obtenerArchivoDesdeImagen(
                color.imagen_ruta || color.ruta_original || color.ruta_webp || color.url || color.ruta || null
            );
            if (archivoPorRutaBlob) return archivoPorRutaBlob;

            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.getImage === 'function' && color.imagen_id) {
                return obtenerArchivoDesdeImagen(window.ColoresPorTalla.getImage(color.imagen_id));
            }

            return null;
        };

        const normalizarRutaPersistible = (ruta) => {
            if (typeof ruta !== 'string') return null;
            const limpia = ruta.trim();
            if (!limpia) return null;
            if (limpia.startsWith('blob:') || limpia.startsWith('data:')) return null;
            return limpia;
        };

        const telas = prenda.telasAgregadas || prenda.colores_telas || prenda.telas || [];
        const fotosTelaJSON = [];
        const imagenesTelaAgregadas = new Set();
        let indiceFotoTelaArchivo = 0;

        const textoNoVacio = (valor) => {
            if (typeof valor !== 'string') return '';
            return valor.trim();
        };

        const construirPlaceholderFotoTela = (colorTelaId, colorId, telaId, colorNombre = '', telaNombre = '') => {
            const payload = {};
            if (colorTelaId !== null && colorTelaId !== undefined && colorTelaId !== '') {
                payload.prenda_pedido_colores_telas_id = colorTelaId;
            }

            const colorIdNum = Number(colorId || 0);
            if (Number.isFinite(colorIdNum) && colorIdNum > 0) {
                payload.color_id = colorIdNum;
            }

            const telaIdNum = Number(telaId || 0);
            if (Number.isFinite(telaIdNum) && telaIdNum > 0) {
                payload.tela_id = telaIdNum;
            }

            const colorNombreNorm = textoNoVacio(colorNombre);
            if (colorNombreNorm) {
                payload.color_nombre = colorNombreNorm;
            }

            const telaNombreNorm = textoNoVacio(telaNombre);
            if (telaNombreNorm) {
                payload.tela_nombre = telaNombreNorm;
            }

            return payload;
        };

        const tieneRelacionExplicitaFotoTela = (payload) => {
            return !!(
                payload?.prenda_pedido_colores_telas_id ||
                (payload?.color_id && payload?.tela_id)
            );
        };

        const validarRelacionExplicita = (payload, contexto) => {
            if (tieneRelacionExplicitaFotoTela(payload)) {
                return;
            }

            throw new Error(
                `Falta vínculo explícito color/tela para ${contexto}. Esta pantalla usa una sola fuente de verdad.`
            );
        };

        const agregarNuevaFotoTelaDesdeArchivo = (file, contexto = {}) => {
            if (!(file instanceof File)) return;

            const colorTelaId = contexto.colorTelaId ?? null;
            const dedupeBase = contexto.imagenId
                ? String(contexto.imagenId)
                : `${file.name}::${file.size}::${file.lastModified}`;
            const dedupeKey = `${colorTelaId ?? contexto.colorId ?? 'sin_relacion'}::${dedupeBase}`;
            if (imagenesTelaAgregadas.has(dedupeKey)) return;
            imagenesTelaAgregadas.add(dedupeKey);

            agregarArchivo(`fotos_tela[${indiceFotoTelaArchivo}]`, file);
            indiceFotoTelaArchivo++;

            const placeholder = construirPlaceholderFotoTela(
                colorTelaId,
                contexto.colorId,
                contexto.telaId,
                contexto.colorNombre,
                contexto.telaNombre
            );
            validarRelacionExplicita(placeholder, 'una imagen nueva de tela');
            fotosTelaJSON.push(placeholder);
        };

        const telasJSON = telas.map((t) => ({
            tela: t.tela || t.nombre_tela || '',
            color: t.color || t.color_nombre || '',
            referencia: t.referencia || '',
            tela_id: t.tela_id || 0,
            color_id: t.color_id || 0,
            id: t.id || t._original_id || t.prenda_pedido_colores_telas_id || undefined
        }));

        const tieneAsignacionesWizard = !!(
            asignacionesColores
            && typeof asignacionesColores === 'object'
            && !Array.isArray(asignacionesColores)
            && Object.keys(asignacionesColores).length > 0
        );

        if (tieneAsignacionesWizard) {
            Object.entries(asignacionesColores).forEach(([claveAsignacion, asignacion]) => {
                const coloresAsignados = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
                coloresAsignados.forEach((color, idxColor) => {
                    const contextoRelacion = {
                        colorTelaId:
                            color?.prenda_pedido_colores_telas_id ||
                            color?.color_tela_id ||
                            asignacion?.prenda_pedido_colores_telas_id ||
                            asignacion?.color_tela_id ||
                            null,
                        colorId: color?.color_id || asignacion?.color_id || null,
                        telaId: color?.tela_id || asignacion?.tela_id || null,
                        colorNombre: color?.nombre || color?.color || '',
                        telaNombre: asignacion?.tela || ''
                    };

                    const placeholderRelacion = construirPlaceholderFotoTela(
                        contextoRelacion.colorTelaId,
                        contextoRelacion.colorId,
                        contextoRelacion.telaId,
                        contextoRelacion.colorNombre,
                        contextoRelacion.telaNombre
                    );
                    validarRelacionExplicita(
                        placeholderRelacion,
                        `la asignación ${claveAsignacion} (color ${idxColor + 1})`
                    );

                    const archivoColor = obtenerArchivoDesdeColorWizard(color);
                    if (archivoColor) {
                        agregarNuevaFotoTelaDesdeArchivo(archivoColor, {
                            ...contextoRelacion,
                            imagenId: color?.imagen_id || color?.id
                        });
                        return;
                    }

                    const rutaColor = normalizarRutaPersistible(
                        color?.imagen_ruta ||
                        color?.ruta_webp ||
                        color?.ruta_original ||
                        color?.url ||
                        color?.ruta ||
                        color?.imagen ||
                        null
                    );

                    if (rutaColor || color?.id) {
                        fotosTelaJSON.push({
                            ...placeholderRelacion,
                            id: color?.id || undefined,
                            ruta_original: rutaColor || '',
                            ruta_webp: color?.ruta_webp || ''
                        });
                    }
                });
            });
        } else if (Array.isArray(prenda.colores_telas) && prenda.colores_telas.length > 0) {
            // Fuente unica fuera de wizard: relaciones oficiales color/tela desde el payload de la prenda.
            prenda.colores_telas.forEach((colorTela, idxRelacion) => {
                const placeholderBase = construirPlaceholderFotoTela(
                    colorTela?.prenda_pedido_colores_telas_id || colorTela?.id || null,
                    colorTela?.color_id || null,
                    colorTela?.tela_id || null,
                    colorTela?.color_nombre || colorTela?.color || '',
                    colorTela?.tela_nombre || colorTela?.tela || ''
                );

                validarRelacionExplicita(placeholderBase, `la relacion color/tela ${idxRelacion + 1}`);

                const fotosOficiales = Array.isArray(colorTela?.fotos_tela) ? colorTela.fotos_tela : [];
                fotosOficiales.forEach((foto) => {
                    const rutaOriginal = normalizarRutaPersistible(foto?.ruta_original || foto?.url || '');
                    const rutaWebp = normalizarRutaPersistible(foto?.ruta_webp || '');
                    if (rutaOriginal || rutaWebp || foto?.id) {
                        fotosTelaJSON.push({
                            ...placeholderBase,
                            id: foto?.id || undefined,
                            ruta_original: rutaOriginal || '',
                            ruta_webp: rutaWebp || ''
                        });
                    }
                });
            });
        }

        const fotosTelaUnicas = [];
        const fotosTelaKeys = new Set();
        fotosTelaJSON.forEach((f) => {
            if (!f || typeof f !== 'object') return;

            const esPlaceholderSinRuta = !f?.id && !f?.ruta_original && !f?.ruta_webp;
            if (esPlaceholderSinRuta) {
                fotosTelaUnicas.push(f);
                return;
            }

            const key = `${f?.prenda_pedido_colores_telas_id || ''}|${f?.color_id || ''}|${f?.tela_id || ''}|${f?.id || ''}|${f?.ruta_original || ''}|${f?.ruta_webp || ''}`;
            if (!fotosTelaKeys.has(key)) {
                fotosTelaKeys.add(key);
                fotosTelaUnicas.push(f);
            }
        });

        const fotosTelaConVinculo = fotosTelaUnicas.filter((f) => tieneRelacionExplicitaFotoTela(f)).length;

        if (indiceFotoTelaArchivo > 0 && fotosTelaConVinculo < indiceFotoTelaArchivo) {
            throw new Error('Hay imágenes de tela sin vínculo color/tela. Corrige las asignaciones antes de guardar.');
        }

        const placeholdersNuevos = fotosTelaUnicas.filter((f) => !f?.id && !f?.ruta_original && !f?.ruta_webp).length;
        if (placeholdersNuevos !== indiceFotoTelaArchivo) {
            throw new Error('Desfase entre archivos de tela y metadatos fotos_telas. Reabre la asignación y guarda de nuevo.');
        }

        const imagenesExistentes = [];
        const archivosYaAgregados = new Set();
        (Array.isArray(prenda.imagenes) ? prenda.imagenes : []).forEach((img) => {
            const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
            if (file) {
                agregarArchivo('imagenes[]', file);
                archivosYaAgregados.add(file);
                return;
            }

            if (img?.urlDesdeDB || img?.id || img?.url?.startsWith('/') || img?.ruta || img?.ruta_original || img?.ruta_webp || img?.previewUrl?.startsWith('/storage/')) {
                const url = img.url || img.ruta || img.ruta_webp || img.ruta_original || img.previewUrl || '';
                imagenesExistentes.push({ id: img.id, url });
            }
        });

        const procesosAEliminar = Array.isArray(prenda.procesos_a_eliminar)
            ? prenda.procesos_a_eliminar
            : (estaPrendaEnEdicion && window.procesosParaEliminarIds ? Array.from(window.procesosParaEliminarIds) : []);
        const procesosAEliminarSet = new Set(
            procesosAEliminar
                .map((entry) => {
                    if (typeof entry === 'number' || typeof entry === 'string') {
                        return Number(entry);
                    }
                    if (entry && typeof entry === 'object') {
                        return Number(entry.id || entry.proceso_prenda_detalle_id || 0);
                    }
                    return 0;
                })
                .filter((id) => Number.isInteger(id) && id > 0)
        );

        const procesosRaw = prenda.procesos || {};
        const procesosArray = [];
        if (!Array.isArray(procesosRaw) && typeof procesosRaw === 'object') {
            Object.entries(procesosRaw).forEach(([tipo, proc], procesoIdx) => {
                const d = proc?.datos || proc || {};
                const procesoId = Number(d.id || d.proceso_prenda_detalle_id || 0);
                if (procesoId > 0 && procesosAEliminarSet.has(procesoId)) {
                    return;
                }
                const imagenesExistentesProceso = [];
                const imagenesAEliminarProceso = [];
                const archivosProcesoAgregados = new Set();

                // 1) Siempre preservar explícitas las existentes si vienen (fuente canónica)
                if (Array.isArray(d.imagenes_existentes)) {
                    d.imagenes_existentes.forEach((img) => {
                        const url = img?.url || img?.ruta_original || img?.ruta_webp || img?.ruta || img?.previewUrl || '';
                        const id = img?.id || null;
                        if (url || id) {
                            imagenesExistentesProceso.push({
                                id,
                                url,
                                ruta_original: img?.ruta_original || url,
                                ruta_webp: img?.ruta_webp || ''
                            });
                        }
                    });
                }

                // 2) Siempre extraer Files nuevos de TODAS las fuentes temporales de proceso
                const fuentesImagenesProceso = [
                    ...(Array.isArray(d.imagenesFiles) ? d.imagenesFiles : []),
                    ...(Array.isArray(d.fotosGeneralesFiles) ? d.fotosGeneralesFiles : []),
                    ...(Array.isArray(d.imagenes) ? d.imagenes : []),
                ];

                fuentesImagenesProceso.forEach((img) => {
                    const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
                    if (file) {
                        if (archivosProcesoAgregados.has(file)) {
                            return;
                        }
                        archivosProcesoAgregados.add(file);
                        agregarArchivo(`fotosProcesoNuevo_${procesoIdx}[]`, file);
                        return;
                    }

                    // 3) Si vienen URLs en imágenes mixtas, preservarlas también
                    const url = img?.url || img?.ruta_original || img?.ruta_webp || img?.ruta || img?.previewUrl || '';
                    const id = img?.id || null;
                    if (url || id) {
                        imagenesExistentesProceso.push({
                            id,
                            url,
                            ruta_original: img?.ruta_original || url,
                            ruta_webp: img?.ruta_webp || ''
                        });
                    }
                });

                // 4) Deduplicar existentes por id+url
                const existentesUnicos = [];
                const clavesExistentes = new Set();
                imagenesExistentesProceso.forEach((img) => {
                    const key = `${img.id || ''}|${img.url || img.ruta_original || ''}`;
                    if (!clavesExistentes.has(key)) {
                        clavesExistentes.add(key);
                        existentesUnicos.push(img);
                    }
                });

                const eliminadas = d.imagenes_a_eliminar || d.imagenes_eliminadas || d.imagenesEliminadas || [];
                if (Array.isArray(eliminadas)) {
                    eliminadas.forEach(img => {
                        if (!img) return;
                        imagenesAEliminarProceso.push(
                            typeof img === 'string'
                                ? { ruta_original: img, url: img }
                                : {
                                    id: img.id || undefined,
                                    ruta_original: img.ruta_original || img.url || '',
                                    ruta_webp: img.ruta_webp || ''
                                }
                        );
                    });
                }

                if (d.datosExtendidos && typeof d.datosExtendidos === 'object') {
                    Object.entries(d.datosExtendidos).forEach(([genero, tallasDatos]) => {
                        if (!tallasDatos || typeof tallasDatos !== 'object') return;
                        Object.entries(tallasDatos).forEach(([talla, tallaData]) => {
                            if (!Array.isArray(tallaData?.imagenesFiles)) return;
                            tallaData.imagenesFiles.forEach((img) => {
                                const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
                                if (file) {
                                    agregarArchivo(`fotosProcesoTallasNuevo_${procesoIdx}_${genero}_${talla}[]`, file);
                                }
                            });
                        });
                    });
                }

                const procesoEnvio = {
                    id: d.id || undefined,
                    tipo_proceso_id: d.tipo_proceso_id || undefined,
                    tipo: d.tipo || tipo,
                    nombre: d.nombre || tipo,
                    ubicaciones: d.ubicaciones || [],
                    observaciones: d.observaciones || '',
                    modo_tallas: d.modo_tallas || 'generico',
                    tallas: d.tallas || {},
                    datosExtendidos: d.datosExtendidos || {},
                    estado: d.estado || 'PENDIENTE',
                    imagenes_existentes: existentesUnicos
                };

                if (imagenesAEliminarProceso.length > 0) {
                    procesoEnvio.imagenes_a_eliminar = imagenesAEliminarProceso;
                }

                procesosArray.push(procesoEnvio);
            });
        }

        const imagenesAEliminar = Array.isArray(prenda.imagenes_a_eliminar) ? prenda.imagenes_a_eliminar : [];

        console.debug('[DraftPedidoSerializer] Prenda existente serializada', {
            prendaIndex,
            prendaId,
            asignacionesCount: asignacionesColores ? Object.keys(asignacionesColores).length : 0,
            coloresAsignadosTotales: asignacionesColores
                ? Object.values(asignacionesColores).reduce((acc, asig) => acc + (Array.isArray(asig?.colores) ? asig.colores.length : 0), 0)
                : 0,
            coloresConImagenId: asignacionesColores
                ? Object.values(asignacionesColores).reduce((acc, asig) => acc + (Array.isArray(asig?.colores) ? asig.colores.filter((c) => !!c?.imagen_id).length : 0), 0)
                : 0,
            coloresConImagenFile: asignacionesColores
                ? Object.values(asignacionesColores).reduce((acc, asig) => acc + (Array.isArray(asig?.colores) ? asig.colores.filter((c) => !!(c?.imagen?.file instanceof File)).length : 0), 0)
                : 0,
            telasCount: Array.isArray(telasJSON) ? telasJSON.length : 0,
            fotosTelasCount: fotosTelaUnicas.length,
            fotosTelasConRelacion: fotosTelaUnicas.filter((f) =>
                !!(f?.prenda_pedido_colores_telas_id || (f?.color_id && f?.tela_id))
            ).length
        });

        return {
            prenda_id: prendaId,
            nombre_prenda: prenda.nombre_prenda || prenda.nombre_producto || '',
            descripcion: prenda.descripcion || '',
            origen: prenda.origen || (prenda.de_bodega == 1 ? 'bodega' : 'confeccion'),
            de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
            tallas: tallasData,
            variantes,
            asignaciones_colores: asignacionesColores,
            colores_telas: telasJSON,
            fotos_telas: fotosTelaUnicas.length > 0 ? fotosTelaUnicas : undefined,
            procesos: procesosArray,
            imagenes_existentes: imagenesExistentes,
            imagenes_a_eliminar: imagenesAEliminar,
            procesos_a_eliminar: procesosAEliminar
        };
    }

    window.DraftPedidoSerializer = {
        sincronizarPrendaModalAntesDeGuardarBorrador,
        serializarPrendaExistenteParaBorrador
    };

    window.sincronizarPrendaModalAntesDeGuardarBorrador = sincronizarPrendaModalAntesDeGuardarBorrador;
    window.serializarPrendaExistenteParaBorrador = serializarPrendaExistenteParaBorrador;
})();
