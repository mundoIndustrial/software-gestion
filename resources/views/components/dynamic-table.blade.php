<!-- Tabla de registros -->
<div x-show="showRecords" class="records-table-container">
    <div class="table-scroll-container">
        <table class="modern-table">
            <thead class="table-head">
                <tr>
                    @foreach($columns as $column)
                        <th class="table-header-cell">{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
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
                        @endphp
                        <td class="table-cell editable-cell" data-column="{{ $column }}" data-value="{{ $value }}">{{ $displayValue }}</td>
                    @endforeach
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
