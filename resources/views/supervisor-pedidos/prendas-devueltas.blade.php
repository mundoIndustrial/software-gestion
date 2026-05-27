@extends('supervisor-pedidos.layout')

@section('title', 'Prendas Devueltas')
@section('page-title', 'Prendas Devueltas')
@section('search-action', route('supervisor-pedidos.prendas-devueltas'))

@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Prendas enviadas a corrección</h5>
                <small class="text-muted">Pedidos/prendas con recibos en estado DEVUELTO_ASESOR.</small>
            </div>
            <span class="badge bg-danger">{{ $prendasDevueltas->total() }} registro(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Asesora</th>
                            <th>Prenda</th>
                            <th class="text-center">Recibos Devueltos</th>
                            <th>Última Devolución</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($prendasDevueltas as $row)
                            <tr>
                                <td>
                                    <strong>#{{ $row->numero_pedido }}</strong>
                                </td>
                                <td>{{ $row->cliente ?: 'Sin cliente' }}</td>
                                <td>{{ $row->asesor_nombre ?: 'Sin asesora' }}</td>
                                <td>{{ $row->prenda_nombre ?: 'Prenda sin nombre' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ (int) $row->recibos_devueltos }}</span>
                                </td>
                                <td>
                                    {{ $row->ultima_devolucion_en ? \Carbon\Carbon::parse($row->ultima_devolucion_en)->format('d/m/Y h:i A') : 'Sin fecha' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay prendas devueltas a asesora con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($prendasDevueltas->hasPages())
            <div class="card-footer bg-white">
                {{ $prendasDevueltas->links('vendor.pagination.bootstrap-custom') }}
            </div>
        @endif
    </div>
</div>
@endsection

