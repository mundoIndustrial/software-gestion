<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RegistroOrdenQueryService;
use App\Models\PedidoProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistroOrdenQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RegistroOrdenQueryService();
    }

    /**
     * Prueba que el service puede obtener valores Ãºnicos de columna 'estado'
     */
    public function test_get_unique_values_returns_array_for_valid_column()
    {
        // Crear algunos pedidos de prueba
        PedidoProduccion::factory()->create(['estado' => 'No iniciado']);
        PedidoProduccion::factory()->create(['estado' => 'En EjecuciÃ³n']);
        PedidoProduccion::factory()->create(['estado' => 'En EjecuciÃ³n']);

        $values = $this->service->getUniqueValues('estado');

        $this->assertIsArray($values);
        $this->assertCount(2, $values);
        $this->assertContains('No iniciado', $values);
        $this->assertContains('En EjecuciÃ³n', $values);
    }

    /**
     * Prueba que lanza excepciÃ³n para columna invÃ¡lida
     */
    public function test_get_unique_values_throws_exception_for_invalid_column()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getUniqueValues('columna_inexistente');
    }

    /**
     * Prueba que puede obtener valores Ãºnicos de 'cliente'
     */
    public function test_get_unique_values_works_for_cliente_column()
    {
        PedidoProduccion::factory()->create(['cliente' => 'Cliente A']);
        PedidoProduccion::factory()->create(['cliente' => 'Cliente B']);
        PedidoProduccion::factory()->create(['cliente' => 'Cliente A']);

        $values = $this->service->getUniqueValues('cliente');

        $this->assertCount(2, $values);
        $this->assertContains('Cliente A', $values);
        $this->assertContains('Cliente B', $values);
    }

    /**
     * Prueba que filtra valores null y vacÃ­os
     */
    public function test_get_unique_values_filters_null_and_empty()
    {
        PedidoProduccion::factory()->create(['numero_pedido' => 'PED-001']);
        PedidoProduccion::factory()->create(['numero_pedido' => 'PED-002']);
        PedidoProduccion::factory()->create(['numero_pedido' => null]);
        PedidoProduccion::factory()->create(['numero_pedido' => '']);

        $values = $this->service->getUniqueValues('numero_pedido');

        $this->assertCount(2, $values);
        $this->assertNotContains(null, $values);
        $this->assertNotContains('', $values);
    }

    /**
     * Prueba que los valores estÃ¡n ordenados alfabÃ©ticamente
     */
    public function test_get_unique_values_returns_sorted_array()
    {
        PedidoProduccion::factory()->create(['estado' => 'Z - Completado']);
        PedidoProduccion::factory()->create(['estado' => 'A - No iniciado']);
        PedidoProduccion::factory()->create(['estado' => 'M - En EjecuciÃ³n']);

        $values = $this->service->getUniqueValues('estado');

        $this->assertEquals(['A - No iniciado', 'M - En EjecuciÃ³n', 'Z - Completado'], $values);
    }
}

