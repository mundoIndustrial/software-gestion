/**
 * GESTIÓN DE TELAS
 * Sistema centralizado para manejar telas, colores, referencias e imágenes
 * 
 * CARACTERÍSTICAS:
 * - Gestión de múltiples telas con colores, referencias e imágenes
 * - Hasta 3 imágenes por tela
 * - Modal de visualización de imágenes en galería
 * - Tabla dinámica de telas agregadas
 */

// ========== ESTADO GLOBAL DE TELAS ==========
// FLUJO CREACIÓN: Prendas nuevas (NO se afecta por edición)
window.telasCreacion = [];
// FLUJO EDICIÓN: Prendas existentes (en modal-novedad-edicion.js)
window.imagenesTelaModalNueva = [];

// Función para limpiar errores en campos de tela
window.limpiarErrorTela = function(campo) {
    if (campo && campo.classList.contains('campo-error-tela')) {
        campo.classList.remove('campo-error-tela');
        campo.style.borderColor = '';
        campo.style.backgroundColor = '';
        const mensajeError = campo.nextElementSibling;
        if (mensajeError && mensajeError.classList.contains('error-mensaje-tela')) {
            mensajeError.remove();
        }
    }
}

// Agregar event listeners a los campos de tela cuando estén listos
window.inicializarEventosTela = function() {
    const campos = ['nueva-prenda-color', 'nueva-prenda-tela', 'nueva-prenda-referencia'];
    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', function() {
                window.limpiarErrorTela(this);
            });
            campo.addEventListener('focus', function() {
                window.limpiarErrorTela(this);
            });
        }
    });
}

// Llamar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.inicializarEventosTela);
} else {
    window.inicializarEventosTela();
}

//  GUARD: Asegurar que imagenesTelaStorage existe
if (!window.imagenesTelaStorage) {

    window.imagenesTelaStorage = {
        obtenerImagenes: () => [],
        agregarImagen: (file) => {

            return Promise.resolve();
        },
        limpiar: () => {

            return Promise.resolve();
        },
        obtenerBlob: (index) => null
    };
} else {

}

