/**
 * DeletionService.js - Gestión centralizada de eliminación de recursos
 * 
 * Consolidación de:
 * - eliminarPedido() variantes en pedidos-list.js
 * - eliminarCotizacion() variantes en cotizaciones-index.js
 * - eliminarCliente() en clientes/index.blade.php
 * - eliminarTela() en inventario.js
 * - deleteUser() en users.js
 * 
 * SOLID:
 * - Single Responsibility: Solo manejo de DELETE
 * - DRY: Un solo lugar para la lógica
 */

'use strict';

class DeletionService {
    // Configuración por defecto
    static config = {
        showLoadingSpinner: true,
        reloadOnSuccess: false,
        animationDuration: 300
    };

    // ============================================================
    // CONFIGURACIÓN
    // ============================================================

    static setConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }

    // ============================================================
    // MÉTODO PRINCIPAL: Eliminar recurso genérico
    // ============================================================

    /**
     * Eliminar un recurso genérico con confirmación y manejo de errores
     * 
     * @param {Object} config - Configuración de la eliminación
     * @param {string} config.endpoint - URL del endpoint DELETE
     * @param {string} config.resourceName - Nombre del recurso (ej: "Pedido", "Cliente")
     * @param {string|number} config.identifier - Identificador del recurso (ej: número de pedido)
     * @param {Function} config.onSuccess - Callback de éxito (opcional)
     * @param {Function} config.onError - Callback de error (opcional)
     * @param {boolean} config.reloadPage - Recargar página después (default: false)
     * 
     * @example
     * DeletionService.eliminar({
     *     endpoint: `/asesores/pedidos/${pedidoId}`,
     *     resourceName: 'Pedido',
     *     identifier: numeroPedido,
     *     onSuccess: () => {
     *         UI.toastExito('Pedido eliminado');
     *         setTimeout(() => location.reload(), 1000);
     *     }
     * });
     */
    static async eliminar(config) {
        const {
            endpoint,
            resourceName,
            identifier,
            onSuccess,
            onError,
            reloadPage = this.config.reloadOnSuccess
        } = config;

        // ✅ Validaciones
        if (!endpoint) {
            console.error('❌ [DeletionService] endpoint es requerido');
            UI.toastError('Error: configuración incompleta');
            return;
        }

        if (!resourceName) {
            console.error('❌ [DeletionService] resourceName es requerido');
            return;
        }

        // ✅ 1. Solicitar confirmación
        console.log(`🗑️ [DeletionService] Confirmando eliminación de ${resourceName} #${identifier}`);
        
        const confirmed = await UI.confirmarEliminacion(resourceName, identifier);
        if (!confirmed) {
            console.log(`⏸️ [DeletionService] Eliminación cancelada por usuario`);
            return;
        }

        // ✅ 2. Mostrar estado de carga
        UI.cargando(
            `Eliminando ${resourceName}...`,
            `Por favor espera mientras se elimina ${resourceName}`
        );

        try {
            // ✅ 3. Hacer petición DELETE
            console.log(`📤 [DeletionService] DELETE ${endpoint}`);
            
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': UI.getCsrfToken(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            // ✅ 4. Manejar respuesta
            if (response.ok && data.success) {
                console.log(`✅ [DeletionService] ${resourceName} eliminado correctamente`);
                
                // Cerrar modal de carga
                Swal.close();
                
                // Mostrar éxito
                UI.toastExito(`${resourceName} eliminado correctamente`);
                
                // Ejecutar callback de éxito
                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                }
                
                // Recargar página si es necesario
                if (reloadPage) {
                    setTimeout(() => location.reload(), 1000);
                }
                
                return { success: true, data };
                
            } else {
                // Error desde el servidor
                const mensaje = data.message || `Error al eliminar ${resourceName}`;
                throw new Error(mensaje);
            }

        } catch (error) {
            console.error(`❌ [DeletionService] Error:`, error);
            
            // Cerrar modal de carga
            Swal.close();
            
            // Mostrar error
            UI.error(
                `Error al eliminar ${resourceName}`,
                error.message || 'Error de conexión con el servidor'
            );
            
            // Ejecutar callback de error
            if (typeof onError === 'function') {
                onError(error);
            }
            
            return { success: false, error };
        }
    }

    // ============================================================
    // MÉTODOS ESPECÍFICOS (PARA FACILITAR USO)
    // ============================================================

    /**
     * Eliminar un pedido
     * @param {number} pedidoId - ID del pedido
     * @param {string|number} numeroPedido - Número del pedido (para mostrar)
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarPedido(pedidoId, numeroPedido, options = {}) {
        return this.eliminar({
            endpoint: `/asesores/pedidos-produccion/${pedidoId}`,
            resourceName: 'Pedido',
            identifier: numeroPedido,
            reloadPage: true,
            ...options
        });
    }

    /**
     * Eliminar una cotización
     * @param {number} cotizacionId - ID de la cotización
     * @param {string|number} numeroCotizacion - Número de la cotización
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarCotizacion(cotizacionId, numeroCotizacion, options = {}) {
        return this.eliminar({
            endpoint: `/asesores/cotizaciones/${cotizacionId}`,
            resourceName: 'Cotización',
            identifier: numeroCotizacion,
            reloadPage: true,
            ...options
        });
    }

    /**
     * Eliminar un cliente
     * @param {number} clienteId - ID del cliente
     * @param {string} nombreCliente - Nombre del cliente
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarCliente(clienteId, nombreCliente, options = {}) {
        return this.eliminar({
            endpoint: `/asesores/clientes/${clienteId}`,
            resourceName: 'Cliente',
            identifier: nombreCliente,
            reloadPage: true,
            ...options
        });
    }

    /**
     * Eliminar una tela
     * @param {number} telaId - ID de la tela
     * @param {string} telaNombre - Nombre de la tela
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarTela(telaId, telaNombre, options = {}) {
        return this.eliminar({
            endpoint: `/asesores/telas/${telaId}`,
            resourceName: 'Tela',
            identifier: telaNombre,
            reloadPage: false,
            onSuccess: () => {
                // Remover fila de la tabla si existe
                const row = document.querySelector(`tr[data-tela-id="${telaId}"]`);
                if (row) {
                    row.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => row.remove(), 300);
                }
            },
            ...options
        });
    }

    /**
     * Eliminar un usuario
     * @param {number} userId - ID del usuario
     * @param {string} userEmail - Email del usuario
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarUsuario(userId, userEmail, options = {}) {
        return this.eliminar({
            endpoint: `/users/${userId}`,
            resourceName: 'Usuario',
            identifier: userEmail,
            reloadPage: true,
            ...options
        });
    }

    /**
     * Eliminar un item/prenda del pedido
     * @param {number} pedidoId - ID del pedido
     * @param {number} itemId - ID del item
     * @param {string} itemNombre - Nombre del item
     * @param {Object} options - Opciones adicionales
     */
    static async eliminarItem(pedidoId, itemId, itemNombre, options = {}) {
        return this.eliminar({
            endpoint: `/asesores/pedidos/${pedidoId}/items/${itemId}`,
            resourceName: itemNombre || 'Prenda',
            identifier: itemId,
            reloadPage: false,
            onSuccess: () => {
                // Remover card del item si existe
                const card = document.querySelector(`[data-item-id="${itemId}"]`);
                if (card) {
                    card.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => card.remove(), 300);
                }
            },
            ...options
        });
    }

    // ============================================================
    // ELIMINACIÓN MÚLTIPLE
    // ============================================================

    /**
     * Eliminar varios recursos
     * @param {Array} recursos - Array de objetos con { id, nombre, endpoint }
     * @param {string} tipoRecurso - Tipo de recurso (ej: "Pedidos")
     * @param {Function} onSuccess - Callback de éxito
     */
    static async eliminarMultiples(recursos, tipoRecurso, onSuccess) {
        if (!recursos || recursos.length === 0) {
            UI.toastAdvertencia('No hay recursos seleccionados para eliminar');
            return;
        }

        // Confirmación
        const resultado = await UI.confirmar({
            titulo: `🗑️ Eliminar ${tipoRecurso}`,
            mensaje: `¿Estás seguro de que deseas eliminar ${recursos.length} ${tipoRecurso.toLowerCase()}? Esta acción no se puede deshacer.`,
            icono: 'warning',
            confirmText: 'Sí, eliminar todos',
            dangerMode: true
        });

        if (!resultado.isConfirmed) return;

        // Procesamiento
        UI.cargando(
            `Eliminando ${tipoRecurso}...`,
            `Procesando ${recursos.length} recurso(s)...`
        );

        const resultados = {
            exitosos: 0,
            fallidos: 0,
            errores: []
        };

        // Eliminar cada recurso
        for (const recurso of recursos) {
            try {
                const response = await fetch(recurso.endpoint, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': UI.getCsrfToken(),
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    resultados.exitosos++;
                } else {
                    resultados.fallidos++;
                    resultados.errores.push(`${recurso.nombre}: ${data.message}`);
                }
            } catch (error) {
                resultados.fallidos++;
                resultados.errores.push(`${recurso.nombre}: Error de conexión`);
            }
        }

        // Cerrar modal de carga
        Swal.close();

        // Mostrar resultados
        if (resultados.exitosos > 0) {
            UI.toastExito(`${resultados.exitosos} ${tipoRecurso.toLowerCase()} eliminado(s) correctamente`);
        }

        if (resultados.fallidos > 0) {
            UI.error(
                `Error al eliminar ${resultados.fallidos} ${tipoRecurso.toLowerCase()}`,
                resultados.errores.join('\n')
            );
        }

        // Callback de éxito
        if (typeof onSuccess === 'function' && resultados.exitosos > 0) {
            onSuccess(resultados);
        }

        return resultados;
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Confirmar y ejecutar eliminación sin esperar respuesta
     * (Para operaciones que se procesan en background)
     */
    static async eliminarEnBackground(config) {
        const confirmed = await UI.confirmarEliminacion(
            config.resourceName,
            config.identifier
        );

        if (!confirmed) return;

        UI.toastInfo(`${config.resourceName} será eliminado en segundo plano...`);

        // Hacer la petición sin esperar
        fetch(config.endpoint, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': UI.getCsrfToken(),
                'Content-Type': 'application/json'
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  UI.toastExito(`${config.resourceName} eliminado`);
                  config.onSuccess?.(data);
              } else {
                  UI.toastError(`Error: ${data.message}`);
                  config.onError?.(data);
              }
          })
          .catch(error => {
              UI.toastError('Error de conexión');
              config.onError?.(error);
          });
    }
}

// ============================================================
// EXPONER GLOBALMENTE
// ============================================================

window.Deletion = DeletionService;

console.log('✅ DeletionService cargado y disponible como window.Deletion');
