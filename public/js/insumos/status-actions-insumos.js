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

if (typeof window.productionState === 'undefined') {
    window.productionState = {
        reciboId: null,
        consecutivo: null,
        pedidoId: null,
    };
}

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
    window.productionState.reciboId = reciboId;
    window.productionState.consecutivo = consecutivo;
    
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
        window.productionState.pedidoId = numeroPedido;
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
    window.productionState.reciboId = null;
    window.productionState.consecutivo = null;
    window.productionState.pedidoId = null;
    
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
    const reciboId = window.productionState.reciboId;
    const pedidoId = window.productionState.pedidoId;
    
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

/**
 * Envia un recibo de reflectivo a producción (Costura)
 * Lógica específica para la vista de reflectivo
 */
async function enviarProduccionReflectivo(reciboId, consecutivo) {
    if (!reciboId) return;

    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: `Enviar recibo #${consecutivo} a producción`,
            text: "¿Estás seguro de enviar este recibo de reflectivo al área de Costura?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb'
        });

        if (!result.isConfirmed) return;
    } else {
        if (!confirm(`¿Estás seguro de enviar el recibo #${consecutivo} a producción?`)) return;
    }

    // Mostrar loading overlay
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.add('active');

    try {
        const response = await fetch(`/insumos/materiales/recibo/${reciboId}/enviar-produccion-reflectivo`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Encontrar la fila de la tabla y aplicar animación de desaparición
            const tableRow = document.querySelector(`tr[data-recibo="${reciboId}"]`);
            if (tableRow) {
                // Aplicar la clase de animación (puedes cambiar a otra si prefieres)
                tableRow.classList.add('row-disappearing-elegant');
                
                // Esperar a que termine la animación antes de mostrar el toast
                await new Promise(resolve => {
                    setTimeout(resolve, 800); // Duración de la animación
                });
            }

            if (typeof showToast === 'function') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error al enviar a producción', 'error');
            } else {
                alert(data.message || 'Error al enviar a producción');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('Error al procesar la solicitud', 'error');
        } else {
            alert('Error al procesar la solicitud');
        }
    } finally {
        if (overlay) overlay.classList.remove('active');
    }
}



// ===== VARIABLES GLOBALES PARA MODAL DE CONFIRMACIÓN =====
if (typeof window.cambioEstadoPendiente === 'undefined') {
    window.cambioEstadoPendiente = {
        reciboId: null,
        estadoActual: null,
        nuevoEstado: null,
        selectElement: null
    };
}

/**
 * Obtener el mapeo de estado a display text y clase CSS
 */
function obtenerEstadoInfo(estado) {
    const mapa = {
        'No iniciado': { display: 'No iniciado', clase: 'bg-gray-400 text-white' },
        'En Ejecución': { display: 'En Ejecución', clase: 'bg-blue-100 text-blue-800' },
        'En Ejecucion': { display: 'En Ejecución', clase: 'bg-blue-100 text-blue-800' },
        'Anulada': { display: 'Anulada', clase: 'bg-red-100 text-red-800' },
        'ANULADO': { display: 'Anulada', clase: 'bg-red-100 text-red-800' },
        'PENDIENTE_INSUMOS': { display: 'Pendiente Insumos', clase: 'bg-amber-500 text-white' },
        'Pendiente_Insumos': { display: 'Pendiente Insumos', clase: 'bg-amber-500 text-white' },
        'Pendiente Insumos': { display: 'Pendiente Insumos', clase: 'bg-amber-500 text-white' },
        'PENDIENTE_TELA': { display: 'Pendiente Tela', clase: 'bg-yellow-400 text-gray-900' },
        'Pendiente Tela': { display: 'Pendiente Tela', clase: 'bg-yellow-400 text-gray-900' },
        'PENDIENTE_PLOTTER': { display: 'Pendiente Plotter', clase: 'bg-gray-400 text-white' },
        'Pendiente Plotter': { display: 'Pendiente Plotter', clase: 'bg-gray-400 text-white' },
        'INSUMOS_PEDIDOS': { display: 'Insumos Pedidos', clase: 'bg-green-500 text-white' },
        'Insumos Pedidos': { display: 'Insumos Pedidos', clase: 'bg-green-500 text-white' },
        'DEVUELTO_ASESOR': { display: 'Devuelto Asesor', clase: 'bg-red-500 text-white' },
        'Devuelto Asesor': { display: 'Devuelto Asesor', clase: 'bg-red-500 text-white' }
    };
    return mapa[estado] || { display: estado, clase: 'bg-gray-400 text-white' };
}

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
    window.cambioEstadoPendiente.reciboId = reciboId;
    window.cambioEstadoPendiente.estadoActual = estadoActual;
    window.cambioEstadoPendiente.nuevoEstado = nuevoEstado;
    window.cambioEstadoPendiente.selectElement = selectElement;
    
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
    if (window.cambioEstadoPendiente.selectElement) {
        window.cambioEstadoPendiente.selectElement.value = window.cambioEstadoPendiente.estadoActual;
    }
    
    // Limpiar los datos
    window.cambioEstadoPendiente.reciboId = null;
    window.cambioEstadoPendiente.estadoActual = null;
    window.cambioEstadoPendiente.nuevoEstado = null;
    window.cambioEstadoPendiente.selectElement = null;
}

