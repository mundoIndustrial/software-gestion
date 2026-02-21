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
            telas: prenda.telasAgregadas?.map(t => t.nombre_tela || t.tela_nombre || t.tela || t.nombre || 'Sin nombre')
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
        
        // 🔴 NUEVO: Cargar datalist de telas y colores
        console.log('[Telas] 🔄 Verificando si cargarDatalistTelasColores existe:', typeof cargarDatalistTelasColores);
        if (typeof cargarDatalistTelasColores === 'function') {
            console.log('[Telas] ✅ Llamando a cargarDatalistTelasColores con pequeño retraso...');
            // Pequeño retraso para asegurar que el DOM del modal esté listo
            setTimeout(() => {
                cargarDatalistTelasColores();
            }, 100);
        } else {
            console.warn('[Telas] ⚠️ cargarDatalistTelasColores no existe');
        }
        
        // 🔴 NUEVO: Configurar drag & drop para telas
        if (typeof configurarDragDropTela === 'function') {
            configurarDragDropTela();
        }
        
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
                
                console.log(`✅ [Telas] ${idx + 1}: ${tela.nombre_tela || tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}`);
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
            <td style="padding: 0.5rem;">${tela.nombre_tela || tela.tela_nombre || tela.tela || tela.nombre || 'Sin nombre'}</td>
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

// 🔴 NUEVO: Función global para eliminar tela en modal de edición
window.eliminarTela = function(index, event) {
    console.log('[eliminarTela] 🗑️ Iniciando eliminación de tela:', index);
    
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    try {
        const telas = window.telasCreacion;
        if (!telas || index < 0 || index >= telas.length) {
            console.warn('[eliminarTela] Índice inválido:', index);
            return;
        }
        
        const telaAEliminar = telas[index];
        console.log('[eliminarTela] Tela a eliminar:', telaAEliminar);
        
        // 🚨 IMPORTANTE: Eliminar tallas asociadas a esta tela
        if (window.tallasRelacionales && typeof window.tallasRelacionales === 'object') {
            console.log('[eliminarTela] 🧹 Eliminando tallas asociadas a la tela:', telaAEliminar.tela);
            
            let tallasEliminadas = 0;
            for (const genero in window.tallasRelacionales) {
                if (window.tallasRelacionales.hasOwnProperty(genero)) {
                    // Eliminar todas las tallas de este género
                    const generoData = window.tallasRelacionales[genero];
                    console.log('[eliminarTela] 🗑️ Eliminando tallas del género:', genero, 'tallas:', Object.keys(generoData));
                    
                    // Limpiar todas las tallas de este género
                    window.tallasRelacionales[genero] = {};
                    tallasEliminadas += Object.keys(generoData).length;
                }
            }
            
            console.log('[eliminarTela] ✅ Tallas eliminadas:', tallasEliminadas);
            
            // Actualizar el total de prendas
            if (typeof actualizarTotalPrendas === 'function') {
                actualizarTotalPrendas();
            }
            
            // Recargar las tarjetas de tallas para reflejar los cambios
            if (window.PrendaEditorTallas && typeof window.PrendaEditorTallas.cargar === 'function') {
                const prenda = {
                    cantidad_talla: window.tallasRelacionales || {}
                };
                window.PrendaEditorTallas.cargar(prenda);
            }
        }
        
        // Eliminar del array
        telas.splice(index, 1);
        console.log('[eliminarTela] ✅ Tela eliminada. Telas restantes:', telas.length);
        
        // 🚨 IMPORTANTE: Limpiar asignaciones de colores asociadas
        if (window.StateManager && typeof window.StateManager.limpiarAsignaciones === 'function') {
            console.log('[eliminarTela] 🎨 Limpiando asignaciones de colores');
            window.StateManager.limpiarAsignaciones();
        }
        
        // Recargar tabla
        const prenda = {
            telasAgregadas: telas
        };
        PrendaEditorTelas.cargar(prenda);
        
    } catch (error) {
        console.error('[eliminarTela] ❌ Error:', error);
    }
};

