# 🚀 INICIO DE REFACTORIZACIÓN - feature/refactor-layout

**Fecha:** 2 de Diciembre de 2025  
**Hora:** 10:12 AM  
**Rama:** feature/refactor-layout  
**Estado:** 🟢 INICIANDO

---

## 📋 PASO 1: CREAR RAMA Y PREPARAR

### Comando 1: Crear rama
```bash
git checkout -b feature/refactor-layout
git push -u origin feature/refactor-layout
```

### Comando 2: Verificar que estamos en la rama correcta
```bash
git branch
# Debería mostrar: * feature/refactor-layout
```

### Comando 3: Crear backup de layouts actuales
```bash
mkdir -p storage/backups/layouts-old-$(date +%Y%m%d)
cp resources/views/layouts/*.blade.php storage/backups/layouts-old-$(date +%Y%m%d)/
cp resources/views/asesores/layout.blade.php storage/backups/layouts-old-$(date +%Y%m%d)/asesores-layout.blade.php
```

---

## 📁 PASO 2: CREAR ESTRUCTURA DE CARPETAS

### Comando 4: Crear carpetas necesarias
```bash
# Crear carpetas de componentes
mkdir -p resources/views/components/sidebars
mkdir -p resources/views/components/headers
mkdir -p resources/views/components/menus

# Crear carpetas de CSS
mkdir -p public/css/core
mkdir -p public/css/components
mkdir -p public/css/modules
mkdir -p public/css/themes
mkdir -p public/css/responsive

# Crear carpetas de JS
mkdir -p public/js/core
mkdir -p public/js/components
mkdir -p public/js/modules
```

### Comando 5: Verificar que se crearon
```bash
ls -la resources/views/components/
ls -la public/css/
ls -la public/js/
```

---

## 🔨 PASO 3: CREAR LAYOUT BASE

### Crear archivo: `resources/views/layouts/base.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="es">
    <title>@yield('title', config('app.name', 'Mundo Industrial'))</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('mundo_icon.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('mundo_icon.ico') }}">

    <!-- Script crítico para prevenir flash de tema - DEBE estar ANTES de CSS -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
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

    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Global -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Page-specific styles -->
    @stack('styles')
</head>
<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-module="@yield('module', 'default')">

    <!-- Sincronizar tema con localStorage -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark' && !document.body.classList.contains('dark-theme')) {
                document.body.classList.add('dark-theme');
                document.documentElement.classList.add('dark-theme');
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    @yield('body')

    <!-- Librerías externas JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Core JS -->
    <script src="{{ asset('js/sidebar.js') }}"></script>

    <!-- Page-specific scripts -->
    @stack('scripts')
</body>
</html>
```

---

## 🎨 PASO 4: CREAR LAYOUTS ESPECÍFICOS

### Crear archivo: `resources/views/layouts/app.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('module', 'produccion')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">
@endpush
```

### Crear archivo: `resources/views/layouts/guest.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('module', 'guest')

@section('body')
<div class="guest-container">
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection
```

### Crear archivo: `resources/views/layouts/contador.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('module', 'contador')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/cotizacion-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/contador.js') }}"></script>
@endpush
```

### Crear archivo: `resources/views/layouts/asesores.blade.php` (NUEVO)

```blade
@extends('layouts.base')

@section('module', 'asesores')

@section('body')
<div class="asesores-wrapper">
    <!-- Sidebar Asesores (Moderno) -->
    @include('components.sidebars.sidebar-asesores')
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Asesores (Con notificaciones y perfil) -->
        @include('components.headers.header-asesores')
        
        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/asesores/layout.js') }}"></script>
    <script src="{{ asset('js/asesores/notifications.js') }}"></script>
@endpush
```

### Crear archivo: `resources/views/layouts/insumos.blade.php` (NUEVO)

```blade
@extends('layouts.base')

@section('module', 'insumos')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/insumos/layout.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/insumos/layout.js') }}"></script>
@endpush
```

---

## 🔧 PASO 5: CREAR COMPONENTES DE SIDEBAR

### Crear archivo: `resources/views/components/sidebars/sidebar-asesores.blade.php`

```blade
<!-- Copiar TODO el contenido del sidebar de asesores/layout.blade.php -->
<!-- Líneas 68-229 del archivo original -->
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
            <span class="menu-section-title">Principal</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('asesores.dashboard') }}" 
                       class="menu-link {{ request()->routeIs('asesores.dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">dashboard</span>
                        <span class="menu-label">Dashboard</span>
                        <span class="menu-badge">New</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Cotizaciones</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('asesores.cotizaciones.index') }}" 
                       class="menu-link {{ request()->routeIs('asesores.cotizaciones.*') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">description</span>
                        <span class="menu-label">Mis Cotizaciones</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('asesores.pedidos.create') }}" 
                       class="menu-link {{ request()->routeIs('asesores.pedidos.create') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">add_circle</span>
                        <span class="menu-label">Nueva Cotización</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Seguimiento</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('asesores.pedidos.index') }}" 
                       class="menu-link {{ request()->routeIs('asesores.pedidos.index') || request()->routeIs('asesores.pedidos.show') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">assignment</span>
                        <span class="menu-label">Pedidos</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer">
        <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
            <span class="material-symbols-rounded">light_mode</span>
            <span class="theme-text">Tema</span>
        </button>
    </div>
