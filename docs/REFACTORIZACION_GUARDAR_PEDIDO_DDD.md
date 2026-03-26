# 🏗️ Refactorización GuardarPedidoUseCase - DDD & Clean Architecture

##  RESUMEN EJECUTIVO

**Problema Original:**
El UseCase estaba acoplado a Laravel `Request`, mezclaba responsabilidades y no seguía DDD.

**Solución:**
Refactorización completa siguiendo Clean Architecture y DDD con:
- Eliminación de dependencia a Request HTTP
- Inyección de dependencias mediante DTOs
- Value Objects para lógica de dominio
- Separación clara de capas
- Transacciones ACID correctas

---

## 🔴 VIOLACIONES ENCONTRADAS EN EL CÓDIGO ORIGINAL

### 1. **Request HTTP en UseCase (Mayor violación)**
```php
// ❌ ANTES - UseCase acoplado a HTTP
public function ejecutar(array $validated, $request, string $productosKey)
{
    $tipoCotizacion = $request->input('tipo_cotizacion');  // ← Acoplado a Laravel
    $cotizacionId = $request->input('cotizacion_id');      // ← Viola Clean Arch
}
```

**Problema:**
- Application capa NO debe conocer sobre HTTP
- Imposible testear sin Request real
- Acoplamiento fuerte a framework

**Solución:**
```php
//  DESPUÉS - Solo DTOs
public function ejecutar(GuardarPedidoInputDTO $input): GuardarPedidoOutputDTO
{
    // $input contiene todos los datos necesarios
    if ($input->tipoPedido->esLogo()) { ... }
}
```

---

### 2. **Lógica de Decisión Fuera del Domain**
```php
// ❌ ANTES - Lógica en Application
if ($this->guardarPedidoLogoService->esLogoPedido($tipoCotizacion, $cotizacionId))
```

**Problema:**
- El "qué es un pedido de logo" es regla de negocio
- Debería estar en Domain, no en Service
- Difícil de reutilizar en otros contextos

**Solución:**
```php
//  DESPUÉS - Lógica en Value Object del Domain
$tipoPedido = TipoPedido::fromCotizacion(
    $request->input('tipo_cotizacion'),
    $request->input('cotizacion_id')
);

if ($input->tipoPedido->esLogo()) { ... }
```

---

### 3. **Responsabilidades Dispersas**
```php
// ❌ ANTES - Demasiados servicios acoplados
public function __construct(
    CrearProduccionPedidoUseCase $crear,
    GuardarPedidoLogoService $logo,
    ProcesarFotosTelasService $fotos  // ← ¿Aquí debe estar?
) {}
```

**Problema:**
- UseCase tiene 3 inyecciones
- `ProcesarFotosTelasService` trata archivos HTTP → Infrastructure
- Difícil de testear

**Solución:**
```php
//  DESPUÉS - Solo lo esencial
public function __construct(
    CrearProduccionPedidoUseCase $crear,
    GuardarPedidoLogoService $logo,
) {}

// ProcesarFotosTelasService queda en Controller/Mapper
// El UseCase recibe datos YA procesados (URLs)
```

---

### 4. **Transacción Manual (Anti-pattern)**
```php
// ❌ ANTES - Manual y propenso a errores
DB::beginTransaction();
try {
    // lógica
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw new \Exception(...);
}
```

**Problema:**
- Boilerplate innecesario
- Fácil olvidar rollback
- Menos legible

**Solución:**
```php
//  DESPUÉS - Automático y limpio
return DB::transaction(function () use ($input): GuardarPedidoOutputDTO {
    // Laravel automáticamente hace rollback si hay excepción
    return $this->guardarPedidoLogo($input);
});
```

---

### 5. **DTOs Confusas**
```php
// ❌ ANTES - Parámetros sin sentido
$dto = new CrearProduccionPedidoDTO(
    $validated['cliente'],      // ← ¿Qué es esto?
    $validated['cliente'],      // ← Repetido?
    $productosConFotos
);
```

**Solución:**
```php
//  DESPUÉS - Constructor explícito con named arguments
$crearPedidoDTO = new CrearProduccionPedidoDTO(
    clienteId: $input->clienteId,
    datosCliente: $input->datosCliente,
    productos: $input->productos,
);
```

---

##  MEJORAS IMPLEMENTADAS

### 1. **Value Object - TipoPedido**
```php
app/Domain/Pedidos/ValueObjects/TipoPedido.php
```

