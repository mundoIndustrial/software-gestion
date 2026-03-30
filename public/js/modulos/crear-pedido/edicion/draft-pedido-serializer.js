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

        const telas = prenda.telasAgregadas || prenda.colores_telas || prenda.telas || [];
        const fotosTelaJSON = [];

        const telasJSON = telas.map((t, idx) => {
            const colorTelaId = t.id || t._original_id || t.prenda_pedido_colores_telas_id || null;

            if (Array.isArray(t.imagenes)) {
                t.imagenes.forEach((img) => {
                    const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
                    if (file) {
                        agregarArchivo(`fotos_tela[${idx}]`, file);
                        return;
                    }
                    // Imagen existente guardada en DB — incluir en fotos_telas JSON
                    const ruta = (typeof img === 'string')
                        ? img
                        : (img?.ruta_original || img?.ruta_webp || img?.url || img?.ruta || null);
                    if (colorTelaId && (ruta || img?.id)) {
                        fotosTelaJSON.push({
                            prenda_pedido_colores_telas_id: colorTelaId,
                            id: img?.id || undefined,
                            ruta_original: ruta || '',
                            ruta_webp: img?.ruta_webp || ''
                        });
                    }
                });
            }

            // Fuente de verdad oficial en edición: colores_telas[].fotos_tela[]
            if (colorTelaId && Array.isArray(prenda.colores_telas)) {
                const colorTelaOficial = prenda.colores_telas.find((ct) =>
                    ct && (ct.id === colorTelaId || ct.prenda_pedido_colores_telas_id === colorTelaId)
                );
                if (colorTelaOficial && Array.isArray(colorTelaOficial.fotos_tela)) {
                    colorTelaOficial.fotos_tela.forEach((foto) => {
                        const rutaOriginal = foto?.ruta_original || foto?.url || '';
                        const rutaWebp = foto?.ruta_webp || '';
                        if (rutaOriginal || rutaWebp || foto?.id) {
                            fotosTelaJSON.push({
                                prenda_pedido_colores_telas_id: colorTelaId,
                                id: foto?.id || undefined,
                                ruta_original: rutaOriginal,
                                ruta_webp: rutaWebp
                            });
                        }
                    });
                }
            }

            // Imágenes de tela pre-subidas al servidor desde el modal (ya tienen rutas, no son Files)
            const telasFotosGestor = window.gestorPrendaSinCotizacion?.telasFotosNuevas?.[prendaIndex]?.[idx];
            if (colorTelaId && Array.isArray(telasFotosGestor) && telasFotosGestor.length > 0) {
                telasFotosGestor.forEach(foto => {
                    fotosTelaJSON.push({
                        prenda_pedido_colores_telas_id: colorTelaId,
                        ruta_original: foto.ruta_original || foto.url || '',
                        ruta_webp: foto.ruta_webp || ''
                    });
                });
            }

            return {
                tela: t.tela || t.nombre_tela || '',
                color: t.color || t.color_nombre || '',
                referencia: t.referencia || '',
                tela_id: t.tela_id || 0,
                color_id: t.color_id || 0,
                id: t.id || t._original_id || t.prenda_pedido_colores_telas_id || undefined
            };
        });

        const fotosTelaUnicas = [];
        const fotosTelaKeys = new Set();
        fotosTelaJSON.forEach((f) => {
            const key = `${f?.prenda_pedido_colores_telas_id || ''}|${f?.id || ''}|${f?.ruta_original || ''}|${f?.ruta_webp || ''}`;
            if (!fotosTelaKeys.has(key)) {
                fotosTelaKeys.add(key);
                fotosTelaUnicas.push(f);
            }
        });

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


        const procesosRaw = prenda.procesos || {};
        const procesosArray = [];
        if (!Array.isArray(procesosRaw) && typeof procesosRaw === 'object') {
            Object.entries(procesosRaw).forEach(([tipo, proc], procesoIdx) => {
                const d = proc?.datos || proc || {};
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
        const procesosAEliminar = Array.isArray(prenda.procesos_a_eliminar)
            ? prenda.procesos_a_eliminar
            : (estaPrendaEnEdicion && window.procesosParaEliminarIds ? Array.from(window.procesosParaEliminarIds) : []);

        return {
            prenda_id: prendaId,
            nombre_prenda: prenda.nombre_prenda || prenda.nombre_producto || '',
            descripcion: prenda.descripcion || '',
            origen: prenda.origen || (prenda.de_bodega == 1 ? 'bodega' : 'confeccion'),
            de_bodega: prenda.de_bodega !== undefined ? prenda.de_bodega : 1,
            novedad: 'Actualización desde guardado de borrador',
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
