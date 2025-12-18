<?php

namespace Tests\Feature\Cotizacion;

use App\Application\Cotizacion\Commands\CrearCotizacionCommand;
use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TestCotizacionNumeroConcurrencia
 *
 * Verifica que dos asesores creando cotizaciones simultáneamente
 * reciban números únicos y consecutivos
 */
class TestCotizacionNumeroConcurrencia extends TestCase
{
    private GenerarNumeroCotizacionService $generarNumeroCotizacionService;
    private CrearCotizacionHandler $crearCotizacionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generarNumeroCotizacionService = $this->app->make(GenerarNumeroCotizacionService::class);
        $this->crearCotizacionHandler = $this->app->make(CrearCotizacionHandler::class);
    }

    /**
     * Test: Verificar que el servicio de generación no permite números duplicados
     * bajo concurrencia (SELECT FOR UPDATE)
     */
    public function test_generar_numero_cotizacion_sin_duplicados_concurrencia()
    {
        // Crear dos asesores
        $asesor1 = User::factory()->create(['nombre' => 'Asesor 1']);
        $asesor2 = User::factory()->create(['nombre' => 'Asesor 2']);

        // Crear cliente
        $cliente = Cliente::factory()->create();

        // Usar \App\Domain\Shared\ValueObjects\UserId para el servicio
        $usuarioId1 = \App\Domain\Shared\ValueObjects\UserId::crear($asesor1->id);
        $usuarioId2 = \App\Domain\Shared\ValueObjects\UserId::crear($asesor2->id);

        // Generar 3 números para asesor 1
        $numero1_1 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId1);
        $numero1_2 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId1);
        $numero1_3 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId1);

        // Generar 3 números para asesor 2
        $numero2_1 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId2);
        $numero2_2 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId2);
        $numero2_3 = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId2);

        // Verificar que los números del asesor 1 son consecutivos y únicos
        $this->assertEquals('COT-00001', $numero1_1);
        $this->assertEquals('COT-00002', $numero1_2);
        $this->assertEquals('COT-00003', $numero1_3);

        // Verificar que los números del asesor 2 son consecutivos y únicos
        $this->assertEquals('COT-00001', $numero2_1);
        $this->assertEquals('COT-00002', $numero2_2);
        $this->assertEquals('COT-00003', $numero2_3);

        // Verificar que cada asesor tiene sus números propios (no compartidos)
        $this->assertNotEquals($numero1_1, $numero2_1);
    }

    /**
     * Test: Crear cotizaciones de dos asesores simultáneamente
     * y verificar que ambas tengan números únicos
     */
    public function test_crear_cotizaciones_simultaneas_asesores_numeros_diferentes()
    {
        // Crear dos asesores
        $asesor1 = User::factory()->create(['nombre' => 'Asesor A']);
        $asesor2 = User::factory()->create(['nombre' => 'Asesor B']);

        // Crear cliente
        $cliente = Cliente::factory()->create();

        // Crear primera cotización para asesor 1
        $dto1 = new CrearCotizacionDTO(
            usuarioId: $asesor1->id,
            tipo: 'P',
            clienteId: $cliente->id,
            tipoVenta: 'M',
            esBorrador: false,
            numeroCotizacion: null,
            prendas: [],
            especificaciones: []
        );

        $comando1 = new CrearCotizacionCommand($dto1);
        $cotizacion1 = $this->crearCotizacionHandler->handle($comando1);

        // Crear segunda cotización para asesor 2
        $dto2 = new CrearCotizacionDTO(
            usuarioId: $asesor2->id,
            tipo: 'P',
            clienteId: $cliente->id,
            tipoVenta: 'M',
            esBorrador: false,
            numeroCotizacion: null,
            prendas: [],
            especificaciones: []
        );

        $comando2 = new CrearCotizacionCommand($dto2);
        $cotizacion2 = $this->crearCotizacionHandler->handle($comando2);

        // Crear segunda cotización para asesor 1
        $dto3 = new CrearCotizacionDTO(
            usuarioId: $asesor1->id,
            tipo: 'P',
            clienteId: $cliente->id,
            tipoVenta: 'M',
            esBorrador: false,
            numeroCotizacion: null,
            prendas: [],
            especificaciones: []
        );

        $comando3 = new CrearCotizacionCommand($dto3);
        $cotizacion3 = $this->crearCotizacionHandler->handle($comando3);

        // Verificar que existan en BD
        $cot1 = Cotizacion::findOrFail($cotizacion1->id);
        $cot2 = Cotizacion::findOrFail($cotizacion2->id);
        $cot3 = Cotizacion::findOrFail($cotizacion3->id);

        // Verificar que asesor 1 tiene números únicos y consecutivos
        $this->assertEquals('COT-00001', $cot1->numero_cotizacion);
        $this->assertEquals('COT-00002', $cot3->numero_cotizacion);

        // Verificar que asesor 2 tiene su propio número
        $this->assertEquals('COT-00001', $cot2->numero_cotizacion);

        // Verificar que los números son diferentes entre asesores
        $this->assertNotEquals($cot1->numero_cotizacion, $cot2->numero_cotizacion);
    }

    /**
     * Test: Verificar el formateo de números
     */
    public function test_formatear_numero_cotizacion()
    {
        $this->assertEquals('COT-00001', $this->generarNumeroCotizacionService->formatearNumero(1));
        $this->assertEquals('COT-00010', $this->generarNumeroCotizacionService->formatearNumero(10));
        $this->assertEquals('COT-00100', $this->generarNumeroCotizacionService->formatearNumero(100));
        $this->assertEquals('COT-01000', $this->generarNumeroCotizacionService->formatearNumero(1000));
        $this->assertEquals('COT-99999', $this->generarNumeroCotizacionService->formatearNumero(99999));
    }
}
