# ✅ CHECKLIST: FASE 0 – PREPARACIÓN

**Estado:** En progreso  
**Objetivo:** Setup de estructura sin usar en producción

---

## 📁 Carpetas Creadas

- [x] `app/Domain/Pedidos/Agregado/`
- [x] `app/Domain/Pedidos/Entities/`
- [x] `app/Domain/Pedidos/ValueObjects/`
- [x] `app/Domain/Pedidos/Repositories/`
- [x] `app/Domain/Pedidos/Services/`
- [x] `app/Domain/Pedidos/Events/`
- [x] `app/Domain/Pedidos/Exceptions/`
- [x] `app/Application/Pedidos/UseCases/`
- [x] `app/Application/Pedidos/DTOs/`
- [x] `app/Application/Pedidos/Listeners/`
- [x] `app/Infrastructure/Pedidos/Persistence/Eloquent/`
- [x] `app/Infrastructure/Pedidos/Providers/`
- [x] `tests/Unit/Domain/Pedidos/`

---

## 📄 Archivos Domain Creados

### Value Objects
- [x] `app/Domain/Pedidos/ValueObjects/NumeroPedido.php`
- [x] `app/Domain/Pedidos/ValueObjects/Estado.php`

### Entities
- [x] `app/Domain/Pedidos/Entities/PrendaPedido.php`

### Agregado Raíz
- [x] `app/Domain/Pedidos/Agregado/PedidoAggregate.php`

### Repository Interface
- [x] `app/Domain/Pedidos/Repositories/PedidoRepository.php`

### Domain Events
- [x] `app/Domain/Pedidos/Events/PedidoCreado.php`
- [x] `app/Domain/Pedidos/Events/PedidoActualizado.php`
- [x] `app/Domain/Pedidos/Events/PedidoEliminado.php`

### Custom Exceptions
- [x] `app/Domain/Pedidos/Exceptions/PedidoNoEncontrado.php`
- [x] `app/Domain/Pedidos/Exceptions/EstadoPedidoInvalido.php`

---

## 📄 Archivos Application Creados

### DTOs
- [x] `app/Application/Pedidos/DTOs/CrearPedidoDTO.php`
- [x] `app/Application/Pedidos/DTOs/PedidoResponseDTO.php`

### Use Cases
- [x] `app/Application/Pedidos/UseCases/CrearPedidoUseCase.php`
- [x] `app/Application/Pedidos/UseCases/ConfirmarPedidoUseCase.php`

### Listeners
- [x] `app/Application/Pedidos/Listeners/PedidoCreadoListener.php`

---

## 📄 Archivos Infrastructure Creados

### Persistence
- [x] `app/Infrastructure/Pedidos/Persistence/Eloquent/PedidoRepositoryImpl.php`

### Providers
- [x] `app/Infrastructure/Pedidos/Providers/PedidoServiceProvider.php`

---

## 🧪 Tests Creados

- [x] `tests/Unit/Domain/Pedidos/PedidoAggregateTest.php`
  - Test 1: Crear pedido válido
  - Test 2: Confirmar pedido
  - Test 3: No permitir confirmar pedido finalizado

---

## ✅ PRÓXIMOS PASOS

### 1. Verificar que compila
```bash
php artisan tinker
# En tinker:
$pedido = \App\Domain\Pedidos\Agregado\PedidoAggregate::crear(1, 'Test', [[...]]);
dd($pedido);
```

### 2. Ejecutar tests
```bash
php artisan test tests/Unit/Domain/Pedidos/PedidoAggregateTest.php
```

### 3. Si todo compila y los tests pasan ✅
Pasar a **Fase 1 – Dominio completo**

---

## 📝 NOTAS IMPORTANTES

- Los Value Objects no dependen de Eloquent
- El Agregado es puro, solo lógica de negocio
- Los DTOs hacen validación básica
- El Repository (Interface) define el contrato
- La implementación está en Infrastructure
- Los tests NO usan BD, solo lógica pura

**Estado de Fase 0:** ✅ COMPLETA

Próximo: Comenzar Fase 1 cuando estos tests pasen.
