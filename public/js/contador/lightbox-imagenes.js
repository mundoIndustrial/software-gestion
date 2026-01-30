/**
 * Lightbox para visualizar imágenes de prendas con navegación
 */

let lightboxActual = {
    prendaIndex: 0,
    imagenIndex: 0,
    imagenes: []
};

/**
 * Abre el lightbox de imágenes para una prenda específica
 */
function abrirLightboxImagenes(prendaIndex) {
    const detalles = visorCostosActual.prendaDetalles[prendaIndex];
    
    // Combinar fotos de prenda y fotos de tela desde los detalles
    let todasLasImagenes = [];
    
    if (detalles && detalles.fotos && detalles.fotos.length > 0) {
        todasLasImagenes = [...detalles.fotos];
    }
    
    if (detalles && detalles.tela_fotos && detalles.tela_fotos.length > 0) {
        todasLasImagenes = [...todasLasImagenes, ...detalles.tela_fotos];
    }
    
    if (todasLasImagenes.length === 0) {
        return;
    }
    
    lightboxActual = {
        prendaIndex: prendaIndex,
        imagenIndex: 0,
        imagenes: todasLasImagenes
    };
    
    mostrarImagenLightbox();
    
    // Mostrar el lightbox
    const lightbox = document.getElementById('lightboxImagenes');
    if (lightbox) {
        lightbox.style.display = 'flex';
    }
}

/**
 * Muestra la imagen actual en el lightbox
 */
function mostrarImagenLightbox() {
    const imagen = document.getElementById('lightboxImagen');
    const contador = document.getElementById('lightboxContador');
    const btnAnterior = document.getElementById('lightboxAnterior');
    const btnSiguiente = document.getElementById('lightboxSiguiente');
    
    if (!imagen) return;
    
    // Actualizar imagen
    imagen.src = lightboxActual.imagenes[lightboxActual.imagenIndex];
    
    // Actualizar contador
    if (contador) {
        contador.textContent = `${lightboxActual.imagenIndex + 1} / ${lightboxActual.imagenes.length}`;
    }
    
    // Mostrar/ocultar botones de navegación
    if (btnAnterior) {
        btnAnterior.style.display = lightboxActual.imagenIndex > 0 ? 'flex' : 'none';
    }
    if (btnSiguiente) {
        btnSiguiente.style.display = lightboxActual.imagenIndex < lightboxActual.imagenes.length - 1 ? 'flex' : 'none';
    }
}

/**
 * Navega a la imagen anterior
 */
function lightboxImagenAnterior() {
    if (lightboxActual.imagenIndex > 0) {
        lightboxActual.imagenIndex--;
        mostrarImagenLightbox();
    }
}

/**
 * Navega a la imagen siguiente
 */
function lightboxImagenSiguiente() {
    if (lightboxActual.imagenIndex < lightboxActual.imagenes.length - 1) {
        lightboxActual.imagenIndex++;
        mostrarImagenLightbox();
    }
}

/**
 * Cierra el lightbox
 */
function cerrarLightboxImagenes() {
    const lightbox = document.getElementById('lightboxImagenes');
    if (lightbox) {
        lightbox.style.display = 'none';
    }
    
    lightboxActual = {
        prendaIndex: 0,
        imagenIndex: 0,
        imagenes: []
    };
}

// Event listeners para navegación con teclado
document.addEventListener('keydown', function(event) {
    const lightbox = document.getElementById('lightboxImagenes');
    if (lightbox && lightbox.style.display === 'flex') {
        if (event.key === 'Escape') {
            cerrarLightboxImagenes();
        } else if (event.key === 'ArrowLeft') {
            lightboxImagenAnterior();
        } else if (event.key === 'ArrowRight') {
            lightboxImagenSiguiente();
        }
    }
});