// 🔴 NUEVO: Función global para agregar tela en modal de edición
window.agregarTelaNueva = function() {
    console.log('[agregarTelaNueva] 🟦 CLICK DETECTADO EN BOTÓN AGREGAR TELA');
    
    try {
        // Obtener elementos del DOM
        const colorElement = document.getElementById('nueva-prenda-color');
        const telaElement = document.getElementById('nueva-prenda-tela');
        const referenciaElement = document.getElementById('nueva-prenda-referencia');
        
        if (!colorElement || !telaElement || !referenciaElement) {
            console.error('[agregarTelaNueva] ❌ Elementos del modal no encontrados');
            return false;
        }
        
        // Obtener valores
        const color = colorElement.value.trim().toUpperCase();
        const tela = telaElement.value.trim().toUpperCase();
        const referencia = referenciaElement.value.trim().toUpperCase();
        
        console.log('[agregarTelaNueva] 📝 VALORES CAPTURADOS:', { color, tela, referencia });
        
        // 🔴 NUEVO: Validar solo tela (color y referencia son opcionales)
        if (!tela) {
            console.warn('[agregarTelaNueva] ❌ Validación fallida: tela es requerida');
            alert('Por favor completa la Tela');
            return false;
        }
        
        // Verificar duplicados
        const telaExistente = window.telasCreacion.find(t => 
            t.color.toUpperCase() === color && 
            t.tela.toUpperCase() === tela
        );
        
        if (telaExistente) {
            console.warn('[agregarTelaNueva] ⚠️ Tela ya existe');
            alert('Esta tela ya está agregada');
            return false;
        }
        
        // Obtener imágenes temporales
        const imagenesActuales = window.imagenesTelaModalNueva || [];
        
        // Crear objeto de tela
        const nuevaTela = {
            color: color,
            tela: tela,
            referencia: referencia,
            imagenes: [...imagenesActuales],
            fechaCreacion: new Date().toISOString()
        };
        
        console.log('[agregarTelaNueva] 🆕 OBJETO TELA CREADO:', nuevaTela);
        
        // Agregar al array
        window.telasCreacion.push(nuevaTela);
        console.log('[agregarTelaNueva] ✅ Tela agregada. Total:', window.telasCreacion.length);
        
        // Limpiar campos
        colorElement.value = '';
        telaElement.value = '';
        referenciaElement.value = '';
        window.imagenesTelaModalNueva = [];
        
        // Limpiar preview
        const preview = document.getElementById('nueva-prenda-tela-preview');
        if (preview) {
            preview.innerHTML = '';
            preview.style.display = 'none';
        }
        
        // Recargar tabla
        const prenda = {
            telasAgregadas: window.telasCreacion
        };
        PrendaEditorTelas.cargar(prenda);
        
        return true;
        
    } catch (error) {
        console.error('[agregarTelaNueva] ❌ Error:', error);
        return false;
    }
};

// 🔴 NUEVO: Función de prueba para verificar APIs
window.probarApisTelasColores = async function() {
    console.log('[probarApisTelasColores] 🧪 Iniciando prueba de APIs...');
    
    try {
        // Probar API de telas
        console.log('[probarApisTelasColores] 📡 Probando /asesores/api/telas...');
        const responseTelas = await fetch('/asesores/api/telas');
        console.log('[probarApisTelasColores] 📡 Status telas:', responseTelas.status);
        
        if (responseTelas.ok) {
            const dataTelas = await responseTelas.json();
            console.log('[probarApisTelasColores] ✅ API telas funciona:', dataTelas);
        } else {
            console.error('[probarApisTelasColores] ❌ API telas falló:', responseTelas.status);
        }
        
        // Probar API de colores
        console.log('[probarApisTelasColores] 📡 Probando /asesores/api/colores...');
        const responseColores = await fetch('/asesores/api/colores');
        console.log('[probarApisTelasColores] 📡 Status colores:', responseColores.status);
        
        if (responseColores.ok) {
            const dataColores = await responseColores.json();
            console.log('[probarApisTelasColores] ✅ API colores funciona:', dataColores);
        } else {
            console.error('[probarApisTelasColores] ❌ API colores falló:', responseColores.status);
        }
        
    } catch (error) {
        console.error('[probarApisTelasColores] ❌ Error en prueba:', error);
    }
};

