const updateDebounceMap = new Map();

// Función para recargar la tabla de pedidos
async function recargarTablaPedidos() {
    try {
        const response = await fetch(window.fetchUrl + window.location.search, {
            headers: {
                'Accept': 'application/json'
            }
        });
        if (!response.ok) {
            console.error('Error al cargar datos de pedidos:', response.statusText);
            return;
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Respuesta no es JSON:', await response.text());
            return;
        }
        const data = await response.json();

        // Reconstruir cuerpo de la tabla
        const tbody = document.getElementById('tablaOrdenesBody');
        if (!tbody) {
            console.error('No se encontró el elemento tbody para la tabla de pedidos');
            return;
        }
        tbody.innerHTML = '';

        if (data.orders.length === 0) {
            tbody.innerHTML = `
                <tr class="table-row">
                    <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                        No hay resultados que coincidan con los filtros aplicados.
                    </td>
                </tr>
            `;
        } else {
            // Obtener las columnas del thead EXCLUYENDO la primera (acciones)
            const theadRow = document.querySelector('#tablaOrdenes thead tr');
            const ths = Array.from(theadRow.querySelectorAll('th'));
            const dataColumns = ths.slice(1).map(th => th.dataset.column).filter(col => col); // Saltar la primera columna de acciones

            data.orders.forEach(orden => {
                const totalDias = data.totalDiasCalculados[orden.pedido] ?? 0;
                const diaDeEntrega = orden.dia_de_entrega ? parseInt(orden.dia_de_entrega) : null;
                
                // Debug: Log para verificar valores
                if (diaDeEntrega !== null) {
                    console.log(`🔍 Orden ${orden.pedido}: diaDeEntrega=${diaDeEntrega}, totalDias=${totalDias}`);
                }
                
                let conditionalClass = '';
                
                // PRIORIDAD 1: Estados especiales
                if (orden.estado === 'Entregado') {
                    conditionalClass = 'row-delivered';
                } else if (orden.estado === 'Anulada') {
                    conditionalClass = 'row-anulada';
                }
                // PRIORIDAD 2: NUEVA LÓGICA - Día de entrega (si existe)
                else if (diaDeEntrega !== null && diaDeEntrega > 0) {
                    if (totalDias >= 15) {
                        conditionalClass = 'row-dia-entrega-critical'; // Negro (15+)
                        console.log(`✅ Aplicando NEGRO a orden ${orden.pedido}`);
                    } else if (totalDias >= 10 && totalDias <= 14) {
                        conditionalClass = 'row-dia-entrega-danger'; // Rojo (10-14)
                        console.log(`✅ Aplicando ROJO a orden ${orden.pedido}`);
                    } else if (totalDias >= 5 && totalDias <= 9) {
                        conditionalClass = 'row-dia-entrega-warning'; // Amarillo (5-9)
                        console.log(`✅ Aplicando AMARILLO a orden ${orden.pedido}`);
                    }
                    // Si totalDias < 5, no se aplica ninguna clase (sin color)
                }
                // PRIORIDAD 3: LÓGICA ORIGINAL - Solo si NO hay día de entrega
                else {
                    if (totalDias > 20) {
                        conditionalClass = 'row-secondary';
                    } else if (totalDias === 20) {
                        conditionalClass = 'row-danger-light';
                    } else if (totalDias > 14 && totalDias < 20) {
                        conditionalClass = 'row-warning';
                    }
                }

                const tr = document.createElement('tr');
                tr.className = `table-row ${conditionalClass}`;
                tr.dataset.orderId = orden.pedido;

                // SIEMPRE crear primero la columna de acciones
                const accionesTd = document.createElement('td');
                accionesTd.className = 'table-cell acciones-column';
                const accionesDiv = document.createElement('div');
                accionesDiv.className = 'cell-content';
                accionesDiv.innerHTML = `
                    <button class="action-btn delete-btn" onclick="deleteOrder(${orden.pedido})"
                        title="Eliminar orden"
                        style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; margin-right: 5px; border-radius: 4px; cursor: pointer;">
                        Borrar
                    </button>
                    <button class="action-btn detail-btn" onclick="viewDetail(${orden.pedido})"
                        title="Ver detalle"
                        style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                        Ver
                    </button>
                `;
                accionesTd.appendChild(accionesDiv);
                tr.appendChild(accionesTd);

                // Ahora crear las demás columnas basándose SOLO en las columnas de datos
                dataColumns.forEach(column => {
                    const valor = orden[column] !== undefined && orden[column] !== null ? orden[column] : '';
                    const td = document.createElement('td');
                    td.className = 'table-cell';
                    td.dataset.column = column;

                    const div = document.createElement('div');
                    div.className = 'cell-content';
                    div.title = valor;

                    if (column === 'estado') {
                        const select = document.createElement('select');
                        select.className = 'estado-dropdown';
                        select.dataset.id = orden.pedido;
                        select.dataset.value = valor;

                        ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'].forEach(estado => {
                            const option = document.createElement('option');
                            option.value = estado;
                            option.textContent = estado;
                            if (estado === valor) option.selected = true;
                            select.appendChild(option);
                        });
                        div.appendChild(select);
                    } else if (column === 'area') {
                        const select = document.createElement('select');
                        select.className = 'area-dropdown';
                        select.dataset.id = orden.pedido;
                        select.dataset.value = valor;

                        // Usar areaOptions del data o del window
                        const areas = data.areaOptions || window.areaOptions || [];
                        areas.forEach(areaOption => {
                            const option = document.createElement('option');
                            option.value = areaOption;
                            option.textContent = areaOption;
                            if (areaOption === valor) option.selected = true;
                            select.appendChild(option);
                        });
                        div.appendChild(select);
                    } else if (column === 'dia_de_entrega' && window.modalContext === 'orden') {
                        const select = document.createElement('select');
                        select.className = 'dia-entrega-dropdown';
                        select.dataset.id = orden.pedido;
                        
                        // IMPORTANTE: Normalizar el valor (null, undefined, '' → '')
                        const diasValue = (valor === null || valor === undefined || valor === '') ? '' : String(valor);
                        select.dataset.value = diasValue;

                        // Opción "Seleccionar" por defecto
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'Seleccionar';
                        if (diasValue === '') defaultOption.selected = true;
                        select.appendChild(defaultOption);

                        // Opciones de días
                        [15, 20, 25, 30].forEach(dias => {
                            const option = document.createElement('option');
                            option.value = dias;
                            option.textContent = `${dias} días`;
                            if (String(dias) === diasValue) option.selected = true;
                            select.appendChild(option);
                        });
                        
                        div.appendChild(select);
                        
                        // Debug: Verificar que se creó correctamente
                        console.log(`🔧 Dropdown creado para orden ${orden.pedido}: valor="${diasValue}", selected="${select.value}"`);
                    } else {
                        const span = document.createElement('span');
                        span.className = 'cell-text';
                        if (column === 'total_de_dias_') {
                            span.textContent = totalDias;
                        } else {
                            span.textContent = valor;
                        }
                        div.appendChild(span);
                    }

                    td.appendChild(div);
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });
        }

        // Actualizar paginación
        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.innerHTML = data.pagination_html;
        }

        // Re-inicializar dropdowns y eventos
        initializeStatusDropdowns();
        initializeAreaDropdowns();
        initializeDiaEntregaDropdowns();

    } catch (error) {
        console.error('Error al recargar tabla de pedidos:', error);
    }
}

