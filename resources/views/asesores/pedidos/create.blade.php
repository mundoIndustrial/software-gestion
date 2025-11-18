@extends('asesores.layout-clean')

@section('title', 'Crear Pedido')

@section('content')
<div class="orden-create-wrapper">
    <!-- Header con botón volver -->
    <div class="orden-create-header">
        <a href="{{ route('asesores.pedidos.index') }}" class="btn-volver">
            <span class="material-symbols-rounded">arrow_back</span>
            <span>Volver</span>
        </a>
        <div class="header-info">
            <h1>
                <span class="material-symbols-rounded">note_add</span>
                Nuevo Pedido
            </h1>
            <p>Crea un nuevo pedido con toda la información necesaria</p>
        </div>
    </div>

    <form id="formCrearPedido" class="orden-form">
    <form id="formCrearPedido" class="orden-form">
        @csrf
        
        <!-- SECCIÓN 1: Información General -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-rounded">info</span>
                </div>
                <div>
                    <h2>Información General</h2>
                    <p>Datos principales del pedido</p>
                </div>
            </div>

            <div class="section-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="pedido">Número de Pedido <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="material-symbols-rounded">numbers</span>
                            <input type="number" id="pedido" name="pedido" value="{{ $siguientePedido }}" readonly required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cliente">Cliente <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="material-symbols-rounded">business</span>
                            <input type="text" id="cliente" name="cliente" placeholder="Ej: DOTACIÓN DE PALMA" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="forma_de_pago">Forma de Pago</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-rounded">credit_card</span>
                            <select id="forma_de_pago" name="forma_de_pago" class="form-control">
                                <option value="">Seleccionar...</option>
                                <option value="Crédito">Crédito</option>
                                <option value="Contado">Contado</option>
                                <option value="50/50">50/50</option>
                                <option value="Anticipo">Anticipo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado Inicial</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-rounded">status</span>
                            <select id="estado" name="estado" class="form-control">
                                <option value="No iniciado" selected>No iniciado</option>
                                <option value="En Ejecución">En Ejecución</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="descripcion">Descripción General</label>
                        <div class="textarea-wrapper">
                            <span class="material-symbols-rounded">description</span>
                            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Descripción general del pedido..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="novedades">Novedades</label>
                        <div class="textarea-wrapper">
                            <span class="material-symbols-rounded">note</span>
                            <textarea id="novedades" name="novedades" class="form-control" rows="2" placeholder="Novedades o instrucciones especiales..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Productos del Pedido -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-rounded">shopping_bag</span>
                </div>
                <div>
                    <h2>Productos del Pedido</h2>
                    <p>Detalle de todos los artículos a fabricar</p>
                </div>
                <button type="button" class="btn-add-product" id="btnAgregarProducto">
                    <span class="material-symbols-rounded">add_circle</span>
                    <span>Agregar Producto</span>
                </button>
            </div>

            <div class="section-body">
                <div id="productosContainer" class="productos-list">
                    <!-- Los productos se agregarán aquí dinámicamente -->
                </div>

                <div class="productos-summary">
                    <div class="summary-stat">
                        <span class="summary-label">Total de Productos:</span>
                        <strong id="totalProductos" class="summary-value">0</strong>
                    </div>
                    <div class="summary-stat">
                        <span class="summary-label">Cantidad Total:</span>
                        <strong id="cantidadTotal" class="summary-value">0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: Botones de Acción -->
        <div class="form-actions">
            <div class="actions-left">
                <a href="{{ route('asesores.pedidos.index') }}" class="btn-action btn-cancel">
                    <span class="material-symbols-rounded">close</span>
                    <span>Cancelar</span>
                </a>
            </div>

            <div class="actions-right">
                <button type="button" onclick="guardarPedido(document.getElementById('formCrearPedido'), false)" class="btn-action btn-secondary">
                    <span class="material-symbols-rounded">save</span>
                    <span>Guardar Pedido</span>
                </button>
                <button type="button" onclick="guardarPedido(document.getElementById('formCrearPedido'), true)" class="btn-action btn-primary">
                    <span class="material-symbols-rounded">check_circle</span>
                    <span>Crear</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template para Producto -->
