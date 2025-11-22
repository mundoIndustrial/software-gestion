<!-- Modal de Imagen Reutilizable -->
<style>
    .modal-imagen-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-imagen-container.activo {
        display: flex;
    }

    .modal-imagen-contenido {
        position: relative;
        background: white;
        border-radius: 12px;
        max-width: 700px;
        max-height: 90vh;
        overflow: visible;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }

    .modal-imagen-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem 1.2rem;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
    }

    .modal-imagen-titulo {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0;
    }

    .modal-imagen-cerrar {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .modal-imagen-cerrar:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .modal-imagen-body {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        padding: 1rem;
        flex: 1;
        overflow: auto;
    }

    .modal-imagen-contenedor {
        position: relative;
        width: 600px;
        height: 400px;
        overflow: hidden;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .modal-imagen-zoom-overlay {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 0.5rem;
        z-index: 100;
        flex-wrap: wrap;
        justify-content: flex-end;
        max-width: 200px;
    }

    .modal-imagen-img {
        width: 600px;
        height: 400px;
        object-fit: cover;
        cursor: grab;
        transition: transform 0.1s ease-out;
        user-select: none;
    }

    .modal-imagen-img:active {
        cursor: grabbing;
    }

    .modal-imagen-zoom-controles {
        display: none;
    }

    .modal-imagen-zoom-btn {
        background: rgba(30, 64, 175, 0.9);
        color: white;
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.75rem;
        transition: all 0.2s;
        white-space: nowrap;
        backdrop-filter: blur(4px);
    }

    .modal-imagen-zoom-btn:hover {
        background: rgba(30, 64, 175, 1);
        transform: scale(1.05);
    }

    .modal-imagen-zoom-nivel {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #1e40af;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        font-size: 0.75rem;
        color: #1e40af;
        font-weight: 700;
        min-width: 45px;
        text-align: center;
        backdrop-filter: blur(4px);
    }

    .modal-imagen-controles {
        display: flex;
        justify-content: center;
        gap: 1rem;
        padding: 1rem;
        border-top: 1px solid #e2e8f0;
        background: white;
    }

    .modal-imagen-btn {
        background: #1e40af;
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .modal-imagen-btn:hover {
        background: #1e3a8a;
        transform: translateY(-2px);
    }

    .modal-imagen-info {
        padding: 1rem;
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
    }
</style>

<div id="modalImagenComponente" class="modal-imagen-container">
    <div class="modal-imagen-contenido">
        <!-- Header -->
        <div class="modal-imagen-header">
            <h2 class="modal-imagen-titulo" id="modalImagenTitulo">Imagen</h2>
            <button class="modal-imagen-cerrar" onclick="cerrarModalImagen()">✕</button>
        </div>

        <!-- Body -->
        <div class="modal-imagen-body">
            <div class="modal-imagen-contenedor">
                <img id="modalImagenSrc" src="" alt="" class="modal-imagen-img">
                
                <!-- Controles de Zoom Overlay -->
                <div class="modal-imagen-zoom-overlay">
                    <button class="modal-imagen-zoom-btn" onclick="zoomOut()">−</button>
                    <div class="modal-imagen-zoom-nivel" id="zoomLevel">100%</div>
                    <button class="modal-imagen-zoom-btn" onclick="zoomIn()">+</button>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
let zoomActual = 100;
let offsetX = 0;
let offsetY = 0;
let isDragging = false;
let startX = 0;
let startY = 0;

function abrirModalImagen(src, titulo = 'Imagen') {
    const modal = document.getElementById('modalImagenComponente');
    const img = document.getElementById('modalImagenSrc');
    const titulo_elem = document.getElementById('modalImagenTitulo');
    
    img.src = src;
    titulo_elem.textContent = titulo;
    modal.classList.add('activo');
    
    // Resetear zoom y posición
    zoomActual = 100;
    offsetX = 0;
    offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    img.style.transform = 'scale(1)';
}

function cerrarModalImagen() {
    const modal = document.getElementById('modalImagenComponente');
    modal.classList.remove('activo');
}

function zoomIn() {
    zoomActual = Math.min(zoomActual + 10, 300);
    document.getElementById('zoomLevel').textContent = zoomActual + '%';
    const scale = zoomActual / 100;
    const img = document.getElementById('modalImagenSrc');
    img.style.transform = `scale(${scale}) translate(${offsetX}px, ${offsetY}px)`;
}

function zoomOut() {
    zoomActual = Math.max(zoomActual - 10, 50);
    document.getElementById('zoomLevel').textContent = zoomActual + '%';
    const scale = zoomActual / 100;
    const img = document.getElementById('modalImagenSrc');
    img.style.transform = `scale(${scale}) translate(${offsetX}px, ${offsetY}px)`;
}

// Drag and drop
const img = document.getElementById('modalImagenSrc');
const contenedor = document.querySelector('.modal-imagen-contenedor');

img.addEventListener('mousedown', function(e) {
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    img.style.cursor = 'grabbing';
});

document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    
    const deltaX = e.clientX - startX;
    const deltaY = e.clientY - startY;
    
    offsetX += deltaX;
    offsetY += deltaY;
    
    startX = e.clientX;
    startY = e.clientY;
    
    const scale = zoomActual / 100;
    img.style.transform = `scale(${scale}) translate(${offsetX}px, ${offsetY}px)`;
});

document.addEventListener('mouseup', function() {
    isDragging = false;
    img.style.cursor = 'grab';
});

// Zoom con rueda del mouse
contenedor.addEventListener('wheel', function(e) {
    e.preventDefault();
    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
});

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalImagen();
    }
});

// Cerrar al hacer click fuera
document.getElementById('modalImagenComponente').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalImagen();
    }
});
</script>
