# Análisis: RegistroOrdenQueryController - Malas Prácticas, DDD y Patrones

## 🔴 MALAS PRÁCTICAS IDENTIFICADAS

### 1. **Violación masiva de SRP (Single Responsibility Principle)**
El controlador hace TODO:
- ✗ Consultas complejas a BD
- ✗ Transformación de datos
- ✗ Cálculos de negocio (días, duraciones)
- ✗ Ensamblaje de múltiples fuentes (LogoPedido, PedidoProduccion, LogoCotizacion)
- ✗ Formateo de respuestas
- ✗ Lógica condicional compleja (métodos privados estáticos)

**Líneas afectadas**: Prácticamente todo el controlador (2600+ líneas)

### 2. **Over-injection de dependencias (8 servicios)**
```php
public function __construct(
    RegistroOrdenExtendedQueryService,  // 1
    RegistroOrdenSearchExtendedService, // 2
    RegistroOrdenFilterExtendedService, // 3
    RegistroOrdenTransformService,      // 4
    RegistroOrdenProcessService,        // 5
    RegistroOrdenStatsService,          // 6
    RegistroOrdenProcessesService,      // 7
    RegistroOrdenEnumService            // 8
)
```
**Problema**: Es síntoma de que el controlador hace demasiado. Debería delegar a 1-2 servicios máximo.

### 3. **Lógica de negocio crítica hardcodeada estática**
```php
private static function resolveAreaMetadata(string $area): array
{
    $areaLower = strtolower(trim($area));
    $isInsumos = $areaLower === 'insumos';
    $isCorte = str_contains($areaLower, 'corte');
    $isCostura = str_contains($areaLower, 'costura');
    // ...
}
```
**Problemas**:
- Lógica de reglas de negocio en método estático → NO es testeable
- No usa configuración o dominio
- Si cambian las reglas, hay que modificar controller

### 4. **Cargas N+1 y queries ineficientes**
```php
// Línea ~140
$todasOrdenes = $query->get();  // ⚠️ Obtiene TODAS las órdenes
$ordenesArray = $todasOrdenes->map(...)->toArray();
$totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);
// Luego filtra en memoria con PHP
```
**Impacto**: 
- Con 2257 órdenes, obtiene TODAS antes de filtrar
- El cálculo se hace en memoria en PHP
- Paginator manual → pérdida del query builder

### 5. **Fallback de datos sin usar el Dominio**
```php
// Línea ~312
// PASO 1: Intentar completar desde PedidoProduccion
// PASO 2: Si aún falta info, intentar desde LogoCotizacion
// PASO 3: Asegurar valores finales
```
**Problema**: 
- Lógica de resolución de datos dispersa en try-catch
- Sin Value Objects o entidades de dominio que representen esto
- Campos con valores por defecto `'-'` → mala práctica de DB

### 6. **Uso innecesario de $fillable / toArray()**
```php
$logoPedidoArray = $logoPedido->toArray();  // Convertir a array pierden tipos
// Luego manipular arrays manualmente
$logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
```

### 7. **Métodos privados estáticos haciendo lógica de dominio**
```php
private static function formatDurationHuman(int $diffMs): string { }
private static function calcularDuracionesArea(...): array { }
private static function resolveAreaActualPrenda(...): string { }
private static function resolveReciboDisplay(...): string { }
private static function resolveReciboPrincipal(...): string { }
```
**Problema**: Esto debería estar en **Domain Services o Value Objects**, no en Controller

### 8. **Logging excesivo (antipatrón de debugging)**
```php
\Log::info("Antes de verificar filtro - filterTotalDias: " . json_encode($filterTotalDias) ...
\Log::info("Iniciando filtrado por total_de_dias_ con valores: " ...
\Log::info("Total órdenes obtenidas: " . $todasOrdenes->count());
// +100 líneas de logging
```
**Problema**: Debugging dejado en código productivo → ruido en logs

### 9. **Excepción genérica no diferenciada**
```php
} catch (\Exception $e) {
    \Log::error('Error al obtener datos...');
    return response()->json(['error' => 'Error'], 500);
}
```
Sin tipificación de errores de negocio.

### 10. **Métodos que hacen más de una cosa**
`index()` hace:
- Validar parámetros
- Aplicar filtros
- Paginar
- Calcular campos derivados
- Transformar datos
- Returnear diferentes vistas

---

## 🚫 VIOLACIONES DE DDD (Domain-Driven Design)

