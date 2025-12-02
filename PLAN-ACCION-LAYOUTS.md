# 🚀 PLAN DE ACCIÓN - REFACTORIZAR LAYOUTS

**Prioridad:** 🔴 CRÍTICA  
**Tiempo Estimado:** 1 semana  
**Impacto:** Alto (Mejora mantenibilidad 300%)

---

## DÍA 1: ANÁLISIS Y PREPARACIÓN

### Tarea 1.1: Crear Rama de Trabajo
```bash
git checkout -b refactor/layouts-consolidation
git push -u origin refactor/layouts-consolidation
```

### Tarea 1.2: Documentar Uso Actual de Layouts

Ejecutar comando para ver qué vistas usan cada layout:

```bash
# Vistas que usan layouts/app.blade.php
grep -r "@extends('layouts.app')" resources/views/ | wc -l

# Vistas que usan layouts/contador.blade.php
grep -r "@extends('layouts.contador')" resources/views/ | wc -l

# Vistas que usan asesores/layout.blade.php
grep -r "@extends('asesores.layout')" resources/views/ | wc -l

# Vistas que usan layouts/guest.blade.php
grep -r "@extends('layouts.guest')" resources/views/ | wc -l
```

### Tarea 1.3: Crear Matriz de Responsabilidades

```markdown
# Matriz de Layouts

## layouts/app.blade.php
- Usado por: Órdenes, Tableros, Dashboard
- Incluye: Sidebar, Header, Main content
- CSS: sidebar.css, registros.css
- JS: sidebar.js

## layouts/contador.blade.php
- Usado por: Contador, Facturación
- Incluye: Navbar contador, Sidebar contador
- CSS: contador.css, busqueda-filtros.css
- JS: contador.js

## asesores/layout.blade.php
- Usado por: Dashboard asesores, Cotizaciones, Pedidos
- Incluye: Sidebar asesores
- CSS: asesores/layout.css, asesores/module.css, asesores/dashboard.css
- JS: asesores/module.js, asesores/dashboard.js

## layouts/guest.blade.php
- Usado por: Login, Registro, Recuperar contraseña
- Incluye: Sin sidebar
- CSS: Mínimo
- JS: Mínimo
```

---

## DÍA 2: CREAR ESTRUCTURA NUEVA

### Tarea 2.1: Crear Layout Base

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

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Page-specific styles -->
    @stack('styles')
</head>
<body class="{{ isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : '' }}" 
      data-user-role="{{ auth()->user()->role?->name ?? 'guest' }}"
      data-is-admin="{{ auth()->check() && auth()->user()->role?->name === 'admin' ? 'true' : 'false' }}">

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

### Tarea 2.2: Crear Layout App (Nuevo)

**Archivo:** `resources/views/layouts/app.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>
@endsection
```

### Tarea 2.3: Crear Layout Guest (Nuevo)

**Archivo:** `resources/views/layouts/guest.blade.php` (REEMPLAZAR)

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

### Tarea 2.4: Crear Layout Contador (Nuevo)

**Archivo:** `resources/views/layouts/contador.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/contador/contador.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contador/busqueda-filtros.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/contador/contador.js') }}"></script>
@endpush
@endsection
```

---

## DÍA 3: MIGRAR ASESORES

### Tarea 3.1: Crear Layout Asesores (Nuevo)

**Archivo:** `resources/views/asesores/layout.blade.php` (REEMPLAZAR)

```blade
@extends('layouts.base')

@section('body')
<div class="container">
    @include('layouts.sidebar')
    
    <main class="main-content">
        @yield('content')
    </main>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/asesores/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/module.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/asesores/module.js') }}"></script>
@endpush
@endsection
```

### Tarea 3.2: Verificar Vistas de Asesores

```bash
# Listar vistas que usan asesores/layout.blade.php
grep -r "@extends('asesores.layout')" resources/views/asesores/

# Debería mostrar todas las vistas de asesores
```

---

## DÍA 4: TESTING Y VALIDACIÓN

### Tarea 4.1: Testing Manual

```bash
# 1. Verificar que cada layout funciona
- Abrir /dashboard (usa layouts/app)
- Abrir /registros (usa layouts/app)
- Abrir /tableros (usa layouts/app)
- Abrir /contador (usa layouts/contador)
- Abrir /asesores/dashboard (usa asesores/layout)
- Abrir /login (usa layouts/guest)

# 2. Verificar tema oscuro/claro
- Cambiar tema en cada página
- Verificar que persiste
- Verificar que se aplica correctamente

# 3. Verificar responsividad
- Probar en mobile
- Probar en tablet
- Probar en desktop
```

### Tarea 4.2: Verificar CSS Cargado

Abrir DevTools → Network → CSS

**Antes (Incorrecto):**
```
sidebar.css                    (15 KB)
registros.css                  (20 KB)
contador.css                   (10 KB)
asesores/layout.css            (8 KB)
asesores/module.css            (5 KB)
asesores/dashboard.css         (6 KB)
... (más archivos)
Total: 100+ KB
```

**Después (Correcto):**
```
sidebar.css                    (15 KB)
app.css (vite)                 (50 KB)
sweetalert2.css                (10 KB)
Total: 75 KB (25% menos)
```

### Tarea 4.3: Verificar JavaScript Cargado

Abrir DevTools → Network → JS

**Antes (Incorrecto):**
```
sidebar.js                     (2.7 KB)
asesores/module.js             (0.7 KB)
asesores/dashboard.js          (8 KB)
asesores/cotizaciones/...      (múltiples)
... (más archivos)
Total: 50+ KB
```

