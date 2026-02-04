/**
 * pedidos-modal-edit.js
 * Funciones para editar pedidos, prendas y EPP en modales
 * Utilizado por asesores/pedidos/index.blade.php y supervisor-pedidos/index.blade.php
 */

/**
 * Abrir formulario para editar datos generales del pedido
 */
function abrirEditarDatos() {
    Validator.requireEdicionPedido(() => {
        const datos = window.datosEdicionPedido;
    
    const html = `
        <div style="text-align: left;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Cliente</label>
                <input type="text" id="editCliente" value="${datos.cliente || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Forma de Pago</label>
                <input type="text" id="editFormaPago" value="${datos.forma_de_pago || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem;">
            </div>
        </div>
    `;
    
    UI.contenido({
        titulo: ' Editar Datos Generales',
        html: html,
        confirmButtonText: ' Guardar',
        confirmButtonColor: '#10b981',
        showCancelButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            const datosActualizados = {
                cliente: document.getElementById('editCliente').value,
                forma_de_pago: document.getElementById('editFormaPago').value
            };
            
            // Abrir modal de justificación ANTES de guardar
            abrirModalJustificacionCambio(datos.id || datos.numero_pedido, datosActualizados);
        }
    });
    });
}

/**
 * Abre un modal para justificar los cambios del pedido
 */
function abrirModalJustificacionCambio(pedidoId, datosActualizados) {
    const html = `
        <div style="text-align: left;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">¿Por qué hiciste este cambio?</label>
                <textarea id="justificacionCambio" 
                    placeholder="Explica brevemente el motivo de los cambios..." 
                    style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.95rem; min-height: 100px; resize: vertical;">
                </textarea>
            </div>
        </div>
    `;
    
    UI.contenido({
        titulo: 'Registrar Novedad del Cambio',
        html: html,
        confirmButtonText: ' Confirmar y Guardar',
        confirmButtonColor: '#10b981',
        showCancelButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            const justificacion = document.getElementById('justificacionCambio').value.trim();
            
            if (!justificacion) {
                showNotification('Debes ingresar una novedad del cambio', 'warning');
                // Reabrir modal si no hay justificación
                setTimeout(() => abrirModalJustificacionCambio(pedidoId, datosActualizados), 300);
                return;
            }
            
            // Agregar justificación a los datos
            datosActualizados.justificacion = justificacion;
            
            // Ahora sí guardar
            guardarCambiosPedido(pedidoId, datosActualizados);
        }
    });
}

/**
 * Guardar cambios del pedido en el backend
 * FIX: Usar async/await para mejor manejo de race conditions
 */
async function guardarCambiosPedido(pedidoId, datosActualizados) {
    try {
        //  Esperar a que Swal esté disponible
        await _ensureSwal();
        
        UI.cargando('Guardando cambios...', 'Por favor espera');
        
        //  Hacer fetch
        const response = await fetch(`/api/pedidos/${pedidoId}/actualizar-descripcion`, {
            method: 'PATCH',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                cliente: datosActualizados.cliente || '',
                forma_de_pago: datosActualizados.forma_de_pago || '',
                justificacion: datosActualizados.justificacion || ''
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        //  Actualizar la fila en la tabla en tiempo real
        if (data.data) {
            actualizarFilaTabla(pedidoId, data.data);
        }
        
        //  Cerrar modal de carga ANTES de abrir el siguiente
        Swal.close();
        
        //  Actualizar los datos globales
        if (window.datosEdicionPedido) {
            window.datosEdicionPedido.cliente = datosActualizados.cliente;
            window.datosEdicionPedido.forma_de_pago = datosActualizados.forma_de_pago;
            if (data.data && data.data.novedades) {
                window.datosEdicionPedido.novedades = data.data.novedades;
            }
        }
        
        //  Esperar a que Swal esté disponible para mostrar éxito
        await _ensureSwal();
        
        //  Mostrar modal de confirmación para continuar editando
        Swal.fire({
            title: ' Guardado Exitosamente',
            text: '¿Deseas continuar editando este pedido?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, continuar editando',
            cancelButtonText: 'No, cerrar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Volver a abrir el modal de edición del pedido
                abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');
            }
            // Ya no necesitamos recargar la página
        });
        
    } catch (error) {
        // Cerrar modal de carga
        Swal.close();
        
        UI.error('Error al guardar', error.message || 'Ocurrió un error al guardar los cambios');
    }
}

/**
 * actualizarFilaTabla()
 * Actualiza la fila de la tabla en tiempo real sin recargar la página
 */
function actualizarFilaTabla(pedidoId, pedidoActualizado) {
    try {
        // Buscar la fila correspondiente en la tabla
        const filas = document.querySelectorAll('[data-pedido-row]');
        
        filas.forEach((fila) => {
            // Verificar si esta fila corresponde al pedido actualizado
            const btnEditarEnFila = fila.querySelector(`button[onclick*="editarPedido(${pedidoId})"]`);
            
            if (btnEditarEnFila) {
                // Actualizar cliente
                const cellasCliente = fila.querySelectorAll('div');
                let indiceCliente = 4; // Índice aproximado de la celda de cliente
                if (cellasCliente[indiceCliente]) {
                    cellasCliente[indiceCliente].textContent = pedidoActualizado.cliente || '-';
                }
                
                // Actualizar novedades
                let indiceNovedades = 6; // Índice aproximado de la celda de novedades
                if (cellasCliente[indiceNovedades]) {
                    if (pedidoActualizado.novedades && pedidoActualizado.novedades.trim()) {
                        cellasCliente[indiceNovedades].textContent = pedidoActualizado.novedades;
                        cellasCliente[indiceNovedades].style.cursor = 'pointer';
                        cellasCliente[indiceNovedades].onclick = function() {
                            abrirModalNovedades(pedidoActualizado.numero_pedido, pedidoActualizado.novedades);
                        };
                    } else {
                        cellasCliente[indiceNovedades].innerHTML = '<span style="color: #d1d5db;">-</span>';
                    }
                }
                
                // Actualizar forma de pago
                let indiceFormaPago = 7; // Índice aproximado de la celda de forma de pago
                if (cellasCliente[indiceFormaPago]) {
                    cellasCliente[indiceFormaPago].textContent = pedidoActualizado.forma_de_pago || '-';
                    cellasCliente[indiceFormaPago].style.cursor = 'pointer';
                    cellasCliente[indiceFormaPago].onclick = function() {
                        abrirModalCelda('Forma de Pago', pedidoActualizado.forma_de_pago || '-');
                    };
                }
                
                // Animar la actualización
                fila.style.backgroundColor = '#fef3c7';
                setTimeout(() => {
                    fila.style.transition = 'background-color 0.5s ease';
                    fila.style.backgroundColor = 'white';
                }, 100);
            }
        });
        
    } catch (error) {
    }
}

/**
 * abrirEditarPrendas() - Placeholder
 * La implementación real está en modal-prendas-lista.blade.php
 */
function abrirEditarPrendas() {
    console.log('[abrirEditarPrendas] Delegando a modal-prendas-lista');
}

/**
 * abrirEditarEPP() - Placeholder
 * La implementación real está en modal-editar-epp.blade.php
 */
function abrirEditarEPP() {
    console.log('[abrirEditarEPP] Delegando a modal-editar-epp');
}
