/**
 * EppImagenManager - Gestiona las imágenes de EPP
 * Patrón: Image Manager
 */

class EppImagenManager {
    constructor(apiService, stateManager, modalManager) {
        this.apiService = apiService;
        this.stateManager = stateManager;
        this.modalManager = modalManager;
    }

    /**
     * Manejar selección de imágenes
     */
    async manejarSeleccionImagenes(event) {
        const archivos = event.target.files;
        const producto = this.stateManager.getProductoSeleccionado();

        if (!producto) {
            alert('Selecciona un EPP primero');
            document.getElementById('inputCargaImagenesEPP').value = '';
            return;
        }

        if (archivos.length === 0) return;

        for (const archivo of archivos) {
            await this._procesarImagenLocal(archivo);
        }

        // Limpiar input
        document.getElementById('inputCargaImagenesEPP').value = '';
    }

    /**
     * Procesar imagen localmente (sin subir a API)
     * Guarda en memoria hasta que se cree el pedido
     */
    async _procesarImagenLocal(archivo) {
        try {
            // Validar tipo de archivo
            if (!archivo.type.startsWith('image/')) {
                alert('Solo se permiten archivos de imagen');
                return;
            }

            // Validar tamaño (máximo 5MB)
            const maxSize = 5 * 1024 * 1024;
            if (archivo.size > maxSize) {
                alert('El archivo es demasiado grande (máximo 5MB)');
                return;
            }

            // ✅ Guardar archivo directamente en el estado (sin subir)
            // Se enviará como FormData cuando se cree el pedido
            const imagenData = {
                id: Date.now(), // ID temporal basado en timestamp
                nombre: archivo.name,
                archivo: archivo, // Guardar el archivo para enviarlo después
                preview: null // Preview generado localmente
            };

            // Generar preview local usando FileReader
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenData.preview = e.target.result; // Data URL para preview
                this.stateManager.agregarImagenSubida(imagenData);
                this.modalManager.agregarImagenUI(imagenData);
                console.log('[EppImagenManager] ✅ Imagen cargada en memoria:', imagenData);
            };

            reader.readAsDataURL(archivo);
        } catch (error) {
            alert('Error procesando imagen: ' + error.message);
        }
    }

    /**
     * Eliminar imagen
     */
    async eliminarImagen(imagenId) {
        // Mostrar confirmación elegante con SweetAlert
        const result = await Swal.fire({
            title: '⚠️ Eliminar Imagen',
            text: '¿Estás seguro de que deseas eliminar esta imagen? Se eliminará de la base de datos y del servidor.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '✓ Sí, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            zIndex: 99999,
            didOpen: (modal) => {
                // Asegurar que el modal esté bien posicionado
                const popup = modal.querySelector('.swal2-popup');
                if (popup) {
                    popup.style.zIndex = '99999';
                }
                const backdrop = document.querySelector('.swal2-backdrop-show');
                if (backdrop) {
                    backdrop.style.zIndex = '99998';
                }
            }
        });

        if (!result.isConfirmed) {

            return;
        }

        try {
            // Llamar al API para eliminar del servidor y base de datos
            await this.apiService.eliminarImagen(imagenId);

            this.stateManager.eliminarImagenSubida(imagenId);
            this.modalManager.eliminarImagenUI(imagenId);

            // Mostrar confirmación de éxito
            Swal.fire({
                title: '✓ Eliminada',
                text: 'La imagen ha sido eliminada correctamente',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK',
                zIndex: 99999
            });


        } catch (error) {

            
            // Mostrar error
            Swal.fire({
                title: ' Error',
                text: 'No se pudo eliminar la imagen: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'OK',
                zIndex: 99999
            });
        }
    }

    /**
     * Obtener imágenes cargadas
     */
    obtenerImagenesCargadas() {
        return this.stateManager.getImagenesSubidas();
    }

    /**
     * Limpiar imágenes
     */
    limpiarImagenes() {
        this.stateManager.limpiarImagenesSubidas();
        this.modalManager.limpiarImagenes();
    }
}

// Exportar instancia global
window.eppImagenManager = null; // Se inicializa después
