@extends('operario.layout')

@section('title', 'Resultados de Búsqueda - Talleres')

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
                <span class="material-symbols-rounded">arrow_back</span>
            </a>
        </div>

    <div class="results-content">
        <div class="section-label">Resultados</div>

        @forelse($recibos as $recibo)
            <div class="recibo-card" onclick="window.location.href='{{ route('entregas-talleres.show', $recibo->id) }}?es_parcial={{ $recibo->es_parcial }}'">
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
            <div style="text-align: center; padding: 40px 0; color: #94a3b8;">
                <span class="material-symbols-rounded" style="font-size: 48px; margin-bottom: 16px;">search_off</span>
                <p>No se encontraron recibos para "{{ $busqueda }}"</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
