<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║            VERIFICACIÓN FINAL DE DUPLICADOS\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Analizar TODAS las cotizaciones
$cotizaciones = DB::table('cotizaciones')->get();

echo "📊 Analizando " . $cotizaciones->count() . " cotizaciones...\n\n";

$totalDuplicados = 0;
$cotizacionesConDuplicados = [];

foreach ($cotizaciones as $cot) {
    // Obtener fotos de tela para esta cotización
    $fotosDuplicadas = DB::table('prenda_tela_fotos_cot')
        ->join('prendas_cot', 'prendas_cot.id', '=', 'prenda_tela_fotos_cot.prenda_cot_id')
        ->where('prendas_cot.cotizacion_id', $cot->id)
        ->select('prenda_tela_fotos_cot.ruta_original', DB::raw('COUNT(*) as cantidad'))
        ->groupBy('prenda_tela_fotos_cot.ruta_original')
        ->having('cantidad', '>', 1)
        ->get();
    
    if ($fotosDuplicadas->count() > 0) {
        echo "❌ Cotización ID: {$cot->id} - TIENE DUPLICADOS\n";
        foreach ($fotosDuplicadas as $foto) {
            echo "   • Ruta aparece {$foto->cantidad} veces\n";
            $totalDuplicados += ($foto->cantidad - 1);
        }
        $cotizacionesConDuplicados[] = $cot->id;
    }
}

if (count($cotizacionesConDuplicados) === 0) {
    echo "✅ NO HAY DUPLICADOS EN LA BASE DE DATOS\n";
} else {
    echo "\n⚠️ Encontradas " . count($cotizacionesConDuplicados) . " cotizaciones con duplicados\n";
    echo "   Cotizaciones: " . implode(', ', $cotizacionesConDuplicados) . "\n";
    echo "   Total duplicados: $totalDuplicados\n";
}

// Contar fotos totales por cotización para verificar integridad
echo "\n\n📈 RESUMEN POR COTIZACIÓN (últimas 5):\n\n";

$cotizacionesRecientes = DB::table('cotizaciones')
    ->orderByDesc('id')
    ->limit(5)
    ->get();

foreach ($cotizacionesRecientes as $cot) {
    $totalFotos = DB::table('prenda_tela_fotos_cot')
        ->join('prendas_cot', 'prendas_cot.id', '=', 'prenda_tela_fotos_cot.prenda_cot_id')
        ->where('prendas_cot.cotizacion_id', $cot->id)
        ->count();
    
    echo "Cotización ID {$cot->id}: $totalFotos fotos de tela\n";
}

echo "\n";
?>
