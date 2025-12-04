# 🚀 ACTIVAR ROL SUPERVISOR_PEDIDOS - INSTRUCCIONES RÁPIDAS

## ⚡ 3 Pasos para Activar

### Paso 1: Crear el Rol en la BD

Opción A - Usando SQL directo:
```sql
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at) 
VALUES ('supervisor_pedidos', 'Supervisor de Pedidos de Producción', 0, NOW(), NOW());
```

Opción B - Usando Tinker:
```bash
php artisan tinker
```

Luego en Tinker:
```php
DB::table('roles')->insert([
    'name' => 'supervisor_pedidos',
    'description' => 'Supervisor de Pedidos de Producción',
    'requires_credentials' => 0,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Paso 2: Asignar el Rol a un Usuario

Opción A - Rol principal:
```bash
php artisan tinker
```

```php
$user = User::find(1); // Cambiar 1 por el ID del usuario
$user->role_id = 5; // Cambiar 5 por el ID del rol creado
$user->save();
```

Opción B - Múltiples roles (si tienes la migración):
```php
$user = User::find(1);
$user->addRole(5); // Agregar rol supervisor_pedidos
```

### Paso 3: Verificar Acceso

1. Acceder a: `http://localhost:8000/supervisor-pedidos/`
2. Debería ver la tabla de órdenes
3. Si no, verificar que:
   - El usuario esté autenticado
   - El usuario tenga el rol correcto
   - Las rutas estén registradas

---

## 🔍 Verificación

### Verificar que el rol existe:
```bash
php artisan tinker
```
```php
DB::table('roles')->where('name', 'supervisor_pedidos')->first();
```

Debería retornar algo como:
```
{
  "id": 5,
  "name": "supervisor_pedidos",
  "description": "Supervisor de Pedidos de Producción",
  "requires_credentials": 0,
  "created_at": "2025-12-04 10:30:00",
  "updated_at": "2025-12-04 10:30:00"
}
```

### Verificar que el usuario tiene el rol:
```php
$user = User::find(1);
$user->role_id; // Debería ser 5 (o el ID del rol)
```

### Verificar que las rutas existen:
```bash
php artisan route:list | grep supervisor-pedidos
```

Debería mostrar:
```
GET|HEAD  /supervisor-pedidos                                    supervisor-pedidos.index
GET|HEAD  /supervisor-pedidos/{id}                               supervisor-pedidos.show
GET|HEAD  /supervisor-pedidos/{id}/pdf                           supervisor-pedidos.pdf
POST      /supervisor-pedidos/{id}/anular                        supervisor-pedidos.anular
PATCH     /supervisor-pedidos/{id}/estado                        supervisor-pedidos.cambiar-estado
GET|HEAD  /supervisor-pedidos/{id}/datos                         supervisor-pedidos.datos
```

---

## 🧪 Pruebas Rápidas

### Test 1: Acceso a la página
```
1. Abrir: http://localhost:8000/supervisor-pedidos/
2. Debería ver tabla de órdenes
3. Si ve error 403: Verificar rol del usuario
4. Si ve error 404: Verificar rutas en web.php
```

### Test 2: Ver detalle de orden
```
1. Hacer clic en botón "Ver" (ojo)
2. Debería abrirse modal con detalles
3. Verificar que muestre información correcta
```

### Test 3: Descargar PDF
```
1. Hacer clic en botón "PDF"
2. Debería descargar archivo PDF
3. Abrir PDF y verificar contenido
```

### Test 4: Anular orden
```
1. Hacer clic en botón "Anular"
2. Debería abrirse modal de confirmación
3. Ingresar motivo (mínimo 10 caracteres)
4. Hacer clic en "Confirmar Anulación"
5. Debería mostrar mensaje de éxito
6. Página debería recargarse
7. Orden debería aparecer con estado "Anulada"
```

---

## 🐛 Troubleshooting

### Error: "No tienes permiso para acceder a esta sección"
**Causa**: Usuario no tiene el rol correcto
**Solución**: 
```php
$user = User::find(1);
$user->role_id = 5; // ID del rol supervisor_pedidos
$user->save();
```

### Error: "Route not found" (404)
**Causa**: Rutas no están registradas
**Solución**: 
1. Verificar que `routes/web.php` tenga las rutas (líneas 372-393)
2. Ejecutar: `php artisan route:clear`
3. Ejecutar: `php artisan cache:clear`

### Error: "Call to undefined method" en Controller
**Causa**: Falta importar modelos
**Solución**: Verificar imports en `SupervisorPedidosController.php`:
```php
use App\Models\PedidoProduccion;
use Barryvdh\DomPDF\Facade\Pdf;
```

### Modal no se abre
**Causa**: JavaScript error
**Solución**: 
1. Abrir DevTools (F12)
2. Ir a Console
3. Buscar errores
4. Verificar que jQuery esté cargado

### PDF no se descarga
**Causa**: `barryvdh/laravel-dompdf` no instalado
**Solución**:
```bash
composer require barryvdh/laravel-dompdf
```

---

## 📋 Checklist de Implementación

- [ ] Crear rol en BD
- [ ] Asignar rol a usuario
- [ ] Verificar rutas en web.php
- [ ] Verificar controller existe
- [ ] Verificar vistas existen
- [ ] Verificar sidebar existe
- [ ] Probar acceso a /supervisor-pedidos/
- [ ] Probar ver detalle de orden
- [ ] Probar descargar PDF
- [ ] Probar anular orden
- [ ] Verificar logs de auditoría

---

## 📞 Soporte

Si encuentras problemas:

1. Revisar `storage/logs/laravel.log` para errores
2. Ejecutar: `php artisan tinker` para debugging
3. Verificar que todos los archivos existan:
   - `app/Http/Controllers/SupervisorPedidosController.php`
   - `resources/views/supervisor-pedidos/index.blade.php`
   - `resources/views/supervisor-pedidos/pdf.blade.php`
   - `resources/views/components/sidebars/sidebar-supervisor-pedidos.blade.php`

---

## 🎉 ¡Listo!

Una vez completados los 3 pasos, el rol `supervisor_pedidos` estará completamente funcional.

**Acceso**: http://localhost:8000/supervisor-pedidos/

**Fecha**: Diciembre 2025
**Versión**: 1.0
