/**
 * ServicioProcesos - Maneja actualizaciones de procesos en el backend
 * 
 * Responsabilidad: 
 * - Enviar cambios de procesos editados al servidor
 * - Mantener separadas las actualizaciones de procesos vs prenda
 * - Aplicar cambios de forma atómica (un proceso a la vez)
 */

class ServicioProcesos {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api';
    }

    /**
     * Actualizar un proceso específico de una prenda
     * 
     * @param {number} prendaId - ID de la prenda que contiene el proceso
     * @param {object} datosActualizacion - {id, tipo_proceso_id, tipo, cambios}
     * @returns {Promise} Resultado de la actualización
     */
    async actualizarProceso(prendaId, datosActualizacion) {
        console.log(' [SERVICIO-PROCESOS] Actualizando proceso:', {
            prendaId,
            procesoId: datosActualizacion.id,
            cambios: Object.keys(datosActualizacion.cambios || {})
        });

        try {
            const response = await fetch(`${this.baseUrl}/prendas-pedido/${prendaId}/procesos/${datosActualizacion.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    tipo_proceso_id: datosActualizacion.tipo_proceso_id,
                    tipo: datosActualizacion.tipo,
                    ...datosActualizacion.cambios
                })
            });

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const resultado = await response.json();
            console.log(' [SERVICIO-PROCESOS] Proceso actualizado:', resultado);
            return resultado;

        } catch (error) {
            console.error(' [SERVICIO-PROCESOS] Error actualizando proceso:', error);
            throw error;
        }
    }

    /**
     * Actualizar múltiples procesos de una prenda
     * Se ejecuta de forma secuencial para mantener atomicidad
     * 
     * @param {number} prendaId - ID de la prenda
     * @param {array} procesosActualizacion - Array de {id, tipo_proceso_id, tipo, cambios}
     * @returns {Promise<array>} Resultados de todas las actualizaciones
     */
    async actualizarMultiplesProcesos(prendaId, procesosActualizacion) {
        console.log(' [SERVICIO-PROCESOS] Actualizando múltiples procesos:', {
            prendaId,
            cantidad: procesosActualizacion.length,
            tipos: procesosActualizacion.map(p => p.tipo)
        });

        const resultados = [];
        let errores = [];

        // Ejecutar actualizaciones de forma secuencial
        for (const datosActualizacion of procesosActualizacion) {
            try {
                const resultado = await this.actualizarProceso(prendaId, datosActualizacion);
                resultados.push({
                    tipo: datosActualizacion.tipo,
                    éxito: true,
                    resultado
                });
            } catch (error) {
                console.error(` Error actualizando proceso ${datosActualizacion.tipo}:`, error);
                errores.push({
                    tipo: datosActualizacion.tipo,
                    error: error.message
                });
                resultados.push({
                    tipo: datosActualizacion.tipo,
                    éxito: false,
                    error: error.message
                });
            }
        }

        if (errores.length > 0) {
            console.warn(' [SERVICIO-PROCESOS] Algunos procesos fallaron:', errores);
        }

        console.log(' [SERVICIO-PROCESOS] Actualización completada:', {
            exitosos: resultados.filter(r => r.éxito).length,
            fallidos: resultados.filter(r => !r.éxito).length
        });

        return {
            exitosos: resultados.filter(r => r.éxito),
            fallidos: resultados.filter(r => !r.éxito),
            total: resultados.length
        };
    }

    /**
     * Obtener procesos editados desde el gestor
     */
    obtenerProcesosEditados() {
        if (!window.gestorEditacionProcesos) {
            console.warn(' [SERVICIO-PROCESOS] No existe gestorEditacionProcesos global');
            return [];
        }

        return window.gestorEditacionProcesos.obtenerProcesosEditados();
    }

    /**
     * Validar que los cambios sean correctos antes de enviar
     */
    validarCambios(datosActualizacion) {
        const { cambios, id, tipo } = datosActualizacion;

        if (!id) {
            throw new Error('Falta ID del proceso');
        }

        if (!tipo) {
            throw new Error('Falta tipo de proceso');
        }

        // Validar ubicaciones si existen
        if (cambios.ubicaciones !== undefined) {
            if (!Array.isArray(cambios.ubicaciones)) {
                throw new Error('Ubicaciones debe ser un array');
            }
        }

        // Validar imágenes si existen
        if (cambios.imagenes !== undefined) {
            if (!Array.isArray(cambios.imagenes)) {
                throw new Error('Imágenes debe ser un array');
            }
        }

        // Validar observaciones si existen
        if (cambios.observaciones !== undefined) {
            if (typeof cambios.observaciones !== 'string') {
                throw new Error('Observaciones debe ser texto');
            }
        }

        // Validar tallas si existen
        if (cambios.tallas !== undefined) {
            if (typeof cambios.tallas !== 'object') {
                throw new Error('Tallas debe ser un objeto');
            }
        }

        return true;
    }

    /**
     * Limpiar registro de procesos editados después de guardar
     */
    limpiarProcesosEditados() {
        if (window.gestorEditacionProcesos) {
            window.gestorEditacionProcesos.limpiar();
        }
    }
}

// Crear instancia global
window.servicioProcesos = new ServicioProcesos();
window.ServicioProcesos = ServicioProcesos;
