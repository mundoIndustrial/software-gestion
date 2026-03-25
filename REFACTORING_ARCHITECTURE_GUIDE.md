# REFACTORIZACIÓN: RegistroOrdenController - DDD & Clean Architecture

## 📋 Resumen Ejecutivo

Se ha refactorizado un controller monolítico de **2638 líneas** en múltiples capas especializadas, reduciendo el controller a **~200 líneas** distribuidas en:

- **7 UseCases** en Application Layer
- **4 Domain Services** + **2 Value Objects**
- **2 Query Services** en Infrastructure Layer
- **1 Controller simplificado** que solo orquesta

## 🏗️ Nueva Estructura de Carpetas

```
app/
├── Application/
│   └── UseCases/
│       ├── Orders/
│       │   ├── CreateOrderUseCase.php
│       │   ├── UpdateOrderUseCase.php
│       │   ├── DeleteOrderUseCase.php
│       │   ├── GetOrderUseCase.php
│       │   ├── EditFullOrderUseCase.php
│       │   ├── AddNovedadUseCase.php
│       │   └── SaveDiaEntregaUseCase.php
│       └── Receipts/
│           └── GetSewingReceiptsUseCase.php
│
├── Domain/
│   ├── Services/
│   │   ├── OrderCalculationService.php
│   │   └── OrderFilteringService.php
│   └── ValueObjects/
│       ├── PedidoNumber.php
│       └── EntregaEstado.php
│
├── Infrastructure/
│   ├── Http/
│   │   └── Controllers/
│   │       └── RegistroOrdenControllerRefactored.php  (200 líneas)
│   └── QueryServices/
│       └── OrderQueryService.php
│
├── Http/
│   └── Controllers/
│       └── RegistroOrdenController.php  (MANTENER COMO ESTÁ - versionado)
```

## 🎯 Principios Aplicados

### 1. **Single Responsibility Principle (SRP)**
- ✅ Controller: Solo orquesta Request → UseCase → Response
- ✅ UseCases: Orquestación de un flujo de negocio
- ✅ Domain Services: Lógica de negocio pura
- ✅ Query Services: Consultas complejas
- ✅ Value Objects: Validación y encapsulación de datos

### 2. **Dependency Injection**
```php
// Antes (anti-patrón):
$this->creationService->createOrder(...);

// Ahora (inyectado):
public function __construct(CreateOrderUseCase $useCase) {}
```

### 3. **Separation of Concerns**
- **Application**: *¿Qué hacer?* (orquestación)
- **Domain**: *¿Cómo hacerlo?* (reglas de negocio)
- **Infrastructure**: *¿Dónde obtener datos?* (BD, APIs)

## 📦 Capas y Responsabilidades

### Application Layer (UseCases)

**CreateOrderUseCase.php**
```
Input:  Request
- Validar datos
- Verificar número consecutivo
- Delegar creación
- Disparar eventos
Output: ['success' => true, 'pedido' => 123]
```

**UpdateOrderUseCase.php**
```
Input:  Request, pedido_id
- Validar actualización
- Ejecutar cambios
- Ejecución de broadcast
Output: Datos actualizados
```

**SaveDiaEntregaUseCase.php**
```
Input:  orden_id, dias_entrega
- Validar día (1-35)
- Calcular fecha estimada
- Actualizar orden
- Broadcast
Output: Orden actualizada
```

### Domain Layer

**OrderCalculationService.php** (Domain Service)
```php
// Lógica de negocio purmente funcional
- calcularDiasHabiles(inicio, fin)
- calcularFechaEstimada(inicio, dias)
- validarDiaEntrega(dia)
```

**PedidoNumber.php** (Value Object)
```php
// Encapsula reglas del dominio
- Validación de número
- Verificación de consecutivo
- Comparaciones
```

### Infrastructure Layer

**OrderQueryService.php** (Query Service)
```php
// Operaciones complejas de lectura
- getFilterOptions()
- filterOrders(filtros, page)
- searchOrders(termino, page)
- getColumnFilterOptions(columna, search)
```

## 🔄 Flujo de Datos

### Ejemplo: Crear Orden

```
HTTP Request
    ↓
Controller::store()
    ↓
CreateOrderUseCase::execute(request)
    ├→ RegistroOrdenValidationService::validateStoreRequest()
    ├→ RegistroOrdenCreationService::createOrder()
    ├→ RegistroOrdenCreationService::logOrderCreated()
    └→ RegistroOrdenCreationService::broadcastOrderCreated()
    ↓
Response JSON
```

### Ejemplo: Guardar Día de Entrega

```
HTTP POST /registros/{id}/dia-entrega
    ↓
Controller::saveDiaEntrega()
    ↓
SaveDiaEntregaUseCase::execute(id, dias)
    ├→ OrderCalculationService::validarDiaEntrega()
    ├→ OrderCalculationService::calcularFechaEstimada()
    ├→ PedidoProduccion::update()
    └→ broadcast(OrdenUpdated)
    ↓
Response JSON
```

## ⚙️ Cómo Registrar en el Service Provider

**AppServiceProvider.php**

