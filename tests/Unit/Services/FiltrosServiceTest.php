<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FiltrosService;

class FiltrosServiceTest extends TestCase
{
    private FiltrosService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FiltrosService();
    }

    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(FiltrosService::class, $this->service);
    }

    public function test_hereda_de_base_service(): void
    {
        $this->assertInstanceOf(\App\Services\BaseService::class, $this->service);
    }

    public function test_tiene_metodo_filtrar(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $this->assertTrue($reflection->hasMethod('filtrarRegistrosPorFecha'));
        $this->assertTrue($reflection->getMethod('filtrarRegistrosPorFecha')->isPublic());
    }

    public function test_filtrar_con_coleccion_vacia(): void
    {
        $registros = collect([]);
        $request = $this->createMock(\Illuminate\Http\Request::class);

        $resultado = $this->service->filtrarRegistrosPorFecha($registros, $request);
        $this->assertIsObject($resultado);
    }

    public function test_filtrar_preserva_estructura_collection(): void
    {
        $registros = collect([
            (object)['id' => 1, 'fecha' => now(), 'cantidad' => 100]
        ]);
        
        $request = $this->createMock(\Illuminate\Http\Request::class);
        $resultado = $this->service->filtrarRegistrosPorFecha($registros, $request);
        
        $this->assertTrue(method_exists($resultado, 'toArray'));
    }

    public function test_metodos_son_publicos(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('filtrarRegistrosPorFecha');
        $this->assertTrue($method->isPublic());
    }
}

