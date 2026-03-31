/**
 * Servicio para eliminacion de procesos en UI y backend.
 * Mantiene API global para compatibilidad.
 */
(function() {
    'use strict';

    globalThis.procesosParaEliminarIds = globalThis.procesosParaEliminarIds || new Set();

    const ProcesoDeleteService = {
        obtenerProceso(tipo) {
            return globalThis.procesosSeleccionados?.[tipo] || null;
        },

        async confirmarYEliminarTarjeta(tipo) {
            const proceso = this.obtenerProceso(tipo);
            if (!proceso) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontro el proceso para eliminar'
                });
                return;
            }

            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar proceso?',
                html: `<p>Esta a punto de eliminar el proceso <strong>${globalThis.nombresProcesos?.[tipo] || tipo}</strong></p>
                       <p style="font-size: 0.9em; color: #666; margin-top: 0.5rem;">El cambio se aplicara cuando guardes los cambios de la prenda.</p>`,
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                confirmButtonColor: '#ef4444',
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#6b7280',
                width: '400px',
                customClass: {
                    container: 'swal-container-centered',
                    popup: 'swal-popup-compact'
                }
            });

            if (result.isConfirmed) {
                this.marcarParaEliminar(tipo, proceso);
            }
        },

        buscarTarjetaPorTipo(tipo) {
            const selectors = [
                `[data-proceso-tipo="${tipo}"]`,
                `[data-tipo="${tipo}"]`,
                `[data-process-type="${tipo}"]`
            ];

            for (const selector of selectors) {
                const tarjeta = document.querySelector(selector);
                if (tarjeta) {
                    return { tarjeta, selector };
                }
            }
            return { tarjeta: null, selector: null };
        },

        marcarParaEliminar(tipo, proceso) {
            if (proceso?.datos?.id) {
                globalThis.procesosParaEliminarIds.add(proceso.datos.id);
            }

            proceso.marcadoParaEliminar = true;

            const { tarjeta } = this.buscarTarjetaPorTipo(tipo);
            if (tarjeta) {
                tarjeta.style.display = 'none';
                setTimeout(() => {
                    try {
                        tarjeta.remove();
                    } catch (error) {
                        console.error('[MARCAR-ELIMINAR] Error removiendo tarjeta:', error);
                    }
                }, 200);
            }

            Swal.fire({
                icon: 'success',
                title: 'Marcado para eliminar',
                html: `<p>El proceso <strong>${globalThis.nombresProcesos?.[tipo] || tipo}</strong> sera eliminado cuando guardes los cambios.</p>`,
                timer: 1500
            });
        },

        obtenerNumeroPedido() {
            return globalThis.prendaEnEdicion?.pedidoId ||
                   globalThis.numeroPedidoActual ||
                   document.querySelector('[data-numero-pedido]')?.getAttribute('data-numero-pedido') ||
                   document.querySelector('[data-pedido-id]')?.getAttribute('data-pedido-id');
        },

        async eliminarMarcadosDelBackend() {
            const idsParaEliminar = Array.from(globalThis.procesosParaEliminarIds || new Set());
            if (idsParaEliminar.length === 0) {
                return true;
            }

            const numeroPedido = this.obtenerNumeroPedido();

            for (const id of idsParaEliminar) {
                const response = await fetch(`/api/procesos/${id}/eliminar`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ numero_pedido: numeroPedido })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Error eliminando proceso ${id}`);
                }
            }

            globalThis.procesosParaEliminarIds.clear();
            return true;
        }
    };

    globalThis.ProcesoDeleteService = ProcesoDeleteService;
    globalThis.eliminarProcesossMarcadosDelBackend = async function() {
        return ProcesoDeleteService.eliminarMarcadosDelBackend();
    };
    globalThis.eliminarTarjetaProceso = function(tipo) {
        return ProcesoDeleteService.confirmarYEliminarTarjeta(tipo);
    };
})();
