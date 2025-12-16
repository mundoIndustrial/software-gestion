<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Ver si existe un reflectivo_id en cotizaciones
$cotizacion = \App\Models\Cotizacion::where('es_borrador', true)->first();

if ($cotizacion) {
    echo "Cotización encontrada ID: " . $cotizacion->id . "\n";
    echo "Atributos:\n";
    foreach ($cotizacion->getAttributes() as $k => $v) {
        if (strpos($k, 'reflectivo') !== false) {
            echo "  ✓ $k: $v\n";
        }
    }
    echo "\n";
    
    // Buscar reflectivos asociados a esta cotización
    $reflectivos = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->with('fotos')->get();
    echo "Reflectivos con cotizacion_id=" . $cotizacion->id . ": " . count($reflectivos) . "\n";
    
    if (count($reflectivos) > 0) {
        foreach ($reflectivos as $ref) {
            echo "  - ID: " . $ref->id . ", Fotos: " . count($ref->fotos) . "\n";
            foreach ($ref->fotos as $foto) {
                echo "    Foto ID: " . $foto->id . ", URL: " . $foto->url . "\n";
            }
        }
    }
}
?>
