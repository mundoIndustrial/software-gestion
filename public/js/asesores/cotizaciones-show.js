// ============================================================================
// Cotizaciones Show - JavaScript Functions
// ============================================================================

// Función para cambiar tabs
function cambiarTab(tabName, button) {
    // Ocultar todos los tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach((tab, index) => {
        tab.classList.remove('active');
    });
    
    // Mostrar el tab seleccionado
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Actualizar botones activos
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach((btn, index) => {
        btn.classList.remove('active');
    });
    button.classList.add('active');
}

// Ocultar navbar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = 'none';
    }
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = 'none';
    }
});

// Mostrar navbar cuando se vuelve a la lista
window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) {
        topNav.style.display = '';
    }
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) {
        pageHeader.style.display = '';
    }
});

function abrirModalImagen(src, titulo, imagenes = null, indiceActual = 0) {
    console.log('Abriendo imagen:', src);
    console.log('Imágenes disponibles:', imagenes, 'Índice:', indiceActual);
    
    // Guardar imágenes en window para navegación
    if (imagenes && Array.isArray(imagenes)) {
        window.imagenesModal = imagenes;
        window.indiceImagenModal = indiceActual;
    } else {
        window.imagenesModal = [src];
        window.indiceImagenModal = 0;
    }
    
    // Crear modal si no existe
    let modal = document.getElementById('modalImagen');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'modalImagen';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease-in;
            overflow: hidden;
        `;
        
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
        const hora = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        modal.innerHTML = `
            <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <!-- Botón cerrar -->
                <button onclick="cerrarModalImagen()" style="
                    position: absolute;
                    top: 20px;
                    right: 40px;
                    background: white;
                    border: none;
                    font-size: 28px;
                    cursor: pointer;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    transition: all 0.3s;
                " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">
                    ✕
                </button>
                
                <!-- Controles de zoom -->
                <div style="
                    position: absolute;
                    top: 20px;
                    left: 40px;
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 12px 16px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    z-index: 10000;
                    display: flex;
                    gap: 12px;
                    align-items: center;
                ">
                    <button onclick="zoomOut()" style="
                        background: white;
                        color: #333;
                        border: none;
                        width: 28px;
                        height: 28px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">−</button>
                    <span id="zoomLevel" style="min-width: 40px; text-align: center;">100%</span>
                    <button onclick="zoomIn()" style="
                        background: white;
                        color: #333;
                        border: none;
                        width: 28px;
                        height: 28px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: bold;
                        transition: all 0.2s;
                    " onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='white'">+</button>
                </div>
                
                <!-- Contenedor de imagen con drag -->
                <div id="imagenContenedor" style="
                    position: relative;
                    width: 90vw;
                    height: 80vh;
                    max-width: 1200px;
                    max-height: 750px;
                    overflow: hidden;
                    border-radius: 8px;
                    background: transparent;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <img id="imagenModal" src="" alt="Imagen ampliada" style="
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                        border-radius: 8px;
                        box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
                        cursor: grab;
                        transition: transform 0.1s ease-out;
                        position: relative;
                    ">
                </div>
                
                <!-- Botones de navegación -->
                <button onclick="imagenAnterior()" style="
                    position: absolute;
                    left: 20px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(255, 255, 255, 0.9);
                    border: none;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10001;
                    transition: all 0.3s;
                " onmouseover="this.style.background='white'" onmouseout="this.style.background='rgba(255, 255, 255, 0.9)'">
                    ◀
                </button>
                
                <button onclick="imagenSiguiente()" style="
                    position: absolute;
                    right: 20px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(255, 255, 255, 0.9);
                    border: none;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10001;
                    transition: all 0.3s;
                " onmouseover="this.style.background='white'" onmouseout="this.style.background='rgba(255, 255, 255, 0.9)'">
                    ▶
                </button>
                
                <!-- Información -->
                <div style="
                    position: absolute;
                    bottom: 30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    max-width: 80%;
                    text-align: center;
                    z-index: 10000;
                ">
                    <div id="tituloModal" style="margin-bottom: 6px;"></div>
                    <div style="font-size: 11px; opacity: 0.8;">
                        📅 ${fecha} | 🕐 ${hora}
                    </div>
                </div>
            </div>
            
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
            </style>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar al hacer click fuera de la imagen
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalImagen();
            }
        });
        
        // Cerrar con tecla ESC y navegar con flechas
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalImagen();
            } else if (e.key === 'ArrowLeft') {
                imagenAnterior();
            } else if (e.key === 'ArrowRight') {
                imagenSiguiente();
            }
        });
        
        // Zoom con rueda del mouse
        const imagenContenedor = document.getElementById('imagenContenedor');
        imagenContenedor.addEventListener('wheel', function(e) {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        });
        
        // Drag and drop para mover imagen
        window.isDragging = false;
        window.startX = 0;
        window.startY = 0;
        window.offsetX = 0;
        window.offsetY = 0;
        
        const imagenModal = document.getElementById('imagenModal');
        
        imagenContenedor.addEventListener('mousedown', function(e) {
            window.isDragging = true;
            window.startX = e.clientX;
            window.startY = e.clientY;
            imagenModal.style.cursor = 'grabbing';
            e.preventDefault();
        });
        
        document.addEventListener('mousemove', function(e) {
            if (!window.isDragging) return;
            
            const deltaX = e.clientX - window.startX;
            const deltaY = e.clientY - window.startY;
            
            window.offsetX += deltaX;
            window.offsetY += deltaY;
            
            window.startX = e.clientX;
            window.startY = e.clientY;
            
            const scale = window.zoomActual / 100;
            imagenModal.style.transform = `scale(${scale}) translate(${window.offsetX}px, ${window.offsetY}px)`;
        });
        
        document.addEventListener('mouseup', function() {
            window.isDragging = false;
            imagenModal.style.cursor = 'grab';
        });
    }
    
    // Mostrar imagen
    document.getElementById('imagenModal').src = src;
    document.getElementById('tituloModal').textContent = titulo;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
    
    modal.style.display = 'flex';
}

