/**
 *  Módulo de Imágenes
 * Responsabilidad: Cargar y mostrar imágenes en el modal
 */

class PrendaEditorImagenes {
    /**
     * Cargar imágenes en modal
     */
    static cargar(prenda) {
        console.log(' [Imagenes] Cargando:', {
            cantidad: prenda.imagenes?.length || 0,
            tipo: typeof prenda.imagenes,
            estructura_primera: prenda.imagenes?.[0] ? Object.keys(prenda.imagenes[0]) : 'N/A'
        });
        
        const { preview, contador, btn } = this._obtenerElementosDom();
        if (!preview) return;
        
        preview.innerHTML = '';
        
        // Validar si hay imágenes válidas
        const imagenesValidas = this._obtenerImagenesValidas(prenda);
        
        if (imagenesValidas.length > 0) {
            this._cargarConImagenes({ imagenes: imagenesValidas }, preview, contador, btn);
        } else {
            this._cargarSinImagenes(preview, contador, btn);
        }
        
        console.log(' [Imagenes] Completado');
    }
    
    /**
     * Obtener imágenes válidas de la estructura de prenda
     * Soporta múltiples formatos de estructura
     * @private
     */
    static _obtenerImagenesValidas(prenda) {
        // Formato 1: prenda.imagenes es un array
        if (Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            return prenda.imagenes.filter(img => {
                if (!img) return false;
                // Aceptar si tiene URL/ruta/previewUrl
                if (img.url || img.previewUrl || img.ruta || img.ruta_webp || img.ruta_original) return true;
                // ⚠️ CRÍTICO FIX: Aceptar si tiene File object (incluso si previewUrl fue revocado)  
                // Esto permite recrear blob URLs para imágenes que fueron revocadas
                if (img.file instanceof File) {
                    console.log('[_obtenerImagenesValidas] Imagen con File object encontrada - previewUrl será recreado');
                    return true;
                }
                return false;
            });
        }
        
        // Formato 2: prenda.imagen es una URL única
        if (prenda.imagen && typeof prenda.imagen === 'string') {
            console.log('[_obtenerImagenesValidas] Usando prenda.imagen única:', prenda.imagen.substring(0, 60));
            return [{
                url: prenda.imagen,
                previewUrl: prenda.imagen,
                nombre: 'imagen-principal'
            }];
        }
        
        // Formato 3: Intentar obtener desde storage si no hay imagenes
        if (globalThis.imagenesPrendaStorage) {
            const imagenes = globalThis.imagenesPrendaStorage.obtenerImagenes();
            if (imagenes.length > 0) {
                console.log('[_obtenerImagenesValidas] Usando imágenes del storage:', imagenes.length);
                return imagenes;
            }
        }
        
        return [];
    }

    /**
     * Obtener referencias a elementos del DOM
     * @private
     */
    static _obtenerElementosDom() {
        return {
            preview: document.getElementById('nueva-prenda-foto-preview'),
            contador: document.getElementById('nueva-prenda-foto-contador'),
            btn: document.getElementById('nueva-prenda-foto-btn')
        };
    }

    /**
     * Cargar preview con imágenes
     * @private
     */
    static _cargarConImagenes(prendaODatos, preview, contador, btn) {
        // Soportar tanto prenda completa como { imagenes: [...] }
        const imagenes = prendaODatos.imagenes || prendaODatos;
        const imagenesConBlobUrl = this._procesarImagenesConBlobUrl(imagenes);
        
        if (globalThis.imagenesPrendaStorage) {
            globalThis.imagenesPrendaStorage.establecerImagenes(imagenesConBlobUrl);
        }
        
        this._renderizarPreviewConImagenes(preview, imagenesConBlobUrl, contador, btn);
        setupDragAndDropConImagen(preview, imagenesConBlobUrl);
        this._agregarListenerPaste(preview);
        console.log('[PrendaEditorImagenes]  Listener de paste configurado en preview (cargar)');
        console.log('[Imagenes]  Preview renderizado idéntico a creación');
    }