**Beneficios:**
-  Encapsula lógica de decisión ("¿es logo?")
-  Garantiza valores válidos (solo LOGO o PRODUCCION)
-  Métodos semánticos: `esLogo()`, `esProduccion()`
-  Reutilizable en Domain

**Uso:**
```php
$tipoPedido = TipoPedido::fromCotizacion($tipo, $id);

if ($tipoPedido->esLogo()) {
    // Flujo de logo
}
```

---

### 2. **Input DTO - GuardarPedidoInputDTO**
```php
app/Application/Pedidos/DTOs/GuardarPedidoInputDTO.php
```

**Beneficios:**
-  Application desacoplada de HTTP
-  Todos los datos explícitos en el constructor
-  Fácil de testear (crear DTO con datos de prueba)
-  Self-documenting (claro qué necesita el UseCase)

**Uso:**
```php
$input = new GuardarPedidoInputDTO(
    clienteId: '123',
    tipoPedido: TipoPedido::logo(),
    datosCliente: [...],
    imagenesProcesadas: [...],
    productos: [...]
);

$output = $useCase->ejecutar($input);
```

---

### 3. **Output DTO - GuardarPedidoOutputDTO**
```php
app/Application/Pedidos/DTOs/GuardarPedidoOutputDTO.php
```

**Beneficios:**
-  UseCase retorna estructura clara
-  Controller sabe exactamente qué esperar
-  Fácil serializar a JSON

---

### 4. **Request Mapper - Infrastructure**
```php
app/Infrastructure/Http/Mappers/GuardarPedidoRequestMapper.php
```

**Beneficios:**
-  Centraliza conversión Request → DTO
-  Application no ve Request
-  Fácil cambiar Request sin afectar UseCase
-  Lógica de procesamiento de archivos aquí

**Uso en Controller:**
```php
$input = GuardarPedidoRequestMapper::fromRequest($request);
$output = $useCase->ejecutar($input);
```

---

### 5. **UseCase Limpio**
```php
app/Application/Asesores/UseCases/GuardarPedidoUseCase.php
```

**Beneficios:**
-  Solo orquestación (29 líneas vs 73)
-  NO depende de Request
-  Testeable sin framework
-  Responsabilidades claras

---

## 🗂️ ESTRUCTURA DE CARPETAS CORRECTA

```
app/
├── Domain/                          # LÓGICA PURA DE NEGOCIO
│   └── Pedidos/
│       ├── Entities/
│       │   ├── Pedido.php
│       │   └── PedidoLogo.php
│       ├── ValueObjects/
│       │   ├── TipoPedido.php       # ← Decisión de negocio
│       │   ├── ClienteId.php
│       │   └── NumeroPedido.php
│       ├── Repositories/            # Interfaces
│       │   └── PedidoRepository.php
│       ├── Services/                # Domain Services (lógica pura)
│       │   └── PedidoCalculadorService.php
│       └── Exceptions/
│           └── PedidoInvalidoException.php
│
├── Application/                     # ORQUESTACIÓN Y CASOS DE USO
│   └── Pedidos/
│       ├── UseCases/
│       │   ├── GuardarPedidoUseCase.php         # ← Refactorizado
│       │   ├── CrearProduccionPedidoUseCase.php
│       │   └── ObtenerPedidoUseCase.php
│       ├── DTOs/
│       │   ├── GuardarPedidoInputDTO.php        # ← Entrada
│       │   ├── GuardarPedidoOutputDTO.php       # ← Salida
│       │   └── CrearProduccionPedidoDTO.php
│       ├── Services/                # Application Services
│       │   └── GuardarPedidoLogoService.php     # Coordinación técnica
│       └── Mappers/                 # Mapeo interno
│           └── PedidoToDTOMapper.php
│
└── Infrastructure/                  # IMPLEMENTACIONES TÉCNICAS
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Asesores/
    │   │   │   ├── GuardarPedidoController.php
    │   │   │   └── GuardarPedidoControllerExample.php
    │   │   └── ...
    │   ├── Requests/
    │   │   └── GuardarPedidoRequest.php
    │   ├── Mappers/                 # Request → DTO ← CLAVE
    │   │   └── GuardarPedidoRequestMapper.php
    │   └── Responses/
    │       └── GuardarPedidoResponse.php
    ├── Persistence/
    │   └── Repositories/
    │       ├── LaravelPedidoRepository.php
    │       └── LaravelPedidoLogoRepository.php
    └── Services/                    # Servicios técnicos
        ├── ImagenProcesadorService.php
        └── Almacenamiento.php
```