// 🔴 NUEVO: Función para cargar datalist de telas y colores
window.cargarDatalistTelasColores = async function() {
    console.log('[cargarDatalistTelasColores] 🔄 Iniciando carga de datalist');
    
    try {
        console.log('[cargarDatalistTelasColores] 📡 Haciendo fetch a /asesores/api/telas...');
        // Cargar telas
        const responseTelas = await fetch('/asesores/api/telas');
        console.log('[cargarDatalistTelasColores] 📡 Respuesta telas:', responseTelas.status, responseTelas.ok);
        
        if (responseTelas.ok) {
            const resultTelas = await responseTelas.json();
            console.log('[cargarDatalistTelasColores] 📦 Datos telas recibidos:', resultTelas);
            // Extraer el array de datos de la respuesta
            let telas = resultTelas.data || resultTelas;
            // 🔴 NUEVO: Manejar caso donde API devuelve objeto en lugar de array
            if (telas && typeof telas === 'object' && !Array.isArray(telas)) {
                telas = Object.values(telas);
            }
            const datalistTelas = document.getElementById('opciones-telas');
            console.log('[cargarDatalistTelasColores] 🔍 Datalist telas encontrado:', !!datalistTelas);
            console.log('[cargarDatalistTelasColores] 📊 Telas array válido:', Array.isArray(telas), 'cantidad:', telas?.length);
            
            if (datalistTelas && Array.isArray(telas)) {
                datalistTelas.innerHTML = '';
                telas.forEach(tela => {
                    const option = document.createElement('option');
                    option.value = tela.nombre;
                    option.setAttribute('data-id', tela.id);
                    option.setAttribute('data-referencia', tela.referencia || '');
                    datalistTelas.appendChild(option);
                });
                console.log('[cargarDatalistTelasColores] ✅ Telas cargadas:', telas.length);
            } else {
                console.warn('[cargarDatalistTelasColores] ⚠️ No se encontraron telas o datalist no existe');
            }
        } else {
            console.error('[cargarDatalistTelasColores] ❌ Error en respuesta de telas:', responseTelas.status);
        }
        
        // Cargar colores
        console.log('[cargarDatalistTelasColores] 📡 Haciendo fetch a /asesores/api/colores...');
        const responseColores = await fetch('/asesores/api/colores');
        console.log('[cargarDatalistTelasColores] 📡 Respuesta colores:', responseColores.status, responseColores.ok);
        
        if (responseColores.ok) {
            const resultColores = await responseColores.json();
            console.log('[cargarDatalistTelasColores] 📦 Datos colores recibidos:', resultColores);
            // Extraer el array de datos de la respuesta
            let colores = resultColores.data || resultColores;
            // 🔴 NUEVO: Manejar caso donde API devuelve objeto en lugar de array
            if (colores && typeof colores === 'object' && !Array.isArray(colores)) {
                colores = Object.values(colores);
            }
            const datalistColores = document.getElementById('opciones-colores');
            console.log('[cargarDatalistTelasColores] 🔍 Datalist colores encontrado:', !!datalistColores);
            console.log('[cargarDatalistTelasColores] 📊 Colores array válido:', Array.isArray(colores), 'cantidad:', colores?.length);
            
            if (datalistColores && Array.isArray(colores)) {
                datalistColores.innerHTML = '';
                colores.forEach(color => {
                    const option = document.createElement('option');
                    option.value = color.nombre;
                    option.setAttribute('data-id', color.id);
                    option.setAttribute('data-codigo', color.codigo || '');
                    datalistColores.appendChild(option);
                });
                console.log('[cargarDatalistTelasColores] ✅ Colores cargados:', colores.length);
            } else {
                console.warn('[cargarDatalistTelasColores] ⚠️ No se encontraron colores o datalist no existe');
            }
        } else {
            console.error('[cargarDatalistTelasColores] ❌ Error en respuesta de colores:', responseColores.status);
        }
    } catch (error) {
        console.error('[cargarDatalistTelasColores] ❌ Error cargando datalist:', error);
    }
};

