# 🏛️ ARQUITECTURA DDD - MÓDULO DE COTIZACIONES

## 📋 TABLA DE CONTENIDOS

1. [Visión General](#visión-general)
2. [Estructura de Carpetas](#estructura-de-carpetas)
3. [Componentes Principales](#componentes-principales)
4. [Flujo de Datos](#flujo-de-datos)
5. [Cómo Usar](#cómo-usar)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Visión General

El módulo de Cotizaciones ha sido refactorizado siguiendo **Domain-Driven Design (DDD)** con **CQRS** (Command Query Responsibility Segregation), **SOLID** y patrones avanzados.

### Beneficios

✅ **Separación de responsabilidades** - Cada clase tiene una única responsabilidad
✅ **Testabilidad** - 42 tests con 94 assertions
✅ **Escalabilidad** - Fácil agregar nuevas funcionalidades
✅ **Mantenibilidad** - Código limpio y autodocumentado
✅ **Reutilización** - Componentes reutilizables

---

## 📁 Estructura de Carpetas

```
app/
├── Domain/Cotizacion/                    # CAPA DE DOMINIO
│   ├── Entities/
│   │   ├── Cotizacion.php               # Aggregate Root
│   │   ├── PrendaCotizacion.php         # Entity
│   │   └── LogoCotizacion.php           # Entity
│   ├── ValueObjects/
│   │   ├── EstadoCotizacion.php         # Enum con lógica
│   │   ├── TipoCotizacion.php           # Enum
│   │   ├── Cliente.php                  # VO
│   │   ├── Asesora.php                  # VO
│   │   ├── CotizacionId.php             # VO
│   │   ├── NumeroCotizacion.php         # VO
│   │   └── RutaImagen.php               # VO
│   ├── Repositories/
│   │   └── CotizacionRepositoryInterface.php
│   ├── Specifications/
│   │   ├── PuedeSerEliminadaSpecification.php
│   │   └── EsPropietarioSpecification.php
│   ├── Events/
│   │   └── CotizacionAceptada.php       # Domain Event
│   └── Exceptions/
│       └── CotizacionNoAutorizadaException.php
│
├── Application/Cotizacion/               # CAPA DE APLICACIÓN
│   ├── Commands/
│   │   ├── CrearCotizacionCommand.php
│   │   ├── EliminarCotizacionCommand.php
│   │   ├── CambiarEstadoCotizacionCommand.php
│   │   └── AceptarCotizacionCommand.php
│   ├── Queries/
│   │   ├── ObtenerCotizacionQuery.php
│   │   └── ListarCotizacionesQuery.php
│   ├── Handlers/
│   │   ├── Commands/
│   │   │   ├── CrearCotizacionHandler.php
│   │   │   ├── EliminarCotizacionHandler.php
│   │   │   ├── CambiarEstadoCotizacionHandler.php
│   │   │   └── AceptarCotizacionHandler.php
│   │   └── Queries/
│   │       ├── ObtenerCotizacionHandler.php
│   │       └── ListarCotizacionesHandler.php
│   └── DTOs/
│       ├── CrearCotizacionDTO.php
│       └── CotizacionDTO.php
│
├── Infrastructure/                       # CAPA DE INFRAESTRUCTURA
│   ├── Persistence/Eloquent/Repositories/
│   │   └── EloquentCotizacionRepository.php
│   ├── Providers/
│   │   └── CotizacionServiceProvider.php
│   └── Http/Controllers/
│       └── CotizacionController.php     # Controller SLIM (186 líneas)
│
└── Shared/
    └── ValueObjects/
        └── UserId.php                   # VO compartido
```

---

## 🔧 Componentes Principales

### 1. Value Objects (Dominio)

Objetos inmutables que representan conceptos del dominio:

```php
// EstadoCotizacion - Enum con lógica
$estado = EstadoCotizacion::BORRADOR;
$estado->label();                    // "Borrador"
$estado->puedeTransicionarA(EstadoCotizacion::ENVIADA_CONTADOR); // true

// Cliente - VO con validación
$cliente = Cliente::crear('Acme Corp');
$cliente->valor();                   // "Acme Corp"
$cliente->equals($otro);             // true/false

// NumeroCotizacion - VO con formato
$numero = NumeroCotizacion::generar(14);
$numero->valor();                    // "COT-00014"
```

### 2. Entities (Dominio)

Objetos con identidad que contienen lógica de negocio:

```php
// Cotizacion - Aggregate Root
$cotizacion = Cotizacion::crearBorrador($usuarioId, $tipo, $cliente, $asesora);
$cotizacion->agregarPrenda($prenda);
$cotizacion->cambiarEstado(EstadoCotizacion::ENVIADA_CONTADOR);
$cotizacion->aceptar();              // Dispara Domain Event
```

### 3. Commands (Aplicación)

Objetos que representan acciones:

```php
// Crear cotización
$comando = CrearCotizacionCommand::crear($dto);
$handler = app(CrearCotizacionHandler::class);
$cotizacion = $handler->handle($comando);

// Cambiar estado
$comando = CambiarEstadoCotizacionCommand::crear($id, 'ENVIADA_CONTADOR', $usuarioId);
$handler = app(CambiarEstadoCotizacionHandler::class);
$cotizacion = $handler->handle($comando);
```

### 4. Queries (Aplicación)

Objetos que representan consultas:

```php
// Obtener cotización
$query = ObtenerCotizacionQuery::crear($cotizacionId, $usuarioId);
$handler = app(ObtenerCotizacionHandler::class);
$cotizacion = $handler->handle($query);

// Listar cotizaciones
$query = ListarCotizacionesQuery::crear($usuarioId, $soloBorradores = true);
$handler = app(ListarCotizacionesHandler::class);
$cotizaciones = $handler->handle($query);
```

### 5. Specifications (Dominio)

Reglas de negocio reutilizables:

```php
// Verificar si puede ser eliminada
$spec = new PuedeSerEliminadaSpecification();
$spec->isSatisfiedBy($cotizacion);   // true/false
$spec->throwIfNotSatisfied($cotizacion); // Lanza excepción si no cumple

// Verificar propiedad
$spec = new EsPropietarioSpecification($usuarioId);
$spec->isSatisfiedBy($cotizacion);   // true/false
```

---

## 🔄 Flujo de Datos

### Crear Cotización

```
HTTP POST /asesores/cotizaciones
    ↓
CotizacionController::store()
    ↓
CrearCotizacionCommand::crear()
    ↓
CrearCotizacionHandler::handle()
    ├─ Validar datos (DTO)
    ├─ Crear Value Objects
    ├─ Crear Aggregate Root
    ├─ Guardar en BD (Repository)
    └─ Retornar DTO
    ↓
HTTP 201 JSON Response
```

### Cambiar Estado

```
HTTP PATCH /asesores/cotizaciones/{id}/estado/{estado}
    ↓
CotizacionController::cambiarEstado()
    ↓
CambiarEstadoCotizacionCommand::crear()
    ↓
CambiarEstadoCotizacionHandler::handle()
    ├─ Obtener cotización (Repository)
    ├─ Verificar propiedad (Specification)
    ├─ Cambiar estado (Aggregate Root)
    ├─ Guardar en BD (Repository)
    └─ Retornar DTO
    ↓
HTTP 200 JSON Response
```

---

## 💻 Cómo Usar

### Desde el Controller

```php
// Inyección de dependencias automática
public function __construct(
    private readonly CrearCotizacionHandler $crearHandler,
    private readonly ObtenerCotizacionHandler $obtenerHandler,
    // ...
) {}

// Usar en método
public function store(Request $request): JsonResponse
{
    $dto = CrearCotizacionDTO::desdeArray($request->all());
    $comando = CrearCotizacionCommand::crear($dto);
    $cotizacion = $this->crearHandler->handle($comando);
    
    return response()->json(['success' => true, 'data' => $cotizacion->toArray()]);
}
```

### Desde un Servicio

```php
// Inyectar handlers
public function __construct(
    private readonly CrearCotizacionHandler $handler
) {}

// Usar
public function crearCotizacion(array $datos)
{
    $dto = CrearCotizacionDTO::desdeArray($datos);
    $comando = CrearCotizacionCommand::crear($dto);
    return $this->handler->handle($comando);
}
```

### Desde un Job/Queue

```php
public function handle()
{
    $handler = app(CrearCotizacionHandler::class);
    $comando = CrearCotizacionCommand::crear($dto);
    $cotizacion = $handler->handle($comando);
}
```

---

## 🧪 Testing

### Tests Unitarios

```bash
php artisan test tests/Unit/Domain/Cotizacion/
```

Cubre:
- Value Objects (validación, comparación)
- Entities (lógica de dominio)
- Specifications (reglas de negocio)

### Tests E2E

```bash
php artisan test tests/Feature/Cotizacion/CotizacionE2ETest.php
```

Cubre:
- Flujo completo CRUD
- Autorización
- Transiciones de estado
- Validaciones

---

## 🐛 Troubleshooting

### Error: "Route [asesores.cotizaciones.guardar] not defined"

**Causa:** Caché de rutas desactualizado

**Solución:**
```bash
php artisan route:clear
php artisan cache:clear
```

### Error: "Class not found: EliminarCotizacionHandler"

**Causa:** Service Provider no registrado

**Solución:** Verificar que `CotizacionServiceProvider` está en `bootstrap/app.php`

### Error: "No se puede transicionar de BORRADOR a ACEPTADA"

**Causa:** Transición de estado inválida

**Solución:** Seguir el flujo correcto:
```
BORRADOR → ENVIADA_CONTADOR → APROBADA_CONTADOR 
→ ENVIADA_APROBADOR → APROBADA_APROBADOR → ACEPTADA
```

---

## 📚 Referencias

- [Domain-Driven Design - Eric Evans](https://www.domainlanguage.com/ddd/)
- [CQRS - Greg Young](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Laravel DDD](https://laravel.com/docs)

---

**Última actualización:** 10 de Diciembre de 2025
**Versión:** 1.0
**Estado:** ✅ Producción
