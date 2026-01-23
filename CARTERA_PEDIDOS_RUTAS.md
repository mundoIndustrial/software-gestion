# RUTAS RECOMENDADAS - CARTERA PEDIDOS

## Web Routes (routes/web.php)

```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ===== CARTERA ROUTES =====
Route::middleware(['auth', 'role:cartera,admin'])->prefix('cartera')->name('cartera.')->group(function () {
    /**
     * Dashboard de Cartera
     * GET /cartera
     */
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');
    
    /**
     * Gestión de Pedidos por Aprobar
     * GET /cartera/pedidos
     */
    Route::get('/pedidos', function () {
        return view('cartera-pedidos.cartera_pedidos');
    })->name('pedidos');
    
    /**
     * Historial de Aprobaciones/Rechazos
     * GET /cartera/historial
     */
    Route::get('/historial', function () {
        return view('cartera.historial');  // Opcional: crear vista
    })->name('historial');
    
    /**
     * Reportes de Cartera
     * GET /cartera/reportes
     */
    Route::get('/reportes', function () {
        return view('cartera.reportes');  // Opcional: crear vista
    })->name('reportes');
});
```

---

## API Routes (routes/api.php)

```php
<?php

use App\Http\Controllers\API\CarterapedidoController;
use Illuminate\Support\Facades\Route;

// ===== CARTERA API ROUTES =====
Route::middleware(['auth:sanctum', 'role:cartera,admin'])->prefix('cartera')->group(function () {
    
    /**
     * Obtener pedidos por estado
     * GET /api/cartera/pedidos
     * 
     * Query Parameters:
     * - estado: pendiente_cartera | aprobado | rechazado (default: pendiente_cartera)
     * - cliente: string (búsqueda por nombre)
     * - numero_pedido: string (búsqueda por número)
     * - per_page: int (default: 50)
     * - page: int (default: 1)
     */
    Route::get('/pedidos', [CarterapedidoController::class, 'index']);
    
    /**
     * Obtener detalle de un pedido
     * GET /api/cartera/pedidos/{id}
     */
    Route::get('/pedidos/{id}', [CarterapedidoController::class, 'show']);
});

// ===== PEDIDOS API ROUTES (acceso general) =====
Route::middleware(['auth:sanctum'])->prefix('pedidos')->group(function () {
    
    /**
     * Aprobar un pedido
     * POST /api/pedidos/{id}/aprobar
     * 
     * Required Role: cartera, admin
     * 
     * Body:
     * {
     *   "pedido_id": 1,
     *   "accion": "aprobar"
     * }
     */
    Route::post('/{id}/aprobar', [CarterapedidoController::class, 'aprobar'])
        ->middleware('role:cartera,admin');
    
    /**
     * Rechazar un pedido
     * POST /api/pedidos/{id}/rechazar
     * 
     * Required Role: cartera, admin
     * 
     * Body:
     * {
     *   "pedido_id": 1,
     *   "motivo": "Razón del rechazo...",
     *   "accion": "rechazar"
     * }
     * 
     * Validation:
     * - motivo: required|string|min:10|max:1000
     */
    Route::post('/{id}/rechazar', [CarterapedidoController::class, 'rechazar'])
        ->middleware('role:cartera,admin');
});
```

---

## URLs Disponibles

### Web URLs

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/cartera` | Dashboard de Cartera |
| GET | `/cartera/pedidos` | **Gestión de Pedidos** (PRINCIPAL) |
| GET | `/cartera/historial` | Historial de cambios |
| GET | `/cartera/reportes` | Reportes de cartera |

### API URLs

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/cartera/pedidos` | Obtener pedidos por estado |
| GET | `/api/cartera/pedidos/{id}` | Detalle de pedido |
| POST | `/api/pedidos/{id}/aprobar` | Aprobar pedido |
| POST | `/api/pedidos/{id}/rechazar` | Rechazar pedido |

---

## Ejemplos de Uso

### 1. Acceder a la interfaz principal

```
URL: http://localhost:8000/cartera/pedidos
Usuario: Debe tener rol 'cartera' o 'admin'
Esperado: Ver tabla de pedidos por aprobar
```

### 2. Llamar API para obtener pedidos

```bash
curl -X GET "http://localhost:8000/api/cartera/pedidos?estado=pendiente_cartera" \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: $(curl -s http://localhost:8000/cartera/pedidos | grep -oP 'csrf-token" content="\K[^"]*')" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Aprobar pedido vía API

```bash
curl -X POST "http://localhost:8000/api/pedidos/1/aprobar" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_TOKEN" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"pedido_id": 1, "accion": "aprobar"}'
```

### 4. Rechazar pedido vía API

```bash
curl -X POST "http://localhost:8000/api/pedidos/1/rechazar" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_TOKEN" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "pedido_id": 1,
    "motivo": "Crédito vencido del cliente",
    "accion": "rechazar"
  }'
```

---

## Parámetros de Query

### GET /api/cartera/pedidos

| Parámetro | Tipo | Descripción | Default | Ejemplo |
|-----------|------|-------------|---------|---------|
| `estado` | string | pendiente_cartera, aprobado, rechazado | pendiente_cartera | `?estado=pendiente_cartera` |
| `cliente` | string | Búsqueda por nombre cliente | - | `?cliente=ABC` |
| `numero_pedido` | string | Búsqueda por número | - | `?numero_pedido=PED-2024` |
| `per_page` | integer | Resultados por página | 50 | `?per_page=25` |
| `page` | integer | Número de página | 1 | `?page=2` |

### Ejemplos de Queries

```
# Solo pendientes de cartera
GET /api/cartera/pedidos?estado=pendiente_cartera

