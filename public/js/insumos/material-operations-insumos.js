/**
 * Material Operations for Insumos/Materiales Module
 * Handles CRUD operations: create, read, update, delete materials
 */

// ===== DELETION OPERATIONS =====

function confirmarEliminacion(checkbox, materialId) {
    // Si se deselecciona, mostrar modal de confirmación
    if (!checkbox.checked) {
        // Obtener datos del material
        const fila = checkbox.closest('tr');
        const celdas = fila.querySelectorAll('td');
        const nombreMaterial = celdas[0].textContent.trim().replace(/^[•●○◐◑\s]+/, '').trim();
        
        const inputsFecha = fila.querySelectorAll('input[type="date"]');
        const fechaPedido = inputsFecha[0]?.value || 'No especificada';
        const fechaLlegada = inputsFecha[1]?.value || 'No especificada';
        
        // Obtener el pedido del modal (es más confiable)
        const ordenPedido = document.getElementById('modalPedido').textContent;
        
        // Mostrar modal de confirmación
        Swal.fire({
            title: '¿Eliminar Material?',
            html: `<div style="text-align: left; margin: 20px 0;">
                <p><strong>Material:</strong> ${nombreMaterial}</p>
                <p><strong>Fecha Pedido:</strong> ${fechaPedido}</p>
                <p><strong>Fecha Llegada:</strong> ${fechaLlegada}</p>
                <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro y todos sus datos.</strong></p>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10020';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Eliminar inmediatamente sin guardar
                eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila);
            } else {
                // Volver a seleccionar si cancela
                checkbox.checked = true;
            }
        });
    }
}

function eliminarMaterialInmediatamente(nombreMaterial, ordenPedido, fila) {
    Swal.showLoading();
    
    fetch(`/insumos/materiales/${ordenPedido}/eliminar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ 
            nombre_material: nombreMaterial
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Eliminar la fila con animación
            fila.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                fila.remove();
                showToast('Material eliminado correctamente', 'success');
                Swal.hideLoading();
                Swal.close();
            }, 300);
        } else {
            showToast('Error al eliminar: ' + data.message, 'error');
            Swal.hideLoading();
            Swal.close();
            // Volver a marcar el checkbox si falla
            const checkbox = fila.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
        }
    })
    .catch(error => {
        showToast('Error al eliminar el material', 'error');
        Swal.hideLoading();
        Swal.close();
        // Volver a marcar el checkbox si falla
        const checkbox = fila.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = true;
    });
}

function eliminarFilaMaterial(materialId) {
    const row = document.getElementById(`row_${materialId}`);
    const checkbox = document.getElementById(`checkbox_${materialId}`);
    
    if (row && checkbox) {
        // Obtener nombre del material
        const nombreMaterial = row.querySelector('td:first-child span').textContent.trim();
        const pedido = document.getElementById('modalPedido').textContent;
        
        // Verificar si la fila es nueva (aún no guardada en BD)
        const esFilaNueva = row.hasAttribute('data-nuevo') || !row.dataset.guardado;
        
        // Mostrar confirmación
        Swal.fire({
            title: '¿Eliminar Material?',
            html: `<div style="text-align: left; margin: 20px 0;">
                <p><strong>Material:</strong> ${nombreMaterial}</p>
                <p style="color: #ef4444; margin-top: 15px;"><strong><i class="fas fa-exclamation-triangle"></i> Se eliminará este registro${esFilaNueva ? '' : ' permanentemente'}.</strong></p>
            </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10020';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (esFilaNueva) {
                    // Si es nueva, solo remover del DOM sin llamar al servidor
                    row.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => {
                        row.remove();
                        showToast('Material eliminado', 'success');
                    }, 300);
                } else {
                    // Eliminar del servidor
                    fetch(`/insumos/materiales/${pedido}/eliminar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ 
                            nombre_material: nombreMaterial
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Eliminar fila con animación
                            row.style.animation = 'slideOut 0.3s ease-out';
                            setTimeout(() => {
                                row.remove();
                                showToast('Material eliminado', 'success');
                            }, 300);
                        } else {
                            showToast('Error al eliminar: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error al eliminar el material', 'error');
                    });
                }
            }
        });
    }
}

function eliminarMaterial(materialId) {
    const checkbox = document.getElementById(`checkbox_${materialId}`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.style.opacity = '0.5';
    }
}

// ===== SAVING OPERATIONS =====

