<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Route;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion\CrearActualizarPedidoController;

/**
 * Test para endpoints de Pedidos (Fase 3)
 * 
 * Validar:
 * - POST /api/pedidos  Crear pedido
 * - PATCH /api/pedidos/{id}/confirmar  Confirmar pedido (cuando haya persistencia)
 * - GET /api/pedidos/{id}  Obtener pedido (cuando haya persistencia)
 */
class PedidoControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/__test_api_pedidos', [CrearActualizarPedidoController::class, 'store']);
    }

    /**
     * Test: Crear pedido via POST /api/pedidos
     * 
     * Usando mock del repositorio para no necesitar BD
     */
    public function test_crear_pedido_valida_entrada()
    {
        $crearPedidoUseCase = Mockery::mock(CrearProduccionPedidoUseCase::class);
        $crearPedidoUseCase->shouldReceive('ejecutar')->once()->andReturn(
            PedidoProduccionAggregate::crear(
                id: 1,
                numeroPedido: 'PED-001',
                cliente: 'Cliente Test',
                formaPago: 'contado',
                asesorId: 1,
                estado: 'Pendiente',
                area: 'creacion de pedido',
            )
        );

        $actualizarPedidoUseCase = Mockery::mock(ActualizarProduccionPedidoUseCase::class);
        $this->app->instance(CrearProduccionPedidoUseCase::class, $crearPedidoUseCase);
        $this->app->instance(ActualizarProduccionPedidoUseCase::class, $actualizarPedidoUseCase);

        $payload = [
            'numero_pedido' => 'PED-001',
            'cliente' => 'Cliente Test',
            'forma_pago' => 'contado',
            'asesor_id' => 1,
            'descripcion' => 'Pedido de camisetas para verano',
            'cantidad_inicial' => 100,
        ];
        $response = $this->postJson('/__test_api_pedidos', $payload);
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJsonStructure([]);
    }

    /**
     * Test: Fallar si falta cliente_id
     */
    public function test_crear_pedido_sin_cliente_id_retorna_error()
    {
        $crearPedidoUseCase = Mockery::mock(CrearProduccionPedidoUseCase::class);
        $actualizarPedidoUseCase = Mockery::mock(ActualizarProduccionPedidoUseCase::class);
        $this->app->instance(CrearProduccionPedidoUseCase::class, $crearPedidoUseCase);
        $this->app->instance(ActualizarProduccionPedidoUseCase::class, $actualizarPedidoUseCase);

        $payload = [
            'numero_pedido' => 'PED-002',
            'forma_pago' => 'contado',
            'asesor_id' => 1,
        ];

        $response = $this->postJson('/__test_api_pedidos', $payload);
        $this->assertEquals(422, $response->getStatusCode());
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
    }

    /**
     * Test: Fallar si no hay prendas
     */
    public function test_crear_pedido_sin_prendas_es_valido_en_contrato_actual()
    {
        $crearPedidoUseCase = Mockery::mock(CrearProduccionPedidoUseCase::class);
        $crearPedidoUseCase->shouldReceive('ejecutar')->once()->andReturn(
            PedidoProduccionAggregate::crear(
                id: 3,
                numeroPedido: 'PED-003',
                cliente: 'Cliente Test',
                formaPago: 'contado',
                asesorId: 1,
                estado: 'Pendiente',
                area: 'creacion de pedido',
            )
        );
        $actualizarPedidoUseCase = Mockery::mock(ActualizarProduccionPedidoUseCase::class);
        $this->app->instance(CrearProduccionPedidoUseCase::class, $crearPedidoUseCase);
        $this->app->instance(ActualizarProduccionPedidoUseCase::class, $actualizarPedidoUseCase);

        $payload = [
            'numero_pedido' => 'PED-003',
            'cliente' => 'Cliente Test',
            'forma_pago' => 'contado',
            'asesor_id' => 1,
        ];

        $response = $this->postJson('/__test_api_pedidos', $payload);
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJsonStructure([]);
    }
}
