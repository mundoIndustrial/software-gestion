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
        
        productosCards.forEach((card, idx) => {
            // Obtener nombre de la prenda
            const inputNombre = card.querySelector('input[name*="nombre_producto"]');
            const nombre = inputNombre ? inputNombre.value.trim() : '';
            
            if (!nombre) {
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
            }
            
            // Obtener género
            let genero = '';
            const selectGenero = card.querySelector('.talla-genero-select');
            if (selectGenero) {
                genero = selectGenero.value.trim();
            }
            
            // Obtener colores (pueden ser múltiples) - Buscar en las filas de telas
            let colores = [];
            
            // MÉTODO 1: Buscar en filas de tabla de telas con class color-input
            let colorInputs = card.querySelectorAll('input.color-input');
            
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
                        }
                    }
                });
            }
            
            // MÉTODO 3: Buscar en cualquier input que tenga name variantes[color]
            if (colores.length === 0) {
                const colorInputsVariantes = card.querySelectorAll('input[name*="[color]"]');
                
                colorInputsVariantes.forEach((input) => {
                    const color = input.value.trim();
                    if (color && !colores.includes(color)) {
                        colores.push(color);
                    }
                });
            }
            
            // Obtener imágenes de la prenda
            let imagenes = [];
            const fotosPreview = card.querySelector('.fotos-preview');
            if (fotosPreview) {
                const fotosElements = fotosPreview.querySelectorAll('img, [data-foto] img');
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
    
    // Usar directamente el modal de SweetAlert con la técnica seleccionada
    abrirModalTecnicaCombinada([tipo]);
}

// =========================================================
// 5. MODAL TÉCNICAS COMBINADAS (Similar a logo-cotizacion)
// =========================================================

function abrirModalTecnicaCombinada(tecnicas) {
    // Abrir directamente el modal de "Datos iguales" sin mostrar el selector
    abrirModalDatosIgualesPaso3(tecnicas);
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
        maxHeight: '70vh',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 60vh; overflow-y: auto;">
                
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
                
                <!-- LOGOS COMPARTIDOS -->
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f0f7ff; border-radius: 4px; border: 1px solid #dbeafe;">
                        <input type="checkbox" id="dUsarLogoCompartido" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="dUsarLogoCompartido" style="font-weight: 600; color: #333; cursor: pointer; margin: 0;">Usar el mismo logo en múltiples técnicas</label>
                    </div>
                    <div id="dLogosCompartidosP3" style="margin-top: 12px; display: none; display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente cuando está checked -->
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
                // UBICACIÓN CON MÚLTIPLES VALORES (igual a logo individual)
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;';
                labelUbicacion.textContent = tecnica.nombre + ' - Ubicaciones';
                
                const inputDiv = document.createElement('div');
                inputDiv.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacionInput-p3-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                const btnAgregarUbicacion = document.createElement('button');
                btnAgregarUbicacion.type = 'button';
                btnAgregarUbicacion.textContent = '+ Agregar';
                btnAgregarUbicacion.style.cssText = 'background: #0066cc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap;';
                btnAgregarUbicacion.className = 'dBtnAgregarUbicacion-p3-' + idx;
                
                inputDiv.appendChild(inputUbicacion);
                inputDiv.appendChild(btnAgregarUbicacion);
                
                const ubicacionesContainer = document.createElement('div');
                ubicacionesContainer.className = 'dUbicacionesList-p3-' + idx;
                ubicacionesContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputDiv);
                divUbicacion.appendChild(ubicacionesContainer);
                
                ubicacionesPorTecnicaDiv.appendChild(divUbicacion);
                
                // Manejar agregar ubicaciones
                let ubicacionesTecnica = [];
                btnAgregarUbicacion.addEventListener('click', (e) => {
                    e.preventDefault();
                    const ubicacion = inputUbicacion.value.trim().toUpperCase();
                    
                    if (!ubicacion) {
                        Swal.showValidationMessage('Escribe una ubicación primero');
                        return;
                    }
                    
                    if (ubicacionesTecnica.includes(ubicacion)) {
                        Swal.showValidationMessage('Esta ubicación ya fue agregada');
                        return;
                    }
                    
                    ubicacionesTecnica.push(ubicacion);
                    inputUbicacion.value = '';
                    
                    // Actualizar vista
                    actualizarUbicacionesList();
                    inputUbicacion.focus();
                });
                
                inputUbicacion.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnAgregarUbicacion.click();
                    }
                });
                
                function actualizarUbicacionesList() {
                    ubicacionesContainer.innerHTML = ubicacionesTecnica.map((ub, i) => `
                        <span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                            ${ub}
                            <button type="button" data-ubicacion-idx="${i}" style="background: none; border: none; color: #0369a1; cursor: pointer; font-weight: 700; padding: 0; margin-left: 0.25rem;">✕</button>
                        </span>
                    `).join('');
                    
                    // Agregar event listeners a los botones de eliminar
                    ubicacionesContainer.querySelectorAll('button[data-ubicacion-idx]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const idxAEliminar = parseInt(btn.getAttribute('data-ubicacion-idx'));
                            ubicacionesTecnica.splice(idxAEliminar, 1);
                            actualizarUbicacionesList();
                        });
                    });
                }
                
                // Guardar referencia global para acceso en preConfirm
                window['dUbicacionesTecnicaP3' + idx] = ubicacionesTecnica;
                
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
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                        
                        // Manejar tanto URLs como Blobs
                        if (typeof archivo === 'object' && archivo.src && archivo.type === 'url') {
                            // Es una URL del Paso 2
                            img.src = archivo.src;
                            preview.appendChild(img);
                            preview.appendChild(btnEliminar);
                            previewContainer.appendChild(preview);
                        } else if (archivo instanceof Blob) {
                            // Es un Blob (archivo nuevo)
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                img.src = e.target.result;
                                preview.appendChild(img);
                                preview.appendChild(btnEliminar);
                                previewContainer.appendChild(preview);
                            };
                            reader.readAsDataURL(archivo);
                        }
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
            
            // ============================================================
            // MANEJO DE LOGOS COMPARTIDOS
            // ============================================================
            const checkboxLogoCompartido = document.getElementById('dUsarLogoCompartido');
            const logosCompartidosContainer = document.getElementById('dLogosCompartidosP3');
            const imagenesCompartidas = {}; // { "TECNICA1-TECNICA2": [archivos] }
            
            checkboxLogoCompartido.addEventListener('change', (e) => {
                if (e.target.checked) {
                    logosCompartidosContainer.style.display = 'grid';
                    // Crear botón para agregar logos compartidos
                    logosCompartidosContainer.innerHTML = '';
                    const btnAgregarLogoCompartido = document.createElement('button');
                    btnAgregarLogoCompartido.type = 'button';
                    btnAgregarLogoCompartido.textContent = '+ Agregar Logo Compartido';
                    btnAgregarLogoCompartido.style.cssText = 'background: #1e40af; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: 600;';
                    
                    btnAgregarLogoCompartido.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        abrirModalSeleccionarTecnicasCompartidas(tecnicas, imagenesCompartidas);
                    });
                    
                    logosCompartidosContainer.appendChild(btnAgregarLogoCompartido);
                } else {
                    logosCompartidosContainer.style.display = 'none';
                    logosCompartidosContainer.innerHTML = '';
                    // Limpiar imágenes compartidas
                    for (let key in imagenesCompartidas) {
                        delete imagenesCompartidas[key];
                    }
                }
            });
            
            // Guardar referencia global para preConfirm
            window.imagenesCompartidasP3 = imagenesCompartidas;
            
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
            if (!selectPrenda) {
                Swal.showValidationMessage('Error: elemento de prenda no encontrado');
                return false;
            }
            
            const selectedOption = selectPrenda.options[selectPrenda.selectedIndex];
            const nombrePrendaBase = selectPrenda.value.trim().toUpperCase();
            
            if (!nombrePrendaBase || nombrePrendaBase === '' || selectPrenda.selectedIndex === 0) {
                Swal.showValidationMessage(' Debes seleccionar una prenda del dropdown (Paso 2)');
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
            
            // Validar ubicaciones (múltiples por técnica)
            const ubicacionesPorTecnica = {};
            let valido = true;
            
            window.tecnicasCombinadas.forEach((tecnica, idx) => {
                // Obtener ubicaciones desde la referencia global que se crea en didOpen
                const ubicacionesTecnica = window['dUbicacionesTecnicaP3' + idx] || [];
                
                if (!ubicacionesTecnica || ubicacionesTecnica.length === 0) {
                    Swal.showValidationMessage(`Agrega al menos una ubicación para ${tecnica.nombre}`);
                    valido = false;
                    return;
                }
                ubicacionesPorTecnica[idx] = ubicacionesTecnica;
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
                imagenesAgregadas: window.imagenesAgregadasPorTecnicaP3,
                imagenesCompartidas: window.imagenesCompartidasP3 || {}
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            guardarTecnicaCombinada(result.value, window.tecnicasCombinadas);
        }
    });
}

