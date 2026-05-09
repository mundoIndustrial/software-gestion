@extends('layouts.base')

@section('title', 'Gestión de Talleres')
@section('page-title', 'Gestión de Talleres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}">
@endpush

@section('body')
    <!-- Dashboard Top Nav -->
    @include('components.top-nav')

    <!-- Main Content -->
    <main class="main-container">
        <div class="page-header">
            <div class="page-title-group">
                <div class="subtitle">TALLERES ACTIVOS</div>
                <h1>Selección de Responsable</h1>
            </div>
            <div class="page-actions">
                <div class="search-box">
                    <span class="material-symbols-rounded">search</span>
                    <input type="text" class="search-input" placeholder="Buscar..." id="searchInput">
                </div>
                <button class="btn-filter">
                    <span class="material-symbols-rounded">filter_alt</span>
                    Filtrar
                </button>
            </div>
        </div>

        <div class="cards-grid" id="talleresGrid">
            @forelse($talleres as $taller)
                <div class="taller-card" data-name="{{ strtolower($taller->name) }}">
                    <div class="card-header-info">
                        <h2 class="taller-name">{{ $taller->name }}</h2>
                        <span class="badge-active">ACTIVO</span>
                    </div>
                    <p class="taller-role">RESPONSABLE DE TALLER</p>
                    
                    <div class="stats-container">
                        <div class="stat-row">
                            <span>Completados:</span>
                            <!-- Aquí asumo que no hay lógica real todavía, así que uso mock o 0 -->
                            <span class="stat-value stat-completed">1</span>
                        </div>
                        <div class="stat-row">
                            <span>Pendientes:</span>
                            <span class="stat-value stat-pending">0</span>
                        </div>
                    </div>
                    
                    <a href="{{ route('talleres.show', $taller->id) }}" class="btn-view">
                        Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                    </a>
                </div>
            @empty
                <div style="width: 100%; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px;">inbox</span>
                    <p>No hay talleres disponibles en este momento.</p>
                </div>
            @endforelse
        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/modulos/talleres/talleres-admin.js') }}"></script>
@endpush
