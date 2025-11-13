@extends('error')

@section('error-content')
    @php
        $friendlyMessage = 'No tienes permisos para acceder a esta sección. Si crees que deberías tener acceso, contacta al administrador del sistema.';
        $errorCode = 'ERR-403-ACCESS';
        $technicalDetails = 'HTTP 403 - Acceso denegado';
    @endphp
@endsection
