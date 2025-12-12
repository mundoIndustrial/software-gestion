@extends('layouts.asesores')

@push('styles')
<style>
    /* Desactivar navbar */
    header {
        display: none !important;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 0.5rem;
    }

    .form-container {
        max-width: 1400px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 1.25rem 1.5rem;
    }

    .form-header {
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-header h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .form-header p {
        color: #64748b;
        font-size: 0.8rem;
    }

    .form-section {
        margin-bottom: 1.25rem;
    }

    .form-section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.3rem;
        font-size: 0.8rem;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 0.75rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        flex: 1;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .input-large {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.85rem;
        font-family: inherit;
    }

    .input-large:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    /* Ubicación */
    .ubicacion-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 1rem;
    }

    .ubicacion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .ubicacion-header label {
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0;
    }

    .btn-add {
        background: #3498db;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 1.2rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .btn-add:hover {
        background: #2980b9;
    }

    .secciones-agregadas {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .seccion-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
    }

    .seccion-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Observaciones Generales */
    .obs-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 1rem;
    }

    .obs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .obs-header label {
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0;
    }

    .obs-lista {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .obs-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .obs-item input {
        flex: 1;
        border: none;
        padding: 0;
        font-size: 0.85rem;
    }

    .obs-item input:focus {
        outline: none;
    }

    .obs-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Imágenes */
    .drop-zone {
        border: 2px dashed #3498db;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #f0f7ff;
        cursor: pointer;
        margin-bottom: 10px;
    }

    .drop-zone i {
        font-size: 2.5rem;
        color: #3498db;
        margin-bottom: 10px;
        display: block;
    }

    .drop-zone p {
        margin: 10px 0;
        color: #3498db;
        font-weight: 600;
    }

    .drop-zone-small {
        margin: 5px 0;
        color: #666;
        font-size: 0.9rem;
    }

    .galeria-imagenes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .imagen-item {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        border-radius: 6px;
        overflow: hidden;
        background: #f0f0f0;
    }

    .imagen-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .imagen-item .remove {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .shake {
        animation: shake 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <!-- Header Moderno -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <!-- Título y descripción -->
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">light_mode</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización de Reflectivo</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>
            
            <!-- Campos del Header en una fila -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <!-- Cliente -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
                
                <!-- Asesor -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>
                
                <!-- Fecha -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionReflectivoForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">

            <!-- DESCRIPCIÓN -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-sticky-note"></i> DESCRIPCIÓN
                </div>
                <div class="form-group-large">
                    <textarea id="descripcion_reflectivo" name="descripcion_reflectivo" class="input-large" rows="3" placeholder="Describe el reflectivo, detalles especiales, aplicación, etc."></textarea>
                </div>
            </div>

            <!-- IMÁGENES -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-images"></i> IMÁGENES (MÁXIMO 3)
                </div>
                <div class="form-group-large">
                    <div class="drop-zone" id="drop_zone_imagenes">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <p class="drop-zone-small">Máximo 3 imágenes</p>
                        <input type="file" id="imagenes_reflectivo" name="imagenes_reflectivo[]" accept="image/*" multiple style="display: none;">
                    </div>
                    <div class="galeria-imagenes" id="galeria_imagenes"></div>
                </div>
            </div>

            <!-- UBICACIÓN -->
            <div class="form-section">
                <div class="ubicacion-box">
                    <div class="ubicacion-header">
                        <label>Ubicación</label>
                        <button type="button" class="btn-add" onclick="agregarUbicacion()">+</button>
                    </div>
                    
                    <label for="seccion_reflectivo" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <input type="text" id="seccion_reflectivo" list="opciones_seccion_reflectivo" class="input-large" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Escribe o selecciona una sección">
                    <datalist id="opciones_seccion_reflectivo">
                        <option value="PECHO">
                        <option value="ESPALDA">
                        <option value="MANGA IZQUIERDA">
                        <option value="MANGA DERECHA">
                        <option value="CUELLO">
                        <option value="COSTADO">
                        <option value="BOLSILLO">
                    </datalist>
                    <div id="errorSeccionReflectivo" style="display: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.5rem; background: #fee2e2; border-radius: 4px; margin-bottom: 10px;">
                        ⚠️ Debes seleccionar una ubicación
                    </div>
                    
                    <div class="secciones-agregadas" id="secciones_agregadas"></div>
                </div>
            </div>

            <!-- OBSERVACIONES GENERALES -->
            <div class="form-section">
                <div class="obs-box">
                    <div class="obs-header">
                        <label>Observaciones Generales</label>
                        <button type="button" class="btn-add" onclick="agregarObservacion()">+</button>
                    </div>
                    
                    <div class="obs-lista" id="observaciones_lista"></div>
                </div>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="{{ route('asesores.cotizaciones.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <i class="fas fa-times" style="font-size: 0.9rem;"></i> Cancelar
                </a>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="submit" name="action" value="borrador" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="action" value="enviar" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Arrays para almacenar datos
let ubicacionesSeleccionadas = [];
let observacionesGenerales = [];
let imagenesSeleccionadas = [];

// Drag and drop para imágenes
const dropZone = document.getElementById('drop_zone_imagenes');
const inputImagenes = document.getElementById('imagenes_reflectivo');

dropZone.addEventListener('click', () => inputImagenes.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.background = '#e8f4f8';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.background = '#f0f7ff';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.background = '#f0f7ff';
    manejarImagenes(e.dataTransfer.files);
});

inputImagenes.addEventListener('change', (e) => {
    manejarImagenes(e.target.files);
});

function manejarImagenes(files) {
    if (imagenesSeleccionadas.length + files.length > 3) {
        alert('Máximo 3 imágenes permitidas');
        return;
    }

    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenesSeleccionadas.push({
                    file: file,
                    preview: e.target.result
                });
                renderizarImagenes();
            };
            reader.readAsDataURL(file);
        }
    });
}

