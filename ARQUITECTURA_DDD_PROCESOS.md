# 📐 Estructura DDD para Procesos

## 📂 Árbol de Directorios

```
app/
├── Domain/
│   └── Procesos/                          ← BOUNDED CONTEXT
│       ├── Entities/
│       │   ├── TipoProceso.php
│       │   └── ProcesoPrendaDetalle.php
│       ├── Repositories/
│       │   ├── TipoProcesoRepository.php
│       │   └── ProcesoPrendaDetalleRepository.php
│       ├── Services/
│       │   ├── CrearProcesoPrendaService.php
│       │   ├── AprobarProcesoPrendaService.php
│       │   └── RechazarProcesoPrendaService.php
│       └── ValueObjects/
│           └── (futuro: EstadoProceso, TallaSet, etc)
│
├── Application/
│   └── Actions/
│       └── Procesos/
│           ├── CrearProcesoAction.php
│           ├── AprobarProcesoAction.php
│           ├── RechazarProcesoAction.php
│           └── (más actions...)
│
├── DTOs/
│   └── CrearProcesoPrendaDTO.php
│
└── Http/
    └── Controllers/
        └── Api/
            └── ProcesosController.php

database/
├── migrations/
│   └── 2026_01_14_000000_create_procesos_tables.php
└── seeders/
    └── TiposProcesosSeeder.php
```

## 🏗️ Capas de la Arquitectura DDD

### 1️⃣ DOMAIN LAYER (app/Domain/Procesos/)

**Propósito:** Contiene la lógica de negocio pura, sin dependencias a frameworks

#### Entities
- **TipoProceso** - Entidad que representa un tipo de proceso
  - Contiene valores: nombre, slug, descripcion, color, icono, activo
  - Métodos: getNombre(), isActivo(), desactivar(), activar()
  - NO tiene awareness de persistencia

- **ProcesoPrendaDetalle** - Entidad que representa un proceso asignado a una prenda
  - Contiene valores: ubicaciones, observaciones, tallas, estado, etc
  - Métodos de transición de estado: aprobar(), rechazar(), enviarAProduccion(), marcarCompletado()
  - Valida lógica de negocio en métodos (ej: no puedo aprobar si no está PENDIENTE)

#### Repositories (Interfaces)
- **TipoProcesoRepository** - Contrato para persistencia de tipos de procesos
  - obtenerPorId(), obtenerPorSlug(), obtenerTodos(), obtenerActivos()
  - guardar(), actualizar(), eliminar()

- **ProcesoPrendaDetalleRepository** - Contrato para persistencia de procesos
  - obtenerPorId(), obtenerPorPrenda(), obtenerPorPedido()
  - obtenerPendientes(), obtenerAprobados(), obtenerCompletados()
  - guardar(), actualizar(), eliminar()

#### Domain Services
- **CrearProcesoPrendaService** - Orquesta la lógica de creación
  - Valida que no exista otro proceso del mismo tipo
  - Valida ubicaciones
  - Crea la entity y la persiste

- **AprobarProcesoPrendaService** - Orquesta la lógica de aprobación
  - Obtiene el proceso
  - Llama al método aprobar() de la entity
  - Actualiza en persistencia

- **RechazarProcesoPrendaService** - Orquesta la lógica de rechazo
  - Valida que el motivo sea válido
  - Obtiene el proceso
  - Llama al método rechazar() de la entity
  - Actualiza en persistencia

### 2️⃣ APPLICATION LAYER (app/Application/Actions/)

**Propósito:** Use Cases u Orquestación. Coordina entre Domain y Infrastructure

#### Actions (Use Cases)
- **CrearProcesoAction** - Use case completo para crear un proceso
  - ✅ Validar tipo de proceso existe (Infrastructure)
  - ✅ Procesar imagen (Infrastructure - Storage)
  - ✅ Ejecutar domain service (Domain)
  - ✅ Actualizar persistencia (Infrastructure)

Próximas Actions:
- AprobarProcesoAction
- RechazarProcesoAction
- ActualizarProcesoAction
- EliminarProcesoAction
- ListarProcesosPrendaAction

### 3️⃣ INFRASTRUCTURE LAYER

#### Repositories Implementation
```
app/Repositories/
├── EloquentTipoProcesoRepository.php    (implementa TipoProcesoRepository)
└── EloquentProcesoPrendaDetalleRepository.php  (implementa ProcesoPrendaDetalleRepository)
```

