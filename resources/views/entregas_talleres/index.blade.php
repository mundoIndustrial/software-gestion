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
<div class="entregas-container" id="entregas-app">
    <!-- View 1: Search -->
    <div id="view-search">
        <div class="entregas-content">
            <div class="content-title">
                <h2>Registro de Entregas</h2>
                <p>Busca un recibo por número de orden o nombre.</p>
            </div>

            <form action="{{ route('entregas-talleres.buscar') }}" method="GET">
                <div class="search-group">
                    <input type="text" name="busqueda" class="search-input" placeholder="N° ORDEN O CLIENTE..." required id="main-search">
                    <div class="search-icon">
                        <span class="material-symbols-rounded">search</span>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="background: #94a3b8; opacity: 0.7;">Siguiente</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/entregas-talleres.js') }}?v={{ time() }}"></script>
@endpush
