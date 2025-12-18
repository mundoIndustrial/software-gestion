# Guía del Rol: Visualizador de Cotizaciones Logo

## 📋 Descripción

El rol **VISUALIZADOR_COTIZACIONES_LOGO** es un rol de solo lectura que permite a los usuarios:

- ✅ Ver cotizaciones tipo **Logo (L)**
- ✅ Ver cotizaciones tipo **Combinada (PL)** - pero solo la información de logo
- ✅ Descargar PDFs de logo de las cotizaciones permitidas
- ❌ **NO** puede ver PDFs de prenda
- ❌ **NO** puede crear, editar o eliminar cotizaciones
- ❌ **NO** puede ver cotizaciones tipo Prenda (P) puras

## 🚀 Instalación

### 1. Ejecutar el Seeder del Rol

```bash
php artisan db:seed --class=AddVisualizadorCotizacionesLogoRoleSeeder
```

Este comando creará el rol `visualizador_cotizaciones_logo` en la tabla `roles`.

### 2. Asignar el Rol a un Usuario

Puedes asignar el rol de dos formas:

#### Opción A: Mediante la interfaz de administración
1. Ir a la sección de usuarios
2. Editar el usuario deseado
3. Asignar el rol "Visualizador de Cotizaciones Logo"

#### Opción B: Mediante Tinker

```bash
php artisan tinker
```

```php
// Obtener el rol
$rol = \App\Models\Role::where('name', 'visualizador_cotizaciones_logo')->first();

// Obtener el usuario (por email o ID)
$usuario = \App\Models\User::where('email', 'usuario@ejemplo.com')->first();

// Asignar el rol
$usuario->roles_ids = [$rol->id];
$usuario->save();
```

## 📁 Archivos Creados

### Backend

1. **Seeder del Rol**
   - `database/seeders/AddVisualizadorCotizacionesLogoRoleSeeder.php`
   - Crea el rol en la base de datos

2. **Controlador**
   - `app/Http/Controllers/VisualizadorLogoController.php`
   - Métodos:
     - `dashboard()` - Dashboard principal
     - `getCotizaciones()` - Lista cotizaciones con filtros
     - `verCotizacion($id)` - Detalle de una cotización
     - `getEstadisticas()` - Estadísticas del dashboard

3. **Actualización de Controladores Existentes**
   - `app/Http/Controllers/PDFCotizacionController.php`
     - Validación de permisos para el visualizador
     - Solo permite descargar PDFs de logo
   - `app/Http/Controllers/DashboardController.php`
     - Redirección automática al dashboard del visualizador

### Frontend

4. **Vistas**
   - `resources/views/visualizador-logo/dashboard.blade.php`
     - Dashboard con tabla de cotizaciones
     - Filtros por búsqueda, estado y fechas
     - Estadísticas (total, pendientes, aprobadas, este mes)
   - `resources/views/visualizador-logo/detalle.blade.php`
     - Vista detallada de una cotización
     - Información del logo (técnicas, ubicaciones, observaciones)
     - Galería de imágenes del logo

### Rutas

5. **Rutas Protegidas** (`routes/web.php`)
   ```php
   Route::middleware(['auth', 'role:visualizador_cotizaciones_logo,admin'])
       ->prefix('visualizador-logo')
       ->name('visualizador-logo.')
       ->group(function () {
           // Dashboard
           Route::get('/dashboard', ...);
           
           // Cotizaciones
           Route::get('/cotizaciones', ...);
           Route::get('/cotizaciones/{id}', ...);
           
           // Estadísticas
           Route::get('/estadisticas', ...);
           
           // PDF de Logo
           Route::get('/cotizaciones/{id}/pdf-logo', ...);
       });
   ```

## 🔐 Permisos y Restricciones

### Cotizaciones Permitidas

El visualizador puede ver cotizaciones que cumplan **TODAS** estas condiciones:

1. **Tipo de Cotización:**
   - Código `L` (Logo)
   - Código `PL` (Combinada/Prenda+Logo)
   - Código `C` (Combinada - código antiguo)

2. **Estado:**
   - Solo cotizaciones **enviadas** (no borradores)
   - `es_borrador = false`
   - `numero_cotizacion IS NOT NULL`

3. **Información de Logo:**
   - Debe tener registro en la tabla `logo_cotizacion`

### Restricciones de Acceso

