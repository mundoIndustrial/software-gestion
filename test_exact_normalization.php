<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA CON NORMALIZACION EXACTA ===\n\n";

$values = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion')
    ->toArray();

echo "Valores seleccionados: " . count($values) . "\n\n";

// Normalize values
echo "Valores normalizados:\n";
$normalizedValues = [];
foreach ($values as $value) {
    $normalized = str_replace(['\r', '\n', ',', '"', ':', '='], ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    $normalizedValues[] = $normalized;
    echo "  " . substr($normalized, 0, 80) . "...\n";
}
echo "\n";

// Query con normalizacion igual
$query = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where(function($q) use ($normalizedValues) {
        $first = true;
        foreach ($normalizedValues as $normalized) {
            if ($first) {
                $q->whereRaw(
                    "LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(prendas_pedido.descripcion, '\r', ' '), '\n', ' '), ',', ' '), '\"', ' '), ':', ' '), '=', ' '), '  +', ' '))) = LOWER(?)",
                    [$normalized]
                );
                $first = false;
            } else {
                $q->orWhereRaw(
                    "LOWER(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(prendas_pedido.descripcion, '\r', ' '), '\n', ' '), ',', ' '), '\"', ' '), ':', ' '), '=', ' '), '  +', ' '))) = LOWER(?)",
                    [$normalized]
                );
            }
        }
    });

$results = $query->pluck('id')->toArray();

echo "🔍 === RESULTADOS ===\n\n";
echo "Total de pedidos encontrados: " . count($results) . "\n";
sort($results);
echo "Pedidos: " . implode(', ', $results) . "\n\n";

$realPedidos = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where('prendas_pedido.descripcion', 'LIKE', '%napole%')
    ->pluck('id')
    ->toArray();

sort($realPedidos);

echo "=== COMPARACIÓN ===\n\n";
echo "Esperado: " . implode(', ', $realPedidos) . "\n";
echo "Obtenido: " . implode(', ', $results) . "\n\n";

$missing = array_diff($realPedidos, $results);
$extra = array_diff($results, $realPedidos);

if (empty($missing) && empty($extra)) {
    echo "✅ ¡PERFECTO!\n";
} else {
    if (!empty($missing)) echo "❌ FALTAN: " . implode(', ', $missing) . "\n";
    if (!empty($extra)) echo "⚠️  SOBRAN: " . implode(', ', $extra) . "\n";
    echo "\nTotal: " . count($results) . " vs esperado " . count($realPedidos) . "\n";
}
?>
