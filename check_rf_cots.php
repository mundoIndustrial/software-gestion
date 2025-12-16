<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check distinct types
$types = \App\Models\Cotizacion::distinct('tipo')->pluck('tipo');
echo "Tipos de cotizaciones:\n";
foreach ($types as $type) {
    $count = \App\Models\Cotizacion::where('tipo', $type)->count();
    echo "- $type: $count\n";
}

// Check cotizations 67 y 68
echo "\n\nCotización 67:\n";
$cot67 = \App\Models\Cotizacion::find(67);
if ($cot67) {
    echo "- Tipo: " . $cot67->tipo . "\n";
    echo "- Reflectivo: " . ($cot67->reflectivoCotizacion ? 'SÍ' : 'NO') . "\n";
}

echo "\nCotización 68:\n";
$cot68 = \App\Models\Cotizacion::find(68);
if ($cot68) {
    echo "- Tipo: " . $cot68->tipo . "\n";
    echo "- Reflectivo: " . ($cot68->reflectivoCotizacion ? 'SÍ' : 'NO') . "\n";
}
