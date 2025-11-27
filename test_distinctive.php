<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA CON PALABRAS CLAVE DISTINTIVAS ===\n\n";

$values = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion')
    ->toArray();

echo "Valores seleccionados: " . count($values) . "\n\n";

$keywords = [];
foreach ($values as $value) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    $words = explode(' ', strtolower($normalized));
    
    foreach ($words as $word) {
        $cleaned = trim($word);
        if (strlen($cleaned) >= 3 && preg_match('/[a-záéíóúñü]/i', $cleaned)) {
            $common = ['con', 'del', 'que', 'una', 'dos', 'los', 'las', 'por', 'para', 'mas', 'mas', 'moda', 'jean', 'jeans', 'bota'];
            if (!in_array($cleaned, $common)) {
                $keywords[$cleaned] = true;
            }
        }
    }
}

$keywords = array_keys($keywords);

echo "Palabras clave distintivas: " . count($keywords) . "\n";
foreach ($keywords as $kw) {
    echo "  - $kw\n";
}
echo "\n";

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
                    ['%' . $keyword . '%']
                );
                $first = false;
            } else {
                $q->orWhereRaw(
                    "LOWER(prendas_pedido.descripcion) LIKE ?",
                    ['%' . $keyword . '%']
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
echo "Esperado: " . implode(', ', $realPedidos) . " (16)\n";
echo "Obtenido: " . implode(', ', $results) . "\n\n";

$missing = array_diff($realPedidos, $results);
$extra = array_diff($results, $realPedidos);

if (empty($missing) && empty($extra)) {
    echo "✅ ¡PERFECTO!\n";
} else {
    if (!empty($missing)) echo "❌ FALTAN: " . implode(', ', $missing) . "\n";
    if (!empty($extra)) echo "⚠️  SOBRAN: " . implode(', ', $extra) . "\n";
}
?>
