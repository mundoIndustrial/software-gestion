{{-- Vista de Asesores para Inventario de Telas - Reutiliza vista general --}}
@extends('layouts.asesores')

@section('title', 'Inventario de Telas')
@section('page-title', 'Inventario de Telas')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventario-telas/inventario.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- Incluir vista general de inventario de telas sin duplicación de código --}}
@include('inventario-telas.index')
@endsection
