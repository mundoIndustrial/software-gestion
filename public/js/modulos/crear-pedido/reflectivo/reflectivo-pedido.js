/**
 * MÓDULO: Gestión de Cotizaciones Tipo Reflectivo
 * 
 * Este módulo contiene toda la lógica para el renderizado y manejo
 * de cotizaciones tipo Reflectivo (RF).
 * 
 * Funciones principales:
 * - renderizarReflectivo() - Renderiza la vista completa del reflectivo
 * - eliminarFotoReflectivoPedido() - Elimina una foto del reflectivo
 * - eliminarTallaReflectivo() - Elimina una talla del reflectivo
 * - abrirModalAgregarFotosReflectivo() - Abre el modal para agregar fotos
 * - manejarArchivosReflectivo() - Procesa los archivos de fotos
 */

/**
 * FUNCIÓN PRINCIPAL: Renderizar cotización tipo Reflectivo
 * 
 * @param {Array} prendas - Array de prendas de la cotización
 * @param {Object} datosReflectivo - Datos específicos del reflectivo
 * @returns {string} HTML generado para la vista reflectivo
 */
window.renderizarReflectivo = function(prendas, datosReflectivo = null) {
    console.log(' RENDERIZANDO COTIZACIÓN TIPO REFLECTIVO');
    console.log(' Datos reflectivo:', datosReflectivo);
    
    // Parsear ubicaciones del reflectivo
    let ubicacionesReflectivo = [];
    if (datosReflectivo && datosReflectivo.ubicacion) {
        try {
            ubicacionesReflectivo = typeof datosReflectivo.ubicacion === 'string' 
                ? JSON.parse(datosReflectivo.ubicacion) 
                : datosReflectivo.ubicacion;
            console.log('📍 Ubicaciones parseadas:', ubicacionesReflectivo);
        } catch (e) {
            console.error('Error parseando ubicaciones:', e);
            ubicacionesReflectivo = [];
        }
    }
    
    let html = '';
    
    // Renderizar cada prenda con su información de reflectivo
    prendas.forEach((prenda, index) => {
        // Inicializar contenedor de galería de telas por prenda
        if (!window.telasGaleria) window.telasGaleria = [];
        window.telasGaleria[index] = [];
        
        console.log(` Prenda ${index + 1}:`, prenda);
        console.log(`   - Tallas:`, prenda.tallas);
        console.log(`   - Tipo de tallas:`, typeof prenda.tallas);
        
        html += generarCardPrendaReflectivo(prenda, index, ubicacionesReflectivo);
    });
    
    // Agregar sección de fotos del reflectivo
    html += generarSeccionFotosReflectivo(datosReflectivo);
    
    return html;
};

/**
 * FUNCIÓN: Generar card HTML de una prenda en modo reflectivo
 * @param {Object} prenda - Datos de la prenda
 * @param {number} index - Índice de la prenda
 * @param {Array} ubicacionesReflectivo - Ubicaciones disponibles
 * @returns {string} HTML de la card
 */
