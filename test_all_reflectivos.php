<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Ver todos los reflectivos con fotos y sus cotizaciones
$reflectivos = \App\Models\ReflectivoCotizacion::with('fotos')->get();

echo "Total de reflectivos: " . count($reflectivos) . "\n\n";

foreach ($reflectivos as $ref) {
    $fotos = \App\Models\ReflectivoCotizacionFoto::where('reflectivo_cotizacion_id', $ref->id)->get();
    if (count($fotos) > 0) {
        echo "Reflectivo ID: " . $ref->id . "\n";
        echo "  cotizacion_id: " . $ref->cotizacion_id . "\n";
        echo "  Fotos: " . count($fotos) . "\n";
        foreach ($fotos as $foto) {
            echo "    - " . $foto->ruta_original . " (URL: " . $foto->url . ")\n";
        }
        echo "\n";
    }
}
?>
