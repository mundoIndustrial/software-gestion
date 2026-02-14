/**
 * ================================================
 * TELAS MODULE - UI Y RENDERIZADO
 * ================================================
 * 
 * Funciones para renderizar la tabla de telas
 * Actualización dinámica y optimizada del DOM
 * 
 * @module TelasModule
 * @version 2.0.0
 */

/**
 * Actualizar tabla de telas - OPTIMIZADO CON DOCUMENTFRAGMENT
 * Evita múltiples reflows usando batch rendering
 */
window.actualizarTablaTelas = function() {
    const tbody = document.getElementById('tbody-telas');
    
    if (!tbody) {
        console.warn('[actualizarTablasTelas]  tbody-telas no encontrado');
        return;
    }
    
    const telas = window.telasCreacion;
    
    // Identificar la fila de INPUTS usando el botón "Agregar" (selector robusto)
    const todasLasFilas = Array.from(tbody.querySelectorAll('tr'));
    const filaInputs = todasLasFilas.find(tr => 
        tr.querySelector('button[onclick="agregarTelaNueva()"]') !== null
    );
    
    // Eliminar SOLO las filas de telas (las que tienen onclick="eliminarTela()")
    // NO eliminar la fila de inputs
    todasLasFilas.forEach(fila => {
        if (fila !== filaInputs) {
            fila.remove();
        }
    });
    
    // Usar DocumentFragment para mejor rendimiento
    const fragment = document.createDocumentFragment();
    
    if (telas.length === 0) {
        // Mensaje cuando no hay telas
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td colspan="5" style="padding: 2rem; text-align: center; color: #9ca3af;">
                <div style="font-size: 1.1rem; margin-bottom: 0.5rem;"></div>
                <div style="font-size: 0.9rem;">No hay telas agregadas</div>
            </td>
        `;
        fragment.appendChild(tr);
    } else {
        // Renderizar cada tela
        telas.forEach((tela, index) => {
            const tr = crearFilaTela(tela, index);
            fragment.appendChild(tr);
        });
    }
    
    // Insertar las telas ANTES de la fila de inputs (si existe)
    if (filaInputs) {
        filaInputs.parentNode.insertBefore(fragment, filaInputs);
    } else {
        // Fallback: insertar al final
        tbody.appendChild(fragment);
    }
};

/**
 * Crear fila de tabla para una tela
 * @param {Object} tela - Datos de la tela
 * @param {number} index - Índice de la tela
 * @returns {HTMLTableRowElement} Fila creada
 */
function crearFilaTela(tela, index) {
    const tr = document.createElement('tr');
    tr.style.cssText = 'border-bottom: 1px solid #e5e7eb;';
    
    // Procesar imágenes para mostrar en la tabla
    let imagenHTML = '';
    if (tela.imagenes && tela.imagenes.length > 0) {
        console.log(`[actualizarTablaTelas] 📸 [Tela ${index}] Procesando ${tela.imagenes.length} imágenes`);
        
        // Usar directamente las URLs de preview que ya existen
        const imagenConBlobUrl = tela.imagenes.map(img => {
            // CASO 0: Si img es un File directamente, crear blob
            if (img instanceof File) {
                const blobUrl = URL.createObjectURL(img);
                return { file: img, previewUrl: blobUrl, nombre: img.name };
            }
            
            // Para edición, las imágenes guardadas tienen diferentes estructura
            // Si tiene ruta (URL del servidor), usarla
            if (img.ruta) {
                return { ...img, previewUrl: img.ruta };
            }
            
            // Si tiene url, usarla
            if (img.url) {
                return { ...img, previewUrl: img.url };
            }
            
            // Si tiene previewUrl y no es un blob temporal, usarla directamente
            if (img.previewUrl && !img.previewUrl.startsWith('blob:')) {
                return { ...img, previewUrl: img.previewUrl };
            }
            
            // Si tiene file (solo para creación), crear blob
            if (img.file) {
                const blobUrl = URL.createObjectURL(img.file);
                return { ...img, previewUrl: blobUrl };
            }
            
            // Si tiene previewUrl pero es blob temporal, mantenerlo
            if (img.previewUrl) {
                return { ...img, previewUrl: img.previewUrl };
            }
            
            // Si no tiene ninguna URL, marcar como inválida
            console.warn('[actualizarTablaTelas]  Imagen sin URL válida:', img);
            return { ...img, previewUrl: null, invalida: true };
        }).filter(img => !img.invalida); // Filtrar imágenes inválidas
        
        console.log(`[actualizarTablaTelas] 📸 [Tela ${index}] Imágenes procesadas: ${imagenConBlobUrl.length}`);
        
        if (imagenConBlobUrl.length === 0) {
            console.warn(`[actualizarTablaTelas]  No hay imágenes válidas para la tela ${index}`);
            imagenHTML = `
                <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                    <span style="color: #999; font-size: 0.875rem;">Sin imágenes</span>
                </div>
            `;
        } else {
            // Crear imagen con onclick seguro usando data attributes
            const imgElement = document.createElement('img');
            imgElement.src = imagenConBlobUrl[0].previewUrl;
            imgElement.style.cssText = 'width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;';
            imgElement.title = imagenConBlobUrl[0].name || 'Imagen de tela';
        
        // Agregar evento onclick de forma segura
        imgElement.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[actualizarTablaTelas] 🖼️ Click en imagen de tela', { 
                index: index, 
                imagen: imagenConBlobUrl[0].name,
                totalImagenes: imagenConBlobUrl.length
            });
            
            // Verificar que la función exista
            if (typeof window.mostrarGaleriaImagenesTela === 'function') {
                window.mostrarGaleriaImagenesTela(imagenConBlobUrl, index, 0);
            } else {
                console.error('[actualizarTablaTelas]  La función mostrarGaleriaImagenesTela no está disponible');
                alert('La función de galería no está disponible. Por favor recarga la página.');
            }
        };
        
        // Agregar evento de error por si acaso
        imgElement.onerror = function() {
            console.error('[actualizarTablaTelas]  Error cargando imagen:', imagenConBlobUrl[0].name);
        };
        
        // Agregar evento de carga exitosa
        imgElement.onload = function() {
            console.log('[actualizarTablaTelas]  Imagen cargada exitosamente:', imagenConBlobUrl[0].name);
        };
        
        // En lugar de usar outerHTML, vamos a crear el contenedor y agregar el elemento img directamente
        const contenedor = document.createElement('div');
        contenedor.style.cssText = 'display: flex; gap: 0.5rem; align-items: center; justify-content: center;';
        
        // Agregar la imagen directamente al contenedor
        contenedor.appendChild(imgElement);
        
        // Agregar badge si hay más imágenes
        if (imagenConBlobUrl.length > 1) {
            const badge = document.createElement('span');
            badge.textContent = `+${imagenConBlobUrl.length - 1}`;
            badge.style.cssText = 'background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;';
            contenedor.appendChild(badge);
        }
        
        imagenHTML = contenedor.outerHTML;
        }
    } else {
        imagenHTML = `
            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                <span style="color: #999; font-size: 0.875rem;">Sin foto</span>
            </div>
        `;
    }
    
    tr.innerHTML = `
        <td style="padding: 0.75rem; vertical-align: middle;">${tela.tela}</td>
        <td style="padding: 0.75rem; vertical-align: middle;">${tela.color || '(sin color)'}</td>
        <td style="padding: 0.75rem; vertical-align: middle;">${tela.referencia || '-'}</td>
        <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
            ${imagenHTML}
        </td>
        <td style="padding: 0.75rem; vertical-align: middle; text-align: center;">
            <button type="button" onclick="eliminarTela(${index}, event)" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                <span class="material-symbols-rounded" style="font-size: 1rem;">delete</span>
            </button>
        </td>
    `;
    
    return tr;
}

/**
 * Actualizar contador de telas
 */
window.actualizarContadorTelas = function() {
    const contador = document.getElementById('contador-telas');
    if (contador) {
        const total = window.telasCreacion ? window.telasCreacion.length : 0;
        contador.textContent = `Telas: ${total}`;
    }
};

/**
 * Mostrar mensaje de estado de telas
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo de mensaje (success, error, warning)
 */
window.mostrarMensajeTela = function(mensaje, tipo = 'info') {
    const mensajeDiv = document.getElementById('mensaje-tela');
    if (!mensajeDiv) return;
    
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = `mensaje-tela mensaje-${tipo}`;
    mensajeDiv.style.display = 'block';
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);
};

/**
 * Ocultar mensaje de estado
 */
window.ocultarMensajeTela = function() {
    const mensajeDiv = document.getElementById('mensaje-tela');
    if (mensajeDiv) {
        mensajeDiv.style.display = 'none';
    }
};

/**
 * Animar adición de fila
 * @param {HTMLElement} fila - Fila a animar
 */
window.animarAdicionFila = function(fila) {
    fila.style.opacity = '0';
    fila.style.transform = 'translateY(-20px)';
    
    setTimeout(() => {
        fila.style.transition = 'all 0.3s ease';
        fila.style.opacity = '1';
        fila.style.transform = 'translateY(0)';
    }, 100);
};

/**
 * Animar eliminación de fila
 * @param {HTMLElement} fila - Fila a animar
 */
window.animarEliminacionFila = function(fila) {
    fila.style.transition = 'all 0.3s ease';
    fila.style.opacity = '0';
    fila.style.transform = 'translateX(-20px)';
    
    setTimeout(() => {
        fila.remove();
    }, 300);
};

/**
 * Actualizar vista de telas (función principal)
 */
window.actualizarVistaTelas = function() {
    console.log('[actualizarVistaTelas]  Actualizando vista completa de telas');
    
    // Actualizar tabla
    window.actualizarTablaTelas();
    
    // Actualizar contador
    window.actualizarContadorTelas();
    
    // Actualizar botones
    window.actualizarBotonesTelas();
    
    console.log('[actualizarVistaTelas]  Vista de telas actualizada');
};

/**
 * Actualizar estado de botones de telas
 */
window.actualizarBotonesTelas = function() {
    // Aquí se pueden agregar actualizaciones para botones específicos de telas
    console.log('[actualizarBotonesTelas]  Botones actualizados');
};

/**
 * Crear contenedor para imágenes de tela
 * @returns {HTMLElement} Contenedor creado
 */
window.crearContenedorImagenesTela = function() {
    const container = document.createElement('div');
    container.className = 'contenedor-imagenes-tela';
    container.style.cssText = `
        display: flex; gap: 10px; flex-wrap: wrap;
        padding: 10px;
        background: #f9fafb;
        border: 1px dashed #d1d5db;
        border-radius: 4px;
        min-height: 80px;
        align-items: center;
        justify-content: center;
    `;
    return container;
};