function initializeStatusDropdowns() {
    document.querySelectorAll('.estado-dropdown').forEach(dropdown => {
        // Establecer color inicial basado en el valor seleccionado
        dropdown.setAttribute('data-value', dropdown.value);

        // Limpiar eventos anteriores para evitar duplicados
        dropdown.removeEventListener('change', handleStatusChange);

        // Cambiar color cuando se selecciona una nueva opción
        dropdown.addEventListener('change', handleStatusChange);
    });
}

function initializeAreaDropdowns() {
    document.querySelectorAll('.area-dropdown').forEach(dropdown => {
        // Establecer color inicial basado en el valor seleccionado
        dropdown.setAttribute('data-value', dropdown.value);

        // Limpiar eventos anteriores para evitar duplicados
        dropdown.removeEventListener('change', handleAreaChange);

        // Cambiar color cuando se selecciona una nueva opción
        dropdown.addEventListener('change', handleAreaChange);
    });
}

// Manejador de cambio de estado
function handleStatusChange() {
    this.setAttribute('data-value', this.value);
    updateOrderStatus(this.dataset.id, this.value);
}

// Manejador de cambio de area
function handleAreaChange() {
    this.setAttribute('data-value', this.value);
    updateOrderArea(this.dataset.id, this.value);
}

// OPTIMIZACIÓN: Debounce map para evitar múltiples requests simultáneos
const updateStatusDebounce = new Map();

// Función para actualizar estado en la base de datos
function updateOrderStatus(orderId, newStatus) {
    const dropdown = document.querySelector(`.estado-dropdown[data-id="${orderId}"]`);
    const oldStatus = dropdown ? dropdown.dataset.value : '';
    
    // OPTIMIZACIÓN: Debounce de 300ms para evitar requests duplicados
    const debounceKey = `status-${orderId}`;
    if (updateStatusDebounce.has(debounceKey)) {
        clearTimeout(updateStatusDebounce.get(debounceKey));
    }
    
    const timeoutId = setTimeout(() => {
        updateStatusDebounce.delete(debounceKey);
        executeStatusUpdate(orderId, newStatus, oldStatus, dropdown);
    }, 300);
    
    updateStatusDebounce.set(debounceKey, timeoutId);
}

// Función auxiliar para ejecutar la actualización
function executeStatusUpdate(orderId, newStatus, oldStatus, dropdown) {
    fetch(`${window.updateUrl}/${orderId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ estado: newStatus })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Estado actualizado correctamente');
                // Actualizar color de la fila dinámicamente
                updateRowColor(orderId, newStatus);

                // Enviar mensaje a otras pestañas usando localStorage
                const timestamp = Date.now();
                localStorage.setItem('orders-updates', JSON.stringify({
                    type: 'status_update',
                    orderId: orderId,
                    field: 'estado',
                    newValue: newStatus,
                    oldValue: oldStatus,
                    updatedFields: data.updated_fields || {},
                    order: data.order,
                    totalDiasCalculados: data.totalDiasCalculados || {},
                    timestamp: timestamp // Para evitar duplicados
                }));
                // Actualizar timestamp local para evitar procesar mensaje propio
                localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
            } else {
                console.error('Error al actualizar el estado:', data.message);
                // Revertir cambio en caso de error
                if (dropdown) dropdown.value = oldStatus;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revertir cambio en caso de error
            if (dropdown) dropdown.value = oldStatus;
        });
}

// OPTIMIZACIÓN: Debounce map para área
const updateAreaDebounce = new Map();

// Función para actualizar area en la base de datos
function updateOrderArea(orderId, newArea) {
    const dropdown = document.querySelector(`.area-dropdown[data-id="${orderId}"]`);
    const oldArea = dropdown ? dropdown.dataset.value : '';
    
    // OPTIMIZACIÓN: Debounce de 300ms para evitar requests duplicados
    const debounceKey = `area-${orderId}`;
    if (updateAreaDebounce.has(debounceKey)) {
        clearTimeout(updateAreaDebounce.get(debounceKey));
    }
    
    const timeoutId = setTimeout(() => {
        updateAreaDebounce.delete(debounceKey);
        executeAreaUpdate(orderId, newArea, oldArea, dropdown);
    }, 300);
    
    updateAreaDebounce.set(debounceKey, timeoutId);
}

// Función auxiliar para ejecutar la actualización de área
function executeAreaUpdate(orderId, newArea, oldArea, dropdown) {
    fetch(`${window.updateUrl}/${orderId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ area: newArea })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Area actualizada correctamente');
                // Actualizar las celdas con las fechas actualizadas según la respuesta del servidor
                if (data.updated_fields) {
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    if (row) {
                        for (const [field, date] of Object.entries(data.updated_fields)) {
                            const cell = row.querySelector(`td[data-column="${field}"] .cell-text`);
                            if (cell) {
                                cell.textContent = date;
                            }
                        }
                    }
                }

                // Enviar mensaje a otras pestañas usando localStorage
                const timestamp = Date.now();
                localStorage.setItem('orders-updates', JSON.stringify({
                    type: 'area_update',
                    orderId: orderId,
                    field: 'area',
                    newValue: newArea,
                    oldValue: oldArea,
                    updatedFields: data.updated_fields || {},
                    order: data.order,
                    totalDiasCalculados: data.totalDiasCalculados || {},
                    timestamp: timestamp // Para evitar duplicados
                }));
                // Actualizar timestamp local para evitar procesar mensaje propio
                localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
            } else {
                console.error('Error al actualizar el area:', data.message);
                // Revertir cambio en caso de error
                if (dropdown) dropdown.value = oldArea;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revertir cambio en caso de error
            if (dropdown) dropdown.value = oldArea;
        });
}

