/**
 * EJEMPLO DE INTEGRACIÓN JAVASCRIPT
 * Para: resources/views/cotizaciones/bordado/create.blade.php
 * 
 * Este archivo muestra cómo integrar los endpoints API en la interfaz
 */

// =========================================================
// 1. VARIABLES GLOBALES
// =========================================================

let tecnicasAgregadas = [];
let tiposDisponibles = [];
let logoCotizacionId = null;

// =========================================================
// 2. CARGAR TIPOS DE TÉCNICAS AL INICIALIZAR
// =========================================================

async function cargarTiposDisponibles() {
    try {
        const response = await fetch('/api/logo-cotizacion-tecnicas/tipos-disponibles');
        const data = await response.json();
        
        if (data.success) {
            tiposDisponibles = data.data;
            renderizarSelectTecnicas();
        }
    } catch (error) {

    }
}

// =========================================================
// 3. RENDERIZAR CHECKBOXES DE TÉCNICAS
// =========================================================

function renderizarSelectTecnicas() {
    const container = document.getElementById('tecnicas-checkboxes');
    
    if (!container) {

        return;
    }
    
    container.innerHTML = '';
    
    tiposDisponibles.forEach(tipo => {
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; border-radius: 4px; border: 1px solid #e5e7eb; transition: all 0.2s;';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = tipo.id;
        checkbox.className = 'tecnica-checkbox';
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

function abrirModalAgregarTecnica() {
    // Obtener técnicas seleccionadas
    const checkboxes = document.querySelectorAll('.tecnica-checkbox:checked');
    const tecnicasSeleccionadas = Array.from(checkboxes).map(cb => {
        const id = parseInt(cb.value);
        return tiposDisponibles.find(t => t.id === id);
    });
    
    if (tecnicasSeleccionadas.length === 0) {
        abrirModalValidacionTecnica();
        return;
    }
    
    // Si solo seleccionó 1 técnica, flujo simple
    if (tecnicasSeleccionadas.length === 1) {
        abrirModalSimpleTecnica(tecnicasSeleccionadas[0]);
    } else {
        // Si seleccionó múltiples, ir DIRECTO a datos iguales (sin pregunta)
        abrirModalDatosIguales(tecnicasSeleccionadas);
    }
}

function abrirModalSimpleTecnica(tipo) {
    // Obtener el nombre de la técnica
    const nombreElement = document.getElementById('tecnicaSeleccionadaNombre');
    if (nombreElement) {
        nombreElement.textContent = tipo.nombre;
    }
    
    // Limpiar prendas del modal
    const listaPrendas = document.getElementById('listaPrendas');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
    }
    
    // Agregar una prenda por defecto
    agregarFilaPrenda();
    
    // Mostrar/ocultar mensaje de sin prendas
    const noPrendasMsg = document.getElementById('noPrendasMsg');
    if (noPrendasMsg) {
        noPrendasMsg.style.display = 'none';
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// =========================================================
// 4.2 MODAL TÉCNICAS COMBINADAS (Pregunta iguales/diferentes)
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
                        <input type="radio" name="opcion-tecnicas" value="iguales" checked style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;"> Datos iguales</strong>
                            <small style="color: #666; font-size: 0.8rem;">Una misma prenda para todas</small>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas" value="diferentes" style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;"> Datos diferentes</strong>
                            <small style="color: #666; font-size: 0.8rem;">Cada técnica con su prenda</small>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas" value="por-talla" style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;"> Por talla</strong>
                            <small style="color: #666; font-size: 0.8rem;">Técnicas diferentes según talla</small>
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
            const opcion = document.querySelector('input[name="opcion-tecnicas"]:checked').value;
            
            if (opcion === 'iguales') {
                abrirModalDatosIguales(tecnicas);
            } else if (opcion === 'diferentes') {
                iniciarFlujoDatosDiferentes(tecnicas);
            } else if (opcion === 'por-talla') {
                abrirModalPrendaTecnicasPorTalla(tecnicas);
            }
        }
    });
}

function abrirModalDatosIguales(tecnicas) {
    // Guardar técnicas combinadas en contexto global temporal
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'iguales';
    
    // Modal MINIMALISTA tipo TNS
    Swal.fire({
        title: 'Técnicas Combinadas',
        width: '600px',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 70vh; overflow-y: auto;">
                
                <!-- PRENDA ÚNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Prenda</h3>
                    <div style="position: relative;">
                        <input type="text" id="dNombrePrenda" placeholder="POLO, CAMISA, PANTALÓN..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;" autocomplete="off">
                        <div id="dListaSugerencias" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; max-height: 150px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                    </div>
                </div>
                
                <!-- UBICACIONES POR TÉCNICA + BOTONES TALLAS -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Ubicaciones</h3>
                    <div id="dUbicacionesPorTecnica" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
                
                <!-- OBSERVACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones</h3>
                    <textarea id="dObservaciones" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
                
                <!-- TALLAS GENÉRICAS (Por defecto) -->
                <div id="dSeccionTallasGeneral" style="display: block; margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Tallas y Cantidades (General)</h3>
                    <div id="dTallaCantidadContainer" style="display: grid; gap: 8px; margin-bottom: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                    <button type="button" id="dBtnAgregarTalla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%; font-size: 0.9rem;">+ Agregar talla general</button>
                </div>
                
                <!-- VARIACIONES DE PRENDA (Manga, Bolsillos, Broche) -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Variaciones</h3>
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Opción</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Valor</th>
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 8px; font-weight: 600; color: #555;">Manga</td>
                                <td style="padding: 8px;">
                                    <input type="text" id="dVariacionManga" placeholder="Corta, Larga, Tres Cuartos" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                </td>
                                <td style="padding: 8px;">
                                    <input type="text" id="dVariacionMangaObs" placeholder="Observación" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 8px; font-weight: 600; color: #555;">Bolsillos</td>
                                <td style="padding: 8px;">
                                    <input type="text" id="dVariacionBolsillos" placeholder="Con Tapa, Sin Tapa, Con Botón" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                </td>
                                <td style="padding: 8px;">
                                    <input type="text" id="dVariacionBolsillosObs" placeholder="Observación" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; font-weight: 600; color: #555;">Broche/Botón</td>
                                <td style="padding: 8px;">
                                    <select id="dVariacionBroche" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                        <option value="">-- Seleccionar --</option>
                                        <option value="BROCHE">BROCHE</option>
                                        <option value="BOTÓN">BOTÓN</option>
                                    </select>
                                </td>
                                <td style="padding: 8px;">
                                    <input type="text" id="dVariacionBrocheObs" placeholder="Observación" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- IMÁGENES POR TÉCNICA -->
                <div style="margin-top: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Imágenes por Técnica</h3>
                    <div id="dImagenesPorTecnica" style="display: grid; gap: 12px;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            const inputPrenda = document.getElementById('dNombrePrenda');
            const listaSugerencias = document.getElementById('dListaSugerencias');
            const btnAgregarTalla = document.getElementById('dBtnAgregarTalla');
            const tallaCantidadContainer = document.getElementById('dTallaCantidadContainer');
            const ubicacionesPorTecnicaDiv = document.getElementById('dUbicacionesPorTecnica');
            
            let contadorTallas = 0;
            let prendasDisponibles = [];
            
            // Cargar prendas para autocomplete
            fetch('/api/logo-cotizacion-tecnicas/prendas')
                .then(r => r.json())
                .then(data => {
                    prendasDisponibles = data.data || [];

                })
                .catch(e => console.warn('No se pudo cargar prendas:', e));
            
            // Autocomplete
            inputPrenda.addEventListener('input', (e) => {
                const valor = e.target.value.trim().toUpperCase();
                
                if (valor.length === 0) {
                    listaSugerencias.style.display = 'none';
                    return;
                }
                
                const sugerencias = prendasDisponibles.filter(p => p.includes(valor));
                
                if (sugerencias.length === 0) {
                    listaSugerencias.style.display = 'none';
                    return;
                }
                
                listaSugerencias.innerHTML = sugerencias.map(p => 
                    `<div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.1s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'" onclick="document.getElementById('dNombrePrenda').value='${p}'; document.getElementById('dListaSugerencias').style.display='none';">${p}</div>`
                ).join('');
                listaSugerencias.style.display = 'block';
            });
            
            // Cerrar sugerencias al hacer click afuera
            document.addEventListener('click', (e) => {
                if (e.target !== inputPrenda && e.target !== listaSugerencias) {
                    listaSugerencias.style.display = 'none';
                }
            });
            
            // Agregar talla inicial (genérica)
            agregarFilaTalla();
            
            function agregarFilaTalla() {
                const idTalla = 'talla-' + (contadorTallas++);
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
            
            // Crear inputs de ubicación por técnica
            tecnicas.forEach((tecnica, idx) => {
                // Ubicación
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'display: flex; gap: 8px; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 4px;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; min-width: 100px; font-size: 0.9rem; color: #333; flex-shrink: 0;';
                labelUbicacion.textContent = tecnica.nombre + ':';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacion-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputUbicacion);
                ubicacionesPorTecnicaDiv.appendChild(divUbicacion);
            });
            
            // Crear inputs de imagen por técnica con Drag and Drop
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                tecnicas.forEach((tecnica, idx) => {
                    // Array para almacenar imágenes de esta técnica
                    const imagenesAgregadas = [];
                    
                    const divImagen = document.createElement('div');
                    divImagen.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                    divImagen.innerHTML = `
                        <label style="font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;">
                            Imágenes - ${tecnica.nombre} (Máximo 3)
                        </label>
                        <div class="dImagenesDropzone-${idx}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 16px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                            <div style="margin-bottom: 6px; font-size: 1.3rem;">📁</div>
                            <p style="margin: 0 0 4px 0; font-weight: 500; color: #333; font-size: 0.9rem;">Arrastra imágenes aquí</p>
                            <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                            <input type="file" class="dImagenTecnicaInput-${idx}" accept="image/*" multiple style="display: none;" />
                        </div>
                        <div class="dImagenesPreview-${idx}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                            <!-- Previsualizaciones aquí -->
                        </div>
                    `;
                    
                    imagesPorTecnicaDiv.appendChild(divImagen);
                    
                    // Setup Drag and Drop
                    const dropzone = divImagen.querySelector(`.dImagenesDropzone-${idx}`);
                    const input = divImagen.querySelector(`.dImagenTecnicaInput-${idx}`);
                    const previewContainer = divImagen.querySelector(`.dImagenesPreview-${idx}`);
                    
                    // Click en dropzone
                    dropzone.addEventListener('click', () => input.click());
                    
                    // Drag over
                    dropzone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropzone.style.background = '#e8f1ff';
                        dropzone.style.borderColor = '#1e40af';
                    });
                    
                    dropzone.addEventListener('dragleave', () => {
                        dropzone.style.background = '#fafafa';
                        dropzone.style.borderColor = '#ddd';
                    });
                    
                    // Drop
                    dropzone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropzone.style.background = '#fafafa';
                        dropzone.style.borderColor = '#ddd';
                        agregarImagenesDelDropzone(Array.from(e.dataTransfer.files));
                    });
                    
                    // Cambio en input file
                    input.addEventListener('change', (e) => {
                        agregarImagenesDelDropzone(Array.from(e.target.files));
                    });
                    
                    function agregarImagenesDelDropzone(archivos) {
                        // Filtrar solo imágenes
                        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
                        
                        if (imagenes.length === 0) {
                            return;
                        }
                        
                        // Agregar imágenes hasta el límite de 3
                        for (const archivo of imagenes) {
                            if (imagenesAgregadas.length >= 3) {
                                break;
                            }
                            imagenesAgregadas.push(archivo);
                        }
                        
                        // Actualizar previsualizaciones
                        actualizarPrevisualizaciones();
                    }
                    
                    function actualizarPrevisualizaciones() {
                        previewContainer.innerHTML = '';
                        
                        imagenesAgregadas.forEach((archivo, imgIdx) => {
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
                                    imagenesAgregadas.splice(imgIdx, 1);
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
                    
                    // Guardar referencia al array de imágenes para extracción posterior
                    divImagen.imagenesAgregadas = imagenesAgregadas;
                    divImagen.dataset.tecnicaIdx = idx;
                });
            }
            
            // Listener agregar talla
            if (btnAgregarTalla) {
                btnAgregarTalla.addEventListener('click', (e) => {
                    e.preventDefault();
                    agregarFilaTalla();
                });
            }
        },
        preConfirm: () => {
            // Validar prenda
            const nombrePrenda = document.getElementById('dNombrePrenda').value.trim().toUpperCase();
            if (!nombrePrenda) {
                Swal.showValidationMessage('Completa el nombre de la prenda');
                return false;
            }
            
            // Guardar prenda en historial (async, no bloquea)
            fetch('/api/logo-cotizacion-tecnicas/prendas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ nombre: nombrePrenda })
            }).catch(e => console.warn(' No se pudo guardar prenda en historial:', e));
            
            // Validar ubicaciones
            const ubicacionesPorTecnica = {};
            let valido = true;
            
            window.tecnicasCombinadas.forEach((tecnica, idx) => {
                const ubicacion = document.querySelector('.dUbicacion-' + idx)?.value.trim() || '';
                if (!ubicacion) {
                    Swal.showValidationMessage(`Agrega ubicación para ${tecnica.nombre}`);
                    valido = false;
                    return;
                }
                ubicacionesPorTecnica[idx] = [ubicacion];
            });
            
            if (!valido) return false;
            
            // Validar tallas genéricas (ya no hay tallas específicas por técnica)
            let tallasPorTecnica = {};
            let tallas = [];
            
            // Recopilar tallas genéricas
            const tallasFilas = document.querySelectorAll('[data-talla-id]');
            tallasFilas.forEach(fila => {
                const talla = fila.querySelector('.dTallaInput').value.trim();
                const cantidad = fila.querySelector('.dCantidadInput').value;
                if (talla && cantidad) {
                    tallas.push({ talla, cantidad: parseInt(cantidad) });
                }
            });
            
            // Validar que haya al menos una talla
            if (tallas.length === 0) {
                Swal.showValidationMessage('Agrega al menos una talla');
                return false;
            }
            
            // Todas las técnicas usan las mismas tallas genéricas
            window.tecnicasCombinadas.forEach((tecnica, idx) => {
                tallasPorTecnica[idx] = tallas;
            });
            
            // Recopilar imágenes por técnica desde el nuevo sistema de drag and drop
            const imagenesPorTecnica = {};
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                const divImagenes = imagesPorTecnicaDiv.querySelectorAll('[data-tecnica-idx]');
                divImagenes.forEach(div => {
                    const idx = parseInt(div.getAttribute('data-tecnica-idx'));
                    if (div.imagenesAgregadas && div.imagenesAgregadas.length > 0) {
                        imagenesPorTecnica[idx] = div.imagenesAgregadas;
                    }
                });
            }
            
            // Recopilar variaciones (Manga, Bolsillos, Broche)
            const variacionesPrenda = {};
            
            const manga = document.getElementById('dVariacionManga')?.value.trim() || '';
            const mangaObs = document.getElementById('dVariacionMangaObs')?.value.trim() || '';
            if (manga) {
                variacionesPrenda.manga = {
                    opcion: manga,
                    observacion: mangaObs || null
                };
            }
            
            const bolsillos = document.getElementById('dVariacionBolsillos')?.value.trim() || '';
            const bolsillosObs = document.getElementById('dVariacionBolsillosObs')?.value.trim() || '';
            if (bolsillos) {
                variacionesPrenda.bolsillos = {
                    opcion: bolsillos,
                    observacion: bolsillosObs || null
                };
            }
            
            const broche = document.getElementById('dVariacionBroche')?.value || '';
            const brocheObs = document.getElementById('dVariacionBrocheObs')?.value.trim() || '';
            if (broche) {
                variacionesPrenda.broche_boton = {
                    opcion: broche,
                    observacion: brocheObs || null
                };
            }
            
            return {
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('dObservaciones').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
                tallasPorTecnica: tallasPorTecnica,
                imagenesPorTecnica: imagenesPorTecnica,
                variaciones_prenda: Object.keys(variacionesPrenda).length > 0 ? variacionesPrenda : null
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Cerrar el modal inmediatamente
            Swal.close();
            // Guardar después de cerrar
            guardarTecnicaCombinada(result.value);
        }
    });
}

