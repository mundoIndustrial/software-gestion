<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;

// Obtener cotización #36
$cotizacion = Cotizacion::with([
    'usuario',
    'tipoCotizacion',
    'prendasCotizaciones.variantes.color',
    'prendasCotizaciones.variantes.tela',
    'prendasCotizaciones.variantes.tipoManga',
    'prendasCotizaciones.variantes.tipoBroche',
    'logoCotizacion'
])->find(36);

if (!$cotizacion) {
    echo "❌ Cotización #36 no encontrada\n";
    exit(1);
}

echo "✅ Cotización #36 encontrada\n";
echo "================================================\n";
echo "Cliente: " . $cotizacion->cliente . "\n";
echo "Estado: " . ($cotizacion->es_borrador ? 'Borrador' : $cotizacion->estado) . "\n";
echo "\n";

// Revisar LogoCotizacion
$logo = $cotizacion->logoCotizacion;
if ($logo) {
    echo "📋 LOGO COTIZACIÓN:\n";
    echo "================================================\n";
    
    // Revisar técnicas
    echo "\n🔧 Técnicas:\n";
    echo "Type: " . gettype($logo->tecnicas) . "\n";
    echo "Is Array: " . (is_array($logo->tecnicas) ? "YES" : "NO") . "\n";
    if (is_array($logo->tecnicas)) {
        echo "Count: " . count($logo->tecnicas) . "\n";
        foreach ($logo->tecnicas as $i => $tecnica) {
            echo "  [$i] Type: " . gettype($tecnica) . " - Value: " . json_encode($tecnica) . "\n";
        }
    }
    
    // Revisar observaciones_tecnicas
    echo "\n📝 Observaciones Técnicas:\n";
    echo "Type: " . gettype($logo->observaciones_tecnicas) . "\n";
    echo "Is Array: " . (is_array($logo->observaciones_tecnicas) ? "YES" : "NO") . "\n";
    echo "Value: " . json_encode($logo->observaciones_tecnicas) . "\n";
    
    // Revisar ubicaciones
    echo "\n📍 Ubicaciones:\n";
    echo "Type: " . gettype($logo->ubicaciones) . "\n";
    echo "Is Array: " . (is_array($logo->ubicaciones) ? "YES" : "NO") . "\n";
    if (is_array($logo->ubicaciones)) {
        echo "Count: " . count($logo->ubicaciones) . "\n";
        foreach ($logo->ubicaciones as $i => $ubicacion) {
            echo "  [$i] Type: " . gettype($ubicacion) . " - Value: " . json_encode($ubicacion) . "\n";
        }
    }
    
    // Revisar observaciones_generales
    echo "\n💬 Observaciones Generales:\n";
    echo "Type: " . gettype($logo->observaciones_generales) . "\n";
    echo "Is Array: " . (is_array($logo->observaciones_generales) ? "YES" : "NO") . "\n";
    echo "Value: " . json_encode($logo->observaciones_generales) . "\n";
} else {
    echo "⚠️  No hay LogoCotización asociada\n";
}

// Revisar prendas
echo "\n\n👕 PRENDAS COTIZACIÓN:\n";
echo "================================================\n";
$prendas = $cotizacion->prendasCotizaciones;
if ($prendas && count($prendas) > 0) {
    foreach ($prendas as $idx => $prenda) {
        echo "\nPrenda $idx: " . $prenda->nombre_producto . "\n";
        echo "  Tallas Type: " . gettype($prenda->tallas) . "\n";
        if (is_array($prenda->tallas)) {
            echo "  Tallas: " . json_encode($prenda->tallas) . "\n";
        } else {
            echo "  Tallas Value: " . $prenda->tallas . "\n";
        }
        
        echo "  Fotos Type: " . gettype($prenda->fotos) . "\n";
        if (is_array($prenda->fotos)) {
            echo "  Fotos Count: " . count($prenda->fotos) . "\n";
        }
        
        echo "  Telas Type: " . gettype($prenda->telas) . "\n";
        if (is_array($prenda->telas)) {
            echo "  Telas Count: " . count($prenda->telas) . "\n";
        }
    }
} else {
    echo "⚠️  No hay prendas\n";
}

echo "\n✅ Debug completado\n";
?>
