@extends('layouts.app')

@section('title', 'Detalles de Entrega - Pedido #' . $entrega->numero_pedido)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <span class="material-symbols-rounded me-2">visibility</span>
                Detalles de Entrega
            </h1>
            <p class="text-muted mb-0">Pedido #{{ $entrega->numero_pedido }} - {{ $entrega->cliente }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('entregas-completas.index') }}" class="btn btn-outline-secondary">
                <span class="material-symbols-rounded me-1">arrow_back</span>
                Volver
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <span class="material-symbols-rounded me-1">print</span>
                Imprimir
            </button>
        </div>
    </div>

    <!-- Información General -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <span class="material-symbols-rounded me-1">info</span>
                        Información del Pedido
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Número Pedido:</strong></td>
                            <td>#{{ $entrega->numero_pedido }}</td>
                        </tr>
                        <tr>
                            <td><strong>Número Cotización:</strong></td>
                            <td>{{ $entrega->numero_cotizacion ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cliente:</strong></td>
                            <td>{{ $entrega->cliente }}</td>
                        </tr>
                        <tr>
                            <td><strong>Estado Pedido:</strong></td>
                            <td>
                                <span class="badge estado-pedido {{ getEstadoBadgeClass($entrega->estado_pedido) }}">
                                    {{ $entrega->estado_pedido }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Estado Entrega:</strong></td>
                            <td>
                                <span class="badge estado-entrega {{ getEstadoEntregaBadgeClass($entrega->estado_entrega_general) }}">
                                    {{ $entrega->estado_entrega_general }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Creación:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($entrega->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Fecha Estimada Entrega:</strong></td>
                            <td>{{ $entrega->fecha_estimada_de_entrega ? \Carbon\Carbon::parse($entrega->fecha_estimada_de_entrega)->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <span class="material-symbols-rounded me-1">timeline</span>
                        Timeline de Entregas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Entrega Supervisor -->
                        <div class="timeline-item {{ $entrega->fecha_entrega_supervisor ? 'completed' : 'pending' }}">
                            <div class="timeline-marker">
                                <span class="material-symbols-rounded">
                                    {{ $entrega->fecha_entrega_supervisor ? 'check_circle' : 'radio_button_unchecked' }}
                                </span>
                            </div>
                            <div class="timeline-content">
                                <h6>Entrega Supervisor → Despacho</h6>
                                @if($entrega->fecha_entrega_supervisor)
                                    <p class="text-muted mb-1">
                                        {{ \Carbon\Carbon::parse($entrega->fecha_entrega_supervisor)->format('d/m/Y H:i') }}
                                    </p>
                                    @if($entrega->nombre_supervisor_entrega)
                                        <p class="mb-0"><strong>Responsable:</strong> {{ $entrega->nombre_supervisor_entrega }}</p>
                                    @endif
                                @else
                                    <p class="text-muted mb-0">Pendiente de entrega</p>
                                @endif
                            </div>
                        </div>

                        <!-- Entrega Despacho -->
                        <div class="timeline-item {{ $entrega->fecha_entrega_despacho ? 'completed' : 'pending' }}">
                            <div class="timeline-marker">
                                <span class="material-symbols-rounded">
                                    {{ $entrega->fecha_entrega_despacho ? 'check_circle' : 'radio_button_unchecked' }}
                                </span>
                            </div>
                            <div class="timeline-content">
                                <h6>Entrega Despacho → Asesor</h6>
                                @if($entrega->fecha_entrega_despacho)
                                    <p class="text-muted mb-1">
                                        {{ \Carbon\Carbon::parse($entrega->fecha_entrega_despacho)->format('d/m/Y H:i') }}
                                    </p>
                                    @if($entrega->nombre_despacho_entrega)
                                        <p class="mb-0"><strong>Responsable:</strong> {{ $entrega->nombre_despacho_entrega }}</p>
                                    @endif
                                    @if($entrega->horas_entre_entregas)
                                        <p class="mb-0">
                                            <small class="text-info">
                                                Tiempo desde entrega supervisor: {{ number_format($entrega->horas_entre_entregas, 1) }} horas
                                            </small>
                                        </p>
                                    @endif
                                @else
                                    <p class="text-muted mb-0">Pendiente de entrega</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles de Entrega Supervisor -->
    @if($detallesSupervisor->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <span class="material-symbols-rounded me-1">person</span>
                Detalles de Entrega Supervisor
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Prenda</th>
                            <th>Cantidad</th>
                            <th>Fecha Entrega</th>
                            <th>Entregado Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detallesSupervisor as $detalle)
                            <tr>
                                <td>{{ $detalle->nombre_prenda }}</td>
                                <td>{{ $detalle->cantidad }}</td>
                                <td>{{ \Carbon\Carbon::parse($detalle->fecha_entrega)->format('d/m/Y H:i') }}</td>
                                <td>{{ $detalle->nombre_usuario }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Detalles de Entrega Despacho -->
    @if($detallesDespacho->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <span class="material-symbols-rounded me-1">local_shipping</span>
                Detalles de Entrega Despacho
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo Item</th>
                            <th>Género</th>
                            <th>Fecha Entrega</th>
                            <th>Entregado Por</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detallesDespacho as $detalle)
                            <tr>
                                <td>
                                    <span class="badge bg-primary">{{ $detalle->tipo_item }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $detalle->genero }}</span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($detalle->fecha_entrega)->format('d/m/Y H:i') }}</td>
                                <td>{{ $detalle->nombre_usuario ?? '-' }}</td>
                                <td>{{ $detalle->observaciones ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Resumen -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <span class="material-symbols-rounded me-1">summarize</span>
                Resumen de Entregas
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="text-primary">{{ $detallesSupervisor->count() }}</h4>
                        <p class="text-muted">Prendas entregadas por supervisor</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="text-success">{{ $detallesDespacho->count() }}</h4>
                        <p class="text-muted">Parciales entregados por despacho</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="text-info">{{ $entrega->horas_entre_entregas ? number_format($entrega->horas_entre_entregas, 1) . 'h' : 'N/A' }}</h4>
                        <p class="text-muted">Tiempo entre entregas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
function getEstadoBadgeClass($estado) {
    $classes = [
        'Pendiente' => 'bg-secondary',
        'No iniciado' => 'bg-secondary',
        'En Ejecución' => 'bg-primary',
        'Entregado' => 'bg-success',
        'Anulada' => 'bg-danger',
        'PENDIENTE_SUPERVISOR' => 'bg-warning',
        'pendiente_cartera' => 'bg-info',
        'RECHAZADO_CARTERA' => 'bg-danger',
        'PENDIENTE_INSUMOS' => 'bg-purple',
        'DEVUELTO_A_ASESORA' => 'bg-orange',
    ];
    return $classes[$estado] ?? 'bg-secondary';
}

function getEstadoEntregaBadgeClass($estado) {
    $classes = [
        'Completado' => 'bg-success',
        'Pendiente Despacho' => 'bg-warning',
        'Pendiente Supervisor' => 'bg-info',
        'Pendiente Ambos' => 'bg-secondary',
    ];
    return $classes[$estado] ?? 'bg-secondary';
}
@endphp

@push('styles')
<link href="{{ asset('css/entregas-completas.css') }}" rel="stylesheet">
@endpush
