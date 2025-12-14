# Módulo Supervisor de Asesores - Implementación Completa

## Descripción General
Se ha implementado un nuevo módulo **Supervisor de Asesores** que permite a los supervisores monitorear y gestionar todas las cotizaciones y pedidos de todos los asesores del equipo.

## Características Principales

### 1. Dashboard
- **Ruta**: `/supervisor-asesores/dashboard`
- **Funcionalidades**:
  - Estadísticas generales (total de cotizaciones, pedidos, asesores, cotizaciones este mes)
  - Acceso rápido a todas las secciones del módulo
  - Diseño responsivo con tarjetas de estadísticas

### 2. Gestión de Cotizaciones
- **Ruta**: `/supervisor-asesores/cotizaciones`
- **API Data**: GET `/supervisor-asesores/cotizaciones/data`
- **Funcionalidades**:
  - Vista de todas las cotizaciones de todos los asesores
  - Filtrado por:
    - Asesor específico
    - Estado de la cotización
  - Tabla con información: ID, número, cliente, asesor, estado, fecha
  - Paginación (estructura lista)

### 3. Gestión de Pedidos
- **Ruta**: `/supervisor-asesores/pedidos`
- **API Data**: GET `/supervisor-asesores/pedidos/data`
- **Funcionalidades**:
  - Vista de todos los pedidos de todos los asesores
  - Filtrado por:
    - Asesor específico
    - Estado del pedido
  - Tabla con información: ID, número de pedido, cliente, asesor, estado, fecha
  - Paginación (estructura lista)

### 4. Gestión de Asesores
- **Ruta**: `/supervisor-asesores/asesores`
- **API Data**: GET `/supervisor-asesores/asesores/data`
- **Ruta de Detalle**: `/supervisor-asesores/asesores/{id}`
- **Funcionalidades**:
  - Grid de tarjetas de asesores
  - Información: nombre, email, avatar
  - Estadísticas: cotizaciones, pedidos
  - Vista detallada de cada asesor con:
    - Información personal
    - Estadísticas (cotizaciones, pedidos, tasa de conversión)
    - Últimas cotizaciones
    - Últimos pedidos

### 5. Reportes y Análisis
- **Ruta**: `/supervisor-asesores/reportes`
- **API Data**: GET `/supervisor-asesores/reportes/data`
- **Funcionalidades**:
  - Filtro por período: Semana, Mes, Trimestre, Año
  - Filtro por asesor específico
  - Resumen general:
    - Total de cotizaciones
    - Total de pedidos
    - Tasa de conversión
    - Ingresos generados
  - Análisis por:
    - Cotizaciones por estado
    - Top 5 asesores
    - Prendas más cotizadas
    - Técnicas más usadas
    - Top 10 clientes

### 6. Perfil del Supervisor
- **Ruta**: `/supervisor-asesores/perfil`
- **API Data**: GET `/supervisor-asesores/perfil/stats`
- **Funcionalidades**:
  - Información personal (nombre, email, teléfono, departamento)
  - Avatar (con imagen o iniciales)
  - Estadísticas personales:
    - Cotizaciones monitoreadas
    - Pedidos monitoreados
    - Asesores bajo supervisión
    - Tasa de conversión promedio
  - Cambio de contraseña
  - Información de sesiones activas
  - Preferencias de notificaciones
  - Botón de cerrar sesión

## Archivos Creados

### Vistas (Blade Templates)
```
resources/views/supervisor-asesores/
├── dashboard.blade.php
├── cotizaciones/
│   └── index.blade.php
├── pedidos/
│   └── index.blade.php
├── asesores/
│   ├── index.blade.php
│   └── show.blade.php
├── reportes/
│   └── index.blade.php
└── profile/
    └── index.blade.php
```

### Componentes
```
resources/views/components/
├── sidebars/
│   └── sidebar-supervisor-asesores.blade.php
├── headers/
│   └── header-supervisor-asesores.blade.php
└── layouts/
    └── supervisor-asesores.blade.php
```

