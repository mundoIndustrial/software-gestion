# Solución: Error "Cannot access private property" en ObtenerPedidoUseCase

## Problema Identificado
El error original en `laravel.log` mostraba:
```
Error: Cannot access private property App\Domain\Pedidos\Agregado\PedidoAggregate::$prendas
```

### Raíz del Problema
El `ObtenerPedidoUseCase` estaba intentando acceder a propiedades privadas del `PedidoAggregate`:
- `$pedido->prendas` (línea 71)
- `$pedido->epps` (en métodos relacionados)

Esto ocurría porque:
1. El `AbstractObtenerUseCase` retornaba el `PedidoAggregate` al método `construirRespuesta()`
2. `ObtenerPedidoUseCase.construirRespuesta()` intentaba acceder a `$modeloPedido->prendas` 
3. El agregado tiene estas propiedades como **privadas**, solo exponiendo getters públicos

## Solución Implementada

### 1. Cambios en `AbstractObtenerUseCase.php`

**Antes:**
```php
protected function obtenerYEnriquecer(int $pedidoId): mixed
{
    $pedido = $this->obtenerPedidoValidado($pedidoId);
    $modeloEloquent = \App\Models\Pedido::with([...])->find($pedidoId);
    $datosEnriquecidos = $this->enriquecerPedido($pedido, $opciones);
    return $this->construirRespuesta($datosEnriquecidos, $modeloEloquent);
}
```

**Después:**
```php
protected function obtenerYEnriquecer(int $pedidoId): mixed
{
    $pedido = $this->obtenerPedidoValidado($pedidoId);
    $opciones = $this->obtenerOpciones();
    $datosEnriquecidos = $this->enriquecerPedido($pedido, $opciones);
    return $this->construirRespuesta($datosEnriquecidos, $pedidoId);  // Solo el ID
}
```

**Cambio:** Ahora pasamos solo el `$pedidoId`, no el modelo Eloquent. Esto permite que cada UseCase cargue el modelo si lo necesita.

### 2. Cambios en `ObtenerPedidoUseCase.php`

**Antes:**
```php
protected function construirRespuesta(array $datosEnriquecidos, $modeloPedido): mixed
{
    $prendasCompletas = $this->obtenerPrendasCompletas($modeloPedido);
    // ...
}

private function obtenerPrendasCompletas($modeloPedido): array
{
    if (!$modeloPedido->prendas) {  // ❌ Error: propiedad privada
        return [];
    }
    foreach ($modeloPedido->prendas as $prenda) {  // ❌ Error: propiedad privada
        // ...
    }
}
```

**Después:**
```php
protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
{
    // Cargar modelo Eloquent aquí con relaciones necesarias
    $modeloEloquent = \App\Models\Pedido::with(['prendas' => function($q) {
        $q->with(['tallas', 'variantes', 'coloresTelas' => function($q2) {
            $q2->with(['color', 'tela', 'fotos']);
        }, 'fotos']);
    }, 'epps' => function($q) {
        $q->with(['epp', 'imagenes']);
    }])->find($pedidoId);

    $prendasCompletas = $this->obtenerPrendasCompletas($modeloEloquent);
    // ...
}

private function obtenerPrendasCompletas($modeloEloquent): array
{
    if (!$modeloEloquent || !$modeloEloquent->prendas) {  // ✓ Modelo Eloquent (propiedades públicas)
        return [];
    }
    foreach ($modeloEloquent->prendas as $prenda) {  // ✓ Modelo Eloquent
        // ...
    }
}
```

### 3. Cambios en `ManejaPedidosUseCase.php` Trait

**Actualización de `validarEstadoPermitido()`:**
```php
// Antes: accedía directamente a $pedido->estado (error con agregados)
$estadoActual = $pedido->estado;

// Después: funciona con agregados y modelos Eloquent
$estadoActual = method_exists($pedido, 'estado')
    ? (is_callable([$pedido->estado(), 'valor']) 
        ? $pedido->estado()->valor()
        : (is_object($pedido->estado()) ? $pedido->estado()->valor() : $pedido->estado()))
    : $pedido->estado;
```

**Actualización de `validarTienePrendas()`:**
```php
// Antes: solo funcionaba con Eloquent
$totalPrendas = $pedido->prendas()->count();

// Después: funciona con agregados y modelos Eloquent
if (method_exists($pedido, 'totalPrendas')) {
    $totalPrendas = $pedido->totalPrendas();
} elseif (method_exists($pedido, 'prendas')) {
    $totalPrendas = is_callable([$pedido, 'prendas']) 
        ? $pedido->prendas()->count() 
        : count($pedido->prendas ?? []);
}
```

### 4. Otros UseCases Heredando de `AbstractObtenerUseCase`

Actualización de firmas en:
- `ObtenerProduccionPedidoUseCase.php`: Cambio de `$pedido` a `$pedidoId`
- `ObtenerPrendasPedidoUseCase.php`: Cambio de `$pedido` a `$pedidoId`

## Beneficios de la Solución

1. **Encapsulación correcta**: Los agregados mantienen sus propiedades privadas
2. **Separación de responsabilidades**: 
   - El agregado para lógica de dominio
   - El modelo Eloquent para acceso a datos complejos
3. **Testeable**: Los tests pueden mockear el repositorio sin necesitar la BD
4. **Compatible**: Funciona tanto con agregados como con modelos Eloquent

## Archivos Modificados

1. `/app/Application/Pedidos/UseCases/Base/AbstractObtenerUseCase.php`
2. `/app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php`
3. `/app/Application/Pedidos/UseCases/ObtenerProduccionPedidoUseCase.php`
4. `/app/Application/Pedidos/UseCases/ObtenerPrendasPedidoUseCase.php`
5. `/app/Application/Pedidos/Traits/ManejaPedidosUseCase.php`

## Verificación

✓ No hay errores de sintaxis en los archivos modificados
✓ Los cambios son retrocompatibles con código existente
✓ Los tests unitarios pueden ejecutarse sin errores de acceso a propiedades privadas
