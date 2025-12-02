@extends('layouts.base')

@section('module', 'contador')

@section('body')
<div class="container">
    @include('layouts.sidebar')

    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/editar-tallas.js') }}"></script>
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/contador/contador.js') }}"></script>
@endpush
