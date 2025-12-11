# 🚀 Guía de Queue Worker - Cotizaciones

## ¿Por qué se quedó "pegado"?

**¡Eso es correcto!** El worker está funcionando perfectamente.

El comando `php artisan queue:work --queue=cotizaciones` se mantiene corriendo en background esperando jobs.

```
PS C:\Users\Usuario\Documents\proyecto\v10\mundoindustrial> php artisan queue:work --queue=cotizaciones
[2025-12-11 17:47:00] Processing jobs from the [cotizaciones] queue.
[2025-12-11 17:47:00] Waiting for jobs...
```

**Estado:** ✅ CORRECTO - El worker está esperando jobs

## 🎯 Cómo usar el Worker

### Opción 1: Desarrollo (Terminal dedicada)

**Terminal 1 - Ejecutar el servidor Laravel:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 - Ejecutar el queue worker:**
```bash
php artisan queue:work --queue=cotizaciones
```

El worker se mantiene corriendo y procesa jobs automáticamente.

### Opción 2: Desarrollo (Background con Supervisor)

Para que el worker se ejecute en background incluso si cierras la terminal:

**1. Crear archivo de configuración:**
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

**2. Agregar contenido:**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/mundoindustrial/artisan queue:work --queue=cotizaciones --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/ruta/a/mundoindustrial/storage/logs/worker.log
```

**3. Recargar supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

**4. Verificar estado:**
```bash
sudo supervisorctl status laravel-worker:*
```

## 📊 Flujo Completo de Prueba

### Paso 1: Iniciar el Worker
```bash
# Terminal 1
php artisan queue:work --queue=cotizaciones --verbose
```

Verás:
```
[2025-12-11 17:47:00] Processing jobs from the [cotizaciones] queue.
[2025-12-11 17:47:00] Waiting for jobs...
```

### Paso 2: Crear una cotización (en otra terminal o navegador)
```bash
# Terminal 2 o navegador
POST http://SERVERMI:8000/asesores/cotizaciones/prenda
{
  "cliente": "Test Client",
  "action": "enviar",
  "tipo_venta": "M",
  "prendas": [{"nombre": "Polo"}],
  "especificaciones": {...}
}
```

### Paso 3: Ver el job procesarse en el worker
```
[2025-12-11 17:47:15] Processing: App\Jobs\ProcesarEnvioCotizacionJob
[2025-12-11 17:47:15] 🔵 ProcesarEnvioCotizacionJob - Iniciando procesamiento
[2025-12-11 17:47:15] 🔵 EnviarCotizacionHandler - Iniciando envío
[2025-12-11 17:47:15] 📊 Número de cotización generado: 000001
[2025-12-11 17:47:15] ✅ Cotización enviada exitosamente
[2025-12-11 17:47:15] Processed: App\Jobs\ProcesarEnvioCotizacionJob
```

### Paso 4: Verificar en BD
```bash
mysql> SELECT id, numero_cotizacion, es_borrador, estado FROM cotizaciones WHERE id = 1;
+----+-------------------+-------------+----------+
| id | numero_cotizacion | es_borrador | estado   |
+----+-------------------+-------------+----------+
|  1 | 000001            |           0 | ENVIADA  |
+----+-------------------+-------------+----------+
```

## 🔍 Monitoreo y Debugging

### Ver jobs pendientes
```bash
php artisan queue:failed
```

### Ver logs en tiempo real
```bash
tail -f storage/logs/laravel.log | grep "Cotizacion"
```

### Ejecutar worker en modo verbose
```bash
php artisan queue:work --queue=cotizaciones --verbose
```

### Reintentar jobs fallidos
```bash
php artisan queue:retry all
```

### Limpiar jobs completados
```bash
php artisan queue:flush
```

## 📋 Comandos Útiles

```bash
# Ver estado de jobs
php artisan queue:failed

# Reintentar un job específico
php artisan queue:retry {id}

# Reintentar todos los jobs fallidos
php artisan queue:retry all

# Limpiar la cola
php artisan queue:flush

# Ver jobs en la cola (tabla jobs)
SELECT * FROM jobs;

# Ver jobs fallidos (tabla failed_jobs)
SELECT * FROM failed_jobs;
```

## 🛠️ Troubleshooting

### El worker no procesa jobs
```bash
# Verificar que la tabla jobs existe
php artisan queue:table
php artisan migrate

# Verificar QUEUE_CONNECTION en .env
QUEUE_CONNECTION=database  # ✅ Debe ser database
```

### El worker se detiene
```bash
# Ejecutar con reintentos
php artisan queue:work --queue=cotizaciones --tries=3

# Ejecutar con timeout
php artisan queue:work --queue=cotizaciones --timeout=60
```

### Ver errores detallados
```bash
# Modo verbose
php artisan queue:work --queue=cotizaciones --verbose

# Ver logs
tail -f storage/logs/laravel.log
```

## 📊 Tabla de Jobs

Cuando ejecutas `php artisan queue:table`, se crea la tabla `jobs`:

```sql
CREATE TABLE jobs (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  queue varchar(255) NOT NULL,
  payload longtext NOT NULL,
  attempts tinyint unsigned NOT NULL DEFAULT 0,
  reserved_at bigint unsigned DEFAULT NULL,
  available_at bigint unsigned NOT NULL,
  created_at bigint unsigned NOT NULL,
  PRIMARY KEY (id),
  KEY jobs_queue_index (queue)
);
```

## 🎯 Resumen

| Acción | Comando |
|--------|---------|
| Iniciar worker | `php artisan queue:work --queue=cotizaciones` |
| Ver jobs pendientes | `php artisan queue:failed` |
| Reintentar jobs | `php artisan queue:retry all` |
| Ver logs | `tail -f storage/logs/laravel.log` |
| Limpiar cola | `php artisan queue:flush` |

---

**Estado:** ✅ El worker está funcionando correctamente

El sistema está listo para procesar cotizaciones de forma asincrónica.
