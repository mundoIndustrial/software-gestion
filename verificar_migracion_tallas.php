<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar que las migraciones funcionaron
$prenda = \App\Models\PrendaPedido::where('id', 2927)->first();

echo "=== Prenda ID 2927 ===\n";
echo "Descripción:\n" . $prenda->descripcion . "\n\n";
echo "Cantidad Talla JSON:\n" . $prenda->cantidad_talla . "\n\n";
echo "Parsed:\n" . json_encode(json_decode($prenda->cantidad_talla, true), JSON_PRETTY_PRINT) . "\n\n";

// Revisar una nueva con nuevo formato
$prenda2 = \App\Models\PrendaPedido::where('id', 2912)->first();
echo "\n=== Prenda ID 2912 (nuevo formato) ===\n";
echo "Descripción (primeras líneas):\n" . substr($prenda2->descripcion, 0, 300) . "\n\n";
echo "Cantidad Talla JSON:\n" . $prenda2->cantidad_talla . "\n\n";
echo "Parsed:\n" . json_encode(json_decode($prenda2->cantidad_talla, true), JSON_PRETTY_PRINT) . "\n";
