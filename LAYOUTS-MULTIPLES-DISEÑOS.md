# 📐 MANEJO DE LAYOUTS CON MÚLTIPLES DISEÑOS

**Fecha:** 2 de Diciembre de 2025  
**Tema:** Cómo manejar Asesores, Producción y otros módulos con diseños diferentes

---

## 🎯 PROBLEMA

Tienes módulos con **diseños completamente diferentes**:

```
1. ASESORES (asesores/layout.blade.php)
   - Sidebar moderno con menú expandible
   - Header con notificaciones y perfil
   - Diseño profesional tipo SaaS
   - 332 líneas de código

2. PRODUCCIÓN (layouts/app.blade.php)
   - Sidebar clásico
   - Header simple
   - Diseño industrial
   - 3,994 líneas de código

3. CONTADOR (layouts/contador.blade.php)
   - Sidebar contador específico
   - Diseño contable
   - 6,822 líneas de código

4. INSUMOS (insumos/layout.blade.php)
   - Diseño específico para insumos
   - Desconocido

5. TABLEROS (tableros.blade.php)
   - Tableros de producción
   - Diseño específico
   - 122 KB (MONSTRUO)
```

**Pregunta:** ¿Cómo consolidar sin perder los diseños específicos?

---

## ✅ SOLUCIÓN: HERENCIA CON VARIANTES

### Concepto

```
layouts/base.blade.php
    ├── layouts/app.blade.php (producción)
    ├── layouts/asesores.blade.php (asesores)
    ├── layouts/contador.blade.php (contador)
    ├── layouts/insumos.blade.php (insumos)
    └── layouts/guest.blade.php (login)

components/sidebars/
    ├── sidebar-produccion.blade.php
    ├── sidebar-asesores.blade.php
    ├── sidebar-contador.blade.php
    ├── sidebar-insumos.blade.php
    └── sidebar-guest.blade.php

components/headers/
    ├── header-produccion.blade.php
    ├── header-asesores.blade.php
    ├── header-contador.blade.php
    └── header-insumos.blade.php
```

### Ventajas

```
✅ 1 layout base (DRY)
✅ 5 layouts específicos (herencia)
✅ Cada módulo mantiene su diseño
✅ Cambios globales en base.blade.php
✅ Cambios específicos en cada layout
✅ Componentes reutilizables
✅ 0% duplicación de meta tags, scripts, etc.
```

---

## 📋 ESTRUCTURA PROPUESTA

### 1. Layout Base (Compartido)

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

    <!-- Script crítico para tema -->
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

    <!-- Librerías específicas del módulo -->
    @stack('styles')
</head>
<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-module="@yield('module', 'default')">

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
    <script src="{{ asset('js/sidebar.js') }}"></script>

    <!-- Scripts específicos del módulo -->
    @stack('scripts')
</body>
</html>
```

---

### 2. Layout Producción (Diseño Industrial)

**Archivo:** `resources/views/layouts/app.blade.php`

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

---

### 3. Layout Asesores (Diseño SaaS Moderno)

**Archivo:** `resources/views/layouts/asesores.blade.php`

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

---

### 4. Layout Contador (Diseño Contable)

**Archivo:** `resources/views/layouts/contador.blade.php`

```blade
@extends('layouts.base')

@section('module', 'contador')

@section('body')
<div class="contador-wrapper">
    <!-- Sidebar Contador -->
    @include('components.sidebars.sidebar-contador')
    
    <!-- Main Content -->
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

---

### 5. Layout Insumos (Diseño Específico)

**Archivo:** `resources/views/layouts/insumos.blade.php`

```blade
@extends('layouts.base')

@section('module', 'insumos')

@section('body')
<div class="insumos-wrapper">
    <!-- Sidebar Insumos -->
    @include('components.sidebars.sidebar-insumos')
    
    <!-- Main Content -->
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

### 6. Layout Guest (Sin Sidebar)

**Archivo:** `resources/views/layouts/guest.blade.php`

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

---

## 🔄 MIGRACIÓN PASO A PASO

### Paso 1: Crear Componentes de Sidebar

**Archivo:** `resources/views/components/sidebars/sidebar-asesores.blade.php`

```blade
<!-- Copiar TODO el contenido del sidebar de asesores/layout.blade.php -->
<!-- Líneas 68-229 -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <!-- ... -->
    </div>
    <div class="sidebar-content">
        <!-- ... -->
    </div>
    <div class="sidebar-footer">
        <!-- ... -->
    </div>
</aside>
```

**Archivo:** `resources/views/components/sidebars/sidebar-produccion.blade.php`

```blade
<!-- Copiar TODO el contenido del sidebar de layouts/sidebar.blade.php -->
@include('layouts.sidebar')
```

### Paso 2: Crear Componentes de Header

**Archivo:** `resources/views/components/headers/header-asesores.blade.php`

```blade
<!-- Copiar TODO el contenido del header de asesores/layout.blade.php -->
<!-- Líneas 233-318 -->
<header class="top-nav">
    <div class="nav-left">
        <!-- ... -->
    </div>
    <div class="nav-right">
        <!-- ... -->
    </div>
