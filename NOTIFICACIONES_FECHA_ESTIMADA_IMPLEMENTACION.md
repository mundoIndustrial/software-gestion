No iniciado (136 registros)
En Ejecución (216 registros)# 📅 Notificaciones de Fecha Estimada de Entrega - Guía de Implementación

## Descripción
Sistema de notificaciones que alerta a los asesores cuando se asigna la **Fecha Estimada de Entrega** en sus pedidos.

## Componentes Implementados

### 1. **Observer: PedidoProduccionObserver** 
   - Ubicación: `app/Observers/PedidoProduccionObserver.php`
   - Detecta cambios en `fecha_estimada_de_entrega`
   - Crea notificación cuando se asigna por primera vez
   - Guarda en tabla `notifications` de Laravel

### 2. **Controlador: AsesoresController**
   - Método: `getNotificaciones()` - Obtiene notificaciones de fecha estimada no leídas
   - Método: `markAllAsRead()` - Marca todas como leídas
   - Método: `markNotificationAsRead($notificationId)` - Marca una específica como leída

### 3. **Notificación: FechaEstimadaAsignada**
   - Ubicación: `app/Notifications/FechaEstimadaAsignada.php`
   - Define estructura de datos de la notificación
   - Canal: `database` (tabla `notifications`)

### 4. **Rutas API**
```php
GET    /asesores/notifications                           # Obtener notificaciones
POST   /asesores/notifications/mark-all-read             # Marcar todas como leídas
POST   /asesores/notifications/{notificationId}/mark-read # Marcar una como leída
```

## Base de Datos

### Tabla `notifications` (Laravel estándar)
```sql
- id (UUID)
- notifiable_type: 'App\Models\User'
- notifiable_id: asesor_id
- type: 'App\Notifications\FechaEstimadaAsignada'
- data: JSON con datos del pedido
- read_at: timestamp (NULL si no leída)
- created_at, updated_at
```

### Datos guardados en JSON
```json
{
  "tipo": "fecha_estimada_asignada",
  "titulo": "📅 Fecha Estimada Asignada",
  "mensaje": "Se asignó la fecha estimada...",
  "pedido_id": 123,
  "numero_pedido": "P-2025-001",
  "cliente": "Cliente XYZ",
  "fecha_estimada": "20/12/2025",
  "usuario_que_genero": "Juan Pérez"
}
```

## Flujo de Funcionamiento

1. **Supervisor/Admin actualiza `dia_de_entrega`** del pedido
   ↓
2. **PedidoProduccion calcula `fecha_estimada_de_entrega`** (automático)
   ↓
3. **PedidoProduccionObserver detecta el cambio**
   ↓
4. **Crea notificación en tabla `notifications`**
   ↓
5. **Asesor recibe la notificación** al acceder a `/asesores/pedidos`

## Integración Frontend

### JavaScript para obtener notificaciones
```javascript
fetch('/asesores/notifications')
  .then(r => r.json())
  .then(data => {
    console.log('Notificaciones:', data.notificaciones_fecha_estimada);
    // Mostrar notificaciones al usuario
  });
```

### Marcar como leída
```javascript
fetch(`/asesores/notifications/${notificationId}/mark-read`, {
  method: 'POST',
  headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
});
```

## Logging

Cada notificación creada genera un log:
```
✅ Notificación de fecha estimada creada
   - pedido_id: 123
   - asesor_id: 5
   - numero_pedido: P-2025-001
   - fecha_estimada: 20/12/2025
   - usuario_que_genero: 2
```

## Consideraciones

✅ **Ventajas del enfoque**
- Usa tabla `notifications` estándar de Laravel (sin redundancia)
- Separación clara de responsabilidades
- Fácil de extender para otros tipos de notificaciones
- Datos estructurados en JSON

⚠️ **Notas importantes**
- Las notificaciones son **POR ASESOR** (no para quien actualizó)
- Solo se notifica si la fecha pasó de NULL a un valor
- Las notificaciones se marcan como leídas en `read_at`

## Testing

```php
// Ver todas las notificaciones de un asesor
$notificaciones = DB::table('notifications')
    ->where('notifiable_id', $asesorId)
    ->where('type', 'App\Notifications\FechaEstimadaAsignada')
    ->get();

// Ver no leídas
$noLeidas = DB::table('notifications')
    ->where('notifiable_id', $asesorId)
    ->whereNull('read_at')
    ->count();
```

---
**Implementado:** 14 de Diciembre, 2025
