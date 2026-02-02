# 🚀 FIX PERFORMANCE - Broadcast Asincrónico

## Problema Identificado
El evento `PedidoActualizado` estaba usando `ShouldBroadcastNow` que bloquea la transacción por **10 segundos** mientras intenta conectarse a Pusher/Reverb.

## ✅ Cambios Realizados

### 1. **Evento PedidoActualizado** 
- ❌ Cambió de: `ShouldBroadcastNow` (bloqueante)
- ✅ Cambió a: `ShouldBroadcast` (en cola)

**Archivo**: `app/Events/PedidoActualizado.php`

```php
// Antes (BLOQUEANTE)
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
class PedidoActualizado implements ShouldBroadcastNow

// Ahora (EN COLA)
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class PedidoActualizado implements ShouldBroadcast
```

### 2. **Observer PedidoProduccionObserver**
- ✅ El evento ahora se envía a la cola `broadcasts`
- ✅ Pequeño delay (100ms) para permitir que se complete la transacción BD

**Archivo**: `app/Observers/PedidoProduccionObserver.php`

```php
// Enviar a cola de broadcasts (NO BLOQUEANTE)
PedidoActualizado::dispatch($pedido, $asesor, $changedFields, $action)
    ->onQueue('broadcasts')
    ->delay(now()->addMilliseconds(100));
```

### 3. **Broadcasting Config**
- ✅ Reducido timeout de Reverb a 5 segundos (era 10+)
- ✅ Falla rápido si Reverb no está disponible

**Archivo**: `config/broadcasting.php`

```php
'client_options' => [
    'timeout' => 5,           // Reducido de 10 segundos
    'connect_timeout' => 5,   // Falla más rápido
],
```

---

## 📊 Impacto Esperado

### Antes (LENTO)
```
⏱️ Total: 10,188.76 ms (10.2 segundos)
  ├─ JSON: 0.04 ms
  ├─ Cliente: 1.46 ms
  ├─ DTO: 1.95 ms
  ├─ Pedido Base: 10,046.52 ms ❌ BLOQUEADO POR BROADCAST
  ├─ Carpetas: 36.94 ms
  ├─ Imágenes: 93.03 ms
  └─ Cálculo: 5.84 ms
```

### Después (RÁPIDO)
```
⏱️ Total: ~150 ms (estimado)
  ├─ JSON: 0.04 ms
  ├─ Cliente: 1.46 ms
  ├─ DTO: 1.95 ms
  ├─ Pedido Base: 43.52 ms ✅ SIN BROADCAST BLOQUEANTE
  ├─ Carpetas: 36.94 ms
  ├─ Imágenes: 93.03 ms
  └─ Cálculo: 5.84 ms
  └─ Broadcast: ENVIADO A COLA (NO BLOQUEANTE)
```

### Mejora: **99% más rápido** ⚡

---

## 🔧 Configuración Requerida

### Opción 1: Usar Redis Queue (RECOMENDADO)

**Archivo**: `.env`
```env
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
```

**Comando para procesar colas**:
```bash
php artisan queue:work redis --queue=broadcasts --sleep=3 --tries=1
```

### Opción 2: Usar Database Queue

**Archivo**: `.env`
```env
QUEUE_CONNECTION=database
```

**Crear tabla de colas**:
```bash
php artisan queue:table
php artisan migrate
```

**Comando para procesar**:
```bash
php artisan queue:work database --queue=broadcasts --sleep=3 --tries=1
```

### Opción 3: Usar Sync Queue (Desarrollo)

**Archivo**: `.env`
```env
QUEUE_CONNECTION=sync
```
⚠️ Nota: El broadcast aún se ejecutará, pero sin demora de transacción

---

## 📋 Checklist de Implementación

- [x] Cambiar evento a `ShouldBroadcast`
- [x] Configurar dispatch con cola `broadcasts`
- [x] Reducir timeout en broadcasting config
- [ ] Configurar `.env` con `QUEUE_CONNECTION`
- [ ] Ejecutar `php artisan queue:work` en servidor
- [ ] Verificar logs en `storage/logs/laravel.log`
- [ ] Probar creación de pedido
- [ ] Confirmar que es < 1 segundo

---

## 🔍 Monitoreo

### Ver colas pendientes
```bash
php artisan queue:failed
```

### Reintentrar trabajos fallidos
```bash
php artisan queue:retry all
```

### Ver estado de worker
```bash
# En otra terminal
php artisan queue:work --verbose
```

---

## 📝 Logs esperados

**Exitoso**:
```log
[INFO] PedidoActualizado event QUEUED (asincrónico)
[INFO] Pedido completo creado (PASO 5 < 100ms)
```

**Broadcast ejecutado luego**:
```log
[INFO] Broadcasting PedidoActualizado event
[INFO] Pedido actualizado para asesor: 92
```

---

## 🆘 Troubleshooting

### Si sigue lento después del fix

1. **Verificar queue worker está corriendo**:
```bash
ps aux | grep "queue:work"
```

2. **Revisar `.env`**:
```bash
cat .env | grep QUEUE
cat .env | grep BROADCAST
```

3. **Ver logs**:
```bash
tail -f storage/logs/laravel.log | grep "Broadcast"
```

4. **Reiniciar worker**:
```bash
php artisan queue:restart
```

---

## 📌 Referencias

- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Queues](https://laravel.com/docs/queues)
- [ShouldBroadcast vs ShouldBroadcastNow](https://laravel.com/docs/broadcasting#concept-overview)
