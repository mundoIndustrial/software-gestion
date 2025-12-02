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
        padding: 2rem;
    }

    .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 2rem;
    }

    .form-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .form-header p {
        color: #64748b;
        font-size: 0.95rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.95rem;
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
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
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

    /* Estilos del Paso 3 */
    .form-group-large {
        margin-bottom: 1.5rem;
    }

    .form-group-large label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .input-large {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.95rem;
        font-family: inherit;
    }

    .input-large:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    /* Técnicas */
    .tecnicas-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .tecnicas-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .tecnicas-header label {
        font-weight: bold;
        font-size: 1.1rem;
        margin: 0;
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
    }

    .btn-add:hover {
        background: #2980b9;
    }

    .tecnicas-seleccionadas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        min-height: 30px;
    }

    .tecnica-badge {
        background: #3498db;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .tecnica-badge .remove {
        cursor: pointer;
        font-weight: bold;
    }

    /* Ubicación */
    .ubicacion-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .ubicacion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .ubicacion-header label {
        font-weight: bold;
        font-size: 1.1rem;
        margin: 0;
    }

    .secciones-agregadas {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .seccion-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .seccion-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
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
        padding: 15px;
        margin-bottom: 20px;
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
    }

    .obs-lista {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .obs-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .obs-item input {
        flex: 1;
        border: none;
        padding: 0;
        font-size: 0.95rem;
    }

    .obs-item input:focus {
        outline: none;
    }

    .obs-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
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
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>🎨 Nueva Cotización de Bordado</h1>
            <p>Completa los pasos 3 y 4 para crear una cotización de bordado en borrador</p>
        </div>

        <form id="cotizacionBordadoForm">
            @csrf

            <!-- Información de Cliente -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="material-symbols-rounded">info</span>
                    Información General
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" id="cliente" name="cliente" required placeholder="Nombre del cliente">
                </div>

                <div class="form-group">
                    <label for="asesora">Asesora *</label>
                    <input type="text" id="asesora" name="asesora" required value="{{ auth()->user()->name }}" readonly>
                </div>
            </div>

            <!-- IMÁGENES -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)
                </div>
                <div class="form-group-large">
                    <div class="drop-zone" id="drop_zone_imagenes">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <p class="drop-zone-small">Máximo 5 imágenes</p>
                        <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
                    </div>
                    <div class="galeria-imagenes" id="galeria_imagenes"></div>
                </div>
            </div>

            <!-- TÉCNICAS -->
            <div class="form-section">
                <div class="tecnicas-box">
                    <div class="tecnicas-header">
                        <label>Técnicas disponibles</label>
                        <button type="button" class="btn-add" onclick="agregarTecnica()">+</button>
                    </div>
                    
                    <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px;">
                        <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                        <option value="BORDADO">BORDADO</option>
                        <option value="DTF">DTF</option>
                        <option value="ESTAMPADO">ESTAMPADO</option>
                        <option value="SUBLIMADO">SUBLIMADO</option>
                    </select>
                    
                    <div class="tecnicas-seleccionadas" id="tecnicas_seleccionadas"></div>
                    
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observaciones</label>
                    <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..."></textarea>
                </div>
            </div>

            <!-- UBICACIÓN -->
            <div class="form-section">
                <div class="ubicacion-box">
                    <div class="ubicacion-header">
                        <label>Ubicación</label>
                        <button type="button" class="btn-add" onclick="agregarSeccion()">+</button>
                    </div>
                    
                    <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <select id="seccion_prenda" class="input-large" style="width: 100%; margin-bottom: 12px;">
                        <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                        <option value="PECHO">PECHO</option>
                        <option value="ESPALDA">ESPALDA</option>
                        <option value="MANGA">MANGA</option>
                        <option value="CUELLO">CUELLO</option>
                        <option value="COSTADO">COSTADO</option>
                        <option value="MÚLTIPLE">MÚLTIPLE</option>
                    </select>
                    
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
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar en Borrador
                </button>
                <a href="{{ route('asesores.cotizaciones-bordado.lista') }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Arrays para almacenar datos
let tecnicasSeleccionadas = [];
let seccionesSeleccionadas = [];
let observacionesGenerales = [];
let imagenesSeleccionadas = [];

