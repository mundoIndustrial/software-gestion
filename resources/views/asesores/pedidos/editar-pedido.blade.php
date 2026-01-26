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
    
    <!-- CRÍTICO: Definir datos del pedido ANTES de que se cargue crear-pedido-desde-cotizacion -->
    <script>
        console.log('🔥 [editar-pedido] Definiendo datos del pedido ANTES de todo...');
        
        window.modoEdicion = true;
        window.pedidoEdicionId = {{ $pedido->id }};
        window.pedidoEdicionData = @json($pedidoData);
        
        console.log('🔥 [editar-pedido] pedidoData recibido:', window.pedidoEdicionData);
        console.log('🔥 [editar-pedido] ¿Tiene procesos?', 'procesos' in window.pedidoEdicionData);
        
        // IMPORTANTE: Para que abrirEditarPrendaModal() funcione correctamente
        // Establecer datosEdicionPedido con la estructura que espera
        window.datosEdicionPedido = {
            numero_pedido: {{ $pedido->id }},
            id: {{ $pedido->id }},
            ...(window.pedidoEdicionData && window.pedidoEdicionData.pedido ? window.pedidoEdicionData.pedido : {})
        };
        
        // Establecer en body para que obtenerPedidoId() lo encuentre
        document.body.dataset.pedidoIdEdicion = {{ $pedido->id }};
        
        console.log('🔥 [editar-pedido] window.datosEdicionPedido =', window.datosEdicionPedido);
    </script>

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
    <!-- Script de edición - carga DESPUÉS de todos los demás módulos -->
    <script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion.js') }}"></script>
    
    <!-- Componente para editar prendas con procesos desde API -->
    <script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>
@endpush


