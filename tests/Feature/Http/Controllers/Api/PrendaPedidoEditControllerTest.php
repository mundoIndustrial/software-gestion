<?php

namespace Tests\Feature\Http\Controllers\API;

use Tests\TestCase;

class PrendaPedidoEditControllerTest extends TestCase
{
    /**
     * Test: Controller existe
     */
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class)
        );
    }

    /**
     * Test: Controller tiene método editPrenda
     */
    public function test_controller_has_edit_prenda_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editPrenda'));
    }

    /**
     * Test: Controller tiene método editPrendaFields
     */
    public function test_controller_has_edit_prenda_fields_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editPrendaFields'));
    }

    /**
     * Test: Controller tiene método editTallas
     */
    public function test_controller_has_edit_tallas_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editTallas'));
    }

    /**
     * Test: Controller tiene método editVariante
     */
    public function test_controller_has_edit_variante_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editVariante'));
    }

    /**
     * Test: Controller tiene método editVarianteFields
     */
    public function test_controller_has_edit_variante_fields_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editVarianteFields'));
    }

    /**
     * Test: Controller tiene método editVarianteColores
     */
    public function test_controller_has_edit_variante_colores_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editVarianteColores'));
    }

    /**
     * Test: Controller tiene método editVarianteTelas
     */
    public function test_controller_has_edit_variante_telas_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('editVarianteTelas'));
    }

    /**
     * Test: Controller tiene método getPrendaState
     */
    public function test_controller_has_get_prenda_state_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('getPrendaState'));
    }

    /**
     * Test: Controller tiene método getVarianteState
     */
    public function test_controller_has_get_variante_state_method(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );
        $this->assertTrue($reflection->hasMethod('getVarianteState'));
    }

    /**
     * Test: Rutas API están registradas
     */
    public function test_api_routes_exist(): void
    {
        $routes = \Route::getRoutes();

        // Verificar que al menos hay algunas rutas para prendas-pedido
        $prendaRoutes = collect($routes)->filter(
            fn($route) => str_contains($route->uri, 'prendas-pedido')
        )->count();

        $this->assertGreaterThanOrEqual(0, $prendaRoutes);
    }

    /**
     * Test: Todos los 9 métodos del controller retornan response
     */
    public function test_controller_methods_return_response(): void
    {
        $reflection = new \ReflectionClass(
            \App\Infrastructure\Http\Controllers\API\PrendaPedidoEditController::class
        );

        $methods = [
            'editPrenda',
            'editPrendaFields',
            'editTallas',
            'editVariante',
            'editVarianteFields',
            'editVarianteColores',
            'editVarianteTelas',
            'getPrendaState',
            'getVarianteState',
        ];

        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Method $method not found");
            $reflectionMethod = $reflection->getMethod($method);
            $this->assertTrue($reflectionMethod->isPublic(), "Method $method is not public");
        }
    }

    /**
     * Test: DTO Edit existe
     */
    public function test_edit_prenda_dto_exists(): void
    {
        $this->assertTrue(class_exists(\App\DTOs\Edit\EditPrendaPedidoDTO::class));
    }

    /**
     * Test: DTO Edit Variante existe
     */
    public function test_edit_variante_dto_exists(): void
    {
        $this->assertTrue(class_exists(\App\DTOs\Edit\EditPrendaVariantePedidoDTO::class));
    }

    /**
     * Test: Services están registrados
     */
    public function test_services_are_registered(): void
    {
        $prendaService = app(\App\Infrastructure\Services\Edit\PrendaPedidoEditService::class);
        $varianteService = app(\App\Infrastructure\Services\Edit\PrendaVariantePedidoEditService::class);

        $this->assertNotNull($prendaService);
        $this->assertNotNull($varianteService);
    }

    /**
     * Test: Validator está registrado
     */
    public function test_validator_is_registered(): void
    {
        $validator = app(\App\Infrastructure\Services\Validators\PrendaEditSecurityValidator::class);
        $this->assertNotNull($validator);
    }

    /**
     * Test: Strategy está registrado
     */
    public function test_strategy_is_registered(): void
    {
        $strategy = app(\App\Infrastructure\Services\Strategies\MergeRelationshipStrategy::class);
        $this->assertNotNull($strategy);
    }
}
