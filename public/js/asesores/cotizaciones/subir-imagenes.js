/**
 * SISTEMA DE SUBIDA DE IMÁGENES - NUEVA ARQUITECTURA DDD
 * 
 * Usa FormData (no Base64) para mejor rendimiento
 * - 33% menos datos transmitidos
 * - Más rápido
 * - Escalable
 * - Estándar de la industria
 */

/**
 * Subir imagen individual a cotización
 * 
 * @param {File} archivo - Archivo a subir
 * @param {number} cotizacionId - ID de la cotización
 * @param {number} prendaId - ID de la prenda
 * @param {string} tipo - Tipo: 'prenda', 'tela', 'logo', 'bordado', 'estampado'
 * @returns {Promise<{success: boolean, ruta: string}>}
 */
async function subirImagenCotizacion(archivo, cotizacionId, prendaId, tipo) {
    console.log('📸 Subiendo imagen a cotización', {
        archivo: archivo.name,
        cotizacion_id: cotizacionId,
        prenda_id: prendaId,
        tipo: tipo,
        tamaño: (archivo.size / 1024).toFixed(2) + ' KB'
    });

    try {
        // Validar archivo
        if (!archivo) {
            throw new Error('No se proporcionó archivo');
        }

        if (archivo.size > 5 * 1024 * 1024) {
            throw new Error('Archivo demasiado grande (máximo 5 MB)');
        }

        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!tiposPermitidos.includes(archivo.type)) {
            throw new Error('Tipo de archivo no permitido');
        }

        // Crear FormData
        const formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('prenda_id', prendaId);
        formData.append('tipo', tipo);

        // Enviar
        const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error al subir imagen');
        }

        if (data.success) {
            console.log('✅ Imagen subida exitosamente', {
                ruta: data.data.ruta,
                tamaño: (archivo.size / 1024).toFixed(2) + ' KB'
            });

            return {
                success: true,
                ruta: data.data.ruta
            };
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('❌ Error al subir imagen', {
            error: error.message,
            archivo: archivo.name,
            cotizacion_id: cotizacionId
        });

        throw error;
    }
}

/**
 * Subir múltiples imágenes
 * 
 * @param {File[]} archivos - Array de archivos
 * @param {number} cotizacionId - ID de la cotización
 * @param {number} prendaId - ID de la prenda
 * @param {string} tipo - Tipo de imagen
 * @returns {Promise<{success: boolean, rutas: string[]}>}
 */
async function subirMultiplesImagenes(archivos, cotizacionId, prendaId, tipo) {
    console.log('📸 Subiendo múltiples imágenes', {
        cantidad: archivos.length,
        cotizacion_id: cotizacionId,
        prenda_id: prendaId,
        tipo: tipo
    });

    const rutas = [];
    const errores = [];

    for (let i = 0; i < archivos.length; i++) {
        try {
            const resultado = await subirImagenCotizacion(
                archivos[i],
                cotizacionId,
                prendaId,
                tipo
            );

            if (resultado.success) {
                rutas.push(resultado.ruta);
            }
        } catch (error) {
            console.error(`❌ Error subiendo imagen ${i + 1}`, error.message);
            errores.push({
                archivo: archivos[i].name,
                error: error.message
            });
        }
    }

    console.log('✅ Subida de múltiples imágenes completada', {
        exitosas: rutas.length,
        fallidas: errores.length,
        rutas: rutas
    });

    return {
        success: errores.length === 0,
        rutas: rutas,
        errores: errores
    };
}

/**
 * Manejador de drop de archivos para imágenes
 * 
 * @param {DragEvent} event - Evento de drop
 * @param {number} cotizacionId - ID de la cotización
 * @param {number} prendaId - ID de la prenda
 * @param {string} tipo - Tipo de imagen
 * @param {Function} callback - Callback con resultado
 */
function manejarDropImagenes(event, cotizacionId, prendaId, tipo, callback) {
    event.preventDefault();
    event.stopPropagation();

    const archivos = event.dataTransfer.files;

    if (archivos.length === 0) {
        console.warn('⚠️ No se proporcionaron archivos');
        return;
    }

    console.log('📁 Archivos detectados en drop:', archivos.length);

    subirMultiplesImagenes(Array.from(archivos), cotizacionId, prendaId, tipo)
        .then(resultado => {
            if (callback) {
                callback(resultado);
            }
        })
        .catch(error => {
            console.error('❌ Error en manejador de drop', error);
            if (callback) {
                callback({
                    success: false,
                    error: error.message
                });
            }
        });
}

/**
 * Manejador de input file para imágenes
 * 
 * @param {Event} event - Evento del input
 * @param {number} cotizacionId - ID de la cotización
 * @param {number} prendaId - ID de la prenda
 * @param {string} tipo - Tipo de imagen
 * @param {Function} callback - Callback con resultado
 */
function manejarInputImagenes(event, cotizacionId, prendaId, tipo, callback) {
    const archivos = event.target.files;

    if (archivos.length === 0) {
        console.warn('⚠️ No se seleccionaron archivos');
        return;
    }

    console.log('📁 Archivos seleccionados:', archivos.length);

    subirMultiplesImagenes(Array.from(archivos), cotizacionId, prendaId, tipo)
        .then(resultado => {
            if (callback) {
                callback(resultado);
            }
            // Limpiar input
            event.target.value = '';
        })
        .catch(error => {
            console.error('❌ Error en manejador de input', error);
            if (callback) {
                callback({
                    success: false,
                    error: error.message
                });
            }
        });
}

/**
 * Mostrar progreso de subida
 * 
 * @param {string} mensaje - Mensaje a mostrar
 * @param {number} porcentaje - Porcentaje de progreso (0-100)
 */
function mostrarProgresoSubida(mensaje, porcentaje) {
    const elemento = document.getElementById('progreso-subida');

    if (!elemento) {
        console.warn('⚠️ Elemento de progreso no encontrado');
        return;
    }

    elemento.style.display = 'block';
    elemento.innerHTML = `
        <div style="padding: 15px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <p style="margin: 0 0 10px 0; font-weight: 500; color: #1e40af;">${mensaje}</p>
            <div style="width: 100%; height: 8px; background: #e0e7ff; border-radius: 4px; overflow: hidden;">
                <div style="width: ${porcentaje}%; height: 100%; background: #3b82f6; transition: width 0.3s ease;"></div>
            </div>
            <p style="margin: 8px 0 0 0; font-size: 0.85rem; color: #666;">${porcentaje}%</p>
        </div>
    `;
}

/**
 * Ocultar progreso de subida
 */
function ocultarProgresoSubida() {
    const elemento = document.getElementById('progreso-subida');
    if (elemento) {
        elemento.style.display = 'none';
    }
}

// Exportar funciones globales
window.subirImagenCotizacion = subirImagenCotizacion;
window.subirMultiplesImagenes = subirMultiplesImagenes;
window.manejarDropImagenes = manejarDropImagenes;
window.manejarInputImagenes = manejarInputImagenes;
window.mostrarProgresoSubida = mostrarProgresoSubida;
window.ocultarProgresoSubida = ocultarProgresoSubida;

console.log('✅ Sistema de subida de imágenes (FormData) inicializado');
