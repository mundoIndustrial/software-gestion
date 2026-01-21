/**
 * reflectivo.js - Módulo para gestionar reflectivo en cotizaciones
 * 
 * Responsabilidades:
 * - Gestionar imágenes del reflectivo
 * - Gestionar ubicación del reflectivo
 * - Gestionar observaciones generales
 */

// ============================================================================
// IMÁGENES DEL REFLECTIVO
// ============================================================================

let imagenesReflectivo = [];

/**
 * Inicializar drag & drop para imágenes del reflectivo
 */
function inicializarDragDropReflectivo() {
    const dropZone = document.getElementById('drop_zone_reflectivo');
    const fileInput = document.getElementById('imagenes_reflectivo');

    if (!dropZone || !fileInput) return;

    // Click en el área de drop
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag over
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.background = '#e8f4ff';
        dropZone.style.borderColor = '#0066cc';
    });

    // Drag leave
    dropZone.addEventListener('dragleave', () => {
        dropZone.style.background = '#f0f7ff';
        dropZone.style.borderColor = '#3498db';
    });

    // Drop
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.background = '#f0f7ff';
        dropZone.style.borderColor = '#3498db';
        
        const files = e.dataTransfer.files;
        manejarArchivosReflectivo(files);
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        manejarArchivosReflectivo(e.target.files);
    });
}

/**
 * Manejar archivos del reflectivo
 */
function manejarArchivosReflectivo(files) {
    console.log('📸 Procesando archivos reflectivo:', files.length);

    for (let file of files) {
        if (imagenesReflectivo.length >= 5) {
            alert('Máximo 5 imágenes permitidas');
            break;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const imagen = {
                nombre: file.name,
                data: e.target.result,
                tipo: file.type,
                archivo: file
            };

            imagenesReflectivo.push(imagen);
            renderizarGaleriaReflectivo();
            console.log(' Imagen agregada:', file.name);
        };

        reader.readAsDataURL(file);
    }
}

/**
 * Renderizar galería de imágenes del reflectivo
 */
