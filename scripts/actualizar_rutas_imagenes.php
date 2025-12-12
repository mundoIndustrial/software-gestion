<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "🔄 Actualizando rutas de imágenes en la BD...\n";

// Actualizar prenda_fotos_cot
$fotosActualizadas = DB::table('prenda_fotos_cot')
    ->where('ruta_original', 'like', '%storage-serve%')
    ->orWhere('ruta_webp', 'like', '%storage-serve%')
    ->update([
        'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
        'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
    ]);

echo "✅ Fotos de prendas actualizadas: $fotosActualizadas\n";

// Actualizar prenda_tela_fotos
$telasActualizadas = DB::table('prenda_tela_fotos')
    ->where('ruta_original', 'like', '%storage-serve%')
    ->orWhere('ruta_webp', 'like', '%storage-serve%')
    ->update([
        'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
        'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
    ]);

echo "✅ Fotos de telas actualizadas: $telasActualizadas\n";

// Actualizar logo_fotos
$logoActualizadas = DB::table('logo_fotos')
    ->where('ruta_original', 'like', '%storage-serve%')
    ->orWhere('ruta_webp', 'like', '%storage-serve%')
    ->update([
        'ruta_original' => DB::raw("REPLACE(ruta_original, '/storage-serve/', '/storage/')"),
        'ruta_webp' => DB::raw("REPLACE(ruta_webp, '/storage-serve/', '/storage/')")
    ]);

echo "✅ Fotos de logos actualizadas: $logoActualizadas\n";

echo "\n✅ Todas las rutas han sido actualizadas correctamente.\n";
