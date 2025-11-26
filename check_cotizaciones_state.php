<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Estado de Cotizaciones y Variantes ===\n\n";

// Última cotización
$cotizacion = DB::table('cotizaciones')->latest('id')->first();
if ($cotizacion) {
    echo "Última cotización: ID {$cotizacion->id}\n";
    $prendas = DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacion->id)->get();
    echo "Prendas: " . count($prendas) . "\n";
    foreach ($prendas as $prenda) {
        $variantes = DB::table('variantes_prenda')->where('prenda_cotizacion_id', $prenda->id)->count();
        echo "  - Prenda cotización ID {$prenda->id}: {$variantes} variantes\n";
    }
}
