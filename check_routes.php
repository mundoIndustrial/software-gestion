<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel');

use Illuminate\Support\Facades\DB;

echo "\n=== VERIFICACIÓN DE RUTAS EN BD ===\n\n";

// Tabla 1: prenda_fotos_pedido
echo "1. PRENDA_FOTOS_PEDIDO:\n";
$count_storage = DB::table('prenda_fotos_pedido')->where('ruta_original', 'like', '/storage/%')->count();
$count_no_storage = DB::table('prenda_fotos_pedido')->where('ruta_original', 'not like', '/storage/%')->whereNotNull('ruta_original')->count();
echo "   - Con /storage/: $count_storage\n";
echo "   - Sin /storage/: $count_no_storage\n";

$sample = DB::table('prenda_fotos_pedido')->limit(1)->first();
if ($sample) {
    echo "   - Ejemplo ruta_original: " . $sample->ruta_original . "\n";
    echo "   - Ejemplo ruta_webp: " . $sample->ruta_webp . "\n";
}

// Tabla 2: prenda_fotos_logo_pedido
echo "\n2. PRENDA_FOTOS_LOGO_PEDIDO:\n";
$count_storage = DB::table('prenda_fotos_logo_pedido')->where('ruta_original', 'like', '/storage/%')->count();
$count_no_storage = DB::table('prenda_fotos_logo_pedido')->where('ruta_original', 'not like', '/storage/%')->whereNotNull('ruta_original')->count();
echo "   - Con /storage/: $count_storage\n";
echo "   - Sin /storage/: $count_no_storage\n";

$sample = DB::table('prenda_fotos_logo_pedido')->limit(1)->first();
if ($sample) {
    echo "   - Ejemplo ruta_original: " . $sample->ruta_original . "\n";
}

// Tabla 3: prenda_fotos_tela_pedido
echo "\n3. PRENDA_FOTOS_TELA_PEDIDO:\n";
$count_storage = DB::table('prenda_fotos_tela_pedido')->where('ruta_original', 'like', '/storage/%')->count();
$count_no_storage = DB::table('prenda_fotos_tela_pedido')->where('ruta_original', 'not like', '/storage/%')->whereNotNull('ruta_original')->count();
echo "   - Con /storage/: $count_storage\n";
echo "   - Sin /storage/: $count_no_storage\n";

$sample = DB::table('prenda_fotos_tela_pedido')->limit(1)->first();
if ($sample) {
    echo "   - Ejemplo ruta_original: " . $sample->ruta_original . "\n";
}

echo "\n";
