<?php

namespace Tests\Unit\Application\Bodega\CQRS;

use Tests\TestCase;
use App\Application\Bodega\CQRS\Queries\ObtenerPedidosPorAreaQuery;
use App\Domain\Bodega\ValueObjects\AreaBodega;

class ObtenerPedidosPorAreaQueryTest extends TestCase
{
    /** @test */
    public function puede_crear_query_valida()
    {
        $area = AreaBodega::costura();
        $filtros = ['cliente' => 'Test Cliente'];
        $query = new ObtenerPedidosPorAreaQuery($area, $filtros, 2, 25);

        $this->assertEquals($area, $query->getArea());
        $this->assertEquals($filtros, $query->getFiltros());
        $this->assertEquals(2, $query->getPagina());
        $this->assertEquals(25, $query->getPorPagina());
        $this->assertNotEmpty($query->getQueryId());
    }

    /** @test */
    public function puede_crear_query_con_parametros_por_defecto()
    {
        $area = AreaBodega::epp();
        $query = new ObtenerPedidosPorAreaQuery($area);

        $this->assertEquals($area, $query->getArea());
        $this->assertEquals([], $query->getFiltros());
        $this->assertEquals(1, $query->getPagina());
        $this->assertEquals(20, $query->getPorPagina());
    }

    /** @test */
    public function query_valida_pasa_validacion()
    {
        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, [], 1, 10);
        
        // No debe lanzar excepción
        $query->validate();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function query_invalida_falla_validacion_pagina_cero()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La página debe ser mayor a 0');

        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, [], 0, 10);
        $query->validate();
    }

    /** @test */
    public function query_invalida_falla_validacion_pagina_negativa()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La página debe ser mayor a 0');

        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, [], -1, 10);
        $query->validate();
    }

    /** @test */
    public function query_invalida_falla_validacion_por_pagina_menor_a_1()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El por página debe estar entre 1 y 100');

        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, [], 1, 0);
        $query->validate();
    }

    /** @test */
    public function query_invalida_falla_validacion_por_pagina_mayor_a_100()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El por página debe estar entre 1 y 100');

        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, [], 1, 101);
        $query->validate();
    }

    /** @test */
    public function query_invalida_falla_validacion_filtro_no_valido()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filtro no válido: filtro_invalido');

        $area = AreaBodega::costura();
        $query = new ObtenerPedidosPorAreaQuery($area, ['filtro_invalido' => 'valor']);
        $query->validate();
    }

    /** @test */
    public function puede_obtener_parametros()
    {
        $area = AreaBodega::costura();
        $filtros = ['cliente' => 'Test', 'asesor' => 'Juan'];
        $query = new ObtenerPedidosPorAreaQuery($area, $filtros, 2, 15);

        $parametros = $query->getParameters();

        $this->assertIsArray($parametros);
        $this->assertEquals('Costura', $parametros['area']);
        $this->assertEquals($filtros, $parametros['filtros']);
        $this->assertEquals(2, $parametros['pagina']);
        $this->assertEquals(15, $parametros['por_pagina']);
    }

    /** @test */
    public function puede_crear_query_con_diferente_paginacion()
    {
        $area = AreaBodega::epp();
        $queryOriginal = new ObtenerPedidosPorAreaQuery($area, [], 1, 10);
        
        $queryNueva = $queryOriginal->conPaginacion(3, 25);

        $this->assertEquals($area, $queryNueva->getArea());
        $this->assertEquals(3, $queryNueva->getPagina());
        $this->assertEquals(25, $queryNueva->getPorPagina());
        $this->assertNotEquals($queryOriginal->getQueryId(), $queryNueva->getQueryId());
    }

    /** @test */
    public function puede_crear_query_con_filtros_adicionales()
    {
        $area = AreaBodega::costura();
        $queryOriginal = new ObtenerPedidosPorAreaQuery($area, ['cliente' => 'Test']);
        
        $queryNueva = $queryOriginal->conFiltros(['asesor' => 'Juan', 'solo_retrasados' => true]);

        $this->assertEquals(['cliente' => 'Test', 'asesor' => 'Juan', 'solo_retrasados' => true], $queryNueva->getFiltros());
        $this->assertNotEquals($queryOriginal->getQueryId(), $queryNueva->getQueryId());
    }

    /** @test */
    public function query_id_es_deterministico_con_mismos_parametros()
    {
        $area = AreaBodega::costura();
        $filtros = ['cliente' => 'Test'];
        
        $query1 = new ObtenerPedidosPorAreaQuery($area, $filtros, 1, 10);
        $query2 = new ObtenerPedidosPorAreaQuery($area, $filtros, 1, 10);
        
        $this->assertEquals($query1->getQueryId(), $query2->getQueryId());
    }

    /** @test */
    public function query_id_es_diferente_con_parametros_diferentes()
    {
        $area = AreaBodega::costura();
        
        $query1 = new ObtenerPedidosPorAreaQuery($area, ['cliente' => 'Test1'], 1, 10);
        $query2 = new ObtenerPedidosPorAreaQuery($area, ['cliente' => 'Test2'], 1, 10);
        
        $this->assertNotEquals($query1->getQueryId(), $query2->getQueryId());
    }
}
