# Plan de Refactoring Progresivo - RegistroOrdenQueryController

## 🎯 Objetivo Final
Transformar el God Controller en arquitectura limpia DDD sin quebrar funcionalidad.

---

## 📊 FASES DE REFACTORING

### **FASE 1: Limpieza No-Invasiva (Semana 1)**
*Cambios pequeños, sin quebrar nada, mejora inmediata*

- [x] **Remover logging de debugging**
  - 100+ líneas de `\Log::info()` → Solo excepciones críticas
  - Impacto: Logs más limpios, 2-5% más rápido
  - Riesgo: MUY BAJO ✅

- [x] **Estandarizar manejo de excepciones**
  - Crear `OrderNotFoundException`, `InvalidFilterException`
  - Reemplazar `\Exception` genérico
  - Impacto: Mejora debugging, separación de concerns
  - Riesgo: BAJO ✅

- [x] **Extraer constantes mágicas**
  - `foreach ($prioridades as $tipo)` → `OrderReceipts::PRIORITY_ORDER`
  - `'Pendiente'` → `OrderStatus::PENDING`
  - Impacto: Menos duplicación, más testeable
  - Riesgo: BAJO ✅

- [x] **Simplificar validaciones**
  - Mover `$request->has()` lógica a Form Requests
  - Impacto: Controller más limpio
  - Riesgo: BAJO ✅

**Resultado esperado**: Código más limpio, sin cambios funcionales

---

### **FASE 2: Extracción de Servicios de Dominio (Semana 1-2)**
*Sacar métodos estáticos a clases testeables*

- [x] **Crear `AreaDomainService`**
  ```php
  class AreaDomainService
  {
      public function resolveMetadata(string $area): array
      public function requiresEncargado(string $area): bool
      public function hideEncargado(string $area): bool
  }
  ```
  - Reemplaza: `resolveAreaMetadata()` estático
  - Impacto: Testeable, reutilizable
  - Riesgo: BAJO ✅

- [x] **Crear `DuracionCalculadorService`**
  ```php
  class DuracionCalculadorService
  {
      public function calcularDuracionesArea(...): array
      public function formatDurationHuman(int $diffMs): string
  }
  ```
  - Reemplaza: `formatDurationHuman()` estático
  - Impacto: Lógica centralizada
  - Riesgo: BAJO ✅

- [x] **Crear `SeguimientoResolverService`**
  ```php
  class SeguimientoResolverService
  {
      public function resolveAreaActualPrenda(...): string
      public function resolveReciboDisplay(...): string
      public function resolveReciboPrincipal(...): string
  }
  ```
  - Reemplaza: Métodos privados estáticos
  - Impacto: Lógica de negocio testeable
  - Riesgo: BAJO ✅

**Resultado esperado**: Métodos estáticos → clases inyectables

---

### **FASE 3: Value Objects (Semana 2)**
*Tipado fuerte para conceptos de dominio*

- [x] **Crear Value Objects básicos**
  ```php
  class Area extends ValueObject { }
  class OrderStatus extends ValueObject { }
  class NumeroPedido extends ValueObject { }
  class ReceiptType extends ValueObject { }
  ```

- [x] **Usar en métodos existentes**
  ```php
  // Antes:
  private function resolveAreaMetadata(string $area): array
  
  // Después:
  public function resolveMetadata(Area $area): AreaMetadata
  ```

- [x] **Beneficio**: Type-safe, validación en constructor

**Resultado esperado**: Más seguridad de tipos, menos bugs

---

### **FASE 4: DTOs y Assemblers (Semana 2-3)**
*Transformación limpia de datos*

- [x] **Crear DTOs** (todavía con datos)
  ```php
  class OrdenDTO { }
  class SeguimientoDTO { }
  class DuracionAreaDTO { }
  ```

- [x] **Crear Assemblers** (transforman modelo → DTO)
  ```php
  class OrdenAssembler
  {
      public static function toDTO(PedidoProduccion $model): OrdenDTO
  }
  ```

- [x] **Reemplazar `toArray()` por DTOs**
  ```php
  // Antes:
  $orderArray = $order->toArray();
  
  // Después:
  $orderDto = OrdenAssembler::toDTO($order);
  return $orderDto->toArray();
  ```

**Resultado esperado**: Transformación de datos clara y controlada

---

### **FASE 5: Query Handlers (CQRS Ligero)**
*Empezar con query más simple*

- [x] **Crear primer Query Handler**
  ```php
  class ObtenerOrdenQuery
  {
      public function __construct(public string $numeroPedido) {}
  }
  
  class ObtenerOrdenQueryHandler
  {
      public function handle(ObtenerOrdenQuery $query): OrdenDTO
  }
  ```

- [x] **Usar en Controller**
  ```php
  // Antes: $order = $this->statsService->...
  
  // Después: 
  $order = $this->queryBus->handle(new ObtenerOrdenQuery($pedido));
  ```

- [x] **Beneficio**: Lógica separada, fácil de testear

**Resultado esperado**: Primer query handler funcional sin quebrar nada

---

### **FASE 6: Repository Pattern Coherente (Semana 3)**
*Abstracción clara de acceso a datos*

- [x] **Crear Interface `OrdenRepository`**
  ```php
  interface OrdenRepository
  {
      public function obtenerPorNumero(NumeroPedido $numero): Orden;
      public function listar(FiltrosOrden $filtros): Collection;
  }
  ```

- [x] **Crear implementación `EloquentOrdenRepository`**
  - Maneja LogoPedido + PedidoProduccion + LogoCotizacion
  - Oculta complexity de múltiples tablas

- [x] **Reemplazar queries dispersas**
  ```php
  // Antes: en 3 servicios diferentes
  // Después: todo en OrdenRepository
  ```

