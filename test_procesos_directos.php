<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Verificando tabla procesos_prenda...\n\n";

// Contar registros
$total = DB::table('procesos_prenda')->count();
echo "Total de procesos: " . $total . "\n\n";

if ($total > 0) {
    // Ver un registro de ejemplo
    $ejemplo = DB::table('procesos_prenda')->first();
    echo "📌 Ejemplo de registro:\n";
    echo "   - ID: " . $ejemplo->id . "\n";
    echo "   - pedidos_produccion_id: " . $ejemplo->pedidos_produccion_id . "\n";
    echo "   - prenda_pedido_id: " . $ejemplo->prenda_pedido_id . "\n";
    echo "   - proceso: " . $ejemplo->proceso . "\n";
    echo "   - fecha_inicio: " . $ejemplo->fecha_inicio . "\n";
    echo "   - encargado: " . $ejemplo->encargado . "\n";
    echo "   - estado_proceso: " . $ejemplo->estado_proceso . "\n";
    
    // Agrupar por pedidos_produccion_id
    echo "\n\n📊 Procesos por orden:\n";
    $gruposOrdenes = DB::table('procesos_prenda')
        ->groupBy('pedidos_produccion_id')
        ->select('pedidos_produccion_id', DB::raw('count(*) as cantidad'))
        ->limit(10)
        ->get();
    
    foreach ($gruposOrdenes as $grupo) {
        echo "   • Orden ID: " . $grupo->pedidos_produccion_id . " → " . $grupo->cantidad . " procesos\n";
    }
} else {
    echo "❌ No hay procesos en la tabla\n";
}

echo "\n✅ Completado\n";
