<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Buscar una cotización borrador con reflectivo
$cotizaciones = \App\Models\Cotizacion::where('es_borrador', true)->limit(5)->get();

echo "Cotizaciones borrador encontradas: " . count($cotizaciones) . "\n\n";

foreach ($cotizaciones as $cot) {
    echo "--- Cotización ID: " . $cot->id . " ---\n";
    echo "Columnas en BD:\n";
    foreach ($cot->getAttributes() as $k => $v) {
        if ($k === 'especificaciones' || $k === 'observaciones') {
            echo "  $k: [JSON truncado]\n";
        } else {
            echo "  $k: $v\n";
        }
    }
    echo "\n";
}
?>
