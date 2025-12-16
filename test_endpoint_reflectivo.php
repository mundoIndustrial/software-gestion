<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;

echo "\n=== SIMULACIÓN: ENDPOINT getReflectivoForEdit ===\n\n";

// Buscar una cotización con reflectivo y fotos
$cotizacion = Cotizacion::whereHas('reflectivoCotizacion.fotos')
    ->with(['reflectivoCotizacion.fotos'])
    ->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones con reflectivo y fotos encontradas\n\n";
    exit;
}

echo "✅ Cotización encontrada: #{$cotizacion->id}\n\n";

// Simular lo que devuelve el endpoint
$response = [
    'success' => true,
    'data' => [
        'cotizacion' => $cotizacion->toArray(),
        'prendas' => [],
        'reflectivo' => $cotizacion->reflectivoCotizacion?->toArray(),
        'fotos' => $cotizacion->reflectivoCotizacion?->fotos ? $cotizacion->reflectivoCotizacion->fotos->toArray() : [],
    ],
];

echo "📦 RESPUESTA JSON DEL ENDPOINT:\n";
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";

// Verificar que los URLs están presentes
echo "✅ VERIFICACIÓN DE URLs EN FOTOS:\n";
if (isset($response['data']['fotos']) && !empty($response['data']['fotos'])) {
    foreach ($response['data']['fotos'] as $index => $foto) {
        echo "  Foto {$index}: ";
        if (isset($foto['url'])) {
            echo "✅ url = {$foto['url']}\n";
        } else {
            echo "❌ url NO ENCONTRADO\n";
            echo "     Disponibles: " . implode(', ', array_keys($foto)) . "\n";
        }
    }
} else {
    echo "  ❌ Sin fotos en la respuesta\n";
}

echo "\n";
