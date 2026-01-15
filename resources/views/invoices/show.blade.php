@extends('layouts.app')

@section('title', 'Factura - Pedido #' . $orden->numero_pedido)
@section('page-title', 'Factura de Pedido')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
    <x-invoice-factura :orden="$orden" :mostrarProcesos="true" :mostrarEPP="true" />
@endsection
