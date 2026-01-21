/**
 * PASO 3: COTIZACIÓN COMBINADA - GESTIÓN DE TÉCNICAS Y PRENDAS
 * Integración completa de técnicas, prendas y observaciones para paso 3
 */

// =========================================================
// 1. VARIABLES GLOBALES
// =========================================================

let tecnicasAgregadasPaso3 = [];
let tiposDisponiblesPaso3 = [];
let tecnicasSeleccionadasPaso3 = [];
let contadorFilasPrendaPaso3 = 0;

// =========================================================
// 2. FUNCIÓN AUXILIAR - OBTENER PRENDAS DEL PASO 2
// =========================================================

function obtenerPrendasDelPaso2() {
    const prendas = [];
    
    try {
        // Buscar prendas en el contenedor de PASO 2
        let productosCards = document.querySelectorAll('[data-step="2"] .producto-card');
        
        // Si no encuentra en data-step="2", buscar en #productosContainer
        if (productosCards.length === 0) {
            productosCards = document.querySelectorAll('#productosContainer .producto-card');
        }
        
        // Si sigue sin encontrar, buscar en TODO el documento
        if (productosCards.length === 0) {
            productosCards = document.querySelectorAll('.producto-card');
        }
        
        console.log(' Buscando prendas en PASO 2 - Encontradas:', productosCards.length);
        
        productosCards.forEach((card, idx) => {
            console.log(`🔸 Analizando prenda ${idx + 1}...`);
            
            // Obtener nombre de la prenda
            const inputNombre = card.querySelector('input[name*="nombre_producto"]');
            const nombre = inputNombre ? inputNombre.value.trim() : '';
            
            console.log(`  - Nombre: ${nombre || '(vacío)'}`);
            
            if (!nombre) {
                console.log('  - Saltando porque no tiene nombre');
                return; // Saltar si no hay nombre
            }
            
            // Obtener tallas
            let tallas = [];
            
            // MÉTODO 1: Input hidden con tallas (el que se llena cuando se agregan tallas)
            const inputTallasHidden = card.querySelector('input.tallas-hidden');
            if (inputTallasHidden) {
                const tallaValue = inputTallasHidden.value.trim();
                console.log(`  - Buscando en input.tallas-hidden: "${tallaValue}"`);
                if (tallaValue) {
                    try {
                        const parsed = JSON.parse(tallaValue);
                        if (Array.isArray(parsed)) {
                            tallas = parsed;
                        }
                    } catch (e) {
                        tallas = tallaValue.split(',').map(t => t.trim()).filter(t => t);
                    }
                }
            }
            
            // MÉTODO 2: Si el input estaba vacío, buscar en las tallas-agregadas (los chips visibles)
            if (tallas.length === 0) {
                const tallasAgregadas = card.querySelector('.tallas-agregadas');
                if (tallasAgregadas) {
                    // Buscar todos los elements que parecen ser tallas (excluir el input hidden)
                    const tallaElements = tallasAgregadas.querySelectorAll('[class*="talla"], [data-talla], .badge, .tag, .chip');
                    tallaElements.forEach(el => {
                        // Excluir inputs
                        if (el.tagName !== 'INPUT') {
                            const talla = el.textContent.trim();
                            if (talla && !tallas.includes(talla)) {
                                tallas.push(talla);
                            }
                        }
                    });
                    console.log(`  - Buscando en .tallas-agregadas: encontrados ${tallaElements.length} elementos, tallas: ${JSON.stringify(tallas)}`);
                }
            }
            
            // MÉTODO 3: Último recurso - buscar en cualquier elemento con clase talla
            if (tallas.length === 0) {
                const tallaButtons = card.querySelectorAll('.talla-btn, [data-talla], .talla-tag, .talla-chip, .badge');
                tallaButtons.forEach(btn => {
                    const talla = btn.textContent.trim() || btn.getAttribute('data-talla');
                    if (talla && !talla.includes('input') && !tallas.includes(talla)) {
                        tallas.push(talla);
                    }
                });
                console.log(`  - Buscando en botones/elementos: encontrados ${tallaButtons.length} elementos`);
            }
            
            console.log(`  - Tallas finales: ${JSON.stringify(tallas)}`);
            
            // Obtener género
            let genero = '';
            const selectGenero = card.querySelector('.talla-genero-select');
            if (selectGenero) {
                genero = selectGenero.value.trim();
            }
            console.log(`  - Selector .talla-genero-select encontrado: ${selectGenero ? 'Sí' : 'No'}, valor: "${genero}"`);
            
            // Obtener colores (pueden ser múltiples) - Buscar en las filas de telas
            let colores = [];
            
            // MÉTODO 1: Buscar en filas de tabla de telas con class color-input
            let colorInputs = card.querySelectorAll('input.color-input');
            console.log(`  - Inputs con class color-input encontrados: ${colorInputs.length}`);
            
            colorInputs.forEach((input) => {
                const color = input.value.trim();
                if (color && !colores.includes(color)) {
                    colores.push(color);
                    console.log(`    - Color encontrado: "${color}"`);
                }
            });
            
            // MÉTODO 2: Si no encontró en color-input, buscar en data-tela-index rows
            if (colores.length === 0) {
                const telaFilas = card.querySelectorAll('tr[data-tela-index], .fila-tela');
                console.log(`  - Filas de tela con data-tela-index encontradas: ${telaFilas.length}`);
                
                telaFilas.forEach((fila, telaIdx) => {
                    const colorInput = fila.querySelector('.color-input, input[placeholder*="Color"], input[placeholder*="color"]');
                    if (colorInput) {
                        const color = colorInput.value.trim();
                        if (color && !colores.includes(color)) {
                            colores.push(color);
                            console.log(`    - Color encontrado en fila: "${color}"`);
                        }
                    }
                });
            }
            
            // MÉTODO 3: Buscar en cualquier input que tenga name variantes[color]
            if (colores.length === 0) {
                const colorInputsVariantes = card.querySelectorAll('input[name*="[color]"]');
                console.log(`  - Inputs con name [color] encontrados: ${colorInputsVariantes.length}`);
                
                colorInputsVariantes.forEach((input) => {
                    const color = input.value.trim();
                    if (color && !colores.includes(color)) {
                        colores.push(color);
                        console.log(`    - Color encontrado en variante: "${color}"`);
                    }
                });
            }
            
            console.log(`  - Colores finales: ${JSON.stringify(colores)}`);
            
            // Obtener imágenes de la prenda
            let imagenes = [];
            const fotosPreview = card.querySelector('.fotos-preview');
            if (fotosPreview) {
                const fotosElements = fotosPreview.querySelectorAll('img, [data-foto] img');
                console.log(`  - Imágenes encontradas: ${fotosElements.length}`);
                fotosElements.forEach(img => {
                    if (img.src) {
                        imagenes.push(img.src);
                    }
                });
            }
            console.log(`  - Imágenes finales: ${imagenes.length}`);
            
            // ============================================
            // OBTENER VARIACIONES DE LA PRENDA (NUEVO)
            // ============================================
            let variaciones = {};
            
            // MANGA
            const aplicaMangaCheckbox = card.querySelector('input[name*="aplica_manga"]');
            const mangaInput = card.querySelector('input.manga-input');
            const mangaObsInput = card.querySelector('input[name*="obs_manga"]');
            
            if (aplicaMangaCheckbox && aplicaMangaCheckbox.checked) {
                const mangaTipo = mangaInput ? mangaInput.value.trim() : '';
                const mangaObs = mangaObsInput ? mangaObsInput.value.trim() : '';
                variaciones.manga = {
                    valor: mangaTipo,
                    observacion: mangaObs
                };
            }
            
            // BOLSILLOS
            const aplicaBolsillosCheckbox = card.querySelector('input[name*="aplica_bolsillos"]');
            const bolsillosObsInput = card.querySelector('input[name*="obs_bolsillos"]');
            
            if (aplicaBolsillosCheckbox && aplicaBolsillosCheckbox.checked) {
                const bolsillosObs = bolsillosObsInput ? bolsillosObsInput.value.trim() : '';
                variaciones.bolsillos = {
                    valor: 'Sí',  // Si aplica, significa "Sí"
                    observacion: bolsillosObs
                };
            }
            
            // BROCHE
            const aplicaBrocheCheckbox = card.querySelector('input[name*="aplica_broche"]');
            const brocheTipoSelect = card.querySelector('select[name*="tipo_broche_id"]');
            const brocheObsInput = card.querySelector('input[name*="obs_broche"]');
            
            if (aplicaBrocheCheckbox && aplicaBrocheCheckbox.checked) {
                const brocheTipo = brocheTipoSelect ? brocheTipoSelect.value.trim() : '';
                let brocheValor = '';
                
                // Traducir ID a valor
                if (brocheTipo === '1') {
                    brocheValor = 'Broche';
                } else if (brocheTipo === '2') {
                    brocheValor = 'Botón';
                } else {
                    brocheValor = brocheTipo;
                }
                
                const brocheObs = brocheObsInput ? brocheObsInput.value.trim() : '';
                variaciones.broche_boton = {
                    valor: brocheValor,
                    observacion: brocheObs
                };
            }
            
            // REFLECTIVO (opcional, similar a otros)
            const aplicaReflectivoCheckbox = card.querySelector('input[name*="aplica_reflectivo"]');
            const reflectivoObsInput = card.querySelector('input[name*="obs_reflectivo"]');
            
            if (aplicaReflectivoCheckbox && aplicaReflectivoCheckbox.checked) {
                const reflectivoObs = reflectivoObsInput ? reflectivoObsInput.value.trim() : '';
                variaciones.reflectivo = {
                    valor: 'Sí',
                    observacion: reflectivoObs
                };
            }
            
            prendas.push({
                nombre: nombre,
                tallas: tallas,
                genero: genero,
                colores: colores,
                imagenes: imagenes,
                variaciones: variaciones  // AGREGAR VARIACIONES
            });
        });
    } catch (error) {
        console.error('Error al obtener prendas del PASO 2:', error);
    }
    
    return prendas;
}

// =========================================================
// 3. CARGAR TIPOS DE TÉCNICAS AL INICIALIZAR
// =========================================================

async function cargarTiposDisponiblesPaso3() {
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/tipos-disponibles');
        const data = await response.json();
        
        if (data.success) {
            tiposDisponiblesPaso3 = data.data;
            renderizarSelectTecnicasPaso3();
        }
    } catch (error) {
        console.error(' Error cargando tipos paso3:', error);
    }
}

// =========================================================
// 3. RENDERIZAR CHECKBOXES DE TÉCNICAS
// =========================================================

function renderizarSelectTecnicasPaso3() {
    const container = document.getElementById('tecnicas-checkboxes-paso3');
    
    if (!container) {
        console.error(' Elemento tecnicas-checkboxes-paso3 no encontrado');
        return;
    }
    
    container.innerHTML = '';
    
    tiposDisponiblesPaso3.forEach(tipo => {
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: 4px; border: 1px solid #e5e7eb; transition: all 0.2s;';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = tipo.id;
        checkbox.className = 'tecnica-checkbox-paso3';
        checkbox.style.cssText = 'cursor: pointer; width: 18px; height: 18px;';
        
        const span = document.createElement('span');
        span.textContent = tipo.nombre;
        span.style.cssText = 'font-weight: 500; color: #333;';
        
        label.appendChild(checkbox);
        label.appendChild(span);
        
        // Hover effect
        label.addEventListener('mouseover', () => {
            label.style.backgroundColor = '#f3f4f6';
            if (tipo.color) {
                label.style.borderColor = tipo.color;
            }
        });
        label.addEventListener('mouseout', () => {
            label.style.backgroundColor = 'transparent';
            label.style.borderColor = '#e5e7eb';
        });
        
        container.appendChild(label);
    });
}

// =========================================================
// 4. ABRIR MODAL PARA AGREGAR TÉCNICA
// =========================================================

function abrirModalAgregarTecnicaPaso3() {
    const checkboxes = document.querySelectorAll('.tecnica-checkbox-paso3:checked');
    tecnicasSeleccionadasPaso3 = Array.from(checkboxes).map(cb => {
        const id = parseInt(cb.value);
        return tiposDisponiblesPaso3.find(t => t.id === id);
    });
    
    if (tecnicasSeleccionadasPaso3.length === 0) {
        abrirModalValidacionTecnicaPaso3();
        return;
    }
    
    // Flujo simple para 1 técnica
    if (tecnicasSeleccionadasPaso3.length === 1) {
        abrirModalSimpleTecnicaPaso3(tecnicasSeleccionadasPaso3[0]);
    } else {
        // Flujo combinado para múltiples técnicas - va directamente a "datos iguales"
        abrirModalDatosIgualesPaso3(tecnicasSeleccionadasPaso3);
    }
}

function abrirModalSimpleTecnicaPaso3(tipo) {
    window.tecnicaSeleccionadaPaso3 = tipo;
    window.modoTecnicasCombinadas = 'simple';
    
    const nombreElement = document.getElementById('tecnicaSeleccionadaNombrePaso3');
    if (nombreElement) {
        nombreElement.textContent = tipo.nombre;
    }
    
    const listaPrendas = document.getElementById('listaPrendasPaso3');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
    }
    
    agregarFilaPrendaPaso3();
    
    const noPrendasMsg = document.getElementById('noPrendasMsgPaso3');
    if (noPrendasMsg) {
        noPrendasMsg.style.display = 'none';
    }
    
    const modal = document.getElementById('modalAgregarTecnicaPaso3');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// =========================================================
// 5. MODAL TÉCNICAS COMBINADAS (Similar a logo-cotizacion)
// =========================================================

