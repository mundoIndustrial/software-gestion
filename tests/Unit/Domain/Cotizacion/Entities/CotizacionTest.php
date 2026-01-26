<?php

namespace Tests\Unit\Domain\Cotizacion\Entities;

use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\Entities\PrendaCotizacion;
use App\Domain\Cotizacion\ValueObjects\{
    Asesora,
    Cliente,
    EstadoCotizacion,
    TipoCotizacion
};
use App\Domain\Shared\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

class CotizacionTest extends TestCase
{
    private UserId $usuarioId;
    private Cliente $cliente;
    private Asesora $asesora;

    protected function setUp(): void
    {
        $this->usuarioId = UserId::crear(1);
        $this->cliente = Cliente::crear('Acme Corp');
        $this->asesora = Asesora::crear('MarÃ­a GarcÃ­a');
    }

    /**
     * @test
     */
    public function puede_crear_borrador(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $this->assertTrue($cotizacion->esBorrador());
        $this->assertTrue($cotizacion->numero()->estaVacio());
        $this->assertEquals(EstadoCotizacion::BORRADOR, $cotizacion->estado());
    }

    /**
     * @test
     */
    public function puede_crear_enviada(): void
    {
        $cotizacion = Cotizacion::crearEnviada(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora,
            14
        );

        $this->assertFalse($cotizacion->esBorrador());
        $this->assertEquals('COT-00014', $cotizacion->numero()->valor());
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR, $cotizacion->estado());
        $this->assertNotNull($cotizacion->fechaEnvio());
    }

    /**
     * @test
     */
    public function puede_agregar_prenda(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $prenda = PrendaCotizacion::crear('Camiseta', 'Camiseta de algodón', 100);
        $cotizacion->agregarPrenda($prenda);

        $this->assertCount(1, $cotizacion->prendas());
    }

    /**
     * @test
     */
    public function verifica_propietario(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $otroUsuario = UserId::crear(999);

        $this->assertTrue($cotizacion->esPropietarioDe($this->usuarioId));
        $this->assertFalse($cotizacion->esPropietarioDe($otroUsuario));
    }

    /**
     * @test
     */
    public function puede_cambiar_estado(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $cotizacion->cambiarEstado(EstadoCotizacion::ENVIADA_CONTADOR);

        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR, $cotizacion->estado());
    }

    /**
     * @test
     */
    public function lanza_excepcion_transicion_invalida(): void
    {
        $this->expectException(\DomainException::class);

        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        // No se puede ir directamente de BORRADOR a ACEPTADA
        $cotizacion->cambiarEstado(EstadoCotizacion::ACEPTADA);
    }

    /**
     * @test
     */
    public function puede_ser_eliminada_si_es_borrador(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $this->assertTrue($cotizacion->puedeSerEliminada());
    }

    /**
     * @test
     */
    public function no_puede_ser_eliminada_si_no_es_borrador(): void
    {
        $cotizacion = Cotizacion::crearEnviada(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora,
            14
        );

        $this->assertFalse($cotizacion->puedeSerEliminada());
    }

    /**
     * @test
     */
    public function registra_eventos(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $cotizacion->cambiarEstado(EstadoCotizacion::ENVIADA_CONTADOR);

        $eventos = $cotizacion->eventos();
        $this->assertCount(1, $eventos);
        $this->assertEquals('EstadoCambiado', $eventos[0]['tipo']);
    }

    /**
     * @test
     */
    public function puede_limpiar_eventos(): void
    {
        $cotizacion = Cotizacion::crearBorrador(
            $this->usuarioId,
            TipoCotizacion::PRENDA,
            $this->cliente,
            $this->asesora
        );

        $cotizacion->cambiarEstado(EstadoCotizacion::ENVIADA_CONTADOR);
        $this->assertCount(1, $cotizacion->eventos());

        $cotizacion->limpiarEventos();
        $this->assertCount(0, $cotizacion->eventos());
    }
}