// ========== AGREGAR NUEVA TELA ==========
window.agregarTelaNueva = async function() {

    
    const color = document.getElementById('nueva-prenda-color').value.trim().toUpperCase();
    const tela = document.getElementById('nueva-prenda-tela').value.trim().toUpperCase();
    const referencia = document.getElementById('nueva-prenda-referencia').value.trim().toUpperCase();
    

    // Limpiar errores anteriores
    ['nueva-prenda-color', 'nueva-prenda-tela', 'nueva-prenda-referencia'].forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.classList.remove('campo-error-tela');
            campo.style.borderColor = '';
            campo.style.backgroundColor = '';
            const mensajeError = campo.nextElementSibling;
            if (mensajeError && mensajeError.classList.contains('error-mensaje-tela')) {
                mensajeError.remove();
            }
        }
    });
    
    // Validación con mensajes en rojo
    let errores = [];
    // ✅ MEJORADO: Tela O color (o ambos) son requeridos
    if (!tela && !color) {
        errores.push({ campo: 'nueva-prenda-tela', mensaje: ' Se requiere al menos Tela o Color' });
        errores.push({ campo: 'nueva-prenda-color', mensaje: ' Se requiere al menos Tela o Color' });
    }
    // Referencia es opcional - no se valida
    
    if (errores.length > 0) {
        errores.forEach(error => {
            const campo = document.getElementById(error.campo);
            if (campo) {
                campo.classList.add('campo-error-tela');
                campo.style.borderColor = '#ef4444';
                campo.style.backgroundColor = '#fee2e2';
                const mensajeDiv = document.createElement('div');
                mensajeDiv.classList.add('error-mensaje-tela');
                mensajeDiv.style.color = '#dc2626';
                mensajeDiv.style.fontSize = '0.85rem';
                mensajeDiv.style.marginTop = '4px';
                mensajeDiv.style.fontWeight = '500';
                mensajeDiv.textContent = error.mensaje;
                campo.parentNode.insertBefore(mensajeDiv, campo.nextSibling);
            }
        });
        return;
    }
    
    // Buscar o crear tela en BD (SOLO SI HAY NOMBRE)
    let telaId = null;
    if (tela && tela.trim()) {  // ✅ Solo buscar/crear si tela no está vacía
        const datalistTelas = document.getElementById('opciones-telas');
        if (datalistTelas) {
            // 🔥 BÚSQUEDA EXACTA: Solo match perfecto, sin similares
            for (let option of datalistTelas.options) {
                // Comparación exacta: ambas en MAYÚSCULAS para consistencia
                const opcionNormalizada = option.value.toUpperCase().trim();
                const telaInput = tela.toUpperCase().trim();
                
                if (opcionNormalizada === telaInput) {
                    telaId = parseInt(option.dataset.id);
                    console.log('[guardarTela] ✅ Tela encontrada exactamente:', {
                        búsqueda: telaInput,
                        encontrada: opcionNormalizada,
                        id: telaId
                    });
                    break;
                }
            }
        }
        
        // Si no existe EXACTAMENTE, crearla (NUNCA reutilizar similares)
        if (!telaId) {
            console.log('[guardarTela] 📝 Tela no existe (o no hay coincidencia exacta), creando nueva:', { tela });
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                console.log('[guardarTela] 🔐 CSRF Token obtenido:', { hasToken: !!csrfToken, tokenLength: csrfToken.length });
                
                const payload = { nombre: tela, referencia: referencia };
                console.log('[guardarTela] 📤 Payload a enviar:', payload);
                
                const response = await fetch('/api/public/telas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                
                console.log('[guardarTela] 📨 Response status:', response.status, response.statusText);
                
                const result = await response.json();
                console.log('[guardarTela] 📥 Response JSON:', result);
                
                if (result.success && result.data) {
                    telaId = result.data.id;
                    console.log('[guardarTela] ✅ Tela creada con ID:', telaId);
                    
                    // Agregar al datalist
                    if (datalistTelas) {
                        const newOption = document.createElement('option');
                        newOption.value = result.data.nombre;
                        newOption.dataset.id = result.data.id;
                        newOption.dataset.referencia = result.data.referencia || '';
                        datalistTelas.appendChild(newOption);
                        console.log('[guardarTela] ✅ Opción agregada al datalist');
                    }
                    
                    console.log('[Telas] ✨ Tela creada:', result.data);
                } else {
                    console.error('[guardarTela] ❌ Response sin success o sin data:', result);
                }
            } catch (error) {
                console.error('[Telas] ❌ Error creando tela:', error);
                console.error('[Telas] Stack:', error.stack);
            }
        }
    }
    
    // Buscar o crear color en BD (SOLO SI COLOR NO ESTÁ VACÍO)
    let colorId = null;
    if (color && color.trim()) {  // ✅ Solo buscar/crear si hay color
        const datalistColores = document.getElementById('opciones-colores');
        if (datalistColores) {
            // 🔥 BÚSQUEDA EXACTA: Solo match perfecto, sin similares
            for (let option of datalistColores.options) {
                // Comparación exacta: ambas en MAYÚSCULAS para consistencia
                const opcionNormalizada = option.value.toUpperCase().trim();
                const colorInput = color.toUpperCase().trim();
                
                if (opcionNormalizada === colorInput) {
                    colorId = parseInt(option.dataset.id);
                    console.log('[guardarTela] ✅ Color encontrado exactamente:', {
                        búsqueda: colorInput,
                        encontrado: opcionNormalizada,
                        id: colorId
                    });
                    break;
                }
            }
        }
        
        // Si no existe EXACTAMENTE, crearlo (NUNCA reutilizar similares)
        if (!colorId) {
            console.log('[guardarTela] 📝 Color no existe (o no hay coincidencia exacta), creando nuevo:', { color });
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                console.log('[guardarTela] 🔐 CSRF Token obtenido:', { hasToken: !!csrfToken, tokenLength: csrfToken.length });
                
                const payload = { nombre: color };
                console.log('[guardarTela] 📤 Payload a enviar:', payload);
                
                const response = await fetch('/api/public/colores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                
                console.log('[guardarTela] 📨 Response status:', response.status, response.statusText);
                
                const result = await response.json();
                console.log('[guardarTela] 📥 Response JSON:', result);
                
                if (result.success && result.data) {
                    colorId = result.data.id;
                    console.log('[guardarTela] ✅ Color creado con ID:', colorId);
                    
                    // Agregar al datalist
                    if (datalistColores) {
                        const newOption = document.createElement('option');
                        newOption.value = result.data.nombre;
                        newOption.dataset.id = result.data.id;
                        newOption.dataset.codigo = result.data.codigo || '';
                        datalistColores.appendChild(newOption);
                        console.log('[guardarTela] ✅ Opción de color agregada al datalist');
                    }
                    
                    console.log('[Colores] ✨ Color creado:', result.data);
                } else {
                    console.error('[guardarTela] ❌ Response sin success o sin data:', result);
                }
            } catch (error) {
                console.error('[Colores] ❌ Error creando color:', error);
                console.error('[Colores] Stack:', error.stack);
            }
        }
    }
    
    // Obtener imágenes del storage temporal - SOLO GUARDAR FILE OBJECTS (no blob URLs)
    const imagenesTemporales = window.imagenesTelaStorage.obtenerImagenes();

    
    // Copiar archivos E IMPORTANTE: generar previewUrl para que sean válidas en tabla
    const imagenesCopia = imagenesTemporales.map(img => {
        let previewUrl = '';
        
        // 1. Usar el previewUrl actual si existe (es un blob válido)
        if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            previewUrl = img.previewUrl;
        }
        // 2. Si no hay blob URL, generar uno del File object
        else if (img.file instanceof File) {
            previewUrl = URL.createObjectURL(img.file);
        }
        // 3. Si no hay previewUrl, intentar crear uno del objeto
        else if (img.file) {
            previewUrl = URL.createObjectURL(img.file);
        }
        
        return {
            file: img.file,  // El File object es permanente
            nombre: img.nombre,
            tamaño: img.tamaño,
            previewUrl: previewUrl,  // ✅ CRÍTICO: Incluir previewUrl válida
            url: previewUrl  // Fallback para compatibilidad
        };
    });
    
    console.log('[guardarTela] 📷 Imágenes copiadas con previewUrl:', {
        total: imagenesCopia.length,
        conPreviewUrl: imagenesCopia.filter(i => !!i.previewUrl).length,
        detalles: imagenesCopia.map(i => ({ 
            nombre: i.nombre, 
            tienePreviewUrl: !!i.previewUrl, 
            tieneFile: !!i.file 
        }))
    });
    
    // Agregar a la lista CORRECTA según el modo
    // En EDICIÓN: agregar a window.telasAgregadas (conserva telas de BD + nuevas)
    // En CREACIÓN: agregar a window.telasCreacion
    // 🔥 FIX: Detectar modo edición igual que en gestion-items-pedido.js
    const modoEdicion = window.prendaEditIndex !== null && window.prendaEditIndex !== undefined;
    
    // Si es edición pero telasAgregadas no existe, inicializarlo
    if (modoEdicion && !window.telasAgregadas) {
        window.telasAgregadas = [];
    }
    
    const destino = modoEdicion ? window.telasAgregadas : window.telasCreacion;
    
    destino.push({ 
        color, 
        tela, 
        referencia,
        color_id: colorId || 0,
        tela_id: telaId || 0,
        nombre: tela,  // Nombre general de la tela
        nombre_tela: tela,  // Normalizar para que sea compatible
        color_nombre: color,  // ✅ Explícito para el backend
        tela_nombre: tela,    // ✅ Explícito para el backend
        imagenes: imagenesCopia
    });
    
    console.log(`[guardarTela] 🧵 Tela agregada (Modo: ${modoEdicion ? 'EDICIÓN' : 'CREACIÓN'})`, {
        tela,
        color,
        destino_array: modoEdicion ? 'telasAgregadas' : 'telasCreacion',
        total_telas: destino.length
    });
    


    
    // Limpiar inputs
    document.getElementById('nueva-prenda-color').value = '';
    document.getElementById('nueva-prenda-tela').value = '';
    document.getElementById('nueva-prenda-referencia').value = '';
    
    // Actualizar tabla para mostrar la tela nueva agregada
    if (window.actualizarTablaTelas) {
        window.actualizarTablaTelas();
    }
    
    // NO LIMPIAR window.imagenesTelaStorage aquí - se necesita para enviar las imágenes
    // Se limpiará después de que se envíe el pedido
    // window.imagenesTelaStorage.limpiar();
    
    // Limpiar preview temporal (el que se mostró mientras se agregaban imágenes - ahora dentro de la celda)
    const previewTemporal = document.getElementById('nueva-prenda-tela-preview');
    if (previewTemporal) {
        previewTemporal.innerHTML = '';
        previewTemporal.style.display = 'none'; // Ocultar completamente
    }
    
    // Limpiar input file
    const inputFile = document.getElementById('nueva-prenda-tela-img-input');
    if (inputFile) {
        inputFile.value = '';
    }
    
    // Actualizar tabla

    actualizarTablaTelas();
};

