# 📦 GUÍA DE INSTALACIÓN - CARTERA PEDIDOS

##  Paso a Paso de Implementación

### FASE 1: Preparación

#### 1.1 Verificar archivos creados
```bash
# Verifica que estos archivos existen:
ls resources/views/cartera-pedidos/
ls public/css/cartera-pedidos/
ls public/js/cartera-pedidos/
```

**Archivos esperados:**
```
✓ resources/views/cartera-pedidos/cartera_pedidos.blade.php
✓ public/css/cartera-pedidos/cartera_pedidos.css
✓ public/js/cartera-pedidos/cartera_pedidos.js
```

#### 1.2 Verificar documentación
```
✓ CARTERA_PEDIDOS_DOCUMENTACION.md
✓ CARTERA_PEDIDOS_RESUMEN.md
✓ CARTERA_PEDIDOS_TESTING.md
✓ EJEMPLO_CONTROLADOR_CARTERA_PEDIDOS.php
```

---

### FASE 2: Configuración Base

#### 2.1 Crear rol 'cartera' (si no existe)

En `database/seeders/` crear un seeder:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CarteraRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'cartera', 'guard_name' => 'web'],
            ['description' => 'Rol de Cartera - Aprueba y rechaza pedidos']
        );
    }
}
```

**Ejecutar:**
```bash
php artisan db:seed --class=CarteraRoleSeeder
```

#### 2.2 Crear ruta web

En `routes/web.php`:

```php
// Grupo de Cartera
Route::middleware(['auth', 'role:cartera,admin'])->group(function () {
    Route::get('/cartera/pedidos', function () {
        return view('cartera-pedidos.cartera_pedidos');
    })->name('cartera.pedidos');
});
```

#### 2.3 Crear rutas API

En `routes/api.php`:

```php
use App\Http\Controllers\API\CarterapedidoController;

Route::middleware(['auth:sanctum', 'role:cartera,admin'])->group(function () {
    // Listar pedidos
    Route::get('/pedidos', [CarterapedidoController::class, 'index']);
    
    // Aprobar pedido
    Route::post('/pedidos/{id}/aprobar', [CarterapedidoController::class, 'aprobar']);
    
    // Rechazar pedido
    Route::post('/pedidos/{id}/rechazar', [CarterapedidoController::class, 'rechazar']);
});
```

---

### FASE 3: Base de Datos

#### 3.1 Crear migración

El archivo ya está creado:
```
database/migrations/2024_01_23_000000_agregar_campos_cartera_pedidos.php
```

**Ejecutar migración:**
```bash
php artisan migrate
```

#### 3.2 Verificar tabla pedidos

Después de migrar, verifica que la tabla tiene estos campos:
- `aprobado_por_usuario_cartera` (nullable)
- `aprobado_por_cartera_en` (nullable)
- `rechazado_por_usuario_cartera` (nullable)
- `rechazado_por_cartera_en` (nullable)
- `motivo_rechazo_cartera` (nullable)

```bash
# En terminal SQL o phpMyAdmin
DESCRIBE pedidos;  # O SHOW COLUMNS FROM pedidos;
```

---

### FASE 4: Implementación del Controlador

#### 4.1 Crear el controlador

Crear archivo: `app/Http/Controllers/API/CarterapedidoController.php`

Copiar contenido de: `EJEMPLO_CONTROLADOR_CARTERA_PEDIDOS.php`

```bash
# O crear con artisan:
php artisan make:controller API/CarterapedidoController
```

#### 4.2 Ajustar el controlador

```php
<?php

namespace App\Http\Controllers\API;

