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
                            
                            <!-- VARIACIONES -->
                            @if($producto->color_id || $producto->tela_id || $producto->tipo_manga_id || $producto->tipo_broche_id)
                                <div class="producto-variaciones" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #0066cc; font-size: 0.95rem; font-weight: 600;">
                                        <i class="fas fa-sliders-h"></i> Variaciones
                                    </h4>
                                    
                                    @if($producto->color_id && $producto->color)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Color:</span>
                                            <span>{{ $producto->color->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tela_id && $producto->tela)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Tela:</span>
                                            <span>{{ $producto->tela->nombre }}</span>
                                            @if($producto->tela->referencia)
                                                <span style="color: #64748b; font-size: 0.85rem;">(Ref: {{ $producto->tela->referencia }})</span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($producto->tipo_manga_id && $producto->tipoManga)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Manga:</span>
                                            <span>{{ $producto->tipoManga->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tipo_broche_id && $producto->tipoBroche)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Broche:</span>
                                            <span>{{ $producto->tipoBroche->nombre }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tiene_bolsillos)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Bolsillos:</span>
                                            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">Sí</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->tiene_reflectivo)
                                        <div style="margin-bottom: 0.5rem;">
                                            <span style="font-weight: 600; color: #0066cc;">Reflectivo:</span>
                                            <span style="background: #10b981; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">Sí</span>
                                        </div>
                                    @endif
                                    
                                    @if($producto->descripcion_variaciones)
                                        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #eee;">
                                            <span style="font-weight: 600; color: #0066cc;">Observaciones:</span>
                                            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">{{ $producto->descripcion_variaciones }}</p>
                                        </div>
                                    @endif
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
