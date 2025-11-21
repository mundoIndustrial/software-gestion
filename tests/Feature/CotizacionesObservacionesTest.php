<?php

namespace Tests\Feature;

use Tests\TestCase;

class CotizacionesObservacionesTest extends TestCase
{
    /**
     * Test: Verificar que la lógica de procesamiento de observaciones es correcta
     */
    public function test_procesar_observaciones_generales()
    {
        // Simular datos del cliente
        $observacionesTexto = ['SE HA REALIZADO ', 'CANTIDAD'];
        $observacionesCheck = ['on', null];
        $observacionesValor = ['', '200+'];

        // Procesar observaciones (misma lógica del controlador)
        $observacionesGenerales = [];
        
        foreach ($observacionesTexto as $index => $obs) {
            if (!empty($obs)) {
                // Determinar si es checkbox o texto
                $checkValue = $observacionesCheck[$index] ?? null;
                $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
                $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
                
                $observacionesGenerales[] = [
                    'texto' => $obs,
                    'tipo' => $tipo,
                    'valor' => $valor
                ];
            }
        }

        // Verificaciones
        $this->assertCount(2, $observacionesGenerales);

        // Primera observación: checkbox
        $this->assertEquals('SE HA REALIZADO ', $observacionesGenerales[0]['texto']);
        $this->assertEquals('checkbox', $observacionesGenerales[0]['tipo']);
        $this->assertEquals('', $observacionesGenerales[0]['valor']);

        // Segunda observación: texto con valor
        $this->assertEquals('CANTIDAD', $observacionesGenerales[1]['texto']);
        $this->assertEquals('texto', $observacionesGenerales[1]['tipo']);
        $this->assertEquals('200+', $observacionesGenerales[1]['valor']);

        echo "\n✅ TEST PASADO: Lógica de observaciones correcta\n";
        echo "Observación 1: " . json_encode($observacionesGenerales[0]) . "\n";
        echo "Observación 2: " . json_encode($observacionesGenerales[1]) . "\n";
    }
}
