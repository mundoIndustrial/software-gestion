@extends('layouts.asesores')

@include('components.modal-imagen')

@section('extra_styles')
    <link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
@endsection

@section('content')
    @php
        // Modo edición: usar el flujo de cotización como base
        $tipo = 'cotizacion';
        $esModoEdicion = true;
        $pedidoEdicion = $pedido ?? null;
    @endphp

    <!-- Loading Overlay de Página Completa -->
    <div id="page-loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Cargando editor de pedidos...</div>
        <div class="loading-subtext">Por favor espera mientras preparamos todo</div>
    </div>

    {{-- Flujo para editar pedidos existentes --}}
    @include('asesores.pedidos.crear-pedido-desde-cotizacion')

@endsection

@push('scripts')
    <!-- Pasar datos del pedido a JavaScript (ANTES de otros scripts) -->
    <script>
        window.modoEdicion = true;
        window.pedidoEdicionId = {{ $pedido->id }};
        window.pedidoEdicionData = @json($pedidoData);

        // Loguear las rutas exactas que se están pasando
        if (window.pedidoEdicionData && window.pedidoEdicionData.pedido && window.pedidoEdicionData.pedido.prendas) {
            window.pedidoEdicionData.pedido.prendas.forEach((prenda, idx) => {
                if (prenda.fotos && prenda.fotos.length > 0) {
                    prenda.fotos.forEach((foto, fIdx) => {
                    });
                }
                
                if (prenda.fotos_tela && prenda.fotos_tela.length > 0) {
                    prenda.fotos_tela.forEach((foto, fIdx) => {
                    });
                }
            });
        }
        
        // Loguear las rutas de las imágenes de prendas
        if (window.pedidoEdicionData && window.pedidoEdicionData.pedido && window.pedidoEdicionData.pedido.prendas) {
            window.pedidoEdicionData.pedido.prendas.forEach((prenda, idx) => {
                // Loguear fotos de prenda
                if (prenda.fotos && prenda.fotos.length > 0) {
                    prenda.fotos.forEach((foto, fIdx) => {
                    });
                }
                
                // Loguear fotos de tela
                if (prenda.fotos_tela && prenda.fotos_tela.length > 0) {
                    prenda.fotos_tela.forEach((foto, fIdx) => {
                    });
                }
            });
        }
    </script>
    
    <!-- Script de edición - carga DESPUÉS de todos los demás módulos -->
    <script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion.js') }}"></script>
@endpush