// =========================================================
// MODAL PARA SELECCIONAR TÉCNICAS COMPARTIDAS (HTML PERSONALIZADO)
// =========================================================

function abrirModalSeleccionarTecnicasCompartidas(tecnicas, imagenesCompartidas) {
    const checkboxesTecnicas = tecnicas.map((tecnica, idx) => `
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer;">
            <input type="checkbox" class="dCheckTecnicaCompartida" data-tecnica-idx="${idx}" data-tecnica-nombre="${tecnica.nombre}" style="width: 18px; height: 18px; cursor: pointer;">
            <label style="flex: 1; cursor: pointer; margin: 0; font-weight: 500; color: #333;">${tecnica.nombre}</label>
        </div>
    `).join('');
    
    // Crear modal HTML flotante (no es Swal, es un div HTML puro)
    const modalHTML = `
        <div id="dModalLogosCompartidosEmergente" style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            max-height: 80vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 2000;
            padding: 30px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-y: auto;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #333;">Logos Compartidos por Técnicas</h2>
                <button type="button" id="dBtnCerrarModalCompartido" style="
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #666;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">✕</button>
            </div>
            
            <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: #666;">Selecciona las técnicas que comparten el mismo logo:</p>
            
            <div style="display: grid; gap: 10px; margin-bottom: 20px; max-height: 200px; overflow-y: auto;">
                ${checkboxesTecnicas}
            </div>
            
            <div style="border-top: 1px solid #eee; padding-top: 15px; margin-bottom: 20px;">
                <label style="font-weight: 600; display: block; margin-bottom: 10px; color: #333;">Imagen del Logo:</label>
                <div class="dImagenesCompartidasDropzone" style="
                    border: 2px dashed #ddd;
                    border-radius: 6px;
                    padding: 20px;
                    text-align: center;
                    background: #fafafa;
                    cursor: pointer;
                    transition: all 0.2s;
                ">
                    <div style="margin-bottom: 8px; font-size: 1.3rem;">📁</div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imagen aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar</p>
                    <input type="file" class="dImagenCompartidasInput" accept="image/*" style="display: none;" />
                </div>
                <div class="dImagenCompartidasPreview" style="margin-top: 12px; display: flex; justify-content: center;">
                    <!-- Preview de imagen -->
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button type="button" id="dBtnCancelarCompartido" style="
                    background: #f0f0f0;
                    color: #333;
                    border: 1px solid #ddd;
                    padding: 10px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.9rem;
                ">Cancelar</button>
                <button type="button" id="dBtnGuardarCompartido" style="
                    background: #1e40af;
                    color: white;
                    border: none;
                    padding: 10px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.9rem;
                ">Guardar Logo Compartido</button>
            </div>
        </div>
        
        <!-- Backdrop oscuro -->
        <div id="dBackdropLogosCompartidos" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1999;
        "></div>
    `;
    
    // Insertar en el DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modalElement = document.getElementById('dModalLogosCompartidosEmergente');
    const backdropElement = document.getElementById('dBackdropLogosCompartidos');
    const btnCerrar = document.getElementById('dBtnCerrarModalCompartido');
    const btnCancelar = document.getElementById('dBtnCancelarCompartido');
    const btnGuardar = document.getElementById('dBtnGuardarCompartido');
    const dropzone = modalElement.querySelector('.dImagenesCompartidasDropzone');
    const inputFile = modalElement.querySelector('.dImagenCompartidasInput');
    const previewDiv = modalElement.querySelector('.dImagenCompartidasPreview');
    const tecnicasCheckboxes = modalElement.querySelectorAll('.dCheckTecnicaCompartida');
    
    let imagenSeleccionada = null;
    window.imagenCompartidasActual = null;
    
    // Setup dropzone
    dropzone.addEventListener('click', () => inputFile.click());
    
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
        procesarArchivoCompartido(Array.from(e.dataTransfer.files));
    });
    
    inputFile.addEventListener('change', (e) => {
        procesarArchivoCompartido(Array.from(e.target.files));
    });
    
    function procesarArchivoCompartido(archivos) {
        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
        if (imagenes.length > 0) {
            imagenSeleccionada = imagenes[0];
            window.imagenCompartidasActual = imagenSeleccionada;
            mostrarPreviewCompartido();
        }
    }
    
    function mostrarPreviewCompartido() {
        if (!imagenSeleccionada) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            previewDiv.innerHTML = `
                <div style="position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 2px solid #1e40af;">
                    <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            `;
        };
        reader.readAsDataURL(imagenSeleccionada);
    }
    
    function cerrarModal() {
        modalElement.remove();
        backdropElement.remove();
    }
    
    function guardarYCerrar() {
        const tecnicasSeleccionadas = Array.from(tecnicasCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-tecnica-nombre'));
        
        if (tecnicasSeleccionadas.length < 2) {
            alert('Selecciona al menos 2 técnicas');
            return;
        }
        
        if (!window.imagenCompartidasActual) {
            alert('Sube una imagen para las técnicas compartidas');
            return;
        }
        
        // Procesar el resultado
        const clave = tecnicasSeleccionadas.sort().join('-');
        window.imagenesCompartidasP3[clave] = window.imagenCompartidasActual;
        
        // ACTUALIZAR DINÁMICAMENTE el formulario de agregar prenda
        actualizarSeccionImagenesConLogosCompartidos(tecnicasSeleccionadas, window.imagenesCompartidasP3);
        
        // Mostrar en el contenedor de logos compartidos
        mostrarLogosCompartidosAgregados(window.imagenesCompartidasP3, tecnicasSeleccionadas);
        
        cerrarModal();
    }
    
    // Event listeners
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    btnGuardar.addEventListener('click', guardarYCerrar);
    backdropElement.addEventListener('click', cerrarModal);
}

