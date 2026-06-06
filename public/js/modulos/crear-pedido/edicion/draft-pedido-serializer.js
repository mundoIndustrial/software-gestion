(function() {
    'use strict';

    function generarLocalIdTemporalPrenda() {
        return `prenda-local-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    }

    function obtenerLocalIdTemporalModal(modalPrenda) {
        const actual = typeof modalPrenda?.dataset?.draftPrendaLocalId === 'string'
            ? modalPrenda.dataset.draftPrendaLocalId.trim()
            : '';

        if (actual) {
            return actual;
        }

        const nuevo = generarLocalIdTemporalPrenda();
        if (modalPrenda?.dataset) {
            modalPrenda.dataset.draftPrendaLocalId = nuevo;
        }

        return nuevo;
    }

    function convertirTallasArrayAObjeto(tallas) {
        if (!Array.isArray(tallas)) {
            return {};
        }

        const resultado = {};

        tallas.forEach((tallaObj) => {
            if (!tallaObj || typeof tallaObj !== 'object') {
                return;
            }

            const genero = String(tallaObj.genero || '').toUpperCase().trim();
            const talla = String(tallaObj.talla || '').toUpperCase().trim();
            const cantidad = parseInt(tallaObj.cantidad, 10) || 0;
            const esSobremedida = Boolean(tallaObj.es_sobremedida);

            if (!genero || cantidad <= 0) {
                return;
            }

        if (esSobremedida) {
            if (!resultado.SOBREMEDIDA) {
                resultado.SOBREMEDIDA = {};
            }
            resultado.SOBREMEDIDA[genero] = cantidad;
            return;
        }

            if (!resultado[genero]) {
                resultado[genero] = {};
            }

            if (talla) {
                resultado[genero][talla] = cantidad;
            }
        });

        return resultado;
    }

    function resolverCantidadTallaPrenda(prenda = {}) {
        const fuentes = [
            prenda.cantidad_talla,
            prenda.generosConTallas,
            prenda.tallas
        ];

        console.debug('[DraftPedidoSerializer][Tallas] resolverCantidadTallaPrenda entrada', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            cantidad_talla: prenda?.cantidad_talla,
            generosConTallas: prenda?.generosConTallas,
            tallas: prenda?.tallas
        });

        for (const fuente of fuentes) {
            if (!fuente) continue;

            if (typeof fuente === 'string') {
                try {
                    const parsed = JSON.parse(fuente);
                    if (parsed && typeof parsed === 'object') {
                        if (Array.isArray(parsed)) {
                            const convertida = convertirTallasArrayAObjeto(parsed);
                            if (Object.keys(convertida).length > 0) {
                                console.debug('[DraftPedidoSerializer][Tallas] convertido desde array', convertida);
                                return convertida;
                            }
                        } else if (Object.keys(parsed).length > 0) {
                            console.debug('[DraftPedidoSerializer][Tallas] parsed directo', parsed);
                            return parsed;
                        }
                    }
                } catch (error) {
                    continue;
                }
            }

            if (Array.isArray(fuente)) {
                const convertida = convertirTallasArrayAObjeto(fuente);
                if (Object.keys(convertida).length > 0) {
                    console.debug('[DraftPedidoSerializer][Tallas] convertido desde array fuente', convertida);
                    return convertida;
                }
                continue;
            }

            if (typeof fuente === 'object' && Object.keys(fuente).length > 0) {
                console.debug('[DraftPedidoSerializer][Tallas] usando objeto fuente directo', fuente);
                return fuente;
            }
        }

        console.debug('[DraftPedidoSerializer][Tallas] sin datos');
        return {};
    }

    function formularioNuevaPrendaTieneContenido() {
        const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim() || '';
        const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim() || '';
        const imagenes = globalThis.imagenesPrendaStorage?.obtenerImagenes?.() || [];
        const telasCreacion = Array.isArray(globalThis.telasCreacion) ? globalThis.telasCreacion : [];
        const telasAgregadas = Array.isArray(globalThis.telasAgregadas) ? globalThis.telasAgregadas : [];
        const tallasRelacionales = globalThis.tallasRelacionales && typeof globalThis.tallasRelacionales === 'object'
            ? globalThis.tallasRelacionales
            : {};
        const procesosSeleccionados = globalThis.procesosSeleccionados && typeof globalThis.procesosSeleccionados === 'object'
            ? globalThis.procesosSeleccionados
            : {};
        const cantidadSoloSeleccionada = Number(globalThis.cantidadSoloSeleccionada || 0);

        const tieneTallas = Object.values(tallasRelacionales).some((genero) =>
            genero && typeof genero === 'object' && Object.keys(genero).length > 0
        );
        const tieneProcesos = Object.keys(procesosSeleccionados).length > 0;

        return !!(
            nombre ||
            descripcion ||
            imagenes.length > 0 ||
            telasCreacion.length > 0 ||
            telasAgregadas.length > 0 ||
            tieneTallas ||
            tieneProcesos ||
            cantidadSoloSeleccionada > 0
        );
    }

    function clonarMetadatosImagenes(imagenes = []) {
        if (!Array.isArray(imagenes)) {
            return [];
        }

        return imagenes.map((img) => {
            if (!img || typeof img !== 'object') {
                return img;
            }

            return { ...img };
        });
    }

    function obtenerClaveImagen(img) {
        if (!img) {
            return null;
        }

        const id = Number(img.id || img.imagen_id || 0);
        if (Number.isInteger(id) && id > 0) {
            return `id:${id}`;
        }

        const ruta = [
            img.ruta_original,
            img.ruta_webp,
            img.url,
            img.ruta,
            img.previewUrl
        ].find((valor) => typeof valor === 'string' && valor.trim() !== '');

        if (ruta) {
            return `ruta:${ruta.trim()}`;
        }

        return null;
    }

    function normalizarImagenParaEliminar(img) {
        if (!img) {
            return null;
        }

        if (typeof img === 'number' || (typeof img === 'string' && /^\d+$/.test(img.trim()))) {
            const id = Number(img);
            return Number.isInteger(id) && id > 0 ? { id } : null;
        }

        if (typeof img === 'string') {
            const ruta = img.trim();
            return ruta ? { ruta_original: ruta, url: ruta } : null;
        }

        if (typeof img !== 'object') {
            return null;
        }

        const normalizada = {};
        const id = Number(img.id || img.imagen_id || 0);
        if (Number.isInteger(id) && id > 0) {
            normalizada.id = id;
        }

        const rutaOriginal = typeof img.ruta_original === 'string' ? img.ruta_original.trim() : '';
        const rutaWebp = typeof img.ruta_webp === 'string' ? img.ruta_webp.trim() : '';
        const url = typeof img.url === 'string' ? img.url.trim() : '';
        const previewUrl = typeof img.previewUrl === 'string' ? img.previewUrl.trim() : '';

        if (rutaOriginal) normalizada.ruta_original = rutaOriginal;
        if (rutaWebp) normalizada.ruta_webp = rutaWebp;
        if (url) normalizada.url = url;
        if (!normalizada.ruta_original && previewUrl && !previewUrl.startsWith('blob:')) {
            normalizada.ruta_original = previewUrl;
        }

        return Object.keys(normalizada).length > 0 ? normalizada : null;
    }

    function resolverImagenesAEliminarPrenda(prenda) {
        const imagenesOriginales = Array.isArray(prenda?._imagenes_originales)
            ? prenda._imagenes_originales
            : [];
        const imagenesActuales = Array.isArray(prenda?.imagenes)
            ? prenda.imagenes
            : [];
        const imagenesMarcadas = Array.isArray(prenda?.imagenes_a_eliminar)
            ? prenda.imagenes_a_eliminar
            : [];

        const clavesActuales = new Set(
            imagenesActuales
                .map((img) => obtenerClaveImagen(img))
                .filter(Boolean)
        );

        const detectadasPorDiferencia = imagenesOriginales
            .filter((img) => {
                const clave = obtenerClaveImagen(img);
                return clave && !clavesActuales.has(clave);
            })
            .map((img) => normalizarImagenParaEliminar(img))
            .filter(Boolean);

        const combinadas = [...imagenesMarcadas, ...detectadasPorDiferencia]
            .map((img) => normalizarImagenParaEliminar(img))
            .filter(Boolean);

        const imagenesAEliminar = [];
        const clavesEliminacion = new Set();
        combinadas.forEach((img) => {
            const clave = obtenerClaveImagen(img);
            if (!clave || clavesEliminacion.has(clave)) {
                return;
            }

            clavesEliminacion.add(clave);
            imagenesAEliminar.push(img);
        });

        return {
            imagenesOriginales,
            imagenesActuales,
            imagenesAEliminar
        };
    }

    function obtenerClaveRelacionFotoTela(foto) {
        if (!foto || typeof foto !== 'object') {
            return null;
        }

        const colorTelaId = Number(foto.prenda_pedido_colores_telas_id || foto.color_tela_id || 0);
        if (Number.isInteger(colorTelaId) && colorTelaId > 0) {
            return `color-tela:${colorTelaId}`;
        }

        const colorId = Number(foto.color_id || 0);
        const telaId = Number(foto.tela_id || 0);
        if (Number.isInteger(colorId) && colorId > 0 && Number.isInteger(telaId) && telaId > 0) {
            return `color:${colorId}|tela:${telaId}`;
        }

        return null;
    }

    function construirPlaceholderEliminacionFotoTela(fotoOriginal) {
        const claveRelacion = obtenerClaveRelacionFotoTela(fotoOriginal);
        if (!claveRelacion) {
            return null;
        }

        return {
            prenda_pedido_colores_telas_id: fotoOriginal?.prenda_pedido_colores_telas_id || fotoOriginal?.color_tela_id || null,
            color_id: fotoOriginal?.color_id || null,
            tela_id: fotoOriginal?.tela_id || null,
            color_nombre: fotoOriginal?.color_nombre || fotoOriginal?.color || '',
            tela_nombre: fotoOriginal?.tela_nombre || fotoOriginal?.tela || ''
        };
    }

    function resolverFotosTelasParaGuardar(prenda, fotosTelaActuales) {
        const fotosTelasOriginales = Array.isArray(prenda?._fotos_telas_originales)
            ? prenda._fotos_telas_originales
            : [];
        const fotosTelaConRuta = Array.isArray(fotosTelaActuales)
            ? fotosTelaActuales.filter((foto) => !!(foto?.id || foto?.ruta_original || foto?.ruta_webp))
            : [];

        const relacionesActuales = new Set(
            fotosTelaConRuta
                .map((foto) => obtenerClaveRelacionFotoTela(foto))
                .filter(Boolean)
        );

        const placeholdersEliminacion = [];
        const relacionesMarcadas = new Set();
        fotosTelasOriginales.forEach((fotoOriginal) => {
            const claveRelacion = obtenerClaveRelacionFotoTela(fotoOriginal);
            if (!claveRelacion || relacionesActuales.has(claveRelacion) || relacionesMarcadas.has(claveRelacion)) {
                return;
            }

            const placeholder = construirPlaceholderEliminacionFotoTela(fotoOriginal);
            if (!placeholder) {
                return;
            }

            relacionesMarcadas.add(claveRelacion);
            placeholdersEliminacion.push(placeholder);
        });

        return {
            fotosTelasFinales: [...(Array.isArray(fotosTelaActuales) ? fotosTelaActuales : []), ...placeholdersEliminacion],
            placeholdersEliminacion,
            fotosTelasOriginales
        };
    }

    function resolverImagenesAEliminarProceso(datosProceso, imagenesExistentesProceso, imagenesMarcadasProceso) {
        const imagenesOriginales = Array.isArray(datosProceso?._imagenes_originales)
            ? datosProceso._imagenes_originales
            : [];
        const clavesActuales = new Set(
            (Array.isArray(imagenesExistentesProceso) ? imagenesExistentesProceso : [])
                .map((img) => obtenerClaveImagen(img))
                .filter(Boolean)
        );

        const detectadasPorDiferencia = imagenesOriginales
            .filter((img) => {
                const clave = obtenerClaveImagen(img);
                return clave && !clavesActuales.has(clave);
            })
            .map((img) => normalizarImagenParaEliminar(img))
            .filter(Boolean);

        const combinadas = [...(Array.isArray(imagenesMarcadasProceso) ? imagenesMarcadasProceso : []), ...detectadasPorDiferencia]
            .map((img) => normalizarImagenParaEliminar(img))
            .filter(Boolean);

        const imagenesAEliminar = [];
        const clavesEliminacion = new Set();
        combinadas.forEach((img) => {
            const clave = obtenerClaveImagen(img);
            if (!clave || clavesEliminacion.has(clave)) {
                return;
            }

            clavesEliminacion.add(clave);
            imagenesAEliminar.push(img);
        });

        return {
            imagenesOriginales,
            imagenesAEliminar
        };
    }

    function sincronizarPrendaModalAntesDeGuardarBorrador() {
        const modalPrenda = document.getElementById('modal-agregar-prenda-nueva');
        const modalWizardColores = document.getElementById('modal-asignar-colores-por-talla');
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

        const estiloWizard = (modalWizardColores && window.getComputedStyle)
            ? window.getComputedStyle(modalWizardColores)
            : null;
        const wizardVisible = !!(
            modalWizardColores
            && (
                modalWizardColores.style.display === 'block'
                || modalWizardColores.classList.contains('show')
                || (estiloWizard && estiloWizard.display !== 'none')
            )
        );

        // Si el wizard sigue abierto, forzar guardado de asignaciones para no perder imágenes
        // que todavía están en inputs temporales (.imagen-tela-wizard).
        if (wizardVisible && window.ColoresPorTalla && typeof window.ColoresPorTalla.wizardGuardarAsignacion === 'function') {
            const guardadoWizard = window.ColoresPorTalla.wizardGuardarAsignacion();
            if (!guardadoWizard) {
                throw new Error('No se pudieron guardar las asignaciones de colores/telas antes de guardar el borrador.');
            }
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
        const prendasActuales = Array.isArray(gestionItems.prendas) ? gestionItems.prendas : [];
        const prendaLocalId = prendaActual?._local_id || null;
        const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
            prendaEditIndex,
            prendasActuales,
            prendaLocalId
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
            // No heredar imagenes_a_eliminar antiguas para evitar borrados involuntarios.
            imagenes_a_eliminar: Array.isArray(prendaData?.imagenes_a_eliminar) ? prendaData.imagenes_a_eliminar : []
        };

        console.debug('[DraftPedidoSerializer] Prenda en edición sincronizada antes de guardar borrador:', {
            prendaEditIndex,
            nombre: gestionItems.prendas[prendaEditIndex]?.nombre_prenda || gestionItems.prendas[prendaEditIndex]?.nombre_producto,
            imagenes: gestionItems.prendas[prendaEditIndex]?.imagenes?.length || 0,
            telas: gestionItems.prendas[prendaEditIndex]?.telasAgregadas?.length || gestionItems.prendas[prendaEditIndex]?.telas?.length || 0,
            procesos: Object.keys(gestionItems.prendas[prendaEditIndex]?.procesos || {}).length,
            cantidad_talla: gestionItems.prendas[prendaEditIndex]?.cantidad_talla,
            tallas: gestionItems.prendas[prendaEditIndex]?.tallas,
            generosConTallas: gestionItems.prendas[prendaEditIndex]?.generosConTallas
        });

        return true;
    }

    async function serializarPrendaExistenteParaBorrador(prenda, prendaIndex, formData) {
        if (!prenda || !formData) {
            return null;
        }

        const prendaId = prenda.prenda_pedido_id || prenda.id || null;
        if (!prendaId) {
            return null;
        }

        const prefijo = `prenda_existente_${prendaIndex}_`;
        const prendaArrayIndex = Array.isArray(window.gestionItemsUI?.prendas)
            ? window.gestionItemsUI.prendas.findIndex((item) => {
                if (!item || typeof item !== 'object') {
                    return false;
                }

                if (item === prenda) {
                    return true;
                }

                const itemId = item.prenda_pedido_id || item.id || null;
                if (itemId !== null && itemId !== undefined && String(itemId) === String(prendaId)) {
                    return true;
                }

                const itemLocalId = typeof item._local_id === 'string' ? item._local_id.trim() : '';
                const prendaLocalId = typeof prenda._local_id === 'string' ? prenda._local_id.trim() : '';
                return !!(itemLocalId && prendaLocalId && itemLocalId === prendaLocalId);
            })
            : -1;
        const estaPrendaEnEdicion = prendaArrayIndex >= 0 && window.gestionItemsUI?.prendaEditIndex === prendaArrayIndex;

        const agregarArchivo = (clave, archivo) => {
            if (!archivo) return;
            formData.append(`${prefijo}${clave}`, archivo);
        };

        // FIX: prenda.tallas es [] (truthy) de crearPrendaBase() — usar cantidad_talla (fuente canónica relacional)
        const tallasData = resolverCantidadTallaPrenda(prenda);
        console.debug('[DraftPedidoSerializer][Tallas] tallasData final', {
            prenda_id: prenda?.id || prenda?.prenda_pedido_id || null,
            tallasData
        });
        const variantes = prenda.variantes && Object.keys(prenda.variantes).length > 0 ? prenda.variantes : null;
        const asignacionesColores = prenda.asignacionesColoresPorTalla !== undefined && prenda.asignacionesColoresPorTalla !== null
            ? prenda.asignacionesColoresPorTalla
            : null;

        const obtenerArchivoDesdeImagenAsync = async (imagen) => {
            if (!imagen) return null;
            if (imagen instanceof File) return imagen;
            if (imagen?.file instanceof File) return imagen.file;
            if (typeof imagen === 'string' && imagen.startsWith('blob:')) {
                return await convertirBlobUrlAArchivo(imagen);
            }
            if (typeof imagen === 'string' && imagen.startsWith('data:image/')) {
                return convertirDataUrlAArchivo(imagen);
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
                    return await convertirBlobUrlAArchivo(blobUrl, imagen.nombre || imagen.imagen_nombre || null);
                }

                const dataUrl =
                    (typeof imagen.previewUrl === 'string' && imagen.previewUrl.startsWith('data:image/') && imagen.previewUrl) ||
                    (typeof imagen.url === 'string' && imagen.url.startsWith('data:image/') && imagen.url) ||
                    (typeof imagen.ruta === 'string' && imagen.ruta.startsWith('data:image/') && imagen.ruta) ||
                    (typeof imagen.ruta_original === 'string' && imagen.ruta_original.startsWith('data:image/') && imagen.ruta_original) ||
                    (typeof imagen.imagen_ruta === 'string' && imagen.imagen_ruta.startsWith('data:image/') && imagen.imagen_ruta) ||
                    null;

                if (dataUrl) {
                    return convertirDataUrlAArchivo(dataUrl, imagen.nombre || imagen.imagen_nombre || null);
                }
            }
            return null;
        };

        const convertirBlobUrlAArchivo = async (blobUrl, nombreSugerido = null) => {
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
                console.warn('[DraftPedidoSerializer] No se pudo convertir blob URL a File:', error);
                return null;
            }
        };

        const convertirDataUrlAArchivo = (dataUrl, nombreSugerido = null) => {
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
                console.warn('[DraftPedidoSerializer] No se pudo convertir data URL a File:', error);
                return null;
            }
        };

        const obtenerArchivoDesdeColorWizardAsync = async (color) => {
            if (!color || typeof color !== 'object') return null;

            const archivoDirecto = await obtenerArchivoDesdeImagenAsync(color.imagen);
            if (archivoDirecto) return archivoDirecto;

            const archivoPorRutaBlob = await obtenerArchivoDesdeImagenAsync(
                color.imagen_ruta || color.ruta_original || color.ruta_webp || color.url || color.ruta || null
            );
            if (archivoPorRutaBlob) return archivoPorRutaBlob;

            if (window.ColoresPorTalla && typeof window.ColoresPorTalla.getImage === 'function' && color.imagen_id) {
                return await obtenerArchivoDesdeImagenAsync(window.ColoresPorTalla.getImage(color.imagen_id));
            }

            return null;
        };

        const normalizarRutaPersistible = (ruta) => {
            if (typeof ruta !== 'string') return null;
            const limpia = ruta.trim();
            if (!limpia) return null;
            if (limpia.startsWith('blob:') || limpia.startsWith('data:')) return null;

            const sinBackslashes = limpia.replace(/\\/g, '/');
            const matchUrlStorage = sinBackslashes.match(/^https?:\/\/[^/]+\/storage\/(.+)$/i);
            if (matchUrlStorage?.[1]) {
                return matchUrlStorage[1];
            }

            if (sinBackslashes.startsWith('/storage/')) {
                return sinBackslashes.slice('/storage/'.length);
            }

            if (sinBackslashes.startsWith('storage/')) {
                return sinBackslashes.slice('storage/'.length);
            }

            return sinBackslashes.replace(/^\/+/, '');
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
        const coloresTelasOficiales = Array.isArray(prenda.colores_telas) ? prenda.colores_telas : [];

        const normalizarNombreRelacion = (valor) => {
            if (typeof valor !== 'string') return '';
            return valor
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toUpperCase();
        };
        const extraerTokensColor = (valor) => {
            const normalizado = normalizarNombreRelacion(valor);
            if (!normalizado) return [];
            return normalizado
                .split(',')
                .map((v) => v.trim())
                .filter(Boolean);
        };

        const aNumeroPositivo = (valor) => {
            const num = Number(valor || 0);
            return Number.isFinite(num) && num > 0 ? num : null;
        };

        const resolverIdCatalogoPorNombre = (tipo, nombre) => {
            const nombreNormalizado = normalizarNombreRelacion(nombre);
            if (!nombreNormalizado) return null;

            const datalistId = tipo === 'color' ? 'opciones-colores' : 'opciones-telas';
            const datalist = document.getElementById(datalistId);
            if (!datalist) return null;

            const opcion = Array.from(datalist.querySelectorAll('option')).find((item) => {
                const valor = normalizarNombreRelacion(item.value || '');
                return valor === nombreNormalizado;
            });

            if (!opcion) return null;

            return aNumeroPositivo(opcion.getAttribute('data-id'));
        };

        const resolverRelacionExplicita = (contexto, color, asignacion) => {
            const relacionYaExplicita = aNumeroPositivo(contexto?.colorTelaId)
                || (aNumeroPositivo(contexto?.colorId) && aNumeroPositivo(contexto?.telaId));
            if (relacionYaExplicita) {
                return contexto;
            }

            const colorNombre = normalizarNombreRelacion(
                contexto?.colorNombre || color?.nombre || color?.color || ''
            );
            const telaNombre = normalizarNombreRelacion(
                contexto?.telaNombre || asignacion?.tela || ''
            );

            const buscarEnColeccion = (coleccion, extractor) => {
                if (!Array.isArray(coleccion) || coleccion.length === 0) return null;

                const encontradaExacta = coleccion.find((item) => {
                    const datos = extractor(item);
                    if (!datos) return false;

                    const colorCoincide = colorNombre && (
                        datos.colorNombre === colorNombre ||
                        (Array.isArray(datos.coloresTokens) && datos.coloresTokens.includes(colorNombre))
                    );
                    const telaCoincide = telaNombre && datos.telaNombre === telaNombre;

                    if (colorNombre && telaNombre) return colorCoincide && telaCoincide;
                    if (colorNombre) return colorCoincide;
                    if (telaNombre) return telaCoincide;
                    return false;
                });
                if (encontradaExacta) {
                    return extractor(encontradaExacta);
                }

                return null;
            };

            const oficial = buscarEnColeccion(coloresTelasOficiales, (item) => {
                const colorId = aNumeroPositivo(item?.color_id);
                const telaId = aNumeroPositivo(item?.tela_id);
                const colorTelaId = aNumeroPositivo(item?.prenda_pedido_colores_telas_id || item?.id);
                if (!colorTelaId && !(colorId && telaId)) return null;
                const colorNormalizado = normalizarNombreRelacion(item?.color_nombre || item?.color || '');

                return {
                    colorTelaId,
                    colorId,
                    telaId,
                    colorNombre: colorNormalizado,
                    coloresTokens: extraerTokensColor(item?.color_nombre || item?.color || ''),
                    telaNombre: normalizarNombreRelacion(item?.tela_nombre || item?.tela || '')
                };
            });
            if (oficial) {
                return {
                    ...contexto,
                    colorTelaId: contexto?.colorTelaId || oficial.colorTelaId || null,
                    colorId: contexto?.colorId || oficial.colorId || null,
                    telaId: contexto?.telaId || oficial.telaId || null
                };
            }

            const desdeTelas = buscarEnColeccion(telasJSON, (item) => {
                const colorId = aNumeroPositivo(item?.color_id);
                const telaId = aNumeroPositivo(item?.tela_id);
                const colorTelaId = aNumeroPositivo(item?.id);
                if (!colorTelaId && !(colorId && telaId)) return null;
                const colorNormalizado = normalizarNombreRelacion(item?.color || '');

                return {
                    colorTelaId,
                    colorId,
                    telaId,
                    colorNombre: colorNormalizado,
                    coloresTokens: extraerTokensColor(item?.color || ''),
                    telaNombre: normalizarNombreRelacion(item?.tela || '')
                };
            });
            if (desdeTelas) {
                return {
                    ...contexto,
                    colorTelaId: contexto?.colorTelaId || desdeTelas.colorTelaId || null,
                    colorId: contexto?.colorId || desdeTelas.colorId || null,
                    telaId: contexto?.telaId || desdeTelas.telaId || null
                };
            }

            const colorIdCatalogo = aNumeroPositivo(contexto?.colorId)
                || resolverIdCatalogoPorNombre('color', contexto?.colorNombre || color?.nombre || color?.color || '');
            const telaIdCatalogo = aNumeroPositivo(contexto?.telaId)
                || resolverIdCatalogoPorNombre('tela', contexto?.telaNombre || asignacion?.tela || '');

            return {
                ...contexto,
                colorId: colorIdCatalogo || contexto?.colorId || null,
                telaId: telaIdCatalogo || contexto?.telaId || null
            };
        };

        const tieneAsignacionesWizard = !!(
            asignacionesColores
            && typeof asignacionesColores === 'object'
            && !Array.isArray(asignacionesColores)
            && Object.keys(asignacionesColores).length > 0
        );

        if (tieneAsignacionesWizard) {
            for (const [claveAsignacion, asignacion] of Object.entries(asignacionesColores)) {
                const coloresAsignados = Array.isArray(asignacion?.colores) ? asignacion.colores : [];
                for (let idxColor = 0; idxColor < coloresAsignados.length; idxColor++) {
                    const color = coloresAsignados[idxColor];
                    const contextoBase = {
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
                    const contextoRelacion = resolverRelacionExplicita(contextoBase, color, asignacion);

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

                    const archivoColor = await obtenerArchivoDesdeColorWizardAsync(color);
                    if (archivoColor) {
                        agregarNuevaFotoTelaDesdeArchivo(archivoColor, {
                            ...contextoRelacion,
                            imagenId: color?.imagen_id || color?.id
                        });
                        continue;
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
                }
            }
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

                const fotosOficiales = Array.isArray(colorTela?.fotos) ? colorTela.fotos : (Array.isArray(colorTela?.fotos_tela) ? colorTela.fotos_tela : []);
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

        const {
            fotosTelasFinales,
            placeholdersEliminacion: fotosTelasEliminadasPorCompleto,
            fotosTelasOriginales
        } = resolverFotosTelasParaGuardar(prenda, fotosTelaUnicas);

        // 🔧 NUEVO: Distinción clara entre imágenes nuevas (Files) y existentes (URLs de BD)
        const imagenesNuevas = [];
        const imagenesExistentes = [];
        const archivosYaAgregados = new Set();

        (Array.isArray(prenda.imagenes) ? prenda.imagenes : []).forEach((img) => {
            // 1️⃣ ¿Es un File nuevo?
            const file = img instanceof File ? img : (img?.file instanceof File ? img.file : null);
            if (file) {
                agregarArchivo('imagenes[]', file);
                imagenesNuevas.push(file);
                archivosYaAgregados.add(file);
                return;
            }

            // 2️⃣ ¿Es URL de BD? (tiene id + url válida)
            const tieneId = img?.id || img?.urlDesdeDB;
            const tieneUrl = img?.url?.startsWith('/') || img?.ruta || img?.ruta_original || img?.ruta_webp || img?.previewUrl?.startsWith('/storage/');

            if (tieneId && tieneUrl) {
                const url = img.ruta_original || img.ruta || img.url || img.ruta_webp || img.previewUrl || '';
                imagenesExistentes.push({
                    id: img.id,
                    ruta_original: img.ruta_original || url,
                    ruta_webp: img.ruta_webp || null,
                    urlDesdeDB: true
                });
                return;
            }

            // 3️⃣ Si no es ni File ni URL válida, registrar y omitir
            console.warn('[DraftPedidoSerializer] Imagen descartada (no es File ni URL de BD):', {
                tipo: typeof img,
                tieneId: !!(img?.id),
                urlParcial: (img?.url || img?.ruta || '').substring(0, 50)
            });
        });

        const procesosAEliminar = Array.isArray(prenda.procesos_a_eliminar)
            ? prenda.procesos_a_eliminar
            : (estaPrendaEnEdicion && window.procesosParaEliminarIds instanceof Set ? Array.from(window.procesosParaEliminarIds) : []);
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

                const {
                    imagenesOriginales: imagenesOriginalesProceso,
                    imagenesAEliminar: imagenesAEliminarProcesoResueltas
                } = resolverImagenesAEliminarProceso(d, existentesUnicos, eliminadas);

                imagenesAEliminarProceso.push(...imagenesAEliminarProcesoResueltas);

                if (imagenesAEliminarProceso.length > 0) {
                    procesoEnvio.imagenes_a_eliminar = imagenesAEliminarProceso;
                }

                if (imagenesOriginalesProceso.length > 0) {
                    console.debug('[DraftPedidoSerializer] Proceso serializado con diff de imagenes', {
                        prendaId,
                        proceso: tipo,
                        imagenesOriginales: imagenesOriginalesProceso.map((img) => obtenerClaveImagen(img)).filter(Boolean),
                        imagenesActuales: existentesUnicos.map((img) => obtenerClaveImagen(img)).filter(Boolean),
                        imagenesAEliminar: imagenesAEliminarProceso.map((img) => obtenerClaveImagen(img)).filter(Boolean)
                    });
                }

                procesosArray.push(procesoEnvio);
            });
        }

        const {
            imagenesOriginales,
            imagenesActuales,
            imagenesAEliminar
        } = resolverImagenesAEliminarPrenda(prenda);

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
            ).length,
            fotosTelasOriginales: fotosTelasOriginales.length,
            fotosTelasEliminadasPorCompleto: fotosTelasEliminadasPorCompleto.map((foto) => obtenerClaveRelacionFotoTela(foto)).filter(Boolean),
            imagenesOriginales: imagenesOriginales.map((img) => obtenerClaveImagen(img)).filter(Boolean),
            imagenesActuales: imagenesActuales.map((img) => obtenerClaveImagen(img)).filter(Boolean),
            imagenesAEliminar: imagenesAEliminar.map((img) => obtenerClaveImagen(img)).filter(Boolean)
        });

        // 🔧 NUEVO: imagenes_existentes como JSON string (preserva URLs de BD)
        const imagenes_existentes_json = imagenesExistentes.length > 0 ? JSON.stringify(imagenesExistentes) : undefined;

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
            fotos_telas: fotosTelasFinales.length > 0 ? fotosTelasFinales : undefined,
            procesos: procesosArray,
            // 🔧 Enviar como JSON string para preservar en backend
            imagenes_existentes: imagenes_existentes_json,
            imagenes_a_eliminar: imagenesAEliminar,
            procesos_a_eliminar: procesosAEliminar
        };
    }

    function sincronizarPrendaModalAntesDeGuardarBorrador() {
        const modalPrenda = document.getElementById('modal-agregar-prenda-nueva');
        const modalWizardColores = document.getElementById('modal-asignar-colores-por-talla');
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

        const estiloWizard = (modalWizardColores && window.getComputedStyle)
            ? window.getComputedStyle(modalWizardColores)
            : null;
        const wizardVisible = !!(
            modalWizardColores
            && (
                modalWizardColores.style.display === 'block'
                || modalWizardColores.classList.contains('show')
                || (estiloWizard && estiloWizard.display !== 'none')
            )
        );

        if (wizardVisible && window.ColoresPorTalla && typeof window.ColoresPorTalla.wizardGuardarAsignacion === 'function') {
            const guardadoWizard = window.ColoresPorTalla.wizardGuardarAsignacion();
            if (!guardadoWizard) {
                throw new Error('No se pudieron guardar las asignaciones de colores/telas antes de guardar el borrador.');
            }
        }

        if (!window.prendaFormCollector && typeof PrendaFormCollector !== 'undefined') {
            window.prendaFormCollector = new PrendaFormCollector();
        }

        if (!window.prendaFormCollector || typeof window.prendaFormCollector.construirPrendaDesdeFormulario !== 'function') {
            throw new Error('No se pudo sincronizar la prenda del modal antes de guardar el borrador.');
        }

        if (typeof window.prendaFormCollector.setNotificationService === 'function') {
            window.prendaFormCollector.setNotificationService(gestionItems.notificationService || null);
        }

        const prendaEditIndex = gestionItems.prendaEditIndex;
        const estaEditandoPrenda = prendaEditIndex !== null && prendaEditIndex !== undefined;
        const prendasActuales = Array.isArray(gestionItems.prendas) ? gestionItems.prendas : [];

        if (!estaEditandoPrenda && !formularioNuevaPrendaTieneContenido()) {
            console.debug('[DraftPedidoSerializer] Modal de nueva prenda abierto sin contenido relevante; se omite sincronizacion previa.');
            return true;
        }

        // ✅ CRÍTICO: Establecer contexto en IndexedImageStorageService ANTES de recolectar imágenes
        // Esto asegura que se obtengan las imágenes del almacenamiento correcto
        if (globalThis.imagenesPrendaStorage && typeof globalThis.imagenesPrendaStorage.setPrendaActual === 'function') {
            let prendaLocalId = null;
        if (estaEditandoPrenda) {
                // Para prenda existente: usar el índice
                globalThis.imagenesPrendaStorage.setPrendaActual(prendaEditIndex);
                prendaLocalId = prendasActuales?.[prendaEditIndex]?._local_id || null;
                console.log('[DraftPedidoSerializer] Contexto de prenda existente establecido:', prendaEditIndex);
            } else {
                // Para prenda nueva: usar el _local_id si existe, o generar uno
                const localIdTemporal = obtenerLocalIdTemporalModal(modalPrenda);
                globalThis.imagenesPrendaStorage.setPrendaActual(localIdTemporal);
                prendaLocalId = localIdTemporal;
                console.log('[DraftPedidoSerializer] Contexto de prenda nueva establecido:', localIdTemporal);
            }
        }

        const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
            estaEditandoPrenda ? prendaEditIndex : null,
            prendasActuales,
            prendaLocalId
        );

        if (!prendaData) {
            if (!estaEditandoPrenda) {
                throw new Error('Hay una prenda nueva abierta en el formulario. Completala, agregala a la lista o cierra el modal antes de guardar el borrador.');
            }

            throw new Error('No se pudo recopilar la prenda abierta en edicion. Guarda o cierra el modal e intenta nuevamente.');
        }

        if (!estaEditandoPrenda) {
            const localIdTemporal = obtenerLocalIdTemporalModal(modalPrenda);
            prendaData._local_id = prendaData._local_id || localIdTemporal;

            const indiceExistente = prendasActuales.findIndex((item) => {
                const itemLocalId = typeof item?._local_id === 'string' ? item._local_id.trim() : '';
                return itemLocalId && itemLocalId === prendaData._local_id;
            });

            if (indiceExistente >= 0) {
                const prendaAnterior = prendasActuales[indiceExistente] || {};
                gestionItems.prendas[indiceExistente] = {
                    ...prendaAnterior,
                    ...prendaData,
                    _local_id: prendaAnterior?._local_id || prendaData._local_id,
                    id: prendaAnterior?.id ?? prendaData?.id ?? null,
                    prenda_pedido_id: prendaAnterior?.prenda_pedido_id ?? prendaData?.prenda_pedido_id ?? null,
                    _imagenes_originales: Array.isArray(prendaAnterior?._imagenes_originales)
                        ? clonarMetadatosImagenes(prendaAnterior._imagenes_originales)
                        : clonarMetadatosImagenes(prendaAnterior?.imagenes)
                };

                console.debug('[DraftPedidoSerializer] Nueva prenda del modal sincronizada sobre registro temporal existente antes de guardar borrador:', {
                    indiceExistente,
                    nombre: gestionItems.prendas[indiceExistente]?.nombre_prenda || gestionItems.prendas[indiceExistente]?.nombre_producto,
                    local_id: gestionItems.prendas[indiceExistente]?._local_id || null
                });
                return true;
            }

            if (typeof gestionItems.agregarPrendaAlOrden === 'function') {
                gestionItems.agregarPrendaAlOrden(prendaData);
            } else {
                gestionItems.prendas.push(prendaData);
            }

            console.debug('[DraftPedidoSerializer] Nueva prenda abierta en modal sincronizada antes de guardar borrador:', {
                nombre: prendaData?.nombre_prenda || prendaData?.nombre_producto,
                local_id: prendaData?._local_id || null,
                imagenes: Array.isArray(prendaData?.imagenes) ? prendaData.imagenes.length : 0
            });
            return true;
        }

        const prendaActual = gestionItems.prendas?.[prendaEditIndex] || {};
        gestionItems.prendas[prendaEditIndex] = {
            ...prendaActual,
            ...prendaData,
            id: prendaActual?.id ?? prendaData?.id ?? null,
            prenda_pedido_id: prendaActual?.prenda_pedido_id ?? prendaData?.prenda_pedido_id ?? prendaActual?.id ?? null,
            imagenes: Array.isArray(prendaData?.imagenes) ? prendaData.imagenes : [],
            _imagenes_originales: Array.isArray(prendaActual?._imagenes_originales)
                ? clonarMetadatosImagenes(prendaActual._imagenes_originales)
                : clonarMetadatosImagenes(prendaActual?.imagenes),
            telasAgregadas: Array.isArray(prendaData?.telasAgregadas) ? prendaData.telasAgregadas : [],
            procesos: (prendaData?.procesos && typeof prendaData.procesos === 'object') ? prendaData.procesos : {},
            asignacionesColoresPorTalla: prendaData?.asignacionesColoresPorTalla || {},
            cantidad_talla: prendaData?.cantidad_talla || {},
            procesos_a_eliminar: Array.isArray(prendaData?.procesos_a_eliminar) ? prendaData.procesos_a_eliminar : (prendaActual?.procesos_a_eliminar || []),
            imagenes_a_eliminar: Array.isArray(prendaData?.imagenes_a_eliminar) ? prendaData.imagenes_a_eliminar : []
        };

        console.debug('[DraftPedidoSerializer] Prenda en edicion sincronizada antes de guardar borrador:', {
            prendaEditIndex,
            nombre: gestionItems.prendas[prendaEditIndex]?.nombre_prenda || gestionItems.prendas[prendaEditIndex]?.nombre_producto,
            imagenes: gestionItems.prendas[prendaEditIndex]?.imagenes?.length || 0,
            telas: gestionItems.prendas[prendaEditIndex]?.telasAgregadas?.length || gestionItems.prendas[prendaEditIndex]?.telas?.length || 0,
            procesos: Object.keys(gestionItems.prendas[prendaEditIndex]?.procesos || {}).length
        });

        return true;
    }

    window.DraftPedidoSerializer = {
        sincronizarPrendaModalAntesDeGuardarBorrador,
        serializarPrendaExistenteParaBorrador
    };

    window.sincronizarPrendaModalAntesDeGuardarBorrador = sincronizarPrendaModalAntesDeGuardarBorrador;
    window.serializarPrendaExistenteParaBorrador = serializarPrendaExistenteParaBorrador;
})();
