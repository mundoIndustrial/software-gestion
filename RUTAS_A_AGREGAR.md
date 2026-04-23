# 📍 Rutas Que Necesitas Agregar

## En `routes/api.php`

Busca la sección de rutas autenticadas y agrega esto:

```php
// En routes/api.php - dentro de un grupo de rutas autenticadas

Route::middleware('auth:sanctum')->group(function () {
    // ... tus otras rutas ...

    // Sistema de Errores
    Route::post('/errores/registrar', [\App\Http\Controllers\Api\ErrorLogController::class, 'registrar']);
    Route::get('/errores/estadisticas', [\App\Http\Controllers\Api\ErrorLogController::class, 'estadisticas']);
});
```

**O si usas Laravel sin Sanctum:**

```php
Route::middleware('auth')->group(function () {
    // ... tus otras rutas ...

    // Sistema de Errores
    Route::post('/errores/registrar', [\App\Http\Controllers\Api\ErrorLogController::class, 'registrar']);
    Route::get('/errores/estadisticas', [\App\Http\Controllers\Api\ErrorLogController::class, 'estadisticas']);
});
```

---

## En `routes/web.php`

Busca donde están tus rutas admin y agrega esto:

```php
// En routes/web.php - dentro de tus rutas de admin

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // ... tus otras rutas admin ...

    // Sistema de Monitoreo de Errores
    Route::prefix('configuracion/errores')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SystemErrorController::class, 'index'])->name('errores.index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\SystemErrorController::class, 'ver'])->name('errores.ver');
        Route::post('/limpiar', [\App\Http\Controllers\Admin\SystemErrorController::class, 'limpiar'])->name('errores.limpiar');
        Route::get('/exportar', [\App\Http\Controllers\Admin\SystemErrorController::class, 'exportar'])->name('errores.exportar');
    });
});
```

**Nota:** Ajusta el middleware `['auth', 'admin']` según tu aplicación.

---

## Verificar que las rutas se registraron

```bash
php artisan route:list | grep errores
```

Debe mostrar:
```
POST      /api/errores/registrar
GET       /api/errores/estadisticas
GET       /admin/configuracion/errores
GET       /admin/configuracion/errores/{id}
POST      /admin/configuracion/errores/limpiar
GET       /admin/configuracion/errores/exportar
```

---

## Ya Completado Automáticamente ✅

- ✅ Meta tag `<meta name="user-id">` en layout
- ✅ Scripts JS cargados en layout
- ✅ Enlace agregado al menú Administración → Errores del Sistema
- ✅ Tablas y modelos creados
- ✅ Controladores creados
- ✅ Vistas creadas

---

## Lo Único Que Falta

✏️ **Agregar las rutas en `routes/api.php` y `routes/web.php`** ← HAGO ESTO AHORA

Luego ejecutar: `php artisan migrate`
