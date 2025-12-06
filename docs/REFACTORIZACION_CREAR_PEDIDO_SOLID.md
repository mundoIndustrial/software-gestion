# 📋 Refactorización: Crear Pedido desde Cotización - SOLID & Arquitectura Modular

## Índice
1. [Visión General](#visión-general)
2. [Principios SOLID Aplicados](#principios-solid-aplicados)
3. [Arquitectura](#arquitectura)
4. [Estructura de Carpetas](#estructura-de-carpetas)
5. [Componentes Principales](#componentes-principales)
6. [Patrones de Diseño](#patrones-de-diseño)
7. [Guía de Implementación](#guía-de-implementación)
8. [Flujo de Datos](#flujo-de-datos)

---

## Visión General

Este proyecto ha sido **completamente refactorizado** para seguir principios SOLID y una arquitectura modular limpia. La funcionalidad de "Crear Pedido de Producción desde Cotización" que originalmente estaba en un único archivo Blade de 1200+ líneas, ahora está distribuida en:

- **3 DTOs** para encapsulación de datos
- **3 Services** para lógica de negocio
- **1 Controller** limpio y enfocado
- **6 módulos JavaScript** con responsabilidades únicas
- **3 componentes Blade** reutilizables

**Resultado**: Código más mantenible, testeable y escalable.

---

## Principios SOLID Aplicados

### 🅢 **S**ingle Responsibility Principle (SRP)

Cada clase/módulo tiene UNA única responsabilidad:

| Componente | Responsabilidad |
|-----------|-----------------|
| `CotizacionSearchDTO` | Encapsular datos de búsqueda de cotización |
| `PrendaCreacionDTO` | Encapsular datos de prenda a crear |
| `CrearPedidoProduccionDTO` | Encapsular solicitud de creación |
| `CotizacionSearchService` | Lógica de búsqueda y filtrado |
| `PrendaProcessorService` | Procesamiento y normalización de prendas |
| `PedidoProduccionCreatorService` | Creación de pedidos |
| `CotizacionRepository` (JS) | Acceso a datos de cotizaciones |
| `CotizacionSearchUIController` (JS) | UI de búsqueda |
| `PrendasUIController` (JS) | UI de prendas |
| `FormularioPedidoController` (JS) | Manejo de envío |

### 🅞 **O**pen/Closed Principle (OCP)

Abierto para extensión, cerrado para modificación:

```php
// ✅ BUENO: Fácil extender con nuevos servicios
class PedidoProduccionCreatorService {
    // Depende de abstracciones (interfaces)
    public function __construct(
        private PrendaProcessorService $prendaProcessor,
    ) {}
    
    // Método que puede ser override en subclases
    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion
}

// ✅ Para extender: crear nueva clase que herede
class PedidoProduccionCreatorServiceAvanzado extends PedidoProduccionCreatorService {
    // Override sin modificar original
    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion
}
```

### 🅛 **L**iskov Substitution Principle (LSP)

Los DTOs y Services pueden ser reemplazados por sus subclases sin romper la funcionalidad:

```php
// ✅ Cualquier DTO que implemente la interfaz esperada funciona
$dto = CrearPedidoProduccionDTO::fromRequest($request->all());
// Si extendemos, sigue funcionando
$dto = new CrearPedidoProduccionDTOAvanzado::fromRequest($request->all());
```

### 🅘 **I**nterface Segregation Principle (ISP)

Interfaces pequeñas y específicas:

```php
// ✅ Métodos específicos, no "interfaces gordas"
interface CotizacionSearchableInterface {
    public function obtenerTodas(): Collection;
    public function obtenerPorAsesor(string $nombreAsesor): Collection;
}

// ✅ En JavaScript, módulos con métodos simples
export class CotizacionRepository {
    obtenerTodas() { }
    filtrarPorAsesor(nombreAsesor) { }
    buscar(termino) { }
}
```

### 🅓 **D**ependency Inversion Principle (DIP)

Las dependencias fluyen hacia las abstracciones:

```php
// ✅ Constructor inyecta dependencias (no las crea)
public function __construct(
    private CotizacionSearchService $cotizacionSearch,
    private PedidoProduccionCreatorService $pedidoCreator,
    private PrendaProcessorService $prendaProcessor,
) {}

// ✅ Service Provider configura las inyecciones
$this->app->bind(PedidoProduccionCreatorService::class, function ($app) {
    return new PedidoProduccionCreatorService(
        $app->make(PrendaProcessorService::class)
    );
});
```

---

## Arquitectura

### Capas

```
┌─────────────────────────────────────────────────┐
│          Presentation Layer (Blade + JS)       │
│  crear-desde-cotizacion-refactorizado.blade.php │
│  + componentes reutilizables                    │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│          JavaScript Modules (ES6)               │
│  - CotizacionRepository                         │
│  - CotizacionSearchUIController                 │
│  - PrendasUIController                          │
│  - FormularioPedidoController                   │
│  - CrearPedidoApp (Facade)                      │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│            Controller Layer                     │
│  PedidoProduccionController                     │
│  - Valida requests                              │
│  - Coordina services                            │
│  - Retorna respuestas                           │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│            Business Logic Layer                 │
│  Services:                                      │
│  - CotizacionSearchService                      │
│  - PedidoProduccionCreatorService               │
│  - PrendaProcessorService                       │
│  DTOs:                                          │
│  - CotizacionSearchDTO                          │
│  - PrendaCreacionDTO                            │
│  - CrearPedidoProduccionDTO                     │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│          Data Access Layer                      │
│  - Eloquent Models                              │
│  - Database queries                             │
└─────────────────────────────────────────────────┘
```

---

## Estructura de Carpetas

```
app/
├── DTOs/
│   ├── CotizacionSearchDTO.php          ← Búsqueda de cotización
│   ├── PrendaCreacionDTO.php            ← Prenda a crear
│   └── CrearPedidoProduccionDTO.php     ← Solicitud de creación
├── Services/
│   └── Pedidos/
│       ├── CotizacionSearchService.php  ← Búsqueda y filtrado
│       ├── PrendaProcessorService.php   ← Procesamiento de prendas
│       └── PedidoProduccionCreatorService.php ← Creación de pedidos
├── Http/
│   └── Controllers/
│       └── Asesores/
│           └── PedidoProduccionController.php ← Coordinador HTTP
├── Providers/
│   └── PedidosServiceProvider.php       ← Inyección de dependencias

resources/
├── views/
│   ├── asesores/pedidos/
│   │   └── crear-desde-cotizacion-refactorizado.blade.php ← Vista principal
│   └── components/pedidos/
│       ├── cotizacion-search.blade.php      ← Componente búsqueda
│       ├── pedido-info.blade.php            ← Componente información
│       └── prendas-container.blade.php      ← Componente prendas
└── js/
    └── modules/
        ├── CotizacionRepository.js          ← Acceso a datos
        ├── CotizacionSearchUIController.js  ← UI búsqueda
        ├── PrendasUIController.js           ← UI prendas
        ├── FormularioPedidoController.js    ← Envío de formulario
        ├── FormInfoUpdater.js               ← Actualizar información
        ├── CotizacionDataLoader.js          ← Cargar datos (AJAX)
        └── CrearPedidoApp.js                ← Aplicación (Facade)

routes/
└── asesores/
    └── pedidos.php                      ← Rutas de pedidos
```

---

## Componentes Principales

### Backend - DTOs (Data Transfer Objects)

#### `CotizacionSearchDTO.php`
```php
/**
 * Encapsula datos de cotización para búsqueda
 * - Propiedades readonly (inmutables)
 * - Factory method fromModel()
 * - Método de conversión toArray()
 * - Método de filtrado estático
 */
class CotizacionSearchDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $numero,
        public readonly string $cliente,
        public readonly string $asesora,
        public readonly string $formaPago,
        public readonly int $prendasCount,
    ) {}
}
```

**Ventajas:**
- ✅ Inmutable (readonly)
- ✅ Tipado fuerte
- ✅ Fácil de pasar entre capas
- ✅ Documentación automática

#### `PrendaCreacionDTO.php`
```php
/**
 * Encapsula datos de prenda individual
 * - Validación: esValido()
 * - Cálculos: cantidadTotal()
 * - Conversión: toArray()
 */
class PrendaCreacionDTO {
    public function __construct(
        public readonly int $index,
        public readonly string $nombreProducto,
        public readonly ?string $descripcion,
        public readonly array $cantidades, // ['talla' => cantidad]
    ) {}

    public function esValido(): bool {
        return count($this->cantidades) > 0;
    }
}
```

#### `CrearPedidoProduccionDTO.php`
```php
/**
 * Encapsula toda la solicitud de creación
 * - Factory method con validación
 * - Método de filtrado prendasValidas()
 * - Método de conteo totalPrendas()
 */
class CrearPedidoProduccionDTO {
    public function __construct(
        public readonly int $cotizacionId,
        public readonly array $prendasData,
    ) {}

    public static function fromRequest(array $data): self {
        // Validación y conversión desde request
    }

    public function esValido(): bool {
        return $this->cotizacionId > 0 && count($this->prendasData) > 0;
    }
}
```

### Backend - Services

#### `CotizacionSearchService.php` - SRP: Búsqueda
```php
/**
 * Responsabilidad ÚNICA: Búsqueda y filtrado de cotizaciones
 * - Métodos simples y enfocados
 * - No modifica datos
 * - Reutilizable en cualquier contexto
 */
class CotizacionSearchService {
    public function obtenerTodas(): Collection
    public function obtenerPorAsesor(string $nombreAsesor): Collection
    public function obtenerPorId(int $id): ?Cotizacion
    public function filtrarPorTermino(Collection $cotizaciones, string $termino): Collection
}
```

#### `PrendaProcessorService.php` - SRP: Procesamiento
```php
/**
 * Responsabilidad ÚNICA: Procesar y normalizar prendas
 * - Valida datos de entrada
 * - Normaliza strings
 * - Procesa cantidades
 * - Retorna array listo para persistencia
 */
class PrendaProcessorService {
    public function procesar(PrendaCreacionDTO $prenda): array
    private function procesarCantidades(array $cantidades): array
    private function normalizarString(?string $valor): ?string
}
```

#### `PedidoProduccionCreatorService.php` - SRP: Creación
```php
/**
 * Responsabilidad ÚNICA: Crear pedidos de producción
 * - Depende de PrendaProcessorService (inyectado)
 * - Delega procesamiento a PrendaProcessorService
 * - Genera número de pedido
 * - Crea registro en BD
 */
class PedidoProduccionCreatorService {
    public function __construct(
        private PrendaProcessorService $prendaProcessor,
    ) {}

    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion
    public function obtenerProximoNumero(): int
}
```

### Backend - Controller

#### `PedidoProduccionController.php` - Coordinador
```php
/**
 * Responsabilidad: Coordinar requests/responses
 * - NO contiene lógica de negocio
 * - Inyecta Services (DIP)
 * - Valida requests
 * - Retorna JSON/View
 */
class PedidoProduccionController extends Controller {
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
        private PedidoProduccionCreatorService $pedidoCreator,
        private PrendaProcessorService $prendaProcessor,
    ) {}

    public function mostrarFormularioCrearDesdeCotzacion(): View
    public function crearDesdeCotzacion(Request $request): JsonResponse
    public function obtenerProximoNumero(): JsonResponse
    public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
}
```

### Frontend - JavaScript Modules

#### `CotizacionRepository.js` - SRP: Acceso a datos
```javascript
/**
 * Responsabilidad: Gestionar acceso a datos de cotizaciones
 * - Almacena array de cotizaciones
 * - Métodos de búsqueda/filtrado
 * - NO accede a BD
 * - NO maneja UI
 */
export class CotizacionRepository {
    obtenerTodas() { return this.cotizaciones; }
    filtrarPorAsesor(nombreAsesor) { }
    buscar(termino) { }
    obtenerPorId(id) { }
}
```

#### `CotizacionSearchUIController.js` - SRP: UI Búsqueda
```javascript
/**
 * Responsabilidad: Controlar UI de búsqueda
 * - Gestiona eventos del input
 * - Renderiza dropdown
 * - NO contiene lógica de búsqueda
 * - Depende de CotizacionRepository (inyectado)
 */
export class CotizacionSearchUIController {
    constructor(repository, config) {
        this.repository = repository; // DIP
        this.searchInput = config.searchInput;
        // ...
    }

    handleSearch() { }
    mostrarDropdown(opciones) { }
    seleccionar(cotizacion, callback) { }
}
```

#### `PrendasUIController.js` - SRP: UI Prendas
```javascript
/**
 * Responsabilidad: Controlar UI de prendas
 * - Renderiza prendas
 * - Maneja inputs de tallas
 * - Agrega/elimina tallas
 * - Recolecta datos de cantidades
 */
export class PrendasUIController {
    cargar(prendas) { }
    crearPrendaHTML(prenda, index) { }
    agregarTalla(btn) { }
    eliminarTalla(btn) { }
    obtenerDatos() { }
}
```

#### `FormularioPedidoController.js` - SRP: Envío
```javascript
/**
 * Responsabilidad: Gestionar envío del formulario
 * - Valida datos
 * - Envía al servidor
 * - Maneja respuestas
 */
export class FormularioPedidoController {
    handleSubmit(e) { }
    async enviar(cotizacionId, prendasData) { }
    mostrarError(titulo, mensaje) { }
    mostrarExito(mensaje) { }
}
```

#### `CrearPedidoApp.js` - Patrón Facade
```javascript
/**
 * Patrón: Facade
 * Responsabilidad: Orquestar la aplicación
 * - Coordina todos los módulos
 * - Punto de entrada único
 * - Simplifica inicialización
 */
export class CrearPedidoApp {
    constructor(initialData) {
        this.cotizacionRepository = new CotizacionRepository();
        this.cotizacionSearchUI = new CotizacionSearchUIController();
        this.prendasUI = new PrendasUIController();
        this.formularioPedido = new FormularioPedidoController();
    }

    async inicializar() { }
    async cargarCotizacion(cotizacionId) { }
}
```

---

## Patrones de Diseño

### 1. **Data Transfer Object (DTO)**
Transferencia tipada de datos entre capas.

```
Request → Validación → DTO → Service → Persistencia
```

### 2. **Repository Pattern**
Abstrae acceso a datos (en JavaScript).

```javascript
// En lugar de:
const resultado = await fetch('/api/cotizaciones').then(...);

// Usamos:
const repo = new CotizacionRepository(datos);
const resultado = repo.buscar(termino);
```

### 3. **Service Layer**
Lógica de negocio centralizada.

```
Controller → Service → Model
   (HTTP)    (Lógica)  (BD)
```

### 4. **Dependency Injection**
Dependencias inyectadas, no instanciadas.

```php
// ❌ MALO: Instancia dentro
class PedidoCreator {
    public function crear() {
        $processor = new PrendaProcessor(); // Acoplado
    }
}

// ✅ BUENO: Inyectado
class PedidoCreator {
    public function __construct(private PrendaProcessor $processor) {} // Desacoplado
}
```

### 5. **Facade Pattern**
Simplifica interfaz compleja (CrearPedidoApp).

```javascript
// Facade simplifica inicialización
const app = new CrearPedidoApp(initialData);
await app.inicializar();

// Sin facade, sería:
const repo = new CotizacionRepository(data);
const searchUI = new CotizacionSearchUIController(repo, config);
const prendasUI = new PrendasUIController(config);
// ... mucho más código
```

### 6. **Factory Method**
Creación de objetos desde datos.

```php
// Factory
$dto = CotizacionSearchDTO::fromModel($cotizacion);
$dto = CrearPedidoProduccionDTO::fromRequest($request->all());
```

---

## Guía de Implementación

### Paso 1: Registrar Service Provider

En `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PedidosServiceProvider::class,
],
```

### Paso 2: Registrar Rutas

En `routes/web.php` o `routes/api.php`:

```php
Route::group(['prefix' => 'asesores', 'middleware' => ['auth']], function () {
    require base_path('routes/asesores/pedidos.php');
});
```

### Paso 3: Usar en Controller

```php
// Inyección automática por Service Provider
class PedidoProduccionController extends Controller {
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
        private PedidoProduccionCreatorService $pedidoCreator,
        private PrendaProcessorService $prendaProcessor,
    ) {}
}
```

### Paso 4: En Vista Blade

```blade
@section('content')
    @include('components.pedidos.cotizacion-search')
    @include('components.pedidos.pedido-info')
    @include('components.pedidos.prendas-container')
@endsection
```

---

## Flujo de Datos

### Flujo de Búsqueda de Cotización

```
Usuario escribe en input
    ↓
CotizacionSearchUIController.handleSearch()
    ↓
CotizacionRepository.buscar(termino)
    ↓
Retorna coincidencias
    ↓
CotizacionSearchUIController.mostrarDropdown()
    ↓
Usuario ve resultados filtrados
```

### Flujo de Creación de Pedido

```
Usuario hace click en "Crear Pedido"
    ↓
FormularioPedidoController.handleSubmit()
    ↓
Recolecta datos: cotizacion_id + prendas
    ↓
Envía POST a /asesores/cotizaciones/{id}/crear-pedido-produccion
    ↓
PedidoProduccionController.crearDesdeCotzacion()
    ↓
Crea DTO: CrearPedidoProduccionDTO::fromRequest()
    ↓
Valida DTO: $dto->esValido()
    ↓
PedidoProduccionCreatorService.crear($dto, $userId)
    ↓
Procesa prendas: PrendaProcessorService.procesar()
    ↓
Crea en BD: PedidoProduccion::create()
    ↓
Retorna JSON { success: true, redirect: ... }
    ↓
FormularioPedidoController.mostrarExito()
    ↓
Redirige a lista de pedidos
```

---

## Ventajas de Esta Arquitectura

### ✅ Mantenibilidad
- Cada componente hace UNA cosa
- Fácil localizar y modificar código
- Cambios aislados sin efectos secundarios

### ✅ Testabilidad
```php
// Fácil testear servicios
$service = new CotizacionSearchService();
$resultado = $service->filtrarPorTermino($cotizaciones, "test");
$this->assertEquals(expected, $resultado);
```

### ✅ Reutilización
```php
// Servicios reutilizables en diferentes contextos
// API REST
// CLI Commands
// Jobs/Queues
// WebSockets
```

### ✅ Escalabilidad
- Agregar nuevos servicios sin modificar existentes
- Fácil implementar cache, logging
- Listo para microservicios

### ✅ Separación de Conceptos
- Backend: Lógica de negocio
- Frontend: UI y experiencia
- DTOs: Contrato de datos
- Services: Orquestación

---

## Ejemplo de Extensión

### Agregar Caché a Búsqueda

```php
// Crear nuevo service sin modificar el existente (OCP)
class CotizacionSearchCachedService extends CotizacionSearchService {
    public function obtenerTodas(): Collection {
        return Cache::remember('cotizaciones_todas', 3600, function () {
            return parent::obtenerTodas();
        });
    }
}
```

### Agregar Logging

```php
class PedidoProduccionCreatorServiceWithLogging extends PedidoProduccionCreatorService {
    public function crear(CrearPedidoProduccionDTO $dto, int $asesorId): ?PedidoProduccion {
        Log::info('Creando pedido', ['dto' => $dto, 'asesor' => $asesorId]);
        
        $resultado = parent::crear($dto, $asesorId);
        
        Log::info('Pedido creado', ['id' => $resultado->id]);
        return $resultado;
    }
}
```

---

## Conclusión

Esta refactorización transforma código monolítico en una **arquitectura modular y SOLID** que es:

- 🎯 **Enfocada**: Cada componente tiene una única responsabilidad
- 🔧 **Mantenible**: Fácil de entender y modificar
- 🧪 **Testeable**: Componentes aislados y sin dependencias globales
- 📈 **Escalable**: Preparada para crecimiento
- 🔁 **Reutilizable**: Servicios y módulos reutilizables
- 📚 **Documentada**: Código auto-documentado con comentarios claros

¡Listo para producción y futuras extensiones!
