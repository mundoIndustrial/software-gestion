# 🔴 ERROR CRÍTICO ENCONTRADO

## El Problema

**Archivo**: `app/Jobs/CrearPedidoProduccionJob.php` línea 71

```php
// ❌ INCORRECTO - Pasa array de DTOs
$prendaService->guardarPrendasEnPedido($pedido, $this->prendas);
```

**Archivo**: `app/Application/Services/PedidoPrendaService.php` línea 61

```php
// ✓ Espera array de arrays
private function guardarPrenda(PedidoProduccion $pedido, array $prendaData): void
```

---

## El Conflicto de Tipos

### Lo que se envía:
```php
$this->prendas  // Array de PrendaCreacionDTO (objetos)
```

### Lo que se espera:
```php
// Array de arrays simple
[
    [
        'nombre_producto' => 'Camisa',
        'descripcion' => '...',
        'cantidad' => 10,
        'fotos' => [...],
        'telas' => [...],
        'tallas' => [...],
    ],
    ...
]
```

---

## El Error Exacto

```
guardarPrenda(): Argument #2 ($prendaData) must be of type array, 
App\DTOs\PrendaCreacionDTO given
```

---

## Soluciones

### OPCIÓN 1: Convertir DTOs a arrays antes de enviar
```php
// En CrearPedidoProduccionJob.php línea 71
$prendasArray = array_map(
    fn(PrendaCreacionDTO $dto) => $dto->toArray(),
    $this->prendas
);
$prendaService->guardarPrendasEnPedido($pedido, $prendasArray);
```

### OPCIÓN 2: Hacer que el servicio acepte DTOs
```php
// En PedidoPrendaService.php línea 61
private function guardarPrenda(PedidoProduccion $pedido, PrendaCreacionDTO $prendaData): void
{
    $prenda = PrendaPed::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_producto' => $prendaData->nombreProducto,
        'descripcion' => $prendaData->descripcion,
        'cantidad' => $prendaData->cantidad,
    ]);
    // ...
}
```

### OPCIÓN 3: Crear un adaptador
```php
// Crear clase PrendaDTOToArrayAdapter
class PrendaDTOToArrayAdapter {
    public static function convert(PrendaCreacionDTO $dto): array {
        return [
            'nombre_producto' => $dto->nombreProducto,
            'descripcion' => $dto->descripcion,
            'cantidad' => $dto->cantidad,
            'fotos' => $dto->fotos,
            'telas' => $dto->telas,
            'tallas' => $dto->tallas,
            'variantes' => $dto->variantes,
        ];
    }
}
```

---

## ¿Cuál prefieres que implemente?
