# GUÍA DE IMPLEMENTACIÓN: Migración a RegistroOrdenControllerRefactored

## 📋 Checklist de Implementación

### Fase 1: Setup (1-2 horas)

- [ ] Crear carpetas de estructura DDD
- [ ] Crear Domain Services y Value Objects
- [ ] Crear Application UseCases
- [ ] Crear Infrastructure QueryServices
- [ ] Registrar en Service Provider

```bash
# Ejecución
mkdir -p app/Application/UseCases/Orders
mkdir -p app/Application/UseCases/Receipts
mkdir -p app/Domain/Services
mkdir -p app/Domain/ValueObjects
mkdir -p app/Infrastructure/QueryServices
```

### Fase 2: Validación (2-3 horas)

- [ ] Ejecutar tests unitarios
- [ ] Validar inyección de dependencias
- [ ] Validar que los UseCases se cargan correctamente
- [ ] Mock de servicios heredados

### Fase 3: Migración de Routes (1-2 horas)

- [ ] Crear rutas de prueba
- [ ] Validar redirecciones
- [ ] Probar cada endpoint
- [ ] Mantener compatibilidad backward

### Fase 4: Refactorización Incremental (Según necesidad)

- [ ] Migrar endpoints uno por uno
- [ ] Mantener tests en verde
- [ ] Documentar cambios en git

---

## 🔧 Paso a Paso de Instalación

### Paso 1: Actualizar AppServiceProvider

**File: `app/Providers/AppServiceProvider.php`**

```php
public function boot()
{
    // Registrar el DDDServiceProvider
    $this->app->register(DDDServiceProvider::class);
}
```

### Paso 2: Actualizar Routes

**File: `routes/web.php` o `routes/api.php`**

```php
// Nuevas rutas (refactorizadas)
Route::name('ordenes.')->middleware(['auth', 'verified'])->group(function () {
    Route::post('/ordenes', [RegistroOrdenControllerRefactored::class, 'store'])->name('store');
    Route::put('/ordenes/{pedido}', [RegistroOrdenControllerRefactored::class, 'update'])->name('update');
    Route::delete('/ordenes/{pedido}', [RegistroOrdenControllerRefactored::class, 'destroy'])->name('destroy');
    Route::get('/ordenes/{id}', [RegistroOrdenControllerRefactored::class, 'show'])->name('show');
    
    // Filtros y búsqueda
    Route::post('/ordenes/buscar', [RegistroOrdenControllerRefactored::class, 'searchOrders'])->name('search');
    Route::post('/ordenes/filtrar', [RegistroOrdenControllerRefactored::class, 'filterOrders'])->name('filter');
    
    // Novedades y entregas
    Route::post('/ordenes/{id}/novedades', [RegistroOrdenControllerRefactored::class, 'addNovedad'])->name('add-novedad');
    Route::post('/ordenes/{id}/dia-entrega', [RegistroOrdenControllerRefactored::class, 'saveDiaEntrega'])->name('save-dia-entrega');
});

// Mantener rutas antiguas para compatibilidad (deprecadas)
Route::name('ordenes-legacy.')->middleware(['auth', 'verified'])->group(function () {
    // TODO: Implementar redirecciones a nuevas rutas
    // Route::post('/ordenes-legacy', [RegistroOrdenController::class, 'store']);
});
```

### Paso 3: Crear tests para cada UseCase

**File: `tests/Feature/Orders/CreateOrderTest.php`**

```php
<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_crear_orden_valida()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/ordenes', [
                'pedido' => 100,
                'cliente' => 'Cliente Test',
                'estado' => 'Pendiente',
                'forma_de_pago' => 'Contado',
                'area' => 'Insumos',
                'prendas' => []
            ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonPath('pedido', 100);

        $this->assertDatabaseHas('pedidos_produccion', [
            'numero_pedido' => 100,
            'cliente' => 'Cliente Test'
        ]);
    }

    public function test_crear_orden_sin_datos_falla()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/ordenes', []);

        $response->assertStatus(422); // Validation error
    }
}
```

### Paso 4: Ejecutar Tests

```bash
# Tests unitarios de Domain Services
php artisan test tests/Unit/Domain/Services/

# Tests de Application UseCases
php artisan test tests/Unit/Application/UseCases/

# Tests de integración
php artisan test tests/Feature/Orders/

# Todos los tests
php artisan test
```

---

## 🧪 Tests Específicos por Componente

### Test: Value Object PedidoNumber

```php
<?php
namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObjects\PedidoNumber;

class PedidoNumberTest extends TestCase
{
    public function test_crear_numero_valido()
    {
        $numero = PedidoNumber::create(100, 101);
        $this->assertEquals(100, $numero->toInt());
    }

    public function test_crear_numero_invalido_lanza_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        PedidoNumber::create(0, 1);
    }

    public function test_verificar_siguiente_esperado()
    {
        $numero = PedidoNumber::create(101, 101);
        $this->assertTrue($numero->isNextExpected());
    }
}
```

### Test: Domain Service OrderCalculationService

