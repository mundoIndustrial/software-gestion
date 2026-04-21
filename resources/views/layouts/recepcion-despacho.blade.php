@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="app-container">
    <div class="main-content" id="mainContent">
        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>

<style>
body[data-module="produccion"] .page-content {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    min-height: 100vh !important;
    background: transparent !important;
}

body[data-module="produccion"] .main-content {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}

body[data-module="produccion"] .app-container {
    margin: 0 !important;
    padding: 0 !important;
    width: 100vw !important;
}
</style>

@push('styles')
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .container {
            display: flex;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .main-content .page-content {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }

        .main-content {
            margin: initial !important;
            padding: initial !important;
        }

        table {
            visibility: visible !important;
            opacity: 1 !important;
        }

        th, td {
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
@endpush