// Función para actualizar el color de la fila basado en estado y total_dias
function updateRowColor(orderId, newStatus) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (!row) return;

    // Obtener total_dias de la celda correspondiente (columna 'total_de_dias_')
    const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
    let totalDias = 0;
    if (totalDiasCell && totalDiasCell.textContent.trim() !== 'N/A') {
        const text = totalDiasCell.textContent.trim();
        totalDias = parseInt(text) || 0;
    }

    // Obtener dia_de_entrega del dropdown
    let diaDeEntrega = null;
    const diaEntregaDropdown = row.querySelector('.dia-entrega-dropdown');
    if (diaEntregaDropdown) {
        const valorDiaEntrega = diaEntregaDropdown.value;
        if (valorDiaEntrega && valorDiaEntrega !== '') {
            diaDeEntrega = parseInt(valorDiaEntrega);
        }
    }

    // Remover todas las clases condicionales
    row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary', 'row-dia-entrega-warning', 'row-dia-entrega-danger', 'row-dia-entrega-critical');

    let conditionalClass = '';
    
    // PRIORIDAD 1: Estados especiales
    if (newStatus === 'Entregado') {
        conditionalClass = 'row-delivered';
    } else if (newStatus === 'Anulada') {
        conditionalClass = 'row-anulada';
    }
    // PRIORIDAD 2: NUEVA LÓGICA - Día de entrega (si existe)
    else if (diaDeEntrega !== null && diaDeEntrega > 0) {
        if (totalDias >= 15) {
            conditionalClass = 'row-dia-entrega-critical'; // Negro (15+)
        } else if (totalDias >= 10 && totalDias <= 14) {
            conditionalClass = 'row-dia-entrega-danger'; // Rojo (10-14)
        } else if (totalDias >= 5 && totalDias <= 9) {
            conditionalClass = 'row-dia-entrega-warning'; // Amarillo (5-9)
        }
    }
    // PRIORIDAD 3: LÓGICA ORIGINAL - Solo si NO hay día de entrega
    else {
        if (totalDias > 20) {
            conditionalClass = 'row-secondary';
        } else if (totalDias === 20) {
            conditionalClass = 'row-danger-light';
        } else if (totalDias > 14 && totalDias < 20) {
            conditionalClass = 'row-warning';
        }
    }

    // Agregar la clase correspondiente
    if (conditionalClass) {
        row.classList.add(conditionalClass);
    }
    
    console.log(`🎨 Color actualizado para orden ${orderId}: estado="${newStatus}", totalDias=${totalDias}, diaEntrega=${diaDeEntrega}, clase="${conditionalClass}"`);
}

// OPTIMIZACIÓN: Inicializar solo una vez al cargar el DOM
document.addEventListener('DOMContentLoaded', function () {
    initializeStatusDropdowns();
    initializeAreaDropdowns();
});

// OPTIMIZACIÓN: MutationObserver ELIMINADO - causaba reinicializaciones innecesarias
// Los dropdowns se inicializan automáticamente cuando se recarga la tabla

// Listener para mensajes de localStorage (comunicación entre pestañas/ventanas)
window.addEventListener('storage', function(event) {
    if (event.key === 'orders-updates') {
        try {
            const data = JSON.parse(event.newValue);
            console.log('Recibido mensaje de localStorage en index.blade.php:', data);

            const { type, orderId, field, newValue, updatedFields, order, totalDiasCalculados, timestamp } = data;

            // Evitar procesar mensajes propios (usando timestamp)
            const lastTimestamp = parseInt(localStorage.getItem('last-orders-update-timestamp') || '0');
            if (timestamp && timestamp <= lastTimestamp) {
                console.log('Mensaje duplicado ignorado en index.blade.php');
                return;
            }

            // Actualizar timestamp para evitar duplicados
            localStorage.setItem('last-orders-update-timestamp', timestamp.toString());

            // Actualizar la fila específica
            updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados);
        } catch (e) {
            console.error('Error parsing localStorage message:', e);
        }
    }
});

// Función para actualizar fila desde localStorage
function updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (!row) {
        console.warn(`Fila con orderId ${orderId} no encontrada`);
        return;
    }

    // Actualizar el campo específico
    if (field === 'estado') {
        const estadoDropdown = row.querySelector('.estado-dropdown');
        if (estadoDropdown) {
            estadoDropdown.value = newValue;
            estadoDropdown.dataset.value = newValue;
            updateRowColor(orderId, newValue);
        }
    } else if (field === 'area') {
        const areaDropdown = row.querySelector('.area-dropdown');
        if (areaDropdown) {
            areaDropdown.value = newValue;
            areaDropdown.dataset.value = newValue;
        }
    } else if (field === 'dia_de_entrega') {
        const diaEntregaDropdown = row.querySelector('.dia-entrega-dropdown');
        if (diaEntregaDropdown) {
            // Si newValue es null o vacío, establecer a cadena vacía
            const valorFinal = (newValue === null || newValue === '') ? '' : newValue;
            diaEntregaDropdown.value = valorFinal;
            diaEntregaDropdown.setAttribute('data-value', valorFinal);
            console.log(`✅ Día de entrega sincronizado en tiempo real: ${valorFinal || 'Seleccionar'} para orden ${orderId}`);
        }
    } else {
        // Para otros campos (celdas editables)
        const cell = row.querySelector(`td[data-column="${field}"] .cell-text`);
        if (cell) {
            cell.textContent = newValue;
            cell.closest('.cell-content').title = newValue;
        }
    }

    // Actualizar campos relacionados (fechas, etc.)
    if (updatedFields) {
        for (const [updateField, updateValue] of Object.entries(updatedFields)) {
            const updateCell = row.querySelector(`td[data-column="${updateField}"] .cell-text`);
            if (updateCell) {
                updateCell.textContent = updateValue;
            }
        }
    }

    // Actualizar total_de_dias_ si viene en totalDiasCalculados
    if (totalDiasCalculados && totalDiasCalculados[orderId] !== undefined) {
        const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
        if (totalDiasCell) {
            totalDiasCell.textContent = totalDiasCalculados[orderId];
        }
    }

    console.log(`Fila ${orderId} actualizada desde localStorage: ${field} = ${newValue}`);
}

