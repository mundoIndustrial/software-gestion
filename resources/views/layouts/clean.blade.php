@extends('layouts.base')

@section('body')
<div class="clean-layout">
    <main class="clean-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
<style>
    .clean-layout {
        min-height: 100vh;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
    }
    .clean-content {
        flex: 1;
        padding: 0;
        margin: 0;
    }
    /* Asegurar que el contenedor del módulo ocupe el espacio necesario */
    .entregas-container {
        margin: 40px auto;
    }
    @media (max-width: 640px) {
        .entregas-container {
            margin: 0;
            border-radius: 0;
            min-height: 100vh;
            width: 100%;
            max-width: none;
        }
    }
</style>
@endpush
