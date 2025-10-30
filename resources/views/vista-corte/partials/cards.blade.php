<div class="cards-container">
    @php
        $groupedRegistros = $registros->groupBy('pedido');
    @endphp

    @foreach($groupedRegistros as $pedido => $groupRegistros)
        <div class="pedido-card">
            <div class="card-header">
                <h3>{{ $pedido ?: '-' }}</h3>
                <div class="encargado-corte">
                    <span class="encargado-label">Encargado de Corte:</span>
                    <span class="encargado-value">
                        @php
                            $encargado = '-';
                            $registro = \App\Models\TablaOriginal::where('pedido', $pedido)->first();
                            if ($registro && isset($registro->encargados_de_corte)) {
                                $encargado = $registro->encargados_de_corte;
                            }
                        @endphp
                        {{ $encargado }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <table class="card-table">
                    <thead>
                        <tr>
                            <th>Prenda</th>
                            <th>Cortador</th>
                            <th>Cantidad Prendas</th>
                            <th>Piezas</th>
                            <th>Pasadas</th>
                            <th>Etiquetadas</th>
                            <th>Etiquetador</th>
                            <th>Fecha Entrega</th>
                            <th>Mes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupRegistros as $registro)
                            <tr>
                                <td class="prenda-cell cell-clickable" data-content="{{ $registro->prenda ?: '-' }}">{{ $registro->prenda ?: '-' }}</td>
                                <td class="cortador-cell">{{ $registro->cortador ?: '-' }}</td>
                                <td class="cantidad_prendas-cell">{{ $registro->cantidad_prendas ?: '-' }}</td>
                                <td class="piezas-cell">{{ $registro->piezas ?: '-' }}</td>
                                <td class="pasadas-cell">{{ $registro->pasadas ?: '-' }}</td>
                                <td class="etiqueteadas-cell">{{ $registro->etiqueteadas ?: '-' }}</td>
                                <td class="etiquetador-cell">{{ $registro->etiquetador ?: '-' }}</td>
                                <td class="fecha_entrega-cell">
                                    @if($registro->fecha_entrega)
                                        {{ \Carbon\Carbon::parse($registro->fecha_entrega)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="mes-cell">{{ $registro->mes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
