@extends('error')

@section('error-content')
    @php
        $friendlyMessage = 'Ocurrió un error interno en el servidor. Nuestro equipo técnico ha sido notificado y está trabajando para solucionarlo.';
        $errorCode = 'ERR-500-SERVER';
        $technicalDetails = 'HTTP 500 - Error interno del servidor';
    @endphp
@endsection
