<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;

$cot = Cotizacion::with('prendasCotizaciones')->first();
if ($cot) {
    echo "✓ Cotización: " . $cot->numero_cotizacion . PHP_EOL;
    echo "✓ Prendas: " . $cot->prendasCotizaciones->count() . PHP_EOL;
    if ($cot->prendasCotizaciones->count() > 0) {
        echo "✓ Primera prenda: " . $cot->prendasCotizaciones->first()->nombre_producto . PHP_EOL;
    }
} else {
    echo "✗ No hay cotizaciones" . PHP_EOL;
}
