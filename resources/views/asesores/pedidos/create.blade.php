@extends('asesores.layout')

@section('title', 'Crear Pedido')
@section('page-title', 'Crear Nuevo Pedido')

@section('content')
<div class="pedidos-container">
    <form id="formCrearPedido" class="pedido-form">
        @csrf
        
        <!-- Información General -->
        <div class="form-section">
            <h2 class="section-title">
                <i class="fas fa-info-circle"></i>
                Información General
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="pedido">Número de Pedido *</label>
                    <input type="number" id="pedido" name="pedido" value="{{ $siguientePedido }}" readonly required>
                </div>

                <div class="form-group">
                    <label for="cliente">Cliente *</label>
                    <input type="text" id="cliente" name="cliente" placeholder="Ej: DOTACIÓN DE PALMA" required>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago">Forma de Pago</label>
                    <select id="forma_de_pago" name="forma_de_pago">
                        <option value="">Seleccionar...</option>
                        <option value="Crédito">Crédito</option>
                        <option value="Contado">Contado</option>
                        <option value="50/50">50/50</option>
                        <option value="Anticipo">Anticipo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado">Estado Inicial</label>
                    <select id="estado" name="estado">
                        <option value="No iniciado" selected>No iniciado</option>
                        <option value="En Ejecución">En Ejecución</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="descripcion">Descripción General</label>
                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Descripción general del pedido..."></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="novedades">Novedades</label>
                    <textarea id="novedades" name="novedades" rows="2" placeholder="Novedades o instrucciones especiales..."></textarea>
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
                <!-- Los productos se agregarán aquí dinámicamente -->
            </div>

            <div class="productos-summary">
                <div class="summary-item">
                    <span>Total de Productos:</span>
                    <strong id="totalProductos">0</strong>
                </div>
                <div class="summary-item">
                    <span>Cantidad Total:</span>
                    <strong id="cantidadTotal">0</strong>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="form-actions">
            <a href="{{ route('asesores.pedidos.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Guardar Pedido
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
                <!-- Tipo de Prenda -->
                <div class="form-group">
                    <label>Tipo de Prenda *</label>
                    <input type="text" name="productos[][nombre_producto]" placeholder="Ej: CAMISA TIPO POLO" required>
                </div>

                <!-- Tela -->
                <div class="form-group">
                    <label>Tela</label>
                    <input type="text" name="productos[][tela]" placeholder="Ej: TELA LAFAYETTE">
                </div>

                <!-- Manga -->
                <div class="form-group">
                    <label>Tipo de Manga</label>
                    <select name="productos[][tipo_manga]">
                        <option value="">Seleccionar...</option>
                        <option value="Manga Corta">Manga Corta</option>
                        <option value="Manga Larga">Manga Larga</option>
                        <option value="Sin Manga">Sin Manga</option>
                        <option value="Manga 3/4">Manga 3/4</option>
                    </select>
                </div>

                <!-- Color -->
                <div class="form-group">
                    <label>Color *</label>
                    <input type="text" name="productos[][color]" placeholder="Ej: AZUL REY, NEGRO" required>
                </div>

                <!-- Talla -->
                <div class="form-group">
                    <label>Talla *</label>
                    <input type="text" name="productos[][talla]" placeholder="Ej: S, M, L, XL" required>
                </div>

                <!-- Género -->
                <div class="form-group">
                    <label>Género</label>
                    <select name="productos[][genero]">
                        <option value="">Seleccionar...</option>
                        <option value="Dama">Dama</option>
                        <option value="Caballero">Caballero</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" name="productos[][cantidad]" min="1" value="1" class="producto-cantidad" required>
                </div>

                <!-- Referencia de Hilo -->
                <div class="form-group">
                    <label>Ref. Hilo</label>
                    <input type="text" name="productos[][ref_hilo]" placeholder="Ej: REF HILO 293">
                </div>

                <!-- Descripción Completa -->
                <div class="form-group full-width">
                    <label>Descripción Completa</label>
                    <textarea name="productos[][descripcion]" rows="4" placeholder="Ej: CON CUELLOS Y PUÑOS EN HILO, CON BROCHES, SIN BOLSILLO, MODELO DE BODEGA..."></textarea>
                </div>

                <!-- Bordados/Logos -->
                <div class="form-group full-width">
                    <label>Bordados / Logos</label>
                    <textarea name="productos[][bordados]" rows="3" placeholder="Ej: LOGO BORDADO EN LADO DERECHO DEL PECHO 'INVERSIONES EVAN', LOGO BORDADO EN LADO IZQUIERDO DEL PECHO 'DIFFER'"></textarea>
                </div>

                <!-- Modelo/Referencia Foto -->
                <div class="form-group full-width">
                    <label>Modelo / Referencia Foto</label>
                    <input type="text" name="productos[][modelo_foto]" placeholder="URL de la foto o descripción del modelo">
                </div>

                <!-- Precio y Subtotal -->
                <div class="form-group">
                    <label>Precio Unitario</label>
                    <input type="number" name="productos[][precio_unitario]" min="0" step="0.01" class="producto-precio" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Subtotal</label>
                    <input type="text" class="producto-subtotal" readonly placeholder="$0.00">
                </div>

                <!-- Notas Adicionales -->
                <div class="form-group full-width">
                    <label>Notas Adicionales</label>
                    <textarea name="productos[][notas]" rows="2" placeholder="Cualquier observación adicional sobre este producto..."></textarea>
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
@endpush
