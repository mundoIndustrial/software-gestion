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
                let conditionalClass = '';
                if (orden.estado === 'Entregado') {
                    conditionalClass = 'row-delivered';
                } else if (orden.estado === 'Anulada') {
                    conditionalClass = 'row-anulada';
                } else if (totalDias > 14 && totalDias < 20) {
                    conditionalClass = 'row-warning';
                } else if (totalDias === 20) {
                    conditionalClass = 'row-danger-light';
                } else if (totalDias > 20) {
                    conditionalClass = 'row-secondary';
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

// Función para actualizar estado en la base de datos
function updateOrderStatus(orderId, newStatus) {
    const dropdown = document.querySelector(`.estado-dropdown[data-id="${orderId}"]`);
    const oldStatus = dropdown ? dropdown.dataset.value : '';

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

// Función para actualizar area en la base de datos
function updateOrderArea(orderId, newArea) {
    const dropdown = document.querySelector(`.area-dropdown[data-id="${orderId}"]`);
    const oldArea = dropdown ? dropdown.dataset.value : '';

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

    let conditionalClass = '';
    if (newStatus === 'Entregado') {
        conditionalClass = 'row-delivered';
    } else if (totalDias > 14 && totalDias < 20) {
        conditionalClass = 'row-warning';
    } else if (totalDias === 20) {
        conditionalClass = 'row-danger-light';
    } else if (totalDias > 20) {
        conditionalClass = 'row-secondary';
    }

    // Remover clases anteriores y agregar la nueva
    row.classList.remove('row-delivered', 'row-warning', 'row-danger-light', 'row-secondary');
    if (conditionalClass) {
        row.classList.add(conditionalClass);
    }
}

// Ejecutar en diferentes momentos para asegurar que funcione
document.addEventListener('DOMContentLoaded', function () {
    initializeStatusDropdowns();
    initializeAreaDropdowns();
});
window.addEventListener('load', function () {
    initializeStatusDropdowns();
    initializeAreaDropdowns();
});

// Observer para detectar cambios dinámicos en la tabla
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // Reinicializar si se agregan nuevos elementos
                setTimeout(function () {
                    initializeStatusDropdowns();
                    initializeAreaDropdowns();
                }, 100);
            }
        });
    });

    // Observar cambios en la tabla
    const tableContainer = document.querySelector('#tablaOrdenes');
    if (tableContainer) {
        observer.observe(tableContainer, {
            childList: true,
            subtree: true
        });
    }
}

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
                    prevArrow.style.display = 'none';
                    nextArrow.style.display = 'none';
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
                            prevArrow.style.display = 'none';
                            nextArrow.style.display = 'none';
                        } else {
                            if (currentIndex === 0) {
                                descripcionText.textContent = prendas[0] + '\n\n' + prendas[1];
                            } else {
                                descripcionText.textContent = prendas[currentIndex + 1];
                            }
                            prevArrow.style.display = currentIndex > 0 ? 'inline' : 'none';
                            nextArrow.style.display = currentIndex < prendas.length - 2 ? 'inline' : 'none';
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
                    prevArrow.style.display = 'none';
                    nextArrow.style.display = 'none';
                }
                verEntregasLink.textContent = 'VER ENTREGAS';
                verEntregasLink.style.color = 'red';
            }
        };
        // Agregar el nuevo listener
        verEntregasLink.addEventListener('click', verEntregasLink._verEntregasHandler);

        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');

        let currentIndex = 0;
        let prendas = [];

        if (descripcionText && order.descripcion) {
            prendas = order.descripcion.split(/\n\s*\n/).filter(p => p.trim());

            function updateDescripcion() {
                if (prendas.length <= 2) {
                    descripcionText.textContent = prendas.join('\n\n');
                    prevArrow.style.display = 'none';
                    nextArrow.style.display = 'none';
                } else {
                    if (currentIndex === 0) {
                        descripcionText.textContent = prendas[0] + '\n\n' + prendas[1];
                    } else {
                        descripcionText.textContent = prendas[currentIndex + 1];
                    }
                    prevArrow.style.display = currentIndex > 0 ? 'inline' : 'none';
                    nextArrow.style.display = currentIndex < prendas.length - 2 ? 'inline' : 'none';
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
            prevArrow.style.display = 'none';
            nextArrow.style.display = 'none';
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

// Ejecutar también después de que se cargue modern-table.jst
setTimeout(function () {
    initializeStatusDropdowns();
    initializeAreaDropdowns();
}, 500);

// === Real-time updates script for orders ===
function initializeOrdenesRealtimeListeners() {
    console.log('=== ÓRDENES - Inicializando Echo para tiempo real ===');
    console.log('window.Echo disponible:', !!window.Echo);

    if (!window.Echo) {
        console.error('❌ Echo NO está disponible. Reintentando en 500ms...');
        setTimeout(initializeOrdenesRealtimeListeners, 500);
        return;
    }

    console.log('✅ Echo disponible. Suscribiendo al canal "ordenes"...');

    // Canal de Órdenes
    const ordenesChannel = window.Echo.channel('ordenes');

    ordenesChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "ordenes"');
    });

    ordenesChannel.error((error) => {
        console.error('❌ Error en canal "ordenes":', error);
    });

    ordenesChannel.listen('OrdenUpdated', (e) => {
        console.log('🎉 Evento OrdenUpdated recibido!', e);
        handleOrdenUpdate(e.orden, e.action);
    });

    console.log('✅ Listener de órdenes configurado');
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

    console.log(`🔍 Debug - Orden ${orden.pedido}: estado="${estado}", totalDias=${totalDias}, clase a aplicar: ${
        estado === 'Entregado' ? 'row-delivered' :
        estado === 'Anulada' ? 'row-anulada' :
        totalDias > 20 ? 'row-secondary' :
        totalDias === 20 ? 'row-danger-light' :
        totalDias > 14 ? 'row-warning' : 'ninguna'
    }`);

    // Remove all conditional classes
    row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary');

    // Remove any inline background color that might override CSS
    row.style.backgroundColor = '';

    // Apply new class based on estado and dias (ORDEN DE PRIORIDAD)
    if (estado === 'Entregado') {
        row.classList.add('row-delivered');
    } else if (estado === 'Anulada') {
        row.classList.add('row-anulada');
    } else if (totalDias > 20) {
        row.classList.add('row-secondary');
    } else if (totalDias === 20) {
        row.classList.add('row-danger-light');
    } else if (totalDias > 14 && totalDias < 20) {
        row.classList.add('row-warning');
    }

    if (hasChanges) {
        console.log(`✅ Orden ${orden.pedido} actualizada (estado: ${estado}, días: ${totalDias})`);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeOrdenesRealtimeListeners, 100);
    });
} else {
    setTimeout(initializeOrdenesRealtimeListeners, 100);
}
