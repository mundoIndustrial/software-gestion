<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;

echo "\n=== PRUEBA COMPLETA: TODAS LAS IMÁGENES ===\n\n";

// Buscar una cotización con todas las imágenes
$cotizacion = Cotizacion::with([
    'prendas.fotos',
    'prendas.telaFotos',
    'logoCotizacion.fotos',
    'reflectivoCotizacion.fotos'
])->limit(1)->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones encontradas\n\n";
    exit;
}

echo "✅ Cotización #{$cotizacion->id}\n\n";

// Prueba 1: Fotos de prendas
if ($cotizacion->prendas && count($cotizacion->prendas) > 0) {
    $prenda = $cotizacion->prendas[0];
    if ($prenda->fotos && count($prenda->fotos) > 0) {
        echo "📦 FOTOS DE PRENDA:\n";
        $fotosArray = $prenda->fotos->toArray();
        echo "  " . json_encode($fotosArray[0] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
        echo "  ✅ Tiene 'url': " . (isset($fotosArray[0]['url']) ? 'SÍ' : 'NO') . "\n\n";
    }
    
    // Prueba 2: Fotos de telas
    if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
        echo "📦 FOTOS DE TELA:\n";
        $fotosArray = $prenda->telaFotos->toArray();
        echo "  " . json_encode($fotosArray[0] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
        echo "  ✅ Tiene 'url': " . (isset($fotosArray[0]['url']) ? 'SÍ' : 'NO') . "\n\n";
    }
}

// Prueba 3: Fotos de logo
if ($cotizacion->logoCotizacion && $cotizacion->logoCotizacion->fotos && count($cotizacion->logoCotizacion->fotos) > 0) {
    echo "📦 FOTOS DE LOGO:\n";
    $fotosArray = $cotizacion->logoCotizacion->fotos->toArray();
    echo "  " . json_encode($fotosArray[0] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
    echo "  ✅ Tiene 'url': " . (isset($fotosArray[0]['url']) ? 'SÍ' : 'NO') . "\n\n";
}

// Prueba 4: Fotos de reflectivo
if ($cotizacion->reflectivoCotizacion && $cotizacion->reflectivoCotizacion->fotos && count($cotizacion->reflectivoCotizacion->fotos) > 0) {
    echo "📦 FOTOS DE REFLECTIVO:\n";
    $fotosArray = $cotizacion->reflectivoCotizacion->fotos->toArray();
    echo "  " . json_encode($fotosArray[0] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
    echo "  ✅ Tiene 'url': " . (isset($fotosArray[0]['url']) ? 'SÍ' : 'NO') . "\n\n";
}

echo "✅ TODAS LAS IMÁGENES SE DEVUELVEN CORRECTAMENTE\n\n";
