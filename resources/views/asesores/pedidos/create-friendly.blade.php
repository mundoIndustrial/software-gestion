@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones')

@push('styles')
<style>
    /* Estilos específicos para cotizaciones */
    
    /* Loading Spinner */
    #loadingSpinner {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        backdrop-filter: blur(2px);
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e0e0e0;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .spinner-text {
        position: absolute;
        bottom: 40px;
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }
</style>

{{-- CSS específicos de crear cotización - lazy loaded por ruta --}}
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-utilities.css') }}?v={{ time() }}" media="print" onload="this.media='all'">
<noscript>
    <link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-tabs.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-utilities.css') }}?v={{ time() }}">
</noscript>
@endpush

@section('content')

<!-- Loading Spinner -->
<div id="loadingSpinner">
    <div style="text-align: center;">
        <div class="spinner"></div>
        <div class="spinner-text">Cargando cotización...</div>
    </div>
</div>

<div class="friendly-form-fullscreen">
    <!-- TÍTULO PRINCIPAL -->
    <div class="page-header">
        <h1>COTIZACIONES</h1>
        <p>Crea una nueva cotización para tu cliente</p>
    </div>

    <x-stepper />

    <form id="formCrearPedidoFriendly" class="friendly-form">
        @csrf

        <!-- Campo oculto para cotizacion_id (si es actualización) -->
        @if(isset($cotizacion))
            <input type="hidden" name="cotizacion_id" value="{{ $cotizacion->id }}">
        @endif

        <x-paso-uno />
        <x-paso-dos />
        {{-- <x-paso-cuatro-reflectivo /> --}}
        <x-paso-tres />
        <x-paso-cuatro />
    </form>
</div>

<!-- TEMPLATE PARA PRODUCTO -->
<x-template-producto />

<script>
    // Ocultar loading cuando los CSS lazy-loaded se hayan cargado
    document.addEventListener('DOMContentLoaded', function() {
        // Pequeño delay para asegurar que todo esté listo
        setTimeout(function() {
            const loadingSpinner = document.getElementById('loadingSpinner');
            if (loadingSpinner) {
                loadingSpinner.style.transition = 'opacity 0.3s ease-out';
                loadingSpinner.style.opacity = '0';
                setTimeout(() => {
                    loadingSpinner.style.display = 'none';
                }, 300);
            }
        }, 500);
    });
    
    // Asegurar que se oculte cuando la ventana esté completamente cargada
    window.addEventListener('load', function() {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner && loadingSpinner.style.display !== 'none') {
            loadingSpinner.style.transition = 'opacity 0.3s ease-out';
            loadingSpinner.style.opacity = '0';
            setTimeout(() => {
                loadingSpinner.style.display = 'none';
            }, 300);
        }
    });
</script>

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
<script src="{{ asset('js/asesores/cotizaciones/reflectivo.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/resumen-reflectivo.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagen-borrador.js') }}"></script>

<!-- 3. Módulos de variantes y búsqueda -->
<script src="{{ asset('js/asesores/variantes-prendas.js') }}"></script>
<script src="{{ asset('js/asesores/color-tela-referencia.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/integracion-variantes-inline.js') }}"></script>

<!-- 4. Configuración global -->
<script>
    window.tipoCotizacionGlobal = 'PL'; // Prenda-Logo
    window.routes = window.routes || {};
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';
</script>

<!-- 5. Carga de borrador (si aplica) -->
@if(isset($esEdicion) && $esEdicion && isset($cotizacion))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cotizacion = {!! json_encode($cotizacion->toArray()) !!};
        console.log('📂 Datos de cotización recibidos:', cotizacion);
        console.log('📂 Cotización ID:', cotizacion.id);
        
        // Guardar ID en variable global para actualizar después
        window.cotizacionIdActual = cotizacion.id;
        
        // Esperar a que los módulos estén cargados
        setTimeout(() => {
            if (typeof cargarBorrador === 'function') {
                console.log('✅ Llamando a cargarBorrador()');
                cargarBorrador(cotizacion);
            } else {
                console.error('❌ Función cargarBorrador no encontrada');
            }
        }, 1000);
    });
</script>
@endif

<!-- 6. Inicialización final -->
<script src="{{ asset('js/asesores/cotizaciones/init.js') }}"></script>
@endpush

@endsection

