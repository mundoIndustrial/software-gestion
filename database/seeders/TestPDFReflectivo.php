<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cotizacion;

class TestPDFReflectivo extends Seeder
{
    public function run()
    {
        // Buscar una cotización reflectiva
        $cotizacion = Cotizacion::where('tipo_cotizacion_id', 4)
            ->with('tipoCotizacion', 'prendaCotReflectivos.prendaCot.tallas', 'cliente', 'usuario')
            ->first();

        if ($cotizacion) {
            echo "\n✅ Cotización Reflectiva Encontrada\n";
            echo "───────────────────────────────────\n";
            echo "ID: " . $cotizacion->id . "\n";
            echo "Número: " . $cotizacion->numero_cotizacion . "\n";
            echo "Tipo ID: " . $cotizacion->tipo_cotizacion_id . "\n";
            echo "Código: " . $cotizacion->tipoCotizacion->codigo . "\n";
            echo "Cliente: " . $cotizacion->cliente->nombre . "\n";
            echo "Asesor: " . $cotizacion->usuario->name . "\n";
            echo "Prendas Reflectivas: " . $cotizacion->prendaCotReflectivos->count() . "\n";
            echo "\n";
            
            if ($cotizacion->prendaCotReflectivos->count() > 0) {
                echo "Prendas:\n";
                foreach ($cotizacion->prendaCotReflectivos as $refPrenda) {
                    $prenda = $refPrenda->prendaCot;
                    echo "  - " . $prenda->nombre_producto . " (Tallas: " . $prenda->tallas->pluck('talla')->implode(', ') . ")\n";
                }
            }
            
            echo "\n✅ PDF URL: /asesores/cotizacion/" . $cotizacion->id . "/pdf/reflectivo\n\n";
        } else {
            echo "❌ No hay cotizaciones reflectivas en la base de datos\n\n";
        }
    }
}