    /**
     * Cargar preview sin imágenes
     * @private
     */
    static _cargarSinImagenes(preview, contador, btn) {
        // ⚠️ CRÍTICO FIX: NO limpiar el storage si ya tiene imágenes válidas
        // Esto previene que se eliminen las imágenes cuando se edita una prenda
        if (globalThis.imagenesPrendaStorage) {
            const imagenesEnStorage = globalThis.imagenesPrendaStorage.obtenerImagenes();
            if (imagenesEnStorage && imagenesEnStorage.length > 0) {
                // Storage tiene imágenes → NO las limpies, renderiza desde el storage
                console.log(' [Imagenes]  Prenda sin .imagenes pero storage tiene', imagenesEnStorage.length, 'imágenes - PRESERVANDO');
                this._renderizarPreviewConImagenes(preview, imagenesEnStorage, contador, btn);
                return;
            }
            
            // Storage vacío → es seguro limpiar
            console.log(' [Imagenes]  Prenda sin imágenes, limpiando storage');
            globalThis.imagenesPrendaStorage.establecerImagenes([]);
        }
        
        preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click o arrastra para agregar</div></div>';
        preview.style.cursor = 'pointer';
        
        if (contador) contador.textContent = '';
        if (btn) btn.style.display = 'block';
        
        setupDragAndDrop(preview);
    }

    /**
     * Procesar imágenes agregando blob URLs
     *  CRÍTICO: Preservar blob URLs válidas existentes para edición
     * @private
     */
    static _procesarImagenesConBlobUrl(imagenes) {
        console.log('[_procesarImagenesConBlobUrl] INICIANDO - Imágenes a procesar:', imagenes.length);
        
        return imagenes.map((img, idx) => {
            const imagenConId = {
                ...img,
                id: img.id || img.imagen_id || null,
            };
            
            // 1️⃣ PRIMERO: Si hay File object SIEMPRE crear blob URL NUEVO
            // (Los blob URLs revocados causarían que las imágenes no se muestren)
            // ⚠️ FIX: Esto es CRÍTICO para recuperar imágenes después de revocación
            if (img.file instanceof File) {
                const nuevoBlob = URL.createObjectURL(img.file);
                console.log(`[_procesarImagenesConBlobUrl] Imagen ${idx + 1}: Creando blob URL NUEVO desde File`);
                return {
                    ...imagenConId,
                    file: img.file,
                    previewUrl: nuevoBlob,
                    nombre: img.nombre || img.file.name || `imagen-${idx + 1}`,
                    tamano: img.tamano || img.file.size,
                };
            }
            
            // 2️⃣ SEGUNDO: Si NO hay File object pero hay URL del servidor, usar esa
            // (Para imágenes que ya fueron guardadas en BD)
            const urlServidor = img.url || img.ruta || img.ruta_webp || img.ruta_original || '';
            if (urlServidor && typeof urlServidor === 'string' && !urlServidor.startsWith('blob:')) {
                console.log(`[_procesarImagenesConBlobUrl] Imagen ${idx + 1}: Usando URL servidor: ${urlServidor.substring(0, 50)}...`);
                return {
                    ...imagenConId,
                    previewUrl: urlServidor,
                    url: urlServidor,
                    nombre: img.nombre || `imagen-${idx + 1}`,
                    tamano: img.tamano || 0
                };
            }
            
            // 3️⃣ TERCERO: Si hay blob URL existente (data: o blob:), intentar preservarlo
            // ⚠️ AVISO: Si fue revocado, la imagen no cargará, pero tenemos fallback en paso 2
            if (img.previewUrl && (img.previewUrl.startsWith('blob:') || img.previewUrl.startsWith('data:'))) {
                console.log(`[_procesarImagenesConBlobUrl] Imagen ${idx + 1}: Preservando blob URL existente (RIESGO: puede estar revocado)`);
                return imagenConId;
            }
            
            // 4️⃣ FALLBACK: Retornar tal cual
            console.warn(`[_procesarImagenesConBlobUrl] Imagen ${idx + 1}: No se pudo determinar URL válida`);
            return imagenConId;
        });
    }

