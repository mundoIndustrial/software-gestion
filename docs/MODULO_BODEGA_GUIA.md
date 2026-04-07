# Módulo de Gestión de Bodega - Guía de Implementación

##  Descripción General

Sistema completo de gestión de pedidos para el rol de Bodeguero en el ERP textil. Permite visualizar, filtrar, buscar y actualizar el estado de pedidos con interfaz moderna y responsive.

##  Características Principales

-  Vista de pedidos agrupados por número de pedido
-  Filtros por asesor y estado
-  Buscador en tiempo real
-  Edición de observaciones inline
-  Edición de fecha de entrega
-  Marcar como entregado con confirmación
-  Estadísticas en tiempo real
-  Notificaciones tipo Toast
-  Detección automática de pedidos retrasados
-  Interfaz responsive (mobile-friendly)
-  Auditoría de cambios
-  Validación de datos en backend y frontend

##  Archivos Creados

```
resources/views/bodega/
├── pedidos.blade.php          # Vista principal con tabla de pedidos

public/js/
├── bodega-pedidos.js           # JavaScript vanilla para interactividad

app/Http/Controllers/Bodega/
├── PedidosController.php       # Lógica del controlador

routes/
├── bodega.php                  # Definición de rutas
```

##  Pasos de Integración

### 1. Registrar las Rutas

En tu archivo `routes/web.php`, añade:

```php
// Importar las rutas de bodega
require base_path('routes/bodega.php');
```

### 2. Verificar Modelo ReciboPrenda

Tu modelo debe tener estos campos y relaciones:

```php
class ReciboPrenda extends Model
{
    protected $fillable = [
        'numero_pedido',
        'asesor_id',
        'empresa_id',
        'articulo_id',
        'cantidad',
        'observaciones',
        'fecha_entrega',
        'fecha_entrega_real',
        'estado',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'fecha_entrega_real' => 'datetime',
    ];

    // Relaciones
    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
```

### 3. Crear Permisos (Spatie Laravel Permission)

Si usas Spatie Laravel Permission:

```php
// En tu seeder o artisan command
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$bodeguero = Role::firstOrCreate(['name' => 'bodeguero']);

$permissions = [
    'view-bodega-pedidos',
    'marcar-entregado',
    'editar-observaciones',
    'editar-fecha-entrega',
    'export-bodega',
    'view-bodega-dashboard',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
    $bodeguero->givePermissionTo($permission);
}
```

### 4. Layout Base (layouts/app.blade.php)

Asegúrate de que tu layout incluya:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP Textil')</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- O si usas compilado: -->
    <!-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> -->
</head>
<body class="bg-gray-50">
    @yield('content')

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
```

##  Configuración

### Variables de Entorno Necesarias

```env
APP_NAME="Tu ERP"
APP_ENV=production
DB_CONNECTION=mysql
QUEUE_CONNECTION=database
```

### Permisos en Controlador (Policy)

Crea una policy si no existe:

```bash
php artisan make:policy BodegueroPolicy
```

```php
class BodegueroPolicy
{
    public function bodegueroDashboard(User $user)
    {
        return $user->hasRole('bodeguero') && 
               $user->hasPermission('view-bodega-pedidos');
    }
}
```

##  Estados de Pedidos

| Estado | Condición | Color | Descripción |
|--------|-----------|-------|-------------|
| **PENDIENTE** | Estado actual = pendiente | Amarillo | Pedido en espera de entrega |
| **ENTREGADO** | Estado = entregado | Verde | Pedido ya fue entregado |
| **RETRASADO** | Fecha entrega < Hoy | Rojo | Pedido pasó su fecha |

## 🔌 API de JavaScript

### Funciones Disponibles

```javascript
// Filtrar tabla
filterTable()

// Marcar como entregado
handleEntregarClick(e)

// Actualizar observaciones
handleObservacionesChange(e)

// Actualizar fecha
handleFechaChange(e)

// Mostrar notificación
showToast(message, type)