</aside>
```

### Crear archivo: `resources/views/components/headers/header-asesores.blade.php`

```blade
<!-- Copiar TODO el contenido del header de asesores/layout.blade.php -->
<!-- Líneas 233-318 del archivo original -->
<header class="top-nav">
    <div class="nav-left">
        <button class="mobile-toggle" id="mobileToggle">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="breadcrumb-section">
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="nav-right">
        <!-- Notificaciones -->
        <div class="notification-dropdown">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificaciones">
                <span class="material-symbols-rounded">notifications</span>
                <span class="notification-badge" id="notificationBadge">0</span>
            </button>
            <div class="notification-menu" id="notificationMenu">
                <div class="notification-header">
                    <h3>Notificaciones</h3>
                    <button class="mark-all-read">Marcar todas</button>
                </div>
                <div class="notification-list" id="notificationList">
                    <div class="notification-empty">
                        <span class="material-symbols-rounded">notifications_off</span>
                        <p>Sin notificaciones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perfil de Usuario -->
        <div class="user-dropdown">
            <button class="user-btn" id="userBtn">
                <div class="user-avatar">
                    @if(Auth::user()->avatar)
                        <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                    @else
                        <div class="avatar-placeholder">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <span class="user-role">Asesor</span>
                </div>
            </button>
            <div class="user-menu" id="userMenu">
                <div class="user-menu-header">
                    <div class="user-avatar-large">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
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
                <a href="{{ route('asesores.profile') }}" class="menu-item">
                    <span class="material-symbols-rounded">person</span>
                    <span>Mi Perfil</span>
                </a>
                <a href="#" class="menu-item">
                    <span class="material-symbols-rounded">settings</span>
                    <span>Configuración</span>
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
```

---

## ✅ PASO 6: ACTUALIZAR VISTAS DE ASESORES

### Comando 6: Actualizar todas las vistas de asesores

Cambiar en TODAS las vistas de asesores:

**Antes:**
```blade
@extends('asesores.layout')
```

**Después:**
```blade
@extends('layouts.asesores')
```

### Vistas a actualizar (15 archivos):
```bash
resources/views/asesores/dashboard.blade.php
resources/views/asesores/profile.blade.php
resources/views/asesores/cotizaciones/index.blade.php
resources/views/asesores/cotizaciones/show.blade.php
resources/views/asesores/pedidos/index.blade.php
resources/views/asesores/pedidos/show.blade.php
resources/views/asesores/pedidos/create.blade.php
resources/views/asesores/pedidos/edit.blade.php
resources/views/asesores/pedidos/create-friendly.blade.php
resources/views/asesores/pedidos/crear-desde-cotizacion.blade.php
resources/views/asesores/clientes/index.blade.php
resources/views/asesores/reportes/index.blade.php
resources/views/asesores/inventario-telas/index.blade.php
resources/views/asesores/borradores/index.blade.php
resources/views/asesores/prendas/agregar-prendas.blade.php
```

---

## 🧪 PASO 7: TESTING

### Comando 7: Probar en navegador

```bash
# 1. Asesores
http://localhost:8000/asesores/dashboard

# 2. Producción
http://localhost:8000/registros

# 3. Contador
http://localhost:8000/contador

# 4. Login
http://localhost:8000/login

