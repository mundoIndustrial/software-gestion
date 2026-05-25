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
            <div class="recibo-card" onclick="window.location.href='{{ route('entregas-talleres.show', $recibo->id) }}?es_parcial={{ $recibo->es_parcial }}&es_bodega={{ $recibo->es_bodega ?? 0 }}&prenda_bodega_id={{ $recibo->prenda_bodega_id ?? 0 }}'">
                <div class="recibo-info">
                    <div class="recibo-id">Recibo #{{ $recibo->numero_recibo }} - {{ $recibo->tipo_recibo }}</div>
                    <div class="recibo-name">{{ $recibo->nombre_prenda }}</div>
                    <div class="recibo-user">
                        <span class="material-symbols-rounded" style="font-size: 16px;">person</span>
                        {{ $recibo->encargado ?? 'SIN ENCARGADO' }}
                    </div>
                </div>
                <span class="material-symbols-rounded" style="color: #cbd5e1;">chevron_right</span>
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
@endsection

@push('scripts')
<script src="{{ asset('js/entregas-talleres.js') }}?v={{ time() }}"></script>
@endpush
