@extends('layouts.base')

@section('module', 'insumos')

@section('body')
<div class="container">
    @include('layouts.sidebar')

    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/insumos/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/insumos/pagination.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/insumos/layout.js') }}"></script>
@endpush
