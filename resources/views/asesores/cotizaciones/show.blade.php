@extends('layouts.asesores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-show.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/cotizaciones-show.js') }}"></script>
@endpush

@section('content')

<div class="page-wrapper">
    <div class="container-fluid py-4">
        {{-- Header --}}
        @include('components.cotizaciones.show.header', ['cotizacion' => $cotizacion])

        {{-- Info Cards --}}
        @include('components.cotizaciones.show.info-cards', ['cotizacion' => $cotizacion])

        {{-- Tabs Navigation --}}
        @include('components.cotizaciones.show.tabs', ['cotizacion' => $cotizacion, 'logo' => $logo ?? null])

        {{-- Tab Content Wrapper --}}
        <div class="tab-content-wrapper">
            {{-- Prendas Tab --}}
            @include('components.cotizaciones.show.prendas-tab', [
                'cotizacion' => $cotizacion,
                'esLogo' => strpos(strtolower($cotizacion->tipo === 'L' ? 'logo' : ''), 'logo') !== false,
                'tienePrendas' => $cotizacion->prendas && count($cotizacion->prendas) > 0
            ])

            {{-- Logo Tab --}}
            @include('components.cotizaciones.show.logo-tab', [
                'logo' => $logo ?? null,
                'cotizacion' => $cotizacion,
                'esLogo' => strpos(strtolower($cotizacion->tipo === 'L' ? 'logo' : ''), 'logo') !== false
            ])
        </div>
    </div>
</div>

@endsection
