@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly-refactored.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-utilities.css') }}">
@endpush

@section('content')

<div class="friendly-form-fullscreen">
    <!-- TÍTULO PRINCIPAL -->
    <div class="page-header">
        <h1>COTIZACIONES</h1>
        <p>Crea una nueva cotización para tu cliente</p>
    </div>

    <x-stepper />

    <form id="formCrearPedidoFriendly" class="friendly-form">
        @csrf

        <!-- Campo oculto para cotizacion_id (si es actualizaci�n) -->
        @if(isset($cotizacion))
            <input type="hidden" name="cotizacion_id" value="{{ $cotizacion->id }}">
        @endif

        <x-paso-uno />
        <x-paso-dos />
        <x-paso-tres />
        <x-paso-cuatro />
    </form>
</div>

<!-- TEMPLATE PARA PRODUCTO -->
<x-template-producto />

<x-modal-especificaciones />

@push('scripts')
<!-- 1. Módulos base del sistema de cotizaciones -->
<script src="{{ asset('js/asesores/cotizaciones/rutas.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/pastillas.js') }}"></script>

<!-- 2. Módulos de gestión de datos -->
<script src="{{ asset('js/asesores/cotizaciones/tallas.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cotizaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/productos.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagenes.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/especificaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>

<!-- 3. Módulos de variantes y búsqueda -->
<script src="{{ asset('js/asesores/variantes-prendas.js') }}"></script>
<script src="{{ asset('js/asesores/color-tela-referencia.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/integracion-variantes-inline.js') }}"></script>

<!-- 4. Configuración global -->
<script>
    window.tipoCotizacionGlobal = 'PB'; // Prenda-Bordado
    window.routes = window.routes || {};
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';
</script>

<!-- 5. Carga de borrador (si aplica) -->
@if(isset($esEdicion) && $esEdicion && isset($cotizacion))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cotizacion = {!! json_encode($cotizacion) !!};
        if (typeof window.cargarBorradorCompleto === 'function') {
            window.cargarBorradorCompleto(cotizacion);
        }
    });
</script>
@endif

<!-- 6. Inicialización final -->
<script src="{{ asset('js/asesores/cotizaciones/init.js') }}"></script>
@endpush

@endsection

