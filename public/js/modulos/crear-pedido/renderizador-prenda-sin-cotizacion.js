/**
 * RENDERIZADOR DE PRENDAS SIN COTIZACIÓN - Tipo PRENDA
 * 
 * Maneja toda la lógica de renderización HTML para prendas de tipo PRENDA
 * cuando no hay cotización previa.
 * 
 * RESPONSABILIDADES:
 * - Renderizar tarjetas de prenda con todos los campos
 * - Renderizar secciones de tallas
 * - Renderizar secciones de variaciones
 * - Renderizar secciones de telas
 * - Renderizar galerías de fotos
 * - Gestionar interacciones de usuario
 * - Sincronizar datos entre UI y gestor
 */

/**
 * Sincronizar datos de UI con el gestor ANTES de renderizar
 * CRÍTICO: Se debe llamar ANTES de renderizar para no perder datos
 */
function sincronizarDatosAntesDERenderizar() {
    if (!window.gestorPrendaSinCotizacion) return;

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();
    
    prendas.forEach((prenda, prendaIndex) => {
        // Sincronizar nombre, descripción y género
        const inputNombre = document.querySelector(`.prenda-nombre[data-prenda="${prendaIndex}"]`);
        const inputDesc = document.querySelector(`.prenda-descripcion[data-prenda="${prendaIndex}"]`);
        const selectGenero = document.querySelector(`.prenda-genero[data-prenda="${prendaIndex}"]`);
        
        if (inputNombre && inputNombre.value) {
            prenda.nombre_producto = inputNombre.value;
        }
        if (inputDesc && inputDesc.value) {
            prenda.descripcion = inputDesc.value;
        }
        if (selectGenero && selectGenero.value) {
            prenda.genero = selectGenero.value;
        }
        
        // Sincronizar cantidades de tallas
        document.querySelectorAll(`.talla-cantidad[data-prenda="${prendaIndex}"]`).forEach(input => {
            const talla = input.dataset.talla;
            const cantidad = parseInt(input.value) || 0;
            if (prenda.cantidadesPorTalla) {
                prenda.cantidadesPorTalla[talla] = cantidad;
            }
        });

        // Sincronizar datos de telas
        document.querySelectorAll(`[data-prenda-index="${prendaIndex}"] [data-tela-index]`).forEach(row => {
            const telaIdx = parseInt(row.dataset.telaIndex);
            const inputNombreTela = row.querySelector('.tela-nombre');
            const inputColor = row.querySelector('.tela-color');
            const inputReferencia = row.querySelector('.tela-referencia');
            
            if (prenda.variantes?.telas_multiples?.[telaIdx]) {
                if (inputNombreTela?.value) prenda.variantes.telas_multiples[telaIdx].nombre_tela = inputNombreTela.value;
                if (inputColor?.value) prenda.variantes.telas_multiples[telaIdx].color = inputColor.value;
                if (inputReferencia?.value) prenda.variantes.telas_multiples[telaIdx].referencia = inputReferencia.value;
            }
        });

        // Sincronizar variaciones
        document.querySelectorAll(`[data-prenda-index="${prendaIndex}"] [data-field]`).forEach(field => {
            const nombreCampo = field.dataset.field;
            if (nombreCampo && !nombreCampo.includes('_obs')) {
                const valor = field.value || field.textContent;
                if (prenda.variantes && nombreCampo in prenda.variantes) {
                    if (nombreCampo.includes('tiene_')) {
                        prenda.variantes[nombreCampo] = valor === 'Sí';
                        prenda[nombreCampo] = valor === 'Sí';
                    } else {
                        prenda.variantes[nombreCampo] = valor;
                        prenda[nombreCampo] = valor;
                    }
                }
            }
        });
    });

    logWithEmoji('🔄', 'Datos sincronizados antes de renderizar');
}

/**
 * Renderizar todas las prendas de tipo PRENDA sin cotización
 */
