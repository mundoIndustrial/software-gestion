# Verificación: Persistencia del Campo `area` en Pedidos

## Problema Identificado
El campo `area` no se estaba guardando cuando se creaban nuevos pedidos. El valor quedaba NULL en la BD.

## Cambios Realizados

### 1. Controller - PedidosProduccionController.php (Line 219)
**Antes:**
```php
$dto = CrearProduccionPedidoDTO::fromRequest(null, $validated);
```

**Después:**
```php
$dto = CrearProduccionPedidoDTO::fromRequest($validated);
```

**Razón:** El método `fromRequest()` solo acepta 1 parámetro, no 2.

---

### 2. DTO - CrearProduccionPedidoDTO.php
**Propiedades Agregadas:**
- `public ?string $area;`
- `public ?string $estado;`
- `public ?int $asesorId;`
- `public ?int $clienteId;`
- `public ?string $formaDePago;`

**Constructor - Defaults:**
```php
$this->area = $area ?? 'creacion de pedido';  // Default value
$this->estado = $estado ?? 'Pendiente';       // Default value
```

**fromRequest() - Mapping:**
```php
$datos['area'] ?? 'creacion de pedido',
$datos['estado'] ?? 'Pendiente',
$datos['asesor_id'] ?? null,
$datos['cliente_id'] ?? null,
$datos['forma_pago'] ?? null
```

---

### 3. Use Case - CrearProduccionPedidoUseCase.php
**Antes:**
- Usaba `PedidoRepository::guardar()` (que no existe)
- No persistía `area` ni `estado`
- Usaba agregado no existente `PedidosAggregate`

**Después:**
```php
// 1. CREAR EN BD PRIMERO para obtener ID
$pedidoModel = PedidoProduccion::create([
    'numero_pedido' => $dto->numeroPedido,
    'cliente' => $dto->cliente,
    'forma_de_pago' => strtolower(trim($dto->formaDePago ?? 'contado')),
    'asesor_id' => $dto->asesorId,
    'cliente_id' => $dto->clienteId,
    'estado' => $dto->estado ?? 'Pendiente',
    'area' => $dto->area ?? 'creacion de pedido',  // ← CRITICAL
    'cantidad_total' => 0,
]);

// 2. CREAR EL AGREGADO con ID ya generado
$agregado = PedidoProduccionAggregate::crear(
    id: $pedidoModel->id,
    numeroPedido: $pedidoModel->numero_pedido,
    cliente: $pedidoModel->cliente,
    formaPago: $pedidoModel->forma_de_pago,
    asesorId: $pedidoModel->asesor_id,
    estado: $pedidoModel->estado,
    area: $pedidoModel->area,  // ← CRITICAL
);
```

**Cambios Clave:**
- Se crea el pedido PRIMERO en la BD
- Se obtiene el ID generado
- Se crea el agregado DESPUÉS con el ID real
- Se publican los eventos

---

### 4. Agregado - PedidoProduccionAggregate.php

**Propiedades Agregadas:**
```php
/**
 * Área del pedido
 */
private ?string $area = null;
```

**Constructor - Parámetro Nuevo:**
```php
private function __construct(
    // ... otros parámetros ...
    ?string $area = null,
) {
    // ...
    $this->area = $area;
}
```

**Factory Method - Parámetro Nuevo:**
```php
public static function crear(
    // ... otros parámetros ...
    ?string $area = null,
): self {
    $agregado = new self(
        // ... otros parámetros ...
        $area,
    );
    // ...
}
```

**Getters/Setters - Nuevos:**
```php
public function getArea(): ?string {
    return $this->area;
}

public function setArea(?string $area): void {
    $this->area = $area;
}
```

---

## Flujo de Ejecución (Corregido)

```
POST /asesores/pedidos
│
├─ Controller valida entrada HTTP
│
├─ Controller crea DTO: CrearProduccionPedidoDTO::fromRequest($validated)
│  └─ DTO extrae 'area' del request o usa default 'creacion de pedido'
│
├─ Controller ejecuta UseCase: $useCase->ejecutar($dto)
│
└─ UseCase:
   ├─ 1. PedidoProduccion::create([...area...]) → ID generado
   ├─ 2. PedidoProduccionAggregate::crear(...area...) → Agregado
   ├─ 3. Publicar eventos
   └─ 4. Retornar agregado
```

---

## Verificación en BD

**Query para verificar:**
```sql
SELECT id, numero_pedido, cliente, area, estado, created_at 
FROM pedidos_produccion 
WHERE id = <nuevo_id>
LIMIT 1;
```

**Resultado Esperado:**
```
id | numero_pedido | cliente | area                 | estado    | created_at
---|---------------|---------|----------------------|-----------|---
XX | 2780          | ACME    | creacion de pedido   | Pendiente | 2024-...
```

---

## Valores por Defecto (Aplicados Automáticamente)

| Campo      | Valor Default              |
|------------|----------------------------|
| `area`     | `'creacion de pedido'`    |
| `estado`   | `'Pendiente'`             |

Si el cliente envía estos campos en el request, se usan esos valores en lugar de los defaults.

---

## Archivos Modificados

1. `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
   - Line 219: Corregir llamada a fromRequest()

2. `app/Application/Pedidos/DTOs/CrearProduccionPedidoDTO.php`
   - Lines 14-21: Agregar propiedades
   - Lines 35-37: Setear defaults
   - Lines 47-57: Actualizar fromRequest()

3. `app/Application/Pedidos/UseCases/CrearProduccionPedidoUseCase.php`
   - Completamente refactorizado
   - Ahora crea en BD primero, luego crea agregado
   - Persiste todos los campos incluyendo `area`

4. `app/Domain/Pedidos/Aggregates/PedidoProduccionAggregate.php`
   - Lines 37-40: Agregar propiedad `area`
   - Lines 75-77: Agregar parámetro al constructor
   - Lines 92-99: Agregar parámetro a factory method
   - Lines 225-232: Agregar getters/setters

---

## Testing

### Test Manual (Postman)
```http
POST /asesores/pedidos
Content-Type: application/json

{
  "numero_pedido": "2780",
  "cliente": "CLIENTE TEST",
  "forma_pago": "contado",
  "asesor_id": 1,
  "area": "produccion"  // ← Optional, defaults to 'creacion de pedido'
}
```

**Response Esperado:**
```json
{
  "id": XX,
  "numero_pedido": "2780",
  "cliente": "CLIENTE TEST",
  "area": "produccion",
  "estado": "Pendiente",
  "created_at": "2024-..."
}
```

### Verificación BD
```php
$pedido = DB::table('pedidos_produccion')->find(XX);
echo $pedido->area;  // 'produccion'
echo $pedido->estado; // 'Pendiente'
```

---

## Status

✅ **COMPLETADO**

- DTO captura y defaultea `area`
- UseCase persiste `area` en BD
- Agregado acepta y almacena `area`
- Sintaxis PHP validada
- Caches limpios

---

## Próximos Pasos Recomendados

1. **Test end-to-end:** Crear pedido vía API y verificar BD
2. **Verificar frontend:** Confirmar que envía `area` en request si es necesario
3. **Validación opcional:** Permitir que `area` sea capturada desde frontend
4. **Audit logs:** Registrar cambios en área de pedidos
