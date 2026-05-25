@extends('operario.layout')

@section('title', 'Detalle de Entrega - Talleres')

@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <span class="material-symbols-rounded">construction</span>
        <span>ENTREGAS TALLERES</span>
    </span>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/entregas-talleres.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="entregas-container">
    <div class="results-content" style="padding-top: 20px;" 
         id="recibo-data" 
         data-id="{{ $recibo->id }}" 
         data-parcial="{{ $esParcial ? '1' : '0' }}"
         data-es-bodega="{{ ($esBodega ?? false) ? '1' : '0' }}"
         data-prenda-bodega-id="{{ $prendaBodegaId ?? 0 }}"
         data-route-registrar="{{ route('entregas-talleres.registrar') }}"
         data-route-historial="{{ route('entregas-talleres.historial', $recibo->id) }}"
         data-route-eliminar="{{ route('entregas-talleres.eliminar', ':id') }}">

        <div class="detail-actions">
            <a href="javascript:history.back()" class="back-btn detail-back-btn" aria-label="Volver">
                <span class="material-symbols-rounded">arrow_back_ios_new</span>
            </a>
            <a href="{{ route('entregas-talleres.index') }}" class="back-btn detail-close-btn" aria-label="Cerrar">
                <span class="material-symbols-rounded">close_small</span>
            </a>
        </div>
        
        <div class="detail-card">
            <div class="recibo-info">
                <div class="recibo-id">Recibo #{{ $numeroRecibo }}</div>
                <div class="recibo-name" style="font-size: 20px; margin: 8px 0;">{{ $prendaNombre }}</div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="recibo-user">
                        <span class="material-symbols-rounded" style="font-size: 16px;">person</span>
                        {{ $encargado ?? 'Sin asignar' }}
                    </div>
                    <a href="javascript:void(0)" onclick="openHistorial()" style="color: var(--accent-blue); font-weight: 700; font-size: 13px; text-decoration: underline;">Historial</a>
                </div>
            </div>
        </div>

        <div class="section-label" style="padding: 0 16px;">Tallas y Cantidades</div>

        <div class="tallas-grouped-container">
            @foreach($tallasAgrupadas as $genero => $colores)
                <div class="genero-group">
                    <div class="genero-header">
                        <span class="material-symbols-rounded">group</span>
                        {{ $genero }}
                    </div>
                    
                    @foreach($colores as $color => $items)
                        <div class="color-section">
                            @if($color !== 'SIN COLOR')
                                <div class="color-label">
                                    <span class="material-symbols-rounded">palette</span>
                                    COLOR: {{ $color }}
                                </div>
                            @endif

                            <div class="tallas-section">
                                @foreach($items as $t)
                                    @php
                                        $key = "{$t->talla}|{$t->genero}|{$t->color}";
                                        $entregado = $entregasPorLlave[$key] ?? 0;
                                        $disponible = $t->cantidad - $entregado;
                                        $isCompleted = $disponible <= 0;
                                        $safeId = str_replace(['|', ' '], '_', $key);
                                    @endphp
                                    <div class="talla-item {{ $isCompleted ? 'completed' : '' }}" id="talla-item-{{ $safeId }}">
                                        <div class="talla-badge">{{ $t->talla }}</div>
                                        <div class="talla-info">
                                            <div class="talla-counts">
                                                <span class="delivered" id="delivered-{{ $safeId }}">{{ $entregado }}</span>
                                                <span class="total"> / {{ $t->cantidad }}</span>
                                            </div>
                                            <div class="talla-status" id="status-container-{{ $safeId }}">
                                                @if($isCompleted)
                                                    COMPLETADO
                                                @else
                                                    <span id="disponibles-{{ $safeId }}">{{ $disponible }}</span> DISPONIBLES
                                                @endif
                                            </div>
                                        </div>
                                        @if($isCompleted)
                                            <div class="btn-completed">
                                                <span class="material-symbols-rounded">check</span>
                                            </div>
                                        @else
                                            <button class="btn-add" onclick="promptDelivery('{{ $t->talla }}', {{ $disponible }}, '{{ $t->genero }}', '{{ $t->color }}', '{{ $safeId }}')">
                                                <span class="material-symbols-rounded">add</span>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal-overlay" id="modal-overlay" onclick="closeHistorial()"></div>
<div class="historial-modal" id="historial-modal">
    <div class="modal-header">
        <div class="close-btn" onclick="closeHistorial()">
            <span class="material-symbols-rounded">close</span>
        </div>
        <h2 style="font-weight: 800; font-size: 22px;">Historial de Entregas</h2>
    </div>
    <div id="historial-items-container">
        <!-- Items loaded via JS -->
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/entregas-talleres.js') }}?v={{ time() }}"></script>
@endpush