function iniciarFlujoDatosDiferentes(tecnicas) {
    // Guardar contexto
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'diferentes';
    window.indiceActualTecnica = 0;
    
    mostrarFormularioTecnicaDiferente(0);
}

// =========================================================
// 4.4 FLUJO: MISMA PRENDA, TÉCNICAS POR TALLA
// =========================================================

function abrirModalPrendaTecnicasPorTalla(tecnicas) {
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'por-talla';
    window.prendaTecnicasPorTalla = {
        nombre: '',
        ubicaciones: [],
        observaciones: '',
        tallaTecnicas: {} // { "M": [id1, id2], "L": [id3], ...}
    };
    
    Swal.fire({
        title: 'Prenda con Técnicas por Talla',
        width: '85%',
        maxWidth: '500px',
        html: `
            <div style="text-align: left; margin: 20px 0;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nombre de prenda:</label>
                    <input type="text" id="ptpNombrePrenda" placeholder="Ej: Camisa" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Ubicaciones:</label>
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <input type="text" id="ptpUbicacionInput" placeholder="Ej: Pecho" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <button type="button" id="ptpBtnAgregarUbicacion" style="background: #1e40af; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                            <i class="fas fa-plus"></i> Ubicación
                        </button>
                    </div>
                    <div id="ptpUbicaciones" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;"></div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Observaciones:</label>
                    <textarea id="ptpObservaciones" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 10px;">Tallas y Técnicas:</label>
                    <div id="ptpTallas" style="display: grid; gap: 10px; max-height: 250px; overflow-y: auto;">
                        <!-- Se agregará dinámicamente -->
                    </div>
                    <button type="button" id="ptpBtnAgregarTalla" style="background: #1e40af; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-top: 10px; width: 100%; font-weight: 600;">+ Agregar Talla</button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#27ae60',
        didOpen: (modal) => {
            const btnAgregarUbicacion = document.getElementById('ptpBtnAgregarUbicacion');
            const inputUbicacion = document.getElementById('ptpUbicacionInput');
            const ubicacionesDiv = document.getElementById('ptpUbicaciones');
            const btnAgregarTalla = document.getElementById('ptpBtnAgregarTalla');
            const tallasDiv = document.getElementById('ptpTallas');
            
            // Agregar ubicación
            if (btnAgregarUbicacion) {
                btnAgregarUbicacion.addEventListener('click', (e) => {
                    e.preventDefault();
                    const ubicacion = inputUbicacion.value.trim();
                    
                    if (!ubicacion) {
                        Swal.showValidationMessage('Escribe una ubicación primero');
                        return;
                    }
                    
                    const existentes = Array.from(ubicacionesDiv.querySelectorAll('[data-ubicacion]'))
                        .map(tag => tag.getAttribute('data-ubicacion').toLowerCase());
                    if (existentes.includes(ubicacion.toLowerCase())) {
                        Swal.showValidationMessage('Esta ubicación ya fue agregada');
                        return;
                    }
                    
                    const tag = document.createElement('div');
                    tag.setAttribute('data-ubicacion', ubicacion);
                    tag.style.cssText = 'background: #1e40af; color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; font-weight: 500;';
                    tag.innerHTML = `
                        ${ubicacion}
                        <button type="button" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.2s;">×</button>
                    `;
                    
                    tag.querySelector('button').addEventListener('click', (e) => {
                        e.preventDefault();
                        tag.remove();
                        inputUbicacion.focus();
                    });
                    
                    ubicacionesDiv.appendChild(tag);
                    inputUbicacion.value = '';
                    inputUbicacion.focus();
                });
            }
            
            // Agregar talla
            if (btnAgregarTalla) {
                btnAgregarTalla.addEventListener('click', (e) => {
                    e.preventDefault();
                    const rowTalla = document.createElement('div');
                    rowTalla.className = 'ptp-talla-row';
                    rowTalla.style.cssText = 'border: 1px solid #ddd; border-radius: 6px; padding: 10px; background: #f9f9f9;';
                    rowTalla.innerHTML = `
                        <div style="display: flex; gap: 8px; margin-bottom: 10px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 3px;">Talla</label>
                                <input type="text" class="ptp-talla" placeholder="S, M, L, XL" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div style="flex: 1;">
                                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 3px;">Cantidad</label>
                                <input type="number" class="ptp-cantidad" min="1" value="1" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <button type="button" class="ptp-btn-eliminar" style="background: #d32f2f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">×</button>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 6px;">Técnicas para esta talla:</label>
                            <div class="ptp-tecnicas-checkboxes" style="display: flex; flex-wrap: wrap; gap: 12px;">
                                <!-- Checkboxes se generan aquí -->
                            </div>
                        </div>
                    `;
                    
                    // Generar checkboxes de técnicas
                    const checkboxesDiv = rowTalla.querySelector('.ptp-tecnicas-checkboxes');
                    tecnicas.forEach(tecnica => {
                        const label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;';
                        label.innerHTML = `
                            <input type="checkbox" class="ptp-tecnica-check" data-tecnica-id="${tecnica.id}" style="width: 16px; height: 16px; cursor: pointer;">
                            <span style="font-size: 0.9rem; color: ${tecnica.color}; font-weight: 600;">${tecnica.nombre}</span>
                        `;
                        checkboxesDiv.appendChild(label);
                    });
                    
                    // Botón eliminar talla
                    rowTalla.querySelector('.ptp-btn-eliminar').addEventListener('click', () => rowTalla.remove());
                    
                    tallasDiv.appendChild(rowTalla);
                });
            }
        },
        preConfirm: () => {
            const nombre = document.getElementById('ptpNombrePrenda').value.trim();
            const observaciones = document.getElementById('ptpObservaciones').value.trim();
            const ubicacionTags = Array.from(document.querySelectorAll('#ptpUbicaciones [data-ubicacion]'))
                .map(tag => tag.getAttribute('data-ubicacion'));
            const tallasRows = document.querySelectorAll('.ptp-talla-row');
            
            if (!nombre) {
                Swal.showValidationMessage('El nombre de la prenda es obligatorio');
                return false;
            }
            
            if (ubicacionTags.length === 0) {
                Swal.showValidationMessage('Debe agregar al menos una ubicación');
                return false;
            }
            
            if (tallasRows.length === 0) {
                Swal.showValidationMessage('Debe agregar al menos una talla');
                return false;
            }
            
            const tallaTecnicas = {};
            let tieneErrores = false;
            
            tallasRows.forEach(row => {
                const talla = row.querySelector('.ptp-talla').value.trim();
                const cantidad = parseInt(row.querySelector('.ptp-cantidad').value);
                const tecnicasChecked = Array.from(row.querySelectorAll('.ptp-tecnica-check:checked'))
                    .map(cb => parseInt(cb.dataset.tecnicaId));
                
                if (!talla) {
                    Swal.showValidationMessage('Todas las tallas deben tener un nombre');
                    tieneErrores = true;
                    return;
                }
                
                if (tecnicasChecked.length === 0) {
                    Swal.showValidationMessage(`Selecciona al menos una técnica para la talla ${talla}`);
                    tieneErrores = true;
                    return;
                }
                
                tallaTecnicas[talla] = { tecnicas: tecnicasChecked, cantidad };
            });
            
            if (tieneErrores) return false;
            
            return { nombre, ubicaciones: ubicacionTags, observaciones, tallaTecnicas };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const datos = result.value;
            guardarPrendaTecnicasPorTalla(datos);
        }
    });
}

function mostrarFormularioTecnicaDiferente(index) {
    const tecnicas = window.tecnicasCombinadas;
    if (index >= tecnicas.length) {
        // Terminar y limpiar
        window.tecnicasCombinadas = null;
        window.modoTecnicasCombinadas = null;
        return;
    }
    
    const tecnicaActual = tecnicas[index];
    const totalTecnicas = tecnicas.length;
    
    Swal.fire({
        title: `Técnica ${index + 1} de ${totalTecnicas}`,
        width: '85%',
        maxWidth: '450px',
        html: `
            <div style="text-align: left; margin: 20px 0;">
                <h4 style="color: #1e40af; margin-bottom: 15px;">${tecnicaActual.nombre}</h4>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Nombre de prenda:</label>
                    <input type="text" id="modalPrendaNombre" placeholder="Ej: Camisa" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Ubicaciones:</label>
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <input type="text" id="modalUbicacionInput" placeholder="Ej: Pecho" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <button type="button" id="btnAgregarUbicacion" style="background: #1e40af; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                            <i class="fas fa-plus"></i> Ubicación
                        </button>
                    </div>
                    <div id="modalUbicaciones" style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;">
                        <!-- Tags de ubicación aquí -->
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Observaciones:</label>
                    <textarea id="modalObservaciones" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 10px;">Tallas y Cantidades:</label>
                    <div id="modalTallas" style="display: grid; gap: 10px; max-height: 200px; overflow-y: auto;">
                        <div style="display: flex; gap: 8px;">
                            <input type="text" class="modal-talla" placeholder="Ej: M" style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <input type="number" class="modal-cantidad" min="1" value="1" style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                            <button class="btn-eliminar-talla" style="background: #d32f2f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">×</button>
                        </div>
                    </div>
                    <button id="btnAgregarTallaModal" style="background: #1e40af; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-top: 10px; width: 100%;">+ Agregar Talla</button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: index === totalTecnicas - 1 ? 'Finalizar' : 'Siguiente',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1e40af',
        didOpen: (modal) => {
            // Agregar eventos a los botones después de que se abre el modal
            const btnAgregar = document.getElementById('btnAgregarTallaModal');
            const modalTallas = document.getElementById('modalTallas');
            const btnAgregarUbicacion = document.getElementById('btnAgregarUbicacion');
            const inputUbicacion = document.getElementById('modalUbicacionInput');
            const ubicacionesDiv = document.getElementById('modalUbicaciones');
            
            // Evento para agregar ubicaciones
            if (btnAgregarUbicacion) {
                btnAgregarUbicacion.addEventListener('click', (e) => {
                    e.preventDefault();
                    const ubicacion = inputUbicacion.value.trim();
                    
                    if (!ubicacion) {
                        Swal.showValidationMessage('Escribe una ubicación primero');
                        return;
                    }
                    
                    // Verificar duplicados
                    const existentes = Array.from(ubicacionesDiv.querySelectorAll('[data-ubicacion]'))
                        .map(tag => tag.getAttribute('data-ubicacion').toLowerCase());
                    if (existentes.includes(ubicacion.toLowerCase())) {
                        Swal.showValidationMessage('Esta ubicación ya fue agregada');
                        return;
                    }
                    
                    // Crear tag
                    const tag = document.createElement('div');
                    tag.setAttribute('data-ubicacion', ubicacion);
                    tag.style.cssText = 'background: #1e40af; color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; font-weight: 500;';
                    tag.innerHTML = `
                        ${ubicacion}
                        <button type="button" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.2s;" onmouseover="this.style.background='rgba(0,0,0,0.2)'" onmouseout="this.style.background='none'">×</button>
                    `;
                    
                    tag.querySelector('button').addEventListener('click', (e) => {
                        e.preventDefault();
                        tag.remove();
                        inputUbicacion.focus();
                    });
                    
                    ubicacionesDiv.appendChild(tag);
                    inputUbicacion.value = '';
                    inputUbicacion.focus();
                });
            }
            
            // Evento para agregar tallas
            if (btnAgregar && modalTallas) {
                btnAgregar.addEventListener('click', (e) => {
                    e.preventDefault();
                    const div = document.createElement('div');
                    div.style.cssText = 'display: flex; gap: 8px;';
                    div.innerHTML = `
                        <input type="text" class="modal-talla" placeholder="Ej: M" style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="number" class="modal-cantidad" min="1" value="1" style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <button class="btn-eliminar-talla" style="background: #d32f2f; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">×</button>
                    `;
                    modalTallas.appendChild(div);
                    
                    const btnEliminar = div.querySelector('.btn-eliminar-talla');
                    if (btnEliminar) {
                        btnEliminar.addEventListener('click', () => div.remove());
                    }
                });
            }
            
            // Agregar eventos a los botones eliminar existentes
            document.querySelectorAll('.btn-eliminar-talla').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    btn.parentElement.remove();
                });
            });
        },
        preConfirm: () => {
            const nombre = document.getElementById('modalPrendaNombre').value.trim();
            const observaciones = document.getElementById('modalObservaciones').value.trim();
            
            // Extraer ubicaciones de los tags/chips
            const ubicacionTags = document.querySelectorAll('#modalUbicaciones [data-ubicacion]');
            const ubicaciones = Array.from(ubicacionTags).map(tag => tag.getAttribute('data-ubicacion'));
            
            const tallas = [];
            document.querySelectorAll('#modalTallas > div').forEach(row => {
                const talla = row.querySelector('.modal-talla').value.trim();
                const cantidad = parseInt(row.querySelector('.modal-cantidad').value) || 1;
                if (talla) {
                    tallas.push({ talla, cantidad });
                }
            });
            
            if (!nombre) {
                Swal.showValidationMessage('El nombre de la prenda es obligatorio');
                return false;
            }
            if (ubicaciones.length === 0) {
                Swal.showValidationMessage('Debe agregar al menos una ubicación');
                return false;
            }
            if (tallas.length === 0) {
                Swal.showValidationMessage('Debe agregar al menos una talla');
                return false;
            }
            
            return { nombre, ubicaciones, observaciones, tallas };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const datos = result.value;
            
            // Guardar datos de esta técnica
            if (!window.datosMultiplesTecnicas) {
                window.datosMultiplesTecnicas = [];
            }
            window.datosMultiplesTecnicas[index] = {
                tecnica: tecnicaActual,
                datos: datos
            };
            


            
            // Verificar si es la última técnica
            if (index === totalTecnicas - 1) {
                // Es la última técnica, guardar todas

                guardarTecnica();
            } else {
                // Ir a la siguiente

                window.indiceActualTecnica = index + 1;
                mostrarFormularioTecnicaDiferente(index + 1);
            }
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Cancelar flujo

            window.tecnicasCombinadas = null;
            window.modoTecnicasCombinadas = null;
            window.datosMultiplesTecnicas = null;
        }
    });
}

