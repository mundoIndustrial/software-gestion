@extends('operario.layout')

@section('title', 'Registro de Entregas - Talleres')

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
<div class="entregas-container entregas-talleres-index"
     data-route-api-search="{{ route('entregas-talleres.api.search') }}"
     data-route-buscar="{{ route('entregas-talleres.buscar') }}">
    <div class="page-header">
        <div class="page-title-group">
            <div class="subtitle">TALLERES ACTIVOS</div>
            <h2>Selecciona un taller para ver sus recibos</h2>
        </div>

        <div class="page-actions">
            <form action="{{ route('entregas-talleres.index') }}" method="GET" class="gooey-search-wrapper">
                <span class="material-symbols-rounded gooey-search-icon">search</span>
                <input
                    type="text"
                    name="search"
                    class="gooey-search-input"
                    placeholder="Buscar taller..."
                    value="{{ $search ?? '' }}"
                    id="searchInput"
                >
                <button
                    class="gooey-search-clear"
                    id="clearSearch"
                    type="button"
                    onclick="window.location.href='{{ route('entregas-talleres.index') }}'"
                >
                    <span class="material-symbols-rounded">close</span>
                </button>
            </form>
        </div>
    </div>

    <div class="cards-grid talleres-grid-entregas" id="talleresGrid">
        @forelse($talleres as $taller)
            <a
                href="{{ route('entregas-talleres.buscar', ['taller_id' => $taller->id]) }}"
                class="taller-card taller-card-link {{ !$taller->activo ? 'inactive' : '' }}"
                data-name="{{ strtolower($taller->name) }}"
                data-taller-id="{{ $taller->id }}"
            >
                <div class="card-header-info">
                    <h2 class="taller-name">{{ $taller->name }}</h2>
                    <div class="taller-status-badge active">ACTIVO</div>
                </div>

                <p class="taller-role">RESPONSABLE DE TALLER</p>

                <div class="stats-container">
                    <div class="stat-row">
                        <span>Recibos asignados</span>
                        <span class="stat-value">Ver</span>
                    </div>
                    <div class="stat-row">
                        <span>Estado</span>
                        <span class="stat-value stat-active">Activo</span>
                    </div>
                </div>

                <div class="card-footer-actions">
                    <span class="btn-view btn-view-recibos">
                        Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                    </span>
                </div>
            </a>
        @empty
            <div style="width: 100%; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block;">inbox</span>
                <p>No se encontraron talleres activos.</p>
            </div>
        @endforelse
    </div>

    <div class="pagination-container">
        @if($talleres instanceof \Illuminate\Pagination\LengthAwarePaginator)
            {{ $talleres->appends(['search' => $search])->links('vendor.pagination.simple-clean') }}
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/entregas-talleres.js') }}?v={{ time() }}"></script>
@endpush
