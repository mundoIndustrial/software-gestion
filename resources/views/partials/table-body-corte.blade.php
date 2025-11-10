@php
    // Función para determinar la clase de eficiencia
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

@foreach($registros as $registro)
<tr class="table-row {{ (str_contains(strtolower($registro->actividad), 'extender') || str_contains(strtolower($registro->actividad), 'trazar')) ? 'extend-trazar-row' : '' }}" data-id="{{ $registro->id }}">
    @foreach($columns as $column)
        @php
            $value = $registro->$column;
            $displayValue = $value;
            $dataValue = $value; // Valor para data-value
            
            if ($column === 'fecha' && $value) {
                $displayValue = $value->format('d-m-Y');
                $dataValue = $displayValue;
            } elseif ($column === 'hora_id' && $registro->hora) {
                $displayValue = $registro->hora->hora;
            } elseif ($column === 'operario_id' && $registro->operario) {
                $displayValue = $registro->operario->name;
                $dataValue = $registro->operario->name; // Usar nombre en lugar de ID
            } elseif ($column === 'maquina_id' && $registro->maquina) {
                $displayValue = $registro->maquina->nombre_maquina;
                $dataValue = $registro->maquina->nombre_maquina; // Usar nombre en lugar de ID
            } elseif ($column === 'tela_id' && $registro->tela) {
                $displayValue = $registro->tela->nombre_tela;
                $dataValue = $registro->tela->nombre_tela; // Usar nombre en lugar de ID
            } elseif ($column === 'eficiencia' && $value !== null) {
                $displayValue = round($value * 100, 1) . '%';
            }
            $eficienciaClass = ($column === 'eficiencia' && $value !== null) ? getEficienciaClass($value) : '';
        @endphp
        <td class="table-cell editable-cell {{ $eficienciaClass }}" data-column="{{ $column }}" data-value="{{ $dataValue }}" title="Doble clic para editar">{{ $displayValue }}</td>
    @endforeach
    <td class="table-cell">
        <div class="action-buttons">
            <button class="duplicate-btn" data-id="{{ $registro->id }}" data-section="corte" title="Duplicar registro">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
            <button class="delete-btn" data-id="{{ $registro->id }}" data-section="corte" title="Eliminar registro">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
        </div>
    </td>
</tr>
@endforeach
