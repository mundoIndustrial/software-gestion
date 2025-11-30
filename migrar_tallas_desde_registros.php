<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MIGRACIÓN DE TALLAS DESDE registros_por_orden ===\n\n";

// Obtener todas las prendas sin cantidad_talla
$prendas = \App\Models\PrendaPedido::where('cantidad_talla', null)
    ->whereNotNull('pedido_produccion_id')
    ->get();

echo "Prendas a procesar: " . $prendas->count() . "\n\n";

$actualizadas = 0;
$sin_datos = 0;

foreach ($prendas as $prenda) {
    // Buscar registros en registros_por_orden que coincidan con:
    // pedido = numero_pedido de la orden
    // prenda = nombre_prenda
    
    $orden = $prenda->pedido; // relación a PedidoProduccion
    
    if (!$orden) {
        $sin_datos++;
        continue;
    }
    
    // Buscar en registros_por_orden
    $registros = \DB::table('registros_por_orden')
        ->where('pedido', $orden->numero_pedido)
        ->where('prenda', $prenda->nombre_prenda)
        ->select('talla', 'cantidad')
        ->get();
    
    if ($registros->isEmpty()) {
        $sin_datos++;
        continue;
    }
    
    // Consolidar tallas: agrupar por talla y sumar cantidades
    $tallas = [];
    foreach ($registros as $reg) {
        $talla = strtoupper(trim($reg->talla ?? 'SIN TALLA'));
        $cantidad = intval($reg->cantidad ?? 0);
        
        if (!isset($tallas[$talla])) {
            $tallas[$talla] = 0;
        }
        $tallas[$talla] += $cantidad;
    }
    
    if (!empty($tallas)) {
        $json = json_encode($tallas);
        $prenda->update(['cantidad_talla' => $json]);
        $actualizadas++;
        echo "✅ Prenda {$prenda->id} ({$prenda->nombre_prenda}): " . json_encode($tallas) . "\n";
    } else {
        $sin_datos++;
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "RESULTADO:\n";
echo "  ✅ Actualizadas: $actualizadas\n";
echo "  ⚠️  Sin datos en registros_por_orden: $sin_datos\n";
echo "════════════════════════════════════════════════════════════════\n";
