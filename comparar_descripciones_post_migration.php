<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Obtener una orden vieja que ya fue migrada
$orden = \App\Models\PedidoProduccion::find(43133);

echo "=== ORDEN #43133 ===\n";
echo "Descripción de prenda generada:\n";
echo $orden->prendas->first()->descripcion_prendas;
echo "\n\n";

// Comparar con una orden nueva
$orden2 = \App\Models\PedidoProduccion::find(45451);

echo "\n=== ORDEN #45451 (nueva) ===\n";
echo "Descripción de prenda generada:\n";
echo $orden2->prendas->first()->descripcion_prendas;