// 🔴 NUEVO: Función para configurar drag & drop y paste en drop zones de tela
window.configurarDragDropTela = function() {
    console.log('[configurarDragDropTela] 🔄 Configurando drag & drop para telas');
    
    const dropZone = document.getElementById('nueva-prenda-tela-drop-zone');
    if (!dropZone) {
        console.warn('[configurarDragDropTela] ⚠️ Drop zone de tela no encontrada');
        return;
    }
    
    // Drag over
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.style.background = '#e0f2fe';
        dropZone.style.borderColor = '#0066cc';
    });
    
    // Drag leave
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.style.background = '#f0f7ff';
        dropZone.style.borderColor = '#0066cc';
    });
    
    // Drop
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.style.background = '#f0f7ff';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const fileInput = document.getElementById('modal-agregar-prenda-nueva-file-input');
            if (fileInput) {
                fileInput.files = files;
                // Disparar evento change
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        }
    });
    
    // Paste
    dropZone.addEventListener('paste', (e) => {
        e.preventDefault();
        const items = e.clipboardData.items;
        for (let item of items) {
            if (item.kind === 'file' && item.type.startsWith('image/')) {
                const file = item.getAsFile();
                const fileInput = document.getElementById('modal-agregar-prenda-nueva-file-input');
                if (fileInput) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            }
        }
    });
    
    console.log('[configurarDragDropTela] ✅ Drag & drop configurado para telas');
};

