# Guía: Validación de Roles en Frontend

## Problema Original
El sistema mostraba errores **403 (Forbidden)** cuando:
1. El usuario veía una opción en el menú
2. Clickeaba en ella
3. No tenía permisos → Error 403

**Solución:** Ocultar opciones en el frontend que el usuario NO puede usar.

## Componente: `<x-auth.can-access>`

Se creó un componente Blade para validar si el usuario tiene ciertos roles.

### Ubicación
`resources/views/components/auth/can-access.blade.php`

### Uso Básico

```blade
<x-auth.can-access roles="asesor,admin">
  <a href="{{ route('mi-ruta') }}">Mi Opción</a>
</x-auth.can-access>
```

**Solo se muestra si el usuario tiene rol `asesor` O `admin`.**

### Parámetros

- `roles` (requerido): Lista de roles separados por coma
  - Ejemplo: `roles="bodeguero,admin"`
  - Ejemplo: `roles="asesor,supervisor_pedidos"`

### Ejemplos Reales

#### En un Sidebar
```blade
<x-auth.can-access roles="bodeguero,supervisor_pedidos,admin">
  <li class="menu-item">
    <a href="{{ route('gestion-bodega.index') }}" class="menu-link">
      <span class="icon">📦</span>
      <span>Gestión de Bodega</span>
    </a>
  </li>
</x-auth.can-access>
```

#### En un Botón
```blade
<x-auth.can-access roles="asesor,admin">
  <button class="btn btn-primary" onclick="crear_nuevo_pedido()">
    Crear Pedido
  </button>
</x-auth.can-access>
```

#### Con Múltiples Opciones
```blade
<div class="menu-section">
  <h3>Gestión</h3>
  
  <x-auth.can-access roles="bodeguero,admin">
    <a href="{{ route('bodega.index') }}">Bodega</a>
  </x-auth.can-access>
  
  <x-auth.can-access roles="despacho,admin">
    <a href="{{ route('despacho.index') }}">Despacho</a>
  </x-auth.can-access>
  
  <x-auth.can-access roles="asesor,admin">
    <a href="{{ route('asesores.dashboard') }}">Panel Asesor</a>
  </x-auth.can-access>
</div>
```

## Dónde Agregar Validaciones

### 1. **Sidebars** (IMPORTANTE)
- `resources/views/components/sidebars/sidebar-asesores.blade.php`
- `resources/views/components/sidebars/sidebar-despacho.blade.php`
- `resources/views/components/sidebars/sidebar-bodega.blade.php`
- etc.

Rodea opciones que requieren roles específicos.

### 2. **Botones de Acción**
En formularios, modales, y vistas que tienen botones que requieren permisos.

### 3. **Secciones Dinámicas**
En cualquier lugar donde se muestren opciones que dependen de roles.

## Roles en el Sistema

### Asesor
```
roles: asesor
```
Acceso a: Cotizaciones, Pedidos, Inventario de Telas

### Bodeguero
```
roles: bodeguero, EPP-Bodega
```
Acceso a: Gestión de Bodega, Recibos

### Despacho
```
roles: despacho
```
Acceso a: Despacho, Entregas

### Supervisor Pedidos
```
roles: supervisor_pedidos
```
Acceso a: Todo (tiene permisos amplios)

### Admin
```
roles: admin
```
Acceso a: Todo

## Validación en Backend

Aunque agregamos validación en frontend, **el backend sigue siendo la fuente de verdad**:

- Middleware `CheckRole` en `app/Http/Middleware/CheckRole.php`
- Middleware específicos: `CheckBodegaAccess`, `CheckDespachoRole`, etc.

## Diferencia: Frontend vs Backend

| Aspecto | Frontend | Backend |
|---------|----------|---------|
| **Propósito** | UX: Ocultar opciones unavailable | Seguridad: Proteger rutas |
| **Confiabilidad** | Puede ser bypasseada | No puede ser bypasseada |
| **Error 403** | No debería ocurrir | Ocurre si falla validación |

## Mensajes de Error

Si ocurre un error 403 (por cualquier razón), el usuario verá:

```
"No tienes permisos para acceder a esta sección."
```

O el mensaje específico del middleware:
```
"No tienes permisos para acceder a bodega"
"No tienes permiso para acceder al módulo de despacho"
```

## Checklist de Implementación

- [ ] Revisar sidebar-asesores.blade.php
- [ ] Revisar sidebar-despacho.blade.php
- [ ] Revisar sidebar-bodega.blade.php
- [ ] Revisar sidebar-supervisor-pedidos.blade.php
- [ ] Agregar validaciones donde corresponda
- [ ] Probar que opciones se ocultan/muestran correctamente
- [ ] Verificar que los permisos en backend y frontend coincidan

## Consulta de Debugging

Si necesitas ver qué roles tiene el usuario actual:

```blade
<!-- En una vista Blade -->
<pre>Roles: @json(auth()->user()->roles->pluck('name')->toArray())</pre>
```

```javascript
// En la consola del navegador
console.log('Roles del usuario:', window.usuarioAutenticado.roles);
```
