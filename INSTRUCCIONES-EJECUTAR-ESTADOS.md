# INSTRUCCIONES DE IMPLEMENTACIÓN: ESTADOS COTIZACIONES Y PEDIDOS

## ✅ VERIFICACIÓN PREVIA

Antes de ejecutar, verifica que tienes:
- [ ] Laravel 11 instalado
- [ ] PHP 8.2+
- [ ] Base de datos MySQL/PostgreSQL activa
- [ ] Composer actualizado
- [ ] Queue driver configurado

---

## 🚀 PASOS DE IMPLEMENTACIÓN

### PASO 1: Ejecutar Migraciones

```bash
# En la carpeta raíz del proyecto
php artisan migrate

# Deberías ver:
# Migrating: 2025_12_04_000001_add_estado_to_cotizaciones
# Migrating: 2025_12_04_000002_add_estado_to_pedidos_produccion
# Migrating: 2025_12_04_000003_create_historial_cambios_cotizaciones_table
# Migrating: 2025_12_04_000004_create_historial_cambios_pedidos_table
```

### PASO 2: Configurar Variable de Entorno

En tu archivo `.env`:

```env
# Si quieres usar base de datos como cola (recomendado para desarrollo)
QUEUE_CONNECTION=database

# O si prefieres redis (para producción)
QUEUE_CONNECTION=redis

# Configurar tabla de fallidos
QUEUE_FAILED_TABLE=failed_jobs
```

### PASO 3: Iniciar el Queue Worker

En una terminal separada, inicia el worker:

```bash
# Opción 1: Modo interactivo (ctrl+c para detener)
php artisan queue:work

# Opción 2: Modo daemon (se reinicia automáticamente)
php artisan queue:work --daemon

# Opción 3: Con más details para debugging
php artisan queue:work --verbose

# Opción 4: Procesar solo 1 job y salir (útil para testing)
php artisan queue:work --once
```

### PASO 4: Verificar que Todo Funciona

Abre otra terminal:

```bash
# Ver cola en espera
php artisan queue:failed

# Monitorear trabajos en progreso
php artisan queue:monitor

# Reintentar jobs fallidos
php artisan queue:retry all
```

---

## 📋 ARCHIVOS CREADOS

### 1. Migraciones (4 archivos)
```
database/migrations/
├── 2025_12_04_000001_add_estado_to_cotizaciones.php
├── 2025_12_04_000002_add_estado_to_pedidos_produccion.php
├── 2025_12_04_000003_create_historial_cambios_cotizaciones_table.php
└── 2025_12_04_000004_create_historial_cambios_pedidos_table.php
```

### 2. Enums (2 archivos)
```
app/Enums/
├── EstadoCotizacion.php
└── EstadoPedido.php
```

### 3. Modelos (2 archivos - actualizados)
```
app/Models/
├── HistorialCambiosCotizacion.php (nuevo)
├── HistorialCambiosPedido.php (nuevo)
├── Cotizacion.php (actualizado - agregada relación)
└── PedidoProduccion.php (actualizado - agregada relación)
```

### 4. Servicios (2 archivos)
```
app/Services/
├── CotizacionEstadoService.php
└── PedidoEstadoService.php
```

### 5. Jobs (4 archivos)
```
app/Jobs/
├── EnviarCotizacionAContadorJob.php
├── AsignarNumeroCotizacionJob.php
├── EnviarCotizacionAAprobadorJob.php
└── AsignarNumeroPedidoJob.php
```

### 6. Controllers (2 archivos)
```
app/Http/Controllers/
├── CotizacionEstadoController.php
└── PedidoEstadoController.php
```

### 7. Rutas (actualizado)
```
routes/web.php (agregadas nuevas rutas)
```

---

## 🧪 PRUEBA RÁPIDA

### Opción 1: Desde el Controlador API

```bash
# 1. Obtener ID de una cotización existente en estado BORRADOR
curl -X GET "http://localhost:8000/api/cotizaciones" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 2. Enviar cotización
curl -X POST "http://localhost:8000/cotizaciones/1/enviar" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# 3. Ver el historial
curl -X GET "http://localhost:8000/cotizaciones/1/historial" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Ver el seguimiento
curl -X GET "http://localhost:8000/cotizaciones/1/seguimiento" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Opción 2: Desde Tinker (Interactive Shell)

```bash
php artisan tinker

# Obtener una cotización
$cot = App\Models\Cotizacion::where('estado', 'BORRADOR')->first();

# Crear servicio
$service = new App\Services\CotizacionEstadoService();

# Enviar a contador
$service->enviarACOntador($cot);

# Verificar cambio
$cot->refresh();
echo $cot->estado; // Debe mostrar: ENVIADA_CONTADOR

