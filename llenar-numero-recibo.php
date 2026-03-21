<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== LLENANDO NUMERO_RECIBO VACÍOS EN PROCESOS_PRENDA ===\n\n";

// Encontrar procesos sin numero_recibo
$procesosSinRecibo = DB::table('procesos_prenda')
    ->where('proceso', 'Control de Calidad')
    ->where('estado_proceso', 'Pendiente')
    ->whereNull('numero_recibo')
    ->orWhere('numero_recibo', '=', '')
    ->get();

echo "Procesos sin numero_recibo: " . count($procesosSinRecibo) . "\n";

foreach ($procesosSinRecibo as $proceso) {
    echo "\nProcesando: Pedido {$proceso->numero_pedido}, Prenda {$proceso->prenda_pedido_id}\n";
    
    // Buscar el consecutivo_actual del recibo correspondiente
    $recibo = DB::table('consecutivos_recibos_pedidos as crp')
        ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
        ->where('p.numero_pedido', $proceso->numero_pedido)
        ->where('crp.prenda_id', $proceso->prenda_pedido_id)
        ->where('crp.tipo_recibo', 'COSTURA')
        ->where('crp.activo', 1)
        ->select('crp.consecutivo_actual')
        ->first();
    
    if ($recibo) {
        echo "  ✓ Encontrado recibo: " . $recibo->consecutivo_actual . "\n";
        
        // Actualizar el numero_recibo
        DB::table('procesos_prenda')
            ->where('id', $proceso->id)
            ->update(['numero_recibo' => $recibo->consecutivo_actual]);
        
        echo "  ✓ Actualizado proceso_id: {$proceso->id}\n";
    } else {
        echo "  ✗ No se encontró recibo correspondiente\n";
    }
}

echo "\n=== FIN DE ACTUALIZACIÓN ===\n";
?>
