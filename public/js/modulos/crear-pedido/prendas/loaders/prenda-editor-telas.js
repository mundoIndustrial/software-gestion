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
            console.warn(' [Telas] No encontrado #tbody-telas');
            return;
        }
        
        // Encontrar fila de inputs usando el botón "Agregar" (selector más robusto)
        // El botón tiene onclick="agregarTelaNueva()" que es más estable que buscar por ID
        const todasLasFilas = Array.from(tablaTelas.querySelectorAll('tr'));
        const filaInputs = todasLasFilas.find(tr => 
            tr.querySelector('button[onclick="agregarTelaNueva()"]') !== null
        );
        
        console.log('[Telas] Fila de inputs encontrada:', !!filaInputs);
        
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
                
                console.log(` [Telas] ${idx + 1}: ${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}`);
            });
        }
        
        //  Replicar a global para que sea editable
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
            window.telasCreacion = JSON.parse(JSON.stringify(prenda.telasAgregadas));
            console.log('[Carga] 🧵 Telas replicadas en window.telasCreacion:', window.telasCreacion.length);
            // IMPORTANTE: Limpiar telasAgregadas para evitar conflicto en la colección de datos
            window.telasAgregadas = [];
        }
        
        console.log(' [Telas] Completado');
    }

    /**
     * Crear fila de tela para la tabla
     * @private
     */
    static _crearFilaTela(tela, idx) {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #e5e7eb';
        
        // Procesar imágenes de la tela
        let imagenHTML = '';
        
        // Intentar obtener imagen de diferentes campos posibles
        let imagenesArray = [];
        
        if (tela.imagenes) {
            if (Array.isArray(tela.imagenes)) {
                imagenesArray = tela.imagenes;
            } else if (typeof tela.imagenes === 'object') {
                // Si es un objeto, convertirlo a array
                imagenesArray = [tela.imagenes];
            } else if (typeof tela.imagenes === 'string') {
                imagenesArray = [tela.imagenes];
            }
        } else if (tela.imagen) {
            // Alternativa: campo "imagen" singular
            imagenesArray = [tela.imagen];
        } else if (tela.foto) {
            // Otra alternativa: campo "foto"
            imagenesArray = [tela.foto];
        }
        
        if (imagenesArray && imagenesArray.length > 0) {
            // Tomar la primera imagen válida
            const imagenValida = imagenesArray.find(img => img !== null && img !== undefined);
            if (imagenValida) {
                let imgSrc = '';
                
                // Procesar según el tipo de imagen
                if (typeof imagenValida === 'string') {
                    // String: puede ser URL, ruta, o base64
                    if (imagenValida.startsWith('data:') || imagenValida.startsWith('blob:')) {
                        imgSrc = imagenValida;
                    } else {
                        // Ruta de archivo, agregar /storage/ si necesario
                        imgSrc = imagenValida.startsWith('/') ? imagenValida : '/storage/' + imagenValida;
                    }
                } else if (imagenValida instanceof File) {
                    // Si es un verdadero File object, crear blob URL
                    imgSrc = URL.createObjectURL(imagenValida);
                } else if (typeof imagenValida === 'object') {
                    // Objeto con propiedades - intentar múltiples campos
                    if (imagenValida.previewUrl) {
                        imgSrc = imagenValida.previewUrl;
                    } else if (imagenValida.dataURL) {
                        imgSrc = imagenValida.dataURL;
                    } else if (imagenValida.src) {
                        imgSrc = imagenValida.src;
                    } else if (imagenValida.url) {
                        imgSrc = imagenValida.url.startsWith('/') || imagenValida.url.startsWith('http') ? imagenValida.url : '/storage/' + imagenValida.url;
                    } else if (imagenValida.ruta) {
                        imgSrc = imagenValida.ruta.startsWith('/') ? imagenValida.ruta : '/storage/' + imagenValida.ruta;
                    } else if (imagenValida.ruta_webp) {
                        imgSrc = imagenValida.ruta_webp.startsWith('/') ? imagenValida.ruta_webp : '/storage/' + imagenValida.ruta_webp;
                    } else if (imagenValida.ruta_original) {
                        imgSrc = imagenValida.ruta_original.startsWith('/') ? imagenValida.ruta_original : '/storage/' + imagenValida.ruta_original;
                    }
                }
                
                if (imgSrc) {
                    imagenHTML = `
                        <img src="${imgSrc}" 
                            style="max-width: 80px; max-height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;" 
                            alt="Tela ${idx + 1}"
                            onerror="const d=document.createElement('div'); d.style.cssText='font-size: 0.75rem; color: #9ca3af;'; d.textContent='(imagen no disponible)'; this.parentElement.innerHTML=''; this.parentElement.appendChild(d);">
                    `;
                } else {
                    imagenHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(sin imagen)</div>';
                }
            } else {
                imagenHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(sin imagen)</div>';
            }
        } else {
            imagenHTML = '<div style="font-size: 0.75rem; color: #9ca3af;">(sin imagen)</div>';
        }
        
        fila.innerHTML = `
            <td style="padding: 0.5rem;">${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}</td>
            <td style="padding: 0.5rem;">${tela.color_nombre || tela.color || 'Sin color'}</td>
            <td style="padding: 0.5rem;">${tela.referencia || '-'}</td>
            <td style="padding: 0.5rem; text-align: center; vertical-align: top;">
                ${imagenHTML}
            </td>
            <td style="padding: 0.5rem; text-align: center;">
                <button type="button" class="btn btn-sm btn-danger" 
                    onclick="eliminarTela(${idx})"
                    title="Eliminar tela">
                    ✕
                </button>
            </td>
        `;
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
