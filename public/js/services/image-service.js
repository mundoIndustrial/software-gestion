/**
 * Servicio centralizado para gestión de imágenes
 * Maneja upload, eliminación y preview de imágenes para pedidos
 */

class ImageService {
    constructor() {
        this.baseUrl = '/api/pedidos';
        this.csrfToken = this.getCsrfToken();
        this.maxFileSize = 10 * 1024 * 1024; // 10MB
        this.allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    }

    /**
     * Obtener token CSRF
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="_token"]')?.value;
    }

    /**
     * Validar archivo de imagen
     */
    validateImage(file) {
        if (!file) {
            throw new Error('No se seleccionó ningún archivo');
        }

        if (!this.allowedTypes.includes(file.type)) {
            throw new Error('Tipo de archivo no permitido. Use JPG, PNG o WebP');
        }

        if (file.size > this.maxFileSize) {
            throw new Error('El archivo es demasiado grande. Máximo 10MB');
        }

        return true;
    }

    /**
     * Upload de imagen de prenda
     * @param {File} file - Archivo de imagen
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number|null} cotizacionId - ID de cotización (opcional)
     * @returns {Promise<Object>} Datos de la imagen subida
     */
    async uploadPrendaImage(file, prendaIndex, cotizacionId = null) {
        this.validateImage(file);

        const formData = new FormData();
        formData.append('image', file);
        formData.append('prenda_index', prendaIndex);
        if (cotizacionId) {
            formData.append('cotizacion_id', cotizacionId);
        }

        try {
            const response = await fetch(`${this.baseUrl}/upload-imagen-prenda`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al subir la imagen');
            }

            return data.data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Upload de imagen de tela
     * @param {File} file - Archivo de imagen
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     * @param {number|null} telaId - ID de la tela (opcional)
     * @returns {Promise<Object>} Datos de la imagen subida
     */
    async uploadTelaImage(file, prendaIndex, telaIndex, telaId = null) {
        this.validateImage(file);

        const formData = new FormData();
        formData.append('image', file);
        formData.append('prenda_index', prendaIndex);
        formData.append('tela_index', telaIndex);
        if (telaId) {
            formData.append('tela_id', telaId);
        }

        try {
            const response = await fetch(`${this.baseUrl}/upload-imagen-tela`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al subir la imagen');
            }

            return data.data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Upload de imagen de logo
     * @param {File} file - Archivo de imagen
     * @param {number|null} logoCotizacionId - ID del logo cotización (opcional)
     * @returns {Promise<Object>} Datos de la imagen subida
     */
    async uploadLogoImage(file, logoCotizacionId = null) {
        this.validateImage(file);

        const formData = new FormData();
        formData.append('image', file);
        if (logoCotizacionId) {
            formData.append('logo_cotizacion_id', logoCotizacionId);
        }

        try {
            const response = await fetch(`${this.baseUrl}/upload-imagen-logo`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al subir la imagen');
            }

            return data.data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Upload de imagen de reflectivo
     * @param {File} file - Archivo de imagen
     * @param {number|null} reflectivoId - ID del reflectivo (opcional)
     * @returns {Promise<Object>} Datos de la imagen subida
     */
    async uploadReflectivoImage(file, reflectivoId = null) {
        this.validateImage(file);

        const formData = new FormData();
        formData.append('image', file);
        if (reflectivoId) {
            formData.append('reflectivo_id', reflectivoId);
        }

        try {
            const response = await fetch(`${this.baseUrl}/upload-imagen-reflectivo`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al subir la imagen');
            }

            return data.data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Upload múltiple de imágenes
     * @param {FileList|Array<File>} files - Archivos de imagen
     * @param {string} tipo - Tipo de imagen (prenda, tela, logo, reflectivo)
     * @param {Object} options - Opciones adicionales (prendaIndex, telaIndex, etc.)
     * @returns {Promise<Array>} Array de datos de imágenes subidas
     */
    async uploadMultiple(files, tipo, options = {}) {
        const filesArray = Array.from(files);
        
        // Validar todos los archivos primero
        filesArray.forEach(file => this.validateImage(file));

        const formData = new FormData();
        filesArray.forEach(file => {
            formData.append('images[]', file);
        });
        formData.append('tipo', tipo);

        // Agregar opciones adicionales
        if (options.prendaIndex !== undefined) {
            formData.append('prenda_index', options.prendaIndex);
        }
        if (options.telaIndex !== undefined) {
            formData.append('tela_index', options.telaIndex);
        }

        try {
            const response = await fetch(`${this.baseUrl}/upload-imagenes-multiple`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al subir las imágenes');
            }

            return data.data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Eliminar imagen
     * @param {Object} imagePaths - Rutas de la imagen a eliminar
     * @returns {Promise<Object>} Resultado de la eliminación
     */
    async deleteImage(imagePaths) {
        try {
            const response = await fetch(`${this.baseUrl}/eliminar-imagen`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    ruta_webp: imagePaths.ruta_webp || imagePaths.webp,
                    ruta_original: imagePaths.ruta_original || imagePaths.original,
                    thumbnail: imagePaths.thumbnail
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al eliminar la imagen');
            }

            return data;
        } catch (error) {

            throw error;
        }
    }

    /**
     * Crear preview de imagen local (antes de subir)
     * @param {File} file - Archivo de imagen
     * @returns {Promise<string>} URL del preview
     */
    createPreview(file) {
        return new Promise((resolve, reject) => {
            if (!file.type.startsWith('image/')) {
                reject(new Error('El archivo no es una imagen'));
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => reject(new Error('Error al leer el archivo'));
            reader.readAsDataURL(file);
        });
    }

    /**
     * Mostrar notificación de éxito
     */
    showSuccess(message, count = 1) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Imágenes subidas',
                text: `${count} imagen${count !== 1 ? 'es' : ''} ${message}`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    /**
     * Mostrar notificación de error
     */
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert('Error: ' + message);
        }
    }

    /**
     * Mostrar notificación de advertencia
     */
    showWarning(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: message,
                confirmButtonColor: '#ffc107'
            });
        } else {
            alert('Advertencia: ' + message);
        }
    }
}

// Exportar instancia global
window.ImageService = new ImageService();
