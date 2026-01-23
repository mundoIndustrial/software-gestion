@extends('bordado.layout')

@section('title', 'Bordado - Cotizaciones')
@section('page-title', 'Bordado')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/supervisor-pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/cartera.css') }}">
@endpush

@section('content')
<div class="supervisor-pedidos-container">
    <!-- Tabla de Cotizaciones - Diseño Cartera -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-scroll-container">
                <!-- TABLA CON ESTRUCTURA DE CARTERA -->
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 200px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 100px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 160px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modern-table">
                    <div class="table-body" id="tablaCotizacionesBody" style="min-height: 200px;">
                        <!-- Cotizaciones cargadas aquí -->
                    </div>
                </div>

                <!-- ESTADO VACÍO -->
                <div id="emptyState" class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #9ca3af; width: 100%; min-height: 300px;">
                    <span class="material-symbols-rounded" style="font-size: 3rem; opacity: 0.5;">description</span>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">No hay cotizaciones disponibles</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar tabla vacía
    document.addEventListener('DOMContentLoaded', function() {
        const tablaCotizacionesBody = document.getElementById('tablaCotizacionesBody');
        const emptyState = document.getElementById('emptyState');
        
        if (tablaCotizacionesBody && tablaCotizacionesBody.children.length === 0) {
            emptyState.style.display = 'flex';
        }
    });
</script>
@endsection
