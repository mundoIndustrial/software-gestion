# 🔧 Integración del Sistema de Errores del Sistema

## Resumen

Se creó un sistema completo de **logging y monitoreo de errores** que:

1. ✅ **Captura automáticamente TODOS los errores** en JavaScript (imagen, validación, red, etc.)
2. ✅ **Envía errores al servidor** para almacenamiento persistente
3. ✅ **Panel visual en admin** bajo "Configuración → Errores del Sistema"
4. ✅ **Exportar a CSV** para análisis
5. ✅ **Filtros por tipo, origen, período**

---

## 📦 Archivos Creados

### Frontend (JavaScript)
```
public/js/modulos/crear-pedido/diagnostico/
├── error-logger-service.js       ← Captura y envía errores
├── diagnostic-panel.js           ← Panel visual interactivo
└── README.md                     ← Documentación
```

### Backend (Laravel)
```
app/
├── Models/
│   └── SystemError.php           ← Modelo para BD
├── Http/Controllers/
│   ├── Api/ErrorLogController.php      ← Endpoints API
│   └── Admin/SystemErrorController.php ← Vista Admin
└── ...

database/
└── migrations/
    └── 2026_04_23_000000_create_system_errors_table.php

resources/views/admin/configuracion/
├── errores-sistema.blade.php    ← Lista de errores
└── error-detalle.blade.php      ← Detalle de un error
```

---

## 🚀 Pasos de Integración

### 1. **Ejecutar Migración**

```bash
php artisan migrate
```

Crea la tabla `system_errors` con campos:
- `tipo` (ERROR_IMAGEN, ERROR_RED, etc.)
- `mensaje` (descripción del error)
- `detalles` (JSON con información extra)
- `origen` ('image-upload', 'api', 'validation', 'client-js')
- `usuario_id` (quién causó el error)
- `pedido_id` (pedido relacionado, si aplica)

### 2. **Registrar Rutas API**

En `routes/api.php`, agregar:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/errores/registrar', [ErrorLogController::class, 'registrar']);
    Route::get('/errores/estadisticas', [ErrorLogController::class, 'estadisticas']);
});
```

### 3. **Registrar Rutas Web (Admin)**

En `routes/web.php`, agregar:

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // ... otras rutas admin
    
    Route::prefix('errores')->group(function () {
        Route::get('/', [SystemErrorController::class, 'index'])->name('errores.index');
        Route::get('/{id}', [SystemErrorController::class, 'ver'])->name('errores.ver');
        Route::post('/limpiar', [SystemErrorController::class, 'limpiar'])->name('errores.limpiar');
        Route::get('/exportar', [SystemErrorController::class, 'exportar'])->name('errores.exportar');
    });
});
```

### 4. **Cargar los Scripts JavaScript**

En `recursos/views/layouts/app.blade.php` (o donde sea que cargues scripts globales):

```blade
<!-- Error Logging System -->
<script src="{{ asset('js/modulos/crear-pedido/diagnostico/error-logger-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/diagnostico/diagnostic-panel.js') }}"></script>
```

### 5. **Agregar Enlace en Admin**

En el menú de admin (sidebar), agregar un enlace a:

```blade
<a href="{{ route('admin.errores.index') }}" class="nav-link">
    <i class="fas fa-exclamation-triangle"></i> Errores del Sistema
</a>
```

---

## 🎯 Cómo Funciona

### Flujo de Captura

```
1. Usuario realiza acción (cargar imagen, guardar pedido, etc.)
   ↓
2. Error ocurre en JavaScript
   ↓
3. error-logger-service.js lo captura automáticamente
   ↓
4. Registra en localStorage (para debugging local)
   ↓
5. Envía al servidor: POST /api/errores/registrar
   ↓
6. SystemErrorController guarda en BD (system_errors)
   ↓
7. Admin puede verlo en: /admin/errores
```

### Desde el Admin

```
Admin accede a: /admin/errores
   ↓
Ve lista de todos los errores (últimas 24h por defecto)
   ↓
Puede filtrar por:
   - Tipo (ERROR_IMAGEN, ERROR_RED, etc.)
   - Origen (image-upload, api, validation)
   - Período (1h, 24h, 3 días, 1 semana)
   - Buscar por tipo/mensaje
   ↓
Ver detalle de un error (usuario, pedido, detalles técnicos)
   ↓
Exportar a CSV para análisis
```

---

## 📊 Tipos de Errores Capturados

