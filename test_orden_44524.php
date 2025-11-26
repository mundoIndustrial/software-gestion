<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧪 Verificando si la orden 44524 existe...\n\n";

// Buscar la orden
$orden = DB::table('pedidos_produccion')->where('numero_pedido', 44524)->orWhere('id', 44524)->first();

if ($orden) {
    echo "✅ Orden encontrada:\n";
    echo "   - ID: " . $orden->id . "\n";
    echo "   - Numero: " . $orden->numero_pedido . "\n";
    echo "   - Cliente: " . $orden->cliente . "\n\n";
    
    // Verificar procesos
    $procesos = DB::table('procesos_prenda')
        ->where('pedidos_produccion_id', $orden->id)
        ->count();
    
    echo "   - Procesos: " . $procesos . "\n";
} else {
    echo "❌ Orden no encontrada con ID o número 44524\n\n";
    
    // Mostrar las primeras órdenes
    echo "Primeras órdenes en la BD:\n";
    $ordenes = DB::table('pedidos_produccion')->limit(10)->get();
    foreach ($ordenes as $o) {
        echo "   - ID: " . $o->id . " | Número: " . $o->numero_pedido . " | Cliente: " . $o->cliente . "\n";
    }
}
