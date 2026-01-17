# Módulo Pedidos (DDD)

Módulo encargado de la gestión de pedidos de producción y sus EPP asociados.

## Estructura

```
app/Modules/Pedidos/
├── Application/           # Use Cases, Application Services
├── Domain/               # Entidades, Value Objects, Interfaces del dominio
├── Infrastructure/       # Implementaciones de tecnología
│   ├── Http/
│   │   └── Controllers/
│   │       └── PedidoEppController.php
│   ├── Repositories/     # Implementaciones de repositorios
│   └── Providers/
│       └── PedidosServiceProvider.php
├── DTOs/                 # Data Transfer Objects
├── Routes/
│   └── api.php          # Rutas del módulo
└── README.md            # Este archivo
```

## Responsabilidades

### Domain/
Define las entidades y reglas de negocio puras, sin dependencias de framework.

### Application/
Contiene los casos de uso (Use Cases) y servicios de aplicación.

### Infrastructure/
Implementaciones concretas de persistencia, HTTP, y otras tecnologías.

- **Http/Controllers**: Controladores REST
- **Repositories**: Implementaciones de acceso a datos
- **Providers**: Service providers del módulo

## Rutas

Las rutas del módulo se cargan desde `Routes/api.php` a través del `PedidosServiceProvider`.

### Endpoints

```
GET    /api/v1/pedidos/{pedido}/epps               - Listar EPP de un pedido
POST   /api/v1/pedidos/{pedido}/epps               - Crear EPP en pedido
PATCH  /api/v1/pedidos/{pedido}/epps/{pedidoEpp}   - Actualizar EPP
DELETE /api/v1/pedidos/{pedido}/epps/{pedidoEpp}   - Eliminar EPP
GET    /api/v1/pedidos/{pedido}/epps/exportar/json - Exportar EPP como JSON
```

## Configuración

El módulo se registra automáticamente en `bootstrap/providers.php`:

```php
App\Modules\Pedidos\Infrastructure\Providers\PedidosServiceProvider::class,
```

## Controladores

### PedidoEppController
Gestiona operaciones CRUD de EPP en pedidos de producción.

**Métodos:**
- `index()` - Lista todos los EPP de un pedido
- `store()` - Crea nuevos EPP en un pedido
- `update()` - Actualiza un EPP existente
- `destroy()` - Elimina un EPP de un pedido
- `exportarJson()` - Exporta todos los EPP en formato JSON