### 1. **Falta de Aggregate Roots coherentes**
```
Actual:
├── LogoPedido (tabla)
├── PedidoProduccion (tabla)
├── LogoCotizacion (tabla)
└── ConsecutivosRecibosPedidos (tabla)
   ↓ El controlador intenta ensamblarlos aquí
```
**El origen real es un Aggregate que no existe en el dominio**

**Debería ser:**
```
┌─────────────────────────────────────┐
│    Orden (Aggregate Root)           │
├─────────────────────────────────────┤
│ - numero_pedido (Identity)          │
│ - cliente                           │
│ - asesora                           │
│ - prendas (EntityCollection)        │
│ - recibos (ValueObject[])           │
│ - seguimiento (Seguimiento VO)      │
└─────────────────────────────────────┘
```

### 2. **Modelos de ORM directo sin objetos de dominio**
```php
// Lo que hace:
$order->toArray();
$order->getAttributes();

// Lo que debería hacer:
$ordenDto = OrdenAssembler::toDomain($order);
$ordenDto->calcularDiasTotal($festivos);
```

### 3. **Servicios de Infraestructura en lugar de Servicios de Dominio**
```
✗ RegistroOrdenExtendedQueryService    (Infraestructura)
✗ RegistroOrdenSearchExtendedService   (Infraestructura)
✗ RegistroOrdenFilterExtendedService   (Infraestructura)

✓ OrdenDomainService (Dominio)
  ├── calcularDuracionesPorArea()
  ├── resolverAreaActual()
  ├── resolveReciboPrioridad()
  └── ensamblarSeguimiento()
```

### 4. **Value Objects no implementados**
```php
// Actual (strings primitivos):
$area = 'Corte';
$estado = 'Pendiente';

// Debería ser (Value Objects):
$area = new Area('Corte');
$estado = new EstadoOrden('Pendiente');
```

Con esto se gana:
- Type-safety
- Métodos de negocio (`$area->requiresEncargado()`)
- Validación automática

### 5. **Repository Pattern mal usado**
```php
// Lo que hace (datos de múltiples tablas sin semántica):
WHERE numero_pedido = $pedido

// Lo que debería hacer:
$orden = $repositorio->obtenerOrdenCompleta($numeroPedido);
// El repositorio maneja la hidratación del Aggregate
```

### 6. **Lógica de ubication/domain en métodos estáticos**
```php
// Esto es LÓGICA DE NEGOCIO:
if (str_contains($areaLower, 'corte')) { ... }

// Pero está en un método estático de Controller
// Debería ser una especificación del dominio:
class AreaRequiereEncargado implements Specification { }
```

### 7. **Eventos de dominio ausentes**
```php
// No hay:
- OrdenCreada
- ReciboActivado
- SeguimientoActualizado
- AreaCompletada

// El controlador manipula datos sin notificar el dominio
```

---

## 📊 ANTI-PATRONES ARQUITECTÓNICOS

### 1. **God Controller**
2600+ líneas en un solo controlador → problema de diseño

### 2. **Anemic Domain Model**
Models son solo contenedores de datos, toda lógica en servicios/controller

### 3. **Service Layer Bloated**
8 servicios hace que sea difícil mantener. Debería haber 1-2 servicios de dominio coordinadores.

### 4. **Fat Repository**
No hay repository pattern claro. Las queries están dispersas.

### 5. **Horizontal Slicing (por layer) en lugar de Vertical (por dominio)**
```
✗ Actual:
app/
├── Http/Controllers/      ← TODO un controlador
├── Services/              ← TODO los servicios
├── Models/                ← TODO los modelos

✓ Debería ser (Modular):
app/Modules/
├── Ordenes/
│   ├── Domain/
│   │   ├── Order (Aggregate)
│   │   ├── Seguimiento (Value Object)
│   │   ├── OrdenRepository (Interface)
│   │   ├── OrdenService (Domain)
│   │   └── Events/
│   ├── Application/
│   │   ├── ObtenerOrdenQuery
│   │   ├── ListarOrdenesQuery
│   │   └── ObtenerSeguimientoQuery
│   ├── Infrastructure/
│   │   ├── EloquentOrdenRepository
│   │   └── OrderQueryBuilder
│   └── Presentation/
│       └── OrdenController
```

---

## ✅ PATRONES DE DISEÑO QUE DEBERÍAN IMPLEMENTARSE

