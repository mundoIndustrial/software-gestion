<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;

// Obtener primeras 5 órdenes
$ordenes = PedidoProduccion::with('prendas')->limit(5)->get();

echo "\n========== INSPECCIÓN DE PRENDAS ==========\n\n";

foreach ($ordenes as $orden) {
    echo "📋 ORDEN ID: {$orden->id} | PEDIDO: {$orden->pedido}\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    if ($orden->prendas->isEmpty()) {
        echo "   ❌ Sin prendas\n\n";
        continue;
    }
    
    foreach ($orden->prendas as $idx => $prenda) {
        $numero = $idx + 1;
        echo "\n   PRENDA {$numero}:\n";
        echo "   ├─ Nombre: {$prenda->nombre_prenda}\n";
        echo "   ├─ Descripción: {$prenda->descripcion}\n";
        echo "   ├─ Cantidad: {$prenda->cantidad}\n";
        echo "   ├─ cantidad_talla (RAW): " . ($prenda->cantidad_talla ?? 'NULL') . "\n";
        
        if ($prenda->cantidad_talla) {
            $tallas = is_string($prenda->cantidad_talla) 
                ? json_decode($prenda->cantidad_talla, true) 
                : $prenda->cantidad_talla;
            
            if (is_array($tallas)) {
                echo "   └─ Tallas parseadas:\n";
                foreach ($tallas as $t) {
                    echo "      • {$t['talla']}: {$t['cantidad']}\n";
                }
            } else {
                echo "   └─ ⚠️  No es un array válido\n";
            }
        } else {
            echo "   └─ ❌ cantidad_talla está NULL\n";
        }
    }
    
    echo "\n   📄 Descripción formateada:\n";
    echo "   " . str_replace("\n", "\n   ", $orden->descripcion_prendas) . "\n";
    echo "\n═══════════════════════════════════════════════════════════════\n\n";
}

echo "✅ Inspección completada\n";