### Controlador
```
app/Http/Controllers/SupervisorAsesoresController.php
```
- Métodos principales:
  - `dashboard()` - Vista del dashboard
  - `dashboardStats()` - Estadísticas en JSON
  - `cotizacionesIndex()` - Lista de cotizaciones
  - `cotizacionesData()` - Datos de cotizaciones en JSON
  - `pedidosIndex()` - Lista de pedidos
  - `pedidosData()` - Datos de pedidos en JSON
  - `asesoresIndex()` - Lista de asesores
  - `asesoresData()` - Datos de asesores en JSON
  - `asesoresShow()` - Detalle de asesor
  - `reportesIndex()` - Página de reportes
  - `reportesData()` - Datos de reportes en JSON
  - `profileIndex()` - Página de perfil
  - `profileStats()` - Estadísticas del perfil en JSON
  - `profilePasswordUpdate()` - Actualización de contraseña

## Rutas (routes/web.php)

```php
Route::middleware(['auth', 'role:supervisor_asesores,admin'])
    ->prefix('supervisor-asesores')
    ->name('supervisor-asesores.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [SupervisorAsesoresController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard-stats', [SupervisorAsesoresController::class, 'dashboardStats'])->name('dashboard-stats');
        
        // Cotizaciones
        Route::get('/cotizaciones', [SupervisorAsesoresController::class, 'cotizacionesIndex'])->name('cotizaciones.index');
        Route::get('/cotizaciones/data', [SupervisorAsesoresController::class, 'cotizacionesData'])->name('cotizaciones.data');
        
        // Pedidos
        Route::get('/pedidos', [SupervisorAsesoresController::class, 'pedidosIndex'])->name('pedidos.index');
        Route::get('/pedidos/data', [SupervisorAsesoresController::class, 'pedidosData'])->name('pedidos.data');
        
        // Asesores
        Route::get('/asesores', [SupervisorAsesoresController::class, 'asesoresIndex'])->name('asesores.index');
        Route::get('/asesores/data', [SupervisorAsesoresController::class, 'asesoresData'])->name('asesores.data');
        Route::get('/asesores/{id}', [SupervisorAsesoresController::class, 'asesoresShow'])->name('asesores.show');
        
        // Reportes
        Route::get('/reportes', [SupervisorAsesoresController::class, 'reportesIndex'])->name('reportes.index');
        Route::get('/reportes/data', [SupervisorAsesoresController::class, 'reportesData'])->name('reportes.data');
        
        // Perfil
        Route::get('/perfil', [SupervisorAsesoresController::class, 'profileIndex'])->name('profile.index');
        Route::get('/perfil/stats', [SupervisorAsesoresController::class, 'profileStats'])->name('profile.stats');
        Route::post('/perfil/password-update', [SupervisorAsesoresController::class, 'profilePasswordUpdate'])->name('profile.password-update');
    });
```

## Control de Acceso

### Middleware Requerido
- `auth` - Usuario debe estar autenticado
- `role:supervisor_asesores,admin` - Usuario debe tener rol supervisor_asesores o admin

### Rol Requerido
- **supervisor_asesores** - Nuevo rol para supervisores de asesores

## Modelos Utilizados
- `User` - Usuarios del sistema
- `Cotizacion` - Cotizaciones creadas por asesores
- `PedidoProduccion` - Pedidos de producción
- `Role` - Roles del sistema

## Funcionalidades de JavaScript

### Carga Dinámica de Datos
Todas las vistas usan `fetch()` para cargar datos en tiempo real desde las API endpoints.

### Filtros en Tiempo Real
- Los filtros actualizan los datos automáticamente sin recargar la página
- Los asesores se cargan dinámicamente en los selects de filtrado

## Estilos y Diseño

### Componentes Visuales
- Gradientes modernos (púrpura a rosa)
- Iconos Material Symbols
- Tarjetas con sombras suaves
- Tablas responsivas
- Grids fluidos
- Botones interactivos con transiciones

