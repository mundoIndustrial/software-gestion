<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <meta name="google" content="notranslate">
      <meta http-equiv="Content-Language" content="es">
      <title>{{ config('app.name', 'Mundo Industrial') }}</title>

     <!-- Favicon -->
      <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
      <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
      <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">

      <!-- Script crítico para prevenir flash de modo claro - DEBE ejecutarse ANTES de cargar CSS -->
      <script>
          // Aplicar tema inmediatamente antes de cualquier renderizado
          (function() {
              // Leer de localStorage o cookie
              let theme = localStorage.getItem('theme');
              if (!theme) {
                  const cookies = document.cookie.split(';');
                  const themeCookie = cookies.find(c => c.trim().startsWith('theme='));
                  theme = themeCookie ? themeCookie.split('=')[1] : 'light';
              }
              if (theme === 'dark') {
                  document.documentElement.classList.add('dark-theme');
                  document.documentElement.setAttribute('data-theme', 'dark');
              }
          })();
      </script>
      
      <!-- Estilo crítico inline para prevenir flash -->
      <style>
          html[data-theme="dark"] body {
              background-color: #0f172a !important;
              color: #F1F5F9 !important;
          }
      </style>

      <!-- Fuentes y estilos -->
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
      
      <!-- SweetAlert2 CSS -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
      
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">
      
      <!-- Page-specific styles -->
      @stack('styles')

      <!-- Alpine.js -->
      <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
      
      <!-- SweetAlert2 JS -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  </head>
  <body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" 
        data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
        data-is-admin="{{ auth()->check() && auth()->user()->role?->name === 'admin' ? 'true' : 'false' }}">
      <script>
          // Sincronizar con localStorage inmediatamente
          (function() {
              const theme = localStorage.getItem('theme') || 'light';
              if (theme === 'dark') {
                  if (!document.body.classList.contains('dark-theme')) {
                      document.body.classList.add('dark-theme');
                      document.documentElement.classList.add('dark-theme');
                      document.documentElement.setAttribute('data-theme', 'dark');
                  }
              } else {
                  // Si el tema es claro, remover todas las clases dark-theme
                  document.body.classList.remove('dark-theme');
                  document.documentElement.classList.remove('dark-theme');
                  document.documentElement.removeAttribute('data-theme');
              }
          })();
      </script>
      <div class="container">
          @include('layouts.sidebar')

          <main class="main-content">
              @yield('content')
          </main>
      </div>

      <script src="{{ asset('js/sidebar.js') }}"></script>
  </body>
</html>