/**
 * Actualizar tabla de telas - OPTIMIZADO CON DOCUMENTFRAGMENT
 * Evita múltiples reflows usando batch rendering
 */
window.actualizarTablaTelas = function() {
    const tbody = document.getElementById('tbody-telas');
    
    if (!tbody) {
        console.warn('[actualizarTablaTelas]  tbody-telas no encontrado');
        return;
    }
    
    // 🔥 CRÍTICO: Asegurar que la fila de inputs SIEMPRE existe
    // Verificar si existe la fila de entrada (la que tiene los inputs)
    let filaEntrada = tbody.querySelector('tr:first-child');
    const tieneInputs = filaEntrada && filaEntrada.querySelector('#nueva-prenda-tela');
    
    if (!tieneInputs) {
        console.warn('[actualizarTablaTelas] ⚠️ Fila de entrada NO encontrada, recreando...');
        // Recrear SOLO la fila de entrada con todos los inputs
        const html = `
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 0.5rem; width: 20%;">
                    <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" list="opciones-telas" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    <datalist id="opciones-telas"></datalist>
                </td>
                <td style="padding: 0.5rem; width: 20%;">
                    <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" list="opciones-colores" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    <datalist id="opciones-colores"></datalist>
                </td>
                <td style="padding: 0.5rem; width: 20%;">
                    <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem; text-align: center; vertical-align: top; width: 20%;">
                    <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar imagen (opcional)">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                    </button>
                    <input type="file" id="nueva-prenda-tela-img-input" accept="image/*" style="display: none;" onchange="manejarImagenTela(this)">
                    <div id="nueva-prenda-tela-preview" style="display: none; flex-wrap: wrap; gap: 0.5rem; justify-content: center; align-items: flex-start; margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 4px;"></div>
                </td>
                <td style="padding: 0.5rem; text-align: center; width: 20%;">
                    <button type="button" onclick="agregarTelaNueva()" class="btn btn-success btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;" title="Agregar esta tela">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">add</span>Agregar
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML = html;
        console.log('[actualizarTablaTelas] ✅ Fila de entrada recreada exitosamente');
    } else {
        // La fila de entrada existe, solo limpiar las filas de telas
        console.log('[actualizarTablaTelas] ✓ Fila de entrada ya existe, limpiando filas de telas agregadas');
        const filas = Array.from(tbody.querySelectorAll('tr'));
        // Eliminar todas EXCEPTO la primera (que tiene los inputs)
        for (let i = filas.length - 1; i > 0; i--) {
            filas[i].remove();
        }
    }
    
    // 🔥 CRITERIO DE SELECCIÓN: Detectar modo edición PRIMERO
    const enModoEdicion = window.prendaEditIndex !== null && window.prendaEditIndex !== undefined;
    
    console.log('[actualizarTablaTelas] 🔍 Contexto:',  {
        enModoEdicion: enModoEdicion,
        prendaEditIndex: window.prendaEditIndex,
        telasAgregadas_length: window.telasAgregadas?.length || 0,
        telasCreacion_length: window.telasCreacion?.length || 0,
        telasEdicion_length: window.telasEdicion?.length || 0
    });
    
    let telasParaMostrar = [];
    let fuente = '';
    
    if (enModoEdicion) {
        telasParaMostrar = window.telasAgregadas || [];
        fuente = 'telasAgregadas (EDICIÓN)';
    } else {
        if (window.telasAgregadas && window.telasAgregadas.length > 0) {
            telasParaMostrar = window.telasAgregadas;
            fuente = 'telasAgregadas (LEGACY)';
        } else if (window.telasEdicion && window.telasEdicion.length > 0) {
            telasParaMostrar = window.telasEdicion;
            fuente = 'telasEdicion';
        } else {
            telasParaMostrar = window.telasCreacion || [];
            fuente = 'telasCreacion (CREACIÓN)';
        }
    }
    
    console.log(`[actualizarTablaTelas] 📦 Usando ${fuente}:`, {
        cantidad: telasParaMostrar.length,
        datos: telasParaMostrar.map((t, i) => ({ 
            index: i, 
            nombre: t.nombre_tela || t.tela,
            color: t.color
        }))
    });
    
    // 🔥 SI NO HAY TELAS, la tabla solo tiene la fila de entrada (que ya está ahí)
    if (!telasParaMostrar || telasParaMostrar.length === 0) {
        console.log('[actualizarTablaTelas] ℹ️ Sin telas agregadas - mostrando solo fila de entrada');
        return;
    }

    // Usar DocumentFragment para batch rendering
    const fragment = document.createDocumentFragment();
    
    telasParaMostrar.forEach((telaData, index) => {
        // Normalizar datos
        const nombre_tela = telaData.nombre_tela || telaData.tela || telaData.nombre || '(Sin nombre)';
        const color = telaData.color || telaData.color_nombre || '(Sin color)';
        const referencia = telaData.referencia || telaData.tela_referencia || '';
        
        console.log(`[actualizarTablaTelas] 📋 Procesando tela ${index} para mostrar:`, {
            nombre_tela,
            color,
            referencia: `"${referencia}"`,
            origen: telaData.origen || 'desconocido'
        });
        
        // Crear celda de imágenes
        let imagenHTML = '';
        if (telaData.imagenes && telaData.imagenes.length > 0) {
            const imagenConBlobUrl = telaData.imagenes.map((img) => {
                let blobUrl;
                
                if (!img.urlDesdeDB && img.previewUrl) {
                    blobUrl = img.previewUrl;
                }
                else if (img.urlDesdeDB && (img.url || img.ruta)) {
                    blobUrl = img.url || img.ruta;
                }
                else if (img.file instanceof File) {
                    blobUrl = URL.createObjectURL(img.file);
                }
                else if (img.previewUrl) {
                    blobUrl = img.previewUrl;
                } else if (img.blobUrl) {
                    blobUrl = img.blobUrl;
                } else if (typeof img === 'string' && img.trim()) {
                    blobUrl = img;
                } else if (img.ruta_webp && img.ruta_webp.trim()) {
                    blobUrl = img.ruta_webp;
                } else if (img.ruta_original && img.ruta_original.trim()) {
                    blobUrl = img.ruta_original;
                } else if (img instanceof Blob) {
                    blobUrl = URL.createObjectURL(img);
                } else {
                    console.warn(`[actualizarTablaTelas] ⚠️ Imagen sin ruta válida en tela ${index}:`, img);
                    blobUrl = '';
                }
                
                return { ...img, previewUrl: blobUrl };
            });
            
            console.log(`[actualizarTablaTelas] 📸 [Tela ${index}] Imágenes procesadas: ${imagenConBlobUrl.length}`);
            
            imagenHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                    ${imagenConBlobUrl[0].previewUrl ? `
                        <img src="${imagenConBlobUrl[0].previewUrl}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;" onclick="mostrarGaleriaImagenesTela(null, ${index}, 0)">
                        ${imagenConBlobUrl.length > 1 ? `<span style="background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${imagenConBlobUrl.length - 1}</span>` : ''}
                    ` : `
                        <span style="color: #999; font-size: 0.875rem;">Sin foto</span>
                    `}
                </div>
            `;
        }
        
        const tr = document.createElement('tr');
        tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
        tr.innerHTML = `
            <td style="padding: 0.75rem; vertical-align: middle;">${nombre_tela}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${color}</td>
            <td style="padding: 0.75rem; vertical-align: middle;">${referencia}</td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle; min-height: 60px; display: table-cell;">
                ${imagenHTML}
            </td>
            <td style="padding: 0.75rem; text-align: center; vertical-align: middle;">
                <button type="button" onclick="eliminarTela(${index}, event)" class="btn btn-sm" style="background: #ef4444; color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem; border: none; cursor: pointer; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
                </button>
            </td>
        `;
        
        fragment.appendChild(tr);
    });
    
    // Agregar todas las filas al tbody
    tbody.appendChild(fragment);
    console.log(`[actualizarTablaTelas] ✅ Tabla actualizada con ${telasParaMostrar.length} telas`);
};

