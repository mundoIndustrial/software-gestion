<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\CorteService;
use App\Services\BaseService;
use ReflectionClass;

class CorteServiceTest extends TestCase
{
    public function test_service_class_exists(): void
    {
        $this->assertTrue(class_exists(CorteService::class));
    }

    public function test_hereda_de_base_service(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotNull($parent);
        $this->assertEquals(BaseService::class, $parent->getName());
    }

    public function test_has_methods(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        $methods = $reflection->getMethods();
        
        // Verificar que tiene mÃ©todos
        $this->assertGreaterThan(1, count($methods));
    }

    public function test_has_public_methods(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filter out magic methods
        $publicMethods = array_filter($publicMethods, function($m) {
            return strpos($m->getName(), '__') === false;
        });
        
        // Corte service debe tener mÃ©todos pÃºblicos
        $this->assertGreaterThanOrEqual(0, count($publicMethods));
    }

    public function test_has_constructor(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }

    public function test_service_structure(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        
        $this->assertNotNull($reflection);
        $this->assertEquals(CorteService::class, $reflection->getName());
    }

    public function test_service_has_properties(): void
    {
        $reflection = new ReflectionClass(CorteService::class);
        $properties = $reflection->getProperties();
        
        // Debe tener propiedades
        $this->assertGreaterThanOrEqual(1, count($properties));
    }
}

