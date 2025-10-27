@extends('layouts.app')

@section('content')
    <!-- Agregar referencia a FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
        integrity="sha512-dyZt+6Q6VbUaz2+miFj7XwjlzAIXazhbug+DUFc1l1b/HFB70dNDO7xjOIKPQ4j/wZUp3NEiqPFwAckj4iigcw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/modern-table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dropdown-styles.css') }}">
    <style>
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: var(--table-width, 100%);
            max-width: var(--table-width, 100%);
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .clear-filters-btn,
        .add-order-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .clear-filters-btn {
            background-color: #6c757d;
            color: white;
        }

        .clear-filters-btn:hover {
            background-color: #5a6268;
        }

        .add-order-btn {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }

        .add-order-btn:hover {
            background-color: #218838;
        }
    </style>

    <div class="table-container">
        <div class="table-header" id="tableHeader">
            <h1 class="table-title">
                <i class="fas {{ $icon }}"></i>
                {{ $title }}
            </h1>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="buscarOrden" placeholder="Buscar por número de orden..." class="search-input">
                </div>
            </div>

            <!-- llamada de botones de la  tabla -->
            <div class="table-actions"></div>
        </div>

        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <table id="tablaOrdenes" class="modern-table">
                    <thead class="table-head">
                        @if($ordenes->isNotEmpty())
                            <tr>
                                <th class="table-header-cell acciones-column">
                                    <div class="header-content">
                                        <span class="header-text">Acciones</span>
                                    </div>
                                </th>
                                @foreach(array_keys($ordenes->first()->getAttributes()) as $index => $columna)
                                    @if($columna !== 'id' && $columna !== 'tiempo')
                                        <th class="table-header-cell" data-column="{{ $columna }}">
                                            <div class="header-content">
                                                <span class="header-text">{{ ucfirst(str_replace('_', ' ', $columna)) }}</span>
                                                @if($columna !== 'acciones')
                                                    <button class="filter-btn" data-column="{{ $columna }}" data-column-name="{{ $columna }}">
                                                        <i class="fas fa-filter"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                        @endif
                    </thead>
                    <tbody id="tablaOrdenesBody" class="table-body">
                        @if($ordenes->isEmpty())
                            <tr class="table-row">
                                <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                                    No hay resultados que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        @else
                            @foreach($ordenes as $orden)
                                @php
                                    $totalDias = intval($totalDiasCalculados[$orden->pedido] ?? 0);
                                    $estado = $orden->estado ?? '';
                                    $conditionalClass = '';
                                    if ($estado === 'Entregado') {
                                        $conditionalClass = 'row-delivered';
                                    } elseif ($estado === 'Anulada') {
                                        $conditionalClass = 'row-anulada';
                                    } elseif ($totalDias > 14 && $totalDias < 20) {
                                        $conditionalClass = 'row-warning';
                                    } elseif ($totalDias == 20) {
                                        $conditionalClass = 'row-danger-light';
                                    } elseif ($totalDias > 20) {
                                        $conditionalClass = 'row-secondary';
                                    }
                                @endphp
                                <tr class="table-row {{ $conditionalClass }}" data-order-id="{{ $orden->pedido }}">
                                    <td class="table-cell acciones-column">
                                        <div class="cell-content">
                                            <button class="action-btn delete-btn" onclick="deleteOrder({{ $orden->pedido }})"
                                                title="Eliminar orden"
                                                style="background-color:#f84c4cff ; color: white; border: none; padding: 5px 10px; margin-right: 5px; border-radius: 4px; cursor: pointer;">
                                                Borrar
                                            </button>
                                            <button class="action-btn detail-btn" onclick="viewDetail({{ $orden->pedido }})"
                                                title="Ver detalle"
                                                style="background-color: green; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                                Ver
                                            </button>
                                        </div>
                                    </td>
                                    @foreach($orden->getAttributes() as $key => $valor)
                                        @if($key !== 'id' && $key !== 'tiempo')
                                            <td class="table-cell" data-column="{{ $key }}">
                                                <div class="cell-content" title="{{ $valor }}">
                                                    @if($key === 'estado')
                                                        <select class="estado-dropdown" data-id="{{ $orden->pedido }}"
                                                            data-value="{{ $valor }}">
                                                            @foreach(['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'] as $estado)
                                                                <option value="{{ $estado }}" {{ $valor === $estado ? 'selected' : '' }}>{{ $estado }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($key === 'area')
                                                        <select class="area-dropdown" data-id="{{ $orden->pedido }}" data-value="{{ $valor }}">
                                                            @foreach($areaOptions as $areaOption)
                                                                <option value="{{ $areaOption }}" {{ $valor === $areaOption ? 'selected' : '' }}>
                                                                    {{ $areaOption }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <span class="cell-text">
                                                            @if($key === 'total_de_dias_')
                                                                {{ $totalDiasCalculados[$orden->pedido] ?? 'N/A' }}
                                                            @else
                                                                {{ $valor }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="table-pagination"
                style="position: relative; z-index: 1; background: white; padding: 20px 0; border-top: 1px solid #e2e8f0;">
                <div class="pagination-info">
                    <span>Mostrando {{ $ordenes->firstItem() }}-{{ $ordenes->lastItem() }} de {{ $ordenes->total() }}
                        registros</span>
                </div>
                <div class="pagination-controls">
                    @if($ordenes->hasPages())
                        {{ $ordenes->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para filtros -->
    <div id="filterModal" class="filter-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Filtrar por: <span id="filterColumnName"></span></h3>
                <button class="modal-close" id="closeModal"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body">
                <div class="modal-search">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="filterSearch" placeholder="Buscar valores..." style="color: black;">
                    </div>
                </div>

                <div class="filter-options">
                    <div class="filter-actions">
                        <button id="selectAll" class="action-btn select-all">
                            <i class="fas fa-check-double"></i> Seleccionar todos
                        </button>
                        <button id="deselectAll" class="action-btn deselect-all">
                            <i class="fas fa-times"></i> Deseleccionar todos
                        </button>
                    </div>

                    <div class="filter-list" id="filterList"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelFilter">Cancelar</button>
                <button class="btn btn-primary" id="applyFilter">Aplicar filtro</button>
            </div>
        </div>
    </div>

    <!-- Modal para vista completa de celda -->
    <div id="cellModal" class="cell-modal">
        <div class="cell-modal-content">
            <div class="cell-modal-header">
                <h3 class="cell-modal-title">Editar celda</h3>
                <button class="modal-close" id="closeCellModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="cell-modal-body">
                <textarea id="cellEditInput" class="cell-edit-input" rows="5"
                    style="width: 100%; text-align: left; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
            </div>
            <div class="cell-modal-footer">
                <button id="saveCellEdit" class="btn btn-primary">Guardar (Enter)</button>
                <button id="cancelCellEdit" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay"></div>

    <script>
        // Pasar opciones de area a JS
        window.areaOptions = @json($areaOptions);
        window.modalContext = '{{ $modalContext }}';
        window.fetchUrl = '{{ $fetchUrl }}';
        window.updateUrl = '{{ $updateUrl }}';



   // Función para recargar la tabla de pedidos
async function recargarTablaPedidos() {
    try {
        const response = await fetch(window.fetchUrl, {
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

        // Función para eliminar orden
        function deleteOrder(pedido) {
            if (confirm(`¿Estás seguro de que deseas eliminar la orden ${pedido}? Esto eliminará todos los registros relacionados.`)) {
                fetch(`${window.fetchUrl}/${pedido}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Orden eliminada correctamente');
                            recargarTablaPedidos(); // Recargar la tabla
                        } else {
                            alert('Error al eliminar la orden: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar la orden');
                    });
            }
        }

        // Función para ver detalle
        async function viewDetail(pedido) {
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
                if (descripcionText && order.descripcion) {
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
    </script>
    <div class="order-registration-modal">
        <x-order-registration-modal :areaOptions="$areaOptions" />
    </div>

    <div class="order-detail-modal">
        <x-order-detail-modal />
    </div>

    <script src="{{ asset('js/modern-table.js') }}"></script>

    <script>
        // Sistema híbrido: localStorage + polling para actualizaciones en tiempo real
        console.log('🔄 Configurando sistema de actualizaciones en tiempo real...');

        // 1. Configurar localStorage listener (para pestañas del mismo navegador)
        window.addEventListener('storage', function(event) {
            if (event.key === 'orders-updates') {
                try {
                    const data = JSON.parse(event.newValue);
                    console.log('📱 Actualización desde localStorage:', data);

                    const { type, orderId, field, newValue, updatedFields, order, totalDiasCalculados, timestamp } = data;

                    // Evitar procesar mensajes propios
                    const lastTimestamp = parseInt(localStorage.getItem('last-orders-update-timestamp') || '0');
                    if (timestamp && timestamp <= lastTimestamp) {
                        console.log('🚫 Mensaje duplicado ignorado');
                        return;
                    }

                    localStorage.setItem('last-orders-update-timestamp', timestamp.toString());
                    updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados);
                } catch (e) {
                    console.error('❌ Error procesando localStorage:', e);
                }
            }
        });

        // 2. Configurar polling periódico (cada 30 segundos) para sincronización entre usuarios
        let lastUpdateTimestamp = Date.now();
        setInterval(async () => {
            try {
                const response = await fetch(`${window.fetchUrl}?_=${Date.now()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) return;

                const data = await response.json();
                if (data.orders && data.orders.length > 0) {
                    console.log('🔄 Verificando actualizaciones desde servidor...');

                    // Comparar con datos actuales y actualizar si hay cambios
                    data.orders.forEach(serverOrder => {
                        const localRow = document.querySelector(`tr[data-order-id="${serverOrder.pedido}"]`);
                        if (localRow) {
                            // Verificar si hay cambios en estado o área
                            const localStatus = localRow.querySelector('.estado-dropdown')?.value;
                            const localArea = localRow.querySelector('.area-dropdown')?.value;

                            if (serverOrder.estado !== localStatus) {
                                console.log(`📡 Estado actualizado por polling: ${serverOrder.pedido} ${localStatus} → ${serverOrder.estado}`);
                                updateRowFromBroadcast(serverOrder.pedido, 'estado', serverOrder.estado, {}, serverOrder, data.totalDiasCalculados);
                            }

                            if (serverOrder.area !== localArea) {
                                console.log(`📡 Área actualizada por polling: ${serverOrder.pedido} ${localArea} → ${serverOrder.area}`);
                                updateRowFromBroadcast(serverOrder.pedido, 'area', serverOrder.area, {}, serverOrder, data.totalDiasCalculados);
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('❌ Error en polling:', error);
            }
        }, 2000); // Polling cada 5 segundos

        // 3. Intentar configurar WebSocket (si está disponible)
        setTimeout(() => {
            if (window.Echo) {
                console.log('✅ Intentando configurar Laravel Echo...');

                try {
                    const channel = window.Echo.channel('orders-updates');
                    console.log('📡 Canal WebSocket conectado');

                    channel.listen('.order.updated', (e) => {
                        console.log('🎉 Evento recibido via WebSocket:', e);
                        updateRowFromBroadcast(e.orderId, e.field, e.newValue, e.updatedFields, e.order, e.totalDiasCalculados);
                    });

                    console.log('🎯 WebSocket configurado correctamente');
                } catch (error) {
                    console.warn('⚠️ WebSocket no disponible, usando polling + localStorage');
                }
            } else {
                console.log('ℹ️ WebSocket no disponible, usando polling + localStorage');
            }
        }, 1000);

        function updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados) {
            console.log('🔄 Actualizando fila:', { orderId, field, newValue });

            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (!row) {
                console.warn(`❌ Fila con orderId ${orderId} no encontrada`);
                return;
            }

            // Actualizar el dropdown de estado si cambió
            if (field === 'estado') {
                const statusDropdown = row.querySelector('.estado-dropdown');
                if (statusDropdown) {
                    statusDropdown.value = newValue;
                    statusDropdown.dataset.value = newValue;
                    console.log(`✅ Estado actualizado: ${statusDropdown.dataset.value} → ${newValue}`);
                }

                // Actualizar color de la fila
                updateRowColor(orderId, newValue);
            }

            // Actualizar el dropdown de área si cambió
            if (field === 'area') {
                const areaDropdown = row.querySelector('.area-dropdown');
                if (areaDropdown) {
                    areaDropdown.value = newValue;
                    areaDropdown.dataset.value = newValue;
                    console.log(`✅ Área actualizada: ${areaDropdown.dataset.value} → ${newValue}`);
                }
            }

            // Actualizar otros campos si se proporcionan
            if (updatedFields) {
                Object.entries(updatedFields).forEach(([updateField, value]) => {
                    const cell = row.querySelector(`td[data-column="${updateField}"] .cell-text`);
                    if (cell) {
                        cell.textContent = value;
                        console.log(`✅ Campo ${updateField} actualizado: ${value}`);
                    }
                });
            }

            // Actualizar total_de_dias_ si viene en totalDiasCalculados
            if (totalDiasCalculados && totalDiasCalculados[orderId] !== undefined) {
                const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
                if (totalDiasCell) {
                    totalDiasCell.textContent = totalDiasCalculados[orderId];
                    console.log(`✅ Total días actualizado: ${totalDiasCalculados[orderId]}`);
                }
            }

            console.log(`🎯 Order ${orderId} completamente actualizada`);
        }

        function updateRowColor(orderId, status) {
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (!row) return;

            // Remover clases de color anteriores
            row.classList.remove('row-delivered', 'row-anulada', 'row-warning', 'row-danger-light', 'row-secondary');

            // Agregar clase de color según el estado
            let conditionalClass = '';
            if (status === 'Entregado') {
                conditionalClass = 'row-delivered';
            } else if (status === 'Anulada') {
                conditionalClass = 'row-anulada';
            }

            // También considerar total_dias para colores adicionales
            const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
            if (totalDiasCell) {
                const totalDias = parseInt(totalDiasCell.textContent) || 0;
                if (totalDias > 14 && totalDias < 20) {
                    conditionalClass = 'row-warning';
                } else if (totalDias === 20) {
                    conditionalClass = 'row-danger-light';
                } else if (totalDias > 20) {
                    conditionalClass = 'row-secondary';
                }
            }

            if (conditionalClass) {
                row.classList.add(conditionalClass);
                console.log(`🎨 Color de fila actualizado: ${conditionalClass}`);
            }
        }
    </script>
@endsection
