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

function configurarDropzoneImagenes({ dropzone, input, previewContainer, maximo = 3 }) {
    const imagenesAgregadas = [];

    const actualizarPrevisualizaciones = () => {
        if (!previewContainer) return;
        previewContainer.innerHTML = '';

        imagenesAgregadas.forEach((archivo, idx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = document.createElement('div');
                preview.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #ddd;';

                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.style.cssText = 'position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.6); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center; justify-content: center;';
                btnEliminar.textContent = '×';
                btnEliminar.addEventListener('click', (ev) => {
                    ev.preventDefault();
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
    };

    const agregarImagenes = (archivos) => {
        const imagenes = (archivos || []).filter(f => f && f.type && f.type.startsWith('image/'));
        if (imagenes.length === 0) {
            return;
        }

        for (const archivo of imagenes) {
            if (imagenesAgregadas.length >= maximo) {
                break;
            }
            imagenesAgregadas.push(archivo);
        }

        actualizarPrevisualizaciones();
    };

    if (dropzone && input) {
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
            agregarImagenes(Array.from(e.dataTransfer.files || []));
        });

        input.addEventListener('change', (e) => {
            agregarImagenes(Array.from(e.target.files || []));
            e.target.value = '';
        });
    }

    return imagenesAgregadas;
}

function guardarEdicionPrenda() {
    const listaPrendas = document.getElementById('listaPrendas');
    const prendaDiv = listaPrendas ? listaPrendas.querySelector('.prenda-item') : null;

    if (!prendaDiv) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró el formulario de edición'
        });
        return;
    }

    const nombreInput = prendaDiv.querySelector('.nombre_prenda');
    const observacionesInput = prendaDiv.querySelector('.observaciones');
    const nuevoNombre = nombreInput ? nombreInput.value.trim().toUpperCase() : '';
    const nuevasObs = observacionesInput ? observacionesInput.value.trim() : '';

    if (!nuevoNombre) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo vacío',
            text: 'Completa el nombre de la prenda'
        });
        return;
    }

    const originalNombre = window.prendaNombreOriginalEdicion || (window.datosPrendaEdicion ? window.datosPrendaEdicion.nombre_prenda : null);
    const indices = Array.isArray(window.tecnicaIndicesEdicion) ? window.tecnicaIndicesEdicion : [];
    const tecnicasEditData = (window.datosPrendaEdicion && window.datosPrendaEdicion.tecnicas_imagenes) ? window.datosPrendaEdicion.tecnicas_imagenes : {};
    const logoCompartidoEnabled = !!(window.datosPrendaEdicion && window.datosPrendaEdicion.logoCompartido && window.datosPrendaEdicion.logoCompartido.enabled);
    const logoCompartidoData = (window.datosPrendaEdicion && window.datosPrendaEdicion.logoCompartido) ? window.datosPrendaEdicion.logoCompartido : { enabled: false, items: {}, files: {} };

    indices.forEach((tecnicaIndex) => {
        const tecnica = tecnicasAgregadas[tecnicaIndex];
        if (!tecnica || !Array.isArray(tecnica.prendas)) return;

        const nombreTecnica = tecnica.tipo_logo ? tecnica.tipo_logo.nombre : null;
        const dataTecnica = nombreTecnica ? tecnicasEditData[nombreTecnica] : null;

        // Actualizar logos compartidos a nivel de técnica
        if (logoCompartidoEnabled) {
            tecnica.logosCompartidos = tecnica.logosCompartidos || {};
            Object.keys(logoCompartidoData.items || {}).forEach((clave) => {
                const file = (logoCompartidoData.files && logoCompartidoData.files[clave]) ? logoCompartidoData.files[clave] : null;
                if (file instanceof File) {
                    tecnica.logosCompartidos[clave] = file;
                }
            });
        } else {
            tecnica.logosCompartidos = {};
        }

        tecnica.prendas.forEach((p) => {
            if (!p) return;
            if (originalNombre && p.nombre_prenda !== originalNombre) return;

            p.nombre_prenda = nuevoNombre;
            p.observaciones = nuevasObs;
            p.talla_cantidad = [];

            if (dataTecnica) {
                const ubicaciones = Array.isArray(dataTecnica.ubicaciones) ? dataTecnica.ubicaciones : [];
                p.ubicaciones = ubicaciones;

                const imgs = Array.isArray(dataTecnica.imagenes_data_urls) ? dataTecnica.imagenes_data_urls : [];
                // Guardar como objetos {data_url} para compatibilidad con el resto del flujo
                const imagenesPropias = imgs.map(u => ({ data_url: u }));

                // Mantener Files reales (solo imágenes nuevas agregadas en el modal)
                // Esto es lo que el submit usa para adjuntar archivos en FormData.
                const filesActuales = Array.isArray(dataTecnica.imagenes_files) ? dataTecnica.imagenes_files : [];
                p.imagenes_files = filesActuales.filter(f => f instanceof File);

                // Si hay logo compartido activo, agregar imágenes compartidas que correspondan a esta técnica
                let imagenesCompartidas = [];
                if (logoCompartidoEnabled) {
                    Object.entries(logoCompartidoData.items || {}).forEach(([clave, item]) => {
                        const tecnicasCompartidas = Array.isArray(item.tecnicasCompartidas) ? item.tecnicasCompartidas : [];
                        if (nombreTecnica && tecnicasCompartidas.includes(nombreTecnica)) {
                            const dataUrl = item.previewUrl || null;
                            if (dataUrl) {
                                imagenesCompartidas.push({
                                    data_url: dataUrl,
                                    esCompartida: true,
                                    nombreCompartido: clave,
                                    tecnicasCompartidas
                                });
                            }
                        }
                    });
                }

                // Reconstruir imagenes_data_urls sin duplicar
                p.imagenes_data_urls = [...imagenesPropias, ...imagenesCompartidas];
            }
        });
    });

    // Reset flags de edición
    window.modoEdicionPrenda = false;
    window.datosPrendaEdicion = null;
    window.tecnicasInfoEdicion = null;
    window.tecnicaIndicesEdicion = null;
    window.prendaNombreOriginalEdicion = null;

    cerrarModalAgregarTecnica();
    renderizarTecnicasAgregadas();
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
    // Resetear cualquier estado previo de técnicas combinadas
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;

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

