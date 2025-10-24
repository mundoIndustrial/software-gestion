<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>{{ config('app.name', 'Mundo Industrial') }}</title>

     <!-- Favicon optimizado -->
      <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" type="image/x-icon">
      <link rel="icon" href="{{ asset('favicon_32x32.png') }}" sizes="32x32" type="image/png">
      <link rel="apple-touch-icon" href="{{ asset('favicon_180x180.png') }}">


      <!-- Fuentes y estilos -->
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      <link rel="stylesheet" href="{{ asset('css/registros.css') }}">

      <!-- Alpine.js -->
      <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  </head>
  <body>
      <div class="container">
          @include('layouts.sidebar')

          <main class="main-content">
              @yield('content')
          </main>
      </div>

      <script src="{{ asset('js/sidebar.js') }}"></script>
  </body>
</html>
