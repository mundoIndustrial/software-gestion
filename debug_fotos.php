<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaCot;
use App\Models\PrendaFotoCot;

echo "\n=== DEBUG FOTOS PEDIDO 45452 ===\n\n";

// 1. Obtener pedido
$pedido = PedidoProduccion::where('numero_pedido', 45452)->first();
echo "✓ Pedido encontrado: " . ($pedido ? "SI" : "NO") . "\n";

if($pedido) {
    echo "  - ID: " . $pedido->id . "\n";
    echo "  - Cotizacion ID: " . ($pedido->cotizacion_id ?? 'NULL') . "\n\n";
    
    if($pedido->cotizacion_id) {
        // 2. Obtener PrendasCot
        $prendasCot = PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)->get();
        echo "✓ PrendasCot encontradas: " . $prendasCot->count() . "\n";
        
        if($prendasCot->count() > 0) {
            $ids = $prendasCot->pluck('id')->toArray();
            echo "  - IDs: " . json_encode($ids) . "\n\n";
            
            // 3. Obtener fotos
            $fotosPrendas = PrendaFotoCot::whereIn('prenda_cot_id', $ids)->orderBy('orden')->get();
            echo "✓ Fotos de prendas encontradas: " . $fotosPrendas->count() . "\n";
            
            if($fotosPrendas->count() > 0) {
                foreach($fotosPrendas as $foto) {
                    echo "  - ID: " . $foto->id . " (Orden: " . $foto->orden . ")\n";
                    echo "    ruta_webp: " . ($foto->ruta_webp ?? "NULL") . "\n";
                    echo "    ruta_original: " . ($foto->ruta_original ?? "NULL") . "\n";
                    echo "    ruta_miniatura: " . ($foto->ruta_miniatura ?? "NULL") . "\n";
                    echo "    tamaño: " . ($foto->tamaño ?? "NULL") . " bytes\n";
                }
            } else {
                echo "  - SIN FOTOS en PrendaFotoCot\n";
            }
        } else {
            echo "  - ERROR: NO HAY PRENDASCOT PARA ESTA COTIZACION\n";
        }
    } else {
        echo "  - ERROR: PEDIDO SIN COTIZACION_ID\n";
    }
} else {
    echo "  - ERROR: PEDIDO 45452 NO ENCONTRADO\n";
}

echo "\n";
