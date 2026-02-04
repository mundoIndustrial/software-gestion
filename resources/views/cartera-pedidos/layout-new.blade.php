<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cartera de Pedidos') - MundoIndustrial</title>

    <!-- CSS Limpio y Nuevo -->
    <link rel="stylesheet" href="{{ asset('css/cartera-pedidos/styles.css') }}">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @stack('styles')
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="/" class="logo">
                    <span class="logo-icon">🏭</span>
                    <span class="logo-text">MundoIndustrial</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="/cartera-pedidos" class="nav-item active">
                    <span class="nav-icon material-symbols-rounded">shopping_cart</span>
                    <span class="nav-label">Cartera Pedidos</span>
                </a>
                <a href="/dashboard" class="nav-item">
                    <span class="nav-icon material-symbols-rounded">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <button class="sidebar-toggle" id="sidebarToggle" title="Colapsar sidebar">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            <!-- TOP HEADER -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle mobile-only" id="menuToggle">
                        <span class="material-symbols-rounded">menu</span>
                    </button>
                    <h1 class="page-title">@yield('page-title', 'Cartera')</h1>
                </div>

                <div class="header-right">
                    <!-- User Profile -->
                    <div class="user-profile">
                        <button class="user-btn" id="userBtn">
                            <div class="user-avatar">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/supervisores/' . auth()->user()->avatar) }}" alt="Avatar">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <span class="user-name">{{ auth()->user()->name }}</span>
                        </button>

                        <!-- User Menu Dropdown -->
                        <div class="dropdown-menu" id="userMenu">
                            <div class="menu-header">
                                <div class="user-avatar-large">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ asset('storage/supervisores/' . auth()->user()->avatar) }}" alt="Avatar">
                                    @else
                                        <div class="avatar-placeholder">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="menu-name">{{ auth()->user()->name }}</p>
                                    <p class="menu-email">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                            <hr class="menu-divider">
                            <a href="#" class="menu-item">
                                <span class="material-symbols-rounded">person</span>
                                <span>Mi Perfil</span>
                            </a>
                            <hr class="menu-divider">
                            <form method="POST" action="{{ route('logout') }}" style="width: 100%;">
                                @csrf
                                <button type="submit" class="menu-item logout" style="border: none; background: none; cursor: pointer; width: 100%; text-align: left; padding: 0.75rem 1rem;">
                                    <span class="material-symbols-rounded">logout</span>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="{{ asset('js/cartera-pedidos/layout.js') }}"></script>
    @stack('scripts')
</body>
</html>
