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
    console.log('[actualizarTablaTelas] 🔄 Actualizando tabla de telas');
    
    const tbody = document.getElementById('tbody-telas');
    
    if (!tbody) {
        console.warn('[actualizarTablasTelas] ⚠️ tbody-telas no encontrado');
        return;
    }
    
    const telas = window.telasCreacion;
    console.log('[actualizarTablasTelas] 📊 Telas a renderizar:', telas.length);
    
    // Guardar la fila de entrada (primera fila con los inputs)
    const filaEntrada = tbody.querySelector('tr');
    
    // Limpiar tabla (excepto la fila de entrada)
    tbody.innerHTML = '';
    
    // Re-insertar la fila de entrada
    if (filaEntrada) {
        tbody.appendChild(filaEntrada);
    }
    
    // Usar DocumentFragment para mejor rendimiento
    const fragment = document.createDocumentFragment();
    
    if (telas.length === 0) {
        // Mensaje cuando no hay telas
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td colspan="5" style="padding: 2rem; text-align: center; color: #9ca3af;">
                <div style="font-size: 1.1rem; margin-bottom: 0.5rem;">📋</div>
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
    
    // Aplicar cambios en una sola operación
    tbody.appendChild(fragment);
    
    console.log('[actualizarTablasTelas] ✅ Tabla actualizada con ' + telas.length + ' filas');
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
    
    // Generar HTML de imágenes
    let imagenHTML = '';
    let imagenConBlobUrl = [];
    
    if (tela.imagenes && tela.imagenes.length > 0) {
        // Crear blob URLs para las imágenes
        imagenConBlobUrl = tela.imagenes.map((img, idx) => {
            let blobUrl;
            if (img.file instanceof File || img.file instanceof Blob) {
                blobUrl = URL.createObjectURL(img.file);
            } else if (img.blobUrl) {
                blobUrl = img.blobUrl;
            } else if (img.previewUrl) {
                blobUrl = img.previewUrl;
            } else {
                // Crear blob URL desde base64 si está disponible
                if (img.base64) {
                    const byteCharacters = atob(img.base64);
                    const byteNumbers = new Array(byteNumbers.length);
                    for (let i = 0; i < byteNumbers.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    blobUrl = URL.createObjectURL(new Blob([byteArray], {type: 'image/jpeg'}));
                }
            }
            
            return { ...img, previewUrl: blobUrl };
        });
        
        console.log(`[actualizarTablaTelas] 📸 [Tela ${index}] Imágenes procesadas: ${imagenConBlobUrl.length}`);
        
        imagenHTML = `
            <div style="display: flex; gap: 0.5rem; align-items: center; justify-content: center;">
                ${imagenConBlobUrl[0].previewUrl ? `
                    <img src="${imagenConBlobUrl[0].previewUrl}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover; cursor: pointer;" onclick="mostrarGaleriaImagenesTela(${JSON.stringify(imagenConBlobUrl)}, ${index}, 0)">
                    ${imagenConBlobUrl.length > 1 ? `<span style="background: #0066cc; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">+${imagenConBlobUrl.length - 1}</span>` : ''}
                    ` : `
                        <span style="color: #999; font-size: 0.875rem;">Sin foto</span>
                    `}
                </div>
            `;
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
    console.log('[actualizarVistaTelas] 🔄 Actualizando vista completa de telas');
    
    // Actualizar tabla
    window.actualizarTablaTelas();
    
    // Actualizar contador
    window.actualizarContadorTelas();
    
    // Actualizar botones
    window.actualizarBotonesTelas();
    
    console.log('[actualizarVistaTelas] ✅ Vista de telas actualizada');
};

/**
 * Actualizar estado de botones de telas
 */
window.actualizarBotonesTelas = function() {
    // Aquí se pueden agregar actualizaciones para botones específicos de telas
    console.log('[actualizarBotonesTelas] 🔄 Botones actualizados');
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
