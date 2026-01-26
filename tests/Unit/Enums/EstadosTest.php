<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;

class EstadosTest extends TestCase
{
    /**
     * Test: EstadoCotizacion tiene 6 valores
     */
    public function test_estado_cotizacion_tiene_6_valores()
    {
        $estados = EstadoCotizacion::cases();
        $this->assertCount(6, $estados);
    }

    /**
     * Test: EstadoPedido tiene 4 valores
     */
    public function test_estado_pedido_tiene_4_valores()
    {
        $estados = EstadoPedido::cases();
        $this->assertCount(4, $estados);
    }

    /**
     * Test: Transición vÃ¡lida BORRADOR â†’ ENVIADA_CONTADOR
     */
    public function test_transicion_valida_borrador_a_enviada_contador()
    {
        $estado = EstadoCotizacion::BORRADOR;
        $this->assertTrue($estado->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR));
    }

    /**
     * Test: Transición invÃ¡lida BORRADOR â†’ FINALIZADA
     */
    public function test_transicion_invalida_borrador_a_finalizada()
    {
        $estado = EstadoCotizacion::BORRADOR;
        $this->assertFalse($estado->puedePasar(EstadoCotizacion::FINALIZADA));
    }

    /**
     * Test: Transición vÃ¡lida completa de cotización
     */
    public function test_transiciones_validas_completas_cotizacion()
    {
        $this->assertTrue(EstadoCotizacion::BORRADOR->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR));
        $this->assertTrue(EstadoCotizacion::ENVIADA_CONTADOR->puedePasar(EstadoCotizacion::APROBADA_CONTADOR));
        $this->assertTrue(EstadoCotizacion::APROBADA_CONTADOR->puedePasar(EstadoCotizacion::APROBADA_COTIZACIONES));
        $this->assertTrue(EstadoCotizacion::APROBADA_COTIZACIONES->puedePasar(EstadoCotizacion::CONVERTIDA_PEDIDO));
        $this->assertTrue(EstadoCotizacion::CONVERTIDA_PEDIDO->puedePasar(EstadoCotizacion::FINALIZADA));
    }

    /**
     * Test: Transiciones vÃ¡lidas completas de pedido
     */
    public function test_transiciones_validas_completas_pedido()
    {
        $this->assertTrue(EstadoPedido::PENDIENTE_SUPERVISOR->puedePasar(EstadoPedido::APROBADO_SUPERVISOR));
        $this->assertTrue(EstadoPedido::APROBADO_SUPERVISOR->puedePasar(EstadoPedido::EN_PRODUCCION));
        $this->assertTrue(EstadoPedido::EN_PRODUCCION->puedePasar(EstadoPedido::FINALIZADO));
    }

    /**
     * Test: Estados finales no tienen transiciones
     */
    public function test_estados_finales_sin_transiciones()
    {
        $this->assertEmpty(EstadoCotizacion::FINALIZADA->transicionesPermitidas());
        $this->assertEmpty(EstadoPedido::FINALIZADO->transicionesPermitidas());
    }

    /**
     * Test: Labels de cotización
     */
    public function test_labels_cotizacion()
    {
        $this->assertEquals('Borrador', EstadoCotizacion::BORRADOR->label());
        $this->assertEquals('Enviada a Contador', EstadoCotizacion::ENVIADA_CONTADOR->label());
        $this->assertEquals('Aprobada por Contador', EstadoCotizacion::APROBADA_CONTADOR->label());
        $this->assertEquals('Aprobada por Aprobador', EstadoCotizacion::APROBADA_COTIZACIONES->label());
        $this->assertEquals('Convertida a Pedido', EstadoCotizacion::CONVERTIDA_PEDIDO->label());
        $this->assertEquals('Finalizada', EstadoCotizacion::FINALIZADA->label());
    }

    /**
     * Test: Labels de pedido
     */
    public function test_labels_pedido()
    {
        $this->assertEquals('Pendiente de Supervisor', EstadoPedido::PENDIENTE_SUPERVISOR->label());
        $this->assertEquals('Aprobado por Supervisor', EstadoPedido::APROBADO_SUPERVISOR->label());
        $this->assertEquals('En Producción', EstadoPedido::EN_PRODUCCION->label());
        $this->assertEquals('Finalizado', EstadoPedido::FINALIZADO->label());
    }

    /**
     * Test: Colores de cotización
     */
    public function test_colores_cotizacion()
    {
        $this->assertEquals('gray', EstadoCotizacion::BORRADOR->color());
        $this->assertEquals('blue', EstadoCotizacion::ENVIADA_CONTADOR->color());
        $this->assertEquals('yellow', EstadoCotizacion::APROBADA_CONTADOR->color());
        $this->assertEquals('green', EstadoCotizacion::APROBADA_COTIZACIONES->color());
        $this->assertEquals('purple', EstadoCotizacion::CONVERTIDA_PEDIDO->color());
        $this->assertEquals('dark-green', EstadoCotizacion::FINALIZADA->color());
    }

    /**
     * Test: Colores de pedido
     */
    public function test_colores_pedido()
    {
        $this->assertEquals('blue', EstadoPedido::PENDIENTE_SUPERVISOR->color());
        $this->assertEquals('yellow', EstadoPedido::APROBADO_SUPERVISOR->color());
        $this->assertEquals('orange', EstadoPedido::EN_PRODUCCION->color());
        $this->assertEquals('green', EstadoPedido::FINALIZADO->color());
    }

    /**
     * Test: Iconos de cotización
     */
    public function test_iconos_cotizacion()
    {
        $this->assertNotEmpty(EstadoCotizacion::BORRADOR->icon());
        $this->assertNotEmpty(EstadoCotizacion::ENVIADA_CONTADOR->icon());
        $this->assertNotEmpty(EstadoCotizacion::APROBADA_CONTADOR->icon());
        $this->assertNotEmpty(EstadoCotizacion::APROBADA_COTIZACIONES->icon());
        $this->assertNotEmpty(EstadoCotizacion::CONVERTIDA_PEDIDO->icon());
        $this->assertNotEmpty(EstadoCotizacion::FINALIZADA->icon());
    }

    /**
     * Test: Iconos de pedido
     */
    public function test_iconos_pedido()
    {
        $this->assertNotEmpty(EstadoPedido::PENDIENTE_SUPERVISOR->icon());
        $this->assertNotEmpty(EstadoPedido::APROBADO_SUPERVISOR->icon());
        $this->assertNotEmpty(EstadoPedido::EN_PRODUCCION->icon());
        $this->assertNotEmpty(EstadoPedido::FINALIZADO->icon());
    }

    /**
     * Test: Enum from string
     */
    public function test_enum_from_string_cotizacion()
    {
        $estado = EstadoCotizacion::tryFrom('BORRADOR');
        $this->assertNotNull($estado);
        $this->assertEquals(EstadoCotizacion::BORRADOR, $estado);
    }

    /**
     * Test: Enum from invalid string
     */
    public function test_enum_from_invalid_string()
    {
        $estado = EstadoCotizacion::tryFrom('ESTADO_INVALIDO');
        $this->assertNull($estado);
    }
}

