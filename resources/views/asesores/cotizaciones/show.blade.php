@extends('layouts.asesores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/cotizaciones-show.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/asesores/cotizaciones-show.js') }}"></script>
@endpush

@section('content')

@php
    $idReflectivo = \App\Models\TipoCotizacion::getIdPorCodigo('RF');
    $esReflectivo = $cotizacion->tipo_cotizacion_id === $idReflectivo;
@endphp

<div class="page-wrapper">
    <div class="container-fluid py-4">
        {{-- Header --}}
        @include('components.cotizaciones.show.header', ['cotizacion' => $cotizacion])

        {{-- Info Cards --}}
        @include('components.cotizaciones.show.info-cards', ['cotizacion' => $cotizacion])

        @if($esReflectivo)
            {{-- Si es SOLO reflectivo, mostrar directamente sin tabs --}}
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                @include('components.cotizaciones.show.reflectivo-tab-direct', [
                    'cotizacion' => $cotizacion
                ])
            </div>
        @else
            {{-- Tabs Navigation --}}
            @include('components.cotizaciones.show.tabs', ['cotizacion' => $cotizacion, 'logo' => $logo ?? null])

            {{-- Tab Content Wrapper --}}
            <div class="tab-content-wrapper">
                {{-- Prendas Tab --}}
                @php
                    $idLogo = \App\Models\TipoCotizacion::getIdPorCodigo('L');
                    $idCombinada = \App\Models\TipoCotizacion::getIdPorCodigo('PL');
                    $esLogoTab = $cotizacion->tipo_cotizacion_id === $idLogo || $cotizacion->tipo_cotizacion_id === $idCombinada;
                @endphp
                @include('components.cotizaciones.show.prendas-tab', [
                    'cotizacion' => $cotizacion,
                    'esLogo' => $esLogoTab,
                    'tienePrendas' => $cotizacion->prendas && count($cotizacion->prendas) > 0
                ])

                {{-- Logo Tab --}}
                @include('components.cotizaciones.show.logo-tab', [
                    'logo' => $logo ?? null,
                    'cotizacion' => $cotizacion,
                    'esLogo' => $esLogoTab
                ])

                {{-- Reflectivo Tab --}}
                @include('components.cotizaciones.show.reflectivo-tab', [
                    'cotizacion' => $cotizacion
                ])
            </div>
        @endif
    </div>
</div>

@endsection
