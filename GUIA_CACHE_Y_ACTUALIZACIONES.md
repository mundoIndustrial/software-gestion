# Guía: Cache y Actualizaciones en Tiempo Real

## Problema Original

**¿Puede el cache perjudicar las actualizaciones?** ✅ **SÍ**

Si un dato está cacheado por 1 hora y se actualiza, el usuario verá datos **desactualizados hasta que expire el cache**.

---

## Solución Implementada

Se agregó **invalidación de cache automática** cuando hay cambios:

### 1. **Invalidación en Cambios de Pedidos**

Cuando se actualiza un pedido, se invalidan automáticamente estos caches:

```php
cache()->forget("pedido_{$pedido_id}_completo");
cache()->forget("pedido_numero_{$pedido_numero}");
cache()->forget('pedidos_lista');
cache()->forget('pedidos_estados_list'); // ← NUEVO
```

### 2. **Invalidación en Cambios de Estado**

Cuando cambia el estado de un pedido:

```php
cache()->forget('pedidos_estados_list'); // ← Se invalida el cache de estados
```

### 3. **Invalidación al Crear Pedido**

Cuando se crea un nuevo pedido:

```php
cache()->forget('pedidos_lista');
cache()->forget('pedidos_recientes');
cache()->forget('pedidos_estados_list'); // ← Se invalida el cache de estados
```

---

## Datos Cacheados en el Sistema

| Dato | Duración | Invalidación |
|------|----------|--------------|
| **Estados de pedidos** | 1 hora | ✅ Ahora se invalida en cambios |
| **EPPs activos** | 1 hora | ⚠️ No se invalida (ver abajo) |
| **Fotos de pedidos** | 10 minutos | ⚠️ No se invalida (ver abajo) |
| **Control de calidad** | 10 minutos | ⚠️ No se invalida (ver abajo) |

---

## Casos Restantes (Bajo Riesgo)

### EPPs Activos
- **Duración**: 1 hora
- **Problema**: Si se agrega/cambia un EPP, verás datos viejos por 1 hora
- **Frecuencia**: Muy bajo (EPPs cambian raramente)
- **Solución**: Si necesitas urgente, refrescar navegador o borrar manualmente cache

### Fotos de Pedidos
- **Duración**: 10 minutos
- **Problema**: Fotos nuevas pueden tardar 10 min en verse
- **Frecuencia**: Bajo (fotos se suben ocasionalmente)
- **Solución**: Refrescar navegador para forzar recarga

---

## Cómo Verificar que el Cache se Está Invalidando

### En Desarrollo (APP_DEBUG=true)

En los logs, deberías ver:

```
[CrearPedidoHandler] Pedido creado exitosamente
[ActualizarPedidoHandler] Actualizando pedido
[CambiarEstadoPedidoHandler] Estado actualizado
```

Seguido de invalidaciones de cache.

### En Database

La tabla `cache` en la base de datos mostrará entradas siendo eliminadas:

```bash
# Verificar cache en BD
php artisan tinker
>>> Cache::all()  # Ver todo el cache
>>> Cache::forget('pedidos_estados_list')  # Borrar manual si es necesario
```

---

## ¿Qué Pasa Cuando Cambia Algo?

### Escenario 1: Se crea un nuevo estado

**Antes de la fix:**
1. Asesor ve lista de estados (cacheada 1 hora)
2. Admin crea nuevo estado
3. Asesor **sigue viendo estados viejos por 1 hora** ❌

**Después de la fix:**
1. Asesor ve lista de estados
2. Admin crea nuevo estado → **Cache se invalida automáticamente** ✅
3. Próxima vez que asesor carga, ve nuevo estado

### Escenario 2: Se actualiza un pedido

1. Asesor ve pedido cacheado
2. Alguien actualiza el pedido
3. Cache se invalida automáticamente ✅
4. Próxima carga, asesor ve datos nuevos

---

## ¿Cómo Agregar Invalidación de Cache en Nuevo Código?

Si creas un nuevo handler o servicio que modifica datos:

```php
// En tu CommandHandler o servicio
public function handle(Command $command): mixed
{
    // ... hacer cambios ...
    
    // Invalidar cache relevante
    cache()->forget('mi_cache_key');
    cache()->forget('otro_cache_key');
    
    return $resultado;
}
```

**Regla de oro:** Siempre invalida el cache cuando **modificas** datos (CREATE, UPDATE, DELETE).

---

## TTL (Time To Live) Recomendado

| Tipo de Dato | TTL Recomendado | Razón |
|--------------|-----------------|-------|
| **Datos que cambian frecuentemente** | 5-10 minutos | Riesgo bajo de retraso |
| **Datos que cambian ocasionalmente** | 1 hora | Buen balance de performance |
| **Datos casi estáticos** | 24 horas+ | Seguro cachear largo tiempo |
| **Datos críticos** | No cachear | Siempre datos frescos |

---

## Checklist: Al Agregar Cache

- [ ] ¿Es realmente necesario cachear esto?
- [ ] ¿Cuál debería ser el TTL?
- [ ] ¿Dónde debería invalidarse este cache?
- [ ] ¿Hay handlers/servicios que modifican este dato?
- [ ] ¿Está la invalidación implementada?
- [ ] ¿Se probó que se invalida correctamente?

---

## Testing: Cómo Verificar la Invalidación

### Prueba Manual

1. Abre DevTools (F12) → Network
2. Ve al endpoint que devuelve datos cacheados
3. Haz un cambio que debería invalidar el cache
4. Haz la misma petición de nuevo
5. Deberías ver datos frescos

### Prueba Automatizada

```php
// Test en PHPUnit
public function test_cache_invalidates_on_update()
{
    $pedido = Pedido::factory()->create();
    Cache::put('pedidos_estados_list', ['viejo'], 3600);
    
    // Actualizar pedido
    $command = new ActualizarPedidoCommand($pedido->id, [...]);
    $this->handler->handle($command);
    
    // Cache debería estar vacío o nuevo
    $this->assertFalse(Cache::has('pedidos_estados_list'));
}
```

---

## Problemas Conocidos

### ⚠️ EPPs Activos (1 hora)
- Solución futura: Agregar invalidación cuando se agrega/edita EPP
- Impacto: Bajo (EPPs cambian raramente)

### ⚠️ Fotos de Pedidos (10 minutos)
- Solución futura: Usar WebSocket para notificaciones en tiempo real
- Impacto: Bajo (usuario puede refrescar)

---

## Resumen

✅ **Invalidación de cache agregada para:**
- Estados de pedidos
- Lista de pedidos
- Pedidos recientes

⚠️ **Casos pendientes:**
- EPPs activos (bajo riesgo)
- Fotos de pedidos (bajo riesgo)

El usuario ahora verá cambios **prácticamente en tiempo real** en datos críticos.