/**
 * Confirmar el cambio de estado
 */
async function confirmarCambioEstado() {
    const reciboId = window.cambioEstadoPendiente.reciboId;
    const estadoActual = window.cambioEstadoPendiente.estadoActual;
    const nuevoEstado = window.cambioEstadoPendiente.nuevoEstado;
    const selectElement = window.cambioEstadoPendiente.selectElement;
    
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
            // Actualizar el atributo data-estado-actual Y el valor del select
            if (selectElement) {
                // Usar el estado que realmente se guardó en el backend
                const estadoGuardado = data.estado_guardado || nuevoEstado;
                
                // Asegurar que el select tenga el nuevo valor
                selectElement.value = estadoGuardado;
                selectElement.setAttribute('data-estado-actual', estadoGuardado);
                
                // Aplicar el nuevo estilo del select inmediatamente
                if (typeof window.aplicarEstiloEstadoSelect === 'function') {
                    window.aplicarEstiloEstadoSelect(selectElement);
                }
                
                // Si no se aplicó el estilo, intentar con setTimeout como fallback
                setTimeout(() => {
                    if (typeof window.aplicarEstiloEstadoSelect === 'function') {
                        window.aplicarEstiloEstadoSelect(selectElement);
                    }
                }, 100);
                
                // Actualizar el span de estado si existe (para cuando no es editable)
                const spanEstado = document.querySelector(`span.estado-span[data-recibo-id="${reciboId}"]`);
                if (spanEstado) {
                    const info = obtenerEstadoInfo(estadoGuardado);
                    // Remover todas las clases de color anteriores
                    spanEstado.className = 'estado-span inline-block px-3 py-2 rounded-lg text-sm font-semibold break-words';
                    // Agregar las nuevas clases
                    spanEstado.className += ' ' + info.clase;
                    // Actualizar el texto
                    spanEstado.textContent = info.display;
                }
            }
            
            // Mostrar toast de éxito
            showToast(`Estado cambiado a ${data.estado_guardado || nuevoEstado}`, 'success');
            
            // NO recargar la página - mantener el cambio visible sin recarga
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
        window.cambioEstadoPendiente.reciboId = null;
        window.cambioEstadoPendiente.estadoActual = null;
        window.cambioEstadoPendiente.nuevoEstado = null;
        window.cambioEstadoPendiente.selectElement = null;
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
        'bg-red-100', 'text-red-800',
        'bg-yellow-400', 'text-gray-900',
        'bg-green-500',
        'bg-red-500'
    );

    // Agregar las nuevas clases según el estado
    if (estado === 'No iniciado') {
        selectElement.classList.add('bg-gray-400', 'text-white');
    } else if (estado === 'En Ejecución') {
        selectElement.classList.add('bg-blue-100', 'text-blue-800');
    } else if (estado === 'Anulada' || estado === 'ANULADO') {
        selectElement.classList.add('bg-red-100', 'text-red-800');
    } else if (estado === 'PENDIENTE_INSUMOS' || estado === 'Pendiente_Insumos') {
        selectElement.classList.add('bg-amber-500', 'text-white');
    } else if (estado === 'Pendiente Tela' || estado === 'PENDIENTE_TELA') {
        selectElement.classList.add('bg-yellow-400', 'text-gray-900');
    } else if (estado === 'Pendiente Plotter' || estado === 'PENDIENTE_PLOTTER') {
        selectElement.classList.add('bg-gray-400', 'text-white');
    } else if (estado === 'DEVUELTO_ASESOR') {
        selectElement.classList.add('bg-red-500', 'text-white');
    } else if (estado === 'Insumos Pedidos' || estado === 'INSUMOS_PEDIDOS') {
        selectElement.classList.add('bg-green-500', 'text-white');
    }
}