function renderizarImagenes() {
    const galeria = document.getElementById('galeria_imagenes');
    galeria.innerHTML = '';
    
    imagenesSeleccionadas.forEach((img, index) => {
        const div = document.createElement('div');
        div.className = 'imagen-item';
        div.innerHTML = `
            <img src="${img.preview}" alt="Imagen ${index + 1}">
            <button type="button" class="remove" onclick="eliminarImagen(${index})">×</button>
        `;
        galeria.appendChild(div);
    });
}

function eliminarImagen(index) {
    imagenesSeleccionadas.splice(index, 1);
    renderizarImagenes();
}

// Ubicaciones
function agregarUbicacion() {
    const input = document.getElementById('seccion_reflectivo');
    const ubicacion = input.value.trim();
    const errorDiv = document.getElementById('errorSeccionReflectivo');
    
    if (!ubicacion) {
        input.style.border = '2px solid #ef4444';
        input.style.background = '#fee2e2';
        input.classList.add('shake');
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            input.style.border = '';
            input.style.background = '';
            input.classList.remove('shake');
        }, 600);
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 3000);
        
        return;
    }
    
    input.style.border = '';
    input.style.background = '';
    errorDiv.style.display = 'none';
    
    // Crear modal para descripción
    let html = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;" id="modalUbicacion">
            <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #1e40af; font-size: 1.1rem;">${ubicacion}</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" onclick="cerrarModalUbicacion()" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">×</button>
                        <button type="button" onclick="guardarUbicacion('${ubicacion}')" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">+</button>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Descripción</label>
                    <textarea id="descUbicacion" placeholder="Describe la ubicación..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; resize: vertical; min-height: 80px;"></textarea>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
}

function cerrarModalUbicacion() {
    const modal = document.getElementById('modalUbicacion');
    if (modal) modal.remove();
}

