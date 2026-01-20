/**
 * RENDERIZADOR DE REFLECTIVO SIN COTIZACIÓN
 * 
 * Este módulo contiene funciones para renderizar la interfaz de prendas
 * tipo REFLECTIVO sin cotización previa.
 * 
 * Funciones principales:
 * - renderizarPrendaReflectivoSinCotizacion() - Renderiza una tarjeta de prenda completa
 * - renderizarSeccionTallasReflectivo() - Renderiza la sección de tallas
 * - renderizarSeccionImagenesReflectivo() - Renderiza la sección de imágenes
 */

/**
 * Renderizar una prenda tipo REFLECTIVO sin cotización
 * @param {Object} prenda - Datos de la prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de la prenda
 */
function renderizarPrendaReflectivoSinCotizacion(prenda, index) {
    const genero = prenda.genero || '';
    
    return `
    <div class="prenda-card-reflectivo" data-prenda-index="${index}" style="margin-bottom: 2rem;">
        <div class="prenda-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
            <div class="prenda-title" style="font-weight: 700; font-size: 1.125rem; color: #333;">
                <i class="fas fa-fire-alt" style="margin-right: 0.5rem; color: #1e40af;"></i>Prenda Reflectivo
            </div>
            <!--  Botón eliminar OCULTO en tipo Reflectivo (máximo 1 prenda) -->
        </div>

        <!-- CONTENIDO -->
        <div style="padding: 0;">
            <!-- TIPO DE PRENDA -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-tshirt"></i> Tipo de Prenda
                </label>
                <input type="text" 
                       name="reflectivo_tipo_prenda[${index}]" 
                       class="tipo-prenda-input-reflectivo"
                       placeholder="Ej: Camiseta, Pantalón, Chaqueta..."
                       value="${prenda.nombre_producto || ''}"
                       style="
                           width: 100%;
                           padding: 0.75rem;
                           border: 2px solid #cbd5e1;
                           border-radius: 6px;
                           font-size: 0.95rem;
                           background: white;
                           font-family: inherit;
                           transition: all 0.2s ease;
                       "
                       onchange="actualizarNombrePrendaReflectivo(${index}, this.value)"
                       onfocus="this.style.borderColor = '#1e40af'; this.style.boxShadow = '0 0 0 3px rgba(30, 64, 175, 0.1)';"
                       onblur="this.style.borderColor = '#cbd5e1'; this.style.boxShadow = 'none';">
            </div>

            <!-- DESCRIPCIÓN -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-sticky-note"></i> Descripción del Reflectivo
                </label>
                <textarea name="reflectivo_descripcion[${index}]" 
                          class="descripcion-input-reflectivo"
                          placeholder="Describe el reflectivo (tipo, tamaño, color, ubicación, etc.)..."
                          value="${prenda.descripcion || ''}"
                          style="
                              width: 100%;
                              padding: 0.75rem;
                              border: 2px solid #cbd5e1;
                              border-radius: 6px;
                              font-size: 0.95rem;
                              background: white;
                              font-family: inherit;
                              min-height: 80px;
                              transition: all 0.2s ease;
                              resize: vertical;
                          "
                          onchange="actualizarDescripcionPrendaReflectivo(${index}, this.value)"
                          onfocus="this.style.borderColor = '#1e40af'; this.style.boxShadow = '0 0 0 3px rgba(30, 64, 175, 0.1)';"
                          onblur="this.style.borderColor = '#cbd5e1'; this.style.boxShadow = 'none';">${prenda.descripcion || ''}</textarea>
            </div>

            <!-- IMÁGENES -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-images"></i> Imágenes (máximo 3)
                </label>
                <div class="galeria-imagenes-reflectivo" data-prenda-index="${index}" style="
                    background: #f9fafb;
                    border: 2px dashed #cbd5e1;
                    border-radius: 8px;
                    padding: 1rem;
                    margin-bottom: 0.75rem;
                ">
                    <div style="
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                        gap: 0.75rem;
                        margin-bottom: 0.75rem;
                        min-height: 90px;
                        align-items: start;
                    " id="imagenes-container-${index}">
                        <!-- Las imágenes se mostrarán aquí -->
                    </div>
                </div>
                <button type="button" 
                        onclick="abrirGaleriaImagenesReflectivo(${index})"
                        class="btn-agregar-imagenes-reflectivo"
                        style="
                            width: 100%;
                            padding: 0.75rem;
                            background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
                            color: white;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        "
                        onmouseover="this.style.transform = 'translateY(-2px)'; this.style.boxShadow = '0 4px 12px rgba(30, 64, 175, 0.3)';"
                        onmouseout="this.style.transform = 'none'; this.style.boxShadow = 'none';">
                    <i class="fas fa-cloud-upload-alt"></i> Agregar Imágenes
                </button>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                    Máximo 3 imágenes • Formatos: JPG, PNG, WebP
                </div>
                <input type="file" 
                       name="imagenes_reflectivo[${index}][]" 
                       class="input-file-imagenes-reflectivo"
                       accept="image/*"
                       multiple
                       style="display: none;"
                       onchange="procesarImagenesReflectivo(${index}, this.files)">
            </div>

            <!-- GÉNERO -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-user"></i> Género
                </label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" 
                               name="genero_reflectivo_dama[${index}]" 
                               value="dama" 
                               class="genero-checkbox-reflectivo"
                               ${genero === 'Dama' || genero === 'dama' ? 'checked' : ''}
                               onchange="actualizarGeneroReflectivo(${index}, 'dama')"
                               style="width: 18px; height: 18px; cursor: pointer; accent-color: #1e40af;">
                        <span style="font-size: 0.95rem;">Dama</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" 
                               name="genero_reflectivo_caballero[${index}]" 
                               value="caballero" 
                               class="genero-checkbox-reflectivo"
                               ${genero === 'Caballero' || genero === 'caballero' ? 'checked' : ''}
                               onchange="actualizarGeneroReflectivo(${index}, 'caballero')"
                               style="width: 18px; height: 18px; cursor: pointer; accent-color: #1e40af;">
                        <span style="font-size: 0.95rem;">Caballero</span>
                    </label>
                </div>
            </div>

            <!-- TALLAS Y CANTIDADES - SISTEMA DE GÉNEROS COMO EN PRENDA -->
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-ruler"></i> Tallas y Cantidades
                </label>
                
                <!-- SECCIÓN DAMA -->
                <div class="genero-section-reflectivo genero-dama-section" data-genero="dama" style="margin-bottom: 2rem; display: none;">
                    <div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600; color: #1e40af; font-size: 0.95rem;"><i class="fas fa-woman" style="margin-right: 0.5rem;"></i>DAMA</span>
                        <button type="button" class="btn-agregar-tallas-genero-reflectivo" onclick="agregarTallasAlGeneroReflectivo(${index}, 'dama')" style="padding: 0.4rem 0.8rem; background: #1e40af; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-plus"></i> Agregar Tallas
                        </button>
                    </div>
                    <div class="tallas-genero-container-reflectivo" data-prenda="${index}" data-genero="dama" style="border: 1px solid #1e40af; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                        <p style="padding: 0.75rem 1rem; background: white; color: #9ca3af; font-size: 0.85rem; margin: 0; border-radius: 0 0 6px 6px;">Sin tallas agregadas</p>
                    </div>
                </div>
                
                <!-- SECCIÓN CABALLERO -->
                <div class="genero-section-reflectivo genero-caballero-section" data-genero="caballero" style="margin-bottom: 1rem; display: none;">
                    <div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 2px solid #1e40af; border-radius: 6px 6px 0 0; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600; color: #1e40af; font-size: 0.95rem;"><i class="fas fa-man" style="margin-right: 0.5rem;"></i>CABALLERO</span>
                        <button type="button" class="btn-agregar-tallas-genero-reflectivo" onclick="agregarTallasAlGeneroReflectivo(${index}, 'caballero')" style="padding: 0.4rem 0.8rem; background: #1e40af; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-plus"></i> Agregar Tallas
                        </button>
                    </div>
                    <div class="tallas-genero-container-reflectivo" data-prenda="${index}" data-genero="caballero" style="border: 1px solid #1e40af; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">
                        <p style="padding: 0.75rem 1rem; background: white; color: #9ca3af; font-size: 0.85rem; margin: 0; border-radius: 0 0 6px 6px;">Sin tallas agregadas</p>
                    </div>
                </div>
            </div>

            <!-- UBICACIONES DEL REFLECTIVO -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                    <i class="fas fa-map-pin"></i> Ubicación
                </label>
                <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <input type="text" 
                           class="ubicacion-input-reflectivo"
                           placeholder="Ej: PECHO, ESPALDA, MANGA, etc."
                           style="
                               flex: 1;
                               padding: 0.75rem;
                               border: 2px solid #cbd5e1;
                               border-radius: 6px;
                               font-size: 0.95rem;
                               background: white;
                               font-family: inherit;
                               transition: all 0.2s ease;
                           "
                           onfocus="this.style.borderColor = '#1e40af'; this.style.boxShadow = '0 0 0 3px rgba(30, 64, 175, 0.1)';"
                           onblur="this.style.borderColor = '#cbd5e1'; this.style.boxShadow = 'none';">
                    <button type="button" 
                            class="btn-agregar-ubicacion-reflectivo"
                            onclick="abrirModalAgregarUbicacionReflectivo(${index})"
                            style="
                                background: #0ea5e9;
                                color: white;
                                border: none;
                                border-radius: 6px;
                                width: 45px;
                                height: 45px;
                                cursor: pointer;
                                font-size: 1.2rem;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: 700;
                                box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
                                transition: all 0.2s ease;
                            "
                            onmouseover="this.style.background = '#0284c7'; this.style.boxShadow = '0 4px 12px rgba(14, 165, 233, 0.4)';"
                            onmouseout="this.style.background = '#0ea5e9'; this.style.boxShadow = '0 2px 8px rgba(14, 165, 233, 0.3)';">
                        +
                    </button>
                </div>
                
                <!-- Contenedor de Ubicaciones Agregadas -->
                <div class="ubicaciones-agregadas-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                </div>
            </div>
        </div>
    </div>
    `;
}

