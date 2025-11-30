<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orden = \App\Models\PedidoProduccion::with('prendas')->find(45446);

if (!$orden) {
    echo "Orden no encontrada\n";
    exit;
}

echo "=== ORDEN #45446 ===\n";
echo "Total de prendas: " . $orden->prendas->count() . "\n\n";

foreach ($orden->prendas as $i => $prenda) {
    echo "--- PRENDA " . ($i + 1) . " ---\n";
    echo "Nombre: " . $prenda->nombre_prenda . "\n";
    echo "Cantidad: " . $prenda->cantidad . "\n";
    echo "Cantidad Talla (JSON): " . ($prenda->cantidad_talla ?? "NULL") . "\n";
    echo "Descripción (primeros 400 chars):\n";
    echo substr($prenda->descripcion ?? "", 0, 400) . "\n";
    echo "\n";
}

echo "\n=== DESCRIPCIÓN GENERADA ===\n";
echo $orden->descripcion_prendas;
