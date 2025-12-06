## 🚀 Guía Rápida de Integración

### Paso 1: Copiar archivos

**DTOs** → `app/DTOs/`
- `CotizacionSearchDTO.php`
- `PrendaCreacionDTO.php`
- `CrearPedidoProduccionDTO.php`

**Services** → `app/Services/Pedidos/`
- `CotizacionSearchService.php`
- `PrendaProcessorService.php`
- `PedidoProduccionCreatorService.php`

**Controller** → `app/Http/Controllers/Asesores/`
- `PedidoProduccionController.php`

**Provider** → `app/Providers/`
- `PedidosServiceProvider.php`

**Views** → `resources/views/`
- `asesores/pedidos/crear-desde-cotizacion-refactorizado.blade.php`
- `components/pedidos/cotizacion-search.blade.php`
- `components/pedidos/pedido-info.blade.php`
- `components/pedidos/prendas-container.blade.php`

**JavaScript** → `resources/js/modules/`
- `CotizacionRepository.js`
- `CotizacionSearchUIController.js`
- `PrendasUIController.js`
- `FormularioPedidoController.js`
- `FormInfoUpdater.js`
- `CotizacionDataLoader.js`
- `CrearPedidoApp.js`

**Routes** → `routes/asesores/`
- `pedidos.php`

### Paso 2: Registrar Service Provider

**Archivo**: `config/app.php`

```php
'providers' => [
    // ...
    App\Providers\PedidosServiceProvider::class,  // ← Agregar esta línea
],
```

### Paso 3: Actualizar rutas principal

**Archivo**: `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    Route::group(['prefix' => 'asesores'], function () {
        require base_path('routes/asesores/pedidos.php'); // ← Agregar esta línea
    });
});
```

### Paso 4: Compilar assets (si usas Vite/Mix)

```bash
npm run dev
# o
npm run build
```

### Paso 5: Cargar módulos en vista

**Archivo**: `resources/views/asesores/pedidos/crear-desde-cotizacion-refactorizado.blade.php`

```blade
<script type="module">
    import { CrearPedidoApp } from '{{ asset('js/modules/CrearPedidoApp.js') }}';
    
    const initialData = {
        cotizaciones: {!! json_encode($cotizacionesDTOs) !!},
        asesorActual: '{{ Auth::user()->name ?? '' }}',
        csrfToken: document.querySelector('input[name="_token"]').value
    };

    document.addEventListener('DOMContentLoaded', async () => {
        const app = new CrearPedidoApp(initialData);
        await app.inicializar();
    });
</script>
```

### Paso 6: En el Controller, proporcionar DTOs

**Archivo**: `app/Http/Controllers/Asesores/PedidoProduccionController.php`

```php
public function mostrarFormularioCrearDesdeCotzacion(): View
{
    $todas = $this->cotizacionSearch->obtenerTodas();
    
    $cotizacionesDTOs = $todas
        ->map(fn($cot) => $cot->toArray())
        ->values();

    return view('asesores.pedidos.crear-desde-cotizacion-refactorizado', [
        'cotizacionesDTOs' => $cotizacionesDTOs,  // ← DTOs convertidas a arrays
    ]);
}
```

---

## ✅ Checklist de Implementación

- [ ] Copiar archivos a sus carpetas
- [ ] Registrar `PedidosServiceProvider` en `config/app.php`
- [ ] Agregar rutas en `routes/web.php`
- [ ] Compilar assets (`npm run dev`)
- [ ] Verificar rutas: `php artisan route:list`
- [ ] Probar formulario en navegador
- [ ] Verificar console del navegador (F12)
- [ ] Probar envío de formulario
- [ ] Verificar logs en `storage/logs/laravel.log`

---

## 🧪 Ejemplo de Unit Test

