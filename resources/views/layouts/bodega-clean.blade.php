<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <!-- Meta tags para WebSockets/Reverb -->
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-app-id" content="{{ config('broadcasting.connections.reverb.app_id') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    @endauth
    
    <title>@yield('title', 'Bodega')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Vite CSS -->
    @vite(['resources/css/app.css'])
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
    @stack('styles')
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
    </style>
</head>
<body class="bg-slate-50">
    @yield('content')

    <!-- Vite (contiene app.css y app.js críticos) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Laravel Echo & Pusher JS cargado vía Vite en app.js (bootstrap.js) -->
    
    @auth
    <!-- Echo se inicializa automáticamente vía Vite en app.js -->
    <!-- Script de tiempo real para bodega -->
    <script defer src="{{ asset('js/modulos/bodega/bodega-realtime.js') }}"></script>
    @endauth
    
    <script src="{{ asset('js/bodega-pedidos.js') }}"></script>
    @stack('scripts')
</body>
</html>
