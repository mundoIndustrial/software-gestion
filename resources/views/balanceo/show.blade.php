@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/orders-styles/modern-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/balanceo-responsive.css') }}">

<div class="tableros-container balanceo-container" x-data="balanceoApp({{ $balanceo ? $balanceo->id : 'null' }})">
    @include('balanceo.partials.header', ['prenda' => $prenda])
    
    @if(!$balanceo)
        @include('balanceo.partials.no-balanceo', ['prenda' => $prenda])
    @else
        @include('balanceo.partials.tabla-operaciones')
        @include('balanceo.partials.tabla-metricas-globales')
        @include('balanceo.partials.modal-operacion')
    @endif
</div>

@include('balanceo.partials.scripts', ['balanceo' => $balanceo])

@endsection
