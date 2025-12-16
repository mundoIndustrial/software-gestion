<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Revisar cotizaciones 67 y 68
foreach ([67, 68] as $cot_id) {
    echo "\n=== COTIZACIÓN {$cot_id} ===\n";
    $reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cot_id)->first();
    if ($reflectivo) {
        echo "✓ Reflectivo ID: " . $reflectivo->id . "\n";
        // Get raw value from database
        $rawValue = \DB::table('reflectivo_cotizacion')->where('id', $reflectivo->id)->first()?->ubicacion;
        echo "  Raw ubicacion from DB: " . $rawValue . "\n";
        echo "  Casted ubicacion: " . json_encode($reflectivo->ubicacion) . "\n";
        echo "  Type of ubicacion: " . gettype($reflectivo->ubicacion) . "\n";
    } else {
        echo "✗ No hay reflectivo\n";
    }
}
