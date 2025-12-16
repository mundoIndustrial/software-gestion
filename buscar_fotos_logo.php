<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n🔍 Buscando fotos de logo con IDs 60, 61, 62...\n\n";

$fotos = DB::table('logo_fotos_cot')
    ->whereIn('id', [60, 61, 62])
    ->get();

if ($fotos->count() > 0) {
    echo "✅ Se encontraron {$fotos->count()} fotos:\n\n";
    foreach ($fotos as $foto) {
        echo "📷 Foto ID: {$foto->id}\n";
        echo "   - Logo Cotización ID: {$foto->logo_cotizacion_id}\n";
        echo "   - Ruta: {$foto->ruta_webp}\n";
        echo "   - Orden: {$foto->orden}\n";
        echo "   - Created: {$foto->created_at}\n\n";
    }
} else {
    echo "❌ No se encontraron fotos con esos IDs\n\n";
    
    echo "📋 Últimas 10 fotos en logo_fotos_cot:\n";
    $ultimasFotos = DB::table('logo_fotos_cot')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($ultimasFotos as $foto) {
        echo "   - ID: {$foto->id}, Logo ID: {$foto->logo_cotizacion_id}, Created: {$foto->created_at}\n";
    }
}

echo "\n";