```php
<?php
namespace Tests\Unit\Domain\Services;

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;
use App\Domain\Services\OrderCalculationService;
use App\Services\FestivosColombiaService;

class OrderCalculationServiceTest extends TestCase
{
    private OrderCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderCalculationService(
            new FestivosColombiaService()
        );
    }

    public function test_calcular_dias_habiles()
    {
        $inicio = Carbon::parse('2024-01-01'); // Lunes
        $fin = Carbon::parse('2024-01-05');    // Viernes
        
        $dias = $this->service->calcularDiasHabiles($inicio, $fin);
        
        // De lunes a viernes = 4 días (no incluye el inicio)
        $this->assertEqual(4, $dias);
    }

    public function test_validar_dia_entrega_valido()
    {
        $this->assertTrue($this->service->validarDiaEntrega(1));
        $this->assertTrue($this->service->validarDiaEntrega(35));
    }

    public function test_validar_dia_entrega_invalido()
    {
        $this->assertFalse($this->service->validarDiaEntrega(0));
        $this->assertFalse($this->service->validarDiaEntrega(36));
    }
}
```

### Test: UseCase CreateOrderUseCase

```php
<?php
namespace Tests\Unit\Application\UseCases\Orders;

use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;
use App\Application\UseCases\Orders\CreateOrderUseCase;

class CreateOrderUseCaseTest extends TestCase
{
    private CreateOrderUseCase $useCase;
    private MockInterface $validationService;
    private MockInterface $creationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationService = Mockery::mock(RegistroOrdenValidationService::class);
        $this->creationService = Mockery::mock(RegistroOrdenCreationService::class);

        $this->useCase = new CreateOrderUseCase(
            $this->validationService,
            $this->creationService
        );
    }

    public function test_crear_orden_exitoso()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('allow_any_pedido', false)->andReturn(false);
        $request->pedido = 100;

        $this->validationService
            ->shouldReceive('validateStoreRequest')
            ->with($request)
            ->andReturn([
                'pedido' => 100,
                'cliente' => 'Test Client',
                'estado' => 'Pendiente'
            ]);

        $mockOrder = Mockery::mock();
        $mockOrder->numero_pedido = 100;

        $this->creationService
            ->shouldReceive('getNextNumber')
            ->andReturn(100);

        $this->creationService
            ->shouldReceive('createOrder')
            ->andReturn($mockOrder);

        $this->creationService
            ->shouldReceive('logOrderCreated');

        $this->creationService
            ->shouldReceive('broadcastOrderCreated');

        $result = $this->useCase->execute($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
```

---

## 🚨 Troubleshooting

### Error: "Class not found: CreateOrderUseCase"

**Solución:**
```php
// Verificar que el archivo existe
ls app/Application/UseCases/Orders/CreateOrderUseCase.php

// Regenerar autoloader
composer dump-autoload

// En Artisan
php artisan make:provider DDDServiceProvider
```

### Error: "Dependency injection failed"

**Debug:**
```php
// En AppServiceProvider
$this->app->make(CreateOrderUseCase::class);

// Verificar servicios registrados
php artisan tinker
> app(CreateOrderUseCase::class)
```

### Error: "DDDServiceProvider not registered"

**Solución:**
```php
// En config/app.php (providers array)
'providers' => [
    // ...
    App\Providers\DDDServiceProvider::class,
],
```

### Error: "Broadcast failed"

**Verificación:**
```php
// config/broadcasting.php
'default' => env('BROADCAST_DRIVER', 'log'),

// En .env
BROADCAST_DRIVER=log  # Para testing
BROADCAST_DRIVER=pusher  # Para producción
```

---

## 📊 Validación Post-Migración

### Endpoint: POST /ordenes

```bash
curl -X POST http://localhost:8000/ordenes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "pedido": 100,
    "cliente": "Test Client",
    "estado": "Pendiente",
    "forma_de_pago": "Contado",
    "area": "Insumos"
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Orden registrada correctamente",
  "pedido": 100
}
```

### Endpoint: PUT /ordenes/{pedido}

```bash
curl -X PUT http://localhost:8000/ordenes/100 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "estado": "En Proceso",
    "cliente": "Updated Client"
  }'
```

### Endpoint: POST /ordenes/buscar

```bash
curl -X POST http://localhost:8000/ordenes/buscar \
  -H "Content-Type: application/json" \
  -d '{"search": "100"}'
```

---

## 🔄 Rollback Plan

Si algo sale mal:

```php
// Revert a las rutas antiguas
Route::post('/ordenes', [RegistroOrdenController::class, 'store']);
Route::put('/ordenes/{pedido}', [RegistroOrdenController::class, 'update']);

// Comentar el DDDServiceProvider en AppServiceProvider
// $this->app->register(DDDServiceProvider::class);

// Ejecutar migrations si es necesario
php artisan migrate:rollback
```

---

## 📈 Roadmap Futuro

**Mes 1:**
- ✅ Implementar UseCases de Orders
- ⏳ Tests completos
- ⏳ Deploy a staging

**Mes 2:**
- ⏳ Implementar UseCases de Receipts
- ⏳ Integración con sistemas existentes
- ⏳ Documentación

**Mes 3:**
- ⏳ Implementar Domain Aggregates
- ⏳ Event Sourcing
- ⏳ CQRS Pattern