<template id="productoTemplate">
    <div class="producto-card">
        <div class="producto-header">
            <div class="producto-info">
                <span class="producto-icon">
                    <span class="material-symbols-rounded">checkroom</span>
                </span>
                <h4>Producto <span class="producto-numero">1</span></h4>
            </div>
            <button 
                type="button" 
                onclick="eliminarProducto(this)" 
                class="btn-delete-product"
                title="Eliminar producto"
            >
                <span class="material-symbols-rounded">delete_outline</span>
            </button>
        </div>

        <div class="producto-body">
            <!-- Fila 1: Tipo de Prenda y Color -->
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de Prenda <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">checkroom</span>
                        <input 
                            type="text" 
                            name="productos[][nombre_producto]" 
                            class="form-control nombre_producto" 
                            placeholder="Ej: Camisa Polo"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Color <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">palette</span>
                        <input 
                            type="text" 
                            name="productos[][color]" 
                            class="form-control color" 
                            placeholder="Ej: Azul Rey, Negro"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Fila 2: Talla y Cantidad -->
            <div class="form-row">
                <div class="form-group">
                    <label>Talla <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">straighten</span>
                        <input 
                            type="text" 
                            name="productos[][talla]" 
                            class="form-control talla"
                            placeholder="XS, S, M, L, XL, XXL"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Cantidad <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">numbers</span>
                        <input 
                            type="number" 
                            name="productos[][cantidad]" 
                            class="form-control producto-cantidad cantidad" 
                            min="1" 
                            value="1"
                            onchange="actualizarTotalProductos()"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Fila 3: Tela y Manga -->
            <div class="form-row">
                <div class="form-group">
                    <label>Tela</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">texture</span>
                        <input 
                            type="text" 
                            name="productos[][tela]" 
                            class="form-control tela"
                            placeholder="Ej: Lafayette, Drill, Piqué"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo de Manga</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">back_hand</span>
                        <select name="productos[][tipo_manga]" class="form-control tipo_manga">
                            <option value="">Seleccionar...</option>
                            <option value="Manga Corta">Manga Corta</option>
                            <option value="Manga Larga">Manga Larga</option>
                            <option value="Sin Manga">Sin Manga</option>
                            <option value="Manga 3/4">Manga 3/4</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 4: Género y Precio -->
            <div class="form-row">
                <div class="form-group">
                    <label>Género</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">wc</span>
                        <select name="productos[][genero]" class="form-control genero">
                            <option value="">Seleccionar...</option>
                            <option value="Dama">Dama</option>
                            <option value="Caballero">Caballero</option>
                            <option value="Unisex">Unisex</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Precio Unitario</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">attach_money</span>
                        <input 
                            type="number" 
                            name="productos[][precio_unitario]" 
                            class="form-control producto-precio precio_unitario" 
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        >
                    </div>
                </div>
            </div>

            <!-- Descripción Completa -->
            <div class="form-row">
                <div class="form-group full-width">
                    <label>Descripción Completa</label>
                    <div class="textarea-wrapper">
                        <span class="material-symbols-rounded">description</span>
                        <textarea 
                            name="productos[][descripcion]" 
                            class="form-control descripcion" 
                            rows="2"
                            placeholder="Detalles completos del producto, instrucciones especiales, etc..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Bordados/Logos -->
            <div class="form-row">
                <div class="form-group full-width">
                    <label>Bordados / Logos</label>
                    <div class="textarea-wrapper">
                        <span class="material-symbols-rounded">brush</span>
                        <textarea 
                            name="productos[][bordados]" 
                            class="form-control bordados" 
                            rows="2"
                            placeholder="Descripción de bordados, logos, diseños personalizados..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Referencia de Hilo y Modelo -->
            <div class="form-row">
                <div class="form-group">
                    <label>Ref. Hilo</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">thread</span>
                        <input 
                            type="text" 
                            name="productos[][ref_hilo]" 
                            class="form-control ref_hilo"
                            placeholder="Ej: REF HILO 293"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Modelo / Referencia Foto</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-rounded">image</span>
                        <input 
                            type="text" 
                            name="productos[][modelo_foto]" 
                            class="form-control modelo_foto"
                            placeholder="URL o referencia del modelo"
                        >
                    </div>
                </div>
            </div>

            <!-- Notas Adicionales -->
            <div class="form-row">
                <div class="form-group full-width">
                    <label>Notas Adicionales</label>
                    <div class="textarea-wrapper">
                        <span class="material-symbols-rounded">note</span>
                        <textarea 
                            name="productos[][notas]" 
                            class="form-control notas" 
                            rows="2"
                            placeholder="Observaciones adicionales sobre este producto..."
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
<style>
/* ========================================
   VARIABLES DE COLOR - PALETA ASESOR
   ======================================== */