// 🔴 NUEVO: Función para configurar drag & drop en previews de procesos
window.configurarDragDropProcesos = function() {
    console.log('[configurarDragDropProcesos] 🔄 INICIO - Configurando drag & drop para procesos');
    console.log('[configurarDragDropProcesos] 📊 Timestamp:', new Date().toISOString());
    console.log('[configurarDragDropProcesos] 🔍 Stack trace:', new Error().stack);
    
    // Configurar para cada preview de proceso (1, 2, 3)
    for (let i = 1; i <= 3; i++) {
        const preview = document.getElementById(`proceso-foto-preview-${i}`);
        if (!preview) {
            console.log(`[configurarDragDropProcesos] ⚠️ Preview ${i} no encontrado`);
            continue;
        }
        
        console.log(`[configurarDragDropProcesos] 🎯 Procesando preview ${i}`);
        console.log(`[configurarDragDropProcesos] 📸 Preview ${i} encontrado:`, preview);
        
        // 🔧 SOLUCIÓN: Eliminar listeners previos clonando el nodo
        // Esto evita la duplicación de listeners que causa doble apertura del input
        console.log(`[configurarDragDropProcesos] 🔄 Clonando preview ${i} para eliminar listeners previos`);
        const newPreview = preview.cloneNode(true);
        console.log(`[configurarDragDropProcesos] ✅ Preview ${i} clonado, reemplazando en DOM`);
        preview.parentNode.replaceChild(newPreview, preview);
        console.log(`[configurarDragDropProcesos] 🔄 Preview ${i} reemplazado en DOM`);
        
        // Click para abrir file input
        console.log(`[configurarDragDropProcesos] 🖱️ Agregando listener CLICK al preview ${i}`);
        newPreview.addEventListener('click', (e) => {
            console.log(`[configurarDragDropProcesos] 🖱️ CLICK detectado en preview ${i}`);
            console.log(`[configurarDragDropProcesos] 📊 Event details:`, {
                target: e.target,
                currentTarget: e.currentTarget,
                timeStamp: e.timeStamp,
                eventPhase: e.eventPhase
            });
            
            const fileInput = document.getElementById(`proceso-foto-input-${i}`);
            console.log(`[configurarDragDropProcesos] 📁 Input file ${i}:`, fileInput);
            
            if (fileInput) {
                console.log(`[configurarDragDropProcesos] 🚀 Abriendo input file ${i}`);
                console.log(`[configurarDragDropProcesos] 📊 Input state antes de click:`, {
                    files: fileInput.files?.length || 0,
                    value: fileInput.value,
                    disabled: fileInput.disabled
                });
                
                // 🔴 BANDERA ANTI-DUPLICACIÓN
                if (!fileInput._abiendoAhora) {
                    console.log(`[configurarDragDropProcesos] ✅ Input ${i} no está siendo abierto, procediendo`);
                    fileInput._abiendoAhora = true;
                    fileInput.click();
                    console.log(`[configurarDragDropProcesos] 🎯 Click ejecutado en input ${i}`);
                    
                    setTimeout(() => {
                        fileInput._abiendoAhora = false;
                        console.log(`[configurarDragDropProcesos] 🔓 Bandera liberada para input ${i}`);
                    }, 500);
                } else {
                    console.warn(`[configurarDragDropProcesos] ⚠️ Input ${i} ya está siendo abierto, IGNORANDO`);
                }
            } else {
                console.error(`[configurarDragDropProcesos] ❌ Input file ${i} NO encontrado`);
            }
        });
        
        console.log(`[configurarDragDropProcesos] ✅ Listener CLICK agregado a preview ${i}`);
        
        // Drag over
        newPreview.addEventListener('dragover', (e) => {
            console.log(`[configurarDragDropProcesos] 🎯 DRAGOVER en preview ${i}`);
            e.preventDefault();
            e.stopPropagation();
            newPreview.style.background = '#e0f2fe';
            newPreview.style.borderColor = '#0066cc';
        });
        
        // Drag leave
        newPreview.addEventListener('dragleave', (e) => {
            console.log(`[configurarDragDropProcesos] 🎯 DRAGLEAVE en preview ${i}`);
            e.preventDefault();
            e.stopPropagation();
            newPreview.style.background = '#f9fafb';
            newPreview.style.borderColor = '#0066cc';
        });
        
        // Drop
        newPreview.addEventListener('drop', (e) => {
            console.log(`[configurarDragDropProcesos] 🎯 DROP en preview ${i}`);
            e.preventDefault();
            e.stopPropagation();
            newPreview.style.background = '#f9fafb';
            
            const files = e.dataTransfer.files;
            console.log(`[configurarDragDropProcesos] 📁 Files recibidos en drop ${i}:`, files.length);
            
            if (files.length > 0) {
                const fileInput = document.getElementById(`proceso-foto-input-${i}`);
                if (fileInput) {
                    fileInput.files = files;
                    console.log(`[configurarDragDropProcesos] 📁 Files asignados a input ${i}`);
                    // Disparar evento change
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                    console.log(`[configurarDragDropProcesos] 📡 Event change disparado en input ${i}`);
                }
            }
        });
        
        // Paste
        newPreview.addEventListener('paste', (e) => {
            console.log(`[configurarDragDropProcesos] 🎯 PASTE en preview ${i}`);
            e.preventDefault();
            const items = e.clipboardData.items;
            console.log(`[configurarDragDropProcesos] 📋 Items en clipboard:`, items.length);
            
            for (let item of items) {
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    const file = item.getAsFile();
                    console.log(`[configurarDragDropProcesos] 📸 Imagen pegada en preview ${i}:`, file.name);
                    const fileInput = document.getElementById(`proceso-foto-input-${i}`);
                    if (fileInput) {
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                        console.log(`[configurarDragDropProcesos] 📡 Event change disparado por paste en input ${i}`);
                    }
                }
            }
        });
        
        console.log(`[configurarDragDropProcesos] ✅ Todos los listeners agregados a preview ${i}`);
    }
    
    console.log('[configurarDragDropProcesos] ✅ FIN - Drag & drop configurado para procesos');
    console.log('[configurarDragDropProcesos] 📊 Timestamp final:', new Date().toISOString());
};

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditorTelas;
}

// 🔍 DIAGNÓSTICO: Probar APIs al cargar el módulo
console.log('[PrendaEditorTelas] 🔍 Módulo cargado, probando APIs...');
setTimeout(() => {
    if (typeof probarApisTelasColores === 'function') {
        probarApisTelasColores();
    }
}, 1000);