/**
 * Renderizar las imágenes de una prenda reflectivo
 * @param {number} prendaIndex - Índice de la prenda
 * @param {Array} imagenes - Array de imágenes
 */
function renderizarImagenesReflectivo(prendaIndex, imagenes = []) {
    const container = document.getElementById(`imagenes-container-${prendaIndex}`);
    if (!container) return;

    if (imagenes.length === 0) {
        container.innerHTML = `
            <div style="
                grid-column: 1 / -1;
                text-align: center;
                color: #94a3b8;
                padding: 1rem;
                font-size: 0.85rem;
            ">
                No hay imágenes agregadas
            </div>
        `;
        return;
    }

    container.innerHTML = imagenes.map((img, idx) => `
        <div style="
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 6px;
            overflow: hidden;
            background: white;
            border: 1px solid #e5e7eb;
        ">
            <img src="${typeof img === 'string' ? img : img.preview || img.url}" 
                 alt="Imagen ${idx + 1}"
                 style="
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                ">
            <button type="button" 
                    onclick="eliminarImagenReflectivo(${prendaIndex}, ${idx})"
                    style="
                        position: absolute;
                        top: 4px;
                        right: 4px;
                        background: #1e40af;
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 20px;
                        height: 20px;
                        padding: 0;
                        cursor: pointer;
                        font-size: 0.75rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s ease;
                    "
                    title="Eliminar imagen"
                    onmouseover="this.style.transform = 'scale(1.1)'; this.style.background = '#0ea5e9';"
                    onmouseout="this.style.transform = 'scale(1)'; this.style.background = '#1e40af';">
                ×
            </button>
        </div>
    `).join('');
}

