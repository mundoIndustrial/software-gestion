/**
 * MODALES DE ACCIONES - SUPERVISOR-PEDIDOS
 * ==========================================
 * Funciones para abrir modales de aprobación, anulación y ocultación de pedidos
 * Se carga TEMPRANO para evitar errores de "función no definida"
 *
 * Requiere: shared/bootstrap.js → globalThis.shared (http, notify, modal)
 */

if (!globalThis.shared?.isReady) {
    throw new Error('[modales-acciones] globalThis.shared no está disponible. Asegúrate de cargar shared/bootstrap.js ANTES de este archivo.');
}

const { http: _http, notify: _notify, modal: _modal } = globalThis.shared;

// ===== FUNCIÓN PARA ABRIR MODAL DE ANULACIÓN =====
function abrirModalAnulacion(ordenId, numeroOrden) {
    document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
    document.getElementById('formAnulacion').dataset.ordenId = ordenId;
    document.getElementById('motivoAnulacion').value = '';
    document.getElementById('contadorActual').textContent = '0';
    _modal.open('modalAnulacion');
}

function cerrarModalAnulacion() {
    _modal.close('modalAnulacion');
}

async function confirmarAnulacion(event) {
    event.preventDefault();

    const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
    const motivo = document.getElementById('motivoAnulacion').value;

    try {
        const data = await _http.post(`/supervisor-pedidos/${ordenId}/anular`, { motivo_anulacion: motivo });

        if (data.success) {
            cerrarModalAnulacion();
            _modal.open('modalExitoRevision');
            if (typeof cargarNotificacionesPendientes === 'function') { cargarNotificacionesPendientes(); }
        } else {
            _notify.error(data.message || 'Error al anular');
        }
    } catch (error) {
        console.error('[Anulación] Error:', error);
        _notify.error('Error al enviar la orden a revisión');
    }
}

function cerrarModalExitoRevision() {
    _modal.close('modalExitoRevision');
    setTimeout(() => location.reload(), 300);
}

// ===== FUNCIONES PARA OCULTAR PEDIDO =====
let ordenIdOcultar = null;

function abrirModalOcultar(ordenId, numeroOrden) {
    ordenIdOcultar = ordenId;
    document.getElementById('ordenOcultarNumero').textContent = '#' + numeroOrden;
    _modal.open('modalOcultar');
}

function cerrarModalOcultar() {
    _modal.close('modalOcultar');
    ordenIdOcultar = null;
}

async function confirmarOcultar() {
    if (!ordenIdOcultar) return;

    const btnConfirmar = document.getElementById('btnConfirmarOcultar');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Ocultando...';

    try {
        const data = await _http.post(`/supervisor-pedidos/${ordenIdOcultar}/ocultar`, {});

        if (data.success) {
            cerrarModalOcultar();
            _modal.open('modalExitoOcultar');
            if (typeof cargarNotificacionesPendientes === 'function') { cargarNotificacionesPendientes(); }
        } else {
            _notify.error(data.message || 'Error al ocultar');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> Ocultar Pedido';
        }
    } catch (error) {
        console.error('[Ocultar] Error:', error);
        _notify.error('Error al ocultar el pedido');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> Ocultar Pedido';
    }
}

function cerrarModalExitoOcultar() {
    _modal.close('modalExitoOcultar');
    setTimeout(() => location.reload(), 300);
}

// ===== FUNCIÓN PARA ABRIR MODAL DE APROBACIÓN =====
globalThis.abrirModalAprobacion = function(ordenId, numeroPedido) {
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
            _http.post(`/supervisor-pedidos/${ordenId}/aprobar`, {})
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
