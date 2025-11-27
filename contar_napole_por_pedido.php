<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRENDAS CON 'NAPOLE' POR PEDIDO ===\n\n";

$resultados = DB::table('prendas_pedido')
    ->where('descripcion_armada', 'LIKE', '%napole%')
    ->select('pedido_produccion_id', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('pedido_produccion_id')
    ->orderBy('cantidad', 'desc')
    ->get();

echo "Total de pedidos con prendas napole: " . count($resultados) . "\n\n";

$totalPrendas = 0;
foreach ($resultados as $row) {
    echo "Pedido " . $row->pedido_produccion_id . ": " . $row->cantidad . " prenda(s)\n";
    $totalPrendas += $row->cantidad;
}

echo "\n---\n";
echo "Total de prendas: " . $totalPrendas . "\n";
echo "Promedio por pedido: " . round($totalPrendas / count($resultados), 2) . "\n";
?>
