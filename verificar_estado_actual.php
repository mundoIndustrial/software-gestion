<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN ESTADO ACTUAL DE PEDIDOS ===\n";

try {
    // Mostrar TODOS los pedidos con sus estados
    $todos = DB::table('pedidos_produccion')
        ->select('id', 'numero_pedido', 'cliente', 'estado', 'aprobado_por_cartera_en', 'aprobado_por_supervisor_en')
        ->orderBy('id')
        ->get();
    
    echo "\n--- TODOS LOS PEDIDOS ---\n";
    foreach ($todos as $pedido) {
        echo sprintf("ID: %d | Pedido: %s | Cliente: %-25s | Estado: %-20s | Cartera: %s | Supervisor: %s\n", 
            $pedido->id, 
            $pedido->numero_pedido ?? 'N/A', 
            substr($pedido->cliente, 0, 25),
            $pedido->estado,
            $pedido->aprobado_por_cartera_en ?? 'N/A',
            $pedido->aprobado_por_supervisor_en ?? 'N/A'
        );
    }
    
    // Buscar específicamente PENDIENTE_SUPERVISOR
    echo "\n--- PEDIDOS PENDIENTE_SUPERVISOR ---\n";
    $aprobados = DB::table('pedidos_produccion')
        ->where('estado', 'PENDIENTE_SUPERVISOR')
        ->get();
    
    if ($aprobados->isEmpty()) {
        echo "❌ NO HAY pedidos con estado PENDIENTE_SUPERVISOR\n";
    } else {
        foreach ($aprobados as $pedido) {
            echo sprintf("✅ ID: %d | Pedido: %s | Cliente: %s | Aprobado Cartera: %s\n", 
                $pedido->id, 
                $pedido->numero_pedido ?? 'N/A', 
                $pedido->cliente,
                $pedido->aprobado_por_cartera_en ?? 'N/A'
            );
        }
    }
    
    // Verificar si hay pedidos que fueron aprobados por cartera pero no tienen el estado correcto
    echo "\n--- PEDIDOS APROBADOS POR CARTERA (por fecha) ---\n";
    $aprobadosCartera = DB::table('pedidos_produccion')
        ->whereNotNull('aprobado_por_cartera_en')
        ->where('estado', '!=', 'PENDIENTE_SUPERVISOR')
        ->get();
    
    if ($aprobadosCartera->isEmpty()) {
        echo "No hay pedidos aprobados por cartera con estado diferente a PENDIENTE_SUPERVISOR\n";
    } else {
        foreach ($aprobadosCartera as $pedido) {
            echo sprintf("⚠️  ID: %d | Pedido: %s | Estado: %s | Aprobado: %s\n", 
                $pedido->id, 
                $pedido->numero_pedido ?? 'N/A', 
                $pedido->estado,
                $pedido->aprobado_por_cartera_en
            );
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
