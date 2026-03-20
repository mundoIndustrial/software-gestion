# DDD Refactoring - SupervisorPedidosController

## Resumen de la Arquitectura DDD Implementada

Se ha aplicado un refactoring completo siguiendo principios DDD (Domain-Driven Design) al SupervisorPedidosController.

### Estructura de Carpetas Creada

```
app/
├── Domain/SupervisorPedidos/
│   ├── Entities/
│   │   ├── Order.php (Aggregate Root)
│   │   └── Receipt.php
│   ├── ValueObjects/
│   │   ├── OrderId.php
│   │   ├── OrderStatus.php
│   │   ├── PrendaId.php
│   │   ├── ReceiptType.php
│   │   └── SupervisorId.php
│   ├── Repositories/
│   │   ├── OrderRepository.php (interface)
│   │   └── ReceiptRepository.php (interface)
│   └── DomainEvents/
│       ├── OrderApprovedEvent.php
│       ├── OrderReturnedEvent.php
│       └── OrderCancelledEvent.php
├── Application/SupervisorPedidos/
│   ├── UseCases/
│   │   ├── ApproveOrderUseCase.php
│   │   ├── ReturnOrderUseCase.php
│   │   ├── ActivateSewingReceiptUseCase.php
│   │   └── ListPendingOrdersUseCase.php
│   └── DTOs/
│       ├── ApproveOrderRequest.php
│       ├── ApproveOrderResponse.php
│       ├── ReturnOrderRequest.php
│       ├── ReturnOrderResponse.php
│       ├── ActivateReceiptRequest.php
│       ├── ActivateReceiptResponse.php
│       └── ListPendingOrdersResponse.php
├── Infrastructure/Repositories/SupervisorPedidos/
│   ├── EloquentOrderRepository.php (implementación)
│   └── EloquentReceiptRepository.php (implementación)
└── Providers/
    └── SupervisorPedidosServiceProvider.php (DI Container)
```

## Componentes Principales

### 1. Value Objects (Inmutables)
- **OrderId**: Id único de orden
- **OrderStatus**: Estado válido de orden con métodos de negocio
- **PrendaId**: Id de prenda
- **ReceiptType**: Tipo de recibo (COSTURA, BORDADO, ESTAMPADO, LOGO)
- **SupervisorId**: Id de supervisor

### 2. Entities (Persistibles con lógica de negocio)
- **Order** (Aggregate Root):
  - Métodos: `approve()`, `returnToAdvisor()`, `cancel()`, `addNote()`
  - Events: OrderApprovedEvent, OrderReturnedEvent, OrderCancelledEvent
  
- **Receipt**:
  - Métodos: `approve()`, `cancel()`, `setSewingColor()`
  - Validaciones de negocio para COSTURA

### 3. Repositories (Contratos)
- **OrderRepository**: Buscar, guardar órdenes
- **ReceiptRepository**: Gestión de recibos

### 4. Use Cases (Casos de uso)
Encapsulan lógica de aplicación:
- `ApproveOrderUseCase`: Aprueba una orden pendiente
- `ReturnOrderUseCase`: Devuelve una orden a asesora con motivo
- `ActivateSewingReceiptUseCase`: Activa recibo de costura
- `ListPendingOrdersUseCase`: Obtiene órdenes pendientes

### 5. DTOs (Objetos de Transfer de Datos)
Comunican controller ↔ Use Cases:
- Requests: ApproveOrderRequest, ReturnOrderRequest, ActivateReceiptRequest
- Responses: ApproveOrderResponse, ReturnOrderResponse, ActivateReceiptResponse

### 6. Service Provider
Registra todas las dependencias en el contenedor DI de Laravel

## Beneficios de la Refactorización

✅ **Separación de responsabilidades**: Lógica de negocio en Domain, orquestación en Use Cases
✅ **Testabilidad**: Cada capa puede ser testeada independientemente
✅ **Mantenibilidad**: Cambios de negocio centralizados en Entities y Use Cases
✅ **Reutilización**: Use Cases pueden ser llamados desde Controller, API, CLI, etc.
✅ **Domain Events**: Base para arquitectura event-driven
✅ **Inmutabilidad**: Value Objects garantizan consistencia

## Próximos Pasos Recomendados

### Fase 2 - Refactorizar más métodos del Controller:
- `profile()` → ProfileUseCase
- `obtenerDatos()` → GetOrderDetailsUseCase
- `obtenerDatosFactura()` → Ya usa Use Case, pero integrar mejor
- `pendientesCostura()` → ListPendingSewingReceiptsUseCase
- `seleccionarPedido()` → SelectOrderUseCase

### Fase 3 - Domain Events Handlers:
- Listener para OrderApprovedEvent (actualizar stock, notificar asesora)
- Listener para OrderReturnedEvent (enviar notificación)
- Listener para OrderCancelledEvent (registrar auditoría)

### Fase 4 - Query Objects:
- Servicios especializados para búsquedas complejas
- FilterService para aplicar múltiples criterios
- Queries basadas en especificaciones de negocio

## Registro en config/app.php

Agregar a `providers` array:
```php
'providers' => [
    // ... otros providers
    App\Providers\SupervisorPedidosServiceProvider::class,
],
```

## Ejemplo de Uso en Controller

```php
public function aprobar($id)
{
    try {
        $request = new ApproveOrderRequest((int) $id);
        $response = $this->approveOrderUseCase->execute($request);
        
        return response()->json($response->toArray());
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
```

---

**Fecha de Implementación**: Marzo 2026
**Estado**: Estructura base completada, 4 Use Cases principales implementados
