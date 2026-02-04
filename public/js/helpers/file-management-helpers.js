/**
 * HELPER: Funciones para manejo correcto de File objects en FormData
 * 
 * Propósito: Mantener File objects FUERA de JSON.stringify()
 * Patrón: Extrae Files, mantiene JSON limpio, agrega ambos a FormData correctamente
 */

/**
 * Extrae File objects de un wrapper {file, preview, ...}
 * Soporta múltiples formatos
 */
function extraerFileDeWrapper(wrapper) {
    if (!wrapper) return null;
    
    // Si ya es un File
    if (wrapper instanceof File) return wrapper;
    
    // Si es objeto con propiedad 'file'
    if (wrapper?.file instanceof File) return wrapper.file;
    
    return null;
}

/**
 * Extrae Files de una estructura anidada
 * Retorna array de Files válidos
 */
function extraerFilesDeEstructura(estructura) {
    if (!estructura) return [];
    
    if (Array.isArray(estructura)) {
        return estructura
            .map(item => {
                if (item instanceof File) return item;
                if (item?.file instanceof File) return item.file;
                return null;
            })
            .filter(f => f !== null);
    }
    
    return [];
}

/**
 * Limpia estructura de datos removiendo File objects
 * Dejando la estructura lista para JSON.stringify()
 */
function limpiarParaJSON(objeto) {
    if (objeto === null || objeto === undefined) return objeto;
    
    if (objeto instanceof File) return undefined; // Remover Files
    
    if (Array.isArray(objeto)) {
        return objeto
            .map(item => limpiarParaJSON(item))
            .filter(item => item !== undefined && item !== null);
    }
    
    if (typeof objeto === 'object') {
        const limpio = {};
        for (const [key, value] of Object.entries(objeto)) {
            if (value instanceof File) {
                continue; // Saltear Files
            }
            if (value instanceof Blob) {
                continue; // Saltear Blobs
            }
            if (typeof value === 'object') {
                limpio[key] = limpiarParaJSON(value);
            } else {
                limpio[key] = value;
            }
        }
        return limpio;
    }
    
    return objeto;
}

/**
 * Agrega Files a FormData con estructura de ruta correcta
 * 
 * Ejemplo:
 * addFilesToFormData(formData, items, 'prendas');
 * Genera: formData.append('prendas[0][imagenes][0]', file)
 */
function agregarFilesAFormData(formData, items, nombreCampo = 'prendas') {
    if (!items || !Array.isArray(items)) return;
    
    items.forEach((item, itemIdx) => {
        // Imágenes de prenda
        if (item.imagenes && Array.isArray(item.imagenes)) {
            const files = extraerFilesDeEstructura(item.imagenes);
            files.forEach((file, fileIdx) => {
                if (file instanceof File) {
                    formData.append(`${nombreCampo}[${itemIdx}][imagenes][${fileIdx}]`, file);
                }
            });
        }
        
        // Imágenes de telas
        if (item.telas && Array.isArray(item.telas)) {
            item.telas.forEach((tela, telaIdx) => {
                if (tela.imagenes && Array.isArray(tela.imagenes)) {
                    const files = extraerFilesDeEstructura(tela.imagenes);
                    files.forEach((file, fileIdx) => {
                        if (file instanceof File) {
                            formData.append(`${nombreCampo}[${itemIdx}][telas][${telaIdx}][imagenes][${fileIdx}]`, file);
                        }
                    });
                }
            });
        }
        
        // Imágenes de procesos (reflectivo, bordado, etc)
        if (item.procesos && typeof item.procesos === 'object') {
            Object.entries(item.procesos).forEach(([procesoClave, proceso]) => {
                if (proceso.datos?.imagenes && Array.isArray(proceso.datos.imagenes)) {
                    const files = extraerFilesDeEstructura(proceso.datos.imagenes);
                    files.forEach((file, fileIdx) => {
                        if (file instanceof File) {
                            formData.append(
                                `${nombreCampo}[${itemIdx}][procesos][${procesoClave}][imagenes][${fileIdx}]`,
                                file
                            );
                        }
                    });
                }
            });
        }
    });
}

/**
 * Construye FormData correctamente separando JSON de Files
 * 
 * Uso:
 * const formData = construirFormDataConArchivos(pedidoData);
 * fetch(..., {body: formData})
 */
function construirFormDataConArchivos(pedidoData) {
    const formData = new FormData();
    
    // 1. Limpiar estructura removiendo Files
    const datosLimpios = limpiarParaJSON(pedidoData);
    
    // 2. Agregar JSON limpio
    formData.append('pedido', JSON.stringify(datosLimpios));
    
    // 3. Agregar Files con estructura
    if (datosLimpios.items && Array.isArray(datosLimpios.items)) {
        agregarFilesAFormData(formData, pedidoData.items, 'prendas');
    }
    
    // Log para debugging
    console.info(' FormData construido correctamente', {
        totalArchivos: contarArchivosEnFormData(formData),
        estructura: datosLimpios
    });
    
    return formData;
}

/**
 * Cuenta archivos en FormData (para debugging)
 */
function contarArchivosEnFormData(formData) {
    let count = 0;
    for (const [key, value] of formData.entries()) {
        if (value instanceof File) count++;
    }
    return count;
}
