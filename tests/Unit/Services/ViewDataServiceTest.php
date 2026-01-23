<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ViewDataService;
use App\Services\BaseService;
use ReflectionClass;

class ViewDataServiceTest extends TestCase
{
    public function test_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ViewDataService::class));
    }

    public function test_hereda_de_base_service(): void
    {
        $reflection = new ReflectionClass(ViewDataService::class);
        $parent = $reflection->getParentClass();
        
        $this->assertNotNull($parent);
        $this->assertEquals(BaseService::class, $parent->getName());
    }

    public function test_has_public_method_exists(): void
    {
        $reflection = new ReflectionClass(ViewDataService::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $methodNames = array_map(function($m) { return $m->getName(); }, $publicMethods);
        $nonMagicMethods = array_filter($methodNames, function($n) {
            return strpos($n, '__') === false;
        });
        
        $this->assertGreaterThan(0, count($nonMagicMethods));
    }

    public function test_has_constructor(): void
    {
        $reflection = new ReflectionClass(ViewDataService::class);
        $this->assertTrue($reflection->hasMethod('__construct'));
    }

    public function test_service_has_public_methods(): void
    {
        $reflection = new ReflectionClass(ViewDataService::class);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filter out magic methods
        $publicMethods = array_filter($publicMethods, function($m) {
            return strpos($m->getName(), '__') === false;
        });
        
        $this->assertGreaterThan(0, count($publicMethods));
    }

    public function test_service_structure(): void
    {
        $reflection = new ReflectionClass(ViewDataService::class);
        
        $this->assertNotNull($reflection);
        $this->assertEquals(ViewDataService::class, $reflection->getName());
    }
}

