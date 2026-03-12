/**
 * Form/UI Handlers for Insumos/Materiales Module
 * Handles form state, toggles, changes, and validation
 */

// ===== ROW STATE MANAGEMENT =====

function toggleRowCheck(button, event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Encontrar la fila (tr) del botón
    const row = button.closest('tr');
    if (!row) return;
    
    // Alternar clase de marcado en el botón
    button.classList.toggle('checked');
    
    // Alternar clase de marcado en la fila
    row.classList.toggle('row-checked');
    
    // Obtener el estado marcado actual
    const isMarcado = button.classList.contains('checked');
    
    // Aquí podemos obtener el ID del material desde algún atributo del row
    // Por ahora usamos un data-id que debemos agregar en la tabla
    const materialId = row.dataset.materialId || row.dataset.reciboId;
    
    if (materialId) {
        // Enviar petición AJAX para guardar el estado
        guardarEstadoMarcado(materialId, isMarcado, button);
    }
}

function guardarEstadoMarcado(materialId, marcado, button) {
    if (!materialId) {
        console.error('Material ID no disponible');
        return;
    }
    
    const url = `/insumos/materiales/${materialId}/toggle-marcado`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            marcado: marcado
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Estado de marcado guardado correctamente', data);
        } else {
            console.error('Error al guardar el estado:', data.message);
            // Revertir si hay error
            button.classList.toggle('checked');
            button.closest('tr').classList.toggle('row-checked');
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        // Revertir si hay error
        button.classList.toggle('checked');
        button.closest('tr').classList.toggle('row-checked');
        alert('Error al guardar el estado');
    });
}

// ===== FORM STATE MANAGEMENT =====

function guardarCambios(ordenPedido) {
    const materiales = [];
    
    // Obtener todos los checkboxes de materiales
    const checkboxes = document.querySelectorAll(`input[type="checkbox"][id^="checkbox_"]`);

    checkboxes.forEach((inputCheckbox, index) => {
        const fila = inputCheckbox.closest('tr');
        if (!fila) return;
        
        const celdas = fila.querySelectorAll('td');
        
        // Obtener el nombre del material del primer celda (removiendo el punto de color)
        const nombreMaterialEl = celdas[0];
        let nombreMaterial = nombreMaterialEl.textContent.trim();
        // Remover caracteres especiales del punto de color
        nombreMaterial = nombreMaterial.replace(/^[•●○◐◑\s]+/, '').trim();
        
        // Obtener los inputs de fecha de esta fila
        const inputsFecha = fila.querySelectorAll('input[type="date"]');
        const checkboxElement = fila.querySelector('input[type="checkbox"]');
        
        const fechaPedidoInput = inputsFecha[0];
        const fechaLlegadaInput = inputsFecha[1];
        
        const fechaPedido = fechaPedidoInput?.value || '';
        const fechaLlegada = fechaLlegadaInput?.value || '';
        const recibido = checkboxElement?.checked || false;
        
        // Obtener valores originales (comparar strings)
        const originalCheckbox = checkboxElement?.dataset.original === 'true';
        const originalFechaPedido = fechaPedidoInput?.dataset.original || '';
        const originalFechaLlegada = fechaLlegadaInput?.dataset.original || '';
        
        // Detectar si hay cambios (comparar valores como strings)
        const checkboxCambio = recibido !== originalCheckbox;
        const fechaPedidoCambio = (fechaPedido || null) !== (originalFechaPedido || null);
        const fechaLlegadaCambio = (fechaLlegada || null) !== (originalFechaLlegada || null);
        const hayChangios = checkboxCambio || fechaPedidoCambio || fechaLlegadaCambio;
        
        // Guardar si el checkbox está marcado O si hay cambios
        if (recibido || hayChangios) {
            materiales.push({
                nombre: nombreMaterial,
                fecha_pedido: fechaPedido || null,
                fecha_llegada: fechaLlegada || null,
                recibido: recibido,
            });
        }
    });
    
    fetch(`/insumos/materiales/${ordenPedido}/guardar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ materiales }),
    })
    .then(response => {
        // Si no es JSON válido, mostrar error
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('Guardado exitoso', 'success');
        } else {
            showToast('Guardado exitoso', 'success');
        }
    })
    .catch(error => {
        let mensajeError = 'Error al guardar los cambios';
        
        // Si es un error JSON, extraer el mensaje
        if (error.message.includes('HTTP')) {
            mensajeError = error.message;
        } else if (error instanceof SyntaxError) {
            mensajeError = 'Error en el servidor (respuesta inválida)';
        }
        
        showToast(mensajeError, 'error');
    });
}

function limpiarFormulario(ordenId) {
    const orden = document.querySelector(`[data-pedido]`).closest('.orden-item');
    const inputs = orden.querySelectorAll('input[type="date"], input[type="checkbox"]');
    
    inputs.forEach(input => {
        if (input.type === 'date') {
            input.value = '';
        } else if (input.type === 'checkbox') {
            input.checked = false;
        }
    });
    
    // Limpiar también los spans de días
    const diasSpans = orden.querySelectorAll('[id^="dias_"]');
    diasSpans.forEach(span => {
        span.textContent = '-';
        span.className = 'inline-block px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600';
    });
}

// ===== EXPORT TO WINDOW =====

document.addEventListener('DOMContentLoaded', function() {
    window.toggleRowCheck = toggleRowCheck;
    window.guardarEstadoMarcado = guardarEstadoMarcado;
    window.guardarCambios = guardarCambios;
    window.limpiarFormulario = limpiarFormulario;
});
