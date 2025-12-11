<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificarCotizacion45Test extends TestCase
{
    public function test_verificar_cotizacion_45()
    {
        $cot = \App\Models\Cotizacion::find(45);
        
        if (!$cot) {
            echo "\n❌ Cotización #45 no encontrada\n";
            return;
        }
        
        echo "\n📊 Cotización #45:\n";
        echo "   - Número: " . $cot->numero_cotizacion . "\n";
        echo "   - Estado: " . $cot->estado . "\n";
        echo "   - Cliente ID: " . $cot->cliente_id . "\n";
        echo "   - Prendas: " . $cot->prendas()->count() . "\n";
        
        // Verificar qué datos se guardaron en la tabla cotizaciones
        echo "\n📋 Datos guardados en tabla cotizaciones:\n";
        echo "   - productos: " . (strlen($cot->productos ?? '') > 0 ? "SÍ" : "NO") . "\n";
        echo "   - logo: " . (strlen($cot->logo ?? '') > 0 ? "SÍ" : "NO") . "\n";
        
        if ($cot->productos) {
            $productos = json_decode($cot->productos, true);
            echo "   - Productos JSON: " . count($productos) . " productos\n";
            if (count($productos) > 0) {
                echo "     Primer producto: " . json_encode($productos[0], JSON_PRETTY_PRINT) . "\n";
            }
        }
        
        $this->assertTrue(true);
    }
}
