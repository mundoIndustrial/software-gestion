<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar prendas SIN tallas y CON descripción para ver formatos variados
$prendas = \App\Models\PrendaPedido::where('cantidad_talla', null)
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

foreach ($prendas as $i => $prenda) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Prenda " . ($i + 1) . " (ID: {$prenda->id})\n";
    echo str_repeat("=", 80) . "\n";
    echo substr($prenda->descripcion, 0, 800);
    echo "\n";
}