# Ver historial
$cot->historialCambios()->get();
```

### Opción 3: Usando Postman

1. Importa la colección JSON (crear en Postman)
2. Configura el Bearer Token
3. Ejecuta los requests en orden

---

## 🔍 DEBUGGING

### Ver jobs en cola

```bash
# Con database queue
php artisan tinker
DB::table('jobs')->get();

# Ver estructura
DB::table('jobs')->where('id', 1)->first();
```

### Ver jobs fallidos

```bash
# Listar fallidos
php artisan queue:failed

# Reintentar específico
php artisan queue:retry 1

# Reintentar todos
php artisan queue:retry all

# Ver logs
tail -f storage/logs/laravel.log
```

### Monitorear en tiempo real

```bash
# Ver estado del worker
php artisan queue:monitor

# Ver jobs que están siendo procesados
php artisan queue:work --verbose
```

---

## 🛠️ TROUBLESHOOTING

### "Jobs no se procesan"

**Problema**: Los jobs se quedan en la cola

**Soluciones**:
```bash
# 1. Verificar que el worker está corriendo
ps aux | grep queue:work

# 2. Iniciar worker si no está corriendo
php artisan queue:work &

# 3. Limpiar queue corrupta
php artisan queue:flush

# 4. Ver logs
tail -f storage/logs/laravel.log
```

### "Error: Table 'jobs' doesn't exist"

**Problema**: Tabla jobs no existe

**Solución**:
```bash
# Crear tabla
php artisan queue:table
php artisan migrate
```

### "Número de cotización no se asigna"

**Problema**: Job se ejecuta pero no asigna número

**Causa probable**: El modelo no se recarga del BD en el Job

**Solución**: 
```php
// En el Job, la primera línea debe ser:
$this->cotizacion->refresh();
```

### "Error 403 Forbidden"

**Problema**: Usuario no tiene permisos

**Soluciones**:
```php
// 1. Implementar los Gates en AuthServiceProvider
Gate::define('isContador', function (User $user) {
    return $user->hasRole('contador');
});

// 2. O verificar que el usuario tiene el rol correcto en BD
User::find(1)->hasRole('contador'); // debe ser true
```

---

## 📊 MONITOREO EN PRODUCCIÓN

### Usando Supervisor (Recomendado)

Crear archivo `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/proyecto/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
```

Luego:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Usando Systemd (Alternativa)

Crear `/etc/systemd/system/laravel-queue-worker.service`:

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/proyecto
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Luego:
```bash
sudo systemctl enable laravel-queue-worker
sudo systemctl start laravel-queue-worker
sudo systemctl status laravel-queue-worker
```

---

## 📈 VERIFICACIÓN FINAL

Después de ejecutar migraciones y jobs:

```bash
# 1. Verificar tablas creadas
php artisan tinker
DB::table('cotizaciones')->first();
DB::table('historial_cambios_cotizaciones')->first();
DB::table('pedidos_produccion')->first();
DB::table('historial_cambios_pedidos')->first();

# 2. Verificar Enums cargan correctamente
App\Enums\EstadoCotizacion::BORRADOR->label();
App\Enums\EstadoPedido::PENDIENTE_SUPERVISOR->label();

# 3. Verificar Servicios disponibles
$service = app(App\Services\CotizacionEstadoService::class);
echo $service->obtenerSiguienteNumeroCotizacion();

# 4. Verificar Controllers están registrados
Route::getRoutes()->where('name', 'like', '%cotizaciones%')->get();
```

---

## 🔐 SEGURIDAD

Checklist de seguridad:

- [ ] Implementar Gates para cada rol
- [ ] Validar permisos en cada Controller
- [ ] Encriptar datos sensibles (si aplica)
- [ ] Configurar rate limiting en rutas
- [ ] Validar input de usuario
- [ ] Usar HTTPS en producción
- [ ] Sanitizar logs (no guardar datos sensibles)
- [ ] Configurar CORS si es necesario

---

## 📞 SOPORTE

Si tienes problemas:

1. **Revisa los logs**: `tail -f storage/logs/laravel.log`
2. **Consulta la base de datos**: Verify migrations ran
3. **Verifica servicios**: Worker corriendo, Queue configurado
4. **Debugging**: Usa `php artisan tinker` para probar manualmente
5. **Revisa la documentación**: Ver `IMPLEMENTACION-ESTADOS-COMPLETADA.md`

---

## ✨ NEXT STEPS

Una vez verificado que todo funciona:

1. Implementar Gates/Policies
2. Crear Notificaciones
3. Crear Vistas y Componentes Blade
4. Integrar con frontend (JavaScript/Vue/Alpine)
5. Crear seeders para testing
6. Escribir tests
7. Deploy a staging
8. Deploy a producción con Supervisor

¿Necesitas ayuda con algún paso específico?
