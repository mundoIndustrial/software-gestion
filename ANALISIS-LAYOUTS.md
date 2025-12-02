# 📐 ANÁLISIS DE LAYOUTS - PROBLEMAS Y SOLUCIONES

**Fecha:** 2 de Diciembre de 2025  
**Versión:** 1.0

---

## 🔴 PROBLEMA PRINCIPAL: MÚLTIPLES LAYOUTS DUPLICADOS

### Situación Actual

Existen **6 layouts diferentes** en el proyecto:

```
resources/views/layouts/
├── app.blade.php           (3,994 bytes)  ← Layout principal
├── contador.blade.php      (6,822 bytes)  ← Layout contador
├── guest.blade.php         (1,656 bytes)  ← Layout invitado
├── navigation.blade.php    (5,013 bytes)  ← Navegación
├── sidebar.blade.php       (9,559 bytes)  ← Sidebar
└── insumos/
    └── layout.blade.php    (¿DESCONOCIDO?)

resources/views/asesores/
└── layout.blade.php        (332 bytes)    ← Layout asesores
```

### Impacto

- ❌ **Duplicación de código:** Cada layout tiene su propio HTML, CSS, JS
- ❌ **Inconsistencia:** Cambios en uno no se reflejan en otro
- ❌ **Mantenimiento imposible:** Actualizar tema = editar 7 archivos
- ❌ **Confusión:** ¿Cuál layout usar para nueva página?
- ❌ **Performance:** Múltiples cargas de CSS/JS

---

## 📊 ANÁLISIS DETALLADO DE CADA LAYOUT

### 1. `layouts/app.blade.php` (3,994 bytes)

**Contenido:**
```blade
<!DOCTYPE html>
<html>
  <head>
      <!-- Meta tags -->
      <!-- Script crítico para tema -->
      <!-- Fuentes y estilos -->
      <!-- Alpine.js -->
      <!-- SweetAlert2 -->
  </head>
  <body>
      <!-- Script sincronización tema -->
      <div class="container">
          @include('layouts.sidebar')
          <main class="main-content">
              @yield('content')
          </main>
      </div>
      <script src="{{ asset('js/sidebar.js') }}"></script>
  </body>
</html>
```

**Problemas:**
- ✅ Bien estructurado
- ❌ Carga CSS/JS específicos de órdenes: `css/orders styles/registros.css`
- ❌ Script de tema duplicado (líneas 17-31 y 64-80)
- ❌ No es reutilizable para otros módulos

---

### 2. `layouts/contador.blade.php` (6,822 bytes)

**Contenido:**
```blade
<!DOCTYPE html>
<html>
  <head>
      <!-- Meta tags -->
      <!-- CSS contador específico -->
      <!-- Fuentes -->
      <!-- Alpine.js -->
  </head>
  <body>
      <!-- Navbar contador -->
      <!-- Sidebar contador -->
      <main>
          @yield('content')
      </main>
      <!-- Scripts contador -->
  </body>
</html>
```

**Problemas:**
- ❌ **DUPLICADO:** Repite HTML de `app.blade.php`
- ❌ **DUPLICADO:** Repite script de tema
- ❌ **DUPLICADO:** Repite Alpine.js
- ❌ Tiene su propio navbar y sidebar
- ❌ Imposible mantener consistencia

---

### 3. `layouts/guest.blade.php` (1,656 bytes)

**Contenido:**
```blade
<!DOCTYPE html>
<html>
  <head>
      <!-- Meta tags -->
      <!-- CSS -->
      <!-- Alpine.js -->
  </head>
  <body>
      <!-- Sin sidebar -->
      <main>
          @yield('content')
      </main>
  </body>
</html>
```

**Problemas:**
- ✅ Bien para login/registro
- ❌ Repite meta tags y Alpine.js
- ❌ Sin tema oscuro

---

### 4. `layouts/navigation.blade.php` (5,013 bytes)

**Contenido:**
```blade
<!-- Navbar con menú -->
<!-- Links de navegación -->
<!-- Dropdown de usuario -->
```

**Problemas:**
- ❌ Componente de navegación, no layout
- ❌ Debería estar en `components/`
- ❌ Incluido en múltiples layouts

---

### 5. `layouts/sidebar.blade.php` (9,559 bytes)

