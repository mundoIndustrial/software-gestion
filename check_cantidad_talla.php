<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrendaPedido;

$prenda = PrendaPedido::first();
if ($prenda) {
    echo "cantidad_talla raw: " . $prenda->cantidad_talla . "\n";
    $decoded = json_decode($prenda->cantidad_talla, true);
    echo "Decoded:\n";
    var_dump($decoded);
    echo "\nFormatted description:\n";
    echo $prenda->formatted_description . "\n";
} else {
    echo "No prendas found\n";
}
