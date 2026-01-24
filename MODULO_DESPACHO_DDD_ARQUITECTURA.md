# 📦 MÓDULO DE DESPACHO - ARQUITECTURA DDD

##  Verificación de arquitectura DDD

Este módulo **CUMPLE 100% con Domain-Driven Design (DDD)** siguiendo la estructura del proyecto.

### Capas de la aplicación

```
┌─────────────────────────────────────────────────────┐
│        PRESENTATION LAYER                           │
│  (Http/Controllers/DespachoController)              │
│  - Solo coordina requests/responses                 │
│  - Delega al Application Layer                      │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│      APPLICATION LAYER                              │
│  (Application/Pedidos/UseCases)                     │
│  - ObtenerFilasDespachoUseCase                      │
│  - GuardarDespachoUseCase                           │
│  - Coordina Domain Services                         │
│  - Maneja transacciones                             │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│        DOMAIN LAYER                                 │
│  (Domain/Pedidos/Services)                          │
│  - DespachoGeneradorService                         │
│  - DespachoValidadorService                         │
│  - Lógica de negocio pura                           │
│  - Value Objects, Entities                          │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│    INFRASTRUCTURE LAYER                             │
│  (Models, Database)                                 │
│  - PedidoProduccion, PrendaPedido, etc.             │
│  - Persistencia de datos                            │
└─────────────────────────────────────────────────────┘
```

---

## 🏗️ Estructura de carpetas DDD

### Domain Layer
```
app/Domain/Pedidos/
├── Services/
│   ├── DespachoGeneradorService.php    ← Domain Service
│   └── DespachoValidadorService.php    ← Domain Service
├── Exceptions/
│   └── DespachoInvalidoException.php   ← Domain Exception
└── ... (ya existentes)
```

**Responsabilidades:**
- Lógica de negocio pura (sin frameworks)
- Validaciones de reglas de negocio
- Excepciones de dominio
- Services que coordinan entities/value objects

### Application Layer
```
app/Application/Pedidos/
├── UseCases/
│   ├── ObtenerFilasDespachoUseCase.php ← Use Case
│   └── GuardarDespachoUseCase.php      ← Use Case
└── DTOs/
    ├── FilaDespachoDTO.php             ← DTO
    ├── DespachoParcialesDTO.php        ← DTO
    └── ControlEntregasDTO.php          ← DTO
```

**Responsabilidades:**
- Casos de uso (use cases)
- Coordinar Domain Services
- Manejar transacciones
- DTOs para transferencia de datos

### Presentation Layer
```
app/Http/Controllers/
└── DespachoController.php              ← Controller

resources/views/despacho/
├── index.blade.php                     ← Vista
├── show.blade.php                      ← Vista
└── print.blade.php                     ← Vista
```

**Responsabilidades:**
- Recibir requests HTTP
- Delegar a UseCases
- Retornar responses

---

## 🔄 Flujo de datos (DDD)

### Request HTTP
```
Usuario: GET /despacho/{id}
    ↓
DespachoController::show($pedido)
    (PRESENTATION LAYER - Sin lógica de negocio)
    ↓
ObtenerFilasDespachoUseCase::obtenerTodas($pedidoId)
    (APPLICATION LAYER - Coordina)
    ↓
DespachoGeneradorService::generarFilasDespacho($pedido)
    (DOMAIN LAYER - Lógica pura)
    ↓
Models (PedidoProduccion, PrendaPedido, etc.)
    (INFRASTRUCTURE LAYER - Persistencia)
    ↓
FilaDespachoDTO[] (DTOs)
    ↓
show.blade.php (Vista)
    ↓
Usuario: HTML renderizado
```

---

## 📝 DTOs (Data Transfer Objects)

### FilaDespachoDTO
```php
new FilaDespachoDTO(
    tipo: 'prenda|epp',
    id: 1,
    tallaId: 5,           // null para EPP
    descripcion: '...',
    cantidadTotal: 50,
    talla: 'XL',          // '—' para EPP
    genero: 'Hombre',     // null para EPP
    objetoPrenda: [...],
    objetoTalla: [...],   // null para EPP
    objetoEpp: null       // null para prenda
)
```

