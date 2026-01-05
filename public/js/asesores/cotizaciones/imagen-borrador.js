/**
 * imagen-borrador.js
 * Gestiona la eliminación de imágenes del borrador
 */

/**
 * Borrar imagen de prenda
 * @param {number} fotoId - ID de la imagen en BD
 * @param {HTMLElement} element - Elemento DOM a remover
 */
async function borrarImagenPrenda(fotoId, element) {
    const result = await Swal.fire({
        title: '¿Eliminar imagen?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/asesores/cotizaciones/imagenes/prenda/${fotoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Error al eliminar imagen');
        }
        
        // Remover del DOM - buscar el div padre inmediato con la foto
        const fotoContainer = element.closest('div[style*="position: relative"]');
        if (fotoContainer) {
            fotoContainer.remove();
        }
        console.log('✅ Imagen de prenda eliminada:', fotoId);
        
        // Mostrar notificación
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: 'Imagen eliminada',
            showConfirmButton: false,
            timer: 2000
        });
        
    } catch (error) {
        console.error('❌ Error al eliminar imagen de prenda:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo eliminar la imagen',
            confirmButtonColor: '#1e40af'
        });
    }
}

/**
 * Borrar imagen de tela
 * @param {number} fotoId - ID de la imagen en BD
 * @param {HTMLElement} element - Elemento DOM a remover
 */
async function borrarImagenTela(fotoId, element) {
    const result = await Swal.fire({
        title: '¿Eliminar imagen de tela?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/asesores/cotizaciones/imagenes/tela/${fotoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Error al eliminar imagen');
        }
        
        // Remover del DOM - buscar el div padre inmediato con la foto
        const fotoContainer = element.closest('div[style*="position: relative"]');
        if (fotoContainer) {
            fotoContainer.remove();
        }
        console.log('✅ Imagen de tela eliminada:', fotoId);
        
        // Mostrar notificación
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: 'Imagen de tela eliminada',
            showConfirmButton: false,
            timer: 2000
        });
        
    } catch (error) {
        console.error('❌ Error al eliminar imagen de tela:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo eliminar la imagen',
            confirmButtonColor: '#1e40af'
        });
    }
}

/**
 * Borrar imagen de logo
 * @param {number} fotoId - ID de la imagen en BD
 * @param {HTMLElement} element - Elemento DOM a remover
 */
async function borrarImagenLogo(fotoId, element) {
    const result = await Swal.fire({
        title: '¿Eliminar imagen de logo?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`/asesores/cotizaciones/imagenes/logo/${fotoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Error al eliminar imagen');
        }
        
        // Remover del DOM - buscar el div padre inmediato con la foto
        const fotoContainer = element.closest('div[style*="position: relative"]');
        if (fotoContainer) {
            fotoContainer.remove();
        }
        
        // ✅ IMPORTANTE: Eliminar también de window.imagenesEnMemoria.logo por ID
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
            console.log(`🔍 Eliminando de memoria por fotoId: ${fotoId}`);
            const beforeCount = window.imagenesEnMemoria.logo.length;
            
            window.imagenesEnMemoria.logo = window.imagenesEnMemoria.logo.filter(imagen => {
                // Si es un objeto con fotoId
                if (imagen && typeof imagen === 'object' && imagen.fotoId === fotoId) {
                    console.log(`❌ Eliminando de memoria:`, imagen);
                    return false;  // Eliminar
                }
                return true;  // Mantener
            });
            
            console.log(`✅ Eliminación completada. Antes: ${beforeCount}, Después: ${window.imagenesEnMemoria.logo.length}`);
        }
        
        console.log('✅ Imagen de logo eliminada:', fotoId);
        
        // Mostrar notificación
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: 'Imagen de logo eliminada',
            showConfirmButton: false,
            timer: 2000
        });
        
    } catch (error) {
        console.error('❌ Error al eliminar imagen de logo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo eliminar la imagen',
            confirmButtonColor: '#1e40af'
        });
    }
}
