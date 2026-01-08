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
        console.error('❌ Error cargando tipos:', error);
    }
}

// =========================================================
// 3. RENDERIZAR CHECKBOXES DE TÉCNICAS
// =========================================================

function renderizarSelectTecnicas() {
    const container = document.getElementById('tecnicas-checkboxes');
    
    if (!container) {
        console.error('❌ Elemento tecnicas-checkboxes no encontrado');
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
                            <strong style="color: #333; display: block; font-size: 0.9rem;">✨ Datos iguales</strong>
                            <small style="color: #666; font-size: 0.8rem;">Una misma prenda para todas</small>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas" value="diferentes" style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;">🎯 Datos diferentes</strong>
                            <small style="color: #666; font-size: 0.8rem;">Cada técnica con su prenda</small>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#1e40af'; this.style.backgroundColor='#f0f4ff';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                        <input type="radio" name="opcion-tecnicas" value="por-talla" style="margin-right: 10px; cursor: pointer; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: #333; display: block; font-size: 0.9rem;">📊 Por talla</strong>
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
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                
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
                <div id="dSeccionTallasGeneral" style="display: block;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Tallas y Cantidades (General)</h3>
                    <div id="dTallaCantidadContainer" style="display: grid; gap: 8px; margin-bottom: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Se agrega dinámicamente -->
                    </div>
                    <button type="button" id="dBtnAgregarTalla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%; font-size: 0.9rem;">+ Agregar talla general</button>
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
                    console.log('✅ Prendas cargadas:', prendasDisponibles);
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
            }).catch(e => console.warn('⚠️ No se pudo guardar prenda en historial:', e));
            
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
            
            return {
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('dObservaciones').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
                tallasPorTecnica: tallasPorTecnica,
                imagenesPorTecnica: imagenesPorTecnica
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
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
            
            console.log(`✅ Técnica ${index} guardada en datosMultiplesTecnicas`);
            console.log('📦 Estado actual:', window.datosMultiplesTecnicas);
            
            // Verificar si es la última técnica
            if (index === totalTecnicas - 1) {
                // Es la última técnica, guardar todas
                console.log('🎉 Última técnica completada, guardando todas...');
                guardarTecnica();
            } else {
                // Ir a la siguiente
                console.log(`➡️ Mostrando técnica ${index + 1}`);
                window.indiceActualTecnica = index + 1;
                mostrarFormularioTecnicaDiferente(index + 1);
            }
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Cancelar flujo
            console.log('❌ Flujo cancelado por el usuario');
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
        console.error('❌ Elemento listaPrendas no encontrado');
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
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 0.85rem;">Observaciones</label>
                <textarea class="observaciones" rows="2" placeholder="Detalles adicionales (opcional)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical; font-size: 0.9rem;"></textarea>
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
        console.log('✅ Guardando técnicas con datos IGUALES');
        guardarTecnicaCombinada();
    } else if (window.modoTecnicasCombinadas === 'diferentes') {
        console.log('✅ Guardando técnicas con datos DIFERENTES');
        console.log('📦 datosMultiplesTecnicas:', window.datosMultiplesTecnicas);
        guardarTecnicasMultiples();
    } else if (window.modoTecnicasCombinadas === 'por-talla') {
        console.log('✅ Guardando técnicas POR TALLA');
        // Este modo se maneja directamente en guardarPrendaTecnicasPorTalla
    } else {
        // Modo simple: una única técnica
        console.log('✅ Guardando técnica SIMPLE');
        guardarTecnicaSimple();
    }
}

function guardarPrendaTecnicasPorTalla(datos) {
    console.log('✅ Guardando prenda con técnicas por talla');
    console.log('📦 Datos:', datos);
    
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
                console.warn(`⚠️ Técnica con ID ${tecnicaId} no encontrada`);
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
            
            console.log(`  → Talla ${talla} / ${tipo.nombre}: ${cantidad} unidades`);
            tecnicasAgregadas.push(nuevaTecnica);
        });
    });
    
    window.tecnicasAgregadas = tecnicasAgregadas;  // ✅ Sincronizar global
    console.log(`📊 Total técnicas agregadas: ${tecnicasAgregadas.length}`);
    
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
        console.error('Validación fallida:', error.message);
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
    
    console.log('✅ Técnica simple agregada:', nuevaTecnica);
    tecnicasAgregadas.push(nuevaTecnica);
    window.tecnicasAgregadas = tecnicasAgregadas;  // ✅ Sincronizar global
    console.log(`📊 Total técnicas: ${tecnicasAgregadas.length}`);
    
    // Cerrar modal y actualizar
    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
}

