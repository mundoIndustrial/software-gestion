// bodega-cell-edit.js - Edición de celdas individuales

let currentCellData = {
    pedido: null,
    column: null,
    value: null,
    element: null
};

/**
 * Abrir modal para editar una celda
 */
function openCellEditModal(column, value, pedido) {
    console.log(` Abriendo edición de celda: ${column} = ${value}`);
    
    currentCellData = {
        pedido: pedido,
        column: column,
        value: value,
        element: null
    };

    // Actualizar título y label del modal
    const columnLabels = {
        'estado': 'Estado',
        'area': 'Área',
        'novedades': 'Novedades',
        'encargado_orden': 'Encargado Orden',
        'dias_orden': 'Días Orden',
        'encargados_inventario': 'Encargados Inventario',
        'dias_inventario': 'Días Inventario',
        'encargados_insumos': 'Encargados Insumos',
        'dias_insumos': 'Días Insumos',
        'encargados_de_corte': 'Encargados Corte',
        'dias_corte': 'Días Corte',
        'codigo_de_bordado': 'Código Bordado',
        'dias_bordado': 'Días Bordado',
        'encargados_estampado': 'Encargados Estampado',
        'dias_estampado': 'Días Estampado',
        'modulo': 'Módulo',
        'dias_costura': 'Días Costura',
        'encargado_reflectivo': 'Encargado Reflectivo',
        'total_de_dias_reflectivo': 'Total Días Reflectivo',
        'encargado_lavanderia': 'Encargado Lavandería',
        'dias_lavanderia': 'Días Lavandería',
        'encargado_arreglos': 'Encargado Arreglos',
        'total_de_dias_arreglos': 'Total Días Arreglos',
        'encargados_marras': 'Encargados Marras',
        'total_de_dias_marras': 'Total Días Marras',
        'encargados_calidad': 'Encargados Calidad',
        'dias_c_c': 'Días Control Calidad',
        'encargados_entrega': 'Encargados Entrega'
    };

    const label = columnLabels[column] || column;
    document.getElementById('cellModalTitle').textContent = `Editar ${label}`;
    document.getElementById('cellModalLabel').textContent = `${label}:`;
    document.getElementById('cellModalInput').value = value || '';
    document.getElementById('cellModalInput').focus();

    // Mostrar modal
    const modal = document.getElementById('bodegaCellEditModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log(' Modal de edición de celda abierto');
    }
}

/**
 * Cerrar modal de edición de celda
 */
function closeCellEditModal() {
    console.log(' Cerrando modal de edición de celda');
    const modal = document.getElementById('bodegaCellEditModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentCellData = {
        pedido: null,
        column: null,
        value: null,
        element: null
    };
}

/**
 * Guardar cambio de celda
 */
async function saveCellEdit() {
    const newValue = document.getElementById('cellModalInput').value;
    
    if (!currentCellData.pedido || !currentCellData.column) {
        console.error(' Datos incompletos');
        return;
    }

    console.log(`💾 Guardando cambio: ${currentCellData.column} = ${newValue}`);

    try {
        // Preparar datos para enviar
        const payload = {
            [currentCellData.column]: newValue
        };

        console.log('📤 Enviando PATCH a /bodega/' + currentCellData.pedido, payload);

        // Enviar PATCH
        const response = await fetch(`/bodega/${currentCellData.pedido}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log(' Cambio guardado:', result);

        // Actualizar celda en la tabla
        if (currentCellData.element) {
            currentCellData.element.textContent = newValue;
            console.log(' Celda actualizada en la tabla');
        }

        // Mostrar notificación
        showCellEditNotification('Cambio guardado correctamente', 'success');

        // Cerrar modal
        closeCellEditModal();

        // Recargar tabla después de 1 segundo
        setTimeout(() => {
            location.reload();
        }, 1000);

    } catch (error) {
        console.error(' Error al guardar:', error);
        showCellEditNotification('Error al guardar el cambio: ' + error.message, 'error');
    }
}

/**
 * Mostrar notificación
 */
function showCellEditNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10001;
        animation: slideIn 0.3s ease;
        font-weight: 600;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Permitir editar celda con doble clic
 */
document.addEventListener('dblclick', function(e) {
    const cell = e.target.closest('td.table-cell');
    if (!cell) return;

    const row = cell.closest('tr');
    if (!row) return;

    const pedido = row.dataset.ordenId || row.dataset.numeroPedido;
    const column = cell.dataset.column;
    const value = cell.textContent.trim();

    if (pedido && column && column !== 'acciones') {
        console.log(`🖱️ Doble clic en celda: ${column}`);
        openCellEditModal(pedido, column, value, cell.querySelector('.cell-text'));
    }
});

/**
 * Permitir guardar con Enter
 */
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('bodegaCellEditModal');
    if (!modal || modal.style.display === 'none') return;

    if (e.key === 'Enter') {
        e.preventDefault();
        saveCellEdit();
    } else if (e.key === 'Escape') {
        e.preventDefault();
        closeCellEditModal();
    }
});

console.log(' Bodega Cell Edit Script Loaded');
