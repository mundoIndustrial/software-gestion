@extends('bordado.layout')

@section('title', 'Cartera de Pedidos - Bordado')
@section('page-title', 'Cartera de Pedidos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bordado/index.css') }}">
@endpush

@section('content')
<div class="bordado-container">

    <!-- Tabla de Pedidos -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <!-- Header con información -->
            <div class="table-header-info">
                <h2>Pedidos Asignados</h2>
                <div class="header-stats">
                    <div class="stat">
                        <span class="stat-label">Total de Pedidos:</span>
                        <span class="stat-value">{{ $ordenes->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabla scrollable -->
            <div class="table-scroll-container">
                <div class="table-head">
                    <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                        @php
                            $columns = [
                                ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 180px', 'justify' => 'flex-start'],
                                ['key' => 'numero', 'label' => 'Número Pedido', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha de Creación', 'flex' => '0 0 160px', 'justify' => 'center'],
                                ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'forma_pago', 'label' => 'Forma de Pago', 'flex' => '0 0 140px', 'justify' => 'center'],
                                ['key' => 'fecha_estimada', 'label' => 'Entrega Estimada', 'flex' => '0 0 160px', 'justify' => 'center'],
                            ];
                        @endphp
                        @foreach($columns as $column)
                            <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                                <span class="header-text">{{ $column['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Cuerpo de la tabla -->
                <div class="modern-table">
                    <div class="table-body">
                        @if($ordenes->count() > 0)
                            @foreach($ordenes as $orden)
                                <div class="table-row" data-orden-id="{{ $orden->id }}" data-numero="{{ $orden->numero_pedido }}">
                                    
                                    <!-- Acciones -->
                                    <div class="table-cell acciones-column" style="flex: 0 0 180px; justify-content: center; display: flex; gap: 0.5rem;">
                                        <!-- Botón Acciones -->
                                        <button class="btn-action btn-acciones" title="Opciones" onclick="toggleAcciones(event, {{ $orden->id }})">
                                            <span class="material-symbols-rounded">more_vert</span>
                                            <span>Acciones</span>
                                        </button>
                                        
                                        <!-- Menú desplegable de acciones -->
                                        <div class="action-menu" id="menu-{{ $orden->id }}" style="display: none;">
                                            <button class="action-menu-item" onclick="verDetalles({{ $orden->id }})">
                                                <span class="material-symbols-rounded">description</span>
                                                <span>Ver Detalles</span>
                                            </button>
                                            <button class="action-menu-item" onclick="verSeguimiento({{ $orden->id }})">
                                                <span class="material-symbols-rounded">local_shipping</span>
                                                <span>Seguimiento</span>
                                            </button>
                                        </div>

                                        <!-- Botón Ver -->
                                        <button class="btn-action btn-ver" title="Ver pedido" onclick="verPedido({{ $orden->id }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            <span>Ver</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Número de Pedido -->
                                    <div class="table-cell" style="flex: 0 0 140px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span style="font-weight: 600; color: #1e5ba8;">#{{ $orden->numero_pedido }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Cliente -->
                                    <div class="table-cell" style="flex: 0 0 200px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->cliente }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha de Creación -->
                                    <div class="table-cell" style="flex: 0 0 160px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->fecha_de_creacion_de_orden->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Estado -->
                                    <div class="table-cell" style="flex: 0 0 150px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            @php
                                                $estadoColors = [
                                                    'No iniciado' => ['bg' => '#ecf0f1', 'color' => '#7f8c8d'],
                                                    'En Ejecución' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                    'Entregado' => ['bg' => '#d4edda', 'color' => '#155724'],
                                                    'Anulada' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                                    'PENDIENTE_SUPERVISOR' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                ];
                                                $colors = $estadoColors[$orden->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                            @endphp
                                            <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; white-space: nowrap;">
                                                {{ $orden->estado }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Asesora -->
                                    <div class="table-cell" style="flex: 0 0 150px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->asesora?->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Forma de Pago -->
                                    <div class="table-cell" style="flex: 0 0 140px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->forma_de_pago ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha Estimada de Entrega -->
                                    <div class="table-cell" style="flex: 0 0 160px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af; width: 100%;">
                                <p>No hay pedidos asignados en este momento</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Toggle del menú de acciones
     */
    function toggleAcciones(event, ordenId) {
        event.stopPropagation();
        const menu = document.getElementById(`menu-${ordenId}`);
        
        // Cerrar otros menús
        document.querySelectorAll('.action-menu').forEach(m => {
            if (m.id !== `menu-${ordenId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle el menú actual
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    /**
     * Ver detalles del pedido
     */
    function verDetalles(ordenId) {
        console.log('Ver detalles del pedido:', ordenId);
        // Implementar navegación a vista de detalles
    }

    /**
     * Ver seguimiento del pedido
     */
    function verSeguimiento(ordenId) {
        console.log('Ver seguimiento del pedido:', ordenId);
        // Implementar navegación a vista de seguimiento
    }

    /**
     * Ver el pedido completo
     */
    function verPedido(ordenId) {
        console.log('Ver pedido:', ordenId);
        // Implementar navegación a vista del pedido
    }

    /**
     * Cerrar menús al hacer clic fuera
     */
    document.addEventListener('click', function() {
        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    });
</script>
@endsection
