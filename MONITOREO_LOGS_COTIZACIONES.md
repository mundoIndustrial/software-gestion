# 📊 MONITOREO Y LOGS - COTIZACIONES DDD

## 📋 TABLA DE CONTENIDOS

1. [Configuración de Logs](#configuración-de-logs)
2. [Eventos Registrados](#eventos-registrados)
3. [Monitoreo en Producción](#monitoreo-en-producción)
4. [Debugging](#debugging)
5. [Alertas y Notificaciones](#alertas-y-notificaciones)

---

## 🔧 Configuración de Logs

### Archivo de Configuración

**Ubicación:** `config/logging.php`

```php
'channels' => [
    'cotizaciones' => [
        'driver' => 'single',
        'path' => storage_path('logs/cotizaciones.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
]
```

### Usar Canal Específico

```php
Log::channel('cotizaciones')->info('Cotización creada', ['id' => 1]);
```

---

## 📝 Eventos Registrados

### 1. Creación de Cotización

```
[INFO] CrearCotizacionHandler: Iniciando creación
{
    "usuario_id": 1,
    "tipo": "P",
    "cliente": "Acme Corp",
    "es_borrador": true
}

[INFO] CrearCotizacionHandler: Cotización creada exitosamente
{
    "cotizacion_id": 1,
    "numero": null
}
```

### 2. Obtención de Cotización

```
[INFO] ObtenerCotizacionHandler: Obteniendo cotización
{
    "cotizacion_id": 1,
    "usuario_id": 1
}

[INFO] ObtenerCotizacionHandler: Cotización obtenida exitosamente
{
    "cotizacion_id": 1
}
```

### 3. Cambio de Estado

```
[INFO] CambiarEstadoCotizacionHandler: Iniciando cambio de estado
{
    "cotizacion_id": 1,
    "nuevo_estado": "ENVIADA_CONTADOR",
    "usuario_id": 1
}

[INFO] CambiarEstadoCotizacionHandler: Estado cambiado exitosamente
{
    "cotizacion_id": 1,
    "nuevo_estado": "ENVIADA_CONTADOR"
}
```

### 4. Aceptación de Cotización

```
[INFO] AceptarCotizacionHandler: Iniciando aceptación
{
    "cotizacion_id": 1,
    "usuario_id": 1
}

[INFO] AceptarCotizacionHandler: Cotización aceptada exitosamente
{
    "cotizacion_id": 1,
    "eventos": 1
}
```

### 5. Eliminación de Cotización

```
[INFO] EliminarCotizacionHandler: Iniciando eliminación
{
    "cotizacion_id": 1,
    "usuario_id": 1
}

[INFO] EliminarCotizacionHandler: Cotización eliminada exitosamente
{
    "cotizacion_id": 1
}
```

### 6. Errores

```
[ERROR] CrearCotizacionHandler: Error al crear cotización
{
    "error": "El nombre del cliente no puede estar vacío",
    "trace": "..."
}

[ERROR] ObtenerCotizacionHandler: Error al obtener cotización
{
    "error": "Cotización no encontrada",
    "trace": "..."
}

[ERROR] CambiarEstadoCotizacionHandler: Error al cambiar estado
{
    "error": "No se puede transicionar de BORRADOR a ACEPTADA",
    "trace": "..."
}
```

---

## 🚀 Monitoreo en Producción

### 1. Ver Logs en Tiempo Real

```bash
# Todos los logs
tail -f storage/logs/laravel.log

# Solo logs de cotizaciones
tail -f storage/logs/cotizaciones.log

# Últimas 100 líneas
tail -100 storage/logs/laravel.log

# Buscar errores
grep ERROR storage/logs/laravel.log

# Contar eventos
grep "CrearCotizacionHandler" storage/logs/laravel.log | wc -l
```

### 2. Filtrar por Nivel

```bash
# Solo INFO
grep "\[INFO\]" storage/logs/laravel.log

# Solo ERROR
grep "\[ERROR\]" storage/logs/laravel.log

# Solo WARNING
grep "\[WARNING\]" storage/logs/laravel.log
```

### 3. Filtrar por Handler

```bash
# Crear
grep "CrearCotizacionHandler" storage/logs/laravel.log

# Cambiar estado
grep "CambiarEstadoCotizacionHandler" storage/logs/laravel.log

# Aceptar
grep "AceptarCotizacionHandler" storage/logs/laravel.log
```

### 4. Análisis de Rendimiento

```bash
# Contar operaciones por hora
grep "CrearCotizacionHandler" storage/logs/laravel.log | \
    awk '{print $1}' | sort | uniq -c

# Errores por tipo
grep "ERROR" storage/logs/laravel.log | \
    grep -o '"error":"[^"]*"' | sort | uniq -c
```

---

## 🐛 Debugging

### 1. Habilitar Debug Mode

**Archivo:** `.env`

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### 2. Agregar Logs Personalizados

```php
// En un Handler
Log::debug('Estado actual', ['estado' => $cotizacion->estado()->value]);
Log::debug('Prendas', ['cantidad' => count($cotizacion->prendas())]);
Log::debug('Eventos', $cotizacion->eventos());
```

### 3. Usar Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

Acceder a: `http://localhost:8000?debugbar`

### 4. Usar Telescope (Monitoreo Avanzado)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

Acceder a: `http://localhost:8000/telescope`

---

## 🚨 Alertas y Notificaciones

### 1. Errores Críticos

**Configurar notificación por email:**

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'error',
    ],
]
```

### 2. Monitorear Transacciones Fallidas

```php
// En Handler
try {
    // ...
} catch (\Exception $e) {
    Log::error('ALERTA: Error crítico en cotización', [
        'error' => $e->getMessage(),
        'usuario_id' => $comando->usuarioId,
        'cotizacion_id' => $comando->cotizacionId,
    ]);
    
    // Notificar admin
    Notification::route('mail', 'admin@example.com')
        ->notify(new CotizacionErrorNotification($e));
    
    throw $e;
}
```

### 3. Métricas Importantes

```bash
# Cotizaciones creadas hoy
grep "Cotización creada exitosamente" storage/logs/laravel.log | \
    grep "$(date +%Y-%m-%d)" | wc -l

# Cotizaciones aceptadas hoy
grep "Cotización aceptada exitosamente" storage/logs/laravel.log | \
    grep "$(date +%Y-%m-%d)" | wc -l

# Errores hoy
grep "ERROR" storage/logs/laravel.log | \
    grep "$(date +%Y-%m-%d)" | wc -l
```

---

## 📊 Dashboard de Monitoreo

### Script de Monitoreo (Bash)

```bash
#!/bin/bash

echo "=== MONITOREO DE COTIZACIONES ==="
echo ""

echo "📊 Estadísticas de Hoy:"
TODAY=$(date +%Y-%m-%d)

echo "✅ Creadas: $(grep "Cotización creada exitosamente" storage/logs/laravel.log | grep "$TODAY" | wc -l)"
echo "✅ Aceptadas: $(grep "Cotización aceptada exitosamente" storage/logs/laravel.log | grep "$TODAY" | wc -l)"
echo "❌ Errores: $(grep "ERROR" storage/logs/laravel.log | grep "$TODAY" | wc -l)"

echo ""
echo "🔴 Últimos Errores:"
grep "ERROR" storage/logs/laravel.log | tail -5

echo ""
echo "⏰ Última actividad:"
tail -1 storage/logs/laravel.log
```

### Ejecutar Monitoreo

```bash
chmod +x monitor.sh
./monitor.sh
```

---

## 🔍 Checklist de Monitoreo

### Diario

- [ ] Revisar logs de errores
- [ ] Verificar cantidad de cotizaciones creadas
- [ ] Verificar transiciones de estado
- [ ] Revisar tiempos de respuesta

### Semanal

- [ ] Analizar tendencias de uso
- [ ] Revisar errores recurrentes
- [ ] Optimizar queries lentas
- [ ] Actualizar alertas

### Mensual

- [ ] Revisar métricas generales
- [ ] Planificar mejoras
- [ ] Revisar seguridad
- [ ] Actualizar documentación

---

## 📈 Métricas Clave

| Métrica | Objetivo | Alerta |
|---------|----------|--------|
| Tiempo respuesta | < 200ms | > 500ms |
| Errores por día | < 5 | > 20 |
| Disponibilidad | > 99.9% | < 99% |
| Cotizaciones/día | Variable | Anomalía |

---

**Última actualización:** 10 de Diciembre de 2025
**Estado:** ✅ Listo para producción
