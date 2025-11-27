@extends('asesores.layout')

@push('styles')
<style>
    * {
        --primary: #1e40af;
        --secondary: #0ea5e9;
        --accent: #06b6d4;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem;
    }

    .form-container {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
    }

    .form-header {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-header h1 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .status-badge.borrador {
        background: #dbeafe;
        color: #0c4a6e;
    }

    .status-badge.enviada {
        background: #dcfce7;
        color: #14532d;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
        margin: 2rem 0 1.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--secondary);
    }

    .section-title i {
        color: var(--secondary);
        font-size: 1.4rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .form-group input[type="text"],
    .form-group textarea,
    .form-group input[type="email"] {
        width: 100%;
        padding: 0.875rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.2s ease;
    }

    .form-group input[type="text"]:focus,
    .form-group textarea:focus,
    .form-group input[type="email"]:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.875rem 1.75rem;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        flex: 1;
        min-width: 160px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(30, 64, 175, 0.3);
    }

    .btn-success {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        text-decoration: none;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="form-container">
        <h1 class="text-3xl font-bold mb-6">Editar Cotización de Bordado</h1>

        <form id="cotizacionBordadoForm" class="bg-white rounded-lg shadow p-6">
            @csrf
            @method('PUT')

            <!-- Cliente -->
            <div class="mb-4">
                <label for="cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
                <input type="text" id="cliente" name="cliente" required value="{{ $cotizacion->cliente }}"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Asesora -->
            <div class="mb-4">
                <label for="asesora" class="block text-sm font-medium text-gray-700">Asesora</label>
                <input type="text" id="asesora" name="asesora" required value="{{ $cotizacion->asesora }}"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Estado Actual -->
            <div class="mb-4 p-3 bg-blue-50 rounded-md">
                <p class="text-sm text-gray-700">
                    <strong>Estado:</strong> 
                    <span class="inline-block px-2 py-1 rounded text-white" style="background-color: {{ $cotizacion->estado === 'borrador' ? '#3b82f6' : '#10b981' }}">
                        {{ ucfirst($cotizacion->estado) }}
                    </span>
                </p>
                <p class="text-sm text-gray-700 mt-1">
                    <strong>Número de Cotización:</strong> {{ $cotizacion->numero_cotizacion }}
                </p>
            </div>

            <!-- Observaciones Técnicas (Paso 3) -->
            <div class="mb-4">
                <label for="observaciones_tecnicas" class="block text-sm font-medium text-gray-700">Observaciones Técnicas de Bordado</label>
                <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" rows="4"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Detallar especificaciones técnicas del bordado...">{{ $cotizacion->observaciones_tecnicas }}</textarea>
            </div>

            <!-- Especificaciones (Paso 4 - Presupuesto) -->
            <div class="mb-4">
                <label for="especificaciones" class="block text-sm font-medium text-gray-700">Especificaciones / Presupuesto</label>
                <textarea id="especificaciones" name="especificaciones" rows="4"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingresar presupuesto y especificaciones...">{{ json_encode($cotizacion->especificaciones, JSON_PRETTY_PRINT) }}</textarea>
            </div>

            <!-- Observaciones Generales -->
            <div class="mb-6">
                <label for="observaciones_generales" class="block text-sm font-medium text-gray-700">Observaciones Generales</label>
                <textarea id="observaciones_generales" name="observaciones_generales" rows="3"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Notas adicionales...">{{ json_encode($cotizacion->observaciones_generales, JSON_PRETTY_PRINT) }}</textarea>
            </div>

            <!-- Botones -->
            <div class="flex gap-3">
                <button type="button" id="btnGuardar" class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                    Guardar Cambios
                </button>
                
                @if ($cotizacion->estado === 'borrador')
                <button type="button" id="btnEnviar" class="flex-1 bg-green-600 text-white py-2 rounded-md hover:bg-green-700">
                    Enviar Cotización
                </button>
                @endif
                
                <a href="{{ route('asesores.cotizaciones-bordado.lista') }}" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-400 text-center">
                    Volver
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const cotizacionId = {{ $cotizacion->id }};

document.getElementById('btnGuardar').addEventListener('click', async function() {
    const formData = new FormData(document.getElementById('cotizacionBordadoForm'));
    const data = {
        cliente: formData.get('cliente'),
        asesora: formData.get('asesora'),
        observaciones_tecnicas: formData.get('observaciones_tecnicas'),
        especificaciones: formData.get('especificaciones'),
        observaciones_generales: formData.get('observaciones_generales'),
    };

    try {
        const response = await fetch(`/cotizaciones/bordado/${cotizacionId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Cotización actualizada');
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});

document.getElementById('btnEnviar').addEventListener('click', async function() {
    if (!confirm('¿Enviar esta cotización? Se creará un pedido de producción.')) {
        return;
    }

    try {
        const response = await fetch(`/cotizaciones/bordado/${cotizacionId}/enviar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('Cotización enviada. Pedido creado: #' + result.pedido_id);
            window.location.href = '{{ route("cotizaciones.bordado.lista") }}';
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endsection
