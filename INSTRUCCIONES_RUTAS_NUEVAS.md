# 📍 Actualización de Rutas - Nueva Arquitectura de Pedidos

## 🔄 Cambios Necesarios en `routes/web.php`

### Antes (ANTIGUO)
```php
// ❌ ANTIGUO - Una sola ruta
Route::get('/crear-pedido', [PedidoController::class, 'crear'])
    ->name('asesores.crear-pedido');
    
Route::get('/crear-pedido-desde-cotizacion', [PedidoController::class, 'crearDesdeCotizacion'])
    ->name('asesores.crear-pedido-desde-cotizacion');
```

### Después (NUEVO - Recomendado)
```php
// ✅ NUEVO - Ruta unificada con parámetro
Route::get('/crear-pedido/{tipo?}', [PedidoController::class, 'crearPedido'])
    ->where('tipo', 'cotizacion|nuevo')
    ->defaults('tipo', 'cotizacion')
    ->name('asesores.crear-pedido');
```

## 🎯 Actualización del Controller

### Antes (ANTIGUO)
```php
public function crearDesdeCotizacion()
{
    return view('asesores.pedidos.crear-desde-cotizacion-editable', [
        'tipoInicial' => 'cotizacion',
        'cotizacionesData' => Cotizacion::all()
    ]);
}

public function crearPedidoNuevo()
{
    return view('asesores.pedidos.crear-desde-cotizacion-editable', [
        'tipoInicial' => 'nuevo'
    ]);
}
```

### Después (NUEVO - Simplificado)
```php
public function crearPedido($tipo = 'cotizacion')
{
    if (!in_array($tipo, ['cotizacion', 'nuevo'])) {
        $tipo = 'cotizacion';
    }

    $data = [
        'tipoInicial' => $tipo
    ];

    // Si es cotización, agregar datos de cotizaciones
    if ($tipo === 'cotizacion') {
        $data['cotizacionesData'] = Cotizacion::with(['items'])->get();
    }

    return view('asesores.pedidos.crear-pedido', $data);
}
```

## 🔗 URLs Resultantes

### Flujo Cotización
```
GET /asesores/crear-pedido
GET /asesores/crear-pedido/cotizacion
→ Ambas abren la vista "desde cotización"
```

### Flujo Nuevo
```
GET /asesores/crear-pedido/nuevo
→ Abre la vista "nuevo pedido"
```

## 🖇️ Referencias en Vistas y JavaScript

### En Blade (para links)
```blade
<!-- Link para crear desde cotización -->
<a href="{{ route('asesores.crear-pedido', 'cotizacion') }}">
    Crear desde Cotización
</a>

<!-- Link para crear nuevo -->
<a href="{{ route('asesores.crear-pedido', 'nuevo') }}">
    Crear Nuevo Pedido
</a>

<!-- Link por defecto (redirige a cotización) -->
<a href="{{ route('asesores.crear-pedido') }}">
    Crear Pedido
</a>
```

### En JavaScript (para redirecciones)
```javascript
// Redireccionar a cotización
window.location.href = '{{ route("asesores.crear-pedido", "cotizacion") }}';

// Redireccionar a nuevo
window.location.href = '{{ route("asesores.crear-pedido", "nuevo") }}';
```

## 📋 Vista Antigua vs Nueva

### Antigua (crear-desde-cotizacion-editable.blade.php)
```
❌ 926 líneas
❌ Mezcla dos flujos
❌ Condicionales complejos
❌ Difícil de mantener
```

### Nueva (crear-pedido.blade.php)
```
✅ 50 líneas
✅ Solo orquestación
✅ Claro y simple
✅ Fácil de mantener
```

## 📚 Estructura Definitiva

```
routes/
└── web.php
    └── Route::get('/crear-pedido/{tipo?}', [...])

app/Http/Controllers/
└── PedidoController.php
    └── public function crearPedido($tipo)

resources/views/asesores/pedidos/
├── crear-pedido.blade.php                    (ROUTER)
├── crear-pedido-desde-cotizacion.blade.php   (FLUJO ESPECÍFICO)
├── crear-pedido-nuevo.blade.php              (FLUJO ESPECÍFICO)
└── components/
    ├── prendas-editable.blade.php
    └── reflectivo-editable.blade.php
```

## ✅ Verificación

Después de hacer los cambios, verificar:

```php
// En routes/web.php
✅ Ruta con parámetro opcional creada
✅ where() con validación de tipos

// En PedidoController
✅ Parámetro $tipo recibido
✅ Validación de tipo
✅ Vista 'asesores.pedidos.crear-pedido' usada
✅ $tipoInicial pasado correctamente

// En crear-pedido.blade.php
✅ Verifica $tipoInicial
✅ Incluye vista correcta según tipo
```

## 🔗 Migración de URLs Antiguas

Si tienes URLs antiguas en tu aplicación:

```php
// Agregar redirects temporales (opcional)
Route::redirect('/crear-pedido-desde-cotizacion', '/crear-pedido/cotizacion');
Route::redirect('/crear-pedido-nuevo', '/crear-pedido/nuevo');
```

## 📝 Ejemplo Completo de web.php

```php
// Grupo de rutas para asesores
Route::middleware(['auth', 'role:asesor'])->prefix('asesores')->group(function () {
    
    // Crear Pedido (Router - maneja ambos flujos)
    Route::get('/crear-pedido/{tipo?}', [PedidoController::class, 'crearPedido'])
        ->where('tipo', 'cotizacion|nuevo')
        ->defaults('tipo', 'cotizacion')
        ->name('asesores.crear-pedido');
    
    // Resto de rutas de pedidos...
    Route::get('/pedidos', [PedidoController::class, 'index'])
        ->name('asesores.pedidos-produccion.index');
    
    Route::post('/pedidos', [PedidoController::class, 'store'])
        ->name('asesores.pedidos-produccion.store');
    
    // ... más rutas
});
```

## 🎯 Resumen de Cambios

| Item | Antes | Después |
|------|-------|---------|
| **Rutas** | 2 rutas distintas | 1 ruta con parámetro |
| **Vista** | 1 (hacer-desde-cotizacion-editable.blade.php) | 3 (crear-pedido.blade.php + 2 específicas) |
| **Controller** | 2 métodos | 1 método |
| **Lógica** | Mezclada | Separada |
| **Mantenibilidad** | Baja | Alta |

---

**Nota:** Después de hacer estos cambios, prueba ambas URLs en el navegador para confirmar que funcionan correctamente.