use App\Models\Pedido;
use App\Models\HistorialCambiosPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class CarterapedidoController extends Controller
{
    // ... copiar métodos de EJEMPLO_CONTROLADOR_CARTERA_PEDIDOS.php
}
```

---

### FASE 5: Testing

#### 5.1 Test de rutas

```bash
# Verificar que las rutas existen
php artisan route:list | grep cartera
php artisan route:list | grep pedidos
```

#### 5.2 Test en navegador

1. Acceder a: `http://localhost:8000/cartera/pedidos`
2. Debería ver la página con tabla vacía (mientras no haya datos)
3. Abrir DevTools (F12) → Console
4. Ver logs confirmando que se cargó correctamente

#### 5.3 Test de API con Postman/Insomnia

**1. GET /api/pedidos**
```
Method: GET
URL: http://localhost:8000/api/pedidos?estado=pendiente_cartera
Headers: 
  - Accept: application/json
  - X-CSRF-TOKEN: (copiar del meta tag de la página)
```

**Respuesta esperada:** 200 OK con array de pedidos

#### 5.4 Insertar datos de prueba

```sql
-- En la base de datos, agregar pedidos en estado "Pendiente cartera"
INSERT INTO pedidos (
    numero_pedido,
    cliente,
    estado,
    fecha_de_creacion_de_orden,
    created_at,
    updated_at
) VALUES (
    'PED-TEST-001',
    'Cliente Prueba',
    'Pendiente cartera',
    NOW(),
    NOW(),
    NOW()
);
```

---

### FASE 6: Asignar Usuarios

#### 6.1 Asignar rol a usuario

En `database/seeders/` o manualmente:

```php
// Opción 1: Seeder
$user = User::find(1);  // Cambiar ID según necesario
$user->assignRole('cartera');

// Opción 2: Query SQL
UPDATE user_roles 
SET role_id = (SELECT id FROM roles WHERE name = 'cartera') 
WHERE user_id = 1;
```

#### 6.2 Verificar permisos

```bash
# Hacer login con usuario que tiene rol 'cartera'
# Acceder a http://localhost:8000/cartera/pedidos
# Debe funcionar correctamente
```

---

### FASE 7: Pruebas Completas

#### 7.1 Tabla carga correctamente
- [ ] Ver pedidos en estado "Pendiente cartera"
- [ ] Columnas correctas: # Pedido, Cliente, Estado, Fecha, Acciones
- [ ] Botones Aprobar y Rechazar visibles

#### 7.2 Modal de Aprobación
- [ ] Clic en Aprobar → abre modal
- [ ] Modal muestra datos del pedido
- [ ] Clic en "Aprobar Pedido" → env'a POST /api/pedidos/{id}/aprobar
- [ ] Respuesta correcta → tabla se recarga
- [ ] Pedido desaparece de la tabla (ya no está en "Pendiente cartera")

#### 7.3 Modal de Rechazo
- [ ] Clic en Rechazar → abre modal
- [ ] Textarea para motivo
- [ ] Contador de caracteres funciona
- [ ] Clic en "Confirmar Rechazo" → envía POST /api/pedidos/{id}/rechazar
- [ ] Respuesta correcta → tabla se recarga
- [ ] Pedido desaparece de la tabla

#### 7.4 Notificaciones
- [ ] Toast de éxito aparece después de aprobar/rechazar
- [ ] Toast de error aparece si falla
- [ ] Toast desaparece automáticamente

#### 7.5 Validaciones
- [ ] Si motivo < 10 caracteres → muestra advertencia
- [ ] Si motivo > 1000 caracteres → se trunca/advierte
- [ ] Token CSRF se incluye en todas las requests

---

### FASE 8: Auditoría (Opcional pero Recomendado)

#### 8.1 Registrar cambios en historial

Si existe tabla `historial_cambios_pedidos`:

```sql
INSERT INTO historial_cambios_pedidos (
    pedido_id,
    estado_anterior,
    estado_nuevo,
    usuario_id,
    rol_usuario,
    comentario,
    fecha_cambio
) VALUES (
    ?,
    'Pendiente cartera',
    'Aprobado por Cartera',
    ?,
    'cartera',
    'Pedido aprobado por cartera',
    NOW()
);
```

