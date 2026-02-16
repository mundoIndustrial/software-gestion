/**
 * 🧵 Módulo de Telas
 * Responsabilidad: Cargar y gestionar tabla de telas
 */

class PrendaEditorTelas {
    /**
     * Cargar telas en la tabla
     */
    static cargar(prenda) {
        console.log('🧵 [Telas] Cargando:', {
            cantidad: prenda.telasAgregadas?.length || 0,
            telas: prenda.telasAgregadas?.map(t => t.tela_nombre || t.tela || t.nombre || 'Sin nombre')
        });
        
        // Buscar tabla
        const tablaTelas = document.querySelector('#tbody-telas');
        if (!tablaTelas) {
            console.warn('❌ [Telas] No encontrado #tbody-telas');
            return;
        }
        
        // Encontrar fila de inputs (para agregar nuevas)
        const filaInputs = tablaTelas.querySelector('[id="nueva-prenda-tela"]')?.closest('tr');
        
        // Eliminar filas viejas (excepto inputs)
        const filasExistentes = tablaTelas.querySelectorAll('tr');
        filasExistentes.forEach(fila => {
            if (fila !== filaInputs) {
                fila.remove();
            }
        });
        
        console.log('[Telas] Filas viejas eliminadas');
        
        // Cargar telas nuevas
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
            prenda.telasAgregadas.forEach((tela, idx) => {
                const fila = this._crearFilaTela(tela, idx);
                
                // Insertar ANTES de la fila de inputs
                if (filaInputs) {
                    filaInputs.parentNode.insertBefore(fila, filaInputs);
                } else {
                    tablaTelas.appendChild(fila);
                }
                
                console.log(`✅ [Telas] ${idx + 1}: ${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}`);
            });
        }
        
