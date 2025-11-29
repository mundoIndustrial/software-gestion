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
     * Test: Determinar tipo de cotización Prenda/Logo
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

        // Usar reflexión para acceder al método privado
        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('Prenda/Logo', $resultado);
        echo "\n✅ Test Prenda/Logo PASÓ\n";
    }

    /**
     * Test: Determinar tipo de cotización Solo Logo
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
        echo "\n✅ Test Solo Logo PASÓ\n";
    }

    /**
     * Test: Determinar tipo de cotización General (solo prendas)
     */
    public function test_determinar_tipo_general()
    {
        $datos = [
            'productos' => [
                ['nombre_producto' => 'Pantalón']
            ],
            'tecnicas' => [], // SIN técnicas
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
        echo "\n✅ Test General PASÓ\n";
    }

    /**
     * Test: Determinar tipo de cotización con imagenes
     */
    public function test_determinar_tipo_con_imagenes()
    {
        $datos = [
            'productos' => [], // SIN prendas
            'tecnicas' => [],
            'imagenes' => ['imagen1.jpg'], // CON imágenes
            'observaciones_tecnicas' => null,
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        $this->assertEquals('Solo Logo', $resultado);
        echo "\n✅ Test Con Imágenes PASÓ\n";
    }

    /**
     * Test: Determinar tipo de cotización con observaciones generales
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
                ['texto' => 'Observación importante', 'tipo' => 'texto']
            ]
        ];

        $reflection = new \ReflectionClass($this->cotizacionService);
        $method = $reflection->getMethod('determinarTipoCotizacion');
        $method->setAccessible(true);
        
        $resultado = $method->invoke($this->cotizacionService, $datos);

        // Con observaciones generales pero sin prendas, debería ser General
        $this->assertEquals('General', $resultado);
        echo "\n✅ Test Con Observaciones PASÓ\n";
    }
}
