@extends('asesores.layout')

@include('components.modal-imagen')

@push('styles')
<style>
    .top-nav {
        display: none !important;
    }

    * {
        --primary: #1e40af;
        --secondary: #0ea5e9;
        --accent: #06b6d4;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-radius: 9px;
        box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
    }

    .page-header h1 {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 0.375rem 0;
    }

    .page-header p {
        font-size: 0.7125rem;
        opacity: 0.95;
        margin: 0;
    }

    .form-section {
        background: white;
        border-radius: 7.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        padding: 0.9rem;
        margin-bottom: 0.9rem;
        border-left: 3px solid var(--primary);
    }

    .form-section h2 {
        font-size: 0.975rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.125rem;
        display: flex;
        align-items: center;
        gap: 0.5625rem;
    }

    .form-section h2 span {
        background: var(--primary);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.675rem;
    }

    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-group label {
        display: block;
        font-size: 0.6375rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.375px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.5625rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 4.5px;
        font-size: 0.7125rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary);
        background-color: white;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-group input:disabled,
    .form-group input[readonly] {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        border-color: #e2e8f0;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(187.5px, 1fr));
        gap: 1.125rem;
    }

    .bg-white {
        background-color: white;
    }

    .rounded-lg {
        border-radius: 10px;
    }

    .shadow {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .p-6 {
        padding: 2rem;
    }

    .mb-4 {
        margin-bottom: 1rem;
    }

    .mb-8 {
        margin-bottom: 2rem;
    }

    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    .grid {
        display: grid;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gap-4 {
        gap: 1rem;
    }

    .flex {
        display: flex;
    }

    .items-center {
        align-items: center;
    }

    .justify-between {
        justify-content: space-between;
    }

    .flex-1 {
        flex: 1 1 0%;
    }

    .btn {
        padding: 0.5625rem 1.125rem;
        border-radius: 4.5px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        text-align: center;
        font-size: 0.75rem;
    }

    .btn-primary {
        background-color: var(--primary);
        color: white;
        box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
        box-shadow: 0 4px 8px rgba(30, 64, 175, 0.3);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background-color: #64748b;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #475569;
    }

    .btn-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .btn-actions .btn {
        flex: 1;
        padding: 0.75rem;
        font-size: 0.75rem;
    }

    .text-3xl {
        font-size: 1.875rem;
    }

    .font-bold {
        font-weight: 700;
    }

    .text-gray-900 {
        color: #111827;
    }

    .text-gray-600 {
        color: #4b5563;
    }

    .text-xl {
        font-size: 1.25rem;
    }

    .text-blue-600 {
        color: #2563eb;
    }

    .text-red-500 {
        color: #ef4444;
    }

    .text-gray-700 {
        color: #374151;
    }

    .text-gray-500 {
        color: #6b7280;
    }

    .text-center {
        text-align: center;
    }

    .py-8 {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .px-4 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .mt-2 {
        margin-top: 0.5rem;
    }

    .prenda-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .prenda-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: var(--primary);
    }

    .prenda-titulo {
        font-size: 0.825rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.375rem;
    }

    .prenda-descripcion {
        color: #64748b;
        font-size: 0.6375rem;
        margin-bottom: 0.75rem;
    }

    .tallas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
        align-items: center;
    }

    .talla-group {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        transition: all 0.2s;
    }

    .talla-group:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 6px rgba(30, 64, 175, 0.1);
    }

    .talla-group.talla-eliminada {
        opacity: 0.4;
        background: #f1f5f9;
    }

    .talla-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .talla-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        min-width: 30px;
        text-align: center;
    }

    .btn-eliminar-talla {
        background: #ef4444 !important;
        color: white !important;
        border: none !important;
        border-radius: 2px !important;
        width: 16px !important;
        height: 16px !important;
        min-width: 16px !important;
        padding: 0 !important;
        cursor: pointer !important;
        font-size: 10px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s !important;
        flex-shrink: 0;
    }

    .btn-eliminar-talla:hover {
        background: #dc2626 !important;
    }

    .talla-input {
        padding: 0.35rem 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 3px;
        font-size: 0.7rem;
        background-color: white;
        text-align: center;
        font-weight: 600;
        transition: all 0.2s;
        width: 50px;
    }

    .talla-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.1);
    }

    .talla-input:disabled {
        background-color: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .empty-state {
        text-align: center;
        padding: 2.25rem;
        color: #64748b;
    }

    .empty-state p {
        margin: 0;
        font-size: 0.7125rem;
    }

    .btn-agregar-talla {
        background: #10b981 !important;
        color: white !important;
        border: none !important;
        border-radius: 4px !important;
        padding: 0.5rem 1rem !important;
        cursor: pointer !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        transition: all 0.2s !important;
        margin-top: 0.75rem !important;
    }

    .btn-agregar-talla:hover {
        background: #059669 !important;
    }

    .tallas-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .input-nueva-talla {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="page-header">
        <h1>📋 Crear Pedido de Producción</h1>
        <p>Selecciona una cotización y agrega las cantidades por talla</p>
    </div>

    <form id="formCrearPedido" class="space-y-6">
        @csrf

        <!-- PASO 1: Seleccionar Cotización -->
        <div class="form-section">
            <h2>
                <span>1</span> Seleccionar Cotización
            </h2>

            <div class="form-group">
                <label for="cotizacion_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Cotización <span class="text-red-500">*</span>
                </label>
                <select id="cotizacion_id" name="cotizacion_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <option value="">-- Seleccionar cotización --</option>
                    @foreach($cotizaciones as $cot)
                        @php
                            $formaPago = '';
                            $cliente = $cot->cliente ?? '';
                            $asesora = $cot->asesora ?? '';
                            $numeroCotizacion = $cot->numero_cotizacion ?? '';
                            
                            if (is_array($cot->especificaciones)) {
                                $formaPago = $cot->especificaciones['forma_pago'] ?? '';
                            }
                            
                            // Asegurar que todos sean strings
                            $formaPago = is_string($formaPago) ? $formaPago : '';
                            $cliente = is_string($cliente) ? $cliente : '';
                            $asesora = is_string($asesora) ? $asesora : '';
                            $numeroCotizacion = is_string($numeroCotizacion) ? $numeroCotizacion : '';
                        @endphp
                        <option value="{{ $cot->id }}" data-cliente="{{ $cliente }}" data-asesora="{{ $asesora }}" data-forma-pago="{{ $formaPago }}" data-numero-cotizacion="{{ $numeroCotizacion }}">
                            {{ $numeroCotizacion ?: '#' . $cot->id }} - {{ $cliente }} ({{ $cot->prendasCotizaciones->count() }} prendas)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- PASO 2: Información del Pedido -->
        <div class="form-section">
            <h2>
                <span>2</span> Información del Pedido
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="numero_cotizacion" class="block text-sm font-medium text-gray-700 mb-2">Número de Cotización</label>
                    <input type="text" id="numero_cotizacion" name="numero_cotizacion" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-blue-50 font-bold text-blue-600" readonly>
                </div>

                <div class="form-group">
                    <label for="cliente" class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <input type="text" id="cliente" name="cliente" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>

                <div class="form-group">
                    <label for="asesora" class="block text-sm font-medium text-gray-700 mb-2">Asesora</label>
                    <input type="text" id="asesora" name="asesora" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>

                <div class="form-group">
                    <label for="forma_de_pago" class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago</label>
                    <input type="text" id="forma_de_pago" name="forma_de_pago" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>

                <div class="form-group">
                    <label for="numero_pedido" class="block text-sm font-medium text-gray-700 mb-2">Número de Pedido</label>
                    <input type="text" id="numero_pedido" name="numero_pedido" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>
            </div>
        </div>

        <!-- PASO 3: Prendas y Cantidades por Talla -->
        <div class="form-section">
            <h2>
                <span>3</span> Prendas y Cantidades por Talla
            </h2>

            <div id="prendas-container" class="space-y-6">
                <!-- Las prendas se cargarán aquí dinámicamente -->
                <div class="empty-state">
                    <p>Selecciona una cotización para ver las prendas</p>
                </div>
            </div>
        </div>

        <!-- PASO 4: Botones de Acción -->
        <div class="btn-actions">
            <button type="submit" class="btn btn-primary">
                ✓ Crear Pedido de Producción
            </button>
            <a href="{{ route('asesores.cotizaciones.index') }}" class="btn btn-secondary">
                ✕ Cancelar
            </a>
        </div>
    </form>
</div>

<style>
    .talla-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 14px;
    }

    .talla-input:focus {
        outline: none;
        ring: 2px;
        ring-color: #3b82f6;
        border-color: transparent;
    }

    .prenda-card {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        background-color: #f9fafb;
    }

    .prenda-card:hover {
        border-color: #3b82f6;
        background-color: #f0f9ff;
    }

    .prenda-titulo {
        font-size: 16px;
        font-weight: bold;
        color: #1f2937;
        margin-bottom: 12px;
    }

    .prenda-descripcion {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 12px;
    }

    .tallas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 12px;
    }

    .talla-group {
        display: flex;
        flex-direction: column;
    }

    .talla-label {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cotizacionSelect = document.getElementById('cotizacion_id');
    const prendasContainer = document.getElementById('prendas-container');
    const clienteInput = document.getElementById('cliente');
    const asesoraInput = document.getElementById('asesora');
    const formaPagoInput = document.getElementById('forma_de_pago');
    const numeroPedidoInput = document.getElementById('numero_pedido');
    const formCrearPedido = document.getElementById('formCrearPedido');

    // Cargar próximo número de pedido
    fetch('{{ route("asesores.next-pedido") }}')
        .then(response => response.json())
        .then(data => {
            numeroPedidoInput.value = data.siguiente_pedido;
        });

    // Cuando se selecciona una cotización
    cotizacionSelect.addEventListener('change', function() {
        const cotizacionId = this.value;
        
        if (!cotizacionId) {
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Selecciona una cotización para ver las prendas</p>';
            document.getElementById('numero_cotizacion').value = '';
            clienteInput.value = '';
            asesoraInput.value = '';
            formaPagoInput.value = '';
            return;
        }

        // Obtener datos de la opción seleccionada
        const option = this.options[this.selectedIndex];
        document.getElementById('numero_cotizacion').value = option.dataset.numeroCotizacion || 'Por asignar';
        clienteInput.value = option.dataset.cliente;
        asesoraInput.value = option.dataset.asesora;
        formaPagoInput.value = option.dataset.formaPago;

        // Cargar prendas de la cotización
        fetch(`/asesores/cotizaciones/${cotizacionId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                cargarPrendas(data.prendas);
            })
            .catch(error => {
                console.error('Error:', error);
                prendasContainer.innerHTML = '<p class="text-red-500">Error al cargar las prendas: ' + error.message + '</p>';
            });
    });

    function cargarPrendas(prendas) {
        if (!prendas || prendas.length === 0) {
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        let html = '';

        prendas.forEach((prenda, index) => {
            const tallas = prenda.tallas || [];
            const imagen = prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos[0] : null;
            
            html += `
                <div class="prenda-card">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div class="prenda-titulo">
                                ${prenda.nombre_producto}
                            </div>
                            ${prenda.descripcion ? `<div class="prenda-descripcion">${prenda.descripcion}</div>` : ''}
                        </div>
                        ${imagen ? `
                            <div style="flex-shrink: 0;">
                                <img src="${imagen}" alt="${prenda.nombre_producto}" onclick="abrirModalImagen('${imagen}', '${prenda.nombre_producto}')" style="
                                    width: 80px;
                                    height: 80px;
                                    object-fit: cover;
                                    border-radius: 4px;
                                    border: 1px solid #e2e8f0;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                " onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='none'">
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="tallas-grid">
            `;

            tallas.forEach((talla, tallaIndex) => {
                html += `
                    <div class="talla-group" data-talla="${talla}" data-prenda="${index}">
                        <div class="talla-header">
                            <label class="talla-label">${talla}</label>
                            <button type="button" class="btn-eliminar-talla" onclick="eliminarTalla(this)" title="Eliminar talla">
                                ✕
                            </button>
                        </div>
                        <input type="number" 
                               name="cantidades[${index}][${talla}]" 
                               class="talla-input" 
                               min="0" 
                               value="0" 
                               placeholder="0">
                    </div>
                `;
            });

            html += `
                    </div>
                    <div class="tallas-actions">
                        <input type="text" class="input-nueva-talla" placeholder="Nueva talla (ej: XS, 3XL)" data-prenda="${index}">
                        <button type="button" class="btn-agregar-talla" onclick="agregarTalla(this)" title="Agregar talla">
                            + Agregar
                        </button>
                    </div>
                </div>
            `;
        });

        prendasContainer.innerHTML = html;
    }

    // Enviar formulario
    formCrearPedido.addEventListener('submit', function(e) {
        e.preventDefault();

        const cotizacionId = document.getElementById('cotizacion_id').value;
        
        if (!cotizacionId) {
            alert('Por favor selecciona una cotización');
            return;
        }

        // Recopilar datos
        const formData = new FormData(this);
        formData.append('cotizacion_id', cotizacionId);

        // Enviar al servidor
        fetch('{{ route("asesores.pedidos-produccion.crear-desde-cotizacion", ":id") }}'.replace(':id', cotizacionId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Pedido creado exitosamente');
                window.location.href = `{{ route('asesores.pedidos-produccion.show', ':id') }}`.replace(':id', data.pedido_id);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear el pedido');
        });
    });
});

