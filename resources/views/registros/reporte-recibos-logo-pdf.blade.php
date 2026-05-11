<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Recibos de Logo</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { margin: 0 0 8px 0; font-size: 18px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: bold; }
        .section-title { font-size: 14px; margin: 12px 0 8px 0; }
        .muted { color: #6b7280; }
        .prenda-item { margin-bottom: 4px; padding: 2px 0; }
        .area-badge { background: #e8f3ff; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>
    <h1>Reporte de Recibos de Logo</h1>

    <div class="meta">
        <div><strong>Fecha de generacion:</strong> {{ $fechaGeneracion->format('d/m/Y H:i:s') }}</div>
        <div><strong>Total recibos:</strong> {{ $totalRecibos }}</div>
        <div>
            <strong>Filtros aplicados:</strong>
            @php
                $filtrosActivos = [];
                foreach ($filtros as $key => $value) {
                    if ($value !== null && trim((string) $value) !== '') {
                        if ($key === 'dias_antiguedad') {
                            $filtrosActivos[] = 'Antigüedad: 1 a ' . $value . ' días';
                        }
                    }
                }
            @endphp
            @if(empty($filtrosActivos))
                <span class="muted">Ninguno</span>
            @else
                {{ implode(' | ', $filtrosActivos) }}
            @endif
        </div>
    </div>

    <div class="section-title"><strong>Resumen por área</strong></div>
    <table>
        <thead>
            <tr>
                <th>Área</th>
                <th>Cantidad recibos</th>
            </tr>
        </thead>
        <tbody>
            @php
                $resumenArea = [];
                foreach ($grouped as $dias => $rows) {
                    foreach ($rows as $row) {
                        $area = trim((string) data_get($row, 'area', ''));
                        $area = $area !== '' ? $area : 'Sin Área';
                        $resumenArea[$area] = ($resumenArea[$area] ?? 0) + 1;
                    }
                }
                ksort($resumenArea);
            @endphp
            @forelse($resumenArea as $area => $cantidad)
                <tr>
                    <td>{{ $area }}</td>
                    <td>{{ $cantidad }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">Sin datos</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title"><strong>Detalle (agrupado por antigüedad en días hábiles)</strong></div>
    @forelse($grouped as $dias => $rows)
        <div style="margin-bottom: 16px;">
            <h3 style="margin: 8px 0; font-size: 12px; color: #1f2937; background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">
                {{ $dias }} día{{ $dias != 1 ? 's' : '' }} hábiles ({{ $rows->count() }} recibos)
            </h3>
        </div>
        <table style="margin-bottom: 14px;">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th style="width: 70px;">Area</th>
                    <th style="width: 60px;">No. Recibo</th>
                    <th style="width: 70px;">Fecha</th>
                    <th style="width: 120px;">Cliente</th>
                    <th style="width: 200px;">Prendas</th>
                    <th style="width: 80px;">Asesora</th>
                </tr>
            </thead>
            <tbody>
                @php $contador = 1; @endphp
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $contador++ }}</td>
                        <td><span class="area-badge">{{ data_get($row, 'area', '') }}</span></td>
                        <td>{{ data_get($row, 'numero_recibo', '') }}</td>
                        <td>
                            @php $fecha = data_get($row, 'fecha_creacion'); @endphp
                            {{ $fecha ? \Carbon\Carbon::parse((string) $fecha)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ data_get($row, 'cliente', '') }}</td>
                        <td>
                            @php
                                $prendas = data_get($row, 'prendas', []);
                                $prendasAgrupadas = [];
                                if (is_array($prendas) || $prendas instanceof Illuminate\Support\Collection) {
                                    foreach($prendas as $prenda) {
                                        $colorNombre = !empty($prenda->color_nombre) ? $prenda->color_nombre : '';
                                        $cantidadColor = !empty($prenda->cantidad_color) ? $prenda->cantidad_color : 0;
                                        $cantidadTalla = !empty($prenda->cantidad_talla) ? $prenda->cantidad_talla : 0;
                                        $tela = !empty($prenda->tela) ? ' ' . $prenda->tela : '';

                                        if (!empty($colorNombre) && !empty($cantidadColor)) {
                                            $key = $prenda->nombre_prenda . '|' . $colorNombre;
                                            $prendasAgrupadas[$key] = ($prendasAgrupadas[$key] ?? 0) + (int) $cantidadColor;
                                        } elseif (!empty($cantidadTalla)) {
                                            $key = $prenda->nombre_prenda . $tela . '|sin-color';
                                            $prendasAgrupadas[$key] = ($prendasAgrupadas[$key] ?? 0) + (int) $cantidadTalla;
                                        }
                                    }
                                }
                            @endphp
                            @forelse($prendasAgrupadas as $prenda => $cantidad)
                                <div class="prenda-item">
                                    @php
                                        $partes = explode('|', $prenda);
                                        $nombrePrenda = $partes[0];
                                        $tipo = $partes[1] ?? 'sin-color';

                                        if($tipo === 'sin-color') {
                                            echo $cantidad . ' ' . $nombrePrenda;
                                        } else {
                                            echo $cantidad . ' ' . $nombrePrenda . ' color ' . $tipo;
                                        }
                                    @endphp
                                </div>
                            @empty
                                <span class="muted">-</span>
                            @endforelse
                        </td>
                        <td>{{ data_get($row, 'asesor', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div class="section-title" style="color: #6b7280;">Sin datos para mostrar</div>
    @endforelse

</body>
</html>
