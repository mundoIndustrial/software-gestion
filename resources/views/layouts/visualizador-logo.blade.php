<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Visualizador Logo') - MundoIndustrial</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    @stack('styles')
    
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            margin: 0;
        }

        .top-nav {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .breadcrumb-section {
            flex: 1;
        }

        .page-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-title i {
            color: #663399;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .user-btn:hover {
            background: #f0f0f0;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #663399, #00A86B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .user-role {
            color: #999;
            font-size: 0.75rem;
        }

        .user-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            display: none;
            z-index: 1000;
        }

        .user-menu.show {
            display: block;
        }

        .user-menu-header {
            padding: 1rem;
            display: flex;
            gap: 0.75rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .user-avatar-large {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #663399, #00A86B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .user-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-menu-name {
            margin: 0;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .user-menu-email {
            margin: 0;
            color: #666;
            font-size: 0.75rem;
        }

        .menu-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 0.5rem 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .menu-item:hover {
            background: #f5f5f5;
        }

        .menu-item.logout {
            color: #dc3545;
        }

        .menu-item span.material-symbols-rounded {
            font-size: 1.2rem;
        }

        .page-content {
            flex: 1;
            padding: 0;
        }

        .mobile-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .top-nav {
                padding: 0.5rem 1rem;
            }

            .nav-right {
                gap: 1rem;
            }

            .user-info {
                display: none;
            }

            .page-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body class="light-theme">
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <header class="top-nav">
            <div class="nav-left">
                <!-- Logo de la empresa -->
                <div style="margin-right: 1.5rem;">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 55px; width: auto;">
                </div>
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Visualizador Logo')</h1>
                </div>
            </div>

            <div class="nav-right">
                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            @if(Auth::user()->avatar)
                                <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
                            @else
                                <div class="avatar-placeholder">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu">
                        <div class="user-menu-header">
                            <div class="user-avatar-large">
                                @if(Auth::user()->avatar)
                                    <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="user-menu-name">{{ Auth::user()->name }}</p>
                                <p class="user-menu-email">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <div class="menu-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-item logout">
                                <span class="material-symbols-rounded">logout</span>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle user menu
        document.getElementById('userBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userMenu')?.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('userMenu');
            const userBtn = document.getElementById('userBtn');
            if (userMenu && !userBtn?.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
