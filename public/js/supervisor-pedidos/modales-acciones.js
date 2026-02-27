/**
 * MODALES DE ACCIONES - SUPERVISOR-PEDIDOS
 * ==========================================
 * Funciones para abrir modales de aprobación, anulación y ocultación de pedidos
 * Se carga TEMPRANO para evitar errores de "función no definida"
 */

// ===== FUNCIÓN PARA ABRIR MODAL DE ANULACIÓN =====
function abrirModalAnulacion(ordenId, numeroOrden) {
    document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
    document.getElementById('formAnulacion').dataset.ordenId = ordenId;
    document.getElementById('motivoAnulacion').value = '';
    document.getElementById('contadorActual').textContent = '0';
    document.getElementById('modalAnulacion').style.display = 'flex';
}

function cerrarModalAnulacion() {
    document.getElementById('modalAnulacion').style.display = 'none';
}

function confirmarAnulacion(event) {
    event.preventDefault();
    
    const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
    const motivo = document.getElementById('motivoAnulacion').value;

    fetch(`/supervisor-pedidos/${ordenId}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            motivo_anulacion: motivo,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal de revisión
            cerrarModalAnulacion();
            // Mostrar modal de éxito
            document.getElementById('modalExitoRevision').style.display = 'flex';
            // Recargar notificaciones si la función existe
            if (typeof cargarNotificacionesPendientes === 'function') {
                cargarNotificacionesPendientes();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error al enviar la orden a revisión');
    });
}

function cerrarModalExitoRevision() {
    document.getElementById('modalExitoRevision').style.display = 'none';
    // Recargar la página después de cerrar
    setTimeout(() => location.reload(), 300);
}

// ===== FUNCIONES PARA OCULTAR PEDIDO =====
let ordenIdOcultar = null;

function abrirModalOcultar(ordenId, numeroOrden) {
    ordenIdOcultar = ordenId;
    document.getElementById('ordenOcultarNumero').textContent = '#' + numeroOrden;
    document.getElementById('modalOcultar').style.display = 'flex';
}

function cerrarModalOcultar() {
    document.getElementById('modalOcultar').style.display = 'none';
    ordenIdOcultar = null;
}

function confirmarOcultar() {
    if (!ordenIdOcultar) return;

    const btnConfirmar = document.getElementById('btnConfirmarOcultar');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Ocultando...';

    fetch(`/supervisor-pedidos/${ordenIdOcultar}/ocultar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({}),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal de confirmación
            cerrarModalOcultar();
            // Mostrar modal de éxito
            document.getElementById('modalExitoOcultar').style.display = 'flex';
            // Recargar notificaciones si la función existe
            if (typeof cargarNotificacionesPendientes === 'function') {
                cargarNotificacionesPendientes();
            }
        } else {
            alert('Error: ' + data.message);
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> Ocultar Pedido';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al ocultar el pedido');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> Ocultar Pedido';
    });
}

function cerrarModalExitoOcultar() {
    document.getElementById('modalExitoOcultar').style.display = 'none';
    // Recargar la página después de cerrar
    setTimeout(() => location.reload(), 300);
}

// ===== FUNCIÓN PARA ABRIR MODAL DE APROBACIÓN =====
window.abrirModalAprobacion = function(ordenId, numeroPedido) {
    console.log('[Aprobación] Abriendo modal para orden:', { ordenId, numeroPedido });
    
    Swal.fire({
        title: '¿Aprobar Pedido?',
        html: `<p>¿Deseas aprobar el pedido <strong>#${numeroPedido}</strong>?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check"></i> Sí, aprobar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar modal de cargando
            Swal.fire({
                title: 'Procesando...',
                html: '<p>Por favor espera mientras se aprueba el pedido</p><div style="margin-top: 20px;"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar solicitud de aprobación
            fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Aprobado!',
                        html: `<p>${data.message || 'Pedido aprobado correctamente'}</p><p style="margin-top: 10px; font-weight: 600; color: #10b981;">Estado: ${data.estado}</p>`,
                        icon: 'success',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo aprobar el pedido',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('[Aprobación] Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al procesar la solicitud',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
};