// Drag and drop para imágenes
const dropZone = document.getElementById('drop_zone_imagenes');
const inputImagenes = document.getElementById('imagenes_bordado');

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
    if (imagenesSeleccionadas.length + files.length > 5) {
        alert('Máximo 5 imágenes permitidas');
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

// Técnicas
function agregarTecnica() {
    const selector = document.getElementById('selector_tecnicas');
    const tecnica = selector.value;
    
    if (!tecnica) {
        alert('Selecciona una técnica');
        return;
    }
    
    if (tecnicasSeleccionadas.includes(tecnica)) {
        alert('Esta técnica ya está agregada');
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
        badge.className = 'tecnica-badge';
        badge.innerHTML = `
            ${tecnica}
            <span class="remove" onclick="eliminarTecnica(${index})">×</span>
        `;
        container.appendChild(badge);
    });
}

function eliminarTecnica(index) {
    tecnicasSeleccionadas.splice(index, 1);
    renderizarTecnicas();
}

// Ubicaciones
function agregarSeccion() {
    const selector = document.getElementById('seccion_prenda');
    const seccion = selector.value;
    
    if (!seccion) {
        alert('Selecciona una sección');
        return;
    }
    
    if (seccionesSeleccionadas.includes(seccion)) {
        alert('Esta sección ya está agregada');
        return;
    }
    
    seccionesSeleccionadas.push(seccion);
    selector.value = '';
    renderizarSecciones();
}

function renderizarSecciones() {
    const container = document.getElementById('secciones_agregadas');
    container.innerHTML = '';
    
    seccionesSeleccionadas.forEach((seccion, index) => {
        const div = document.createElement('div');
        div.className = 'seccion-item';
        div.innerHTML = `
            <span>${seccion}</span>
            <button type="button" class="remove" onclick="eliminarSeccion(${index})">×</button>
        `;
        container.appendChild(div);
    });
}

function eliminarSeccion(index) {
    seccionesSeleccionadas.splice(index, 1);
    renderizarSecciones();
}

// Observaciones Generales
function agregarObservacion() {
    const container = document.getElementById('observaciones_lista');
    const index = observacionesGenerales.length;
    
    observacionesGenerales.push('');
    renderizarObservaciones();
    
    // Focus en el nuevo input
    setTimeout(() => {
        const inputs = container.querySelectorAll('input');
        if (inputs[index]) inputs[index].focus();
    }, 0);
}

function renderizarObservaciones() {
    const container = document.getElementById('observaciones_lista');
    container.innerHTML = '';
    
    observacionesGenerales.forEach((obs, index) => {
        const div = document.createElement('div');
        div.className = 'obs-item';
        div.innerHTML = `
            <input type="text" value="${obs}" placeholder="Observación ${index + 1}..." 
                   onchange="actualizarObservacion(${index}, this.value)">
            <button type="button" class="remove" onclick="eliminarObservacion(${index})">×</button>
        `;
        container.appendChild(div);
    });
}

function actualizarObservacion(index, valor) {
    observacionesGenerales[index] = valor;
}

function eliminarObservacion(index) {
    observacionesGenerales.splice(index, 1);
    renderizarObservaciones();
}

// Envío del formulario
document.getElementById('cotizacionBordadoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const cliente = document.getElementById('cliente').value;
    const asesora = document.getElementById('asesora').value;
    const observacionesTecnicas = document.getElementById('observaciones_tecnicas').value;

    if (!cliente || !asesora) {
        alert('Completa los campos obligatorios');
        return;
    }

    // Preparar FormData para subir imágenes
    const formData = new FormData();
    formData.append('cliente', cliente);
    formData.append('asesora', asesora);
    formData.append('observaciones_tecnicas', observacionesTecnicas);
    formData.append('tecnicas', JSON.stringify(tecnicasSeleccionadas));
    formData.append('ubicaciones', JSON.stringify(seccionesSeleccionadas));
    formData.append('observaciones_generales', JSON.stringify(observacionesGenerales));

    // Agregar imágenes
    imagenesSeleccionadas.forEach((img) => {
        formData.append('imagenes[]', img.file);
    });

    try {
        const response = await fetch('{{ route("asesores.cotizaciones-bordado.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: '✅ Éxito',
                text: 'Cotización guardada en borrador',
                icon: 'success',
                confirmButtonText: 'Continuar'
            }).then(() => {
                window.location.href = result.redirect;
            });
        } else {
            Swal.fire({
                title: '❌ Error',
                text: result.message,
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: '❌ Error',
            text: 'Error al guardar: ' + error.message,
            icon: 'error'
        });
    }
});
</script>
@endsection
