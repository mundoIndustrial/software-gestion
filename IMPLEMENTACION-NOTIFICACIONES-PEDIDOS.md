# ✅ Implementación: Sistema de Notificaciones para Supervisores de Pedidos

## 📋 Resumen de la Implementación

Se ha implementado un sistema completo de notificaciones automáticas que notifica a todos los supervisores de pedidos (`supervisor_pedido`) cuando un asesor crea un nuevo pedido de producción.

---

## 📁 Archivos Creados

### 1. **Notificación**
- **Ruta:** `app/Notifications/PedidoCreado.php`
- **Función:** Define el contenido y estructura de la notificación
- **Canal:** Base de datos (almacenado en tabla `notifications`)
- **Datos incluidos:** ID del pedido, número, cliente, asesor, cantidad de prendas, etc.

### 2. **Evento**
- **Ruta:** `app/Events/PedidoCreado.php`
- **Función:** Evento que se dispara cuando se crea un pedido
- **Datos:** Referencia al pedido y al asesor que lo creó

### 3. **Listener (Oyente)**
- **Ruta:** `app/Listeners/NotificarSupervisoresPedidoCreado.php`
- **Función:** Ejecuta la lógica de notificación
- **Acciones:**
  - Obtiene todos los usuarios con rol `supervisor_pedido`
  - Envía la notificación a cada uno
  - Registra en logs la acción

### 4. **Service Provider**
- **Ruta:** `app/Providers/EventServiceProvider.php`
- **Función:** Registra la relación evento → listener

### 5. **Tests**
- **Ruta:** `tests/Feature/NotificacionesPedidoTest.php`
- **Función:** Tests para validar el funcionamiento del sistema

### 6. **Documentación**
- **Ruta:** `NOTIFICACIONES-PEDIDOS-SUPERVISORES.md`
- **Función:** Documentación completa del sistema

---

## 📝 Archivos Modificados

### 1. **Modelo PedidoProduccion**
- **Ruta:** `app/Models/PedidoProduccion.php`
- **Cambios:**
  - ✅ Importado evento `PedidoCreado`
  - ✅ Agregado observer `created()` en método `boot()` que dispara el evento

**Código agregado:**
```php
// En el método boot()
static::created(function ($model) {
    $asesor = $model->asesora;
    if ($asesor) {
        event(new PedidoCreado($model, $asesor));
    }
});
```

### 2. **SupervisorPedidosController**
- **Ruta:** `app/Http/Controllers/SupervisorPedidosController.php`
- **Cambios:**
  - ✅ Agregado método `getNotifications()` - Obtiene notificaciones del supervisor
  - ✅ Agregado método `markAllNotificationsAsRead()` - Marca todas como leídas
  - ✅ Agregado método `markNotificationAsRead($notificationId)` - Marca una notificación como leída

### 3. **Bootstrap Providers**
- **Ruta:** `bootstrap/providers.php`
- **Cambios:**
  - ✅ Agregado registro de `EventServiceProvider::class`

### 4. **Rutas Web**
- **Ruta:** `routes/web.php`
- **Cambios:** Agregadas 3 rutas nuevas en el grupo `supervisor-pedidos`:
  - `GET /supervisor-pedidos/notificaciones` - Obtener notificaciones (JSON)
  - `POST /supervisor-pedidos/notificaciones/marcar-todas-leidas` - Marcar todas como leídas
  - `POST /supervisor-pedidos/notificaciones/{id}/marcar-leida` - Marcar una como leída

---

## 🔄 Flujo de Funcionamiento

```
┌─────────────────────────────────────────────────────────────┐
│ 1. ASESOR CREA UN PEDIDO                                     │
│    (En PedidosProduccionController::crearDesdeCotizacion)    │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. SE CREA REGISTRO EN BD (PedidoProduccion::create)         │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. SE DISPARA EVENTO "CREATED" (boot del modelo)            │
│    → Se ejecuta: event(new PedidoCreado($model, $asesor))   │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. LARAVEL BUSCA LISTENERS (EventServiceProvider)           │
│    → Encuentra: NotificarSupervisoresPedidoCreado           │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. SE EJECUTA LISTENER (en queue, asincrónico)              │
│    → Obtiene usuarios con rol "supervisor_pedido"           │
│    → Envía notificación a cada uno                          │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. NOTIFICACIONES ALMACENADAS EN BD (tabla: notifications)  │
│    → Campos clave:                                           │
│       • pedido_id                                            │
│       • numero_pedido                                        │
│       • cliente                                              │
│       • asesor_nombre                                        │
│       • cantidad_prendas                                     │
│       • tipo: "pedido_creado"                                │
└─────────────────┬───────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. SUPERVISORES VEN LA NOTIFICACIÓN                          │
│    → En el dashboard/panel de notificaciones                 │
│    → Pueden marcar como leída                                │
│    → Acceder directamente al pedido                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔗 Cómo Usar en la Aplicación

### **1. Mostrar Notificaciones en el Frontend**

```javascript
// Obtener notificaciones (JSON)
fetch('/supervisor-pedidos/notificaciones')
  .then(r => r.json())
  .then(data => {
    console.log('Notificaciones sin leer:', data.notificacionesSinLeer);
    console.log('Total notificaciones:', data.totalNotificaciones);
  });
