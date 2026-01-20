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

            // Crear URL local para la imagen
            const reader = new FileReader();
            reader.onload = (e) => {
                const imagenData = {
                    id: Date.now(), // ID temporal basado en timestamp
                    url: e.target.result, // Data URL
                    nombre: archivo.name,
                    archivo: archivo // Guardar el archivo para enviarlo después
                };

                this.stateManager.agregarImagenSubida(imagenData);
                this.modalManager.agregarImagenUI(imagenData);
                console.log('[EppImagenManager] Imagen agregada localmente:', imagenData.id);
            };

            reader.readAsDataURL(archivo);
        } catch (error) {
            console.error('[EppImagenManager] Error procesando imagen:', error);
            alert('Error procesando imagen: ' + error.message);
        }
    }

    /**
     * Eliminar imagen
     */
    async eliminarImagen(imagenId) {
        if (!confirm('¿Eliminar esta imagen?')) return;

        try {
            await this.apiService.eliminarImagen(imagenId);

            this.stateManager.eliminarImagenSubida(imagenId);
            this.modalManager.eliminarImagenUI(imagenId);

            console.log('[EppImagenManager] Imagen eliminada:', imagenId);
        } catch (error) {
            console.error('[EppImagenManager] Error eliminando imagen:', error);
            alert('Error eliminando imagen: ' + error.message);
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
