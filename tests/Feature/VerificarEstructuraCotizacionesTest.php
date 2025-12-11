<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificarEstructuraCotizacionesTest extends TestCase
{
    public function test_verificar_estructura_cotizaciones()
    {
        $columns = \DB::select('DESCRIBE cotizaciones');
        
        echo "\n📊 Columnas de cotizaciones:\n";
        foreach ($columns as $col) {
            echo "  - " . $col->Field . " (" . $col->Type . ")\n";
        }
        
        $this->assertTrue(true);
    }
}