function guardarTecnicaCombinada(datosForm) {
    const tecnicas = window.tecnicasCombinadas;
    
    console.log(`✅ Guardando técnicas combinadas con ${tecnicas.length} técnicas`);
    
    // Si no se pasa datosForm, construirlo desde el formulario actual
    if (!datosForm) {
        console.log('📝 Construyendo datosForm desde el formulario...');
        
        // Validar que haya nombre de prenda
        const nombrePrenda = document.querySelector('.nombre_prenda')?.value.trim();
        if (!nombrePrenda) {
            console.error('❌ No se encontró nombre de prenda');
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
            console.log('🖼️ Divs de imágenes encontrados:', divImagenes.length);
            divImagenes.forEach(div => {
                const idx = parseInt(div.getAttribute('data-tecnica-idx'));
                console.log(`  Div ${idx}:`, {
                    tiene_imagenesAgregadas: !!div.imagenesAgregadas,
                    cantidad: div.imagenesAgregadas ? div.imagenesAgregadas.length : 0,
                    contenido: div.imagenesAgregadas ? div.imagenesAgregadas.map(f => f.name) : []
                });
                if (div.imagenesAgregadas && div.imagenesAgregadas.length > 0) {
                    imagenesPorTecnica[idx] = div.imagenesAgregadas;
                    console.log(`✅ Imágenes agregadas para técnica ${idx}:`, imagenesPorTecnica[idx].map(f => f.name));
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
    
    console.log('📦 Datos del formulario:', datosForm);
    
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
                    imagenes_files: datosForm.imagenesPorTecnica[idx] || [],  // Array de File objects
                    imagenes_data_urls: imagenesTecnica.map(img => img.data_url)  // Array de Data URLs
                }],
                es_combinada: true,
                grupo_combinado: grupoId  // ID numérico único para agrupar técnicas combinadas
            };
            console.log(`  → ${tipo.nombre} + ${datosForm.nombre_prenda}:`, nuevaTecnica);
            tecnicasAgregadas.push(nuevaTecnica);
        });
        
        console.log(`📊 Total técnicas agregadas: ${tecnicasAgregadas.length}`);
        console.log(`🔗 Grupo combinado asignado: ${grupoId}`);
        
        window.tecnicasAgregadas = tecnicasAgregadas;  // ✅ Sincronizar global
        
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
            console.log('📊 Llamando a renderizarLogoPrendasTecnicas() para actualizar tabla de prendas');
            renderizarLogoPrendasTecnicas();
        }
    });
}

