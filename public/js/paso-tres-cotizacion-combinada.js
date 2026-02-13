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

            // Obtener género (del UI actual del Paso 2)
            let genero = '';
            const generosSeleccionados = Array.from(card.querySelectorAll('.talla-genero-checkbox:checked'))
                .map(cb => (cb.value || '').trim())
                .filter(v => v);
            if (generosSeleccionados.length > 0) {
                const etiquetas = generosSeleccionados.map(v => {
                    const vv = v.toLowerCase();
                    if (vv === 'dama') return 'Dama';
                    if (vv === 'caballero') return 'Caballero';
                    if (vv === 'ambos') return 'Ambos';
                    return v;
                });
                genero = etiquetas.join('/');
            }

            // Si no hay género marcado por checkboxes, intentar inferirlo desde el flujo avanzado
            // "Asignar colores a tallas" (AdvancedSizeVariationManager => window.advancedVariationsByProductoId).
            if (!genero) {
                try {
                    const productoId = card?.dataset?.productoId;
                    const store = window.advancedVariationsByProductoId || {};
                    const vars = productoId ? (store[String(productoId)] || []) : [];
                    if (Array.isArray(vars) && vars.length > 0) {
                        const set = new Set();
                        vars.forEach(v => {
                            if (!v || v.assignmentType !== 'Género') return;
                            const gs = Array.isArray(v.genders) ? v.genders : [];
                            gs.forEach(g => {
                                const gg = String(g || '').trim().toUpperCase();
                                if (gg === 'DAMA') set.add('Dama');
                                if (gg === 'CABALLERO') set.add('Caballero');
                            });
                        });
                        if (set.size > 0) {
                            const arr = Array.from(set);
                            // Orden consistente
                            arr.sort((a, b) => {
                                const w = (x) => (x === 'Caballero' ? 1 : (x === 'Dama' ? 2 : 3));
                                return w(a) - w(b);
                            });
                            genero = arr.join('/');
                        }
                    }
                } catch (e) {
                    // no-op
                }
            }

            // Obtener color de tela (tabla Color/Tela/Referencia) - tomamos la primera fila como referencia
            let colorTela = '';
            const inputTela = card.querySelector('table tbody tr .tela-input') || card.querySelector('.tela-input');
            const filaTela = inputTela ? inputTela.closest('tr') : null;
            const inputColorTela = filaTela ? (filaTela.querySelector('.color-input') || filaTela.querySelector('input[name*="[color_id]"]')) : null;
            if (inputColorTela && inputColorTela.value) {
                colorTela = String(inputColorTela.value).trim();
            } else {
                const inputColorFallback = card.querySelector('table tbody tr .color-input') || card.querySelector('.color-input');
                if (inputColorFallback && inputColorFallback.value) {
                    colorTela = String(inputColorFallback.value).trim();
                }
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

            prendas.push({
                index: idx,
                nombre: nombre,
                genero: genero,
                color_tela: colorTela,
                imagenes: imagenes,
            });
        });
    } catch (error) {
        console.error('Error al obtener prendas del PASO 2:', error);
    }
    
    return prendas;
}

function construirLabelPrendaPaso2(prenda) {
    if (!prenda) return '';
    const nombre = (prenda.nombre || '').trim().toUpperCase();
    const genero = (prenda.genero || '').trim();
    const colorTela = (prenda.color_tela || '').trim().toUpperCase();
    const partes = [nombre];
    if (genero) partes.push(genero);
    if (colorTela) partes.push(colorTela);
    return partes.join(' - ');
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

function abrirModalValidacionTecnicaPaso3() {
    try {
        Swal.fire({
            icon: 'warning',
            title: 'Selecciona una técnica',
            text: 'Debes seleccionar al menos una técnica antes de agregar una prenda.',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#333',
        });
    } catch (_) {
        alert('Debes seleccionar al menos una técnica antes de agregar una prenda.');
    }
}

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
    abrirModalDatosIgualesPaso3([tipo]);
}