function abrirModalTecnicaCombinada(tecnicas) {
    const nombresT = tecnicas.map(t => t.nombre).join(' + ');
    
    Swal.fire({
        title: 'Técnicas Combinadas',
        html: `
            <div style="text-align: left; margin: 15px 0;">
                <p style="margin-bottom: 12px; font-weight: 600; color: #1e40af;">${nombresT}</p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas-p3" value="iguales" checked style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;">✨ Datos iguales</strong>
                            <small style="color: #666; font-size: 0.8rem;">Una misma prenda para todas</small>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas-p3" value="diferentes" style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;"> Datos diferentes</strong>
                            <small style="color: #666; font-size: 0.8rem;">Cada técnica con su prenda</small>
                        </div>
                    </label>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Siguiente',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e40af',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            const opcion = document.querySelector('input[name="opcion-tecnicas-p3"]:checked').value;
            
            if (opcion === 'iguales') {
                abrirModalDatosIgualesPaso3(tecnicas);
            } else if (opcion === 'diferentes') {
                iniciarFlujoDatosDiferentesPaso3(tecnicas);
            }
        }
    });
}

function abrirModalDatosIgualesPaso3(tecnicas) {
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'iguales';
    
    // Obtener prendas del PASO 2
    const prendasPaso2 = obtenerPrendasDelPaso2();
    
    // Crear opciones de dropdown
    let opcionesDropdown = '<option value="">-- Selecciona una prenda --</option>';
    prendasPaso2.forEach(prenda => {
        // Construir texto para mostrar: Nombre - Género - Colores
        let textoOpcion = prenda.nombre;
        
        if (prenda.genero) {
            textoOpcion += ' - ' + prenda.genero;
        }
        
        if (prenda.colores && prenda.colores.length > 0) {
            textoOpcion += ' - Color: ' + prenda.colores.join(' - ');
        }
        
        // Almacenar en data attributes: tallas, género, colores, imágenes y variaciones
        const dataTallas = JSON.stringify(prenda.tallas).replace(/"/g, '&quot;');
        const dataGenero = prenda.genero || '';
        const dataColores = JSON.stringify(prenda.colores).replace(/"/g, '&quot;');
        const dataImagenes = JSON.stringify(prenda.imagenes || []).replace(/"/g, '&quot;');
        const dataVariaciones = JSON.stringify(prenda.variaciones || {}).replace(/"/g, '&quot;');
        
        opcionesDropdown += `<option value="${prenda.nombre}" data-tallas="${dataTallas}" data-genero="${dataGenero}" data-colores="${dataColores}" data-imagenes="${dataImagenes}" data-variaciones="${dataVariaciones}">${textoOpcion}</option>`;
    });
    
    Swal.fire({
        title: 'Datos Iguales para Todas las Técnicas',
        width: '650px',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 80vh; overflow-y: auto;">
                
                <!-- PRENDA ÚNICA - DROPDOWN -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Prenda</h3>
                    <select id="dNombrePrendaP3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; cursor: pointer;">
                        ${opcionesDropdown}
                    </select>
                    <small style="color: #999; display: block; margin-top: 4px; font-size: 0.8rem;">Las prendas se cargan del PASO 2</small>
                </div>
                
                <!-- UBICACIONES POR TÉCNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Ubicaciones</h3>
                    <div id="dUbicacionesPorTecnicaP3" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- IMÁGENES POR TÉCNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Imágenes por Técnica</h3>
                    <div id="dImagenesPorTecnicaP3" style="display: grid; gap: 16px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- VARIACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Variaciones por Técnica</h3>
                    <div id="dVariacionesPorTecnicaP3" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- OBSERVACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones</h3>
                    <textarea id="dObservacionesP3" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                
                <!-- TALLAS GENÉRICAS -->
                <div id="dSeccionTallasGeneralP3" style="display: block;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Tallas y Cantidades</h3>
                    <div id="dTallaCantidadContainerP3" style="display: grid; gap: 8px; margin-bottom: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                    <button type="button" id="dBtnAgregarTallaP3" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%; font-size: 0.9rem;">+ Agregar talla</button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            const selectPrenda = document.getElementById('dNombrePrendaP3');
            const btnAgregarTalla = document.getElementById('dBtnAgregarTallaP3');
            const tallaCantidadContainer = document.getElementById('dTallaCantidadContainerP3');
            const ubicacionesPorTecnicaDiv = document.getElementById('dUbicacionesPorTecnicaP3');
            const imagenesPorTecnicaDiv = document.getElementById('dImagenesPorTecnicaP3');
            
            let contadorTallas = 0;
            const imagenesAgregadasPorTecnica = {}; // Almacena imágenes por técnica
            
            // Listener para cambio de prenda - carga las tallas, imágenes y variaciones automáticamente
            selectPrenda.addEventListener('change', (e) => {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const tallasJson = selectedOption.getAttribute('data-tallas');
                const imagenesJson = selectedOption.getAttribute('data-imagenes');
                const nombrePrendaBase = selectPrenda.value.trim().toUpperCase();
                
                // Construir nombre completo IGUAL que en preConfirm
                const genero = selectedOption.getAttribute('data-genero') || '';
                const coloresJson = selectedOption.getAttribute('data-colores') || '[]';
                
                let colores = [];
                try {
                    colores = JSON.parse(coloresJson);
                } catch (e) {
                    colores = [];
                }
                
                let nombrePrendaCompleto = nombrePrendaBase;
                if (genero) {
                    nombrePrendaCompleto += ' - ' + genero;
                }
                if (colores && colores.length > 0) {
                    nombrePrendaCompleto += ' - Color: ' + colores.join(' - ');
                }
                
                // Limpiar tallas actuales
                tallaCantidadContainer.innerHTML = '';
                
                if (tallasJson) {
                    try {
                        const tallas = JSON.parse(tallasJson);
                        if (Array.isArray(tallas) && tallas.length > 0) {
                            // Agregar una fila por cada talla de la prenda seleccionada
                            tallas.forEach(talla => {
                                const idTalla = 'talla-p3-' + (contadorTallas++);
                                const fila = document.createElement('div');
                                fila.setAttribute('data-talla-id', idTalla);
                                fila.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center;';
                                fila.innerHTML = `
                                    <input type="text" class="dTallaInput" placeholder="Talla" value="${talla}" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;" readonly>
                                    <input type="number" class="dCantidadInput" placeholder="Cantidad" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                                    <button type="button" class="dBtnEliminarTalla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">✕</button>
                                `;
                                
                                const btnEliminar = fila.querySelector('.dBtnEliminarTalla');
                                btnEliminar.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    if (tallaCantidadContainer.children.length > 0) {
                                        fila.remove();
                                    }
                                });
                                
                                tallaCantidadContainer.appendChild(fila);
                            });
                        }
                    } catch (err) {
                        console.error('Error al parsear tallas:', err);
                    }
                }
                
                // Cargar imágenes de la prenda seleccionada en todos los dropzones
                if (imagenesJson) {
                    try {
                        const imagenes = JSON.parse(imagenesJson);
                        if (Array.isArray(imagenes) && imagenes.length > 0) {
                            tecnicas.forEach((tecnica, idx) => {
                                const previewContainer = document.querySelector(`.dImagenesPreview-p3-${idx}`);
                                if (previewContainer) {
                                    // Limpiar previews anteriores
                                    previewContainer.innerHTML = '';
                                    imagenesAgregadasPorTecnica[idx] = [];
                                    
                                    // Cargar las imágenes
                                    imagenes.slice(0, 3).forEach((imagenSrc, imgIdx) => {
                                        const divPreview = document.createElement('div');
                                        divPreview.style.cssText = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer; border: 1px solid #ddd;';
                                        divPreview.innerHTML = `
                                            <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                                            <span style="position: absolute; top: 2px; right: 2px; background: #0066cc; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">${imgIdx + 1}</span>
                                            <button type="button" onclick="event.stopPropagation(); this.closest('[style*=\"position: relative\"]').remove();" style="position: absolute; top: 2px; left: 2px; background: #f44336; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
                                        `;
                                        divPreview.addEventListener('mouseenter', function() {
                                            this.querySelector('button').style.opacity = '1';
                                        });
                                        divPreview.addEventListener('mouseleave', function() {
                                            this.querySelector('button').style.opacity = '0';
                                        });
                                        
                                        previewContainer.appendChild(divPreview);
                                        // Almacenar como una "imagen" ficticia para contar
                                        imagenesAgregadasPorTecnica[idx].push({type: 'image'});
                                    });
                                }
                            });
                        }
                    } catch (err) {
                        console.error('Error al parsear imágenes:', err);
                    }
                }
                
                // CARGAR VARIACIONES DE PRENDA DESDE PASO 2
                const variacionesJson = selectedOption.getAttribute('data-variaciones');
                let variacionesPaso2 = {};
                
                try {
                    if (variacionesJson) {
                        variacionesPaso2 = JSON.parse(variacionesJson);
                    }
                } catch (err) {
                    console.error('Error al parsear variaciones:', err);
                    variacionesPaso2 = {};
                }
                
                // Cargar variaciones en la tabla única (no por técnica)
                const swalPopup = Swal.getPopup();
                const mangaValor = swalPopup.querySelector('.dVariacionManga-valor-p3-unique');
                const mangaObs = swalPopup.querySelector('.dVariacionManga-obs-p3-unique');
                const bolsillosValor = swalPopup.querySelector('.dVariacionBolsillos-valor-p3-unique');
                const bolsillosObs = swalPopup.querySelector('.dVariacionBolsillos-obs-p3-unique');
                const brocheValor = swalPopup.querySelector('.dVariacionBroche-valor-p3-unique');
                const brocheObs = swalPopup.querySelector('.dVariacionBroche-obs-p3-unique');
                
                if (variacionesPaso2 && Object.keys(variacionesPaso2).length > 0) {
                    if (variacionesPaso2.manga) {
                        if (mangaValor) mangaValor.value = variacionesPaso2.manga.valor || '';
                        if (mangaObs) mangaObs.value = variacionesPaso2.manga.observacion || '';
                    } else {
                        if (mangaValor) mangaValor.value = '';
                        if (mangaObs) mangaObs.value = '';
                    }
                    
                    if (variacionesPaso2.bolsillos) {
                        if (bolsillosValor) bolsillosValor.value = variacionesPaso2.bolsillos.valor || '';
                        if (bolsillosObs) bolsillosObs.value = variacionesPaso2.bolsillos.observacion || '';
                    } else {
                        if (bolsillosValor) bolsillosValor.value = '';
                        if (bolsillosObs) bolsillosObs.value = '';
                    }
                    
                    if (variacionesPaso2.broche_boton) {
                        if (brocheValor) brocheValor.value = variacionesPaso2.broche_boton.valor || '';
                        if (brocheObs) brocheObs.value = variacionesPaso2.broche_boton.observacion || '';
                    } else {
                        if (brocheValor) brocheValor.value = '';
                        if (brocheObs) brocheObs.value = '';
                    }
                } else {
                    // Si no hay variaciones, limpiar campos
                    if (mangaValor) mangaValor.value = '';
                    if (mangaObs) mangaObs.value = '';
                    if (bolsillosValor) bolsillosValor.value = '';
                    if (bolsillosObs) bolsillosObs.value = '';
                    if (brocheValor) brocheValor.value = '';
                    if (brocheObs) brocheObs.value = '';
                }
            });
            
            // Crear inputs de ubicación por técnica, dropzones de imagen y variaciones
            const variacionesPorTecnica = {};
            
            tecnicas.forEach((tecnica, idx) => {
                // UBICACIÓN
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'display: flex; gap: 8px; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 4px;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; min-width: 100px; font-size: 0.9rem; color: #333; flex-shrink: 0;';
                labelUbicacion.textContent = tecnica.nombre + ':';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacion-p3-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputUbicacion);
                ubicacionesPorTecnicaDiv.appendChild(divUbicacion);
                
                // IMÁGENES
                imagenesAgregadasPorTecnica[idx] = [];
                
                const divImagen = document.createElement('div');
                divImagen.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                divImagen.innerHTML = `
                    <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                        Imágenes - ${tecnica.nombre} (Máximo 3)
                    </label>
                    <div class="dImagenesDropzone-p3-${idx}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                        <div style="margin-bottom: 6px; font-size: 1.3rem;">📁</div>
                        <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                        <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                        <input type="file" class="dImagenInput-p3-${idx}" accept="image/*" multiple style="display: none;" />
                    </div>
                    <div class="dImagenesPreview-p3-${idx}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        <!-- Previsualizaciones aquí -->
                    </div>
                `;
                
                imagenesPorTecnicaDiv.appendChild(divImagen);
                
                // Setup Drag and Drop
                const dropzone = divImagen.querySelector(`.dImagenesDropzone-p3-${idx}`);
                const input = divImagen.querySelector(`.dImagenInput-p3-${idx}`);
                const previewContainer = divImagen.querySelector(`.dImagenesPreview-p3-${idx}`);
                
                function actualizarPrevisualizaciones() {
                    previewContainer.innerHTML = '';
                    
                    imagenesAgregadasPorTecnica[idx].forEach((archivo, imgIdx) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const preview = document.createElement('div');
                            preview.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                            
                            const btnEliminar = document.createElement('button');
                            btnEliminar.type = 'button';
                            btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
                            btnEliminar.textContent = '×';
                            btnEliminar.addEventListener('click', (e) => {
                                e.preventDefault();
                                imagenesAgregadasPorTecnica[idx].splice(imgIdx, 1);
                                actualizarPrevisualizaciones();
                            });
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                            
                            preview.appendChild(img);
                            preview.appendChild(btnEliminar);
                            previewContainer.appendChild(preview);
                        };
                        reader.readAsDataURL(archivo);
                    });
                }
                
                dropzone.addEventListener('click', () => input.click());
                
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.style.background = '#e8f1ff';
                    dropzone.style.borderColor = '#1e40af';
                });
                
                dropzone.addEventListener('dragleave', () => {
                    dropzone.style.background = '#fafafa';
                    dropzone.style.borderColor = '#ddd';
                });
                
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.style.background = '#fafafa';
                    dropzone.style.borderColor = '#ddd';
                    agregarImagenesDrop(Array.from(e.dataTransfer.files));
                });
                
                input.addEventListener('change', (e) => {
                    agregarImagenesDrop(Array.from(e.target.files));
                });
                
                function agregarImagenesDrop(archivos) {
                    const imagenes = archivos.filter(f => f.type.startsWith('image/'));
                    
                    if (imagenes.length === 0) return;
                    
                    for (const archivo of imagenes) {
                        if (imagenesAgregadasPorTecnica[idx].length >= 3) break;
                        imagenesAgregadasPorTecnica[idx].push(archivo);
                    }
                    
                    actualizarPrevisualizaciones();
                }
            });
            
            // CREAR TABLA DE VARIACIONES UNA SOLA VEZ (NO POR TÉCNICA)
            const divVariaciones = document.createElement('div');
            divVariaciones.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
            divVariaciones.innerHTML = `
                <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                    Variaciones de la Prenda
                </label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionManga-valor-p3-unique" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="dVariacionManga-obs-p3-unique" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionBolsillos-valor-p3-unique" placeholder="Sí, No..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="dVariacionBolsillos-obs-p3-unique" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionBroche-valor-p3-unique" placeholder="Automático, Metal..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="dVariacionBroche-obs-p3-unique" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                    </tbody>
                </table>
            `;
            
            const variacionesPorTecnicaDiv = document.getElementById('dVariacionesPorTecnicaP3');
            if (variacionesPorTecnicaDiv) {
                variacionesPorTecnicaDiv.appendChild(divVariaciones);
            }
            
            // Agregar talla inicial (vacía)
            function agregarFilaTallaPaso3() {
                const idTalla = 'talla-p3-' + (contadorTallas++);
                const fila = document.createElement('div');
                fila.setAttribute('data-talla-id', idTalla);
                fila.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center;';
                fila.innerHTML = `
                    <input type="text" class="dTallaInput" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <input type="number" class="dCantidadInput" placeholder="Cantidad" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <button type="button" class="dBtnEliminarTalla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">✕</button>
                `;
                
                const btnEliminar = fila.querySelector('.dBtnEliminarTalla');
                btnEliminar.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (tallaCantidadContainer.children.length > 1) {
                        fila.remove();
                    }
                });
                
                tallaCantidadContainer.appendChild(fila);
            }
            
            if (btnAgregarTalla) {
                btnAgregarTalla.addEventListener('click', (e) => {
                    e.preventDefault();
                    agregarFilaTallaPaso3();
                });
            }
            
            // Guardar referencia global para usar en preConfirm
            window.imagenesAgregadasPorTecnicaP3 = imagenesAgregadasPorTecnica;
        },
        preConfirm: () => {
            const selectPrenda = document.getElementById('dNombrePrendaP3');
            const selectedOption = selectPrenda.options[selectPrenda.selectedIndex];
            const nombrePrendaBase = selectPrenda.value.trim().toUpperCase();
            
            if (!nombrePrendaBase) {
                Swal.showValidationMessage('Selecciona una prenda');
                return false;
            }
            
            // Obtener género y colores del data attribute
            const genero = selectedOption.getAttribute('data-genero') || '';
            const coloresJson = selectedOption.getAttribute('data-colores') || '[]';
            
            let colores = [];
            try {
                colores = JSON.parse(coloresJson);
            } catch (e) {
                colores = [];
            }
            
            // Construir nombre completo: Nombre - Género - Colores
            let nombrePrendaCompleto = nombrePrendaBase;
            
            if (genero) {
                nombrePrendaCompleto += ' - ' + genero;
            }
            
            if (colores && colores.length > 0) {
                nombrePrendaCompleto += ' - Color: ' + colores.join(' - ');
            }
            
            // Validar ubicaciones
            const ubicacionesPorTecnica = {};
            let valido = true;
            
            window.tecnicasCombinadas.forEach((tecnica, idx) => {
                const ubicacion = document.querySelector('.dUbicacion-p3-' + idx)?.value.trim() || '';
                if (!ubicacion) {
                    Swal.showValidationMessage(`Agrega ubicación para ${tecnica.nombre}`);
                    valido = false;
                    return;
                }
                ubicacionesPorTecnica[idx] = [ubicacion];
            });
            
            if (!valido) return false;
            
            // Validar tallas
            const tallasFilas = document.querySelectorAll('[data-talla-id]');
            let tallas = [];
            tallasFilas.forEach(fila => {
                const talla = fila.querySelector('.dTallaInput').value.trim();
                const cantidad = fila.querySelector('.dCantidadInput').value;
                if (talla && cantidad) {
                    tallas.push({ talla, cantidad: parseInt(cantidad) });
                }
            });
            
            if (tallas.length === 0) {
                Swal.showValidationMessage('Agrega al menos una talla y cantidad');
                return false;
            }
            
            // Capturar variaciones (UNA SOLA VEZ para todas las técnicas)
            const variacionesUnicas = {
                manga: {
                    valor: document.querySelector('.dVariacionManga-valor-p3-unique')?.value.trim() || '',
                    observacion: document.querySelector('.dVariacionManga-obs-p3-unique')?.value.trim() || ''
                },
                bolsillos: {
                    valor: document.querySelector('.dVariacionBolsillos-valor-p3-unique')?.value.trim() || '',
                    observacion: document.querySelector('.dVariacionBolsillos-obs-p3-unique')?.value.trim() || ''
                },
                broche_boton: {
                    valor: document.querySelector('.dVariacionBroche-valor-p3-unique')?.value.trim() || '',
                    observacion: document.querySelector('.dVariacionBroche-obs-p3-unique')?.value.trim() || ''
                }
            };
            
            // Replicar las mismas variaciones para todas las técnicas
            const variaciones_prenda = {};
            window.tecnicasCombinadas.forEach((tecnica) => {
                variaciones_prenda[tecnica.nombre] = variacionesUnicas;
            });
            
            return {
                nombre_prenda: nombrePrendaCompleto,
                observaciones: document.getElementById('dObservacionesP3').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
                tallas: tallas,
                variaciones_prenda: variaciones_prenda,
                imagenesAgregadas: window.imagenesAgregadasPorTecnicaP3
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            guardarTecnicaCombinada(result.value, window.tecnicasCombinadas);
        }
    });
}

function iniciarFlujoDatosDiferentesPaso3(tecnicas) {
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'diferentes';
    window.indiceActualTecnica = 0;
    
    mostrarFormularioTecnicaDiferentePaso3(0);
}

function mostrarFormularioTecnicaDiferentePaso3(indice) {
    if (indice >= window.tecnicasCombinadas.length) {
        renderizarTecnicasAgregadasPaso3();
        return;
    }
    
    const tecnica = window.tecnicasCombinadas[indice];
    
    Swal.fire({
        title: `Datos para ${tecnica.nombre}`,
        width: '550px',
        html: `
            <div style="text-align: left; padding: 20px; max-height: 70vh; overflow-y: auto;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Prenda:</label>
                    <input type="text" id="dNombrePrendaDifP3" placeholder="POLO, CAMISA, PANTALÓN..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-transform: uppercase;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Ubicación:</label>
                    <input type="text" id="dUbicacionDifP3" placeholder="Ej: Pecho, Espalda..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Observaciones:</label>
                    <textarea id="dObservacionesDifP3" placeholder="..." rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"></textarea>
                </div>
                <div style="margin-bottom: 0;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Variaciones:</label>
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" id="dVariacionMangaDifP3Valor" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" id="dVariacionMangaDifP3Obs" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" id="dVariacionBolsillosDifP3Valor" placeholder="Sí, No..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" id="dVariacionBolsillosDifP3Obs" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" id="dVariacionBrocheDifP3Valor" placeholder="Automático, Metal..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" id="dVariacionBrocheDifP3Obs" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Siguiente',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e40af',
        preConfirm: () => {
            const nombre = document.getElementById('dNombrePrendaDifP3').value.trim();
            const ubicacion = document.getElementById('dUbicacionDifP3').value.trim();
            
            if (!nombre || !ubicacion) {
                Swal.showValidationMessage('Completa los campos requeridos');
                return false;
            }
            
            return {
                nombre_prenda: nombre.toUpperCase(),
                ubicacion: ubicacion,
                observaciones: document.getElementById('dObservacionesDifP3').value.trim(),
                variaciones_prenda: {
                    manga: {
                        valor: document.getElementById('dVariacionMangaDifP3Valor').value.trim() || '',
                        observacion: document.getElementById('dVariacionMangaDifP3Obs').value.trim() || ''
                    },
                    bolsillos: {
                        valor: document.getElementById('dVariacionBolsillosDifP3Valor').value.trim() || '',
                        observacion: document.getElementById('dVariacionBolsillosDifP3Obs').value.trim() || ''
                    },
                    broche_boton: {
                        valor: document.getElementById('dVariacionBrocheDifP3Valor').value.trim() || '',
                        observacion: document.getElementById('dVariacionBrocheDifP3Obs').value.trim() || ''
                    }
                },
                tecnica_idx: indice
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Guardar datos de la técnica diferente
            if (!window.datosDiferentesPaso3) {
                window.datosDiferentesPaso3 = [];
            }
            window.datosDiferentesPaso3.push(result.value);
            
            // Guardar y pasar al siguiente
            window.indiceActualTecnica++;
            mostrarFormularioTecnicaDiferentePaso3(window.indiceActualTecnica);
        }
    });
}

function guardarTecnicaCombinada(datosForm, tecnicas) {
    // Guardar una prenda por técnica con sus imágenes y variaciones
    tecnicas.forEach((tecnica, idx) => {
        const nuevaTecnica = {
            tipo: tecnica.nombre,
            prendas: [{
                nombre: datosForm.nombre_prenda,
                ubicaciones: datosForm.ubicacionesPorTecnica[idx] || [],
                tallasCantidad: datosForm.tallas.map(t => ({ talla: t.talla, cantidad: t.cantidad })),
                observaciones: datosForm.observaciones,
                variaciones_prenda: datosForm.variaciones_prenda || {},
                imagenes_files: datosForm.imagenesAgregadas[idx] || []
            }],
            observacionesGenerales: ''
        };
        
        if (!window.tecnicasAgregadasPaso3) {
            window.tecnicasAgregadasPaso3 = [];
        }
        
        const tecnicaExistente = window.tecnicasAgregadasPaso3.find(t => t.tipo === tecnica.nombre);
        if (tecnicaExistente) {
            tecnicaExistente.prendas = nuevaTecnica.prendas;
        } else {
            window.tecnicasAgregadasPaso3.push(nuevaTecnica);
        }
    });
    
    console.log(' Técnicas combinadas guardadas en PASO 3:', window.tecnicasAgregadasPaso3);
    
    renderizarTecnicasAgregadasPaso3();
    cerrarModalAgregarTecnicaPaso3();
}

// =========================================================
// 6. RENDERIZAR TÉCNICAS AGREGADAS COMO CARDS
// =========================================================

function renderizarTecnicasAgregadasPaso3() {
    const container = document.getElementById('tecnicas_agregadas_paso3');
    const sinTecnicas = document.getElementById('sin_tecnicas_paso3');
    
    if (!container) {
        console.error(' Elemento tecnicas_agregadas_paso3 no encontrado');
        return;
    }
    
    container.innerHTML = '';
    
    if (!window.tecnicasAgregadasPaso3 || window.tecnicasAgregadasPaso3.length === 0) {
        if (sinTecnicas) sinTecnicas.style.display = 'block';
        return;
    }
    
    if (sinTecnicas) sinTecnicas.style.display = 'none';
    
    // Array para procesar imágenes de forma asíncrona
    const imagenesParaCargar = [];
    
    // Agrupar por nombre de prenda
    const prendasMap = {};
    
    window.tecnicasAgregadasPaso3.forEach((tecnicaData, tecnicaIndex) => {
        if (!tecnicaData.prendas) return;
        
        tecnicaData.prendas.forEach(prenda => {
            const nombrePrenda = prenda.nombre || 'SIN NOMBRE';
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: prenda.observaciones,
                    tallasCantidad: prenda.tallasCantidad || [],
                    tecnicas: [],
                    imagenes: [],
                    variaciones_prenda: prenda.variaciones_prenda || {}
                };
            }
            
            prendasMap[nombrePrenda].tecnicas.push({
                tipo: tecnicaData.tipo,
                tecnicaIndex: tecnicaIndex
            });
            
            // Agregar ubicaciones
            if (prenda.ubicaciones) {
                if (!prendasMap[nombrePrenda].ubicaciones) {
                    prendasMap[nombrePrenda].ubicaciones = {};
                }
                prendasMap[nombrePrenda].ubicaciones[tecnicaData.tipo] = prenda.ubicaciones;
            }
            
            // Procesar imágenes
            if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                prenda.imagenes_files.forEach(archivo => {
                    imagenesParaCargar.push({
                        archivo: archivo,
                        nombrePrenda: nombrePrenda,
                        tecnica: tecnicaData.tipo
                    });
                });
            }
        });
    });
    
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; margin-bottom: 20px; max-width: 1400px;';
    
    // Renderizar tarjetas
    Object.entries(prendasMap).forEach(([nombrePrenda, datosPrenda]) => {
        const tarjeta = document.createElement('div');
        tarjeta.setAttribute('data-prenda-nombre', nombrePrenda);
        tarjeta.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        `;
        tarjeta.onmouseover = function() {
            this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.15)';
            this.style.transform = 'translateY(-2px)';
        };
        tarjeta.onmouseout = function() {
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            this.style.transform = 'translateY(0)';
        };
        
        // HEADER CON TÉCNICAS Y BOTONES
        let headerHTML = '<div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1rem; border-bottom: 1px solid #ddd;">';
        headerHTML += '<div style="display: flex; justify-content: space-between; align-items: flex-start;">';
        
        // Sección de técnicas
        headerHTML += '<div style="flex: 1;">';
        headerHTML += '<h4 style="margin: 0 0 0.5rem 0; font-size: 0.95rem;">Técnica(s)</h4>';
        headerHTML += '<div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">';
        
        datosPrenda.tecnicas.forEach(t => {
            const colorsTec = {
                'BORDADO': '#ec4899',
                'DTF': '#8b5cf6',
                'ESTAMPADO': '#f97316',
                'SUBLIMADO': '#06b6d4'
            };
            const color = colorsTec[t.tipo] || '#3b82f6';
            
            headerHTML += `
                <span style="
                    background: ${color};
                    color: white;
                    padding: 0.4rem 0.8rem;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 0.85rem;
                ">
                    ${t.tipo}
                </span>
            `;
        });
        
        headerHTML += '</div></div>';
        
        // Botones de acción
        headerHTML += '<div style="display: flex; gap: 0.6rem;">';
        headerHTML += `
            <button class="btn-editar-prenda" onclick="abrirModalEditarTecnicaPaso3('${nombrePrenda}')" style="
                background: rgba(255,255,255,0.2);
                color: white;
                border: 1px solid rgba(255,255,255,0.3);
                padding: 0.5rem 0.8rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 0.4rem;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                ✏️
            </button>
            <button class="btn-eliminar-prenda" onclick="eliminarTecnicaPaso3('${nombrePrenda}')" style="
                background: rgba(255,255,255,0.2);
                color: white;
                border: 1px solid rgba(255,255,255,0.3);
                padding: 0.5rem 0.8rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 0.85rem;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 0.4rem;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                🗑️
            </button>
        `;
        headerHTML += '</div>';
        
        headerHTML += '</div></div>';
        
        // CUERPO
        let bodyHTML = '<div style="padding: 1rem;">';
        
        // Nombre de prenda
        bodyHTML += `
            <div style="margin-bottom: 1rem;">
                <h5 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                    ${nombrePrenda}
                </h5>
            </div>
        `;
        
        // SECCIÓN DE IMÁGENES
        const tieneImagenes = datosPrenda.imagenes.length > 0;
        bodyHTML += `
            <div class="imagenes-section" style="margin-bottom: 1rem; ${!tieneImagenes ? 'display: none;' : ''}">
                <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 0.6rem;">
                     Imágenes:
                </span>
                <div class="imagenes-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.4rem;">
                    ${datosPrenda.imagenes.map((img, imgIdx) => `
                        <div style="position: relative; border-radius: 3px; overflow: hidden; border: 1px solid #1e40af; cursor: pointer;" ondblclick="abrirModalImagenPrendaConIndice('${img.data}', ${imgIdx})">
                            <img src="${img.data}" style="width: 100%; aspect-ratio: 1; object-fit: cover; min-height: 60px;" alt="Imagen prenda">
                            <div style="
                                position: absolute;
                                bottom: 0;
                                left: 0;
                                right: 0;
                                background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
                                padding: 0.4rem;
                            ">
                                <span style="
                                    color: white;
                                    font-size: 0.7rem;
                                    font-weight: 600;
                                    background: #1e40af;
                                    padding: 0.2rem 0.4rem;
                                    border-radius: 2px;
                                    display: inline-block;
                                ">
                                    ${img.tecnica}
                                </span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        // SECCIÓN DE UBICACIONES
        if (datosPrenda.ubicaciones && Object.keys(datosPrenda.ubicaciones).length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 0.6rem;">
                         Ubicaciones:
                    </span>
            `;
            
            Object.entries(datosPrenda.ubicaciones).forEach(([nombreTec, ubicaciones]) => {
                bodyHTML += `
                    <div style="padding-left: 1rem; border-left: 4px solid #3b82f6;">
                        ${ubicaciones.map(ub => `
                            <div style="
                                font-size: 0.9rem;
                                color: #1e293b;
                                margin-bottom: 0.3rem;
                            ">
                                • ${nombreTec} - ${ub}
                            </div>
                        `).join('')}
                    </div>
                `;
            });
            
            bodyHTML += '</div>';
        }
        
        // TABLA DE VARIACIONES
        if (datosPrenda.variaciones_prenda && Object.keys(datosPrenda.variaciones_prenda).length > 0) {
            // Obtener las variaciones de la PRIMERA técnica (son iguales para todas)
            const variacionesUnicas = Object.values(datosPrenda.variaciones_prenda)[0] || {};
            
            bodyHTML += `
                <div style="margin-bottom: 1rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 0.6rem;">
                         Variaciones:
                    </span>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 0.6rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e2e8f0; color: #1e293b;">Tipo</th>
                            <th style="padding: 0.6rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e2e8f0; color: #1e293b;">Valor</th>
                            <th style="padding: 0.6rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e2e8f0; color: #1e293b;">Observación</th>
                        </tr>
            `;
            
            // Mostrar manga, bolsillos y broche_boton UNA SOLA VEZ
            ['manga', 'bolsillos', 'broche_boton'].forEach((tipo) => {
                const variacion = variacionesUnicas[tipo] || { valor: '-', observacion: '-' };
                const valor = typeof variacion === 'object' ? (variacion.valor || '-') : (variacion || '-');
                const obs = typeof variacion === 'object' ? (variacion.observacion || '-') : '-';
                
                bodyHTML += `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 0.6rem; color: #1e293b; font-weight: 500; font-size: 0.8rem;">${tipo === 'manga' ? '🧥 Manga' : tipo === 'bolsillos' ? '🔖 Bolsillos' : '🔘 Broche/Botón'}</td>
                        <td style="padding: 0.6rem; color: #475569;">${valor}</td>
                        <td style="padding: 0.6rem; color: #64748b; font-size: 0.8rem;">${obs}</td>
                    </tr>
                `;
            });
            
            bodyHTML += `
                    </table>
                </div>
            `;
        }
        
        // TALLAS Y CANTIDADES
        if (datosPrenda.tallasCantidad && datosPrenda.tallasCantidad.length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 0.6rem;">
                         Tallas:
                    </span>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
            `;
            
            datosPrenda.tallasCantidad.forEach(t => {
                bodyHTML += `
                    <span style="background: #e8f0ff; color: #1e40af; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">
                        ${t.talla} (${t.cantidad})
                    </span>
                `;
            });
            
            bodyHTML += '</div></div>';
        }
        
        // OBSERVACIONES
        if (datosPrenda.observaciones) {
            bodyHTML += `
                <div style="background: #fef3c7; border-left: 3px solid #f59e0b; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem;">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #92400e;"> Observaciones:</span>
                    <p style="margin: 0.4rem 0 0 0; font-size: 0.8rem; color: #78350f;">
                        ${datosPrenda.observaciones}
                    </p>
                </div>
            `;
        }
        
        bodyHTML += '</div>';
        
        tarjeta.innerHTML = headerHTML + bodyHTML;
        contenedor.appendChild(tarjeta);
    });
    
    container.appendChild(contenedor);
    
    // Procesar imágenes de forma asíncrona
    imagenesParaCargar.forEach(imgData => {
        const tarjeta = document.querySelector(`[data-prenda-nombre="${imgData.nombrePrenda}"]`);
        if (tarjeta) {
            const imagenesMaps = prendasMap[imgData.nombrePrenda].imagenes;
            
            // Verificar si es una URL (string) o un File object
            if (typeof imgData.archivo === 'string') {
                // Es una URL del PASO 2
                imagenesMaps.push({
                    data: imgData.archivo,
                    tecnica: imgData.tecnica
                });
                
                // Actualizar el grid inmediatamente
                const imgSection = tarjeta.querySelector('.imagenes-section');
                if (imgSection) {
                    imgSection.style.display = 'block';
                    const grid = imgSection.querySelector('.imagenes-grid');
                    if (grid) {
                        grid.innerHTML = imagenesMaps.map((img, idx) => `
                            <div style="position: relative; border-radius: 3px; overflow: hidden; border: 1px solid #1e40af;">
                                <img src="${img.data}" style="width: 100%; aspect-ratio: 1; object-fit: cover; min-height: 60px;" alt="Imagen prenda">
                                <div style="
                                    position: absolute;
                                    bottom: 0;
                                    left: 0;
                                    right: 0;
                                    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
                                    padding: 0.4rem;
                                ">
                                    <span style="
                                        color: white;
                                        font-size: 0.7rem;
                                        font-weight: 600;
                                        background: #1e40af;
                                        padding: 0.2rem 0.4rem;
                                        border-radius: 2px;
                                        display: inline-block;
                                    ">
                                        ${img.tecnica}
                                    </span>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } else {
                // Es un File object (imagen nueva agregada)
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagenesMaps.push({
                        data: e.target.result,
                        tecnica: imgData.tecnica
                    });
                    
                    const imgSection = tarjeta.querySelector('.imagenes-section');
                    if (imgSection) {
                        imgSection.style.display = 'block';
                        const grid = imgSection.querySelector('.imagenes-grid');
                        if (grid) {
                            grid.innerHTML = imagenesMaps.map((img, idx) => `
                                <div style="position: relative; border-radius: 3px; overflow: hidden; border: 1px solid #1e40af; cursor: pointer;" ondblclick="abrirModalImagenPrendaConIndice('${img.data}', ${idx})">
                                    <img src="${img.data}" style="width: 100%; aspect-ratio: 1; object-fit: cover; min-height: 60px;" alt="Imagen prenda">
                                    <div style="
                                        position: absolute;
                                        bottom: 0;
                                        left: 0;
                                        right: 0;
                                        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
                                        padding: 0.4rem;
                                    ">
                                        <span style="
                                            color: white;
                                            font-size: 0.7rem;
                                            font-weight: 600;
                                            background: #1e40af;
                                            padding: 0.2rem 0.4rem;
                                            border-radius: 2px;
                                            display: inline-block;
                                        ">
                                            ${img.tecnica}
                                        </span>
                                    </div>
                                </div>
                            `).join('');
                        }
                    }
                };
                reader.readAsDataURL(imgData.archivo);
            }
        }
    });
}

