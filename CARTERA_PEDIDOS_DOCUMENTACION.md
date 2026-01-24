# CARTERA PEDIDOS - DOCUMENTACIÓN Y ESPECIFICACIÓN

##  Descripción General

La vista `cartera_pedidos.blade.php` es una interfaz para que los usuarios con rol "Cartera" aprueben o rechacen pedidos en estado "Pendiente cartera". 

Está diseñada para ser modular, limpia y siguiendo las mismas convenciones que `supervisor_pedidos.blade.php`.

---

## 📁 Estructura de Archivos Creados

```
resources/views/cartera-pedidos/
  └── cartera_pedidos.blade.php          # Vista principal

public/css/cartera-pedidos/
  └── cartera_pedidos.css                # Estilos (sin dependencias externas)

public/js/cartera-pedidos/
  └── cartera_pedidos.js                 # Lógica JavaScript (vanilla)
```

---

## 🎨 Características Frontend

### 1. **Tabla de Pedidos**
- Columnas: # Pedido, Cliente, Estado, Fecha, Acciones
- Estilo consistente con supervisor_pedidos.blade.php
- Carga dinámica desde API
- Indicador de carga con spinner
- Estado vacío si no hay pedidos

### 2. **Botones de Acción**
- **Aprobar**: Abre modal de confirmación con resumen del pedido
- **Rechazar**: Abre modal para ingresar motivo del rechazo

### 3. **Modales**
- Modal de Aprobación: Confirmación simple con datos del pedido
- Modal de Rechazo: Textarea con contador de caracteres (máx. 1000)
- Ambos con validaciones de cliente

### 4. **Notificaciones**
- Toast notifications (éxito, error, info, advertencia)
- Auto-desaparición después de 5 segundos
- Posicionadas en top-right

### 5. **Funcionalidades JavaScript**
- Carga automática de pedidos al iniciar
- Auto-refresh cada 5 minutos
- Manejo completo de errores
- Contadores de caracteres en textareas
- Cierre de modales con ESC o clic en overlay

---

## 🔌 ENDPOINTS REQUERIDOS EN BACKEND

### 1. **Obtener Pedidos (GET)**

```
GET /api/pedidos?estado=pendiente_cartera
```

**Headers Requeridos:**
- `Accept: application/json`
- `X-CSRF-TOKEN: {token}`

**Respuesta (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "numero_pedido": "PED-2024-001",
      "cliente": "Cliente ABC",
      "estado": "Pendiente cartera",
      "fecha_de_creacion_de_orden": "2024-01-20T10:30:00",
      "asesora": {
        "id": 5,
        "name": "María García"
      },
      "forma_de_pago": "Crédito",
      "fecha_estimada_de_entrega": "2024-02-01"
    },
    {
      "id": 2,
      "numero_pedido": "PED-2024-002",
      "cliente": "Cliente XYZ",
      "estado": "Pendiente cartera",
      "fecha_de_creacion_de_orden": "2024-01-21T14:15:00",
      "asesora": {
        "id": 6,
        "name": "Laura Martínez"
      },
      "forma_de_pago": "Contado",
      "fecha_estimada_de_entrega": "2024-02-05"
    }
  ],
  "total": 2,
  "message": "Pedidos obtenidos correctamente"
}
```

**Respuesta (400 Bad Request):**
```json
{
  "message": "Error al obtener los pedidos",
  "error": "Estado no válido"
}
```

---

### 2. **Aprobar Pedido (POST)**

```
POST /api/pedidos/{id}/aprobar
```

**Headers Requeridos:**
- `Accept: application/json`
- `X-CSRF-TOKEN: {token}`
- `Content-Type: application/json`

**Body:**
```json
{
  "pedido_id": 1,
  "accion": "aprobar"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "Pedido aprobado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "estado": "Aprobado por Cartera",
    "aprobado_por_cartera_en": "2024-01-23T10:45:00",
    "aprobado_por_usuario": {
      "id": 10,
      "name": "Supervisor Cartera"
    }
  },
  "success": true
}
```

**Respuesta (404 Not Found):**
```json
{
  "message": "Pedido no encontrado",
  "error": "El pedido con ID 1 no existe"
}
```

**Respuesta (403 Forbidden):**
```json
{
  "message": "No tienes permiso para aprobar este pedido",
  "error": "Usuario no tiene rol 'cartera'"
}
```

---

### 3. **Rechazar Pedido (POST)**

```
POST /api/pedidos/{id}/rechazar
```

**Headers Requeridos:**
- `Accept: application/json`
- `X-CSRF-TOKEN: {token}`
- `Content-Type: application/json`

**Body:**
```json
{
  "pedido_id": 1,
  "motivo": "Crédito vencido. El cliente tiene deudas pendientes con el plazo vencido.",
  "accion": "rechazar"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "Pedido rechazado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "estado": "Rechazado por Cartera",
    "rechazado_por_cartera_en": "2024-01-23T10:50:00",
    "rechazado_por_usuario": {
      "id": 10,
      "name": "Supervisor Cartera"
    },
    "motivo_rechazo": "Crédito vencido. El cliente tiene deudas pendientes con el plazo vencido.",
    "notificacion_enviada": true
  },
  "success": true
}
```

**Respuesta (422 Unprocessable Entity):**
```json
{
  "message": "Validación fallida",
  "errors": {
    "motivo": ["El motivo es requerido y debe tener al menos 10 caracteres"]
  }
}
```

**Respuesta (400 Bad Request):**
```json
{
  "message": "Error al rechazar el pedido",
  "error": "El pedido ya ha sido procesado"
}
```

---

## 📊 Estructura de Datos Esperada

### Objeto Pedido (desde API)

```typescript
interface Pedido {
  id: number;
  numero_pedido: string;           // Ej: "PED-2024-001"
  cliente: string;                  // Nombre del cliente
  estado: string;                   // "Pendiente cartera"
  fecha_de_creacion_de_orden: string | Date;  // ISO 8601 o Date
  asesora?: {
    id: number;
    name: string;
  };
  forma_de_pago?: string;          // "Crédito" o "Contado"
  fecha_estimada_de_entrega?: string | Date;
  
