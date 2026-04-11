/**
 * Status & Action Handlers - Insumos Module (FASE 3)
 * Funciones para gestionar cambios de estado de recibos y pedidos
 * 
 * Funciones incluidas:
 * - confirmarPasarRevisar() - Pasar recibo a revision
 * - cambiarEstadoRecibo() - Enviar recibo a produccion
 * - cambiarEstadoPedido() - Enviar pedido a produccion  
 * - cerrarModalConfirmarProduccion() - Cerrar modal de confirmacion
 * - restaurarBotonAprobar() - Restaurar estado del boton reprobar
 * - confirmarEnvioProduccion() - Confirmar y enviar a produccion
 */

const productionState = {
    reciboId: null,
    consecutivo: null,
    pedidoId: null,
};

function getPasarARevisarHandler(name) {
    return window.insumosHandlers?.pasarARevisar?.[name];
}

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
    
    // Enviar peticion
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
            showToast('Recibo pasado a revision correctamente', 'success');
            const closePasarRevisar = getPasarARevisarHandler('cerrarModalPasarRevisar');
            if (typeof closePasarRevisar === 'function') {
                closePasarRevisar();
            }
            // Recargar la tabla
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Error al pasar a revision', 'error');
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
 * Envia un recibo individual a produccion
 * Guarda el ID del recibo en variables globales y abre modal de confirmacion
 */
function cambiarEstadoRecibo(reciboId, consecutivo) {
    // Guardar el ID del recibo y su consecutivo en variables globales
    productionState.reciboId = reciboId;
    productionState.consecutivo = consecutivo;
    
    // Mostrar el modal
    document.getElementById('numeroPedidoConfirm').textContent = consecutivo;
    document.getElementById('modalConfirmarProduccion').style.display = 'flex';
}

/**
 * Mantener compatibilidad con llamadas anteriores
 * Envia un pedido completo a produccion
 */
function cambiarEstadoPedido(numeroPedido, estadoActual) {
    if (estadoActual.toLowerCase() === 'pendiente' || estadoActual === 'PENDIENTE_INSUMOS') {
        productionState.pedidoId = numeroPedido;
        document.getElementById('numeroPedidoConfirm').textContent = numeroPedido;
        document.getElementById('modalConfirmarProduccion').style.display = 'flex';
    } else {
        showToast('Este pedido ya ha sido enviado a produccion', 'info');
    }
}

/**
 * Cierra el modal de confirmacion de produccion
 * Limpia variables globales y restaura boton
 */
function cerrarModalConfirmarProduccion() {
    document.getElementById('modalConfirmarProduccion').style.display = 'none';
    productionState.reciboId = null;
    productionState.consecutivo = null;
    productionState.pedidoId = null;
    
    // Restaurar boton al cerrar modal
    restaurarBotonAprobar();
}

/**
 * Restaura el estado original del boton Aprobar
 * Detiene la animacion de carga y rehabilita el boton
 */
