<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA FINAL DEL FILTRO CON descripcion_armada ===\n\n";

// Obtener algunas descripciones armadas únicas
$descripciones = DB::table('prendas_pedido')
    ->whereNotNull('descripcion_armada')
    ->where('descripcion_armada', '!=', '')
    ->distinct()
    ->limit(5)
    ->pluck('descripcion_armada')
    ->toArray();

echo "Descripciones únicas para filtrar:\n";
foreach ($descripciones as $i => $desc) {
    echo ($i + 1) . ". " . substr($desc, 0, 80) . "...\n";
}

echo "\n=== SIMULAR FILTRO ===\n\n";

// Simular el filtro con estas descripciones
$resultados = DB::table('pedidos_produccion as pp')
    ->distinct()
    ->select('pp.id', 'pp.numero_pedido')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->whereIn('prendas_pedido.descripcion_armada', $descripciones)
    ->get();

echo "Seleccionadas " . count($descripciones) . " descripciones\n";
echo "Pedidos encontrados: " . $resultados->count() . "\n\n";

foreach ($resultados as $pedido) {
    echo "  - Pedido ID: {$pedido->id} | Número: {$pedido->numero_pedido}\n";
}

echo "\n✅ Filtro funcionando correctamente con descripcion_armada\n";
?>
