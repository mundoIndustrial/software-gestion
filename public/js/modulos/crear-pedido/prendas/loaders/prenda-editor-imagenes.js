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
                // 🔴 CRITICAL FIX: Si tiene File object, SIEMPRE crear un blob URL NUEVO
                // No importa si previewUrl existe - puede estar revocado
                if (img.file instanceof File) {
                    console.log(`[prenda-editor-imagenes] 🔴 RECONSTITUYENDO blob URL para imagen ${idx} (File object presente)`);
                    const nuevoBlob = URL.createObjectURL(img.file);
                    return {
                        ...img,
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
                    return img;
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
     * Limpiar imágenes
     */
    static limpiar() {
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (preview) {
            preview.innerHTML = '';
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorImagenes;
}