function guardarTecnicasMultiples() {
    const datosMultiples = window.datosMultiplesTecnicas;
    
    console.log('🔍 Intentando guardar técnicas múltiples');
    console.log('📦 datosMultiples:', datosMultiples);
    
    if (!datosMultiples || datosMultiples.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No hay datos de técnicas para guardar'
        });
        console.error('❌ No hay datosMultiplesTecnicas');
        return;
    }
    
    console.log(`📊 Cantidad de técnicas: ${datosMultiples.length}`);
    
    datosMultiples.forEach((item, idx) => {
        console.log(`\n📝 Procesando técnica ${idx}:`, item);
        
        if (!item || !item.tecnica) {
            console.warn(`⚠️ Item ${idx} sin técnica`);
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
        
        console.log(`✅ Técnica procesada:`, nuevaTecnica);
        tecnicasAgregadas.push(nuevaTecnica);
    });
    
    window.tecnicasAgregadas = tecnicasAgregadas;  // ✅ Sincronizar global
    console.log(`\n📊 Total técnicas en array: ${tecnicasAgregadas.length}`);
    console.log('📋 tecnicasAgregadas:', tecnicasAgregadas);
    
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
        
        prendas.push({
            nombre_prenda: nombrePrenda,
            observaciones: observaciones,
            ubicaciones: ubicacionesChecked,
            talla_cantidad: tallaCantidad,
            imagenes_files: imagenesArray  // Array de File objects
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
        console.error('❌ Guardar Técnicas - Error:', { error: 'No se encontró logoCotizacionId' });
        return false;
    }
    
    try {
        for (const tecnica of tecnicasAgregadas) {
            console.log('📍 Procesando técnica:', tecnica.tipo_logo.nombre);
            
            // Validar que tenga tipo_logo
            if (!tecnica.tipo_logo || !tecnica.tipo_logo.id) {
                console.error('❌ Guardar Técnica - Error:', { error: 'Técnica sin tipo_logo', tecnica });
                continue;
            }
            
            console.log('✓ tipo_logo_id:', tecnica.tipo_logo.id);
            console.log('✓ es_combinada:', tecnica.es_combinada, '(tipo:', typeof tecnica.es_combinada + ')');
            console.log('✓ grupo_combinado:', tecnica.grupo_combinado);
            
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
                imagenes_data_urls: prenda.imagenes_data_urls || [] // URLs para BD si las hay
            }));
            
            console.log('✓ Prendas a guardar:', prendasSinArchivos.length);
            prendasSinArchivos.forEach((p, idx) => {
                console.log(`  Prenda ${idx}:`, p.nombre_prenda, '- Ubicaciones:', p.ubicaciones, '- Tallas:', p.talla_cantidad);
            });
            
            formData.append('prendas', JSON.stringify(prendasSinArchivos));
            
            // Agregar archivos de imágenes de cada prenda
            let totalArchivos = 0;
            tecnica.prendas.forEach((prenda, prendasIdx) => {
                if (prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                    console.log(`  Prenda ${prendasIdx} tiene ${prenda.imagenes_files.length} imágenes`);
                    prenda.imagenes_files.forEach((archivo, imgIdx) => {
                        const fieldName = `imagenes_prenda_${prendasIdx}_${imgIdx}`;
                        console.log(`    → Imagen ${imgIdx}: ${archivo.name} (${(archivo.size / 1024).toFixed(2)}KB)`);
                        formData.append(fieldName, archivo);
                        totalArchivos++;
                    });
                }
            });
            
            console.log('✅ FormData construido:', {
                logo_cotizacion_id: logoCotId,
                tipo_logo_id: tecnica.tipo_logo.id,
                tipo_logo_nombre: tecnica.tipo_logo.nombre,
                es_combinada: tecnica.es_combinada,
                grupo_combinado: tecnica.grupo_combinado,
                prendas: prendasSinArchivos.length,
                archivos_totales: totalArchivos
            });
            
            // DEBUG: Mostrar contenido de FormData
            console.log('📋 FormData entries:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`  ${key}: File(${value.name}, ${(value.size/1024).toFixed(2)}KB)`);
                } else {
                    console.log(`  ${key}: ${value}`);
                }
            }
            
            console.log('📤 Enviando POST a /api/logo-cotizacion-tecnicas/agregar');
            const response = await fetch('/api/logo-cotizacion-tecnicas/agregar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            
            console.log('📥 Response status:', response.status);
            const data = await response.json();
            
            console.log('📦 Response data COMPLETO:', JSON.stringify(data, null, 2));
            
            if (!data.success) {
                console.error('❌ Guardar Técnica - Error DETALLADO:', { 
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
            
            console.log('✅ Técnica guardada exitosamente');
        }
        
        tecnicasAgregadas = []; // Limpiar array temporal
        console.log('✅ Todas las técnicas guardadas');
        return true;
        
    } catch (error) {
        console.error('❌ Guardar Técnicas - Error:', { error: error.message || error, stack: error.stack });
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
        console.error('❌ Elemento tecnicas_agregadas no encontrado');
        return;
    }
    
    container.innerHTML = '';
    
    if (tecnicasAgregadas.length === 0) {
        if (sinTecnicas) sinTecnicas.style.display = 'block';
        return;
    }
    
    if (sinTecnicas) sinTecnicas.style.display = 'none';
    
    // PASO 1: Agrupar por GRUPO_COMBINADO o ÍNDICE (no por nombre de prenda)
    // Esto permite que dos técnicas con el mismo nombre de prenda sean filas separadas
    const gruposMap = {};
    tecnicasAgregadas.forEach((tecnica, tecnicaIndex) => {
        const grupoId = tecnica.grupo_combinado || `individual-${tecnicaIndex}`;
        if (!gruposMap[grupoId]) {
            gruposMap[grupoId] = {
                grupoId: grupoId,
                esCombinada: tecnica.grupo_combinado !== null && tecnica.grupo_combinado !== undefined,
                tecnicas: [],
                prendas: [],
                observaciones: null,
                talla_cantidad: [],
                tecnicaIndexes: []
            };
        }
        
        gruposMap[grupoId].tecnicas.push(tecnica);
        gruposMap[grupoId].tecnicaIndexes.push(tecnicaIndex);
        
        // Agregar datos de prendas (pueden ser múltiples en técnicas combinadas)
        tecnica.prendas.forEach(prenda => {
            gruposMap[grupoId].prendas.push(prenda);
            gruposMap[grupoId].observaciones = prenda.observaciones;
            gruposMap[grupoId].talla_cantidad = prenda.talla_cantidad || [];
        });
    });
    
    console.log('📦 [renderizarTecnicasAgregadas] Agrupado por GRUPO/ÍNDICE:', Object.keys(gruposMap).length, 'grupos');
    Object.entries(gruposMap).forEach(([grupoId, datos]) => {
        console.log(`  → Grupo: ${grupoId}, Técnicas: ${datos.tecnicas.length}, Prendas:`, datos.prendas.map(p => p.nombre_prenda));
    });
    
    
    // Crear tabla
    const tabla = document.createElement('table');
    tabla.style.cssText = 'width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;';
    
    // Encabezados
    const thead = document.createElement('thead');
    thead.style.cssText = 'background: #f0f0f0; border-bottom: 2px solid #ddd; font-weight: 600; color: #333;';
    thead.innerHTML = `
        <tr>
            <th style="padding: 10px 12px; text-align: left; border: 1px solid #eee; font-size: 0.85rem;">Técnica(s)</th>
            <th style="padding: 10px 12px; text-align: left; border: 1px solid #eee; font-size: 0.85rem;">Prenda</th>
            <th style="padding: 10px 12px; text-align: left; border: 1px solid #eee; font-size: 0.85rem;">Observaciones</th>
            <th style="padding: 10px 12px; text-align: left; border: 1px solid #eee; font-size: 0.85rem;">Talla/Cantidad</th>
            <th style="padding: 10px 12px; text-align: center; border: 1px solid #eee; font-size: 0.85rem;">Acciones</th>
        </tr>
    `;
    tabla.appendChild(thead);
    
    // Cuerpo de la tabla
    const tbody = document.createElement('tbody');
    let rowIndex = 0;
    
    // PASO 2: Renderizar una fila por GRUPO (técnica simple o grupo combinado)
    Object.entries(gruposMap).forEach(([grupoId, datosGrupo]) => {
        const tallasArray = datosGrupo.talla_cantidad || [];
        const esCombinada = datosGrupo.esCombinada;
        
        console.log(`🎨 Renderizando GRUPO: ${grupoId}, esCombinada: ${esCombinada}, Técnicas: ${datosGrupo.tecnicas.length}, Tallas: ${tallasArray.length}`);
        
        // Construir HTML de técnicas con sus ubicaciones e imágenes
        let tecnicasHTML = '';
        datosGrupo.tecnicas.forEach((tecnica, techIdx) => {
            tecnica.prendas.forEach(prenda => {
                const ubicacionesText = Array.isArray(prenda.ubicaciones) ? prenda.ubicaciones.join(', ') : prenda.ubicaciones;
                
                // Manejar tanto imagenes_data_urls (strings) como imagenes que son File objects
                let imagenesHTML = '';
                if (prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0) {
                    imagenesHTML = `<div style="margin-top: 4px; display: flex; gap: 4px; flex-wrap: wrap;">
                        ${prenda.imagenes_data_urls.map(img => {
                            const src = typeof img === 'string' ? img : URL.createObjectURL(img);
                            return `<img src="${src}" style="max-width: 50px; max-height: 50px; object-fit: contain; border-radius: 3px;" />`;
                        }).join('')}
                    </div>`;
                }
                
                tecnicasHTML += `
                    <div style="margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-left: 3px solid ${tecnica.tipo_logo.color}; border-radius: 3px;">
                        <div style="font-weight: 600; color: ${tecnica.tipo_logo.color}; margin-bottom: 4px;">
                            ${tecnica.tipo_logo.nombre}
                        </div>
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 4px;">
                            📍 ${ubicacionesText || '-'}
                        </div>
                        ${imagenesHTML}
                    </div>
                `;
            });
        });
        
        if (esCombinada) {
            tecnicasHTML = `<div style="border: 1px dashed #ccc; padding: 8px; border-radius: 4px;">
                <span style="background: #ddd; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: 600; display: inline-block; margin-bottom: 8px;">🔗 COMBINADA</span>
                ${tecnicasHTML}
            </div>`;
        }
        
        // Obtener nombre de prenda (igual para todas en el grupo)
        const nombrePrenda = datosGrupo.prendas[0]?.nombre_prenda || 'SIN NOMBRE';
        
        // Si no hay tallas, renderizar una sola fila
        if (tallasArray.length === 0) {
            const row = document.createElement('tr');
            const isEven = rowIndex % 2 === 0;
            row.style.cssText = `background: ${isEven ? '#f9fafb' : '#ffffff'}; border-bottom: 1px solid #eee;`;
            row.addEventListener('mouseover', function() { this.style.background = '#f3f4f6'; });
            row.addEventListener('mouseout', function() { this.style.background = isEven ? '#f9fafb' : '#ffffff'; });
            
            row.innerHTML = `
                <td style="padding: 10px 12px; border: 1px solid #eee; font-size: 0.9rem; vertical-align: top;">
                    ${tecnicasHTML}
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; font-size: 0.9rem; vertical-align: top;">
                    <strong>${nombrePrenda}</strong>
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; color: #666; font-size: 0.9rem; vertical-align: top;">
                    ${datosGrupo.observaciones || '-'}
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; text-align: center; font-size: 0.9rem; vertical-align: top;"></td>
                <td style="padding: 10px 12px; border: 1px solid #eee; text-align: center; vertical-align: top;">
                    <div style="display: flex; gap: 4px; justify-content: center;">
                        <button type="button" class="btn btn-primary btn-sm btn-editar-grupo" data-grupo-id="${grupoId}"
                                style="background: none; color: #0066cc; border: 1px solid #0066cc; padding: 6px 8px; border-radius: 3px; cursor: pointer; font-size: 0.9rem;"
                                onmouseover="this.style.background='#0066cc'; this.style.color='white'" 
                                onmouseout="this.style.background='none'; this.style.color='#0066cc'"
                                title="Editar técnica(s)">
                            ✎
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-grupo" data-grupo-id="${grupoId}"
                                style="background: none; color: #999; border: 1px solid #ddd; padding: 6px 8px; border-radius: 3px; cursor: pointer; font-size: 0.9rem;"
                                onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" 
                                onmouseout="this.style.background='none'; this.style.color='#999'"
                                title="Eliminar técnica(s)">
                            ✕
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
            rowIndex++;
        } else {
            // Con tallas: UNA SOLA fila con todas las tallas listadas
            const row = document.createElement('tr');
            const isEven = rowIndex % 2 === 0;
            row.style.cssText = `background: ${isEven ? '#f9fafb' : '#ffffff'}; border-bottom: 1px solid #eee;`;
            row.addEventListener('mouseover', function() { this.style.background = '#f3f4f6'; });
            row.addEventListener('mouseout', function() { this.style.background = isEven ? '#f9fafb' : '#ffffff'; });
            
            // Construir lista de tallas y cantidades
            const tallasTexto = tallasArray.map(tc => `<div style="margin: 4px 0; padding: 4px; background: #f0f0f0; border-radius: 3px; font-size: 0.85rem;"><strong>${tc.talla}</strong>: ${tc.cantidad}</div>`).join('');
            
            row.innerHTML = `
                <td style="padding: 10px 12px; border: 1px solid #eee; font-size: 0.9rem; vertical-align: top;">
                    ${tecnicasHTML}
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; font-size: 0.9rem; vertical-align: top;">
                    <strong>${nombrePrenda}</strong>
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; color: #666; font-size: 0.9rem; vertical-align: top;">
                    ${datosGrupo.observaciones || '-'}
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; font-size: 0.9rem; vertical-align: top;">
                    ${tallasTexto}
                </td>
                <td style="padding: 10px 12px; border: 1px solid #eee; text-align: center; vertical-align: top;">
                    <div style="display: flex; gap: 4px; justify-content: center;">
                        <button type="button" class="btn btn-primary btn-sm btn-editar-grupo" data-grupo-id="${grupoId}"
                                style="background: none; color: #0066cc; border: 1px solid #0066cc; padding: 6px 8px; border-radius: 3px; cursor: pointer; font-size: 0.9rem;"
                                onmouseover="this.style.background='#0066cc'; this.style.color='white'" 
                                onmouseout="this.style.background='none'; this.style.color='#0066cc'"
                                title="Editar técnica(s)">
                            ✎
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-grupo" data-grupo-id="${grupoId}"
                                style="background: none; color: #999; border: 1px solid #ddd; padding: 6px 8px; border-radius: 3px; cursor: pointer; font-size: 0.9rem;"
                                onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" 
                                onmouseout="this.style.background='none'; this.style.color='#999'"
                                title="Eliminar técnica(s)">
                            ✕
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
            rowIndex++;
        }
    });
    
    tabla.appendChild(tbody);
    container.appendChild(tabla);
    
    console.log('✅ [renderizarTecnicasAgregadas] COMPLETADO - Tabla renderizada por PRENDA');
    
    // Agregar event listeners para botones de editar grupo
    document.querySelectorAll('.btn-editar-grupo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const grupoId = this.getAttribute('data-grupo-id');
            editarTecnicaDelGrupo(grupoId);
        });
    });
    
    // Agregar event listeners para botones de eliminar grupo
    document.querySelectorAll('.btn-eliminar-grupo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const grupoId = this.getAttribute('data-grupo-id');
            eliminarTecnicaDelGrupo(grupoId);
        });
    });
}

function getTipoIcono(nombreTipo) {
    const iconos = {
        'BORDADO': 'needle',
        'ESTAMPADO': 'stamp',
        'SUBLIMADO': 'fire',
        'DTF': 'film'
    };
    return iconos[nombreTipo] || 'tools';
}

// =========================================================
// 8. ELIMINAR TÉCNICA (temporal o de BD)
// =========================================================

function eliminarTecnicaTemporal(tecnicaIndex) {
    // Eliminar de lista temporal
    tecnicasAgregadas.splice(tecnicaIndex, 1);
    console.log('✅ Técnica eliminada de lista temporal');
    renderizarTecnicasAgregadas();
}

function editarTecnicaDelGrupo(grupoId) {
    // Encontrar las técnicas de este grupo
    const tecnicasDelGrupo = tecnicasAgregadas.filter(t => {
        if (grupoId.startsWith('individual-')) {
            const idx = parseInt(grupoId.split('-')[1]);
            return tecnicasAgregadas.indexOf(t) === idx;
        } else {
            return t.grupo_combinado === grupoId || (grupoId.includes(t.grupo_combinado));
        }
    });
    
    if (tecnicasDelGrupo.length === 0) {
        console.error('❌ No se encontraron técnicas para editar');
        return;
    }
    
    console.log('🔧 Editando grupo:', grupoId, tecnicasDelGrupo);
    
    // Obtener datos actuales (tomar del primer grupo ya que todos comparten datos)
    const tecnicaActual = tecnicasDelGrupo[0];
    const prendasActuales = tecnicaActual.prendas[0] || {};
    const nombrePrendaActual = prendasActuales.nombre_prenda || '';
    const observacionesActuales = prendasActuales.observaciones || '';
    const ubicacionesActuales = prendasActuales.ubicaciones || [];
    const tallasActuales = prendasActuales.talla_cantidad || [];
    
    // Construir HTML del formulario de edición
    let tallasHTML = '';
    tallasActuales.forEach((tc, idx) => {
        tallasHTML += `
            <div class="edit-talla-row" style="display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center; margin-bottom: 8px;" data-edit-idx="${idx}">
                <input type="text" class="edit-talla" value="${tc.talla || ''}" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <input type="number" class="edit-cantidad" value="${tc.cantidad || 1}" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <button type="button" class="edit-btn-eliminar-talla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer;">✕</button>
            </div>
        `;
    });
    
    Swal.fire({
        title: `Editar ${tecnicasDelGrupo.map(t => t.tipo_logo.nombre).join(' + ')}`,
        width: '600px',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Prenda</label>
                    <input type="text" id="edit-nombre-prenda" value="${nombrePrendaActual}" placeholder="POLO, CAMISA..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; text-transform: uppercase;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Observaciones</label>
                    <textarea id="edit-observaciones" placeholder="Detalles adicionales" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;">${observacionesActuales}</textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Tallas y Cantidades</label>
                    <div id="edit-tallas-container" style="display: grid; gap: 8px; margin-bottom: 10px;">
                        ${tallasHTML}
                    </div>
                    <button type="button" id="edit-btn-agregar-talla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 500; width: 100%;">+ Agregar talla</button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            const btnAgregarTalla = document.getElementById('edit-btn-agregar-talla');
            const tallasContainer = document.getElementById('edit-tallas-container');
            let contadorTallasEdit = tallasActuales.length;
            
            // Agregar talla
            if (btnAgregarTalla) {
                btnAgregarTalla.addEventListener('click', (e) => {
                    e.preventDefault();
                    const nuevaTalla = document.createElement('div');
                    nuevaTalla.className = 'edit-talla-row';
                    nuevaTalla.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 8px; align-items: center; margin-bottom: 8px;';
                    nuevaTalla.dataset.editIdx = contadorTallasEdit++;
                    nuevaTalla.innerHTML = `
                        <input type="text" class="edit-talla" placeholder="Talla" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <input type="number" class="edit-cantidad" value="1" min="1" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        <button type="button" class="edit-btn-eliminar-talla" style="background: #f0f0f0; color: #333; border: 1px solid #ddd; padding: 6px; border-radius: 4px; cursor: pointer;">✕</button>
                    `;
                    
                    nuevaTalla.querySelector('.edit-btn-eliminar-talla').addEventListener('click', (e) => {
                        e.preventDefault();
                        nuevaTalla.remove();
                    });
                    
                    tallasContainer.appendChild(nuevaTalla);
                });
            }
            
            // Botones eliminar talla existentes
            document.querySelectorAll('.edit-btn-eliminar-talla').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    btn.closest('.edit-talla-row').remove();
                });
            });
        },
        preConfirm: () => {
            const nombrePrenda = document.getElementById('edit-nombre-prenda').value.trim().toUpperCase();
            if (!nombrePrenda) {
                Swal.showValidationMessage('Completa el nombre de la prenda');
                return false;
            }
            
            const nuevasTallas = [];
            document.querySelectorAll('.edit-talla-row').forEach(row => {
                const talla = row.querySelector('.edit-talla').value.trim();
                const cantidad = row.querySelector('.edit-cantidad').value;
                if (talla && cantidad) {
                    nuevasTallas.push({ talla, cantidad: parseInt(cantidad) });
                }
            });
            
            if (nuevasTallas.length === 0) {
                Swal.showValidationMessage('Agrega al menos una talla');
                return false;
            }
            
            return {
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('edit-observaciones').value.trim(),
                talla_cantidad: nuevasTallas
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Actualizar TODAS las técnicas del grupo con los nuevos datos
            tecnicasDelGrupo.forEach(tecnica => {
                tecnica.prendas.forEach(prenda => {
                    prenda.nombre_prenda = result.value.nombre_prenda;
                    prenda.observaciones = result.value.observaciones;
                    prenda.talla_cantidad = result.value.talla_cantidad;
                });
            });
            
            console.log('✅ Técnicas editadas y actualizadas en memory');
            renderizarTecnicasAgregadas();
        }
    });
}

function eliminarTecnicaDelGrupo(grupoId) {
    // Eliminar todas las técnicas del grupo combinado
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar técnicas combinadas?',
        text: 'Se eliminarán todas las técnicas de este grupo',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        // Filtrar técnicas que NO pertenecen a este grupo
        tecnicasAgregadas = tecnicasAgregadas.filter(t => t.grupo_combinado !== grupoId);
        console.log('✅ Grupo combinado eliminado');
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
        console.error('❌ Error:', error);
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
    console.log('🚀 abrirModalValidacionTecnica() ejecutándose');
    
    const modal = document.getElementById('modalValidacionTecnica');
    
    console.log('🔍 Modal elemento:', modal);
    console.log('🔍 Modal existe:', !!modal);
    
    if (modal) {
        console.log('✅ Modal encontrado, mostrando...');
        modal.style.display = 'flex';
        console.log('✅ Display set a flex');
    } else {
        console.error('❌ Modal no encontrado en el DOM');
    }
}

function cerrarModalValidacionTecnica() {
    console.log('🔴 Cerrando modalValidacionTecnica');
    const modal = document.getElementById('modalValidacionTecnica');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ✅ EXPORTAR PARA ACCESO GLOBAL EN create.blade.php
window.tecnicasAgregadas = tecnicasAgregadas;

