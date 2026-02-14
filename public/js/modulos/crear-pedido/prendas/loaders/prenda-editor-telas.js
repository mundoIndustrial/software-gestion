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
        
        // 🔴 CRÍTICO: RESETEAR window.telasCreacion COMPLETAMENTE ANTES DE CARGAR
        // Esto evita que telas de prenda anterior contaminen la prenda actual
        console.log('[Telas] 💣 RESET EXPLOSIVO - Limpiando window.telasCreacion ANTES de cargar');
        console.log('[Telas]   ANTES:', window.telasCreacion);
        window.telasCreacion = [];
        window.telasAgregadas = [];
        console.log('[Telas]   DESPUÉS:', window.telasCreacion);
        
        // 🔴 CRÍTICO: LIMPIAR imagenesTelaStorage SOLO cuando se ABRE una NUEVA prenda
        // NO limpiar durante guardado/cierre, eso se hace aquí al CARGAR
        if (window.imagenesTelaStorage && typeof window.imagenesTelaStorage.limpiar === 'function') {
            console.log('[Telas] 🧹 Limpiando OLD imagenesTelaStorage para NUEVA prenda');
            const imagenesAntes = window.imagenesTelaStorage.obtenerImagenes?.() || [];
            console.log('[Telas] 🧹 Imágenes de tela ANTES de limpiar:', imagenesAntes.length, imagenesAntes);
            window.imagenesTelaStorage.limpiar();
            const imagenesDespues = window.imagenesTelaStorage.obtenerImagenes?.() || [];
            console.log('[Telas] 🧹 Imágenes de tela DESPUÉS de limpiar:', imagenesDespues.length);
        }
        
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
                
                // 🔴 CAMBIO: Insertar DESPUÉS de la fila de inputs (no antes)
                // Esto hace que la fila de inputs quede ARRIBA y las telas existentes ABAJO
                if (filaInputs) {
                    filaInputs.parentNode.insertBefore(fila, filaInputs.nextSibling);
                } else {
                    tablaTelas.appendChild(fila);
                }
                
                console.log(` [Telas] ${idx + 1}: ${tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}`);
            });
        }
        
        //  Replicar a global para que sea editable
        // ⚠️ CRÍTICO: NO usar JSON.stringify porque DESTRUYE File objects y blob URLs
        if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas) && prenda.telasAgregadas.length > 0) {
            // Hacer copia profunda que preserve File objects y datos de imagen
            // Approach: spread cada tela y copiar su array de imágenes
            window.telasCreacion = prenda.telasAgregadas.map(tela => ({
                ...tela,
                imagenes: tela.imagenes ? [...tela.imagenes] : []  // Copia el array de imágenes sin procesar
            }));
            
            // 🔍 DEBUG PROFUNDO: Mostrar exactamente qué se cargó
            const detallesDebug = window.telasCreacion[0]?.imagenes?.map(img => ({
                tipo: typeof img,
                esFile: img instanceof File,
                constructor: img?.constructor?.name || 'N/A',
                toStringValor: Object.prototype.toString.call(img),
                campos_enumerables: Object.keys(img || {}),
                campos_propios: Object.getOwnPropertyNames(img || {}),
                // Valores directos
                previewUrl: img?.previewUrl,
                ruta: img?.ruta,
                ruta_original: img?.ruta_original,
                ruta_webp: img?.ruta_webp,
                url: img?.url,
                id: img?.id,
                stringify: JSON.stringify(img)
            })) || [];
            
            console.log('[Carga] 🧵 Telas replicadas en window.telasCreacion (SIN stringify/parse):', {
                cantidad: window.telasCreacion.length,
                primeraTela: window.telasCreacion[0]?.tela,
                imagenesEnPrimera: window.telasCreacion[0]?.imagenes?.length,
                detallesImagenesProfundo: detallesDebug
            });
            
            // IMPORTANTE: Limpiar telasAgregadas para evitar conflicto en la colección de datos
            window.telasAgregadas = [];
        } else {
            // 🔴 CRÍTICO: Si la prenda NO tiene telas (nueva prenda o sin telas), resetear telasCreacion
            console.log('[Carga] 🧹 Prenda sin telas: reseteando window.telasCreacion a []');
            window.telasCreacion = [];
            window.telasAgregadas = [];
        }
        
        console.log(' [Telas] Completado');
    }

    /**
     * 🧹 LIMPIAR TABLA DE TELAS
     * Se ejecuta cuando se abre el modal (CREATE o EDIT)
     * Elimina todas las filas exceptuando la fila de inputs
     * TAMBIÉN RESETS window.telasCreacion para evitar fantasmas de prenda anterior
     */
    static limpiarTabla() {
        console.log('═══════════════════════════════════════════════════');
        console.log('[Telas.limpiarTabla] 🧹 INICIANDO LIMPIEZA');
        console.log('═══════════════════════════════════════════════════');
        
        // Estado ANTES de limpiar
        console.log('[Telas.limpiarTabla] 📊 ESTADO ANTES:');
        console.log('  window.telasCreacion:', window.telasCreacion);
        console.log('  window.telasCreacion.length:', window.telasCreacion?.length);
        if (window.telasCreacion && window.telasCreacion.length > 0) {
            console.log('  Primera tela:', window.telasCreacion[0]?.tela);
            console.log('  Primera tela imagenes:', window.telasCreacion[0]?.imagenes?.length);
        }
        
        // 🔴 CRÍTICO: Resetear variables globales PRIMERO
        console.log('[Telas.limpiarTabla] 🧹 Reseteando variables globales...');
        window.telasCreacion = [];
        window.telasAgregadas = [];
        
        // Verificar que se reseteó
        console.log('[Telas.limpiarTabla] ✓ DESPUÉS de resetear window.telasCreacion:', window.telasCreacion);
        console.log('[Telas.limpiarTabla] ✓ DESPUÉS de resetear window.telasAgregadas:', window.telasAgregadas);
        
        const tablaTelas = document.querySelector('#tbody-telas');
        if (!tablaTelas) {
            console.warn('[Telas.limpiarTabla] ❌ No encontrado #tbody-telas');
            return;
        }

        // Encontrar fila de inputs
        const todasLasFilas = Array.from(tablaTelas.querySelectorAll('tr'));
        console.log('[Telas.limpiarTabla] 📊 Total de filas en tabla:', todasLasFilas.length);
        
        const filaInputs = todasLasFilas.find(tr => 
            tr.querySelector('button[onclick="agregarTelaNueva()"]') !== null
        );
        
        console.log('[Telas.limpiarTabla] 📌 Fila de inputs encontrada:', !!filaInputs);

        // Eliminar filas viejas (excepto inputs)
        const filasExistentes = tablaTelas.querySelectorAll('tr');
        let filasEliminadas = 0;
        filasExistentes.forEach((fila, idx) => {
            if (fila !== filaInputs) {
                console.log(`[Telas.limpiarTabla] 🗑️ Eliminando fila ${idx}:`, fila.textContent.substring(0, 50));
                fila.remove();
                filasEliminadas++;
            } else {
                console.log(`[Telas.limpiarTabla] ✓ Conservando fila de inputs`);
            }
        });

        console.log('[Telas.limpiarTabla] 🎉 COMPLETADO');
        console.log('  - Filas eliminadas:', filasEliminadas);
        console.log('  - telasCreacion reseteado a:', window.telasCreacion);
        console.log('═══════════════════════════════════════════════════');
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