async function anularReciboInsumos(reciboId, consecutivo) {
    if (!reciboId) {
        showToast('No se encontro el recibo a anular', 'error');
        return;
    }

    let motivo = '';

    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await window.Swal.fire({
            title: `Anular recibo #${consecutivo || reciboId}`,
            text: 'Esta accion cambiara el estado del recibo a Anulada.',
            input: 'textarea',
            inputLabel: 'Motivo de anulacion',
            inputPlaceholder: 'Describe por que se anula este recibo...',
            inputAttributes: {
                maxlength: 500,
            },
            showCancelButton: true,
            confirmButtonText: 'Anular recibo',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
            inputValidator: (value) => {
                if (!value || value.trim().length < 10) {
                    return 'El motivo debe tener al menos 10 caracteres.';
                }
                return null;
            }
        });

        if (!result.isConfirmed) {
            return;
        }

        motivo = String(result.value || '').trim();
    } else {
        const valor = prompt('Motivo de anulacion (minimo 10 caracteres):');
        if (valor === null) return;
        motivo = String(valor).trim();
        if (motivo.length < 10) {
            showToast('El motivo debe tener al menos 10 caracteres', 'error');
            return;
        }
    }

    try {
        const response = await fetch(`/insumos/materiales/${reciboId}/anular`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ motivo })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        showToast(data.message || 'Recibo anulado correctamente', 'success');
        setTimeout(() => {
            window.location.reload();
        }, 1200);
    } catch (error) {
        console.error('Error anulando recibo:', error);
        showToast(error.message || 'Error al anular el recibo', 'error');
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
        anularReciboInsumos,
        enviarProduccionReflectivo,
    };
    
    // Función para aplicar estilos de color según el estado
    window.aplicarEstiloEstadoSelect = function(selectElement) {
        const estado = selectElement.value;
        const estilosEstado = {
            'No iniciado': { bg: '#6b7280', color: '#ffffff', border: '#4b5563' },
            'En Ejecución': { bg: '#3b82f6', color: '#ffffff', border: '#1d4ed8' },
            'PENDIENTE_INSUMOS': { bg: '#f97316', color: '#ffffff', border: '#ea580c' },
            'Pendiente_Insumos': { bg: '#f97316', color: '#ffffff', border: '#ea580c' },
            'Pendiente Insumos': { bg: '#f97316', color: '#ffffff', border: '#ea580c' },
            'Pendiente Tela': { bg: '#f59e0b', color: '#ffffff', border: '#d97706' },
            'PENDIENTE_TELA': { bg: '#f59e0b', color: '#ffffff', border: '#d97706' },
            'Pendiente Plotter': { bg: '#9ca3af', color: '#ffffff', border: '#6b7280' },
            'PENDIENTE_PLOTTER': { bg: '#9ca3af', color: '#ffffff', border: '#6b7280' },
            'Insumos Pedidos': { bg: '#10b981', color: '#ffffff', border: '#059669' },
            'INSUMOS_PEDIDOS': { bg: '#10b981', color: '#ffffff', border: '#059669' },
            'DEVUELTO_ASESOR': { bg: '#ef4444', color: '#ffffff', border: '#dc2626' },
            'Devuelto Asesor': { bg: '#ef4444', color: '#ffffff', border: '#dc2626' },
            'Anulada': { bg: '#fecaca', color: '#991b1b', border: '#fca5a5' },
            'ANULADO': { bg: '#fecaca', color: '#991b1b', border: '#fca5a5' }
        };
        
        const estilo = estilosEstado[estado] || { bg: '#e5e7eb', color: '#1f2937', border: '#d1d5db' };
        selectElement.style.backgroundColor = estilo.bg;
        selectElement.style.color = estilo.color;
        selectElement.style.borderColor = estilo.border;
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