// =========================================================
// 5. FORMULARIO DINÁMICO DE PRENDAS EN MODAL
// =========================================================

function agregarFilaPrenda() {
    const container = document.getElementById('listaPrendas');
    
    if (!container) {

        return;
    }
    
    const numeroPrenda = container.children.length + 1;
    const prendasIndex = numeroPrenda - 1;
    
    const fila = document.createElement('div');
    fila.className = 'prenda-item';
    fila.style.cssText = 'margin-bottom: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;';
    fila.innerHTML = `
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 600; color: #333; font-size: 0.9rem;">
                <span>Prenda <span class="numero-prenda">${numeroPrenda}</span></span>
                <button type="button" onclick="this.closest('.prenda-item').remove(); actualizarNumeracionPrendas();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar prenda">
                    ✕
                </button>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Nombre de prenda</label>
                <input type="text" class="nombre_prenda" placeholder="CAMISA, PANTALÓN, POLO..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;">
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Ubicaciones</label>
                <div style="display: flex; gap: 6px; margin-bottom: 8px;">
                    <input type="text" class="ubicacion-input" placeholder="PECHO, ESPALDA, MANGA..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
                    <button type="button" class="btn-agregar-ubicacion" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem;">
                        + Agregar
                    </button>
                </div>
                <div class="ubicaciones-lista" style="display: flex; flex-wrap: wrap; gap: 6px; min-height: 24px; align-content: flex-start;">
                    <!-- Ubicaciones agregadas aquí como tags -->
                </div>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Observaciones Generales</label>
                <textarea class="observaciones" rows="2" placeholder="Detalles adicionales (opcional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
            </div>
            
            <!-- TABLA DE VARIACIONES -->
            <div style="border: 1px solid #ddd; border-radius: 4px; padding: 12px; background: #fafafa; margin-top: 12px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333; font-size: 0.85rem;"> Variaciones de Prenda</label>
                <table class="variaciones-table" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #e8f1ff;">
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: 600; color: #333;">Variación</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: 600; color: #333;">Opción</th>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-weight: 600; color: #333;">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- MANGA -->
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Manga</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <input type="text" class="variacion-manga" placeholder="Ej: Corta, Larga..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                            </td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <input type="text" class="observacion-manga" placeholder="Observación..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                            </td>
                        </tr>
                        
                        <!-- BOLSILLOS -->
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Bolsillos</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <input type="text" class="variacion-bolsillos" placeholder="Ej: Con bolsillos, Sin..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                            </td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <input type="text" class="observacion-bolsillos" placeholder="Observación..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                            </td>
                        </tr>
                        
                        <!-- BROCHE/BOTÓN -->
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-weight: 600; color: #333;">Broche/Botón</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <select class="variacion-broche" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                                    <option value="">SELECCIONAR</option>
                                    <option value="BROCHE">BROCHE</option>
                                    <option value="BOTÓN">BOTÓN</option>
                                </select>
                            </td>
                            <td style="border: 1px solid #ddd; padding: 8px;">
                                <input type="text" class="observacion-broche" placeholder="Ej: Metálico, Plástico..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Imágenes de Prenda (máximo 3) con Drag and Drop -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Imágenes (máximo 3)</label>
                <div class="imagenes-dropzone-${prendasIndex}" style="border: 2px dashed #ddd; border-radius: 6px; padding: 24px; text-align: center; background: #fafafa; cursor: pointer; transition: all 0.2s; color: #999; font-size: 0.85rem;" onmouseover="this.style.background='#f0f0f0'; this.style.borderColor='#1e40af'" onmouseout="this.style.background='#fafafa'; this.style.borderColor='#ddd'">
                    <div style="margin-bottom: 8px; font-size: 1.5rem;">📁</div>
                    <p style="margin: 0 0 4px 0; font-weight: 500; color: #333;">Arrastra imágenes aquí</p>
                    <p style="margin: 0; font-size: 0.8rem; color: #999;">O haz clic para seleccionar (máx. 3)</p>
                    <input type="file" class="imagen-prenda-input-${prendasIndex}" accept="image/*" multiple style="display: none;" />
                </div>
                <div class="imagenes-preview-${prendasIndex}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                    <!-- Previsualizaciones aquí -->
                </div>
            </div>
            
            <!-- Tallas y Cantidades Dinámicas -->
            <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                <button type="button" onclick="agregarTallaCantidad(this)" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.85rem;">+ Talla</button>
            </div>
            <div class="tallas-container" style="display: grid; gap: 8px;">
                <!-- Filas de talla-cantidad aquí -->
            </div>
        </div>
    `;
    
    container.appendChild(fila);
    
    // Array para almacenar archivos de esta prenda
    const imagenesAgregadas = [];
    
    // Setup Drag and Drop
    const dropzone = fila.querySelector(`.imagenes-dropzone-${prendasIndex}`);
    const input = fila.querySelector(`.imagen-prenda-input-${prendasIndex}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-${prendasIndex}`);
    
    // Click en dropzone
    dropzone.addEventListener('click', () => input.click());
    
    // Drag over
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#1e40af';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    // Drop
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        agregarImagenesDelDropzone(Array.from(e.dataTransfer.files));
    });
    
    // Cambio en input file
    input.addEventListener('change', (e) => {
        agregarImagenesDelDropzone(Array.from(e.target.files));
    });
    
    function agregarImagenesDelDropzone(archivos) {
        // Filtrar solo imágenes
        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
        
        if (imagenes.length === 0) {
            alert('Por favor selecciona imágenes válidas');
            return;
        }
        
        // Agregar imágenes hasta el límite de 3
        for (const archivo of imagenes) {
            if (imagenesAgregadas.length >= 3) {
                alert('Máximo 3 imágenes permitidas');
                break;
            }
            imagenesAgregadas.push(archivo);
        }
        
        // Actualizar previsualizaciones
        actualizarPrevisualizaciones();
    }
    
    function actualizarPrevisualizaciones() {
        previewContainer.innerHTML = '';
        
        imagenesAgregadas.forEach((archivo, idx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = document.createElement('div');
                preview.style.cssText = 'position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';
                
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
                btnEliminar.textContent = '×';
                btnEliminar.addEventListener('click', (e) => {
                    e.preventDefault();
                    imagenesAgregadas.splice(idx, 1);
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
    
    // Guardar referencia al array de imágenes en el elemento
    fila.dataset.imagenesAgregadas = JSON.stringify([]);
    Object.defineProperty(fila, 'imagenesAgregadas', {
        get: function() { return imagenesAgregadas; },
        set: function(val) { Object.assign(imagenesAgregadas, val); }
    });
    
    // Agregar una fila de talla-cantidad por defecto
    agregarTallaCantidad(fila.querySelector('button'));
}

// =========================================================
// 5.5 ACTUALIZAR NUMERACIÓN DE PRENDAS
// =========================================================

function actualizarNumeracionPrendas() {
    const prendas = document.querySelectorAll('.prenda-item');
    prendas.forEach((prenda, index) => {
        const numeroPrendaElement = prenda.querySelector('.numero-prenda');
        if (numeroPrendaElement) {
            numeroPrendaElement.textContent = index + 1;
        }
    });
}

// 5.6 ELIMINAR FILA DE PRENDA
// =========================================================

function eliminarFilaPrenda(prendasIndex) {
    const item = document.querySelector(`[data-prenda-index="${prendasIndex}"]`);
    if (item) {
        item.remove();
        
        // Mostrar mensaje de sin prendas si no hay más prendas
        const listaPrendas = document.getElementById('listaPrendas');
        if (listaPrendas && listaPrendas.children.length === 0) {
            const noPrendasMsg = document.getElementById('noPrendasMsg');
            if (noPrendasMsg) {
                noPrendasMsg.style.display = 'block';
            }
        }
    }
}

// Agregar event listeners para ubicaciones cuando se agrega una prenda
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-agregar-ubicacion') || (e.target.closest('.btn-agregar-ubicacion'))) {
        e.preventDefault();
        const btn = e.target.closest('.btn-agregar-ubicacion');
        const input = btn.previousElementSibling;
        const ubicacion = input.value.trim();
        
        if (!ubicacion) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Escribe una ubicación antes de agregar',
                confirmButtonColor: '#1e40af'
            });
            return;
        }
        
        const lista = btn.closest('div').nextElementSibling;
        
        // Verificar que no esté duplicada (case-insensitive)
        const existentes = Array.from(lista.querySelectorAll('[data-ubicacion]')).map(tag => tag.getAttribute('data-ubicacion').toLowerCase());
        if (existentes.includes(ubicacion.toLowerCase())) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya fue agregada',
                confirmButtonColor: '#1e40af'
            });
            return;
        }
        
        // Crear tag de ubicación
        const tag = document.createElement('div');
        tag.setAttribute('data-ubicacion', ubicacion);
        tag.style.cssText = 'background: #f0f0f0; color: #333; padding: 6px 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 0.85rem; display: flex; align-items: center; gap: 6px; font-weight: 500;';
        tag.innerHTML = `
            ${ubicacion}
            <button type="button" style="background: none; border: none; color: #999; cursor: pointer; font-size: 0.9rem; padding: 0; line-height: 1; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#999'">×</button>
        `;
        
        // Eliminar ubicación al hacer click en ×
        tag.querySelector('button').addEventListener('click', function(e) {
            e.preventDefault();
            tag.remove();
            input.focus();
        });
        
        lista.appendChild(tag);
        input.value = '';
        input.focus();
    }
});

