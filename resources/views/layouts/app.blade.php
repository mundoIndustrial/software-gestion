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
      
      <!-- CSS Crítico Inline -->
      @include('partials.critical-css')

      <!-- Preconnect a dominios externos (DNS prefetch) -->
      <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preconnect" href="https://unpkg.com" crossorigin>
      <link rel="dns-prefetch" href="https://fonts.googleapis.com">
      <link rel="dns-prefetch" href="https://unpkg.com">
      
      <!-- Preload critical CSS -->
      <link rel="preload" href="{{ asset('css/sidebar.css') }}" as="style">
      
      <!-- Load critical CSS immediately -->
      <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
      
      <!-- Lazy load non-critical CSS -->
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded&display=swap" rel="stylesheet"></noscript>
      
      <!-- Vite assets with preload -->
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      
      <!-- Page-specific styles -->
      @stack('styles')

      <!-- Alpine.js - defer and preload -->
      <link rel="preload" href="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" as="script">
      <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  </head>
  <body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}">
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

      <!-- Lazy loading de estilos -->
      <script defer src="{{ asset('js/lazy-styles.js') }}"></script>
      
      <!-- Script principal -->
      <script defer src="{{ asset('js/sidebar.js') }}"></script>
  </body>
</html>
