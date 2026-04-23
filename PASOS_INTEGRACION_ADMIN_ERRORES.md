# 📋 Pasos Para Integrar "Errores del Sistema" en el Admin

## Paso 1️⃣: Ejecutar la Migración

```bash
php artisan migrate
```

Esto crea la tabla `system_errors` en tu BD.

**Verificar que funcionó:**
```bash
php artisan tinker
> DB::table('system_errors')->count()
# Debe retornar 0 (tabla vacía)
```

---

## Paso 2️⃣: Agregar Rutas API

En `routes/api.php`, busca la sección de rutas autenticadas y agrega:

```php
// En routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // ... otras rutas existentes ...
    
    // Nuevas rutas para errores
    Route::post('/errores/registrar', [App\Http\Controllers\Api\ErrorLogController::class, 'registrar']);
    Route::get('/errores/estadisticas', [App\Http\Controllers\Api\ErrorLogController::class, 'estadisticas']);
});
```

**Si usas Laravel sin Sanctum (auth tradicional):**
```php
Route::middleware('auth')->group(function () {
    Route::post('/errores/registrar', [App\Http\Controllers\Api\ErrorLogController::class, 'registrar']);
    Route::get('/errores/estadisticas', [App\Http\Controllers\Api\ErrorLogController::class, 'estadisticas']);
});
```

---

## Paso 3️⃣: Agregar Rutas Web (Admin)

En `routes/web.php`, busca donde están las rutas de admin y agrega:

```php
// En routes/web.php

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // ... otras rutas admin existentes ...
    
    // Nuevas rutas para visualizar errores
    Route::prefix('configuracion/errores')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SystemErrorController::class, 'index'])->name('errores.index');
        Route::get('/{id}', [App\Http\Controllers\Admin\SystemErrorController::class, 'ver'])->name('errores.ver');
        Route::post('/limpiar', [App\Http\Controllers\Admin\SystemErrorController::class, 'limpiar'])->name('errores.limpiar');
        Route::get('/exportar', [App\Http\Controllers\Admin\SystemErrorController::class, 'exportar'])->name('errores.exportar');
    });
});
```

**Nota:** Ajusta `['auth', 'admin']` según tu middleware de autenticación.

---

## Paso 4️⃣: Cargar Scripts JavaScript

En tu layout principal (probablemente `resources/views/layouts/app.blade.php`), agregar en la sección `<head>`:

```blade
<!-- Agregar estos scripts (al final del <head> o antes de </body>) -->
<script src="{{ asset('js/modulos/crear-pedido/diagnostico/error-logger-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/diagnostico/diagnostic-panel.js') }}"></script>
```

**Y en la sección donde está el auth (si no existe, agregar):**

```blade
<!-- En <head> -->
@auth
    <meta name="user-id" content="{{ auth()->id() }}">
@endauth
```

---

## Paso 5️⃣: Agregar Meta Tag para Usuario

**IMPORTANTE:** Sin esto, el sistema no capturará quién causó el error.

En `resources/views/layouts/app.blade.php`, en la sección `<head>`, agrega:

```blade
<!DOCTYPE html>
<html>
<head>
    <!-- ... otros metas ... -->
    
    <!-- AGREGAR ESTA LÍNEA -->
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    
    <!-- ... resto del head ... -->
</head>
<body data-pedido-id="{{ request()->route('pedido')?->id ?? '' }}">
    <!-- ... -->
</body>
```

---

## Paso 6️⃣: Agregar Enlace en el Menú Admin

Busca el **menú/sidebar del admin** (probablemente `resources/views/admin/layouts/sidebar.blade.php` o similar).

Agrega esta línea en la sección de "Configuración":

```blade
<!-- En resources/views/admin/layouts/sidebar.blade.php o donde esté el menú -->

<div class="nav-item">
    <a href="{{ route('admin.errores.index') }}" class="nav-link">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Errores del Sistema</span>
    </a>
</div>
```

**O si usas un navbar top:**

```blade
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="navbarErrors" role="button" data-bs-toggle="dropdown">
        <i class="fas fa-exclamation-triangle"></i> Monitoreo
    </a>
    <ul class="dropdown-menu" aria-labelledby="navbarErrors">
        <li>
            <a class="dropdown-item" href="{{ route('admin.errores.index') }}">
                <i class="fas fa-bug"></i> Errores del Sistema
            </a>
        </li>
    </ul>
</li>
```

---

## Paso 7️⃣: Verificar las Rutas