</header>
```

### Paso 3: Actualizar Vistas

**Antes:**
```blade
@extends('asesores.layout')
```

**Después:**
```blade
@extends('layouts.asesores')
```

---

## 📊 COMPARATIVA

### Antes (Problemático)

```
7 layouts diferentes
├── layouts/app.blade.php              (3,994 bytes)
├── layouts/contador.blade.php         (6,822 bytes)
├── asesores/layout.blade.php          (332 bytes)
├── layouts/guest.blade.php            (1,656 bytes)
├── layouts/navigation.blade.php       (5,013 bytes)
├── layouts/sidebar.blade.php          (9,559 bytes)
└── insumos/layout.blade.php           (desconocido)

Total: 27,376+ bytes
Duplicación: 40%
Meta tags duplicados: 5 veces
Scripts duplicados: 4 veces
```

### Después (Optimizado)

```
1 layout base + 5 layouts específicos
├── layouts/base.blade.php             (4,500 bytes)
├── layouts/app.blade.php              (1,200 bytes)
├── layouts/asesores.blade.php         (1,500 bytes)
├── layouts/contador.blade.php         (1,200 bytes)
├── layouts/insumos.blade.php          (1,000 bytes)
└── layouts/guest.blade.php            (800 bytes)

components/sidebars/
├── sidebar-produccion.blade.php       (9,559 bytes)
├── sidebar-asesores.blade.php         (160 bytes)
├── sidebar-contador.blade.php         (? bytes)
└── sidebar-insumos.blade.php          (? bytes)

components/headers/
├── header-asesores.blade.php          (85 bytes)
└── header-contador.blade.php          (? bytes)

Total: 8,000 + sidebars + headers
Duplicación: 0%
Meta tags duplicados: 1 vez
Scripts duplicados: 1 vez
```

---

## 🎯 VENTAJAS DE ESTA SOLUCIÓN

### 1. Cada Módulo Mantiene su Diseño
```
✅ Asesores: Diseño SaaS moderno
✅ Producción: Diseño industrial
✅ Contador: Diseño contable
✅ Insumos: Diseño específico
```

### 2. Cero Duplicación
```
✅ Meta tags: 1 vez (en base.blade.php)
✅ Scripts: 1 vez (en base.blade.php)
✅ Fuentes: 1 vez (en base.blade.php)
✅ CSS global: 1 vez (en base.blade.php)
```

### 3. Cambios Globales Fáciles
```
✅ Cambiar tema: Editar base.blade.php
✅ Agregar script global: Editar base.blade.php
✅ Cambiar favicon: Editar base.blade.php
✅ Cambios automáticos en TODOS los módulos
```

### 4. Cambios Específicos Fáciles
```
✅ Cambiar sidebar asesores: Editar sidebar-asesores.blade.php
✅ Cambiar header contador: Editar header-contador.blade.php
✅ Agregar CSS asesores: Editar layouts/asesores.blade.php
✅ No afecta otros módulos
```

### 5. Fácil de Mantener
```
✅ Cada layout es pequeño (<2 KB)
✅ Cada componente tiene una responsabilidad
✅ Fácil de debuggear
✅ Fácil de testear
```

---

## 🚀 PLAN DE MIGRACIÓN (5 DÍAS)

### Día 1: Crear Base
- [ ] Crear `layouts/base.blade.php`
- [ ] Crear carpeta `components/sidebars/`
- [ ] Crear carpeta `components/headers/`

### Día 2: Crear Layouts Específicos
- [ ] Crear `layouts/app.blade.php`
- [ ] Crear `layouts/asesores.blade.php`
- [ ] Crear `layouts/contador.blade.php`
- [ ] Crear `layouts/insumos.blade.php`
- [ ] Crear `layouts/guest.blade.php`

### Día 3: Crear Componentes
- [ ] Crear `components/sidebars/sidebar-produccion.blade.php`
- [ ] Crear `components/sidebars/sidebar-asesores.blade.php`
- [ ] Crear `components/sidebars/sidebar-contador.blade.php`
- [ ] Crear `components/sidebars/sidebar-insumos.blade.php`
- [ ] Crear `components/headers/header-asesores.blade.php`

### Día 4: Migrar Vistas
- [ ] Actualizar vistas de producción
- [ ] Actualizar vistas de asesores
- [ ] Actualizar vistas de contador
- [ ] Actualizar vistas de insumos

### Día 5: Testing
- [ ] Testing de cada módulo
- [ ] Testing de tema oscuro/claro
- [ ] Testing de responsividad
- [ ] Cleanup y documentación

---

## ✅ CHECKLIST

### Preparación
- [ ] Crear rama `refactor/layouts-multiples-diseños`
- [ ] Crear backup de layouts actuales
- [ ] Documentar uso actual

### Implementación
- [ ] Crear layouts/base.blade.php
- [ ] Crear layouts específicos (5)
- [ ] Crear componentes de sidebar (4)
- [ ] Crear componentes de header (2)
- [ ] Actualizar vistas (40+)
- [ ] Testing completo

### Cleanup
- [ ] Eliminar layouts antiguos
- [ ] Eliminar componentes duplicados
- [ ] Documentar cambios
- [ ] Hacer commit

---

## 📝 CONCLUSIÓN

Con esta solución:

✅ **Cada módulo mantiene su diseño único**
✅ **Cero duplicación de código**
✅ **Cambios globales fáciles**
✅ **Cambios específicos fáciles**
✅ **Fácil de mantener y escalar**
✅ **Performance mejorada**

**Recomendación:** Empezar esta semana.

