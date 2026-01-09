/**
 * TEMPLATES HTML - Crear Pedido Editable
 * 
 * Este archivo contiene todos los templates HTML que se generan dinámicamente
 * en crear-pedido-editable.js para mantener el código más limpio y legible.
 * 
 * Los templates están organizados en funciones reutilizables que retornan 
 * strings HTML generados con template literals.
 */

// ============================================================
// TEMPLATES DE TABS PRINCIPALES
// ============================================================

window.templates = {
    
    /**
     * Template para el contenedor de tabs (navegación)
     */
    tabsContainer: () => `<div id="tabs-container-pedido" style="
        display: flex;
        gap: 0;
        margin-bottom: 0;
        border-bottom: 2px solid #e2e8f0;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        overflow-x: auto;
        overflow-y: hidden;
        width: 100%;
        flex-wrap: nowrap;
        align-items: stretch;
        box-sizing: border-box;
    ">`,

    /**
     * Tab button para navegación
     */
    tabButton: (label, icon, isActive = false) => `<button type="button" class="tab-button-editable ${isActive ? 'active' : ''}" data-tab="${label.toLowerCase()}" onclick="cambiarTab('${label.toLowerCase()}', this)" style="
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        color: #64748b;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 3px solid transparent;
        position: relative;
        bottom: -2px;
        white-space: nowrap;
        flex-shrink: 0;
    ">
        <i class="${icon}"></i> ${label}
    </button>`,

    /**
     * Contenedor de contenido de tabs
     */
    tabContentWrapper: () => `<div class="tab-content-wrapper" style="
        background: white;
        border-radius: 0 0 12px 12px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        width: 100%;
        display: block;
        box-sizing: border-box;
        max-width: 100%;
        margin: 0;
    "></div>`,

    /**
     * Tab individual de contenido
     */
    tabContent: (id, isActive = false) => `<div id="${id}" class="tab-content ${isActive ? 'active' : ''}" style="display: ${isActive ? 'block' : 'none'};">`,

    // ============================================================
    // TEMPLATES DE TARJETA DE PRENDA
    // ============================================================

    /**
     * Encabezado de tarjeta de prenda
     */
    prendaHeader: (index, nombre) => `<div class="prenda-header">
        <div class="prenda-title">
            Prenda ${index + 1}: ${nombre}
        </div>
        <div class="prenda-actions">
            <button type="button"
                    class="btn-eliminar-prenda"
                    onclick="eliminarPrendaDelPedido(${index})"
                    style="background: linear-gradient(135deg, #dc3545 0%, #b91c1c 100%); color: white; border: none; border-radius: 999px; padding: 0.45rem 0.85rem; cursor: pointer; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 3px 10px rgba(185,28,28,0.25);">
                <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(255,255,255,0.18); font-size: 0.9rem; line-height: 1;">✕</span>
                <span style="font-size: 0.9rem;">Eliminar</span>
            </button>
        </div>
    </div>`,

    /**
     * Contenido principal de prenda con 2 columnas
     */
    prendaContent: (fotosHtml) => `<div class="prenda-content" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem; align-items: start;">
        <div class="prenda-info-section" style="display: flex; flex-direction: column; gap: 0.75rem;">
            {FORM_FIELDS}
        </div>
        <div class="prenda-fotos-section" style="width: 100%;">
            ${fotosHtml}
        </div>
    </div>`,

    /**
     * Campo de formulario de prenda (nombre o descripción)
     */
    prendaFormField: (label, name, value, type = 'text', index) => {
        if (type === 'textarea') {
            return `<div class="form-group-editable" style="width: 100%;">
                <label style="font-weight: 600;">${label}:</label>
                <textarea name="${name}" 
                          class="prenda-${name.replace(/\[|\]/g, '')}"
                          data-prenda="${index}" style="min-height: 110px; width: 100%;">${value || ''}</textarea>
            </div>`;
        }
        return `<div class="form-group-editable" style="width: 100%;">
            <label style="font-weight: 600;">${label}:</label>
            <input type="${type}" 
                   name="${name}" 
                   value="${value || ''}"
                   class="prenda-${name.replace(/\[|\]/g, '')}"
                   data-prenda="${index}" style="width: 100%;">
        </div>`;
    },

    /**
     * Galería de fotos de prenda con modal
     */
    prendaGaleria: (index, fotoPrincipal, fotosNormalizadas, restantes) => `
        <div style="position: relative; width: 100%; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.75rem 0.75rem 0.6rem 0.75rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">Galería de la prenda</div>
                <button type="button"
                        onclick="abrirModalAgregarFotosPrenda(${index})"
                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);"
                        title="Agregar foto">
                    ＋
                </button>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.65rem;">
                ${fotoPrincipal ? `
                    ${fotosNormalizadas.slice(0, 1).map((foto) => `
                        <div style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-height: 180px; overflow: hidden; border-radius: 8px; border: 1px solid #d1d5db; box-shadow: 0 2px 6px rgba(0,0,0,0.08); background: white;">
                            <img src="${foto}" alt="Foto prenda"
                                 class="prenda-foto-thumb"
                                 data-prenda-index="${index}"
                                 data-foto-idx="${fotosNormalizadas.indexOf(foto)}"
                                 style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                 onclick="abrirGaleriaPrenda(${index}, ${fotosNormalizadas.indexOf(foto)})">
                            <button type="button"
                                    onclick="eliminarImagenPrenda(this)"
                                    style="position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10; transform: translate(0,0); opacity: 0.95;" title="Eliminar imagen">×</button>
                        </div>
                    `).join('')}
                    ${restantes > 0 ? `<div style="width: 100%; aspect-ratio: 1/1; max-height: 180px; display:flex; align-items:center; justify-content:center; border: 1px dashed #1e40af; border-radius: 8px; background: #e0f2fe; color: #1e40af; font-weight: 700; font-size: 0.95rem;">+${restantes} más</div>` : ''}
                ` : `
                    <div style="width: 100%; aspect-ratio: 1/1; max-height: 180px; display:flex; align-items:center; justify-content:center; border: 2px dashed #9ca3af; border-radius: 8px; background: #f3f4f6; color: #6b7280; font-weight: 600; font-size: 0.9rem; text-align: center; padding: 1rem;">
                        📁 Sin fotos<br><span style="font-size: 0.8rem; font-weight: 400;">Agrega fotos para mostrar aquí</span>
                    </div>
                `}
            </div>
        </div>
    `,

    // ============================================================
    // TEMPLATES DE TABLAS (TALLAS, VARIACIONES, TELAS)
    // ============================================================

    /**
     * Encabezado de tabla editable (azul degradado)
     */
    tableHeader: (columns, hasAddButton = false, addButtonText = '+', addButtonOnClick = '') => {
        let headerHtml = `<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0052a3 0%, #0ea5e9 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 700; display: flex; justify-content: space-between; align-items: center; width: 100%;">`;
        headerHtml += `<div style="display: flex; gap: 1rem; flex: 1;">`;
        columns.forEach(col => {
            headerHtml += `<div style="flex: ${col.flex || '1'};">${col.name}</div>`;
        });
        headerHtml += `</div>`;
        if (hasAddButton) {
            headerHtml += `
                <button type="button" onclick="${addButtonOnClick}" style="background: white; color: #0b4f91; border: none; padding: 0.45rem 0.65rem; border-radius: 999px; cursor: pointer; font-size: 1rem; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,0,0,0.18); width: 36px; height: 36px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(14,165,233,0.18); color: #0b4f91; font-size: 1rem; line-height: 1;">+</span>
                </button>
            `;
        }
        headerHtml += `</div>`;
        return headerHtml;
    },

    /**
     * Fila de tabla con talla y cantidad
     */
    tallaRow: (index, talla) => `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
        <div style="display: flex; flex-direction: column;">
            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
            <div style="font-weight: 500; color: #1f2937;">${talla}</div>
        </div>
        <div style="display: flex; flex-direction: column;">
            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
            <input type="number" 
                   name="cantidades[${index}][${talla}]" 
                   class="talla-cantidad"
                   min="0" 
                   value="0" 
                   placeholder="0"
                   data-talla="${talla}"
                   data-prenda="${index}"
                   style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
        </div>
        <div style="text-align: center;">
            <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${index}, '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                ✕ Quitar
            </button>
        </div>
    </div>`,

    /**
     * Fila de tabla para variaciones
     */
    variacionRow: (index, varIdx, variacion, inputHtml) => `<div style="padding: 0.6rem 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; transition: background 0.2s; width: 100%; font-size: 0.85rem;" data-variacion="${varIdx}" data-prenda="${index}">
        <div style="font-weight: 500; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${variacion.tipo}</div>
        <div style="display: flex; justify-content: center; align-items: center;">
            ${inputHtml}
        </div>
        <div style="display: flex; align-items: center;">
            <textarea 
                   data-field="${variacion.campo}_obs" 
                   data-prenda="${index}"
                   data-variacion="${varIdx}"
                   style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; min-height: 36px; resize: vertical; font-family: inherit; box-sizing: border-box;" placeholder="...">${variacion.obs || ''}</textarea>
        </div>
        <div style="display: flex; justify-content: center; align-items: center;">
            <button type="button" 
                    class="btn-eliminar-variacion" 
                    onclick="eliminarVariacionDePrenda(${index}, ${varIdx})"
                    title="Eliminar variación"
                    style="background: #dc3545; color: white; border: none; padding: 0.4rem; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; min-width: auto; flex-shrink: 0;">
                ✕
            </button>
        </div>
    </div>`,

    /**
     * Fila de tabla para telas/colores
     */
    telaRow: (index, telaIdx, tela, fotosTelaHtml) => `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
        <div style="display: flex; flex-direction: column;">
            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Tela</label>
            <input type="text" value="${tela.nombre_tela || ''}" data-field="tela_nombre" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: Algodón">
        </div>
        <div style="display: flex; flex-direction: column;">
            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Color</label>
            <input type="text" value="${typeof tela.color === 'object' ? (tela.color?.nombre || tela.color?.name || '') : (tela.color || '')}" data-field="tela_color" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: Rojo">
        </div>
        <div style="display: flex; flex-direction: column;">
            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Referencia</label>
            <input type="text" value="${tela.referencia || ''}" data-field="tela_ref" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: REF-001">
        </div>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            ${fotosTelaHtml}
            <button type="button"
                    onclick="eliminarFilaTela(${index}, ${telaIdx})"
                    style="background: #dc3545; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.18);"
                    title="Eliminar fila">×</button>
        </div>
    </div>`,

    // ============================================================
    // TEMPLATES DE LOGO
    // ============================================================

    /**
     * Encabezado de sección de logo
     */
    logoHeader: () => `<div style="margin-top: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; color: #1f2937; border-bottom: 3px solid #0066cc; padding-bottom: 0.75rem;">📋 Información del Logo</h3>`,

    /**
     * Campo de descripción del logo
     */
    logoDescripcion: (value = '') => `<div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">DESCRIPCIÓN</label>
        <textarea id="logo_descripcion" name="logo_descripcion" 
                  style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 100px; color: #333;">${value || ''}</textarea>
    </div>`,

    /**
     * Galería de fotos del logo - solo encabezado y apertura
     */
    logoFotosGaleriaStart: () => `<div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">IMÁGENES (MÁXIMO 5)</label>
            <button type="button" onclick="abrirModalAgregarFotosLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        <div id="galeria-fotos-logo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">`,

    /**
     * Cierre de galería de fotos
     */
    logoFotosGaleriaEnd: () => `</div>
    </div>`,

    /**
     * Galería de fotos del logo (versión completa - sin fotos)
     */
    logoFotosGaleria: () => `<div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">IMÁGENES (MÁXIMO 5)</label>
            <button type="button" onclick="abrirModalAgregarFotosLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        <div id="galeria-fotos-logo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;"></div>
    </div>`,

    /**
     * Tabla de ubicaciones del logo
     */
    logoUbicacionesTabla: () => `<div style="margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem; margin: 0; flex-shrink: 0;">📍 UBICACIÓN</label>
            <input type="text" id="seccion_prenda_logo_tab" placeholder="Ej: CAMISA, JEAN, GORRA" style="flex: 1; padding: 0.6rem 0.75rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;" />
            <button type="button" onclick="agregarSeccionLogoTab()" title="Agregar nueva sección" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,102,204,0.3); flex-shrink: 0;">+</button>
        </div>
        <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 8px; overflow: hidden; font-size: 0.9rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                    <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Sección</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Tallas</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Ubicaciones</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Obs.</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; width: 100px; color: white;">Acciones</th>
                </tr>
            </thead>
            <tbody id="logo-ubicaciones-tbody-tab"></tbody>
        </table>
    </div>`,

    /**
     * Técnicas selector y tabla
     */
    logoTecnicasSelectorAndTable: () => `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">Técnicas Disponibles</label>
            <button type="button" onclick="agregarTecnicaTabLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        <select id="selector_tecnicas_logo" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            <option value="">-- SELECCIONA UNA TÉCNICA --</option>
            <option value="BORDADO">BORDADO</option>
            <option value="DTF">DTF</option>
            <option value="ESTAMPADO">ESTAMPADO</option>
            <option value="SUBLIMADO">SUBLIMADO</option>
        </select>
        <div id="tecnicas_seleccionadas_logo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
    </div>`,

    /**
     * Observaciones de técnicas
     */
    logoObservacionesTecnicas: (value = '') => `<div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Observaciones de Técnicas</label>
        <textarea id="logo_observaciones_tecnicas" name="logo_observaciones_tecnicas" 
                  style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 80px; color: #333;">${value || ''}</textarea>
    </div>`,

    /**
     * Contenedor de prenda (tarjeta completa)
     */
    prendaCard: (index, nombre) => `<div class="prenda-card-editable" data-prenda-index="${index}">
        {HEADER}
        {CONTENT}
        {VARIACIONES}
        {TALLAS}
        {TELAS}
    </div>`,

    /**
     * Tabla de telas con encabezado mejorado
     */
    /**
     * Contenedor del tab-logo con estilos
     */
    logoTabContainer: () => `<div id="tab-logo" class="tab-content" style="display: none;">
        <div style="margin-top: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; color: #1f2937; border-bottom: 3px solid #0066cc; padding-bottom: 0.75rem;">📋 Información del Logo</h3>`,

    /**
     * Cierre del contenedor del tab-logo
     */
    logoTabContainerClose: () => `</div></div>`,

    /**
     * Cierre del contenedor tab-prendas
     */
    prendasTabClose: () => `</div>`,

    /**
     * Contenedor de tarjeta de prenda completa
     */
    prendaCardStart: (index, nombreProenda) => `<div class="prenda-card-editable" data-prenda-index="${index}">
        <div class="prenda-header">
            <div class="prenda-title">
                Prenda ${index + 1}: ${nombreProenda}
            </div>
            <div class="prenda-actions">
                <button type="button"
                        class="btn-eliminar-prenda"
                        onclick="eliminarPrendaDelPedido(${index})"
                        style="background: linear-gradient(135deg, #dc3545 0%, #b91c1c 100%); color: white; border: none; border-radius: 999px; padding: 0.45rem 0.85rem; cursor: pointer; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 3px 10px rgba(185,28,28,0.25);">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(255,255,255,0.18); font-size: 0.9rem; line-height: 1;">✕</span>
                    <span style="font-size: 0.9rem;">Eliminar</span>
                </button>
            </div>
        </div>`,

    /**
     * Contenido de prenda (formulario)
     */
    prendaCardContent: (index, prenda, fotosHtml) => `<div class="prenda-content" style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="prenda-info-section" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1rem; align-items: start;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="form-group-editable" style="width: 100%;">
                    <label style="font-weight: 600;">Nombre del Producto:</label>
                    <input type="text" 
                           name="nombre_producto[${index}]" 
                           value="${prenda.nombre_producto || ''}"
                           class="prenda-nombre"
                           data-prenda="${index}" style="width: 100%;">
                </div>

                <div class="form-group-editable" style="width: 100%;">
                    <label style="font-weight: 600;">Descripción:</label>
                    <textarea name="descripcion[${index}]" 
                              class="prenda-descripcion"
                              data-prenda="${index}" style="min-height: 110px; width: 100%;">${prenda.descripcion || ''}</textarea>
                </div>
            </div>

            <div class="prenda-fotos-section" style="width: 100%;">
                ${fotosHtml}
            </div>
        </div>
        
        <!-- NUEVO: SELECTOR DE MÚLTIPLES GÉNEROS -->
        <div class="genero-selector" style="margin: 1rem 0; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">
                <i class="fas fa-venus"></i> Selecciona género(s) y asigna tallas para cada uno:
            </label>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="genero[${index}][]" value="dama" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer; width: 18px; height: 18px;">
                    <span style="font-size: 0.9rem; color: #374151; font-weight: 500;"><i class="fas fa-user"></i> Dama</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="genero[${index}][]" value="caballero" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer; width: 18px; height: 18px;">
                    <span style="font-size: 0.9rem; color: #374151; font-weight: 500;"><i class="fas fa-user"></i> Caballero</span>
                </label>
            </div>
            
            <!-- CONTENEDOR DINÁMICO DE TALLAS POR GÉNERO -->
            <div class="tallas-por-genero-container" style="margin-top: 1rem;">
                <!-- Se llena dinámicamente cuando se selecciona un género -->
            </div>
        </div>
    </div>`,

    /**
     * Cierre de tarjeta de prenda
     */
    prendaCardClose: () => `</div>`,

    telasTableHeader: (index) => `<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">
        <div style="position: relative; padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 1fr 1fr 120px; gap: 1rem; align-items: center; width: 100%;">
            <div>Telas</div>
            <div>Color</div>
            <div>Referencia</div>
            <div style="text-align: center;">Fotos</div>
            <button type="button" onclick="agregarFilaTela(${index})" style="position:absolute; top: 10px; right: 12px; background: white; color: #0052a3; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-weight: 900; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.18);" title="Agregar tela">＋</button>
        </div>
    </div>`,

    /**
     * Variaciones con encabezado mejorado
     */
    variacionesTableHeader: () => `<div style="padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">
        <div>📋 Variaciones</div>
        <div style="text-align: center;">Valor</div>
        <div>Observaciones</div>
        <div style="text-align: center;">Acción</div>
    </div>`,

    /**
     * Tabla de tallas con encabezado mejorado
     */
    tallasTableHeader: (index) => `<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0052a3 0%, #0ea5e9 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 700; display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div style="display: flex; gap: 1rem; flex: 1;"><div style="flex: 1.5;">Talla</div><div style="flex: 1;">Cantidad</div><div style="width: 100px; text-align: center;">Acción</div></div>
        <button type="button" onclick="mostrarModalAgregarTalla(${index})" style="background: white; color: #0b4f91; border: none; padding: 0.45rem 0.65rem; border-radius: 999px; cursor: pointer; font-size: 1rem; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,0,0,0.18); width: 36px; height: 36px;">
            <span style="display:inline-flex; align-items:center; justify-content:center; width: 18px; height: 18px; border-radius: 50%; background: rgba(14,165,233,0.18); color: #0b4f91; font-size: 1rem; line-height: 1;">+</span>
        </button>
    </div>`

};

// Exportar para uso en otros archivos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.templates;
}
