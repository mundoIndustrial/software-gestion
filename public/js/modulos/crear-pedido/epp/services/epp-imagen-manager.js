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

            // Validar tamano (máximo 5MB)
            const maxSize = 5 * 1024 * 1024;
            if (archivo.size > maxSize) {
                alert('El archivo es demasiado grande (máximo 5MB)');
                return;
            }

            // Guardar archivo directamente en el estado (sin subir)
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
            title: ' Eliminar Imagen',
            text: '¿Estás seguro de que deseas eliminar esta imagen?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '✓ Sí, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            willOpen: (modal) => {
                // Preparar el z-index ANTES de que aparezca
                setTimeout(() => {
                    const container = document.querySelector('.swal2-container');
                    const backdrop = document.querySelector('.swal2-backdrop-show');
                    const popup = document.querySelector('.swal2-popup');
                    
                    if (container) {
                        container.style.setProperty('z-index', '1000005', 'important');
                        container.style.setProperty('position', 'fixed', 'important');
                    }
                    if (backdrop) {
                        backdrop.style.setProperty('z-index', '1000000', 'important');
                    }
                    if (popup) {
                        popup.style.setProperty('z-index', '1000005', 'important');
                        popup.style.setProperty('position', 'relative', 'important');
                    }
                }, 0);
            },
            didOpen: (modal) => {
                // Asegurar nuevamente después de renderizar
                const container = document.querySelector('.swal2-container');
                const backdrop = document.querySelector('.swal2-backdrop-show');
                const popup = document.querySelector('.swal2-popup');
                
                if (container) {
                    container.style.setProperty('z-index', '1000005', 'important');
                    container.style.setProperty('position', 'fixed', 'important');
                }
                if (backdrop) {
                    backdrop.style.setProperty('z-index', '1000000', 'important');
                }
                if (popup) {
                    popup.style.setProperty('z-index', '1000005', 'important');
                }
            }
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            // Detectar si la imagen es local o de BD
            // IDs locales: números grandes (timestamps: 13+ dígitos) o formato EPP-img-X (1670-img-1)
            // IDs de BD: números pequeños
            const esTimestamp = typeof imagenId === 'number' || (typeof imagenId === 'string' && imagenId.match(/^\d{13,}$/));
            const esDOMFormat = typeof imagenId === 'string' && imagenId.includes('-img-');
            const esIdTemporal = esTimestamp || esDOMFormat;
            
            console.log(' [EppImagenManager] Analizando imagenId:', imagenId);
            console.log(' [EppImagenManager] - Es timestamp?:', esTimestamp);
            console.log(' [EppImagenManager] - Es DOM format (XXX-img-X)?:', esDOMFormat);
            console.log(' [EppImagenManager] - Es temporal?:', esIdTemporal);
            
            if (!esIdTemporal) {
                // Solo llamar al API si es una imagen guardada en BD
                console.log(' [EppImagenManager] Imagen de BD, eliminando del servidor');
                try {
                    await this.apiService.eliminarImagen(imagenId);
                } catch (apiError) {
                    console.warn(' [EppImagenManager] Error al eliminar de BD, pero continuando con UI:', apiError.message);
                }
            } else {
                console.log(' [EppImagenManager] Imagen local/temporal, eliminando solo del cliente');
            }

            this.stateManager.eliminarImagenSubida(imagenId);
            
            this.modalManager.eliminarImagenUI(imagenId);

            // Mostrar confirmación de éxito
            await Swal.fire({
                title: '✓ Eliminada',
                text: 'La imagen ha sido eliminada',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK',
                willOpen: (modal) => {
                    setTimeout(() => {
                        const container = document.querySelector('.swal2-container');
                        const backdrop = document.querySelector('.swal2-backdrop-show');
                        const popup = document.querySelector('.swal2-popup');
                        
                        if (container) {
                            container.style.setProperty('z-index', '1000005', 'important');
                            container.style.setProperty('position', 'fixed', 'important');
                        }
                        if (backdrop) {
                            backdrop.style.setProperty('z-index', '1000000', 'important');
                        }
                        if (popup) {
                            popup.style.setProperty('z-index', '1000005', 'important');
                        }
                    }, 0);
                }
            });

        } catch (error) {
            // Mostrar error
            await Swal.fire({
                title: ' Error',
                text: 'No se pudo eliminar la imagen: ' + error.message,
                icon: 'error',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'OK',
                willOpen: (modal) => {
                    setTimeout(() => {
                        const container = document.querySelector('.swal2-container');
                        const backdrop = document.querySelector('.swal2-backdrop-show');
                        const popup = document.querySelector('.swal2-popup');
                        
                        if (container) {
                            container.style.setProperty('z-index', '1000005', 'important');
                            container.style.setProperty('position', 'fixed', 'important');
                        }
                        if (backdrop) {
                            backdrop.style.setProperty('z-index', '1000000', 'important');
                        }
                        if (popup) {
                            popup.style.setProperty('z-index', '1000005', 'important');
                        }
                    }, 0);
                }
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