  // Campos alternativos soportados
  numero?: string;                 // Alternativa a numero_pedido
  nombre_cliente?: string;         // Alternativa a cliente
  fecha_creacion?: string | Date;  // Alternativa a fecha_de_creacion_de_orden
}
```

### Request de Aprobación

```typescript
interface AprobacionRequest {
  pedido_id: number;
  accion: string;  // "aprobar"
}
```

### Request de Rechazo

```typescript
interface RechazoRequest {
  pedido_id: number;
  motivo: string;           // Min 10 chars, Max 1000 chars
  accion: string;           // "rechazar"
}
```

---

## 🛣️ Rutas Recomendadas en Laravel

### Web Routes
```php
// En routes/web.php
Route::middleware(['auth', 'role:cartera,admin'])->group(function () {
    Route::get('/cartera/pedidos', [CarteraPedidosController::class, 'index'])
        ->name('cartera.pedidos.index');
});
```

### API Routes
```php
// En routes/api.php
Route::middleware(['auth:sanctum', 'role:cartera,admin'])->group(function () {
    // GET - Listar pedidos por estado
    Route::get('/pedidos', [PedidoController::class, 'index']);
    
    // POST - Aprobar pedido
    Route::post('/pedidos/{id}/aprobar', [PedidoController::class, 'aprobar']);
    
    // POST - Rechazar pedido
    Route::post('/pedidos/{id}/rechazar', [PedidoController::class, 'rechazar']);
});
```

---

## 🔐 Consideraciones de Seguridad

1. **Validación en Backend:**
   - Verificar que el usuario tiene rol 'cartera' o 'admin'
   - Validar que el pedido existe y está en estado "Pendiente cartera"
   - Validar que el motivo de rechazo tiene entre 10 y 1000 caracteres
   - Usar validación CSRF token en todas las requests POST

2. **Auditoría:**
   - Registrar quién aprobó/rechazó y cuándo
   - Guardar el motivo del rechazo en la base de datos
   - Generar historial de cambios en el estado del pedido

3. **Notificaciones:**
   - Notificar al cliente cuando su pedido es rechazado
   - Notificar al asesor cuando el pedido es aprobado

---

## 🧪 Datos de Prueba

Para probar sin backend, puedes simular la respuesta de la API editando `cartera_pedidos.js`:

```javascript
// Agregar en la función cargarPedidos() para mock data:
const mockData = {
  data: [
    {
      id: 1,
      numero_pedido: 'PED-2024-001',
      cliente: 'Cliente ABC',
      estado: 'Pendiente cartera',
      fecha_de_creacion_de_orden: new Date(2024, 0, 20),
      asesora: { id: 5, name: 'María García' },
      forma_de_pago: 'Crédito',
      fecha_estimada_de_entrega: new Date(2024, 1, 1)
    },
    {
      id: 2,
      numero_pedido: 'PED-2024-002',
      cliente: 'Cliente XYZ',
      estado: 'Pendiente cartera',
      fecha_de_creacion_de_orden: new Date(2024, 0, 21),
      asesora: { id: 6, name: 'Laura Martínez' },
      forma_de_pago: 'Contado',
      fecha_estimada_de_entrega: new Date(2024, 1, 5)
    }
  ]
};

pedidosData = mockData.data;
renderizarTabla(pedidosData);
```

---

## 📱 Responsiveness

La vista es totalmente responsive:
- **Desktop**: Tabla completa con todas las columnas
- **Tablet**: Tabla con scroll horizontal
- **Mobile**: Modales ocupan 95% del ancho, botones stacked

---

## 🔄 Flujo de Uso

```
1. Usuario accede a /cartera/pedidos
   ↓
2. cargarPedidos() → GET /api/pedidos?estado=pendiente_cartera
   ↓
3. renderizarTabla() muestra los pedidos
   ↓
4. Usuario hace clic en Aprobar o Rechazar
   ↓
5. Se abre el modal correspondiente
   ↓
6. Usuario confirma la acción
   ↓
7. Se envía POST a /api/pedidos/{id}/aprobar o /rechazar
   ↓
8. Se muestra notificación (éxito/error)
   ↓
9. Se recarga la tabla automáticamente
```

---

## Puntos Clave

 **Implementado:**
- Interfaz completa y responsiva
- Tabla con carga dinámica
- Dos modales (Aprobación y Rechazo)
- Validaciones en cliente
- Manejo de errores
- Toast notifications
- Auto-refresh cada 5 minutos
- Soporte para múltiples formatos de datos

⚠️ **Por Implementar en Backend:**
- Endpoint GET /api/pedidos?estado=pendiente_cartera
- Endpoint POST /api/pedidos/{id}/aprobar
- Endpoint POST /api/pedidos/{id}/rechazar
- Lógica de actualización del estado del pedido
- Auditoría y notificaciones

---

## 📞 Soporte

Para preguntas o problemas:
1. Revisa la consola del navegador (F12) para mensajes de log
2. Verifica que la API está respondiendo correctamente
3. Confirma que el token CSRF está siendo enviado
4. Verifica permisos del rol 'cartera' en la base de datos

---

**Última actualización:** 23 de Enero, 2024
