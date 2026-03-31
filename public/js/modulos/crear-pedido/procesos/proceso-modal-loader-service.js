/**
 * Servicio para cargar datos de un proceso dentro del modal de edicion.
 * Mantiene API global para compatibilidad.
 */
(function() {
    'use strict';

    const PREVIEW_IMAGENES_MAX = 3;

    function agregarStorageUrl(url) {
        if (!url || typeof url !== 'string') return '';
        if (url.startsWith('/')) return url;
        if (url.startsWith('http')) return url;
        if (url.startsWith('blob:')) return url;
        if (url.startsWith('data:')) return url;
        return '/storage/' + url;
    }

    function resolverUrlImagenProceso(img) {
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        if (img?.previewUrl) {
            return img.previewUrl;
        }
        if (img?.dataURL) {
            return img.dataURL;
        }
        if (typeof img === 'string') {
            return agregarStorageUrl(img);
        }
        if (typeof img === 'object' && img) {
            const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
            return (typeof url === 'string') ? agregarStorageUrl(url) : '';
        }
        return '';
    }

    function inicializarEstadoModalProceso(datos) {
        globalThis.imagenesProcesoActual = [null, null, null];
        globalThis.imagenesProcesoExistentes = [];
        globalThis.imagenesEliminadasProcesoStorage = [];

        if (!globalThis.ubicacionesProcesoSeleccionadas) {
            globalThis.ubicacionesProcesoSeleccionadas = [];
        }

        const imagenesValidas = (datos.imagenes || []).filter(img => {
            if (img && img.deleted_at) return false;
            return img !== null && img !== undefined && img !== '';
        });

        globalThis.imagenesProcesoExistentes = imagenesValidas.map(img => img || null);
    }

    function resetearPreviewsProceso() {
        for (let i = 1; i <= PREVIEW_IMAGENES_MAX; i++) {
            const preview = document.getElementById(`proceso-foto-preview-${i}`);
            if (!preview) continue;

            preview.style.border = '2px dashed #0066cc';
            preview.style.background = '#f9fafb';
            preview.innerHTML = `
                <div class="placeholder-content" style="text-align: center;">
                    <div class="material-symbols-rounded" style="font-size: 1.5rem; color: #6b7280;">add_photo_alternate</div>
                    <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.25rem;">Imagen ${i}</div>
                </div>
            `;
        }
    }

    function obtenerImagenesDatosProceso(datos) {
        return datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    }

    function resolverUrlPreviewModalProceso(img, indice) {
        const isFile = img instanceof File;
        const hasEmbeddedFile = !isFile && img && img.file instanceof File;

        if (isFile) {
            return URL.createObjectURL(img);
        }

        if (hasEmbeddedFile) {
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                try { URL.revokeObjectURL(img.previewUrl); } catch (e) {}
            }
            const url = URL.createObjectURL(img.file);
            img.previewUrl = url;
            return url;
        }

        if (typeof img === 'string') {
            return resolverUrlImagenProceso(img);
        }

        if (img && img.previewUrl) {
            return img.previewUrl;
        }

        if (img && (img.url || img.ruta_original || img.ruta || img.ruta_webp)) {
            return resolverUrlImagenProceso(img);
        }

        console.warn(`[cargarDatosProcesoEnModal] Imagen ${indice} tipo no reconocido:`, img);
        return '';
    }

    function crearBotonEliminarPreview(preview, indice) {
        let deleteBtn = preview.querySelector('.btn-eliminar-imagen-proceso');
        if (deleteBtn) deleteBtn.remove();

        deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn-eliminar-imagen-proceso';
        deleteBtn.type = 'button';
        deleteBtn.setAttribute('data-indice', indice);
        deleteBtn.style.cssText = 'position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 16px; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold; z-index: 10;';
        deleteBtn.textContent = '×';
        preview.appendChild(deleteBtn);
    }

    function renderizarPreviewProceso(indice, img) {
        const preview = document.getElementById(`proceso-foto-preview-${indice}`);
        if (!preview) return;

        const imgUrl = resolverUrlPreviewModalProceso(img, indice);

        preview.style.border = '2px solid #0066cc';
        preview.style.background = 'transparent';
        preview.innerHTML = `
            <img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
        `;

        crearBotonEliminarPreview(preview, indice);
    }

    function registrarImagenEnEstadoModal(previewIndex, img) {
        const isFile = img instanceof File;
        if (isFile) {
            if (globalThis.imagenesProcesoActual) {
                globalThis.imagenesProcesoActual[previewIndex - 1] = img;
            }
            return;
        }
        globalThis.imagenesProcesoExistentes[previewIndex - 1] = img;
    }

    function limpiarUbicacionesProceso(raw) {
        if (!raw) return [];

        if (typeof raw === 'string') {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    if (parsed.length > 0 && typeof parsed[0] === 'object' && parsed[0].ubicacion) {
                        return parsed;
                    }
                    return parsed.map(u => (typeof u === 'string' ? u.replace(/^["\\]*|["\\]*$/g, '').trim() : String(u)));
                }
                return Array.isArray(parsed) ? parsed : [parsed];
            } catch (e) {
                const cleaned = raw.replace(/^["\\]*|["\\]*$/g, '').trim();
                return cleaned ? [cleaned] : [];
            }
        }

        if (Array.isArray(raw)) {
            return raw.map(ub => {
                if (typeof ub === 'object' && ub !== null && ub.ubicacion) {
                    return ub;
                }
                if (typeof ub === 'string') {
                    const limpio = ub.replace(/^["\\]*|["\\]*$/g, '').trim();
                    if (limpio.startsWith('[') || limpio.startsWith('{')) {
                        try {
                            const parsed = JSON.parse(limpio);
                            if (Array.isArray(parsed)) return parsed[0];
                            return String(parsed);
                        } catch (e) {
                            return limpio;
                        }
                    }
                    return limpio;
                }
                return String(ub);
            });
        }

        return [String(raw)];
    }

    function sincronizarUbicacionesModal(datos) {
        if (!datos.ubicaciones || !globalThis.ubicacionesProcesoSeleccionadas) return;

        const ubicacionesLimpias = limpiarUbicacionesProceso(datos.ubicaciones);
        globalThis.ubicacionesProcesoSeleccionadas.length = 0;
        globalThis.ubicacionesProcesoSeleccionadas.push(...ubicacionesLimpias);

        if (globalThis.renderizarListaUbicaciones) {
            globalThis.renderizarListaUbicaciones();
        }
    }

    function normalizarTallasProceso(tallas) {
        let damaTallas = tallas?.dama || {};
        let caballeroTallas = tallas?.caballero || {};
        const sobremedidaTallas = { ...(tallas?.sobremedida || {}) };

        const extraerSobremedida = (sourceGenero, targetGenero) => {
            const limpio = {};
            for (const [talla, valor] of Object.entries(sourceGenero || {})) {
                if (talla !== 'SOBREMEDIDA') {
                    limpio[talla] = valor;
                    continue;
                }

                if (typeof valor === 'number') {
                    sobremedidaTallas[targetGenero] = valor;
                    continue;
                }

                if (typeof valor === 'object' && valor !== null) {
                    Object.entries(valor).forEach(([genero, cantidad]) => {
                        sobremedidaTallas[genero] = cantidad;
                    });
                }
            }
            return limpio;
        };

        damaTallas = extraerSobremedida(damaTallas, 'DAMA');
        caballeroTallas = extraerSobremedida(caballeroTallas, 'CABALLERO');

        return { damaTallas, caballeroTallas, sobremedidaTallas };
    }

    function cargarDatosProcesoEnModal(tipo, datos) {
        console.log(' [CARGAR-DATOS-PROCESO] Cargando datos en modal para:', tipo, datos);

        inicializarEstadoModalProceso(datos);
        resetearPreviewsProceso();

        const imagenes = obtenerImagenesDatosProceso(datos);
        let previewIndex = 1;

        imagenes.forEach((img, bdIndex) => {
            if (!img || img === null || img === undefined || img === '') {
                console.log('[cargarDatosProcesoEnModal] Saltando imagen', bdIndex, '(null/undefined)');
                return;
            }

            if (previewIndex > PREVIEW_IMAGENES_MAX) {
                console.log('[cargarDatosProcesoEnModal] Saltando imagen', bdIndex, '(maximo 3 imagenes)');
                return;
            }

            renderizarPreviewProceso(previewIndex, img);
            registrarImagenEnEstadoModal(previewIndex, img);
            previewIndex++;
        });

        sincronizarUbicacionesModal(datos);

        const obsInput = document.getElementById('proceso-observaciones');
        if (obsInput) {
            obsInput.value = datos.observaciones || '';
        }

        if (!datos.tallas || !globalThis.tallasSeleccionadasProceso) {
            return;
        }

        const { damaTallas, caballeroTallas, sobremedidaTallas } = normalizarTallasProceso(datos.tallas);

        globalThis.tallasSeleccionadasProceso.dama = Object.keys(damaTallas);
        globalThis.tallasSeleccionadasProceso.caballero = Object.keys(caballeroTallas);
        globalThis.tallasSeleccionadasProceso.sobremedida = Object.keys(sobremedidaTallas).length > 0 ? sobremedidaTallas : null;

        if (!globalThis.tallasCantidadesProceso) {
            globalThis.tallasCantidadesProceso = { dama: {}, caballero: {}, sobremedida: {} };
        }

        globalThis.tallasCantidadesProceso.dama = { ...damaTallas };
        globalThis.tallasCantidadesProceso.caballero = { ...caballeroTallas };
        globalThis.tallasCantidadesProceso.sobremedida = { ...sobremedidaTallas };

        if (globalThis.actualizarResumenTallasProceso) {
            globalThis.actualizarResumenTallasProceso();
        }
    }

    globalThis.ProcesoModalLoaderService = Object.freeze({
        cargarDatos: cargarDatosProcesoEnModal
    });

    globalThis.cargarDatosProcesoEnModal = cargarDatosProcesoEnModal;
})();