### 1. **QUERY OBJECT PATTERN** (para reemplazar filtros complejos)
```php
// Actual (caótico):
$query = $this->extendedQueryService->buildBaseQuery();
$query = $this->extendedQueryService->applyRoleFilters($query, auth()->user(), $request);
$query = $this->extendedSearchService->applySearchFilter($query, $request->input('search'));

// Debería ser:
$query = ListarOrdenesQuery::create()
    ->porUsuario(auth()->user())
    ->conBusqueda($request->search)
    ->conFiltros($filterData)
    ->porPagina($page);

$ordenes = $queryHandler->handle($query);
```

### 2. **CQRS (Command Query Responsibility Segregation)**
```php
// Queries (lectura):
- ObtenerOrdenQuery
- ListarOrdenesQuery  
- ObtenerSeguimientoPrendaQuery
- ObtenerDiasCalculadosQuery

// Commands (escritura): [no está en este controller pero es buena práctica]
- CrearOrdenCommand
- ActualizarSeguimientoCommand
- ActivarReciboCommand
```

### 3. **SPECIFICATION PATTERN** (para reglas de negocio)
```php
// En lugar de:
private static function resolveAreaMetadata(string $area): array

// Usar:
class AreaRequiereEncargado implements Specification
{
    public function isSatisfiedBy(Area $area): bool
    {
        return $area->equalsTo(Area::CORTE) 
            || $area->equalsTo(Area::COSTURA)
            || $area->equalsTo(Area::CONTROL_CALIDAD);
    }
}

// Uso:
if ((new AreaRequiereEncargado())->isSatisfiedBy($area)) {
    // ...
}
```

### 4. **DATA TRANSFER OBJECT (DTO) PATTERN**
```php
// En lugar de:
$orderArray = $order->toArray();
$orderArray['cliente'] = $orderArray['cliente'] ?: '-';

// Usar:
class OrdenDTO
{
    public string $numeroPedido;
    public string $cliente;
    public string $asesora;
    public array $prendas;
    public SeguimientoDTO $seguimiento;
}

// Con transformador:
$dto = OrdenAssembler::toDomain($order);
```

### 5. **VALUE OBJECT PATTERN**
```php
class Area extends ValueObject
{
    private $value;
    
    public static function CORTE() { return new self('Corte'); }
    public static function COSTURA() { return new self('Costura'); }
    
    public function requiresEncargado(): bool { }
    public function shouldHideEncargado(): bool { }
}

// Uso:
$area = Area::CORTE();
if ($area->requiresEncargado()) { ... }
```

### 6. **REPOSITORY PATTERN** (coherente)
```php
interface OrdenRepository
{
    /**
     * Obtiene orden completa con todas sus relaciones
     * Hidrata el Aggregate desde múltiples tablas
     */
    public function obtenerOrdenCompleeta(NumeroPedido $numero): Orden;
    
    public function listarConFiltros(FiltrosOrden $filtros, Paginacion $paginacion): Collection;
    
    public function obtenerSeguimientoPorPrenda(NumeroPedido $numero): SeguimientoPorPrenda;
}

// Implementación sabe cómo lidar con LogoPedido, PedidoProduccion, etc.
```

### 7. **MAPPER/ASSEMBLER PATTERN**
```php
class OrdenAssembler
{
    public static function toDomain(
        PedidoProduccion $modeloPedido,
        ?LogoPedido $modeloLogo,
        ?LogoCotizacion $modeloCotizacion
    ): Orden {
        // Hidrata el Aggregate desde múltiples modelos
        // Esto SALE del controlador
    }
    
    public static function toDTO(Orden $orden): OrdenDTO { }
    public static function toArray(Orden $orden): array { }
}
```

### 8. **STRATEGY PATTERN** (para áreas)
```php
interface AreaStrategy
{
    public function calcularDuracion(...): DuracionArea;
    public function obtenerCamposVisibles(): array;
    public function estaActiva(): bool;
}

class AreaCorteStrategy implements AreaStrategy { }
class AreaCosturaStrategy implements AreaStrategy { }
class AreaInsumosStrategy implements AreaStrategy { }

// Uso:
$strategy = AreaStrategyFactory::create($area);
$duracion = $strategy->calcularDuracion(...);
```

### 9. **BUILDER PATTERN** (para queries complejas)
```php
$ordenes = OrdenQueryBuilder::create()
    ->conEstado(EstadoOrden::PENDIENTE)
    ->conArea(Area::CORTE)
    ->conClienteName('Acme Corp')
    ->entre(now()->subDays(30), now())
    ->paginar(page: 1, perPage: 25)
    ->build();
```