        // 🔥 Replicar a global para que sea editable
        // 🔴 NO usar JSON.parse/stringify - destruye File objects y blob URLs
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            window.telasCreacion = prenda.telasAgregadas.map(tela => ({
                ...tela,
                imagenes: tela.imagenes ? [...tela.imagenes] : []
            }));
            console.log('[Carga] 🧵 Telas replicadas en window.telasCreacion (preservando File objects):', window.telasCreacion.length);
        }
        
        console.log('✅ [Telas] Completado');
    }

    /**
     * Crear fila de tela para la tabla
     * @private
     * 🔴 IMPORTANTE: Usa DOM API (createElement) en vez de innerHTML para la imagen.
     * Esto evita problemas con blob: URLs y onerror roto por parsing HTML.
     */
    static _crearFilaTela(tela, idx) {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #e5e7eb';
        
        // Determinar la fuente de imagen y el File object (si existe) para fallback
        let imgSrc = '';
        let fileParaFallback = null;
        
        if (tela.imagenes && Array.isArray(tela.imagenes) && tela.imagenes.length > 0) {
            const imagenValida = tela.imagenes.find(img => img !== null && img !== undefined);
            if (imagenValida) {
                if (imagenValida instanceof File) {
                    fileParaFallback = imagenValida;
                    imgSrc = URL.createObjectURL(imagenValida);
                    console.log(`[Telas] 🔄 Blob URL creada para tela ${idx} desde File object crudo: ${imgSrc.substring(0, 60)}`);
                } else if (typeof imagenValida === 'string') {
                    if (imagenValida.startsWith('data:') || imagenValida.startsWith('blob:')) {
                        imgSrc = imagenValida;
                    } else {
                        imgSrc = imagenValida.startsWith('/') ? imagenValida : '/storage/' + imagenValida;
                    }
                } else if (typeof imagenValida === 'object') {
                    if (imagenValida.file && imagenValida.file instanceof File) {
                        fileParaFallback = imagenValida.file;
                        imgSrc = URL.createObjectURL(imagenValida.file);
                        imagenValida.previewUrl = imgSrc;
                        console.log(`[Telas] 🔄 Blob URL reconstituida para tela ${idx} desde File object`);
                    } else if (imagenValida.previewUrl && !imagenValida.previewUrl.startsWith('blob:')) {
                        imgSrc = imagenValida.previewUrl;
                    } else if (imagenValida.previewUrl && imagenValida.previewUrl.startsWith('blob:')) {
                        imgSrc = imagenValida.previewUrl;
                    } else if (imagenValida.dataURL) {
                        imgSrc = imagenValida.dataURL;
                    } else if (imagenValida.url || imagenValida.ruta || imagenValida.ruta_webp || imagenValida.ruta_original) {
                        const url = imagenValida.url || imagenValida.ruta || imagenValida.ruta_webp || imagenValida.ruta_original;
                        imgSrc = url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url;
                    }
                }
            }
        }
        
        // Construir filas base con innerHTML (SIN la imagen - la imagen va por DOM API)
        fila.innerHTML = `
            <td style="padding: 0.5rem;">${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}</td>
            <td style="padding: 0.5rem;">${tela.color_nombre || tela.color || 'Sin color'}</td>
            <td style="padding: 0.5rem;">${tela.referencia || '-'}</td>
            <td class="td-imagen-tela" style="padding: 0.5rem; text-align: center; vertical-align: top;"></td>
            <td style="padding: 0.5rem; text-align: center;">
                <button type="button" class="btn btn-sm btn-danger" 
                    onclick="eliminarTela(${idx})"
                    title="Eliminar tela">
                    ✕
                </button>
            </td>
        `;
        
        // 🔴 CONSTRUIR IMAGEN VÍA DOM API (evita problemas de innerHTML + blob: + onerror)
        const tdImagen = fila.querySelector('.td-imagen-tela');
        if (imgSrc && tdImagen) {
            const img = document.createElement('img');
            img.style.cssText = 'max-width: 80px; max-height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;';
            img.alt = `Tela ${idx + 1}`;
            
            // Evento de error: si blob URL falla, intentar data URL como fallback
            img.onerror = function() {
                console.warn(`[Telas] ⚠️ Error cargando imagen tela ${idx}, src: ${img.src?.substring(0, 60)}`);
                
                // Si tenemos el File original, re-intentar con data URL (base64)
                if (fileParaFallback && fileParaFallback instanceof File) {
                    console.log(`[Telas] 🔄 Intentando fallback con FileReader (data URL) para tela ${idx}`);
                    const reader = new FileReader();
                    reader.onload = function() {
                        img.onerror = null; // Evitar bucle infinito
                        img.src = reader.result;
                        console.log(`[Telas] ✅ Data URL cargada exitosamente para tela ${idx}`);
                    };
                    reader.onerror = function() {
                        console.error(`[Telas] ❌ FileReader también falló para tela ${idx}`);
                        img.style.display = 'none';
                        tdImagen.innerHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(imagen no disponible)</div>';
                    };
                    reader.readAsDataURL(fileParaFallback);
                } else {
                    // Sin File para fallback, mostrar mensaje
                    img.style.display = 'none';
                    tdImagen.innerHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(imagen no disponible)</div>';
                }
            };
            
            // Evento de carga exitosa
            img.onload = function() {
                console.log(`[Telas] ✅ Imagen tela ${idx} cargada exitosamente`);
            };
            
            // 🔴 Asignar src DESPUÉS de configurar onerror/onload
            img.src = imgSrc;
            tdImagen.appendChild(img);
        } else if (tdImagen) {
            tdImagen.innerHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(sin imagen)</div>';
        }
        
        return fila;
    }

    /**
     * Limpiar tabla de telas
     */
    static limpiar() {
        const tablaTelas = document.querySelector('#tbody-telas');
        if (!tablaTelas) return;
        
        // Eliminar todo excepto fila de inputs
        const filaInputs = tablaTelas.querySelector('[id="nueva-prenda-tela"]')?.closest('tr');
        const filasExistentes = tablaTelas.querySelectorAll('tr');
        
        filasExistentes.forEach(fila => {
            if (fila !== filaInputs) {
                fila.remove();
            }
        });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorTelas;
}