function agregarTallaCantidad(button) {
    // Encontrar la prenda más cercana (el div principal)
    const prendaDiv = button.closest('div[style*="margin-bottom: 12px"]') || button.closest('div').parentElement;
    
    // Buscar el contenedor dentro de esa prenda
    const container = prendaDiv ? prendaDiv.querySelector('.tallas-container') : null;
    
    if (!container) {
        return;
    }
    
    const filaTC = document.createElement('div');
    filaTC.className = 'talla-cantidad-row';
    filaTC.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 40px; gap: 6px; align-items: flex-end;';
    filaTC.innerHTML = `
        <div>
            <label style="display: block; font-weight: 600; font-size: 0.8rem; margin-bottom: 4px; color: #333;">Talla</label>
            <input type="text" class="talla-input" placeholder="S, M, L, XL" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; text-transform: uppercase;">
        </div>
        <div>
            <label style="display: block; font-weight: 600; font-size: 0.8rem; margin-bottom: 4px; color: #333;">Cantidad</label>
            <input type="number" class="cantidad-input" min="1" value="1" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="background: none; color: #999; border: 1px solid #ddd; padding: 6px 4px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'">✕</button>
    `;
    
    container.appendChild(filaTC);
}

// =========================================================
// 6. GUARDAR TÉCNICA CON PRENDAS
// =========================================================

async function guardarTecnica() {
    // Verificar si estamos en modo técnicas combinadas
    if (window.modoTecnicasCombinadas === 'iguales') {

        guardarTecnicaCombinada();
    } else if (window.modoTecnicasCombinadas === 'diferentes') {


        guardarTecnicasMultiples();
    } else if (window.modoTecnicasCombinadas === 'por-talla') {

        // Este modo se maneja directamente en guardarPrendaTecnicasPorTalla
    } else {
        // Modo simple: una única técnica

        guardarTecnicaSimple();
    }
}

function guardarPrendaTecnicasPorTalla(datos) {


    
    const tecnicas = window.tecnicasCombinadas;
    
    // Por cada talla
    Object.keys(datos.tallaTecnicas).forEach(talla => {
        const tallaData = datos.tallaTecnicas[talla];
        const tecnicasIds = tallaData.tecnicas;
        const cantidad = tallaData.cantidad;
        
        // Por cada técnica seleccionada para esa talla
        tecnicasIds.forEach(tecnicaId => {
            const tipo = tecnicas.find(t => t.id === tecnicaId);
            
            if (!tipo) {

                return;
            }
            
            const nuevaTecnica = {
                tipo_logo: tipo,
                prendas: [{
                    nombre_prenda: datos.nombre,
                    observaciones: datos.observaciones,
                    ubicaciones: datos.ubicaciones,
                    talla_cantidad: [{ talla, cantidad }]
                }],
                es_combinada: true,
                grupo_combinado: null // El backend generará el grupo_combinado automáticamente El backend generará el grupo_combinado automáticamente
            };
            

            tecnicasAgregadas.push(nuevaTecnica);
        });
    });
    
    window.tecnicasAgregadas = tecnicasAgregadas;  //  Sincronizar global

    
    // Limpiar contexto
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;
    
    // Cerrar modal y actualizar
    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
}

function guardarTecnicaSimple() {
    const checkboxes = document.querySelectorAll('.tecnica-checkbox:checked');
    const tiposIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (tiposIds.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Selecciona un tipo de técnica'
        });
        return;
    }
    
    const tipoId = tiposIds[0];
    
    let prendas;
    try {
        prendas = extraerPrendasDelModal();
    } catch (error) {
        // La validación ya mostró el error con SweetAlert

        return;
    }
    
    if (!prendas || prendas.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Agrega al menos una prenda'
        });
        return;
    }
    
    const tipo = tiposDisponibles.find(t => t.id === tipoId);
    
    // Crear técnica SIMPLE (sin procesar a data URLs, mantener Files)
    const nuevaTecnica = {
        tipo_logo: tipo,
        prendas: prendas,
        es_combinada: false,  // ← IMPORTANTE: Marcar como simple
        grupo_combinado: null
    };
    

    tecnicasAgregadas.push(nuevaTecnica);
    window.tecnicasAgregadas = tecnicasAgregadas;  //  Sincronizar global

    
    // Cerrar modal y actualizar
    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
}

function guardarTecnicaCombinada(datosForm) {
    const tecnicas = window.tecnicasCombinadas;
    

    
    // Si no se pasa datosForm, construirlo desde el formulario actual
    if (!datosForm) {

        
        // Validar que haya nombre de prenda
        const nombrePrenda = document.querySelector('.nombre_prenda')?.value.trim();
        if (!nombrePrenda) {

            return;
        }
        
        // Obtener ubicaciones
        const ubicacionesPorTecnica = {};
        tecnicas.forEach((tecnica, idx) => {
            const ubicacion = document.querySelector(`.dUbicacion-${idx}`)?.value.trim() || '';
            ubicacionesPorTecnica[idx] = [ubicacion];
        });
        
        // Obtener tallas
        let tallas = [];
        const tallasFilas = document.querySelectorAll('[data-talla-id]');
        tallasFilas.forEach(fila => {
            const talla = fila.querySelector('.dTallaInput').value.trim();
            const cantidad = fila.querySelector('.dCantidadInput').value;
            if (talla && cantidad) {
                tallas.push({ talla, cantidad: parseInt(cantidad) });
            }
        });
        
        const tallasPorTecnica = {};
        tecnicas.forEach((tecnica, idx) => {
            tallasPorTecnica[idx] = tallas;
        });
        
        // Obtener imágenes
        const imagenesPorTecnica = {};
        const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
        if (imagesPorTecnicaDiv) {
            const divImagenes = imagesPorTecnicaDiv.querySelectorAll('[data-tecnica-idx]');

            divImagenes.forEach(div => {
                const idx = parseInt(div.getAttribute('data-tecnica-idx'));
                console.log(`  Div ${idx}:`, {
                    tiene_imagenesAgregadas: !!div.imagenesAgregadas,
                    cantidad: div.imagenesAgregadas ? div.imagenesAgregadas.length : 0,
                    contenido: div.imagenesAgregadas ? div.imagenesAgregadas.map(f => f.name) : []
                });
                if (div.imagenesAgregadas && div.imagenesAgregadas.length > 0) {
                    imagenesPorTecnica[idx] = div.imagenesAgregadas;

                }
            });
        }
        
        datosForm = {
            nombre_prenda: nombrePrenda,
            observaciones: document.getElementById('dObservaciones')?.value.trim() || '',
            ubicacionesPorTecnica: ubicacionesPorTecnica,
            tallasPorTecnica: tallasPorTecnica,
            imagenesPorTecnica: imagenesPorTecnica
        };
    }
    

    
    // Generar un ID único para el grupo combinado (basado en timestamp en milisegundos)
    // Esto garantiza unicidad a nivel de usuario y sesión
    const grupoId = Math.floor(Date.now() / 1000) + Math.floor(Math.random() * 10000);
    
    // Procesar imágenes: convertir File a data URL (máximo 3 por técnica)
    const processarImagenes = async () => {
        const imagenesProcessadas = {};
        
        for (const idx in datosForm.imagenesPorTecnica) {
            const archivos = datosForm.imagenesPorTecnica[idx];
            if (archivos && archivos.length > 0) {
                imagenesProcessadas[idx] = [];
                
                for (const archivo of archivos) {
                    const imagenProcessada = await new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            resolve({
                                data_url: e.target.result,
                                nombre: archivo.name
                            });
                        };
                        reader.readAsDataURL(archivo);
                    });
                    imagenesProcessadas[idx].push(imagenProcessada);
                }
            }
        }
        
        return imagenesProcessadas;
    };
    
    processarImagenes().then((imagenesProcessadas) => {
        // Para cada técnica
        tecnicas.forEach((tipo, idx) => {
            const ubicacionesTecnica = datosForm.ubicacionesPorTecnica[idx] || [];
            const tallasTecnica = datosForm.tallasPorTecnica[idx] || [];
            const imagenesTecnica = imagenesProcessadas[idx] || [];
            
            const nuevaTecnica = {
                tipo_logo: tipo,
                prendas: [{
                    nombre_prenda: datosForm.nombre_prenda,
                    observaciones: datosForm.observaciones,
                    ubicaciones: ubicacionesTecnica,
                    talla_cantidad: tallasTecnica,
                    variaciones_prenda: datosForm.variaciones_prenda || null,
                    imagenes_files: datosForm.imagenesPorTecnica[idx] || [],  // Array de File objects
                    imagenes_data_urls: imagenesTecnica.map(img => img.data_url)  // Array de Data URLs
                }],
                es_combinada: true,
                grupo_combinado: grupoId  // ID numérico único para agrupar técnicas combinadas
            };

            tecnicasAgregadas.push(nuevaTecnica);
        });
        


        
        window.tecnicasAgregadas = tecnicasAgregadas;  //  Sincronizar global
        
        // Limpiar contexto
        window.tecnicasCombinadas = null;
        window.modoTecnicasCombinadas = null;
        
        // Limpiar checkboxes
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-tecnica-id]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Actualizar renderizado
        renderizarTecnicasAgregadas();
        
        // Actualizar también el renderizado en logo-pedido-tecnicas.js si existe
        if (typeof renderizarLogoPrendasTecnicas === 'function') {

            renderizarLogoPrendasTecnicas();
        }
    });
}