```php
// Registrar UseCases
$this->app->bind(CreateOrderUseCase::class, function ($app) {
    return new CreateOrderUseCase(
        $app->make(RegistroOrdenValidationService::class),
        $app->make(RegistroOrdenCreationService::class),
    );
});

$this->app->bind(UpdateOrderUseCase::class, function ($app) {
    return new UpdateOrderUseCase(
        $app->make(RegistroOrdenValidationService::class),
        $app->make(RegistroOrdenUpdateService::class),
    );
});

// Registrar Domain Services
$this->app->singleton(OrderCalculationService::class);
$this->app->singleton(OrderQueryService::class);

// ... resto de UseCases
```

## 📊 Comparativa: Antes vs Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Líneas Controller** | 2638 | ~200 |
| **Responsabilidades** | 15+ | 1 (orquestación) |
| **Testabilidad** | Difícil | Fácil (cada UseCase independiente) |
| **Reutilización** | No | Sí (UseCases desde múltiples controllers) |
| **Mantenibilidad** | Baja | Alta (cambios aislados) |
| **Lógica de Negocio** | Mezclada | Centralizada en Domain |
| **Coupling** | Alto | Bajo (inyección de dependencias) |

## 🧪 Testing Strategy

### Unit Test: Domain Service

```php
public function test_calcula_dias_habiles_correctamente()
{
    $service = new OrderCalculationService($festivosService);
    
    $inicio = Carbon::parse('2024-01-01');
    $fin = Carbon::parse('2024-01-15');
    
    $dias = $service->calcularDiasHabiles($inicio, $fin);
    
    $this->assertGreaterThan(0, $dias);
}
```

### Unit Test: UseCase

```php
public function test_crear_orden_valida()
{
    $validationService = Mockery::mock(RegistroOrdenValidationService::class);
    $creationService = Mockery::mock(RegistroOrdenCreationService::class);
    
    $useCase = new CreateOrderUseCase($validationService, $creationService);
    $request = Request::create('POST', '/ordenes', [...]);
    
    $result = $useCase->execute($request);
    
    $this->assertTrue($result['success']);
}
```

### Integration Test: Complete Flow

```php
public function test_flujo_completo_crear_actualizar_orden()
{
    // Setup
    $useCase = $this->app->make(CreateOrderUseCase::class);
    $request = $this->createValidOrderRequest();
    
    // Act
    $result = $useCase->execute($request);
    $pedidoId = $result['pedido'];
    
    // Assert
    $orden = PedidoProduccion::find($pedidoId);
    $this->assertNotNull($orden);
}
```

## 🚀 Próximas Fases de Refactorización

### Fase 2: Recibos (Receipts)
```
- ✅ GetSewingReceiptsUseCase (iniciado)
- ⏳ GetReflectiveReceiptsUseCase
- ⏳ CreateReceiptDetailUseCase
- ⏳ MarkReceiptAsViewedUseCase
```

### Fase 3: QueryServices Adicionales
```
- ⏳ ReceiptQueryService
- ⏳ EntreguasQueryService
- ⏳ PrendaQueryService
```

### Fase 4: Domain Aggregates
```
- ⏳ OrderAggregate (raíz agregada)
- ⏳ ReceiptAggregate
- ⏳ DeliveryAggregate
```

## 📝 Notas de Migración

### 1. Actualizar Routes
```php
// Nuevo Controller
Route::post('/ordenes', [RegistroOrdenControllerRefactored::class, 'store']);
Route::put('/ordenes/{pedido}', [RegistroOrdenControllerRefactored::class, 'update']);

// Mantener redirecciones durante testing
Route::post('/ordenes-legacy', [RegistroOrdenController::class, 'store']);
```

### 2. Mantener Backward Compatibility
- Mantener el `RegistroOrdenController` original
- Apuntar sus métodos a los nuevos UseCases
- Deprecar gradualmente métodos antiguos

### 3. Logging & Monitoring
```php
// Cada UseCase registra:
Log::info('CreateOrderUseCase::execute', [
    'numero_pedido' => $pedido->numero_pedido,
    'cliente' => $validatedData['cliente'],
    'usuario_id' => auth()->id()
]);
```

## 🎓 Decisiones Arquitectónicas

### ✅ Por qué UseCases?
- Cada operación de negocio es un UseCase
- Reutilizable desde múltiples controllers o consola
- Fácil de testear en aislamiento

### ✅ Por qué Value Objects?
- `PedidoNumber` encapsula validaciones
- `EntregaEstado` enum de estados con métodos
- Previene bugs relacionados con tipos primitivos

### ✅ Por qué Domain Services?
- `OrderCalculationService`: Cálculos críticos reutilizables
- `OrderFilteringService`: Reglas de filtrado complejas
- No dependen de Eloquent

### ✅ Por qué Query Services?
- Consultas complejas en una sola clase
- Reutilizable desde múltiples controllers
- Separación clara entre lectura y escritura (CQRS pattern)

## 🔗 Referencias

- Domain Driven Design: Eric Evans
- Clean Architecture: Robert C. Martin
- CQRS Pattern: Greg Young
- DDD in laravel: [https://github.com/barryvanveen/laravel-ddd-example](referencia)
