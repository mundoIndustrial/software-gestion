<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA NUEVO ENFOQUE (MATCHING POR VALOR) ===\n\n";

// Get the 15 selected description values
$values = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion')
    ->toArray();

echo "Valores seleccionados: " . count($values) . "\n\n";

// Simulate the new filter logic: per-value matching
$normalizedValues = [];
foreach ($values as $value) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    $normalizedValues[] = $normalized;
}

echo "Valores normalizados:\n";
foreach ($normalizedValues as $v) {
    echo "  - " . substr($v, 0, 60) . "...\n";
}
echo "\n";

// Query: match prendas_pedido descriptions exactly to one of the normalized values
$query = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where(function($q) use ($normalizedValues) {
        $first = true;
        foreach ($normalizedValues as $normalized) {
            if ($first) {
                $q->whereRaw(
                    "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(prendas_pedido.descripcion, '\r', ' '), '\n', ' '), ',', ' '), '\"', ' '), ':', ' '))) = LOWER(?)",
                    [$normalized]
                );
                $first = false;
            } else {
                $q->orWhereRaw(
                    "LOWER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(prendas_pedido.descripcion, '\r', ' '), '\n', ' '), ',', ' '), '\"', ' '), ':', ' '))) = LOWER(?)",
                    [$normalized]
                );
            }
        }
    });

$results = $query->pluck('id')->toArray();

echo "🔍 === RESULTADOS CON NUEVO ENFOQUE ===\n\n";
echo "Total de pedidos encontrados: " . count($results) . "\n";
echo "Pedidos: " . implode(', ', $results) . "\n\n";

// Compare with reality
$realPedidos = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where('prendas_pedido.descripcion', 'LIKE', '%napole%')
    ->pluck('id')
    ->toArray();

sort($realPedidos);

echo "=== COMPARACIÓN CON REALIDAD ===\n\n";
echo "Pedidos REALES con napole: " . implode(', ', $realPedidos) . "\n";
echo "Total real: " . count($realPedidos) . "\n\n";

// Check differences
$missing = array_diff($realPedidos, $results);
$extra = array_diff($results, $realPedidos);

if (empty($missing) && empty($extra)) {
    echo "✅ PERFECTO: Todos los pedidos coinciden!\n";
} else {
    if (!empty($missing)) {
        echo "❌ FALTAN: " . implode(', ', $missing) . "\n";
    }
    if (!empty($extra)) {
        echo "⚠️  SOBRAN: " . implode(', ', $extra) . "\n";
    }
    echo "\nTotal esperado: " . count($realPedidos) . ", Total obtenido: " . count($results) . "\n";
}
?>
