@extends('layouts.asesores')

@section('title', 'Editar Pedido')
@section('page-title', 'Editar Pedido #' . $pedidoData->pedido)

@section('content')
<div class="pedidos-container">
    <form id="formEditarPedido" class="pedido-form" data-pedido="{{ $pedidoData->pedido }}">
        @csrf
        @method('PUT')
        
        <!-- Información General -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Información General
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="pedido">Número de Pedido</label>
                    <input type="number" id="pedido" value="{{ $pedidoData->pedido }}" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" id="cliente" name="cliente" value="{{ $pedidoData->cliente }}" required>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago">Forma de Pago</label>
                    <select id="forma_de_pago" name="forma_de_pago">
                        <option value="">Seleccionar...</option>
                        <option value="Crédito" {{ $pedidoData->forma_de_pago == 'Crédito' ? 'selected' : '' }}>Crédito</option>
                        <option value="Contado" {{ $pedidoData->forma_de_pago == 'Contado' ? 'selected' : '' }}>Contado</option>
                        <option value="50/50" {{ $pedidoData->forma_de_pago == '50/50' ? 'selected' : '' }}>50/50</option>
                        <option value="Anticipo" {{ $pedidoData->forma_de_pago == 'Anticipo' ? 'selected' : '' }}>Anticipo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" {{ $pedidoData->estado == $estado ? 'selected' : '' }}>
                                {{ $estado }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="descripcion">Descripción General</label>
                    <textarea id="descripcion" name="descripcion" rows="3">{{ $pedidoData->descripcion }}</textarea>
                </div>

                <div class="form-group full-width">
                    <label for="novedades">Novedades</label>
                    <textarea id="novedades" name="novedades" rows="2">{{ $pedidoData->novedades }}</textarea>
                </div>
            </div>
        </div>

        <!-- Productos del Pedido -->
        <div class="form-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-box"></i>
                    Productos del Pedido
                </h2>
                <button type="button" class="btn btn-add-product" id="btnAgregarProducto">
                    <i class="fas fa-plus"></i>
                    Agregar Producto
                </button>
            </div>

            <div id="productosContainer" class="productos-container">
                @foreach($pedidoData->productos as $index => $producto)
                    <div class="producto-item">
                        <div class="producto-header">
                            <h3>Producto {{ $index + 1 }}</h3>
                            <button type="button" class="btn-remove-product">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="producto-body">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Nombre del Producto *</label>
                                    <input type="text" name="productos[{{ $index }}][nombre_producto]" value="{{ $producto->nombre_producto }}" required>
                                </div>

                                <div class="form-group full-width">
                                    <label>Descripción Completa</label>
                                    <textarea name="productos[{{ $index }}][descripcion]" rows="3">{{ $producto->descripcion }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label>Talla</label>
                                    <input type="text" name="productos[{{ $index }}][talla]" value="{{ $producto->talla }}">
                                </div>

                                <div class="form-group">
                                    <label>Cantidad *</label>
                                    <input type="number" name="productos[{{ $index }}][cantidad]" min="1" value="{{ $producto->cantidad }}" class="producto-cantidad" required>
                                </div>

                                <div class="form-group">
                                    <label>Precio Unitario</label>
                                    <input type="number" name="productos[{{ $index }}][precio_unitario]" min="0" step="0.01" value="{{ $producto->precio_unitario }}" class="producto-precio">
                                </div>

                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" class="producto-subtotal" readonly value="${{ number_format($producto->subtotal ?? 0, 2) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="productos-summary">
                <div class="summary-item">
                    <span>Total de Productos:</span>
                    <strong id="totalProductos">{{ $pedidoData->productos->count() }}</strong>
                </div>
                <div class="summary-item">
                    <span>Cantidad Total:</span>
                    <strong id="cantidadTotal">{{ $pedidoData->productos->sum('cantidad') }}</strong>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions">
            <a href="{{ route('asesores.pedidos.show', $pedidoData->pedido) }}" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Actualizar Pedido
            </button>
        </div>
    </form>
</div>

<!-- Template para Producto -->
<template id="productoTemplate">
    <div class="producto-item">
        <div class="producto-header">
            <h3>Producto <span class="producto-numero"></span></h3>
            <button type="button" class="btn-remove-product">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="producto-body">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="productos[][nombre_producto]" required>
                </div>

                <div class="form-group full-width">
                    <label>Descripción Completa</label>
                    <textarea name="productos[][descripcion]" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Talla</label>
                    <input type="text" name="productos[][talla]">
                </div>

                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" name="productos[][cantidad]" min="1" value="1" class="producto-cantidad" required>
                </div>

                <div class="form-group">
                    <label>Precio Unitario</label>
                    <input type="number" name="productos[][precio_unitario]" min="0" step="0.01" class="producto-precio">
                </div>

                <div class="form-group">
                    <label>Subtotal</label>
                    <input type="text" class="producto-subtotal" readonly placeholder="$0.00">
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script>
    // Marcar como modo edición
    document.getElementById('formCrearPedido').id = 'formEditarPedido';
</script>
@endpush
