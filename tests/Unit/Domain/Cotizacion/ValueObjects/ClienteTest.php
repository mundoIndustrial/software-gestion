<?php

namespace Tests\Unit\Domain\Cotizacion\ValueObjects;

use App\Domain\Cotizacion\ValueObjects\Cliente;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ClienteTest extends TestCase
{
    /**
     * @test
     */
    public function puede_crear_cliente_valido(): void
    {
        $cliente = Cliente::crear('Acme Corporation');

        $this->assertInstanceOf(Cliente::class, $cliente);
        $this->assertEquals('Acme Corporation', $cliente->valor());
    }

    /**
     * @test
     */
    public function trimea_espacios_en_blanco(): void
    {
        $cliente = Cliente::crear('  Acme Corporation  ');

        $this->assertEquals('Acme Corporation', $cliente->valor());
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_esta_vacio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del cliente no puede estar vacío');

        Cliente::crear('');
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_solo_espacios(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del cliente no puede estar vacío');

        Cliente::crear('   ');
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_excede_255_caracteres(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del cliente no puede exceder 255 caracteres');

        $nombreLargo = str_repeat('A', 256);
        Cliente::crear($nombreLargo);
    }

    /**
     * @test
     */
    public function acepta_255_caracteres(): void
    {
        $nombre255 = str_repeat('A', 255);
        $cliente = Cliente::crear($nombre255);

        $this->assertEquals($nombre255, $cliente->valor());
    }

    /**
     * @test
     */
    public function compara_clientes_iguales(): void
    {
        $cliente1 = Cliente::crear('Acme Corporation');
        $cliente2 = Cliente::crear('Acme Corporation');

        $this->assertTrue($cliente1->equals($cliente2));
    }

    /**
     * @test
     */
    public function compara_clientes_diferentes(): void
    {
        $cliente1 = Cliente::crear('Acme Corporation');
        $cliente2 = Cliente::crear('Beta Inc');

        $this->assertFalse($cliente1->equals($cliente2));
    }

    /**
     * @test
     */
    public function convierte_a_string(): void
    {
        $cliente = Cliente::crear('Acme Corporation');

        $this->assertEquals('Acme Corporation', (string) $cliente);
    }

    /**
     * @test
     */
    public function es_inmutable(): void
    {
        $cliente = Cliente::crear('Acme Corporation');

        // Intentar cambiar la propiedad (debería fallar en PHP 8.2+)
        $this->expectException(\Error::class);
        $cliente->valor = 'Nueva Empresa';
    }
}
