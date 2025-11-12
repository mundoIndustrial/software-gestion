@extends('error')

@section('error-content')
    @php
        $friendlyMessage = 'La página que buscas no existe o ha sido movida. Verifica que la dirección web esté correcta.';
        $errorCode = 'ERR-404-PAGE';
        $technicalDetails = 'HTTP 404 - Página no encontrada';
    @endphp
@endsection