function abrirModalEditarTecnicaPaso3(nombrePrenda) {
    // Encontrar los datos de la prenda a editar
    if (!window.tecnicasAgregadasPaso3) return;
    
    let datosPrendaActual = null;
    let tecnicasConPrenda = [];
    
    window.tecnicasAgregadasPaso3.forEach(tecnicaData => {
        if (tecnicaData.prendas) {
            const prenda = tecnicaData.prendas.find(p => p.nombre === nombrePrenda);
            if (prenda) {
                if (!datosPrendaActual) {
                    datosPrendaActual = prenda;
                }
                tecnicasConPrenda.push({
                    tipo: tecnicaData.tipo,
                    prenda: prenda
                });
            }
        }
    });
    
    if (!datosPrendaActual || tecnicasConPrenda.length === 0) {
        console.error('No se encontraron datos de la prenda');
        return;
    }
    
    Swal.fire({
        title: 'Editar Prenda',
        width: '750px',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 80vh; overflow-y: auto;">
                
                <!-- NOMBRE DE PRENDA (SOLO LECTURA) -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Prenda</h3>
                    <div style="padding: 10px; background: #f5f5f5; border-radius: 4px; color: #333; font-weight: 500;">
                        ${nombrePrenda}
                    </div>
                </div>
                
                <!-- UBICACIONES POR TÉCNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Ubicaciones</h3>
                    <div id="dUbicacionesEditarP3" style="display: grid; gap: 10px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- IMÁGENES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Imágenes por Técnica</h3>
                    <div id="dImagenesEditarP3" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- VARIACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Variaciones por Técnica</h3>
                    <div id="dVariacionesEditarP3" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- OBSERVACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones</h3>
                    <textarea id="dObservacionesEditarP3" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${datosPrendaActual.observaciones || ''}</textarea>
                </div>
                
                <!-- TALLAS Y CANTIDADES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Tallas y Cantidades</h3>
                    <div id="dTallaCantidadEditarP3" style="display: grid; gap: 8px; margin-bottom: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                    <button type="button" id="dBtnAgregarTallaEditarP3" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%; font-size: 0.9rem;">+ Agregar talla</button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            const ubicacionesDiv = document.getElementById('dUbicacionesEditarP3');
            const imagenesDiv = document.getElementById('dImagenesEditarP3');
            const variacionesDiv = document.getElementById('dVariacionesEditarP3');
            const tallaCantidadContainer = document.getElementById('dTallaCantidadEditarP3');
            const btnAgregarTalla = document.getElementById('dBtnAgregarTallaEditarP3');
            
            let contadorTallas = 0;
            const imagenesAgregadasPorTecnicaEditar = {};
            const variacionesEditarPorTecnica = {};
            
            // UBICACIONES
            tecnicasConPrenda.forEach((tecnicaInfo, idx) => {
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'display: flex; gap: 8px; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 4px;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; min-width: 100px; font-size: 0.9rem; color: #333; flex-shrink: 0;';
                labelUbicacion.textContent = tecnicaInfo.tipo + ':';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacionEditar-p3-' + idx;
                inputUbicacion.value = (tecnicaInfo.prenda.ubicaciones && tecnicaInfo.prenda.ubicaciones[0]) || '';
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputUbicacion);
                ubicacionesDiv.appendChild(divUbicacion);
                
                // IMÁGENES
                imagenesAgregadasPorTecnicaEditar[idx] = tecnicaInfo.prenda.imagenes_files ? [...tecnicaInfo.prenda.imagenes_files] : [];
                
                const divImagen = document.createElement('div');
                divImagen.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                divImagen.innerHTML = `
                    <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                        Imágenes - ${tecnicaInfo.tipo} (Máximo 3)
                    </label>
                    <div class="dImagenesDropzoneEditar-p3-${idx}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                        <div style="margin-bottom: 6px; font-size: 1.3rem;">📁</div>
                        <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                        <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                        <input type="file" class="dImagenInputEditar-p3-${idx}" accept="image/*" multiple style="display: none;" />
                    </div>
                    <div class="dImagenesPreviewEditar-p3-${idx}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        <!-- Previsualizaciones aquí -->
                    </div>
                `;
                
                imagenesDiv.appendChild(divImagen);
                
                // Setup Drag and Drop
                const dropzone = divImagen.querySelector(`.dImagenesDropzoneEditar-p3-${idx}`);
                const input = divImagen.querySelector(`.dImagenInputEditar-p3-${idx}`);
                const previewContainer = divImagen.querySelector(`.dImagenesPreviewEditar-p3-${idx}`);
                
                function actualizarPrevisualizacionesEditar() {
                    previewContainer.innerHTML = '';
                    
                    imagenesAgregadasPorTecnicaEditar[idx].forEach((archivo, imgIdx) => {
                        let imagenSrc;
                        
                        if (typeof archivo === 'string') {
                            // Es una URL
                            imagenSrc = archivo;
                            mostrarPreview(imagenSrc, imgIdx);
                        } else {
                            // Es un File object
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                imagenSrc = e.target.result;
                                mostrarPreview(imagenSrc, imgIdx);
                            };
                            reader.readAsDataURL(archivo);
                        }
                    });
                    
                    function mostrarPreview(src, imgIdx) {
                        const preview = document.createElement('div');
                        preview.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                        
                        const btnEliminar = document.createElement('button');
                        btnEliminar.type = 'button';
                        btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
                        btnEliminar.textContent = '×';
                        btnEliminar.addEventListener('click', (e) => {
                            e.preventDefault();
                            imagenesAgregadasPorTecnicaEditar[idx].splice(imgIdx, 1);
                            actualizarPrevisualizacionesEditar();
                        });
                        
                        const img = document.createElement('img');
                        img.src = src;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                        
                        preview.appendChild(img);
                        preview.appendChild(btnEliminar);
                        previewContainer.appendChild(preview);
                    }
                }
                
                dropzone.addEventListener('click', () => input.click());
                
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.style.background = '#e8f1ff';
                    dropzone.style.borderColor = '#1e40af';
                });
                
                dropzone.addEventListener('dragleave', () => {
                    dropzone.style.background = '#fafafa';
                    dropzone.style.borderColor = '#ddd';
                });
                
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.style.background = '#fafafa';
                    dropzone.style.borderColor = '#ddd';
                    agregarImagenesDropEditar(Array.from(e.dataTransfer.files));
                });
                
                input.addEventListener('change', (e) => {
                    agregarImagenesDropEditar(Array.from(e.target.files));
                });
                
                function agregarImagenesDropEditar(archivos) {
                    const imagenes = archivos.filter(f => f.type.startsWith('image/'));
                    
                    if (imagenes.length === 0) return;
                    
                    for (const archivo of imagenes) {
                        if (imagenesAgregadasPorTecnicaEditar[idx].length >= 3) break;
                        imagenesAgregadasPorTecnicaEditar[idx].push(archivo);
                    }
                    
                    actualizarPrevisualizacionesEditar();
                }
                
                // Mostrar previsualizaciones iniciales
                actualizarPrevisualizacionesEditar();
                
                // VARIACIONES
                variacionesEditarPorTecnica[idx] = {
                    manga: {
                        valor: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.manga?.valor) || '',
                        observacion: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.manga?.observacion) || ''
                    },
                    bolsillos: {
                        valor: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.bolsillos?.valor) || '',
                        observacion: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.bolsillos?.observacion) || ''
                    },
                    broche_boton: {
                        valor: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.broche_boton?.valor) || '',
                        observacion: (tecnicaInfo.prenda.variaciones_prenda && tecnicaInfo.prenda.variaciones_prenda[tecnicaInfo.tipo]?.broche_boton?.observacion) || ''
                    }
                };
                
                const divVariaciones = document.createElement('div');
                divVariaciones.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                divVariaciones.innerHTML = `
                    <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                        Variaciones - ${tecnicaInfo.tipo}
                    </label>
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionEditarMangaValor-p3-${idx}" placeholder="Corta, Larga..." value="${variacionesEditarPorTecnica[idx].manga.valor}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" class="dVariacionEditarMangaObs-p3-${idx}" placeholder="Observación..." value="${variacionesEditarPorTecnica[idx].manga.observacion}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionEditarBolsillosValor-p3-${idx}" placeholder="Sí, No..." value="${variacionesEditarPorTecnica[idx].bolsillos.valor}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" class="dVariacionEditarBolsillosObs-p3-${idx}" placeholder="Observación..." value="${variacionesEditarPorTecnica[idx].bolsillos.observacion}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                                <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="dVariacionEditarBrocheValor-p3-${idx}" placeholder="Automático, Metal..." value="${variacionesEditarPorTecnica[idx].broche_boton.valor}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                                <td style="padding: 8px;"><input type="text" class="dVariacionEditarBrocheObs-p3-${idx}" placeholder="Observación..." value="${variacionesEditarPorTecnica[idx].broche_boton.observacion}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            </tr>
                        </tbody>
                    </table>
                `;
                
                variacionesDiv.appendChild(divVariaciones);
            });
            
            // TALLAS - Cargar tallas existentes
            if (datosPrendaActual.tallasCantidad && datosPrendaActual.tallasCantidad.length > 0) {
                datosPrendaActual.tallasCantidad.forEach(t => {
                    const idTalla = 'talla-editar-p3-' + (contadorTallas++);
                    const fila = document.createElement('div');
                    fila.setAttribute('data-talla-id', idTalla);
                    fila.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center;';
                    fila.innerHTML = `
                        <input type="text" class="dTallaInputEditar" value="${t.talla}" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                        <input type="number" class="dCantidadInputEditar" value="${t.cantidad}" placeholder="Cantidad" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                        <button type="button" class="dBtnEliminarTallaEditar" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">✕</button>
                    `;
                    
                    const btnEliminar = fila.querySelector('.dBtnEliminarTallaEditar');
                    btnEliminar.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (tallaCantidadContainer.children.length > 1) {
                            fila.remove();
                        }
                    });
                    
                    tallaCantidadContainer.appendChild(fila);
                });
            }
            
            // Función para agregar talla
            function agregarFilaTallaEditar() {
                const idTalla = 'talla-editar-p3-' + (contadorTallas++);
                const fila = document.createElement('div');
                fila.setAttribute('data-talla-id', idTalla);
                fila.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center;';
                fila.innerHTML = `
                    <input type="text" class="dTallaInputEditar" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <input type="number" class="dCantidadInputEditar" placeholder="Cantidad" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;">
                    <button type="button" class="dBtnEliminarTallaEditar" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">✕</button>
                `;
                
                const btnEliminar = fila.querySelector('.dBtnEliminarTallaEditar');
                btnEliminar.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (tallaCantidadContainer.children.length > 1) {
                        fila.remove();
                    }
                });
                
                tallaCantidadContainer.appendChild(fila);
            }
            
            btnAgregarTalla.addEventListener('click', (e) => {
                e.preventDefault();
                agregarFilaTallaEditar();
            });
            
            // Guardar referencia global
            window.imagenesAgregadasPorTecnicaEditarP3 = imagenesAgregadasPorTecnicaEditar;
            window.variacionesEditarPorTecnicaP3 = variacionesEditarPorTecnica;
            window.tecnicasConPrendaActual = tecnicasConPrenda;
        },
        preConfirm: () => {
            // Validar tallas
            const tallasFilas = document.querySelectorAll('[data-talla-id]');
            let tallas = [];
            tallasFilas.forEach(fila => {
                const talla = fila.querySelector('.dTallaInputEditar').value.trim();
                const cantidad = fila.querySelector('.dCantidadInputEditar').value;
                if (talla && cantidad) {
                    tallas.push({ talla, cantidad: parseInt(cantidad) });
                }
            });
            
            if (tallas.length === 0) {
                Swal.showValidationMessage('Agrega al menos una talla y cantidad');
                return false;
            }
            
            // Capturar ubicaciones
            const ubicacionesActualizadas = {};
            window.tecnicasConPrendaActual.forEach((tecnicaInfo, idx) => {
                const ubicacion = document.querySelector(`.dUbicacionEditar-p3-${idx}`)?.value.trim() || '';
                if (ubicacion) {
                    ubicacionesActualizadas[tecnicaInfo.tipo] = [ubicacion];
                }
            });
            
            // Capturar variaciones
            const variacionesActualizadas = {};
            window.tecnicasConPrendaActual.forEach((tecnicaInfo, idx) => {
                variacionesActualizadas[tecnicaInfo.tipo] = {
                    manga: {
                        valor: document.querySelector(`.dVariacionEditarMangaValor-p3-${idx}`)?.value.trim() || '',
                        observacion: document.querySelector(`.dVariacionEditarMangaObs-p3-${idx}`)?.value.trim() || ''
                    },
                    bolsillos: {
                        valor: document.querySelector(`.dVariacionEditarBolsillosValor-p3-${idx}`)?.value.trim() || '',
                        observacion: document.querySelector(`.dVariacionEditarBolsillosObs-p3-${idx}`)?.value.trim() || ''
                    },
                    broche_boton: {
                        valor: document.querySelector(`.dVariacionEditarBrocheValor-p3-${idx}`)?.value.trim() || '',
                        observacion: document.querySelector(`.dVariacionEditarBrocheObs-p3-${idx}`)?.value.trim() || ''
                    }
                };
            });
            
            return {
                nombrePrenda: nombrePrenda,
                observaciones: document.getElementById('dObservacionesEditarP3').value.trim(),
                ubicacionesActualizadas: ubicacionesActualizadas,
                variacionesActualizadas: variacionesActualizadas,
                tallas: tallas,
                imagenesAgregadas: window.imagenesAgregadasPorTecnicaEditarP3
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            guardarEdiciónPaso3(result.value);
        }
    });
}

