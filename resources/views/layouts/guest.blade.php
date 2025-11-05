<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">


        <title>{{ config('app.name', 'Mundo Industrial') }}</title>

        <!-- Favicon optimizado -->
        <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ time() }}" sizes="any" type="image/x-icon">
        <link rel="icon" href="{{ asset('favicon_16x16.png') }}?v={{ time() }}" sizes="16x16" type="image/png">
        <link rel="icon" href="{{ asset('favicon_32x32.png') }}?v={{ time() }}" sizes="32x32" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('favicon_180x180.png') }}?v={{ time() }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-800">


            <!-- Logo de la empresa -->
            <div class="mb-6">
                <a href="/">
                    <img src="{{ asset('logo.png') }}" alt="Mundo Industrial" class="h-24 w-auto">
                </a>
            </div>


            <!-- Contenedor del formulario -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>



