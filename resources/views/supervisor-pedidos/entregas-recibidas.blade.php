@extends('supervisor-pedidos.layout')

@section('title', 'Entregas y Recibidas')
@section('page-title', 'Entregas y Recibidas')

@push('styles')
<style>
    .er-wrapper {
        padding: 1rem;
    }

    .er-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 26px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    .er-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #edf0f3;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .er-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .er-subtitle {
        margin: 0.2rem 0 0 0;
        font-size: 0.83rem;
        color: #64748b;
    }

    .er-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        white-space: nowrap;
    }

    .er-table tbody td {
        vertical-align: middle;
        border-color: #f1f5f9;
        color: #1f2937;
        font-size: 0.86rem;
    }

    .er-muted {
        color: #94a3b8;
    }

    .er-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .er-chip-ok {
        background: #dcfce7;
        color: #166534;
    }

    .er-chip-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .er-chip-empty {
        background: #e2e8f0;
        color: #475569;
    }

    .er-pagination {
        display: flex;
        justify-content: center;
    }

    .er-pagination .pagination {
        margin-bottom: 0;
        gap: 0.25rem;
    }

    .er-pagination .page-link {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 0.4rem 0.7rem;
        line-height: 1.1;
    }

    .er-pagination .page-item.active .page-link {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .er-pagination .page-item.disabled .page-link {
        color: #94a3b8;
        background: #f8fafc;
        border-color: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="supervisor-pedidos-container er-wrapper">
    <div class="card er-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 er-table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Prenda</th>
                            <th>Recibo</th>
                            <th>Fecha Entrega</th>
                            <th>Usuario Entrega</th>
                            <th>Fecha Recibido</th>
                            <th>Usuario Recibido</th>
                            <th>Estado</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $fila)
                            <tr>
                                <td><strong>#{{ $fila->numero_pedido ?? '-' }}</strong></td>
                                <td>{{ $fila->cliente ?? '-' }}</td>
                                <td>{{ $fila->nombre_prenda ?? '-' }}</td>
                                <td>{{ $fila->numero_recibo ? ('#' . $fila->numero_recibo) : '—' }}</td>
                                <td>{{ $fila->fecha_entrega ? \Carbon\Carbon::parse($fila->fecha_entrega)->format('d/m/Y H:i') : '—' }}</td>
                                <td>{{ $fila->usuario_entrega ?? '—' }}</td>
                                <td>{{ $fila->fecha_recibido ? \Carbon\Carbon::parse($fila->fecha_recibido)->format('d/m/Y H:i') : '—' }}</td>
                                <td>{{ $fila->usuario_recibido ?? '—' }}</td>
                                <td>
                                    @if(($fila->estado_recibido ?? null) === 'recibido')
                                        <span class="er-chip er-chip-ok">Recibido</span>
                                    @elseif(($fila->estado_recibido ?? null) === 'pendiente')
                                        <span class="er-chip er-chip-pending">Pendiente</span>
                                    @else
                                        <span class="er-chip er-chip-empty">Sin recibir</span>
                                    @endif
                                </td>
                                <td>{{ $fila->cantidad_entregada ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center er-muted py-4">
                                    No hay registros para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top er-pagination" style="border-color:#edf0f3 !important;">
                {{ $registros->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection
