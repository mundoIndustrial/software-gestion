<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 14px; margin: 0 0 8px; }
        .meta { margin-bottom: 10px; font-size: 9px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 4px; vertical-align: top; word-wrap: break-word; }
        th { background: #f3f4f6; font-size: 9px; }
        td { font-size: 8px; }
    </style>
</head>
<body>
    <h1>Reporte de Seguimiento - Dashboard</h1>
    <div class="meta">Generado: {{ $fechaGeneracion->format('Y-m-d H:i:s') }}</div>

    <table>
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Recibo</th>
                <th>Tipo</th>
                <th>Día Entrega</th>
                <th>Fecha Estimada</th>
                <th>Aprob. Cartera</th>
                <th>Demora Creación→Cartera</th>
                <th>Aprob. Supervisor</th>
                <th>Demora Cartera→Supervisor</th>
                <th>Aprob. Insumos/Corte</th>
                <th>Demora Supervisor→Insumos</th>
                <th>Seguimiento Procesos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['numero_pedido'] }}</td>
                    <td>{{ $row['cliente'] }}</td>
                    <td>{{ $row['numero_recibo'] }}</td>
                    <td>{{ $row['tipo_recibo'] }}</td>
                    <td>{{ $row['dia_de_entrega'] }}</td>
                    <td>{{ $row['fecha_estimada_de_entrega'] }}</td>
                    <td>{{ $row['aprobado_por_cartera_en'] }}</td>
                    <td>{{ $row['demora_creacion_cartera'] }}</td>
                    <td>{{ $row['aprobado_por_supervisor_en'] }}</td>
                    <td>{{ $row['demora_cartera_supervisor'] }}</td>
                    <td>{{ $row['recibo_aprobado_insumos_en'] }}</td>
                    <td>{{ $row['demora_supervisor_insumos'] }}</td>
                    <td>{{ $row['procesos_resumen'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No hay datos para el reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

