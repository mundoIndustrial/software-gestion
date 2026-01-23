namespace Tests\Unit\Domain\Pedidos;

use App\Domain\Pedidos\Agregado\PedidoProduccionAggregate;
use PHPUnit\Framework\TestCase;

class PedidoProduccionAggregateTest extends TestCase
{
    /**
     * @test
     * Validar que se puede crear un agregado de producciÃ³n
     */
    public function puede_crear_pedido_produccion()
    {
        $datos = [
            'numero_pedido' => 'PED-2024-001',
            'cliente' => 'Cliente Test',
            'estado' => 'pendiente',
            'fecha' => now(),
        ];

        $pedido = PedidoProduccionAggregate::crear($datos);

        $this->assertNotNull($pedido);
        $this->assertEquals('PED-2024-001', $pedido->getNumeroPedido());
        $this->assertEquals('Cliente Test', $pedido->getCliente());
        $this->assertEquals('pendiente', $pedido->getEstado());
    }

    /**
     * @test
     * Validar que se puede cambiar estado a confirmado
     */
    public function puede_cambiar_a_confirmado()
    {
        $pedido = PedidoProduccionAggregate::crear([
            'numero_pedido' => 'PED-2024-001',
            'cliente' => 'Cliente Test',
        ]);

        $pedido->confirmar();

        $this->assertEquals('confirmado', $pedido->getEstado());
    }

    /**
     * @test
     * Validar que no se puede cambiar a confirmado si ya estÃ¡
     */
    public function no_puede_confirmar_ya_confirmado()
    {
        $this->expectException(\InvalidArgumentException::class);

        $pedido = PedidoProduccionAggregate::crear([
            'numero_pedido' => 'PED-2024-001',
        ]);

        $pedido->confirmar();
        $pedido->confirmar(); // â† Error
    }

    /**
     * @test
     * Validar que se puede anular un pedido
     */
    public function puede_anular_pedido()
    {
        $pedido = PedidoProduccionAggregate::crear([
            'numero_pedido' => 'PED-2024-001',
        ]);

        $pedido->anular('Cliente solicitÃ³ cancelaciÃ³n');

        $this->assertEquals('anulado', $pedido->getEstado());
        $this->assertEquals('Cliente solicitÃ³ cancelaciÃ³n', $pedido->getRazonAnulacion());
    }
}