# Búsqueda por cliente
GET /api/cartera/pedidos?cliente=ClienteABC&estado=pendiente_cartera

# Búsqueda por número
GET /api/cartera/pedidos?numero_pedido=PED-2024&estado=pendiente_cartera

# Paginación
GET /api/cartera/pedidos?estado=pendiente_cartera&per_page=10&page=2

# Combinada
GET /api/cartera/pedidos?estado=pendiente_cartera&cliente=ABC&per_page=25&page=1
```

---

## Headers Requeridos

### Todos los requests

```http
Accept: application/json
X-CSRF-TOKEN: {token_from_meta_tag}
Content-Type: application/json
```

### Requests autenticados (API)

```http
Authorization: Bearer {sanctum_token}
Accept: application/json
X-CSRF-TOKEN: {token}
Content-Type: application/json
```

---

## Códigos de Respuesta

| Código | Significado | Ejemplo |
|--------|-------------|---------|
| 200 | OK - Éxito | Pedido aprobado/rechazado |
| 400 | Bad Request | Estado no válido |
| 403 | Forbidden | Usuario sin permisos |
| 404 | Not Found | Pedido no existe |
| 422 | Validation Failed | Motivo muy corto |
| 500 | Server Error | Error en el servidor |

---

## Estructura de Respuestas

### GET /api/cartera/pedidos - 200 OK

```json
{
  "data": [
    {
      "id": 1,
      "numero_pedido": "PED-2024-001",
      "cliente": "Cliente ABC",
      "estado": "Pendiente cartera",
      "fecha_de_creacion_de_orden": "2024-01-20 10:30:00",
      "asesora": {
        "id": 5,
        "name": "María García"
      },
      "forma_de_pago": "Crédito",
      "fecha_estimada_de_entrega": "2024-02-01"
    }
  ],
  "total": 15,
  "per_page": 50,
  "current_page": 1,
  "last_page": 1,
  "message": "Pedidos obtenidos correctamente"
}
```

### POST /api/pedidos/{id}/aprobar - 200 OK

```json
{
  "message": "Pedido aprobado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "estado": "Aprobado por Cartera",
    "aprobado_por_cartera_en": "2024-01-23 10:45:00"
  },
  "success": true
}
```

### POST /api/pedidos/{id}/rechazar - 200 OK

```json
{
  "message": "Pedido rechazado correctamente",
  "data": {
    "id": 1,
    "numero_pedido": "PED-2024-001",
    "estado": "Rechazado por Cartera",
    "motivo_rechazo": "Crédito vencido...",
    "rechazado_por_cartera_en": "2024-01-23 10:50:00",
    "notificacion_enviada": true
  },
  "success": true
}
```

### Error - 422 Validation Failed

```json
{
  "message": "Validación fallida",
  "errors": {
    "motivo": [
      "El motivo es requerido y debe tener al menos 10 caracteres"
    ]
  }
}
```

---

## Middleware Requerido

```php
// Autenticación
middleware('auth')                    // Para rutas web
middleware('auth:sanctum')            // Para API

// Rol/Permisos
middleware('role:cartera,admin')      // Requiere rol cartera o admin
middleware('role:cartera')            // Requiere rol cartera específicamente
```

---

## Navegación en la Aplicación

### De Supervisor a Cartera

Si tienes enlace en supervisor-pedidos:

```blade
<!-- En supervisor-pedidos layout o componente -->
@if(auth()->user()->hasRole(['cartera', 'admin']))
    <a href="{{ route('cartera.pedidos') }}" class="btn btn-link">
        <span class="material-symbols-rounded">receipt_long</span>
        Cartera
    </a>
@endif
```

### Menu Principal

```blade
<!-- En layout principal -->
@if(auth()->user()->hasRole('cartera'))
    <li>
        <a href="{{ route('cartera.pedidos') }}">
            <span class="material-symbols-rounded">receipt_long</span>
            Cartera - Pedidos
        </a>
    </li>
@endif
```

---

## Testing con cURL

### Obtener lista de pedidos

```bash
curl -X GET "http://localhost:8000/api/cartera/pedidos?estado=pendiente_cartera" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### Aprobar pedido

```bash
curl -X POST "http://localhost:8000/api/pedidos/1/aprobar" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "pedido_id": 1,
    "accion": "aprobar"
  }'
```

### Rechazar pedido

```bash
curl -X POST "http://localhost:8000/api/pedidos/1/rechazar" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "pedido_id": 1,
    "motivo": "Crédito vencido del cliente",
    "accion": "rechazar"
  }'
```

---

## Permisos y Roles

### Rol Required: `cartera`

Para acceder a todas las funcionalidades de Cartera Pedidos:

```sql
-- Crear rol
INSERT INTO roles (name, guard_name) VALUES ('cartera', 'web');

-- Asignar a usuario
INSERT INTO model_has_roles (role_id, model_type, model_id) 
VALUES ((SELECT id FROM roles WHERE name = 'cartera'), 'App\\Models\\User', 1);
```

### Alternativa con Admin

Los usuarios con rol `admin` también tienen acceso.

---

**Última actualización:** 23 de Enero, 2024
