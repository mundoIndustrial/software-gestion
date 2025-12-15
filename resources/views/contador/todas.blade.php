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
                            ['key' => 'numero', 'label' => 'Número', 'flex' => '0 0 140px', 'justify' => 'center'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'flex' => '0 0 180px', 'justify' => 'center'],
                            ['key' => 'cliente', 'label' => 'Cliente', 'flex' => '0 0 200px', 'justify' => 'center'],
                            ['key' => 'asesora', 'label' => 'Asesora', 'flex' => '0 0 150px', 'justify' => 'center'],
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
                            <div class="table-row" data-cotizacion-id="{{ $cotizacion->id }}" data-numero="COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}" data-cliente="{{ is_object($cotizacion->cliente) ? $cotizacion->cliente->nombre : ($cotizacion->cliente ?? '') }}" data-asesora="{{ $cotizacion->asesora ?? ($cotizacion->usuario->name ?? '') }}" data-fecha="{{ $cotizacion->created_at ? $cotizacion->created_at->format('d/m/Y') : '' }}">
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
                                        <a href="#" class="action-menu-item" data-action="pdf" onclick="abrirModalPDF({{ $cotizacion->id }}); return false;">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>Ver PDF</span>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Número -->
                                <div class="table-cell" style="flex: 0 0 140px;" data-numero="COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}">
                                    <div class="cell-content" style="justify-content: center;">
                                        <span style="font-weight: 600;">COT-{{ str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT) }}</span>
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

<!-- Modal de Cotización -->
<div id="cotizacionModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto;">
    <div class="modal-content" style="background: white; border-radius: 12px; margin: 2rem auto; max-width: 1000px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <button onclick="cerrarModalCotizacion()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; z-index: 10001;">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div id="cotizacionContent" style="padding: 2rem;"></div>
    </div>
</div>

<!-- Modal de Visor de Costos por Prenda -->
<div id="visorCostosModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9998; justify-content: center; align-items: center; padding: 2rem; overflow: hidden;">
    <div class="modal-content" id="visorCostosModalContent" style="width: 90%; max-width: 800px; height: auto; overflow: visible; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); display: flex; flex-direction: column;">
        <style>
            #visorCostosModal {
                overflow: hidden;
            }
            #visorCostosModal .modal-content {
                max-height: calc(100vh - 4rem);
                overflow: visible;
            }
            #visorCostosContenido {
                overflow-x: hidden;
                overflow-y: auto;
                max-height: none;
            }
            #visorCostosContenido::-webkit-scrollbar {
                width: 8px;
            }
            #visorCostosContenido::-webkit-scrollbar-track {
                background: transparent;
            }
            #visorCostosContenido::-webkit-scrollbar-thumb {
                background: #ccc;
                border-radius: 4px;
            }
            #visorCostosContenido::-webkit-scrollbar-thumb:hover {
                background: #999;
            }
        </style>
        <!-- Header del Modal -->
        <div style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; transform: scale(0.8); transform-origin: top left; width: 125%;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 700;" id="visorTitulo">-</h2>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; opacity: 0.9;" id="visorCliente">Cliente: -</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="visorCostosAnterior()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.2s;" title="Prenda Anterior" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ‹
                </button>
                <span style="color: white; font-weight: 600; min-width: 60px; text-align: center;" id="visorIndice">1 / 1</span>
                <button onclick="visorCostosProximo()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.2s;" title="Próxima Prenda" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ›
                </button>
                <button onclick="cerrarVisorCostos()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ✕
                </button>
            </div>
        </div>

        <!-- Contenido del Modal -->
        <div style="padding: 2rem;" id="visorCostosContenido">
            <!-- Se llena dinámicamente -->
        </div>
    </div>
</div>

<!-- Script de Cotización Modal -->
<script src="{{ asset('js/contador/cotizacion.js') }}"></script>

<!-- Script de Tabla de Cotizaciones -->
<script src="{{ asset('js/contador/tabla-cotizaciones.js') }}"></script>

@endsection