---

## 📊 FLUJO DE DATOS (BEFORE vs AFTER)

### ❌ ANTES (Acoplado)
```
HTTP Request
    ↓
Controller → Request object
    ↓
UseCase.ejecutar($validated, $request, 'productos')  ← ¡Request aquí!
    ↓
Lee de $request.input()  ← Acoplado
    ↓
Lógica de negocio
    ↓
Response JSON
```

###  DESPUÉS (Limpio)
```
HTTP Request
    ↓
GuardarPedidoRequest (Validación)  ← Form Request
    ↓
GuardarPedidoRequestMapper.fromRequest()
    ↓
GuardarPedidoInputDTO
    ↓
GuardarPedidoUseCase.ejecutar($input)  ← Solo DTO
    ↓
Domain logic (TipoPedido, etc)
    ↓
GuardarPedidoOutputDTO
    ↓
Controller → Response JSON
```

---

## 🧪 TESTABILIDAD

### ❌ ANTES (Difícil de testear)
```php
public function testGuardarPedido() {
    // Necesito crear un Request real (complicado)
    $request = Request::create('/ruta', 'POST', [...]);
    
    // Difícil mockear partes del Request
    $useCase->ejecutar($validated, $request, 'productos');
}
```

###  DESPUÉS (Fácil de testear)
```php
public function testGuardarPedidoLogo() {
    // Crear DTO directamente (simple)
    $input = new GuardarPedidoInputDTO(
        clienteId: 'test-123',
        tipoPedido: TipoPedido::logo(),
        datosCliente: [...],
        imagenesProcesadas: [...],
        productos: []
    );
    
    $output = $useCase->ejecutar($input);
    
    $this->assertEquals('logo', $output->tipo);
    $this->assertNotNull($output->id);
}
```

---

## 🔄 CÓMO USAR EN LA PRÁCTICA

### 1. En el Controller
```php
public function store(GuardarPedidoRequest $request): JsonResponse {
    try {
        // 1. Mapear Request → DTO (Infrastructure)
        $input = GuardarPedidoRequestMapper::fromRequest($request);
        
        // 2. Ejecutar UseCase (Application)
        $output = $this->guardarPedidoUseCase->ejecutar($input);
        
        // 3. Retornar Response
        return response()->json([
            'success' => true,
            'data' => ['tipo' => $output->tipo, 'id' => $output->id]
        ], 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

### 2. En Tests
```php
public function testGuardarPedido() {
    $useCase = new GuardarPedidoUseCase(
        new CrearProduccionPedidoUseCaseFake(),
        new GuardarPedidoLogoServiceFake()
    );
    
    $input = new GuardarPedidoInputDTO(...);
    $output = $useCase->ejecutar($input);
    
    $this->assertEquals('produccion', $output->tipo);
}
```

---

## 📝 CHECKLIST - CLEAN ARCHITECTURE & DDD

-  UseCase NO depende de HTTP
-  UseCase recibe DTOs (no arrays/requests)
-  Value Objects encapsulan decisiones de negocio
-  Lógica de decisión está en Domain
-  Responsabilidades claras por capa
-  Transacciones automáticas con DB::transaction()
-  Mapper transforma Request → DTO en Infrastructure
-  Exceptions son del Domain (no DTOs de error)
-  Output DTO es claro y explícito
-  UseCase es testeable sin framework

---

## 🚀 PRÓXIMOS PASOS

1. **Aplicar el mismo patrón a otros UseCases**
   - `CrearProduccionPedidoUseCase`
   - `ActualizarPedidoUseCase`

2. **Crear más Value Objects**
   - `ClienteId` (VO que valida)
   - `NumeroSecuencia` (VO con lógica)

3. **Implementar interfaces en todos los repositories**
   ```php
   interface PedidoRepository {
       public function guardar(Pedido $pedido): void;
       public function obtenerPorId(PedidoId $id): ?Pedido;
   }
   ```

4. **Crear Exceptions del Domain**
   ```php
   class PedidoInvalidoException extends DomainException {}
   class ClienteNoEncontradoException extends DomainException {}
   ```

---

## 📚 REFERENCIAS

- **Clean Architecture**: Uncle Bob
- **Domain-Driven Design**: Eric Evans
- **Hexagonal Architecture**: Alistair Cockburn