function zoomIn() {
    if (!window.zoomActual) window.zoomActual = 100;
    window.zoomActual = Math.min(window.zoomActual + 10, 300);
    document.getElementById('zoomLevel').textContent = window.zoomActual + '%';
    
    // Obtener offset actual
    const imagenModal = document.getElementById('imagenModal');
    const transform = imagenModal.style.transform;
    const translateMatch = transform.match(/translate\(([^,]+)px,\s*([^)]+)px\)/);
    const offsetX = translateMatch ? parseFloat(translateMatch[1]) : 0;
    const offsetY = translateMatch ? parseFloat(translateMatch[2]) : 0;
    
    imagenModal.style.transform = `scale(${window.zoomActual / 100}) translate(${offsetX}px, ${offsetY}px)`;
}

function zoomOut() {
    if (!window.zoomActual) window.zoomActual = 100;
    window.zoomActual = Math.max(window.zoomActual - 10, 50);
    document.getElementById('zoomLevel').textContent = window.zoomActual + '%';
    
    // Obtener offset actual
    const imagenModal = document.getElementById('imagenModal');
    const transform = imagenModal.style.transform;
    const translateMatch = transform.match(/translate\(([^,]+)px,\s*([^)]+)px\)/);
    const offsetX = translateMatch ? parseFloat(translateMatch[1]) : 0;
    const offsetY = translateMatch ? parseFloat(translateMatch[2]) : 0;
    
    imagenModal.style.transform = `scale(${window.zoomActual / 100}) translate(${offsetX}px, ${offsetY}px)`;
}

function imagenAnterior() {
    if (!window.imagenesModal || window.imagenesModal.length === 0) return;
    
    window.indiceImagenModal = (window.indiceImagenModal - 1 + window.imagenesModal.length) % window.imagenesModal.length;
    const imagen = window.imagenesModal[window.indiceImagenModal];
    
    document.getElementById('imagenModal').src = imagen;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
}

function imagenSiguiente() {
    if (!window.imagenesModal || window.imagenesModal.length === 0) return;
    
    window.indiceImagenModal = (window.indiceImagenModal + 1) % window.imagenesModal.length;
    const imagen = window.imagenesModal[window.indiceImagenModal];
    
    document.getElementById('imagenModal').src = imagen;
    
    // Resetear zoom y posición
    window.zoomActual = 100;
    window.offsetX = 0;
    window.offsetY = 0;
    document.getElementById('zoomLevel').textContent = '100%';
    document.getElementById('imagenModal').style.transform = 'scale(1)';
}

function cerrarModalImagen() {
    const modal = document.getElementById('modalImagen');
    if (modal) {
        modal.style.display = 'none';
        window.zoomActual = 100;
    }
}
