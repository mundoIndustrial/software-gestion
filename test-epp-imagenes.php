<?php

/**
 * TEST: Verificar fotos en EPPs
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Fotos en pedido_epp_imagenes ===\n\n";

// Pedido 45807 tiene pedido_produccion_id = 3
$pedido = \App\Models\PedidoProduccion::find(3);

if ($pedido) {
    echo "Pedido: {$pedido->numero_pedido}\n";
    
    $epps = $pedido->epps()->get();
    echo "EPPs encontrados: " . count($epps) . "\n\n";
    
    foreach ($epps as $epp) {
        echo "EPP: {$epp->nombre}\n";
        $fotosCount = \DB::table('pedido_epp_imagenes')
            ->where('pedido_epp_id', $epp->pivot->id)
            ->count();
        echo "  Fotos: $fotosCount\n";
        
        if ($fotosCount > 0) {
            $fotos = \DB::table('pedido_epp_imagenes')
                ->where('pedido_epp_id', $epp->pivot->id)
                ->orderBy('orden')
                ->get();
            foreach ($fotos as $foto) {
                echo "    - " . substr($foto->ruta_web ?? $foto->ruta_original, 0, 55) . "\n";
            }
        }
    }
} else {
    echo "Pedido no encontrado\n";
}

echo "\n✅ TEST COMPLETADO\n";
