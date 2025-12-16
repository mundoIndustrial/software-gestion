<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use App\Models\ReflectivoCotizacionFoto;

echo "\n=== TEST: IMÁGENES DEL REFLECTIVO ===\n\n";

// Buscar una cotización con reflectivo
$cotizaciones = Cotizacion::whereHas('reflectivoCotizacion')->with('reflectivoCotizacion.fotos')->limit(5)->get();

if ($cotizaciones->isEmpty()) {
    echo "❌ No hay cotizaciones con reflectivo encontradas\n\n";
    exit;
}

echo "✅ Cotizaciones con reflectivo encontradas: " . $cotizaciones->count() . "\n\n";

foreach ($cotizaciones as $cot) {
    echo "─── COTIZACIÓN #{$cot->id} ───\n";
    
    if (!$cot->reflectivoCotizacion) {
        echo "  ⚠️ Sin reflectivo\n\n";
        continue;
    }
    
    $reflectivo = $cot->reflectivoCotizacion;
    echo "  Reflectivo ID: {$reflectivo->id}\n";
    echo "  Fotos count: " . ($reflectivo->fotos ? count($reflectivo->fotos) : 0) . "\n";
    
    if ($reflectivo->fotos && count($reflectivo->fotos) > 0) {
        echo "  \n  📸 FOTOS:\n";
        
        foreach ($reflectivo->fotos as $foto) {
            echo "    ─ ID: {$foto->id}\n";
            echo "      ruta_original: {$foto->ruta_original}\n";
            echo "      ruta_webp: " . ($foto->ruta_webp ?? 'null') . "\n";
            echo "      url (accessor): {$foto->url}\n";
            echo "      orden: {$foto->orden}\n";
        }
        
        // Probar toArray()
        echo "  \n  📦 TOARRAY():\n";
        $fotosArray = $reflectivo->fotos->toArray();
        echo "    JSON: " . json_encode($fotosArray[0] ?? [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "  ⚠️ Sin fotos\n";
    }
    
    echo "\n";
}
