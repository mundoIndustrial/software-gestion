/**
 * Utilities for prenda-editor-pedidos-adapter save flow.
 * Exposes: window.PedidosAdapterSaveUtils
 */
(function() {
    'use strict';

    function _mostrarLoadingGuardadoPrenda() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Guardando cambios...',
                text: 'Actualizando la prenda',
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: { container: 'swal-galeria-container' },
                didOpen: () => Swal.showLoading()
            });
        }
    }

    function _appendCamposBasePrenda(formData, prendaId, datos, novedad) {
        formData.append('prenda_id', prendaId);
        formData.append('nombre_prenda', datos.nombre_prenda || datos.nombre || '');
        formData.append('descripcion', datos.descripcion || '');
        formData.append('novedad', novedad);

        const origen = datos.origen || (datos.de_bodega == 1 ? 'bodega' : 'confeccion');
        formData.append('origen', origen);
        formData.append('de_bodega', datos.de_bodega !== undefined ? datos.de_bodega : (origen === 'bodega' ? 1 : 0));

        const tallasData = datos.tallas || datos.cantidad_talla || null;
        if (tallasData && typeof tallasData === 'object' && Object.keys(tallasData).length > 0) {
            const tieneDataReal = Object.values(tallasData).some(genero =>
                typeof genero === 'object' && genero !== null && Object.keys(genero).filter(k => !k.startsWith('_')).length > 0
            );
            if (tieneDataReal) {
                formData.append('tallas', JSON.stringify(tallasData));
            }
        }

        if (datos.variantes && Object.keys(datos.variantes).length > 0) {
            formData.append('variantes', JSON.stringify(datos.variantes));
        }
    }

    function _appendAsignacionesColoresPrenda(formData, datos) {
        const hayAsignacionesParaEnviar = datos.asignacionesColoresPorTalla !== undefined &&
                                          datos.asignacionesColoresPorTalla !== null &&
                                          Object.keys(datos.asignacionesColoresPorTalla).length > 0;
        if (!hayAsignacionesParaEnviar) return;

        formData.append('asignaciones_colores', JSON.stringify(datos.asignacionesColoresPorTalla));
        console.log('[PedidosAdapter] asignaciones_colores enviado (CON CAMBIOS):', JSON.stringify(datos.asignacionesColoresPorTalla).substring(0, 200) + '...');

        if (window.ColoresPorTalla && typeof window.ColoresPorTalla.getImage === 'function') {
            let colorImgIdx = 0;
            Object.entries(datos.asignacionesColoresPorTalla).forEach(([clave, asignacion]) => {
                if (asignacion.colores && Array.isArray(asignacion.colores)) {
                    asignacion.colores.forEach((colorItem) => {
                        if (!colorItem.imagen_id) return;
                        const imgData = window.ColoresPorTalla.getImage(colorItem.imagen_id);
                        if (imgData && imgData.file) {
                            formData.append(`fotos_color[${colorImgIdx}]`, imgData.file);
                            formData.append(`fotos_color_meta[${colorImgIdx}]`, JSON.stringify({
                                clave: clave,
                                color_nombre: colorItem.nombre,
                                imagen_id: colorItem.imagen_id,
                                imagen_nombre: imgData.nombre || imgData.file.name
                            }));
                            colorImgIdx++;
                        }
                    });
                }
            });
            if (colorImgIdx > 0) {
                console.log(`[PedidosAdapter]  Total imagenes de color adjuntadas: ${colorImgIdx}`);
            }
        }
    }

    function _appendTelasPrenda(formData, datos) {
        const telas = datos.telasAgregadas || datos.colores_telas || datos.telas || [];
        const telasJSON = telas.map((t, idx) => {
            const telaData = {
                tela: t.tela || t.nombre_tela || '',
                color: t.color || t.color_nombre || '',
                referencia: t.referencia || '',
                tela_id: t.tela_id || 0,
                color_id: t.color_id || 0,
                id: t.id || t._original_id || undefined,
                prenda_pedido_colores_telas_id: t.prenda_pedido_colores_telas_id || t.id || t._original_id || undefined
            };

            if (t.imagenes && Array.isArray(t.imagenes)) {
                t.imagenes.forEach((img) => {
                    if (img instanceof File) {
                        formData.append(`fotos_tela[${idx}]`, img);
                    } else if (img?.file instanceof File) {
                        formData.append(`fotos_tela[${idx}]`, img.file);
                    }
                });
            }
            return telaData;
        });

        formData.append('colores_telas', JSON.stringify(telasJSON));
        
        // CRÍTICO: Crear metadatos de fotos de telas para que el backend no rechace los archivos
        const fotosTelasMetadata = [];
        telas.forEach((t, telaIdx) => {
            if (t.imagenes && Array.isArray(t.imagenes)) {
                t.imagenes.forEach((img, imgIdx) => {
                    const esArchivo = img instanceof File || img?.file instanceof File;
                    const tieneRutaExistente = img.ruta_original || img.url;
                    
                    if (esArchivo || tieneRutaExistente) {
                        fotosTelasMetadata.push({
                            prenda_pedido_colores_telas_id: t.prenda_pedido_colores_telas_id || t.id || t._original_id,
                            ruta_original: img.ruta_original || img.url || undefined,
                            nombre: img.nombre || (img.file?.name) || 'foto_tela',
                            es_nueva: esArchivo,
                            indice_archivo: esArchivo ? telaIdx : undefined
                        });
                    }
                });
            }
        });

        if (fotosTelasMetadata.length > 0) {
            formData.append('fotosTelas', JSON.stringify(fotosTelasMetadata));
            console.log('[PedidosAdapter]  Metadatos de fotos de telas enviados:', fotosTelasMetadata.length, 'fotos');
        }
        
        console.log('[PedidosAdapter]  Telas enviadas:', telasJSON.length, 'telas');
    }

    function _appendProcesosPrenda(formData, datos) {
        if (window.PedidosAdapterDataUtils && typeof window.PedidosAdapterDataUtils.appendProcesosPrenda === 'function') {
            window.PedidosAdapterDataUtils.appendProcesosPrenda(formData, datos);
            return;
        }
        console.error('[PedidosAdapter] PedidosAdapterDataUtils.appendProcesosPrenda no disponible');
    }

    function _appendImagenesPrendaYEliminaciones(formData, datos) {
        if (window.PedidosAdapterDataUtils && typeof window.PedidosAdapterDataUtils.appendImagenesPrendaYEliminaciones === 'function') {
            window.PedidosAdapterDataUtils.appendImagenesPrendaYEliminaciones(formData, datos);
            return;
        }
        console.error('[PedidosAdapter] PedidosAdapterDataUtils.appendImagenesPrendaYEliminaciones no disponible');
    }

    async function _enviarGuardarPrendaRequest(pedidoId, formData, getUrlPrefix) {
        const urlPrefix = getUrlPrefix();
        const saveUrl = `${urlPrefix.save}/${pedidoId}/actualizar-prenda`;
        console.log('[PedidosAdapter]  Enviando a POST', saveUrl, '(contexto:', urlPrefix.context + ')');

        return fetch(saveUrl, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: formData
        });
    }

    async function _obtenerErrorDeResponse(response) {
        let errorMsg = 'Error desconocido';
        try {
            const error = await response.json();
            errorMsg = error.message || error.error || JSON.stringify(error);
        } catch (e) {
            errorMsg = `HTTP ${response.status}: ${response.statusText}`;
        }
        return errorMsg;
    }

    function _actualizarDatosLocalesPrenda(prendaIndex, datos, result, normalizarDatosBD) {
        if (!(window.datosEdicionPedido?.prendas && prendaIndex !== null && prendaIndex !== undefined)) {
            return;
        }

        const prendaActualizada = result?.prenda && typeof normalizarDatosBD === 'function'
            ? normalizarDatosBD(result.prenda)
            : null;
        if (prendaActualizada) {
            window.datosEdicionPedido.prendas[prendaIndex] = {
                ...window.datosEdicionPedido.prendas[prendaIndex],
                ...prendaActualizada,
            };
            return;
        }

        Object.assign(window.datosEdicionPedido.prendas[prendaIndex], datos);
        if (datos.nombre_prenda) {
            window.datosEdicionPedido.prendas[prendaIndex].nombre = datos.nombre_prenda;
            window.datosEdicionPedido.prendas[prendaIndex].nombre_prenda = datos.nombre_prenda;
        }
    }

    function _mostrarExitoGuardadoPrenda() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: ' Prenda actualizada',
                text: 'Los cambios se guardaron correctamente',
                timer: 1800,
                showConfirmButton: false,
                customClass: { container: 'swal-galeria-container' }
            });
        }
    }

    function _cerrarModalDespuesDeGuardar(getUrlPrefix, cerrarModalPrendaNueva) {
        setTimeout(function() {
            if (typeof cerrarModalPrendaNueva === 'function') {
                cerrarModalPrendaNueva();
            }

            const urlPrefix = getUrlPrefix();
            if (urlPrefix.context === 'supervisor') {
                console.log('[PedidosAdapter]  Recargando pagina de supervisor-pedidos para mostrar cambios');
                setTimeout(() => {
                    window.location.reload();
                }, 200);
            }
        }, 1900);
    }

    function _mostrarErrorGuardadoPrenda(errorMsg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg, customClass: { container: 'swal-galeria-container' } });
        }
    }

    async function guardarPrendaEnAPI(options = {}) {
        const pedidoId = options.pedidoId;
        const prendaId = options.prendaId;
        const datos = options.datos || {};
        const prendaIndex = options.prendaIndex;
        const pedirNovedad = options.pedirNovedad;
        const getUrlPrefix = options.getUrlPrefix;
        const normalizarDatosBD = options.normalizarDatosBD;
        const cerrarModalPrendaNueva = options.cerrarModalPrendaNueva;

        try {
            const novedad = await pedirNovedad();
            if (novedad === null) return;

            _mostrarLoadingGuardadoPrenda();

            const formData = new FormData();
            _appendCamposBasePrenda(formData, prendaId, datos, novedad);
            _appendAsignacionesColoresPrenda(formData, datos);
            _appendTelasPrenda(formData, datos);
            _appendProcesosPrenda(formData, datos);
            _appendImagenesPrendaYEliminaciones(formData, datos);

            const response = await _enviarGuardarPrendaRequest(pedidoId, formData, getUrlPrefix);
            if (!response.ok) {
                const errorMsg = await _obtenerErrorDeResponse(response);
                console.error('[PedidosAdapter] Error API:', errorMsg);
                _mostrarErrorGuardadoPrenda(`No se pudo guardar: ${errorMsg}`);
                return;
            }

            const result = await response.json();
            console.log('[PedidosAdapter]  Prenda guardada:', result);

            _actualizarDatosLocalesPrenda(prendaIndex, datos, result, normalizarDatosBD);
            _mostrarExitoGuardadoPrenda();
            _cerrarModalDespuesDeGuardar(getUrlPrefix, cerrarModalPrendaNueva);
        } catch (error) {
            console.error('[PedidosAdapter] Error de red:', error);
            _mostrarErrorGuardadoPrenda('Error de conexion al guardar la prenda');
        }
    }

    window.PedidosAdapterSaveUtils = {
        guardarPrendaEnAPI
    };
})();
