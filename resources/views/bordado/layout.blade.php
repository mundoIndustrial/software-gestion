<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bordado') - MundoIndustrial</title>

    <!-- CSS (heredado de asesores) -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">

    <!-- CSS Específico del Sidebar Bordado -->
    <link rel="stylesheet" href="{{ asset('css/bordado/sidebar.css') }}">>

    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Configuración del main-content como flex */
        .main-content {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            background: white;
            flex-shrink: 0;
        }

        /* Asegurar que content-area expanda correctamente */
        .content-area {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: auto;
            width: 100%;
            min-width: 0;
            min-height: 0;
        }

        .nav-left {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
        }

        /* Estilos para notificaciones */
        .notification-dropdown {
            position: relative;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #2c3e50;
            padding: 0.5rem;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* User dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background-color 0.2s ease;
            color: #2c3e50;
        }

        .user-btn:hover {
            background-color: #ecf0f1;
        }

        .user-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .user-menu.active {
            display: block;
        }

        .user-menu-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #2c3e50;
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
        }

        .user-menu-item:last-child {
            border-bottom: none;
        }

        .user-menu-item:hover {
            background-color: #f8f9fa;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="main-content">
        <!-- Sidebar -->
        @include('bordado.sidebar')

        <!-- Contenido Principal -->
        <div class="content-area">
            <!-- Header/Top Navigation -->
            <div class="top-nav" style="padding: 1rem 2rem; border-bottom: 1px solid #e5e7eb;">
                <div class="nav-left">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0;">@yield('page-title', 'Bordado')</h1>
                </div>
                <div class="nav-right">
                    <!-- User Menu -->
                    <div class="user-dropdown">
                        <button class="user-btn" onclick="toggleUserMenu(event)">
                            <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                            <span>{{ auth()->user()->name }}</span>
                            <span class="material-symbols-rounded">expand_more</span>
                        </button>
                        <div class="user-menu" id="user-menu">
                            <a href="{{ route('profile.edit') }}" class="user-menu-item">
                                <span class="material-symbols-rounded">person</span>
                                <span>Mi Perfil</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                @csrf
                                <button type="submit" class="user-menu-item">
                                    <span class="material-symbols-rounded">logout</span>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido de la página -->
            <div style="flex: 1; overflow: auto; padding: 2rem;">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        // Toggle User Menu
        function toggleUserMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('active');
        }

        // Cerrar menus al hacer clic fuera
        document.addEventListener('click', function() {
            const userMenu = document.getElementById('user-menu');
            if (userMenu) userMenu.classList.remove('active');
        });
    </script>

    @stack('scripts')
</body>
</html>
