<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ProduccionCalculadoraService;

class ProduccionCalculadoraServiceTest extends TestCase
{
    private ProduccionCalculadoraService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProduccionCalculadoraService();
    }

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(ProduccionCalculadoraService::class, $this->service);
    }

    public function test_hereda_de_base_service(): void
    {
        $this->assertInstanceOf(\App\Services\BaseService::class, $this->service);
    }

    public function test_calcular_seguimiento_modulos_retorna_array(): void
    {
        $registros = collect([
            (object)[
                'modulo' => 'MOD1',
                'cantidad' => 100,
                'meta' => 100,
                'eficiencia' => 1.0,
                'hora' => (object)['hora' => '08:00']
            ]
        ]);

        $resultado = $this->service->calcularSeguimientoModulos($registros);
        $this->assertIsArray($resultado);
    }

    public function test_calcular_produccion_por_horas_retorna_array(): void
    {
        $registros = collect([
            (object)[
                'hora' => (object)['hora' => '08:00'],
                'cantidad' => 50,
                'meta' => 60,
                'operario' => (object)['name' => 'Juan']
            ]
        ]);

        $resultado = $this->service->calcularProduccionPorHoras($registros);
        $this->assertIsArray($resultado);
    }

    public function test_calcular_produccion_por_operarios_retorna_array(): void
    {
        $registros = collect([
            (object)[
                'operario' => (object)['name' => 'Juan'],
                'cantidad' => 80,
                'meta' => 100,
                'hora' => (object)['hora' => '08:00']
            ]
        ]);

        $resultado = $this->service->calcularProduccionPorOperarios($registros);
        $this->assertIsArray($resultado);
    }

    public function test_metodos_son_publicos(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->getMethod('calcularSeguimientoModulos')->isPublic());
        $this->assertTrue($reflection->getMethod('calcularProduccionPorHoras')->isPublic());
        $this->assertTrue($reflection->getMethod('calcularProduccionPorOperarios')->isPublic());
    }
}