function guardarEdiciónPaso3(datosEditados) {
    const { nombrePrenda, observaciones, ubicacionesActualizadas, variacionesActualizadas, tallas, imagenesAgregadas } = datosEditados;
    
    // Actualizar cada técnica que tenga esta prenda
    window.tecnicasAgregadasPaso3.forEach((tecnicaData, tecnicaIndex) => {
        if (tecnicaData.prendas) {
            tecnicaData.prendas = tecnicaData.prendas.map(prenda => {
                if (prenda.nombre === nombrePrenda) {
                    // Buscar índice de esta técnica en los datos editados
                    const tecnicaIdx = window.tecnicasConPrendaActual.findIndex(t => t.tipo === tecnicaData.tipo);
                    
                    return {
                        ...prenda,
                        observaciones: observaciones,
                        ubicaciones: ubicacionesActualizadas[tecnicaData.tipo] || prenda.ubicaciones,
                        variaciones_prenda: variacionesActualizadas,
                        tallasCantidad: tallas,
                        imagenes_files: (tecnicaIdx >= 0 && imagenesAgregadas[tecnicaIdx]) ? imagenesAgregadas[tecnicaIdx] : prenda.imagenes_files
                    };
                }
                return prenda;
            });
        }
    });
    
    console.log(' Prenda editada en PASO 3:', nombrePrenda);
    renderizarTecnicasAgregadasPaso3();
}

