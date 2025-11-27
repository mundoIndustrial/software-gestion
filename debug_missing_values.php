<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVESTIGAR VALORES FALTANTES ===\n\n";

$pedidosFaltantes = [101, 309, 319, 381, 492, 498, 506, 572, 591, 913, 958, 1364];

echo "Descripciones en los pedidos faltantes:\n\n";

foreach ($pedidosFaltantes as $pedidoId) {
    $descripciones = DB::table('prendas_pedido')
        ->where('pedido_produccion_id', $pedidoId)
        ->pluck('descripcion')
        ->unique()
        ->toArray();
    
    echo "Pedido $pedidoId:\n";
    foreach ($descripciones as $desc) {
        // Show the raw value and normalized version
        $normalized = preg_replace('/[\r\n]+/', ' ', $desc);
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        echo "  RAW: " . substr($desc, 0, 80) . "\n";
        echo "  NRM: " . substr($normalized, 0, 80) . "\n";
        echo "  LEN: " . strlen($desc) . " -> " . strlen($normalized) . "\n\n";
    }
}

echo "\n\n=== TODAS LAS DESCRIPCIONES UNICAS CON NAPOLE ===\n\n";

$allDescriptions = DB::table('prendas_pedido')
    ->where('descripcion', 'LIKE', '%napole%')
    ->distinct()
    ->pluck('descripcion')
    ->toArray();

echo "Total unique descriptions with 'napole': " . count($allDescriptions) . "\n\n";

foreach ($allDescriptions as $i => $desc) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $desc);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    echo ($i+1) . ". " . substr($normalized, 0, 70) . "...\n";
}
?>