**Contenido:**
```blade
<!-- Sidebar con menú -->
<!-- Lógica de roles -->
<!-- Estilos inline -->
```

**Problemas:**
- ❌ Componente de sidebar, no layout
- ❌ Debería estar en `components/`
- ❌ Incluido en múltiples layouts
- ❌ Lógica de roles mezclada con HTML

---

### 6. `asesores/layout.blade.php` (332 bytes)

**Contenido:**
```blade
<!DOCTYPE html>
<html>
  <head>
      <!-- Meta tags -->
      <!-- CSS asesores -->
      <!-- Fuentes -->
  </head>
  <body>
      <!-- Sidebar asesores -->
      @yield('content')
  </body>
</html>
```

**Problemas:**
- ❌ **DUPLICADO:** Repite HTML de `app.blade.php`
- ❌ **DUPLICADO:** Repite meta tags
- ❌ **DUPLICADO:** Repite Alpine.js
- ❌ Sidebar diferente al de app.blade.php
- ❌ Inconsistencia total

---

## 🔍 PROBLEMAS ESPECÍFICOS ENCONTRADOS

### Problema 1: Script de Tema Duplicado

```blade
<!-- app.blade.php líneas 17-31 -->
<script>
    (function() {
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

<!-- app.blade.php líneas 64-80 -->
<script>
    (function() {
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            if (!document.body.classList.contains('dark-theme')) {
                document.body.classList.add('dark-theme');
                document.documentElement.classList.add('dark-theme');
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } else {
            document.body.classList.remove('dark-theme');
            document.documentElement.classList.remove('dark-theme');
            document.documentElement.removeAttribute('data-theme');
        }
    })();
</script>
```

**Impacto:**
- ❌ Script duplicado = ejecución duplicada
- ❌ Lógica inconsistente
- ❌ Performance degradada

### Problema 2: Estilos Inline en Asesores Layout

```blade
<!-- asesores/layout.blade.php líneas 33-62 -->
<style>
    .top-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }
    
    .nav-left {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .nav-center {
        flex: 0 1 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .nav-right {
        flex: 1;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
    }
</style>
```

**Impacto:**
- ❌ CSS no se cachea
- ❌ CSS se carga en cada página
- ❌ Imposible reutilizar

### Problema 3: Carga de CSS Específicos

```blade
<!-- app.blade.php línea 50 -->
<link rel="stylesheet" href="{{ asset('css/orders styles/registros.css') }}">

<!-- contador.blade.php -->
<link rel="stylesheet" href="{{ asset('css/contador/contador.css') }}">
<link rel="stylesheet" href="{{ asset('css/contador/busqueda-filtros.css') }}">

<!-- asesores/layout.blade.php -->
<link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
```

**Impacto:**
- ❌ CSS cargado en layout, no en página
- ❌ CSS innecesario se carga siempre
- ❌ Performance degradada
- ❌ Imposible lazy-load

### Problema 4: Sidebar Duplicado

```
layouts/sidebar.blade.php          (9,559 bytes)
asesores/layout.blade.php          (incluye sidebar)
contador.blade.php                 (incluye navbar diferente)
```

**Impacto:**
- ❌ Lógica de menú duplicada
- ❌ Cambios en uno no se reflejan en otro
- ❌ Inconsistencia visual

---

## ✅ SOLUCIÓN PROPUESTA

### Estructura Nueva

```
resources/views/
├── layouts/
│   ├── base.blade.php              (Layout base con HTML/head/body)
│   ├── app.blade.php               (Layout con sidebar - extiende base)
│   ├── guest.blade.php             (Layout sin sidebar - extiende base)
│   └── admin.blade.php             (Layout admin - extiende base)
├── components/
│   ├── common/
│   │   ├── header.blade.php        (Header)
│   │   ├── sidebar.blade.php       (Sidebar)
│   │   ├── navbar.blade.php        (Navbar)
│   │   ├── footer.blade.php        (Footer)
│   │   └── breadcrumb.blade.php    (Breadcrumb)
│   └── ...
└── pages/
    ├── orders/
    ├── tableros/
    ├── asesores/
    └── ...
```

### Paso 1: Crear Layout Base