function eliminarTecnicaPaso3(nombrePrenda) {
    if (!window.tecnicasAgregadasPaso3) return;
    
    // Eliminar todas las prendas con este nombre
    window.tecnicasAgregadasPaso3 = window.tecnicasAgregadasPaso3.filter(tecnica => {
        if (tecnica.prendas) {
            tecnica.prendas = tecnica.prendas.filter(p => p.nombre !== nombrePrenda);
            return tecnica.prendas.length > 0;
        }
        return true;
    });
    
    renderizarTecnicasAgregadasPaso3();
}

// =========================================================
// 7. MODAL Y FUNCIONES AUXILIARES
// =========================================================

function cerrarModalAgregarTecnicaPaso3() {
    const modal = document.getElementById('modalAgregarTecnicaPaso3');
    if (modal) {
        modal.style.display = 'none';
    }
}

function cerrarModalValidacionTecnicaPaso3() {
    const modal = document.getElementById('modalValidacionTecnicaPaso3');
    if (modal) {
        modal.style.display = 'none';
    }
}

function agregarFilaPrendaPaso3() {
    const container = document.getElementById('listaPrendasPaso3');
    
    if (!container) {
        console.error(' Elemento listaPrendasPaso3 no encontrado');
        return;
    }
    
    const numeroPrenda = container.children.length + 1;
    const prendasIndex = numeroPrenda - 1;
    const idUnico = Date.now() + Math.floor(Math.random() * 100000);
    
    // Obtener prendas del PASO 2
    const prendasPaso2 = obtenerPrendasDelPaso2();
    
    const fila = document.createElement('div');
    fila.className = 'prenda-item-paso3';
    fila.setAttribute('data-prenda-id', idUnico);
    fila.style.cssText = 'margin-bottom: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 600; color: #333; font-size: 0.9rem;">
                <span>Prenda <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="this.closest('.prenda-item-paso3').remove(); actualizarNumeracionPrendasPaso3();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar prenda">
                    ✕
                </button>
            </div>
            
            <!-- DROPDOWN DE PRENDAS DEL PASO 2 -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Nombre de prenda</label>
                <select class="select-prenda-paso2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; cursor: pointer;">
                    <option value="">-- Selecciona una prenda --</option>
                    ${prendasPaso2.map(prenda => {
                        let textoOpcion = prenda.nombre;
                        if (prenda.genero) {
                            textoOpcion += ' - ' + prenda.genero;
                        }
                        if (prenda.colores && prenda.colores.length > 0) {
                            textoOpcion += ' - Color: ' + prenda.colores.join(' - ');
                        }
                        const dataTallas = JSON.stringify(prenda.tallas).replace(/"/g, '&quot;');
                        const dataGenero = prenda.genero || '';
                        const dataColores = JSON.stringify(prenda.colores).replace(/"/g, '&quot;');
                        const dataImagenes = JSON.stringify(prenda.imagenes || []).replace(/"/g, '&quot;');
                        const dataVariaciones = JSON.stringify(prenda.variaciones || {}).replace(/"/g, '&quot;');
                        return `<option value="${prenda.nombre}" data-tallas="${dataTallas}" data-genero="${dataGenero}" data-colores="${dataColores}" data-imagenes="${dataImagenes}" data-variaciones="${dataVariaciones}">${textoOpcion}</option>`;
                    }).join('')}
                </select>
                <small style="color: #999; display: block; margin-top: 4px; font-size: 0.8rem;">Las prendas se cargan automáticamente del PASO 2</small>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Ubicaciones</label>
                <div style="display: flex; gap: 6px; margin-bottom: 8px;">
                    <input type="text" class="ubicacion-input-paso3" placeholder="PECHO, ESPALDA, MANGA..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
                    <button type="button" class="btn-agregar-ubicacion-paso3" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem;">
                        + Agregar
                    </button>
                </div>
                <div class="ubicaciones-lista-paso3" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 24px; align-content: flex-start;">
                    <!-- Ubicaciones agregadas aquí como tags -->
                </div>
            </div>
            
            <!-- SECCIÓN DE IMÁGENES CON DRAG AND DROP -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Imágenes (Máximo 3)</label>
                <div class="dropzone-paso3-${idUnico}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                    <div style="margin-bottom: 6px; font-size: 1.3rem;">📁</div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar</p>
                    <input type="file" class="imagenes-input-paso3-${idUnico}" accept="image/*" multiple style="display: none;" />
                </div>
                <div class="imagenes-preview-paso3-${idUnico}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                    <!-- Previsualizaciones aquí -->
                </div>
            </div>
            
            <!-- TABLA DE VARIACIONES -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Variaciones</label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-manga-valor-paso3" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-manga-obs-paso3" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-bolsillos-valor-paso3" placeholder="Sí, No..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-bolsillos-obs-paso3" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-broche-valor-paso3" placeholder="Automático, Metal..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-broche-obs-paso3" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Observaciones</label>
                <textarea class="observaciones-paso3" rows="2" placeholder="Detalles adicionales (opcional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
            </div>
            
            <!-- Tallas y Cantidades Dinámicas -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Tallas y Cantidades</label>
                <div class="tallas-container-paso3" style="display: grid; gap: 8px;">
                    <!-- Filas de talla-cantidad aquí -->
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                    <button type="button" onclick="agregarTallaCantidadPaso3(this)" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem;">+ Talla</button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    // Listeners para ubicaciones
    const inputUbicacion = fila.querySelector('.ubicacion-input-paso3');
    const btnAgregarUbicacion = fila.querySelector('.btn-agregar-ubicacion-paso3');
    const ubicacionesLista = fila.querySelector('.ubicaciones-lista-paso3');
    const selectPrenda = fila.querySelector('.select-prenda-paso2');
    const tallasContainer = fila.querySelector('.tallas-container-paso3');
    let ubicacionesAgregadas = [];
    let imagenesPaso2 = []; // Inicializar array de imágenes del PASO 2
    
    function renderizarUbicaciones() {
        ubicacionesLista.innerHTML = ubicacionesAgregadas.map((ub, idx) => `
            <span style="display: inline-flex; align-items: center; gap: 6px; background: #e8f0ff; color: #1e40af; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; font-weight: 600;">
                ${ub}
                <button type="button" onclick="this.closest('span').remove(); " style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; line-height: 1;">×</button>
            </span>
        `).join('');
    }
    
    // Listener para cambio de prenda - carga las tallas, imágenes y variaciones automáticamente
    selectPrenda.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const tallasJson = selectedOption.getAttribute('data-tallas');
        const imagenesJson = selectedOption.getAttribute('data-imagenes');
        const nombrePrendaBase = selectPrenda.value.trim().toUpperCase();
        
        // Construir nombre completo IGUAL que en preConfirm (el que se guarda)
        const genero = selectedOption.getAttribute('data-genero') || '';
        const coloresJson = selectedOption.getAttribute('data-colores') || '[]';
        
        let colores = [];
        try {
            colores = JSON.parse(coloresJson);
        } catch (e) {
            colores = [];
        }
        
        let nombrePrendaCompleto = nombrePrendaBase;
        if (genero) {
            nombrePrendaCompleto += ' - ' + genero;
        }
        if (colores && colores.length > 0) {
            nombrePrendaCompleto += ' - Color: ' + colores.join(' - ');
        }
        
        // Limpiar tallas actuales
        tallasContainer.innerHTML = '';
        
        if (tallasJson) {
            try {
                const tallas = JSON.parse(tallasJson);
                if (Array.isArray(tallas) && tallas.length > 0) {
                    // Agregar una fila por cada talla del PASO 2
                    tallas.forEach(talla => {
                        const filaT = document.createElement('div');
                        filaT.className = 'talla-cantidad-paso3';
                        filaT.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 40px; gap: 8px; align-items: center;';
                        filaT.innerHTML = `
                            <input type="text" class="talla-input-paso3" placeholder="Talla" value="${talla}" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;" readonly>
                            <input type="number" class="cantidad-input-paso3" placeholder="Cantidad" min="1" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
                            <button type="button" onclick="this.closest('.talla-cantidad-paso3').remove()" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px; border-radius: 3px; cursor: pointer; font-size: 0.85rem;">✕</button>
                        `;
                        tallasContainer.appendChild(filaT);
                    });
                }
            } catch (err) {
                console.error('Error al parsear tallas:', err);
            }
        }
        
        // Cargar imágenes de la prenda seleccionada
        if (imagenesJson) {
            try {
                const imagenes = JSON.parse(imagenesJson);
                if (Array.isArray(imagenes) && imagenes.length > 0) {
                    const previewContainer = fila.querySelector(`.imagenes-preview-paso3-${idUnico}`);
                    if (previewContainer) {
                        // Guardar imágenes del PASO 2 sin limpiar el contenedor
                        imagenesPaso2 = imagenes.slice(0, 3);
                        
                        // IMPORTANTE: Guardar imagenesPaso2 en la fila para que se extraiga al guardar
                        fila.imagenesPaso2 = imagenesPaso2;
                        
                        // Actualizar previsualizaciones (mostrará PASO 2 + las nuevas agregadas)
                        actualizarPrevisualizaciones();
                    }
                }
            } catch (err) {
                console.error('Error al parsear imágenes:', err);
            }
        }
        
        // CARGAR VARIACIONES DE PRENDA DESDE PASO 2
        const variacionesJson = selectedOption.getAttribute('data-variaciones');
        let variacionesPaso2 = {};
        
        try {
            if (variacionesJson) {
                variacionesPaso2 = JSON.parse(variacionesJson);
            }
        } catch (err) {
            console.error('Error al parsear variaciones:', err);
            variacionesPaso2 = {};
        }
        
        // Cargar variaciones desde data-variaciones
        if (variacionesPaso2 && Object.keys(variacionesPaso2).length > 0) {
            // Buscar los campos de entrada en la fila
            const mangaValor = fila.querySelector('.variacion-manga-valor-paso3');
            const mangaObs = fila.querySelector('.variacion-manga-obs-paso3');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-paso3');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-paso3');
            const brocheValor = fila.querySelector('.variacion-broche-valor-paso3');
            const brocheObs = fila.querySelector('.variacion-broche-obs-paso3');
            
            // Cargar valores en los campos
            if (variacionesPaso2.manga) {
                if (mangaValor) mangaValor.value = variacionesPaso2.manga.valor || '';
                if (mangaObs) mangaObs.value = variacionesPaso2.manga.observacion || '';
            } else {
                if (mangaValor) mangaValor.value = '';
                if (mangaObs) mangaObs.value = '';
            }
            
            if (variacionesPaso2.bolsillos) {
                if (bolsillosValor) bolsillosValor.value = variacionesPaso2.bolsillos.valor || '';
                if (bolsillosObs) bolsillosObs.value = variacionesPaso2.bolsillos.observacion || '';
            } else {
                if (bolsillosValor) bolsillosValor.value = '';
                if (bolsillosObs) bolsillosObs.value = '';
            }
            
            if (variacionesPaso2.broche_boton) {
                if (brocheValor) brocheValor.value = variacionesPaso2.broche_boton.valor || '';
                if (brocheObs) brocheObs.value = variacionesPaso2.broche_boton.observacion || '';
            } else {
                if (brocheValor) brocheValor.value = '';
                if (brocheObs) brocheObs.value = '';
            }
        } else {
            // Si no hay variaciones, limpiar campos
            const mangaValor = fila.querySelector('.variacion-manga-valor-paso3');
            const mangaObs = fila.querySelector('.variacion-manga-obs-paso3');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-paso3');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-paso3');
            const brocheValor = fila.querySelector('.variacion-broche-valor-paso3');
            const brocheObs = fila.querySelector('.variacion-broche-obs-paso3');
            
            if (mangaValor) mangaValor.value = '';
            if (mangaObs) mangaObs.value = '';
            if (bolsillosValor) bolsillosValor.value = '';
            if (bolsillosObs) bolsillosObs.value = '';
            if (brocheValor) brocheValor.value = '';
            if (brocheObs) brocheObs.value = '';
        }
    });
    
    btnAgregarUbicacion.addEventListener('click', (e) => {
        e.preventDefault();
        const ubicacion = inputUbicacion.value.trim().toUpperCase();
        if (ubicacion && !ubicacionesAgregadas.includes(ubicacion)) {
            ubicacionesAgregadas.push(ubicacion);
            renderizarUbicaciones();
            inputUbicacion.value = '';
            inputUbicacion.focus();
        }
    });
    
    inputUbicacion.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnAgregarUbicacion.click();
        }
    });
    
    // Setup Drag and Drop para imágenes
    const dropzone = fila.querySelector(`.dropzone-paso3-${idUnico}`);
    const inputArchivos = fila.querySelector(`.imagenes-input-paso3-${idUnico}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-paso3-${idUnico}`);
    let imagenesAgregadas = [];
    
    function actualizarPrevisualizaciones() {
        previewContainer.innerHTML = '';
        
        // Primero mostrar imágenes del PASO 2
        imagenesPaso2.forEach((imagenSrc, imgIdx) => {
            const divPreview = document.createElement('div');
            divPreview.style.cssText = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; background: #f0f0f0; cursor: pointer; border: 2px solid #10b981;';
            divPreview.setAttribute('data-tipo', 'paso2');
            divPreview.innerHTML = `
                <img src="${imagenSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 2px; right: 2px; background: #10b981; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">${imgIdx + 1}</span>
                <span style="position: absolute; bottom: 2px; left: 2px; background: rgba(0,0,0,0.7); color: white; border-radius: 3px; padding: 2px 4px; font-size: 9px; font-weight: bold;">PASO 2</span>
                <button type="button" onclick="event.stopPropagation(); this.closest('[style*=\"position: relative\"]').remove();" style="position: absolute; top: 2px; left: 2px; background: #f44336; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;">✕</button>
            `;
            divPreview.addEventListener('mouseenter', function() {
                this.querySelector('button').style.opacity = '1';
            });
            divPreview.addEventListener('mouseleave', function() {
                this.querySelector('button').style.opacity = '0';
            });
            previewContainer.appendChild(divPreview);
        });
        
        // Luego mostrar imágenes nuevas
        imagenesAgregadas.forEach((archivo, imgIdx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = document.createElement('div');
                preview.style.cssText = 'position: relative; width: 70px; height: 70px; border-radius: 4px; overflow: hidden; border: 2px solid #3b82f6;';
                preview.setAttribute('data-tipo', 'nueva');
                
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: #f44336; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; padding: 0; opacity: 0; transition: opacity 0.2s;';
                btnEliminar.textContent = '×';
                btnEliminar.addEventListener('click', (e) => {
                    e.preventDefault();
                    imagenesAgregadas.splice(imgIdx, 1);
                    actualizarPrevisualizaciones();
                });
                
                const numImg = document.createElement('span');
                numImg.style.cssText = 'position: absolute; top: 2px; right: 2px; background: #3b82f6; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;';
                numImg.textContent = imagenesPaso2.length + imgIdx + 1;
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                
                preview.appendChild(img);
                preview.appendChild(numImg);
                preview.appendChild(btnEliminar);
                previewContainer.appendChild(preview);
                
                preview.addEventListener('mouseenter', function() {
                    this.querySelector('button').style.opacity = '1';
                    this.querySelector('span').style.opacity = '0';
                });
                preview.addEventListener('mouseleave', function() {
                    this.querySelector('button').style.opacity = '0';
                    this.querySelector('span').style.opacity = '1';
                });
            };
            reader.readAsDataURL(archivo);
        });
    }
    
    dropzone.addEventListener('click', () => inputArchivos.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#1e40af';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        agregarImagenesDelDropzonePaso3(Array.from(e.dataTransfer.files));
    });
    
    inputArchivos.addEventListener('change', (e) => {
        agregarImagenesDelDropzonePaso3(Array.from(e.target.files));
    });
    
    function agregarImagenesDelDropzonePaso3(archivos) {
        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
        
        if (imagenes.length === 0) {
            return;
        }
        
        // Calcular espacio disponible: máximo 3 imágenes totales (PASO 2 + nuevas)
        const espacioDisponible = 3 - imagenesPaso2.length;
        
        if (espacioDisponible <= 0) {
            Swal.fire(' Límite alcanzado', `Ya hay ${imagenesPaso2.length} imagen(es) del PASO 2. Máximo 3 imágenes total.`, 'warning');
            return;
        }
        
        for (const archivo of imagenes) {
            if (imagenesAgregadas.length >= espacioDisponible) {
                break;
            }
            imagenesAgregadas.push(archivo);
        }
        
        actualizarPrevisualizaciones();
    }
    
    // Guardar referencias para extracción posterior
    fila.ubicacionesAgregadas = ubicacionesAgregadas;
    fila.imagenesAgregadas = imagenesAgregadas;
    // fila.imagenesPaso2 se asigna dinámicamente cuando se selecciona una prenda en el change event
    fila.nombrePrendaSelect = selectPrenda;
}

