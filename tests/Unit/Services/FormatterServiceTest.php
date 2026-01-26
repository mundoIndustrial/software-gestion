<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FormatterService;

class FormatterServiceTest extends TestCase
{
    private FormatterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FormatterService();
    }

    /**
     * Test: Procesar inputs del formulario completo
     */
    public function test_procesar_inputs_formulario_completo(): void
    {
        $validado = [
            'cliente' => 'Cliente ABC',
            'productos' => [['nombre' => 'POLO']],
            'tecnicas' => ['Bordado'],
            'ubicaciones' => [['seccion' => 'Pecho']],
            'imagenes' => ['foto1.jpg'],
            'especificaciones' => ['forma_pago' => 'Contado'],
            'observaciones' => ['Revisar calidad'],
            'observaciones_tecnicas' => 'Bordado fino',
            'tipo_cotizacion' => 'PERSONAL',
            'numero_cotizacion' => 'COT-001'
        ];

        // Actuar
        $resultado = $this->service->procesarInputsFormulario($validado);

        // Afirmar
        $this->assertEquals('Cliente ABC', $resultado['cliente']);
        $this->assertIsArray($resultado['productos']);
        $this->assertIsArray($resultado['tecnicas']);
        $this->assertIsArray($resultado['ubicaciones']);
        $this->assertEquals('Bordado fino', $resultado['observaciones_tecnicas']);
    }

    /**
     * Test: Completar valores faltantes con defaults
     */
    public function test_completar_valores_faltantes(): void
    {
        $validado = [
            'cliente' => 'Cliente Test'
        ];

        // Actuar
        $resultado = $this->service->procesarInputsFormulario($validado);

        // Afirmar
        $this->assertEquals('Cliente Test', $resultado['cliente']);
        $this->assertIsArray($resultado['productos']);
        $this->assertEmpty($resultado['productos']);
    }

    /**
     * Test: Procesar ubicaciones simple
     */
    public function test_procesar_ubicaciones_simple(): void
    {
        $ubicacionesRaw = [
            'Pecho',
            'Espalda'
        ];

        // Actuar
        $resultado = $this->service->procesarUbicaciones($ubicacionesRaw);

        // Afirmar
        $this->assertCount(2, $resultado);
        $this->assertEquals('GENERAL', $resultado[0]['seccion']);
        $this->assertContains('Pecho', $resultado[0]['ubicaciones_seleccionadas']);
    }

    /**
     * Test: Procesar ubicaciones estructuradas
     */
    public function test_procesar_ubicaciones_estructuradas(): void
    {
        $ubicacionesRaw = [
            [
                'seccion' => 'BORDADO',
                'ubicaciones_seleccionadas' => ['Pecho', 'Espalda']
            ]
        ];

        // Actuar
        $resultado = $this->service->procesarUbicaciones($ubicacionesRaw);

        // Afirmar
        $this->assertCount(1, $resultado);
        $this->assertEquals('BORDADO', $resultado[0]['seccion']);
        $this->assertCount(2, $resultado[0]['ubicaciones_seleccionadas']);
    }

    /**
     * Test: Procesar ubicaciones mixtas
     */
    public function test_procesar_ubicaciones_mixtas(): void
    {
        $ubicacionesRaw = [
            'Pecho',
            [
                'seccion' => 'ESTAMPADO',
                'ubicaciones_seleccionadas' => ['Espalda']
            ]
        ];

        // Actuar
        $resultado = $this->service->procesarUbicaciones($ubicacionesRaw);

        // Afirmar
        $this->assertCount(2, $resultado);
        $this->assertEquals('GENERAL', $resultado[0]['seccion']);
        $this->assertEquals('ESTAMPADO', $resultado[1]['seccion']);
    }

    /**
     * Test: Procesar ubicaciones vacÃ­as
     */
    public function test_procesar_ubicaciones_vacias(): void
    {
        // Actuar
        $resultado = $this->service->procesarUbicaciones([]);

        // Afirmar
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    /**
     * Test: Procesar ubicaciones no array
     */
    public function test_procesar_ubicaciones_no_array(): void
    {
        // Actuar
        $resultado = $this->service->procesarUbicaciones([]);

        // Afirmar
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    /**
     * Test: Procesar especificaciones array
     */
    public function test_procesar_especificaciones_array(): void
    {
        $especificaciones = [
            'forma_pago' => 'Contado',
            'plazo_entrega' => '15 dÃ­as'
        ];

        // Actuar
        $resultado = $this->service->procesarEspecificaciones($especificaciones);

        // Afirmar
        $this->assertIsArray($resultado);
        $this->assertEquals('Contado', $resultado['forma_pago']);
    }

    /**
     * Test: Procesar especificaciones no array
     */
    public function test_procesar_especificaciones_no_array(): void
    {
        // Actuar
        $resultado = $this->service->procesarEspecificaciones('especificación simple');

        // Afirmar
        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
    }

    /**
     * Test: Procesar observaciones con textos
     */
    public function test_procesar_observaciones_con_textos(): void
    {
        $request = $this->createMock('Illuminate\Http\Request');
        $request->expects($this->any())
            ->method('input')
            ->willReturnMap([
                ['observaciones_generales', [], ['Observación 1', 'Observación 2']],
                ['observaciones_check', [], [null, null]],
                ['observaciones_valor', [], ['', '']]
            ]);

        // Actuar
        $resultado = $this->service->procesarObservaciones($request);

        // Afirmar
        $this->assertCount(2, $resultado);
        $this->assertEquals('Observación 1', $resultado[0]['texto']);
        $this->assertEquals('texto', $resultado[0]['tipo']);
    }

    /**
     * Test: Procesar observaciones con checkboxes
     */
    public function test_procesar_observaciones_con_checkboxes(): void
    {
        $request = $this->createMock('Illuminate\Http\Request');
        $request->expects($this->any())
            ->method('input')
            ->willReturnMap([
                ['observaciones_generales', [], ['Check 1']],
                ['observaciones_check', [], ['on']],
                ['observaciones_valor', [], ['Valor 1']]
            ]);

        // Actuar
        $resultado = $this->service->procesarObservaciones($request);

        // Afirmar
        $this->assertCount(1, $resultado);
        $this->assertEquals('Check 1', $resultado[0]['texto']);
        $this->assertEquals('checkbox', $resultado[0]['tipo']);
    }

    /**
     * Test: Procesar observaciones vacÃ­as
     */
    public function test_procesar_observaciones_vacias(): void
    {
        $request = $this->createMock('Illuminate\Http\Request');
        $request->expects($this->any())
            ->method('input')
            ->willReturnMap([
                ['observaciones_generales', [], []],
                ['observaciones_check', [], []],
                ['observaciones_valor', [], []]
            ]);

        // Actuar
        $resultado = $this->service->procesarObservaciones($request);

        // Afirmar
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    /**
     * Test: Procesar observaciones ignorando vacÃ­as
     */
    public function test_procesar_observaciones_ignora_vacias(): void
    {
        $request = $this->createMock('Illuminate\Http\Request');
        $request->expects($this->any())
            ->method('input')
            ->willReturnMap([
                ['observaciones_generales', [], ['Obs 1', '', 'Obs 2']],
                ['observaciones_check', [], [null, null, null]],
                ['observaciones_valor', [], ['', '', '']]
            ]);

        // Actuar
        $resultado = $this->service->procesarObservaciones($request);

        // Afirmar - solo 2 observaciones, ignora la vacÃ­a
        $this->assertCount(2, $resultado);
        $this->assertEquals('Obs 1', $resultado[0]['texto']);
        $this->assertEquals('Obs 2', $resultado[1]['texto']);
    }
}

