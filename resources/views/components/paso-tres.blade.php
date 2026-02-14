<!-- PASO 3: LOGO -->
<style>
    /* Estilos para Técnicas */
    .tecnicas-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 1rem;
    }

    .tecnicas-box h3 {
        margin-bottom: 20px;
        color: #1e40af;
        font-weight: 600;
    }

    .tecnicas-box label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }

    .tecnicas-box button {
        background: #1e40af;
        color: white;
        border: none;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .tecnicas-box button:hover {
        background: #182e7d;
    }

    #tecnicas_agregadas {
        margin-top: 15px;
    }

    #sin_tecnicas {
        padding: 20px;
        text-align: center;
        background: #f5f5f5;
        border-radius: 8px;
        color: #999;
        display: block;
    }

    /* Estilos para Observaciones */
    .obs-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 1rem;
    }

    .obs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .obs-header label {
        font-weight: bold;
        font-size: 1.1rem;
        margin: 0;
        color: #1e40af;
    }

    .btn-add {
        background: #3498db;
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        cursor: pointer;
        font-size: 1.5rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: background 0.2s ease;
    }

    .btn-add:hover {
        background: #2980b9;
    }

    .obs-lista {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .obs-lista > div {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 10px;
        background: white;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .obs-lista input[type="text"] {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .obs-lista button {
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: bold;
        border: none;
        flex-shrink: 0;
    }

    .obs-toggle-btn {
        background: #3498db;
        color: white;
    }

    .obs-toggle-btn:hover {
        background: #2980b9;
    }

    .obs-remove-btn {
        background: #f44336;
        color: white;
    }

    .obs-remove-btn:hover {
        background: #da190b;
    }
</style>

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
        <!-- TÉCNICAS - Igual al formulario de logo individual -->
        <div class="tecnicas-box">
            <h3 style="margin-bottom: 20px; color: #1e40af; font-weight: 600;">Técnicas</h3>
            
            <!-- Selector de Técnicas (Checkboxes) -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Selecciona las técnicas a aplicar:</label>
                <div id="tecnicas-checkboxes-paso3" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 15px;">
                    <!-- Se llenan dinámicamente con renderizarCheckboxesTecnicas() -->
                </div>
                <button type="button" id="btnAgregarPrendas" onclick="abrirModalAgregarTecnicaPaso3()" style="background: #1e40af; color: white; border: none; cursor: pointer; padding: 10px 20px; border-radius: 4px; font-weight: 600; transition: background 0.2s ease;" title="Agregar prendas para las técnicas seleccionadas">
                    <i class="fas fa-plus"></i> Agregar Prendas
                </button>
            </div>
            
            <!-- Lista de Técnicas Agregadas por Prenda -->
            <div id="tecnicas_agregadas_paso3" style="margin-top: 15px;"></div>
            
            <!-- Sin Técnicas -->
            <div id="sin_tecnicas_paso3" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999; display: block;">
                <p>Selecciona técnicas y agrega prendas</p>
            </div>
        </div>
        
        <!-- Campo oculto para enviar técnicas al backend -->
        <input type="hidden" id="paso3_tecnicas_datos" name="paso3_tecnicas_datos" value="[]">
    </div>

    <div class="form-section">
        <!-- OBSERVACIONES GENERALES - Igual al formulario de logo individual -->
        <div class="obs-box">
            <div class="obs-header">
                <label for="observaciones_generales">Observaciones Generales</label>
                <button type="button" class="btn-add" onclick="agregarObservacion()">+</button>
            </div>
            
            <div class="obs-lista" id="observaciones_lista"></div>
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

<div id="modalEditarPrendaPaso3" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 18px; max-width: 980px; width: 96%; max-height: 92vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;">
            <div>
                <h2 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b;">Editar Prenda</h2>
                <p style="margin: 6px 0 0 0; color: #64748b; font-size: 0.85rem;">Actualiza ubicaciones, imágenes, variaciones y observaciones</p>
            </div>
            <button type="button" onclick="if (typeof cerrarModalEditarPrendaPaso3 === 'function') cerrarModalEditarPrendaPaso3()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; padding: 0; line-height: 1; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div id="contenidoModalEditarPrendaPaso3"></div>

        <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 12px; margin-top: 12px;">
            <button type="button" onclick="if (typeof cerrarModalEditarPrendaPaso3 === 'function') cerrarModalEditarPrendaPaso3()" style="background: white; color: #333; border: 1px solid #ddd; padding: 10px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">
                Cancelar
            </button>
            <button type="button" onclick="if (typeof guardarEdicionPrendaPaso3DesdeModal === 'function') guardarEdicionPrendaPaso3DesdeModal()" style="background: #111827; color: white; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; font-weight: 700; font-size: 0.9rem;">
                Guardar
            </button>
        </div>
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
        seccionesSeleccionadas.push({
            ubicacion: ubicacion,
            opciones: nuevasUbicaciones,
            observaciones: obs
        });
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


        // Pasar array vacío para tallas (ya no se manejan por ubicación)
        onSave(ubicacionesSeleccionadasModal, [], obsTextarea.value);
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
        <input type="text" name="observaciones_generales[]" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
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
    //  SOBRESCRIBIR LA FUNCIÓN agregarSeccion DE especificaciones.js
    // Paso-tres debe usar SU PROPIA FUNCIÓN, no la de especificaciones.js

    // Usar setTimeout para asegurar que especificaciones.js ya se cargó y puede ser sobrescrita
    setTimeout(() => {
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
        console.log(' PASO-TRES - Ahora usa abrirModalUbicaciones (modal de bordado)');
    }, 100);
});
</script>


