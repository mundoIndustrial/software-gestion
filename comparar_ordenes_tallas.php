<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar órdenes que tengan prendas con cantidad_talla migrada recientemente
$orden = \App\Models\PedidoProduccion::whereHas('prendas', function($q) {
    $q->whereNotNull('cantidad_talla');
})->orderBy('id', 'desc')->first();

if (!$orden) {
    echo "No se encontraron órdenes con prendas migradas\n";
    exit;
}

echo "=== ORDEN #" . $orden->id . " (con tallas) ===\n";
$prenda = $orden->prendas->first();
echo "Descripción de prenda generada:\n";
echo $prenda->descripcion_prendas;
echo "\n\n";

// Buscar una orden que NO tenga cantidad_talla
$ordenVieja = \App\Models\PedidoProduccion::whereHas('prendas', function($q) {
    $q->whereNull('cantidad_talla');
})->orderBy('id', 'desc')->first();

if ($ordenVieja) {
    echo "\n=== ORDEN #" . $ordenVieja->id . " (sin tallas) ===\n";
    $prendaVieja = $ordenVieja->prendas->first();
    echo "Descripción de prenda generada:\n";
    echo $prendaVieja->descripcion_prendas;
}