**Beneficios:**
-  Desacoplamiento entre capas
-  Type-safe (atributos públicos con tipos)
-  Fácil de serializar a JSON
-  Evoluciona sin afectar la BD

### DespachoParcialesDTO
```php
new DespachoParcialesDTO(
    tipo: 'prenda|epp',
    id: 1,
    parcial1: 10,
    parcial2: 5,
    parcial3: 0
)
```

### ControlEntregasDTO
```php
new ControlEntregasDTO(
    pedidoId: 123,
    numeroPedido: 'PED-001',
    cliente: 'Empresa XYZ',
    fechaHora: now(),
    clienteEmpresa: '...',
    despachos: [DespachoParcialesDTO[], ...]
)
```

---

## Domain Services

### DespachoGeneradorService

**Responsabilidad:** Generar la estructura de despacho desde un pedido

```php
$service = new DespachoGeneradorService();

// Obtener todas las filas (prendas + EPP unificadas)
$filas = $service->generarFilasDespacho($pedido);
// → Collection<FilaDespachoDTO>

// Obtener solo prendas
$prendas = $service->generarPrendas($pedido);
// → Collection<FilaDespachoDTO>

// Obtener solo EPP
$epps = $service->generarEpp($pedido);
// → Collection<FilaDespachoDTO>
```

**Métodos privados:**
- `agregarPrendas()` - Procesar prendas con tallas
- `agregarEpp()` - Procesar EPP

### DespachoValidadorService

**Responsabilidad:** Validar y procesar despachos

```php
$service = new DespachoValidadorService();

// Validar un despacho individual
$service->validarDespacho($despacho);
// → Lanza DespachoInvalidoException si hay error

// Validar múltiples despachos
$service->validarMultiplesDespachos($despachos);

// Procesar (validar + log)
$service->procesarDespacho($despacho, $clienteEmpresa);

// Calcular pendiente automático
$p3 = $service->calcularPendiente(50, 10, 5, 0);  // 35
```

**Validaciones:**
-  No permite parciales negativos
-  No permite exceder cantidad total
-  Verifica que el ítem existe

---

##  Use Cases (Application Services)

### ObtenerFilasDespachoUseCase

**Entrada:** `int|string $pedidoId`

**Salida:** `Collection<FilaDespachoDTO>`

**Proceso:**
1. Obtener pedido con relaciones
2. Delegar a Domain Service
3. Retornar DTOs

```php
$useCase = app(ObtenerFilasDespachoUseCase::class);
$filas = $useCase->obtenerTodas($pedidoId);
```

### GuardarDespachoUseCase

**Entrada:** `ControlEntregasDTO $control`

**Salida:** `array` (success/error)

**Proceso:**
1. Validar pedido existe
2. Convertir entrada a DTOs internos
3. Validar con Domain Service
4. Procesar cada despacho
5. Registrar en logs
6. Retornar resultado

```php
$useCase = app(GuardarDespachoUseCase::class);
$resultado = $useCase->ejecutar($controlDTO);
// → ['success' => true, 'message' => '...']
```

---

## 🎮 Controller (Presentation Layer)

```php
class DespachoController extends Controller
{
    public function __construct(
        private ObtenerFilasDespachoUseCase $obtenerFilasUseCase,
        private GuardarDespachoUseCase $guardarDespachoUseCase,
    ) {}

    public function show(PedidoProduccion $pedido)
    {
        // 1. Usar el UseCase
        $filas = $this->obtenerFilasUseCase->obtenerTodas($pedido->id);

        // 2. Retornar vista
        return view('despacho.show', ['filas' => $filas]);
    }

    public function guardarDespacho(Request $request, PedidoProduccion $pedido)
    {
        // 1. Validar entrada
        $validated = $request->validate([...]);

        // 2. Crear DTO
        $control = new ControlEntregasDTO(...$validated);

        // 3. Usar el UseCase
        $resultado = $this->guardarDespachoUseCase->ejecutar($control);

        // 4. Retornar respuesta
        return response()->json($resultado);
    }
}
```

**Características:**
-  No contiene lógica de negocio
-  Inyecta dependencias (UseCases)
-  Delega responsabilidades
-  Maneja HTTP concerns (validation, responses)

---

## 🔌 Inyección de dependencias

