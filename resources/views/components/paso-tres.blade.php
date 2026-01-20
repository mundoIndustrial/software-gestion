<!-- PASO 3: LOGO -->
<div class="form-step" data-step="3">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 3: LOGO / BORDADO</h2>
            <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO</p>
        </div>
        
        <!-- Selector de tipo de venta en la esquina derecha -->
        <div style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
            <label for="tipo_venta_paso3" style="font-weight: 700; font-size: 0.85rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 6px; margin: 0;">
                <i class="fas fa-tag"></i> Tipo
            </label>
            <select id="tipo_venta_paso3" name="tipo_venta_paso3" style="padding: 0.5rem 0.6rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 80px;">
                <option value="">Selecciona</option>
                <option value="M">M</option>
                <option value="D">D</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <!-- DESCRIPCIÓN DEL LOGO/BORDADO -->
        <div class="form-group-large">
            <label for="descripcion_logo"><i class="fas fa-pen"></i> DESCRIPCIÓN DEL LOGO/BORDADO</label>
            <textarea id="descripcion_logo" name="descripcion_logo" class="input-large" rows="3" placeholder="Describe el logo, bordado o estampado que deseas..." style="width: 100%; padding: 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem; font-family: inherit;"></textarea>
            <small class="help-text">Incluye detalles sobre colores, tamaño, posición, etc.</small>
        </div>

        <!-- IMÁGENES -->
        <div class="form-group-large">
            <label for="imagenes_bordado"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)</label>
            <div id="drop_zone_imagenes" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; margin-bottom: 10px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
            </div>
            <div id="galeria_imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
        </div>

        <!-- TÉCNICAS -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Técnicas disponibles</label>
                <button type="button" onclick="agregarTecnica()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="if(this.value) { agregarTecnica(); }">
                <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                <option value="BORDADO">BORDADO</option>
                <option value="DTF">DTF</option>
                <option value="ESTAMPADO">ESTAMPADO</option>
                <option value="SUBLIMADO">SUBLIMADO</option>
            </select>
            
            <div id="tecnicas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 30px;"></div>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observaciones</label>
            <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
            
            <!-- Campo oculto para enviar técnicas al backend -->
            <input type="hidden" id="paso3_tecnicas_datos" name="paso3_tecnicas_datos" value="[]">
        </div>

        <!-- UBICACIÓN / SECCIONES (Exactamente igual al logo) -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Ubicación</label>
                <button type="button" class="btn-add" onclick="agregarSeccion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Escribe la prenda para agregarle las ubicaciones:</label>
            <input type="text" id="seccion_prenda" list="secciones_list" class="input-large" placeholder="Escribe o selecciona una sección" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" oninput="this.value = this.value.toUpperCase()">
            <datalist id="secciones_list">
                <option value="CAMISA">
                <option value="PANTALON">
                <option value="JEAN">
                <option value="SUDADERA">
                <option value="GORRA">
                <option value="POLO">
                <option value="CHAQUETA">
            </datalist>
            <div id="errorSeccionPrenda" style="display: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.5rem; background: #fee2e2; border-radius: 4px; margin-bottom: 10px;">
                ⚠️ Debes seleccionar una ubicación
            </div>
            
            <div id="secciones_agregadas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;"></div>
            
            <!-- Campo oculto para enviar datos al backend -->
            <input type="hidden" id="paso3_secciones_datos" name="paso3_secciones_datos" value="[]">
        </div>

        <!-- OBSERVACIONES GENERALES -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                <button type="button" onclick="agregarObservacion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
            </div>
            
            <div id="observaciones_lista" style="display: flex; flex-direction: column; gap: 10px;"></div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(2)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="if(typeof irAlPaso === 'function') irAlPaso(4)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<!-- PASO 4: REFLECTIVO (COMBINADA) -->