function guardarObservaciones() {
    const modal = document.getElementById('observacionesModal');
    const materialId = modal.getAttribute('data-material-id');
    const pedido = modal.getAttribute('data-pedido');
    const observaciones = document.getElementById('observacionesTexto').value;
    
    if (!materialId) {
        showToast('Error: No se pudo identificar el material', 'error');
        return;
    }
    
    if (!pedido) {
        showToast('Error: No se pudo identificar el pedido', 'error');
        return;
    }
    
    // Guardar en el input hidden
    const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
    if (inputObservaciones) {
        inputObservaciones.value = observaciones;
    }
    
    // Obtener el nombre del material
    const fila = document.getElementById(`row_${materialId}`);
    let nombreMaterial = '';
    if (fila) {
        const primeraColumna = fila.querySelector('td:first-child span');
        if (primeraColumna) {
            nombreMaterial = primeraColumna.textContent.trim();
        }
    }
    
    // Obtener el estado actual del checkbox
    const checkbox = fila ? fila.querySelector('input[type="checkbox"]') : null;
    const recibido = checkbox ? checkbox.checked : false;
    
    // Obtener todas las fechas
    const todosInputsFecha = fila ? fila.querySelectorAll('input[type="date"]') : [];
    const fechaOrden = todosInputsFecha[0]?.value || null;
    const fechaPedido = todosInputsFecha[1]?.value || null;
    const fechaPago = todosInputsFecha[2]?.value || null;
    const fechaLlegada = todosInputsFecha[3]?.value || null;
    const fechaDespacho = todosInputsFecha[4]?.value || null;
    
    // Enviar directamente al servidor
    fetch(`/insumos/materiales/${pedido}/guardar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ 
            materiales: [{
                nombre: nombreMaterial || `Material ${materialId}`,
                fecha_orden: fechaOrden,
                fecha_pedido: fechaPedido,
                fecha_pago: fechaPago,
                fecha_llegada: fechaLlegada,
                fecha_despacho: fechaDespacho,
                observaciones: observaciones || null,
                recibido: recibido,
            }]
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Observaciones guardadas correctamente', 'success');
            // Actualizar el input hidden para que se refleje en futuras aperturas
            const inputObservaciones = document.getElementById(`observaciones_${materialId}`);
            if (inputObservaciones) {
                inputObservaciones.value = observaciones;
            }
            // Recargar los datos del modal para asegurar sincronización
            fetch(`/insumos/api/materiales/${pedido}`)
                .then(response => response.json())
                .then(fetchData => {
                    if (fetchData.materiales) {
                        llenarTablaInsumos(fetchData.materiales || []);
                    }
                })
                .catch(err => console.error('Error recargando datos:', err));
        } else {
            showToast('Error al guardar observaciones: ' + (data.message || ''), 'error');
        }
        cerrarModalObservaciones();
    })
    .catch(error => {
        showToast('Error al guardar observaciones: ' + error.message, 'error');
    });
}

function guardarInsumosModal() {
    const pedido = document.getElementById('modalPedido').textContent;
    const prendaId = document.getElementById('modalPrendaId').value;
    const materiales = [];
    
    // Recopilar todos los materiales del modal
    const tbody = document.getElementById('insumosTableBody');
    const filas = tbody.querySelectorAll('tr');
    
    filas.forEach((fila) => {
        const celdas = fila.querySelectorAll('td');
        
        // Obtener nombre del material
        const nombreMaterialEl = celdas[0];
        let nombreMaterial = nombreMaterialEl.textContent.trim();
        nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
        
        // Obtener checkbox y fechas
        const checkbox = fila.querySelector('input[type="checkbox"]');
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const fechaOrdenInput = todosInputsFecha[0];
        const fechaPedidoInput = todosInputsFecha[1];
        const fechaPagoInput = todosInputsFecha[2];
        const fechaLlegadaInput = todosInputsFecha[3];
        const fechaDespachoInput = todosInputsFecha[4];
        
        const recibido = checkbox?.checked || false;
        const fechaOrden = fechaOrdenInput?.value || '';
        const fechaPedido = fechaPedidoInput?.value || '';
        const fechaPago = fechaPagoInput?.value || '';
        const fechaLlegada = fechaLlegadaInput?.value || '';
        const fechaDespacho = fechaDespachoInput?.value || '';
        
        // Obtener observaciones del input hidden
        const inputObservaciones = fila.querySelector(`input[type="hidden"][id^="observaciones_"]`);
        const observaciones = inputObservaciones ? inputObservaciones.value : '';
        
        // Agregar si está marcado o tiene fechas
        if (recibido || fechaOrden || fechaPedido || fechaPago || fechaLlegada || fechaDespacho || observaciones) {
            materiales.push({
                nombre: nombreMaterial,
                fecha_orden: fechaOrden || null,
                fecha_pedido: fechaPedido || null,
                fecha_pago: fechaPago || null,
                fecha_llegada: fechaLlegada || null,
                fecha_despacho: fechaDespacho || null,
                recibido: recibido,
                observaciones: observaciones || null,
            });
        }
    });
    
    // Enviar al servidor
    fetch(`/insumos/materiales/${pedido}/guardar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ materiales, prenda_id: prendaId || null }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Materiales guardados correctamente', 'success');
        } else {
            showToast('Error al guardar', 'error');
        }
        cerrarModalInsumos();
    })
    .catch(error => {
        showToast('Error al guardar los materiales', 'error');
    });
}

// ===== EXPORT TO WINDOW =====

document.addEventListener('DOMContentLoaded', function() {
    window.confirmarEliminacion = confirmarEliminacion;
    window.eliminarMaterialInmediatamente = eliminarMaterialInmediatamente;
    window.eliminarFilaMaterial = eliminarFilaMaterial;
    window.eliminarMaterial = eliminarMaterial;
    window.guardarObservaciones = guardarObservaciones;
    window.guardarInsumosModal = guardarInsumosModal;
});
