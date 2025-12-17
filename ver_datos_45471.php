<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrendaPedido;

$prendas = PrendaPedido::where('numero_pedido', 45471)->get();

foreach ($prendas as $prenda) {
    echo "\n========================================\n";
    echo "PRENDA: {$prenda->nombre_prenda}\n";
    echo "========================================\n";
    echo "color_id: " . ($prenda->color_id ?? 'NULL') . "\n";
    echo "tela_id: " . ($prenda->tela_id ?? 'NULL') . "\n";
    echo "tipo_manga_id: " . ($prenda->tipo_manga_id ?? 'NULL') . "\n";
    echo "\n--- DESCRIPCION ---\n";
    echo $prenda->descripcion ?? 'NULL';
    echo "\n\n--- DESCRIPCION VARIACIONES ---\n";
    echo $prenda->descripcion_variaciones ?? 'NULL';
    echo "\n";
}