### Responsividad
- Diseño mobile-first
- Sidebar colapsable en dispositivos pequeños
- Tablas con scroll horizontal en móvil
- Grids que se adaptan al tamaño de pantalla

## Flujos de Navegación

### Menú Lateral (Sidebar)
- **Principal** → Dashboard
- **Cotizaciones** → Todas las Cotizaciones
- **Pedidos** → Todos los Pedidos
- **Información**:
  - Asesores
  - Reportes
  - Mi Perfil

### Header
- Notificaciones
- Perfil de usuario (con dropdown)
- Logout

## API Endpoints Generados

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/supervisor-asesores/dashboard` | Vista dashboard |
| GET | `/supervisor-asesores/dashboard-stats` | Estadísticas JSON |
| GET | `/supervisor-asesores/cotizaciones` | Lista cotizaciones |
| GET | `/supervisor-asesores/cotizaciones/data` | Datos cotizaciones JSON |
| GET | `/supervisor-asesores/pedidos` | Lista pedidos |
| GET | `/supervisor-asesores/pedidos/data` | Datos pedidos JSON |
| GET | `/supervisor-asesores/asesores` | Lista asesores |
| GET | `/supervisor-asesores/asesores/data` | Datos asesores JSON |
| GET | `/supervisor-asesores/asesores/{id}` | Detalle asesor |
| GET | `/supervisor-asesores/reportes` | Página reportes |
| GET | `/supervisor-asesores/reportes/data` | Datos reportes JSON |
| GET | `/supervisor-asesores/perfil` | Página perfil |
| GET | `/supervisor-asesores/perfil/stats` | Estadísticas perfil JSON |
| POST | `/supervisor-asesores/perfil/password-update` | Actualizar contraseña |

## Próximos Pasos / Consideraciones

1. **Rol en Base de Datos**: Asegurarse de que el rol `supervisor_asesores` exista en la tabla `roles`
   ```sql
   INSERT INTO roles (name, display_name, description) 
   VALUES ('supervisor_asesores', 'Supervisor de Asesores', 'Supervisa cotizaciones y pedidos de todos los asesores');
   ```

2. **Asignar Usuarios**: Asignar el rol `supervisor_asesores` a usuarios específicos en la tabla `users` (columna `roles_ids` como JSON array)

3. **Validaciones Adicionales**: Se pueden agregar más validaciones de seguridad y autorización según necesidad

4. **Auditoría**: Los cambios no están siendo auditados, se puede agregar si es necesario

5. **Notificaciones**: Las notificaciones están estructuradas pero no totalmente implementadas

6. **Exportación**: Se puede agregar funcionalidad para exportar datos a Excel/PDF

7. **Gráficas**: Se puede agregar Chart.js para visualizar datos de forma gráfica

## Verificación de Funcionamiento

Para verificar que todo funciona correctamente:

1. Crear/asignar un usuario con rol `supervisor_asesores`
2. Loguearse con ese usuario
3. Acceder a `/supervisor-asesores/dashboard`
4. Verificar que se cargan las estadísticas
5. Navegar por las diferentes secciones
6. Probar los filtros en cotizaciones y pedidos
7. Verificar que se muestren los datos correctamente

## Notas Técnicas

- El controlador usa `whereJsonContains()` para buscar usuarios con roles específicos
- Los datos se cargan vía AJAX para mejor performance
- Las estadísticas se calculan en tiempo real
- El filtrado es case-sensitive en algunos casos, se puede mejorar con `ILIKE` si es necesario
- No hay paginación implementada en los endpoints de data (se puede agregar si hay muchos registros)

## Estructura de Datos Esperada

### Modelo Cotizacion
```php
- id
- numero
- cliente
- user_id (asesor_id)
- estado
- created_at
- monto_total
```

### Modelo PedidoProduccion
```php
- id
- numero_pedido
- cliente
- user_id
- estado
- created_at
- monto_total
```

### Modelo User
```php
- id
- name
- email
- avatar
- roles_ids (JSON array de IDs de roles)
```

## Autor
Sistema Mundo Industrial

## Versión
1.0.0

## Última Actualización
2024
