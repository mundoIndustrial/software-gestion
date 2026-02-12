/**
 * ================================================
 * CLIPBOARD SERVICE
 * ================================================
 * 
 * Servicio para manejar operaciones del portapapeles
 * Soporta lectura y escritura, con fallbacks para diferentes navegadores
 * 
 * @class ClipboardService
 */

class ClipboardService {
    constructor() {
        this.soportado = this._verificarSoporte();
        this.tiposImagen = [
            'image/png',
            'image/jpeg', 
            'image/gif',
            'image/webp',
            'image/bmp'
        ];
    }

    /**
     * Verificar si el portapapeles es soportado
     * @returns {boolean} True si es soportado
     * @private
     */
    _verificarSoporte() {
        return !!(navigator.clipboard && navigator.clipboard.read && navigator.clipboard.write);
    }

    /**
     * Leer imágenes del portapapeles
     * @param {Object} opciones - Opciones de lectura
     * @returns {Promise<File[]>} Array de archivos de imagen
     */
    async leerImagenes(opciones = {}) {
        const config = {
            maxArchivos: 1,
            tiposPermitidos: this.tiposImagen,
            ...opciones
        };

        try {
            if (!this.soportado) {
                throw new Error('Clipboard API no soportada en este navegador');
            }

            const items = await navigator.clipboard.read();
            UIHelperService.log('ClipboardService', `Items encontrados: ${items.length}`);

            const archivos = [];

            for (const item of items) {
                UIHelperService.log('ClipboardService', `Tipos disponibles: ${item.types.join(', ')}`);

                // Filtrar tipos de imagen permitidos
                const tiposImagen = item.types.filter(tipo => 
                    config.tiposPermitidos.some(permitido => tipo.includes(permitido))
                );

                UIHelperService.log('ClipboardService', `Tipos de imagen encontrados: ${tiposImagen.join(', ')}`);

                if (tiposImagen.length > 0) {
                    for (const tipo of tiposImagen) {
                        try {
                            const blob = await item.getType(tipo);
                            UIHelperService.log('ClipboardService', `Blob obtenido: ${blob.type}, ${blob.size} bytes`);

                            // Crear archivo con nombre descriptivo
                            const nombreArchivo = this._generarNombreArchivo(tipo, config.nombreBase || 'pasted-image');
                            const archivo = new File([blob], nombreArchivo, { type: blob.type });

                            archivos.push(archivo);

                            // Limitar al máximo configurado
                            if (archivos.length >= config.maxArchivos) {
                                break;
                            }
                        } catch (error) {
                            UIHelperService.log('ClipboardService', `Error obteniendo blob ${tipo}: ${error.message}`, 'warn');
                        }
                    }

                    // Si ya alcanzamos el límite, salir del loop principal
                    if (archivos.length >= config.maxArchivos) {
                        break;
                    }
                }
            }

            if (archivos.length === 0) {
                throw new Error('No se encontraron imágenes válidas en el portapapeles');
            }

            UIHelperService.log('ClipboardService', `Imágenes leídas exitosamente: ${archivos.length}`);
            return archivos;

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error leyendo portapapeles: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Leer archivos del portapapeles (no solo imágenes)
     * @param {Object} opciones - Opciones de lectura
     * @returns {Promise<File[]>} Array de archivos
     */
    async leerArchivos(opciones = {}) {
        const config = {
            maxArchivos: 1,
            ...opciones
        };

        try {
            if (!this.soportado) {
                throw new Error('Clipboard API no soportada en este navegador');
            }

            const items = await navigator.clipboard.read();
            const archivos = [];

            for (const item of items) {
                for (const tipo of item.types) {
                    if (tipo.startsWith('image/') || tipo.startsWith('text/')) {
                        try {
                            const blob = await item.getType(tipo);
                            const nombreArchivo = this._generarNombreArchivo(tipo, 'pasted-file');
                            const archivo = new File([blob], nombreArchivo, { type: blob.type });

                            archivos.push(archivo);

                            if (archivos.length >= config.maxArchivos) {
                                break;
                            }
                        } catch (error) {
                            UIHelperService.log('ClipboardService', `Error obteniendo blob ${tipo}: ${error.message}`, 'warn');
                        }
                    }

                    if (archivos.length >= config.maxArchivos) {
                        break;
                    }
                }

                if (archivos.length >= config.maxArchivos) {
                    break;
                }
            }

            if (archivos.length === 0) {
                throw new Error('No se encontraron archivos válidos en el portapapeles');
            }

            return archivos;

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error leyendo archivos: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Escribir texto en el portapapeles
     * @param {string} texto - Texto a copiar
     * @returns {Promise<void>}
     */
    async escribirTexto(texto) {
        try {
            if (!this.soportado) {
                throw new Error('Clipboard API no soportada en este navegador');
            }

            await navigator.clipboard.writeText(texto);
            UIHelperService.log('ClipboardService', 'Texto escrito en portapapeles exitosamente');

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error escribiendo texto: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Escribir imagen en el portapapeles
     * @param {Blob|File} imagen - Imagen a copiar
     * @returns {Promise<void>}
     */
    async escribirImagen(imagen) {
        try {
            if (!this.soportado) {
                throw new Error('Clipboard API no soportada en este navegador');
            }

            await navigator.clipboard.write([
                new ClipboardItem({
                    [imagen.type]: imagen
                })
            ]);

            UIHelperService.log('ClipboardService', 'Imagen escrita en portapapeles exitosamente');

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error escribiendo imagen: ${error.message}`, 'error');
            throw error;
        }
    }

    /**
     * Verificar si hay imágenes en el portapapeles
     * @returns {Promise<boolean>} True si hay imágenes
     */
    async hayImagenes() {
        try {
            const items = await navigator.clipboard.read();
            
            for (const item of items) {
                const tieneImagen = item.types.some(tipo => 
                    this.tiposImagen.some(tipoImagen => tipo.includes(tipoImagen))
                );
                
                if (tieneImagen) {
                    return true;
                }
            }
            
            return false;

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error verificando imágenes: ${error.message}`, 'warn');
            return false;
        }
    }

    /**
     * Obtener información del portapapeles sin leer los datos
     * @returns {Promise<Object>} Información disponible
     */
    async obtenerInfo() {
        try {
            if (!this.soportado) {
                return {
                    soportado: false,
                    items: 0,
                    tipos: [],
                    hayImagenes: false
                };
            }

            const items = await navigator.clipboard.read();
            const todosLosTipos = new Set();
            let hayImagenes = false;

            for (const item of items) {
                item.types.forEach(tipo => todosLosTipos.add(tipo));
                
                if (!hayImagenes) {
                    hayImagenes = item.types.some(tipo => 
                        this.tiposImagen.some(tipoImagen => tipo.includes(tipoImagen))
                    );
                }
            }

            return {
                soportado: true,
                items: items.length,
                tipos: Array.from(todosLosTipos),
                hayImagenes
            };

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error obteniendo info: ${error.message}`, 'warn');
            return {
                soportado: false,
                items: 0,
                tipos: [],
                hayImagenes: false,
                error: error.message
            };
        }
    }

    /**
     * Solicitar permisos del portapapeles si es necesario
     * @returns {Promise<boolean>} True si se obtuvieron permisos
     */
    async solicitarPermisos() {
        try {
            if (!this.soportado) {
                return false;
            }

            // Algunos navegadores requieren permisos explícitos
            if (navigator.permissions) {
                const result = await navigator.permissions.query({ name: 'clipboard-read' });
                if (result.state === 'denied') {
                    UIHelperService.log('ClipboardService', 'Permiso de portapapeles denegado', 'warn');
                    return false;
                }
            }

            return true;

        } catch (error) {
            UIHelperService.log('ClipboardService', `Error solicitando permisos: ${error.message}`, 'warn');
            return false;
        }
    }

    /**
     * Generar nombre de archivo basado en el tipo
     * @param {string} tipo - MIME type del archivo
     * @param {string} base - Nombre base
     * @returns {string} Nombre de archivo generado
     * @private
     */
    _generarNombreArchivo(tipo, base) {
        const extension = this._obtenerExtension(tipo);
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
        return `${base}-${timestamp}.${extension}`;
    }

    /**
     * Obtener extensión de archivo basada en MIME type
     * @param {string} tipo - MIME type
     * @returns {string} Extensión del archivo
     * @private
     */
    _obtenerExtension(tipo) {
        const extensiones = {
            'image/png': 'png',
            'image/jpeg': 'jpg',
            'image/jpg': 'jpg',
            'image/gif': 'gif',
            'image/webp': 'webp',
            'image/bmp': 'bmp',
            'text/plain': 'txt',
            'text/html': 'html'
        };

        return extensiones[tipo] || 'bin';
    }

    /**
     * Crear un fallback para leer del portapapeles usando eventos
     * @param {HTMLElement} elemento - Elemento que recibirá el paste
     * @returns {Promise<File[]>} Archivos leídos
     */
    async leerConFallback(elemento) {
        return new Promise((resolve, reject) => {
            // Crear un evento paste temporal
            const pasteEvent = new ClipboardEvent('paste', {
                clipboardData: new DataTransfer()
            });

            // Listener temporal para capturar el resultado
            const tempListener = (e) => {
                elemento.removeEventListener('paste', tempListener);
                
                try {
                    const items = e.clipboardData.items;
                    const archivos = [];

                    for (let i = 0; i < items.length; i++) {
                        const item = items[i];
                        
                        if (item.type.startsWith('image/')) {
                            const file = item.getAsFile();
                            if (file) {
                                archivos.push(file);
                            }
                        }
                    }

                    if (archivos.length > 0) {
                        resolve(archivos);
                    } else {
                        reject(new Error('No se encontraron imágenes en el portapapeles'));
                    }

                } catch (error) {
                    reject(error);
                }
            };

            elemento.addEventListener('paste', tempListener);
            
            // Disparar el evento
            elemento.dispatchEvent(pasteEvent);
            
            // Timeout por si no funciona
            setTimeout(() => {
                elemento.removeEventListener('paste', tempListener);
                reject(new Error('Timeout al leer del portapapeles'));
            }, 1000);
        });
    }

    /**
     * Obtener estado del servicio
     * @returns {Object} Estado actual
     */
    getEstado() {
        return {
            soportado: this.soportado,
            tiposImagen: [...this.tiposImagen]
        };
    }

    /**
     * Limpiar recursos
     */
    destruir() {
        UIHelperService.log('ClipboardService', 'Servicio destruido');
    }
}

// Crear instancia global
window.ClipboardService = new ClipboardService();

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ClipboardService;
}