:root {
    --color-primary: #663399;           /* Púrpura principal */
    --color-primary-light: #7a51a8;     /* Púrpura claro */
    --color-primary-dark: #5a2d8f;      /* Púrpura oscuro */
    
    --color-accent: #00A86B;            /* Verde éxito */
    --color-accent-light: #00C97D;      /* Verde claro */
    --color-accent-dark: #008a5a;       /* Verde oscuro */
    
    --color-danger: #ff5252;            /* Rojo error */
    --color-warning: #FF9800;           /* Naranja advertencia */
    --color-info: #0066cc;              /* Azul información */
    
    --color-gray-light: #f5f5f5;        /* Gris muy claro */
    --color-gray-lighter: #fafafa;      /* Gris aún más claro */
    --color-gray-border: #e0e0e0;       /* Gris borde */
    --color-gray-text: #666;            /* Gris texto */
    --color-gray-dark: #333;            /* Gris oscuro */
    
    --color-white: #ffffff;
    --color-black: #000000;
}

/* ========================================
   ESTRUCTURA BASE
   ======================================== */
.orden-create-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%);
    padding: 2rem 1rem;
}

.orden-create-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2.5rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(102, 51, 153, 0.15);
    position: relative;
    overflow: hidden;
}

.orden-create-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.btn-volver {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 0.95rem;
    white-space: nowrap;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}

.btn-volver:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateX(-3px);
}

.btn-volver span:first-child {
    font-size: 1.2rem;
}

.header-info {
    flex: 1;
    position: relative;
    z-index: 1;
    color: white;
}

.header-info h1 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem;
    font-size: 1.8rem;
    font-weight: 700;
}

.header-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

/* ========================================
   FORMULARIO
   ======================================== */
.orden-form {
    max-width: 1200px;
    margin: 0 auto;
}

/* SECCIONES DEL FORMULARIO */
.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-bottom: 2rem;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.form-section:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: linear-gradient(to right, var(--color-gray-lighter), white);
    border-bottom: 2px solid var(--color-gray-border);
    gap: 1.5rem;
    flex-wrap: wrap;
}

.section-header > div:nth-child(2) {
    flex: 1;
}

.section-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.section-header h2 {
    margin: 0 0 0.25rem;
    color: var(--color-gray-dark);
    font-size: 1.15rem;
    font-weight: 700;
}

.section-header p {
    margin: 0;
    color: var(--color-gray-text);
    font-size: 0.85rem;
}

.btn-add-product {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark));
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-add-product:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 169, 107, 0.3);
}

.btn-add-product span:first-child {
    font-size: 1.2rem;
}

.section-body {
    padding: 1.5rem;
}

/* GRID DE FORMULARIO */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 600;
    color: var(--color-gray-dark);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.required {
    color: var(--color-danger);
    font-size: 1rem;
}

.input-wrapper,
.textarea-wrapper {
    position: relative;
    display: flex;
    align-items: flex-start;
}

.input-wrapper > span:first-child,
.textarea-wrapper > span:first-child {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-gray-text);
    font-size: 1.2rem;
    pointer-events: none;
    z-index: 1;
    opacity: 0.7;
}

.textarea-wrapper > span:first-child {
    top: 15px;
    transform: none;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 2.75rem;
    border: 1px solid var(--color-gray-border);
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: white;
    color: var(--color-gray-dark);
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(102, 51, 153, 0.1);
}

.form-control::placeholder {
    color: #999;
}

textarea.form-control {
    padding: 0.875rem 1rem 0.875rem 2.75rem;
    resize: vertical;
}

/* ========================================
   PRODUCTOS
   ======================================== */
