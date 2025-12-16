<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$id = 66;
$cot = \App\Models\Cotizacion::with([
    'cliente',
    'prendas.fotos',
    'prendas.telaFotos',
    'prendas.tallas',
    'prendas.variantes.manga',
    'prendas.variantes.broche',
    'logoCotizacion.fotos',
    'tipoCotizacion',
    'reflectivoCotizacion'
])->findOrFail($id);

echo "✅ Cotización ID: " . $cot->id . PHP_EOL;
echo "📦 Prendas count: " . count($cot->prendas) . PHP_EOL;

$cotArray = $cot->toArray();
echo "📦 Prendas en toArray: " . (isset($cotArray['prendas']) ? count($cotArray['prendas']) : 0) . PHP_EOL;

if (count($cot->prendas) > 0) {
    $prenda = $cot->prendas[0];
    echo "\n🧥 Primera prenda:\n";
    echo "  - ID: " . $prenda->id . PHP_EOL;
    echo "  - Nombre: " . $prenda->nombre_producto . PHP_EOL;
    echo "  - Descripción: " . $prenda->descripcion . PHP_EOL;
    echo "  - Fotos: " . count($prenda->fotos) . PHP_EOL;
    
    $prendaArray = $prenda->toArray();
    echo "\n🔍 Prenda toArray():\n";
    echo "  - Keys: " . implode(', ', array_keys($prendaArray)) . PHP_EOL;
    echo "  - Fotos en array: " . (isset($prendaArray['fotos']) ? count($prendaArray['fotos']) : 0) . PHP_EOL;
}

// Verificar si las prendas se mantienen en toArray
echo "\n\n=== COTIZACION TO ARRAY ===\n";
var_dump(isset($cotArray['prendas']) ? count($cotArray['prendas']) : 'NO PRENDAS');