// Función para eliminar orden con modal moderno
function deleteOrder(pedido) {
    // Mostrar el modal de confirmación
    const modal = document.getElementById('deleteConfirmationModal');
    const orderIdElement = document.getElementById('deleteOrderId');
    const overlay = document.getElementById('deleteModalOverlay');
    const cancelBtn = document.getElementById('deleteCancelBtn');
    const confirmBtn = document.getElementById('deleteConfirmBtn');

    // Configurar el número de pedido
    orderIdElement.textContent = pedido;

    // Mostrar modal
    modal.style.display = 'flex';

    // Función para cerrar modal
    const closeModal = () => {
        modal.style.display = 'none';
    };

    // Event listeners
    const handleCancel = () => {
        closeModal();
        // Limpiar listeners
        overlay.removeEventListener('click', handleCancel);
        cancelBtn.removeEventListener('click', handleCancel);
        confirmBtn.removeEventListener('click', handleConfirm);
    };

    const handleConfirm = () => {
        closeModal();
        // Limpiar listeners
        overlay.removeEventListener('click', handleCancel);
        cancelBtn.removeEventListener('click', handleCancel);
        confirmBtn.removeEventListener('click', handleConfirm);

        // Deshabilitar botón de confirmación durante la operación
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg> Eliminando...';

        // Ejecutar eliminación
        fetch(`${window.fetchUrl}/${pedido}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar notificación de éxito
                    showDeleteNotification('Orden eliminada correctamente', 'success');
                    // Recargar la tabla
                    recargarTablaPedidos();
                } else {
                    showDeleteNotification('Error al eliminar la orden: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showDeleteNotification('Error al eliminar la orden', 'error');
            })
            .finally(() => {
                // Rehabilitar botón
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg> Eliminar Orden';
            });
    };

    // Agregar listeners
    overlay.addEventListener('click', handleCancel);
    cancelBtn.addEventListener('click', handleCancel);
    confirmBtn.addEventListener('click', handleConfirm);
}

// Función para mostrar notificaciones modernas
function showDeleteNotification(message, type) {
    // Remover notificaciones existentes
    const existingNotifications = document.querySelectorAll('.delete-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `delete-notification delete-notification-${type}`;
    notification.textContent = message;

    // Agregar al DOM
    document.body.appendChild(notification);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'notificationSlideOut 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Handle orden updates (created, updated, deleted)
function handleOrdenUpdate(orden, action) {
    const pedido = orden.pedido;
    const updateKey = `${pedido}-${action}`;

    // Debounce: ignore if same update happened in last 500ms
    if (updateDebounceMap.has(updateKey)) {
        const lastUpdate = updateDebounceMap.get(updateKey);
        if (Date.now() - lastUpdate < 500) {
            console.log(`⏭️ Ignorando actualización duplicada para orden ${pedido}`);
            return;
        }
    }
    updateDebounceMap.set(updateKey, Date.now());

    console.log(`Procesando acción: ${action} para orden:`, orden);

    const table = document.querySelector('.modern-table tbody');
    if (!table) {
        console.warn('Tabla de órdenes no encontrada');
        return;
    }

    if (action === 'deleted') {
        // Remove row - usar data-order-id
        const row = table.querySelector(`tr[data-order-id="${pedido}"]`);
        if (row) {
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
            setTimeout(() => {
                row.remove();
                console.log(`✅ Orden ${pedido} eliminada de la tabla`);
            }, 500);
        }
        return;
    }

    if (action === 'created') {
        // Reload table to show new order in correct position
        recargarTablaPedidos();
        return;
    }

    if (action === 'updated') {
        // Update existing row
        actualizarOrdenEnTabla(orden);
        return;
    }
}

// Función para ver detalle
async function viewDetail(pedido) {
    console.log('viewDetail called with pedido:', pedido);
    try {
        const response = await fetch(`${window.fetchUrl}/${pedido}`);
        if (!response.ok) throw new Error('Error fetching order');
        const order = await response.json();
        const fechaCreacion = new Date(order.fecha_de_creacion_de_orden);
        const day = fechaCreacion.getDate().toString().padStart(2, '0');
        const month = fechaCreacion.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase();
        const year = fechaCreacion.getFullYear().toString().slice(-2);
        const orderDate = document.getElementById('order-date');
        if (orderDate) {
            const dayBox = orderDate.querySelector('.day-box');
            const monthBox = orderDate.querySelector('.month-box');
            const yearBox = orderDate.querySelector('.year-box');
            if (dayBox) dayBox.textContent = day;
            if (monthBox) monthBox.textContent = month;
            if (yearBox) yearBox.textContent = year;
        }
        const pedidoDiv = document.getElementById('order-pedido');
        if (pedidoDiv) {
            pedidoDiv.textContent = `N° ${pedido}`;
        }
        const asesoraValue = document.getElementById('asesora-value');
        if (asesoraValue) {
            asesoraValue.textContent = order.asesora || '';
        }
        const formaPagoValue = document.getElementById('forma-pago-value');
        if (formaPagoValue) {
            formaPagoValue.textContent = order.forma_de_pago || '';
        }
        const clienteValue = document.getElementById('cliente-value');
        if (clienteValue) {
            clienteValue.textContent = order.cliente || '';
        }

        const encargadoValue = document.getElementById('encargado-value');
        if (encargadoValue) {
            encargadoValue.textContent = order.encargado_orden || '';
        }

        const prendasEntregadasValue = document.getElementById('prendas-entregadas-value');
        if (prendasEntregadasValue) {
            const totalEntregado = order.total_entregado || 0;
            const totalCantidad = order.total_cantidad || 0;
            prendasEntregadasValue.textContent = `${totalEntregado} de ${totalCantidad}`;
        }

        // Definir elementos del DOM antes de usarlos
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = prevArrow?.parentElement;

        const verEntregasLink = document.getElementById('ver-entregas');
        // Remover el listener anterior si existe
        if (verEntregasLink._verEntregasHandler) {
            verEntregasLink.removeEventListener('click', verEntregasLink._verEntregasHandler);
        }
        // Definir el nuevo handler
        verEntregasLink._verEntregasHandler = async (e) => {
            e.preventDefault();
            if (verEntregasLink.textContent.trim() === 'VER ENTREGAS') {
                try {
                    const response = await fetch(`${window.fetchUrl}/${pedido}/entregas`);
                    const data = await response.json();
                    const tableHtml = `
                        <div style="max-height: 300px; overflow: auto; width: 100%;">
                            <table style="width: 100%; min-width: 600px; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f2f2f2;">
                                        <th style="border: 1px solid #ddd; padding: 8px; width: 40%; vertical-align: top;">Prenda</th>
                                        <th style="border: 1px solid #ddd; padding: 8px; width: 12%; vertical-align: top;">Talla</th>
                                        <th style="border: 1px solid #ddd; padding: 8px; width: 12%; vertical-align: top;">Cantidad</th>
                                        <th style="border: 1px solid #ddd; padding: 8px; width: 18%; vertical-align: top;">Total Producido</th>
                                        <th style="border: 1px solid #ddd; padding: 8px; width: 18%; vertical-align: top;">Total Pendiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.map(r => `
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 8px; width: 40%; vertical-align: top; word-wrap: break-word; white-space: normal;">${r.prenda}</td>
                                            <td style="border: 1px solid #ddd; padding: 8px; width: 12%; vertical-align: top; text-align: center;">${r.talla}</td>
                                            <td style="border: 1px solid #ddd; padding: 8px; width: 12%; vertical-align: top; text-align: center;">${r.cantidad}</td>
                                            <td style="border: 1px solid #ddd; padding: 8px; width: 18%; vertical-align: top; text-align: center;">${r.total_producido_por_talla || 0}</td>
                                            <td style="border: 1px solid #ddd; padding: 8px; width: 18%; vertical-align: top; text-align: center;">${r.total_pendiente_por_talla}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                    descripcionText.innerHTML = tableHtml;
                    if (arrowContainer) arrowContainer.style.display = 'none';
                    verEntregasLink.textContent = 'LIMPIAR';
                    verEntregasLink.style.color = 'green';
                } catch (error) {
                    console.error('Error fetching entregas:', error);
                }
            } else {
                // Restore description
                if (order.descripcion) {
                    const prendas = order.descripcion.split(/\n\s*\n/).filter(p => p.trim());
                    let currentIndex = 0;
                    function updateDescripcion() {
                        if (prendas.length <= 2) {
                            descripcionText.textContent = prendas.join('\n\n');
                            if (arrowContainer) arrowContainer.style.display = 'none';
                        } else {
                            if (currentIndex === 0) {
                                descripcionText.textContent = prendas[0] + '\n\n' + prendas[1];
                            } else {
                                descripcionText.textContent = prendas[currentIndex + 1];
                            }
                            if (arrowContainer) arrowContainer.style.display = 'flex';
                            prevArrow.style.display = currentIndex > 0 ? 'inline-block' : 'none';
                            nextArrow.style.display = currentIndex < prendas.length - 2 ? 'inline-block' : 'none';
                        }
                    }
                    updateDescripcion();
                    prevArrow.addEventListener('click', () => {
                        if (currentIndex > 0) {
                            currentIndex--;
                            updateDescripcion();
                        }
                    });
                    nextArrow.addEventListener('click', () => {
                        if (currentIndex < prendas.length - 2) {
                            currentIndex++;
                            updateDescripcion();
                        }
                    });
                } else {
                    descripcionText.textContent = '';
                    if (arrowContainer) arrowContainer.style.display = 'none';
                }
                verEntregasLink.textContent = 'VER ENTREGAS';
                verEntregasLink.style.color = 'red';
            }
        };
        // Agregar el nuevo listener
        verEntregasLink.addEventListener('click', verEntregasLink._verEntregasHandler);

        let currentIndex = 0;
        let prendas = [];

        if (descripcionText && order.descripcion) {
            prendas = order.descripcion.split(/\n\s*\n/).filter(p => p.trim());

            function updateDescripcion() {
                if (prendas.length <= 2) {
                    descripcionText.textContent = prendas.join('\n\n');
                    if (arrowContainer) arrowContainer.style.display = 'none';
                } else {
                    if (currentIndex === 0) {
                        descripcionText.textContent = prendas[0] + '\n\n' + prendas[1];
                    } else {
                        descripcionText.textContent = prendas[currentIndex + 1];
                    }
                    if (arrowContainer) arrowContainer.style.display = 'flex';
                    prevArrow.style.display = currentIndex > 0 ? 'inline-block' : 'none';
                    nextArrow.style.display = currentIndex < prendas.length - 2 ? 'inline-block' : 'none';
                }
            }

            updateDescripcion();
            prevArrow.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateDescripcion();
                }
            });
            nextArrow.addEventListener('click', () => {
                if (currentIndex < prendas.length - 2) {
                    currentIndex++;
                    updateDescripcion();
                }
            });
        } else {
            descripcionText.textContent = '';
            if (arrowContainer) arrowContainer.style.display = 'none';
        }

        // Adaptar el modal según el contexto
        const receiptTitle = document.querySelector('.receipt-title');
        const asesoraDiv = document.getElementById('order-asesora');
        const formaPagoDiv = document.getElementById('order-forma-pago');
        if (window.modalContext === 'bodega') {
            if (receiptTitle) receiptTitle.innerHTML = 'RECIBO DE CORTE<br>PARA BODEGA';
            if (asesoraDiv) asesoraDiv.style.display = 'none';
            if (formaPagoDiv) formaPagoDiv.style.display = 'none';
            // Lower pedido and cliente positions
            const pedidoDiv = document.getElementById('order-pedido');
            const clienteValue = document.getElementById('cliente-value');
            if (pedidoDiv) pedidoDiv.style.marginTop = '38px';
            if (clienteValue) clienteValue.parentElement.style.marginTop = '20px';
        } else {
            if (receiptTitle) receiptTitle.textContent = 'RECIBO DE COSTURA';
            if (asesoraDiv) asesoraDiv.style.display = 'block';
            if (formaPagoDiv) formaPagoDiv.style.display = 'block';
            // Reset positions if needed
            const pedidoDiv = document.getElementById('order-pedido');
            const clienteValue = document.getElementById('cliente-value');
            if (pedidoDiv) pedidoDiv.style.marginTop = '';
            if (clienteValue) clienteValue.parentElement.style.marginTop = '';
        }

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-detail' }));
    } catch (error) {
        console.error('Error loading order details:', error);
        // Still open the modal, but date will be empty
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-detail' }));
    }
}

// Función para limpiar filtros
function clearFilters() {
    // Limpiar búsqueda
    document.getElementById('buscarOrden').value = '';

    // Limpiar filtros aplicados (asumiendo que hay una variable global o manera de resetear)
    // Aquí puedes agregar lógica para resetear filtros si es necesario

    // Recargar la tabla
    recargarTablaPedidos();
}

// Función para abrir modal de registro de orden
function openOrderRegistration() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-registration' }));
}

// OPTIMIZACIÓN: Eliminada inicialización duplicada con setTimeout
// Los dropdowns ya se inicializan en DOMContentLoaded



// Handle orden updates (created, updated, deleted)
function handleOrdenUpdate(orden, action) {
    const pedido = orden.pedido;
    const updateKey = `${pedido}-${action}`;

    // Debounce: ignore if same update happened in last 500ms
    if (updateDebounceMap.has(updateKey)) {
        const lastUpdate = updateDebounceMap.get(updateKey);
        if (Date.now() - lastUpdate < 500) {
            console.log(`⏭️ Ignorando actualización duplicada para orden ${pedido}`);
            return;
        }
    }
    updateDebounceMap.set(updateKey, Date.now());

    console.log(`Procesando acción: ${action} para orden:`, orden);

    const table = document.querySelector('.modern-table tbody');
    if (!table) {
        console.warn('Tabla de órdenes no encontrada');
        return;
    }

    if (action === 'deleted') {
        // Remove row - usar data-order-id
        const row = table.querySelector(`tr[data-order-id="${pedido}"]`);
        if (row) {
            row.style.backgroundColor = 'rgba(239, 68, 68, 0.2)';
            setTimeout(() => {
                row.remove();
                console.log(`✅ Orden ${pedido} eliminada de la tabla`);
            }, 500);
        }
        return;
    }

    if (action === 'created') {
        // Reload table to show new order in correct position
        recargarTablaPedidos();
        return;
    }

    if (action === 'updated') {
        // Update existing row
        actualizarOrdenEnTabla(orden);
        return;
    }
}

// Add new orden to table
function agregarOrdenATabla(orden) {
    const table = document.querySelector('.modern-table tbody');
    if (!table) return;

    // Check if row already exists - usar data-order-id
    const existingRow = table.querySelector(`tr[data-order-id="${orden.pedido}"]`);
    if (existingRow) {
        console.log(`Orden ${orden.pedido} ya existe, actualizando...`);
        actualizarOrdenEnTabla(orden);
        return;
    }

    // Create new row - usar data-order-id
    const row = document.createElement('tr');
    row.className = 'table-row';
    row.setAttribute('data-order-id', orden.pedido);

    // Get all columns from the table header
    const headers = document.querySelectorAll('.modern-table thead th');
    const columns = Array.from(headers).map(th => th.getAttribute('data-column')).filter(Boolean);

    // Create cells for each column
    columns.forEach(column => {
        const td = document.createElement('td');
        td.className = 'table-cell editable-cell';
        td.setAttribute('data-column', column);
        td.title = 'Doble clic para editar';

        let value = orden[column];
        let displayValue = value || '';

        // Format special columns
        if (column === 'fecha_de_creacion_de_orden' && value) {
            displayValue = new Date(value).toLocaleDateString('es-ES');
        } else if (column === 'total_de_dias_') {
            // This is calculated, might need special handling
            displayValue = value || '0';
        }

        td.setAttribute('data-value', value);
        td.textContent = displayValue;
        row.appendChild(td);
    });

    // Add actions cell
    const actionsTd = document.createElement('td');
    actionsTd.className = 'table-cell';
    actionsTd.innerHTML = `
        <button class="view-details-btn" data-pedido="${orden.pedido}" title="Ver detalles">
            <i class="fas fa-eye"></i>
        </button>
        <button class="delete-order-btn" data-pedido="${orden.pedido}" title="Eliminar orden">
            <i class="fas fa-trash"></i>
        </button>
    `;
    row.appendChild(actionsTd);

    // Insert at the beginning of the table
    table.insertBefore(row, table.firstChild);

    // Animation
    row.style.backgroundColor = 'rgba(34, 197, 94, 0.3)';
    setTimeout(() => {
        row.style.transition = 'background-color 1s ease';
        row.style.backgroundColor = '';
    }, 100);

    console.log(`✅ Orden ${orden.pedido} agregada a la tabla`);
}

// Update existing orden in table
function actualizarOrdenEnTabla(orden) {
    const table = document.querySelector('.modern-table tbody');
    if (!table) return;

    // Usar data-order-id para encontrar la fila
    const row = table.querySelector(`tr[data-order-id="${orden.pedido}"]`);
    if (!row) {
        console.log(`Orden ${orden.pedido} no encontrada en la tabla actual`);
        return; // No agregar si no existe, solo actualizar las que ya están visibles
    }

    let hasChanges = false;

    // Update each cell WITHOUT changing the structure
    const cells = row.querySelectorAll('td[data-column]');
    cells.forEach(cell => {
        const column = cell.getAttribute('data-column');
        if (!column) return;

        // Skip dia_de_entrega column as it's already updated by the dropdown
        if (column === 'dia_de_entrega') return;

        let value = orden[column];
        if (value === null || value === undefined) return;

        // Find the element to update (could be select, span, or div)
        const cellContent = cell.querySelector('.cell-content');
        if (!cellContent) return;

        // Handle different cell types
        if (column === 'estado') {
            const select = cellContent.querySelector('.estado-dropdown');
            if (select && select.value !== value) {
                select.value = value;
                select.setAttribute('data-value', value);
                hasChanges = true;
                // Flash animation only on this cell
                cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                setTimeout(() => {
                    cell.style.transition = 'background-color 0.3s ease';
                    cell.style.backgroundColor = '';
                }, 30);
            }
        } else if (column === 'area') {
            const select = cellContent.querySelector('.area-dropdown');
            if (select && select.value !== value) {
                select.value = value;
                select.setAttribute('data-value', value);
                hasChanges = true;
                // Flash animation only on this cell
                cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                setTimeout(() => {
                    cell.style.transition = 'background-color 0.3s ease';
                    cell.style.backgroundColor = '';
                }, 30);
            }
        } else {
            const span = cellContent.querySelector('.cell-text');
            if (span) {
                let displayValue = value;
                if (column === 'fecha_de_creacion_de_orden' && value) {
                    displayValue = new Date(value).toLocaleDateString('es-ES');
                }

                if (span.textContent.trim() !== String(displayValue).trim()) {
                    span.textContent = displayValue;
                    hasChanges = true;
                    // Flash animation only on this cell
                    cell.style.backgroundColor = 'rgba(59, 130, 246, 0.3)';
                    setTimeout(() => {
                        cell.style.transition = 'background-color 0.3s ease';
                        cell.style.backgroundColor = '';
                    }, 30);
                }
            }
        }
    });

    // Update row classes based on estado and total_de_dias_
    const estado = orden.estado || '';

    // Si total_de_dias_ no viene en el evento, leer de la celda existente
    let totalDias = parseInt(orden.total_de_dias_) || 0;
    if (!totalDias || totalDias === 0) {
        const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
        if (totalDiasCell) {
            totalDias = parseInt(totalDiasCell.textContent) || 0;
        }
    }

    // Obtener dia_de_entrega si existe
    let diaDeEntrega = null;
    const diaEntregaDropdown = row.querySelector('.dia-entrega-dropdown');
    if (diaEntregaDropdown) {
        const valorDiaEntrega = diaEntregaDropdown.value;
        if (valorDiaEntrega && valorDiaEntrega !== '') {
            diaDeEntrega = parseInt(valorDiaEntrega);
        }
    }

    // Remove all conditional classes
    row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary', 'row-dia-entrega-warning', 'row-dia-entrega-danger', 'row-dia-entrega-critical');

    // Remove any inline background color that might override CSS
    row.style.backgroundColor = '';

    // Apply new class based on estado and dias (ORDEN DE PRIORIDAD)
    
    // PRIORIDAD 1: Estados especiales
    if (estado === 'Entregado') {
        row.classList.add('row-delivered');
        console.log(`🔍 Debug - Orden ${orden.pedido}: estado="Entregado", clase: row-delivered`);
    } else if (estado === 'Anulada') {
        row.classList.add('row-anulada');
        console.log(`🔍 Debug - Orden ${orden.pedido}: estado="Anulada", clase: row-anulada`);
    }
    // PRIORIDAD 2: NUEVA LÓGICA - Día de entrega (si existe)
    else if (diaDeEntrega !== null && diaDeEntrega > 0) {
        if (totalDias >= 15) {
            row.classList.add('row-dia-entrega-critical'); // Negro (15+)
            console.log(`🔍 Debug - Orden ${orden.pedido}: diaEntrega=${diaDeEntrega}, totalDias=${totalDias} (≥15), clase: row-dia-entrega-critical (NEGRO)`);
        } else if (totalDias >= 10 && totalDias <= 14) {
            row.classList.add('row-dia-entrega-danger'); // Rojo (10-14)
            console.log(`🔍 Debug - Orden ${orden.pedido}: diaEntrega=${diaDeEntrega}, totalDias=${totalDias} (10-14), clase: row-dia-entrega-danger (ROJO)`);
        } else if (totalDias >= 5 && totalDias <= 9) {
            row.classList.add('row-dia-entrega-warning'); // Amarillo (5-9)
            console.log(`🔍 Debug - Orden ${orden.pedido}: diaEntrega=${diaDeEntrega}, totalDias=${totalDias} (5-9), clase: row-dia-entrega-warning (AMARILLO)`);
        } else {
            // Si totalDias < 5, no se aplica ninguna clase (sin color)
            console.log(`🔍 Debug - Orden ${orden.pedido}: diaEntrega=${diaDeEntrega}, totalDias=${totalDias} (<5), sin color especial`);
        }
    }
    // PRIORIDAD 3: LÓGICA ORIGINAL - Solo si NO hay día de entrega
    else {
        if (totalDias > 20) {
            row.classList.add('row-secondary');
            console.log(`🔍 Debug - Orden ${orden.pedido}: totalDias=${totalDias} (>20), clase: row-secondary`);
        } else if (totalDias === 20) {
            row.classList.add('row-danger-light');
            console.log(`🔍 Debug - Orden ${orden.pedido}: totalDias=${totalDias} (=20), clase: row-danger-light`);
        } else if (totalDias > 14 && totalDias < 20) {
            row.classList.add('row-warning');
            console.log(`🔍 Debug - Orden ${orden.pedido}: totalDias=${totalDias} (>14 y <20), clase: row-warning`);
        } else {
            console.log(`🔍 Debug - Orden ${orden.pedido}: totalDias=${totalDias}, sin clase especial`);
        }
    }

    if (hasChanges) {
        console.log(`✅ Orden ${orden.pedido} actualizada (estado: ${estado}, días: ${totalDias})`);
    }
}

// OPTIMIZACIÓN: Echo listeners ya se inicializan en index.blade.php (líneas 362-404)
// Eliminada duplicación para evitar múltiples suscripciones al mismo canal

// ===== DIA DE ENTREGA DROPDOWN =====
function initializeDiaEntregaDropdowns() {
    const dropdowns = document.querySelectorAll('.dia-entrega-dropdown');
    let newlyInitialized = 0;
    
    dropdowns.forEach(dropdown => {
        // Evitar re-inicializar dropdowns que ya tienen listeners
        if (dropdown.dataset.initialized === 'true') {
            return;
        }
        
        // IMPORTANTE: Siempre sincronizar data-value con el valor actual del select
        const currentValue = dropdown.value || '';
        const existingDataValue = dropdown.getAttribute('data-value');
        
        // Si data-value no coincide con el valor actual, actualizarlo
        if (existingDataValue !== currentValue) {
            dropdown.setAttribute('data-value', currentValue);
            console.log(`🔧 Sincronizando dropdown orden ${dropdown.dataset.id}: data-value="${existingDataValue}" → "${currentValue}"`);
        }

        // Limpiar eventos anteriores para evitar duplicados
        dropdown.removeEventListener('change', handleDiaEntregaChange);

        // Agregar evento de cambio
        dropdown.addEventListener('change', handleDiaEntregaChange);
        
        // Marcar como inicializado
        dropdown.dataset.initialized = 'true';
        newlyInitialized++;
    });
    
    if (newlyInitialized > 0) {
        console.log(`✅ ${newlyInitialized} dropdowns de día de entrega inicializados (${dropdowns.length} total)`);
    }
}

// Manejador de cambio de día de entrega
function handleDiaEntregaChange() {
    const newValue = this.value;
    const oldValue = this.dataset.value;
    const orderId = this.dataset.id;
    
    console.log(`📝 Cambio detectado en orden ${orderId}: ${oldValue} → ${newValue}`);
    
    // Agregar animación visual y deshabilitar temporalmente
    this.classList.add('updating');
    this.disabled = true;
    
    // Actualizar data-value inmediatamente para feedback visual
    this.setAttribute('data-value', newValue);
    
    // Llamar a la función de actualización
    updateOrderDiaEntrega(orderId, newValue, oldValue, this);
}

// OPTIMIZACIÓN: Debounce map para día de entrega
const updateDiaEntregaDebounce = new Map();

// Función para actualizar día de entrega en la base de datos
function updateOrderDiaEntrega(orderId, newDias, oldDias, dropdown) {
    // OPTIMIZACIÓN: Debounce de 300ms para evitar requests duplicados
    const debounceKey = `dia-entrega-${orderId}`;
    if (updateDiaEntregaDebounce.has(debounceKey)) {
        clearTimeout(updateDiaEntregaDebounce.get(debounceKey));
        console.log(`⏱️ Debounce cancelado para orden ${orderId}`);
    }
    
    const timeoutId = setTimeout(() => {
        updateDiaEntregaDebounce.delete(debounceKey);
        console.log(`🚀 Ejecutando actualización para orden ${orderId}`);
        executeDiaEntregaUpdate(orderId, newDias, oldDias, dropdown);
    }, 300);
    
    updateDiaEntregaDebounce.set(debounceKey, timeoutId);
}

// Función auxiliar para ejecutar la actualización de día de entrega
function executeDiaEntregaUpdate(orderId, newDias, oldDias, dropdown) {
    // Si newDias es vacío o null, enviar null; sino convertir a entero
    const valorAEnviar = (newDias === '' || newDias === null) ? null : parseInt(newDias);
    
    fetch(`${window.updateUrl}/${orderId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ dia_de_entrega: valorAEnviar })
    })
        .then(response => {
            console.log(`📥 Respuesta recibida para orden ${orderId}:`, response.status);
            return response.json();
        })
        .then(data => {
            console.log(`📦 Datos recibidos para orden ${orderId}:`, data);
            
            if (data.success) {
                const mensaje = valorAEnviar === null 
                    ? `✅ Día de entrega limpiado (Seleccionar) para orden ${orderId}`
                    : `✅ Día de entrega actualizado: ${newDias} días para orden ${orderId}`;
                console.log(mensaje);
                
                // Re-habilitar el dropdown y remover clase updating
                if (dropdown) {
                    dropdown.disabled = false;
                    dropdown.classList.remove('updating');
                    dropdown.setAttribute('data-value', newDias || '');
                }
                
                // IMPORTANTE: Actualizar el color de la fila inmediatamente
                const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                if (row && data.totalDiasCalculados) {
                    const totalDias = data.totalDiasCalculados[orderId] || 0;
                    const estado = data.order?.estado || '';
                    
                    // Remover todas las clases condicionales
                    row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary', 'row-dia-entrega-warning', 'row-dia-entrega-danger', 'row-dia-entrega-critical');
                    
                    // Aplicar nueva clase según la lógica
                    if (estado === 'Entregado') {
                        row.classList.add('row-delivered');
                    } else if (estado === 'Anulada') {
                        row.classList.add('row-anulada');
                    } else if (valorAEnviar !== null && valorAEnviar > 0) {
                        // Nueva lógica con día de entrega
                        if (totalDias >= 15) {
                            row.classList.add('row-dia-entrega-critical');
                        } else if (totalDias >= 10 && totalDias <= 14) {
                            row.classList.add('row-dia-entrega-danger');
                        } else if (totalDias >= 5 && totalDias <= 9) {
                            row.classList.add('row-dia-entrega-warning');
                        }
                    } else {
                        // Lógica original sin día de entrega
                        if (totalDias > 20) {
                            row.classList.add('row-secondary');
                        } else if (totalDias === 20) {
                            row.classList.add('row-danger-light');
                        } else if (totalDias > 14 && totalDias < 20) {
                            row.classList.add('row-warning');
                        }
                    }
                    
                    console.log(`✅ Color de fila actualizado para orden ${orderId}: totalDias=${totalDias}, diaEntrega=${valorAEnviar}`);
                }
                
                // Enviar mensaje a otras pestañas usando localStorage para sincronización en tiempo real
                const timestamp = Date.now();
                localStorage.setItem('orders-updates', JSON.stringify({
                    type: 'dia_entrega_update',
                    orderId: orderId,
                    field: 'dia_de_entrega',
                    newValue: newDias || null,
                    oldValue: oldDias,
                    order: data.order,
                    totalDiasCalculados: data.totalDiasCalculados,
                    timestamp: timestamp
                }));
                localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
            } else {
                console.error('❌ Error al actualizar día de entrega:', data.message);
                // Revertir cambio en caso de error
                if (dropdown) {
                    dropdown.value = oldDias || '';
                    dropdown.setAttribute('data-value', oldDias || '');
                    dropdown.disabled = false;
                    dropdown.classList.remove('updating');
                }
                alert(`Error al guardar: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('❌ Error de red:', error);
            // Revertir cambio en caso de error
            if (dropdown) {
                dropdown.value = oldDias || '';
                dropdown.setAttribute('data-value', oldDias || '');
                dropdown.disabled = false;
                dropdown.classList.remove('updating');
            }
            alert('Error de conexión. Por favor, intenta de nuevo.');
        });
}

// Inicializar dropdowns de día de entrega al cargar la página
// Usar múltiples estrategias para asegurar que se inicialice correctamente
function ensureInitialization() {
    console.log('🔄 Intentando inicializar dropdowns de día de entrega...');
    
    // Esperar un momento para asegurar que el DOM esté completamente listo
    setTimeout(() => {
        const dropdowns = document.querySelectorAll('.dia-entrega-dropdown');
        if (dropdowns.length > 0) {
            initializeDiaEntregaDropdowns();
            console.log('✅ Inicialización completada');
        } else {
            console.log('⚠️ No se encontraron dropdowns, reintentando...');
            // Reintentar después de 500ms si no hay dropdowns
            setTimeout(() => {
                const retryDropdowns = document.querySelectorAll('.dia-entrega-dropdown');
                if (retryDropdowns.length > 0) {
                    initializeDiaEntregaDropdowns();
                    console.log('✅ Inicialización completada (segundo intento)');
                }
            }, 500);
        }
    }, 100);
}

// Estrategia 1: DOMContentLoaded (carga inicial)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ensureInitialization);
} else {
    // Estrategia 2: Si el DOM ya está listo, ejecutar inmediatamente
    ensureInitialization();
}

// Estrategia 3: window.load como respaldo
window.addEventListener('load', function() {
    // Solo inicializar si no se ha hecho antes
    const dropdowns = document.querySelectorAll('.dia-entrega-dropdown');
    if (dropdowns.length > 0) {
        const hasListeners = Array.from(dropdowns).some(d => d.dataset.initialized === 'true');
        if (!hasListeners) {
            console.log('🔄 Inicializando desde window.load (respaldo)');
            initializeDiaEntregaDropdowns();
        }
    }
});
