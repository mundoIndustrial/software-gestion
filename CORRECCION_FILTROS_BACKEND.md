# Corrección de Filtros Rápidos de Pedidos - Backend

## Resumen
Se corrigió el filtrado de pedidos para que funcione correctamente desde el backend (controlador), eliminando la necesidad de filtrado puramente JavaScript.

## Cambios Realizados

### 1. **PedidosProduccionController.php** (Asesores)
**Archivo**: `app/Http/Controllers/Asesores/PedidosProduccionController.php`

#### Cambio en el método `index()`
```php
// ANTES
public function index()
{
    $pedidos = PedidoProduccion::whereHas('cotizacion', function ($query) {
        $query->where('asesor_id', Auth::id());
    })
    ->orderBy('created_at', 'desc')
    ->paginate(15);

    return view('asesores.pedidos.index', compact('pedidos'));
}

// DESPUÉS
public function index(Request $request)
{
    $query = PedidoProduccion::whereHas('cotizacion', function ($query) {
        $query->where('asesor_id', Auth::id());
    });

    // Filtrar por estado si se proporciona
    if ($request->has('estado')) {
        $estado = $request->input('estado');
        
        // Para "En Producción", filtrar por múltiples estados
        if ($estado === 'En Producción') {
            $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
        } else {
            $query->where('estado', $estado);
        }
    }

    $pedidos = $query->orderBy('created_at', 'desc')->paginate(15);

    return view('asesores.pedidos.index', compact('pedidos'));
}
```

**Cambios**:
- Agregado parámetro `Request $request` al método
- Implementado filtrado por parámetro `estado`
- Caso especial: "En Producción" filtra por múltiples estados ('No iniciado' y 'En Ejecución')
- Validación: ✅ No hay errores de sintaxis PHP

---

### 2. **SupervisorPedidosController.php**
**Archivo**: `app/Http/Controllers/SupervisorPedidosController.php`

#### Cambio en el método `index()` (línea 165)
```php
// ANTES
if ($request->filled('estado')) {
    $query->where('estado', $request->estado);
}

// DESPUÉS
if ($request->filled('estado')) {
    $estado = $request->estado;
    // Para "En Producción", filtrar por múltiples estados
    if ($estado === 'En Producción') {
        $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
    } else {
        $query->where('estado', $estado);
    }
}
```

**Cambios**:
- Mejorado el filtrado existente para manejar el caso especial "En Producción"
- Validación: ✅ No hay errores de sintaxis PHP

---

### 3. **Vista: asesores/pedidos/index.blade.php**
**Archivo**: `resources/views/asesores/pedidos/index.blade.php`

#### Corrección de la función `filtrarEnProduccion()` (línea 1099)
```blade
{{-- ANTES --}}
function filtrarEnProduccion() {
    // Filtrado JavaScript en la tabla
    const table = document.querySelector('table tbody');
    // ... lógica de filtrado en cliente
}

{{-- DESPUÉS --}}
function filtrarEnProduccion() {
    const url = new URL(window.location);
    url.searchParams.set('estado', 'En Producción');
    window.location.href = url.toString();
}
```

**Cambios**:
- Simplificado: Ahora redirige al controlador con `estado=En Producción`
- El backend filtrará correctamente por ambos estados ('No iniciado' y 'En Ejecución')

---

### 4. **Vista: supervisor-asesores/pedidos/index.blade.php**
**Archivo**: `resources/views/supervisor-asesores/pedidos/index.blade.php`

#### Corrección de la función `filtrarEnProduccionSupervisor()` (línea 961)
```blade
{{-- ANTES --}}
function filtrarEnProduccionSupervisor() {
    // Filtrado JavaScript en la tabla
    const table = document.querySelector('table tbody');
    // ... lógica de filtrado en cliente
}

{{-- DESPUÉS --}}
function filtrarEnProduccionSupervisor() {
    const url = new URL(window.location);
    url.searchParams.set('estado', 'En Producción');
    window.location.href = url.toString();
}
```

**Cambios**:
- Simplificado: Ahora redirige al controlador con `estado=En Producción`
- El backend filtrará correctamente por ambos estados ('No iniciado' y 'En Ejecución')

---

## Flujo de Filtrado Completo

### Filtros Rápidos en Pedidos (Asesores y Supervisores)