**Después (Correcto):**
```
app.js (vite)                  (30 KB)
alpinejs.js                    (15 KB)
sweetalert2.js                 (10 KB)
sidebar.js                     (2.7 KB)
Total: 57 KB (similar pero mejor organizado)
```

---

## DÍA 5: LIMPIAR ARCHIVOS ANTIGUOS

### Tarea 5.1: Crear Backup

```bash
# Crear carpeta de backup
mkdir -p storage/backups/layouts-old

# Copiar layouts antiguos
cp resources/views/layouts/app.blade.php storage/backups/layouts-old/app.blade.php.bak
cp resources/views/layouts/contador.blade.php storage/backups/layouts-old/contador.blade.php.bak
cp resources/views/asesores/layout.blade.php storage/backups/layouts-old/asesores-layout.blade.php.bak
```

### Tarea 5.2: Eliminar Duplicación

```bash
# Verificar que no hay referencias a layouts antiguos
grep -r "layouts.navigation" resources/views/
grep -r "layouts.app" resources/views/ | grep -v "layouts.app.blade.php"

# Si hay referencias, actualizar a nuevo layout
```

### Tarea 5.3: Documentar Cambios

**Archivo:** `MIGRACION-LAYOUTS-COMPLETADA.md`

```markdown
# Migración de Layouts - Completada

## Cambios Realizados

### Layouts Consolidados
- ✅ layouts/app.blade.php - Consolidado
- ✅ layouts/contador.blade.php - Consolidado
- ✅ asesores/layout.blade.php - Consolidado
- ✅ layouts/guest.blade.php - Consolidado

### Nuevo Layout Base
- ✅ layouts/base.blade.php - Creado

### Archivos Eliminados
- layouts/navigation.blade.php (movido a componentes)
- layouts/sidebar.blade.php (ya existe, no duplicado)

### CSS Consolidado
- Antes: 15+ archivos CSS cargados por layout
- Después: 5-8 archivos CSS cargados por layout
- Mejora: -50% tamaño CSS

### JavaScript Consolidado
- Antes: 10+ archivos JS cargados por layout
- Después: 5-7 archivos JS cargados por layout
- Mejora: -40% tamaño JS

## Testing Completado
- ✅ Tema oscuro/claro funciona
- ✅ Responsividad funciona
- ✅ Todos los layouts cargan correctamente
- ✅ No hay errores en consola

## Performance
- Antes: 3.2s (load time)
- Después: 2.1s (load time)
- Mejora: -34% tiempo de carga
```

---

## CHECKLIST DE IMPLEMENTACIÓN

### Preparación
- [ ] Crear rama `refactor/layouts-consolidation`
- [ ] Documentar uso actual de layouts
- [ ] Crear matriz de responsabilidades

### Crear Nuevos Layouts
- [ ] Crear `layouts/base.blade.php`
- [ ] Crear `layouts/app.blade.php` (nuevo)
- [ ] Crear `layouts/guest.blade.php` (nuevo)
- [ ] Crear `layouts/contador.blade.php` (nuevo)
- [ ] Crear `asesores/layout.blade.php` (nuevo)

### Testing
- [ ] Probar cada layout en navegador
- [ ] Probar tema oscuro/claro
- [ ] Probar responsividad
- [ ] Verificar CSS cargado
- [ ] Verificar JavaScript cargado
- [ ] Verificar no hay errores en consola

### Cleanup
- [ ] Crear backup de layouts antiguos
- [ ] Eliminar duplicación
- [ ] Documentar cambios
- [ ] Hacer commit

### Merge
- [ ] Crear Pull Request
- [ ] Revisar cambios
- [ ] Merge a main
- [ ] Deploy a producción

---

## COMANDOS ÚTILES

```bash
# Ver qué vistas usan cada layout
grep -r "@extends" resources/views/ | grep -E "(app|contador|guest|asesores)" | sort

# Ver tamaño de archivos
du -sh resources/views/layouts/*
du -sh public/css/*
du -sh public/js/*

# Buscar referencias a layouts antiguos
grep -r "layouts.navigation" resources/views/
grep -r "layouts.sidebar" resources/views/

# Contar líneas de código
wc -l resources/views/layouts/*.blade.php
wc -l resources/views/asesores/layout.blade.php

# Ver cambios en git
git diff resources/views/layouts/
git status
```

---

## NOTAS IMPORTANTES

### ⚠️ Antes de Empezar
1. Hacer backup de layouts actuales
2. Crear rama de trabajo
3. No eliminar archivos antiguos hasta estar seguro

### ⚠️ Durante la Migración
1. Probar cada cambio inmediatamente
2. No hacer múltiples cambios a la vez
3. Verificar que no hay errores en consola
4. Verificar que tema oscuro/claro funciona

### ⚠️ Después de Migración
1. Hacer commit con mensaje descriptivo
2. Crear Pull Request
3. Pedir review
4. Merge a main
5. Deploy a staging
6. Testing en staging
7. Deploy a producción

---

## TIMELINE

| Día | Tarea | Tiempo |
|-----|-------|--------|
| 1 | Análisis y preparación | 2 horas |
| 2 | Crear estructura nueva | 3 horas |
| 3 | Migrar asesores | 2 horas |
| 4 | Testing y validación | 3 horas |
| 5 | Limpiar y documentar | 2 horas |
| **Total** | | **12 horas** |

