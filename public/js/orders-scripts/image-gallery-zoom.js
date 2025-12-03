/**
 * Image Gallery with Zoom Modal
 * Maneja la galería de imágenes y el modal de zoom con navegación
 */

// Evitar redeclaración si el script se carga múltiples veces
if (!window.ImageGalleryZoomModule) {
    window.ImageGalleryZoomModule = (() => {
        // Estado privado del módulo
        const state = {
            currentImageIndex: 0,
            allImages: [],
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,
            offsetX: 0,
            offsetY: 0
        };

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
                state.allImages = data.images || [];

                // Verificar que el elemento existe antes de acceder a su propiedad style
                const imagenesSection = document.getElementById('imagenes-section');
                if (!imagenesSection) {
                    console.warn('⚠️ El elemento imagenes-section no existe en el DOM');
                    return;
                }

                if (state.allImages.length > 0) {
                    renderImageGallery();
                    imagenesSection.style.display = 'block';
                } else {
                    imagenesSection.style.display = 'none';
                }
            } catch (error) {
                console.error('Error al cargar imágenes:', error);
                const imagenesSection = document.getElementById('imagenes-section');
                if (imagenesSection) {
                    imagenesSection.style.display = 'none';
                }
            }
        }

        /**
         * Renderizar la galería de imágenes
         */
        function renderImageGallery() {
            const grid = document.getElementById('imagenes-grid');
            grid.innerHTML = '';

            state.allImages.forEach((image, index) => {
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
            state.currentImageIndex = index;
            const modal = document.getElementById('image-zoom-modal');
            const image = document.getElementById('zoom-image');

            image.src = state.allImages[index].url;
            image.style.transform = 'translate(0, 0) scale(1)';
            state.offsetX = 0;
            state.offsetY = 0;

            // Mostrar/ocultar botones de navegación
            const prevBtn = document.getElementById('zoom-prev-btn');
            const nextBtn = document.getElementById('zoom-next-btn');

            prevBtn.style.display = index > 0 ? 'flex' : 'none';
            nextBtn.style.display = index < state.allImages.length - 1 ? 'flex' : 'none';

            // Actualizar contador
            document.getElementById('zoom-counter').textContent = `${index + 1} / ${state.allImages.length}`;

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
            state.isDragging = false;
        }

        /**
         * Navegar a la imagen anterior
         */
        function previousImage() {
            if (state.currentImageIndex > 0) {
                openImageZoom(state.currentImageIndex - 1);
            }
        }

        /**
         * Navegar a la siguiente imagen
         */
        function nextImage() {
            if (state.currentImageIndex < state.allImages.length - 1) {
                openImageZoom(state.currentImageIndex + 1);
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
            state.isDragging = true;
            state.dragStartX = e.clientX;
            state.dragStartY = e.clientY;
        }

        /**
         * Arrastrar imagen
         */
        function dragImage(e) {
            if (!state.isDragging) return;

            const image = document.getElementById('zoom-image');
            const deltaX = e.clientX - state.dragStartX;
            const deltaY = e.clientY - state.dragStartY;

            state.offsetX += deltaX;
            state.offsetY += deltaY;

            image.style.transform = `translate(${state.offsetX}px, ${state.offsetY}px) scale(1)`;

            state.dragStartX = e.clientX;
            state.dragStartY = e.clientY;
        }

        /**
         * Detener arrastre de imagen
         */
        function stopDrag() {
            state.isDragging = false;
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

        // Interfaz pública
        return {
            loadOrderImages,
            openImageZoom,
            closeImageZoom
        };
    })();

    // Exportar funciones globales
    window.loadOrderImages = window.ImageGalleryZoomModule.loadOrderImages;
    window.openImageZoom = window.ImageGalleryZoomModule.openImageZoom;
    window.closeImageZoom = window.ImageGalleryZoomModule.closeImageZoom;

    console.log('✅ ImageGalleryZoomModule cargado - Evitando redeclaraciones de isDragging');
}

