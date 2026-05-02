<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Pendientes Costura</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { margin: 0 0 8px 0; font-size: 18px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .section-title { font-size: 14px; margin: 12px 0 8px 0; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Reporte de Pendientes Costura por Area</h1>

    <div class="meta">
        <div><strong>Fecha de generacion:</strong> {{ $fechaGeneracion->format('Y-m-d H:i:s') }}</div>
        <div><strong>Total recibos:</strong> {{ $totalRecibos }}</div>
        <div>
            <strong>Filtros aplicados:</strong>
            @php
                $filtrosActivos = collect($filtros)
                    ->filter(fn($value) => $value !== null && trim((string) $value) !== '')
                    ->map(fn($value, $key) => $key . ': ' . $value)
                    ->values();
            @endphp
            @if($filtrosActivos->isEmpty())
                <span class="muted">Ninguno</span>
            @else
                {{ $filtrosActivos->implode(' | ') }}
            @endif
        </div>
    </div>

    <div class="section-title"><strong>Resumen por area</strong></div>
    <table>
        <thead>
            <tr>
                <th>Area</th>
                <th>Cantidad recibos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $area => $rows)
                <tr>
                    <td>{{ $area }}</td>
                    <td>{{ $rows->count() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Sin datos</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title"><strong>Detalle</strong></div>
    <table>
        <thead>
            <tr>
                <th>Area</th>
                <th>N° Recibo</th>
                <th>Fecha Creacion</th>
                <th>Cliente</th>
                <th>Asesora</th>
                <th>Pedido ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $area => $rows)
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $area }}</td>
                        <td>{{ data_get($row, 'numero_recibo', '') }}</td>
                        <td>
                            @php $fecha = data_get($row, 'fecha_creacion'); @endphp
                            {{ $fecha ? \Carbon\Carbon::parse((string) $fecha)->format('Y-m-d') : '' }}
                        </td>
                        <td>{{ data_get($row, 'cliente', '') }}</td>
                        <td>{{ data_get($row, 'asesor', '') }}</td>
                        <td>{{ data_get($row, 'pedido_id', '') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6">Sin datos</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