```

### **2. Marcar Como Leída**

```javascript
// Marcar una notificación específica
fetch('/supervisor-pedidos/notificaciones/{notificationId}/marcar-leida', {
  method: 'POST',
  headers: {'X-CSRF-TOKEN': token}
})
.then(r => r.json());

// Marcar todas como leídas
fetch('/supervisor-pedidos/notificaciones/marcar-todas-leidas', {
  method: 'POST',
  headers: {'X-CSRF-TOKEN': token}
})
.then(r => r.json());
```

### **3. Acceder en Blade**

```blade
<!-- En archivo blade del supervisor -->
@if($user->unreadNotifications->count() > 0)
  <div class="notification-badge">
    {{ $user->unreadNotifications->count() }}
  </div>
@endif

@foreach($user->unreadNotifications as $notification)
  <div class="notification">
    <h5>{{ $notification->data['titulo'] }}</h5>
    <p>{{ $notification->data['mensaje'] }}</p>
    <small>{{ $notification->created_at->diffForHumans() }}</small>
  </div>
@endforeach
```

### **4. En PHP**

```php
$user = auth()->user();

// Obtener notificaciones sin leer
$unread = $user->unreadNotifications;

// Obtener todas las notificaciones
$all = $user->notifications;

// Marcar como leída
$notification = $user->unreadNotifications->first();
$notification->markAsRead();
```

---

## 🛠️ Verificación

Para verificar que el sistema funciona:

1. **Crear un pedido** como asesor
2. **Iniciar sesión** como supervisor
3. **Acceder a:** `/supervisor-pedidos/notificaciones` (JSON)
4. **Verificar logs:** `storage/logs/laravel.log` (buscar "✅ Notificaciones")

---

## 📊 Datos de la Notificación

Cada notificación almacena estos datos:

```json
{
  "pedido_id": 123,
  "numero_pedido": "PED-2024-001",
  "cliente": "Nombre del Cliente",
  "asesor_id": 45,
  "asesor_nombre": "Juan Pérez",
  "cantidad_prendas": 5,
  "titulo": "Nuevo pedido #PED-2024-001 creado",
  "mensaje": "El asesor Juan Pérez ha creado un pedido para Nombre del Cliente",
  "tipo": "pedido_creado"
}
```

---

## ⚙️ Configuración Adicional (Opcional)

### **Si deseas usar Email además de Base de Datos**

Modifica `app/Notifications/PedidoCreado.php`:

```php
public function via(object $notifiable): array
{
    return ['database', 'mail']; // Añade 'mail'
}

public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject("Nuevo Pedido #{$this->pedido->numero_pedido}")
        ->greeting("Hola {$notifiable->name}")
        ->line("El asesor {$this->asesor->name} ha creado un pedido")
        ->action('Ver Pedido', url("/supervisor-pedidos/{$this->pedido->id}"))
        ->line('Gracias por usar nuestro sistema');
}
```

---

## 📚 Archivos de Referencia

- **Documentación completa:** `NOTIFICACIONES-PEDIDOS-SUPERVISORES.md`
- **Tests:** `tests/Feature/NotificacionesPedidoTest.php`
- **Logs:** `storage/logs/laravel.log` (buscar "✅")

---

## ✨ Características

✅ Notificaciones automáticas en base de datos  
✅ Sistema de eventos asincrónico  
✅ Soporte para múltiples supervisores  
✅ API JSON para obtener notificaciones  
✅ Marcar como leída (individual y masivo)  
✅ Logs detallados de cada notificación  
✅ Tests unitarios incluidos  
✅ Documentación completa  

---

**Fecha de implementación:** December 4, 2025  
**Estado:** ✅ COMPLETADO Y FUNCIONAL
