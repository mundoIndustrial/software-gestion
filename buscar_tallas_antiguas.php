<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar prendas antiguas que podrían tener tallas embedidas
$prendas = \App\Models\PrendaPedido::where('cantidad_talla', null)
    ->whereRaw("descripcion LIKE '%TALLA%'")
    ->whereRaw("descripcion NOT LIKE '%TALLA:%'") // Excluir las que ya usamos
    ->limit(20)
    ->get();

echo "Encontradas: " . count($prendas) . " prendas con 'TALLA' pero sin 'TALLA:'\n\n";

foreach ($prendas as $i => $prenda) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Prenda " . ($i + 1) . " (ID: {$prenda->id})\n";
    echo str_repeat("=", 80) . "\n";
    echo substr($prenda->descripcion, 0, 600);
    echo "\n";
}
