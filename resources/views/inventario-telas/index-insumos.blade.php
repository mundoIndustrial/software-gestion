@extends('layouts.insumos')

@section('title', 'Inventario de Telas - Insumos')
@section('page-title', 'Inventario de Telas')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventario-telas/inventario.css') }}">
<style>
    /* Ocultar el top-nav del layout para esta vista */
    .top-nav {
        display: none !important;
    }
    
    /* Ajustar page-content para que no tenga padding superior */
    .page-content {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* FIX: Remover max-width del container para insumos */
    .container {
        max-width: none !important;
        width: 100% !important;
        margin-left: 0 !important;
        padding: 1.5rem !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- Incluir vista general de inventario de telas sin duplicación de código --}}
@include('inventario-telas.index')
@endsection
