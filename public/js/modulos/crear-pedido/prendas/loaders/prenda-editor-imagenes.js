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
            console.warn('⚠️ [Imagenes] No encontrado #nueva-prenda-foto-preview');
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
                        console.warn(`⚠️ [Imagenes] Error cargando imagen ${idx + 1} desde: ${src}`);
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
                    console.log(`✅ [Imagenes] Imagen ${idx + 1} cargada`);
                } else {
                    console.warn(`⚠️ [Imagenes] No se pudo extraer URL de imagen ${idx + 1}`);
                }
            });
        }
        
        // 🔥 Replicar a global para que sea editable
        if (prenda.imagenes && Array.isArray(prenda.imagenes) && window.imagenesPrendaStorage?.establecerImagenes) {
            window.imagenesPrendaStorage.establecerImagenes(prenda.imagenes);
            console.log('[Carga] 📸 Imágenes replicadas en window.imagenesPrendaStorage');
        }
        
        console.log('✅ [Imagenes] Completado');
    }

    /**
     * Extraer URL de diferentes formatos de imagen
     * @private
     */
    static _extraerUrl(img) {
        if (typeof img === 'string') {
            return img; // URL directa
        }
        
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }
        
        if (typeof img === 'object') {
            return img.url || img.ruta || img.ruta_imagen || img.imagen || null;
        }
        
        return null;
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