function guardarUbicacion(ubicacion) {
    const desc = document.getElementById('descUbicacion').value;
    
    ubicacionesSeleccionadas.push({
        ubicacion: ubicacion,
        descripcion: desc
    });
    
    cerrarModalUbicacion();
    document.getElementById('seccion_reflectivo').value = '';
    renderizarUbicaciones();
}

function renderizarUbicaciones() {
    const container = document.getElementById('secciones_agregadas');
    container.innerHTML = '';
    
    ubicacionesSeleccionadas.forEach((ub, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;';
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ub.ubicacion}</h4>
                    ${ub.descripcion ? `<p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Descripción:</strong> ${ub.descripcion}</p>` : ''}
                </div>
                <button type="button" onclick="eliminarUbicacion(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
            </div>
        `;
        container.appendChild(div);
    });
}

function eliminarUbicacion(index) {
    ubicacionesSeleccionadas.splice(index, 1);
    renderizarUbicaciones();
}

// Observaciones
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
            toggleBtn.style.background = '#ff9800';
        }
    });
}

// Sincronizar valores del header con el formulario
document.getElementById('header-cliente').addEventListener('input', function() {
    document.getElementById('cliente').value = this.value;
});

document.getElementById('header-fecha').addEventListener('change', function() {
    document.getElementById('fecha').value = this.value;
});

// Envío del formulario
document.getElementById('cotizacionReflectivoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Sincronizar valores del header antes de enviar
    document.getElementById('cliente').value = document.getElementById('header-cliente').value;
    document.getElementById('fecha').value = document.getElementById('header-fecha').value;

    const cliente = document.getElementById('cliente').value;
    const asesora = document.getElementById('asesora').value;
    const descripcion = document.getElementById('descripcion_reflectivo').value;

    if (!cliente || !asesora || !descripcion) {
        alert('Completa los campos obligatorios');
        return;
    }

    const submitButton = e.submitter;
    const action = submitButton ? submitButton.value : 'borrador';

    // Leer observaciones generales del DOM
    const observacionesDelDOM = [];
    document.querySelectorAll('#observaciones_lista > div').forEach((div) => {
        const inputTexto = div.querySelector('input[name="observaciones_generales[]"]');
        const inputCheck = div.querySelector('input[name="observaciones_check[]"]');
        const inputValor = div.querySelector('input[name="observaciones_valor[]"]');
        
        if (inputTexto && inputTexto.value.trim()) {
            const esCheckbox = inputCheck && inputCheck.checked;
            
            const obs = {
                texto: inputTexto.value.trim(),
                tipo: esCheckbox ? 'checkbox' : 'texto',
                valor: esCheckbox ? inputCheck.checked : (inputValor ? inputValor.value : '')
            };
            
            observacionesDelDOM.push(obs);
        }
    });

    // Preparar datos como JSON
    const data = {
        _token: document.querySelector('input[name="_token"]').value,
        cliente: cliente,
        asesora: asesora,
        fecha: document.getElementById('header-fecha').value,
        action: action,
        descripcion_reflectivo: descripcion,
        ubicaciones_reflectivo: ubicacionesSeleccionadas,
        observaciones_generales: observacionesDelDOM
    };

    // Preparar FormData para subir imágenes
    const formData = new FormData();
    
    // Agregar datos JSON
    Object.keys(data).forEach(key => {
        if (Array.isArray(data[key]) || typeof data[key] === 'object') {
            formData.append(key, JSON.stringify(data[key]));
        } else {
            formData.append(key, data[key]);
        }
    });

    // Agregar imágenes
    imagenesSeleccionadas.forEach((img) => {
        formData.append('imagenes_reflectivo[]', img.file);
    });

    try {
        const response = await fetch('{{ route("asesores.cotizaciones.guardar") }}', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Cotización guardada exitosamente');
            window.location.href = '{{ route("asesores.cotizaciones.index") }}';
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Error al guardar: ' + error.message);
    }
});
</script>

@endsection
