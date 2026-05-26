@extends('layouts.base')

@section('body')
<div style="display: flex; flex-direction: column; height: 100vh; width: 100%;">
    @include('components.top-nav')

    <main style="flex: 1; overflow-y: auto; width: 100%;">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
@endpush
