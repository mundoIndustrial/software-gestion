<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG PEDIDOS APROBADOS POR CARTERA ===\n";

try {
    // Contar pedidos por estado
    $estados = DB::table('pedidos_produccion')
        ->select('estado', DB::raw('count(*) as total'))
        ->groupBy('estado')
        ->orderBy('estado')
        ->get();
    
    echo "\n--- Pedidos por estado ---\n";
    foreach ($estados as $estado) {
        echo sprintf("%-25s: %d\n", $estado->estado, $estado->total);
    }
    
    // Buscar pedidos PENDIENTE_SUPERVISOR
    echo "\n--- Pedidos PENDIENTE_SUPERVISOR ---\n";
    $pedidosAprobados = DB::table('pedidos_produccion')
        ->where('estado', 'PENDIENTE_SUPERVISOR')
        ->get();
    
    if ($pedidosAprobados->isEmpty()) {
        echo "No se encontraron pedidos con estado PENDIENTE_SUPERVISOR\n";
    } else {
        foreach ($pedidosAprobados as $pedido) {
            echo sprintf("ID: %d | Pedido: %s | Cliente: %s | Aprobado: %s\n", 
                $pedido->id, 
                $pedido->numero_pedido ?? 'N/A', 
                $pedido->cliente,
                $pedido->aprobado_por_cartera_en ?? 'N/A'
            );
        }
    }
    
    // Verificar si hay pedidos aprobados recientemente
    echo "\n--- Pedidos aprobados en últimos 7 días ---\n";
    $recientes = DB::table('pedidos_produccion')
        ->whereNotNull('aprobado_por_cartera_en')
        ->where('aprobado_por_cartera_en', '>=', now()->subDays(7))
        ->get();
    
    if ($recientes->isEmpty()) {
        echo "No hay pedidos aprobados en los últimos 7 días\n";
    } else {
        foreach ($recientes as $pedido) {
            echo sprintf("ID: %d | Estado: %s | Aprobado: %s\n", 
                $pedido->id, 
                $pedido->estado,
                $pedido->aprobado_por_cartera_en
            );
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
