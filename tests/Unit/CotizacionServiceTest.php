<?php

namespace Tests\Unit;

use App\Services\CotizacionService;
use Tests\TestCase;

class CotizacionServiceTest extends TestCase
{
    protected CotizacionService $cotizacionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cotizacionService = new CotizacionService();
    }

    /**
     * Test: Determinar tipo de cotizaciÃ³n Prenda/Logo
     */
    public function test_determinar_tipo_prenda_logo()
    {
        $datos = [
            'productos' => [
                ['nombre_producto' => 'Camiseta']
            ],
            'tecnicas' => ['Bordado'],
            'imagenes' => [],
            'observaciones_tecnicas' => 'Bordado en pecho',
            'ubicaciones' => [['seccion' => 'Pecho']],
            'observaciones_generales' => []
        ];

        // Usar reflexiÃ³n para acceder al mÃ©todo privado
        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('Prenda/Logo', $resultado);
        echo "\n Test Prenda/Logo PASÃ“\n";
    }

    /**
     * Test: Determinar tipo de cotizaciÃ³n Solo Logo
     */
    public function test_determinar_tipo_logo()
    {
        $datos = [
            'productos' => [], // SIN prendas
            'tecnicas' => ['Bordado'],
            'imagenes' => [],
            'observaciones_tecnicas' => 'Logo en pecho',
            'ubicaciones' => [['seccion' => 'Pecho']],
            'observaciones_generales' => []
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('Solo Logo', $resultado);
        echo "\n Test Solo Logo PASÃ“\n";
    }

    /**
     * Test: Determinar tipo de cotizaciÃ³n General (solo prendas)
     */
    public function test_determinar_tipo_general()
    {
        $datos = [
            'productos' => [
                ['nombre_producto' => 'PantalÃ³n']
            ],
            'tecnicas' => [], // SIN tÃ©cnicas
            'imagenes' => [],
            'observaciones_tecnicas' => null,
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('General', $resultado);
        echo "\n Test General PASÃ“\n";
    }

    /**
     * Test: Determinar tipo de cotizaciÃ³n con imagenes
     */
    public function test_determinar_tipo_con_imagenes()
    {
        $datos = [
            'productos' => [], // SIN prendas
            'tecnicas' => [],
            'imagenes' => ['imagen1.jpg'], // CON imÃ¡genes
            'observaciones_tecnicas' => null,
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('Solo Logo', $resultado);
        echo "\n Test Con ImÃ¡genes PASÃ“\n";
    }

    /**
     * Test: Determinar tipo de cotizaciÃ³n con observaciones generales
     */
    public function test_determinar_tipo_con_observaciones()
    {
        $datos = [
            'productos' => [], // SIN prendas
            'tecnicas' => [],
            'imagenes' => [],
            'observaciones_tecnicas' => null,
            'ubicaciones' => [],
            'observaciones_generales' => [
                ['texto' => 'ObservaciÃ³n importante', 'tipo' => 'texto']
            ]
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        // Con observaciones generales pero sin prendas, deberÃ­a ser General
        $this->assertEquals('General', $resultado);
        echo "\n Test Con Observaciones PASÃ“\n";
    }
}

