# Módulo Insumos

## Descripción
Módulo modular para la gestión de insumos en órdenes de producción. Implementa el patrón Repository, Service y Controller con inyección de dependencias.

## Estructura

```
modules/insumos/
├── backend/
│   ├── Controllers/
│   │   └── MaterialesController.php       # HTTP Controller
│   ├── Services/
│   │   └── MaterialesService.php          # Business Logic
│   ├── Repositories/
│   │   └── MaterialesRepository.php       # Data Access Layer
│   ├── Models/
│   │   └── MaterialesOrdenInsumos.php     # Eloquent Model
│   └── Routes/
│       └── web.php                        # Module Routes
│
├── frontend/
│   ├── views/
│   │   ├── dashboard.blade.php            # Dashboard View
│   │   └── materiales/
│   │       └── index.blade.php            # Materials List View
│   ├── js/                                # JavaScript modules
│   ├── css/                               # Stylesheets
│   └── components/                        # Blade Components
│
├── config.php                             # Module Configuration
└── InsumosServiceProvider.php             # Service Provider

```

## Características

- **Repository Pattern**: Separación clara de la lógica de acceso a datos
- **Service Layer**: Lógica de negocio centralizada con validaciones
- **Dependency Injection**: Inyección automática mediante ServiceProvider
- **Configuration Centralized**: Configuración en `config.php`
- **Modular Routes**: Rutas auto-registradas sin configuración manual
- **View Namespacing**: Vistas con namespace `insumos::`

## Uso

### Inyección de Dependencias

```php
use Modules\Insumos\Backend\Services\MaterialesService;

class MiController
{
    public function __construct(MaterialesService $materialesService)
    {
        $this->materialesService = $materialesService;
    }
}
```

### Llamadas al Servicio

```php
// Obtener dashboard
$dashboard = $materialesService->obtenerDashboard();

// Obtener materiales filtrados
$materiales = $materialesService->obtenerMaterialesFiltrados([
    'numero_pedido' => '123',
    'estado' => 'En Ejecución',
]);

// Guardar materiales
$materialesService->guardarMateriales($datos);

// Eliminar material
$materialesService->eliminarMaterial($id);
```

## Rutas Disponibles

```
GET    /insumos/dashboard              → MaterialesController@dashboard
GET    /insumos/materiales             → MaterialesController@index
POST   /insumos/materiales/{pedido}    → MaterialesController@store
POST   /insumos/materiales/{pedido}/eliminar → MaterialesController@destroy
GET    /insumos/api/materiales/{pedido}     → MaterialesController@show (API)
GET    /insumos/api/filtros/{column}        → MaterialesController@obtenerFiltros (API)
```

## Configuración

Editar `modules/insumos/config.php` para personalizar:
- Estados permitidos
- Áreas permitidas
- Columnas permitidas para filtros
- Middleware y rutas
- Namespaces

## Vistas

Acceder a las vistas del módulo:

```blade
@include('insumos::components.example')
{{ view('insumos::dashboard') }}
```

## Testing

Crear modelos relacionados en `modules/insumos/backend/Models/` si es necesario.

Crear servicios adicionales en `modules/insumos/backend/Services/`.

## Notas

- El módulo se auto-registra mediante `InsumosServiceProvider`
- Las rutas se cargan automáticamente en el bootstrap
- La configuración se cachea en `config('insumos')`
- Los middlewares se aplican a todas las rutas del módulo
