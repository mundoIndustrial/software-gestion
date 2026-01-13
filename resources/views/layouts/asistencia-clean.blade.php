<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Asistencia Personal')</title>
    
    <!-- Estilos de la aplicación -->
    @yield('styles')
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="header-logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="logo-img">
            </div>
            <div class="header-title">
                <h1>@yield('page-title', 'Asistencia Personal')</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Scripts -->
    @yield('scripts')
</body>
</html>