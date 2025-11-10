@extends('asesores.layout')

@section('title', 'Detalle del Pedido')
@section('page-title', 'Pedido #' . $pedidoData->pedido)

@section('content')
<div class="pedido-detail-container">
    <!-- Acciones -->
    <div class="detail-actions">
        <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Volver
        </a>
        <div class="action-group">
            <a href="{{ route('asesores.pedidos.edit', $pedidoData->pedido) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Editar
            </a>
            <button type="button" class="btn btn-danger" id="btnEliminar" data-pedido="{{ $pedidoData->pedido }}">
                <i class="fas fa-trash"></i>
                Eliminar
            </button>
        </div>
    </div>

    <!-- Información General -->
    <div class="detail-section">
        <h2 class="section-title">
            <i class="fas fa-info-circle"></i>
            Información General
        </h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Número de Pedido:</label>
                <span class="value">#{{ $pedidoData->pedido }}</span>
            </div>
            <div class="info-item">
                <label>Cliente:</label>
                <span class="value">{{ $pedidoData->cliente }}</span>
            </div>
            <div class="info-item">
                <label>Asesora:</label>
                <span class="value">{{ $pedidoData->asesora }}</span>
            </div>
            <div class="info-item">
                <label>Forma de Pago:</label>
                <span class="value">{{ $pedidoData->forma_de_pago ?? 'No especificada' }}</span>
            </div>
            <div class="info-item">
                <label>Estado:</label>
                <span class="badge badge-{{ 
                    $pedidoData->estado == 'Entregado' ? 'success' : 
                    ($pedidoData->estado == 'En Ejecución' ? 'warning' : 
                    ($pedidoData->estado == 'Anulada' ? 'danger' : 'secondary'))
                }}">
                    {{ $pedidoData->estado ?? 'Sin estado' }}
                </span>
            </div>
            <div class="info-item">
                <label>Área Actual:</label>
                <span class="badge badge-light">{{ $pedidoData->area ?? 'Sin área' }}</span>
            </div>
            <div class="info-item">
                <label>Fecha de Creación:</label>
                <span class="value">
                    {{ $pedidoData->fecha_de_creacion_de_orden ? \Carbon\Carbon::parse($pedidoData->fecha_de_creacion_de_orden)->format('d/m/Y') : '-' }}
                </span>
            </div>
            <div class="info-item">
                <label>Cantidad Total:</label>
                <span class="value">{{ $pedidoData->cantidad ?? 0 }} unidades</span>
            </div>
            @if($pedidoData->descripcion)
                <div class="info-item full-width">
                    <label>Descripción:</label>
                    <p class="value">{{ $pedidoData->descripcion }}</p>
                </div>
            @endif
            @if($pedidoData->novedades)
                <div class="info-item full-width">
                    <label>Novedades:</label>
                    <p class="value">{{ $pedidoData->novedades }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Productos del Pedido -->
    <div class="detail-section">
        <h2 class="section-title">
            <i class="fas fa-box"></i>
            Productos del Pedido ({{ $pedidoData->productos->count() }})
        </h2>
        
        @if($pedidoData->productos->count() > 0)
            <div class="productos-list">
                @foreach($pedidoData->productos as $index => $producto)
                    <div class="producto-card">
                        <div class="producto-header">
                            <h3>{{ $index + 1 }}. {{ $producto->nombre_producto }}</h3>
                            @if($producto->talla)
                                <span class="badge badge-info">Talla: {{ $producto->talla }}</span>
                            @endif
                        </div>
                        <div class="producto-body">
                            @if($producto->descripcion)
                                <div class="producto-descripcion">
                                    <label>Descripción:</label>
                                    <p>{{ $producto->descripcion }}</p>
                                </div>
                            @endif
                            <div class="producto-details">
                                <div class="detail-item">
                                    <label>Cantidad:</label>
                                    <span>{{ $producto->cantidad }} unidades</span>
                                </div>
                                @if($producto->precio_unitario)
                                    <div class="detail-item">
                                        <label>Precio Unitario:</label>
                                        <span>${{ number_format($producto->precio_unitario, 2) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Subtotal:</label>
                                        <strong>${{ number_format($producto->subtotal, 2) }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Resumen de Totales -->
            @if($pedidoData->productos->whereNotNull('subtotal')->count() > 0)
                <div class="totales-summary">
                    <div class="total-item">
                        <span>Total General:</span>
                        <strong>${{ number_format($pedidoData->productos->sum('subtotal'), 2) }}</strong>
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No hay productos en este pedido</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
@endpush