function agregarTallaCantidadPaso3(button) {
    const container = button.closest('.prenda-item-paso3');
    const tallasContainer = container.querySelector('.tallas-container-paso3');
    const numeroFila = tallasContainer.children.length + 1;
    
    const fila = document.createElement('div');
    fila.className = 'talla-cantidad-paso3';
    fila.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 40px; gap: 8px; align-items: center;';
    fila.innerHTML = `
        <input type="text" class="talla-input-paso3" placeholder="Talla (XS, S, M, L, XL...)" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;">
        <input type="number" class="cantidad-input-paso3" placeholder="Cantidad" min="1" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;">
        <button type="button" onclick="this.closest('.talla-cantidad-paso3').remove()" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px; border-radius: 3px; cursor: pointer; font-size: 0.85rem;">✕</button>
    `;
    
    tallasContainer.appendChild(fila);
}

function actualizarNumeracionPrendasPaso3() {
    const prendas = document.querySelectorAll('.prenda-item-paso3');
    prendas.forEach((prenda, idx) => {
        const numeroPrendaSpan = prenda.querySelector('.numero-prenda');
        if (numeroPrendaSpan) {
            numeroPrendaSpan.textContent = idx + 1;
        }
    });
}

function guardarTecnicaPaso3() {
    const tecnicaSeleccionada = window.tecnicaSeleccionadaPaso3;
    const listaPrendas = document.getElementById('listaPrendasPaso3');
    
    if (!listaPrendas || listaPrendas.children.length === 0) {
        Swal.fire(' Error', 'Debes agregar al menos una prenda', 'warning');
        return;
    }
    
    // Extraer todas las prendas del modal
    const prendas = [];
    const prendasItems = listaPrendas.querySelectorAll('.prenda-item-paso3');
    
    prendasItems.forEach((item, idx) => {
        // Obtener nombre del select en lugar del input
        const selectPrenda = item.querySelector('.select-prenda-paso2');
        const nombreBase = selectPrenda?.value.trim().toUpperCase() || '';
        const selectedOption = selectPrenda?.options[selectPrenda.selectedIndex];
        
        // Obtener género y colores del data attribute
        const genero = selectedOption?.getAttribute('data-genero') || '';
        const coloresJson = selectedOption?.getAttribute('data-colores') || '[]';
        
        let colores = [];
        try {
            colores = JSON.parse(coloresJson);
        } catch (e) {
            colores = [];
        }
        
        // Construir nombre completo: Nombre - Género - Colores
        let nombre = nombreBase;
        if (genero) {
            nombre += ' - ' + genero;
        }
        if (colores && colores.length > 0) {
            nombre += ' - Color: ' + colores.join(' - ');
        }
        
        const observaciones = item.querySelector('.observaciones-paso3')?.value.trim() || '';
        
        // Obtener ubicaciones
        const ubicacionesTags = item.querySelectorAll('.ubicaciones-lista-paso3 span');
        const ubicaciones = Array.from(ubicacionesTags).map(tag => tag.textContent.trim().replace('×', '').trim());
        
        // Obtener imágenes (combinar PASO 2 + nuevas agregadas)
        const imagenesPaso2 = item.imagenesPaso2 || [];
        const imagenesAgregadas = item.imagenesAgregadas || [];
        const imagenesFiles = [...imagenesPaso2, ...imagenesAgregadas];
        
        // Obtener tallas y cantidades
        const tallasCantidad = [];
        const tallasFilas = item.querySelectorAll('.talla-cantidad-paso3');
        tallasFilas.forEach(fila => {
            const talla = fila.querySelector('.talla-input-paso3')?.value.trim() || '';
            const cantidad = parseInt(fila.querySelector('.cantidad-input-paso3')?.value) || 0;
            if (talla && cantidad > 0) {
                tallasCantidad.push({ talla, cantidad });
            }
        });
        
        // Obtener variaciones
        const variaciones_prenda = {};
        const manga_valor = item.querySelector('.variacion-manga-valor-paso3')?.value.trim() || '';
        const manga_obs = item.querySelector('.variacion-manga-obs-paso3')?.value.trim() || '';
        const bolsillos_valor = item.querySelector('.variacion-bolsillos-valor-paso3')?.value.trim() || '';
        const bolsillos_obs = item.querySelector('.variacion-bolsillos-obs-paso3')?.value.trim() || '';
        const broche_valor = item.querySelector('.variacion-broche-valor-paso3')?.value.trim() || '';
        const broche_obs = item.querySelector('.variacion-broche-obs-paso3')?.value.trim() || '';
        
        variaciones_prenda[tecnicaSeleccionada.nombre] = {
            manga: { valor: manga_valor, observacion: manga_obs },
            bolsillos: { valor: bolsillos_valor, observacion: bolsillos_obs },
            broche_boton: { valor: broche_valor, observacion: broche_obs }
        };
        
        if (!nombreBase) {
            Swal.fire(' Error', `Debes seleccionar una prenda en la prenda ${idx + 1}`, 'warning');
            return;
        }
        
        if (ubicaciones.length === 0) {
            Swal.fire(' Error', `La prenda "${nombre}" debe tener al menos una ubicación`, 'warning');
            return;
        }
        
        prendas.push({
            nombre,
            ubicaciones,
            observaciones,
            tallasCantidad,
            variaciones_prenda,
            imagenes_files: imagenesFiles,
            tecnica: tecnicaSeleccionada
        });
    });
    
    if (prendas.length > 0) {
        // Agregar a tecnicas agregadas
        if (!window.tecnicasAgregadasPaso3) {
            window.tecnicasAgregadasPaso3 = [];
        }
        
        const tecnicaExistente = window.tecnicasAgregadasPaso3.find(t => t.tipo === tecnicaSeleccionada.nombre);
        if (tecnicaExistente) {
            tecnicaExistente.prendas = prendas;
        } else {
            window.tecnicasAgregadasPaso3.push({
                tipo: tecnicaSeleccionada.nombre,
                prendas: prendas,
                observacionesGenerales: ''
            });
        }
        
        renderizarTecnicasAgregadasPaso3();
        cerrarModalAgregarTecnicaPaso3();
    }
}

