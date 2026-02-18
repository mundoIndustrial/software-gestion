<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG PENDIENTES DESPACHO ===\n";

try {
    // Verificar pedidos PENDIENTE_INSUMOS (Costura)
    echo "\n--- PENDIENTES DE COSTURA (PENDIENTE_INSUMOS) ---\n";
    $costura = DB::table('pedidos_produccion')
        ->whereNotNull('numero_pedido')
        ->where('numero_pedido', '!=', '')
        ->where('estado', 'PENDIENTE_INSUMOS')
        ->get();
    
    echo "Total costura: " . $costura->count() . "\n";
    foreach ($costura as $pedido) {
        echo sprintf("ID: %d | Pedido: %s | Cliente: %s | Estado: %s\n", 
            $pedido->id, 
            $pedido->numero_pedido ?? 'N/A', 
            $pedido->cliente,
            $pedido->estado
        );
    }
    
    // Verificar pedidos No iniciado (EPP)
    echo "\n--- PENDIENTES DE EPP (No iniciado) ---\n";
    $epp = DB::table('pedidos_produccion')
        ->whereNotNull('numero_pedido')
        ->where('numero_pedido', '!=', '')
        ->where('estado', 'No iniciado')
        ->get();
    
    echo "Total EPP: " . $epp->count() . "\n";
    foreach ($epp as $pedido) {
        echo sprintf("ID: %d | Pedido: %s | Cliente: %s | Estado: %s\n", 
            $pedido->id, 
            $pedido->numero_pedido ?? 'N/A', 
            $pedido->cliente,
            $pedido->estado
        );
    }
    
    // Total combinado
    echo "\n--- TOTAL COMBINADO ---\n";
    $total = $costura->count() + $epp->count();
    echo "Total pendientes (Costura + EPP): " . $total . "\n";
    
    if ($total === 0) {
        echo "❌ NO HAY PENDIENTES PARA MOSTRAR\n";
        echo "Necesitas crear pedidos con estado 'PENDIENTE_INSUMOS' o 'No iniciado'\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