```bash
# Ver todas las rutas disponibles
php artisan route:list | grep errores

# Debe mostrar:
# GET|POST  /api/errores/registrar
# GET       /api/errores/estadisticas
# GET       /admin/configuracion/errores
# GET       /admin/configuracion/errores/{id}
# POST      /admin/configuracion/errores/limpiar
# GET       /admin/configuracion/errores/exportar
```

---

## Paso 8️⃣: Probar que Funciona

1. **Inicia sesión en el admin**
2. **Busca "Errores del Sistema"** en el menú
3. **Haz clic** → Debe mostrar: "No hay errores en este período"
4. **Vuelve a la creación de pedidos**
5. **Intenta cargar una imagen muy grande** (> 3MB)
6. **Espera 2-3 segundos**
7. **Vuelve a /admin/errores**
8. **¡Debe estar ahí!** Con:
   - Tipo: ERROR_IMAGEN
   - Asesor: Tu nombre
   - Pedido: si estabas editando uno
   - Hora: hace unos segundos

---

## 🎯 Estructura de Archivos Creados

```
app/
├── Http/Controllers/
│   ├── Api/
│   │   └── ErrorLogController.php          ← Nuevo
│   └── Admin/
│       └── SystemErrorController.php       ← Nuevo
├── Models/
│   └── SystemError.php                     ← Nuevo

database/
└── migrations/
    └── 2026_04_23_000000_create_system_errors_table.php ← Nuevo

resources/views/admin/configuracion/
├── errores-sistema.blade.php               ← Nuevo
└── error-detalle.blade.php                 ← Nuevo

public/js/modulos/crear-pedido/diagnostico/
├── error-logger-service.js                 ← Nuevo (actualizado)
├── diagnostic-panel.js                     ← Nuevo
└── README.md                               ← Nuevo
```

---

## ⚠️ Checklist Final

- [ ] Ejecuté `php artisan migrate`
- [ ] Agregué rutas API en `routes/api.php`
- [ ] Agregué rutas web en `routes/web.php`
- [ ] Cargué scripts JS en layout principal
- [ ] Agregué meta tag `<meta name="user-id">` en layout
- [ ] Agregué enlace en menú admin
- [ ] Verifiqué que las rutas existen: `php artisan route:list | grep errores`
- [ ] Probé abriendo `/admin/errores` (debe mostrar tabla vacía)
- [ ] Probé generando un error (cargar imagen grande)
- [ ] Verifiqué que el error aparece en `/admin/errores` con asesor y pedido

---

## 🐛 Si No Aparece la Opción en el Menú

### Buscar el archivo del menú

```bash
# Buscar archivos que contienen "Dashboard" o "Admin"
grep -r "Dashboard" resources/views/admin/
grep -r "nav-link" resources/views/admin/
```

### Ejemplos de rutas posibles:

- `resources/views/admin/layouts/sidebar.blade.php`
- `resources/views/admin/layouts/navbar.blade.php`
- `resources/views/admin/index.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/app.blade.php`

### Una vez encontrado, agregar dentro:

```blade
<a href="{{ route('admin.errores.index') }}" class="nav-link">
    <i class="fas fa-exclamation-triangle text-warning"></i>
    <span>Errores del Sistema</span>
</a>
```

---

## 📞 Troubleshooting Rápido

### "Route not found"
→ Verificar que las rutas están en `routes/web.php` o `routes/api.php` correctamente

### "No aparece en el menú"
→ Verificar que agregaste el enlace en el archivo correcto del menú

### "No se registran errores"
→ Verificar que:
1. Los scripts JS están cargados (F12 → Network → hay `.js?`)
2. El meta tag `user-id` existe en el HTML (F12 → Elements → buscar `user-id`)
3. El endpoint `/api/errores/registrar` existe (`php artisan route:list | grep registrar`)

### "Muestra 'Usuario no encontrado'"
→ Agregar meta tag `<meta name="user-id" content="{{ auth()->id() }}">` en layout

---

## ✅ Una Vez Completo

Verás:
```
Admin Panel
├── Configuración
│   └── 🆕 Errores del Sistema  ← NUEVA OPCIÓN
│       ├── Dashboard con estadísticas
│       ├── Tabla de errores (filtrable)
│       ├── Detalle de cada error
│       └── Opción descargar CSV
```

Y cada error mostrará:
- **Tipo**: ERROR_IMAGEN, ERROR_RED, etc.
- **Asesor**: Juan Pérez (juan@empresa.com)
- **Pedido**: #123 (Cliente: Acme Corp)
- **Hora**: Hace 5 minutos
- **Detalles**: Stack trace completo

---

**¿Necesitas que te ayude a copiar los archivos a las ubicaciones correctas?** 🚀