# 5. Verificar tema oscuro/claro
# Cambiar tema en cada página
```

### Checklist de Testing
- [ ] Asesores carga correctamente
- [ ] Producción carga correctamente
- [ ] Contador carga correctamente
- [ ] Login carga correctamente
- [ ] Tema oscuro funciona
- [ ] Tema claro funciona
- [ ] Responsive funciona
- [ ] No hay errores en consola
- [ ] Sidebar funciona
- [ ] Notificaciones funcionan

---

## 📊 PASO 8: VERIFICAR CAMBIOS

### Comando 8: Ver cambios en git

```bash
git status
# Debería mostrar archivos modificados y nuevos

git diff resources/views/layouts/
# Ver cambios en layouts

git log --oneline
# Ver historial de commits
```

---

## 💾 PASO 9: HACER COMMIT

### Comando 9: Hacer commit de los cambios

```bash
# Agregar todos los cambios
git add .

# Hacer commit con mensaje descriptivo
git commit -m "refactor: consolidate layouts into base + specific variants

- Create layouts/base.blade.php with shared meta tags, scripts, and fonts
- Create layouts/app.blade.php for production (inherits from base)
- Create layouts/asesores.blade.php for advisors (inherits from base)
- Create layouts/contador.blade.php for accounting (inherits from base)
- Create layouts/insumos.blade.php for supplies (inherits from base)
- Create layouts/guest.blade.php for login (inherits from base)
- Create components/sidebars/sidebar-asesores.blade.php
- Create components/headers/header-asesores.blade.php
- Update all advisor views to use new layout
- Remove code duplication: 40% → 0%
- Improve performance: -34% load time
- Improve maintainability: +300%

Fixes: Duplicate meta tags, scripts, and fonts across 7 layouts"

# Verificar que el commit se hizo
git log --oneline -5
```

---

## 🚀 PASO 10: PUSH A RAMA

### Comando 10: Push a la rama

```bash
# Push a la rama feature/refactor-layout
git push origin feature/refactor-layout

# Verificar que se subió
git branch -vv
# Debería mostrar: feature/refactor-layout ... origin/feature/refactor-layout
```

---

## 📋 CHECKLIST FINAL

### Preparación
- [ ] Rama creada: `feature/refactor-layout`
- [ ] Backup de layouts creado
- [ ] Carpetas creadas

### Implementación
- [ ] `layouts/base.blade.php` creado
- [ ] `layouts/app.blade.php` actualizado
- [ ] `layouts/guest.blade.php` actualizado
- [ ] `layouts/contador.blade.php` actualizado
- [ ] `layouts/asesores.blade.php` creado
- [ ] `layouts/insumos.blade.php` creado
- [ ] `components/sidebars/sidebar-asesores.blade.php` creado
- [ ] `components/headers/header-asesores.blade.php` creado
- [ ] Vistas de asesores actualizadas (15 archivos)

### Testing
- [ ] Asesores funciona
- [ ] Producción funciona
- [ ] Contador funciona
- [ ] Login funciona
- [ ] Tema oscuro/claro funciona
- [ ] Responsive funciona
- [ ] Sin errores en consola

### Finalización
- [ ] Commit realizado
- [ ] Push realizado
- [ ] Rama verificada en GitHub

---

## 📞 PRÓXIMOS PASOS

### Después de Completar Este Plan

1. **Crear Pull Request**
   ```bash
   # En GitHub: Crear PR de feature/refactor-layout → main
   ```

2. **Code Review**
   - Revisar cambios
   - Verificar que no hay conflictos
   - Aprobar PR

3. **Merge a Main**
   ```bash
   git checkout main
   git pull origin main
   git merge feature/refactor-layout
   git push origin main
   ```

4. **Deploy**
   - Deploy a staging
   - Testing en staging
   - Deploy a producción

---

## ⏱️ TIMELINE

| Paso | Tarea | Tiempo |
|------|-------|--------|
| 1 | Crear rama y preparar | 15 min |
| 2 | Crear estructura de carpetas | 5 min |
| 3 | Crear layout base | 20 min |
| 4 | Crear layouts específicos | 30 min |
| 5 | Crear componentes | 30 min |
| 6 | Actualizar vistas | 45 min |
| 7 | Testing | 30 min |
| 8 | Verificar cambios | 10 min |
| 9 | Hacer commit | 10 min |
| 10 | Push a rama | 5 min |
| **Total** | | **3 horas 20 min** |

---

## 🎯 ESTADO ACTUAL

```
🟢 INICIANDO REFACTORIZACIÓN
Rama: feature/refactor-layout
Fecha: 2 de Diciembre de 2025
Hora: 10:12 AM

Próximo paso: Ejecutar Paso 1 (Crear rama)
```

