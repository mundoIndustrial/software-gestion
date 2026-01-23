<?php

namespace Tests\Feature\Domain\Pedidos;

use Tests\TestCase;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

class PedidoSimpleTest extends TestCase
{
    public function test_crear_agregado_con_prenda()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Simple test',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 5,
                    'tallas' => ['DAMA' => ['S' => 5]],
                ]
            ]
        );

        $this->assertNotNull($pedido);
        $this->assertEquals(1, $pedido->clienteId());
        $this->assertEquals(1, $pedido->totalPrendas());
    }
}

