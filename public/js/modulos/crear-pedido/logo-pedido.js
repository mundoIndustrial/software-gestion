/**
 * MÓDULO: Gestión de Cotizaciones Tipo Logo (L)
 * 
 * Este módulo contiene toda la lógica para el renderizado y manejo
 * de cotizaciones tipo Logo (L).
 * 
 * Función principal:
 * - renderizarLogoPedido() - Renderiza la vista completa del logo
 * 
 * Nota: Este módulo usa variables globales:
 * - logoTecnicasSeleccionadas, logoSeccionesSeleccionadas, logoObservacionesGenerales
 * - logoFotosSeleccionadas
 * Estas son mantenidas en el archivo principal para compatibilidad.
 */

/**
 * FUNCIÓN PRINCIPAL: Renderizar cotización tipo Logo
 * Alias de renderizarCamposLogo para mantener compatibilidad
 * @param {Object} logoCotizacion - Datos del logo
 */
window.renderizarLogoPedido = function(logoCotizacion) {
    renderizarCamposLogo(logoCotizacion);
};

/**
 * FUNCIÓN: Renderizar campos y formulario del Logo
 * Esta es la función que estaba originalmente en el DOMContentLoaded
 * @param {Object} logoCotizacion - Datos del logo de la cotización
 */
function renderizarCamposLogo(logoCotizacion) {
    console.log('🎨 Renderizando campos LOGO únicamente');
    console.log('📦 Datos logo completos:', logoCotizacion);
    
    // Resetear arrays globales
    logoTecnicasSeleccionadas = [];
    logoSeccionesSeleccionadas = [];
    logoObservacionesGenerales = [];
    
    // Función helper para parsear datos JSON si es necesario
    function parseArrayData(data) {
        if (!data) return [];
        if (Array.isArray(data)) return data;
        if (typeof data === 'string') {
            try {
                return JSON.parse(data);
            } catch (e) {
                console.warn('⚠️ No se pudo parsear:', data);
                return [];
            }
        }
        return [];
    }
    
    // Parsear ubicaciones
    let ubicacionesArray = parseArrayData(logoCotizacion.ubicaciones);
    console.log('📍 Ubicaciones parseadas:', ubicacionesArray);
    
    // Cargar técnicas iniciales
    if (logoCotizacion.tecnicas && logoCotizacion.tecnicas.length > 0) {
        logoCotizacion.tecnicas.forEach(tecnica => {
            const tecnicaText = typeof tecnica === 'object' ? tecnica.nombre : tecnica;
            logoTecnicasSeleccionadas.push(tecnicaText);
        });
    }
    
    // Cargar ubicaciones iniciales
    if (ubicacionesArray && ubicacionesArray.length > 0) {
        ubicacionesArray.forEach(ubicacion => {
            if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
                logoSeccionesSeleccionadas.push({
                    ubicacion: ubicacion.ubicacion,
                    opciones: Array.isArray(ubicacion.opciones) ? ubicacion.opciones : [],
                    observaciones: ubicacion.observaciones || ''
                });
            }
        });
    }
    
    // Parsear observaciones generales
    let observacionesArray = parseArrayData(logoCotizacion.observaciones_generales);
    console.log('📝 Observaciones parseadas:', observacionesArray);
    
    if (observacionesArray && observacionesArray.length > 0) {
        observacionesArray.forEach(obs => {
            if (typeof obs === 'object') {
                logoObservacionesGenerales.push(obs.descripcion || obs.texto || obs.nombre || '');
            } else {
                logoObservacionesGenerales.push(obs);
            }
        });
    }
    
    // Cambiar el título del paso 3
    const paso3Titulo = document.getElementById('paso3_titulo_logo');
    if (paso3Titulo) {
        paso3Titulo.textContent = 'Información del Logo';
    }
    
    let html = '<div style="margin-top: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">';
    html += '<h2 style="margin: 0 0 1.5rem 0; font-size: 1.3rem; color: #1f2937; border-bottom: 3px solid #0066cc; padding-bottom: 0.75rem;">📋 Información del Logo</h2>';
    
    // ========== DESCRIPCIÓN (EDITABLE) ==========
    html += `<div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">DESCRIPCIÓN</label>
        <textarea id="logo_descripcion" name="logo_descripcion" 
                  style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 100px; color: #333;">${logoCotizacion.descripcion || ''}</textarea>
    </div>`;
    
    // ========== FOTOS (EDITABLES) ==========
    // Cargar fotos iniciales
    logoFotosSeleccionadas = [];
    if (logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
        logoCotizacion.fotos.forEach((foto) => {
            const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
            if (fotoUrl) {
                logoFotosSeleccionadas.push({
                    url: fotoUrl,
                    preview: fotoUrl,
                    existing: true,
                    id: foto.id
                });
            }
        });
    }
    
    html += `<div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">IMÁGENES (MÁXIMO 5)</label>
            <button type="button" onclick="abrirModalAgregarFotosLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        <div id="galeria-fotos-logo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;"></div>
    </div>`;
    
    // ========== TÉCNICAS (EDITABLES) ==========
    html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">Técnicas disponibles</label>
            <button type="button" class="btn-add" onclick="agregarTecnicaLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        
        <select id="selector_tecnicas_logo" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            <option value="">-- SELECCIONA UNA TÉCNICA --</option>
            <option value="BORDADO">BORDADO</option>
            <option value="DTF">DTF</option>
            <option value="ESTAMPADO">ESTAMPADO</option>
            <option value="SUBLIMADO">SUBLIMADO</option>
        </select>
        
        <div class="tecnicas-seleccionadas-logo" id="tecnicas_seleccionadas_logo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
    </div>`;
    
    // ========== OBSERVACIONES DE TÉCNICAS (EDITABLE) ==========
    html += `<div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Observaciones de Técnicas</label>
        <textarea id="logo_observaciones_tecnicas" name="logo_observaciones_tecnicas" 
                  style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 80px; color: #333;">${logoCotizacion.observaciones_tecnicas || ''}</textarea>
    </div>`;
    
    // ========== TALLAS A COTIZAR (TABLA EDITABLE) ==========
    // Parsear tallas de las ubicaciones
    let tallasArray = [];
    if (ubicacionesArray && ubicacionesArray.length > 0) {
        ubicacionesArray.forEach(ub => {
            if (ub.tallas && Array.isArray(ub.tallas)) {
                ub.tallas.forEach(talla => {
                    // Evitar duplicados
                    const yaBuscada = tallasArray.find(t => t.talla === talla.talla);
                    if (!yaBuscada) {
                        tallasArray.push(talla);
                    }
                });
            }
        });
    }
    
    html += `<div style="margin-bottom: 1.5rem;">
        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1f2937; font-size: 0.95rem;">🧵 TALLAS A COTIZAR</label>
        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 0.9rem;">
            <thead>
                <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Talla</th>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Cantidad</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; width: 80px;">Acción</th>
                </tr>
            </thead>
            <tbody id="logo-tallas-tbody">`;
    
    if (tallasArray.length > 0) {
        tallasArray.forEach((talla, idx) => {
            html += `<tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                    <input type="text" value="${talla.talla || ''}" data-talla-idx="${idx}" class="logo-talla-nombre" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                </td>
                <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                    <input type="number" value="${talla.cantidad || 0}" data-talla-idx="${idx}" class="logo-talla-cantidad" min="0" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <button type="button" onclick="eliminarTallaLogo(${idx})" style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">✕ Eliminar</button>
                </td>
            </tr>`;
        });
    } else {
        html += `<tr><td colspan="3" style="padding: 1rem; text-align: center; color: #999;">Sin tallas definidas</td></tr>`;
    }
    
    html += `</tbody>
        </table>
    </div>`;
    
    // ========== UBICACIÓN (TABLA EDITABLE) ==========
    html += `<div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">📍 UBICACIÓN</label>
            <button type="button" class="btn-add" onclick="agregarSeccionLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
        </div>
        
        <select id="seccion_prenda_logo" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            <option value="">-- SELECCIONA UNA OPCIÓN --</option>
            <option value="CAMISA">CAMISA</option>
            <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
            <option value="GORRAS">GORRAS</option>
        </select>
        
        <div id="errorSeccionPrendaLogo" style="display: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.5rem; background: #fee2e2; border-radius: 4px; margin-bottom: 10px;">
            ⚠️ Debes seleccionar una ubicación
        </div>
        
        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 0.9rem;">
            <thead>
                <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Sección</th>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Ubicaciones Seleccionadas</th>
                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Observaciones</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; width: 80px;">Acción</th>
                </tr>
            </thead>
            <tbody id="logo-ubicaciones-tbody">`;
    
    if (logoSeccionesSeleccionadas.length > 0) {
        logoSeccionesSeleccionadas.forEach((seccion, idx) => {
            const ubicacionesText = Array.isArray(seccion.opciones) ? seccion.opciones.join(', ') : '';
            html += `<tr style="border-bottom: 1px solid #e0e0e0;" data-ubicacion-idx="${idx}">
                <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                    <input type="text" value="${seccion.ubicacion}" class="logo-ubicacion-nombre" data-ubicacion-idx="${idx}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                </td>
                <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        ${seccion.opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItem(${idx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                            </span>
                        `).join('')}
                    </div>
                </td>
                <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                    <textarea class="logo-ubicacion-obs" data-ubicacion-idx="${idx}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem; min-height: 40px; resize: vertical; font-family: inherit;" placeholder="Observaciones...">${seccion.observaciones || ''}</textarea>
                </td>
                <td style="padding: 0.75rem 1rem; text-align: center;">
                    <button type="button" onclick="eliminarSeccionLogo(${idx})" style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">✕ Eliminar</button>
                </td>
            </tr>`;
        });
    } else {
        html += `<tr><td colspan="4" style="padding: 1rem; text-align: center; color: #999;">Sin ubicaciones definidas. Agrega una haciendo clic en el botón +</td></tr>`;
    }
    
    html += `</tbody>
        </table>
    </div>`;
    
    html += `</div>`;
    
    // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
    prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
    prendasContainer.innerHTML = html;
    
    // Renderizar datos cargados
    renderizarFotosLogo();
    renderizarTecnicasLogo();
    renderizarSeccionesLogo();
    
    // ====== AGREGAR EVENT LISTENERS PARA CAPTURAR CAMBIOS EN TABLA ======
    // Listeners para tallas
    setTimeout(() => {
        const tallasInputs = document.querySelectorAll('.logo-talla-nombre, .logo-talla-cantidad');
        tallasInputs.forEach(input => {
            input.addEventListener('change', function() {
                const idx = parseInt(this.dataset.tallaIdx);
                const fila = this.closest('tr');
                if (fila) {
                    const nombreInput = fila.querySelector('.logo-talla-nombre');
                    const cantidadInput = fila.querySelector('.logo-talla-cantidad');
                    if (nombreInput && cantidadInput && tallasArray[idx]) {
                        tallasArray[idx].talla = nombreInput.value;
                        tallasArray[idx].cantidad = cantidadInput.value;
                        console.log('✅ Talla actualizada:', tallasArray[idx]);
                    }
                }
            });
        });
        
        // Listeners para ubicaciones
        const ubicacionesInputs = document.querySelectorAll('.logo-ubicacion-nombre, .logo-ubicacion-obs');
        ubicacionesInputs.forEach(input => {
            input.addEventListener('change', function() {
                const idx = parseInt(this.dataset.ubicacionIdx);
                if (logoSeccionesSeleccionadas[idx]) {
                    if (this.classList.contains('logo-ubicacion-nombre')) {
                        logoSeccionesSeleccionadas[idx].ubicacion = this.value;
                    } else if (this.classList.contains('logo-ubicacion-obs')) {
                        logoSeccionesSeleccionadas[idx].observaciones = this.value;
                    }
                    console.log('✅ Ubicación actualizada:', logoSeccionesSeleccionadas[idx]);
                }
            });
        });
    }, 200);
}
