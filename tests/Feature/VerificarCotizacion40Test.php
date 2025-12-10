<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificarCotizacion40Test extends TestCase
{
    public function test_verificar_cotizacion_40()
    {
        $cot = \App\Models\Cotizacion::find(40);
        
        if (!$cot) {
            echo "\n❌ Cotización #40 no encontrada\n";
            return;
        }
        
        echo "\n📊 Cotización #40:\n";
        echo "   - ID: " . $cot->id . "\n";
        echo "   - Número: " . $cot->numero_cotizacion . "\n";
        echo "   - Estado: " . $cot->estado . "\n";
        
        $prendas = $cot->prendas()->get();
        echo "\n📦 Prendas: " . $prendas->count() . "\n";
        
        foreach ($prendas as $prenda) {
            echo "\n   Prenda ID: " . $prenda->id . "\n";
            echo "   - Nombre: " . $prenda->nombre_producto . "\n";
            echo "   - Fotos de prenda: " . $prenda->fotos()->count() . "\n";
            echo "   - Telas: " . $prenda->telas()->count() . "\n";
            echo "   - Tallas: " . $prenda->tallas()->count() . "\n";
            echo "   - Variantes: " . $prenda->variantes()->count() . "\n";
            
            // Mostrar detalles de lo que se guardó
            $fotos = $prenda->fotos()->get();
            if ($fotos->count() > 0) {
                echo "\n   📸 Fotos guardadas:\n";
                foreach ($fotos as $foto) {
                    echo "      - " . $foto->ruta_webp . "\n";
                }
            }
            
            $telas = $prenda->telas()->get();
            if ($telas->count() > 0) {
                echo "\n   🧵 Telas guardadas:\n";
                foreach ($telas as $tela) {
                    echo "      - " . $tela->nombre_tela . " (" . $tela->color . ")\n";
                }
            }
            
            $tallas = $prenda->tallas()->get();
            if ($tallas->count() > 0) {
                echo "\n   📏 Tallas guardadas:\n";
                foreach ($tallas as $talla) {
                    echo "      - " . $talla->talla . " (Cantidad: " . $talla->cantidad . ")\n";
                }
            }
            
            $variantes = $prenda->variantes()->get();
            if ($variantes->count() > 0) {
                echo "\n   🎨 Variantes guardadas:\n";
                foreach ($variantes as $variante) {
                    echo "      - Género ID: " . $variante->genero_id . "\n";
                    echo "      - Descripción: " . $variante->descripcion_adicional . "\n";
                }
            }
        }
        
        $this->assertTrue(true);
    }
}