| Tipo | Dónde | Descripción |
|------|-------|-------------|
| `ERROR_IMAGEN` | image-management.js | Falla al cargar imagen (tamaño, formato) |
| `ERROR_VALIDACION` | orchestrator.js | Validación falló (cliente vacío, etc.) |
| `ERROR_RED` | save-service.js | Fallo de conexión, timeout, 5xx |
| `ERROR_NO_MANEJADO` | global handler | Error no capturado en código |
| `PROMISE_RECHAZADA` | global handler | Promise rechazada sin catch |
| `EXITO` | orchestrator.js | Operación exitosa (para contexto) |

---

## 💻 Uso desde Código

### Registrar un Error Manualmente

```javascript
ErrorLoggerService.registrarError('MiError', 'Algo salió mal', {
    detalles: 'aqui'
});
```

### Consultar Errores Locales (Dev)

```javascript
// En consola
ErrorLoggerService.obtenerResumen()
// { total: 45, porTipo: {...}, ... }

// Abrir panel
abrirPanelDiagnostico()
```

### Acceder desde API

```bash
# Obtener estadísticas
GET /api/errores/estadisticas?horas=24
```

Respuesta:
```json
{
  "total": 45,
  "por_tipo": {
    "ERROR_IMAGEN": 8,
    "ERROR_RED": 3,
    "ERROR_VALIDACION": 1
  },
  "por_origen": {
    "image-upload": 8,
    "api": 3,
    "validation": 1
  },
  "periodo_horas": 24
}
```

---

## 🔒 Seguridad

- ✅ Los errores solo se registran para usuarios **autenticados**
- ✅ El endpoint `/api/errores/registrar` requiere autenticación
- ✅ La vista admin requiere rol **admin**
- ✅ CSRF token requerido en POST
- ✅ Los datos sensibles se preservan (para debugging real)

---

## 🧹 Mantenimiento

### Limpiar Errores Antiguos

Opción 1: Desde UI Admin
- Botón "Limpiar Antiguos" en `/admin/errores`
- Elimina errores > 72 horas por defecto

Opción 2: Programado (Scheduler)

En `app/Console/Kernel.php`:

```php
$schedule->call(function () {
    SystemError::where('ocurrido_en', '<', now()->subDays(30))->delete();
})->daily();
```

---

## 📈 Análisis de Datos

### Identificar Patrones

```bash
# Ver top errores de imagen (últimas 24h)
SELECT tipo, COUNT(*) as total 
FROM system_errors 
WHERE origen = 'image-upload' 
AND ocurrido_en >= NOW() - INTERVAL 24 HOUR
GROUP BY tipo
ORDER BY total DESC;

# Ver errores por usuario
SELECT usuario_id, COUNT(*) as total 
FROM system_errors 
WHERE ocurrido_en >= NOW() - INTERVAL 7 DAY
GROUP BY usuario_id
ORDER BY total DESC;
```

---

## ✅ Checklist de Integración

- [ ] Ejecutar migración: `php artisan migrate`
- [ ] Registrar rutas API en `routes/api.php`
- [ ] Registrar rutas web en `routes/web.php`
- [ ] Cargar scripts JS en layout global
- [ ] Agregar enlace en menú admin
- [ ] Probar cargando una imagen (debe capturar)
- [ ] Probar viendo `/admin/errores`
- [ ] Probar filtros y exportar CSV
- [ ] Validar que CSRF token está presente

---

## 🐛 Troubleshooting

### No se registran errores

1. Verificar que `error-logger-service.js` está cargado:
   ```javascript
   console.log(window.ErrorLoggerService) // Debe ser un objeto
   ```

2. Verificar que el endpoint está registrado:
   ```bash
   php artisan route:list | grep errores
   ```

3. Verificar que la tabla existe:
   ```bash
   php artisan tinker
   > DB::table('system_errors')->count()
   ```

### Los errores no aparecen en admin

1. Verificar que estás autenticado como admin
2. Verificar que el controlador está bien importado
3. Verificar en Network tab (DevTools) que la request POST se envía
4. Verificar logs de Laravel: `storage/logs/laravel.log`

---

## 🚀 Mejoras Futuras

1. **Dashboard con gráficos** (Chart.js)
   - Tendencias de errores por hora
   - Top 5 errores más frecuentes
   - Tasa de éxito vs fallo

2. **Alertas automáticas**
   - Si > 10 errores en 1 hora → enviar email a admin
   - Si > 50% de fallo → enviar alerta

3. **Integración Slack**
   - Enviar notificación Slack cuando error crítico
   - Canal #errores-produccion

4. **Análisis de causas**
   - Machine learning para agrupar errores similares
   - Sugerir soluciones basadas en patrones

---

**Status:** ✅ Listo para implementar. Sigue el checklist arriba.
