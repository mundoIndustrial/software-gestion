# Sistema de Despacho - Documentación DDD

## Estructura Domain-Driven Design

### 1. **Domain Layer** (`app/Domain/Pedidos/Despacho/`)

#### Entities (Entidades)
- **DesparChoParcial**: Entidad raíz del agregado que representa un despacho parcial
  - Métodos factory para crear instancias seguras
  - Lógica de negocio: validaciones, cálculos
  - Getters para acceder a propiedades (encapsulación)

#### Repositories (Interfaces)
- **DesparChoParcialesRepository**: Contrato para la persistencia
  - Define operaciones CRUD
  - Abstraer la infraestructura
  - Inverter la dependencia (Application → Domain)

#### Services (Domain Services)
- **DespachoGeneradorService**: Generar filas de despacho
- **DespachoValidadorService**: Validar lógica de negocio
- **DesparChoParcialesPersistenceService**: Coordinar persistencia de despachos

### 2. **Infrastructure Layer** (`app/Infrastructure/`)

#### Repositories (Implementaciones)
- **DesparChoParcialesRepositoryImpl**: Implementa persistencia con Eloquent
  - Conversión entidad ↔ modelo
  - Transacciones
  - Scopes y búsquedas

#### Models (Eloquent)
- **DesparChoParcialesModel**: Modelo de BD
  - Relaciones
  - Scopes
  - Métodos helpers

### 3. **Application Layer** (`app/Application/Pedidos/Despacho/`)

#### Use Cases
- **GuardarDespachoUseCase**: Coordina guardar despachos
  - Orquesta Domain Services
  - Maneja transacciones
  - Retorna DTOs

#### DTOs
- **ControlEntregasDTO**: Transfer Object del control completo
- **DespachoParcialesDTO**: Transfer Object del despacho parcial
- **FilaDespachoDTO**: Transfer Object para visualización

### 4. **HTTP Layer** (`app/Infrastructure/Http/Controllers/`)
- **DespachoController**: Recibe requests y delega a Use Cases

---

## Flujo de Datos

```
HTTP Request (Vista)
    ↓
DespachoController
    ↓
GuardarDespachoUseCase (Application)
    ├─ DespachoValidadorService (Domain) → Validar
    ├─ DesparChoParcialesPersistenceService (Domain)
    │   ├─ DesparChoParcial::crear() (Domain Entity)
    │   └─ DesparChoParcialesRepository (Domain Interface)
    │       └─ DesparChoParcialesRepositoryImpl (Infrastructure)
    │           └─ DesparChoParcialesModel (Eloquent)
    │               └─ Base de Datos
    └─ DTO Response
        ↓
    HTTP Response (JSON)
```

---

## Responsabilidades por Capa

### Domain (Lógica de negocio pura)
-  Reglas de negocio
-  Validaciones del dominio
-  Entidades con identidad
-  Value Objects
-  Interfaces de repositorios (NO implementaciones)

### Application (Orquestación)
-  Coordinar Domain Services
-  Transacciones
-  Manejo de errores
-  Logging de aplicación
- ❌ Lógica de negocio

### Infrastructure (Persistencia)
-  Implementación de repositorios
-  Modelos Eloquent
-  Migraciones
-  Queries complejas
- ❌ Lógica de negocio

### HTTP (API)
-  Recibir requests
-  Validar input
-  Delegar a Use Cases
-  Retornar responses

---

## Ejemplo: Guardar Despacho

```php
// 1. Vista envía JSON
POST /despacho/1/guardar
{
    "despachos": [
        { "tipo": "prenda", "id": 5, "parcial_1": 10, ... }
    ]
}

// 2. Controller recibe y crea DTO
$validated = $request->validate([...]);
$control = new ControlEntregasDTO($pedidoId, ..., $validated['despachos']);

// 3. UseCase orquesta
$resultado = $guardarDespacho->ejecutar($control);

// 4. UseCase valida (Domain Service)
$despachos = array_map(fn($d) => new DespachoParcialesDTO(...$d), $control->despachos);
$this->validador->validarMultiplesDespachos($despachos); // ← Domain Logic

// 5. UseCase persiste (Domain Service → Repository)
$this->persistencia->crearYGuardarMultiples($despachos, $usuarioId);

// 6. Persistencia Service crea entidades
DesparChoParcial::crear(...) // ← Entity with Business Logic

// 7. Repository guarda
$this->repository->guardar($despacho); // ← Infrastructure

// 8. Repository implementación persiste
DesparChoParcialesModel::create($data); // ← Eloquent
```

---

## Ventajas del Diseño

1. **Separación de responsabilidades**: Cada capa tiene un propósito claro
2. **Testeabilidad**: Fácil mockear dependencias
3. **Mantenibilidad**: Cambios en BD no afectan dominio
4. **Reutilización**: Domain Services reutilizables
5. **Escalabilidad**: Agregar nuevas features sin romper existentes
6. **Lógica centralizada**: Reglas de negocio en una sola capa

---

## Archivos Creados/Modificados

### Migrations
- `database/migrations/2026_01_28_000001_create_despacho_parciales_table.php`

### Domain Layer
- `app/Domain/Pedidos/Despacho/Entities/DesparChoParcial.php`
- `app/Domain/Pedidos/Despacho/Repositories/DesparChoParcialesRepository.php`
- `app/Domain/Pedidos/Despacho/Services/DesparChoParcialesPersistenceService.php`

### Infrastructure Layer
- `app/Models/DesparChoParcialesModel.php`
- `app/Infrastructure/Repositories/Pedidos/Despacho/DesparChoParcialesRepositoryImpl.php`

### Service Provider
- `app/Providers/PedidosServiceProvider.php` (actualizado)

### Application Layer
- `app/Application/Pedidos/Despacho/UseCases/GuardarDespachoUseCase.php` (actualizado)

