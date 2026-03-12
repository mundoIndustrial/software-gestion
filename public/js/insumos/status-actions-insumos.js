/**
 * Status & Action Handlers - Insumos Module (FASE 3)
 * Funciones para gestionar cambios de estado de recibos y pedidos
 * 
 * Funciones incluidas:
 * - confirmarPasarRevisar() - Pasar recibo a revisión
 * - cambiarEstadoRecibo() - Enviar recibo a producción
 * - cambiarEstadoPedido() - Enviar pedido a producción  
 * - cerrarModalConfirmarProduccion() - Cerrar modal de confirmación
 * - restaurarBotonAprobar() - Restaurar estado del botón reprobar
 * - confirmarEnvioProduccion() - Confirmar y enviar a producción
 */

/**
 * Confirma pasar un recibo a revisión
 * Envía el motivo al servidor y recarga la tabla
 */
function confirmarPasarRevisar(event) {
    event.preventDefault();
    
    const reciboId = document.getElementById('reciboIdPasarRevisar').value;
    const motivo = document.getElementById('motivoPasarRevisar').value;
    
    if (!motivo.trim()) {
        alert('Por favor ingresa el motivo');
        return;
    }
    
    // Mostrar cargando
    const btnConfirmar = document.getElementById('btnConfirmarPasarRevisar');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Procesando...';
    
    // Enviar petición
    fetch(`/insumos/materiales/${reciboId}/pasar-revisar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            motivo: motivo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Recibo pasado a revisión correctamente', 'success');
            cerrarModalPasarRevisar();
            // Recargar la tabla
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Error al pasar a revisión', 'error');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-arrow-rotate-left"></i> Pasar a Revisar';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al procesar la solicitud', 'error');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-arrow-rotate-left"></i> Pasar a Revisar';
    });
}

/**
 * Envía un recibo individual a producción
 * Guarda el ID del recibo en variables globales y abre modal de confirmación
 */
function cambiarEstadoRecibo(reciboId, consecutivo) {
    // Guardar el ID del recibo y su consecutivo en variables globales
    window.reciboParaProduccion = reciboId;
    window.consecutivoRecibo = consecutivo;
    
    // Mostrar el modal
    document.getElementById('numeroPedidoConfirm').textContent = consecutivo;
    document.getElementById('modalConfirmarProduccion').style.display = 'flex';
}

/**
 * Mantener compatibilidad con llamadas anteriores
 * Envía un pedido completo a producción
 */
function cambiarEstadoPedido(numeroPedido, estadoActual) {
    if (estadoActual.toLowerCase() === 'pendiente' || estadoActual === 'PENDIENTE_INSUMOS') {
        window.pedidoParaProduccion = numeroPedido;
        document.getElementById('numeroPedidoConfirm').textContent = numeroPedido;
        document.getElementById('modalConfirmarProduccion').style.display = 'flex';
    } else {
        showToast('Este pedido ya ha sido enviado a producción', 'info');
    }
}

/**
 * Cierra el modal de confirmación de producción
 * Limpia variables globales y restaura botón
 */
function cerrarModalConfirmarProduccion() {
    document.getElementById('modalConfirmarProduccion').style.display = 'none';
    window.reciboParaProduccion = null;
    window.consecutivoRecibo = null;
    window.pedidoParaProduccion = null;
    
    // Restaurar botón al cerrar modal
    restaurarBotonAprobar();
}

/**
 * Restaura el estado original del botón Aprobar
 * Detiene la animación de carga y rehabilita el botón
 */
function restaurarBotonAprobar() {
    const btnAprobar = document.getElementById('btnAprobarProduccion');
    if (btnAprobar) {
        // Limpiar interval de animación
        if (btnAprobar.loadingInterval) {
            clearInterval(btnAprobar.loadingInterval);
            btnAprobar.loadingInterval = null;
        }
        
        btnAprobar.disabled = false;
        btnAprobar.innerHTML = 'Aprobar';
        btnAprobar.style.fontSize = '';
        btnAprobar.classList.add('hover:bg-blue-700');
        btnAprobar.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

/**
 * Confirma el envío a producción (recibo individual o pedido completo)
 * Muestra animación de carga y recarga la página al éxito
 */
function confirmarEnvioProduccion() {
    const reciboId = window.reciboParaProduccion;
    const pedidoId = window.pedidoParaProduccion;
    
    if (!reciboId && !pedidoId) return;
    
    // Bloquear botón y mostrar "Cargando..."
    const btnAprobar = document.getElementById('btnAprobarProduccion');
    const textoOriginal = btnAprobar.innerHTML;
    btnAprobar.disabled = true;
    btnAprobar.innerHTML = 'Cargando';
    btnAprobar.style.fontSize = '14px';
    
    // Animación de puntos
    let dots = 0;
    const loadingInterval = setInterval(() => {
        dots = (dots + 1) % 4;
        btnAprobar.innerHTML = 'Cargando' + '.'.repeat(dots);
    }, 500);
    
    // Guardar interval para limpiar después
    btnAprobar.loadingInterval = loadingInterval;
    
    btnAprobar.classList.remove('hover:bg-blue-700');
    btnAprobar.classList.add('opacity-75', 'cursor-not-allowed');
    
    const proximoEstado = 'En Ejecución';
    
    // Mostrar loading overlay
    document.getElementById('loadingOverlay').classList.add('active');
    
    // Determinar URL según si es recibo individual o pedido completo
    let url;
    if (reciboId) {
        url = `/insumos/materiales/recibo/${reciboId}/cambiar-estado`;
    } else {
        url = `/insumos/materiales/${pedidoId}/cambiar-estado`;
    }
    
    // Enviar petición al servidor
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ 
            estado: proximoEstado
        }),
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar loading overlay
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (data.success) {
            cerrarModalConfirmarProduccion();
            
            showToast('Recibo aprobado', 'success');
            
            // Recargar la página después de 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Restaurar botón
            restaurarBotonAprobar();
            showToast('Error al cambiar el estado: ' + (data.message || ''), 'error');
        }
    })
    .catch(error => {
        // Ocultar loading overlay
        document.getElementById('loadingOverlay').classList.remove('active');
        
        // Restaurar botón
        restaurarBotonAprobar();
        
        showToast('Error al cambiar el estado', 'error');
    });
}

// Auto-initialize: Export all functions to window on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    window.confirmarPasarRevisar = confirmarPasarRevisar;
    window.cambiarEstadoRecibo = cambiarEstadoRecibo;
    window.cambiarEstadoPedido = cambiarEstadoPedido;
    window.cerrarModalConfirmarProduccion = cerrarModalConfirmarProduccion;
    window.restaurarBotonAprobar = restaurarBotonAprobar;
    window.confirmarEnvioProduccion = confirmarEnvioProduccion;
});
