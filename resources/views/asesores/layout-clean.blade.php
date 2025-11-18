<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Asesores') - MundoIndustrial</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Material Symbols para iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @stack('styles')
    
    <style>
        /* Layout limpio sin sidebar */
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

        /* Navbar simplificado */
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

        .page-title span {
            color: #666;
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

        .page-content {
            flex: 1;
            padding: 0;
        }

        /* Mobile menu toggle hidden */
        .mobile-toggle,
        .sidebar,
        .sidebar-toggle {
            display: none !important;
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
    <!-- Main Content sin Sidebar -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation Simplificada -->
        <header class="top-nav">
            <div class="nav-left">
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="nav-right">
                <!-- Perfil de Usuario -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userBtn">
                        <div class="user-avatar">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                            <span class="user-role">Asesor</span>
                        </div>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
