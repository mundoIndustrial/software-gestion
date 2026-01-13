/**
 * Image Storage Service
 * Maneja almacenamiento temporal de imágenes antes de enviar al servidor
 */

class ImageStorageService {
    constructor(maxImages = 3) {
        this.maxImages = maxImages;
        this.images = [];
    }

    /**
     * Agregar imagen desde input file
     */
    agregarImagen(file) {
        if (!file || !file.type.startsWith('image/')) {
            throw new Error('El archivo debe ser una imagen válida');
        }

        if (this.images.length >= this.maxImages) {
            throw new Error(`Máximo ${this.maxImages} imágenes permitidas`);
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                this.images.push({
                    data: e.target.result,
                    file: file,
                    nombre: file.name,
                    tamaño: file.size,
                });
                resolve(this.images);
            };

            reader.onerror = () => {
                reject(new Error('Error al leer el archivo'));
            };

            reader.readAsDataURL(file);
        });
    }

    /**
     * Obtener todas las imágenes
     */
    obtenerImagenes() {
        return this.images;
    }

    /**
     * Obtener imagen por índice
     */
    obtenerImagen(index) {
        return this.images[index] || null;
    }

    /**
     * Eliminar imagen por índice
     */
    eliminarImagen(index) {
        if (index < 0 || index >= this.images.length) {
            throw new Error('Índice de imagen inválido');
        }

        this.images.splice(index, 1);
        return this.images;
    }

    /**
     * Obtener cantidad de imágenes
     */
    contar() {
        return this.images.length;
    }

    /**
     * Verificar si hay espacio para más imágenes
     */
    tieneEspacio() {
        return this.images.length < this.maxImages;
    }

    /**
     * Limpiar todas las imágenes
     */
    limpiar() {
        this.images = [];
    }

    /**
     * Convertir a FormData para envío
     */
    toFormData(fieldName = 'imagenes') {
        const formData = new FormData();
        
        this.images.forEach((img, index) => {
            formData.append(`${fieldName}[${index}]`, img.file);
        });

        return formData;
    }

    /**
     * Obtener datos para JSON (solo base64, sin archivos)
     */
    toJSON() {
        return this.images.map(img => ({
            data: img.data,
            nombre: img.nombre,
            tamaño: img.tamaño,
        }));
    }
}

// Crear instancias globales para diferentes tipos de imágenes
window.imagenesTelaStorage = new ImageStorageService(3);
window.imagenesPrendaStorage = new ImageStorageService(3);
window.imagenesReflectivoStorage = new ImageStorageService(3);
