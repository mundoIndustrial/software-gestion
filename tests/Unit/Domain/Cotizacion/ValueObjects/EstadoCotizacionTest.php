<?php

namespace Tests\Unit\Domain\Cotizacion\ValueObjects;

use App\Domain\Cotizacion\ValueObjects\EstadoCotizacion;
use PHPUnit\Framework\TestCase;

class EstadoCotizacionTest extends TestCase
{
    /**
     * @test
     */
    public function puede_crear_todos_los_estados(): void
    {
        $estados = [
            EstadoCotizacion::BORRADOR,
            EstadoCotizacion::ENVIADA_CONTADOR,
            EstadoCotizacion::APROBADA_CONTADOR,
            EstadoCotizacion::ENVIADA_APROBADOR,
            EstadoCotizacion::APROBADA_APROBADOR,
            EstadoCotizacion::ACEPTADA,
            EstadoCotizacion::RECHAZADA,
        ];

        foreach ($estados as $estado) {
            $this->assertInstanceOf(EstadoCotizacion::class, $estado);
        }
    }

    /**
     * @test
     */
    public function obtiene_label_correcto(): void
    {
        $this->assertEquals('Borrador', EstadoCotizacion::BORRADOR->label());
        $this->assertEquals('Enviada a Contador', EstadoCotizacion::ENVIADA_CONTADOR->label());
        $this->assertEquals('Aceptada', EstadoCotizacion::ACEPTADA->label());
        $this->assertEquals('Rechazada', EstadoCotizacion::RECHAZADA->label());
    }

    /**
     * @test
     */
    public function identifica_estados_finales(): void
    {
        $this->assertTrue(EstadoCotizacion::ACEPTADA->esEstadoFinal());
        $this->assertTrue(EstadoCotizacion::RECHAZADA->esEstadoFinal());
        $this->assertFalse(EstadoCotizacion::BORRADOR->esEstadoFinal());
        $this->assertFalse(EstadoCotizacion::ENVIADA_CONTADOR->esEstadoFinal());
    }

    /**
     * @test
     */
    public function identifica_borrador(): void
    {
        $this->assertTrue(EstadoCotizacion::BORRADOR->esBorrador());
        $this->assertFalse(EstadoCotizacion::ENVIADA_CONTADOR->esBorrador());
        $this->assertFalse(EstadoCotizacion::ACEPTADA->esBorrador());
    }

    /**
     * @test
     */
    public function obtiene_siguientes_estados_posibles(): void
    {
        $siguientes = EstadoCotizacion::BORRADOR->siguientesEstadosPosibles();
        $this->assertContains(EstadoCotizacion::ENVIADA_CONTADOR, $siguientes);
        $this->assertContains(EstadoCotizacion::RECHAZADA, $siguientes);
        $this->assertCount(2, $siguientes);
    }

    /**
     * @test
     */
    public function verifica_transicion_valida(): void
    {
        $this->assertTrue(
            EstadoCotizacion::BORRADOR->puedeTransicionarA(EstadoCotizacion::ENVIADA_CONTADOR)
        );
        $this->assertTrue(
            EstadoCotizacion::BORRADOR->puedeTransicionarA(EstadoCotizacion::RECHAZADA)
        );
        $this->assertFalse(
            EstadoCotizacion::BORRADOR->puedeTransicionarA(EstadoCotizacion::ACEPTADA)
        );
    }

    /**
     * @test
     */
    public function verifica_transicion_invalida(): void
    {
        $this->assertFalse(
            EstadoCotizacion::ACEPTADA->puedeTransicionarA(EstadoCotizacion::BORRADOR)
        );
        $this->assertFalse(
            EstadoCotizacion::RECHAZADA->puedeTransicionarA(EstadoCotizacion::ACEPTADA)
        );
    }

    /**
     * @test
     */
    public function identifica_estados_que_requieren_aprobacion(): void
    {
        $this->assertTrue(EstadoCotizacion::ENVIADA_CONTADOR->requiereAprobacion());
        $this->assertTrue(EstadoCotizacion::ENVIADA_APROBADOR->requiereAprobacion());
        $this->assertFalse(EstadoCotizacion::BORRADOR->requiereAprobacion());
        $this->assertFalse(EstadoCotizacion::ACEPTADA->requiereAprobacion());
    }

    /**
     * @test
     */
    public function obtiene_color_ui_correcto(): void
    {
        $this->assertEquals('secondary', EstadoCotizacion::BORRADOR->colorUI());
        $this->assertEquals('info', EstadoCotizacion::ENVIADA_CONTADOR->colorUI());
        $this->assertEquals('success', EstadoCotizacion::ACEPTADA->colorUI());
        $this->assertEquals('danger', EstadoCotizacion::RECHAZADA->colorUI());
    }

    /**
     * @test
     */
    public function flujo_completo_de_aprobacion(): void
    {
        $estado = EstadoCotizacion::BORRADOR;

        // Borrador -> Enviada a Contador
        $this->assertTrue($estado->puedeTransicionarA(EstadoCotizacion::ENVIADA_CONTADOR));
        $estado = EstadoCotizacion::ENVIADA_CONTADOR;

        // Enviada a Contador -> Aprobada por Contador
        $this->assertTrue($estado->puedeTransicionarA(EstadoCotizacion::APROBADA_CONTADOR));
        $estado = EstadoCotizacion::APROBADA_CONTADOR;

        // Aprobada por Contador -> Enviada a Aprobador
        $this->assertTrue($estado->puedeTransicionarA(EstadoCotizacion::ENVIADA_APROBADOR));
        $estado = EstadoCotizacion::ENVIADA_APROBADOR;

        // Enviada a Aprobador -> Aprobada por Aprobador
        $this->assertTrue($estado->puedeTransicionarA(EstadoCotizacion::APROBADA_APROBADOR));
        $estado = EstadoCotizacion::APROBADA_APROBADOR;

        // Aprobada por Aprobador -> Aceptada
        $this->assertTrue($estado->puedeTransicionarA(EstadoCotizacion::ACEPTADA));
        $estado = EstadoCotizacion::ACEPTADA;

        // Aceptada es estado final
        $this->assertTrue($estado->esEstadoFinal());
        $this->assertEmpty($estado->siguientesEstadosPosibles());
    }
}

