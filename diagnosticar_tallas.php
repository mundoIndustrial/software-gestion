<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;

// Obtener órdenes con prendas
$ordenes = PedidoProduccion::with('prendas')->limit(10)->get();

echo "\n========== DIAGNÓSTICO DE TALLAS ==========\n\n";

foreach ($ordenes as $orden) {
    if ($orden->prendas->isEmpty()) continue;
    
    echo "📋 ORDEN ID: {$orden->id} | PEDIDO: {$orden->pedido}\n";
    
    foreach ($orden->prendas as $idx => $prenda) {
        $numero = $idx + 1;
        echo "\n   PRENDA {$numero}: {$prenda->nombre_prenda}\n";
        echo "   ├─ cantidad_talla (RAW JSON): " . var_export($prenda->cantidad_talla, true) . "\n";
        
        // Verificar si es null, string o array
        echo "   ├─ Tipo de dato: " . gettype($prenda->cantidad_talla) . "\n";
        
        if ($prenda->cantidad_talla !== null) {
            if (is_string($prenda->cantidad_talla)) {
                echo "   ├─ Es string JSON\n";
                $decoded = json_decode($prenda->cantidad_talla, true);
                echo "   ├─ Decodificado: " . var_export($decoded, true) . "\n";
                echo "   └─ Válido JSON: " . (json_last_error() === JSON_ERROR_NONE ? 'SÍ' : 'NO') . "\n";
            } elseif (is_array($prenda->cantidad_talla)) {
                echo "   ├─ Es array (ya decodificado)\n";
                echo "   └─ Contenido: " . var_export($prenda->cantidad_talla, true) . "\n";
            }
        } else {
            echo "   └─ ❌ cantidad_talla es NULL\n";
        }
    }
    
    echo "\n───────────────────────────────────\n";
    echo "📄 DESCRIPCIÓN FORMATEADA:\n";
    echo $orden->descripcion_prendas . "\n";
    echo "───────────────────────────────────\n\n";
}

echo "✅ Diagnóstico completado\n";
