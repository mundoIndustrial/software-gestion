<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <title>@yield('title', 'Panel de Control de Calidad') - MundoIndustrial</title>

    <!--
        Importante: estos CSS se cargan en modo "no render-blocking" para que el overlay
        inicial aparezca inmediatamente (evita pantalla blanca al entrar/después de login).
    -->
    <link rel="preload" href="{{ asset('css/operario/layout.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="{{ asset('css/operario/dashboard.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="{{ asset('css/operario/layout.css') }}">
        <link rel="stylesheet" href="{{ asset('css/operario/dashboard.css') }}">
    </noscript>

    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" as="style" crossorigin onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    </noscript>

    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" crossorigin="anonymous" referrerpolicy="no-referrer" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </noscript>

    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" crossorigin onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    @stack('styles')

    <style>
        /* Evita pantalla blanca mientras cargan estilos externos */
        html, body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.3s ease;
            flex-direction: column;
            gap: 30px;
        }

        #loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .notification-dropdown {
            position: relative;
            margin-right: 0.2rem;
            flex-shrink: 0;
        }

        .top-nav-content {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }

        .notification-btn {
            position: relative;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .notification-btn:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.28);
        }

        .notification-btn .material-symbols-rounded {
            font-size: 22px;
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -2px;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .notification-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: min(360px, calc(100vw - 24px));
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(226, 232, 240, 0.9);
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-8px);
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 50;
        }

        .notification-menu.active {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .notification-clear {
            border: none;
            background: transparent;
            color: #2563eb;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }

        .notification-list {
            max-height: 360px;
            overflow-y: auto;
            background: #fff;
        }

        .notification-item {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.3rem;
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }

        .notification-item-title .material-symbols-rounded {
            font-size: 18px;
            color: #2563eb;
        }

        .notification-item-title.reflectivo .material-symbols-rounded {
            color: #119669;
        }

        .notification-item-body {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.45;
        }

        .notification-item-time {
            margin-top: 0.4rem;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .notification-empty {
            padding: 1.4rem 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.82rem;
        }
    </style>
</head>
<body data-module="control-calidad" data-user-role="control-calidad">
    <div id="loading-overlay">
        <div style="position: relative; width: 80px; height: 80px;">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="animation: spin 2s linear infinite;">
                <circle cx="40" cy="40" r="35" stroke="url(#gradient)" stroke-width="4" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3498db;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#2ecc71;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>

        <div style="text-align: center;">
            <p style="margin: 0 0 12px 0; color: #2c3e50; font-size: 32px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.5px;">Cargando</p>
            <p style="margin: 0; color: #7f8c8d; font-size: 18px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Te prometemos que será rápido</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.pointerEvents = 'none';
                overlay.classList.add('hidden');
            }
        });

        if (document.readyState === 'complete') {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.style.pointerEvents = 'none';
                overlay.classList.add('hidden');
            }
        }
    </script>

    <div class="main-content" id="mainContent">
        <header class="top-nav">
            <div class="nav-left">
                <div class="breadcrumb-section">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="top-nav-content">
                <div class="notification-dropdown">
                    <button class="notification-btn" id="notificationBtn" type="button" aria-label="Notificaciones">
                        <span class="material-symbols-rounded">notifications</span>
                        <span class="notification-badge" id="notificationBadge">0</span>
                    </button>
                    <div class="notification-menu" id="notificationMenu">
                        <div class="notification-header">
                            <h3>Notificaciones</h3>
                            <button type="button" class="notification-clear" id="notificationClearBtn">Limpiar</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="notification-empty">Sin notificaciones por ahora</div>
                        </div>
                    </div>
                </div>

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
                            <span class="user-role">{{ Auth::user()->roles()->first()?->name ?? 'Control de Calidad' }}</span>
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
                        <a href="{{ route('control-calidad.dashboard') }}" class="menu-item">
                            <span class="material-symbols-rounded">home</span>
                            <span>Dashboard</span>
                        </a>
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

        <main class="page-content">
            @yield('content')
        </main>
    </div>

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/control-calidad.js') }}" defer></script>
    <script src="{{ asset('js/configuraciones/toast-notifications.js') }}"></script>
    <script>
        window.CONTROL_CALIDAD_USUARIO = {
            id: {{ (int) Auth::id() }},
            nombre: @json(Auth::user()->name),
            rol: @json(Auth::user()->roles()->first()?->name ?? 'control-calidad'),
        };

        (function initControlCalidadNotifications() {
            const storageKey = 'control-calidad-push-items';
            const btn = document.getElementById('notificationBtn');
            const menu = document.getElementById('notificationMenu');
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');
            const clearBtn = document.getElementById('notificationClearBtn');

            if (!btn || !menu || !badge || !list || !clearBtn) {
                return;
            }

            const loadItems = () => {
                try {
                    const raw = localStorage.getItem(storageKey);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            const saveItems = (items) => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(items));
                } catch (error) {
                    console.warn('[Control Calidad] No se pudieron guardar notificaciones', error);
                }
            };

            const setBadgeCount = (count) => {
                const value = Math.max(0, Number(count || 0));
                badge.textContent = value > 99 ? '99+' : String(value);
                badge.style.display = value > 0 ? 'inline-flex' : 'none';
            };

            const renderItems = (items) => {
                if (!items.length) {
                    list.innerHTML = '<div class="notification-empty">Sin notificaciones por ahora</div>';
                    return;
                }

                list.innerHTML = items.map((item) => {
                    const reflectivo = String(item?.tipo_recibo || '').toUpperCase() === 'REFLECTIVO';
                    return `
                        <div class="notification-item">
                            <div class="notification-item-title ${reflectivo ? 'reflectivo' : ''}">
                                <span class="material-symbols-rounded">${item?.icon || 'notifications'}</span>
                                <span>${item?.titulo || 'Notificación'}</span>
                            </div>
                            <div class="notification-item-body">${item?.detalle || ''}</div>
                            <div class="notification-item-time">${item?.fecha || ''}</div>
                        </div>
                    `;
                }).join('');
            };

            btn.addEventListener('click', (event) => {
                event.stopPropagation();
                menu.classList.toggle('active');
                if (menu.classList.contains('active')) {
                    renderItems(loadItems());
                }
            });

            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target) && !btn.contains(event.target)) {
                    menu.classList.remove('active');
                }
            });

            clearBtn.addEventListener('click', () => {
                saveItems([]);
                setBadgeCount(0);
                renderItems([]);
            });

            const currentItems = loadItems();
            setBadgeCount(currentItems.length);
            renderItems(currentItems);

            window.NotificacionesPush = {
                add(payload) {
                    const current = loadItems();
                    const id = String(payload?.id || (Date.now() + '-' + Math.random().toString(16).slice(2)));
                    if (current.some((item) => String(item?.id) === id)) {
                        return;
                    }

                    const next = [
                        {
                            id,
                            titulo: payload?.title || payload?.titulo || 'Notificación',
                            detalle: payload?.message || payload?.detalle || '',
                            fecha: payload?.fecha || new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }),
                            icon: payload?.icon || 'notifications',
                            tipo_recibo: payload?.tipo_recibo || '',
                        },
                        ...current,
                    ].slice(0, 50);

                    saveItems(next);
                    setBadgeCount(next.length);
                    if (menu.classList.contains('active')) {
                        renderItems(next);
                    }
                }
            };
        })();

        // Manejo del menú de usuario
        (function initUserMenu() {
            const userBtn = document.getElementById('userBtn');
            const userMenu = document.getElementById('userMenu');

            if (!userBtn || !userMenu) {
                return;
            }

            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('active');
            });

            document.addEventListener('click', function(event) {
                if (!userMenu.contains(event.target) && !userBtn.contains(event.target)) {
                    userMenu.classList.remove('active');
                }
            });

            userMenu.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
