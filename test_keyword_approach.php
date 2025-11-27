<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA CON PALABRAS CLAVE UNICAS ===\n\n";

// Get the 17 selected description values
$values = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion')
    ->toArray();

echo "Valores seleccionados: " . count($values) . "\n\n";

// Extract first 2-3 words from each value as identifier
$keywords = [];
foreach ($values as $value) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    $words = explode(' ', $normalized);
    $identifier = [];
    for ($i = 0; $i < min(3, count($words)); $i++) {
        $w = trim($words[$i]);
        if (strlen($w) >= 2 && !is_numeric($w)) {
            $identifier[] = $w;
        }
    }
    
    if (!empty($identifier)) {
        $searchTerm = implode(' ', $identifier);
        if (!in_array($searchTerm, $keywords)) {
            $keywords[] = $searchTerm;
        }
    }
}

echo "Palabras clave extraídas: " . count($keywords) . "\n";
foreach ($keywords as $kw) {
    echo "  - $kw\n";
}
echo "\n";

// Query: match descriptions containing any of these keywords
$query = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where(function($q) use ($keywords) {
        $first = true;
        foreach ($keywords as $keyword) {
            if ($first) {
                $q->whereRaw(
                    "LOWER(prendas_pedido.descripcion) LIKE ?",
                    ['%' . strtolower($keyword) . '%']
                );
                $first = false;
            } else {
                $q->orWhereRaw(
                    "LOWER(prendas_pedido.descripcion) LIKE ?",
                    ['%' . strtolower($keyword) . '%']
                );
            }
        }
    });

$results = $query->pluck('id')->toArray();

echo "🔍 === RESULTADOS ===\n\n";
echo "Total de pedidos encontrados: " . count($results) . "\n";
sort($results);
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
