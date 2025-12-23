<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📸 Fotos en prenda_cot_id=32 (Draft #54)\n";
echo "─────────────────────────────────────────────────────────\n";

$fotos = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', 32)
    ->get(['id', 'prenda_tela_cot_id', 'ruta_original', 'ruta_webp']);

foreach ($fotos as $foto) {
    echo "Foto ID={$foto->id}:\n";
    echo "  prenda_tela_cot_id: {$foto->prenda_tela_cot_id}\n";
    echo "  ruta_original: {$foto->ruta_original}\n";
    echo "  ruta_webp: {$foto->ruta_webp}\n";
}

echo "\n📸 Fotos en prenda_cot_id=33 (Enviada #55)\n";
echo "─────────────────────────────────────────────────────────\n";

$fotos33 = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', 33)
    ->get(['id', 'prenda_tela_cot_id', 'ruta_original', 'ruta_webp']);

echo "Total de fotos: {$fotos33->count()}\n";
foreach ($fotos33 as $foto) {
    echo "Foto ID={$foto->id}:\n";
    echo "  prenda_tela_cot_id: {$foto->prenda_tela_cot_id}\n";
    echo "  ruta_original: {$foto->ruta_original}\n";
    echo "  ruta_webp: {$foto->ruta_webp}\n";
}

echo "\n";
