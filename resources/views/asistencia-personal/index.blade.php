@extends('layouts.asistencia-clean')

@section('page-title', 'Asistencia Personal')

@section('content')
<div class="container">
    <div class="content-wrapper">
        <h2>Gestión de Asistencia</h2>
        <p>Sistema de control de asistencia del personal</p>
    </div>
</div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/asistencia-personal.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/asistencia-personal.js') }}"></script>
@endsection