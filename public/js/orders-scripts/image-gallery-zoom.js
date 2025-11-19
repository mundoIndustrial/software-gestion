/**
 * Image Gallery with Zoom Modal
 * Maneja la galería de imágenes y el modal de zoom con navegación
 */

let currentImageIndex = 0;
let allImages = [];
let isDragging = false;
let dragStartX = 0;
let dragStartY = 0;
let offsetX = 0;
let offsetY = 0;

/**
 * Cargar imágenes de la orden
 */
async function loadOrderImages(pedido) {
    try {
        // Determinar el contexto (registros o bodega)
        const context = window.modalContext || 'registros';
        const baseUrl = context === 'bodega' ? '/bodega' : '/registros';

        // Obtener imágenes de la orden
        const response = await fetch(`${baseUrl}/${pedido}/images`);
        if (!response.ok) {
            console.log('No hay imágenes para esta orden');
            return;
        }

        const data = await response.json();
        allImages = data.images || [];

        if (allImages.length > 0) {
            renderImageGallery();
            document.getElementById('imagenes-section').style.display = 'block';
        } else {
            document.getElementById('imagenes-section').style.display = 'none';
        }
    } catch (error) {
        console.error('Error al cargar imágenes:', error);
        document.getElementById('imagenes-section').style.display = 'none';
    }
}

/**
 * Renderizar la galería de imágenes
 */
function renderImageGallery() {
    const grid = document.getElementById('imagenes-grid');
    grid.innerHTML = '';

    allImages.forEach((image, index) => {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'imagen-thumbnail';
        thumbnail.innerHTML = `
            <img src="${image.url}" alt="Imagen ${index + 1}" loading="lazy">
            <div class="imagen-overlay">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </div>
        `;

        thumbnail.addEventListener('click', () => openImageZoom(index));
        grid.appendChild(thumbnail);
    });
}

/**
 * Abrir modal de zoom con la imagen seleccionada
 */
function openImageZoom(index) {
    currentImageIndex = index;
    const modal = document.getElementById('image-zoom-modal');
    const image = document.getElementById('zoom-image');

    image.src = allImages[index].url;
    image.style.transform = 'translate(0, 0) scale(1)';
    offsetX = 0;
    offsetY = 0;

    // Mostrar/ocultar botones de navegación
    const prevBtn = document.getElementById('zoom-prev-btn');
    const nextBtn = document.getElementById('zoom-next-btn');

    prevBtn.style.display = index > 0 ? 'flex' : 'none';
    nextBtn.style.display = index < allImages.length - 1 ? 'flex' : 'none';

    // Actualizar contador
    document.getElementById('zoom-counter').textContent = `${index + 1} / ${allImages.length}`;

    // Mostrar modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Agregar event listeners
    attachZoomEventListeners();
}

/**
 * Cerrar modal de zoom
 */
function closeImageZoom() {
    const modal = document.getElementById('image-zoom-modal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    isDragging = false;
}

/**
 * Navegar a la imagen anterior
 */
function previousImage() {
    if (currentImageIndex > 0) {
        openImageZoom(currentImageIndex - 1);
    }
}

/**
 * Navegar a la siguiente imagen
 */
function nextImage() {
    if (currentImageIndex < allImages.length - 1) {
        openImageZoom(currentImageIndex + 1);
    }
}

/**
 * Agregar event listeners para el zoom
 */
function attachZoomEventListeners() {
    const closeBtn = document.getElementById('zoom-close-btn');
    const prevBtn = document.getElementById('zoom-prev-btn');
    const nextBtn = document.getElementById('zoom-next-btn');
    const overlay = document.getElementById('image-zoom-overlay');
    const image = document.getElementById('zoom-image');

    // Cerrar al hacer clic en X
    closeBtn.onclick = closeImageZoom;

    // Cerrar al hacer clic en el overlay
    overlay.onclick = closeImageZoom;

    // Navegación
    prevBtn.onclick = previousImage;
    nextBtn.onclick = nextImage;

    // Arrastrar imagen
    image.onmousedown = startDrag;
    document.onmousemove = dragImage;
    document.onmouseup = stopDrag;

    // Teclado
    document.onkeydown = handleKeyboard;
}

/**
 * Iniciar arrastre de imagen
 */
function startDrag(e) {
    isDragging = true;
    dragStartX = e.clientX;
    dragStartY = e.clientY;
}

/**
 * Arrastrar imagen
 */
function dragImage(e) {
    if (!isDragging) return;

    const image = document.getElementById('zoom-image');
    const deltaX = e.clientX - dragStartX;
    const deltaY = e.clientY - dragStartY;

    offsetX += deltaX;
    offsetY += deltaY;

    image.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(1)`;

    dragStartX = e.clientX;
    dragStartY = e.clientY;
}

/**
 * Detener arrastre de imagen
 */
function stopDrag() {
    isDragging = false;
}

/**
 * Manejar teclas de teclado
 */
function handleKeyboard(e) {
    const modal = document.getElementById('image-zoom-modal');
    if (modal.style.display !== 'flex') return;

    switch (e.key) {
        case 'Escape':
            closeImageZoom();
            break;
        case 'ArrowLeft':
            previousImage();
            break;
        case 'ArrowRight':
            nextImage();
            break;
    }
}

// Exportar funciones globales
window.loadOrderImages = loadOrderImages;
window.openImageZoom = openImageZoom;
window.closeImageZoom = closeImageZoom;
