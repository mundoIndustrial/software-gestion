<?php
/**
 * SCRIPT DE REPARACIÓN: Recrear fotos de logo para cotización 16
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    \Illuminate\Http\Request::capture()
);

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🔧 REPARACIÓN: Recrear fotos de logo en BD\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1. Obtener logo 15 (cotización 16)
echo "1️⃣ Buscando logo 15...\n";
$logo = DB::table('logo_cotizaciones')->where('id', 15)->first();

if (!$logo) {
    echo "❌ Logo 15 no existe\n";
    exit(1);
}

echo "✅ Logo encontrado: ID {$logo->id}, Cotización {$logo->cotizacion_id}\n\n";

// 2. Verificar si tiene fotos
echo "2️⃣ Verificando fotos existentes...\n";
$fotos = DB::table('logo_fotos_cot')->where('logo_cotizacion_id', 15)->count();
echo "   Fotos actualmente: {$fotos}\n\n";

if ($fotos > 0) {
    echo "❌ Ya tiene fotos, eliminando para recrear...\n";
    DB::table('logo_fotos_cot')->where('logo_cotizacion_id', 15)->delete();
    echo "✅ Fotos eliminadas\n\n";
}

// 3. Recrear 3 fotos simuladas
echo "3️⃣ Recreando 3 fotos de logo...\n\n";

$fotosACrear = [
    [
        'logo_cotizacion_id' => 15,
        'ruta_original' => 'cotizaciones/16/logo/BORDADO1_1766520148_b51d.webp',
        'ruta_webp' => 'cotizaciones/16/logo/BORDADO1_1766520148_b51d.webp',
        'orden' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'logo_cotizacion_id' => 15,
        'ruta_original' => 'cotizaciones/16/logo/BORDADO2_1766520148_3089.webp',
        'ruta_webp' => 'cotizaciones/16/logo/BORDADO2_1766520148_3089.webp',
        'orden' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'logo_cotizacion_id' => 15,
        'ruta_original' => 'cotizaciones/16/logo/bordado3_1766520148_b771.webp',
        'ruta_webp' => 'cotizaciones/16/logo/bordado3_1766520148_b771.webp',
        'orden' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

foreach ($fotosACrear as $foto) {
    $id = DB::table('logo_fotos_cot')->insertGetId($foto);
    echo "✅ Foto {$foto['orden']} creada: ID {$id}\n";
    echo "   Ruta: {$foto['ruta_webp']}\n";
}

echo "\n4️⃣ Verificando resultado...\n\n";

$fotosNuevas = DB::table('logo_fotos_cot')
    ->where('logo_cotizacion_id', 15)
    ->orderBy('orden')
    ->get();

echo "✅ Fotos en BD: " . count($fotosNuevas) . "\n";
foreach ($fotosNuevas as $foto) {
    echo "   [{$foto->orden}] ID {$foto->id}: {$foto->ruta_webp}\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "✨ REPARACIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

echo "PRÓXIMOS PASOS:\n";
echo "1. Abre navegador en http://localhost/cotizaciones/editar/16\n";
echo "2. Haz clic en Paso 4 (Revisar cotización)\n";
echo "3. Recarga la página (F5) para cargar el borrador\n";
echo "4. Abre DevTools (F12) → Console\n";
echo "5. Haz clic en ENVIAR\n";
echo "6. Busca en console: 'Encontradas imágenes existentes en galería: 3'\n";
echo "7. Busca en console: 'Ruta de foto existente agregada'\n";
echo "8. Revisa laravel.log para 'fotos_guardadas_count'\n";
echo "\n";
?>