function guardarTecnicasMultiples() {
    const datosMultiples = window.datosMultiplesTecnicas;
    


    
    if (!datosMultiples || datosMultiples.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay datos de técnicas para guardar'
        });

        return;
    }
    

    
    datosMultiples.forEach((item, idx) => {

        
        if (!item || !item.tecnica) {

            return;
        }
        
        const nuevaTecnica = {
            tipo_logo: item.tecnica,
            prendas: [{
                nombre_prenda: item.datos.nombre,
                observaciones: item.datos.observaciones,
                ubicaciones: item.datos.ubicaciones,
                talla_cantidad: item.datos.tallas
            }],
            es_combinada: true,
            grupo_combinado: null // El backend generará el grupo_combinado automáticamente
        };
        

        tecnicasAgregadas.push(nuevaTecnica);
    });
    
    window.tecnicasAgregadas = tecnicasAgregadas;  //  Sincronizar global


    
    // Limpiar contexto
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;
    window.datosMultiplesTecnicas = null;
    
    // Cerrar modal y actualizar
    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
}

function extraerPrendasDelModal() {
    const prendas = [];
    const prendasRows = document.querySelectorAll('#listaPrendas > div');
    
    prendasRows.forEach((fila, index) => {
        const nombrePrenda = fila.querySelector('.nombre_prenda')?.value;
        const observaciones = fila.querySelector('.observaciones')?.value;
        
        const ubicacionesChecked = Array.from(
            fila.querySelectorAll('.ubicaciones-lista')[0]?.querySelectorAll('[data-ubicacion]') || []
        ).map(tag => tag.getAttribute('data-ubicacion'));
        
        // Recopilar tallas y cantidades
        const tallaCantidadInputs = fila.querySelectorAll('.talla-cantidad-row');
        const tallaCantidadSinFiltro = Array.from(tallaCantidadInputs).map((row) => {
            const talla = row.querySelector('.talla-input')?.value?.trim();
            const cantidad = row.querySelector('.cantidad-input')?.value;
            return { talla, cantidad: parseInt(cantidad) };
        });
        
        const tallaCantidad = tallaCantidadSinFiltro.filter(tc => tc.talla && tc.talla.length > 0);
        
        // Recopilar imágenes del array almacenado en la fila
        const imagenesArray = fila.imagenesAgregadas || [];
        
        // Validar datos
        if (!nombrePrenda || nombrePrenda.trim() === '') {
            Swal.fire({
                icon: 'error',
                title: 'Campo vacío',
                text: `Prenda ${index + 1}: Rellena el nombre de la prenda`
            });
            throw new Error('VALIDATION_ERROR');
        }
        
        if (ubicacionesChecked.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin ubicaciones',
                text: `${nombrePrenda}: Debes seleccionar al menos una ubicación`
            });
            throw new Error('VALIDATION_ERROR');
        }
        
        if (tallaCantidad.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin tallas',
                text: `${nombrePrenda}: Debes agregar al menos una talla con cantidad`
            });
            throw new Error('VALIDATION_ERROR');
        }
        
        // Extraer datos de variaciones
        const variacionesPrenda = {};
        

        
        // Verificar que la tabla existe
        const tablaVariaciones = fila.querySelector('.variaciones-table');

        
        // MANGA
        const mangaInput = fila.querySelector('.variacion-manga');

        const manga = mangaInput?.value?.trim();
        const obsMangaInput = fila.querySelector('.observacion-manga');
        const obsManga = obsMangaInput?.value?.trim();

        if (manga) {
            variacionesPrenda.manga = {
                opcion: manga,
                observacion: obsManga || null
            };
        }
        
        // BOLSILLOS
        const bolsillosInput = fila.querySelector('.variacion-bolsillos');

        const bolsillos = bolsillosInput?.value?.trim();
        const obsBolsillosInput = fila.querySelector('.observacion-bolsillos');
        const obsBolsillos = obsBolsillosInput?.value?.trim();

        if (bolsillos) {
            variacionesPrenda.bolsillos = {
                opcion: bolsillos,
                observacion: obsBolsillos || null
            };
        }
        
        // BROCHE/BOTÓN (es un SELECT)
        const brocheSelect = fila.querySelector('.variacion-broche');

        const broche = brocheSelect?.value?.trim();
        const obsBrocheInput = fila.querySelector('.observacion-broche');
        const obsBroche = obsBrocheInput?.value?.trim();

        if (broche && broche !== '') {
            variacionesPrenda.broche_boton = {
                opcion: broche,
                observacion: obsBroche || null
            };
        }
        

        
        prendas.push({
            nombre_prenda: nombrePrenda,
            observaciones: observaciones,
            ubicaciones: ubicacionesChecked,
            talla_cantidad: tallaCantidad,
            imagenes_files: imagenesArray,  // Array de File objects
            variaciones_prenda: Object.keys(variacionesPrenda).length > 0 ? variacionesPrenda : null  // JSON con variaciones
        });
        

    });
    
    return prendas.length > 0 ? prendas : null;
}

// =========================================================
// 6.5 GUARDAR TODAS LAS TÉCNICAS EN BD (cuando se envía formulario)
// =========================================================