- ❌ No puede acceder a rutas de asesores
- ❌ No puede crear cotizaciones
- ❌ No puede editar cotizaciones
- ❌ No puede eliminar cotizaciones
- ❌ No puede ver PDFs de prenda (solo logo)
- ❌ No puede ver cotizaciones tipo Prenda pura (P)

## 📊 Funcionalidades del Dashboard

### Estadísticas

- **Total Cotizaciones:** Cantidad total de cotizaciones logo/combinadas
- **Pendientes:** Cotizaciones en estado pendiente
- **Aprobadas:** Cotizaciones aprobadas
- **Este Mes:** Cotizaciones creadas en el mes actual

### Filtros Disponibles

1. **Búsqueda:** Por número de cotización o nombre de cliente
2. **Estado:** Pendiente, Aprobado, Rechazado
3. **Rango de Fechas:** Desde - Hasta

### Tabla de Cotizaciones

Columnas:
- Número de cotización
- Cliente
- Asesor
- Tipo (Logo o Combinada)
- Estado
- Fecha
- Acciones (Ver detalle, Descargar PDF Logo)

### Paginación

- 20 cotizaciones por página
- Navegación entre páginas

## 🎨 Vista de Detalle

### Información General
- Cliente
- Asesor
- Tipo de cotización
- Estado
- Fecha de inicio
- Fecha de envío

### Información del Logo
- Técnicas (bordado, estampado, etc.)
- Tipo de venta (Muestra, Definitivo, Mixto)
- Ubicaciones del logo
- Observaciones técnicas
- Observaciones generales

### Galería de Imágenes
- Visualización de todas las fotos del logo
- Modal para ver imágenes en tamaño completo

## 🔄 Flujo de Uso

1. **Login:** El usuario inicia sesión con sus credenciales
2. **Redirección:** Automáticamente redirigido a `/visualizador-logo/dashboard`
3. **Dashboard:** Ve estadísticas y lista de cotizaciones
4. **Filtros:** Puede filtrar por búsqueda, estado o fechas
5. **Ver Detalle:** Click en el botón "Ver" para ver información completa
6. **Descargar PDF:** Click en el botón PDF para descargar el PDF de logo

## 🛡️ Validaciones de Seguridad

### En el Controlador (VisualizadorLogoController)

```php
// Verifica que sea tipo Logo o Combinada
$tiposCodigos = ['L', 'PL', 'C'];
if (!in_array($cotizacion->tipoCotizacion->codigo ?? '', $tiposCodigos)) {
    abort(403, 'No tienes permiso para ver esta cotización.');
}

// Verifica que tenga información de logo
if (!$cotizacion->logoCotizacion) {
    abort(404, 'Esta cotización no tiene información de logo.');
}
```

### En PDFCotizacionController

```php
// Si es visualizador, solo puede ver PDFs de logo
if ($user->hasRole('visualizador_cotizaciones_logo')) {
    if ($tipoPDF !== 'logo') {
        abort(403, 'No tienes permiso para ver PDFs de prenda.');
    }
}
```

## 📝 Ejemplo de Uso

### Crear Usuario Visualizador

```php
// En tinker o seeder
$rol = \App\Models\Role::where('name', 'visualizador_cotizaciones_logo')->first();

$usuario = \App\Models\User::create([
    'name' => 'Visualizador Logo',
    'email' => 'visualizador@empresa.com',
    'password' => bcrypt('password123'),
    'roles_ids' => [$rol->id]
]);
```

### Acceder al Sistema

1. URL de login: `http://tu-dominio.com/login`
2. Email: `visualizador@empresa.com`
3. Password: `password123`
4. Redirección automática a: `http://tu-dominio.com/visualizador-logo/dashboard`

## 🐛 Troubleshooting

### El usuario no puede acceder

1. Verificar que el rol esté asignado:
   ```php
   $usuario = User::find($id);
   dd($usuario->roles_ids);
   ```

2. Verificar que el rol exista:
   ```php
   Role::where('name', 'visualizador_cotizaciones_logo')->exists();
   ```

### No aparecen cotizaciones

1. Verificar que existan cotizaciones tipo Logo o Combinada
2. Verificar que no sean borradores
3. Verificar que tengan información de logo en `logo_cotizacion`

### Error 403 al descargar PDF

- El visualizador solo puede descargar PDFs de logo
- Verificar que la URL incluya `?tipo=logo`

## 📞 Soporte

Para más información o problemas, contactar al equipo de desarrollo.

---

**Versión:** 1.0  
**Fecha:** Diciembre 2024  
**Autor:** Sistema MundoIndustrial