**Archivo**: `tests/Unit/Services/PedidoProduccionCreatorServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\PrendaCreacionDTO;

class PedidoProduccionCreatorServiceTest extends TestCase
{
    private PedidoProduccionCreatorService $service;
    private PrendaProcessorService $prendaProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prendaProcessor = new PrendaProcessorService();
        $this->service = new PedidoProduccionCreatorService($this->prendaProcessor);
    }

    /** @test */
    public function puede_validar_dto_invalido()
    {
        $dto = new CrearPedidoProduccionDTO(
            cotizacionId: 0,  // Inválido
            prendasData: []   // Vacío
        );

        $this->assertFalse($dto->esValido());
    }

    /** @test */
    public function puede_obtener_proximo_numero()
    {
        $numero = $this->service->obtenerProximoNumero();
        $this->assertIsInt($numero);
        $this->assertGreater($numero, 0);
    }

    /** @test */
    public function puede_procesar_prenda_correctamente()
    {
        $prenda = new PrendaCreacionDTO(
            index: 0,
            nombreProducto: 'Prenda Test',
            descripcion: 'Descripción test',
            tela: null,
            telaReferencia: null,
            color: 'Rojo',
            genero: 'Hombre',
            manga: null,
            broche: null,
            tieneBolsillos: false,
            tieneReflectivo: false,
            mangaObs: null,
            bolsillosObs: null,
            brocheObs: null,
            reflectivoObs: null,
            observaciones: null,
            cantidades: ['M' => 10, 'L' => 5]
        );

        $this->assertTrue($prenda->esValido());
        $this->assertEquals(15, $prenda->cantidadTotal());
        
        $datos = $this->prendaProcessor->procesar($prenda);
        $this->assertArrayHasKey('nombre_producto', $datos);
        $this->assertArrayHasKey('cantidades', $datos);
        $this->assertEquals(['M' => 10, 'L' => 5], $datos['cantidades']);
    }
}
```

**Ejecutar test**:
```bash
php artisan test tests/Unit/Services/PedidoProduccionCreatorServiceTest.php
```

---

## 🧪 Ejemplo de Feature Test

**Archivo**: `tests/Feature/Asesores/PedidoProduccionControllerTest.php`

```php
<?php

namespace Tests\Feature\Asesores;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cotizacion;
use App\DTOs\CotizacionSearchDTO;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PedidoProduccionControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'asesor']);
    }

    /** @test */
    public function puede_mostrar_formulario_crear_pedido()
    {
        $response = $this->actingAs($this->user)
            ->get(route('pedidos-produccion.crear-desde-cotizacion'));

        $response->assertStatus(200);
        $response->assertViewIs('asesores.pedidos.crear-desde-cotizacion-refactorizado');
        $response->assertViewHas('cotizacionesDTOs');
    }

    /** @test */
    public function obtiene_proximo_numero_pedido()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('next-pedido'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['siguiente_pedido']);
    }

    /** @test */
    public function obtiene_datos_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('cotizaciones.obtener-datos', $cotizacion->id));

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'numero', 'cliente', 'prendas']);
    }

    /** @test */
    public function valida_creacion_pedido_sin_prendas()
    {
        $cotizacion = Cotizacion::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('cotizaciones.crear-pedido-produccion', $cotizacion->id), [
                'cotizacion_id' => $cotizacion->id,
                'prendas' => []
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
```

**Ejecutar tests**:
```bash
php artisan test tests/Feature/Asesores/PedidoProduccionControllerTest.php
```

---

## 🐛 Troubleshooting

### Problema: "Class not found"
**Solución**: Ejecutar `composer dump-autoload`

```bash
composer dump-autoload
```

### Problema: "Module not found" (JavaScript)
**Solución**: Verificar rutas en `resources/js/modules/`

```bash
# Verificar que existen los archivos
ls resources/js/modules/
```

### Problema: CSRF Token mismatch
**Solución**: Verificar que Form tiene `@csrf`

```blade
<form id="formCrearPedido" method="POST">
    @csrf
    <!-- ... -->
</form>
```

### Problema: Service Provider no se carga
**Solución**: Verificar registro en `config/app.php`

```php
// Debe estar en 'providers'
'providers' => [
    // ...
    App\Providers\PedidosServiceProvider::class,
],
```

### Problema: Rutas no encontradas
**Solución**: Verificar registro en `routes/web.php`

```bash
php artisan route:list | grep pedido
```

---

## 📚 Recursos Adicionales

- [SOLID Principles - Martin Fowler](https://martinfowler.com/articles/dipInTheWild.html)
- [Design Patterns - RefactoringGuru](https://refactoring.guru/design-patterns)
- [Laravel Service Providers](https://laravel.com/docs/11.x/providers)
- [JavaScript Modules - MDN](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules)
- [DTOs in PHP](https://www.php.net/manual/en/class.stdclass.php)
