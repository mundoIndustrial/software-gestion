/**
 * Funcionalidad para anular pedidos - Asesores
 */

/**
 * Confirmar anulación de pedido
 */
function confirmarAnularPedido(numeroPedido) {
    Swal.fire({
        title: '¿Anular Pedido?',
        html: `
            <div style="width: 100%; box-sizing: border-box; overflow: hidden;">
                <p style="margin-bottom: 1rem; color: #374151; font-size: 0.95rem;">
                    Estás a punto de anular el pedido <strong style="color: #ef4444;">#${numeroPedido}</strong>
                </p>
                <textarea 
                    id="novedadAnulacion" 
                    class="swal2-textarea" 
                    placeholder="Escribe la novedad de la anulación (mínimo 10 caracteres)..."
                    style="
                        width: calc(100% - 1.5rem);
                        min-height: 100px;
                        max-height: 200px;
                        padding: 0.75rem;
                        margin: 0 auto;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                        font-size: 0.9rem;
                        resize: vertical;
                        font-family: inherit;
                        box-sizing: border-box;
                        line-height: 1.5;
                        display: block;
                        overflow-x: hidden;
                    "
                ></textarea>
                <p style="margin-top: 0.75rem; font-size: 0.75rem; color: #6b7280; line-height: 1.4;">
                    <i class="fas fa-info-circle" style="color: #3b82f6;"></i> 
                    Esta novedad se agregará al campo de novedades del pedido con tu nombre y fecha
                </p>
            </div>
        `,
        icon: 'warning',
        width: '500px',
        padding: '1.5rem',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-ban"></i> Anular Pedido',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        customClass: {
            popup: 'swal-anular-pedido',
            htmlContainer: 'swal-html-container-custom'
        },
        preConfirm: () => {
            const novedad = document.getElementById('novedadAnulacion').value.trim();
            
            if (!novedad) {
                Swal.showValidationMessage('La novedad es obligatoria');
                return false;
            }
            
            if (novedad.length < 10) {
                Swal.showValidationMessage('La novedad debe tener al menos 10 caracteres');
                return false;
            }
            
            if (novedad.length > 500) {
                Swal.showValidationMessage('La novedad no puede exceder 500 caracteres');
                return false;
            }
            
            return novedad;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            anularPedido(numeroPedido, result.value);
        }
    });
    
    // Focus en el textarea después de que se muestre el modal
    setTimeout(() => {
        const textarea = document.getElementById('novedadAnulacion');
        if (textarea) {
            textarea.focus();
        }
    }, 100);
}

/**
 * Anular pedido - Enviar petición al servidor
 */
function anularPedido(numeroPedido, novedad) {
    // Mostrar loading
    Swal.fire({
        title: 'Anulando pedido...',
        html: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Obtener CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudo obtener el token de seguridad',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    // Enviar petición
    fetch(`/asesores/pedidos/${numeroPedido}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            novedad: novedad
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Error al anular el pedido');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Pedido Anulado!',
                html: `
                    <p style="color: #374151; margin-bottom: 0.5rem;">
                        El pedido <strong>#${numeroPedido}</strong> ha sido anulado correctamente
                    </p>
                    <p style="color: #6b7280; font-size: 0.875rem;">
                        Estado: <span style="color: #ef4444; font-weight: 600;">Anulada</span>
                    </p>
                    <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.5rem;">
                        La novedad ha sido agregada al campo de novedades
                    </p>
                `,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Entendido'
            }).then(() => {
                // Recargar la página para actualizar la lista
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al anular el pedido');
        }
    })
    .catch(error => {

        Swal.fire({
            title: 'Error',
            text: error.message || 'Ocurrió un error al anular el pedido',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    });
}