---

### FASE 9: Notificaciones (Opcional)

#### 9.1 Enviar email al cliente

En el controlador, después de aprobar/rechazar:

```php
// Enviar notificación al cliente
if ($pedido->email_cliente) {
    Mail::to($pedido->email_cliente)->send(
        new PedidoAprobadoNotification($pedido)
    );
}
```

---

## 🔍 Checklist de Validación

**Antes de usar en producción:**

- [ ] Archivos en ubicaciones correctas
- [ ] Rutas configuradas
- [ ] Migración ejecutada
- [ ] Rol 'cartera' creado
- [ ] Usuarios tienen el rol asignado
- [ ] Controlador implementado
- [ ] API endpoints funcionan (probado con Postman)
- [ ] Interfaz carga correctamente
- [ ] Botones Aprobar y Rechazar funcionan
- [ ] Modales se abren y cierran
- [ ] Notificaciones se muestran
- [ ] Tabla se recarga después de acciones
- [ ] Validaciones funcionan (contadores, caracteres)
- [ ] Logs en consola son informativos
- [ ] Responsiveness verificada en mobile
- [ ] Auditoría registra cambios
- [ ] Emails de notificación se envían (si aplica)

---

## 🚨 Troubleshooting

### La página no carga
```
Solución: 
1. Verificar que la ruta existe: php artisan route:list | grep cartera
2. Verificar permisos del usuario: usuario debe tener rol 'cartera'
3. Revisar logs: tail -f storage/logs/laravel.log
```

### API returns 404
```
Solución:
1. Verificar que rutas API están en routes/api.php
2. Verificar que controlador existe y tiene los métodos
3. Ejecutar: php artisan route:cache --clear
```

### CSRF Token error
```
Solución:
1. Verificar meta tag: <meta name="csrf-token" content="...">
2. Verificar que está en layout.blade.php
3. Limpiar cache: php artisan config:cache
```

### Modal no se abre
```
Solución:
1. Abrir DevTools (F12)
2. Revisar Console para errores
3. Verificar que CSS está cargando
4. Verificar que JS está cargando
```

### Tabla vacía aunque hay pedidos
```
Solución:
1. Verificar estado del pedido: SELECT estado FROM pedidos;
2. Debe ser exactamente: "Pendiente cartera"
3. Verificar query en: GET /api/pedidos?estado=pendiente_cartera
4. Ver respuesta en Network tab del DevTools
```

---

## 📞 Comandos Útiles

```bash
# Limpiar caches
php artisan cache:clear
php artisan config:cache --clear
php artisan route:cache --clear
php artisan view:clear

# Ver rutas
php artisan route:list

# Ver roles
php artisan tinker
>>> Role::all();

# Ver permisos de usuario
php artisan tinker
>>> Auth::user()->getRoleNames();
```

---

##  Archivos de Referencia

| Archivo | Propósito |
|---------|----------|
| `cartera_pedidos.blade.php` | Vista principal |
| `cartera_pedidos.css` | Estilos |
| `cartera_pedidos.js` | Lógica JavaScript |
| `CarterapedidoController.php` | Controlador API |
| `Migración: agregar_campos_cartera_pedidos.php` | Campos en BD |
| `CARTERA_PEDIDOS_DOCUMENTACION.md` | Especificación técnica |
| `CARTERA_PEDIDOS_TESTING.md` | Guía de testing |

---

## ✨ Próximos Pasos

1. **Notificaciones por email** - Avisar al cliente cuando se rechaza
2. **SMS** - Notificación inmediata
3. **Dashboard** - Gráficas de pedidos aprobados/rechazados
4. **Reportes** - Exportar a Excel/PDF
5. **Automatización** - Auto-aprobar con límites de crédito

---

**Fecha de creación:** 23 de Enero, 2024  
**Estado:**  Completado y listo para usar  
**Versión:** 1.0