/**
 * Renderizar las tallas de una prenda reflectivo
 * @param {number} prendaIndex - Índice de la prenda
 * @param {Array} tallas - Array de tallas
 */
/**
 *  FUNCIÓN DEPRECADA - Se mantiene por compatibilidad
 * Ya no se usa el sistema de tags, ahora usa generosConTallas
 */
function renderizarTallasReflectivo(prendaIndex, tallas = []) {
    //  NUEVO: Renderizar con sistema de géneros como en PRENDA
    // Las tallas se renderizarán cuando se agreguen a través de agregarTallasAlGeneroReflectivo()
    return; // No hacer nada, el nuevo sistema maneja todo
}

/**
 * Procesar imágenes seleccionadas para una prenda reflectivo
 * @param {number} prendaIndex - Índice de la prenda
 * @param {FileList} files - Archivos seleccionados
 */
function procesarImagenesReflectivo(prendaIndex, files) {
    if (!files || files.length === 0) return;

    const gestor = window.gestorReflectivoSinCotizacion;
    const fotosActuales = gestor.obtenerFotosNuevas(prendaIndex) || [];
    const espacioDisponible = Math.max(0, 3 - fotosActuales.length);

    if (espacioDisponible <= 0) {
        modalAlertaReflectivo('Límite de imágenes', 'Máximo 3 imágenes permitidas para esta prenda');
        return;
    }

    const fotosAgregar = Array.from(files).slice(0, espacioDisponible);
    const fotos = [];

    let procesadas = 0;
    fotosAgregar.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            fotos.push({
                id: 'new_' + Date.now() + '_' + idx,
                preview: e.target.result,
                file: file
            });

            procesadas++;
            if (procesadas === fotosAgregar.length) {
                gestor.agregarFotos(prendaIndex, fotos);
                renderizarImagenesReflectivo(prendaIndex, gestor.obtenerFotosNuevas(prendaIndex));
            }
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Renderizar ubicaciones agregadas de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {Array} ubicaciones - Array de ubicaciones
 */
function renderizarUbicacionesReflectivo(prendaIndex, ubicaciones = []) {
    const container = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"] .ubicaciones-agregadas-reflectivo`);
    if (!container) return;

    if (ubicaciones.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = ubicaciones.map((ubicacion) => `
        <div style="
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            min-width: 200px;
            position: relative;
        ">
            <div style="flex: 1; margin-right: 0.5rem;">
                <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 0.25rem;">
                    <i class="fas fa-map-pin" style="margin-right: 0.3rem;"></i>${ubicacion.nombre}
                </div>
                ${ubicacion.observaciones ? `
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.25rem;">
                        ${ubicacion.observaciones}
                    </div>
                ` : ''}
            </div>
            <button type="button"
                    onclick="eliminarUbicacionReflectivo(${prendaIndex}, ${ubicacion.id})"
                    style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        cursor: pointer;
                        font-size: 1.1rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 0;
                        flex-shrink: 0;
                        line-height: 1;
                    "
                    title="Eliminar ubicación">
                ✕
            </button>
        </div>
    `).join('');
}

/**
 * Renderizar modal para agregar ubicación
 * @param {number} prendaIndex - Índice de la prenda
 * @returns {string} HTML del modal
 */
function renderizarModalAgregarUbicacionReflectivo(prendaIndex) {
    const input = document.querySelector(`.prenda-card-reflectivo[data-prenda-index="${prendaIndex}"] .ubicacion-input-reflectivo`);
    const ubicacion = input ? input.value.trim() : '';

    return `
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        ">
            <div style="
                background: white;
                border-radius: 12px;
                padding: 2rem;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            ">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #1e40af; margin: 0;">
                        Agregar Ubicación
                    </h2>
                    <button type="button" onclick="cerrarModalAgregarUbicacionReflectivo()" style="
                        background: none;
                        border: none;
                        font-size: 1.5rem;
                        color: #94a3b8;
                        cursor: pointer;
                    ">✕</button>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                        UBICACIÓN:
                    </label>
                    <input type="text" 
                           id="modal-ubicacion-input-reflectivo"
                           value="${ubicacion}"
                           placeholder="Ej: Pecho, Espalda, Manga..."
                           style="
                               width: 100%;
                               padding: 0.75rem;
                               border: 2px solid #cbd5e1;
                               border-radius: 6px;
                               font-size: 0.95rem;
                               background: white;
                               font-family: inherit;
                               box-sizing: border-box;
                           ">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">
                        OBSERVACIÓN/DETALLES:
                    </label>
                    <textarea id="modal-observaciones-input-reflectivo"
                              placeholder="Ej: Franja vertical de 10cm, color plateado, lado izquierdo..."
                              style="
                                  width: 100%;
                                  padding: 0.75rem;
                                  border: 2px solid #cbd5e1;
                                  border-radius: 6px;
                                  font-size: 0.95rem;
                                  background: white;
                                  font-family: inherit;
                                  box-sizing: border-box;
                                  min-height: 100px;
                                  resize: vertical;
                              "></textarea>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" 
                            onclick="cerrarModalAgregarUbicacionReflectivo()"
                            style="
                                padding: 0.75rem 1.5rem;
                                border: 2px solid #cbd5e1;
                                background: white;
                                color: #64748b;
                                border-radius: 6px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.95rem;
                                transition: all 0.2s ease;
                            "
                            onmouseover="this.style.borderColor = '#94a3b8'; this.style.color = '#475569';"
                            onmouseout="this.style.borderColor = '#cbd5e1'; this.style.color = '#64748b';">
                        Cancelar
                    </button>
                    <button type="button" 
                            onclick="guardarUbicacionReflectivo(${prendaIndex})"
                            style="
                                padding: 0.75rem 1.5rem;
                                background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                                color: white;
                                border: none;
                                border-radius: 6px;
                                cursor: pointer;
                                font-weight: 700;
                                font-size: 0.95rem;
                                box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
                                transition: all 0.2s ease;
                            "
                            onmouseover="this.style.boxShadow = '0 6px 16px rgba(14, 165, 233, 0.4)';"
                            onmouseout="this.style.boxShadow = '0 4px 12px rgba(14, 165, 233, 0.3)';">
                        Guardar Ubicación
                    </button>
                </div>
            </div>
        </div>
    `;
}

