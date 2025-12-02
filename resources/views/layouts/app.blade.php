@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="container">
    @include('layouts.sidebar')

    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">
@endpush
