<?php

namespace Tests\Unit\Domain\Cotizacion\ValueObjects;

use App\Domain\Cotizacion\ValueObjects\NumeroCotizacion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NumeroCotizacionTest extends TestCase
{
    /**
     * @test
     */
    public function puede_crear_numero_valido(): void
    {
        $numero = NumeroCotizacion::crear('COT-00014');

        $this->assertInstanceOf(NumeroCotizacion::class, $numero);
        $this->assertEquals('COT-00014', $numero->valor());
        $this->assertTrue($numero->tieneNumero());
    }

    /**
     * @test
     */
    public function puede_crear_numero_vacio(): void
    {
        $numero = NumeroCotizacion::vacio();

        $this->assertNull($numero->valor());
        $this->assertFalse($numero->tieneNumero());
        $this->assertTrue($numero->estaVacio());
    }

    /**
     * @test
     */
    public function puede_generar_numero_secuencial(): void
    {
        $numero1 = NumeroCotizacion::generar(1);
        $numero2 = NumeroCotizacion::generar(14);
        $numero3 = NumeroCotizacion::generar(999);

        $this->assertEquals('COT-00001', $numero1->valor());
        $this->assertEquals('COT-00014', $numero2->valor());
        $this->assertEquals('COT-00999', $numero3->valor());
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_formato_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El número de cotización debe tener el formato COT-XXXXX');

        NumeroCotizacion::crear('INVALID-00014');
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_numero_muy_corto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NumeroCotizacion::crear('COT-001');
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_numero_muy_largo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NumeroCotizacion::crear('COT-000001');
    }

    /**
     * @test
     */
    public function lanza_excepcion_si_contiene_letras(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NumeroCotizacion::crear('COT-0001A');
    }

    /**
     * @test
     */
    public function compara_numeros_iguales(): void
    {
        $numero1 = NumeroCotizacion::crear('COT-00014');
        $numero2 = NumeroCotizacion::crear('COT-00014');

        $this->assertTrue($numero1->equals($numero2));
    }

    /**
     * @test
     */
    public function compara_numeros_diferentes(): void
    {
        $numero1 = NumeroCotizacion::crear('COT-00014');
        $numero2 = NumeroCotizacion::crear('COT-00015');

        $this->assertFalse($numero1->equals($numero2));
    }

    /**
     * @test
     */
    public function compara_vacio_con_numero(): void
    {
        $vacio = NumeroCotizacion::vacio();
        $numero = NumeroCotizacion::crear('COT-00014');

        $this->assertFalse($vacio->equals($numero));
    }

    /**
     * @test
     */
    public function convierte_a_string(): void
    {
        $numero = NumeroCotizacion::crear('COT-00014');
        $vacio = NumeroCotizacion::vacio();

        $this->assertEquals('COT-00014', (string) $numero);
        $this->assertEquals('', (string) $vacio);
    }

    /**
     * @test
     */
    public function acepta_null_en_crear(): void
    {
        $numero = NumeroCotizacion::crear(null);

        $this->assertNull($numero->valor());
        $this->assertTrue($numero->estaVacio());
    }
}