function restaurarBotonAprobar() {
    const btnAprobar = document.getElementById('btnAprobarProduccion');
    if (btnAprobar) {
        // Limpiar interval de animacion
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
 * Confirma el envio a produccion (recibo individual o pedido completo)
 * Muestra animacion de carga y recarga la Pagina al Exito
 */
function confirmarEnvioProduccion() {
    const reciboId = productionState.reciboId;
    const pedidoId = productionState.pedidoId;
    
    if (!reciboId && !pedidoId) return;
    
    // Bloquear boton y mostrar "Cargando..."
    const btnAprobar = document.getElementById('btnAprobarProduccion');
    const textoOriginal = btnAprobar.innerHTML;
    btnAprobar.disabled = true;
    btnAprobar.innerHTML = 'Cargando';
    btnAprobar.style.fontSize = '14px';
    
    // animacion de puntos
    let dots = 0;
    const loadingInterval = setInterval(() => {
        dots = (dots + 1) % 4;
        btnAprobar.innerHTML = 'Cargando' + '.'.repeat(dots);
    }, 500);
    
    // Guardar interval para limpiar despues
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
    
    // Enviar peticion al servidor
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
    .then(async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        return data;
    })
    .then(data => {
        // Ocultar loading overlay
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (data.success) {
            cerrarModalConfirmarProduccion();
            
            showToast('Recibo aprobado', 'success');
            
            // Recargar la Pagina despues de 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Restaurar boton
            restaurarBotonAprobar();
            showToast('Error al cambiar el estado: ' + (data.message || ''), 'error');
        }
    })
    .catch(error => {
        // Ocultar loading overlay
        document.getElementById('loadingOverlay').classList.remove('active');
        
        // Restaurar boton
        restaurarBotonAprobar();
        
        showToast(`Error al cambiar el estado: ${error.message || 'desconocido'}`, 'error');
    });
}


// ===== VARIABLES GLOBALES PARA MODAL DE CONFIRMACIÓN =====
let cambioEstadoPendiente = {
    reciboId: null,
    estadoActual: null,
    nuevoEstado: null,
    selectElement: null
};

/**
 * Cambiar estado desde el selector dropdown en la tabla
 * @param {HTMLSelectElement} selectElement - El elemento select que disparó el cambio
 */
async function cambiarEstadoDesdeSelector(selectElement) {
    const reciboId = selectElement.getAttribute('data-recibo-id');
    const estadoActual = selectElement.getAttribute('data-estado-actual');
    const nuevoEstado = selectElement.value;
    
    // Si no cambió, no hacer nada
    if (nuevoEstado === estadoActual) {
        return;
    }
    
    // Guardar los datos para confirmar después
    cambioEstadoPendiente.reciboId = reciboId;
    cambioEstadoPendiente.estadoActual = estadoActual;
    cambioEstadoPendiente.nuevoEstado = nuevoEstado;
    cambioEstadoPendiente.selectElement = selectElement;
    
    // Mostrar el modal de confirmación
    mostrarModalConfirmacion(nuevoEstado);
}

/**
 * Mostrar el modal de confirmación con el nuevo estado
 */
function mostrarModalConfirmacion(nuevoEstado) {
    const modal = document.getElementById('modalConfirmarCambioEstado');
    const textoEstado = document.getElementById('nuevoEstadoText');
    
    if (!modal) return;
    
    // Actualizar el texto del modal con el nuevo estado
    textoEstado.textContent = nuevoEstado;
    
    // Mostrar el modal
    modal.style.display = 'flex';
}

/**
 * Cancelar el cambio de estado
 */
function cancelarCambioEstado() {
    const modal = document.getElementById('modalConfirmarCambioEstado');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Revertir el selector al valor anterior
    if (cambioEstadoPendiente.selectElement) {
        cambioEstadoPendiente.selectElement.value = cambioEstadoPendiente.estadoActual;
    }
    
    // Limpiar los datos
    cambioEstadoPendiente = {
        reciboId: null,
        estadoActual: null,
        nuevoEstado: null,
        selectElement: null
    };
}

/**
 * Confirmar el cambio de estado
 */
async function confirmarCambioEstado() {
    const reciboId = cambioEstadoPendiente.reciboId;
    const estadoActual = cambioEstadoPendiente.estadoActual;
    const nuevoEstado = cambioEstadoPendiente.nuevoEstado;
    const selectElement = cambioEstadoPendiente.selectElement;
    
    // Cerrar el modal
    const modal = document.getElementById('modalConfirmarCambioEstado');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Deshabilitar el select mientras se procesa
    if (selectElement) {
        selectElement.disabled = true;
    }
    
    try {
        const response = await fetch(`/insumos/materiales/recibo/${reciboId}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                estado: nuevoEstado
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar el atributo data-estado-actual
            if (selectElement) {
                selectElement.setAttribute('data-estado-actual', nuevoEstado);
                // Actualizar los colores del select basado en el nuevo estado
                actualizarColorSelect(selectElement, nuevoEstado);
            }
            
            // Mostrar toast de éxito
            showToast(`Estado cambiado a ${nuevoEstado}`, 'success');
            
            // Recargar la tabla después de un segundo
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // Revertir el select al valor anterior
            if (selectElement) {
                selectElement.value = estadoActual;
            }
            showToast(data.message || 'Error al cambiar el estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        // Revertir el select al valor anterior
        if (selectElement) {
            selectElement.value = estadoActual;
        }
        showToast('Error al procesar la solicitud', 'error');
    } finally {
        // Habilitar el select
        if (selectElement) {
            selectElement.disabled = false;
        }
        
        // Limpiar los datos
        cambioEstadoPendiente = {
            reciboId: null,
            estadoActual: null,
            nuevoEstado: null,
            selectElement: null
        };
    }
}

/**
 * Actualizar los colores del select basado en el estado
 * @param {HTMLSelectElement} selectElement - El elemento select a actualizar
 * @param {string} estado - El nuevo estado
 */
function actualizarColorSelect(selectElement, estado) {
    // Remover todas las clases de color
    selectElement.classList.remove(
        'bg-gray-400', 'text-white',
        'bg-blue-100', 'text-blue-800',
        'bg-amber-100', 'text-amber-800',
        'bg-yellow-400', 'text-gray-900',
        'bg-green-500',
        'bg-red-500'
    );
    
    // Agregar las nuevas clases según el estado
    if (estado === 'No iniciado') {
        selectElement.classList.add('bg-gray-400', 'text-white');
    } else if (estado === 'En Ejecución') {
        selectElement.classList.add('bg-blue-100', 'text-blue-800');
    } else if (estado === 'Anulada') {
        selectElement.classList.add('bg-amber-100', 'text-amber-800');
    } else if (estado === 'PENDIENTE_INSUMOS' || estado === 'Pendiente_Insumos') {
        selectElement.classList.add('bg-amber-500', 'text-white');
    } else if (estado === 'DEVUELTO_ASESOR') {
        selectElement.classList.add('bg-red-500', 'text-white');
    } else if (estado === 'Insumos Pedidos' || estado === 'INSUMOS_PEDIDOS') {
        selectElement.classList.add('bg-green-500', 'text-white');
    }
}

function exportStatusActionsInsumos() {
    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.statusActions = {
        confirmarPasarRevisar,
        cambiarEstadoRecibo,
        cambiarEstadoPedido,
        cerrarModalConfirmarProduccion,
        restaurarBotonAprobar,
        confirmarEnvioProduccion,
    };
    
    // Exponer funciones globales para el modal de cambio de estado
    window.cambiarEstadoDesdeSelector = cambiarEstadoDesdeSelector;
    window.mostrarModalConfirmacion = mostrarModalConfirmacion;
    window.cancelarCambioEstado = cancelarCambioEstado;
    window.confirmarCambioEstado = confirmarCambioEstado;
    window.actualizarColorSelect = actualizarColorSelect;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', exportStatusActionsInsumos);
} else {
    exportStatusActionsInsumos();
}