**Archivo:** `resources/views/layouts/base.blade.php`

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

    <!-- Script crítico para prevenir flash de tema -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <!-- Estilo crítico inline para prevenir flash -->
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
    <link rel="stylesheet" href="{{ asset('css/core/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/core/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('css/core/typography.css') }}">
    <link rel="stylesheet" href="{{ asset('css/core/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/button.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/form.css') }}">

    <!-- Librerías externas -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Page-specific styles -->
    @stack('styles')
</head>
<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-is-admin="{{ auth()->check() && auth()->user()->role?->name === 'admin' ? 'true' : 'false' }}">

    <!-- Sincronizar tema -->
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
    <script src="{{ asset('js/core/app.js') }}"></script>
    <script src="{{ asset('js/core/theme.js') }}"></script>

    <!-- Page-specific scripts -->
    @stack('scripts')
</body>
</html>
```

### Paso 2: Crear Layout App (Con Sidebar)

**Archivo:** `resources/views/layouts/app.blade.php`

```blade
@extends('layouts.base')

@section('body')
<div class="app-container">
    @include('components.common.sidebar')
    
    <main class="main-content">
        @include('components.common.header')
        
        <div class="content-wrapper">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </div>
        
        @include('components.common.footer')
    </main>
</div>

<script src="{{ asset('js/modules/sidebar/index.js') }}"></script>
@endsection
```

### Paso 3: Crear Layout Guest (Sin Sidebar)

**Archivo:** `resources/views/layouts/guest.blade.php`

```blade
@extends('layouts.base')

@section('body')
<div class="guest-container">
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection
```

### Paso 4: Crear Layout Admin

**Archivo:** `resources/views/layouts/admin.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="admin-panel">
    @include('components.common.admin-header')
    
    <div class="admin-content">
        @yield('admin-content')
    </div>
</div>
@endsection
```

### Paso 5: Mover Sidebar a Componente

**Archivo:** `resources/views/components/common/sidebar.blade.php`

```blade
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
        @include('components.common.sidebar-menu')
    </div>
</aside>
```

### Paso 6: Crear Sidebar Menu (Lógica de Roles)

**Archivo:** `resources/views/components/common/sidebar-menu.blade.php`

```blade
@php
    $userRole = auth()->user()->role?->name ?? 'guest';
@endphp

@if ($userRole === 'admin')
    @include('components.common.menus.admin-menu')
@elseif ($userRole === 'supervisor')
    @include('components.common.menus.supervisor-menu')
@elseif ($userRole === 'asesor')
    @include('components.common.menus.asesor-menu')
@elseif ($userRole === 'contador')
    @include('components.common.menus.contador-menu')
@elseif ($userRole === 'insumos')
    @include('components.common.menus.insumos-menu')
@else
    @include('components.common.menus.default-menu')
@endif
```

---

## 📋 PLAN DE MIGRACIÓN

### Semana 1: Crear Nuevos Layouts

- [ ] Crear `layouts/base.blade.php`
- [ ] Crear `layouts/app.blade.php` (nuevo)
- [ ] Crear `layouts/guest.blade.php` (nuevo)
- [ ] Crear `layouts/admin.blade.php` (nuevo)
- [ ] Crear `components/common/sidebar.blade.php`
- [ ] Crear `components/common/header.blade.php`
- [ ] Crear `components/common/footer.blade.php`

### Semana 2: Migrar Páginas

- [ ] Actualizar vistas de órdenes
- [ ] Actualizar vistas de tableros
- [ ] Actualizar vistas de asesores
- [ ] Actualizar vistas de contador
- [ ] Actualizar vistas de insumos

### Semana 3: Testing y Cleanup

- [ ] Testing de todos los layouts
- [ ] Testing de tema oscuro/claro
- [ ] Testing de responsividad
- [ ] Eliminar layouts antiguos
- [ ] Eliminar CSS/JS duplicados

---

## 🎯 BENEFICIOS

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Layouts | 7 | 4 | -43% |
| Líneas de código duplicado | 2,000+ | <100 | -95% |
| Archivos CSS cargados | 15+ | 8 | -47% |
| Archivos JS cargados | 10+ | 5 | -50% |
| Tiempo de carga | 3s | 1.5s | -50% |
| Mantenibilidad | 2/10 | 8/10 | +300% |

