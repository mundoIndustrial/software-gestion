@extends('asesores.layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold">Mis Cotizaciones de Bordado</h1>
        <a href="{{ route('asesores.cotizaciones-bordado.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            + Nueva Cotización
        </a>
    </div>

    @if ($cotizaciones->count() > 0)
    <div class="grid gap-4">
        @foreach ($cotizaciones as $cotizacion)
        <div class="bg-white rounded-lg shadow p-6 border-l-4" style="border-left-color: {{ $cotizacion->estado === 'borrador' ? '#3b82f6' : '#10b981' }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Número de Cotización</p>
                    <p class="font-semibold">{{ $cotizacion->numero_cotizacion }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Cliente</p>
                    <p class="font-semibold">{{ $cotizacion->cliente }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Estado</p>
                    <span class="inline-block px-2 py-1 rounded text-white text-sm" style="background-color: {{ $cotizacion->estado === 'borrador' ? '#3b82f6' : '#10b981' }}">
                        {{ ucfirst($cotizacion->estado) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Creada</p>
                    <p class="font-semibold">{{ $cotizacion->created_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t flex gap-3">
                <a href="{{ route('asesores.cotizaciones-bordado.edit', $cotizacion->id) }}" 
                   class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">
                    Editar
                </a>
                
                @if ($cotizacion->estado === 'borrador')
                <button onclick="confirmarEnvio({{ $cotizacion->id }})" 
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                    Enviar
                </button>
                <button onclick="confirmarEliminar({{ $cotizacion->id }})" 
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                    Eliminar
                </button>
                @endif

                <a href="{{ route('pedidos-produccion.show', $cotizacion->pedidosProduccion()->first()->id ?? '#') }}" 
                   class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm"
                   @if (!$cotizacion->pedidosProduccion()->exists()) disabled style="pointer-events: none; opacity: 0.5;" @endif>
                    Ver Pedido
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-gray-50 rounded-lg p-8 text-center">
        <p class="text-gray-600 mb-4">No hay cotizaciones de bordado creadas</p>
        <a href="{{ route('asesores.cotizaciones-bordado.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Crear Nueva Cotización
        </a>
    </div>
    @endif
</div>

<script>
async function confirmarEnvio(cotizacionId) {
    if (!confirm('¿Enviar esta cotización? Se creará un pedido de producción.')) {
        return;
    }

    try {
        const response = await fetch(`/cotizaciones/bordado/${cotizacionId}/enviar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('Cotización enviada. Pedido: #' + result.pedido_id);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function confirmarEliminar(cotizacionId) {
    if (!confirm('¿Eliminar esta cotización? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await fetch(`/cotizaciones/bordado/${cotizacionId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('Cotización eliminada');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
@endsection
