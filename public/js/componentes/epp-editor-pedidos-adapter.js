/**
 * EPP Editor Adapter - Maneja operaciones de EPPs en pedidos
 * Similar a prenda-editor-pedidos-adapter.js pero para EPPs
 */

window.abrirModalEliminarEpp = function(epp, eppIndex, pedidoId) {
    const eppId = epp.id || epp.pedido_epp_id;
    const nombreEpp = epp.nombre || epp.epp?.nombre || 'EPP Sin nombre';
    const cantidad = epp.cantidad || 1;
    
    console.log('[EPPAdapter]  Eliminando EPP:', nombreEpp, 'id:', eppId, 'pedidoId:', pedidoId);

    if (!pedidoId || !eppId) {
        console.error('[EPPAdapter] Faltan pedidoId o eppId para eliminar');
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'No se pudo identificar el pedido o el EPP para eliminar', 'error');
        }
        return;
    }

    if (typeof Swal === 'undefined') {
        console.error('[EPPAdapter] SweetAlert2 no disponible');
        return;
    }

    // Inyectar CSS para z-index y centrado
    let eliminarStyle = document.getElementById('swal-eliminar-epp-style');
    if (!eliminarStyle) {
        eliminarStyle = document.createElement('style');
        eliminarStyle.id = 'swal-eliminar-epp-style';
        document.head.appendChild(eliminarStyle);
    }
    eliminarStyle.textContent = `
        .swal-eliminar-epp-container {
            z-index: 2000000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }
        .swal-eliminar-epp-container .swal2-popup {
            margin: auto !important;
        }
    `;

    // Pedir motivo de eliminación
    Swal.fire({
        title: '¿Eliminar EPP?',
        html: `<p>¿Estás seguro de que deseas eliminar <strong>${nombreEpp.toUpperCase()}</strong>?</p>
               <p style="color: #6b7280; font-size: 0.85em; margin-top: 0.5rem;">Cantidad: <strong>${cantidad}</strong></p>
               <p style="color: #ef4444; font-size: 0.9em; margin-top: 1rem;">Se registrará en las novedades del pedido.</p>`,
        icon: 'warning',
        input: 'textarea',
        inputLabel: 'Motivo de la eliminación',
        inputPlaceholder: 'Ej: EPP no requerido, cambio en especificaciones, etc.',
        inputAttributes: { 'aria-label': 'Motivo de eliminación' },
        showCancelButton: true,
        confirmButtonText: ' Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        customClass: {
            container: 'swal-eliminar-epp-container'
        },
        didOpen: (modal) => {
            const container = modal.closest('.swal2-container');
            if (container) {
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.height = '100vh';
                container.style.zIndex = '2000000';
            }
        },
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Debes ingresar un motivo de eliminación';
            }
            if (value.trim().length < 5) {
                return 'El motivo debe tener al menos 5 caracteres';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            _eliminarEppDelAPI(pedidoId, eppId, eppIndex, epp, result.value.trim());
        }
    });
};

/**
 * Eliminar EPP del servidor
 * @private
 */
async function _eliminarEppDelAPI(pedidoId, eppId, eppIndex, epp, motivo) {
    try {
        // Mostrar loading
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Eliminando EPP...',
                html: 'Por favor espera mientras se elimina el EPP',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        console.log('[EPPAdapter] Enviando DELETE a: /api/asesores/pedidos/' + pedidoId + '/eliminar-epp');
        
        const response = await fetch(`/api/asesores/pedidos/${pedidoId}/eliminar-epp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                epp_id: eppId,
                motivo: motivo
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error al eliminar EPP');
        }

        const data = await response.json();

        if (data.success) {
            console.log('[EPPAdapter]  EPP eliminado correctamente:', data);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¡EPP Eliminado!',
                    html: `<p><strong>${data.epp_nombre}</strong> ha sido eliminado correctamente.</p>
                           <p style="color: #6b7280; font-size: 0.9em; margin-top: 0.5rem;">Se ha registrado en las novedades del pedido.</p>`,
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e40af'
                }).then(() => {
                    // Recargar la página o actualizar lista de EPPs
                    if (typeof location !== 'undefined') {
                        location.reload();
                    }
                });
            }
        } else {
            throw new Error(data.message || 'No se pudo eliminar el EPP');
        }

    } catch (error) {
        console.error('[EPPAdapter] Error al eliminar:', error.message);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: error.message || 'No se pudo eliminar el EPP. Por favor intenta de nuevo.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#1e40af'
            });
        }
    }
}