.productos-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.producto-card {
    background: var(--color-gray-lighter);
    border: 2px solid var(--color-gray-border);
    border-left: 5px solid var(--color-primary);
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.producto-card:hover {
    border-left-color: var(--color-accent);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.producto-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: white;
    border-bottom: 1px solid var(--color-gray-border);
    gap: 1rem;
}

.producto-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.producto-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.producto-header h4 {
    margin: 0;
    color: var(--color-gray-dark);
    font-size: 1rem;
    font-weight: 600;
}

.btn-delete-product {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255, 82, 82, 0.1);
    color: var(--color-danger);
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.btn-delete-product:hover {
    background: rgba(255, 82, 82, 0.2);
    color: var(--color-danger);
    transform: scale(1.05);
}

.producto-body {
    padding: 1.5rem;
}

.producto-body .form-row {
    margin-bottom: 1.25rem;
}

/* RESUMEN DE PRODUCTOS */
.productos-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, rgba(102, 51, 153, 0.05), rgba(0, 169, 107, 0.05));
    border-radius: 8px;
    border: 1px dashed var(--color-gray-border);
    margin-top: 1.5rem;
}

.summary-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 6px;
    border-left: 4px solid var(--color-primary);
}

.summary-label {
    color: var(--color-gray-text);
    font-size: 0.9rem;
    font-weight: 500;
}

.summary-value {
    color: var(--color-primary);
    font-size: 1.5rem;
    font-weight: 700;
}

/* ========================================
   ACCIONES / BOTONES
   ======================================== */
.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    border: 1px solid var(--color-gray-border);
    border-radius: 12px;
    padding: 1.5rem;
    gap: 1.5rem;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-top: 2rem;
}

.actions-left,
.actions-right {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.actions-right {
    justify-content: flex-end;
    margin-left: auto;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-action span:first-child {
    font-size: 1.1rem;
}

.btn-cancel {
    background: var(--color-gray-light);
    color: var(--color-gray-dark);
    border: 1px solid var(--color-gray-border);
}

.btn-cancel:hover {
    background: var(--color-gray-border);
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--color-gray-light);
    color: var(--color-gray-dark);
    border: 1px solid var(--color-gray-border);
}

.btn-secondary:hover {
    background: var(--color-gray-border);
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark));
    color: white;
    border: none;
    font-size: 1rem;
    padding: 0.875rem 2rem;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 169, 107, 0.3);
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 768px) {
    .orden-create-wrapper {
        padding: 1rem;
    }

    .orden-create-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
    }

    .header-info h1 {
        font-size: 1.4rem;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .btn-add-product {
        width: 100%;
        justify-content: center;
    }

    .form-actions {
        flex-direction: column;
    }

    .actions-left,
    .actions-right {
        width: 100%;
        justify-content: center;
    }

    .btn-action {
        flex: 1;
        justify-content: center;
    }

    .form-section {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 480px) {
    .orden-create-header {
        padding: 1rem;
    }

    .btn-volver {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }

    .header-info h1 {
        font-size: 1.2rem;
    }

    .section-header {
        padding: 1rem;
    }

    .section-body {
        padding: 1rem;
    }

    .form-control {
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        font-size: 0.9rem;
    }

    .btn-action {
        padding: 0.75rem 1.25rem;
        font-size: 0.85rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script>
let productoCount = 0;

/**
 * Agregar un nuevo producto al formulario
 */
function agregarProducto() {
    const container = document.getElementById('productosContainer');
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar índices
    const productoDiv = clone.querySelector('.producto-card');
    const inputs = clone.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[0]', `[${productoCount}]`));
        }
        
        // Agregar listener para actualizar totales
        if (input.classList.contains('cantidad')) {
            input.addEventListener('change', actualizarTotalProductos);
        }
    });

    // Actualizar número del producto
    clone.querySelector('.producto-numero').textContent = productoCount + 1;

    container.appendChild(clone);
    productoCount++;
    
    // Actualizar conteos
    actualizarTotalProductos();
}

/**
 * Eliminar un producto del formulario
 */
function eliminarProducto(button) {
    Swal.fire({
        title: '¿Eliminar Producto?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ff5252'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('.producto-card').remove();
            actualizarTotalProductos();
        }
    });
}

/**
 * Actualizar totales de productos
 */
function actualizarTotalProductos() {
    const productos = document.querySelectorAll('.producto-card');
    const cantidadInputs = document.querySelectorAll('.cantidad');
    
    let totalProductos = productos.length;
    let totalCantidad = 0;
    
    cantidadInputs.forEach(input => {
        totalCantidad += parseInt(input.value) || 0;
    });
    
    document.getElementById('totalProductos').textContent = totalProductos;
    document.getElementById('cantidadTotal').textContent = totalCantidad;
}

// Cargar la primera vista de productos al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    agregarProducto();
});
</script>
@endpush
