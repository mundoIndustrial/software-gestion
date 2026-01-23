@extends('bordado.layout')

@section('title', 'Cotizaciones - Bordado')
@section('page-title', 'Cotizaciones de Bordado')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bordado/cotizaciones.css') }}">
@endpush

@section('content')
<div class="bordado-container">

    <!-- Tabla de Cotizaciones -->
    <div class="table-container">
        <div class="modern-table-wrapper">
            <!-- Header con información -->
            <div class="table-header-info">
                <h2>Cotizaciones de Bordado</h2>
                <div class="header-stats">
                    <div class="stat">
                        <span class="stat-label">Total de Cotizaciones:</span>
                        <span class="stat-value">{{ $cotizaciones->count() }}</span>
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
                                ['key' => 'id', 'label' => 'ID', 'flex' => '0 0 100px', 'justify' => 'center'],
                                ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                                ['key' => 'fecha', 'label' => 'Fecha de Creación', 'flex' => '0 0 160px', 'justify' => 'center'],
                                ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'usuario', 'label' => 'Usuario', 'flex' => '0 0 150px', 'justify' => 'center'],
                                ['key' => 'tipo', 'label' => 'Tipo', 'flex' => '0 0 120px', 'justify' => 'center'],
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
                        @if($cotizaciones->count() > 0)
                            @foreach($cotizaciones as $cotizacion)
                                <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}">
                                    
                                    <!-- Acciones -->
                                    <div class="table-cell acciones-column" style="flex: 0 0 180px; justify-content: center; display: flex; gap: 0.5rem;">
                                        <!-- Botón Acciones -->
                                        <button class="btn-action btn-acciones" title="Opciones" onclick="toggleAcciones(event, {{ $cotizacion->id }})">
                                            <span class="material-symbols-rounded">more_vert</span>
                                            <span>Acciones</span>
                                        </button>
                                        
                                        <!-- Menú desplegable de acciones -->
                                        <div class="action-menu" id="menu-{{ $cotizacion->id }}" style="display: none;">
                                            <button class="action-menu-item" onclick="verDetalles({{ $cotizacion->id }})">
                                                <span class="material-symbols-rounded">description</span>
                                                <span>Ver Detalles</span>
                                            </button>
                                            <button class="action-menu-item" onclick="descargarPDF({{ $cotizacion->id }})">
                                                <span class="material-symbols-rounded">download</span>
                                                <span>Descargar PDF</span>
                                            </button>
                                        </div>

                                        <!-- Botón Ver -->
                                        <button class="btn-action btn-ver" title="Ver cotización" onclick="verCotizacion({{ $cotizacion->id }})">
                                            <span class="material-symbols-rounded">visibility</span>
                                            <span>Ver</span>
                                        </button>
                                    </div>
                                    
                                    <!-- ID -->
                                    <div class="table-cell" style="flex: 0 0 100px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span style="font-weight: 600; color: #1e5ba8;">#{{ $cotizacion->id }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Cliente -->
                                    <div class="table-cell" style="flex: 0 0 200px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $cotizacion->cliente ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fecha de Creación -->
                                    <div class="table-cell" style="flex: 0 0 160px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $cotizacion->created_at->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Estado -->
                                    <div class="table-cell" style="flex: 0 0 150px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            @php
                                                $estadoColors = [
                                                    'BORRADOR' => ['bg' => '#ecf0f1', 'color' => '#7f8c8d'],
                                                    'ENVIADA' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                    'APROBADA' => ['bg' => '#d4edda', 'color' => '#155724'],
                                                    'RECHAZADA' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                                ];
                                                $colors = $estadoColors[$cotizacion->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                            @endphp
                                            <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; white-space: nowrap;">
                                                {{ $cotizacion->estado }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Usuario -->
                                    <div class="table-cell" style="flex: 0 0 150px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span>{{ $cotizacion->usuario?->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Tipo -->
                                    <div class="table-cell" style="flex: 0 0 120px;">
                                        <div class="cell-content" style="justify-content: center;">
                                            <span style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                                                {{ $cotizacion->tipo ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding: 40px; text-align: center; color: #9ca3af; width: 100%;">
                                <p>No hay cotizaciones disponibles en este momento</p>
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
    function toggleAcciones(event, cotizacionId) {
        event.stopPropagation();
        const menu = document.getElementById(`menu-${cotizacionId}`);
        
        // Cerrar otros menús
        document.querySelectorAll('.action-menu').forEach(m => {
            if (m.id !== `menu-${cotizacionId}`) {
                m.style.display = 'none';
            }
        });
        
        // Toggle el menú actual
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    /**
     * Ver detalles de la cotización
     */
    function verDetalles(cotizacionId) {
        console.log('Ver detalles de cotización:', cotizacionId);
        // Implementar navegación a vista de detalles
    }

    /**
     * Descargar PDF de la cotización
     */
    function descargarPDF(cotizacionId) {
        console.log('Descargar PDF de cotización:', cotizacionId);
        // Implementar descarga de PDF
    }

    /**
     * Ver la cotización completa
     */
    function verCotizacion(cotizacionId) {
        console.log('Ver cotización:', cotizacionId);
        // Implementar navegación a vista de la cotización
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