// =========================================================
// ACTUALIZAR SECCIÓN DE IMÁGENES CON LOGOS COMPARTIDOS
// =========================================================

function actualizarSeccionImagenesConLogosCompartidos(tecnicasCompartidas, imagenesCompartidas) {
    const imagenesPorTecnicaDiv = document.getElementById('dImagenesPorTecnicaP3');
    if (!imagenesPorTecnicaDiv) return;
    
    const clave = tecnicasCompartidas.sort().join('-');
    const imagenCompartida = imagenesCompartidas[clave];
    if (!imagenCompartida) return;
    
    // Obtener todas las técnicas del modal actual
    const tecnicas = window.tecnicasCombinadas || [];
    
    // Ocultar/eliminar dropzones de las técnicas que comparten logo
    tecnicasCompartidas.forEach(nombreTecnica => {
        // Buscar el índice de esta técnica
        const tecnicaIdx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
        if (tecnicaIdx >= 0) {
            const dropzone = imagenesPorTecnicaDiv.querySelector(`.dImagenesDropzone-p3-${tecnicaIdx}`);
            const preview = imagenesPorTecnicaDiv.querySelector(`.dImagenesPreview-p3-${tecnicaIdx}`);
            
            // Ocultar el contenedor de la dropzone
            if (dropzone) {
                const container = dropzone.closest('[data-tecnica-container]') || dropzone.parentElement;
                if (container) container.style.display = 'none';
            }
            
            // Ocultar el contenedor del preview
            if (preview) {
                const container = preview.closest('[data-tecnica-container]') || preview.parentElement;
                if (container) container.style.display = 'none';
            }
        }
    });
    
    // Crear y agregar sección de imágenes compartidas
    const divImagenCompartida = document.createElement('div');
    divImagenCompartida.className = 'dImagenCompartidaSeccion';
    divImagenCompartida.style.cssText = 'padding: 12px; background: #e0f2fe; border: 2px solid #0284c7; border-radius: 4px; margin-bottom: 16px;';
    
    divImagenCompartida.innerHTML = `
        <label style="font-weight: 600; font-size: 0.95rem; color: #0c4a6e; display: block; margin-bottom: 12px;">
            ✓ Imagen Compartida: ${tecnicasCompartidas.join(' + ')}
        </label>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div class="dImagenCompartidaPreview-${clave}" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <!-- Preview de imagen compartida -->
            </div>
            <button type="button" class="dBtnEliminarCompartida-${clave}" style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap; margin-top: auto;">
                ✕ Eliminar
            </button>
        </div>
    `;
    
    imagenesPorTecnicaDiv.insertBefore(divImagenCompartida, imagenesPorTecnicaDiv.firstChild);
    
    // Mostrar preview de la imagen compartida
    const previewContainer = divImagenCompartida.querySelector(`.dImagenCompartidaPreview-${clave}`);
    const reader = new FileReader();
    reader.onload = (e) => {
        previewContainer.innerHTML = `
            <div style="position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 2px solid #0284c7;">
                <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        `;
    };
    reader.readAsDataURL(imagenCompartida);
    
    // Botón para eliminar logo compartido
    const btnEliminar = divImagenCompartida.querySelector(`.dBtnEliminarCompartida-${clave}`);
    btnEliminar.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Eliminar de imagenesCompartidas
        delete imagenesCompartidas[clave];
        
        // Mostrar nuevamente los dropzones de las técnicas
        tecnicasCompartidas.forEach(nombreTecnica => {
            const tecnicaIdx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
            if (tecnicaIdx >= 0) {
                const dropzoneParent = imagenesPorTecnicaDiv.querySelector(`.dImagenesDropzone-p3-${tecnicaIdx}`)?.parentElement;
                const previewParent = imagenesPorTecnicaDiv.querySelector(`.dImagenesPreview-p3-${tecnicaIdx}`)?.parentElement;
                
                if (dropzoneParent) dropzoneParent.style.display = 'block';
                if (previewParent) previewParent.style.display = 'block';
            }
        });
        
        // Eliminar la sección compartida
        divImagenCompartida.remove();
        
        // Actualizar referencia global
        window.imagenesCompartidasP3 = imagenesCompartidas;
    });
    
    // Actualizar referencia global
    window.imagenesCompartidasP3 = imagenesCompartidas;
}

