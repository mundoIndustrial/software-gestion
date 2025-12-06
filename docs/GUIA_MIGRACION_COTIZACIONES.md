# Guía de Migración: Controlador Original → Refactorizado

## 📌 Paso a Paso

### Paso 1: Registrar el Service Provider

**Archivo:** `config/app.php`

```php
'providers' => [
    // ... otros providers
    App\Providers\AppServiceProvider::class,
    App\Modules\Cotizaciones\Providers\CotizacionesServiceProvider::class, // ← AGREGAR
],
```

### Paso 2: Actualizar las Rutas

**Archivo:** `routes/web.php`

```php
// ❌ ANTES
Route::get('/asesores/cotizaciones', [CotizacionesController::class, 'index']);
Route::get('/asesores/cotizaciones/{id}', [CotizacionesController::class, 'show']);

// ✅ DESPUÉS
use App\Modules\Cotizaciones\Http\Controllers\CotizacionesControllerRefactored;

Route::get('/asesores/cotizaciones', [CotizacionesControllerRefactored::class, 'index']);
Route::get('/asesores/cotizaciones/{id}', [CotizacionesControllerRefactored::class, 'show']);
Route::patch('/asesores/cotizaciones/{id}/estado', [CotizacionesControllerRefactored::class, 'changeState']);
Route::delete('/asesores/cotizaciones/{id}', [CotizacionesControllerRefactored::class, 'destroy']);
```

### Paso 3: Actualizar la Vista Blade

**Archivo:** `resources/views/asesores/cotizaciones/index.blade.php`

```blade
{{-- ❌ ANTES: HTML inline de 1200+ líneas --}}

{{-- ✅ DESPUÉS: Componentes reutilizables --}}
@extends('layouts.asesores')

@section('title', 'Cotizaciones')
@section('page-title', 'Cotizaciones y Borradores')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    @component('components.cotizaciones.header', [
        'title' => 'Cotizaciones',
        'subtitle' => 'Gestiona tus cotizaciones',
        'actionButton' => [
            'url' => route('asesores.pedidos.create'),
            'label' => 'Registrar'
        ]
    ])
    @endcomponent

    {{-- Filtros --}}
    @component('components.cotizaciones.filters', [
        'filters' => [
            ['code' => 'todas', 'label' => 'Todas', 'icon' => 'fas fa-list', 'active' => true],
            ['code' => 'P', 'label' => 'Prenda', 'icon' => 'fas fa-shirt', 'active' => false],
            ['code' => 'B', 'label' => 'Logo', 'icon' => 'fas fa-palette', 'active' => false],
            ['code' => 'PB', 'label' => 'Prenda/Logo', 'icon' => 'fas fa-shirt', 'active' => false],
        ]
    ])
    @endcomponent

    {{-- Tabla de Todas --}}
    @component('components.cotizaciones.table', [
        'sectionId' => 'todas',
        'title' => 'Todas las Cotizaciones',
        'cotizaciones' => $cotizacionesTodas,
        'columns' => [
            ['key' => 'fecha', 'label' => 'Fecha', 'filterable' => true, 'align' => 'left'],
            ['key' => 'codigo', 'label' => 'Código', 'filterable' => true, 'align' => 'left'],
            ['key' => 'cliente', 'label' => 'Cliente', 'filterable' => true, 'align' => 'left'],
            ['key' => 'tipo', 'label' => 'Tipo', 'filterable' => true, 'align' => 'left'],
            ['key' => 'estado', 'label' => 'Estado', 'filterable' => true, 'align' => 'left'],
            ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
        ]
    ])
    @endcomponent

    {{-- Tabla de Prenda --}}
    @component('components.cotizaciones.table', [
        'sectionId' => 'prenda',
        'title' => 'Cotizaciones - Prenda',
        'cotizaciones' => $cotizacionesPrenda,
        'columns' => [
            ['key' => 'fecha', 'label' => 'Fecha', 'filterable' => false],
            ['key' => 'codigo', 'label' => 'Código', 'filterable' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'filterable' => false],
            ['key' => 'tipo', 'label' => 'Tipo', 'filterable' => false],
            ['key' => 'estado', 'label' => 'Estado', 'filterable' => false],
            ['key' => 'accion', 'label' => 'Acción', 'align' => 'center'],
        ]
    ])
    @endcomponent

    {{-- Similarmente para Logo y Prenda/Bordado --}}
    {{-- ... --}}

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/cotizaciones/filtros-embudo.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/cotizaciones/index.js') }}"></script>
@endpush
```