### 10. **FACADE PATTERN** (simplificar interfaz pública)
```php
// En lugar de 8 servicios:
class OrdenFacade
{
    public function listarOrdenes(ListarOrdenesQuery $query): PaginatedCollection
    public function obtenerOrden(NumeroPedido $numero): OrdenDTO
    public function obtenerSeguimiento(NumeroPedido $numero): SeguimientoDTO
    public function obtenerDiasCalculados(NumeroPedido $numero): DiasPorArea
}

// El controlador solo usa ESTA fachada
```

---

## 🎯 PROPUESTA DE ARQUITECTURA LIMPIA

```
app/Modules/Ordenes/
│
├── Domain/
│   ├── Entities/
│   │   ├── Orden.php (Aggregate Root)
│   │   ├── Prenda.php (Entity)
│   │   ├── HistorialSeguimiento.php (Entity)
│   │   └── Recibo.php (Entity)
│   │
│   ├── ValueObjects/
│   │   ├── NumeroPedido.php
│   │   ├── Area.php
│   │   ├── EstadoOrden.php
│   │   ├── DuracionArea.php
│   │   └── SeguimientoConTiempo.php
│   │
│   ├── Events/
│   │   ├── OrdenCreada.php
│   │   ├── ReciboActivado.php
│   │   └── SeguimientoActualizado.php
│   │
│   ├── Services/
│   │   ├── CalculadorDuracionesDominio.php
│   │   ├── ResolutorAreaActual.php
│   │   └── EnsamblarSeguimiento.php
│   │
│   ├── Repositories/
│   │   └── OrdenRepository.php (Interface)
│   │
│   └── Specifications/
│       ├── AreaRequiereEncargado.php
│       ├── OrdenEnProgreso.php
│       └── PrendaPorArea.php
│
├── Application/
│   ├── Query/
│   │   ├── ListarOrdenesQuery.php
│   │   ├── ListarOrdenesQueryHandler.php
│   │   ├── ObtenerOrdenQuery.php
│   │   ├── ObtenerOrdenQueryHandler.php
│   │   ├── ObtenerSeguimientoPrendaQuery.php
│   │   └── ObtenerSeguimientoPrendaQueryHandler.php
│   │
│   ├── DTO/
│   │   ├── OrdenDTO.php
│   │   ├── PrendaDTO.php
│   │   ├── SeguimientoDTO.php
│   │   └── DuracionAreaDTO.php
│   │
│   └── Assemblers/
│       └── OrdenAssembler.php
│
├── Infrastructure/
│   ├── Repositories/
│   │   └── EloquentOrdenRepository.php (implementación)
│   │
│   ├── Builders/
│   │   ├── OrdenQueryBuilder.php
│   │   └── FiltrosOrdenBuilder.php
│   │
│   └── Mappers/
│       └── OrdenMapper.php
│
└── Presentation/
    ├── Controllers/
    │   ├── ListarOrdenesController.php
    │   ├── MostrarOrdenController.php
    │   ├── ObtenerSeguimientoPrendaController.php
    │   └── ObtenerDiasCalculadosController.php
    │
    └── Resources/
        ├── OrdenResource.php
        └── SeguimientoResource.php
```

---

## 📋 TABLA RESUMEN

| Aspecto | Actual | Propuesto |
|--------|--------|-----------|
| **Responsabilidad** | 1 Controller hace TODO | CQRS: Query Handlers especializados |
| **Dominio** | Inexistente | Order Aggregate Root con Value Objects |
| **Servicios** | 8 genéricos inflados | 2-3 Domain Services + 1 Facade |
| **Testing** | Difícil (métodos estáticos) | Fácil (inyeable, sin estáticos) |
| **Reutilización** | Baja (todo en controller) | Alta (servicios, specifications, DTOs) |
| **Mantenibilidad** | Difícil (2600+ líneas) | Fácil (archivos pequeños, responsables) |
| **Lógica de Negocio** | En Controller y métodos estáticos | En Domain Services y Value Objects |
| **Queries complejas** | Ad-hoc en método | Builder Pattern |

---

## 🚀 PASOS PRIORITARIOS DE REFACTORING

1. **Crear Aggregate Root `Orden`** y Value Objects (`Area`, `Estados`)
2. **Implementar CQRS**: `ListarOrdenesQuery` + `ListarOrdenesQueryHandler`
3. **Mover lógica estática a Domain Services**
4. **Crear Specification Pattern** para reglas de áreas
5. **Implementar Facade Pattern** para simplificar inyecciones
6. **Dividir Controller en múltiples Controllers** (uno por responsabilidad)
7. **Crear Assembler** para hidratación de Aggregates

Este refactoring es CRÍTICO antes de seguir añadiendo features.