function mostrarLogosCompartidosAgregados(imagenesCompartidas, tecnicasNuevas) {
    const logosCompartidosContainer = document.getElementById('dLogosCompartidosP3');
    if (!logosCompartidosContainer) return;
    
    // Limpiar y reconstruir
    logosCompartidosContainer.innerHTML = '';
    
    const btnAgregarLogoCompartido = document.createElement('button');
    btnAgregarLogoCompartido.type = 'button';
    btnAgregarLogoCompartido.textContent = '+ Agregar Logo Compartido';
    btnAgregarLogoCompartido.style.cssText = 'background: #1e40af; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: 600; grid-column: 1 / -1;';
    
    logosCompartidosContainer.appendChild(btnAgregarLogoCompartido);
    
    // Mostrar cada logo compartido
    for (let clave in imagenesCompartidas) {
        const tecnicas = clave.split('-');
        const imagen = imagenesCompartidas[clave];
        
        const divLogo = document.createElement('div');
        divLogo.style.cssText = 'padding: 12px; background: #f0f7ff; border: 1px solid #dbeafe; border-radius: 4px; display: flex; gap: 12px; align-items: flex-start;';
        
        const img = document.createElement('img');
        img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;';
        
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.readAsDataURL(imagen);
        
        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'flex: 1;';
        infoDiv.innerHTML = `
            <p style="margin: 0 0 8px 0; font-weight: 600; color: #333; font-size: 0.9rem;">
                IMAGEN-${tecnicas.join('-')}
            </p>
            <p style="margin: 0; font-size: 0.8rem; color: #666;">
                ${tecnicas.join(' + ')}
            </p>
        `;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = '✕';
        btnEliminar.style.cssText = 'background: #f44336; color: white; border: none; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; font-weight: 600; flex-shrink: 0;';
        btnEliminar.addEventListener('click', () => {
            delete imagenesCompartidas[clave];
            mostrarLogosCompartidosAgregados(imagenesCompartidas, []);
        });
        
        divLogo.appendChild(img);
        divLogo.appendChild(infoDiv);
        divLogo.appendChild(btnEliminar);
        
        logosCompartidosContainer.appendChild(divLogo);
    }
    
    // Re-agregar botón de agregar
    logosCompartidosContainer.appendChild(btnAgregarLogoCompartido);
    
    // Event listener para nuevos botones
    const nuevoBtnAgregar = logosCompartidosContainer.querySelector('button:last-child');
    nuevoBtnAgregar.addEventListener('click', (e) => {
        e.preventDefault();
        abrirModalSeleccionarTecnicasCompartidas(window.tecnicasCombinadas, imagenesCompartidas);
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
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Ubicaciones:</label>
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" id="dUbicacionInputDifP3" placeholder="Ej: Pecho, Espalda, Manga" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <button type="button" id="dBtnAgregarUbicacionDifP3" style="background: #0066cc; color: white; border: none; padding: 10px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap;">+ Agregar</button>
                    </div>
                    <div id="dUbicacionesListDifP3" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;"></div>
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
        didOpen: (modal) => {
            const inputUbicacionDif = document.getElementById('dUbicacionInputDifP3');
            const btnAgregarDif = document.getElementById('dBtnAgregarUbicacionDifP3');
            const ubicacionesListDif = document.getElementById('dUbicacionesListDifP3');
            
            let ubicacionesDif = [];
            
            btnAgregarDif.addEventListener('click', (e) => {
                e.preventDefault();
                const ubicacion = inputUbicacionDif.value.trim().toUpperCase();
                
                if (!ubicacion) {
                    Swal.showValidationMessage('Escribe una ubicación primero');
                    return;
                }
                
                if (ubicacionesDif.includes(ubicacion)) {
                    Swal.showValidationMessage('Esta ubicación ya fue agregada');
                    return;
                }
                
                ubicacionesDif.push(ubicacion);
                inputUbicacionDif.value = '';
                
                actualizarUbicacionesListDif();
                inputUbicacionDif.focus();
            });
            
            inputUbicacionDif.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnAgregarDif.click();
                }
            });
            
            function actualizarUbicacionesListDif() {
                ubicacionesListDif.innerHTML = ubicacionesDif.map((ub, i) => `
                    <span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                        ${ub}
                        <button type="button" data-ubicacion-idx="${i}" style="background: none; border: none; color: #0369a1; cursor: pointer; font-weight: 700; padding: 0; margin-left: 0.25rem;">✕</button>
                    </span>
                `).join('');
                
                ubicacionesListDif.querySelectorAll('button[data-ubicacion-idx]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const idxAEliminar = parseInt(btn.getAttribute('data-ubicacion-idx'));
                        ubicacionesDif.splice(idxAEliminar, 1);
                        actualizarUbicacionesListDif();
                    });
                });
            }
            
            // Guardar referencia para preConfirm
            window.ubicacionesDifActuales = ubicacionesDif;
        },
        preConfirm: () => {
            const nombre = document.getElementById('dNombrePrendaDifP3').value.trim();
            const ubicacionesDif = window.ubicacionesDifActuales || [];
            
            if (!nombre || ubicacionesDif.length === 0) {
                Swal.showValidationMessage('Completa prenda y agrega al menos una ubicación');
                return false;
            }
            
            return {
                nombre_prenda: nombre.toUpperCase(),
                ubicaciones: ubicacionesDif,
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
    //  VALIDACIÓN: Verificar que hay información escrita en ubicaciones, tallas y/o imágenes
    let tieneInformacionValida = false;
    
    // Validar ubicaciones
    const tieneUbicaciones = datosForm.ubicacionesPorTecnica && 
                             Object.keys(datosForm.ubicacionesPorTecnica).some(key => {
                                 const ubicaciones = datosForm.ubicacionesPorTecnica[key];
                                 return Array.isArray(ubicaciones) && ubicaciones.some(u => u.trim());
                             });
    
    // Validar tallas
    const tieneTallas = datosForm.tallas && datosForm.tallas.length > 0;
    
    // Validar imágenes
    let tieneImagenes = false;
    if (datosForm.imagenesAgregadas) {
        for (let idx in datosForm.imagenesAgregadas) {
            if (Array.isArray(datosForm.imagenesAgregadas[idx]) && 
                datosForm.imagenesAgregadas[idx].length > 0) {
                tieneImagenes = true;
                break;
            }
        }
    }
    
    // La información es válida si hay ubicaciones Y (tallas O imágenes)
    tieneInformacionValida = tieneUbicaciones && (tieneTallas || tieneImagenes);
    
    console.log('🔍 VALIDACIÓN PASO 3 - Información requerida:', {
        tieneUbicaciones,
        tieneTallas,
        tieneImagenes,
        tieneInformacionValida
    });
    
    //  SI NO HAY INFORMACIÓN VÁLIDA, MOSTRAR ADVERTENCIA Y NO GUARDAR
    if (!tieneInformacionValida) {
        Swal.fire({
            title: ' Información incompleta en PASO 3',
            html: `
                <div style="text-align: left; margin: 15px 0;">
                    <p style="margin: 0 0 12px 0; font-size: 0.95rem; color: #666;">
                        Para crear una cotización de logo o reflectivo, debes completar:
                    </p>
                    <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 4px; margin: 10px 0;">
                        <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #92400e; font-weight: 600;">
                             Requerido:
                        </p>
                        <p style="margin: 0; font-size: 0.85rem; color: #78350f;">
                            ✓ Al menos una <strong>ubicación</strong> (ej: Pecho, Espalda)<br>
                            ✓ <strong>Tallas</strong> Y/O <strong>Imágenes</strong>
                        </p>
                    </div>
                    <p style="margin: 12px 0 0 0; font-size: 0.85rem; color: #666;">
                        Si solo deseas usar el PASO 2 (prendas comunes), simplemente no agregues datos al PASO 3.
                    </p>
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: '✓ Entendido'
        });
        return; // No guardar ni cerrar modal
    }
    
    // Validar y limpiar duplicados de técnicas
    const tecnicasUnicas = [];
    const tecnicasYaProcesadas = new Set();
    
    tecnicas.forEach((tecnica) => {
        const key = `${tecnica.id}-${tecnica.nombre}`;
        if (!tecnicasYaProcesadas.has(key)) {
            tecnicasUnicas.push(tecnica);
            tecnicasYaProcesadas.add(key);
        }
    });
    
    console.log(' Técnicas únicas a procesar:', tecnicasUnicas.length);
    
    // Guardar una prenda por técnica con sus imágenes y variaciones
    tecnicasUnicas.forEach((tecnica, idx) => {
        // CAPTURAR IMÁGENES (tanto del PASO 2 como nuevas del PASO 3 como imágenes compartidas)
        const imagenesCapturadas = [];
        
        // Imágenes específicas por técnica
        if (datosForm.imagenesAgregadas && datosForm.imagenesAgregadas[idx] && Array.isArray(datosForm.imagenesAgregadas[idx])) {
            datosForm.imagenesAgregadas[idx].forEach(archivo => {
                // Si es URL del PASO 2
                if (typeof archivo === 'object' && archivo.src && archivo.type === 'url') {
                    imagenesCapturadas.push({
                        ruta: archivo.src,
                        tipo: 'paso2'  // Indica que viene del PASO 2
                    });
                }
                // Si es Blob/File del PASO 3
                else if (archivo instanceof Blob || archivo instanceof File) {
                    imagenesCapturadas.push({
                        file: archivo,
                        tipo: 'paso3'  // Indica que fue agregada en PASO 3
                    });
                }
            });
        }
        
        // Imágenes compartidas - buscar si esta técnica está en alguna combinación compartida
        const tecnicaNombre = tecnica.nombre;
        if (datosForm.imagenesCompartidas) {
            for (let clave in datosForm.imagenesCompartidas) {
                const tecnicasEnClave = clave.split('-');
                if (tecnicasEnClave.includes(tecnicaNombre)) {
                    const imagenCompartida = datosForm.imagenesCompartidas[clave];
                    imagenesCapturadas.push({
                        file: imagenCompartida,
                        tipo: 'paso3',
                        nombreCompartido: `IMAGEN-${clave}`,  // Nombre especial para imágenes compartidas
                        tecnicasCompartidas: tecnicasEnClave  // Array con los nombres de técnicas que la comparten
                    });
                }
            }
        }
        
        const nuevaTecnica = {
            tipo: tecnica.nombre,
            tipo_logo: {
                id: tecnica.id,
                nombre: tecnica.nombre
            },
            prendas: [{
                nombre_prenda: datosForm.nombre_prenda,
                ubicaciones: datosForm.ubicacionesPorTecnica[idx] || [],
                talla_cantidad: datosForm.tallas.map(t => ({ talla: t.talla, cantidad: t.cantidad })),
                observaciones: datosForm.observaciones,
                variaciones_prenda: datosForm.variaciones_prenda || {},
                imagenes: imagenesCapturadas,  // Cambiar de imagenes_files a imagenes con metadata
                cantidad: 1
            }],
            observacionesGenerales: ''
        };
        
        if (!window.tecnicasAgregadasPaso3) {
            window.tecnicasAgregadasPaso3 = [];
        }
        
        const tecnicaExistente = window.tecnicasAgregadasPaso3.find(t => t.tipo_logo.nombre === tecnica.nombre);
        if (tecnicaExistente) {
            // Agregar las nuevas prendas al array existente, no reemplazar
            if (Array.isArray(tecnicaExistente.prendas)) {
                tecnicaExistente.prendas = [...tecnicaExistente.prendas, ...nuevaTecnica.prendas];
            } else {
                tecnicaExistente.prendas = nuevaTecnica.prendas;
            }
            console.log(` Prendas agregadas a técnica existente "${tecnica.nombre}". Total prendas: ${tecnicaExistente.prendas.length}`);
        } else {
            window.tecnicasAgregadasPaso3.push(nuevaTecnica);
            console.log(` Nueva técnica agregada: "${tecnica.nombre}"`);
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
            const nombrePrenda = prenda.nombre_prenda || 'SIN NOMBRE';
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: prenda.observaciones,
                    talla_cantidad: prenda.talla_cantidad || [],
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
            
            // Procesar imágenes (pueden venir en dos formatos: antiguo imagenes_files o nuevo imagenes)
            let imagenesAoProcesar = [];
            
            // Formato antiguo (imagenes_files)
            if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                imagenesAoProcesar = prenda.imagenes_files;
            }
            // Formato nuevo (imagenes con {ruta, tipo} o {file, tipo})
            else if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
                imagenesAoProcesar = prenda.imagenes;
            }
            
            if (imagenesAoProcesar.length > 0) {
                imagenesAoProcesar.forEach(imagen => {
                    imagenesParaCargar.push({
                        imagen: imagen,
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
        
        // HEADER CON NOMBRE, TÉCNICAS Y BOTONES
        let headerHTML = '<div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 1rem; border-bottom: 1px solid #ddd;">';
        
        // Nombre de prenda en el header
        headerHTML += '<div style="margin-bottom: 0.8rem;">';
        headerHTML += `<h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: white;">${nombrePrenda}</h3>`;
        headerHTML += '</div>';
        
        // Contenedor para técnicas y botones
        headerHTML += '<div style="display: flex; justify-content: space-between; align-items: flex-start;">';
        
        // Sección de técnicas
        headerHTML += '<div style="flex: 1;">';
        headerHTML += '<h4 style="margin: 0 0 0.5rem 0; font-size: 0.85rem; opacity: 0.9;">Técnica(s)</h4>';
        headerHTML += '<div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">';
        
        datosPrenda.tecnicas.forEach(t => {
            const colorsTec = {
                'BORDADO': '#ec4899',
                'DTF': '#1e40af',
                'ESTAMPADO': '#f97316',
                'SUBLIMADO': '#06b6d4'
            };
            const tecnicaNombre = t.tipo_logo ? t.tipo_logo.nombre : t.tipo;
            const color = colorsTec[tecnicaNombre] || '#3b82f6';
            
            headerHTML += `
                <span style="
                    background: ${color};
                    color: white;
                    padding: 0.4rem 0.8rem;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 0.85rem;
                ">
                    ${tecnicaNombre}
                </span>
            `;
        });
        
        headerHTML += '</div></div>';
        
        // Botones de acción
        headerHTML += '<div style="display: flex; gap: 0.6rem;">';
        headerHTML += `
            <button type="button" class="btn-editar-prenda" onclick="abrirModalEditarTecnicaPaso3('${nombrePrenda}')" style="
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
                
            </button>
            <button type="button" class="btn-eliminar-prenda" onclick="eliminarTecnicaPaso3('${nombrePrenda}')" style="
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
        if (datosPrenda.talla_cantidad && datosPrenda.talla_cantidad.length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; display: block; margin-bottom: 0.6rem;">
                         Tallas:
                    </span>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
            `;
            
            datosPrenda.talla_cantidad.forEach(t => {
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
        if (!tarjeta) return;
        
        const imagenesMaps = prendasMap[imgData.nombrePrenda].imagenes;
        const imagen = imgData.imagen;
        
        // CASO 1: URL del PASO 2 (antiguo formato - string directo)
        if (typeof imagen === 'string') {
            imagenesMaps.push({
                data: imagen,
                tecnica: imgData.tecnica
            });
            
            actualizarGridImagenes(tarjeta, imagenesMaps);
        }
        // CASO 2: Objeto con {ruta, tipo} - URL del PASO 2 (nuevo formato)
        else if (typeof imagen === 'object' && imagen.ruta && imagen.tipo === 'paso2') {
            imagenesMaps.push({
                data: imagen.ruta,
                tecnica: imgData.tecnica
            });
            
            actualizarGridImagenes(tarjeta, imagenesMaps);
        }
        // CASO 3: Objeto con {file, tipo, nombreCompartido} - File del PASO 3 con imagen compartida (nuevo formato)
        else if (typeof imagen === 'object' && imagen.file && imagen.tipo === 'paso3' && imagen.nombreCompartido && (imagen.file instanceof Blob || imagen.file instanceof File)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenesMaps.push({
                    data: e.target.result,
                    tecnica: imagen.nombreCompartido  // Usar el nombre compartido en lugar del nombre de técnica
                });
                
                actualizarGridImagenes(tarjeta, imagenesMaps);
            };
            reader.readAsDataURL(imagen.file);
        }
        // CASO 4: Objeto con {file, tipo} - File del PASO 3 (nuevo formato)
        else if (typeof imagen === 'object' && imagen.file && imagen.tipo === 'paso3' && (imagen.file instanceof Blob || imagen.file instanceof File)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenesMaps.push({
                    data: e.target.result,
                    tecnica: imgData.tecnica
                });
                
                actualizarGridImagenes(tarjeta, imagenesMaps);
            };
            reader.readAsDataURL(imagen.file);
        }
        // CASO 5: Blob/File directo (Backward compatibility)
        else if (imagen instanceof Blob || imagen instanceof File) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenesMaps.push({
                    data: e.target.result,
                    tecnica: imgData.tecnica
                });
                
                actualizarGridImagenes(tarjeta, imagenesMaps);
            };
            reader.readAsDataURL(imagen);
        }
    });
    
    // Función auxiliar para actualizar el grid de imágenes
    function actualizarGridImagenes(tarjeta, imagenesMaps) {
        const imgSection = tarjeta.querySelector('.imagenes-section');
        if (imgSection) {
            imgSection.style.display = 'block';
            const grid = imgSection.querySelector('.imagenes-grid');
            if (grid) {
                // Deduplicar imágenes compartidas: si una imagen compartida ya está, no la duplicar
                const imagenesUnicas = [];
                const imagenesVistas = new Set();
                
                imagenesMaps.forEach((img) => {
                    // Si es una imagen compartida, solo incluirla una vez
                    if (img.tecnica.startsWith('IMAGEN-')) {
                        if (!imagenesVistas.has(img.data)) {
                            imagenesUnicas.push(img);
                            imagenesVistas.add(img.data);
                        }
                    } else {
                        imagenesUnicas.push(img);
                    }
                });
                
                grid.innerHTML = imagenesUnicas.map((img, idx) => `
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="position: relative; border-radius: 3px; overflow: hidden; border: 1px solid #1e40af; cursor: pointer; width: 100%; aspect-ratio: 1;" ondblclick="abrirModalImagenPrendaConIndice('${img.data}', ${idx})">
                            <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover; min-height: 60px;" alt="Imagen prenda">
                        </div>
                        <div style="margin-top: 8px; text-align: center; font-size: 0.75rem; font-weight: 600; color: #1e40af; word-break: break-word; width: 100%;">
                            ${img.tecnica}
                        </div>
                    </div>
                `).join('');
            }
        }
    }
}

