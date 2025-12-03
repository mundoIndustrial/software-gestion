<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cot = \App\Models\Cotizacion::with('tipoCotizacion')->find(36);
echo "Tipo: " . ($cot->tipoCotizacion ? $cot->tipoCotizacion->nombre : 'NULL') . "\n";
echo "Tipo ID: " . ($cot->tipoCotizacion ? $cot->tipoCotizacion->id : 'NULL') . "\n";
?>