function renderizarGaleriaReflectivo() {
    const galeria = document.getElementById('galeria_reflectivo');
    if (!galeria) return;

    galeria.innerHTML = '';

    imagenesReflectivo.forEach((imagen, index) => {
        const div = document.createElement('div');
        div.style.cssText = `
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 6px;
            overflow: hidden;
            background: #f0f0f0;
        `;

        const img = document.createElement('img');
        img.src = imagen.data;
        img.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: cover;
        `;

        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.innerHTML = '✕';
        btnEliminar.style.cssText = `
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        `;

        btnEliminar.onclick = (e) => {
            e.preventDefault();
            imagenesReflectivo.splice(index, 1);
            renderizarGaleriaReflectivo();
            console.log('🗑️ Imagen eliminada');
        };

        div.appendChild(img);
        div.appendChild(btnEliminar);
        galeria.appendChild(div);
    });

    console.log(' Total imágenes reflectivo:', imagenesReflectivo.length);
}

// ============================================================================
// UBICACIONES DEL REFLECTIVO
// ============================================================================

let ubicacionesReflectivo = [];

/**
 * Agregar ubicación del reflectivo - Abre modal
 */
function agregarUbicacionReflectivo() {
    console.log('➕ Agregando ubicación reflectivo');

    const seccionSelect = document.getElementById('seccion_reflectivo');
    const seccion = seccionSelect.value;

    if (!seccion) {
        alert('Por favor selecciona una sección');
        return;
    }

    // Crear modal simple para escribir descripción
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="modalUbicacionReflectivo">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${seccion}</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="cerrarModalUbicacionReflectivo()" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
                        <button type="button" onclick="guardarUbicacionReflectivo('${seccion}')" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">+</button>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #333;">Descripción</label>
                    <textarea id="descUbicacionReflectivo" placeholder="Ej: Lado izquierdo, Centro, Ambos lados..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; resize: vertical; min-height: 100px; box-sizing: border-box;"></textarea>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    
    // Enfocar en el input de descripción
    setTimeout(() => {
        document.getElementById('descUbicacionReflectivo').focus();
    }, 10);
}

/**
 * Cerrar modal de ubicación del reflectivo
 */
function cerrarModalUbicacionReflectivo() {
    const modal = document.getElementById('modalUbicacionReflectivo');
    if (modal) modal.remove();
}

/**
 * Guardar ubicación del reflectivo
 */
function guardarUbicacionReflectivo(ubicacion) {
    const desc = document.getElementById('descUbicacionReflectivo').value.trim();
    
    if (!desc) {
        alert('Por favor escribe una descripción');
        return;
    }
    
    ubicacionesReflectivo.push({
        ubicacion: ubicacion,
        descripcion: desc
    });
    
    cerrarModalUbicacionReflectivo();
    document.getElementById('seccion_reflectivo').value = '';
    renderizarUbicacionesReflectivo();
}

/**
 * Renderizar ubicaciones del reflectivo
 */
function renderizarUbicacionesReflectivo() {
    const contenedor = document.getElementById('ubicaciones_reflectivo_agregadas');
    if (!contenedor) return;

    contenedor.innerHTML = '';

    ubicacionesReflectivo.forEach((item, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;';
        
        const ubicacionText = item.ubicacion || '';
        const descText = item.descripcion || '';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ubicacionText}</h4>
                    <p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Descripción:</strong> ${descText}</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="eliminarUbicacionReflectivo(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });

    console.log(' Total ubicaciones reflectivo:', ubicacionesReflectivo.length);
}

/**
 * Eliminar ubicación del reflectivo
 */
function eliminarUbicacionReflectivo(index) {
    ubicacionesReflectivo.splice(index, 1);
    renderizarUbicacionesReflectivo();
}

// ============================================================================
// OBSERVACIONES GENERALES DEL REFLECTIVO
// ============================================================================

let observacionesReflectivo = [];

/**
 * Agregar observación general del reflectivo
 */
function agregarObservacionReflectivo() {
    console.log('➕ Agregando observación reflectivo');

    const contenedor = document.getElementById('observaciones_reflectivo_lista');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_reflectivo[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check_reflectivo[]" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" name="observaciones_valor_reflectivo[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
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
            checkboxMode.style.display = 'flex';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'flex';
            toggleBtn.style.background = '#ff9800';
        }
    });
}


// ============================================================================
// RECOPILACIÓN DE DATOS DEL REFLECTIVO
// ============================================================================

/**
 * Recopilar datos del reflectivo
 */
function recopilarDatosReflectivo() {
    console.log(' Recopilando datos del reflectivo...');

    const descripcion = document.getElementById('descripcion_reflectivo')?.value || '';

    const datos = {
        descripcion,
        ubicaciones: ubicacionesReflectivo,
        imagenes: imagenesReflectivo,
        observaciones_generales: observacionesReflectivo
    };

    console.log(' Datos reflectivo recopilados:', datos);
    return datos;
}

/**
 * Validar datos del reflectivo
 */
function validarReflectivo() {
    const descripcion = document.getElementById('descripcion_reflectivo')?.value || '';

    if (!descripcion.trim()) {
        alert('Por favor, ingresa una descripción del reflectivo');
        return false;
    }

    console.log(' Reflectivo validado correctamente');
    return true;
}

/**
 * Limpiar datos del reflectivo
 */
function limpiarReflectivo() {
    console.log('🧹 Limpiando datos del reflectivo');

    imagenesReflectivo = [];
    observacionesReflectivo = [];

    document.getElementById('descripcion_reflectivo').value = '';
    document.getElementById('ubicacion_reflectivo').value = '';
    document.getElementById('galeria_reflectivo').innerHTML = '';
    document.getElementById('observaciones_reflectivo_lista').innerHTML = '';
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

/**
 * Inicializar módulo de reflectivo
 */
function inicializarReflectivo() {
    console.log(' Inicializando módulo de reflectivo');
    inicializarDragDropReflectivo();
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarReflectivo);
} else {
    inicializarReflectivo();
}
