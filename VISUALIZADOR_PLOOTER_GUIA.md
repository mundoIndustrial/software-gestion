# Guía: Crear Usuario "Visualizador de Plooter"

## 🎯 Objetivo
Crear un usuario que solo pueda **visualizar** el registro de plooter sin poder realizar ninguna modificación.

## 📋 Permisos del Rol "Visualizador de Plooter"

### ✅ Permitido:
- Ver tabla de plooter completa
- Ver detalles de recibos y fechas

### ❌ NO Permitido:
- Registrar fecha de envío
- Registrar fecha de llegada
- Eliminar fechas de llegada
- Eliminar registros
- Modificar cualquier dato

---

## 🚀 Pasos para Crear el Usuario

### Opción 1: Usar Script PHP (Recomendado)

```bash
# Navegar al directorio raíz del proyecto
cd c:\Users\Usuario\Documents\mundoindustrial

# Ejecutar el script
php crear_usuario_visualizador_plooter.php
```

El script te guiará para:
1. Crear el rol (si no existe)
2. Solicitar datos del nuevo usuario
3. Crear el usuario con permisos correctos
4. Mostrar un resumen de la creación

### Opción 2: Crear Manualmente en Base de Datos (Advanced)

#### 1. Crear el Rol
```sql
INSERT INTO roles (name, description, requires_credentials, created_at, updated_at)
VALUES (
    'visualizador_plooter',
    'Visualizador de Plooter - Solo puede ver el registro de plooter (solo lectura)',
    true,
    NOW(),
    NOW()
);

-- Obtener el ID del rol creado
SELECT id FROM roles WHERE name = 'visualizador_plooter';
-- Resultado: guardamos este ID (ej: 15)
```

#### 2. Crear el Usuario
```sql
INSERT INTO users (
    nombre,
    email,
    username,
    password,
    roles_ids,
    secciones_permitidas,
    estado,
    created_at,
    updated_at
)
VALUES (
    'Nombre del Usuario',
    'email@ejemplo.com',
    'username',
    'PASSWORD_ENCRIPTADO', -- Ver cómo encriptar abajo
    '[15]',               -- El ID del rol visualizador_plooter
    'plooter',
    'activo',
    NOW(),
    NOW()
);
```

**Para encriptar la contraseña en PHP:**
```php
php -r "echo bcrypt('contraseña');"
```

---

## 🔐 Cómo Funciona la Protección

### 1. Vista (index.blade.php)
```blade
@php
    $userRoles = auth()->user()->roles->pluck('name')->toArray();
    $esVisualizador = in_array('visualizador_plooter', $userRoles) && count($userRoles) === 1;
@endphp

@if(!$esVisualizador)
    <!-- Mostrar botones de acción -->
    <button onclick="registrarFechaLlegada(...)">...</button>
@else
    <!-- Mostrar solo "Lectura" -->
    <span>📖 Lectura</span>
@endif
```

### 2. Controlador (PlooterController.php)
```php
private function verificarPermisoModificacion()
{
    $user = Auth::user();
    $roles = $user->roles->pluck('name')->toArray();
    
    // Si el usuario SOLO tiene visualizador_plooter
    if (in_array('visualizador_plooter', $roles) && count($roles) === 1) {
        return false; // No permitir modificación
    }
    
    return true;
}
```

Cada método de modificación verifica:
- `registrarFechaEnvio()` - Verifica permisos
- `registrarFechaLlegada()` - Verifica permisos
- `remover()` - Verifica permisos

**Respuesta si no tiene permisos:**
```json
{
    "success": false,
    "message": "No tienes permiso para registrar fechas de llegada en plooter"
}
```

---

## 🧪 Prueba de Funcionamiento

### 1. Iniciar sesión como visualizador
- Email/Username: El que creaste
- Contraseña: La que configuraste

### 2. Acceder a plooter
- URL: `/insumos/plooter`

### 3. Verificar que:
- ✅ Puedes ver la tabla
- ✅ Los botones de acción están deshabilitados
- ✅ Ves "📖 Lectura" en lugar de los botones
- ✅ Si intentas hacer POST desde la consola → Error 403 Forbidden

---

## 📝 Ejemplo de Prueba JavaScript

Si quieres probar que la API rechaza modificaciones:

```javascript
// Abrir consola (F12) y ejecutar:
fetch('/insumos/plooter/1/registrar-fecha-llegada', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ fecha_llegada: '2026-03-19' })
})
.then(r => r.json())
.then(d => console.log(d));

// Resultado esperado:
// {
//     "success": false,
//     "message": "No tienes permiso para registrar fechas de llegada en plooter"
// }
```

---

## 🔄 Cambios Realizados

### 1. **Seeder Creado**
- `database/seeders/AddVisualizadorPlooterRoleSeeder.php`
- Crea el rol automáticamente

### 2. **Controlador Protegido**
- `app/Infrastructure/Http/Controllers/Insumos/PlooterController.php`
- Agregado método `verificarPermisoModificacion()`
- Protegidos: `remover()`, `registrarFechaEnvio()`, `registrarFechaLlegada()`

### 3. **Vista Actualizada**
- `resources/views/insumos/plooter/index.blade.php`
- Botones deshabilitados para visualizadores
- Muestra indicador "📖 Lectura"

### 4. **Script de Creación**
- `crear_usuario_visualizador_plooter.php`
- Guía interactiva para crear usuarios

---

## ❓ Preguntas Frecuentes

**P: ¿Qué pasa si un visualizador intenta modificar desde la consola?**
R: Obtiene un error 403 Forbidden con mensaje "No tienes permiso..."

**P: ¿Puede un visualizador acceder a otras partes de la aplicación?**
R: Depende de la configuración de rutas. El rol SOLO tiene permisos de lectura en plooter.

**P: ¿Cómo cambio de visualizador a editor?**
R: Cambia los roles del usuario en la gestión de usuarios. Agrega otro rol que tenga permisos completos.

**P: ¿Es reversible?**
R: Sí, puedes eliminar el rol o cambiar los roles del usuario en cualquier momento.

---

## 📞 Soporte

Si tienes preguntas sobre la implementación, revisa:
- Modelo User: `app/Models/User.php`
- Modelo Role: `app/Models/Role.php`
- Tests: Ejecuta `php crear_usuario_visualizador_plooter.php` para prueba completa
