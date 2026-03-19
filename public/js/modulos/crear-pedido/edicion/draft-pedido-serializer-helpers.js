(function() {
    'use strict';

    /**
     * Sincroniza la prenda abierta en edición en el modal antes de guardar como borrador
     * Extrae datos del formulario modal y actualiza window.gestionItemsUI.prendas[prendaEditIndex]
     * 
     * @returns {boolean} true si la sincronización fue exitosa o no fue necesaria
     * @throws {Error} si no hay datos o servicios requeridos
     */
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
            console.debug('[guardarComoBorrador] Modal de prenda abierto sin índice de edición; se omite sincronización previa.');
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
            cantidad_talla: prendaData?.cantidad_talla || {}
        };

        console.debug('[guardarComoBorrador] Prenda en edición sincronizada antes de guardar borrador:', {
            prendaEditIndex,
            nombre: gestionItems.prendas[prendaEditIndex]?.nombre_prenda || gestionItems.prendas[prendaEditIndex]?.nombre_producto,
            imagenes: gestionItems.prendas[prendaEditIndex]?.imagenes?.length || 0,
            telas: gestionItems.prendas[prendaEditIndex]?.telasAgregadas?.length || gestionItems.prendas[prendaEditIndex]?.telas?.length || 0,
            procesos: Object.keys(gestionItems.prendas[prendaEditIndex]?.procesos || {}).length
        });

        return true;
    }

    /**
     * Serializa una prenda existente para envío al backend como parte de un borrador
     * Procesa imágenes, telas, procesos y sus variantes
     * 
     * @param {Object} prenda - Objeto con datos de la prenda
     * @param {number} prendaIndex - Índice de la prenda en el array
     * @param {FormData} formData - Objeto FormData para agregar archivos adjuntos
     * @returns {Object|null} Objeto serializado listo para envío, o null si hay error
     */
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

        const tallasData = prenda.tallas || prenda.cantidad_talla || {};
        const variantes = prenda.variantes && Object.keys(prenda.variantes).length > 0 ? prenda.variantes : null;
        const asignacionesColores = prenda.asignacionesColoresPorTalla !== undefined && prenda.asignacionesColoresPorTalla !== null
            ? prenda.asignacionesColoresPorTalla
            : null;

        const telas = prenda.telasAgregadas || prenda.colores_telas || prenda.telas || [];
        const telasJSON = telas.map((t, idx) => {
            if (Array.isArray(t.imagenes)) {
                t.imagenes.forEach((img) => {
                    const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
                    if (file) {
                        agregarArchivo(`fotos_tela[${idx}]`, file);
                    }
                });
            }

            return {
                tela: t.tela || t.nombre_tela || '',
                color: t.color || t.color_nombre || '',
                referencia: t.referencia || '',
                tela_id: t.tela_id || 0,
                color_id: t.color_id || 0,
                id: t.id || t._original_id || undefined
            };
        });

        const imagenesExistentes = [];
        (Array.isArray(prenda.imagenes) ? prenda.imagenes : []).forEach((img) => {
            const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
            if (file) {
                agregarArchivo('imagenes[]', file);
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

                if (Array.isArray(d.imagenes_existentes)) {
                    d.imagenes_existentes.forEach(img => {
                        const url = img.url || img.ruta_original || img.ruta_webp || img.ruta || img.previewUrl || '';
                        const id = img.id || null;
                        if (url || id) {
                            imagenesExistentesProceso.push({ id, url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                        }
                    });
                } else if (Array.isArray(d.imagenes)) {
                    d.imagenes.forEach(img => {
                        const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
                        if (file) {
                            agregarArchivo(`fotosProcesoNuevo_${procesoIdx}[]`, file);
                            return;
                        }

                        const url = img?.url || img?.ruta_original || img?.ruta_webp || img?.ruta || img?.previewUrl || '';
                        const id = img?.id || null;
                        if (url || id) {
                            imagenesExistentesProceso.push({ id, url, ruta_original: img.ruta_original || url, ruta_webp: img.ruta_webp || '' });
                        }
                    });
                }

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
                    modoTallas: d.modoTallas || d.modo_tallas || proc?.modoTallas || 'generico',
                    tallas: d.tallas || {},
                    datosExtendidos: d.datosExtendidos || {},
                    estado: d.estado || 'PENDIENTE',
                    imagenes_existentes: imagenesExistentesProceso
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
            procesos: procesosArray,
            imagenes_existentes: imagenesExistentes,
            imagenes_a_eliminar: imagenesAEliminar,
            procesos_a_eliminar: procesosAEliminar
        };
    }

    // Exponemos las funciones globalmente para que el orchestrator las pueda usar
    window.sincronizarPrendaModalAntesDeGuardarBorrador = sincronizarPrendaModalAntesDeGuardarBorrador;
    window.serializarPrendaExistenteParaBorrador = serializarPrendaExistenteParaBorrador;

    console.debug('[draft-pedido-serializer-helpers] Módulo cargado ✅');
})();
