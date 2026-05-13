<?php

namespace Tests\Feature\Pedidos;

use App\Application\Pedidos\UseCases\CrearPedidoCompleteUseCase;
use App\Application\Pedidos\UseCases\CrearPedidoOutput;
use App\Infrastructure\Http\Controllers\Asesores\Pedidos\CrearPedidoController;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

class CrearPedidoIdempotenciaTest extends TestCase
{
    protected User $asesor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config(['broadcasting.default' => 'log']);
        Route::post('/api/asesores/pedidos/crear', [CrearPedidoController::class, 'crearPedido']);
        Cache::flush();

        $this->asesor = User::factory()->create();
    }

    public function test_misma_idempotency_key_no_duplica_creacion_y_retorna_mismo_resultado(): void
    {
        $this->mock(CrearPedidoCompleteUseCase::class, function (MockInterface $mock): void {
            $mock->shouldReceive('ejecutar')
                ->once()
                ->andReturn(CrearPedidoOutput::success(
                    pedidoId: 1121,
                    numeroPedido: '1009',
                    clienteId: 77,
                ));
        });

        $payload = [
            'pedido' => json_encode([
                'cliente' => 'ZAZPI',
                'orden_compra' => 'OC-1009',
                'prendas' => [],
                'epps' => [],
            ]),
        ];

        $headers = [
            'X-Idempotency-Key' => '4f10a91a-7321-4b3f-9047-274a37888811',
        ];

        $response1 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/crear', $payload, $headers);

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pedido_id' => 1121,
                'numero_pedido' => '1009',
            ]);

        $response2 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/crear', $payload, $headers);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pedido_id' => 1121,
                'numero_pedido' => '1009',
            ]);
    }

    public function test_idempotency_keys_distintas_permiten_creaciones_distintas(): void
    {
        $this->mock(CrearPedidoCompleteUseCase::class, function (MockInterface $mock): void {
            $mock->shouldReceive('ejecutar')
                ->twice()
                ->andReturn(
                    CrearPedidoOutput::success(
                        pedidoId: 1121,
                        numeroPedido: '1009',
                        clienteId: 77,
                    ),
                    CrearPedidoOutput::success(
                        pedidoId: 1129,
                        numeroPedido: '1013',
                        clienteId: 77,
                    )
                );
        });

        $payload = [
            'pedido' => json_encode([
                'cliente' => 'ZAZPI',
                'orden_compra' => 'OC-1009',
                'prendas' => [],
                'epps' => [],
            ]),
        ];

        $response1 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/crear', $payload, [
                'X-Idempotency-Key' => 'a2de2ec6-cf4a-4912-bc57-f68c51b68eee',
            ]);

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pedido_id' => 1121,
                'numero_pedido' => '1009',
            ]);

        $response2 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/crear', $payload, [
                'X-Idempotency-Key' => '4e383f8e-f0bc-4cdf-a1af-4db786f47020',
            ]);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pedido_id' => 1129,
                'numero_pedido' => '1013',
            ]);
    }
}