#### Models
```
app/Models/
├── TipoProceso.php      (Eloquent Model)
└── ProcesoPrendaDetalle.php  (Eloquent Model)
```

### 4️⃣ PRESENTATION LAYER (app/Http/Controllers/)

#### API Controllers
```
app/Http/Controllers/Api/
└── ProcesosController.php
  - crear(Request, prendaId)
  - obtenerProcesosPrenda(prendaId)
  - actualizar(Request, procesoId)
  - eliminar(procesoId)
  - aprobar(Request, procesoId)
  - rechazar(Request, procesoId)
  - tiposDisponibles()
```

### 5️⃣ DATA TRANSFER LAYER (app/DTOs/)

#### DTOs
- **CrearProcesoPrendaDTO** - Transfiere datos desde request a domain
  - fromRequest(): convierte request en DTO
  - toArray(): convierte DTO en array

## 🔄 Flujo de Datos (Ejemplo: Crear Proceso)

```
1. HTTP Request
   POST /api/prendas/150/procesos
   {
     "tipo_proceso_id": 1,
     "ubicaciones": ["Frente", "Espalda"],
     "observaciones": "Reflectivo de 3M",
     "tallas_dama": ["S", "M"],
     "tallas_caballero": ["L"],
     "imagen": "base64..."
   }

2. PRESENTATION LAYER
   ├─ ProcesosController::crear()
   ├─ Validar request (validation rules)
   ├─ Crear DTO: CrearProcesoPrendaDTO::fromRequest()
   └─ Llamar Action: $action->ejecutar($dto)

3. APPLICATION LAYER
   ├─ CrearProcesoAction::ejecutar($dto)
   ├─ Validar tipo de proceso existe (TipoProcesoRepository)
   ├─ Procesar imagen (Storage)
   └─ Llamar Domain Service

4. DOMAIN LAYER
   ├─ CrearProcesoPrendaService::ejecutar()
   ├─ Validar no existe otro proceso del mismo tipo
   ├─ Validar ubicaciones no vacías
   ├─ Crear Entity: new ProcesoPrendaDetalle()
   └─ Guardar con Repository

5. INFRASTRUCTURE LAYER
   ├─ ProcesoPrendaDetalleRepository::guardar()
   ├─ Usar Eloquent Model
   ├─ INSERT en BD: procesos_prenda_detalles
   └─ Retornar Entity

6. RESPONSE
   HTTP 201 Created
   {
     "success": true,
     "data": {
       "id": 1,
       "tipo_proceso_id": 1,
       "ubicaciones": [...],
       ...
     }
   }
```

## 📦 Patrón de Inyección de Dependencias

```php
// Registrar en Service Provider (AppServiceProvider.php)
$this->app->bind(
    \App\Domain\Procesos\Repositories\TipoProcesoRepository::class,
    \App\Repositories\EloquentTipoProcesoRepository::class
);

$this->app->bind(
    \App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository::class,
    \App\Repositories\EloquentProcesoPrendaDetalleRepository::class
);

// En controlador o action
public function __construct(
    private TipoProcesoRepository $tipoProcesoRepository,
    private CrearProcesoAction $crearProcesoAction
) {}
```

## ✅ Ventajas de Esta Estructura

1. **Separación de Responsabilidades** - Cada capa tiene un propósito claro
2. **Testable** - Domain logic sin dependencias a framework
3. **Mantenible** - Fácil encontrar dónde está cada cosa
4. **Escalable** - Agregar nuevos procesos sin afectar lo existente
5. **Reutilizable** - Domain services y actions pueden usarse en CLI, jobs, etc
6. **Flexible** - Repository pattern permite cambiar persistencia fácilmente

## 🚀 Próximos Pasos

- [ ] Crear Eloquent Models (TipoProceso.php, ProcesoPrendaDetalle.php)
- [ ] Crear Repository Implementations
- [ ] Crear Service Provider para binding de dependencias
- [ ] Crear Actions para operaciones restantes (actualizar, aprobar, rechazar, etc)
- [ ] Crear Value Objects (EstadoProceso, TallaSet, Ubicaciones)
- [ ] Crear Tests unitarios para Domain logic
- [ ] Crear Tests de integración para Actions
- [ ] Crear API Tests para Controllers
- [ ] Actualizar modal JavaScript para enviar requests correctamente
- [ ] Crear frontend para mostrar procesos y cambiar estados

