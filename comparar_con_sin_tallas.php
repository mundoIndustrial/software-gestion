<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PrendaPedido;

echo "Prendas CON cantidad_talla:\n";
$conTallas = PrendaPedido::whereNotNull('cantidad_talla')
    ->limit(5)
    ->get();

foreach ($conTallas as $prenda) {
    echo "  • {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
}

echo "\nPrendas SIN cantidad_talla:\n";
$sinTallas = PrendaPedido::whereNull('cantidad_talla')
    ->limit(5)
    ->get();

foreach ($sinTallas as $prenda) {
    echo "  • {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
}

// Ahora verificar qué retorna getDescripcionPrendasAttribute para ambos casos
echo "\n════════════════════════════════════════════════════════\n";
echo "Comparación de descripción_prendas:\n\n";

// Una prenda CON tallas
if ($conTallas->count() > 0) {
    $prendaConTallas = $conTallas->first();
    $pedidoConTallas = $prendaConTallas->pedido()->with('prendas')->first();
    echo "Orden CON tallas:\n";
    echo $pedidoConTallas->descripcion_prendas;
    echo "\n\n";
}

// Una prenda SIN tallas
if ($sinTallas->count() > 0) {
    $prendaSinTallas = $sinTallas->first();
    $pedidoSinTallas = $prendaSinTallas->pedido()->with('prendas')->first();
    echo "Orden SIN tallas:\n";
    echo $pedidoSinTallas->descripcion_prendas;
    echo "\n";
}