    /**
     * Renderizar preview con imágenes
     * @private
     */
    static _renderizarPreviewConImagenes(preview, imagenes, contador, btn) {
        console.log('[_renderizarPreviewConImagenes] INICIO', {
            imagenesCount: imagenes.length,
            primeraImagen: imagenes[0] ? {
                previewUrl: imagenes[0].previewUrl?.substring(0, 60) + '...',
                url: imagenes[0].url?.substring(0, 60) + '...',
                nombreCampo: Object.keys(imagenes[0]).join(', ')
            } : 'N/A'
        });
        
        const container = document.createElement('div');
        container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
        
        const img = document.createElement('img');
        const primeraImagen = imagenes[0];
        
        // Obtener URL a usar - priorizar previewUrl, luego url
        let urlAUsar = null;
        if (primeraImagen.previewUrl && primeraImagen.previewUrl !== 'undefined') {
            urlAUsar = primeraImagen.previewUrl;
            console.log('[_renderizarPreviewConImagenes] Usando previewUrl:', urlAUsar.substring(0,  60) + '...');
        } else if (primeraImagen.url && primeraImagen.url !== 'undefined') {
            urlAUsar = primeraImagen.url;
            console.log('[_renderizarPreviewConImagenes] Usando url:', urlAUsar.substring(0, 60) + '...');
        }
        
        if (!urlAUsar) {
            console.error('[_renderizarPreviewConImagenes] NO HAY URL VÁLIDA en primera imagen:', primeraImagen);
            this._establecerImagenNoDisponible(img);
        } else {
            img.src = urlAUsar;
            img.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
            console.log('[_renderizarPreviewConImagenes] Imagen asignada: ' + urlAUsar.substring(0, 60) + '...');
        }
        
        container.appendChild(img);
        preview.appendChild(container);
        preview.dataset.imagenes = JSON.stringify(imagenes);
        preview.dataset.indiceActual = '0';
        
        if (contador) {
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
        }
        
        if (btn) {
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        console.log('[_renderizarPreviewConImagenes] FIN - Preview renderizado');
    }

    /**
     * Establecer estilos para imagen no disponible
     * @private
     */
    static _establecerImagenNoDisponible(img) {
        img.style.cssText = 'max-width: 100%; height: 200px; border-radius: 4px; background: #f3f4f6;';
        img.alt = 'Imagen no disponible';
        img.innerHTML = '<div style="text-align: center; color: #6b7280;">📷<br><small>Imagen no disponible</small></div>';
    }
    
    /**
     * Actualizar preview después de agregar imagen en edición
     */
    static actualizarPreviewDespuesDeAgregar() {
        console.log('[PrendaEditorImagenes]  Actualizando preview después de agregar imagen');
        
        const preview = document.getElementById('nueva-prenda-foto-preview');
        const contador = document.getElementById('nueva-prenda-foto-contador');
        
        if (!preview || !globalThis.imagenesPrendaStorage) {
            console.warn('[PrendaEditorImagenes]  Preview o storage no disponible');
            return;
        }
        
        const imagenes = globalThis.imagenesPrendaStorage.obtenerImagenes();
        console.log('[PrendaEditorImagenes]  Imágenes en storage:', imagenes.length);
        
        // Limpiar preview
        preview.innerHTML = '';
        
        // Mostrar SOLO la primera imagen
        if (imagenes.length > 0) {
            const container = document.createElement('div');
            container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
            
            const img = document.createElement('img');
            
            //  CRÍTICO: Validar previewUrl antes de asignar
            if (!imagenes[0].previewUrl || imagenes[0].previewUrl === 'undefined' || imagenes[0].previewUrl === undefined) {
                console.error('[Imagenes]  previewUrl inválido en actualización:', imagenes[0].previewUrl);
                img.style.cssText = 'max-width: 100%; height: 200px; border-radius: 4px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;';
                img.alt = 'Imagen no disponible';
                img.innerHTML = '<div style="text-align: center; color: #6b7280;">📷<br><small>Imagen no disponible</small></div>';
            } else {
                img.src = imagenes[0].previewUrl;
                img.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
            }
            img.alt = 'Imagen 1';
            
            container.appendChild(img);
            preview.appendChild(container);
            
            // Guardar datos en preview
            preview.dataset.imagenes = JSON.stringify(imagenes);
            preview.dataset.indiceActual = '0';
            
            console.log('[PrendaEditorImagenes]  Preview actualizado - mostrando imagen 1 de ' + imagenes.length);
        }
        
        // Actualizar contador
        if (contador) {
            contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
            console.log('[PrendaEditorImagenes]  Contador actualizado:', contador.textContent);
        }
        
        // Actualizar botón
        const btn = document.getElementById('nueva-prenda-foto-btn');
        if (btn) {
            btn.style.display = imagenes.length < 3 ? 'block' : 'none';
        }
        
        //  CRÍTICO: Re-agregar listener de paste después de actualizar preview
        this._agregarListenerPaste(preview);
    }
    
    /**
     * Agregar listener de paste al preview
     * @private
     * 
     * ✓ COMPLETADO: El listener global de paste en DragDropManager maneja
     * Solo configuramos contenteditable para que el preview pueda recibir el foco y paste events.
     */
    static _agregarListenerPaste(preview) {
        if (!preview) {
            console.error('[PrendaEditorImagenes]  Preview NO ENCONTRADO');
            return;
        }
        
        //  CRÍTICO: Hacer el preview contenteditable para que pueda recibir foco
        preview.contentEditable = 'true';
        preview.style.outline = 'none';
        
        console.log('[PrendaEditorImagenes]  Preview configurado como contenteditable');
    }

    /**
     * Extraer URL de diferentes formatos de imagen
     * @private
     */
    static _extraerUrl(img) {
        if (typeof img === 'string') {
            return this._normalizarRuta(img);
        }
        
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        
        if (typeof img === 'object') {
            //  FIX CRÍTICO: Si el objeto tiene un File object en .file,
            // crear un blob URL desde ese File (aunque previewUrl esté revocado)
            if (img.file instanceof File) {
                console.log('[prenda-editor-imagenes]  DETECTADO File object en img.file - creando blob URL');
                return URL.createObjectURL(img.file);
            }
            
            // Si no, intentar extraer URL de propiedades
            const raw = img.url || img.ruta || img.ruta_webp || img.ruta_original || img.ruta_imagen || img.imagen || img.previewUrl || null;
            return raw ? this._normalizarRuta(raw) : null;
        }
        
        return null;
    }

    /**
     * Normalizar ruta de imagen agregando /storage/ si es necesario
     * @private
     */
    static _normalizarRuta(src) {
        if (!src) return null;
        // Ya es absoluta, blob o data URL → no tocar
        if (src.startsWith('http') || src.startsWith('blob:') || src.startsWith('data:') || src.startsWith('/storage/')) {
            return src;
        }
        // Ruta con / inicial pero sin /storage/ → agregar /storage
        if (src.startsWith('/')) {
            return '/storage' + src;
        }
        // Ruta relativa (ej: "pedidos/22/prenda/xxx.webp") → agregar /storage/
        return '/storage/' + src;
    }

    /**
     * Configurar click handler en preview para abrir galería cuando hay imágenes
     * @private
     */
    static _configurarClickGaleria(preview, imagenes) {
        if (!preview || !imagenes || imagenes.length === 0) return;
        
        if (preview._galeriaClickHandler) {
            preview.removeEventListener('click', preview._galeriaClickHandler);
        }
        
        preview._galeriaClickHandler = (e) => {
            if (e.target.closest('button') || e.target.closest('input')) return;
            e.preventDefault();
            e.stopPropagation();
            if (typeof Swal === 'undefined') return;
            
            // Obtener URLs actualizadas del storage en el momento del click
            const currentImages = globalThis.imagenesPrendaStorage?.obtenerImagenes() || [];
            if (currentImages.length === 0) return;
            
            const urls = currentImages
                .map(img => img.previewUrl || img.url || img.ruta || (typeof img === 'string' ? img : ''))
                .filter(u => u && u.length > 0);
            if (urls.length === 0) return;
            
            let idx = 0;
            const keyHandler = (ev) => {
                if (!globalThis.__galeriaPrendaActiva) return;
                if (ev.key === 'ArrowLeft') { ev.preventDefault(); document.getElementById('gal-prenda-prev')?.click(); }
                else if (ev.key === 'ArrowRight') { ev.preventDefault(); document.getElementById('gal-prenda-next')?.click(); }
            };
            
            const eliminarImagenActual = () => {
                Swal.fire({
                    title: '¿Eliminar imagen?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: { container: 'swal-galeria-container' }
                }).then((result) => {
                    if (!result.isConfirmed) { renderModal(); return; }
                    
                    // Obtener la imagen actual para obtener su ID de BD
                    const imagenActual = currentImages[idx];
                    const imagenId = imagenActual?.id;
                    
                    //  IMPORTANTE: NO eliminar del servidor aquí
                    // Solo marcar para eliminación cuando se guarden los cambios
                    // Esto permite al usuario cancelar la edición sin perder la imagen
                    
                    if (imagenId) {
                        // Marcar imagen para eliminación (se eliminará al guardar)
                        if (!globalThis.imagenesAEliminar) {
                            globalThis.imagenesAEliminar = [];
                        }
                        if (!globalThis.imagenesAEliminar.includes(imagenId)) {
                            globalThis.imagenesAEliminar.push(imagenId);
                            console.log('[prenda-editor-imagenes]  Imagen marcada para eliminación al guardar', {
                                id: imagenId,
                                totalMarcadas: globalThis.imagenesAEliminar.length
                            });
                        }
                    }
                    
                    // Eliminar del storage local SOLO
                    if (globalThis.imagenesPrendaStorage?.obtenerImagenes) {
                        const imgs = globalThis.imagenesPrendaStorage.obtenerImagenes();
                        imgs.splice(idx, 1);
                        globalThis.imagenesPrendaStorage.establecerImagenes(imgs);
                        console.log('[prenda-editor-imagenes]  Imagen eliminada del storage local (no de BD)', {
                            imagenId: imagenId,
                            imagenesRestantes: imgs.length
                        });
                    }
                    
                    //  CRÍTICO: Actualizar preview correctamente sin apilar
                    // En edición: usar PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar()
                    if (PrendaEditorImagenes?.actualizarPreviewDespuesDeAgregar) {
                        PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar();
                        console.log('[prenda-editor-imagenes]  Preview actualizado después de eliminar (edición)');
                    } else if (typeof globalThis.actualizarPreviewPrenda === 'function') {
                        // Fallback para creación
                        globalThis.actualizarPreviewPrenda();
                    }
                    
                    // Si quedan imágenes, reabrir galería
                    const remaining = globalThis.imagenesPrendaStorage?.obtenerImagenes() || [];
                    if (remaining.length > 0) {
                        idx = Math.min(idx, remaining.length - 1);
                        const urlsActualizadas = this._extraerUrlsDeImagenes(remaining);
                        urls.splice(0, urls.length, ...urlsActualizadas);
                        renderModal();
                    } else {
                        // Sin imágenes: limpiar handler y restaurar placeholder
                        if (preview._galeriaClickHandler) {
                            preview.removeEventListener('click', preview._galeriaClickHandler);
                            preview._galeriaClickHandler = null;
                        }
                        preview.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div></div>';
                        Swal.close();
                    }
                });
            };
            
            const renderModal = () => {
                if (urls.length === 0) { Swal.close(); return; }
                Swal.fire({
                    html: `
                        <div style="display:flex !important; flex-direction:column !important; align-items:center !important; gap:1rem !important; width:100% !important; padding:2rem !important;">
                        <div style="position:relative !important; width:100% !important; max-width:1200px !important;">
                            <img src="${urls[idx]}" alt="Foto prenda" style="width:100% !important; height:auto !important; border-radius:8px !important; border:1px solid #e5e7eb !important; object-fit:contain !important; max-height:80vh !important; display:block !important;">
                            
                                <button id="gal-prenda-prev" style="position:absolute !important; top:50% !important; left:-50px !important; transform:translateY(-50%) !important; background:#111827cc !important; color:white !important; border:none !important; border-radius:50% !important; width:45px !important; height:45px !important; cursor:pointer !important; font-size:1.5rem !important; display:flex !important; align-items:center !important; justify-content:center !important; transition:all 0.2s !important; z-index:10 !important;">‹</button>
                                <button id="gal-prenda-next" style="position:absolute !important; top:50% !important; right:-50px !important; transform:translateY(-50%) !important; background:#111827cc !important; color:white !important; border:none !important; border-radius:50% !important; width:45px !important; height:45px !important; cursor:pointer !important; font-size:1.5rem !important; display:flex !important; align-items:center !important; justify-content:center !important; transition:all 0.2s !important; z-index:10 !important;">›</button>
                            
                        </div>
                        <div style="display:flex !important; align-items:center !important; gap:1.5rem !important; margin-top:1rem !important;">
                            <span style="font-size:1rem !important; color:#4b5563 !important; font-weight:500 !important;">${idx + 1} / ${urls.length}</span>
                            <button id="gal-prenda-delete" style="background:#dc2626 !important; color:white !important; border:none !important; border-radius:6px !important; padding:0.6rem 1.2rem !important; cursor:pointer !important; font-size:0.9rem !important; display:flex !important; align-items:center !important; gap:0.5rem !important; transition:background 0.2s !important; font-weight:500 !important;">
                                <span class="material-symbols-rounded" style="font-size:1.1rem !important;">delete</span> Eliminar
                            </button>
                        </div>
                    </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: true,
                    width: '95%',
                    customClass: { container: 'swal-galeria-container' },
                    didOpen: () => {
                        // Aplicar max-width al popup
                        const popup = document.querySelector('.swal2-popup');
                        if (popup) {
                            popup.style.maxWidth = '1400px !important';
                        }
                        globalThis.__galeriaPrendaActiva = true;
                        this._configurarBotonesGaleria({ idx, urls, renderModal, eliminarImagenActual });
                        globalThis.addEventListener('keydown', keyHandler);
                    },
                    willClose: () => {
                        globalThis.__galeriaPrendaActiva = false;
                        globalThis.removeEventListener('keydown', keyHandler);
                    }
                });
            };
            
            renderModal();
        };
        
        preview.addEventListener('click', preview._galeriaClickHandler);
        preview.style.cursor = 'pointer';
        
        // Inyectar/actualizar CSS para z-index encima del modal de prenda (z-index: 1050000)
        let galeriaStyle = document.getElementById('swal-galeria-zindex-style');
        if (!galeriaStyle) {
            galeriaStyle = document.createElement('style');
            galeriaStyle.id = 'swal-galeria-zindex-style';
            document.head.appendChild(galeriaStyle);
        }
        galeriaStyle.textContent = `
            .swal-galeria-container {
                z-index: 2000000 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }
            .swal-galeria-container .swal2-popup {
                margin: auto !important;
            }
        `;
        
        console.log('[Imagenes] Click handler de galería configurado para', imagenes.length, 'imágenes');
    }

    /**
     * Extraer URLs válidas de array de imágenes
     * @private
     */
    static _extraerUrlsDeImagenes(imagenes) {
        return imagenes
            .map(img => img.previewUrl || img.url || img.ruta || '')
            .filter(Boolean);
    }

    /**
     * Configurar botones de navegación en galería modal
     * @private
     */
    static _configurarBotonesGaleria({ idx, urls, renderModal, eliminarImagenActual }) {
        const prev = document.getElementById('gal-prenda-prev');
        const next = document.getElementById('gal-prenda-next');
        const del = document.getElementById('gal-prenda-delete');
        
        if (prev) {
            prev.onclick = () => {
                idx = (idx - 1 + urls.length) % urls.length;
                renderModal();
            };
        }
        if (next) {
            next.onclick = () => {
                idx = (idx + 1) % urls.length;
                renderModal();
            };
        }
        if (del) {
            del.onclick = () => eliminarImagenActual();
        }
    }
    
    /**
     *  ELIMINADO: _actualizarPreviewDOM()
     * Esta función causaba que se apilaran todas las imágenes.
     * Usar siempre PrendaEditorImagenes.actualizarPreviewDespuesDeAgregar() en su lugar.
     */

    /**
     *  NUEVO: Agregar controles de navegación para múltiples imágenes
     * @private
     */
    static _agregarControlesNavegacion(preview, imagenes) {
        // Crear contenedor de controles
        const controles = document.createElement('div');
        controles.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            gap: 0.5rem;
        `;
        
        // Botón anterior
        const btnAnterior = document.createElement('button');
        btnAnterior.type = 'button';
        btnAnterior.innerHTML = '◀ Anterior';
        btnAnterior.style.cssText = `
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        `;
        
        // Indicador
        const indicador = document.createElement('span');
        indicador.style.cssText = `
            font-size: 0.75rem;
            color: #666;
            flex: 1;
            text-align: center;
        `;
        indicador.textContent = `1 de ${imagenes.length}`;
        
        // Botón siguiente
        const btnSiguiente = document.createElement('button');
        btnSiguiente.type = 'button';
        btnSiguiente.innerHTML = 'Siguiente ▶';
        btnSiguiente.style.cssText = `
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        `;
        
        // Función para cambiar imagen
        const cambiarImagen = (nuevoIndice) => {
            if (nuevoIndice < 0 || nuevoIndice >= imagenes.length) return;
            
            preview.dataset.indiceActual = nuevoIndice;
            const img = imagenes[nuevoIndice];
            
            // Encontrar la imagen en el preview y reemplazarla
            const imgEl = preview.querySelector('img');
            if (imgEl) {
                const src = this._extraerUrl(img);
                if (src) {
                    imgEl.src = src;
                    imgEl.alt = `Imagen ${nuevoIndice + 1}`;
                }
            }
            
            // Actualizar indicador
            indicador.textContent = `${nuevoIndice + 1} de ${imagenes.length}`;
            
            // Actualizar estado de botones
            btnAnterior.disabled = nuevoIndice === 0;
            btnSiguiente.disabled = nuevoIndice === imagenes.length - 1;
            
            console.log(`[Imagenes] Navegando a imagen ${nuevoIndice + 1} de ${imagenes.length}`);
        };
        
        // Event listeners
        btnAnterior.addEventListener('click', (e) => {
            e.stopPropagation();
            const indiceActual = Number.parseInt(preview.dataset.indiceActual || '0');
            cambiarImagen(indiceActual - 1);
        });
        
        btnSiguiente.addEventListener('click', (e) => {
            e.stopPropagation();
            const indiceActual = Number.parseInt(preview.dataset.indiceActual || '0');
            cambiarImagen(indiceActual + 1);
        });
        
        // Agregar controles al preview
        controles.appendChild(btnAnterior);
        controles.appendChild(indicador);
        controles.appendChild(btnSiguiente);
        preview.appendChild(controles);
        
        // Inicializar estado de botones
        btnAnterior.disabled = true;
        btnSiguiente.disabled = imagenes.length <= 1;
        
        console.log(`[Imagenes] Controles de navegación agregados para ${imagenes.length} imágenes`);
    }

    /**
     * Limpiar imágenes
     */
    static limpiar() {
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (preview) {
            preview.innerHTML = '';
            // Remover click handler de galería si existe
            if (preview._galeriaClickHandler) {
                preview.removeEventListener('click', preview._galeriaClickHandler);
                preview._galeriaClickHandler = null;
            }
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorImagenes;
}
