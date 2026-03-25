# DDD - Estructura de Repositories y UseCases

## Estructura creada

### 1. **Repositories** (Capa Infrastructure)
Abstraen el acceso a datos, sin lógica de negocio.

#### `PedidoProduccionRepository`
```
Métodos principales:
- obtenerPorIdONumero($identificador): ?PedidoProduccion
- obtenerPorId(int $id): ?PedidoProduccion
- obtenerPorNumero(string $numero): ?PedidoProduccion
- obtenerSinNumero(): ?PedidoProduccion
- obtenerDescripcionPrendas(string $numeroPedido): ?string
- obtenerFestivos(): array
- getAreaActual(PedidoProduccion $pedido): string
- getUltimaFechaProcesoFin(PedidoProduccion $pedido): ?string
- getDesgloseDiasPorProceso(PedidoProduccion $pedido): array
```

#### `ConsecutivosRecibosRepository`
```
Métodos principales:
- obtenerPorPrendaYPedido(int $prendaId, int $pedidoId)
- obtenerCosinturaPorPrenda(int $pedidoId, int $prendaId)
- obtenerCosturaDelPedido(int $pedidoId)
- obtenerTodosPorPrenda(int $prendaId, int $pedidoId)
- obtenerFechasCompletadoPorArea(int $reciboCosturaId): array
```

#### `LogoPedidoRepository`
```
Métodos principales:
- obtenerPorId(int $id): ?LogoPedido
- obtenerCompletoPorId(int $id): ?array
  (completa datos desde LogoPedido + PedidoProduccion + LogoCotizacion)
```

### 2. **UseCases** (Capa Application)
Orquestan Repositories y Services, contienen lógica de negocio.

#### `GetSeguimientoPorPrendaUseCase`
```php
// Ubicación: app/Application/UseCases/RegistroOrden/

public function execute(string $pedido): array {
    // 1. Resolver pedido (Repository)
    // 2. Obtener prendas con relaciones
    // 3. Para cada prenda:
    //    - Obtener consecutivos (Repository)
    //    - Obtener procesos y calcular duraciones
    //    - Agrupar por área
    //    - Inyectar Insumos virtual
    // 4. Retornar estructura completa
}
```

#### `GetDescripcionPrendasUseCase`
```php
// Ubicación: app/Application/UseCases/RegistroOrden/

public function execute(string $pedido): array {
    // 1. Obtener pedido (Repository)
    // 2. Obtener descripción_prendas del modelo
    // 3. Retornar respuesta formateada
}
```

#### `GetConsecutivoCosturaUseCase`
```php
// Ubicación: app/Application/UseCases/RegistroOrden/

public function execute(string $pedido, ?string $prendaId): array {
    // 1. Obtener pedido (Repository)
    // 2. Buscar consecutivo COSTURA (Repository)
    // 3. Obtener encargado desde procesos_prenda
    // 4. Retornar datos completos
}
```

#### `GetLogoPedidoUseCase`
```php
// Ubicación: app/Application/UseCases/RegistroOrden/

public function execute(int $id): array {
    // 1. Obtener LogoPedido completado (Repository)
    // 2. Retornar estructura enriquecida
}
```

## Cómo usar en el Controller

### Inyección de dependencias

```php
namespace App\Infrastructure\Http\Controllers;

use App\Application\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase;
use App\Application\UseCases\RegistroOrden\GetDescripcionPrendasUseCase;
use App\Application\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase;
use App\Application\UseCases\RegistroOrden\GetLogoPedidoUseCase;

class RegistroOrdenQueryController extends Controller
{
    protected GetSeguimientoPorPrendaUseCase $getSeguimientoPorPrendaUseCase;
    protected GetDescripcionPrendasUseCase $getDescripcionPrendasUseCase;
    protected GetConsecutivoCosturaUseCase $getConsecutivoCosturaUseCase;
    protected GetLogoPedidoUseCase $getLogoPedidoUseCase;

    public function __construct(
        GetSeguimientoPorPrendaUseCase $getSeguimientoPorPrendaUseCase,
        GetDescripcionPrendasUseCase $getDescripcionPrendasUseCase,
        GetConsecutivoCosturaUseCase $getConsecutivoCosturaUseCase,
        GetLogoPedidoUseCase $getLogoPedidoUseCase
    ) {
        $this->getSeguimientoPorPrendaUseCase = $getSeguimientoPorPrendaUseCase;
        $this->getDescripcionPrendasUseCase = $getDescripcionPrendasUseCase;
        $this->getConsecutivoCosturaUseCase = $getConsecutivoCosturaUseCase;
        $this->getLogoPedidoUseCase = $getLogoPedidoUseCase;
    }
}
```

### Métodos del Controller (refactorizados)

```php
/**
 * GET /registros/{pedido}/seguimiento-prenda
 */
public function getSeguimientoPorPrenda($pedido)
{
    $result = $this->getSeguimientoPorPrendaUseCase->execute($pedido);

    if (!$result['success']) {
        return response()->json($result, 404);
    }

    return response()->json($result);
}

/**
 * GET /registros/{pedido}/descripcion-prendas
 */
public function getDescripcionPrendas($pedido)
{
    $result = $this->getDescripcionPrendasUseCase->execute($pedido);

    if (!$result['success']) {
        return response()->json($result, !empty($result['message']) ? 404 : 500);
    }

    return response()->json($result);
}

/**
 * GET /registros/{pedido}/consecutivo-costura
 */
public function getConsecutivoCostura($pedido)
{
    $prendaId = request()->query('prenda_id');
    $result = $this->getConsecutivoCosturaUseCase->execute($pedido, $prendaId);

    if (!$result['success']) {
        return response()->json($result, 404);
    }

    return response()->json($result);
}

/**
 * GET /api/logo-pedidos/{id}
 */
public function showLogoPedidoById($id)
{
    $result = $this->getLogoPedidoUseCase->execute((int) $id);

    if (!$result['success']) {
        return response()->json($result, 404);
    }

    return response()->json($result['data']);
}
```

## Beneficios DDD

✅ **Separación de responsabilidades**
- Repositories: Solo acceso a datos
- UseCases: Orquestación y lógica de negocio
- Controllers: Solo HTTP

✅ **Testeable**
- Mock repositories en tests
- Test use cases sin BD

✅ **Mantenible**
- Cambios en BD → solo actualizar Repository
- Lógica de negocio centralizada en UseCase

✅ **Reutilizable**
- Mismo UseCase desde diferentes Controllers/APIs
- Repositories compartidos entre UseCases

## Siguiente paso

Actualizar el Controller para inyectar y usar estos UseCases en lugar de queries directas.
