<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificarTablaTest extends TestCase
{
    public function test_verificar_estructura_prenda_telas_cot()
    {
        $columns = \DB::select('DESCRIBE prenda_telas_cot');
        
        echo "\n📊 Columnas de prenda_telas_cot:\n";
        foreach ($columns as $col) {
            echo "  - " . $col->Field . " (" . $col->Type . ")\n";
        }
        
        $this->assertTrue(true);
    }
}