/**
 * Eliminar tela con confirmación
 */
window.eliminarTela = function(index, event) {
    // Prevenir propagación de eventos para evitar clicks accidentales
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const confirmModal = document.createElement('div');
    confirmModal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 999999;';
    
    // Prevenir clicks en el fondo
    confirmModal.addEventListener('click', (e) => {
        if (e.target === confirmModal) {
            confirmModal.remove();
        }
    });
    
    const confirmBox = document.createElement('div');
    confirmBox.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);';
    
    const titulo = document.createElement('h3');
    titulo.textContent = '¿Eliminar esta tela?';
    titulo.style.cssText = 'margin: 0 0 1rem 0; color: #1f2937; font-size: 1.25rem;';
    confirmBox.appendChild(titulo);
    
    const mensaje = document.createElement('p');
    mensaje.textContent = 'Esta acción no se puede deshacer.';
    mensaje.style.cssText = 'margin: 0 0 1.5rem 0; color: #6b7280; font-size: 0.95rem;';
    confirmBox.appendChild(mensaje);
    
    const botones = document.createElement('div');
    botones.style.cssText = 'display: flex; gap: 1rem; justify-content: flex-end;';
    
    const btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.type = 'button';
    btnCancelar.style.cssText = 'background: #e5e7eb; color: #1f2937; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnCancelar.onmouseover = () => btnCancelar.style.background = '#d1d5db';
    btnCancelar.onmouseout = () => btnCancelar.style.background = '#e5e7eb';
    btnCancelar.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        confirmModal.remove();
    };
    botones.appendChild(btnCancelar);
    
    const btnConfirmar = document.createElement('button');
    btnConfirmar.textContent = 'Eliminar';
    btnConfirmar.type = 'button';
    btnConfirmar.style.cssText = 'background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 500; transition: background 0.2s;';
    btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#dc2626';
    btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#ef4444';
    btnConfirmar.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        confirmModal.remove();

        // 🔥 CRITERIO DE SELECCIÓN MEJORADO: Determinar cuál variable usar
        // SI: Estamos en modo EDICIÓN (prendaEditIndex existe)
        // ENTONCES: Usar SIEMPRE window.telasAgregadas
        // SINO: Usar telasCreacion (modo creación)
        
        const enModoEdicion = window.prendaEditIndex !== null && window.prendaEditIndex !== undefined;
        
        console.log('[eliminarTela] 🔍 Contexto:', {
            enModoEdicion: enModoEdicion,
            prendaEditIndex: window.prendaEditIndex,
            telasAgregadas_existe: !!window.telasAgregadas,
            telasAgregadas_length: window.telasAgregadas?.length || 0,
            telasCreacion_existe: !!window.telasCreacion,
            telasCreacion_length: window.telasCreacion?.length || 0,
            telasEdicion_existe: !!window.telasEdicion,
            telasEdicion_length: window.telasEdicion?.length || 0,
            index: index
        });
        
        let arrayAModificar = null;
        let nombreArray = '';
        
        // LÓGICA DE SELECCIÓN
        if (enModoEdicion) {
            // EN MODO EDICIÓN: SIEMPRE usar telasAgregadas
            if (!window.telasAgregadas) {
                window.telasAgregadas = [];
                console.warn('[eliminarTela] ⚠️ telasAgregadas no existía, inicializado como array vacío');
            }
            arrayAModificar = window.telasAgregadas;
            nombreArray = 'telasAgregadas (EDICIÓN)';
            console.log('[eliminarTela] 📦 MODO EDICIÓN: Usando telasAgregadas');
        } else {
            // EN MODO CREACIÓN: Usar telasCreacion como predeterminado
            // Pero si telasAgregadas tiene datos, usarlo (puede ser legacy)
            if (window.telasAgregadas && window.telasAgregadas.length > 0) {
                arrayAModificar = window.telasAgregadas;
                nombreArray = 'telasAgregadas (LEGACY)';
                console.log('[eliminarTela] 📦 MODO CREACIÓN: Usando telasAgregadas (legacy con datos)');
            } else if (window.telasEdicion && window.telasEdicion.length > 0) {
                arrayAModificar = window.telasEdicion;
                nombreArray = 'telasEdicion';
                console.log('[eliminarTela] 📦 MODO CREACIÓN: Usando telasEdicion');
            } else {
                // Fallback a telasCreacion
                if (!window.telasCreacion) {
                    window.telasCreacion = [];
                    console.warn('[eliminarTela] ⚠️ telasCreacion no existía, inicializado como array vacío');
                }
                arrayAModificar = window.telasCreacion;
                nombreArray = 'telasCreacion';
                console.log('[eliminarTela] 📦 MODO CREACIÓN: Usando telasCreacion');
            }
        }
        
        // Verificar que el array existe y tieneélementos antes de eliminar
        if (arrayAModificar && arrayAModificar.length > index) {
            const telaEliminada = arrayAModificar[index];
            console.log(`[eliminarTela] 🗑️ Eliminando tela index ${index} de ${nombreArray}:`, {
                nombre_tela: telaEliminada.nombre_tela,
                color: telaEliminada.color,
                arrayAntes: arrayAModificar.length,
                arrayDespues: arrayAModificar.length - 1
            });
            
            arrayAModificar.splice(index, 1);
            
            console.log(`[eliminarTela] ✅ Tela eliminada correctamente. Array ahora tiene ${arrayAModificar.length} telas`);
        } else {
            console.error('[eliminarTela] ❌ ERROR: No se puede eliminar, index inválido o array vacío', {
                arrayLength: arrayAModificar?.length || 0,
                index: index,
                nombreArray: nombreArray
            });
        }
        
        // Actualizar tabla
        actualizarTablaTelas();
    };
    botones.appendChild(btnConfirmar);
    
    confirmBox.appendChild(botones);
    confirmModal.appendChild(confirmBox);
    document.body.appendChild(confirmModal);
    
    // Enfoque en el botón de cancelar para evitar acciones accidentales
    btnCancelar.focus();
};

