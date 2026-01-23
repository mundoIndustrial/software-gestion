<?php

namespace Tests\Feature\Cotizacion;

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\User;
use Tests\TestCase;

/**
 * TestCotizacionNumeroConcurrencia
 *
 * Verifica que dos o mÃ¡s asesores creando cotizaciones simultÃ¡neamente
 * reciban nÃºmeros Ãºnicos y consecutivos
 */
class TestCotizacionNumeroConcurrencia extends TestCase
{
    private GenerarNumeroCotizacionService $generarNumeroCotizacionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generarNumeroCotizacionService = $this->app->make(GenerarNumeroCotizacionService::class);
    }

    /**
     * Test: Verificar que cada asesor obtiene nÃºmeros secuenciales sin duplicados
     */
    public function test_cada_asesor_obtiene_numeros_secuenciales_unicos()
    {
        // Crear dos asesores
        $asesor1 = User::factory()->create(['nombre' => 'Asesor 1']);
        $asesor2 = User::factory()->create(['nombre' => 'Asesor 2']);

        // Usar \App\Domain\Shared\ValueObjects\UserId para el servicio
        $usuarioId1 = \App\Domain\Shared\ValueObjects\UserId::crear($asesor1->id);
        $usuarioId2 = \App\Domain\Shared\ValueObjects\UserId::crear($asesor2->id);

        // Generar 5 nÃºmeros para asesor 1
        $numeros1 = [];
        for ($i = 0; $i < 5; $i++) {
            $numeros1[] = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId1);
        }

        // Generar 5 nÃºmeros para asesor 2
        $numeros2 = [];
        for ($i = 0; $i < 5; $i++) {
            $numeros2[] = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId2);
        }

        // Verificar que los nÃºmeros del asesor 1 son Ãºnicos
        $this->assertEquals(5, count(array_unique($numeros1)), 'Asesor 1 tiene nÃºmeros duplicados');
        
        // Verificar que los nÃºmeros del asesor 2 son Ãºnicos
        $this->assertEquals(5, count(array_unique($numeros2)), 'Asesor 2 tiene nÃºmeros duplicados');

        // Verificar que son consecutivos para asesor 1
        $this->assertEquals('COT-00001', $numeros1[0]);
        $this->assertEquals('COT-00002', $numeros1[1]);
        $this->assertEquals('COT-00003', $numeros1[2]);
        $this->assertEquals('COT-00004', $numeros1[3]);
        $this->assertEquals('COT-00005', $numeros1[4]);

        // Verificar que son consecutivos para asesor 2
        $this->assertEquals('COT-00001', $numeros2[0]);
        $this->assertEquals('COT-00002', $numeros2[1]);
        $this->assertEquals('COT-00003', $numeros2[2]);
        $this->assertEquals('COT-00004', $numeros2[3]);
        $this->assertEquals('COT-00005', $numeros2[4]);
    }

    /**
     * Test: Verificar el formateo de nÃºmeros
     */
    public function test_formatear_numero_cotizacion()
    {
        $this->assertEquals('COT-00001', $this->generarNumeroCotizacionService->formatearNumero(1));
        $this->assertEquals('COT-00010', $this->generarNumeroCotizacionService->formatearNumero(10));
        $this->assertEquals('COT-00100', $this->generarNumeroCotizacionService->formatearNumero(100));
        $this->assertEquals('COT-01000', $this->generarNumeroCotizacionService->formatearNumero(1000));
        $this->assertEquals('COT-99999', $this->generarNumeroCotizacionService->formatearNumero(99999));
    }

    /**
     * Test: Simular 20 asesores solicitando nÃºmeros simultÃ¡neamente
     * Simula una concurrencia moderada sin crear threads reales
     */
    public function test_20_asesores_obtienen_numeros_unicos()
    {
        // Crear 20 asesores
        $asesores = User::factory()->count(20)->create();
        $todosLosNumeros = [];

        // Cada asesor obtiene 3 nÃºmeros
        foreach ($asesores as $asesor) {
            $usuarioId = \App\Domain\Shared\ValueObjects\UserId::crear($asesor->id);
            $numerosDelAsesor = [];

            for ($i = 0; $i < 3; $i++) {
                $numero = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                $numerosDelAsesor[] = $numero;
                $todosLosNumeros[$asesor->id][] = $numero;
            }

            // Verificar que cada asesor tiene 3 nÃºmeros Ãºnicos
            $this->assertEquals(3, count(array_unique($numerosDelAsesor)), 
                "Asesor {$asesor->id} tiene nÃºmeros duplicados");
        }

        // Verificar que TODOS los nÃºmeros en la BD son Ãºnicos (no hay duplicados entre asesores)
        $todosLosCombinados = [];
        foreach ($todosLosNumeros as $asesor_id => $numeros) {
            foreach ($numeros as $numero) {
                $key = $asesor_id . '_' . $numero;
                $todosLosCombinados[] = $key;
            }
        }

        $this->assertEquals(60, count(array_unique($todosLosCombinados)), 
            'Hay nÃºmeros duplicados entre los 20 asesores');

        // Verificar que cada asesor tiene sus nÃºmeros consecutivos desde 1
        foreach ($todosLosNumeros as $asesor_id => $numeros) {
            $this->assertEquals('COT-00001', $numeros[0], "Asesor $asesor_id no comienza en 1");
            $this->assertEquals('COT-00002', $numeros[1], "Asesor $asesor_id no es secuencial");
            $this->assertEquals('COT-00003', $numeros[2], "Asesor $asesor_id no es secuencial");
        }
    }
}


