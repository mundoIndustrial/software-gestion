# Sistema de Notificaciones para Creación de Pedidos - Supervisores

## Descripción General

Se ha implementado un sistema de notificaciones automáticas que envía una notificación a todos los usuarios con rol `supervisor_pedido` cada vez que un asesor crea un nuevo pedido de producción.

## Componentes Implementados

### 1. **Evento: `PedidoCreado`** 
   - **Ubicación:** `app/Events/PedidoCreado.php`
   - **Descripción:** Evento que se dispara cuando se crea un nuevo `PedidoProduccion`
   - **Datos:** Contiene referencia al pedido creado y al asesor que lo creó

### 2. **Notificación: `PedidoCreado`**
   - **Ubicación:** `app/Notifications/PedidoCreado.php`
   - **Descripción:** Clase que define el contenido de la notificación
   - **Canal:** `database` (almacenado en base de datos)
   - **Datos incluidos:**
     - ID del pedido
     - Número del pedido
     - Cliente
     - ID y nombre del asesor
     - Cantidad de prendas
     - Título y mensaje descriptivo

### 3. **Listener: `NotificarSupervisoresPedidoCreado`**
   - **Ubicación:** `app/Listeners/NotificarSupervisoresPedidoCreado.php`
   - **Descripción:** Listener que:
     - Obtiene todos los usuarios con rol `supervisor_pedido`
     - Envía la notificación a cada uno
     - Registra en logs la acción realizada
   - **Implementa:** `ShouldQueue` para procesamiento asincrónico

### 4. **Provider: `EventServiceProvider`**
   - **Ubicación:** `app/Providers/EventServiceProvider.php`
   - **Descripción:** Registra la relación entre el evento `PedidoCreado` y el listener

### 5. **Modificaciones al Modelo `PedidoProduccion`**
   - **Ubicación:** `app/Models/PedidoProduccion.php`
   - **Cambios:**
     - Importado evento `PedidoCreado`
     - Agregado observer `created` en el método `boot()` que dispara el evento cuando se crea un pedido

### 6. **Registro del Provider**
   - **Ubicación:** `bootstrap/providers.php`
   - **Cambio:** Se agregó `App\Providers\EventServiceProvider::class` a la lista de providers

## Flujo de Funcionamiento

```
1. Asesor crea un pedido
   ↓
2. Se dispara el evento PedidoCreado (en PedidoProduccion boot)
   ↓
3. EventServiceProvider escucha el evento
   ↓
4. Se ejecuta el listener NotificarSupervisoresPedidoCreado
   ↓
5. Se obtienen todos los usuarios con rol supervisor_pedido
   ↓
6. Se envía notificación PedidoCreado a cada supervisor
   ↓
7. La notificación se almacena en la tabla notifications
```

## Cómo Acceder a las Notificaciones

Las notificaciones se almacenan en la tabla `notifications` de la base de datos. Los supervisores pueden acceder a ellas a través de:

```php
// En el controlador o modelo User
$user->notifications()  // Todas las notificaciones
$user->unreadNotifications()  // Solo las no leídas

// Marcar como leída
$notification->markAsRead();
```

## Rutas Sugeridas

Si deseas mostrar las notificaciones en la interfaz, puedes añadir estas rutas en `routes/web.php`:

```php
// En rutas autenticadas
Route::get('/notificaciones', [AsesorController::class, 'getNotifications'])->name('notifications.index');
Route::post('/notificaciones/marcar-como-leidas', [AsesorController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-as-read');
```

## Vistas Sugeridas

La notificación contiene los siguientes datos accesibles en las vistas:
- `numero_pedido` - El número del pedido
- `cliente` - Nombre del cliente
- `asesor_nombre` - Nombre del asesor que creó el pedido
- `cantidad_prendas` - Total de prendas en el pedido
- `titulo` - Título descriptivo
- `mensaje` - Mensaje completo
- `tipo` - Tipo de notificación (`pedido_creado`)

## Logs

Todos los eventos de notificación se registran en el archivo de logs con el prefijo ✅:

```
[timestamp] local.INFO: ✅ Notificaciones de pedido enviadas a supervisores
```

## Notas Técnicas

- El listener implementa `ShouldQueue` para no bloquear la creación del pedido
- Las notificaciones se almacenan en la base de datos (canal `database`)
- El sistema automáticamente busca usuarios por rol `supervisor_pedido`
- El asesor que creó el pedido se incluye en la notificación para contexto

## Próximas Mejoras (Opcionales)

1. Agregar notificaciones por email además de base de datos
2. Incluir link directo al pedido en la notificación
3. Agregar sonido o toasta cuando una notificación llega
4. Sistema de preferencias de notificaciones por supervisor
