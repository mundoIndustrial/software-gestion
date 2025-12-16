<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\LogoCotizacion;

$cotizacionId = 59;
$cotizacion = Cotizacion::find($cotizacionId);

echo "=== COTIZACIÓN $cotizacionId ===\n";
if (!$cotizacion) {
    echo "❌ Cotización no encontrada\n";
    exit;
}

echo "✅ Cotización encontrada\n";
$prendas = $cotizacion->prendas()->get();
echo "Prendas: " . count($prendas) . "\n";

foreach ($prendas as $prenda) {
    echo "\n  [Prenda ID: " . $prenda->id . "]\n";
    echo "  Nombre: " . $prenda->nombre_prenda . "\n";
    $fotos = $prenda->fotos()->get();
    echo "  Fotos: " . count($fotos) . "\n";
    if (count($fotos) > 0) {
        foreach ($fotos as $foto) {
            echo "    - " . $foto->ruta_original . "\n";
        }
    }
    
    $telaFotos = $prenda->telaFotos()->get();
    echo "  Telas: " . count($telaFotos) . "\n";
    if (count($telaFotos) > 0) {
        foreach ($telaFotos as $tela) {
            echo "    - " . $tela->ruta_original . "\n";
        }
    }
    
    $variantes = $prenda->variantes()->get();
    echo "  Variantes: " . count($variantes) . "\n";
}

$logo = $cotizacion->logoCotizacion;
echo "\n\n[LOGO]\n";
if (!$logo) {
    echo "No hay logo\n";
} else {
    echo "✅ Logo encontrado (ID: " . $logo->id . ")\n";
    $logoFotos = $logo->fotos()->get();
    echo "  Fotos: " . count($logoFotos) . "\n";
    if (count($logoFotos) > 0) {
        foreach ($logoFotos as $foto) {
            echo "    - " . $foto->ruta_original . "\n";
        }
    }
}
?>