function agregarObservacionPaso3() {
    const contenedor = document.getElementById('observaciones_lista_paso3');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_paso3[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(fila);
}

// =========================================================
// 9. PASO 4: REFLECTIVO - GESTIÓN DE PRENDAS
// =========================================================

let prendas_reflectivo_paso4 = [];

function agregarPrendaReflectivoPaso4() {
    console.log('🔸 agregarPrendaReflectivoPaso4() - Iniciando...');
    
    const container = document.getElementById('prendas_reflectivo_container');
    if (!container) {
        console.error(' Container prendas_reflectivo_container no encontrado');
        return;
    }
    
    const numeroPrenda = prendas_reflectivo_paso4.length + 1;
    const prendasIndex = numeroPrenda - 1;
    
    // Obtener prendas del PASO 2 - EXACTAMENTE COMO EN PASO 3
    console.log('📥 Llamando obtenerPrendasDelPaso2()...');
    const prendasPaso2 = obtenerPrendasDelPaso2();
    console.log(` Prendas obtenidas: ${prendasPaso2.length}`);
    if (prendasPaso2.length > 0) {
        console.log(' Prendas del Paso 2:', prendasPaso2);
    }
    
    const fila = document.createElement('div');
    fila.className = 'prenda-reflectivo-item';
    fila.setAttribute('data-prenda-index', prendasIndex);
    fila.style.cssText = 'margin-bottom: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 600; color: #333; font-size: 0.9rem;">
                <span>🔸 Prenda Reflectivo <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="eliminarPrendaReflectivoPaso4(${prendasIndex});" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar prenda">
                    ✕
                </button>
            </div>
            
            <!-- TIPO DE PRENDA - DROPDOWN CON PRENDAS DEL PASO 2 -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;"> TIPO DE PRENDA</label>
                <select class="tipo-prenda-reflectivo select-prenda-paso2-reflectivo" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; cursor: pointer;">
                    <option value="">-- Selecciona una prenda --</option>
                    ${prendasPaso2.map(prenda => {
                        let textoOpcion = prenda.nombre;
                        if (prenda.genero) {
                            textoOpcion += ' - ' + prenda.genero;
                        }
                        if (prenda.colores && prenda.colores.length > 0) {
                            textoOpcion += ' - Color: ' + prenda.colores.join(' - ');
                        }
                        const dataTallas = JSON.stringify(prenda.tallas).replace(/"/g, '&quot;');
                        const dataGenero = prenda.genero || '';
                        const dataColores = JSON.stringify(prenda.colores).replace(/"/g, '&quot;');
                        const dataImagenes = JSON.stringify(prenda.imagenes || []).replace(/"/g, '&quot;');
                        const dataVariaciones = JSON.stringify(prenda.variaciones || {}).replace(/"/g, '&quot;');
                        return `<option value="${prenda.nombre}" data-tallas="${dataTallas}" data-genero="${dataGenero}" data-colores="${dataColores}" data-imagenes="${dataImagenes}" data-variaciones="${dataVariaciones}">${textoOpcion}</option>`;
                    }).join('')}
                </select>
                <small style="color: #999; display: block; margin-top: 4px; font-size: 0.8rem;">Las prendas se cargan del PASO 2</small>
            </div>
            
            <!-- DESCRIPCIÓN -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;"> DESCRIPCIÓN</label>
                <textarea class="descripcion-reflectivo" rows="2" placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
            </div>
            
            <!-- IMÁGENES (MÁXIMO 3) -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">📸 IMÁGENES (MÁXIMO 3)</label>
                <div class="imagenes-dropzone-reflectivo-${prendasIndex}" style="border: 2px dashed #3498db; border-radius: 6px; padding: 20px; text-align: center; background: #f0f7ff; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#dbeafe'; this.style.borderColor='#3498db';" onmouseout="this.style.background='#f0f7ff'; this.style.borderColor='#3498db';">
                    <div style="margin-bottom: 8px; font-size: 1.5rem;"></div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                    <input type="file" class="imagen-reflectivo-input-${prendasIndex}" accept="image/*" multiple style="display: none;" />
                </div>
                <div class="imagenes-preview-reflectivo-${prendasIndex}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                    <!-- Previsualizaciones aquí -->
                </div>
            </div>
            
            <!-- TALLAS A COTIZAR - TABLA DINÁMICA -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">📏 TALLAS A COTIZAR</label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem; margin-bottom: 8px;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Talla</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Cantidad</th>
                            <th style="padding: 8px; text-align: center; font-weight: 600; color: #333; width: 40px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="tallas-reflectivo-tabla-body">
                        <!-- Filas de talla-cantidad aquí -->
                    </tbody>
                </table>
            </div>
            
            <!-- TABLA DE VARIACIONES -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Variaciones</label>
                <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #f3f4f6; border-bottom: 1px solid #ddd;">
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Tipo</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333; border-right: 1px solid #ddd;">Valor</th>
                            <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Manga</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-manga-valor-reflectivo" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-manga-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Bolsillos</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-bolsillos-valor-reflectivo" placeholder="Sí, No..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-bolsillos-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; color: #333; font-weight: 500; border-right: 1px solid #ddd;">Broche/Botón</td>
                            <td style="padding: 8px; border-right: 1px solid #ddd;"><input type="text" class="variacion-broche-valor-reflectivo" placeholder="Automático, Metal..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                            <td style="padding: 8px;"><input type="text" class="variacion-broche-obs-reflectivo" placeholder="Observación..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- UBICACIÓN CON MODAL -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.95rem;"> UBICACIONES</label>
                <div style="margin-bottom: 12px; display: flex; gap: 6px;">
                    <input type="text" class="seccion-ubicacion-reflectivo-input" list="opciones_seccion_reflectivo" placeholder="PECHO, ESPALDA, MANGA..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
                    <button type="button" class="btn-agregar-ubicacion-modal-reflectivo" style="background: #3498db; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem; white-space: nowrap;">
                        + Agregar
                    </button>
                </div>
                <datalist id="opciones_seccion_reflectivo">
                    <option value="PECHO">
                    <option value="ESPALDA">
                    <option value="MANGA">
                    <option value="CUELLO">
                    <option value="COSTADO">
                    <option value="MÚLTIPLE">
                </datalist>
                <div class="ubicaciones-reflectivo-agregadas" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; min-height: 24px;"></div>
            </div>
            
            <!-- OBSERVACIONES GENERALES -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.95rem;">💬 OBSERVACIONES GENERALES</label>
                <button type="button" class="btn-agregar-obs-reflectivo" style="background: #3498db; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem; margin-bottom: 10px; white-space: nowrap;">
                    + Agregar Observación
                </button>
                <div class="observaciones-reflectivo-lista" style="display: flex; flex-direction: column; gap: 8px;"></div>
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    // LOG DE VALIDACIÓN
    const selectCreado = fila.querySelector('.select-prenda-paso2-reflectivo');
    const opcionesCreadas = selectCreado ? selectCreado.querySelectorAll('option').length : 0;
    console.log(` Prenda Reflectivo ${numeroPrenda} agregada. Select con ${opcionesCreadas} opciones (${opcionesCreadas - 1} prendas + 1 opción vacía)`);
    
    prendas_reflectivo_paso4.push({
        index: prendasIndex,
        tipo_prenda: '',
        descripcion: '',
        imagenes: [],
        tallas: [],
        ubicaciones: [],
        variaciones: {
            manga: { valor: '', observacion: '' },
            bolsillos: { valor: '', observacion: '' },
            broche_boton: { valor: '', observacion: '' }
        }
    });
    
    // LISTENER: Cuando se selecciona una prenda, cargar sus tallas, imágenes y variaciones
    const selectPrenda = fila.querySelector('.select-prenda-paso2-reflectivo');
    selectPrenda.addEventListener('change', (e) => {
        console.log(` SELECT PRENDA CAMBIÓ - Prenda ${prendasIndex}:`, e.target.value);
        
        const selectedOption = e.target.options[e.target.selectedIndex];
        const tallasJson = selectedOption.getAttribute('data-tallas');
        const imagenesJson = selectedOption.getAttribute('data-imagenes');
        const variacionesJson = selectedOption.getAttribute('data-variaciones');
        const nombrePrenda = selectPrenda.value.trim();
        
        console.log(`  - Nombre: ${nombrePrenda}`);
        console.log(`  - Tallas JSON: ${tallasJson}`);
        console.log(`  - Imágenes JSON: ${imagenesJson}`);
        console.log(`  - Variaciones JSON: ${variacionesJson}`);
        
        // Actualizar nombre en el objeto prenda
        const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
        if (prenda) {
            prenda.tipo_prenda = nombrePrenda;
        }
        
        // Limpiar tabla de tallas
        const tablaTallasBody = fila.querySelector('.tallas-reflectivo-tabla-body');
        tablaTallasBody.innerHTML = '';
        
        // Cargar tallas de la prenda seleccionada
        if (tallasJson) {
            try {
                const tallas = JSON.parse(tallasJson);
                console.log(`  - Tallas parseadas: ${JSON.stringify(tallas)}`);
                if (Array.isArray(tallas) && tallas.length > 0) {
                    tallas.forEach(talla => {
                        console.log(`    - Agregando fila para talla: ${talla}`);
                        agregarFilaTallaCantidadReflectivo(tablaTallasBody, prendasIndex, talla);
                    });
                }
            } catch (err) {
                console.error(' Error al parsear tallas:', err);
            }
        } else {
            console.warn(' No hay tallasJson en el atributo data-tallas');
        }
        
        // CARGAR VARIACIONES DE PRENDA DESDE PASO 2
        let variacionesPaso2 = {};
        try {
            if (variacionesJson) {
                variacionesPaso2 = JSON.parse(variacionesJson);
            }
        } catch (err) {
            console.error(' Error al parsear variaciones:', err);
            variacionesPaso2 = {};
        }
        
        // Cargar variaciones desde data-variaciones
        if (variacionesPaso2 && Object.keys(variacionesPaso2).length > 0) {
            // Buscar los campos de entrada en la fila
            const mangaValor = fila.querySelector('.variacion-manga-valor-reflectivo');
            const mangaObs = fila.querySelector('.variacion-manga-obs-reflectivo');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-reflectivo');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-reflectivo');
            const brocheValor = fila.querySelector('.variacion-broche-valor-reflectivo');
            const brocheObs = fila.querySelector('.variacion-broche-obs-reflectivo');
            
            // Cargar valores en los campos
            if (variacionesPaso2.manga) {
                if (mangaValor) mangaValor.value = variacionesPaso2.manga.valor || '';
                if (mangaObs) mangaObs.value = variacionesPaso2.manga.observacion || '';
            } else {
                if (mangaValor) mangaValor.value = '';
                if (mangaObs) mangaObs.value = '';
            }
            
            if (variacionesPaso2.bolsillos) {
                if (bolsillosValor) bolsillosValor.value = variacionesPaso2.bolsillos.valor || '';
                if (bolsillosObs) bolsillosObs.value = variacionesPaso2.bolsillos.observacion || '';
            } else {
                if (bolsillosValor) bolsillosValor.value = '';
                if (bolsillosObs) bolsillosObs.value = '';
            }
            
            if (variacionesPaso2.broche_boton) {
                if (brocheValor) brocheValor.value = variacionesPaso2.broche_boton.valor || '';
                if (brocheObs) brocheObs.value = variacionesPaso2.broche_boton.observacion || '';
            } else {
                if (brocheValor) brocheValor.value = '';
                if (brocheObs) brocheObs.value = '';
            }
        } else {
            // Si no hay variaciones, limpiar campos
            const mangaValor = fila.querySelector('.variacion-manga-valor-reflectivo');
            const mangaObs = fila.querySelector('.variacion-manga-obs-reflectivo');
            const bolsillosValor = fila.querySelector('.variacion-bolsillos-valor-reflectivo');
            const bolsillosObs = fila.querySelector('.variacion-bolsillos-obs-reflectivo');
            const brocheValor = fila.querySelector('.variacion-broche-valor-reflectivo');
            const brocheObs = fila.querySelector('.variacion-broche-obs-reflectivo');
            
            if (mangaValor) mangaValor.value = '';
            if (mangaObs) mangaObs.value = '';
            if (bolsillosValor) bolsillosValor.value = '';
            if (bolsillosObs) bolsillosObs.value = '';
            if (brocheValor) brocheValor.value = '';
            if (brocheObs) brocheObs.value = '';
        }
        
        // CARGAR IMÁGENES DE PRENDA DESDE PASO 2
        if (imagenesJson) {
            try {
                const imagenes = JSON.parse(imagenesJson);
                if (Array.isArray(imagenes) && imagenes.length > 0) {
                    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
                    if (previewContainer) {
                        console.log(`  - Imágenes parseadas: ${imagenes.length} encontradas`);
                        
                        // Guardar imágenes del PASO 2
                        fila.imagenesPaso2 = imagenes.slice(0, 3);
                        
                        // Mostrar las imágenes en la previsualización
                        previewContainer.innerHTML = '';
                        
                        fila.imagenesPaso2.forEach((imgSrc, imgIdx) => {
                            const div = document.createElement('div');
                            div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                            div.innerHTML = `
                                <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                                <span style="position: absolute; top: 2px; right: 2px; background: rgba(76, 175, 80, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: default; font-size: 0.75rem; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</span>
                            `;
                            previewContainer.appendChild(div);
                        });
                    }
                }
            } catch (err) {
                console.error(' Error al parsear imágenes:', err);
            }
        } else {
            console.warn(' No hay imagenesJson en el atributo data-imagenes');
        }
    });
    
    // Setup para agregar ubicación CON MODAL (como en reflectivo.js)
    const btnAgregarUbicacionModal = fila.querySelector('.btn-agregar-ubicacion-modal-reflectivo');
    btnAgregarUbicacionModal.addEventListener('click', (e) => {
        e.preventDefault();
        abrirModalUbicacionReflectivoPaso4(prendasIndex, fila);
    });
    
    // Setup para agregar observación
    const btnAgregarObs = fila.querySelector('.btn-agregar-obs-reflectivo');
    btnAgregarObs.addEventListener('click', (e) => {
        e.preventDefault();
        agregarObservacionReflectivoPaso4(prendasIndex, fila);
    });
    
    // Setup drag and drop para imágenes
    const dropzone = fila.querySelector(`.imagenes-dropzone-reflectivo-${prendasIndex}`);
    const input = fila.querySelector(`.imagen-reflectivo-input-${prendasIndex}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
    
    dropzone.addEventListener('click', () => input.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#dbeafe';
        dropzone.style.borderColor = '#3498db';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#f0f7ff';
        dropzone.style.borderColor = '#3498db';
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#f0f7ff';
        dropzone.style.borderColor = '#3498db';
        
        const archivos = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer);
    });
    
    input.addEventListener('change', (e) => {
        const archivos = Array.from(e.target.files || []).filter(f => f.type.startsWith('image/'));
        procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer);
    });
    
    actualizarEstadoSinPrendas();
}

function agregarFilaTallaCantidadReflectivo(tablaTallasBody, prendasIndex, talla = '') {
    const fila = document.createElement('tr');
    fila.style.cssText = 'border-bottom: 1px solid #ddd;';
    
    const inputTalla = document.createElement('input');
    inputTalla.type = 'text';
    inputTalla.className = 'talla-input-reflectivo';
    inputTalla.value = talla;
    inputTalla.placeholder = 'Talla';
    inputTalla.style.cssText = 'width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; text-transform: uppercase;';
    inputTalla.readOnly = talla !== '';
    
    const inputCantidad = document.createElement('input');
    inputCantidad.type = 'number';
    inputCantidad.className = 'cantidad-input-reflectivo';
    inputCantidad.placeholder = 'Cantidad';
    inputCantidad.min = '1';
    inputCantidad.style.cssText = 'width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;';
    
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.textContent = '✕';
    btnEliminar.style.cssText = 'background: none; color: #999; border: 1px solid #ddd; padding: 4px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; width: 100%; transition: all 0.2s;';
    btnEliminar.onmouseover = function() { this.style.background = '#f0f0f0'; this.style.color = '#333'; };
    btnEliminar.onmouseout = function() { this.style.background = 'none'; this.style.color = '#999'; };
    btnEliminar.onclick = function() {
        fila.remove();
    };
    
    const td1 = document.createElement('td');
    td1.style.cssText = 'padding: 8px; border-right: 1px solid #ddd;';
    td1.appendChild(inputTalla);
    
    const td2 = document.createElement('td');
    td2.style.cssText = 'padding: 8px; border-right: 1px solid #ddd;';
    td2.appendChild(inputCantidad);
    
    const td3 = document.createElement('td');
    td3.style.cssText = 'padding: 8px; text-align: center;';
    td3.appendChild(btnEliminar);
    
    fila.appendChild(td1);
    fila.appendChild(td2);
    fila.appendChild(td3);
    
    tablaTallasBody.appendChild(fila);
}

function procesarImagenesReflectivo(archivos, prendasIndex, fila, previewContainer) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    const maxImagenes = 3;
    if (prenda.imagenes.length + archivos.length > maxImagenes) {
        Swal.fire('', `Máximo ${maxImagenes} imágenes permitidas`, 'warning');
        return;
    }
    
    archivos.forEach(archivo => {
        const reader = new FileReader();
        reader.onload = (e) => {
            prenda.imagenes.push({
                file: archivo,
                preview: e.target.result
            });
            
            renderizarImagenesReflectivo(prendasIndex, fila);
        };
        reader.readAsDataURL(archivo);
    });
}

function renderizarImagenesReflectivo(prendasIndex, fila) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    const previewContainer = fila.querySelector(`.imagenes-preview-reflectivo-${prendasIndex}`);
    previewContainer.innerHTML = '';
    
    // PRIMERO: Mostrar imágenes del PASO 2 (si existen)
    if (fila.imagenesPaso2 && Array.isArray(fila.imagenesPaso2) && fila.imagenesPaso2.length > 0) {
        fila.imagenesPaso2.forEach((imgSrc, imgIdx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
            div.innerHTML = `
                <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover;">
                <span style="position: absolute; top: 2px; right: 2px; background: rgba(76, 175, 80, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: default; font-size: 0.75rem; padding: 0; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</span>
            `;
            previewContainer.appendChild(div);
        });
    }
    
    // SEGUNDO: Mostrar imágenes agregadas por el usuario
    prenda.imagenes.forEach((img, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
        div.innerHTML = `
            <img src="${img.preview}" style="width: 100%; height: 100%; object-fit: cover;">
            <button type="button" onclick="eliminarImagenReflectivo(${prendasIndex}, ${idx})" style="position: absolute; top: 2px; right: 2px; background: rgba(244, 67, 54, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 0.85rem; padding: 0; display: flex; align-items: center; justify-content: center;">✕</button>
        `;
        previewContainer.appendChild(div);
    });
}

function eliminarImagenReflectivo(prendasIndex, imgIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (prenda) {
        prenda.imagenes.splice(imgIndex, 1);
        
        const fila = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
        if (fila) {
            renderizarImagenesReflectivo(prendasIndex, fila);
        }
    }
}

function eliminarPrendaReflectivoPaso4(prendasIndex) {
    prendas_reflectivo_paso4 = prendas_reflectivo_paso4.filter(p => p.index !== prendasIndex);
    
    const fila = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (fila) fila.remove();
    
    // Renumerar
    document.querySelectorAll('.prenda-reflectivo-item .numero-prenda').forEach((el, idx) => {
        el.textContent = idx + 1;
    });
    
    actualizarEstadoSinPrendas();
}

function actualizarEstadoSinPrendas() {
    const container = document.getElementById('prendas_reflectivo_container');
    const msgSinPrendas = document.getElementById('sin_prendas_reflectivo');
    
    if (container && container.children.length > 0) {
        msgSinPrendas.style.display = 'none';
    } else {
        msgSinPrendas.style.display = 'block';
    }
}

function capturePrendasReflectivoPaso4() {
    const prendas = [];
    
    document.querySelectorAll('.prenda-reflectivo-item').forEach((fila, idx) => {
        const tipoPrenda = fila.querySelector('.tipo-prenda-reflectivo')?.value.trim() || '';
        const descripcion = fila.querySelector('.descripcion-reflectivo')?.value.trim() || '';
        
        // OBTENER DEL OBJETO PRENDA (no del DOM) - porque están en prendas_reflectivo_paso4
        const prendasData = prendas_reflectivo_paso4[idx];
        const ubicaciones = prendasData ? (prendasData.ubicaciones || []) : [];
        const observacionesGenerales = [];
        
        // Capturar observaciones generales
        fila.querySelectorAll('.observaciones-reflectivo-lista > div').forEach(filaObs => {
            const textoInput = filaObs.querySelector('.obs-reflectivo-texto');
            const checkboxInput = filaObs.querySelector('.obs-reflectivo-check');
            const valorInput = filaObs.querySelector('.obs-reflectivo-valor');
            
            if (textoInput && textoInput.value.trim()) {
                // Si checkbox está visible y marcado
                if (checkboxInput && checkboxInput.checked) {
                    observacionesGenerales.push({
                        tipo: 'checkbox',
                        texto: textoInput.value.trim(),
                        valor: true
                    });
                } 
                // Si text input está visible
                else if (valorInput && valorInput.style.display !== 'none') {
                    observacionesGenerales.push({
                        tipo: 'valor',
                        texto: textoInput.value.trim(),
                        valor: valorInput.value.trim()
                    });
                }
                // Si solo está el texto
                else {
                    observacionesGenerales.push({
                        texto: textoInput.value.trim(),
                        valor: true
                    });
                }
            }
        });
        
        // Capturar tallas con cantidades
        const tallas = [];
        fila.querySelectorAll('.tallas-reflectivo-tabla-body tr').forEach(tr => {
            const tallaInput = tr.querySelector('.talla-input-reflectivo');
            const cantidadInput = tr.querySelector('.cantidad-input-reflectivo');
            
            if (tallaInput && cantidadInput) {
                const talla = tallaInput.value.trim();
                const cantidad = cantidadInput.value.trim();
                
                if (talla) {
                    tallas.push({
                        talla: talla,
                        cantidad: cantidad ? parseInt(cantidad) : 0
                    });
                }
            }
        });
        
        // CAPTURAR VARIACIONES
        const variaciones = {
            manga: {
                valor: fila.querySelector('.variacion-manga-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-manga-obs-reflectivo')?.value.trim() || ''
            },
            bolsillos: {
                valor: fila.querySelector('.variacion-bolsillos-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-bolsillos-obs-reflectivo')?.value.trim() || ''
            },
            broche_boton: {
                valor: fila.querySelector('.variacion-broche-valor-reflectivo')?.value.trim() || '',
                observacion: fila.querySelector('.variacion-broche-obs-reflectivo')?.value.trim() || ''
            }
        };
        
        const prenda = prendas_reflectivo_paso4.find(p => p.index === idx);
        
        prendas.push({
            tipo_prenda: tipoPrenda,
            descripcion: descripcion,
            ubicaciones: ubicaciones.map(u => ({
                ubicacion: u.ubicacion,
                descripcion: u.descripcion
            })),
            tallas: tallas,
            variaciones: variaciones,
            observaciones_generales: observacionesGenerales,
            imagenes: prenda ? prenda.imagenes : []
        });
    });
    
    return prendas;
}

// Actualizar resumen de reflectivo en paso 5 (REVISAR)
function actualizarResumenReflectivoPaso4() {
    const prendasReflectivo = capturePrendasReflectivoPaso4();
    const container = document.getElementById('resumen_reflectivo_prendas');
    
    if (!container) return;
    
    container.innerHTML = '';
    
    if (prendasReflectivo.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 15px;">-</p>';
        return;
    }
    
    prendasReflectivo.forEach((prenda, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'padding: 12px; background: #fff; border: 1px solid #dbeafe; border-radius: 6px;';
        
        let html = `<p style="margin: 0 0 10px 0; font-weight: 600; color: #0066cc; border-bottom: 2px solid #3498db; padding-bottom: 8px;">Prenda ${idx + 1}: ${prenda.tipo_prenda || '-'}</p>`;
        
        html += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>Descripción:</strong> ${prenda.descripcion || '-'}</p>`;
        
        // Mostrar ubicaciones con descripción
        if (prenda.ubicaciones.length > 0) {
            html += `<div style="margin: 8px 0; font-size: 0.9rem;"><strong>Ubicaciones:</strong>`;
            prenda.ubicaciones.forEach(ub => {
                html += `<div style="margin-left: 12px; padding: 6px; background: #f0f7ff; border-left: 3px solid #3498db; border-radius: 3px; margin-top: 4px;">
                    <strong>${ub.ubicacion}:</strong> ${ub.descripcion}
                </div>`;
            });
            html += `</div>`;
        }
        
        // Mostrar tallas con cantidades
        if (prenda.tallas.length > 0) {
            html += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>Tallas:</strong> ${prenda.tallas.map(t => `${t.talla} (${t.cantidad})`).join(', ')}</p>`;
        }
        
        // Mostrar observaciones generales
        if (prenda.observaciones_generales && prenda.observaciones_generales.length > 0) {
            html += `<div style="margin: 8px 0; font-size: 0.9rem;"><strong>Observaciones:</strong>`;
            prenda.observaciones_generales.forEach(obs => {
                const texto = obs.texto || obs;
                const valor = obs.valor || '';
                html += `<div style="margin-left: 12px; padding: 4px; font-size: 0.85rem;">• ${texto}${valor && valor !== true ? ': ' + valor : ''}</div>`;
            });
            html += `</div>`;
        }
        
        if (prenda.imagenes.length > 0) {
            html += `<p style="margin: 5px 0 8px 0; font-size: 0.9rem;"><strong>Imágenes:</strong> ${prenda.imagenes.length}</p>`;
        }
        
        div.innerHTML = html;
        
        if (prenda.imagenes.length > 0) {
            const galeriaDiv = document.createElement('div');
            galeriaDiv.style.cssText = 'display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;';
            prenda.imagenes.forEach(img => {
                const imgDiv = document.createElement('div');
                imgDiv.style.cssText = 'width: 60px; height: 60px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd;';
                imgDiv.innerHTML = `<img src="${img.preview}" style="width: 100%; height: 100%; object-fit: cover;">`;
                galeriaDiv.appendChild(imgDiv);
            });
            div.appendChild(galeriaDiv);
        }
        
        container.appendChild(div);
    });
}

// =========================================================
// FUNCIONES DE UBICACIONES REFLECTIVO CON MODAL (Paso 4)
// =========================================================

/**
 * Abre modal para agregar ubicación con descripción
 */
function abrirModalUbicacionReflectivoPaso4(prendasIndex, fila) {
    const inputSeccion = fila.querySelector('.seccion-ubicacion-reflectivo-input');
    const seccion = inputSeccion.value.trim().toUpperCase();

    if (!seccion) {
        Swal.fire('', 'Por favor escribe una sección (ej: PECHO, ESPALDA, MANGA...)', 'warning');
        return;
    }

    // Crear modal
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="modalUbicacionReflectivoPaso4-${prendasIndex}">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${seccion}</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="document.getElementById('modalUbicacionReflectivoPaso4-${prendasIndex}').remove()" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
                        <button type="button" onclick="guardarUbicacionReflectivoPaso4(${prendasIndex})" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">+</button>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #333; font-size: 0.95rem;">OBSERVACIÓN/DETALLES:</label>
                    <textarea id="descUbicacionReflectivoPaso4-${prendasIndex}" placeholder="Ej: Franja vertical de 10cm, color plateado, lado izquierdo..." style="width: 100%; padding: 0.75rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.2s ease;" onkeydown="if(event.key==='Escape') document.getElementById('modalUbicacionReflectivoPaso4-${prendasIndex}').remove()"></textarea>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Enfocar en el textarea
    setTimeout(() => {
        document.getElementById(`descUbicacionReflectivoPaso4-${prendasIndex}`).focus();
    }, 10);
}

/**
 * Guarda la ubicación y la descripción
 */
function guardarUbicacionReflectivoPaso4(prendasIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda) return;
    
    // Encontrar el fila (elemento DOM)
    const filaElement = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (!filaElement) return;
    
    const inputSeccion = filaElement.querySelector('.seccion-ubicacion-reflectivo-input');
    const seccion = inputSeccion.value.trim().toUpperCase();
    const desc = document.getElementById(`descUbicacionReflectivoPaso4-${prendasIndex}`).value.trim();
    
    if (!desc) {
        Swal.fire('', 'Por favor escribe una descripción', 'warning');
        return;
    }
    
    // Agregar a array de ubicaciones
    if (!prenda.ubicaciones) {
        prenda.ubicaciones = [];
    }
    
    // Verificar que no exista duplicada
    const existe = prenda.ubicaciones.some(u => u.ubicacion === seccion && u.descripcion === desc);
    if (existe) {
        Swal.fire('', 'Esta ubicación ya fue agregada', 'warning');
        return;
    }
    
    prenda.ubicaciones.push({
        ubicacion: seccion,
        descripcion: desc
    });
    
    // Cerrar modal
    document.getElementById(`modalUbicacionReflectivoPaso4-${prendasIndex}`).remove();
    
    // Limpiar input
    inputSeccion.value = '';
    
    // Renderizar ubicaciones
    renderizarUbicacionesReflectivoPaso4(prendasIndex);
}

/**
 * Renderiza las ubicaciones agregadas
 */
function renderizarUbicacionesReflectivoPaso4(prendasIndex) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda || !prenda.ubicaciones) return;
    
    const filaElement = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (!filaElement) return;
    
    const contenedor = filaElement.querySelector('.ubicaciones-reflectivo-agregadas');
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    
    prenda.ubicaciones.forEach((item, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem;';
        
        const ubicacionText = item.ubicacion || '';
        const descText = item.descripcion || '';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem; font-weight: 700;"> ${ubicacionText}</h4>
                    <p style="margin: 0; color: #666; font-size: 0.85rem; line-height: 1.4;">${descText}</p>
                </div>
                <button type="button" onclick="eliminarUbicacionReflectivoPaso4(${prendasIndex}, ${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 10px;">×</button>
            </div>
        `;
        contenedor.appendChild(div);
    });
    
    console.log(` Ubicaciones para prenda ${prendasIndex}: ${prenda.ubicaciones.length}`);
}

/**
 * Elimina una ubicación
 */
function eliminarUbicacionReflectivoPaso4(prendasIndex, index) {
    const prenda = prendas_reflectivo_paso4.find(p => p.index === prendasIndex);
    if (!prenda || !prenda.ubicaciones) return;
    
    prenda.ubicaciones.splice(index, 1);
    renderizarUbicacionesReflectivoPaso4(prendasIndex);
}

// =========================================================
// FUNCIONES DE OBSERVACIONES GENERALES (Paso 4)
// =========================================================

/**
 * Agrega fila de observación
 */
function agregarObservacionReflectivoPaso4(prendasIndex, fila) {
    const contenedor = fila.querySelector('.observaciones-reflectivo-lista');
    if (!contenedor) return;
    
    const filaObs = document.createElement('div');
    filaObs.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    filaObs.innerHTML = `
        <input type="text" class="obs-reflectivo-texto" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" class="obs-reflectivo-check" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" class="obs-reflectivo-valor" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        <button type="button" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(filaObs);
    
    const toggleBtn = filaObs.querySelector('.obs-toggle-btn');
    const checkboxMode = filaObs.querySelector('.obs-checkbox-mode');
    const textMode = filaObs.querySelector('.obs-text-mode');
    const deleteBtn = filaObs.querySelector('button:last-child');
    
    // Toggle entre checkbox y text
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'flex';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'flex';
            toggleBtn.style.background = '#ff9800';
        }
    });
    
    // Eliminar fila
    deleteBtn.addEventListener('click', () => filaObs.remove());
}

// =========================================================
// 8. INICIALIZACIÓN
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    cargarTiposDisponiblesPaso3();
    console.log(' PASO 3 COTIZACIÓN COMBINADA - Inicializado');
});