/**
 * Manejar imagen de tela
 */
window.manejarImagenTela = function(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    const file = input.files[0];
    
    // Validar que sea imagen
    if (!file.type.startsWith('image/')) {
        alert('Por favor selecciona una imagen válida');
        return;
    }
    
    // Verificar límite de 3 imágenes
    if (window.imagenesTelaStorage.obtenerImagenes().length >= 3) {
        alert('Máximo 3 imágenes por tela');
        return;
    }
    
    // Agregar imagen al storage
    window.imagenesTelaStorage.agregarImagen(file)
        .then(() => {

            
            //  Actualizar preview temporal en la primera fila
            const preview = document.getElementById('nueva-prenda-tela-preview');
            if (preview) {
                preview.style.display = 'flex';
                preview.innerHTML = '';
                
                const imagenes = window.imagenesTelaStorage.obtenerImagenes();
                imagenes.forEach((img, idx) => {
                    const imgEl = document.createElement('img');
                    imgEl.src = img.previewUrl;
                    imgEl.style.cssText = 'width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;';
                    imgEl.onclick = () => {

                    };
                    preview.appendChild(imgEl);
                });
                
                // Mostrar badge de cantidad si hay más de 1
                if (imagenes.length > 1) {
                    const badge = document.createElement('span');
                    badge.style.cssText = 'position: absolute; background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; margin-left: -12px; margin-top: 30px;';
                    badge.textContent = `+${imagenes.length - 1}`;
                    preview.appendChild(badge);
                }
                

            }
            
            input.value = '';
        })
        .catch(err => {
            alert(err.message);
        });
};

