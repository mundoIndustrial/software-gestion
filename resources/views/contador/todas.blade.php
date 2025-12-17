@extends('layouts.contador')

@section('content')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/tabla-cotizaciones.css') }}?v={{ time() }}">
@endpush

<!-- Sección de Todas las Cotizaciones -->
<section id="todas-section" class="section-content active" style="display: block;">
    <div class="table-container">
        <div class="modern-table-wrapper">
            <div class="table-head" id="tableHead">
                <div style="display: flex; align-items: center; width: 100%; gap: 12px; padding: 14px 12px;">
                    @php
                        $columns = [
                            ['key' => 'acciones', 'label' => 'Acciones', 'flex' => '0 0 100px', 'justify' => 'flex-start'],
                            ['key' => 'estado', 'label' => 'Estado', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
                            ['key' => 'novedades', 'label' => 'Novedades', 'flex' => '0 0 180px', 'justify' => 'center'],
                        ];
                    @endphp
                    
                    @foreach($columns as $column)
                        <div class="table-header-cell{{ $column['key'] === 'acciones' ? ' acciones-column' : '' }}" style="flex: {{ $column['flex'] }}; justify-content: {{ $column['justify'] }};">
                            <div class="th-wrapper" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                <span class="header-text">{{ $column['label'] }}</span>
                                @if($column['key'] !== 'acciones')
                                    <button type="button" class="btn-filter-column" data-filter-column="{{ $column['key'] }}" onclick="abrirFiltroColumna('{{ $column['key'] }}', obtenerValoresColumna('{{ $column['key'] }}'))" title="Filtrar {{ $column['label'] }}">
                                        <span class="material-symbols-rounded">filter_alt</span>
                                        <div class="filter-badge"></div>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="table-scroll-container">
                <div class="modern-table">
                    <div id="tablaCotizacionesBody" class="table-body">
                        @php
                            // Paginar manualmente: 25 por página
                            $perPage = 25;
                            $currentPage = request()->get('page', 1);
                            $total = $todasLasCotizaciones->count();
                            $totalPages = ceil($total / $perPage);
                            $offset = ($currentPage - 1) * $perPage;
                            $cotizacionesPaginadas = $todasLasCotizaciones->slice($offset, $perPage);
                        @endphp
                        
                        @forelse($cotizacionesPaginadas as $cotizacion)
                            <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}" data-numero="{{ $cotizacion->numero_cotizacion ?? 'N/A' }}" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}" data-estado="{{ $cotizacion->estado }}" data-novedades="{{ $cotizacion->novedades ?? '-' }}">
                                <!-- Acciones -->
                                <div class="table-cell acciones-column" style="flex: 0 0 100px; justify-content: center; position: relative;">
                                    <button class="action-view-btn" title="Ver opciones" data-cotizacion-id="{{ $cotizacion->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="action-menu" data-cotizacion-id="{{ $cotizacion->id }}">
                                        <a href="#" class="action-menu-item" data-action="cotizacion" onclick="openCotizacionModal({{ $cotizacion->id }}); return false;">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Ver Cotización</span>
                                        </a>
                                        <a href="#" class="action-menu-item" data-action="costos" onclick="abrirModalVisorCostos({{ $cotizacion->id }}, '{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}'); return false;">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>Ver Costos</span>
                                        </a>
                                        <a href="/contador/cotizacion/{{ $cotizacion->id }}/pdf?tipo=prenda" class="action-menu-item" data-action="pdf" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>Ver PDF</span>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Estado -->
                                <div class="table-cell" style="flex: 0 0 150px;" data-estado="{{ $cotizacion->estado }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        @php
                                            $estadoColors = [
                                                'ENVIADA_CONTADOR' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                                'EN_CORRECCION' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                                'APROBADA_CONTADOR' => ['bg' => '#d4edda', 'color' => '#155724'],
                                                'RECHAZADA' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                            ];
                                            $colors = $estadoColors[$cotizacion->estado] ?? ['bg' => '#e3f2fd', 'color' => '#1e40af'];
                                        @endphp
                                        <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            {{ str_replace('_', ' ', $cotizacion->estado) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;" data-numero="{{ $cotizacion->numero_cotizacion ?? 'N/A' }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">{{ $cotizacion->numero_cotizacion ?? 'Por asignar' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Fecha -->
                                <div class="table-cell" style="flex: 0 0 180px;" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y H:i') : '-' }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cliente -->
                                <div class="table-cell" style="flex: 0 0 200px;" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Asesora -->
                                <div class="table-cell" style="flex: 0 0 150px;" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span>{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '-') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Novedades -->
                                <div class="table-cell" style="flex: 0 0 180px;">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-size: 0.85rem;">{{ $cotizacion->novedades ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 40px; text-align: center; color: #9ca3af;">
                                <p>No hay cotizaciones disponibles</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="table-pagination" id="tablePagination">
                <div class="pagination-info">
                    <span id="paginationInfo">Mostrando 1-25 de {{ $total }} registros</span>
                </div>
                <div class="pagination-controls" id="paginationControls">
                    @if($totalPages > 1)
                        <button class="pagination-btn" data-page="1" {{ $currentPage == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $currentPage - 1 }}" {{ $currentPage == 1 ? 'disabled' : '' }}>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        
                        @php
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);
                        @endphp
                        
                        @for($i = $start; $i <= $end; $i++)
                            <button class="pagination-btn page-number {{ $i == $currentPage ? 'active' : '' }}" data-page="{{ $i }}">
                                {{ $i }}
                            </button>
                        @endfor
                        
                        <button class="pagination-btn" data-page="{{ $currentPage + 1 }}" {{ $currentPage == $totalPages ? 'disabled' : '' }}>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="pagination-btn" data-page="{{ $totalPages }}" {{ $currentPage == $totalPages ? 'disabled' : '' }}>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script de Cotización Modal -->
<script src="{{ asset('js/contador/cotizacion.js') }}"></script>

<!-- Script de Tabla de Cotizaciones -->
<script src="{{ asset('js/contador/tabla-cotizaciones.js') }}"></script>

@endsection