function renderizarPrendasTipoPrendaSinCotizacion() {
    const container = document.getElementById('prendas-container-editable');
    if (!container || !window.gestorPrendaSinCotizacion) return;

    // 🔴 CRÍTICO: Sincronizar datos ANTES de renderizar
    sincronizarDatosAntesDERenderizar();

    const prendas = window.gestorPrendaSinCotizacion.obtenerActivas();

    if (prendas.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 2rem;">
                <p style="color: #6b7280; margin-bottom: 1rem;">No hay prendas agregadas.</p>
                <button type="button" onclick="agregarPrendaTipoPrendaSinCotizacion()" class="btn btn-primary" 
                        style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Prenda
                </button>
            </div>
        `;
        return;
    }

    let html = '';
    prendas.forEach((prenda, index) => {
        html += renderizarPrendaTipoPrenda(prenda, index);
    });

    html += `
        <div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <button type="button" onclick="agregarPrendaTipoPrendaSinCotizacion()" class="btn btn-primary" 
                    style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                ➕ Agregar Otra Prenda
            </button>
            <button type="button" onclick="enviarPrendaSinCotizacion()" class="btn btn-success" 
                    style="background: #28a745; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                ✅ Guardar Pedido PRENDA
            </button>
        </div>
    `;

    container.innerHTML = html;

    // Re-attach event listeners después de renderizar
    setTimeout(() => {
        attachPrendaTipoPrendaListeners(prendas);
    }, 100);
}

/**
 * Renderizar una tarjeta individual de prenda tipo PRENDA
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de la tarjeta
 */
function renderizarPrendaTipoPrenda(prenda, index) {
    const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevas(index);
    const fotos = [...(prenda.fotos || []), ...fotosNuevas];
    const fotoPrincipal = fotos.length > 0 ? fotos[0] : null;
    const fotosAdicionales = fotos.length > 1 ? fotos.slice(1) : [];

    // HTML de fotos
    let fotosHtml = '';
    if (fotoPrincipal) {
        const fotoUrl = typeof fotoPrincipal === 'string' ? fotoPrincipal : (fotoPrincipal.url || fotoPrincipal.ruta_webp || fotoPrincipal.ruta_original || '');
        const restantes = fotosAdicionales.length;
        fotosHtml = `
            <div style="position: relative; width: 100%; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.75rem 0.75rem 0.6rem 0.75rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">Galería de la prenda</div>
                    <button type="button"
                            onclick="abrirModalAgregarFotosPrendaTipo(${index})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);">
                        ＋
                    </button>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <div style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #d0d0d0; background: white; flex-shrink: 0; position: relative;">
                        <img src="${fotoUrl}" alt="Foto de prenda"
                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" 
                             ondblclick="abrirGaleriaPrendaTipo(${index})" />
                        ${restantes > 0 ? `<span style="position: absolute; bottom: 6px; right: 6px; background: #1e40af; color: white; padding: 2px 6px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">+${restantes}</span>` : ''}
                        <button type="button" onclick="eliminarImagenPrendaTipo(this, ${index})"
                                style="position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem;">
                        <p style="margin: 0; font-size: 0.9rem; color: #1e3a8a; font-weight: 600;">Fotos agregadas: ${fotos.length}</p>
                        <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">Doble click en la imagen para ver galería</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        fotosHtml = `
            <div style="border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 1.5rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📸</div>
                <p style="color: #1e3a8a; font-weight: 600; margin: 0 0 1rem 0;">Sin fotos de prenda</p>
                <button type="button" onclick="abrirModalAgregarFotosPrendaTipo(${index})"
                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Foto
                </button>
            </div>
        `;
    }

    // HTML de tallas
    let tallasHtml = renderizarTallasPrendaTipo(prenda, index);

    // HTML de variaciones
    let variacionesHtml = renderizarVariacionesPrendaTipo(prenda, index);

    // HTML de telas
    let telasHtml = renderizarTelasPrendaTipo(prenda, index);

    return `
        <div class="prenda-card-editable" data-prenda-index="${index}" style="margin-bottom: 2rem;">
            <div class="prenda-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                <div class="prenda-title" style="font-weight: 700; font-size: 1.125rem; color: #333;">
                    Prenda ${index + 1}: ${prenda.nombre_producto || 'Sin nombre'}
                </div>
                <button type="button" onclick="eliminarPrendaTipoPrenda(${index})"
                        class="btn-eliminar-prenda"
                        style="background: linear-gradient(135deg, #dc3545 0%, #b91c1c 100%); color: white; border: none; border-radius: 999px; padding: 0.45rem 0.85rem; cursor: pointer; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 3px 10px rgba(185,28,28,0.25);">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(255,255,255,0.18); font-size: 0.9rem; line-height: 1;">✕</span>
                    <span style="font-size: 0.9rem;">Eliminar</span>
                </button>
            </div>

            <!-- Contenido principal (2 columnas: Información + Fotos) -->
            <div class="prenda-content" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem; align-items: start; margin-bottom: 1.5rem;">
                <!-- Información de la prenda -->
                <div class="prenda-info-section" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;">Nombre del Producto:</label>
                        <input type="text" 
                               name="nombre_producto[${index}]" 
                               value="${prenda.nombre_producto || ''}"
                               class="prenda-nombre"
                               data-prenda="${index}" 
                               style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                    </div>

                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;">Descripción:</label>
                        <textarea name="descripcion[${index}]" 
                                  class="prenda-descripcion"
                                  data-prenda="${index}" 
                                  style="min-height: 110px; width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">${prenda.descripcion || ''}</textarea>
                    </div>

                    <div class="form-group-editable" style="width: 100%;">
                        <label style="font-weight: 600;">Género:</label>
                        <select name="genero[${index}]" 
                                class="prenda-genero"
                                data-prenda="${index}"
                                style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                            <option value="">-- Seleccionar --</option>
                            <option value="Dama" ${prenda.genero === 'Dama' ? 'selected' : ''}>Dama</option>
                            <option value="Caballero" ${prenda.genero === 'Caballero' ? 'selected' : ''}>Caballero</option>
                            <option value="Unisex" ${prenda.genero === 'Unisex' ? 'selected' : ''}>Unisex</option>
                        </select>
                    </div>
                </div>

                <!-- Fotos -->
                <div class="prenda-fotos-section" style="width: 100%;">
                    ${fotosHtml}
                </div>
            </div>

            <!-- Secciones de Detalles -->
            ${tallasHtml}
            ${variacionesHtml}
            ${telasHtml}
        </div>
    `;
}

/**
 * Renderizar sección de tallas
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de tallas
 */
function renderizarTallasPrendaTipo(prenda, index) {
    const tallas = prenda.tallas || [];
    if (!tallas || tallas.length === 0) {
        return `
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 6px; border-left: 4px solid #0066cc;">
                <div style="font-weight: 600; margin-bottom: 1rem; color: #333;">📏 Tallas y Cantidades</div>
                <p style="color: #999; margin: 0;">No hay tallas agregadas. Haz clic en el botón para agregar.</p>
                <button type="button" onclick="agregarTallaPrendaTipo(${index})"
                        style="margin-top: 1rem; background: #0066cc; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Talla
                </button>
            </div>
        `;
    }

    let tallasHtml = `
        <div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
            <div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0052a3 0%, #0ea5e9 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 700; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div style="display: flex; gap: 1rem; flex: 1;">
                    <div style="flex: 1.5;">📏 Talla</div>
                    <div style="flex: 1;">Cantidad</div>
                    <div style="width: 100px; text-align: center;">Acción</div>
                </div>
                <button type="button" onclick="agregarTallaPrendaTipo(${index})"
                        style="background: white; color: #0b4f91; border: none; padding: 0.45rem 0.65rem; border-radius: 999px; cursor: pointer; font-size: 1rem; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,0,0,0.18); width: 36px; height: 36px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(14,165,233,0.18); color: #0b4f91; font-size: 1rem; line-height: 1;">+</span>
                </button>
            </div>
    `;

    tallas.forEach(talla => {
        const cantidad = prenda.cantidadesPorTalla?.[talla] || 0;
        tallasHtml += `
            <div class="talla-item" style="display: flex; gap: 1rem; align-items: center; padding: 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; flex-wrap: wrap; data-talla="${talla}" data-prenda="${index}">
                <div style="flex: 1.5; min-width: 80px;">
                    <input type="text" value="${talla}" readonly
                           style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; background: #f5f5f5;">
                </div>
                <div style="flex: 1; min-width: 80px;">
                    <input type="number" min="0" value="${cantidad}"
                           class="talla-cantidad"
                           data-talla="${talla}"
                           data-prenda="${index}"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; text-align: center;">
                </div>
                <div style="width: 100px; text-align: center;">
                    <button type="button" onclick="eliminarTallaPrendaTipo(${index}, '${talla}')"
                            class="btn-quitar-talla"
                            style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">
                        ✕
                    </button>
                </div>
            </div>
        `;
    });

    tallasHtml += `</div>`;
    return tallasHtml;
}

/**
 * Renderizar sección de variaciones (manga, broche, bolsillos, reflectivo)
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de variaciones
 */
function renderizarVariacionesPrendaTipo(prenda, index) {
    const variantes = prenda.variantes || {};
    const variacionesArray = [];

    // Manga
    if (variantes.tipo_manga !== undefined) {
        variacionesArray.push({
            tipo: 'Manga',
            valor: variantes.tipo_manga || '',
            obs: variantes.obs_manga || '',
            campo: 'tipo_manga',
            esCheckbox: false,
            opciones: ['No aplica', 'Manga Corta', 'Manga Larga', 'Manga Raglan', 'Manga Campana']
        });
    }

    // Broche/Botón
    variacionesArray.push({
        tipo: 'Broche/Botón',
        valor: variantes.tipo_broche || '',
        obs: variantes.obs_broche || '',
        campo: 'tipo_broche',
        esCheckbox: false,
        opciones: ['No aplica', 'Broche', 'Botón']
    });

    // Bolsillos
    if (variantes.tiene_bolsillos !== undefined) {
        variacionesArray.push({
            tipo: 'Bolsillos',
            valor: variantes.tiene_bolsillos ? 'Sí' : 'No',
            obs: variantes.obs_bolsillos || '',
            campo: 'tiene_bolsillos',
            esCheckbox: true
        });
    }

    // Reflectivo
    if (variantes.tiene_reflectivo !== undefined) {
        variacionesArray.push({
            tipo: 'Reflectivo',
            valor: variantes.tiene_reflectivo ? 'Sí' : 'No',
            obs: variantes.obs_reflectivo || '',
            campo: 'tiene_reflectivo',
            esCheckbox: true
        });
    }

    if (variacionesArray.length === 0) {
        return '';
    }

    let html = `
        <div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
            <div style="padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">
                <div>📋 Variaciones</div>
                <div style="text-align: center;">Valor</div>
                <div>Observaciones</div>
                <div style="text-align: center;">Acción</div>
            </div>
    `;

    variacionesArray.forEach((variacion, varIdx) => {
        let inputHtml = '';
        
        if (variacion.esCheckbox) {
            const isYes = variacion.valor === 'Sí' || variacion.valor === true;
            inputHtml = `
                <select data-field="${variacion.campo}" data-prenda="${index}" data-variacion="${varIdx}"
                        style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    <option value="No" ${!isYes ? 'selected' : ''}>No</option>
                    <option value="Sí" ${isYes ? 'selected' : ''}>Sí</option>
                </select>
            `;
        } else {
            let selectOptions = '<option value="">-- Seleccionar --</option>';
            const selectedValue = variacion.valor?.trim() || '';
            
            variacion.opciones?.forEach(opcion => {
                const isSelected = selectedValue === opcion ? 'selected' : '';
                selectOptions += `<option value="${opcion}" ${isSelected}>${opcion}</option>`;
            });
            
            if (selectedValue && !variacion.opciones.includes(selectedValue)) {
                selectOptions += `<option value="${selectedValue}" selected>${selectedValue}</option>`;
            }
            
            inputHtml = `
                <select data-field="${variacion.campo}" data-prenda="${index}" data-variacion="${varIdx}"
                        style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    ${selectOptions}
                </select>
            `;
        }

        html += `
            <div style="padding: 0.6rem 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">
                <div style="font-weight: 500; color: #1f2937;">${variacion.tipo}</div>
                <div style="text-align: center;">
                    ${inputHtml}
                </div>
                <div>
                    <textarea class="variacion-obs"
                              data-field="${variacion.campo}_obs"
                              data-prenda="${index}"
                              data-variacion="${varIdx}"
                              style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; min-height: 36px; resize: vertical; font-family: inherit; box-sizing: border-box;"
                              placeholder="...">${variacion.obs || ''}</textarea>
                </div>
                <div style="text-align: center;">
                    <button type="button" onclick="eliminarVariacionPrendaTipo(${index}, ${varIdx})"
                            class="btn-eliminar-variacion"
                            style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.5rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">
                        ✕
                    </button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
}

/**
 * Renderizar sección de telas
 * @param {Object} prenda - Objeto de prenda
 * @param {number} index - Índice de la prenda
 * @returns {string} HTML de telas
 */
function renderizarTelasPrendaTipo(prenda, index) {
    const telas = prenda.variantes?.telas_multiples || [];

    if (!telas || telas.length === 0) {
        return `
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 6px; border-left: 4px solid #0066cc;">
                <div style="font-weight: 600; margin-bottom: 1rem; color: #333;">🎨 Telas y Colores</div>
                <p style="color: #999; margin: 0;">No hay telas agregadas. Haz clic en el botón para agregar.</p>
                <button type="button" onclick="agregarTelaPrendaTipo(${index})"
                        style="margin-top: 1rem; background: #0066cc; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    ➕ Agregar Tela
                </button>
            </div>
        `;
    }

    let html = `
        <div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
            <div style="position: relative; padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; width: 100%;">
                <div>🎨 Telas</div>
                <div>Color</div>
                <div>Referencia</div>
                <div style="text-align: center;">Fotos</div>
                <button type="button" onclick="agregarTelaPrendaTipo(${index})"
                        style="position: absolute; top: 10px; right: 12px; background: white; color: #0052a3; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-weight: 900; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.18);">
                    ＋
                </button>
            </div>
    `;

    telas.forEach((tela, telaIdx) => {
        const fotosNuevas = window.gestorPrendaSinCotizacion.obtenerFotosNuevasTela(index, telaIdx);
        const fotosTelaJSON = prenda.telaFotos?.filter(f => f.tela_id === tela.id) || [];
        const fotosDeTela = [...fotosTelaJSON, ...fotosNuevas];
        const fotoPrincipal = fotosDeTela.length > 0 ? fotosDeTela[0] : null;
        const restantes = Math.max(0, fotosDeTela.length - 1);

        let fotosTelaHtml = '';
        if (fotoPrincipal) {
            const fotoUrl = typeof fotoPrincipal === 'string' ? fotoPrincipal : (fotoPrincipal.url || fotoPrincipal.ruta_webp || '');
            fotosTelaHtml = `
                <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column; align-items: center; gap: 0.4rem;">
                    <div style="position: relative; width: 90px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #d0d0d0; background: white;">
                        ${fotoUrl ? `<img src="${fotoUrl}" alt="Foto de tela" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" ondblclick="abrirGaleriaTexturaTipo(${index}, ${telaIdx})">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;">Sin foto</div>'}
                        ${restantes > 0 ? `<span style="position:absolute; bottom:6px; right:6px; background:#1e40af; color:white; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:700;">+${restantes}</span>` : ''}
                        <button type="button" onclick="eliminarImagenTelaTipo(this, ${index}, ${telaIdx})"
                                style="position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                    </div>
                    <button type="button" onclick="abrirModalAgregarFotosTelaType(${index}, ${telaIdx})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);">＋</button>
                </div>
            `;
        } else {
            fotosTelaHtml = `
                <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                    <div style="font-size: 0.8rem; color: #1e3a8a; font-weight: 600; text-align: center;">Sin fotos</div>
                    <button type="button" onclick="abrirModalAgregarFotosTelaType(${index}, ${telaIdx})"
                            style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);">＋</button>
                </div>
            `;
        }

        const nombreTela = tela.nombre_tela || '';
        const colorTela = typeof tela.color === 'object' ? (tela.color?.nombre || '') : (tela.color || '');
        const referencia = tela.referencia || '';

        html += `
            <div style="padding: 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; width: 100%;" data-tela-index="${telaIdx}">
                <input type="text" value="${nombreTela}" placeholder="Nombre de tela"
                       class="tela-nombre"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <input type="text" value="${colorTela}" placeholder="Color"
                       class="tela-color"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <input type="text" value="${referencia}" placeholder="Referencia"
                       class="tela-referencia"
                       data-prenda="${index}"
                       data-tela="${telaIdx}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    ${fotosTelaHtml}
                    <button type="button" onclick="eliminarTelaPrendaTipo(${index}, ${telaIdx})"
                            class="btn-eliminar-tela"
                            style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.85rem; width: 100%;">
                        ✕ Quitar
                    </button>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    return html;
}

/**
 * Adjuntar event listeners a los elementos renderizados
 * @param {Array} prendas - Prendas a monitorear
 */
function attachPrendaTipoPrendaListeners(prendas) {
    // Listeners para cambios en nombre de producto
    document.querySelectorAll('.prenda-nombre').forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const nuevoNombre = e.target.value;
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.nombre_producto = nuevoNombre;
            }
        });
    });

    // Listeners para cambios en descripción
    document.querySelectorAll('.prenda-descripcion').forEach(textarea => {
        textarea.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.descripcion = e.target.value;
            }
        });
    });

    // Listeners para cambios en género
    document.querySelectorAll('.prenda-genero').forEach(select => {
        select.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(index);
            if (prenda) {
                prenda.genero = e.target.value;
            }
        });
    });

    // Listeners para cambios en cantidad de talla
    document.querySelectorAll('.talla-cantidad').forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.dataset.prenda);
            const talla = e.target.dataset.talla;
            const cantidad = parseInt(e.target.value) || 0;
            window.gestorPrendaSinCotizacion.actualizarCantidadTalla(index, talla, cantidad);
        });
    });

    logWithEmoji('🔗', 'Event listeners adjunados a prendas tipo PRENDA');
}

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        renderizarPrendasTipoPrendaSinCotizacion,
        renderizarPrendaTipoPrenda,
        attachPrendaTipoPrendaListeners
    };
}