/**
 *  DEPRECATED: Ya no se usa - las imágenes de tela se renderizan en actualizarTablaTelas()
 * Las imágenes se renderizaban en este punto, pero causaba errores porque la fila
 * de la tela aún no existía en la tabla. Ahora solo se renderizan cuando la tela
 * se agrega y se crea su fila correspondiente.
 */
window.actualizarPreviewTela = function() {

};

/**
 * Mostrar galería de imágenes temporales (antes de guardar tela)
 */
window.mostrarGaleriaImagenesTemporales = function(imagenes, indiceInicial = 0) {
    if (!imagenes || imagenes.length === 0) return;
    
    window.imagenesTelaModalNueva = imagenes;
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 100001; padding: 0;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; height: calc(100% - 120px); padding: 2rem;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL en lugar de base64
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {
        indiceActual = (indiceActual - 1 + imagenes.length) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnAnterior);
    
    //  BOTÓN ELIMINAR REMOVIDO - Solo usar la X para cerrar la galería
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {
        indiceActual = (indiceActual + 1) % imagenes.length;
        imgModal.src = imagenes[indiceActual].previewUrl;  // Usar blob URL
        contador.textContent = (indiceActual + 1) + ' de ' + imagenes.length;
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    btnCerrar.onclick = () => modal.remove();
    toolbar.appendChild(btnCerrar);
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
};

/**
 * Obtener telas para envío (FLUJO CREACIÓN)
 */
window.obtenerTelasParaEnvio = function() {

    return window.telasCreacion;
};

/**
 * Limpiar todas las telas (FLUJO CREACIÓN)
 */
window.limpiarTelas = function() {

    window.telasCreacion = [];
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
    actualizarTablaTelas();
};

/**
 * NUEVA GALERÍA: Mostrar galería de imágenes de tela (mismo comportamiento que prendas)
 * @param {Array} imagenes - Array de imágenes de la tela
 * @param {number} telaIndex - Índice de la tela en la tabla
 * @param {number} indiceInicial - Índice inicial a mostrar
 */
window.mostrarGaleriaImagenesTela = function(imagenes, telaIndex = 0, indiceInicial = 0) {
    //  Obtener la tela específica y sus imágenes (fuente de verdad por tela)
    const telaActual = window.telasAgregadas && window.telasAgregadas[telaIndex] ? window.telasAgregadas[telaIndex] : null;
    if (!telaActual) {

        return;
    }
    const imagenesActuales = telaActual.imagenes || [];
    
    if (!imagenesActuales || imagenesActuales.length === 0) {

        return;
    }
    
    //  Evitar que se reabra la galería mientras está en uso
    if (window.__galeriaTelaAbierta) {

        return;
    }
    window.__galeriaTelaAbierta = true;
    

    
    // Crear nuevos blob URLs para evitar que se revoquen
    const imagenesConBlobUrl = imagenesActuales.map((img, idx) => {
        let blobUrl;
        if (img.file instanceof File || img.file instanceof Blob) {
            blobUrl = URL.createObjectURL(img.file);
        } else if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
            blobUrl = img.previewUrl;
        } else {

            return null;
        }
        return {
            ...img,
            previewUrl: blobUrl,
            blobUrl: blobUrl
        };
    }).filter(img => img !== null);
    
    if (imagenesConBlobUrl.length === 0) {

        window.__galeriaTelaAbierta = false;
        return;
    }
    
    let indiceActual = indiceInicial;
    
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); display: flex; flex-direction: column; align-items: center; justify-content: flex-start; z-index: 10000; padding: 0; margin: 0; overflow: hidden;';
    
    const container = document.createElement('div');
    container.style.cssText = 'position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; max-width: 100%; max-height: 100%;';
    
    const imgContainer = document.createElement('div');
    imgContainer.style.cssText = 'flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%; padding: 2rem 1rem; overflow: hidden;';
    
    const imgModal = document.createElement('img');
    imgModal.src = imagenesConBlobUrl[indiceActual].previewUrl;
    imgModal.style.cssText = 'width: 90vw; height: 85vh; border-radius: 8px; object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.7);';
    
    imgContainer.appendChild(imgModal);
    
    //  Función auxiliar para actualizar la imagen
    const actualizarImagen = (nuevoIndice) => {
        indiceActual = nuevoIndice;
        const newBlobUrl = imagenesConBlobUrl[indiceActual].previewUrl;
        imgModal.src = '';
        imgModal.src = newBlobUrl;
        contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;

    };
    
    // Toolbar
    const toolbar = document.createElement('div');
    toolbar.style.cssText = 'display: flex; justify-content: center; align-items: center; width: 100%; gap: 1rem; padding: 1.5rem; background: rgba(0,0,0,0.5);';
    
    const btnAnterior = document.createElement('button');
    btnAnterior.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_back</span>';
    btnAnterior.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnAnterior.onmouseover = () => btnAnterior.style.background = '#0052a3';
    btnAnterior.onmouseout = () => btnAnterior.style.background = '#0066cc';
    btnAnterior.onclick = () => {

        const nuevoIndice = (indiceActual - 1 + imagenesConBlobUrl.length) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnAnterior);
    
    //  BOTÓN ELIMINAR REMOVIDO - Solo usar la X del formulario para eliminar
    // Las imágenes de telas se eliminan desde el formulario, no desde la galería
    
    const contador = document.createElement('div');
    contador.style.cssText = 'color: white; font-size: 0.95rem; font-weight: 500; min-width: 80px; text-align: center;';
    contador.textContent = (indiceActual + 1) + ' de ' + imagenesConBlobUrl.length;
    toolbar.appendChild(contador);
    
    const btnSiguiente = document.createElement('button');
    btnSiguiente.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">arrow_forward</span>';
    btnSiguiente.style.cssText = 'background: #0066cc; color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnSiguiente.onmouseover = () => btnSiguiente.style.background = '#0052a3';
    btnSiguiente.onmouseout = () => btnSiguiente.style.background = '#0066cc';
    btnSiguiente.onclick = () => {

        const nuevoIndice = (indiceActual + 1) % imagenesConBlobUrl.length;
        actualizarImagen(nuevoIndice);
    };
    toolbar.appendChild(btnSiguiente);
    
    const btnCerrar = document.createElement('button');
    btnCerrar.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.5rem;">close</span>';
    btnCerrar.style.cssText = 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; cursor: pointer; padding: 0.75rem; display: flex; align-items: center; justify-content: center; transition: background 0.2s; width: 50px; height: 50px;';
    btnCerrar.onmouseover = () => btnCerrar.style.background = 'rgba(255,255,255,0.3)';
    btnCerrar.onmouseout = () => btnCerrar.style.background = 'rgba(255,255,255,0.2)';
    
    let cerrando = false;
    btnCerrar.onclick = () => {
        if (cerrando) return;
        cerrando = true;

        cerrarGaleria();
    };
    toolbar.appendChild(btnCerrar);
    
    // Cerrar con ESC
    const handleEsc = (e) => {
        if (e.key === 'Escape') {

            cerrarGaleria();
        }
    };
    document.addEventListener('keydown', handleEsc);
    
    // Cerrar al clickear afuera
    modal.onclick = (e) => {
        if (e.target === modal) {

            cerrarGaleria();
        }
    };
    
    //  Función para cerrar la galería y limpiar flags
    const cerrarGaleria = () => {
        document.removeEventListener('keydown', handleEsc);
        modal.remove();
        window.__galeriaTelaAbierta = false;
    };
    
    container.appendChild(imgContainer);
    container.appendChild(toolbar);
    modal.appendChild(container);
    document.body.appendChild(modal);
    

};
