@extends('layouts.app')

@section('content')

@php
    function getEficienciaClass($eficiencia) {
        if ($eficiencia === null) return '';
        $eficiencia = floatval($eficiencia);
        if ($eficiencia < 0.7) return 'eficiencia-red';
        if ($eficiencia >= 0.7 && $eficiencia < 0.8) return 'eficiencia-yellow';
        if ($eficiencia >= 0.8 && $eficiencia < 1.0) return 'eficiencia-green';
        if ($eficiencia >= 1.0) return 'eficiencia-blue';
        return '';
    }
@endphp
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/modern-table.css') }}">
<script src="{{ asset('js/tableros.js') }}"></script>
@include('components.tableros-form-modal')
<div class="tableros-container" x-data="tablerosApp()">
    <h1 class="tableros-title">Tableros de Producción</h1>

    <div class="tab-cards">
        <div class="tab-card" :class="{ 'active': activeTab === 'produccion' }" @click="activeTab = 'produccion'">
            <h3>Tablero de Piso Producción</h3>
            <p>Visualización de métricas de producción general</p>
        </div>

        <div class="tab-card" :class="{ 'active': activeTab === 'polos' }" @click="activeTab = 'polos'">
            <h3>Tablero Piso Polos</h3>
            <p>Métricas específicas del área de polos</p>
        </div>

        <div class="tab-card" :class="{ 'active': activeTab === 'corte' }" @click="activeTab = 'corte'">
            <h3>Tablero Piso Corte</h3>
            <p>Indicadores del proceso de corte</p>
        </div>
    </div>

    <div class="tab-content">
        <div x-show="activeTab === 'produccion'" class="chart-placeholder">
            <h4>📊 Tablero de Piso Producción</h4>
            <p>Gráfica de producción general próximamente</p>

            @include('components.action-buttons')

            <!-- Tabla de registros -->
            <div x-show="showRecords" class="records-table-container">
                <div class="table-scroll-container">
                    <table class="modern-table" data-section="produccion">
                        <thead class="table-head">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="table-header-cell" data-column="{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                                <th class="table-header-cell">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($registros as $registro)
                            <tr class="table-row" data-id="{{ $registro->id }}">
                                @foreach($columns as $column)
                                    @php
                                        $value = $registro->$column;
                                        $displayValue = $value;
                                        if ($column === 'fecha' && $value) {
                                            $displayValue = $value->format('d/m/Y');
                                        } elseif ($column === 'hora' && $value) {
                                            $displayValue = $value->format('H:i');
                                        } elseif ($column === 'eficiencia' && $value) {
                                            $displayValue = $value . '%';
                                        }
                                        $eficienciaClass = ($column === 'eficiencia' && $value !== null) ? getEficienciaClass($value) : '';
                                    @endphp
                                    <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $value }}" title="Doble clic para editar">{{ $displayValue }}</td>
                                @endforeach
                                <td class="table-cell">
                                    <button class="delete-btn" data-id="{{ $registro->id }}" data-section="produccion" title="Eliminar registro">
                                        ×
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="table-pagination">
                    <div class="pagination-info">
                        <span>Mostrando {{ $registros->firstItem() }}-{{ $registros->lastItem() }} de {{ $registros->total() }} registros</span>
                    </div>
                    <div class="pagination-controls">
                        {{ $registros->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'polos'" class="chart-placeholder">
            <h4>👕 Tablero Piso Polos</h4>
            <p>Gráfica de métricas de polos próximamente</p>

            @include('components.action-buttons')

            <!-- Tabla de registros -->
            <div x-show="showRecords" class="records-table-container">
                <div class="table-scroll-container">
                    <table class="modern-table" data-section="polos">
                        <thead class="table-head">
                            <tr>
                                @foreach($columnsPolos as $column)
                                    <th class="table-header-cell" data-column="{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                                <th class="table-header-cell">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @foreach($registrosPolos as $registro)
                            <tr class="table-row" data-id="{{ $registro->id }}">
                                @foreach($columnsPolos as $column)
                                    @php
                                        $value = $registro->$column;
                                        $displayValue = $value;
                                        if ($column === 'fecha' && $value) {
                                            $displayValue = $value->format('d/m/Y');
                                        } elseif ($column === 'hora' && $value) {
                                            $displayValue = $value->format('H:i');
                                        } elseif ($column === 'eficiencia' && $value) {
                                            $displayValue = $value . '%';
                                        }
                                        $eficienciaClass = ($column === 'eficiencia' && $value !== null) ? getEficienciaClass($value) : '';
                                    @endphp
                                    <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $value }}" title="Doble clic para editar">{{ $displayValue }}</td>
                                @endforeach
                                <td class="table-cell">
                                    <button class="delete-btn" data-id="{{ $registro->id }}" data-section="polos" title="Eliminar registro">
                                        ×
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="table-pagination">
                    <div class="pagination-info">
                        <span>Mostrando {{ $registrosPolos->firstItem() }}-{{ $registrosPolos->lastItem() }} de {{ $registrosPolos->total() }} registros</span>
                    </div>
                    <div class="pagination-controls">
                        {{ $registrosPolos->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div x-show="activeTab === 'corte'" class="chart-placeholder">
            <h4>✂️ Tablero Piso Corte</h4>
            <p>Gráfica de indicadores de corte próximamente</p>

            @include('components.action-buttons')
        </div>
    </div>
</div>

<!-- Modal para editar celda -->
<div id="editCellModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Editar Celda</h3>
            <button type="button" class="close" id="closeEditModal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" id="editCellInput" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: black; background: white;">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelEdit">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveEdit">Guardar (Enter)</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado para edición de celdas');
    let currentCell = null;
    let currentRowId = null;
    let currentColumn = null;

    // Función para agregar registros a la tabla dinámicamente
    window.agregarRegistrosATabla = function(registros, section) {
        const table = document.querySelector(`table[data-section="${section}"]`);
        if (!table) {
            console.error('Tabla no encontrada para sección:', section);
            return;
        }

        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('th[data-column]');
        const columns = Array.from(headers).map(th => th.dataset.column);

        registros.forEach(registro => {
            const tr = document.createElement('tr');
            tr.className = 'table-row';
            tr.setAttribute('data-id', registro.id);



            columns.forEach(column => {
                const td = document.createElement('td');
                td.className = 'table-cell editable-cell';
                td.setAttribute('data-column', column);

                let value = registro[column];
                let displayValue = value;
                let eficienciaClass = '';

                if (column === 'fecha' && value) {
                    const date = new Date(value);
                    displayValue = date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                } else if (column === 'hora' && value) {
                    displayValue = value;
                } else if (column === 'eficiencia' && value !== null) {
                    displayValue = value + '%';
                    eficienciaClass = getEficienciaClass(value);
                }

                if (eficienciaClass) {
                    td.classList.add(eficienciaClass);
                }
                td.setAttribute('data-value', value);
                td.title = 'Doble clic para editar';
                td.textContent = displayValue;

                tr.appendChild(td);
            });

            // Agregar celda del botón de eliminar al final
            const deleteTd = document.createElement('td');
            deleteTd.className = 'table-cell';
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.setAttribute('data-id', registro.id);
            deleteBtn.setAttribute('data-section', section);
            deleteBtn.title = 'Eliminar registro';
            deleteBtn.textContent = '×';
            deleteTd.appendChild(deleteBtn);
            tr.appendChild(deleteTd);

            // Agregar al principio de la tabla
            tbody.insertBefore(tr, tbody.firstChild);
        });

        // Actualizar información de paginación (aproximada)
        const paginationInfo = table.closest('.records-table-container').querySelector('.pagination-info span');
        if (paginationInfo) {
            const currentText = paginationInfo.textContent;
            const match = currentText.match(/Mostrando (\d+)-(\d+) de (\d+)/);
            if (match) {
                const start = parseInt(match[1]);
                const end = parseInt(match[2]) + registros.length;
                const total = parseInt(match[3]) + registros.length;
                paginationInfo.textContent = `Mostrando ${start}-${end} de ${total} registros`;
            }
        }

        console.log(`Agregados ${registros.length} registros a la tabla de ${section}`);
    };

    // Función para hacer celdas editables con doble click
    const editableCells = document.querySelectorAll('.editable-cell');
    console.log('Celdas editables encontradas:', editableCells.length);
    editableCells.forEach(cell => {
        console.log('Registrando evento dblclick para celda:', cell);
        cell.addEventListener('dblclick', function() {
            console.log('Doble clic detectado en celda');
            currentCell = this;
            currentRowId = this.closest('tr').dataset.id;
            currentColumn = this.dataset.column;

            const currentValue = this.dataset.value || this.textContent.trim();
            console.log('Valor actual:', currentValue);
            const modal = document.getElementById('editCellModal');
            console.log('Modal encontrado:', !!modal);
            if (modal) {
                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                document.getElementById('editCellInput').value = currentValue;
                document.getElementById('editCellInput').focus();
                document.getElementById('editCellInput').select();
            } else {
                console.error('Modal no encontrado');
            }
        });
    });

    // Guardar cambios
    document.getElementById('saveEdit').addEventListener('click', saveCellEdit);
    document.getElementById('editCellInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            saveCellEdit();
        }
    });

    // Cancelar edición
    document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
    document.getElementById('closeEditModal').addEventListener('click', closeEditModal);
    document.getElementById('editCellModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    function saveCellEdit() {
        const newValue = document.getElementById('editCellInput').value;
        const section = currentCell.closest('table').dataset.section;

        fetch(`/tableros/${currentRowId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ [currentColumn]: newValue, section: section })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la celda en la interfaz
                currentCell.dataset.value = newValue;
                currentCell.textContent = formatDisplayValue(currentColumn, newValue);

                // Si se editó una celda dependiente, actualizar también tiempo_disponible, meta y eficiencia
                if (['porcion_tiempo', 'numero_operarios', 'tiempo_parada_no_programada', 'tiempo_para_programada', 'tiempo_ciclo', 'cantidad'].includes(currentColumn)) {
                    console.log('Actualizando celdas calculadas:', data.data);
                    console.log('data.data existe:', !!data.data);
                    console.log('data.data.tiempo_disponible:', data.data?.tiempo_disponible);
                    console.log('data.data.meta:', data.data?.meta);
                    console.log('data.data.eficiencia:', data.data?.eficiencia);

                    const tiempoDisponibleCell = currentCell.closest('tr').querySelector('[data-column="tiempo_disponible"]');
                    console.log('tiempoDisponibleCell encontrado:', !!tiempoDisponibleCell);
                    if (tiempoDisponibleCell && data.data && data.data.tiempo_disponible !== undefined) {
                        tiempoDisponibleCell.dataset.value = data.data.tiempo_disponible;
                        tiempoDisponibleCell.textContent = formatDisplayValue('tiempo_disponible', data.data.tiempo_disponible);
                        console.log('Tiempo disponible actualizado:', data.data.tiempo_disponible);
                    }

                    const metaCell = currentCell.closest('tr').querySelector('[data-column="meta"]');
                    console.log('metaCell encontrado:', !!metaCell);
                    if (metaCell && data.data && data.data.meta !== undefined) {
                        metaCell.dataset.value = data.data.meta;
                        metaCell.textContent = formatDisplayValue('meta', data.data.meta);
                        console.log('Meta actualizada:', data.data.meta);
                    }

                    const eficienciaCell = currentCell.closest('tr').querySelector('[data-column="eficiencia"]');
                    console.log('eficienciaCell encontrado:', !!eficienciaCell);
                    if (eficienciaCell && data.data && data.data.eficiencia !== undefined) {
                        eficienciaCell.dataset.value = data.data.eficiencia;
                        eficienciaCell.textContent = formatDisplayValue('eficiencia', data.data.eficiencia);
                        // Actualizar clase de formato condicional
                        eficienciaCell.className = eficienciaCell.className.replace(/eficiencia-\w+/g, '');
                        const newClass = getEficienciaClass(data.data.eficiencia);
                        if (newClass) {
                            eficienciaCell.classList.add(newClass);
                        }
                        console.log('Eficiencia actualizada:', data.data.eficiencia);
                    }
                }

                closeEditModal();
            } else {
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar los cambios');
        });
    }

    function closeEditModal() {
        const modal = document.getElementById('editCellModal');
        if (modal) {
            modal.style.display = 'none';
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
        }
        currentCell = null;
        currentRowId = null;
        currentColumn = null;
    }

    function formatDisplayValue(column, value) {
        if (column === 'fecha' && value) {
            const date = new Date(value);
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        if (column === 'hora' && value) {
            return value;
        }
        if (column === 'eficiencia' && value) {
            return value + '%';
        }
        return value;
    }

    function getEficienciaClass(eficiencia) {
        if (eficiencia === null || eficiencia === undefined) return '';
        eficiencia = parseFloat(eficiencia);
        if (eficiencia < 0.7) return 'eficiencia-red';
        if (eficiencia >= 0.7 && eficiencia < 0.8) return 'eficiencia-yellow';
        if (eficiencia >= 0.8 && eficiencia < 1.0) return 'eficiencia-green';
        if (eficiencia >= 1.0) return 'eficiencia-blue';
        return '';
    }

    // Función para manejar eliminación de registros
    function deleteRegistro(id, section) {
        if (confirm('¿Estás seguro de que quieres eliminar este registro?')) {
            fetch(`/tableros/${id}?section=${section}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Eliminar la fila de la tabla
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        row.remove();
                    }
                    alert('Registro eliminado correctamente.');
                } else {
                    alert('Error al eliminar: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el registro');
            });
        }
    }

    // Agregar event listeners a los botones de eliminar
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            e.preventDefault();
            const id = e.target.dataset.id;
            const section = e.target.dataset.section;
            deleteRegistro(id, section);
        }
    });
});
</script>
@endsection
