# 🏗️ Arquitectura de Orden (MUNDOINDUSTRIAL v10)

**Estado:** ✅ DDD + SOLID Completos  
**Última Actualización:** Diciembre 6, 2025  
**Versión:** 1.0 (FASE 4 - Finalizado)

---

## 📊 Diagrama de Capas

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER (HTTP)                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  WEB ROUTES                    API ROUTES                           │
│  ├─ /registros (Query)         ├─ GET    /api/v1/ordenes          │
│  ├─ /registros (CRUD)          ├─ POST   /api/v1/ordenes          │
│  └─ /registros/{id} (Query)    ├─ PATCH  /api/v1/ordenes/{id}/*   │
│                                └─ DELETE /api/v1/ordenes/{id}      │
│                                                                      │
│  RegistroOrdenQueryController  Api/V1/OrdenController              │
│  RegistroOrdenController       (DDD Pure)                           │
│  (CRUD Legacy)                                                      │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                                ▲
                                │ HTTP Requests
                                │
┌─────────────────────────────────────────────────────────────────────┐
│              APPLICATION LAYER (Business Logic Orchestration)       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Application Services (Handles transactions, validation, events)    │
│  ├─ CrearOrdenService                                              │
│  ├─ ActualizarEstadoOrdenService                                   │
│  ├─ CancelarOrdenService                                           │
│  └─ ObtenerOrdenService                                            │
│                                                                      │
│  Query Services (Optimized for reading)                             │
│  ├─ RegistroOrdenExtendedQueryService                              │
│  ├─ RegistroOrdenSearchExtendedService                             │
│  └─ RegistroOrdenFilterExtendedService                             │
│                                                                      │
│  Helper Services (Cross-cutting concerns)                           │
│  ├─ RegistroOrdenValidationService                                 │
│  ├─ RegistroOrdenNumberService                                     │
│  ├─ RegistroOrdenCacheService                                      │
│  └─ RegistroOrdenProcessesService                                  │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                                ▲
                                │
┌─────────────────────────────────────────────────────────────────────┐
│                    DOMAIN LAYER (Pure Business Rules)               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  BOUNDED CONTEXT: Ordenes                                           │
│                                                                      │
│  Entities:                                                          │
│  ├─ Orden (Aggregate Root)                                         │
│  │   ├─ State Machine (5 states)                                   │
│  │   ├─ Business Methods                                           │
│  │   └─ Collections: Prendas                                       │
│  └─ Prenda (Child Entity)                                          │
│                                                                      │
│  Value Objects (Immutable):                                         │
│  ├─ NumeroOrden (Int, validated)                                   │
│  ├─ EstadoOrden (Enum: Borrador, Aprobada, EnProduccion, ...)     │
│  ├─ FormaPago (Enum: Contado, Crédito30, Crédito60, ...)           │
│  └─ Area (Enum: Corte, Costura, Bodega, ...)                       │
│                                                                      │
│  Domain Events (Published):                                         │
│  ├─ OrdenCreada                                                     │
│  ├─ PrendaAgregada                                                  │
│  └─ OrdenActualizada                                                │
│                                                                      │
│  Specifications (Business Rules):                                   │
│  ├─ OrdenEnProduccion (IsSatisfiedBy)                              │
│  ├─ OrdenCompleta (IsSatisfiedBy)                                  │
│  └─ PuedeCancelarse (IsSatisfiedBy)                                │
│                                                                      │
│  Repository Interface (Abstraction):                                │
│  └─ OrdenRepositoryInterface                                        │
│      ├─ save(Orden)                                                │
│      ├─ getById(numero)                                            │
│      ├─ porEstado(estado)                                          │
│      └─ porCliente(cliente)                                        │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
                                ▲
                                │
┌─────────────────────────────────────────────────────────────────────┐
│                  INFRASTRUCTURE LAYER (Implementation)              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Repositories:                                                      │
│  └─ EloquentOrdenRepository implements OrdenRepositoryInterface   │
│     ├─ Translates Domain Model ↔ Eloquent Models                  │
│     └─ Handles persistence logic                                   │
│                                                                      │
│  Eloquent Models (ORM):                                             │
│  ├─ PedidoProduccion (represents Orden table)                      │
│  ├─ PrendaPedido (represents Prenda table)                         │
│  └─ Helper models                                                   │
│                                                                      │
│  IoC Configuration:                                                 │
│  └─ DomainServiceProvider                                           │
│     ├─ Registers OrdenRepositoryInterface                          │
│     ├─ Registers Application Services                              │
│     └─ Binds to Service Container                                  │
│                                                                      │
│  Database (PostgreSQL/MySQL):                                       │
│  ├─ tabla_original (Orders)                                         │
│  ├─ registros_por_orden (Garments)                                 │
│  ├─ procesos_prenda (Process tracking)                             │
│  └─ festivos (Holiday calendar)                                    │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📁 Estructura de Directorios

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── RegistroOrdenController.php           (CRUD Legacy)
│   │   ├── RegistroOrdenQueryController.php      (Query/Search)
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       └── OrdenController.php           (DDD HTTP Layer)
│   │   └── RegistroOrdenExceptionHandler.php     (Error handling)
│   ├── Requests/
│   │   ├── CrearOrdenRequest.php                 (Validation)
│   │   └── ActualizarOrdenRequest.php
│   └── Resources/
│       ├── OrdenResource.php                     (Serialization)
│       └── PrendaResource.php
│
├── Domain/
│   └── Ordenes/                                  (Bounded Context)
│       ├── Entities/
│       │   ├── Orden.php                         (Aggregate Root)
│       │   └── Prenda.php                        (Child Entity)
│       ├── ValueObjects/
│       │   ├── NumeroOrden.php
│       │   ├── EstadoOrden.php
│       │   ├── FormaPago.php
│       │   └── Area.php
│       ├── Events/
│       │   ├── OrdenCreada.php
│       │   ├── PrendaAgregada.php
│       │   └── OrdenActualizada.php
│       ├── Specifications/
│       │   ├── OrdenEnProduccion.php
│       │   ├── OrdenCompleta.php
│       │   └── PuedeCancelarse.php
│       ├── Repositories/
│       │   └── OrdenRepositoryInterface.php
│       └── Services/
│           ├── CrearOrdenService.php             (Application Service)
│           ├── ActualizarEstadoOrdenService.php
│           ├── CancelarOrdenService.php
│           └── ObtenerOrdenService.php
│
├── Repositories/
│   └── EloquentOrdenRepository.php               (Implementation)
│
├── Services/
│   ├── RegistroOrdenValidationService.php        (Validation)
│   ├── RegistroOrdenCreationService.php          (Creation logic)
│   ├── RegistroOrdenUpdateService.php            (Update logic)
│   ├── RegistroOrdenQueryService.php             (Query building)
│   ├── RegistroOrdenFilterService.php            (Filtering)
│   ├── RegistroOrdenSearchService.php            (Search)
│   └── ... (other helper services)
│
├── Models/
│   ├── PedidoProduccion.php                      (Orders table)
│   ├── PrendaPedido.php                          (Garments table)
│   └── ... (other models)
│
└── Providers/
    └── DomainServiceProvider.php                 (IoC Registration)

routes/
├── web.php                                       (Web routes)
└── api.php                                       (API routes - DDD)

docs/
├── FASE-1-BOUNDED-CONTEXT.md
├── FASE-2-REPOSITORY-PATTERN.md
├── FASE-3-DDD-HTTP-INTEGRATION.md
└── FASE-4-SOLID-DDD-REFACTORING.md              (THIS FILE)
```

---

## 🔄 Flujo de Solicitud

### Crear Orden (POST /api/v1/ordenes)

```
1. HTTP Request
   POST /api/v1/ordenes { cliente, prendas, forma_pago, ... }
   ↓
2. Api/V1/OrdenController::store()
   - Delega a CrearOrdenService
   ↓
3. CrearOrdenService::ejecutar()
   - Valida datos del request
   - Crea instancia de Orden (Domain Model)
   - Aplica reglas de negocio
   ↓
4. Orden (Aggregate Root)
   - Valida estado inicial (Borrador)
   - Crea colección de Prendas
   - Publica evento: OrdenCreada
   ↓
5. EloquentOrdenRepository::save()
   - Traduce Domain Model → Eloquent Model
   - Persiste en BD
   ↓
6. Event Dispatcher
   - Publica OrdenCreada
   - Triggers listeners
   ↓
7. HTTP Response
   { success: true, numero_pedido: 1234 }
```

### Listar Órdenes (GET /registros)

```
1. HTTP Request
   GET /registros?estado=Aprobada&cliente=ACME
   ↓
2. RegistroOrdenQueryController::index()
   - Extrae filtros del request
   ↓
3. RegistroOrdenExtendedQueryService
   - Construye query base
   - Aplica filtros de rol
   ↓
4. RegistroOrdenFilterExtendedService
   - Extrae filtros dinámicos
   - Aplica a query
   ↓
5. Database Query
   - Ejecuta query optimizada
   - Paginación (25 items/página)
   ↓
6. CacheCalculosService
   - Calcula días (cached)
   - Obtiene áreas
   ↓
7. RegistroOrdenTransformService
   - Transforma modelos
   - Filtra campos sensibles por rol
   ↓
8. HTTP Response
   {
     orders: [...],
     totalDiasCalculados: {...},
     pagination: {...}
   }
```

### Actualizar Estado (PATCH /api/v1/ordenes/1234/aprobar)

```
1. HTTP Request
   PATCH /api/v1/ordenes/1234/aprobar
   ↓
2. Api/V1/OrdenController::aprobar()
   - Delega a ActualizarEstadoOrdenService
   ↓
3. ActualizarEstadoOrdenService::aprobar()
   - Obtiene orden del repositorio
   - Valida transición de estado
   ↓
4. Orden (Aggregate Root)
   - Aplica Specification: PuedeCancelarse
   - Transiciona: Borrador → Aprobada
   - Publica evento: OrdenActualizada
   ↓
5. EloquentOrdenRepository::save()
   - Persiste cambios
   ↓
6. Event Dispatcher
   - Publica OrdenActualizada
   ↓
7. HTTP Response
   { success: true, message: "Orden 1234 aprobada" }
```

---

## 🎯 Patrones Utilizados

### 1. Domain-Driven Design (DDD)

**Ubicación:** `app/Domain/Ordenes/`

```php
// Aggregate Root con state machine
$orden = Orden::crear(
    NumeroOrden::from(1234),
    $cliente,
    FormaPago::CREDITO_30(),
    Area::COSTURA()
);

// Validar negocio
$orden->agregarPrenda(
    new Prenda('Polo', 100, 'S,M,L')
);

// Transición de estado
if ((new PuedeCancelarse())->isSatisfiedBy($orden)) {
    $orden->cancelar();
}
```

### 2. Application Services

**Ubicación:** `app/Domain/Ordenes/Services/`

```php
// Orquesta domain logic y side effects
class CrearOrdenService {
    public function ejecutar(array $datos): int
    {
        // Crea aggregate
        $orden = Orden::crear(...);
        
        // Persiste
        $this->repository->save($orden);
        
        // Publica evento
        event(new OrdenCreada($orden));
        
        return $orden->getNumeroPedido()->toInt();
    }
}
```

### 3. Repository Pattern

**Ubicación:** `app/Repositories/`

```php
// Abstrae persistencia
interface OrdenRepositoryInterface {
    public function save(Orden $orden): void;
    public function getById(int $numero): ?Orden;
    public function porEstado(EstadoOrden $estado): Collection;
}

// Implementación con Eloquent
class EloquentOrdenRepository implements OrdenRepositoryInterface {
    public function save(Orden $orden): void
    {
        PedidoProduccion::updateOrCreate(
            ['numero_pedido' => $orden->getNumeroPedido()->toInt()],
            $this->toPersistenceModel($orden)
        );
    }
}
```

### 4. Specification Pattern

**Ubicación:** `app/Domain/Ordenes/Specifications/`

```php
// Encapsula regla de negocio
class PuedeCancelarse {
    public function isSatisfiedBy(Orden $orden): bool
    {
        $estado = $orden->getEstado();
        
        // Solo puedes cancelar orden no-completada
        return !$estado->equals(EstadoOrden::COMPLETADA());
    }
}

// Uso
if ((new PuedeCancelarse())->isSatisfiedBy($orden)) {
    $orden->cancelar();
} else {
    throw new \DomainException("No puedes cancelar orden completada");
}
```

### 5. Value Objects

**Ubicación:** `app/Domain/Ordenes/ValueObjects/`

```php
// Tipado seguro
class NumeroOrden {
    private int $numero;
    
    public function __construct(int $numero)
    {
        if ($numero <= 0) {
            throw new \InvalidArgumentException("Número debe ser > 0");
        }
        $this->numero = $numero;
    }
    
    public static function from(int $numero): self
    {
        return new self($numero);
    }
    
    public function toInt(): int
    {
        return $this->numero;
    }
    
    public function equals(NumeroOrden $other): bool
    {
        return $this->numero === $other->numero;
    }
}
```

### 6. Domain Events

**Ubicación:** `app/Domain/Ordenes/Events/`

```php
// Eventos de negocio
class OrdenCreada {
    public function __construct(public Orden $orden) {}
}

// Listeners reaccionan
class EnviarNotificacionOrdenCreada {
    public function handle(OrdenCreada $event)
    {
        // Notificar cliente
        // Registrar en auditoria
        // Actualizar stocks
    }
}
```

---

## 🏛️ Principios SOLID Implementados

### S - Single Responsibility
- RegistroOrdenController: Solo CRUD
- RegistroOrdenQueryController: Solo Query
- Api/V1/OrdenController: Solo DDD

### O - Open/Closed
- Extensible sin modificación
- Nuevos filtros en QueryController
- Nuevas transiciones en DDD controller

### L - Liskov Substitution
- Cada controller reemplazable en su contexto
- No viola contratos de clase base

### I - Interface Segregation
- RegistroOrdenController inyecta solo 9 dependencias
- Api/V1/OrdenController inyecta solo 4

### D - Dependency Inversion
- Controllers dependen de abstracciones (Services)
- Nunca de modelos concretos

---

## 📈 Evolución del Proyecto

```
FASE 1: Bounded Context
├─ Crear Aggregate Root (Orden)
├─ Crear Value Objects
├─ Crear Domain Events
└─ Crear Specifications

FASE 2: Repository Pattern
├─ Crear Repository Interface
├─ Crear EloquentOrdenRepository
├─ Crear Application Services
└─ Registrar en IoC

FASE 3: DDD HTTP Integration
├─ Crear Form Requests
├─ Crear API Resources
├─ Crear RegistroOrdenDDDController
└─ Definir rutas /api/v1/ordenes

FASE 4: SOLID Refactoring ✅
├─ Separar en 3 controllers
├─ Actualizar rutas
├─ Implementar SOLID completo
└─ Documentar
```

---

## 🧪 Testing

### Unit Test (Domain Model)

```php
public function testOrdenTransition()
{
    $orden = Orden::crear(
        NumeroOrden::from(1),
        'Cliente ACME',
        FormaPago::CONTADO(),
        Area::COSTURA()
    );
    
    // Inicial
    $this->assertTrue($orden->getEstado()->equals(EstadoOrden::BORRADOR()));
    
    // Transición
    $orden->aprobar();
    $this->assertTrue($orden->getEstado()->equals(EstadoOrden::APROBADA()));
}
```

### Integration Test (Controller)

```php
public function testStoreOrder()
{
    $response = $this->postJson('/api/v1/ordenes', [
        'numero_pedido' => 1234,
        'cliente' => 'ACME',
        'prendas' => [['nombre' => 'Polo', 'cantidad' => 100]],
        'forma_pago' => 'CONTADO',
        'area' => 'COSTURA'
    ]);
    
    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.numero_pedido', 1234);
}
```

---

## 📚 Documentación Adicional

- [FASE 1: Bounded Context](./FASE-1-BOUNDED-CONTEXT.md)
- [FASE 2: Repository Pattern](./FASE-2-REPOSITORY-PATTERN.md)
- [FASE 3: DDD HTTP Integration](./FASE-3-DDD-HTTP-INTEGRATION.md)
- [FASE 4: SOLID Refactoring](./FASE-4-SOLID-DDD-REFACTORING.md)

---

## 🚀 Próximos Pasos

1. **Agregar más Bounded Contexts**
   - Proveedores
   - Empleados
   - Inventario
   
2. **Event Sourcing** (opcional)
   - Registrar todos los eventos
   - Reconstruir estado desde eventos

3. **CQRS** (Command Query Responsibility Segregation)
   - Separar lectura y escritura
   - Optimizar cada una

4. **Microservicios** (en futuro)
   - Cada Bounded Context → Microservicio
   - Comunicación vía eventos

---

**Versión:** 1.0  
**Última actualización:** Diciembre 6, 2025  
**Status:** ✅ Production Ready
