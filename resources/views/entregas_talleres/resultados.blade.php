@extends('operario.layout')

@section('title', 'Recibos del Taller')

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
    <div class="results-header">
        <a href="{{ route('entregas-talleres.index') }}" class="back-btn">
            <span class="material-symbols-rounded">arrow_back_ios_new</span>
        </a>
        <div class="results-header-copy">
            <div class="results-header-top">
                <div class="section-label section-label-inline">Recibos asignados</div>
            </div>
            <h2>{{ $taller ? $taller->name : 'Resultados de busqueda' }}</h2>
        </div>
    </div>

    @if($taller)
        <form action="{{ route('entregas-talleres.buscar') }}" method="GET" class="results-search-form">
            <input type="hidden" name="taller_id" value="{{ $taller->id }}">
            <input type="hidden" name="estado" value="{{ $estado ?? 'pendientes' }}">
            <div class="gooey-search-wrapper results-search-wrapper">
                <span class="material-symbols-rounded gooey-search-icon">search</span>
                <input
                    type="text"
                    name="busqueda"
                    class="gooey-search-input"
                    placeholder="Buscar recibo en este taller..."
                    value="{{ $busqueda }}"
                >
                <button class="gooey-search-clear" type="button">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
        </form>
    @endif

    <div class="results-content">
        @if($taller)
            <div class="recibos-tabs">
                <button
                    type="button"
                    class="tab-pill {{ ($estado ?? 'pendientes') === 'pendientes' ? 'active' : '' }}"
                    data-estado="pendientes"
                >
                    Pendientes
                </button>
                <button
                    type="button"
                    class="tab-pill {{ ($estado ?? 'pendientes') === 'completados' ? 'active' : '' }}"
                    data-estado="completados"
                >
                    Completados
                </button>
            </div>
        @endif

        @forelse($recibos as $recibo)
            <div class="recibo-card">
                <div class="recibo-info" onclick="window.location.href='{{ route('entregas-talleres.show', $recibo->id) }}?es_parcial={{ $recibo->es_parcial }}&es_bodega={{ $recibo->es_bodega ?? 0 }}&prenda_bodega_id={{ $recibo->prenda_bodega_id ?? 0 }}'">
                    <div class="recibo-id">Recibo #{{ $recibo->numero_recibo }} - {{ $recibo->tipo_recibo }}</div>
                    <div class="recibo-name">{{ $recibo->nombre_prenda }}</div>
                    <div class="recibo-user">
                        <span class="material-symbols-rounded" style="font-size: 16px;">person</span>
                        {{ $recibo->encargado ?? 'SIN ENCARGADO' }}
                    </div>
                    @if($recibo->tiene_observaciones)
                        <div style="margin-top: 8px; padding: 6px 10px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 3px; font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-rounded" style="font-size: 16px;">warning</span>
                            <span>Tiene novedades</span>
                        </div>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button class="btn-agregar-novedad" onclick="openObservacionModal({{ $recibo->id }}, {{ $recibo->es_parcial }}, {{ $recibo->es_bodega ?? 0 }}, {{ $recibo->prenda_bodega_id ?? 0 }}, event)" style="background: #ef4444; border: none; cursor: pointer; padding: 8px 14px; display: flex; align-items: center; justify-content: center; gap: 6px; color: white; font-size: 12px; font-weight: 600; border-radius: 6px; transition: all 0.2s ease; white-space: nowrap;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">note_add</span>
                        <span>Novedad</span>
                    </button>
                    <button onclick="window.location.href='{{ route('entregas-talleres.show', $recibo->id) }}?es_parcial={{ $recibo->es_parcial }}&es_bodega={{ $recibo->es_bodega ?? 0 }}&prenda_bodega_id={{ $recibo->prenda_bodega_id ?? 0 }}'" style="background: #2450ef; border: none; cursor: pointer; padding: 8px 14px; display: flex; align-items: center; justify-content: center; gap: 6px; color: white; font-size: 12px; font-weight: 600; border-radius: 6px; transition: all 0.2s ease; white-space: nowrap;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">visibility</span>
                        <span>Ver</span>
                    </button>
                    <span class="material-symbols-rounded" style="color: #cbd5e1;">chevron_right</span>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <span class="material-symbols-rounded empty-state-icon">search_off</span>
                <p>
                    @if($taller)
                        @if(($estado ?? 'pendientes') === 'completados')
                            No hay recibos completados para este taller.
                        @else
                            No hay recibos pendientes para este taller.
                        @endif
                    @else
                        No se encontraron recibos para "{{ $busqueda }}"
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Novedad -->
<div id="modalNovedad" style="display: none; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; max-width: 760px; width: 92%; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25);">
        <div style="background: #111827; color: white; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
            <div id="modalNovedadHeaderTitulo" style="font-weight: 800; letter-spacing: 0.5px; font-size: 0.95rem; text-transform: uppercase;">AGREGAR NOVEDAD</div>
            <button type="button" onclick="cerrarModalNovedad()" style="background: transparent; border: none; color: white; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0.25rem;">×</button>
        </div>
        <div style="padding: 1.25rem; overflow-y: auto; max-height: calc(85vh - 56px);">
            <input type="hidden" id="novedadReciboId" value="">
            <input type="hidden" id="novedadEsParcial" value="">
            <input type="hidden" id="novedadEsBodega" value="">
            <input type="hidden" id="novedadPrendaBodegaId" value="">
            
            <!-- Historial de Novedades -->
            <div style="margin-bottom: 1rem;">
                <div style="color: #111827; font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem;">Historial:</div>
                <div id="novedadesHistorial" style="max-height: 220px; overflow-y: auto; padding-right: 0.25rem; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.75rem;">
                    <div style="text-align: center; color: #9ca3af; padding: 1rem; font-size: 0.9rem;">Cargando historial...</div>
                </div>
            </div>
            
            <div style="height: 1px; background: #e5e7eb; margin: 1rem 0;"></div>
            
            <!-- Agregar Nueva Novedad -->
            <div style="color: #111827; font-weight: 800; font-size: 1rem; margin-bottom: 0.75rem;">Agregar Nueva Novedad:</div>
            <div style="margin-bottom: 1rem;">
                <textarea id="novedadDescripcionText" rows="5" style="width: 100%; padding: 0.9rem; border: 1px solid #d1d5db; border-radius: 10px; resize: vertical; font-size: 0.95rem;" placeholder="Ej: Entrega incompleta, falta color rojo, prenda dañada..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <button type="button" id="btnGuardarNovedad" onclick="guardarNovedad()" style="padding: 0.85rem 1rem; background: #ef4444; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Guardar Novedad</button>
                <button type="button" onclick="cerrarModalNovedad()" style="padding: 0.85rem 1rem; background: #94a3b8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 800;">Cancelar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/entregas-talleres.js') }}?v={{ time() }}"></script>
@endpush
