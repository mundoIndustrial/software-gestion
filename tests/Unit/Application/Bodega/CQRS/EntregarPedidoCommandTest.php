<?php

namespace Tests\Unit\Application\Bodega\CQRS;

use Tests\TestCase;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Domain\Bodega\ValueObjects\EstadoPedido;

class EntregarPedidoCommandTest extends TestCase
{
    /** @test */
    public function puede_crear_command_valido()
    {
        $command = new EntregarPedidoCommand(123, 'Observación de prueba', 456);

        $this->assertEquals(123, $command->getPedidoId());
        $this->assertEquals('Observación de prueba', $command->getObservaciones());
        $this->assertEquals(456, $command->getUsuarioId());
        $this->assertNotEmpty($command->getCommandId());
        $this->assertInstanceOf(\DateTime::class, $command->getEjecutadoEn());
    }

    /** @test */
    public function puede_crear_command_sin_observaciones()
    {
        $command = new EntregarPedidoCommand(123);

        $this->assertEquals(123, $command->getPedidoId());
        $this->assertNull($command->getObservaciones());
        $this->assertNotNull($command->getUsuarioId()); // Debe usar auth()->id()
    }

    /** @test */
    public function command_valido_pasa_validacion()
    {
        $command = new EntregarPedidoCommand(123, 'Observación', 456);
        
        // No debe lanzar excepción
        $command->validate();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function command_invalido_falla_validacion_id_negativo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del pedido debe ser un número positivo');

        $command = new EntregarPedidoCommand(-1);
        $command->validate();
    }

    /** @test */
    public function command_invalido_falla_validacion_id_cero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del pedido debe ser un número positivo');

        $command = new EntregarPedidoCommand(0);
        $command->validate();
    }

    /** @test */
    public function puede_convertir_a_array()
    {
        $command = new EntregarPedidoCommand(123, 'Observación', 456);
        $array = $command->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('command_id', $array);
        $this->assertArrayHasKey('pedido_id', $array);
        $this->assertArrayHasKey('observaciones', $array);
        $this->assertArrayHasKey('usuario_id', $array);
        $this->assertArrayHasKey('ejecutado_en', $array);
        $this->assertArrayHasKey('tipo', $array);
        
        $this->assertEquals(123, $array['pedido_id']);
        $this->assertEquals('Observación', $array['observaciones']);
        $this->assertEquals(456, $array['usuario_id']);
        $this->assertEquals('entregar_pedido', $array['tipo']);
    }

    /** @test */
    public function command_id_es_unico()
    {
        $command1 = new EntregarPedidoCommand(123);
        $command2 = new EntregarPedidoCommand(124);

        $this->assertNotEquals($command1->getCommandId(), $command2->getCommandId());
    }

    /** @test */
    public function fecha_ejecucion_se_establece_automaticamente()
    {
        $antes = new \DateTime();
        $command = new EntregarPedidoCommand(123);
        $despues = new \DateTime();

        $this->assertGreaterThanOrEqual($antes, $command->getEjecutadoEn());
        $this->assertLessThanOrEqual($despues, $command->getEjecutadoEn());
    }
}