function generarCardPrendaReflectivo(prenda, index, ubicacionesReflectivo) {
    return `
    <div class="prenda-card-editable reflectivo-card" data-prenda-index="${index}" style="margin-bottom: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); padding: 1.25rem; color: white;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">
                <i class="fas fa-tshirt" style="margin-right: 0.5rem;"></i>Prenda ${index + 1}
            </h3>
        </div>
        
        <div style="padding: 1.5rem;">
            <!-- Tipo de Prenda (Editable) -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af;">Tipo de Prenda:</label>
                <input type="text" 
                       name="reflectivo_tipo_prenda[${index}]" 
                       value="${prenda.nombre_producto || ''}"
                       placeholder="Ej: Camiseta, Pantalón, Chaqueta..."
                       style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
            </div>
            
            <!-- Descripción del Reflectivo para esta prenda (Editable) -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af;">Descripción del Reflectivo:</label>
                <textarea name="reflectivo_descripcion[${index}]" 
                          placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..."
                          style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; min-height: 100px; font-family: inherit;">${prenda.descripcion || ''}</textarea>
            </div>
            
            <!-- Ubicaciones del Reflectivo (Mostrar las que vienen de la cotización) -->
            ${ubicacionesReflectivo && ubicacionesReflectivo.length > 0 ? `
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af;">
                    <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>Ubicaciones del Reflectivo:
                </label>
                <div style="display: grid; gap: 0.75rem;">
                    ${ubicacionesReflectivo.map((ubicacion, ubIdx) => {
                        const ubicacionNombre = ubicacion.ubicacion || ubicacion;
                        const ubicacionDesc = ubicacion.descripcion || '';
                        return `
                        <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1rem; background: #f9fafb;">
                            <div style="margin-bottom: ${ubicacionDesc ? '0.5rem' : '0'};">
                                <input type="text" 
                                       name="reflectivo_ubicaciones[${index}][${ubIdx}][ubicacion]" 
                                       value="${ubicacionNombre}"
                                       placeholder="Ubicación (ej: Pecho, Espalda, Mangas...)"
                                       style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; font-weight: 600; color: #1e40af;">
                            </div>
                            ${ubicacionDesc ? `
                            <div>
                                <textarea name="reflectivo_ubicaciones[${index}][${ubIdx}][descripcion]" 
                                          placeholder="Descripción adicional..."
                                          style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; min-height: 60px; font-family: inherit; color: #64748b;">${ubicacionDesc}</textarea>
                            </div>
                            ` : ''}
                        </div>
                        `;
                    }).join('')}
                </div>
            </div>
            ` : ''}
            
            <!-- Tallas (Editable con cantidades y botón eliminar) -->
            ${prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0 ? `
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af;">
                    <i class="fas fa-ruler" style="margin-right: 0.5rem;"></i>Tallas y Cantidades:
                </label>
                <div id="tallas-container-${index}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">
                    ${prenda.tallas.map((talla, tallaIdx) => {
                        console.log(`     Talla ${tallaIdx}:`, talla);
                        return `
                        <div class="talla-item-reflectivo" data-talla="${talla}" data-prenda="${index}" style="background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem; position: relative;">
                            <button type="button" 
                                    onclick="eliminarTallaReflectivo(${index}, '${talla}')"
                                    style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"
                                    title="Eliminar talla">
                                ×
                            </button>
                            <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.4rem; font-size: 0.85rem;">${talla}</label>
                            <input type="number" 
                                   class="talla-cantidad"
                                   data-talla="${talla}"
                                   name="reflectivo_cantidades[${index}][${talla}]" 
                                   min="0" 
                                   value="0"
                                   placeholder="0"
                                   style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem;">
                        </div>
                        `;
                    }).join('')}
                </div>
            </div>
            ` : `<p style="color: #94a3b8; font-style: italic; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>Sin tallas definidas
                ${prenda.tallas ? ' (tallas: ' + JSON.stringify(prenda.tallas) + ')' : ''}
            </p>`}
        </div>
    </div>
    `;
}

/**
 * FUNCIÓN: Generar sección de fotos del reflectivo
 * @param {Object} datosReflectivo - Datos del reflectivo
 * @returns {string} HTML de la sección de fotos
 */