### Paso 4: Agregar métodos al controlador antiguo (Transición)

Si necesitas mantener ambos temporalmente:

```php
class CotizacionesController extends Controller {
    public function __construct(
        private CotizacionFacadeService $facade
    ) {}

    public function index() {
        // Delegar al nuevo controlador
        return (new CotizacionesControllerRefactored($this->facade))->index();
    }
}
```

### Paso 5: Tests

```php
namespace Tests\Feature\Modules\Cotizaciones;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\User;

class CotizacionesControllerTest extends TestCase {
    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_their_cotizaciones() {
        $cot = Cotizacion::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get('/asesores/cotizaciones');

        $response->assertStatus(200);
        $response->assertViewHas('cotizacionesTodas');
    }

    public function test_user_cannot_view_others_cotizaciones() {
        $otherUser = User::factory()->create();
        $cot = Cotizacion::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get("/asesores/cotizaciones/{$cot->id}");

        $response->assertStatus(403);
    }

    public function test_can_delete_cotizacion() {
        $cot = Cotizacion::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete("/asesores/cotizaciones/{$cot->id}");

        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('cotizaciones', ['id' => $cot->id]);
    }

    public function test_can_change_cotizacion_state() {
        $cot = Cotizacion::factory()->create(['user_id' => $this->user->id, 'estado' => 'BORRADOR']);

        $response = $this->actingAs($this->user)
            ->patch("/asesores/cotizaciones/{$cot->id}/estado", ['estado' => 'ENVIADA_ASESOR']);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('cotizaciones', ['id' => $cot->id, 'estado' => 'ENVIADA_ASESOR']);
    }
}
```

### Paso 6: Tests Unitarios de Servicios

```php
namespace Tests\Unit\Modules\Cotizaciones\Services;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\User;
use App\Modules\Cotizaciones\Services\CotizacionFacadeService;

class CotizacionFacadeServiceTest extends TestCase {
    private CotizacionFacadeService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = app(CotizacionFacadeService::class);
    }

    public function test_get_all_user_cotizaciones() {
        $user = User::factory()->create();
        Cotizacion::factory(3)->create(['user_id' => $user->id, 'es_borrador' => false]);
        Cotizacion::factory(2)->create(['user_id' => $user->id, 'es_borrador' => true]);

        $result = $this->service->getAllUserCotizaciones($user->id);

        $this->assertCount(3, $result); // Solo no-borradores
    }

    public function test_get_user_drafts() {
        $user = User::factory()->create();
        Cotizacion::factory(3)->create(['user_id' => $user->id, 'es_borrador' => true]);

        $result = $this->service->getUserDrafts($user->id);

        $this->assertCount(3, $result);
    }

    public function test_filter_by_type() {
        $user = User::factory()->create();
        Cotizacion::factory(2)->create(['user_id' => $user->id, 'tipo_cotizacion_id' => 1]); // Prenda

        $result = $this->service->getByType($user->id, 'P');

        $this->assertCount(2, $result);
    }
}
```

---

## 🔄 Rollback (Si algo va mal)

1. Revertir registro del Service Provider en `config/app.php`
2. Cambiar rutas de vuelta al controlador original
3. Usar la vista antigua

---

## ✅ Checklist de Migración

- [ ] Registrar Service Provider en `config/app.php`
- [ ] Actualizar rutas en `routes/web.php`
- [ ] Crear/Actualizar componentes Blade
- [ ] Refactorizar vista principal
- [ ] Ejecutar tests
- [ ] Revisar en navegador
- [ ] Verificar logs
- [ ] Eliminar controlador antiguo (opcional)
- [ ] Documentar cambios en CHANGELOG

---

## 📞 Soporte

Si hay problemas:

1. **Revisar logs:** `storage/logs/laravel.log`
2. **Verificar rutas:** `php artisan route:list`
3. **Verificar binding:** `php artisan tinker` → `app(CotizacionFacadeService::class)`
4. **Usar xdebug/debugbar**

---

**¡La migración es segura y reversible en cualquier momento!**
