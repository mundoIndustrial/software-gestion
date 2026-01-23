<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Mockery;

/**
 * Test para endpoints de Pedidos (Fase 3)
 * 
 * Validar:
 * - POST /api/pedidos â†’ Crear pedido
 * - PATCH /api/pedidos/{id}/confirmar â†’ Confirmar pedido (cuando haya persistencia)
 * - GET /api/pedidos/{id} â†’ Obtener pedido (cuando haya persistencia)
 */
class PedidoControllerTest extends TestCase
{
    /**
     * Test: Crear pedido vÃ­a POST /api/pedidos
     * 
     * Usando mock del repositorio para no necesitar BD
     */
    public function test_crear_pedido_valida_entrada()
    {
        // Mock del repositorio
        $repositoryMock = Mockery::mock(PedidoRepository::class);
        $repositoryMock->shouldReceive('guardar')->once();
        $this->app->bind(PedidoRepository::class, fn() => $repositoryMock);

        $payload = [
            'cliente_id' => 1,
            'descripcion' => 'Pedido de camisetas para verano',
            'observaciones' => 'Entregar antes de julio',
            'prendas' => [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta BÃ¡sica',
                    'cantidad' => 100,
                    'tallas' => [
                        'DAMA' => [
                            'S' => 20,
                            'M' => 30,
                            'L' => 20,
                        ],
                        'CABALLERO' => [
                            'M' => 20,
                            'L' => 10,
                        ]
                    ]
                ]
            ]
        ];

        // Ejecutar POST /api/pedidos
        $response = $this->postJson('/api/pedidos', $payload);

        // Verificar respuesta
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Verificar estructura de datos
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'numero',
                'cliente_id',
                'descripcion',
                'estado',
                'total_prendas',
                'total_articulos',
            ]
        ]);

        // Verificar que los datos son correctos
        $data = $response->json('data');
        $this->assertEquals(1, $data['cliente_id']);
        $this->assertEquals('Pedido de camisetas para verano', $data['descripcion']);
        $this->assertEquals('PENDIENTE', $data['estado']);
        $this->assertEquals(1, $data['total_prendas']);
        $this->assertEquals(100, $data['total_articulos']);
    }

    /**
     * Test: Fallar si falta cliente_id
     */
    public function test_crear_pedido_sin_cliente_id_retorna_error()
    {
        $payload = [
            'descripcion' => 'Pedido sin cliente',
            'prendas' => [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 10]],
                ]
            ]
        ];

        $response = $this->postJson('/api/pedidos', $payload);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test: Fallar si no hay prendas
     */
    public function test_crear_pedido_sin_prendas_retorna_error()
    {
        $payload = [
            'cliente_id' => 1,
            'descripcion' => 'Pedido sin prendas',
            'prendas' => []
        ];

        $response = $this->postJson('/api/pedidos', $payload);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}

