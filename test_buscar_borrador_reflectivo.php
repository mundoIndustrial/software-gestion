<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;

echo "\n=== BÚSQUEDA: Cotizaciones reflectivo BORRADOR con fotos ===\n\n";

$cotizaciones = Cotizacion::where('es_borrador', true)
    ->whereHas('reflectivoCotizacion.fotos')
    ->with('reflectivoCotizacion.fotos')
    ->limit(10)
    ->get();

echo "Encontradas: " . $cotizaciones->count() . "\n\n";

if ($cotizaciones->count() === 0) {
    echo "❌ No hay cotizaciones borrador con reflectivo y fotos\n\n";
    
    // Buscar solo borradores con reflectivo (sin importar fotos)
    echo "─── BUSCANDO: Solo borradores con reflectivo ───\n";
    $conReflectivo = Cotizacion::where('es_borrador', true)
        ->whereHas('reflectivoCotizacion')
        ->with('reflectivoCotizacion')
        ->limit(10)
        ->get();
    
    echo "Encontradas: " . $conReflectivo->count() . "\n\n";
    
    foreach ($conReflectivo as $cot) {
        $fotosCount = $cot->reflectivoCotizacion?->fotos ? count($cot->reflectivoCotizacion->fotos) : 0;
        echo "  Cotización #{$cot->id}: fotos = {$fotosCount}\n";
    }
    exit;
}

foreach ($cotizaciones as $cot) {
    echo "✅ Cotización #{$cot->id}:\n";
    echo "   Asesor ID: {$cot->asesor_id}\n";
    echo "   Es borrador: " . ($cot->es_borrador ? 'SÍ' : 'NO') . "\n";
    echo "   Reflectivo ID: " . ($cot->reflectivoCotizacion?->id ?? 'N/A') . "\n";
    echo "   Fotos: " . ($cot->reflectivoCotizacion?->fotos ? count($cot->reflectivoCotizacion->fotos) : 0) . "\n\n";
}
