#  GUÍA RÁPIDA DE INSTALACIÓN - MÓDULO BODEGA

##  5 Pasos para Poner en Funcionamiento

### Paso 1: Registrar las Rutas

Abre `routes/web.php` y añade al final:

```php
// Rutas del módulo de Bodega
require base_path('routes/bodega.php');
```

### Paso 2: Crear la Tabla en la Base de Datos

Ejecuta la migración:

```bash
php artisan migrate
```

**Nota:** Si la tabla ya existe, usa:
```bash
php artisan migrate:rollback
php artisan migrate
```

### Paso 3: Crear Permisos y Rol

Ejecuta en tinker o crear un seeder:

```bash
php artisan tinker
```

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$bodeguero = Role::create(['name' => 'bodeguero']);

$permissions = [
    'view-bodega-pedidos',
    'marcar-entregado',
    'editar-observaciones',
    'editar-fecha-entrega',
    'export-bodega',
    'view-bodega-dashboard',
];

foreach ($permissions as $perm) {
    $p = Permission::create(['name' => $perm]);
    $bodeguero->givePermissionTo($p);
}
```

### Paso 4: Asignar Rol a Usuario

```bash
php artisan tinker
```

```php
$user = User::find(1); // Cambiar ID según corresponda
$user->assignRole('bodeguero');
```

### Paso 5: Verificar la Vista

Accede a:
```
http://tuapp.local/bodega/pedidos
```

---

##  Checklist de Instalación

- [ ] Rutas registradas en `routes/web.php`
- [ ] Migración ejecutada (`php artisan migrate`)
- [ ] Permisos creados
- [ ] Rol bodeguero creado
- [ ] Usuario tiene rol bodeguero
- [ ] Usuario tiene permisos bodeguero
- [ ] `resources/views/bodega/pedidos.blade.php` existe
- [ ] `public/js/bodega-pedidos.js` existe
- [ ] `app/Http/Controllers/Bodega/PedidosController.php` existe
- [ ] `routes/bodega.php` existe
- [ ] Layout `resources/views/layouts/app.blade.php` incluye meta csrf-token
- [ ] TailwindCSS está compilado/incluido

---

##  Verificar Que Todo Está Funcionando

### 1. Verificar Rutas

```bash
php artisan route:list | grep bodega
```

Deberías ver:
```
POST    /bodega/pedidos/{id}/entregar
POST    /bodega/pedidos/observaciones
POST    /bodega/pedidos/fecha
GET     /bodega/pedidos
```

### 2. Verificar Permisos

```bash
php artisan tinker
```

```php
$user = User::find(1);
$user->hasPermission('view-bodega-pedidos'); // Debe retornar true
$user->hasRole('bodeguero'); // Debe retornar true
```

### 3. Verificar Tabla

```php
DB::table('recibo_prendas')->count(); // Ver cantidad de registros
```

### 4. Probar en el Navegador

1. Abre DevTools (F12)
2. Ve a la pestaña **Console**
3. No debe haber errores
4. Prueba hacer clic en "ENTREGAR"
5. Verifica que aparezca una notificación

---

## Solucionar Problemas Comunes

### Error: "Undefined variable: pedidosAgrupados"

**Solución:**
```bash
# Verificar que el controlador está en el lugar correcto
ls -la app/Http/Controllers/Bodega/

# Verificar que la ruta está correctamente configurada
php artisan route:list | grep bodega
```

### Error: "Meta CSRF token not found"

**Solución:** En `resources/views/layouts/app.blade.php` añade:
```html
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

### Error 401 Unauthorized

**Solución:** El usuario no tiene permiso:
```bash
php artisan tinker
```

```php
$user = User::find(1);
$user->givePermissionTo('view-bodega-pedidos');
```

### Tabla vacía

**Solución:** Cargar datos de ejemplo:
```bash
php artisan db:seed ReciboPrendaSeeder
```

### Estilos Tailwind no se ven

**Solución:** Si compilas Tailwind, actualiza `tailwind.config.js`:
```js
module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
}
```

Luego compila:
```bash
npm run build
```

---

##  Archivos Necesarios

Verifica que existan estos archivos:

```
✓ resources/views/bodega/pedidos.blade.php
✓ public/js/bodega-pedidos.js
✓ app/Http/Controllers/Bodega/PedidosController.php
✓ routes/bodega.php
✓ app/Models/ReciboPrenda.php
✓ database/migrations/*_create_recibo_prendas_table.php
✓ resources/views/layouts/app.blade.php (con meta csrf-token)
```

---

## 🚨 Si Todo Falla

### Opción 1: Limpiar Caché

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Opción 2: Regenerar Aplicación

```bash
php artisan config:cache
php artisan route:cache
```

### Opción 3: Verificar Logs

```bash
tail -f storage/logs/laravel.log
```

### Opción 4: Modo Debug

En `.env`:
```
APP_DEBUG=true
```

---

##  Datos de Prueba

Para cargar datos de ejemplo:

```bash
php artisan db:seed ReciboPrendaSeeder
```

Esto crea 6 pedidos con múltiples items.

---

##  Configuración de Seguridad

### Middleware Recomendado

El módulo incluye:
-  Validación CSRF
-  Autorización por rol
-  Validación de permisos
-  Logging de actividades
-  Sanitización de inputs

---

## 🎓 Próximos Pasos

Después de instalar:

1. **Personalizar diseño:** Edita `resources/views/bodega/pedidos.blade.php`
2. **Agregar filtros:** Modifica el JavaScript
3. **Agregar campos:** Actualiza modelo y migración
4. **Exportar datos:** Implementa método `export()` en controlador
5. **Integrar con sistema:** Añade menú de navegación

---

## 📞 Soporte

Si encuentras problemas:

1. Verifica los logs: `storage/logs/laravel.log`
2. Abre DevTools en el navegador (F12)
3. Verifica permisos de usuario
4. Ejecuta: `php artisan cache:clear && php artisan config:clear`

---

## ✨ ¡Listo!

Tu módulo de bodega debe estar funcional. Si no es así, revisa el checklist anterior.

Última actualización: Febrero 2026