function abrirModalDatosIgualesPaso3(tecnicas) {
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'iguales';
    
    Swal.fire({
        title: 'Datos Iguales para Todas las Técnicas',
        width: '650px',
        maxHeight: '70vh',
        html: `
            <div style="text-align: left; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-height: 60vh; overflow-y: auto;">
                
                <!-- PRENDA ÚNICA -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Prenda</h3>
                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                        <input id="dNombrePrendaP3" type="text" placeholder="Escribe el nombre de la prenda (Ej: CAMISA, GORRA, CHAQUETA...)" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;" />
                        <label style="display:flex; align-items:center; gap: 8px; cursor:pointer; user-select:none; font-weight: 600; color:#333; font-size: 0.85rem; white-space: nowrap;">
                            <input type="checkbox" id="dUsarPrendasCreadasP3" style="width: 18px; height: 18px; cursor: pointer;" />
                            Seleccionar prendas creadas
                        </label>
                    </div>
                    <select id="dPrendaPaso2SelectP3" style="display:none; width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; margin-bottom: 10px;">
                        <option value="">Selecciona una prenda del Paso 2</option>
                    </select>
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
                
                
                <!-- OBSERVACIONES -->
                <div style="margin-bottom: 25px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 0.95rem; font-weight: 600; color: #333;">Observaciones</h3>
                    <textarea id="dObservacionesP3" placeholder="Detalles adicionales" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#333',
        didOpen: (modal) => {
            const inputNombrePrenda = document.getElementById('dNombrePrendaP3');
            const selectPrendaPaso2 = document.getElementById('dPrendaPaso2SelectP3');
            const cbUsarPrendasCreadas = document.getElementById('dUsarPrendasCreadasP3');
            const ubicacionesPorTecnicaDiv = document.getElementById('dUbicacionesPorTecnicaP3');
            const imagenesPorTecnicaDiv = document.getElementById('dImagenesPorTecnicaP3');
            const logosCompartidosDiv = document.getElementById('dLogosCompartidosP3');
            const usarLogoCompartidoCheck = document.getElementById('dUsarLogoCompartido');
            const imagenesAgregadasPorTecnica = {}; // Almacena imágenes por técnica

            if (inputNombrePrenda) {
                inputNombrePrenda.focus();
            }

            // Precargar prendas del Paso 2 para selección
            try {
                const prendasPaso2 = obtenerPrendasDelPaso2();
                window.__prendasPaso2CacheP3 = Array.isArray(prendasPaso2) ? prendasPaso2 : [];
                window.__prendaPaso2SeleccionadaIndexP3 = null;

                if (selectPrendaPaso2) {
                    selectPrendaPaso2.innerHTML = '<option value="">Selecciona una prenda del Paso 2</option>';
                    window.__prendasPaso2CacheP3.forEach((p) => {
                        const opt = document.createElement('option');
                        opt.value = String(p.index);
                        opt.textContent = construirLabelPrendaPaso2(p);
                        selectPrendaPaso2.appendChild(opt);
                    });

                    selectPrendaPaso2.addEventListener('change', () => {
                        const v = (selectPrendaPaso2.value || '').trim();
                        if (!v) {
                            window.__prendaPaso2SeleccionadaIndexP3 = null;
                            if (inputNombrePrenda) {
                                inputNombrePrenda.value = '';
                                inputNombrePrenda.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            return;
                        }
                        const idx = parseInt(v);
                        if (Number.isNaN(idx)) {
                            window.__prendaPaso2SeleccionadaIndexP3 = null;
                            if (inputNombrePrenda) {
                                inputNombrePrenda.value = '';
                                inputNombrePrenda.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            return;
                        }
                        const prenda = window.__prendasPaso2CacheP3.find(pp => Number(pp.index) === idx);
                        if (!prenda) {
                            window.__prendaPaso2SeleccionadaIndexP3 = null;
                            if (inputNombrePrenda) {
                                inputNombrePrenda.value = '';
                                inputNombrePrenda.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            return;
                        }
                        window.__prendaPaso2SeleccionadaIndexP3 = idx;
                        if (inputNombrePrenda) {
                            inputNombrePrenda.value = construirLabelPrendaPaso2(prenda);
                            inputNombrePrenda.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                }

                const activarModoSelect = (activar) => {
                    const useSelect = !!activar;
                    if (selectPrendaPaso2) {
                        selectPrendaPaso2.style.display = useSelect ? 'block' : 'none';
                        if (!useSelect) {
                            selectPrendaPaso2.value = '';
                        }
                    }
                    if (inputNombrePrenda) {
                        inputNombrePrenda.style.display = useSelect ? 'none' : 'block';
                        if (useSelect) {
                            inputNombrePrenda.value = '';
                            inputNombrePrenda.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                    window.__prendaPaso2SeleccionadaIndexP3 = null;
                };

                if (cbUsarPrendasCreadas) {
                    cbUsarPrendasCreadas.addEventListener('change', () => {
                        activarModoSelect(cbUsarPrendasCreadas.checked);
                    });
                    activarModoSelect(cbUsarPrendasCreadas.checked);
                }
            } catch (e) {
                console.error('Error precargando prendas del Paso 2 para Paso 3:', e);
            }

            // Crear inputs de ubicación por técnica y dropzones de imagen
            
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
                btnAgregarUbicacion.style.cssText = 'background: #0066cc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;';
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
                    <div class="dImagenesDropzone-p3-${idx}" style="
                        border: 2px dashed #ddd;
                        border-radius: 6px;
                        padding: 16px;
                        text-align: center;
                        background: #fafafa;
                        cursor: pointer;
                        transition: all 0.2s;
                    ">
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
            
            // Guardar referencia global para usar en preConfirm
            window.imagenesAgregadasPorTecnicaP3 = imagenesAgregadasPorTecnica;
        },
        preConfirm: () => {
            const cbUsarPrendasCreadas = document.getElementById('dUsarPrendasCreadasP3');
            const useSelect = !!(cbUsarPrendasCreadas && cbUsarPrendasCreadas.checked);
            const inputNombre = document.getElementById('dNombrePrendaP3');
            const selectPrendaPaso2 = document.getElementById('dPrendaPaso2SelectP3');

            let prendaPaso2Index = null;
            let nombrePrendaCompleto = '';

            if (useSelect) {
                const rawIndex = selectPrendaPaso2 ? String(selectPrendaPaso2.value || '').trim() : '';
                if (!rawIndex) {
                    Swal.showValidationMessage('Por favor selecciona una prenda del Paso 2');
                    return false;
                }
                const idx = parseInt(rawIndex, 10);
                if (Number.isNaN(idx)) {
                    Swal.showValidationMessage('Selección de prenda inválida');
                    return false;
                }
                prendaPaso2Index = idx;

                const prendas = Array.isArray(window.__prendasPaso2CacheP3) ? window.__prendasPaso2CacheP3 : [];
                const prenda = prendas.find(pp => Number(pp.index) === idx);
                if (!prenda) {
                    Swal.showValidationMessage('No se encontró la prenda seleccionada del Paso 2');
                    return false;
                }

                // Enviar el nombre base (sin concatenados de género/color)
                nombrePrendaCompleto = String(prenda.nombre || '').trim().toUpperCase();
            } else {
                nombrePrendaCompleto = inputNombre ? String(inputNombre.value || '').trim().toUpperCase() : '';
            }

            // Validar nombre prenda
            if (!nombrePrendaCompleto) {
                Swal.showValidationMessage('Por favor ingresa el nombre de la prenda');
                return false;
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
            
            return {
                nombre_prenda: nombrePrendaCompleto,
                prenda_paso2_index: useSelect ? prendaPaso2Index : null,
                observaciones: document.getElementById('dObservacionesP3').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
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
    btnAgregarLogoCompartido.style.cssText = 'background: #1e40af; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: 600;';
    
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
            <p style="margin: 0 0 8px 0; font-size: 0.95rem; color: #333; font-weight: 600;">
                IMAGEN-${tecnicas.join('-')}
            </p>
            <p style="margin: 0; font-size: 0.8rem; color: #666;">
                ${tecnicas.join(' + ')}
            </p>
        `;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = '✕';
        btnEliminar.style.cssText = 'background: #f44336; color: white; border: none; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; font-weight: 600;';
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

function guardarTecnicaCombinada(datosForm, tecnicas) {
    //  VALIDACIÓN: Verificar que hay información escrita en ubicaciones y/o imágenes
    let tieneInformacionValida = false;
    
    // Validar ubicaciones
    const tieneUbicaciones = Object.values(datosForm.ubicacionesPorTecnica).some(ubicaciones => {
                                 return Array.isArray(ubicaciones) && ubicaciones.some(u => u.trim());
                             });
    
    // Validar imágenes
    let tieneImagenes = false;
    
    // Revisar imágenes agregadas por técnica
    if (datosForm.imagenesAgregadas) {
        Object.values(datosForm.imagenesAgregadas).forEach(imagenesTecnica => {
            if (Array.isArray(imagenesTecnica) && imagenesTecnica.length > 0) {
                tieneImagenes = true;
            }
        });
    }
    
    // La información es válida si hay ubicaciones (imágenes son opcionales)
    tieneInformacionValida = tieneUbicaciones;
    
    console.log(' VALIDACIÓN PASO 3 - Información requerida:', {
        tieneUbicaciones,
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
                            ✓ <strong>Imágenes</strong> (opcional)
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
    
    // Guardar una prenda por técnica con sus imágenes
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
                prenda_paso2_index: datosForm.prenda_paso2_index ?? null,
                ubicaciones: datosForm.ubicacionesPorTecnica[idx] || [],
                observaciones: datosForm.observaciones,
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
                    tecnicas: [],
                    imagenes: [],
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
            <button type="button" class="btn-editar-prenda-paso3" data-prenda-nombre="${nombrePrenda}" style="
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
                <i class="fas fa-edit" style="font-size: 0.9rem;"></i> Editar
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

    // EVENT LISTENERS para editar (sin modales Swal)
    document.querySelectorAll('.btn-editar-prenda-paso3').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const nombrePrenda = e.currentTarget.getAttribute('data-prenda-nombre');
            abrirModalEditarPrendaPaso3(nombrePrenda);
        });
    });
    
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
                // Logo compartido: si múltiples técnicas apuntan a la misma URL, mostrar 1 sola imagen
                // y etiquetar como: "logo compartido - BORDADO-ESTAMPADO".
                const imagenesPorUrl = new Map();
                (Array.isArray(imagenesMaps) ? imagenesMaps : []).forEach((img) => {
                    if (!img || !img.data) return;
                    const url = String(img.data);
                    const tecnica = String(img.tecnica || '').trim();
                    if (!imagenesPorUrl.has(url)) {
                        imagenesPorUrl.set(url, {
                            data: url,
                            tecnicas: new Set(tecnica ? [tecnica] : []),
                        });
                        return;
                    }
                    if (tecnica) {
                        imagenesPorUrl.get(url).tecnicas.add(tecnica);
                    }
                });

                const imagenesFinales = Array.from(imagenesPorUrl.values()).map((item) => {
                    const tecnicas = Array.from(item.tecnicas).filter(Boolean);
                    const tecnicasUnicas = Array.from(new Set(tecnicas));

                    // Si venía el formato antiguo de compartida con prefijo IMAGEN-, lo limpiamos para UI
                    const tecnicasLimpias = tecnicasUnicas
                        .map(t => String(t).replace(/^IMAGEN-/, '').trim())
                        .filter(Boolean);

                    if (tecnicasLimpias.length > 1) {
                        const combo = tecnicasLimpias.join('-');
                        return {
                            data: item.data,
                            tecnica: `logo compartido - ${combo}`
                        };
                    }

                    return {
                        data: item.data,
                        tecnica: tecnicasLimpias[0] || ''
                    };
                });

                grid.innerHTML = imagenesFinales.map((img, idx) => `
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

function cerrarModalEditarPrendaPaso3() {
    const modal = document.getElementById('modalEditarPrendaPaso3');
    const contenido = document.getElementById('contenidoModalEditarPrendaPaso3');
    if (contenido) contenido.innerHTML = '';
    if (modal) modal.style.display = 'none';

    window.p3EdicionContexto = null;
}

function abrirModalEditarPrendaPaso3(nombrePrenda) {
    if (!window.tecnicasAgregadasPaso3 || !Array.isArray(window.tecnicasAgregadasPaso3)) {
        return;
    }

    const tecnicasConPrenda = [];
    let primerPrenda = null;

    window.tecnicasAgregadasPaso3.forEach((tecnicaData, tecnicaIndex) => {
        if (!tecnicaData || !Array.isArray(tecnicaData.prendas)) return;
        const prenda = tecnicaData.prendas.find(p => p && p.nombre_prenda === nombrePrenda);
        if (!prenda) return;
        if (!primerPrenda) primerPrenda = prenda;
        tecnicasConPrenda.push({ tecnicaIndex, tecnicaData, prenda });
    });

    if (!primerPrenda || tecnicasConPrenda.length === 0) {
        return;
    }

    const contenido = document.getElementById('contenidoModalEditarPrendaPaso3');
    const modal = document.getElementById('modalEditarPrendaPaso3');
    if (!contenido || !modal) {
        return;
    }

    window.p3EdicionContexto = {
        nombrePrenda,
        tecnicasConPrenda,
        ubicacionesPorTecnica: {},
        imagenesPorTecnica: {},
        imagenesExistentesPorTecnica: {},
        logoCompartido: {
            enabled: false,
            items: {},
            files: {}
        }
    };

    const obsInicial = primerPrenda.observaciones || '';

    // Detectar logos compartidos existentes en memoria (imagenes paso3 con nombreCompartido)
    // Formato esperado: { file, tipo:'paso3', nombreCompartido:'IMAGEN-XXX', tecnicasCompartidas:[...] }
    const sharedItems = {};
    const sharedFiles = {};
    tecnicasConPrenda.forEach((item) => {
        const imgs = Array.isArray(item.prenda.imagenes) ? item.prenda.imagenes : [];
        imgs.forEach((img) => {
            if (!img || img.tipo !== 'paso3' || !img.nombreCompartido) return;
            const clave = String(img.nombreCompartido).replace(/^IMAGEN-/, '');
            if (!sharedItems[clave]) {
                sharedItems[clave] = {
                    tecnicasCompartidas: Array.isArray(img.tecnicasCompartidas)
                        ? img.tecnicasCompartidas
                        : String(clave).split('-').map(t => t.trim()).filter(Boolean),
                    previewUrl: ''
                };
            }
            if (img.file instanceof File) {
                sharedFiles[clave] = img.file;
            }
        });
    });

    // Detectar logos compartidos por URL (borrador): si la misma ruta aparece en múltiples técnicas,
    // se considera "logo compartido" aunque no exista File (solo URLs tipo paso2).
    // Esto permite reestablecerlo en el modal igual que en creación.
    const sharedPorUrl = new Map();
    tecnicasConPrenda.forEach((item) => {
        const tecnicaNombre = (item.tecnicaData && item.tecnicaData.tipo_logo && item.tecnicaData.tipo_logo.nombre)
            ? item.tecnicaData.tipo_logo.nombre
            : (item.tecnicaData ? item.tecnicaData.tipo : '');
        const imgs = Array.isArray(item.prenda.imagenes) ? item.prenda.imagenes : [];
        imgs.forEach((img) => {
            if (!img) return;
            // URL paso2 puede venir como string o como {ruta, tipo:'paso2'}
            let url = '';
            if (typeof img === 'string') {
                url = img;
            } else if (typeof img === 'object' && img.tipo === 'paso2' && img.ruta) {
                url = img.ruta;
            }
            url = String(url || '').trim();
            if (!url) return;

            if (!sharedPorUrl.has(url)) {
                sharedPorUrl.set(url, new Set());
            }
            if (tecnicaNombre) {
                sharedPorUrl.get(url).add(tecnicaNombre);
            }
        });
    });

    // Convertir URL duplicadas en items del modal
    sharedPorUrl.forEach((setTecnicas, url) => {
        const tecnicas = Array.from(setTecnicas).filter(Boolean);
        if (tecnicas.length < 2) return;

        const clave = tecnicas.join('-');
        if (!sharedItems[clave]) {
            sharedItems[clave] = {
                tecnicasCompartidas: tecnicas,
                previewUrl: url,
                ruta: url,
            };
        } else {
            // Si ya existía por File, al menos setear preview si está vacío
            if (!sharedItems[clave].previewUrl) {
                sharedItems[clave].previewUrl = url;
            }
            if (!sharedItems[clave].ruta) {
                sharedItems[clave].ruta = url;
            }
        }
    });

    window.p3EdicionContexto.logoCompartido.items = sharedItems;
    window.p3EdicionContexto.logoCompartido.files = sharedFiles;
    window.p3EdicionContexto.logoCompartido.enabled = Object.keys(sharedItems).length > 0;

    const tecnicasNombres = tecnicasConPrenda
        .map((item) => (item.tecnicaData && item.tecnicaData.tipo_logo && item.tecnicaData.tipo_logo.nombre)
            ? item.tecnicaData.tipo_logo.nombre
            : (item.tecnicaData ? item.tecnicaData.tipo : ''))
        .filter(Boolean);

    contenido.innerHTML = `
        <div style="margin-bottom: 14px;">
            <h3 style="margin: 0 0 10px 0; font-size: 0.9rem; font-weight: 700; color: #334155;">Prenda</h3>
            <div style="padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; font-weight: 700; color: #0f172a;">${nombrePrenda}</div>
        </div>

        <div id="p3EditarUbicaciones" style="display: grid; gap: 10px; margin-bottom: 14px;"></div>
        <div id="p3EditarImagenes" style="display: grid; gap: 10px; margin-bottom: 14px;"></div>

        <div style="margin-bottom: 14px; padding: 12px; border: 1px solid #dbeafe; background: #f0f7ff; border-radius: 8px;">
            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin: 0;">
                <input type="checkbox" id="p3UsarLogoCompartido" ${window.p3EdicionContexto.logoCompartido.enabled ? 'checked' : ''} style="width:18px; height:18px; cursor:pointer;" />
                <span style="font-weight:800; color:#0369a1;">Usar el mismo logo en múltiples técnicas</span>
            </label>
            <div id="p3LogoCompartidoSection" style="margin-top: 10px; display: ${window.p3EdicionContexto.logoCompartido.enabled ? 'grid' : 'none'}; gap: 10px;">
                <button type="button" id="p3BtnAgregarLogoCompartido" style="background:#0284c7; color:white; border:none; padding:10px 14px; border-radius:6px; cursor:pointer; font-weight:800; width: 100%;">+ Agregar Logo Compartido</button>
                <div id="p3LogosCompartidosContenedor" style="display: grid; gap: 10px;"></div>
                <div id="p3AddLogoCompartidoForm" style="display:none; padding: 12px; background: white; border: 1px dashed #0284c7; border-radius: 8px;">
                    <div style="font-weight:800; color:#0f172a; margin-bottom: 8px;">Nuevo logo compartido</div>
                    <div style="font-weight:700; color:#334155; font-size:0.85rem; margin-bottom: 8px;">Técnicas que comparten el logo</div>
                    <div id="p3AddLogoTecnicas" style="display:flex; flex-wrap:wrap; gap: 10px; margin-bottom: 10px;">
                        ${tecnicasNombres.map((t) => `
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; color:#0f172a; font-size:0.85rem;">
                                <input type="checkbox" class="p3-add-logo-tecnica" value="${t}" style="width:16px; height:16px; cursor:pointer;" />
                                <span>${t}</span>
                            </label>
                        `).join('')}
                    </div>
                    <input type="file" id="p3AddLogoFile" accept="image/*" style="width:100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; cursor:pointer;" />
                    <div style="display:flex; gap: 8px; justify-content: flex-end; margin-top: 10px;">
                        <button type="button" id="p3AddLogoCancel" style="background:white; border:1px solid #cbd5e1; color:#0f172a; padding: 8px 12px; border-radius:6px; cursor:pointer; font-weight:800;">Cancelar</button>
                        <button type="button" id="p3AddLogoSave" style="background:#111827; border:none; color:white; padding: 8px 12px; border-radius:6px; cursor:pointer; font-weight:900;">Agregar</button>
                    </div>
                    <div id="p3AddLogoError" style="display:none; margin-top: 8px; color:#b91c1c; font-weight:700; font-size:0.85rem;"></div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 10px;">
            <h3 style="margin: 0 0 8px 0; font-size: 0.9rem; font-weight: 700; color: #334155;">Observaciones</h3>
            <textarea id="p3EditarObservaciones" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${obsInicial}</textarea>
        </div>
    `;

    const ubicacionesRoot = document.getElementById('p3EditarUbicaciones');
    const imagenesRoot = document.getElementById('p3EditarImagenes');

    // ======================================
    // LOGO COMPARTIDO (edición)
    // ======================================
    const checkboxLogoCompartido = document.getElementById('p3UsarLogoCompartido');
    const sectionLogoCompartido = document.getElementById('p3LogoCompartidoSection');
    const contenedorLogosCompartidos = document.getElementById('p3LogosCompartidosContenedor');
    const btnAgregarLogoCompartido = document.getElementById('p3BtnAgregarLogoCompartido');
    const formAgregarLogoCompartido = document.getElementById('p3AddLogoCompartidoForm');
    const btnAddLogoCancel = document.getElementById('p3AddLogoCancel');
    const btnAddLogoSave = document.getElementById('p3AddLogoSave');
    const inputAddLogoFile = document.getElementById('p3AddLogoFile');
    const addLogoError = document.getElementById('p3AddLogoError');

    const obtenerTecnicasSeleccionadasNuevoLogo = () => {
        const cbs = Array.from(contenido.querySelectorAll('.p3-add-logo-tecnica'));
        return cbs.filter(cb => cb.checked).map(cb => cb.value);
    };

    const setAddError = (msg) => {
        if (!addLogoError) return;
        if (!msg) {
            addLogoError.style.display = 'none';
            addLogoError.textContent = '';
            return;
        }
        addLogoError.style.display = 'block';
        addLogoError.textContent = msg;
    };

    const leerArchivoComoDataUrl = (file) => new Promise((resolve) => {
        if (!(file instanceof File)) return resolve('');
        const r = new FileReader();
        r.onload = (e) => resolve(e.target.result);
        r.onerror = () => resolve('');
        r.readAsDataURL(file);
    });

    const renderizarLogosCompartidos = async () => {
        if (!contenedorLogosCompartidos) return;
        const items = window.p3EdicionContexto.logoCompartido.items || {};
        contenedorLogosCompartidos.innerHTML = '';

        const entries = Object.entries(items);
        for (const [clave, item] of entries) {
            const tecnicasTxt = Array.isArray(item.tecnicasCompartidas) ? item.tecnicasCompartidas.join(' + ') : String(clave);
            const file = window.p3EdicionContexto.logoCompartido.files ? window.p3EdicionContexto.logoCompartido.files[clave] : null;
            const previewUrl = item.previewUrl || (file instanceof File ? await leerArchivoComoDataUrl(file) : '');
            if (!item.previewUrl && previewUrl) {
                item.previewUrl = previewUrl;
            }

            const row = document.createElement('div');
            row.className = 'p3-logo-compartido-row';
            row.setAttribute('data-logo-clave', clave);
            row.style.cssText = 'border: 1px solid #bfdbfe; background: #ffffff; border-radius: 8px; padding: 12px; display: grid; gap: 10px;';
            row.innerHTML = `
                <div style="display:flex; justify-content: space-between; gap: 10px; align-items: flex-start;">
                    <div style="font-weight: 900; color: #0369a1;">Logo compartido: ${tecnicasTxt}</div>
                    <button type="button" class="p3-logo-compartido-del" data-logo-clave="${clave}" style="background: #fee2e2; color:#991b1b; border:1px solid #fecaca; padding: 6px 10px; border-radius: 6px; cursor:pointer; font-weight: 900;">Eliminar</button>
                </div>
                <div style="display:grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: center;">
                    <div class="p3-logo-compartido-preview" data-logo-clave="${clave}" style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid #dbeafe; background: #f8fafc; display:flex; align-items:center; justify-content:center;">
                        ${previewUrl ? `<img src="${previewUrl}" style="width:100%; height:100%; object-fit: cover;" />` : `<span style="color:#64748b; font-weight:800; font-size:0.8rem;">Sin imagen</span>`}
                    </div>
                    <div>
                        <input type="file" class="p3-logo-compartido-file" data-logo-clave="${clave}" accept="image/*" style="width:100%; padding: 10px; border: 1px dashed #0284c7; border-radius: 6px; cursor:pointer; box-sizing:border-box;" />
                        <div style="margin-top: 6px; color:#64748b; font-size:0.8rem; font-weight:700;">Reemplazar logo compartido</div>
                    </div>
                </div>
            `;

            contenedorLogosCompartidos.appendChild(row);
        }

        // bind delete
        contenedorLogosCompartidos.querySelectorAll('.p3-logo-compartido-del').forEach((b) => {
            b.addEventListener('click', (ev) => {
                ev.preventDefault();
                const clave = b.getAttribute('data-logo-clave');
                if (!clave) return;

                const itemAntesDeEliminar = window.p3EdicionContexto.logoCompartido.items
                    ? window.p3EdicionContexto.logoCompartido.items[clave]
                    : null;
                const rutaCompartida = itemAntesDeEliminar && (itemAntesDeEliminar.ruta || itemAntesDeEliminar.previewUrl)
                    ? String(itemAntesDeEliminar.ruta || itemAntesDeEliminar.previewUrl)
                    : '';
                const tecnicasCompartidas = itemAntesDeEliminar && Array.isArray(itemAntesDeEliminar.tecnicasCompartidas)
                    ? itemAntesDeEliminar.tecnicasCompartidas.map(t => String(t))
                    : [];

                if (window.p3EdicionContexto.logoCompartido.items) {
                    delete window.p3EdicionContexto.logoCompartido.items[clave];
                }
                if (window.p3EdicionContexto.logoCompartido.files) {
                    delete window.p3EdicionContexto.logoCompartido.files[clave];
                }

                // IMPORTANTE (borrador): el logo compartido por URL normalmente viene repetido como imágenes existentes
                // en cada técnica. Si el usuario elimina el logo compartido, debemos quitar esa URL también de las
                // imágenes existentes de las técnicas afectadas para que el card se actualice al guardar.
                if (rutaCompartida && tecnicasCompartidas.length > 0) {
                    tecnicasConPrenda.forEach((t) => {
                        const tecnicaKey = `${t.tecnicaIndex}`;
                        const tecnicaNombre = (t.tecnicaData && t.tecnicaData.tipo_logo && t.tecnicaData.tipo_logo.nombre)
                            ? t.tecnicaData.tipo_logo.nombre
                            : (t.tecnicaData ? t.tecnicaData.tipo : '');

                        if (!tecnicaNombre || !tecnicasCompartidas.includes(String(tecnicaNombre))) {
                            return;
                        }

                        const arr = window.p3EdicionContexto.imagenesExistentesPorTecnica && Array.isArray(window.p3EdicionContexto.imagenesExistentesPorTecnica[tecnicaKey])
                            ? window.p3EdicionContexto.imagenesExistentesPorTecnica[tecnicaKey]
                            : [];

                        window.p3EdicionContexto.imagenesExistentesPorTecnica[tecnicaKey] = arr.filter((img) => {
                            const r = img && img.ruta ? String(img.ruta) : '';
                            return r !== rutaCompartida;
                        });
                    });
                }

                if (Object.keys(window.p3EdicionContexto.logoCompartido.items || {}).length === 0) {
                    window.p3EdicionContexto.logoCompartido.enabled = false;
                    if (checkboxLogoCompartido) checkboxLogoCompartido.checked = false;
                    if (sectionLogoCompartido) sectionLogoCompartido.style.display = 'none';
                }
                renderizarLogosCompartidos();
            });
        });

        // bind replace file
        contenedorLogosCompartidos.querySelectorAll('.p3-logo-compartido-file').forEach((inp) => {
            inp.addEventListener('change', async (ev) => {
                const clave = inp.getAttribute('data-logo-clave');
                const file = (ev.target.files && ev.target.files[0]) ? ev.target.files[0] : null;
                if (!clave || !(file instanceof File)) return;
                window.p3EdicionContexto.logoCompartido.files[clave] = file;
                const preview = await leerArchivoComoDataUrl(file);
                if (window.p3EdicionContexto.logoCompartido.items[clave]) {
                    window.p3EdicionContexto.logoCompartido.items[clave].previewUrl = preview;
                }
                renderizarLogosCompartidos();
                ev.target.value = '';
            });
        });
    };

    if (checkboxLogoCompartido && sectionLogoCompartido) {
        checkboxLogoCompartido.addEventListener('change', () => {
            const enabled = !!checkboxLogoCompartido.checked;
            window.p3EdicionContexto.logoCompartido.enabled = enabled;
            sectionLogoCompartido.style.display = enabled ? 'grid' : 'none';

            if (!enabled) {
                window.p3EdicionContexto.logoCompartido.items = {};
                window.p3EdicionContexto.logoCompartido.files = {};
                if (formAgregarLogoCompartido) formAgregarLogoCompartido.style.display = 'none';
            }
            renderizarLogosCompartidos();
        });
    }

    if (btnAgregarLogoCompartido && formAgregarLogoCompartido) {
        btnAgregarLogoCompartido.addEventListener('click', (ev) => {
            ev.preventDefault();
            setAddError('');
            formAgregarLogoCompartido.style.display = 'block';
        });
    }

    if (btnAddLogoCancel && formAgregarLogoCompartido) {
        btnAddLogoCancel.addEventListener('click', (ev) => {
            ev.preventDefault();
            setAddError('');
            formAgregarLogoCompartido.style.display = 'none';
            if (inputAddLogoFile) inputAddLogoFile.value = '';
            contenido.querySelectorAll('.p3-add-logo-tecnica').forEach(cb => cb.checked = false);
        });
    }

    if (btnAddLogoSave) {
        btnAddLogoSave.addEventListener('click', async (ev) => {
            ev.preventDefault();
            setAddError('');
            const tecnicasSel = obtenerTecnicasSeleccionadasNuevoLogo();
            const file = inputAddLogoFile && inputAddLogoFile.files ? inputAddLogoFile.files[0] : null;
            if (tecnicasSel.length < 2) {
                setAddError('Selecciona mínimo 2 técnicas para compartir el logo');
                return;
            }
            if (!(file instanceof File)) {
                setAddError('Selecciona una imagen para el logo compartido');
                return;
            }

            const clave = tecnicasSel.join('-');
            const preview = await leerArchivoComoDataUrl(file);
            window.p3EdicionContexto.logoCompartido.enabled = true;
            window.p3EdicionContexto.logoCompartido.items[clave] = {
                tecnicasCompartidas: tecnicasSel,
                previewUrl: preview
            };
            window.p3EdicionContexto.logoCompartido.files[clave] = file;

            if (checkboxLogoCompartido) checkboxLogoCompartido.checked = true;
            if (sectionLogoCompartido) sectionLogoCompartido.style.display = 'grid';

            formAgregarLogoCompartido.style.display = 'none';
            if (inputAddLogoFile) inputAddLogoFile.value = '';
            contenido.querySelectorAll('.p3-add-logo-tecnica').forEach(cb => cb.checked = false);
            renderizarLogosCompartidos();
        });
    }

    renderizarLogosCompartidos();

    tecnicasConPrenda.forEach((item, idx) => {
        const tecnicaNombre = (item.tecnicaData && item.tecnicaData.tipo_logo && item.tecnicaData.tipo_logo.nombre)
            ? item.tecnicaData.tipo_logo.nombre
            : (item.tecnicaData ? item.tecnicaData.tipo : '');

        const key = `${item.tecnicaIndex}`;

        // UBICACIONES
        const ubicacionesIniciales = Array.isArray(item.prenda.ubicaciones) ? [...item.prenda.ubicaciones] : [];
        window.p3EdicionContexto.ubicacionesPorTecnica[key] = ubicacionesIniciales;

        const blockUb = document.createElement('div');
        blockUb.style.cssText = 'padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;';
        blockUb.innerHTML = `
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 8px;">Ubicaciones - ${tecnicaNombre}</div>
            <div style="display:flex; gap:8px; margin-bottom: 8px;">
                <input type="text" class="p3-edit-ubicacion-input" data-tecnica-key="${key}" placeholder="Ej: PECHO" style="flex:1; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 0.9rem;">
                <button type="button" class="p3-edit-ubicacion-add" data-tecnica-key="${key}" style="background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: 800;">+ Agregar</button>
            </div>
            <div class="p3-edit-ubicaciones-list" data-tecnica-key="${key}" style="display:flex; flex-wrap: wrap; gap: 8px; min-height: 28px;"></div>
        `;
        ubicacionesRoot.appendChild(blockUb);

        const renderUbs = () => {
            const list = blockUb.querySelector(`.p3-edit-ubicaciones-list[data-tecnica-key="${key}"]`);
            if (!list) return;
            const arr = window.p3EdicionContexto.ubicacionesPorTecnica[key] || [];
            list.innerHTML = arr.map((ub, i) => `
                <span style="background:#dbeafe; color:#1e40af; padding: 6px 10px; border-radius: 999px; font-weight: 800; display:inline-flex; align-items:center; gap: 8px; font-size: 0.85rem;">
                    ${ub}
                    <button type="button" class="p3-edit-ubicacion-del" data-tecnica-key="${key}" data-idx="${i}" style="background:none; border:none; cursor:pointer; color:#1e40af; font-weight:900; padding:0;">×</button>
                </span>
            `).join('');

            list.querySelectorAll('.p3-edit-ubicacion-del').forEach((b) => {
                b.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const i = parseInt(b.getAttribute('data-idx'));
                    const arr2 = window.p3EdicionContexto.ubicacionesPorTecnica[key] || [];
                    arr2.splice(i, 1);
                    window.p3EdicionContexto.ubicacionesPorTecnica[key] = arr2;
                    renderUbs();
                });
            });
        };

        renderUbs();

        const btnAddUb = blockUb.querySelector(`.p3-edit-ubicacion-add[data-tecnica-key="${key}"]`);
        const inputUb = blockUb.querySelector(`.p3-edit-ubicacion-input[data-tecnica-key="${key}"]`);

        if (btnAddUb && inputUb) {
            btnAddUb.addEventListener('click', (ev) => {
                ev.preventDefault();
                const ub = (inputUb.value || '').trim().toUpperCase();
                if (!ub) return;
                const arr = window.p3EdicionContexto.ubicacionesPorTecnica[key] || [];
                if (arr.includes(ub)) return;
                arr.push(ub);
                window.p3EdicionContexto.ubicacionesPorTecnica[key] = arr;
                inputUb.value = '';
                renderUbs();
                inputUb.focus();
            });
            inputUb.addEventListener('keypress', (ev) => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    btnAddUb.click();
                }
            });
        }

        // IMÁGENES
        const imagenesIniciales = Array.isArray(item.prenda.imagenes) ? [...item.prenda.imagenes] : [];
        // Guardar imágenes existentes de técnica (URL) para permitir eliminar en edición
        // Compatibilidad: en borradores antiguos puede venir sin `origen`. En ese caso,
        // asumimos que la 1ra imagen paso2 es de prenda y el resto son de técnica.
        const paso2 = imagenesIniciales.filter(img => img && img.tipo === 'paso2' && img.ruta);
        let rutaPaso2Prenda = null;

        const paso2MarcadasPrenda = paso2.filter(img => img.origen === 'prenda');
        if (paso2MarcadasPrenda.length > 0) {
            rutaPaso2Prenda = paso2MarcadasPrenda[0].ruta;
        } else {
            const paso2SinOrigen = paso2.filter(img => !img.origen);
            if (paso2SinOrigen.length > 0) {
                rutaPaso2Prenda = paso2SinOrigen[0].ruta;
            }
        }

        const existentesTecnica = paso2
            .filter((img, idx) => {
                if (!img || !img.ruta) return false;
                if (img.origen === 'tecnica') return true;
                if (img.origen === 'prenda') return false;
                // sin origen: excluir la ruta estimada como prenda
                if (rutaPaso2Prenda && String(img.ruta) === String(rutaPaso2Prenda)) return false;
                // si no pudimos estimar, mantener desde el segundo en adelante
                return idx > 0;
            })
            .map(img => ({
                ruta: img.ruta,
                tipo: 'paso2',
                origen: 'tecnica',
                foto_id: img.foto_id || null,
            }));

        window.p3EdicionContexto.imagenesExistentesPorTecnica[key] = existentesTecnica;

        // solo permitir editar/agregar imágenes del paso3 (File) + mantener paso2 tal cual
        const archivosPaso3 = imagenesIniciales
            .filter(img => img && img.tipo === 'paso3' && img.file instanceof File && !img.nombreCompartido)
            .map(img => img.file);

        window.p3EdicionContexto.imagenesPorTecnica[key] = archivosPaso3;

        const blockImg = document.createElement('div');
        blockImg.style.cssText = 'padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;';
        blockImg.innerHTML = `
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 8px;">Imágenes - ${tecnicaNombre} (máx. 3)</div>
            <div class="p3-edit-existentes" data-tecnica-key="${key}" style="display:flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;"></div>
            <div class="p3-edit-drop" data-tecnica-key="${key}" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 14px; text-align: center; background: #ffffff; cursor: pointer;">
                <div style="font-size: 1.2rem; margin-bottom: 6px;">📁</div>
                <div style="font-weight: 700; color: #334155;">Arrastra imágenes aquí o haz clic</div>
                <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">(solo imágenes, máximo 3)</div>
                <input type="file" class="p3-edit-file" data-tecnica-key="${key}" accept="image/*" multiple style="display:none;" />
            </div>
            <div class="p3-edit-previews" data-tecnica-key="${key}" style="display:flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;"></div>
        `;
        imagenesRoot.appendChild(blockImg);

        const drop = blockImg.querySelector(`.p3-edit-drop[data-tecnica-key="${key}"]`);
        const fileInput = blockImg.querySelector(`.p3-edit-file[data-tecnica-key="${key}"]`);
        const existentesWrap = blockImg.querySelector(`.p3-edit-existentes[data-tecnica-key="${key}"]`);
        const previews = blockImg.querySelector(`.p3-edit-previews[data-tecnica-key="${key}"]`);

        const renderExistentes = () => {
            if (!existentesWrap) return;
            existentesWrap.innerHTML = '';
            const existentes = window.p3EdicionContexto.imagenesExistentesPorTecnica[key] || [];
            existentes.forEach((it, i) => {
                const wrap = document.createElement('div');
                wrap.style.cssText = 'position: relative; width: 90px; height: 90px; border-radius: 8px; overflow: hidden; border: 1px solid #1e40af; background: #fff;';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = '×';
                btn.style.cssText = 'position:absolute; top:4px; right:4px; width:24px; height:24px; border-radius:999px; border:none; cursor:pointer; background: rgba(185,28,28,0.85); color:#fff; font-weight:900;';
                btn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const arr = window.p3EdicionContexto.imagenesExistentesPorTecnica[key] || [];
                    arr.splice(i, 1);
                    window.p3EdicionContexto.imagenesExistentesPorTecnica[key] = arr;
                    renderExistentes();
                });

                const img = document.createElement('img');
                img.style.cssText = 'width:100%; height:100%; object-fit: cover;';
                img.src = it.ruta;

                wrap.appendChild(img);
                wrap.appendChild(btn);
                existentesWrap.appendChild(wrap);
            });
        };

        const renderImgs = () => {
            if (!previews) return;
            previews.innerHTML = '';
            const files = window.p3EdicionContexto.imagenesPorTecnica[key] || [];
            files.forEach((f, i) => {
                const wrap = document.createElement('div');
                wrap.style.cssText = 'position: relative; width: 90px; height: 90px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = '×';
                btn.style.cssText = 'position:absolute; top:4px; right:4px; width:24px; height:24px; border-radius:999px; border:none; cursor:pointer; background: rgba(0,0,0,0.6); color:#fff; font-weight:900;';
                btn.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const arr = window.p3EdicionContexto.imagenesPorTecnica[key] || [];
                    arr.splice(i, 1);
                    window.p3EdicionContexto.imagenesPorTecnica[key] = arr;
                    renderImgs();
                });

                const img = document.createElement('img');
                img.style.cssText = 'width:100%; height:100%; object-fit: cover;';
                const r = new FileReader();
                r.onload = (ev2) => { img.src = ev2.target.result; };
                r.readAsDataURL(f);

                wrap.appendChild(img);
                wrap.appendChild(btn);
                previews.appendChild(wrap);
            });
        };

        renderImgs();
        renderExistentes();

        const addFiles = (files) => {
            const imagenes = (files || []).filter(ff => ff && ff.type && ff.type.startsWith('image/'));
            if (imagenes.length === 0) return;
            const arr = window.p3EdicionContexto.imagenesPorTecnica[key] || [];
            const existentes = window.p3EdicionContexto.imagenesExistentesPorTecnica[key] || [];
            for (const f of imagenes) {
                if ((arr.length + existentes.length) >= 3) break;
                arr.push(f);
            }
            window.p3EdicionContexto.imagenesPorTecnica[key] = arr;
            renderImgs();
        };

        if (drop && fileInput) {
            drop.addEventListener('click', () => fileInput.click());
            drop.addEventListener('dragover', (ev) => {
                ev.preventDefault();
                drop.style.background = '#eff6ff';
                drop.style.borderColor = '#2563eb';
            });
            drop.addEventListener('dragleave', () => {
                drop.style.background = '#ffffff';
                drop.style.borderColor = '#cbd5e1';
            });
            drop.addEventListener('drop', (ev) => {
                ev.preventDefault();
                drop.style.background = '#ffffff';
                drop.style.borderColor = '#cbd5e1';
                addFiles(Array.from(ev.dataTransfer.files || []));
            });
            fileInput.addEventListener('change', (ev) => {
                addFiles(Array.from(ev.target.files || []));
                ev.target.value = '';
            });
        }

    });

    modal.style.display = 'flex';
}

function guardarEdicionPrendaPaso3DesdeModal() {
    if (!window.p3EdicionContexto) {
        return;
    }

    const { nombrePrenda, tecnicasConPrenda, ubicacionesPorTecnica, imagenesPorTecnica, imagenesExistentesPorTecnica, logoCompartido } = window.p3EdicionContexto;
    const obs = document.getElementById('p3EditarObservaciones') ? document.getElementById('p3EditarObservaciones').value.trim() : '';

    tecnicasConPrenda.forEach((item) => {
        const tecnicaKey = `${item.tecnicaIndex}`;
        const tecnicaData = window.tecnicasAgregadasPaso3[item.tecnicaIndex];
        if (!tecnicaData || !Array.isArray(tecnicaData.prendas)) return;

        tecnicaData.prendas = tecnicaData.prendas.map((p) => {
            if (!p || p.nombre_prenda !== nombrePrenda) return p;

            // Mantener imágenes del paso2 + reemplazar las del paso3 por las del modal
            const prevImgs = Array.isArray(p.imagenes) ? p.imagenes : [];
            // Mantener SIEMPRE imágenes paso2 de prenda. Las de técnica se controlan desde el modal.
            const existentesTecnica = Array.isArray(imagenesExistentesPorTecnica[tecnicaKey])
                ? imagenesExistentesPorTecnica[tecnicaKey]
                : [];
            const rutasExistentesTecnica = existentesTecnica
                .map(it => (it && it.ruta) ? String(it.ruta) : '')
                .filter(Boolean);

            // Compatibilidad: borradores antiguos pueden no traer `origen`.
            // En ese caso, conservar como foto de prenda la primera imagen paso2 cuya ruta NO esté en existentesTecnica.
            let paso2Prenda = prevImgs.filter(img => img && img.tipo === 'paso2' && img.origen === 'prenda');
            if (paso2Prenda.length === 0) {
                const candidatos = prevImgs.filter(img => img && img.tipo === 'paso2' && img.ruta && img.origen !== 'tecnica');
                const elegido = candidatos.find(img => !rutasExistentesTecnica.includes(String(img.ruta))) || candidatos[0];
                paso2Prenda = elegido && elegido.ruta
                    ? [{ ruta: elegido.ruta, tipo: 'paso2', origen: 'prenda', foto_id: elegido.foto_id || null }]
                    : [];
            }
            const archivosPaso3 = Array.isArray(imagenesPorTecnica[tecnicaKey]) ? imagenesPorTecnica[tecnicaKey] : [];
            const nuevasPaso3 = archivosPaso3.map(f => ({ file: f, tipo: 'paso3' }));

            // Logo compartido: reconstruir imágenes compartidas por técnica
            const tecnicaNombreActual = (tecnicaData.tipo_logo && tecnicaData.tipo_logo.nombre)
                ? tecnicaData.tipo_logo.nombre
                : tecnicaData.tipo;
            let sharedImgs = [];
            if (logoCompartido && logoCompartido.enabled) {
                const items = logoCompartido.items || {};
                const files = logoCompartido.files || {};
                Object.entries(items).forEach(([clave, item]) => {
                    const tecnicas = Array.isArray(item.tecnicasCompartidas)
                        ? item.tecnicasCompartidas
                        : String(clave).split('-').map(t => t.trim()).filter(Boolean);
                    if (!tecnicas.includes(tecnicaNombreActual)) return;

                    // Caso A: se reemplazó/subió un File (flujo creación)
                    const file = files[clave];
                    if (file instanceof File) {
                        sharedImgs.push({
                            file,
                            tipo: 'paso3',
                            nombreCompartido: `IMAGEN-${clave}`,
                            tecnicasCompartidas: tecnicas
                        });
                        return;
                    }

                    // Caso B: logo compartido existente en borrador por URL (sin File)
                    const ruta = (item && (item.ruta || item.previewUrl)) ? String(item.ruta || item.previewUrl) : '';
                    if (!ruta) return;
                    sharedImgs.push({
                        ruta,
                        tipo: 'paso2'
                    });
                });
            }

            return {
                ...p,
                observaciones: obs,
                ubicaciones: Array.isArray(ubicacionesPorTecnica[tecnicaKey]) ? ubicacionesPorTecnica[tecnicaKey] : (p.ubicaciones || []),
                imagenes: [...paso2Prenda, ...existentesTecnica, ...nuevasPaso3, ...sharedImgs]
            };
        });
    });

    renderizarTecnicasAgregadasPaso3();
    cerrarModalEditarPrendaPaso3();
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
