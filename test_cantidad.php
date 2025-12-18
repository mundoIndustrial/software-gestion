<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrendaPedido;

// Obtener la última prenda creada
$prenda = PrendaPedido::orderBy('id', 'desc')->first();

if (!$prenda) {
    echo "❌ No hay prendas\n";
    exit;
}

echo "📦 TEST DE CANTIDAD DINÁMICA\n";
echo "============================\n\n";
echo "Prenda ID: {$prenda->id}\n";
echo "Nombre: {$prenda->nombre_prenda}\n";
echo "Attributes['cantidad']: " . ($prenda->attributes['cantidad'] ?? 'NULL') . "\n";
echo "Attributes['cantidad_talla']: " . ($prenda->attributes['cantidad_talla'] ?? 'NULL') . "\n";
echo "\nAccediendo a cantidad_talla como propiedad:\n";
echo "  cantidad_talla: " . json_encode($prenda->cantidad_talla) . "\n";
echo "\nAccediendo a cantidad (con accessor):\n";
echo "  cantidad: " . $prenda->cantidad . "\n";
echo "\nToArray():\n";
print_r($prenda->toArray());