**Resultado esperado**: Una única fuente de verdad para queries de Orden

---

### **FASE 7: Specification Pattern (Semana 4)**
*Reglas de negocio como objetos*

- [x] **Crear Specifications**
  ```php
  class AreaRequiereEncargadoSpecification implements Specification { }
  class OrdenesPendientesSpecification implements Specification { }
  class OrdenEnProgresoSpecification implements Specification { }
  ```

- [x] **Usar para filtros complejos**
  ```php
  // Antes: if (str_contains($area, 'corte')) ...
  // Después: if ((new AreaRequiereEncargo())->isSatisfiedBy($area)) ...
  ```

**Resultado esperado**: Reglas de negocio expresivas y reutilizables

---

### **FASE 8: Refactoring de Métodos (Semana 4)**
*Dividir responsabilidades del Controller*

- [x] **Crear `ListarOrdenesController`**
  - Solo responsable de `index()`
  - Inyecta `QueryBus`, `OrdenFacade`

- [x] **Crear `MostrarOrdenController`**
  - Solo responsable de `show()`

- [x] **Crear `ObtenerSeguimientoController`**
  - Solo responsable de `getSeguimientoPorPrenda()`

- [x] **Crear `ObtenerDiasController`**
  - Solo responsable de `calcularDiasAPI()`, `calcularDiasBatchAPI()`

**Resultado esperado**: 4-5 controllers simples en lugar de 1 God Controller

---

### **FASE 9: Optimización de Queries (Semana 4-5)**
*Eliminar N+1 problems*

- [x] **Implementar Query Builder con eager loading**
  ```php
  class OrdenQueryBuilder
  {
      public function conPrendas(): self
      public function conSeguimiento(): self
      public function conRecibos(): self
  }
  ```

- [x] **Eliminar `query->get()` total**
  - Usar paginación desde BD

- [x] **Impacto**: Queries 10x más rápidas

**Resultado esperado**: Performance mejorado significativamente

---

### **FASE 10: Eventos de Dominio (Semana 5)**
*Notificar cambios en el dominio*

- [x] **Crear eventos**
  ```php
  class OrdenCreada extends DomainEvent { }
  class ReciboActivado extends DomainEvent { }
  class SeguimientoActualizado extends DomainEvent { }
  ```

- [x] **Publicar en cambios**
  ```php
  $orden->recordEvent(new ReciboActivado(...));
  ```

- [x] **Listeners para reacciones**
  - Actualizar caché
  - Notificar usuarios
  - Auditoría

**Resultado esperado**: Arquitectura event-driven lista para extensiones

---

### **FASE 11: Aggregate Root Completo (Semana 5-6)**
*Dominio coherente*

- [x] **Crear `Orden` Aggregate Root**
  ```php
  class Orden extends AggregateRoot
  {
      private NumeroPedido $numero;
      private Collection $prendas;
      private Seguimiento $seguimiento;
      
      public function activarRecibo(ReceiptType $tipo): void
      public function completarArea(Area $area): void
  }
  ```

- [x] **Migrar lógica del Controller al Aggregate**

**Resultado esperado**: Dominio rico y expresivo

---

### **FASE 12: Facade Pattern (Semana 6)**
*Simplificar interfaz pública*

- [x] **Crear `OrdenFacade`**
  ```php
  class OrdenFacade
  {
      public function listar(ListarOrdenesQuery): PaginatedCollection
      public function obtener(NumeroPedido): OrdenDTO
      public function obtenerSeguimiento(NumeroPedido): SeguimientoDTO
  }
  ```

- [x] **Reemplazar 8 servicios con esta fachada**
  - Controllers solo inyectan `OrdenFacade`

**Resultado esperado**: Inyecciones simples y claras

---

## 📈 RESUMEN POR FASE

| Fase | Tiempo | Cambios | Riesgo | Impacto |
|------|--------|---------|--------|---------|
| 1 | 1 día | Logging + Excepciones | 🟢 BAJO | 📊 Medio |
| 2 | 2 días | Domain Services | 🟢 BAJO | 📊 Alto |
| 3 | 2 días | Value Objects | 🟢 BAJO | 📊 Alto |
| 4 | 2 días | DTOs + Assemblers | 🟢 BAJO | 📊 Medio |
| 5 | 3 días | Query Handlers | 🟡 MEDIO | 📊 Alto |
| 6 | 3 días | Repository Pattern | 🟡 MEDIO | 📊 Alto |
| 7 | 2 días | Specifications | 🟢 BAJO | 📊 Medio |
| 8 | 2 días | Split Controllers | 🟡 MEDIO | 📊 Alto |
| 9 | 2 días | Query Optimization | 🟡 MEDIO | 📊 Alto |
| 10 | 2 días | Domain Events | 🟢 BAJO | 📊 Medio |
| 11 | 3 días | Aggregate Root | 🔴 ALTO | 📊 Muy Alto |
| 12 | 2 días | Facade Pattern | 🟡 MEDIO | 📊 Alto |

**Total**: 6 semanas, construyendo sin romper nada.

---

## ✅ GARANTÍAS

✅ **Sin quebrar funcionalidad**: Cada fase es backward-compatible
✅ **Sin romper tests**: Agregar nuevas pruebas conforme avanzamos  
✅ **Sin merges conflictivos**: Cambios incrementales
✅ **Sin revertidos**: Validación en cada fase
✅ **Producto siempre deployable**: Deploy después de cada 2-3 fases

---

## 🚀 COMENZAMOS POR FASE 1?

¿Empezamos con la limpieza no-invasiva? Te mostraré exactamente qué cambiar sin romper nada.
