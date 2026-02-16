/**
 * 🖼️ Módulo de Imágenes
 * Responsabilidad: Cargar y mostrar imágenes en el modal
 */

class PrendaEditorImagenes {
    /**
     * Cargar imágenes en modal
     */
    static cargar(prenda) {
        console.log('🖼️ [Imagenes] Cargando:', {
            cantidad: prenda.imagenes?.length || 0,
            tipo: typeof prenda.imagenes
        });
        
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (!preview) {
            console.warn(' [Imagenes] No encontrado #nueva-prenda-foto-preview');
            return;
        }
        
        // Limpiar previos
        preview.innerHTML = '';
        
        // Cargar imágenes
        if (prenda.imagenes && Array.isArray(prenda.imagenes)) {
            prenda.imagenes.forEach((img, idx) => {
                const container = document.createElement('div');
                container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
                
                const imgEl = document.createElement('img');
                
                // Determinar URL de forma robusta
                let src = this._extraerUrl(img);
                
                if (src) {
                    imgEl.src = src;
                    imgEl.alt = `Imagen ${idx + 1}`;
                    imgEl.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
                    
                    // Agregar manejador de error para mostrar placeholder
                    imgEl.onerror = function() {
                        console.warn(` [Imagenes] Error cargando imagen ${idx + 1} desde: ${src}`);
                        // Crear placeholder
                        this.style.display = 'none';
                        const placeholder = document.createElement('div');
                        placeholder.style.cssText = `
                            width: 100%;
                            height: 150px;
                            background: #f3f4f6;
                            border: 2px dashed #d1d5db;
                            border-radius: 4px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-direction: column;
                            color: #9ca3af;
                            font-size: 13px;
                            text-align: center;
                        `;
                        placeholder.innerHTML = `
                            <div style="font-size: 24px; margin-bottom: 4px;">📷</div>
                            <div>Imagen no disponible</div>
                            <div style="font-size: 11px; margin-top: 2px;">${src.split('/').pop()}</div>
                        `;
                        container.appendChild(placeholder);
                    };
                    
                    container.appendChild(imgEl);
                    preview.appendChild(container);
                    console.log(` [Imagenes] Imagen ${idx + 1} cargada`);
                } else {
                    console.warn(` [Imagenes] No se pudo extraer URL de imagen ${idx + 1}`);
                }
            });
        }
        
        //  Replicar a global para que sea editable
        if (prenda.imagenes && Array.isArray(prenda.imagenes) && window.imagenesPrendaStorage?.establecerImagenes) {
            // IMPORTANTE: Procesar imágenes para crear blob URLs si es necesario
            const imagenesConBlobUrl = prenda.imagenes.map((img, idx) => {
                // 🔴 NUEVO: Asegurar que cada imagen tenga un ID (desde BD o generado)
                const imagenConId = {
                    ...img,
                    id: img.id || img.imagen_id || null,  // Intentar obtener ID de BD
                };
                
                // 🔴 CRITICAL FIX: Si tiene File object, SIEMPRE crear un blob URL NUEVO
                // No importa si previewUrl existe - puede estar revocado
                if (img.file instanceof File) {
                    console.log(`[prenda-editor-imagenes] 🔴 RECONSTITUYENDO blob URL para imagen ${idx} (File object presente)`);
                    const nuevoBlob = URL.createObjectURL(img.file);
                    return {
                        ...imagenConId,
                        file: img.file,                    // ← Preservar el File object
                        previewUrl: nuevoBlob,             // ← NUEVO blob URL SIEMPRE desde File
                        nombre: img.nombre || img.file.name || `imagen-${idx + 1}`,
                        tamaño: img.tamaño || img.file.size,
                        fileType: img.file.type,
                        fileSize: img.file.size
                    };
                }
                
                // Si ya tiene previewUrl válido (blob o data URL), mantenerlo
                if (img.previewUrl && (img.previewUrl.startsWith('blob:') || img.previewUrl.startsWith('data:'))) {
                    return imagenConId;
                }
                
                // Si es una string (URL), usar como previewUrl
                if (typeof img === 'string') {
                    return {
                        previewUrl: img,
                        url: img,
                        nombre: `imagen-${idx + 1}`,
                        tamaño: 0
                    };
                }
                
                // Si es un objeto pero no tiene previewUrl, crear uno
                if (typeof img === 'object') {
                    // Crear blob URL si es posible
                    let blobUrl = img.previewUrl;
                    if (!blobUrl && (img.url || img.ruta)) {
                        blobUrl = img.url || img.ruta;
                    }
                    
                    return {
                        ...img,
                        previewUrl: blobUrl || img.url || img.ruta || '',
                        url: img.url || img.ruta || '',
                        nombre: img.nombre || `imagen-${idx + 1}`,
                        tamaño: img.tamaño || 0
                    };
                }
                
                return img;
            });
            
            window.imagenesPrendaStorage.establecerImagenes(imagenesConBlobUrl);
            console.log('[Carga] 📸 Imágenes replicadas en window.imagenesPrendaStorage');
            
            // Reconfigurar DragDrop handler para que click abra galería en vez de file input
            if (window.DragDropManager && typeof window.DragDropManager.actualizarImagenesPrenda === 'function') {
                window.DragDropManager.actualizarImagenesPrenda(imagenesConBlobUrl);
                console.log('[Carga] 📸 DragDrop handler reconfigurado para modo con imágenes');
            }
            
            // Fallback: agregar click handler directo en el preview para abrir galería
            this._configurarClickGaleria(preview, imagenesConBlobUrl);
        }
        
        console.log(' [Imagenes] Completado');
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
            // 🔴 FIX CRÍTICO: Si el objeto tiene un File object en .file,
            // crear un blob URL desde ese File (aunque previewUrl esté revocado)
            if (img.file instanceof File) {
                console.log('[prenda-editor-imagenes] 🔴 DETECTADO File object en img.file - creando blob URL');
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
        
        // Remover handler anterior si existe
        if (preview._galeriaClickHandler) {
            preview.removeEventListener('click', preview._galeriaClickHandler);
        }
        
        // Guardar referencia a la clase para usar en el handler
        const self = this;
        
        preview._galeriaClickHandler = function(e) {
            if (e.target.closest('button') || e.target.closest('input')) return;
            e.preventDefault();
            e.stopPropagation();
            if (typeof Swal === 'undefined') return;
            
            // Obtener URLs actualizadas del storage en el momento del click
            let currentImages = [];
            if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
                currentImages = window.imagenesPrendaStorage.obtenerImagenes();
            }
            if (currentImages.length === 0) return;
            
            const urls = currentImages
                .map(img => img.previewUrl || img.url || img.ruta || (typeof img === 'string' ? img : ''))
                .filter(u => u && u.length > 0);
            if (urls.length === 0) return;
            
            let idx = 0;
            const keyHandler = (ev) => {
                if (!window.__galeriaPrendaActiva) return;
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
                    
                    // 🔴 IMPORTANTE: NO eliminar del servidor aquí
                    // Solo marcar para eliminación cuando se guarden los cambios
                    // Esto permite al usuario cancelar la edición sin perder la imagen
                    
                    if (imagenId) {
                        // Marcar imagen para eliminación (se eliminará al guardar)
                        if (!window.imagenesAEliminar) {
                            window.imagenesAEliminar = [];
                        }
                        if (!window.imagenesAEliminar.includes(imagenId)) {
                            window.imagenesAEliminar.push(imagenId);
                            console.log('[prenda-editor-imagenes] 📝 Imagen marcada para eliminación al guardar', {
                                id: imagenId,
                                totalMarcadas: window.imagenesAEliminar.length
                            });
                        }
                    }
                    
                    // Eliminar del storage local SOLO
                    if (window.imagenesPrendaStorage && window.imagenesPrendaStorage.obtenerImagenes) {
                        const imgs = window.imagenesPrendaStorage.obtenerImagenes();
                        imgs.splice(idx, 1);
                        window.imagenesPrendaStorage.establecerImagenes(imgs);
                        console.log('[prenda-editor-imagenes] 🗑️ Imagen eliminada del storage local (no de BD)', {
                            imagenId: imagenId,
                            imagenesRestantes: imgs.length
                        });
                    }
                    
                    // Actualizar el preview del DOM
                    self._actualizarPreviewDOM(preview);
                    
                    // Si quedan imágenes, reabrir galería
                    const remaining = window.imagenesPrendaStorage?.obtenerImagenes() || [];
                    if (remaining.length > 0) {
                        idx = Math.min(idx, remaining.length - 1);
                        urls.splice(0, urls.length, ...remaining
                            .map(img => img.previewUrl || img.url || img.ruta || '')
                            .filter(u => u));
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
                        <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;">
                            <div style="position:relative; width:100%; max-width:620px;">
                                <img src="${urls[idx]}" alt="Foto prenda" style="width:100%; border-radius:8px; border:1px solid #e5e7eb; object-fit:contain; max-height:65vh;">
                                ${urls.length > 1 ? `
                                    <button id="gal-prenda-prev" style="position:absolute; top:50%; left:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">‹</button>
                                    <button id="gal-prenda-next" style="position:absolute; top:50%; right:-16px; transform:translateY(-50%); background:#111827cc; color:white; border:none; border-radius:50%; width:38px; height:38px; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center;">›</button>
                                ` : ''}
                            </div>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <span style="font-size:0.9rem; color:#4b5563;">${idx + 1} / ${urls.length}</span>
                                <button id="gal-prenda-delete" style="background:#dc2626; color:white; border:none; border-radius:6px; padding:0.4rem 1rem; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; gap:0.3rem;">
                                    <span class="material-symbols-rounded" style="font-size:1rem;">delete</span> Eliminar
                                </button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: true,
                    width: '75%',
                    customClass: { container: 'swal-galeria-container' },
                    didOpen: () => {
                        window.__galeriaPrendaActiva = true;
                        const prev = document.getElementById('gal-prenda-prev');
                        const next = document.getElementById('gal-prenda-next');
                        const del = document.getElementById('gal-prenda-delete');
                        if (prev) prev.onclick = () => { idx = (idx - 1 + urls.length) % urls.length; renderModal(); };
                        if (next) next.onclick = () => { idx = (idx + 1) % urls.length; renderModal(); };
                        if (del) del.onclick = () => eliminarImagenActual();
                        window.addEventListener('keydown', keyHandler);
                    },
                    willClose: () => {
                        window.__galeriaPrendaActiva = false;
                        window.removeEventListener('keydown', keyHandler);
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
     * Actualizar el preview DOM desde el storage actual
     * @private
     */
    static _actualizarPreviewDOM(preview) {
        if (!preview) return;
        const imgs = window.imagenesPrendaStorage?.obtenerImagenes() || [];
        preview.innerHTML = '';
        imgs.forEach((img, idx) => {
            const container = document.createElement('div');
            container.style.cssText = 'position: relative; margin-bottom: 0.5rem;';
            const imgEl = document.createElement('img');
            const src = this._extraerUrl(img);
            if (src) {
                imgEl.src = src;
                imgEl.alt = `Imagen ${idx + 1}`;
                imgEl.style.cssText = 'max-width: 100%; height: auto; border-radius: 4px;';
                container.appendChild(imgEl);
                preview.appendChild(container);
            }
        });
        if (imgs.length === 0) {
            preview.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click para seleccionar o<br>Ctrl+V para pegar imagen</div></div>';
        }
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
