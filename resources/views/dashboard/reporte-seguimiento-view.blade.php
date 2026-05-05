@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-primary font-weight-bold">Reporte de Seguimiento</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard.reporte-seguimiento', ['format' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">
                    <span class="material-symbols-rounded align-middle" style="font-size: 18px;">download</span> Descargar CSV
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
                    <span class="material-symbols-rounded align-middle" style="font-size: 18px;">arrow_back</span> Volver al Dashboard
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 75vh;">
                <table class="table table-hover table-striped table-bordered mb-0">
                    <thead class="bg-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th>ID Pedido</th>
                            <th>Nº Pedido</th>
                            <th>Cliente</th>
                            <th>Asesor</th>
                            <th>Recibo</th>
                            <th>Tipo Recibo</th>
                            <th>Día Entrega</th>
                            <th>Fecha Estimada</th>
                            <th>Creación Pedido</th>
                            <th>Aprobación Cartera</th>
                            <th>Aprobación Supervisor</th>
                            <th>Aprobación Insumos</th>
                            <th>Creación Recibo</th>
                            <th>Resumen Procesos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['pedido_id'] }}</td>
                                <td><strong>{{ $row['numero_pedido'] }}</strong></td>
                                <td>{{ $row['cliente'] }}</td>
                                <td>{{ $row['asesor_nombre'] }}</td>
                                <td>{{ $row['numero_recibo'] }}</td>
                                <td><span class="badge bg-secondary">{{ $row['tipo_recibo'] }}</span></td>
                                <td>{{ $row['dia_de_entrega'] }}</td>
                                <td>{{ $row['fecha_estimada_de_entrega'] }}</td>
                                <td>{{ $row['pedido_creado_en'] }}</td>
                                <td>{{ $row['aprobado_por_cartera_en'] }}</td>
                                <td>{{ $row['aprobado_por_supervisor_en'] }}</td>
                                <td>{{ $row['recibo_aprobado_insumos_en'] }}</td>
                                <td>{{ $row['recibo_creado_en'] }}</td>
                                <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $row['procesos_resumen'] }}">
                                    {{ $row['procesos_resumen'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-4">No hay datos para mostrar en el reporte.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted text-right py-2" style="font-size: 0.85rem;">
            Generado el: {{ $fechaGeneracion->format('Y-m-d H:i:s') }} | Total registros: {{ count($rows) }}
        </div>
    </div>
</div>

<style>
    /* Estilos adicionales si el tema no usa Bootstrap 5 o para refinar */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #4b5563;
        background-color: #f3f4f6;
        white-space: nowrap;
        vertical-align: middle;
    }
    .table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .sticky-top {
        position: sticky;
        top: 0;
    }
</style>
@endsection