function generarSeccionFotosReflectivo(datosReflectivo) {
    // Combinar fotos originales + fotos nuevas
    let fotosReflectivoCompletas = (datosReflectivo && datosReflectivo.fotos) ? [...datosReflectivo.fotos] : [];
    if (window.reflectiveFotosNuevas && window.reflectiveFotosNuevas.length > 0) {
        fotosReflectivoCompletas = [...fotosReflectivoCompletas, ...window.reflectiveFotosNuevas];
    }
    
    // Filtrar fotos eliminadas
    fotosReflectivoCompletas = fotosReflectivoCompletas.filter(foto => {
        const fotoUrl = foto.url || foto.ruta_webp || '/storage/' + foto.ruta_webp;
        return !window.fotosEliminadas.has(fotoUrl);
    });
    
    if (fotosReflectivoCompletas.length > 0) {
        console.log('📸 Fotos del reflectivo encontradas:', fotosReflectivoCompletas);
        return `
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h4 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem; flex: 1;">
                    <i class="fas fa-images" style="margin-right: 0.5rem;"></i>Imágenes del Reflectivo (${fotosReflectivoCompletas.length})
                </h4>
                <button type="button"
                        onclick="abrirModalAgregarFotosReflectivo()"
                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25); margin-left: 1rem;"
                        title="Agregar foto">
                    ＋
                </button>
            </div>
            <div id="reflectivo-fotos-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                ${fotosReflectivoCompletas.map((foto, fotoIdx) => {
                    const fotoUrl = foto.url || foto.ruta_webp || '/storage/' + foto.ruta_webp;
                    const fotoId = foto.id || fotoIdx;
                    return `
                    <div class="reflectivo-foto-item" data-foto-id="${fotoId}" style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" 
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" 
                         onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                        <button type="button" 
                                onclick="eliminarFotoReflectivoPedido(${fotoId})"
                                style="position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            ×
                        </button>
                        <img src="${fotoUrl}" 
                             alt="Reflectivo ${fotoIdx + 1}" 
                             style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform=''"
                             onclick="abrirModalImagen('${fotoUrl}', 'Reflectivo - Imagen ${fotoIdx + 1}')">
                        <input type="hidden" name="reflectivo_fotos_incluir[]" value="${fotoId}">
                    </div>
                    `;
                }).join('')}
            </div>
        </div>
        `;
    } else {
        console.log('📸 Sin fotos del reflectivo - mostrar botón para agregar');
        return `
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h4 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-images" style="margin-right: 0.5rem;"></i>Imágenes del Reflectivo (0)
                </h4>
                <button type="button"
                        onclick="abrirModalAgregarFotosReflectivo()"
                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);"
                        title="Agregar foto">
                    ＋
                </button>
            </div>
            <div id="reflectivo-fotos-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem; min-height: 100px; align-content: center;">
                <div style="grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 2rem;">
                    📁 Sin fotos - Agrega fotos para mostrar aquí
                </div>
            </div>
        </div>
        `;
    }
}

/**
 * FUNCIÓN: Eliminar foto del reflectivo
 * @param {number|string} fotoId - ID de la foto a eliminar
 */
window.eliminarFotoReflectivoPedido = window.eliminarFotoReflectivoPedido || function(fotoId) {
    if (typeof modalConfirmarEliminarImagen === 'function') {
        modalConfirmarEliminarImagen('imagen del reflectivo').then((result) => {
            if (result.isConfirmed) {
                const contenedor = document.querySelector(`.reflectivo-foto-item[data-foto-id="${fotoId}"]`);
                if (contenedor) {
                    const img = contenedor.querySelector('img');
                    const fotoUrl = img?.getAttribute('src');
                    
                    console.log(` Eliminando foto del reflectivo ID ${fotoId}, URL:`, fotoUrl);
                    
                    // Marcar esta foto como eliminada
                    if (fotoUrl) {
                        window.fotosEliminadas.add(fotoUrl);
                    }
                    
                    contenedor.remove();
                    procesarImagenesRestantes(null, 'reflectivo');
                    
                    if (window.eliminarImagenTimeout) clearTimeout(window.eliminarImagenTimeout);
                    window.eliminarImagenTimeout = setTimeout(() => {
                        if (typeof renderizarPrendas === 'function') {
                            renderizarPrendas();
                        }
                        window.eliminarImagenTimeout = null;
                    }, 200);
                }
            }
        });
    }
};

/**
 * FUNCIÓN: Eliminar talla del reflectivo
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a eliminar
 */
