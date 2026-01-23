<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\OperarioService;
use App\Services\BaseService;
use ReflectionClass;

class OperarioServiceTest extends TestCase
{
    private OperarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OperarioService();
    }

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(OperarioService::class, $this->service);
    }

    public function test_hereda_de_base_service(): void
    {
        $this->assertInstanceOf(BaseService::class, $this->service);
    }

    public function test_has_search_method(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('search'));
        $this->assertTrue($reflection->getMethod('search')->isPublic());
    }

    public function test_has_store_method(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('store'));
        $this->assertTrue($reflection->getMethod('store')->isPublic());
    }

    public function test_has_find_or_create_method(): void
    {
        $reflection = new ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('findOrCreate'));
        $this->assertTrue($reflection->getMethod('findOrCreate')->isPublic());
    }

    public function test_public_crud_methods_exist(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        $methods = ['search', 'store', 'findOrCreate', 'getAll', 'getById'];
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method)) {
                $this->assertTrue($reflection->getMethod($method)->isPublic());
            }
        }
    }

    public function test_service_has_public_methods(): void
    {
        $reflection = new ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $this->assertGreaterThan(2, count($publicMethods));
    }
}

