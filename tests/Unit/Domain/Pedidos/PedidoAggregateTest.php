<?php

namespace Tests\Unit\Domain\Pedidos;

use PHPUnit\Framework\TestCase;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\Estado;

/**
 * Tests bÃ¡sicos para PedidoAggregate
 * 
 * Fase 0: Verificar que el dominio compila y funciona
 */
class PedidoAggregateTest extends TestCase
{
    /**
     * Test 1: Crear pedido vÃ¡lido
     */
    public function test_crear_pedido_valido()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido de prueba',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );

        $this->assertNotNull($pedido);
        $this->assertEquals(1, $pedido->clienteId());
        $this->assertEquals('Pedido de prueba', $pedido->descripcion());
        $this->assertEquals(Estado::PENDIENTE, $pedido->estado()->valor());
        $this->assertEquals(1, $pedido->totalPrendas());
        $this->assertEquals(10, $pedido->totalArticulos());
    }

    /**
     * Test 2: Confirmar pedido
     */
    public function test_confirmar_pedido()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido para confirmar',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 5,
                    'tallas' => ['DAMA' => ['S' => 5]],
                ]
            ]
        );

        $pedido->confirmar();

        $this->assertEquals(Estado::CONFIRMADO, $pedido->estado()->valor());
    }

    /**
     * Test 3: No permitir confirmar pedido finalizado
     */
    public function test_no_permitir_confirmar_pedido_finalizado()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido para finalizar',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 5,
                    'tallas' => ['DAMA' => ['S' => 5]],
                ]
            ]
        );

        // Cambiar a CONFIRMADO â†’ EN_PRODUCCION â†’ COMPLETADO
        $pedido->confirmar();
        $pedido->iniciarProduccion();
        $pedido->completar();

        // Intentar confirmar un pedido finalizado
        $this->expectException(\DomainException::class);
        $pedido->confirmar();
    }
}