window.eliminarTallaReflectivo = window.eliminarTallaReflectivo || function(prendaIndex, talla) {
    if (typeof modalConfirmarEliminarTallaReflectivo === 'function') {
        modalConfirmarEliminarTallaReflectivo(talla).then((result) => {
            if (result.isConfirmed) {
                const tallaElement = document.querySelector(`.talla-item-reflectivo[data-talla="${talla}"][data-prenda="${prendaIndex}"]`);
                if (tallaElement) {
                    console.log(` Eliminando talla ${talla} de la prenda ${prendaIndex + 1}`);
                    
                    // GUARDAR CANTIDADES ANTES DE RE-RENDERIZAR
                    if (typeof guardarCantidadesActuales === 'function') {
                        guardarCantidadesActuales(prendaIndex);
                    }
                    
                    // Eliminar del array de tallas
                    if (window.prendasCargadas && window.prendasCargadas[prendaIndex]) {
                        const tallaIdx = window.prendasCargadas[prendaIndex].tallas?.indexOf(talla);
                        if (tallaIdx >= 0) {
                            window.prendasCargadas[prendaIndex].tallas.splice(tallaIdx, 1);
                        }
                    }
                    
                    tallaElement.remove();
                    if (typeof modalExito === 'function') {
                        modalExito('Talla eliminada', `La talla ${talla} no se incluirá en el pedido`);
                    }
                    
                    if (window.eliminarImagenTimeout) clearTimeout(window.eliminarImagenTimeout);
                    window.eliminarImagenTimeout = setTimeout(() => {
                        if (typeof renderizarPrendas === 'function') {
                            console.log(`🔄 Renderizando prendas después de eliminar talla...`);
                            renderizarPrendas();
                            // Restaurar cantidades guardadas después del render
                            setTimeout(() => {
                                if (typeof restaurarCantidadesGuardadas === 'function') {
                                    restaurarCantidadesGuardadas(prendaIndex);
                                }
                            }, 100);
                        }
                        window.eliminarImagenTimeout = null;
                    }, 200);
                }
            }
        });
    }
};

/**
 * FUNCIÓN: Abrir modal para agregar fotos del reflectivo
 */
window.abrirModalAgregarFotosReflectivo = function() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = true;
    
    input.addEventListener('change', (e) => {
        manejarArchivosReflectivo(e.target.files);
    });
    
    input.click();
};

/**
 * FUNCIÓN: Procesar archivos de fotos del reflectivo
 * @param {FileList} files - Archivos seleccionados
 */
window.manejarArchivosReflectivo = function(files) {
    // Inicializar almacenamiento si no existe
    if (!window.reflectiveFotosNuevas) window.reflectiveFotosNuevas = [];

    let fotosAgregadas = 0;
    let fotosADeProcesar = Array.from(files).filter(f => f.type.startsWith('image/')).length;
    let renderTimeout = null;
    
    if (fotosADeProcesar === 0) {
        console.log(' No hay archivos de imagen para procesar');
        return;
    }
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                // Crear objeto de foto con ID único para las nuevas
                const fotoId = 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                const fotoObj = {
                    id: fotoId,
                    url: e.target.result,
                    preview: e.target.result,
                    file: file,
                    isNew: true,
                    fileName: file.name
                };
                
                // Verificar si esta foto ya existe (por nombre de archivo)
                const yaExiste = window.reflectiveFotosNuevas.some(f => f.fileName === file.name && f.url === e.target.result);
                
                if (!yaExiste) {
                    window.reflectiveFotosNuevas.push(fotoObj);
                    fotosAgregadas++;
                    console.log(`📸 Foto agregada a reflectivo: ${file.name} (${fotosAgregadas}/${fotosADeProcesar})`);
                    console.log(`   ID: ${fotoId}`);
                } else {
                    console.log(` Foto duplicada ignorada: ${file.name}`);
                }
                
                // Cuando se terminen de procesar todas las fotos, renderizar una sola vez con debounce
                if (renderTimeout) clearTimeout(renderTimeout);
                
                if (fotosAgregadas === fotosADeProcesar) {
                    console.log(` Todas las fotos han sido procesadas. Renderizando...`);
                    renderTimeout = setTimeout(() => {
                        if (typeof renderizarPrendas === 'function') {
                            renderizarPrendas();
                        }
                        renderTimeout = null;
                    }, 200);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    console.log(`📤 Procesando ${fotosADeProcesar} archivo(s) de imagen...`);
};
