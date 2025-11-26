<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTADO ACTUAL ===\n\n";

$generos = DB::table('generos_prenda')->count();
echo "Géneros en BD: {$generos}\n";

$colores = DB::table('colores_prenda')->count();
echo "Colores en BD: {$colores}\n";

$telas = DB::table('telas_prenda')->count();
echo "Telas en BD: {$telas}\n";

$mangas = DB::table('tipos_manga')->count();
echo "Tipos de manga en BD: {$mangas}\n";

$broches = DB::table('tipos_broche')->count();
echo "Tipos de broche en BD: {$broches}\n";

echo "\n✅ Listo para prueba. El sistema creará automáticamente lo que falta.\n";
