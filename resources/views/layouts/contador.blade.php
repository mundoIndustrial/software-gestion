<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mundo Industrial') }} - Contador</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">

    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- CSS del Contador -->
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">

    @stack('styles')

    <style>
        /* Estilos adicionales específicos si es necesario */
    </style>
</head>
<body>
    <div class="contador-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-wrapper">
                    <img src="{{ asset('images/logo2.png') }}" 
                         alt="Logo" 
                         class="header-logo"
                         data-logo-light="{{ asset('images/logo2.png') }}"
                         data-logo-dark="https://prueba.mundoindustrial.co/wp-content/uploads/2024/07/logo-mundo-industrial-white.png" />
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
                    <span class="material-symbols-rounded">chevron_left</span>
                </button>
            </div>

            <div class="sidebar-content">
                <div class="menu-section">
                    <span class="menu-section-title">Gestión</span>
                    <ul class="menu-list" role="navigation">
                        <li class="menu-item">
                            <a href="#" class="menu-link active" data-section="pedidos">
                                <span class="material-symbols-rounded">shopping_cart</span>
                                <span class="menu-label">Pedidos</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="#" class="menu-link" data-section="formatos">
                                <span class="material-symbols-rounded">description</span>
                                <span class="menu-label">Formatos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="user-details">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role">Contador</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <span class="material-symbols-rounded">logout</span>
                        <span>Salir</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="contador-main">
            <!-- HEADER -->
            <header class="contador-header">
                <div class="header-left">
                    <button class="header-icon-btn" id="sidebarToggle" style="display: none;">
                        <span class="material-symbols-rounded">menu</span>
                    </button>
                    <h1 class="header-title" id="pageTitle">Pedidos</h1>
                </div>
                <div class="header-right">
                    <button class="header-icon-btn">
                        <span class="material-symbols-rounded">notifications</span>
                    </button>
                </div>
            </header>

            <!-- CONTENT -->
            <div class="content-wrapper">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- JavaScript del Contador -->
    <script src="{{ asset('js/contador/cotizacion.js') }}"></script>
    <script src="{{ asset('js/contador/contador.js') }}"></script>

    <script>
        // Navegación del sidebar
        document.querySelectorAll('.menu-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                
                // Actualizar nav items
                document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Actualizar título
                const titles = {
                    'pedidos': 'Pedidos',
                    'formatos': 'Formatos de Cotización'
                };
                document.getElementById('pageTitle').textContent = titles[section] || 'Contador';
                
                // Actualizar secciones
                document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active'));
                document.getElementById(section + '-section').classList.add('active');
                
                // Cerrar sidebar en mobile
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('open');
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Toggle sidebar collapse
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>

    @stack('scripts')
</body>
</html>