async function guardarTecnicasEnBD() {
    if (tecnicasAgregadas.length === 0) {
        return true; // Sin errores, continuar
    }
    
    const logoCotId = document.getElementById('logoCotizacionId')?.value;
    
    if (!logoCotId) {

        return false;
    }
    
    try {
        for (const tecnica of tecnicasAgregadas) {

            
            // Validar que tenga tipo_logo
            if (!tecnica.tipo_logo || !tecnica.tipo_logo.id) {

                continue;
            }
            



            
            // Crear FormData para enviar archivos
            const formData = new FormData();
            formData.append('logo_cotizacion_id', parseInt(logoCotId));
            formData.append('tipo_logo_id', tecnica.tipo_logo.id);
            formData.append('es_combinada', tecnica.es_combinada || false);
            
            // Solo agregar grupo_combinado si tiene valor
            if (tecnica.grupo_combinado !== null && tecnica.grupo_combinado !== undefined) {
                formData.append('grupo_combinado', tecnica.grupo_combinado);
            }
            
            // Agregar datos de prendas (sin imágenes File)
            const prendasSinArchivos = tecnica.prendas.map(prenda => ({
                nombre_prenda: prenda.nombre_prenda,
                observaciones: prenda.observaciones,
                ubicaciones: prenda.ubicaciones,
                talla_cantidad: prenda.talla_cantidad,
                variaciones_prenda: prenda.variaciones_prenda || null,
                imagenes_data_urls: prenda.imagenes_data_urls || [] // URLs para BD si las hay
            }));
            

            prendasSinArchivos.forEach((p, idx) => {


            });
            
            formData.append('prendas', JSON.stringify(prendasSinArchivos));
            
            // Agregar archivos de imágenes de cada prenda
            let totalArchivos = 0;
            tecnica.prendas.forEach((prenda, prendasIdx) => {
                if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {

                    prenda.imagenes_files.forEach((archivo, imgIdx) => {
                        const fieldName = `imagenes_prenda_${prendasIdx}_${imgIdx}`;

                        formData.append(fieldName, archivo);
                        totalArchivos++;
                    });
                }
            });
            // DEBUG: Mostrar contenido de FormData

            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {

                } else {

                }
            }
            

            const response = await fetch('/api/logo-cotizacion-tecnicas/agregar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            

            const data = await response.json();
            

            
            if (!data.success) {
                console.error(' Guardar Técnica - Error DETALLADO:', { 
                    response: data,
                    errors: JSON.stringify(data.errors || {}, null, 2),
                    message: data.message
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Error 422 - Validación',
                    html: '<pre style="text-align: left; font-size: 12px">' + JSON.stringify(data.errors || {}, null, 2) + '</pre>',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
            

        }
        
        tecnicasAgregadas = []; // Limpiar array temporal

        return true;
        
    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar técnicas: ' + (error.message || 'Error desconocido')
        });
        return false;
    }
}

// =========================================================
// 7. CARGAR Y RENDERIZAR TÉCNICAS AGREGADAS
// =========================================================

function cargarTecnicasAgregadas() {
    // Solo renderiza lo que está en tecnicasAgregadas (sin llamar a API)
    renderizarTecnicasAgregadas();
}

function renderizarTecnicasAgregadas() {
    const container = document.getElementById('tecnicas_agregadas');
    const sinTecnicas = document.getElementById('sin_tecnicas');
    
    if (!container) {

        return;
    }
    
    container.innerHTML = '';
    
    if (tecnicasAgregadas.length === 0) {
        if (sinTecnicas) sinTecnicas.style.display = 'block';
        return;
    }
    
    if (sinTecnicas) sinTecnicas.style.display = 'none';
    
    // PASO 1: Agrupar por NOMBRE DE PRENDA (no por técnica)
    // Esto evita duplicación cuando una prenda tiene múltiples técnicas
    const prendasMap = {};
    const imagenesParaCargar = []; // Array para guardar imágenes de File que necesitan ser procesadas
    
    tecnicasAgregadas.forEach((tecnica, tecnicaIndex) => {
        tecnica.prendas.forEach(prenda => {
            const nombrePrenda = prenda.nombre_prenda || 'SIN NOMBRE';
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: prenda.observaciones,
                    talla_cantidad: prenda.talla_cantidad || [],
                    tecnicas: [], // Array de técnicas para esta prenda
                    imagenes: [] // Array de imágenes con técnica
                };
            }
            
            // Agregar técnica y sus imágenes
            prendasMap[nombrePrenda].tecnicas.push({
                tecnica: tecnica,
                tecnicaIndex: tecnicaIndex
            });
            
            // Agregar imágenes con referencia a la técnica
            // Para técnicas combinadas: imagenes_data_urls (URLs procesadas)
            if (prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0) {
                prenda.imagenes_data_urls.forEach(img => {
                    prendasMap[nombrePrenda].imagenes.push({
                        data: img,
                        tecnica: tecnica.tipo_logo.nombre,
                        tecnicaColor: tecnica.tipo_logo.color
                    });
                });
            }
            // Para técnicas simples: imagenes_files (File objects sin procesar)
            else if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                prenda.imagenes_files.forEach(archivo => {
                    imagenesParaCargar.push({
                        archivo: archivo,
                        nombrePrenda: nombrePrenda,
                        tecnica: tecnica.tipo_logo.nombre,
                        tecnicaColor: tecnica.tipo_logo.color
                    });
                });
            }
        });
    });
    

    
    // Procesar las imágenes de File objects de forma asíncrona
    imagenesParaCargar.forEach(imgData => {
        const reader = new FileReader();
        reader.onload = (e) => {
            prendasMap[imgData.nombrePrenda].imagenes.push({
                data: e.target.result,
                tecnica: imgData.tecnica,
                tecnicaColor: imgData.tecnicaColor
            });
            
            // Actualizar la tarjeta si ya está renderizada
            const tarjeta = document.querySelector(`[data-prenda-nombre="${imgData.nombrePrenda}"]`);
            if (tarjeta) {
                const imgSection = tarjeta.querySelector('.imagenes-section');
                if (imgSection && prendasMap[imgData.nombrePrenda].imagenes.length > 0) {
                    imgSection.style.display = 'block';
                    const divGrid = imgSection.querySelector('div:last-child');
                    if (divGrid) {
                        divGrid.innerHTML = prendasMap[imgData.nombrePrenda].imagenes.map((img, imgIdx) => `
                            <div style="position: relative; border-radius: 4px; overflow: hidden; border: 2px solid ${img.tecnicaColor}; aspect-ratio: 1; width: 100%; max-width: 120px;">
                                <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Imagen prenda">
                                <div style="
                                    position: absolute;
                                    bottom: 0;
                                    left: 0;
                                    right: 0;
                                    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
                                    padding: 0.4rem;
                                    display: flex;
                                    align-items: flex-end;
                                ">
                                    <span style="
                                        color: white;
                                        font-size: 0.65rem;
                                        font-weight: 600;
                                        background: ${img.tecnicaColor};
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
            }
        };
        reader.readAsDataURL(imgData.archivo);
    });
    
    // NUEVO DISEÑO: Contenedor con tarjetas - ANCHO FIJO como en la imagen
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; margin-bottom: 20px; max-width: 1400px;';
    
    // PASO 2: Renderizar TARJETAS por prenda
    let prendasContador = 0;
    Object.entries(prendasMap).forEach(([nombrePrenda, datosPrenda]) => {
        prendasContador++;
        
        // TARJETA DE LA PRENDA
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
        
        // HEADER CON TÉCNICAS Y NOMBRE - DISEÑO ACTUALIZADO COMO LA IMAGEN
        let headerHTML = '<div style="background: linear-gradient(135deg, #0a5ba3 0%, #084b8a 100%); color: white; padding: 1.2rem; border-bottom: 1px solid #ddd;">';
        headerHTML += '<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">';
        headerHTML += '<div>';
        headerHTML += '<h4 style="margin: 0 0 0.8rem 0; font-size: 1.1rem; font-weight: 700;">Prenda ' + prendasContador + ': ' + nombrePrenda + '</h4>';
        headerHTML += '<div style="display: flex; flex-wrap: wrap; gap: 0.2rem; align-items: center;" class="tecnicas-header">';
        
        datosPrenda.tecnicas.forEach((tecData, idx) => {
            const tecnica = tecData.tecnica;
            headerHTML += `
                <span style="
                    background: ${tecnica.tipo_logo.color};
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 20px;
                    font-weight: 600;
                    font-size: 0.9rem;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                " class="tecnica-badge" data-tecnica-idx="${tecData.tecnicaIndex}">
                    ${tecnica.tipo_logo.nombre}
                    ${datosPrenda.tecnicas.length > 1 ? `<button type="button" class="btn-eliminar-tecnica" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; padding: 0 4px; border-radius: 2px; font-size: 1rem;" data-tecnica-idx="${tecData.tecnicaIndex}">✕</button>` : ''}
                </span>
            `;
        });
        
        headerHTML += '</div>';
        headerHTML += '</div>';
        
        // Botones de editar y eliminar en el header
        headerHTML += '<div style="display: flex; gap: 0.6rem; flex-shrink: 0;">';
        headerHTML += `<button type="button" class="btn-editar-prenda" style="
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        " data-tecnica-indices="${datosPrenda.tecnicas.map(t => t.tecnicaIndex).join(',')}" data-prenda-nombre="${nombrePrenda}" title="Editar">
            
        </button>`;
        headerHTML += `<button type="button" class="btn-eliminar-prenda" style="
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        " data-tecnica-indices="${datosPrenda.tecnicas.map(t => t.tecnicaIndex).join(',')}" title="Eliminar">
            🗑️
        </button>`;
        headerHTML += '</div>';
        headerHTML += '</div></div>';
        
        // CUERPO CON CONTENIDO DE LA PRENDA - DISEÑO ACTUALIZADO
        let bodyHTML = '<div style="padding: 1.5rem;">';
        
        // SECCIÓN DE UBICACIONES (por técnica - SOLO de la prenda actual)
        const ubicacionesPorTecnica = {};
        datosPrenda.tecnicas.forEach(tecData => {
            const tecnica = tecData.tecnica;
            if (tecnica.prendas && tecnica.prendas.length > 0) {
                // Filtrar solo la prenda que coincide con nombrePrenda
                tecnica.prendas.forEach(p => {
                    if (p.nombre_prenda === nombrePrenda && p.ubicaciones && p.ubicaciones.length > 0) {
                        if (!ubicacionesPorTecnica[tecnica.tipo_logo.nombre]) {
                            ubicacionesPorTecnica[tecnica.tipo_logo.nombre] = [];
                        }
                        ubicacionesPorTecnica[tecnica.tipo_logo.nombre].push(...p.ubicaciones);
                    }
                });
            }
        });
        
        if (Object.keys(ubicacionesPorTecnica).length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1.2rem;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Ubicaciones:
                    </h6>
            `;
            
            Object.entries(ubicacionesPorTecnica).forEach(([nombreTec, ubicaciones]) => {
                bodyHTML += `
                    <div style="margin-bottom: 0.8rem; padding-left: 1rem; border-left: 4px solid #3b82f6;">
                        ${ubicaciones.map(ub => `
                            <div style="
                                font-size: 0.9rem;
                                color: #1e293b;
                                margin-bottom: 0.3rem;
                                text-transform: capitalize;
                            ">
                                • ${nombreTec} - ${ub}
                            </div>
                        `).join('')}
                    </div>
                `;
            });
            
            bodyHTML += '</div>';
        }
        
        // TALLAS
        if (datosPrenda.talla_cantidad && datosPrenda.talla_cantidad.length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1.2rem;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Tallas:
                    </h6>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                        ${datosPrenda.talla_cantidad.map(tc => `
                            <span style="
                                background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
                                color: white;
                                padding: 0.5rem 1rem;
                                border-radius: 3px;
                                font-size: 0.9rem;
                                font-weight: 600;
                            ">
                                ${tc.talla.toUpperCase()}: <strong>${tc.cantidad}</strong>
                            </span>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        // SECCIÓN DE IMÁGENES CON INDICADOR DE TÉCNICA
        if (datosPrenda.imagenes && datosPrenda.imagenes.length > 0) {
            bodyHTML += `
                <div class="imagenes-section" style="margin: 1.2rem 0;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Imágenes:
                    </h6>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        ${datosPrenda.imagenes.map((img, imgIdx) => `
                            <div style="position: relative; border-radius: 4px; overflow: hidden; border: 2px solid ${img.tecnicaColor}; aspect-ratio: 1; width: auto; max-width: 120px; flex: 0 1 auto;">
                                <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Imagen prenda">
                                <div style="
                                    position: absolute;
                                    bottom: 0;
                                    left: 0;
                                    right: 0;
                                    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
                                    padding: 0.4rem;
                                    display: flex;
                                    align-items: flex-end;
                                ">
                                    <span style="
                                        color: white;
                                        font-size: 0.65rem;
                                        font-weight: 600;
                                        background: ${img.tecnicaColor};
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
        } else {
            // Crear sección vacía pero oculta para poder actualizar después
            bodyHTML += `
                <div class="imagenes-section" style="margin: 1.2rem 0; display: none;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Imágenes:
                    </h6>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.2rem;">
                    </div>
                </div>
            `;
        }
        
        // VARIACIONES DE PRENDA
        // Obtener variaciones de la primera prenda que coincida (todas deben tener las mismas)
        let variacionesPrenda = null;
        datosPrenda.tecnicas.forEach(tecData => {
            const tecnica = tecData.tecnica;
            if (tecnica.prendas && tecnica.prendas.length > 0) {
                tecnica.prendas.forEach(p => {
                    if (p.nombre_prenda === nombrePrenda) {
                        if (p.variaciones_prenda && Object.keys(p.variaciones_prenda).length > 0) {
                            variacionesPrenda = p.variaciones_prenda;
                        }
                    }
                });
            }
        });
        
        if (variacionesPrenda && Object.keys(variacionesPrenda).length > 0) {
            bodyHTML += `
                <div style="margin-bottom: 1rem; border: 1px solid #e0e7ff; border-radius: 6px; padding: 0.8rem; background: #f8f9ff;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: #3730a3; display: block; margin-bottom: 0.6rem;">
                         Variaciones:
                    </span>
                    <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse;">
                        <tbody>
            `;
            
            // MANGA
            if (variacionesPrenda.manga && variacionesPrenda.manga.opcion) {
                bodyHTML += `
                    <tr style="border-bottom: 1px solid #e0e7ff;">
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Manga:</td>
                        <td style="padding: 0.4rem; color: #475569;">${variacionesPrenda.manga.opcion}</td>
                        ${variacionesPrenda.manga.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.manga.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            // BOLSILLOS
            if (variacionesPrenda.bolsillos && variacionesPrenda.bolsillos.opcion) {
                bodyHTML += `
                    <tr style="border-bottom: 1px solid #e0e7ff;">
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Bolsillos:</td>
                        <td style="padding: 0.4rem; color: #475569;">${variacionesPrenda.bolsillos.opcion}</td>
                        ${variacionesPrenda.bolsillos.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.bolsillos.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            // BROCHE/BOTÓN
            if (variacionesPrenda.broche_boton && variacionesPrenda.broche_boton.opcion) {
                const brocheColor = variacionesPrenda.broche_boton.opcion === 'BOTÓN' ? '#059669' : '#dc2626';
                bodyHTML += `
                    <tr>
                        <td style="padding: 0.4rem; font-weight: 600; color: #334155; width: 35%;">Broche/Botón:</td>
                        <td style="padding: 0.4rem;">
                            <span style="
                                background: ${brocheColor};
                                color: white;
                                padding: 0.3rem 0.6rem;
                                border-radius: 3px;
                                font-weight: 600;
                                display: inline-block;
                                font-size: 0.75rem;
                            ">
                                ${variacionesPrenda.broche_boton.opcion}
                            </span>
                        </td>
                        ${variacionesPrenda.broche_boton.observacion ? `<td style="padding: 0.4rem; color: #64748b; font-size: 0.7rem; font-style: italic;">${variacionesPrenda.broche_boton.observacion}</td>` : ''}
                    </tr>
                `;
            }
            
            bodyHTML += `
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        bodyHTML += '</div>';
        
        tarjeta.innerHTML = headerHTML + bodyHTML;
        contenedor.appendChild(tarjeta);
    });
    
    container.appendChild(contenedor);
    
    // EVENT LISTENERS para botones en el header
    document.querySelectorAll('.btn-editar-prenda').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const indicesStr = e.currentTarget.getAttribute('data-tecnica-indices');
            const nombrePrenda = e.currentTarget.getAttribute('data-prenda-nombre');
            const indices = indicesStr.split(',').map(Number);
            editarTecnicaDelGrupo(indices, nombrePrenda);
        });
    });
    
    document.querySelectorAll('.btn-eliminar-prenda').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const indicesStr = e.currentTarget.getAttribute('data-tecnica-indices');
            const indices = indicesStr.split(',').map(Number);
            eliminarTecnicaDelGrupo(indices);
        });
    });
}

function editarTecnicaDelGrupo(tecnicaIndices, nombrePrendaFiltro = null) {
    // tecnicaIndices es un array de índices de técnicas
    const tecnicasDelGrupo = tecnicaIndices.map(idx => tecnicasAgregadas[idx]);
    
    if (tecnicasDelGrupo.length === 0) {

        return;
    }
    

    
    // Agrupar por NOMBRE DE PRENDA y mantener datos de CADA TÉCNICA
    const prendasMap = {};
    const datosPorTecnica = {}; // Guardar datos específicos de cada técnica
    
    tecnicasDelGrupo.forEach((tecnica, tecnicaIdx) => {
        tecnica.prendas.forEach(prenda => {
            const nombrePrenda = prenda.nombre_prenda || 'SIN NOMBRE';
            
            // FILTRO: Si se proporcionó nombrePrendaFiltro, solo procesar esa prenda
            if (nombrePrendaFiltro && nombrePrenda !== nombrePrendaFiltro) {
                return; // Saltar esta prenda
            }
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: prenda.nombre_prenda,
                    observaciones: prenda.observaciones,
                    talla_cantidad: prenda.talla_cantidad,
                    variaciones_prenda: prenda.variaciones_prenda || {}, // Incluir variaciones
                    tecnicas: [],
                    tecnicasData: {} // Guardar datos por técnica
                };
            }
            
            prendasMap[nombrePrenda].tecnicas.push(tecnica);
            
            // Guardar los datos específicos de esta técnica para esta prenda
            prendasMap[nombrePrenda].tecnicasData[tecnicaIdx] = {
                ubicaciones: prenda.ubicaciones,
                imagenes_data_urls: prenda.imagenes_data_urls,
                imagenes_files: prenda.imagenes_files
            };
        });
    });
    
    // Convertir map a array
    const todasLasPrendas = Object.values(prendasMap);
    

    
    // Construir HTML de las prendas
    let prendasHTML = '<div style="max-height: 600px; overflow-y: auto;">';
    
    todasLasPrendas.forEach((prenda, prendaIdx) => {
        let tallaHTML = '';
        (prenda.talla_cantidad || []).forEach((tc, tcIdx) => {
            tallaHTML += `
                <div class="edit-talla-row" style="display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center; margin-bottom: 8px;" data-prenda-idx="${prendaIdx}" data-talla-idx="${tcIdx}">
                    <input type="text" class="edit-talla" value="${tc.talla || ''}" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    <input type="number" class="edit-cantidad" value="${tc.cantidad || 1}" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                    <button type="button" class="edit-btn-eliminar-talla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer;">✕</button>
                </div>
            `;
        });
        
        // Construir tabla de variaciones
        let variacionesHTML = '';
        const variacionesPrenda = prenda.variaciones_prenda || {};
        if (Object.keys(variacionesPrenda).length > 0) {
            variacionesHTML = `
                <div style="margin-bottom: 12px; border: 1px solid #e0e7ff; border-radius: 6px; padding: 0.8rem; background: #f8f9ff;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.6rem; font-size: 0.85rem; color: #3730a3;"> Variaciones</label>
                    <table style="width: 100%; font-size: 0.75rem; border-collapse: collapse;">
                        <tbody>
            `;
            
            // MANGA
            const manga = variacionesPrenda.manga || {};
            variacionesHTML += `
                <tr style="border-bottom: 1px solid #e0e7ff;">
                    <td style="padding: 0.4rem; font-weight: 600; color: #334155;">Manga:</td>
                    <td style="padding: 0.4rem;"><input type="text" class="edit-var-manga" data-prenda-idx="${prendaIdx}" value="${manga.opcion || ''}" placeholder="Corta, Larga..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;"></td>
                    <td style="padding: 0.4rem;"><input type="text" class="edit-var-manga-obs" data-prenda-idx="${prendaIdx}" value="${manga.observacion || ''}" placeholder="Obs" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;"></td>
                </tr>
            `;
            
            // BOLSILLOS
            const bolsillos = variacionesPrenda.bolsillos || {};
            variacionesHTML += `
                <tr style="border-bottom: 1px solid #e0e7ff;">
                    <td style="padding: 0.4rem; font-weight: 600; color: #334155;">Bolsillos:</td>
                    <td style="padding: 0.4rem;"><input type="text" class="edit-var-bolsillos" data-prenda-idx="${prendaIdx}" value="${bolsillos.opcion || ''}" placeholder="Con/Sin tapa..." style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;"></td>
                    <td style="padding: 0.4rem;"><input type="text" class="edit-var-bolsillos-obs" data-prenda-idx="${prendaIdx}" value="${bolsillos.observacion || ''}" placeholder="Obs" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;"></td>
                </tr>
            `;
            
            // BROCHE/BOTÓN
            const broche = variacionesPrenda.broche_boton || {};
            variacionesHTML += `
                <tr>
                    <td style="padding: 0.4rem; font-weight: 600; color: #334155;">Broche/Botón:</td>
                    <td style="padding: 0.4rem;">
                        <select class="edit-var-broche" data-prenda-idx="${prendaIdx}" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;">
                            <option value="">-- Seleccionar --</option>
                            <option value="BROCHE" ${broche.opcion === 'BROCHE' ? 'selected' : ''}>BROCHE</option>
                            <option value="BOTÓN" ${broche.opcion === 'BOTÓN' ? 'selected' : ''}>BOTÓN</option>
                        </select>
                    </td>
                    <td style="padding: 0.4rem;"><input type="text" class="edit-var-broche-obs" data-prenda-idx="${prendaIdx}" value="${broche.observacion || ''}" placeholder="Obs" style="width: 100%; padding: 4px; border: 1px solid #ddd; border-radius: 3px; font-size: 0.75rem;"></td>
                </tr>
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        let imagenesHTML = '';
        if (prenda.tecnicas && prenda.tecnicas.length > 0) {
            imagenesHTML = `
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem;"> Imágenes por Técnica</label>
                    <div style="display: grid; gap: 12px;">
            `;
            
            prenda.tecnicas.forEach((tecnica, tecnicaIdx) => {
                const datosActuales = prenda.tecnicasData ? prenda.tecnicasData[tecnicaIdx] : {};
                const imagenesActuales = datosActuales.imagenes_data_urls || [];
                
                imagenesHTML += `
                    <div style="border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #f9f9f9;">
                        <div style="font-weight: 600; margin-bottom: 8px; color: ${tecnica.tipo_logo.color || '#333'}; font-size: 0.9rem;">${tecnica.tipo_logo.nombre}</div>
                `;
                
                // Mostrar imágenes existentes
                if (imagenesActuales && imagenesActuales.length > 0) {
                    imagenesHTML += `
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 8px;" class="edit-imagenes-container" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}">
                            ${imagenesActuales.map((img, imgIdx) => `
                                <div style="position: relative;" class="imagen-item" data-img-idx="${imgIdx}">
                                    <img src="${img}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                    <button type="button" class="edit-btn-eliminar-img" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}" data-img-idx="${imgIdx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    // Si no hay imagenes_data_urls pero sí hay imagenes_files, procesarlas
                    const archivos = datosActuales.imagenes_files || [];
                    if (archivos.length > 0) {
                        imagenesHTML += `
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 8px;" class="edit-imagenes-container" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}">
                        `;
                        
                        archivos.forEach((archivo, imgIdx) => {
                            const urlTemp = URL.createObjectURL(archivo);
                            imagenesHTML += `
                                <div style="position: relative;" class="imagen-item" data-img-idx="${imgIdx}">
                                    <img src="${urlTemp}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                    <button type="button" class="edit-btn-eliminar-img" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}" data-img-idx="${imgIdx}" data-is-file="true" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                                </div>
                            `;
                        });
                        
                        imagenesHTML += `
                            </div>
                        `;
                    }
                }
                
                imagenesHTML += `
                        <input type="file" class="edit-input-imagenes" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}" multiple accept="image/*" style="width: 100%; padding: 6px; border: 1px dashed #0369a1; border-radius: 4px; cursor: pointer; box-sizing: border-box; font-size: 0.85rem;">
                    </div>
                `;
            });
            
            imagenesHTML += `
                    </div>
                </div>
            `;
        }
        
        // Construir HTML de ubicaciones por técnica
        let ubicacionesHTML = '';
        if (prenda.tecnicas && prenda.tecnicas.length > 0) {
            ubicacionesHTML = `
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem;"> Ubicaciones por Técnica</label>
                    <div style="display: grid; gap: 8px;">
            `;
            
            prenda.tecnicas.forEach((tecnica, tecnicaIdx) => {
                const datosActuales = prenda.tecnicasData ? prenda.tecnicasData[tecnicaIdx] : {};
                const ubicacionesTecnica = datosActuales.ubicaciones || [];
                const ubicacionActual = ubicacionesTecnica[0] || ''; // La primera ubicación para esta técnica
                
                ubicacionesHTML += `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f0f4f8; border-radius: 4px; border-left: 3px solid ${tecnica.tipo_logo.color || '#333'};">
                        <label style="font-weight: 600; min-width: 100px; font-size: 0.85rem; color: #333; flex-shrink: 0;">${tecnica.tipo_logo.nombre}:</label>
                        <input type="text" class="edit-ubicacion-tecnica" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}" value="${ubicacionActual}" placeholder="Ej: Pecho, Espalda, Manga..." style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.85rem;">
                    </div>
                `;
            });
            
            ubicacionesHTML += `
                    </div>
                </div>
            `;
        }

        prendasHTML += `
            <div style="
                background: #f9fafb;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 12px;
                margin-bottom: 15px;
            ">
                <div style="background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; padding: 8px; border-radius: 4px; margin-bottom: 12px;">
                    <h4 style="margin: 0; font-size: 0.9rem;">Prenda ${prendaIdx + 1}</h4>
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem;">Nombre</label>
                    <input type="text" class="edit-nombre-prenda" data-prenda-idx="${prendaIdx}" value="${prenda.nombre_prenda || ''}" placeholder="POLO, CAMISA..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-transform: uppercase;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem;">Observaciones</label>
                    <textarea class="edit-observaciones-prenda" data-prenda-idx="${prendaIdx}" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.85rem;">${prenda.observaciones || ''}</textarea>
                </div>
                
                ${ubicacionesHTML}
                
                ${variacionesHTML}
                
                ${imagenesHTML}
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem;">Tallas y Cantidades</label>
                    <div class="edit-tallas-container-prenda" data-prenda-idx="${prendaIdx}" style="display: grid; gap: 8px; margin-bottom: 8px;">
                        ${tallaHTML}
                    </div>
                    <button type="button" class="edit-btn-agregar-talla" data-prenda-idx="${prendaIdx}" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%; font-size: 0.85rem;">+ Agregar talla</button>
                </div>
            </div>
        `;
    });
    
    prendasHTML += '</div>';
    
    Swal.fire({
        title: `Editar ${tecnicasDelGrupo.map(t => t.tipo_logo.nombre).join(' + ')}`,
        width: '700px',
        html: prendasHTML,
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            // Agregar evento a botones de agregar talla
            document.querySelectorAll('.edit-btn-agregar-talla').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const prendaIdx = btn.getAttribute('data-prenda-idx');
                    const container = document.querySelector(`.edit-tallas-container-prenda[data-prenda-idx="${prendaIdx}"]`);
                    
                    const nuevaTalla = document.createElement('div');
                    nuevaTalla.className = 'edit-talla-row';
                    nuevaTalla.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center; margin-bottom: 8px;';
                    nuevaTalla.dataset.prendaIdx = prendaIdx;
                    nuevaTalla.innerHTML = `
                        <input type="text" class="edit-talla" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <input type="number" class="edit-cantidad" value="1" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <button type="button" class="edit-btn-eliminar-talla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer;">✕</button>
                    `;
                    
                    nuevaTalla.querySelector('.edit-btn-eliminar-talla').addEventListener('click', (e) => {
                        e.preventDefault();
                        nuevaTalla.remove();
                    });
                    
                    container.appendChild(nuevaTalla);
                });
            });
            
            // Eliminar imágenes existentes
            document.querySelectorAll('.edit-btn-eliminar-img').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const prendaIdx = parseInt(btn.getAttribute('data-prenda-idx'));
                    const tecnicaIdx = parseInt(btn.getAttribute('data-tecnica-idx'));
                    const imgIdx = parseInt(btn.getAttribute('data-img-idx'));
                    const isFile = btn.getAttribute('data-is-file') === 'true';
                    const imagenItem = btn.closest('.imagen-item');
                    
                    // Eliminar del array
                    if (todasLasPrendas[prendaIdx].tecnicasData && todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx]) {
                        if (isFile) {
                            todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_files.splice(imgIdx, 1);
                        } else {
                            todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls.splice(imgIdx, 1);
                        }
                    }
                    
                    // Eliminar del DOM
                    imagenItem.remove();
                });
            });
            
            // Agregar nuevas imágenes
            document.querySelectorAll('.edit-input-imagenes').forEach(input => {
                input.addEventListener('change', (e) => {
                    const prendaIdx = parseInt(input.getAttribute('data-prenda-idx'));
                    const tecnicaIdx = parseInt(input.getAttribute('data-tecnica-idx'));
                    
                    if (tecnicaIdx === undefined || tecnicaIdx === null) {

                        return;
                    }
                    
                    const container = document.querySelector(`.edit-imagenes-container[data-prenda-idx="${prendaIdx}"][data-tecnica-idx="${tecnicaIdx}"]`);
                    
                    // Obtener cantidad actual de imágenes
                    const imagenesActuales = todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls || [];
                    const limitImgs = 3;
                    
                    const files = Array.from(e.target.files);
                    let archivosAgregados = 0;
                    
                    files.forEach(file => {
                        // Validar límite
                        if (imagenesActuales.length + archivosAgregados >= limitImgs) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Límite de imágenes',
                                text: `Solo se pueden adjuntar máximo ${limitImgs} imágenes por técnica. Elimina una imagen para agregar otra.`,
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            const dataUrl = event.target.result;
                            
                            // Agregar al array de prendas - específicamente a los datos de la técnica
                            if (!todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls) {
                                todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls = [];
                            }
                            todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls.push(dataUrl);
                            
                            // Agregar al DOM
                            const newImgIdx = todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls.length - 1;
                            const imagenItem = document.createElement('div');
                            imagenItem.style.cssText = 'position: relative;';
                            imagenItem.className = 'imagen-item';
                            imagenItem.dataset.imgIdx = newImgIdx;
                            imagenItem.innerHTML = `
                                <img src="${dataUrl}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                <button type="button" class="edit-btn-eliminar-img" data-prenda-idx="${prendaIdx}" data-tecnica-idx="${tecnicaIdx}" data-img-idx="${newImgIdx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                            `;
                            
                            imagenItem.querySelector('.edit-btn-eliminar-img').addEventListener('click', (deleteE) => {
                                deleteE.preventDefault();
                                const delIdx = parseInt(imagenItem.dataset.imgIdx);
                                todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].imagenes_data_urls.splice(delIdx, 1);
                                imagenItem.remove();
                            });
                            
                            if (!container) {
                                // Crear el contenedor si no existe
                                const newContainer = document.createElement('div');
                                newContainer.className = 'edit-imagenes-container';
                                newContainer.dataset.prendaIdx = prendaIdx;
                                newContainer.dataset.tecnicaIdx = tecnicaIdx;
                                newContainer.style.cssText = 'display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-bottom: 8px;';
                                newContainer.appendChild(imagenItem);
                                
                                const inputParent = input.closest('div');
                                inputParent.parentElement.insertBefore(newContainer, inputParent);
                            } else {
                                container.appendChild(imagenItem);
                            }
                        };
                        reader.readAsDataURL(file);
                        archivosAgregados++;
                    });
                    
                    // Limpiar input
                    e.target.value = '';
                });
            });
            
            // Botones eliminar talla existentes
            document.querySelectorAll('.edit-btn-eliminar-talla').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    btn.closest('.edit-talla-row').remove();
                });
            });
        },
        preConfirm: () => {
            // Validar que al menos una prenda tenga nombre
            let prendasActualizadas = {};
            let valido = true;
            let contadorPrendas = 0;
            
            // Obtener todos los índices de prendas del DOM
            const prendaIdxs = new Set();
            document.querySelectorAll('[data-prenda-idx]').forEach((elem) => {
                prendaIdxs.add(elem.getAttribute('data-prenda-idx'));
            });
            

            
            prendaIdxs.forEach((prendaIdxStr) => {
                const prendaIdx = parseInt(prendaIdxStr);
                
                const nombreInput = document.querySelector(`.edit-nombre-prenda[data-prenda-idx="${prendaIdxStr}"]`);
                const nombrePrenda = nombreInput ? nombreInput.value.trim().toUpperCase() : '';
                
                if (!nombrePrenda) {

                    valido = false;
                    return;
                }
                
                const observacionesInput = document.querySelector(`.edit-observaciones-prenda[data-prenda-idx="${prendaIdxStr}"]`);
                const observaciones = observacionesInput ? observacionesInput.value.trim() : '';
                
                // Capturar ubicaciones por técnica y actualizar tecnicasData
                document.querySelectorAll(`.edit-ubicacion-tecnica[data-prenda-idx="${prendaIdxStr}"]`).forEach(input => {
                    const tecnicaIdx = parseInt(input.getAttribute('data-tecnica-idx'));
                    const ubicacion = input.value.trim();
                    
                    // Actualizar la ubicación en tecnicasData
                    if (todasLasPrendas[prendaIdx].tecnicasData && todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx]) {
                        todasLasPrendas[prendaIdx].tecnicasData[tecnicaIdx].ubicaciones = [ubicacion];

                    }
                });
                
                // Capturar variaciones
                const variacionesPrenda = {};
                const mangaInput = document.querySelector(`.edit-var-manga[data-prenda-idx="${prendaIdxStr}"]`);
                if (mangaInput && mangaInput.value.trim()) {
                    variacionesPrenda.manga = {
                        opcion: mangaInput.value.trim(),
                        observacion: document.querySelector(`.edit-var-manga-obs[data-prenda-idx="${prendaIdxStr}"]`)?.value.trim() || null
                    };
                }
                
                const bolsillosInput = document.querySelector(`.edit-var-bolsillos[data-prenda-idx="${prendaIdxStr}"]`);
                if (bolsillosInput && bolsillosInput.value.trim()) {
                    variacionesPrenda.bolsillos = {
                        opcion: bolsillosInput.value.trim(),
                        observacion: document.querySelector(`.edit-var-bolsillos-obs[data-prenda-idx="${prendaIdxStr}"]`)?.value.trim() || null
                    };
                }
                
                const brocheInput = document.querySelector(`.edit-var-broche[data-prenda-idx="${prendaIdxStr}"]`);
                if (brocheInput && brocheInput.value) {
                    variacionesPrenda.broche_boton = {
                        opcion: brocheInput.value,
                        observacion: document.querySelector(`.edit-var-broche-obs[data-prenda-idx="${prendaIdxStr}"]`)?.value.trim() || null
                    };
                }
                
                const nuevasTallas = [];
                document.querySelectorAll(`.edit-talla-row[data-prenda-idx="${prendaIdxStr}"]`).forEach(row => {
                    const tallaInput = row.querySelector('.edit-talla');
                    const cantidadInput = row.querySelector('.edit-cantidad');
                    const talla = tallaInput ? tallaInput.value.trim() : '';
                    const cantidad = cantidadInput ? cantidadInput.value : '';
                    
                    if (talla && cantidad) {
                        nuevasTallas.push({ talla, cantidad: parseInt(cantidad) });
                    }
                });
                
                if (nuevasTallas.length === 0) {

                    valido = false;
                    return;
                }
                
                prendasActualizadas[prendaIdx] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: observaciones,
                    talla_cantidad: nuevasTallas,
                    variaciones_prenda: Object.keys(variacionesPrenda).length > 0 ? variacionesPrenda : null,
                    tecnicasData: todasLasPrendas[prendaIdx].tecnicasData || {}
                };
                

                contadorPrendas++;
            });
            
            if (!valido || contadorPrendas === 0) {
                Swal.showValidationMessage('Completa todos los campos de cada prenda y agrega al menos una talla');
                return false;
            }
            

            return prendasActualizadas;
        }
    }).then((result) => {

        
        if (result.isConfirmed && result.value) {



            
            // Actualizar TODAS las prendas del grupo usando índices numéricos
            todasLasPrendas.forEach((prenda, prendaIdx) => {

                
                if (result.value[prendaIdx]) {

                    prenda.nombre_prenda = result.value[prendaIdx].nombre_prenda;
                    prenda.observaciones = result.value[prendaIdx].observaciones;
                    prenda.talla_cantidad = result.value[prendaIdx].talla_cantidad;
                    prenda.variaciones_prenda = result.value[prendaIdx].variaciones_prenda;
                    prenda.tecnicasData = result.value[prendaIdx].tecnicasData;

                } else {

                }
            });
            
            // También actualizar en las técnicas del grupo original
            tecnicasDelGrupo.forEach((tecnica, tecnicaIdx) => {
                tecnica.prendas.forEach((prenda, prendaIdxLocal) => {
                    const prendaActualizado = todasLasPrendas[prendaIdxLocal];
                    if (prendaActualizado && result.value[prendaIdxLocal]) {

                        prenda.nombre_prenda = prendaActualizado.nombre_prenda;
                        prenda.observaciones = prendaActualizado.observaciones;
                        prenda.talla_cantidad = prendaActualizado.talla_cantidad;
                        prenda.variaciones_prenda = prendaActualizado.variaciones_prenda;
                        
                        // Obtener datos específicos de esta técnica
                        if (prendaActualizado.tecnicasData && prendaActualizado.tecnicasData[tecnicaIdx]) {
                            const datosEstaTecnica = prendaActualizado.tecnicasData[tecnicaIdx];
                            prenda.ubicaciones = datosEstaTecnica.ubicaciones || [];
                            prenda.imagenes_data_urls = datosEstaTecnica.imagenes_data_urls || [];
                        }
                    }
                });
            });
            


            renderizarTecnicasAgregadas();
        } else {

        }
    });
}

function eliminarTecnicaDelGrupo(tecnicaIndices) {
    // tecnicaIndices es un array de índices de técnicas a eliminar
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnica(s)?',
        text: 'Se eliminarán las técnicas seleccionadas',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        // Eliminar en orden inverso para no afectar los índices
        const indicesSorted = Array.isArray(tecnicaIndices) ? tecnicaIndices.sort((a, b) => b - a) : [tecnicaIndices];
        indicesSorted.forEach(idx => {
            if (tecnicasAgregadas[idx]) {
                tecnicasAgregadas.splice(idx, 1);
            }
        });
        

        renderizarTecnicasAgregadas();
    });
}

async function eliminarTecnica(tecnicaIndex) {
    const confirmacion = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnica?',
        text: 'Esta acción no se puede deshacer',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!confirmacion.isConfirmed) return;
    
    // Si es una técnica temporal (sin ID de BD), eliminar del array
    if (typeof tecnicaIndex === 'number') {
        eliminarTecnicaTemporal(tecnicaIndex);
        return;
    }
    
    // Si es una técnica guardada en BD, hacer DELETE
    try {
        const response = await fetch(
            `/api/logo-cotizacion-tecnicas/${tecnicaIndex}`,
            { 
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        );
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminada',
                text: 'Técnica eliminada correctamente'
            });
            cargarTecnicasAgregadas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {

    }
}

// =========================================================
// 8. CERRAR MODAL
// =========================================================

function cerrarModalAgregarTecnica() {
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}

// =========================================================
// 9. INICIALIZACIÓN
// =========================================================

document.addEventListener('DOMContentLoaded', async function() {
    // Obtener ID de cotización del formulario o URL
    const urlParams = new URLSearchParams(window.location.search);
    logoCotizacionId = document.getElementById('logoCotizacionId')?.value 
                    || urlParams.get('editar')
                    || null;
    
    // Cargar datos iniciales
    await cargarTiposDisponibles();
    
    if (logoCotizacionId) {
        await cargarTecnicasAgregadas();
    }
});

// =========================================================
// EXPORTAR PARA USO EN OTROS SCRIPTS
// =========================================================

window.LogoCotizacion = {
    cargarTiposDisponibles,
    abrirModalAgregarTecnica,
    agregarFilaPrenda,
    guardarTecnica,
    cerrarModalAgregarTecnica,
    cargarTecnicasAgregadas,
    eliminarTecnica,
    abrirModalValidacionTecnica,
    cerrarModalValidacionTecnica,
    guardarTecnicasEnBD
};

// =========================================================
// FUNCIONES DE VALIDACIÓN - MODAL
// =========================================================

function abrirModalValidacionTecnica() {

    
    const modal = document.getElementById('modalValidacionTecnica');
    


    
    if (modal) {

        modal.style.display = 'flex';

    } else {

    }
}

function cerrarModalValidacionTecnica() {

    const modal = document.getElementById('modalValidacionTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}

//  EXPORTAR PARA ACCESO GLOBAL EN create.blade.php
window.tecnicasAgregadas = tecnicasAgregadas;


