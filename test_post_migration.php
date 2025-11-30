<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Buscar órdenes que tengan prendas con cantidad_talla migrada recientemente
$orden = \App\Models\PedidoProduccion::with('prendas')
    ->whereHas('prendas', function($q) {
        $q->whereNotNull('cantidad_talla');
    })
    ->orderBy('id', 'desc')
    ->first();

if (!$orden) {
    echo "No se encontraron órdenes con prendas migradas\n";
    exit;
}

echo "=== ORDEN #" . $orden->id . " (con tallas migradas) ===\n";
$prenda = $orden->prendas->first();
echo "Prenda: " . $prenda->nombre_prenda . "\n";
echo "Cantidad Talla: " . $prenda->cantidad_talla . "\n";
echo "\nDescripción generada:\n";
echo $orden->descripcion_prendas;
echo "\n\n";

// Buscar una orden que NO tenga cantidad_talla
$ordenVieja = \App\Models\PedidoProduccion::with('prendas')
    ->whereHas('prendas', function($q) {
        $q->whereNull('cantidad_talla')
          ->whereNotNull('descripcion');
    })
    ->orderBy('id', 'desc')
    ->first();

if ($ordenVieja) {
    echo "\n=== ORDEN #" . $ordenVieja->id . " (sin tallas) ===\n";
    $prendaVieja = $ordenVieja->prendas->first();
    echo "Prenda: " . $prendaVieja->nombre_prenda . "\n";
    echo "Cantidad Talla: " . $prendaVieja->cantidad_talla . "\n";
    echo "\nDescripción generada:\n";
    echo $ordenVieja->descripcion_prendas;
}
