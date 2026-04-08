/**
 * MiddlewareGuardadoPrenda - Interceptor para aplicar cambios de procesos antes de guardar prenda
 * 
 * Responsabilidad: 
 * Cuando el usuario hace click en "Guardar Cambios" de una prenda editada,
 * si hay procesos que fueron editados:
 * 1. Aplicar cambios de procesos editados (PATCH individual)
 * 2. Esperar a que terminen
 * 3. LUEGO guardar la prenda normal
 */

class MiddlewareGuardadoPrenda {
    constructor() {
        this.esperandoActualizaciones = false;
    }

    /**
     * Interceptar guardado de prenda
     * Verificar si hay procesos editados y aplicarlos primero
     * 
     * @param {number} prendaId - ID de la prenda a guardar
     * @param {function} guardarPrendaOriginal - Función original de guardado
     * @returns {Promise} 
     */
    async interceptarGuardado(prendaId, guardarPrendaOriginal) {
        console.log(' [MIDDLEWARE-GUARDADO] Interceptando guardado de prenda:', {
            prendaId,
            hayGestorEdicion: !!window.gestorEditacionProcesos
        });

        try {
            // Obtener procesos editados
            const procesosEditados = this.obtenerProcesosEditados();

            console.log(' [MIDDLEWARE-GUARDADO] Procesos editados encontrados:', {
                cantidad: procesosEditados.length,
                tipos: procesosEditados.map(p => p.tipo)
            });

            // Si hay procesos editados, actualizarlos en el servidor PRIMERO
            if (procesosEditados.length > 0) {
                console.log(' [MIDDLEWARE-GUARDADO] Aplicando cambios de procesos editados...');
                await this.aplicarCambiosProcesos(prendaId, procesosEditados);
                console.log(' [MIDDLEWARE-GUARDADO] Cambios de procesos aplicados');
            }

            // Ahora guardar la prenda normal
            console.log(' [MIDDLEWARE-GUARDADO] Guardando prenda...');
            const resultado = await guardarPrendaOriginal();
            
            // Limpiar registro de procesos editados
            this.limpiarProcesosEditados();

            console.log(' [MIDDLEWARE-GUARDADO] Prenda guardada exitosamente');
            return resultado;

        } catch (error) {
            console.error(' [MIDDLEWARE-GUARDADO] Error en proceso de guardado:', error);
            throw error;
        }
    }

    /**
     * Obtener procesos que fueron editados
     */
    obtenerProcesosEditados() {
        if (!window.gestorEditacionProcesos) {
            return [];
        }
        return window.gestorEditacionProcesos.obtenerProcesosEditados();
    }

    /**
     * Aplicar cambios de procesos editados al servidor
     * Ejecuta PATCH individual para cada proceso
     * 
     * @param {number} prendaId - ID de la prenda
     * @param {array} procesosEditados - Array de procesos editados
     */
    async aplicarCambiosProcesos(prendaId, procesosEditados) {
        const resultados = [];
        let errores = [];

        for (const procesoEditado of procesosEditados) {
            try {
                console.log(' [MIDDLEWARE-GUARDADO] Actualizando proceso:', {
                    tipo: procesoEditado.tipo,
                    id: procesoEditado.id,
                    cambios: Object.keys(procesoEditado.cambios)
                });

                // Determinar la ruta correcta según el rol del usuario
                let urlPatch = `/api/prendas-pedido/${prendaId}/procesos/${procesoEditado.id}`;
                
                // Si tenemos información de que estamos en contexto de supervisor, usar ruta alternativa
                if (window.usuarioAutenticado && window.usuarioAutenticado.rol === 'supervisor_pedidos') {
                    urlPatch = `/api/supervisor-pedidos/prendas/${prendaId}/procesos/${procesoEditado.id}`;
                    console.log(' [MIDDLEWARE-GUARDADO] Usando ruta de supervisor-pedidos:', urlPatch);
                }

                // Hacer PATCH al servidor
                const response = await fetch(
                    urlPatch,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            tipo_proceso_id: procesoEditado.tipo_proceso_id,
                            tipo: procesoEditado.tipo,
                            ...procesoEditado.cambios
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const resultado = await response.json();

                console.log(' [MIDDLEWARE-GUARDADO] Proceso actualizado:', {
                    tipo: procesoEditado.tipo,
                    respuesta: resultado
                });

                resultados.push({
                    tipo: procesoEditado.tipo,
                    éxito: true,
                    resultado
                });

            } catch (error) {
                console.error(` Error actualizando proceso ${procesoEditado.tipo}:`, error);
                errores.push({
                    tipo: procesoEditado.tipo,
                    error: error.message
                });

                resultados.push({
                    tipo: procesoEditado.tipo,
                    éxito: false,
                    error: error.message
                });
            }
        }

        // Si hubo errores, lanzar excepción
        if (errores.length > 0) {
            const mensajeError = errores.map(e => `${e.tipo}: ${e.error}`).join(', ');
            throw new Error(`Error actualizando procesos: ${mensajeError}`);
        }

        console.log(' [MIDDLEWARE-GUARDADO] Todos los procesos actualizados:', {
            exitosos: resultados.length,
            resultados
        });

        return resultados;
    }

    /**
     * Limpiar registro de procesos editados después de guardar
     */
    limpiarProcesosEditados() {
        if (window.gestorEditacionProcesos) {
            window.gestorEditacionProcesos.limpiar();
            console.log(' [MIDDLEWARE-GUARDADO] Registro de procesos editados limpiado');
        }
    }
}

// Crear instancia global
window.middlewareGuardadoPrenda = new MiddlewareGuardadoPrenda();
window.MiddlewareGuardadoPrenda = MiddlewareGuardadoPrenda;
