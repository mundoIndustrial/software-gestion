<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UpdateService;

class UpdateServiceTest extends TestCase
{
    private UpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UpdateService();
    }

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(UpdateService::class, $this->service);
    }

    public function test_hereda_de_base_service(): void
    {
        $this->assertInstanceOf(\App\Services\BaseService::class, $this->service);
    }

    public function test_tiene_metodo_update(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('update'));
        $this->assertTrue($reflection->getMethod('update')->isPublic());
    }

    public function test_metodo_update_es_publico(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('update');
        $this->assertTrue($method->isPublic());
    }

    public function test_servicio_puede_acceder_propiedades_privadas(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Verificar que tiene métodos privados para validación y recálculo
        $this->assertTrue($reflection->hasMethod('getUpdateValidationRules'));
        $this->assertTrue($reflection->hasMethod('shouldRecalculate'));
    }
}