| Filtro | URL Parameter | Estados Mostrados | Backend Filter |
|--------|---------------|-------------------|----------------|
| **Todos** | Sin parámetro | Todos | `Sin WHERE` |
| **Pendientes** | `estado=Pendiente` | Pendiente | `where('estado', 'Pendiente')` |
| **En Producción** | `estado=En Producción` | No iniciado, En Ejecución | `whereIn('estado', ['No iniciado', 'En Ejecución'])` |
| **Entregados** | `estado=Entregado` | Entregado | `where('estado', 'Entregado')` |
| **Anulados** | `estado=Anulado` | Anulado | `where('estado', 'Anulado')` |

### Ventajas del Nuevo Enfoque

✅ **Backend Filtering**: El filtrado ocurre en la base de datos, no en JavaScript  
✅ **Paginación Correcta**: Los resultados paginados incluyen solo los registros filtrados  
✅ **Rendimiento**: Reduce carga de JavaScript en el cliente  
✅ **URL Shareable**: Los filtros se pueden compartir como URLs directas  
✅ **SEO-Friendly**: URLs con parámetros de filtro  
✅ **Múltiples Estados**: Maneja correctamente filtros con múltiples estados  

---

## Testing

### Test 1: Filtro "Pendientes"
```
1. Navega a: http://localhost:8000/asesores/pedidos?estado=Pendiente
2. Esperado: Solo mostrará pedidos con estado "Pendiente"
3. URL activa: Sí
```

### Test 2: Filtro "En Producción"
```
1. Navega a: http://localhost:8000/asesores/pedidos?estado=En Producción
2. Esperado: Mostrará pedidos con estado "No iniciado" o "En Ejecución"
3. URL activa: Sí
4. JavaScript: El filtro se maneja como redirección, no como filtrado en tabla
```

### Test 3: Filtro "Entregados"
```
1. Navega a: http://localhost:8000/asesores/pedidos?estado=Entregado
2. Esperado: Solo mostrará pedidos con estado "Entregado"
3. URL activa: Sí
```

### Test 4: Filtro "Anulados"
```
1. Navega a: http://localhost:8000/asesores/pedidos?estado=Anulado
2. Esperado: Solo mostrará pedidos con estado "Anulado"
3. URL activa: Sí
```

### Test 5: Supervisor - Filtro "En Producción"
```
1. Navega a: http://localhost:8000/supervisor-asesores/pedidos?estado=En Producción
2. Esperado: Mostrará solo los pedidos en estados "No iniciado" o "En Ejecución"
3. URL activa: Sí
```

---

## Validación de Sintaxis

✅ **PedidosProduccionController.php**: No hay errores de sintaxis  
✅ **SupervisorPedidosController.php**: No hay errores de sintaxis  
✅ **Vistas Blade**: Cambios únicamente en JavaScript, no afectan sintaxis Blade  

---

## Notas Importantes

1. **Caché de Laravel**: Si los cambios no se reflejan inmediatamente, ejecutar:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

2. **Botones Activos**: El CSS `.active` se aplica basándose en:
   - `request('estado')` para filtros de un solo estado
   - Condición múltiple para "En Producción": `(request('estado') === 'No iniciado' || request('estado') === 'En Ejecución')`

3. **Parámetros URL**: El valor del parámetro `estado` debe coincidir exactamente con:
   - `'Pendiente'`
   - `'No iniciado'` (para mostrar "En Producción")
   - `'En Ejecución'` (para mostrar "En Producción")
   - `'Entregado'`
   - `'Anulado'`
   - `'En Producción'` (especial: se convierte a múltiples estados en el backend)

---

## Archivo de Instalación/Implementación

Todos los cambios están listos para producción. No se requieren migraciones ni cambios en base de datos.

**Cambios de archivos**:
- ✅ `app/Http/Controllers/Asesores/PedidosProduccionController.php`
- ✅ `app/Http/Controllers/SupervisorPedidosController.php`
- ✅ `resources/views/asesores/pedidos/index.blade.php`
- ✅ `resources/views/supervisor-asesores/pedidos/index.blade.php`

**Comando de verificación** (después de deploying):
```bash
php artisan tinker
# En el tinker:
> PedidoProduccion::where('estado', 'Pendiente')->count()
> PedidoProduccion::whereIn('estado', ['No iniciado', 'En Ejecución'])->count()
```
