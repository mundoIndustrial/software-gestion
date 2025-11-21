// ===== FUNCIONES PARA MODALES DE IMÁGENES =====
function abrirModalImagenes(productoIndex, nombreProducto) {
    // Buscar el contenedor con data-producto-index que coincida
    const contenedorImagenes = document.querySelector(`[data-producto-index="${productoIndex}"]`);
    
    if (!contenedorImagenes) {
        console.error('No se encontró el contenedor de imágenes para el producto:', productoIndex);
        alert('No hay imágenes para este producto');
        return;
    }
    
    // Obtener las imágenes del atributo data
    const imagenesJSON = contenedorImagenes.getAttribute('data-todas-imagenes');
    let todasLasImagenes = [];
    
    try {
        todasLasImagenes = JSON.parse(imagenesJSON) || [];
    } catch (e) {
        console.error('Error al parsear imágenes:', e);
        todasLasImagenes = [];
    }
    
    if (todasLasImagenes.length === 0) {
        alert('No hay imágenes para este producto');
        return;
    }
    
    console.log(`📸 Abriendo modal para producto ${productoIndex} con ${todasLasImagenes.length} imágenes`);
    
    // Llenar el modal con las imágenes
    const grid = document.getElementById('modalImagenesGrid');
    grid.innerHTML = '';
    
    todasLasImagenes.forEach((imagen, index) => {
        const div = document.createElement('div');
        div.style.cssText = 'position: relative; cursor: pointer; overflow: hidden; border-radius: 4px;';
        div.innerHTML = `
            <img src="${imagen}" alt="Imagen ${index + 1}" 
                 style="width: 100%; height: 250px; object-fit: cover; transition: all 0.2s; cursor: pointer;"
                 onmouseover="this.style.transform='scale(1.05)'; this.style.opacity='0.9'"
                 onmouseout="this.style.transform='scale(1)'; this.style.opacity='1'"
                 ondblclick="abrirImagenFullscreen('${imagen}')">
        `;
        grid.appendChild(div);
    });
    
    document.getElementById('modalImagenesTitle').textContent = `Imágenes - ${nombreProducto}`;
    document.getElementById('modalImagenesProducto').style.display = 'block';
}

function cerrarModalImagenes() {
    document.getElementById('modalImagenesProducto').style.display = 'none';
}

function abrirImagenFullscreen(src) {
    const modal = document.getElementById('modalImagenFullscreen');
    document.getElementById('imagenFullscreen').src = src;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarImagenFullscreen() {
    document.getElementById('modalImagenFullscreen').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modales al hacer clic en el fondo
document.addEventListener('click', function(event) {
    const modalImagenes = document.getElementById('modalImagenesProducto');
    const modalFullscreen = document.getElementById('modalImagenFullscreen');
    
    if (event.target === modalImagenes) {
        cerrarModalImagenes();
    }
    if (event.target === modalFullscreen) {
        cerrarImagenFullscreen();
    }
});

// Tecla ESC para cerrar modales
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModalImagenes();
        cerrarImagenFullscreen();
    }
});

// ===== NAVEGACIÓN ENTRE SECCIONES =====
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        
        // Remover clase active de todos los botones y secciones
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active'));
        
        // Agregar clase active al botón y sección seleccionados
        this.classList.add('active');
        document.getElementById(section + '-section').classList.add('active');
        
        // Formatos eliminados - no cargar
    });
});


