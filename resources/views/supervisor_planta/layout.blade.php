<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <meta name="google" content="notranslate">
      <meta http-equiv="Content-Language" content="es">
      <title>{{ config('app.name', 'Mundo Industrial') }} - Supervisor de Planta</title>

     <!-- Favicon -->
      <link rel="icon" href="{{ asset('mundo_icon.png') }}" type="image/png">
      <link rel="apple-touch-icon" href="{{ asset('mundo_icon.png') }}">

      <!-- Script crítico para prevenir flash de modo claro -->
      <script>
          (function() {
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
      
      <!-- Estilo crítico inline -->
      <style>
          html[data-theme="dark"] body {
              background-color: #0f172a !important;
              color: #F1F5F9 !important;
          }
      </style>

      <!-- Fuentes y estilos -->
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">
      
      <!-- Page-specific styles -->
      @stack('styles')

      <!-- Alpine.js -->
      <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  </head>
  <body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" data-user-role="supervisor_planta">
      <script>
          (function() {
              const theme = localStorage.getItem('theme') || 'light';
              if (theme === 'dark') {
                  if (!document.body.classList.contains('dark-theme')) {
                      document.body.classList.add('dark-theme');
                      document.documentElement.classList.add('dark-theme');
                      document.documentElement.setAttribute('data-theme', 'dark');
                  }
              } else {
                  document.body.classList.remove('dark-theme');
                  document.documentElement.classList.remove('dark-theme');
                  document.documentElement.removeAttribute('data-theme');
              }
          })();
      </script>
      <div class="container">
          @include('supervisor_planta.sidebar')

          <main class="main-content">
              @yield('content')
          </main>
      </div>

      <script src="{{ asset('js/sidebar.js') }}"></script>
  </body>
</html>
