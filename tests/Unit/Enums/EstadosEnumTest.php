<?php

namespace Tests\Unit\Enums;

use Tests\TestCase;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;

class EstadosEnumTest extends TestCase
{
    /** @test */
    public function enum_cotizacion_tiene_todos_los_estados()
    {
        $this->assertNotNull(EstadoCotizacion::BORRADOR);
        $this->assertNotNull(EstadoCotizacion::ENVIADA_CONTADOR);
        $this->assertNotNull(EstadoCotizacion::APROBADA_CONTADOR);
        $this->assertNotNull(EstadoCotizacion::APROBADA_COTIZACIONES);
        $this->assertNotNull(EstadoCotizacion::CONVERTIDA_PEDIDO);
        $this->assertNotNull(EstadoCotizacion::FINALIZADA);
    }

    /** @test */
    public function enum_pedido_tiene_todos_los_estados()
    {
        $this->assertNotNull(EstadoPedido::PENDIENTE_SUPERVISOR);
        $this->assertNotNull(EstadoPedido::APROBADO_SUPERVISOR);
        $this->assertNotNull(EstadoPedido::EN_PRODUCCION);
        $this->assertNotNull(EstadoPedido::FINALIZADO);
    }

    /** @test */
    public function cotizacion_label_correcto()
    {
        $this->assertEquals('Borrador', EstadoCotizacion::BORRADOR->label());
        $this->assertEquals('Enviada a Contador', EstadoCotizacion::ENVIADA_CONTADOR->label());
        $this->assertEquals('Aprobada por Contador', EstadoCotizacion::APROBADA_CONTADOR->label());
        $this->assertEquals('Aprobada por Aprobador', EstadoCotizacion::APROBADA_COTIZACIONES->label());
        $this->assertEquals('Convertida a Pedido', EstadoCotizacion::CONVERTIDA_PEDIDO->label());
        $this->assertEquals('Finalizada', EstadoCotizacion::FINALIZADA->label());
    }

    /** @test */
    public function pedido_label_correcto()
    {
        $this->assertEquals('Pendiente de Supervisor', EstadoPedido::PENDIENTE_SUPERVISOR->label());
        $this->assertEquals('Aprobado por Supervisor', EstadoPedido::APROBADO_SUPERVISOR->label());
        $this->assertEquals('En Producción', EstadoPedido::EN_PRODUCCION->label());
        $this->assertEquals('Finalizado', EstadoPedido::FINALIZADO->label());
    }

    /** @test */
    public function cotizacion_color_asignado()
    {
        $this->assertNotEmpty(EstadoCotizacion::BORRADOR->color());
        $this->assertNotEmpty(EstadoCotizacion::ENVIADA_CONTADOR->color());
        $this->assertNotEmpty(EstadoCotizacion::APROBADA_COTIZACIONES->color());
    }

    /** @test */
    public function pedido_color_asignado()
    {
        $this->assertNotEmpty(EstadoPedido::PENDIENTE_SUPERVISOR->color());
        $this->assertNotEmpty(EstadoPedido::EN_PRODUCCION->color());
    }

    /** @test */
    public function cotizacion_icon_asignado()
    {
        $this->assertNotEmpty(EstadoCotizacion::BORRADOR->icon());
        $this->assertNotEmpty(EstadoCotizacion::ENVIADA_CONTADOR->icon());
        $this->assertNotEmpty(EstadoCotizacion::APROBADA_COTIZACIONES->icon());
    }

    /** @test */
    public function pedido_icon_asignado()
    {
        $this->assertNotEmpty(EstadoPedido::PENDIENTE_SUPERVISOR->icon());
        $this->assertNotEmpty(EstadoPedido::EN_PRODUCCION->icon());
    }

    /** @test */
    public function cotizacion_transiciones_correctas()
    {
        // BORRADOR -> ENVIADA_CONTADOR (permitido)
        $this->assertTrue(EstadoCotizacion::BORRADOR->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR));

        // BORRADOR -> FINALIZADA (no permitido)
        $this->assertFalse(EstadoCotizacion::BORRADOR->puedePasar(EstadoCotizacion::FINALIZADA));

        // ENVIADA_CONTADOR -> APROBADA_CONTADOR (permitido)
        $this->assertTrue(EstadoCotizacion::ENVIADA_CONTADOR->puedePasar(EstadoCotizacion::APROBADA_CONTADOR));

        // APROBADA_COTIZACIONES -> CONVERTIDA_PEDIDO (permitido)
        $this->assertTrue(EstadoCotizacion::APROBADA_COTIZACIONES->puedePasar(EstadoCotizacion::CONVERTIDA_PEDIDO));

        // FINALIZADA -> BORRADOR (no permitido)
        $this->assertFalse(EstadoCotizacion::FINALIZADA->puedePasar(EstadoCotizacion::BORRADOR));
    }

    /** @test */
    public function pedido_transiciones_correctas()
    {
        // PENDIENTE_SUPERVISOR -> APROBADO_SUPERVISOR (permitido)
        $this->assertTrue(EstadoPedido::PENDIENTE_SUPERVISOR->puedePasar(EstadoPedido::APROBADO_SUPERVISOR));

        // PENDIENTE_SUPERVISOR -> FINALIZADO (no permitido)
        $this->assertFalse(EstadoPedido::PENDIENTE_SUPERVISOR->puedePasar(EstadoPedido::FINALIZADO));

        // APROBADO_SUPERVISOR -> EN_PRODUCCION (permitido)
        $this->assertTrue(EstadoPedido::APROBADO_SUPERVISOR->puedePasar(EstadoPedido::EN_PRODUCCION));

        // EN_PRODUCCION -> FINALIZADO (permitido)
        $this->assertTrue(EstadoPedido::EN_PRODUCCION->puedePasar(EstadoPedido::FINALIZADO));

        // FINALIZADO -> EN_PRODUCCION (no permitido)
        $this->assertFalse(EstadoPedido::FINALIZADO->puedePasar(EstadoPedido::EN_PRODUCCION));
    }

    /** @test */
    public function cotizacion_obtiene_transiciones_permitidas()
    {
        $transiciones = EstadoCotizacion::BORRADOR->transicionesPermitidas();

        $this->assertIsArray($transiciones);
        $this->assertContains(EstadoCotizacion::ENVIADA_CONTADOR->value, $transiciones);
        $this->assertNotContains(EstadoCotizacion::FINALIZADA->value, $transiciones);
    }

    /** @test */
    public function pedido_obtiene_transiciones_permitidas()
    {
        $transiciones = EstadoPedido::PENDIENTE_SUPERVISOR->transicionesPermitidas();

        $this->assertIsArray($transiciones);
        $this->assertContains(EstadoPedido::APROBADO_SUPERVISOR->value, $transiciones);
        $this->assertNotContains(EstadoPedido::FINALIZADO->value, $transiciones);
    }
}