<div class="form-step" data-step="4">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 4: REFLECTIVO</h2>
            <p style="font-size: 0.8rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DEL REFLECTIVO POR PRENDA</p>
        </div>
    </div>

    <div class="form-section">
        <!-- CONTENEDOR DE PRENDAS REFLECTIVO -->
        <div id="prendas_reflectivo_container" style="margin-bottom: 20px;"></div>
        
        <!-- BOTÓN AGREGAR PRENDA REFLECTIVO -->
        <button type="button" id="btnAgregarPrendaReflectivo" onclick="agregarPrendaReflectivoPaso4(); console.log(' Botón Agregar Prenda Reflectivo clickeado');" style="width: 100%; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; cursor: pointer; padding: 12px 20px; border-radius: 4px; font-weight: 600; transition: background 0.2s ease; margin-bottom: 20px;">
            <i class="fas fa-plus"></i> Agregar Prenda Reflectivo
        </button>
        
        <!-- SIN PRENDAS -->
        <div id="sin_prendas_reflectivo" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999; display: block;">
            <p>Agrega prendas con reflectivo</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log(' Paso 4 Reflectivo - Listo para agregar prendas');
            // NO agregar automáticamente - esperará a que el usuario navegue al paso 4
            console.log('⏳ Esperando a que el usuario navegue al paso 4...');
        });
    </script>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="if(typeof irAlPaso === 'function') irAlPaso(3)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="if(typeof irAlPaso === 'function') irAlPaso(5)">
            REVISAR <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
// Variables para gestionar secciones en paso-tres
let seccionesSeleccionadas = [];
let opcionesPorUbicacion = {};
let todasLasUbicaciones = [];
let tecnicasSeleccionadas = [];

// Opciones por ubicación (igual que en bordado)
const opcionesPrendas = {
    'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
    'PANTALON': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO', 'FRENTE'],
    'JEAN': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO', 'FRENTE'],
    'SUDADERA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA'],
    'GORRA': ['FRENTE', 'LATERAL', 'TRASERA'],
    'POLO': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA'],
    'CHAQUETA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA']
};

// Lista unificada de todas las ubicaciones posibles
todasLasUbicaciones = [...new Set([
    ...opcionesPrendas.CAMISA,
    ...opcionesPrendas.PANTALON,
    ...opcionesPrendas.JEAN,
    ...opcionesPrendas.SUDADERA,
    ...opcionesPrendas.GORRA,
    ...opcionesPrendas.POLO,
    ...opcionesPrendas.CHAQUETA
])];

// console.log(' Ubicaciones iniciales cargadas:', todasLasUbicaciones); // DEBUG: Comentado para evitar logs innecesarios