Las vistas esperan Collection de DTOs, no arrays:

```blade
@foreach($filas as $fila)
    {{ $fila->tipo }}          ← Atributo público DTO
    {{ $fila->descripcion }}   ← Type-safe
@endforeach
```

---

## 📊 Ventajas de esta arquitectura

| Aspecto | Beneficio |
|--------|----------|
| **Testabilidad** |  Domain Services sin dependencias de Framework |
| **Mantenibilidad** |  Código organizado en capas |
| **Escalabilidad** |  Fácil agregar nuevos UseCases |
| **Reutilización** |  Domain Services reutilizables |
| **Separación de intereses** |  Cada capa con responsabilidad clara |
| **Evolución** |  Cambios en BD sin afectar Application Layer |

---

## 🧪 Testing

### Test Domain Service (sin frameworks)
```php
public function test_generar_filas_despacho_con_prendas()
{
    $service = new DespachoGeneradorService();
    $pedido = $this->crearPedidoConPrendas();
    
    $filas = $service->generarFilasDespacho($pedido);
    
    $this->assertCount(2, $filas);
    $this->assertEquals('prenda', $filas[0]->tipo);
}
```

### Test Application Service
```php
public function test_guardar_despacho_valida_antes_de_guardar()
{
    $useCase = app(GuardarDespachoUseCase::class);
    $control = new ControlEntregasDTO(
        pedidoId: 1,
        numeroPedido: 'PED-001',
        cliente: 'Test',
        despachos: [[
            'tipo' => 'prenda',
            'id' => 1,
            'parcial_1' => 999,  // Excede cantidad
            'parcial_2' => 0,
            'parcial_3' => 0,
        ]],
    );
    
    $this->expectException(\Exception::class);
    $useCase->ejecutar($control);
}
```

---

## 📚 Comparativa: Antes vs Después

### ANTES (No DDD)
```php
// En el Controller
$pedido = PedidoProduccion::find($id);
$filas = $pedido->getFilasDespacho();  // ← Lógica en el Model
return view('despacho.show', ['filas' => $filas]);

// Problemas:
// ❌ Model tiene múltiples responsabilidades
// ❌ No hay abstracción de capas
// ❌ Difícil reutilizar lógica
// ❌ Acoplado a Eloquent
```

### DESPUÉS (DDD)
```php
// En el Controller
$filas = $this->obtenerFilasUseCase->obtenerTodas($pedido->id);
return view('despacho.show', ['filas' => $filas]);

// Beneficios:
//  Controller no tiene lógica
//  UseCase reutilizable
//  Domain Service testeable
//  DTOs desacoplados
//  Separa responsabilidades
```

---

## 🎓 Estructura de archivos (Resumen)

```
app/
├── Domain/Pedidos/
│   └── Services/
│       ├── DespachoGeneradorService.php       (Generar filas)
│       └── DespachoValidadorService.php       (Validar despachos)
│   └── Exceptions/
│       └── DespachoInvalidoException.php
│
├── Application/Pedidos/
│   ├── UseCases/
│   │   ├── ObtenerFilasDespachoUseCase.php    (Use case)
│   │   └── GuardarDespachoUseCase.php         (Use case)
│   └── DTOs/
│       ├── FilaDespachoDTO.php                (DTO)
│       ├── DespachoParcialesDTO.php           (DTO)
│       └── ControlEntregasDTO.php             (DTO)
│
├── Http/Controllers/
│   └── DespachoController.php                 (Presentation)
│
└── Models/
    └── PedidoProduccion.php                   (Infrastructure)

resources/views/despacho/
├── index.blade.php                            (Presentation)
├── show.blade.php                             (Presentation)
└── print.blade.php                            (Presentation)
```

---

## ✨ Conclusión

El módulo de Despacho está **100% alineado con DDD** y la arquitectura del proyecto:

 Domain Layer: Services de negocio puro  
 Application Layer: UseCases coordinadores  
 Presentation Layer: Controller sin lógica  
 DTOs: Transferencia de datos desacoplada  
 Exceptions: Domain exceptions  
 Dependency Injection: Inyección clara  
 Separation of Concerns: Responsabilidades claras  

**Pronto para producción** ✨
