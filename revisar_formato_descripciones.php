<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$prendas = \App\Models\PrendaPedido::where('cantidad_talla', null)
    ->limit(5)
    ->get();

foreach ($prendas as $prenda) {
    echo "=== PRENDA ID: {$prenda->id} ===\n";
    echo "Descripción:\n";
    echo $prenda->descripcion ?? "SIN DESCRIPCIÓN";
    echo "\n\n";
}