function agregarTecnica() {
    const selector = document.getElementById('selector_tecnicas');
    const tecnica = selector.value;
    
    if (!tecnica) {
        Swal.fire({
            icon: 'warning',
            title: 'Técnica Requerida',
            text: 'Selecciona una técnica de la lista',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    if (tecnicasSeleccionadas.includes(tecnica)) {
        Swal.fire({
            icon: 'info',
            title: 'Ya Agregada',
            text: `La técnica "${tecnica}" ya está en la lista`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    tecnicasSeleccionadas.push(tecnica);
    selector.value = '';
    renderizarTecnicas();
}

function renderizarTecnicas() {
    const container = document.getElementById('tecnicas_seleccionadas');
    container.innerHTML = '';
    
    tecnicasSeleccionadas.forEach((tecnica, index) => {
        const badge = document.createElement('span');
        badge.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #3498db; color: white; padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.85rem; margin-right: 0.5rem; margin-bottom: 0.5rem;';
        badge.innerHTML = `
            ${tecnica}
            <span onclick="eliminarTecnica(${index})" style="cursor: pointer; font-weight: bold; font-size: 1rem;">×</span>
        `;
        container.appendChild(badge);
    });
    
    // Actualizar campo oculto
    const campo = document.getElementById('paso3_tecnicas_datos');
    if (campo) {
        campo.value = JSON.stringify(tecnicasSeleccionadas);
    }
}

function eliminarTecnica(index) {
    tecnicasSeleccionadas.splice(index, 1);
    renderizarTecnicas();
}

function agregarSeccion() {
    const selector = document.getElementById('seccion_prenda');
    const ubicacion = selector.value.trim().toUpperCase();
    const errorDiv = document.getElementById('errorSeccionPrenda');

    if (!ubicacion) {
        selector.style.border = '2px solid #ef4444';
        selector.style.background = '#fee2e2';
        selector.classList.add('shake');
        errorDiv.style.display = 'block';

        setTimeout(() => {
            selector.style.border = '';
            selector.style.background = '';
            selector.classList.remove('shake');
        }, 600);

        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 3000);

        return;
    }

    errorDiv.style.display = 'none';

    abrirModalUbicaciones(ubicacion, [], (nuevasUbicaciones, obs) => {
        console.log('🎯 CALLBACK - Ubicaciones guardadas desde modal:', {nuevasUbicaciones, obs});
        seccionesSeleccionadas.push({
            ubicacion: ubicacion,
            opciones: nuevasUbicaciones,
            observaciones: obs
        });
        console.log(' Sección agregada a seccionesSeleccionadas:', seccionesSeleccionadas);
        opcionesPorUbicacion[ubicacion] = nuevasUbicaciones;
        renderizarSecciones();
        console.log(' renderizarSecciones() ejecutado, campo oculto actualizado');
        cerrarModalUbicacion('modalUbicaciones');
        selector.value = '';
    });
}

window.editarSeccion = function(index) {
    const seccion = seccionesSeleccionadas[index];
    if (!seccion) return;

    abrirModalUbicaciones(seccion.ubicacion, seccion.opciones, (nuevasUbicaciones, obs) => {
        seccionesSeleccionadas[index] = {
            ...seccion,
            opciones: nuevasUbicaciones,
            observaciones: obs
        };
        opcionesPorUbicacion[seccion.ubicacion] = nuevasUbicaciones;
        renderizarSecciones();
        cerrarModalUbicacion('modalUbicaciones');
    }, seccion.observaciones);
}

window.eliminarSeccion = function(index) {
    seccionesSeleccionadas.splice(index, 1);
    renderizarSecciones();
    actualizarCampoHidden();
}

function cerrarModalUbicacion(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.remove();
}

function abrirModalUbicaciones(prenda, ubicacionesIniciales, onSave, observacionesIniciales = '') {
    console.log(' PASO-TRES - abrirModalUbicaciones iniciado');
    console.log('📌 prenda:', prenda);
    console.log('📌 ubicacionesIniciales:', ubicacionesIniciales);
    console.log('📌 observacionesIniciales:', observacionesIniciales);
    console.log('📌 todasLasUbicaciones:', todasLasUbicaciones);
    
    let ubicacionesSeleccionadasModal = [...ubicacionesIniciales];

    const modalId = 'modalUbicaciones';

    const modalHtml = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="${modalId}">
            <div style="background: white; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column; max-height: 90vh;">
                <div style="padding: 1.5rem 1.5rem 1rem 1.5rem; border-bottom: 1px solid #eee;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${prenda}</h3>
                        <button type="button" onclick="cerrarModalUbicacion('${modalId}')" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                </div>
                
                <div style="overflow-y: auto; padding: 1.5rem;">
                    <!-- Ubicaciones -->
                    <div id="ubicaciones-section-container" style="margin-bottom: 1.5rem;">
                         <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label style="font-weight: 600; color: #333;">Ubicaciones:</label>
                            <button type="button" id="btn-ubicaciones-na" style="background: #7f8c8d; color: white; border: none; border-radius: 4px; padding: 0.2rem 0.5rem; font-size: 0.7rem; cursor: pointer;">No Aplica</button>
                        </div>
                        <div id="ubicaciones-content">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                                <input type="text" id="ubicacion-input" list="ubicaciones-datalist" placeholder="Busca o escribe una ubicación..." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" oninput="this.value = this.value.toUpperCase()">
                                <datalist id="ubicaciones-datalist"></datalist>
                                <button type="button" id="btn-add-ubicacion" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">+</button>
                            </div>
                            <div id="ubicaciones-seleccionadas-container" style="display: flex; flex-direction: column; gap: 6px; border: 1px solid #eee; padding: 0.5rem; border-radius: 4px; min-height: 40px;"></div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Observaciones:</label>
                        <textarea id="obs-ubicacion-modal" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; min-height: 80px;">${observacionesIniciales}</textarea>
                    </div>
                </div>

                <div style="margin-top: auto; padding: 1rem 1.5rem 1.5rem 1.5rem; border-top: 1px solid #eee; display: flex; justify-content: flex-end;">
                    <button type="button" id="btn-save-ubicaciones" style="background: #3498db; color: white; border: none; border-radius: 6px; padding: 0.6rem 1.2rem; cursor: pointer; font-weight: 600;">Guardar</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // --- Lógica del nuevo modal ---
    const datalist = document.getElementById('ubicaciones-datalist');
    todasLasUbicaciones.forEach(op => {
        const option = document.createElement('option');
        option.value = op;
        datalist.appendChild(option);
    });

    // Ubicaciones
    const ubicacionesContent = document.getElementById('ubicaciones-content');
    const btnUbicacionesNA = document.getElementById('btn-ubicaciones-na');
    const ubicacionInput = document.getElementById('ubicacion-input');
    const addUbicacionButton = document.getElementById('btn-add-ubicacion');

    btnUbicacionesNA.addEventListener('click', () => {
        const isApplied = ubicacionesContent.style.display !== 'none';
        if (isApplied) {
            ubicacionesContent.style.display = 'none';
            btnUbicacionesNA.textContent = 'Aplica';
            btnUbicacionesNA.style.background = '#27ae60';
            ubicacionesSeleccionadasModal = []; // Limpiar datos
            renderizarUbicacionesSeleccionadas();
        } else {
            ubicacionesContent.style.display = 'block';
            btnUbicacionesNA.textContent = 'No Aplica';
            btnUbicacionesNA.style.background = '#7f8c8d';
        }
    });
    
    const renderizarUbicacionesSeleccionadas = () => {
        const container = document.getElementById('ubicaciones-seleccionadas-container');
        container.innerHTML = '';
        ubicacionesSeleccionadasModal.forEach((ubicacion, index) => {
            const item = document.createElement('div');
            item.style.cssText = 'background: #e9f5ff; padding: 0.4rem 0.6rem; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;';
            
            const inputField = document.createElement('input');
            inputField.type = 'text';
            inputField.value = ubicacion;
            inputField.style.cssText = 'flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 0.25rem; font-size: 0.85rem; background: white;';
            inputField.addEventListener('input', (e) => {
                if (index >= 0 && index < ubicacionesSeleccionadasModal.length) {
                    ubicacionesSeleccionadasModal[index] = e.target.value.trim().toUpperCase();
                }
            });

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.innerHTML = '×';
            deleteButton.style.cssText = 'background: #e74c3c; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-weight: bold; display: flex; align-items: center; justify-content: center; flex-shrink: 0;';
            deleteButton.addEventListener('click', () => {
                ubicacionesSeleccionadasModal.splice(index, 1);
                renderizarUbicacionesSeleccionadas();
            });

            item.appendChild(inputField);
            item.appendChild(deleteButton);
            container.appendChild(item);
        });
    };

    const agregarUbicacion = () => {
        const nuevaUbicacion = ubicacionInput.value.trim().toUpperCase();
        if (nuevaUbicacion && !ubicacionesSeleccionadasModal.includes(nuevaUbicacion)) {
            ubicacionesSeleccionadasModal.push(nuevaUbicacion);
            if (!todasLasUbicaciones.includes(nuevaUbicacion)) {
                todasLasUbicaciones.push(nuevaUbicacion);
                const option = document.createElement('option');
                option.value = nuevaUbicacion;
                datalist.appendChild(option);
            }
            ubicacionInput.value = '';
            renderizarUbicacionesSeleccionadas();
        }
        ubicacionInput.focus();
    };

    addUbicacionButton.addEventListener('click', agregarUbicacion);
    ubicacionInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            agregarUbicacion();
        }
    });

    // Guardar
    const saveButton = document.getElementById('btn-save-ubicaciones');
    const obsTextarea = document.getElementById('obs-ubicacion-modal');

    saveButton.addEventListener('click', () => {
        console.log('🔵 BOTÓN GUARDAR PRESIONADO - Modal');
        console.log('📍 ubicacionesSeleccionadasModal:', ubicacionesSeleccionadasModal);
        console.log('📍 observaciones:', obsTextarea.value);
        // Pasar array vacío para tallas (ya no se manejan por ubicación)
        onSave(ubicacionesSeleccionadasModal, [], obsTextarea.value);
        console.log(' onSave callback ejecutado');
    });

    renderizarUbicacionesSeleccionadas();
}

function renderizarSecciones() {
    const container = document.getElementById('secciones_agregadas');
    container.innerHTML = '';

    seccionesSeleccionadas.forEach((seccion, index) => {
        const item = document.createElement('div');
        item.style.cssText = `
            background: #f0f7ff;
            border: 1px solid #cce7ff;
            border-radius: 8px;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        `;

        const header = document.createElement('div');
        header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; font-weight: 600; color: #1e40af;';
        header.innerHTML = `
            <span>${seccion.ubicacion}</span>
            <div>
                <button type="button" onclick="editarSeccion(${index})" style="background: none; border: none; cursor: pointer; color: #3498db; font-size: 0.9rem; margin-right: 0.5rem; vertical-align: middle;"><i class="fas fa-pencil-alt"></i></button>
                <button type="button" onclick="eliminarSeccion(${index})" style="background: none; border: none; cursor: pointer; color: #e74c3c; font-size: 0.9rem; vertical-align: middle;"><i class="fas fa-trash-alt"></i></button>
            </div>
        `;

        const tallasHtml = '';

        const content = document.createElement('div');
        content.style.fontSize = '0.8rem';
        content.innerHTML = `
            <strong>Ubicaciones:</strong> ${seccion.opciones.join(', ')}<br>
            ${seccion.observaciones ? `<strong>Obs:</strong> ${seccion.observaciones}` : ''}
        `;

        item.appendChild(header);
        item.appendChild(content);
        container.appendChild(item);
    });
    
    actualizarCampoHidden();
}

function actualizarCampoHidden() {
    const campo = document.getElementById('paso3_secciones_datos');
    if (campo) {
        campo.value = JSON.stringify(seccionesSeleccionadas);
    }
}

function agregarObservacion() {
    const contenedor = document.getElementById('observaciones_lista');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_generales[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" name="observaciones_valor[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(fila);
    
    const toggleBtn = fila.querySelector('.obs-toggle-btn');
    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
    const textMode = fila.querySelector('.obs-text-mode');
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'block';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'block';
            toggleBtn.style.background = '#2ecc71';
        }
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Los selectores de tipo_venta en PASO 2 y PASO 3 son independientes
    // No se sincronizan automáticamente para permitir valores diferentes
    console.log(' Selectores tipo_venta configurados como independientes');
    
    // 🔥 SOBRESCRIBIR LA FUNCIÓN agregarSeccion DE especificaciones.js
    // Paso-tres debe usar SU PROPIA FUNCIÓN, no la de especificaciones.js
    console.log(' PASO-TRES - Inicializando funciones del paso-tres');
    console.log('📍 PASO-TRES - agregarSeccion será redefinida para usar abrirModalUbicaciones de paso-tres');
    
    // Usar setTimeout para asegurar que especificaciones.js ya se cargó y puede ser sobrescrita
    setTimeout(() => {
        console.log('🔄 PASO-TRES - Redefining agregarSeccion to override especificaciones.js version');
        
        window.agregarSeccion = function() {
            const selector = document.getElementById('seccion_prenda');
            const ubicacion = selector.value.trim().toUpperCase();
            const errorDiv = document.getElementById('errorSeccionPrenda');

            if (!ubicacion) {
                selector.style.border = '2px solid #ef4444';
                selector.style.background = '#fee2e2';
                selector.classList.add('shake');
                errorDiv.style.display = 'block';

                setTimeout(() => {
                    selector.style.border = '';
                    selector.style.background = '';
                    selector.classList.remove('shake');
                }, 600);

                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 3000);

                return;
            }

            errorDiv.style.display = 'none';

            abrirModalUbicaciones(ubicacion, [], (nuevasUbicaciones, obs) => {
                seccionesSeleccionadas.push({
                    ubicacion: ubicacion,
                    opciones: nuevasUbicaciones,
                    observaciones: obs
                });
                opcionesPorUbicacion[ubicacion] = nuevasUbicaciones;
                renderizarSecciones();
                cerrarModalUbicacion('modalUbicaciones');
                selector.value = '';
            });
        };
        
        console.log(' PASO-TRES - agregarSeccion redefinida correctamente');
        console.log('🎯 PASO-TRES - Ahora usa abrirModalUbicaciones (modal de bordado)');
    }, 100);
});
</script>

