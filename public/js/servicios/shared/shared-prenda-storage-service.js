/**
 * 🔒 SharedPrendaStorageService
 * 
 * IMPORTANTE: AISLADO DE COTIZACIONES
 * - Solo maneja imágenes de prendas
 * - NO toca imágenes de cotización
 * - Completamente independiente
 */

class SharedPrendaStorageService {
    constructor(config = {}) {
        this.endpointBase = config.endpointBase || '/api/storage/prendas';
        this.maxFileSize = config.maxFileSize || 5 * 1024 * 1024; // 5MB
        this.allowedMimeTypes = config.allowedMimeTypes || [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/jpg'
        ];

        console.log('[SharedPrendaStorageService] ✓ Inicializado');
    }

    /**
     * Procesar cambios de imágenes
     * Retorna: {agregar: [], eliminar: [], mantener: []}
     */
    async procesarCambiosImagenes(imagenesActuales, imagenesNuevas) {
        console.log('[SharedPrendaStorage] 🖼️ Procesando cambios de imágenes...');

        const cambios = {
            agregar: [],    // Archivos nuevos
            eliminar: [],   // IDs a eliminar
            mantener: []    // URLs a mantener
        };

        if (!Array.isArray(imagenesNuevas)) {
            imagenesNuevas = [];
        }

        // Obtener IDs de nuevas imágenes (existentes)
        const idsNuevos = imagenesNuevas
            .filter(img => img.id && img.id > 0)
            .map(img => img.id);

        // Identificar qué eliminar
        cambios.eliminar = (imagenesActuales || [])
            .filter(img => img.id && !idsNuevos.includes(img.id))
            .map(img => img.id);

        // Identificar qué mantener
        cambios.mantener = imagenesNuevas
            .filter(img => img.id && img.id > 0)
            .map(img => ({
                id: img.id,
                url: img.url || img.ruta,
                ruta_original: img.ruta_original,
                ruta_webp: img.ruta_webp
            }));

        // Identificar qué agregar (sin ID = nuevo)
        cambios.agregar = imagenesNuevas
            .filter(img => (!img.id || img.id <= 0) && (img.archivo || img.file));

        console.log('[SharedPrendaStorage] Cambios:', {
            mantener: cambios.mantener.length,
            agregar: cambios.agregar.length,
            eliminar: cambios.eliminar.length
        });

        return cambios;
    }

    /**
     * Subir nuevas imágenes
     * @param {Array} archivos - Array de File objects
     * @returns {Array} URLs de imágenes subidas
     */
    async subirImagenes(archivos) {
        console.log('[SharedPrendaStorage] 📤 Subiendo imágenes...');

        if (!Array.isArray(archivos) || archivos.length === 0) {
            console.log('[SharedPrendaStorage] Sin archivos para subir');
            return [];
        }

        const urlsSubidas = [];

        for (let i = 0; i < archivos.length; i++) {
            const archivo = archivos[i];

            // Validar archivo
            const validacion = this.validarArchivo(archivo);
            if (!validacion.valido) {
                console.warn(`[SharedPrendaStorage]  Archivo ${i + 1} inválido:`, validacion.mensaje);
                continue;
            }

            try {
                // Crear FormData
                const formData = new FormData();
                formData.append('imagen', archivo);
                formData.append('tipo', 'prenda');
                formData.append('orden', i);

                // Subir
                console.log(`[SharedPrendaStorage] Subiendo: ${archivo.name} (${this.formatoTamaño(archivo.size)})`);

                const response = await fetch(this.endpointBase, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const resultado = await response.json();

                if (!resultado.success || !resultado.data?.url) {
                    throw new Error(resultado.message || 'Respuesta inválida del servidor');
                }

                const url = resultado.data.url;
                urlsSubidas.push({
                    archivo: archivo.name,
                    url: url,
                    ruta_webp: resultado.data.ruta_webp,
                    ruta_original: resultado.data.ruta_original
                });

                console.log(`[SharedPrendaStorage] ✓ Subido: ${url}`);

            } catch (error) {
                console.error(`[SharedPrendaStorage]  Error subiendo ${archivo.name}:`, error);
                // Continuar con el siguiente archivo
            }
        }

        return urlsSubidas;
    }

    /**
     * Eliminar imágenes por ID
     */
    async eliminarImagenes(ids) {
        console.log('[SharedPrendaStorage] 🗑️ Eliminando imágenes:', ids);

        if (!Array.isArray(ids) || ids.length === 0) {
            return { eliminadas: [], fallidas: [] };
        }

        const resultado = {
            eliminadas: [],
            fallidas: []
        };

        for (const id of ids) {
            try {
                const response = await fetch(`${this.endpointBase}/${id}`, {
                    method: 'DELETE'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                resultado.eliminadas.push(id);
                console.log(`[SharedPrendaStorage] ✓ Eliminada imagen ${id}`);

            } catch (error) {
                console.error(`[SharedPrendaStorage]  Error eliminando ${id}:`, error);
                resultado.fallidas.push(id);
            }
        }

        return resultado;
    }

    /**
     * Validar archivo
     */
    validarArchivo(archivo) {
        // Verificar que sea un File
        if (!(archivo instanceof File)) {
            return {
                valido: false,
                mensaje: 'No es un archivo válido'
            };
        }

        // Verificar nombre
        if (!archivo.name || archivo.name.length === 0) {
            return {
                valido: false,
                mensaje: 'Archivo sin nombre'
            };
        }

        // Verificar tamaño
        if (archivo.size > this.maxFileSize) {
            return {
                valido: false,
                mensaje: `Archivo muy grande (máx: ${this.formatoTamaño(this.maxFileSize)})`
            };
        }

        // Verificar tipo MIME
        if (!this.allowedMimeTypes.includes(archivo.type)) {
            return {
                valido: false,
                mensaje: `Tipo de archivo no permitido: ${archivo.type}`
            };
        }

        return { valido: true };
    }

    /**
     * Convertir bytes a formato legible
     */
    formatoTamaño(bytes) {
        const unidades = ['B', 'KB', 'MB', 'GB'];
        let tamaño = bytes;
        let unidad = 0;

        while (tamaño >= 1024 && unidad < unidades.length - 1) {
            tamaño /= 1024;
            unidad++;
        }

        return `${tamaño.toFixed(2)} ${unidades[unidad]}`;
    }

    /**
     * Generar preview desde archivo
     */
    generarPreview(archivo) {
        return new Promise((resolve, reject) => {
            if (!(archivo instanceof File)) {
                reject(new Error('No es un archivo válido'));
                return;
            }

            const reader = new FileReader();

            reader.onload = (e) => {
                resolve({
                    preview: e.target.result,
                    nombre: archivo.name,
                    tamaño: archivo.size,
                    tipo: archivo.type
                });
            };

            reader.onerror = () => {
                reject(new Error('Error leyendo archivo'));
            };

            reader.readAsDataURL(archivo);
        });
    }

    /**
     * Establecer tipos MIME permitidos
     */
    setAllowedMimeTypes(tipos) {
        this.allowedMimeTypes = tipos;
        console.log('[SharedPrendaStorage] Tipos MIME permitidos actualizados:', tipos);
    }

    /**
     * Establecer tamaño máximo
     */
    setMaxFileSize(bytes) {
        this.maxFileSize = bytes;
        console.log('[SharedPrendaStorage] Tamaño máximo establecido:', this.formatoTamaño(bytes));
    }
}

// Exportar
window.SharedPrendaStorageService = SharedPrendaStorageService;
console.log('[SharedPrendaStorageService] 🔐 Cargado (AISLADO DE COTIZACIONES)');