function eliminarTalla(btn) {
    const tallaGroup = btn.closest('.talla-group');
    const talla = tallaGroup.getAttribute('data-talla');
    
    if (confirm(`¿Eliminar la talla ${talla}?`)) {
        tallaGroup.style.opacity = '0.5';
        tallaGroup.style.pointerEvents = 'none';
        
        // Marcar como eliminada
        const input = tallaGroup.querySelector('.talla-input');
        input.disabled = true;
        input.value = '';
        
        // Agregar clase para identificarla como eliminada
        tallaGroup.classList.add('talla-eliminada');
        
        // Cambiar estilo del botón
        btn.textContent = '✓';
        btn.style.background = '#10b981';
        btn.disabled = true;
    }
}

function agregarTalla(btn) {
    const input = btn.previousElementSibling;
    const nuevaTalla = input.value.trim().toUpperCase();
    const prendasIndex = input.getAttribute('data-prenda');
    
    if (!nuevaTalla) {
        alert('Por favor ingresa el nombre de la talla');
        return;
    }
    
    // Crear nuevo elemento de talla
    const tallaGroup = document.createElement('div');
    tallaGroup.className = 'talla-group';
    tallaGroup.setAttribute('data-talla', nuevaTalla);
    tallaGroup.setAttribute('data-prenda', prendasIndex);
    
    tallaGroup.innerHTML = `
        <div class="talla-header">
            <label class="talla-label">${nuevaTalla}</label>
            <button type="button" class="btn-eliminar-talla" onclick="eliminarTalla(this)" title="Eliminar talla">
                ✕
            </button>
        </div>
        <input type="number" 
               name="cantidades[${prendasIndex}][${nuevaTalla}]" 
               class="talla-input" 
               min="0" 
               value="0" 
               placeholder="0">
    `;
    
    // Insertar antes de las acciones
    const tallasGrid = input.closest('.tallas-actions').previousElementSibling;
    tallasGrid.appendChild(tallaGroup);
    
    // Limpiar input
    input.value = '';
    input.focus();
}

// Las funciones abrirModalImagen() y cerrarModalImagen() están en el componente modal-imagen.blade.php
</script>
@endsection
