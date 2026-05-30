@extends('layouts.base')

@section('module', 'visualizador-pedidos')

@section('body')
<div class="visualizador-pedidos-wrapper" style="display: flex; width: 100%; height: 100vh; background: #f8fafc;">
    <!-- Main Content (Sin Sidebar) -->
    <div class="main-content" id="mainContent" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; width: 100%;">
        <!-- Top Navigation -->
        <header class="top-nav" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; background: white; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); flex-shrink: 0;">
            <div class="nav-left" style="flex: 0 0 auto;">
                <div class="breadcrumb-section">
                    <h1 class="page-title" style="font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0;">@yield('page-title', 'Visualizador de Pedidos')</h1>
                </div>
            </div>

            <div class="nav-center" style="flex: 1; display: flex; justify-content: center; align-items: center;">
                <div class="search-container" style="width: 100%; max-width: 400px;">
                    <div class="search-input-wrapper" style="position: relative; display: flex; align-items: center;">
                        <i class="fas fa-search search-icon" style="position: absolute; left: 1rem; color: #64748b; z-index: 2; font-size: 0.9rem;"></i>
                        <input type="text" id="search-input" placeholder="Buscar por número de pedido o cliente..." class="search-input" style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.9rem; background: #f8fafc; transition: all 0.3s ease; outline: none;">
                        <button id="clear-search" class="clear-search-btn" style="position: absolute; right: 0.5rem; background: none; border: none; color: #64748b; cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: all 0.2s ease; display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="nav-right" style="flex: 0 0 auto;">
                <!-- Perfil de Usuario -->
                <div class="user-dropdown" style="position: relative;">
                    <button class="user-btn" id="userBtn" style="display: flex; align-items: center; gap: 0.75rem; background: none; border: none; cursor: pointer; padding: 0.5rem; border-radius: 8px; transition: all 0.2s ease;">
                        <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                            @if(Auth::user()->avatar)
                                <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div class="avatar-placeholder" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 700; font-size: 0.9rem;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="user-info" style="display: flex; flex-direction: column; align-items: flex-start;">
                            <span class="user-name" style="font-size: 0.9rem; font-weight: 600; color: #1e293b;">{{ Auth::user()->name }}</span>
                        </div>
                    </button>
                    <div class="user-menu" id="userMenu" style="position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); min-width: 250px; margin-top: 0.5rem; display: none; z-index: 1000;">
                        <div class="user-menu-header" style="padding: 1rem; display: flex; gap: 1rem; align-items: center;">
                            <div class="user-avatar-large" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                @if(Auth::user()->avatar)
                                    <img src="{{ route('storage.serve', ['path' => 'avatars/' . Auth::user()->avatar]) }}" alt="{{ Auth::user()->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div class="avatar-placeholder" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; font-weight: 700; font-size: 1rem;">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="user-menu-name" style="font-weight: 600; color: #1e293b; margin: 0; font-size: 0.95rem;">{{ Auth::user()->name }}</p>
                                <p class="user-menu-email" style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0;">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        <div class="menu-divider" style="height: 1px; background: #e2e8f0;"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-item logout" style="width: 100%; padding: 0.75rem 1rem; background: none; border: none; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; color: #ef4444; font-size: 0.9rem; transition: all 0.2s ease;">
                                <span class="material-symbols-rounded">logout</span>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content" style="flex: 1; overflow-y: auto; padding: 0; display: flex; align-items: flex-start; justify-content: flex-start; width: 100%;">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('styles')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        /* Ocultar sidebar completamente */
        .sidebar,
        [class*="sidebar"],
        nav[class*="sidebar"],
        aside[class*="sidebar"] {
            display: none !important;
        }

        .search-input:focus {
            border-color: #0ea5e9;
            background: white;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        .search-input::placeholder {
            color: #94a3b8;
        }

        .clear-search-btn:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .user-btn:hover {
            background: #f1f5f9;
        }

        .menu-item:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .menu-item.logout:hover {
            background: #fee2e2;
        }

        .user-dropdown:hover .user-menu,
        .user-menu.active {
            display: block !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('userBtn');
            const userMenu = document.getElementById('userMenu');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                });

                document.addEventListener('click', function() {
                    userMenu.classList.remove('active');
                });
            }
        });
    </script>
@endpush