function abrirModalDatosIguales(tecnicas) {
    // Guardar técnicas combinadas en contexto global temporal
    window.tecnicasCombinadas = tecnicas;
    window.modoTecnicasCombinadas = 'iguales';
    
    // Modal MINIMALISTA tipo TNS
    Swal.fire({
        title: 'Técnicas Combinadas',
        width: '1000px',
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
                
                <!-- LOGO COMPARTIDO (Nuevo) -->
                <div style="margin-bottom: 25px; padding: 12px; background: #f0f7ff; border: 2px solid #dbeafe; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                        <input type="checkbox" id="dUsarLogoCompartido" style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 600; color: #0369a1;">Usar el mismo logo en múltiples técnicas</span>
                    </label>
                    <div id="dSeccionLogoCompartido" style="display: none;">
                        <button type="button" id="dBtnAgregarLogoCompartido" style="
                            background: #0284c7;
                            color: white;
                            border: none;
                            padding: 10px 16px;
                            border-radius: 4px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.9rem;
                            width: 100%;
                            margin-bottom: 12px;
                        ">+ Agregar Logo Compartido</button>
                        <div id="dLogosCompartidosContenedor" style="display: grid; gap: 10px;">
                            <!-- Se agregan logos compartidos aquí -->
                        </div>
                    </div>
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
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: (modal) => {
            const inputPrenda = document.getElementById('dNombrePrenda');
            const listaSugerencias = document.getElementById('dListaSugerencias');
            const ubicacionesPorTecnicaDiv = document.getElementById('dUbicacionesPorTecnica');
            
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
            
            // Crear inputs de ubicación por técnica
            tecnicas.forEach((tecnica, idx) => {
                // Ubicación con múltiples valores
                const divUbicacion = document.createElement('div');
                divUbicacion.style.cssText = 'padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #eee;';
                
                const labelUbicacion = document.createElement('label');
                labelUbicacion.style.cssText = 'font-weight: 600; font-size: 0.9rem; color: #333; display: block; margin-bottom: 8px;';
                labelUbicacion.textContent = tecnica.nombre + ' - Ubicaciones';
                
                const inputDiv = document.createElement('div');
                inputDiv.style.cssText = 'display: flex; gap: 8px; margin-bottom: 8px;';
                
                const inputUbicacion = document.createElement('input');
                inputUbicacion.type = 'text';
                inputUbicacion.className = 'dUbicacionInput-' + idx;
                inputUbicacion.placeholder = 'Ej: Pecho, Espalda, Manga';
                inputUbicacion.style.cssText = 'flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem;';
                
                const btnAgregar = document.createElement('button');
                btnAgregar.type = 'button';
                btnAgregar.textContent = '+ Agregar';
                btnAgregar.style.cssText = 'background: #0066cc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; white-space: nowrap;';
                btnAgregar.className = 'dBtnAgregarUbicacion-' + idx;
                
                inputDiv.appendChild(inputUbicacion);
                inputDiv.appendChild(btnAgregar);
                
                const ubicacionesContainer = document.createElement('div');
                ubicacionesContainer.className = 'dUbicacionesList-' + idx;
                ubicacionesContainer.style.cssText = 'display: flex; flex-wrap: wrap; gap: 8px; min-height: 28px; align-content: flex-start;';
                
                divUbicacion.appendChild(labelUbicacion);
                divUbicacion.appendChild(inputDiv);
                divUbicacion.appendChild(ubicacionesContainer);
                
                ubicacionesPorTecnicaDiv.appendChild(divUbicacion);
                
                // Manejar agregar ubicaciones
                let ubicacionesTecnica = [];
                btnAgregar.addEventListener('click', (e) => {
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
                        btnAgregar.click();
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
                window['dUbicacionesTecnica' + idx] = ubicacionesTecnica;
                
                // Guardar referencias de los inputs y lista para el preConfirm
                inputUbicacion.setAttribute('data-tecnica-idx', idx);
                inputUbicacion.setAttribute('data-ubicaciones-list', 'dUbicacionesList-' + idx);
            });
            
            // Crear inputs de imagen por técnica con Drag and Drop
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                tecnicas.forEach((tecnica, idx) => {
                    // Array para almacenar imágenes de esta técnica
                    const imagenesAgregadas = [];
                    
                    const divImagen = document.createElement('div');
                    divImagen.setAttribute('data-tecnica-idx', idx);
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
            
            // ========================================
            // LÓGICA DE LOGO COMPARTIDO
            // ========================================
            const checkboxLogoCompartido = document.getElementById('dUsarLogoCompartido');
            const seccionLogoCompartido = document.getElementById('dSeccionLogoCompartido');
            const btnAgregarLogoCompartido = document.getElementById('dBtnAgregarLogoCompartido');
            
            // Almacenar logos compartidos en contexto
            const logosCompartidos = {};
            window.logosCompartidosCotizacionIndividual = logosCompartidos;
            
            checkboxLogoCompartido.addEventListener('change', (e) => {
                if (e.target.checked) {
                    seccionLogoCompartido.style.display = 'block';
                } else {
                    seccionLogoCompartido.style.display = 'none';
                    // Limpiar logos compartidos
                    for (let key in logosCompartidos) {
                        delete logosCompartidos[key];
                    }
                    document.getElementById('dLogosCompartidosContenedor').innerHTML = '';
                }
            });
            
            btnAgregarLogoCompartido.addEventListener('click', (e) => {
                e.preventDefault();
                abrirModalLogoCompartido(tecnicas, logosCompartidos);
            });
        },
        preConfirm: () => {
            // Seguridad anti doble click
            if (window.__guardandoTecnica) {
                Swal.showValidationMessage('Guardando...');
                return false;
            }
            window.__guardandoTecnica = true;
            const confirmBtn = Swal.getConfirmButton();
            if (confirmBtn) confirmBtn.disabled = true;

            // Validar prenda
            const nombrePrenda = document.getElementById('dNombrePrenda').value.trim().toUpperCase();
            if (!nombrePrenda) {
                Swal.showValidationMessage('Completa el nombre de la prenda');
                window.__guardandoTecnica = false;
                if (confirmBtn) confirmBtn.disabled = false;
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
                const ubicacionesList = window['dUbicacionesTecnica' + idx] || [];
                if (!ubicacionesList || ubicacionesList.length === 0) {
                    Swal.showValidationMessage(`Agrega al menos una ubicación para ${tecnica.nombre}`);
                    valido = false;
                    return;
                }
                ubicacionesPorTecnica[idx] = ubicacionesList;
            });
            
            if (!valido) {
                window.__guardandoTecnica = false;
                if (confirmBtn) confirmBtn.disabled = false;
                return false;
            }
            
            // Recopilar imágenes por técnica desde el nuevo sistema de drag and drop
            const imagenesPorTecnica = {};
            const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
            if (imagesPorTecnicaDiv) {
                const divImagenes = imagesPorTecnicaDiv.querySelectorAll('[data-tecnica-idx]');
                divImagenes.forEach((divImagen, idx) => {
                    const imagenesAgregadas = [];
                    const idxTecnica = parseInt(divImagen.getAttribute('data-tecnica-idx'));
                    if (divImagen.imagenesAgregadas && divImagen.imagenesAgregadas.length > 0) {
                        imagenesPorTecnica[idxTecnica] = divImagen.imagenesAgregadas;
                    }
                });
            }
            
            // Las variaciones están deshabilitadas
            const variacionesPrenda = {};
            
            // Las telas están deshabilitadas
            const telas = [];
            
            return {
                nombre_prenda: nombrePrenda,
                observaciones: document.getElementById('dObservaciones').value.trim(),
                ubicacionesPorTecnica: ubicacionesPorTecnica,
                imagenesPorTecnica: imagenesPorTecnica,
                logosCompartidos: window.logosCompartidosCotizacionIndividual || {},
                variaciones_prenda: null,
                telas: null  // Telas deshabilitadas
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Cerrar el modal inmediatamente
            Swal.close();
            // Guardar después de cerrar
            guardarTecnicaCombinada(result.value);

            // Liberar lock después de un tiempo razonable (guardarTecnicaCombinada usa Promises internas)
            setTimeout(() => {
                window.__guardandoTecnica = false;
            }, 1500);
        } else {
            // Cancelado o cerrado: liberar lock y limpiar estado
            window.__guardandoTecnica = false;
            window.tecnicasCombinadas = null;
            window.modoTecnicasCombinadas = null;
        }
    });
}

// =========================================================
// 4.3 MODAL LOGO COMPARTIDO
// =========================================================

function abrirModalLogoCompartido(tecnicas, logosCompartidos) {
    const checkboxesTecnicas = tecnicas.map((tecnica, idx) => `
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white; cursor: pointer;">
            <input type="checkbox" class="dCheckTecnicaCompartidaLogo" data-tecnica-idx="${idx}" data-tecnica-nombre="${tecnica.nombre}" style="width: 18px; height: 18px; cursor: pointer;">
            <label style="flex: 1; cursor: pointer; margin: 0; font-weight: 500; color: #333;">${tecnica.nombre}</label>
        </div>
    `).join('');
    
    // Modal emergente flotante
    const modalHTML = `
        <div id="dModalLogoCompartidoEmergente" style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            max-height: 80vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            z-index: 3000;
            padding: 30px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-y: auto;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #333;">Logos Compartidos</h2>
                <button type="button" id="dBtnCerrarModalLogoCompartido" style="
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
                <div class="dImagenesLogoCompartidoDropzone" style="
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
                    <input type="file" class="dImagenLogoCompartidoInput" accept="image/*" style="display: none;" />
                </div>
                <div class="dImagenLogoCompartidoPreview" style="margin-top: 12px; display: flex; justify-content: center;">
                    <!-- Preview de imagen -->
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button type="button" id="dBtnCancelarLogoCompartido" style="
                    background: #f0f0f0;
                    color: #333;
                    border: 1px solid #ddd;
                    padding: 10px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 0.9rem;
                ">Cancelar</button>
                <button type="button" id="dBtnGuardarLogoCompartido" style="
                    background: #0284c7;
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
        
        <!-- Backdrop -->
        <div id="dBackdropLogoCompartido" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 2999;
        "></div>
    `;
    
    // Insertar en DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modalElement = document.getElementById('dModalLogoCompartidoEmergente');
    const backdropElement = document.getElementById('dBackdropLogoCompartido');
    const btnCerrar = document.getElementById('dBtnCerrarModalLogoCompartido');
    const btnCancelar = document.getElementById('dBtnCancelarLogoCompartido');
    const btnGuardar = document.getElementById('dBtnGuardarLogoCompartido');
    const dropzone = modalElement.querySelector('.dImagenesLogoCompartidoDropzone');
    const inputFile = modalElement.querySelector('.dImagenLogoCompartidoInput');
    const previewDiv = modalElement.querySelector('.dImagenLogoCompartidoPreview');
    const tecnicasCheckboxes = modalElement.querySelectorAll('.dCheckTecnicaCompartidaLogo');
    
    let imagenSeleccionada = null;
    
    // Setup dropzone
    dropzone.addEventListener('click', () => inputFile.click());
    
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.background = '#e8f1ff';
        dropzone.style.borderColor = '#0284c7';
    });
    
    dropzone.addEventListener('dragleave', () => {
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
    });
    
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.background = '#fafafa';
        dropzone.style.borderColor = '#ddd';
        procesarArchivoLogoCompartido(Array.from(e.dataTransfer.files));
    });
    
    inputFile.addEventListener('change', (e) => {
        procesarArchivoLogoCompartido(Array.from(e.target.files));
    });
    
    function procesarArchivoLogoCompartido(archivos) {
        const imagenes = archivos.filter(f => f.type.startsWith('image/'));
        if (imagenes.length > 0) {
            imagenSeleccionada = imagenes[0];
            mostrarPreviewLogoCompartido();
        }
    }
    
    function mostrarPreviewLogoCompartido() {
        if (!imagenSeleccionada) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            previewDiv.innerHTML = `
                <div style="position: relative; width: 100px; height: 100px; border-radius: 6px; overflow: hidden; border: 2px solid #0284c7;">
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
        
        if (!imagenSeleccionada) {
            alert('Sube una imagen para las técnicas compartidas');
            return;
        }
        
        // Procesar
        const clave = tecnicasSeleccionadas.sort().join('-');
        logosCompartidos[clave] = imagenSeleccionada;
        
        console.log('🔍 DEBUG - Logo guardado en modal:', {
            clave: clave,
            imagen: imagenSeleccionada,
            imagenNombre: imagenSeleccionada.name,
            logosCompartidos: logosCompartidos,
            windowLogos: window.logosCompartidosCotizacionIndividual
        });
        
        // Actualizar UI
        mostrarLogosCompartidosAgregados(logosCompartidos, tecnicasSeleccionadas);
        cerrarModal();
    }
    
    // Event listeners
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    btnGuardar.addEventListener('click', guardarYCerrar);
    backdropElement.addEventListener('click', cerrarModal);
}

function mostrarLogosCompartidosAgregados(logosCompartidos, tecnicasNuevas) {
    const contenedor = document.getElementById('dLogosCompartidosContenedor');
    if (!contenedor) return;
    
    // Reconstruir contenedor
    contenedor.innerHTML = '';
    
    // Obtener el div de imágenes por técnica (para ocultar/mostrar dropzones)
    const imagesPorTecnicaDiv = document.getElementById('dImagenesPorTecnica');
    const tecnicas = window.tecnicasCombinadas || [];
    
    // Primero, mostrar TODOS los dropzones (estado inicial)
    if (imagesPorTecnicaDiv) {
        tecnicas.forEach((tecnica, idx) => {
            const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${idx}"]`);
            if (divTecnica) {
                divTecnica.style.display = 'block';
            }
        });
    }
    
    // Ahora, ocultar los que tienen logos compartidos
    for (let clave in logosCompartidos) {
        const tecnicasArray = clave.split('-');
        const imagen = logosCompartidos[clave];
        
        // PASO 1: Ocultar los contenedores de las técnicas que comparten logo
        tecnicasArray.forEach(nombreTecnica => {
            const tecnicaIdx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
            if (tecnicaIdx >= 0 && imagesPorTecnicaDiv) {
                const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${tecnicaIdx}"]`);
                if (divTecnica) {
                    divTecnica.style.display = 'none';
                }
            }
        });
        
        // PASO 2: Crear tarjeta del logo compartido (COMPACTA COMO PASO 3)
        const divLogo = document.createElement('div');
        divLogo.className = 'dLogoCompartidoItem';
        divLogo.style.cssText = 'padding: 8px 12px; background: #e0f2fe; border: 2px solid #0284c7; border-radius: 4px; display: flex; gap: 10px; align-items: center; margin-bottom: 8px;';
        divLogo.setAttribute('data-clave', clave);
        
        // Crear preview ANTES que el label (para que el texto vaya a la derecha)
        const previewDiv = document.createElement('div');
        previewDiv.style.cssText = 'flex: 0 0 auto;';
        previewDiv.className = `dImagenCompartidaPreview-${clave}`;
        
        const img = document.createElement('img');
        img.style.cssText = 'width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid #0284c7;';
        
        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.readAsDataURL(imagen);
        
        previewDiv.appendChild(img);
        
        // Label con texto (a la derecha de la imagen)
        const labelDiv = document.createElement('div');
        labelDiv.style.cssText = 'flex: 1; min-width: 0;';
        labelDiv.innerHTML = `
            <label style="font-weight: 600; font-size: 0.9rem; color: #0c4a6e; display: block; margin: 0; word-break: break-word;">
                ✓ Logo compartido - ${tecnicasArray.join(' - ')}
            </label>
        `;
        
        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = '✕';
        btnEliminar.style.cssText = 'background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 1.1rem; flex: 0 0 auto; line-height: 1;';
        btnEliminar.className = `dBtnEliminarCompartida-${clave}`;
        
        btnEliminar.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Eliminar del objeto de logos
            delete logosCompartidos[clave];
            
            // Mostrar nuevamente los dropzones de las técnicas
            tecnicasArray.forEach(nombreTecnica => {
                const idx = tecnicas.findIndex(t => t.nombre === nombreTecnica);
                if (idx >= 0 && imagesPorTecnicaDiv) {
                    const divTecnica = imagesPorTecnicaDiv.querySelector(`[data-tecnica-idx="${idx}"]`);
                    if (divTecnica) {
                        divTecnica.style.display = 'block';
                    }
                }
            });
            
            // Eliminar la tarjeta
            divLogo.remove();
            
            // Actualizar referencia global
            window.logosCompartidosCotizacionIndividual = logosCompartidos;
        });
        
        divLogo.appendChild(previewDiv);
        divLogo.appendChild(labelDiv);
        divLogo.appendChild(btnEliminar);
        
        contenedor.appendChild(divLogo);
    }
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
        </div>
    `;
    
    container.appendChild(fila);
    
    const dropzone = fila.querySelector(`.imagenes-dropzone-${prendasIndex}`);
    const input = fila.querySelector(`.imagen-prenda-input-${prendasIndex}`);
    const previewContainer = fila.querySelector(`.imagenes-preview-${prendasIndex}`);
    const imagenesAgregadas = configurarDropzoneImagenes({ dropzone, input, previewContainer, maximo: 3 });

    Object.defineProperty(fila, 'imagenesAgregadas', {
        get: function() { return imagenesAgregadas; }
    });
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

// =========================================================
// 6. GUARDAR TÉCNICA CON PRENDAS
// =========================================================

async function guardarTecnica() {
    // Lock anti doble click
    if (window.__guardandoTecnica) {
        return;
    }
    window.__guardandoTecnica = true;
    // Fallback: liberar lock aunque alguna rama sea async por Promises internas
    setTimeout(() => {
        window.__guardandoTecnica = false;
    }, 1200);

    // MODO EDICIÓN: actualizar prenda(s) existente(s) en tecnicasAgregadas
    if (window.modoEdicionPrenda) {
        guardarEdicionPrenda();
        return;
    }

    // Verificar si estamos en modo técnicas combinadas
    if (window.modoTecnicasCombinadas === 'iguales') {

        guardarTecnicaCombinada();
        return;
    }

    // Modo simple: una única técnica
    guardarTecnicaSimple();
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
    
    console.log('🔍 DEBUG - guardarTecnicaCombinada() llamado con datosForm:', !!datosForm);

    
    // Si no se pasa datosForm, construirlo desde el formulario actual
    if (!datosForm) {
        console.log('🔍 DEBUG - Construyendo datosForm desde formulario...');
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
        
        // Obtener telas (COLOR, TELA, REFERENCIA E IMAGEN)
        const telas = [];
        const tbodyTelas = document.getElementById('dTablaTelasMulti');
        console.log('🔍 DEBUG - Buscando tabla de telas:', {
            tbodyTelasEncontrado: !!tbodyTelas,
            elementId: 'dTablaTelasMulti'
        });
        
        if (tbodyTelas) {
            const filasTelaMulti = tbodyTelas.querySelectorAll('.fila-tela-multi');
            console.log(`🔍 DEBUG - Filas de telas encontradas: ${filasTelaMulti.length}`);
            
            filasTelaMulti.forEach((filaTela, telaIdx) => {
                const color = filaTela.querySelector('.input-color-multi')?.value?.trim();
                const tela = filaTela.querySelector('.input-tela-multi')?.value?.trim();
                const referencia = filaTela.querySelector('.input-referencia-multi')?.value?.trim();
                const inputFile = filaTela.querySelector('.input-file-tela-multi');
                
                // Guardar archivo directamente (no usar FileReader async)
                const archivo = (inputFile && inputFile.files.length > 0) 
                    ? inputFile.files[0] 
                    : null;
                
                if (color || tela || referencia) {
                    telas.push({
                        color: color || null,
                        tela: tela || null,
                        referencia: referencia || null,
                        archivo: archivo
                    });
                }
            });
        }
        
        console.log(` DEBUG - Telas extraídas para datosForm:`, telas);
        
        datosForm = {
            nombre_prenda: nombrePrenda,
            observaciones: document.getElementById('dObservaciones')?.value.trim() || '',
            ubicacionesPorTecnica: ubicacionesPorTecnica,
            imagenesPorTecnica: imagenesPorTecnica,
            telas: telas.length > 0 ? telas : null
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
        // PROCESAR LOGOS COMPARTIDOS PRIMERO
        const logosCompartidosProcessados = {};
        const logosCompartidos = datosForm.logosCompartidos || {};
        
        // Procesar cada logo compartido
        for (let clave in logosCompartidos) {
            const archivo = logosCompartidos[clave];
            const tecnicasCompartidas = clave.split('-');
            
            logosCompartidosProcessados[clave] = new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    resolve({
                        data_url: e.target.result,
                        nombre: archivo.name,
                        tecnicasCompartidas: tecnicasCompartidas,
                        clave: clave
                    });
                };
                reader.readAsDataURL(archivo);
            });
        }
        
        // Cuando todos los logos compartidos estén procesados
        Promise.all(Object.values(logosCompartidosProcessados)).then((logosProcessados) => {
            // Para cada técnica
            tecnicas.forEach((tipo, idx) => {
                const ubicacionesTecnica = datosForm.ubicacionesPorTecnica[idx] || [];
                const imagenesTecnica = imagenesProcessadas[idx] || [];
                
                // Agregar logos compartidos de esta técnica
                const logosParaEstaTecnica = logosProcessados.filter(logo => 
                    logo.tecnicasCompartidas.includes(tipo.nombre)
                );
                
                // Combinar imágenes: las propias + los logos compartidos
                const todasLasImagenes = [
                    ...imagenesTecnica.map(img => ({
                        ...img,
                        esCompartida: false
                    })),
                    ...logosParaEstaTecnica.map((logo, logoIdx) => ({
                        data_url: logo.data_url,
                        nombre: logo.nombre,
                        nombreCompartido: logo.clave,
                        tecnicasCompartidas: logo.tecnicasCompartidas,
                        esCompartida: true,
                        imagenIndex: imagenesTecnica.length + logoIdx
                    }))
                ];
                
                const nuevaTecnica = {
                    tipo_logo: tipo,
                    prendas: [{
                        nombre_prenda: datosForm.nombre_prenda,
                        observaciones: datosForm.observaciones,
                        ubicaciones: ubicacionesTecnica,
                        talla_cantidad: [],
                        variaciones_prenda: datosForm.variaciones_prenda || null,
                        imagenes_files: datosForm.imagenesPorTecnica[idx] || [],
                        imagenes_data_urls: todasLasImagenes,
                        telas: datosForm.telas || null
                    }],
                    es_combinada: true,
                    grupo_combinado: grupoId,
                    logosCompartidos: datosForm.logosCompartidos  // Pasar logos compartidos para envío al backend
                };
                
                console.log('🔍 DEBUG - nuevaTecnica armada:', {
                    tecnica: tipo.nombre,
                    logosCompartidos: datosForm.logosCompartidos,
                    claves: Object.keys(datosForm.logosCompartidos || {})
                });

                tecnicasAgregadas.push(nuevaTecnica);
            });
            

            
            window.tecnicasAgregadas = tecnicasAgregadas;
            
            // Limpiar contexto
            window.tecnicasCombinadas = null;
            window.modoTecnicasCombinadas = null;
            window.logosCompartidosCotizacionIndividual = {};
            
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
    });
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
        
        // Las tallas son opcionales, no validar si están vacías
        
        // Las variaciones están deshabilitadas
        const variacionesPrenda = {};
        
        // Las telas están deshabilitadas
        const telas = [];
        

        
        prendas.push({
            nombre_prenda: nombrePrenda,
            observaciones: observaciones,
            ubicaciones: ubicacionesChecked,
            talla_cantidad: [],
            imagenes_files: imagenesArray,  // Array de File objects
            variaciones_prenda: null,  // Variaciones deshabilitadas
            telas: null  // Telas deshabilitadas
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
            let imagenesCompartidasProcesadas = {};
            
            // PASO 1: Procesar logos compartidos (si existen) - SOLO UNA VEZ
            const logosCompartidos = tecnica.logosCompartidos || {};
            console.log('🔍 DEBUG - Logos compartidos recibidos:', {
                tecnica: tecnica.tipo_logo.nombre,
                logosCompartidos: logosCompartidos,
                claves: Object.keys(logosCompartidos),
                cantidad: Object.keys(logosCompartidos).length
            });
            const logosProcesados = {}; // Para almacenar qué claves ya procesamos
            
            for (let clave in logosCompartidos) {
                // Si ya procesamos este logo, saltar
                if (logosProcesados[clave]) continue;
                
                const archivo = logosCompartidos[clave];
                const tecnicasCompartidas = clave.split('-');
                
                // Enviar el archivo una única vez
                const fieldName = `imagenes_logo_compartido_${totalArchivos}`;
                formData.append(fieldName, archivo);
                
                // Enviar metadatos con la información de qué técnicas comparten este logo
                formData.append(`logo_compartido_metadata_${totalArchivos}`, JSON.stringify({
                    nombreCompartido: clave,
                    tecnicasCompartidas: tecnicasCompartidas,
                    nombreArchivo: archivo.name
                }));
                
                logosProcesados[clave] = true;
                imagenesCompartidasProcesadas[clave] = true;
                totalArchivos++;
            }
            
            // PASO 2: Procesar imágenes propias (no compartidas)
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
    const prendasMap = {};
    const imagenesParaCargar = [];
    const imagenesCompartidasYaProcesadas = new Set(); // Para evitar duplicados
    
    tecnicasAgregadas.forEach((tecnica, tecnicaIndex) => {
        tecnica.prendas.forEach(prenda => {
            const nombrePrenda = prenda.nombre_prenda || 'SIN NOMBRE';
            
            if (!prendasMap[nombrePrenda]) {
                prendasMap[nombrePrenda] = {
                    nombre_prenda: nombrePrenda,
                    observaciones: prenda.observaciones,
                    tecnicas: [],
                    imagenes: [],
                    _imagenesKeys: new Set()
                };
            }

            const agregarImagenDedupe = (imgObj) => {
                const dataKey = imgObj.data || '';
                const tecnicaKey = imgObj.tecnica || '';
                const compartidaKey = imgObj.esCompartida ? 'C' : 'N';
                const key = `${compartidaKey}|${tecnicaKey}|${dataKey}`;
                if (prendasMap[nombrePrenda]._imagenesKeys.has(key)) return;
                prendasMap[nombrePrenda]._imagenesKeys.add(key);
                prendasMap[nombrePrenda].imagenes.push(imgObj);
            };
            
            // Agregar técnica y sus imágenes
            prendasMap[nombrePrenda].tecnicas.push({
                tecnica: tecnica,
                tecnicaIndex: tecnicaIndex
            });
            
            // Procesar imágenes - SOLO desde imagenes_files si NO hay imagenes_data_urls
            // (en combinadas suele existir ambos, y eso causa duplicados en el card)
            const tieneDataUrls = prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0;
            if (!tieneDataUrls && prenda.imagenes_files && prenda.imagenes_files.length > 0) {
                prenda.imagenes_files.forEach(archivo => {
                    imagenesParaCargar.push({
                        archivo: archivo,
                        nombrePrenda: nombrePrenda,
                        tecnica: tecnica.tipo_logo.nombre,
                        tecnicaColor: tecnica.tipo_logo.color,
                        esCompartida: false
                    });
                });
            }
            
            // Procesar imágenes_data_urls (pueden venir del paso 3 con referencias a tecnicas)
            if (prenda.imagenes_data_urls && prenda.imagenes_data_urls.length > 0) {
                prenda.imagenes_data_urls.forEach(img => {
                    const esCompartida = img.esCompartida === true || !!img.nombreCompartido;
                    
                    // Para imágenes compartidas: evitar duplicación
                    if (esCompartida) {
                        const claveCompartida = img.nombreCompartido || 'compartida';
                        if (!imagenesCompartidasYaProcesadas.has(claveCompartida)) {
                            imagenesCompartidasYaProcesadas.add(claveCompartida);
                            agregarImagenDedupe({
                                data: img.data_url || img,
                                tecnica: '⭐ COMPARTIDA',
                                tecnicaColor: '#e0a853', // Dorado para imágenes compartidas
                                esCompartida: true,
                                nombreCompartido: img.nombreCompartido || null,
                                tecnicasCompartidas: img.tecnicasCompartidas || []
                            });
                        }
                    } else {
                        // Imágenes normales (no compartidas)
                        agregarImagenDedupe({
                            data: img.data_url || img,
                            tecnica: tecnica.tipo_logo.nombre,
                            tecnicaColor: tecnica.tipo_logo.color,
                            esCompartida: false
                        });
                    }
                });
            }
        });
    });
    
    
    // Procesar las imágenes de File objects de forma asíncrona
    imagenesParaCargar.forEach(imgData => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const prendaEntry = prendasMap[imgData.nombrePrenda];
            if (prendaEntry) {
                const data = e.target.result;
                const key = `N|${imgData.tecnica || ''}|${data || ''}`;
                if (!prendaEntry._imagenesKeys.has(key)) {
                    prendaEntry._imagenesKeys.add(key);
                    prendaEntry.imagenes.push({
                        data,
                        tecnica: imgData.tecnica,
                        tecnicaColor: imgData.tecnicaColor,
                        esCompartida: false
                    });
                }
            }
            
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
            <i class="fas fa-edit" style="font-size: 1rem;"></i>
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
            <i class="fas fa-trash" style="font-size: 1rem;"></i>
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
        
        // SECCIÓN DE IMÁGENES CON INDICADOR DE TÉCNICA
        if (datosPrenda.imagenes && datosPrenda.imagenes.length > 0) {
            bodyHTML += `
                <div class="imagenes-section" style="margin: 1.2rem 0;">
                    <h6 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                         Imágenes:
                    </h6>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                        ${datosPrenda.imagenes.map((img, imgIdx) => {
                            // Renderizar diferente si es compartida
                            if (img.esCompartida) {
                                return `
                                    <div style="position: relative; border-radius: 4px; overflow: hidden; border: 3px solid #e0a853; aspect-ratio: 1; width: auto; max-width: 120px; flex: 0 1 auto; box-shadow: 0 0 8px rgba(224, 168, 83, 0.4);">
                                        <img src="${img.data}" style="width: 100%; height: 100%; object-fit: cover;" alt="Logo compartido">
                                        <div style="
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            right: 0;
                                            background: linear-gradient(to bottom, rgba(224, 168, 83, 0.9), transparent);
                                            padding: 0.4rem;
                                        ">
                                            <span style="
                                                color: white;
                                                font-size: 0.65rem;
                                                font-weight: 700;
                                                background: #e0a853;
                                                padding: 0.2rem 0.4rem;
                                                border-radius: 2px;
                                                display: inline-block;
                                            ">
                                                ⭐ COMPARTIDO
                                            </span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                return `
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
                                `;
                            }
                        }).join('')}
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
        
        // Las telas están deshabilitadas
        let telasPrenda = [];
        
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
        
        // PROCESAR ARCHIVOS DE TELAS (File objects -> data URLs)
        if (datosPrenda.tecnicas && datosPrenda.tecnicas.length > 0) {
            datosPrenda.tecnicas.forEach(tecData => {
                const tecnica = tecData.tecnica;
                if (tecnica.prendas && tecnica.prendas.length > 0) {
                    tecnica.prendas.forEach(p => {
                        if (p.nombre_prenda === nombrePrenda && p.telas && p.telas.length > 0) {
                            p.telas.forEach((tela, telaIdx) => {
                                if (tela.archivo && tela.archivo instanceof File) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        // Actualizar el data URL en la estructura
                                        tela.imagen = e.target.result;
                                        
                                        // Actualizar la imagen en la tarjeta renderizada
                                        const row = tarjeta.querySelector(`[data-tela-idx="${telaIdx}"]`);
                                        if (row) {
                                            const tdImagen = row.querySelector('td:last-child');
                                            if (tdImagen) {
                                                tdImagen.innerHTML = `<img src="${e.target.result}" style="max-width: 60px; max-height: 60px; border-radius: 4px; border: 1px solid #ddd;" alt="Imagen tela">`;
                                            }
                                        }
                                    };
                                    reader.readAsDataURL(tela.archivo);
                                }
                            });
                        }
                    });
                }
            });
        }
    });
    
    container.appendChild(contenedor);
    
    // EVENT LISTENERS para botones en el header
    document.querySelectorAll('.btn-editar-prenda').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const indicesStr = e.currentTarget.getAttribute('data-tecnica-indices');
            const nombrePrenda = e.currentTarget.getAttribute('data-prenda-nombre');
            const indices = indicesStr.split(',').map(Number);
            abrirModalEditarPrenda(indices, nombrePrenda);
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

function abrirModalEditarPrenda(tecnicaIndices, nombrePrenda) {
    // Obtener las técnicas del grupo
    const tecnicasDelGrupo = tecnicaIndices.map(idx => tecnicasAgregadas[idx]);
    
    if (tecnicasDelGrupo.length === 0) {
        console.error('❌ No se encontraron las técnicas para editar');
        return;
    }
    
    // Buscar los datos de la prenda a editar
    let datosPrenda = null;
    let tecnicasInfo = [];
    
    tecnicasDelGrupo.forEach((tecnica, tecnicaIdx) => {
        tecnica.prendas.forEach(prenda => {
            if (prenda.nombre_prenda === nombrePrenda) {
                if (!datosPrenda) {
                    datosPrenda = {
                        nombre_prenda: prenda.nombre_prenda,
                        observaciones: prenda.observaciones || '',
                        tecnicas_imagenes: {},
                        logoCompartido: {
                            enabled: false,
                            items: {},
                            files: {}
                        }
                    };
                }

                // Detectar logos compartidos por metadata dentro de imagenes_data_urls
                const imagenesData = Array.isArray(prenda.imagenes_data_urls) ? prenda.imagenes_data_urls : [];
                const imagenesPropias = [];
                imagenesData.forEach(img => {
                    const esObj = img && typeof img === 'object';
                    const esCompartida = esObj && (img.esCompartida === true || !!img.nombreCompartido);
                    if (esCompartida) {
                        const clave = img.nombreCompartido || 'compartida';
                        if (!datosPrenda.logoCompartido.items[clave]) {
                            datosPrenda.logoCompartido.items[clave] = {
                                tecnicasCompartidas: Array.isArray(img.tecnicasCompartidas) ? img.tecnicasCompartidas : (clave.split('-').map(t => t.trim()).filter(Boolean)),
                                previewUrl: img.data_url || ''
                            };
                        }
                        datosPrenda.logoCompartido.enabled = true;
                    } else {
                        const url = (esObj && img.data_url) ? img.data_url : img;
                        if (url) imagenesPropias.push(url);
                    }
                });

                // Si técnica tiene logosCompartidos en memoria, guardarlos para reemplazo
                if (tecnica.logosCompartidos && Object.keys(tecnica.logosCompartidos).length > 0) {
                    Object.keys(tecnica.logosCompartidos).forEach((clave) => {
                        const file = tecnica.logosCompartidos[clave];
                        if (file instanceof File) {
                            datosPrenda.logoCompartido.files[clave] = file;
                        }
                        if (!datosPrenda.logoCompartido.items[clave]) {
                            datosPrenda.logoCompartido.items[clave] = {
                                tecnicasCompartidas: clave.split('-').map(t => t.trim()).filter(Boolean),
                                previewUrl: ''
                            };
                        }
                        datosPrenda.logoCompartido.enabled = true;
                    });
                }

                // Almacenar imágenes por técnica (SOLO PROPIAS)
                datosPrenda.tecnicas_imagenes[tecnica.tipo_logo.nombre] = {
                    imagenes_data_urls: imagenesPropias,
                    imagenes_files: prenda.imagenes_files || [],
                    tipo_logo: tecnica.tipo_logo,
                    tecnicaIndex: tecnicaIndices[tecnicaIdx],
                    ubicaciones: prenda.ubicaciones || []
                };
                
                tecnicasInfo.push({
                    tecnicaIndex: tecnicaIndices[tecnicaIdx],
                    tipo_logo: tecnica.tipo_logo,
                    ubicaciones: prenda.ubicaciones || [],
                    imagenes_data_urls: prenda.imagenes_data_urls || [],
                    imagenes_files: prenda.imagenes_files || []
                });
            }
        });
    });
    
    if (!datosPrenda) {
        console.error('❌ No se encontró la prenda:', nombrePrenda);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró la prenda especificada'
        });
        return;
    }
    
    // Usar el mismo modal que para agregar pero con datos existentes
    abrirModalSimpleTecnicaConDatos(tecnicasDelGrupo[0], datosPrenda, tecnicasInfo, tecnicaIndices);
}

function abrirModalSimpleTecnicaConDatos(tipo, datosPrenda, tecnicasInfo, tecnicaIndices) {
    // Establecer modo edición
    window.modoEdicionPrenda = true;
    window.datosPrendaEdicion = datosPrenda;
    window.tecnicasInfoEdicion = tecnicasInfo;
    window.tecnicaIndicesEdicion = tecnicaIndices;
    window.prendaNombreOriginalEdicion = datosPrenda && datosPrenda.nombre_prenda ? datosPrenda.nombre_prenda : null;
    
    // Actualizar el nombre de la técnica en el modal
    const nombreElement = document.getElementById('tecnicaSeleccionadaNombre');
    if (nombreElement) {
        nombreElement.textContent = tipo.nombre;
    }
    
    // Limpiar y cargar prendas del modal con datos existentes
    const listaPrendas = document.getElementById('listaPrendas');
    if (listaPrendas) {
        listaPrendas.innerHTML = '';
        
        // Agregar la prenda con datos existentes
        agregarFilaPrendaConDatos(datosPrenda);
    }
    
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

function agregarFilaPrendaConDatos(datosPrenda) {
    const listaPrendas = document.getElementById('listaPrendas');
    if (!listaPrendas) return;
    
    const prendaDiv = document.createElement('div');
    prendaDiv.className = 'prenda-item';
    prendaDiv.style.cssText = 'border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9f9f9;';
    
    // Sección Logo Compartido (si aplica)
    let logoCompartidoHTML = '';
    if (datosPrenda && datosPrenda.logoCompartido && Object.keys(datosPrenda.logoCompartido.items || {}).length > 0) {
        const filas = Object.entries(datosPrenda.logoCompartido.items).map(([clave, item]) => {
            const tecnicasTxt = (item.tecnicasCompartidas || []).join(' + ');
            const previewUrl = item.previewUrl || '';
            return `
                <div class="edit-logo-compartido-row" data-logo-clave="${clave}" style="border: 1px solid #dbeafe; background: #f0f7ff; border-radius: 6px; padding: 10px;">
                    <div style="font-weight: 700; color: #0369a1; margin-bottom: 6px;">Logo compartido: ${tecnicasTxt}</div>
                    <div style="display: grid; grid-template-columns: 110px 1fr; gap: 10px; align-items: center;">
                        <div class="edit-logo-compartido-preview" data-logo-clave="${clave}" style="width: 110px; height: 110px; border-radius: 6px; overflow: hidden; border: 1px solid #bfdbfe; background: white; display: flex; align-items: center; justify-content: center;">
                            ${previewUrl ? `<img src="${previewUrl}" style="width: 100%; height: 100%; object-fit: cover;" />` : `<span style="color:#64748b; font-size:0.8rem;">Sin imagen</span>`}
                        </div>
                        <div>
                            <input type="file" class="edit-input-logo-compartido" data-logo-clave="${clave}" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #0284c7; border-radius: 4px; cursor: pointer; box-sizing: border-box; font-size: 0.85rem;">
                            <div style="margin-top: 6px; color: #64748b; font-size: 0.8rem;">Reemplazar logo compartido</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const enabled = !!datosPrenda.logoCompartido.enabled;
        logoCompartidoHTML = `
            <div style="margin-bottom: 16px; padding: 12px; border: 2px solid #dbeafe; border-radius: 8px; background: #f8fbff;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom: 10px;">
                    <input type="checkbox" id="editUsarLogoCompartido" ${enabled ? 'checked' : ''} style="width:18px; height:18px; cursor:pointer;" />
                    <span style="font-weight:700; color:#0369a1;">Usar logo compartido</span>
                </label>
                <div id="editSeccionLogoCompartido" style="display: ${enabled ? 'grid' : 'none'}; gap: 10px;">
                    ${filas}
                </div>
            </div>
        `;
    }

    // Generar HTML por técnica: Ubicaciones + Imágenes
    let tecnicasHTML = '';
    if (datosPrenda.tecnicas_imagenes) {
        Object.entries(datosPrenda.tecnicas_imagenes).forEach(([nombreTecnica, datosTecnica]) => {
            const color = (datosTecnica.tipo_logo && datosTecnica.tipo_logo.color) ? datosTecnica.tipo_logo.color : '#333';
            const imagenesTecnica = (datosTecnica.imagenes_data_urls || []).map(img => (img && typeof img === 'object' && img.data_url) ? img.data_url : img);
            const ubicacionesTecnica = Array.isArray(datosTecnica.ubicaciones) ? datosTecnica.ubicaciones : [];
            const ocultarImagenes = !!(datosPrenda.logoCompartido && datosPrenda.logoCompartido.enabled);
            tecnicasHTML += `
                <div class="tecnica-edit-block" data-tecnica="${nombreTecnica}" style="margin-bottom: 18px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background: white;">
                    <div style="font-weight: 700; margin-bottom: 10px; color: ${color};">${nombreTecnica}</div>

                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #333;">Ubicaciones (${nombreTecnica})</label>
                        <textarea class="ubicaciones-tecnica" data-tecnica="${nombreTecnica}" rows="2" placeholder="Ej: PECHO, ESPALDA, MANGA" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${ubicacionesTecnica.join(', ')}</textarea>
                    </div>

                    <div class="tecnica-imagenes-block" style="margin-bottom: 6px; display: ${ocultarImagenes ? 'none' : 'block'};">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: ${color};">Imágenes - ${nombreTecnica}</label>
                        <div class="imagenes-preview" data-tecnica="${nombreTecnica}" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px;">
                            ${imagenesTecnica.map((img, idx) => `
                                <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx}">
                                    <img src="${img}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                    <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                                </div>
                            `).join('')}
                        </div>
                        <input type="file" class="input-imagenes" data-tecnica="${nombreTecnica}" accept="image/*" multiple style="width: 100%; padding: 6px; border: 1px dashed #0369a1; border-radius: 4px; cursor: pointer; box-sizing: border-box; font-size: 0.85rem;">
                    </div>
                </div>
            `;
        });
    }
    
    prendaDiv.innerHTML = `
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Nombre de la Prenda</label>
            <input type="text" class="nombre_prenda" value="${datosPrenda.nombre_prenda || ''}" placeholder="POLO, CAMISA, PANTALÓN..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; text-transform: uppercase;">
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">Observaciones</label>
            <textarea class="observaciones" placeholder="Detalles adicionales" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; resize: vertical;">${datosPrenda.observaciones || ''}</textarea>
        </div>
        
        ${logoCompartidoHTML}
        ${tecnicasHTML}
    `;
    
    listaPrendas.appendChild(prendaDiv);
    
    // Agregar event listeners para la funcionalidad de edición
    agregarEventListenersEdicionPrenda(prendaDiv, datosPrenda);
}

function agregarEventListenersEdicionPrenda(prendaDiv, datosPrenda) {
    // Ubicaciones por técnica (textarea por técnica)
    prendaDiv.querySelectorAll('.ubicaciones-tecnica').forEach(textarea => {
        textarea.addEventListener('input', (e) => {
            const tecnica = textarea.getAttribute('data-tecnica');
            if (!tecnica || !datosPrenda.tecnicas_imagenes || !datosPrenda.tecnicas_imagenes[tecnica]) return;
            const ubicaciones = textarea.value
                .split(',')
                .map(u => u.trim().toUpperCase())
                .filter(u => u.length > 0);
            datosPrenda.tecnicas_imagenes[tecnica].ubicaciones = ubicaciones;
        });
    });
    
    // Event listeners para imágenes por técnica
    const inputsImagenes = prendaDiv.querySelectorAll('.input-imagenes');
    
    inputsImagenes.forEach(input => {
        const nombreTecnica = input.getAttribute('data-tecnica');
        const bloque = input.closest('.tecnica-edit-block');
        const imagenesPreview = bloque ? bloque.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`) : null;
        
        input.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        // Agregar imagen a la técnica correspondiente
                        if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls) {
                            datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls = [];
                        }

                        // Mantener también los File reales para el envío
                        if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta) {
                            datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta = [];
                        }
                        if (!datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files) {
                            datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files = [];
                        }

                        const dataUrl = event.target.result;
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_data_urls.push(dataUrl);

                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta.push({
                            file,
                            data_url: dataUrl
                        });
                        datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files =
                            datosPrenda.tecnicas_imagenes[nombreTecnica].imagenes_files_meta
                                .map(x => x.file)
                                .filter(f => f instanceof File);

                        actualizarImagenesTecnica(nombreTecnica);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            e.target.value = '';
        });
        
        function actualizarImagenesTecnica(tecnicaNombre) {
            const datosTecnica = datosPrenda.tecnicas_imagenes[tecnicaNombre];
            const imagenesTecnica = datosTecnica.imagenes_data_urls || [];
            
            const previewContainer = prendaDiv.querySelector(`.imagenes-preview[data-tecnica="${tecnicaNombre}"]`) || imagenesPreview;
            
            if (previewContainer) {
                previewContainer.innerHTML = imagenesTecnica.map((img, idx) => `
                    <div style="position: relative;" class="imagen-item" data-tecnica="${tecnicaNombre}" data-idx="${idx}">
                        <img src="${img}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <button type="button" class="btn-eliminar-imagen" data-tecnica="${tecnicaNombre}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                    </div>
                `).join('');
                
                // Agregar listeners a los nuevos botones de eliminar imagen
                previewContainer.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const tecnica = btn.getAttribute('data-tecnica');
                        const idx = parseInt(btn.getAttribute('data-idx'));
                        const datosT = datosPrenda.tecnicas_imagenes[tecnica];
                        const removed = (datosT.imagenes_data_urls || [])[idx];
                        if (Array.isArray(datosT.imagenes_data_urls)) {
                            datosT.imagenes_data_urls.splice(idx, 1);
                        }

                        // Si la imagen eliminada corresponde a un File nuevo, removerlo también
                        if (removed && Array.isArray(datosT.imagenes_files_meta)) {
                            datosT.imagenes_files_meta = datosT.imagenes_files_meta.filter(m => m && m.data_url !== removed);
                            datosT.imagenes_files = (datosT.imagenes_files_meta || [])
                                .map(m => m.file)
                                .filter(f => f instanceof File);
                        }
                        actualizarImagenesTecnica(tecnica);
                    });
                });
            }
        }
        
        // Agregar listeners iniciales para eliminar imágenes
        const imagenesPreviewContainer = bloque ? bloque.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`) : null;
        if (imagenesPreviewContainer) {
            imagenesPreviewContainer.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tecnica = btn.getAttribute('data-tecnica');
                    const idx = parseInt(btn.getAttribute('data-idx'));
                    if (datosPrenda.tecnicas_imagenes && datosPrenda.tecnicas_imagenes[tecnica]) {
                        const datosT = datosPrenda.tecnicas_imagenes[tecnica];
                        const removed = (datosT.imagenes_data_urls || [])[idx];
                        if (Array.isArray(datosT.imagenes_data_urls)) {
                            datosT.imagenes_data_urls.splice(idx, 1);
                        }
                        if (removed && Array.isArray(datosT.imagenes_files_meta)) {
                            datosT.imagenes_files_meta = datosT.imagenes_files_meta.filter(m => m && m.data_url !== removed);
                            datosT.imagenes_files = (datosT.imagenes_files_meta || [])
                                .map(m => m.file)
                                .filter(f => f instanceof File);
                        }
                    }
                    actualizarImagenesTecnica(tecnica);
                });
            });
        }
    });

    // Toggle logo compartido (oculta/muestra secciones de imágenes por técnica)
    const chkLogoCompartido = prendaDiv.querySelector('#editUsarLogoCompartido');
    const seccionLogoCompartido = prendaDiv.querySelector('#editSeccionLogoCompartido');
    if (chkLogoCompartido && datosPrenda && datosPrenda.logoCompartido) {
        chkLogoCompartido.addEventListener('change', () => {
            datosPrenda.logoCompartido.enabled = chkLogoCompartido.checked;
            if (seccionLogoCompartido) {
                seccionLogoCompartido.style.display = chkLogoCompartido.checked ? 'grid' : 'none';
            }
            prendaDiv.querySelectorAll('.tecnica-imagenes-block').forEach(div => {
                div.style.display = chkLogoCompartido.checked ? 'none' : 'block';
            });
        });
    }

    // Reemplazo de logo compartido
    prendaDiv.querySelectorAll('.edit-input-logo-compartido').forEach(input => {
        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
            if (!file) return;
            const clave = input.getAttribute('data-logo-clave');
            if (!clave || !datosPrenda.logoCompartido) return;
            datosPrenda.logoCompartido.files[clave] = file;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const url = ev.target.result;
                if (!datosPrenda.logoCompartido.items[clave]) {
                    datosPrenda.logoCompartido.items[clave] = { tecnicasCompartidas: clave.split('-').map(t => t.trim()).filter(Boolean), previewUrl: url };
                } else {
                    datosPrenda.logoCompartido.items[clave].previewUrl = url;
                }
                const preview = prendaDiv.querySelector(`.edit-logo-compartido-preview[data-logo-clave="${clave}"]`);
                if (preview) {
                    preview.innerHTML = `<img src="${url}" style="width: 100%; height: 100%; object-fit: cover;" />`;
                }
            };
            reader.readAsDataURL(file);
        });
    });

    // Precargar previews desde imagenes_files si imagenes_data_urls está vacío (caso 1 técnica / imágenes como File)
    if (datosPrenda && datosPrenda.tecnicas_imagenes) {
        Object.entries(datosPrenda.tecnicas_imagenes).forEach(([nombreTecnica, datosTecnica]) => {
            if (!datosTecnica) return;
            const tieneDataUrls = Array.isArray(datosTecnica.imagenes_data_urls) && datosTecnica.imagenes_data_urls.length > 0;
            const files = Array.isArray(datosTecnica.imagenes_files) ? datosTecnica.imagenes_files : [];
            if (tieneDataUrls || files.length === 0) return;

            // Inicializar array
            datosTecnica.imagenes_data_urls = [];

            files.forEach((file) => {
                if (!(file instanceof File)) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const dataUrl = e.target.result;
                    if (!datosTecnica.imagenes_data_urls.includes(dataUrl)) {
                        datosTecnica.imagenes_data_urls.push(dataUrl);
                    }
                    const preview = prendaDiv.querySelector(`.imagenes-preview[data-tecnica="${nombreTecnica}"]`);
                    if (preview) {
                        preview.innerHTML = datosTecnica.imagenes_data_urls.map((img, idx) => `
                            <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx}">
                                <img src="${img}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                            </div>
                        `).join('');

                        // Re-asignar listeners eliminar
                        preview.querySelectorAll('.btn-eliminar-imagen').forEach(btn => {
                            btn.addEventListener('click', (clickE) => {
                                clickE.preventDefault();
                                const idx = parseInt(btn.getAttribute('data-idx'));
                                datosTecnica.imagenes_data_urls.splice(idx, 1);
                                // Re-render simple
                                preview.innerHTML = datosTecnica.imagenes_data_urls.map((img2, idx2) => `
                                    <div style="position: relative;" class="imagen-item" data-tecnica="${nombreTecnica}" data-idx="${idx2}">
                                        <img src="${img2}" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                        <button type="button" class="btn-eliminar-imagen" data-tecnica="${nombreTecnica}" data-idx="${idx2}" style="position: absolute; top: 2px; right: 2px; background: rgba(255, 59, 48, 0.9); color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center;">✕</button>
                                    </div>
                                `).join('');
                            });
                        });
                    }
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // No hay ubicaciones compartidas; las textareas ya quedan vinculadas.
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
// 7.5 FUNCIONES PARA TABLA COLOR, TELA Y REFERENCIA (LOGO)
// =========================================================

function agregarFilaTelaLogo(boton) {
    const prendasItem = boton.closest('.prenda-item');
    const tbody = prendasItem.querySelector('.telas-tbody-logo');
    
    if (!tbody) return;
    
    const numFila = tbody.querySelectorAll('tr').length + 1;
    
    const fila = document.createElement('tr');
    fila.className = 'fila-tela-logo';
    fila.style.cssText = 'background: white;';
    fila.innerHTML = `
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-color-logo" placeholder="Ej: Azul, Rojo..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-tela-logo" placeholder="Ej: Algodón, Poliéster..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-referencia-logo" placeholder="Ej: REF-001..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <div class="dropzone-tela-logo" style="border: 1px dashed #ddd; padding: 6px; border-radius: 3px; background: #fafafa; cursor: pointer; font-size: 0.75rem; color: #999; transition: all 0.2s;" title="Haz clic o arrastra una imagen">
                📁 Imagen
            </div>
            <input type="file" class="input-file-tela-logo" accept="image/*" style="display: none;">
            <div class="preview-tela-logo" style="margin-top: 4px; display: none; position: relative; width: 60px; height: 60px; border-radius: 3px; overflow: hidden; border: 1px solid #ddd;">
                <img style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" class="btn-eliminar-tela-logo" onclick="this.closest('.fila-tela-logo').querySelector('.preview-tela-logo').style.display='none'; this.closest('.fila-tela-logo').querySelector('.input-file-tela-logo').value='';" style="position: absolute; top: -6px; right: -6px; background: rgba(0,0,0,0.6); color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 0.8rem; display: none;">×</button>
            </div>
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar">
                ✕
            </button>
        </td>
    `;
    
    tbody.appendChild(fila);
    
    // Funcionalidad de imagen
    const dropzone = fila.querySelector('.dropzone-tela-logo');
    const input = fila.querySelector('.input-file-tela-logo');
    const preview = fila.querySelector('.preview-tela-logo');
    const previewImg = preview.querySelector('img');
    const previewBtn = preview.querySelector('.btn-eliminar-tela-logo');
    
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
        if (e.dataTransfer.files[0] && e.dataTransfer.files[0].type.startsWith('image/')) {
            input.files = e.dataTransfer.files;
            manejarArchivoTelaLogo();
        }
    });
    
    // Cambio en input
    input.addEventListener('change', manejarArchivoTelaLogo);
    
    function manejarArchivoTelaLogo() {
        if (input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                previewBtn.style.display = 'block';
                dropzone.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
}

function eliminarFilaTelaLogo(boton) {
    boton.closest('.fila-tela-logo').remove();
}

// =========================================================
// 7.6 FUNCIÓN AGREGAR FILA TELA PARA MÚLTIPLES TÉCNICAS
// =========================================================

function agregarFilaTelaMultiTecnica(tbody) {
    if (!tbody) return;
    
    const numFila = tbody.querySelectorAll('tr').length + 1;
    
    const fila = document.createElement('tr');
    fila.className = 'fila-tela-multi';
    fila.style.cssText = 'border-bottom: 1px solid #eee;';
    fila.innerHTML = `
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-color-multi" placeholder="Ej: Azul, Rojo..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-tela-multi" placeholder="Ej: Algodón, Poliéster..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px;">
            <input type="text" class="input-referencia-multi" placeholder="Ej: REF-001..." style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-size: 0.85rem;">
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <div class="dropzone-tela-multi" style="border: 1px dashed #ddd; padding: 6px; border-radius: 3px; background: #fafafa; cursor: pointer; font-size: 0.75rem; color: #999; transition: all 0.2s;" title="Haz clic o arrastra una imagen">
                📁 Imagen
            </div>
            <input type="file" class="input-file-tela-multi" accept="image/*" style="display: none;">
            <div class="preview-tela-multi" style="margin-top: 4px; display: none; position: relative; width: 60px; height: 60px; border-radius: 3px; overflow: hidden; border: 1px solid #ddd;">
                <img style="width: 100%; height: 100%; object-fit: cover;">
                <button type="button" class="btn-eliminar-tela-multi" onclick="this.closest('.fila-tela-multi').querySelector('.preview-tela-multi').style.display='none'; this.closest('.fila-tela-multi').querySelector('.input-file-tela-multi').value='';" style="position: absolute; top: -6px; right: -6px; background: rgba(0,0,0,0.6); color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 0.8rem; display: none;">×</button>
            </div>
        </td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
            <button type="button" onclick="this.closest('tr').remove();" style="background: none; color: #999; border: 1px solid #ddd; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;" onmouseover="this.style.background='#f0f0f0'; this.style.color='#333'" onmouseout="this.style.background='none'; this.style.color='#999'" title="Eliminar">
                ✕
            </button>
        </td>
    `;
    
    tbody.appendChild(fila);
    
    // Funcionalidad de imagen
    const dropzone = fila.querySelector('.dropzone-tela-multi');
    const input = fila.querySelector('.input-file-tela-multi');
    const preview = fila.querySelector('.preview-tela-multi');
    const previewImg = preview.querySelector('img');
    const previewBtn = preview.querySelector('.btn-eliminar-tela-multi');
    
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
        if (e.dataTransfer.files[0] && e.dataTransfer.files[0].type.startsWith('image/')) {
            input.files = e.dataTransfer.files;
            manejarArchivoTelaMulti();
        }
    });
    
    // Cambio en input
    input.addEventListener('change', manejarArchivoTelaMulti);
    
    function manejarArchivoTelaMulti() {
        if (input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
                previewBtn.style.display = 'block';
                dropzone.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
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

    // Limpiar estado global para evitar que quede pegado el modo combinadas
    window.tecnicasCombinadas = null;
    window.modoTecnicasCombinadas = null;

    // Liberar lock de guardado
    window.__guardandoTecnica = false;
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
    guardarTecnicasEnBD,
    agregarFilaTelaLogo,
    eliminarFilaTelaLogo,
    agregarFilaTelaMultiTecnica
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

// =====================================================
// FUNCIONES DE ESPECIFICACIONES
// =====================================================

// FUNCIÓN PARA ABRIR MODAL ESPECIFICACIONES
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    const especificacionesGuardadas = document.getElementById('especificaciones').value;

    // Si hay especificaciones guardadas, cargarlas en los checkboxes y observaciones
    if (especificacionesGuardadas && especificacionesGuardadas !== '{}' && especificacionesGuardadas !== '[]' && especificacionesGuardadas !== '') {
        try {
            const datos = JSON.parse(especificacionesGuardadas);
            
            // Si tiene estructura de array (forma_pago, disponibilidad, etc)
            if (datos.forma_pago || datos.disponibilidad || datos.regimen) {
                // Procesar FORMA_PAGO
                if (datos.forma_pago && Array.isArray(datos.forma_pago)) {
                    datos.forma_pago.forEach((pago) => {
                        let valorNormalizado = pago.valor.toLowerCase();
                        if (valorNormalizado === 'crédito' || valorNormalizado === 'credito') {
                            valorNormalizado = 'credito';
                        }
                        
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        let checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (pago.observacion) {
                                let obsName;
                                if (valorNormalizado === 'contado') {
                                    obsName = 'tabla_orden[pago_contado_obs]';
                                } else if (valorNormalizado === 'credito') {
                                    obsName = 'tabla_orden[pago_credito_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = pago.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar DISPONIBILIDAD
                if (datos.disponibilidad && Array.isArray(datos.disponibilidad)) {
                    datos.disponibilidad.forEach((disp) => {
                        const valorNormalizado = disp.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (disp.observacion) {
                                const obsName = `tabla_orden[${valorNormalizado}_obs]`;
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = disp.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar RÉGIMEN
                if (datos.regimen && Array.isArray(datos.regimen)) {
                    datos.regimen.forEach((reg) => {
                        const valorNormalizado = reg.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            if (reg.observacion) {
                                let obsName;
                                if (valorNormalizado === 'común' || valorNormalizado === 'comun') {
                                    obsName = 'tabla_orden[regimen_comun_obs]';
                                } else if (valorNormalizado === 'simplificado') {
                                    obsName = 'tabla_orden[regimen_simp_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = reg.observacion;
                                }
                            }
                        }
                    });
                }
                
                // Procesar SE HA VENDIDO
                if (datos.se_ha_vendido && Array.isArray(datos.se_ha_vendido)) {
                    const tbodyVendido = document.querySelector('#tbody_vendido');
                    if (tbodyVendido) {
                        datos.se_ha_vendido.forEach((vendido) => {
                            const firstRow = tbodyVendido.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="vendido_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="vendido"]');
                                const obsInput = firstRow.querySelector('input[name*="vendido_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = vendido.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = vendido.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar ÚLTIMA VENTA
                if (datos.ultima_venta && Array.isArray(datos.ultima_venta)) {
                    const tbodyUltimaVenta = document.querySelector('#tbody_ultima_venta');
                    if (tbodyUltimaVenta) {
                        datos.ultima_venta.forEach((ultimaVenta) => {
                            const firstRow = tbodyUltimaVenta.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="ultima_venta_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="ultima_venta"]');
                                const obsInput = firstRow.querySelector('input[name*="ultima_venta_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = ultimaVenta.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = ultimaVenta.observacion || '';
                                }
                            }
                        });
                    }
                }
                
                // Procesar FLETE
                if (datos.flete && Array.isArray(datos.flete)) {
                    const tbodyFlete = document.querySelector('#tbody_flete');
                    if (tbodyFlete) {
                        datos.flete.forEach((flete) => {
                            const firstRow = tbodyFlete.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="flete_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="flete"]');
                                const obsInput = firstRow.querySelector('input[name*="flete_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = flete.valor;
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                                if (obsInput) {
                                    obsInput.value = flete.observacion || '';
                                }
                            }
                        });
                    }
                }
            }
        } catch (e) {
            console.log('Error al cargar especificaciones:', e);
        }
    } else {
        // Limpiar todos los checkboxes si no hay especificaciones guardadas
        document.querySelectorAll('[name^="tabla_orden"]').forEach((element) => {
            if (element.type === 'checkbox') {
                element.checked = false;
            } else if (element.type === 'text') {
                element.value = '';
            }
        });
    }
    
    if (modal) {
        modal.style.display = 'flex';
    }
}

// FUNCIÓN PARA CERRAR MODAL ESPECIFICACIONES
function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) {
        modal.style.display = 'none';
    }
}

// FUNCIÓN PARA GUARDAR ESPECIFICACIONES
function guardarEspecificacionesReflectivo() {
    // Estructura final en formato cotizaciones.especificaciones
    const especificaciones = {
        forma_pago: [],
        disponibilidad: [],
        regimen: [],
        se_ha_vendido: [],
        ultima_venta: [],
        flete: []
    };
    
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) {
        return;
    }
    
    // PROCESAR FORMA_PAGO
    const formaPagoCheckboxes = [
        { checkbox: 'contado', label: 'Contado', obsField: 'pago_contado_obs' },
        { checkbox: 'credito', label: 'Crédito', obsField: 'pago_credito_obs' }
    ];
    
    formaPagoCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.forma_pago.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR DISPONIBILIDAD
    const disponibilidadCheckboxes = [
        { checkbox: 'bodega', label: 'Bodega', obsField: 'bodega_obs' },
        { checkbox: 'cucuta', label: 'Cúcuta', obsField: 'cucuta_obs' },
        { checkbox: 'lafayette', label: 'Lafayette', obsField: 'lafayette_obs' },
        { checkbox: 'fabrica', label: 'Fábrica', obsField: 'fabrica_obs' }
    ];
    
    disponibilidadCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.disponibilidad.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR RÉGIMEN
    const regimenCheckboxes = [
        { checkbox: 'comun', label: 'Común', obsField: 'regimen_comun_obs' },
        { checkbox: 'simplificado', label: 'Simplificado', obsField: 'regimen_simp_obs' }
    ];
    
    regimenCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.regimen.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
        }
    });
    
    // PROCESAR SE HA VENDIDO
    const tbodySeHaVendido = modal.querySelector('#tbody_vendido');
    if (tbodySeHaVendido) {
        const rows = tbodySeHaVendido.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[vendido_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[vendido]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[vendido_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.se_ha_vendido.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR ÚLTIMA VENTA
    const tbodyUltimaVenta = modal.querySelector('#tbody_ultima_venta');
    if (tbodyUltimaVenta) {
        const rows = tbodyUltimaVenta.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[ultima_venta_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[ultima_venta]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[ultima_venta_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.ultima_venta.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // PROCESAR FLETE
    const tbodyFlete = modal.querySelector('#tbody_flete');
    if (tbodyFlete) {
        const rows = tbodyFlete.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name="tabla_orden[flete_item]"]');
            const checkbox = row.querySelector('input[type="checkbox"][name="tabla_orden[flete]"]');
            const obsInput = row.querySelector('input[name="tabla_orden[flete_obs]"]');
            if (checkbox && checkbox.checked) {
                const valorTexto = valorInput?.value.trim() || 'Sí';
                especificaciones.flete.push({
                    valor: valorTexto,
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
            }
        });
    }
    
    // Convertir a JSON string y guardar en campo oculto
    const especificacionesJSON = JSON.stringify(especificaciones);
    document.getElementById('especificaciones').value = especificacionesJSON;

    cerrarModalEspecificaciones();
}

// FUNCIÓN PARA AGREGAR FILA DE ESPECIFICACIÓN
function agregarFilaEspecificacion(seccion) {
    // Función auxiliar, puede ampliarse según necesidad
}

