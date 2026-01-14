@extends('layouts.asistencia-clean')

@section('page-title', 'Asistencia Personal')

@section('content')
<div class="container">
    <div class="content-wrapper">
        <h2>Gestión de Asistencia</h2>
        <p>Aquí irá el contenido de asistencia personal</p>
    </div>
</div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencia-personal.css') }}">
@endsection

@section('scripts')
    <!-- Módulos del sistema de Asistencia Personal -->
    <script src="{{ asset('js/asistencia-personal/utilidades.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/pdf-handler.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/filtros-horas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/busqueda.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/horas-trabajadas.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/report-details.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/absencias.js') }}"></script>
    <script src="{{ asset('js/asistencia-personal/init.js') }}"></script>
@endsection