function abrirModalEditarTecnicaPaso3(nombrePrenda) {
    // Encontrar los datos de la prenda a editar
    console.log('🔍 Buscando prenda:', nombrePrenda);
    console.log('📦 tecnicasAgregadasPaso3:', window.tecnicasAgregadasPaso3);
    
    if (!window.tecnicasAgregadasPaso3) {
        console.error(' No hay tecnicas agregadas');
        return;
    }
    
    let datosPrendaActual = null;
    let tecnicasConPrenda = [];
    
    window.tecnicasAgregadasPaso3.forEach(tecnicaData => {
        console.log('🔎 Revisando tecnica:', tecnicaData);
        if (tecnicaData.prendas) {
            tecnicaData.prendas.forEach(p => {
                console.log('  Prenda encontrada:', p.nombre_prenda, '- Buscando:', nombrePrenda);
            });
            const prenda = tecnicaData.prendas.find(p => p.nombre_prenda === nombrePrenda);
            if (prenda) {
                console.log(' Prenda encontrada!', prenda);
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
        console.error(' No se encontraron datos de la prenda');
        console.log('datosPrendaActual:', datosPrendaActual);
        console.log('tecnicasConPrenda:', tecnicasConPrenda);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontraron datos de la prenda. Intente nuevamente.',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    Swal.fire({
        title: 'Editar Prenda',
        width: '750px',
        maxHeight: '70vh',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 60vh; overflow-y: auto;">
                
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
                labelUbicacion.style.cssText = 'font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;';
                const tecnicaNombreInfo = tecnicaInfo.tipo_logo ? tecnicaInfo.tipo_logo.nombre : tecnicaInfo.tipo;
                labelUbicacion.textContent = tecnicaNombreInfo + ' - Ubicaciones';
                
                const inputDiv = document.createElement('div');
                inputDiv.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacionInputEditar-p3-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                const btnAgregarUbicacion = document.createElement('button');
                btnAgregarUbicacion.type = 'button';
                btnAgregarUbicacion.textContent = '+ Agregar';
                btnAgregarUbicacion.style.cssText = 'background: #0066cc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap;';
                btnAgregarUbicacion.className = 'dBtnAgregarUbicacionEditar-p3-' + idx;
                
                inputDiv.appendChild(inputUbicacion);
                inputDiv.appendChild(btnAgregarUbicacion);
                
                const ubicacionesContainer = document.createElement('div');
                ubicacionesContainer.className = 'dUbicacionesListEditar-p3-' + idx;
                ubicacionesContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputDiv);
                divUbicacion.appendChild(ubicacionesContainer);
                ubicacionesDiv.appendChild(divUbicacion);
                
                // Manejar agregar ubicaciones en el edit form
                let ubicacionesEditarTecnica = [];
                // Inicializar con ubicaciones existentes
                if (tecnicaInfo.prenda.ubicaciones && Array.isArray(tecnicaInfo.prenda.ubicaciones)) {
                    ubicacionesEditarTecnica = [...tecnicaInfo.prenda.ubicaciones];
                }
                
                btnAgregarUbicacion.addEventListener('click', (e) => {
                    e.preventDefault();
                    const ubicacion = inputUbicacion.value.trim().toUpperCase();
                    
                    if (!ubicacion) {
                        Swal.showValidationMessage('Escribe una ubicación primero');
                        return;
                    }
                    
                    if (ubicacionesEditarTecnica.includes(ubicacion)) {
                        Swal.showValidationMessage('Esta ubicación ya fue agregada');
                        return;
                    }
                    
                    ubicacionesEditarTecnica.push(ubicacion);
                    inputUbicacion.value = '';
                    
                    actualizarUbicacionesListEditar();
                    inputUbicacion.focus();
                });
                
                inputUbicacion.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnAgregarUbicacion.click();
                    }
                });
                
                function actualizarUbicacionesListEditar() {
                    ubicacionesContainer.innerHTML = ubicacionesEditarTecnica.map((ub, i) => `
                        <span style="background: #dbeafe; color: #0369a1; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                            ${ub}
                            <button type="button" data-ubicacion-idx="${i}" style="background: none; border: none; color: #0369a1; cursor: pointer; font-weight: 700; padding: 0; margin-left: 0.25rem;">✕</button>
                        </span>
                    `).join('');
                    
                    ubicacionesContainer.querySelectorAll('button[data-ubicacion-idx]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const idxAEliminar = parseInt(btn.getAttribute('data-ubicacion-idx'));
                            ubicacionesEditarTecnica.splice(idxAEliminar, 1);
                            actualizarUbicacionesListEditar();
                        });
                    });
                }
                
                // Renderizar ubicaciones iniciales
                actualizarUbicacionesListEditar();
                
                // Guardar referencia global para preConfirm
                window['dUbicacionesEditarTecnicaP3' + idx] = ubicacionesEditarTecnica;
                
                // IMÁGENES - Inicializar con archivos File reales (NO convertidos a base64)
                // Solo guardamos los File objects, no las URL de paso2
                imagenesAgregadasPorTecnicaEditar[idx] = [];
                if (tecnicaInfo.prenda.imagenes && Array.isArray(tecnicaInfo.prenda.imagenes)) {
                    tecnicaInfo.prenda.imagenes.forEach(img => {
                        // Solo agregar los archivos del PASO 3 (tienen .file como File object)
                        if (img.tipo === 'paso3' && img.file instanceof File) {
                            imagenesAgregadasPorTecnicaEditar[idx].push(img.file);
                        }
                    });
                }
                
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
                            // Es una URL - mostrar directamente (es de paso2)
                            imagenSrc = archivo;
                            mostrarPreview(imagenSrc, imgIdx);
                        } else if (archivo instanceof Blob || archivo instanceof File) {
                            // Es un File object válido - convertir a data URL SOLO para preview
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
            if (datosPrendaActual.talla_cantidad && datosPrendaActual.talla_cantidad.length > 0) {
                datosPrendaActual.talla_cantidad.forEach(t => {
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
            
            // Capturar ubicaciones (múltiples por técnica)
            const ubicacionesActualizadas = {};
            window.tecnicasConPrendaActual.forEach((tecnicaInfo, idx) => {
                const ubicacionesEditarTecnica = window['dUbicacionesEditarTecnicaP3' + idx] || [];
                if (ubicacionesEditarTecnica.length > 0) {
                    ubicacionesActualizadas[tecnicaInfo.tipo] = ubicacionesEditarTecnica;
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
                if (prenda.nombre_prenda === nombrePrenda) {
                    // Buscar índice de esta técnica en los datos editados
                    const tecnicaNombreBuscar = tecnicaData.tipo_logo ? tecnicaData.tipo_logo.nombre : tecnicaData.tipo;
                    const tecnicaIdx = window.tecnicasConPrendaActual.findIndex(t => {
                        const tNombre = t.tipo_logo ? t.tipo_logo.nombre : t.tipo;
                        return tNombre === tecnicaNombreBuscar;
                    });
                    
                    // Procesar imágenes: mantener del paso2 + agregar nuevas del paso3
                    let imagenesActualizadas = (prenda.imagenes || []).filter(img => img.tipo !== 'paso3');
                    
                    // Agregar imágenes nuevas del PASO 3 (que están en imagenesAgregadas como File objects)
                    if (tecnicaIdx >= 0 && imagenesAgregadas[tecnicaIdx] && imagenesAgregadas[tecnicaIdx].length > 0) {
                        const nuevasImagenes = imagenesAgregadas[tecnicaIdx].map(archivo => ({
                            file: archivo,  // Aquí está el File object
                            tipo: 'paso3'
                        }));
                        imagenesActualizadas = [...imagenesActualizadas, ...nuevasImagenes];
                    }
                    
                    return {
                        ...prenda,
                        observaciones: observaciones,
                        ubicaciones: ubicacionesActualizadas[tecnicaNombreBuscar] || prenda.ubicaciones,
                        variaciones_prenda: variacionesActualizadas,
                        talla_cantidad: tallas,
                        imagenes: imagenesActualizadas  // Array con {file, tipo} para paso3 + paso2 URLs
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
    
    console.log('🗑️ Eliminando prenda:', nombrePrenda);
    
    // Eliminar todas las prendas con este nombre
    window.tecnicasAgregadasPaso3 = window.tecnicasAgregadasPaso3.filter(tecnica => {
        if (tecnica.prendas) {
            tecnica.prendas = tecnica.prendas.filter(p => p.nombre_prenda !== nombrePrenda);
            console.log(`  Prendas restantes en técnica '${tecnica.tipo}': ${tecnica.prendas.length}`);
            return tecnica.prendas.length > 0;
        }
        return true;
    });
    
    console.log(' Prenda eliminada. Técnicas restantes:', window.tecnicasAgregadasPaso3.length);
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


function agregarFilaPrendaPaso3_DEPRECATED() {
    // FUNCIÓN DEPRECADA - NO USAR
    console.warn('agregarFilaPrendaPaso3_DEPRECATED: Esta función ha sido eliminada');
    return; 
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
// 8. INICIALIZACIÓN
// =========================================================

document.addEventListener('DOMContentLoaded', function() {
    cargarTiposDisponiblesPaso3();
    console.log(' PASO 3 COTIZACIÓN COMBINADA - Inicializado');
});