// Actualizar estadísticas
updateStatistics()

// Verificar si está retrasado
checkRetrasado(dateInput)
```

##  Personalización

### Cambiar Colores

En la vista Blade, busca las clases Tailwind y modifica:

```blade
<!-- Cambiar color primario de azul a verde -->
border-blue-500 → border-green-500
ring-blue-500 → ring-green-500
text-blue-600 → text-green-600
bg-blue-100 → bg-green-100
```

### Cambiar Campos de la Tabla

1. Abre `resources/views/bodega/pedidos.blade.php`
2. Modifica el array de datos en la vista
3. Actualiza las columnas en `<thead>` y `<tbody>`

### Agregar Nuevo Campo Editable

1. Añade una fila en la tabla
2. Copia la estructura de inputs existentes
3. Crea función manejadora en `bodega-pedidos.js`
4. Crea endpoint en el controlador
5. Valida en backend

##  Seguridad

-  CSRF Protection en todas las peticiones POST
-  Validación de permisos por rol
-  Validación de datos en backend
-  Auditoría de cambios (Spatie Activity Log)
-  Autorización por policy
-  Sanitización de inputs

## 📱 Responsive Design

La interfaz es completamente responsive:
- **Desktop**: 7 columnas, tabla completa
- **Tablet**: Ajuste de padding, inputs más compactos
- **Mobile**: Stack vertical de información (requiere adaptación)

Para mobile, considera crear una vista alternativa:

```blade
@if(request()->wantsJson() || request()->header('X-Mobile'))
    @include('bodega.pedidos-mobile')
@else
    <!-- Vista desktop actual -->
@endif
```

## Debugging

### Ver Registros de Auditoría

```php
use Spatie\ActivityLog\Models\Activity;

// En tinker o un endpoint de debug
Activity::latest()->take(10)->get();
```

### Consola del Navegador

Abre las DevTools (F12) y revisa:
- Pestaña **Console** para errores JS
- Pestaña **Network** para peticiones AJAX
- Pestaña **Storage > Cookies** para CSRF token

## 📈 Optimizaciones Futura

- [ ] Agregar paginación si hay muchos pedidos
- [ ] Implementar búsqueda avanzada con filtros múltiples
- [ ] Exportar a Excel/PDF
- [ ] Notificaciones en tiempo real con WebSockets (Reverb)
- [ ] Gráficos de estadísticas
- [ ] Historial de cambios por pedido
- [ ] Importar datos en lote
- [ ] API REST completa

## 🆘 Problemas Comunes

### Error: "Meta CSRF token not found"

**Solución:** Asegúrate de incluir en tu `<head>`:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### AJAX devuelve 401 Unauthorized

**Solución:** El usuario no tiene permisos. Verifica:
```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasRole('bodeguero');
>>> $user->hasPermission('view-bodega-pedidos');
```

### Los cambios no se guardan

**Solución:** Revisa la consola del navegador (F12) para ver errores AJAX. Verifica que las rutas existan.

### Estilos Tailwind no se aplican

**Solución:** Si compilas Tailwind, asegúrate de incluir `resources/views/bodega/**` en `tailwind.config.js`:
```js
content: [
    "./resources/views/**/*.blade.php",
    // ... otros paths
]
```

## 📞 Soporte y Mejoras

Para reportar issues o sugerir mejoras, considera:
1. Revisar los logs en `storage/logs/laravel.log`
2. Verificar que todos los modelos tengan las relaciones correctas
3. Asegurar que el usuario tenga los permisos necesarios
4. Testear primero en un endpoint sin autenticación para aislar problemas

##  Notas Importantes

- El módulo requiere Laravel 9+
- Compatible con Spatie Laravel Permission
- Usa Carbon para manejo de fechas
- Los cambios se registran en activity log automáticamente
- Las estadísticas se actualizan en tiempo real (frontend)
- El estado se determina automáticamente en el backend

---

**Última actualización:** Febrero 2026
**Versión:** 1.0.0
**Estado:** Producción Ready 
