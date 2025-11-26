<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TIPOS DE MANGA ===\n";
$tipos = DB::table('tipos_manga')->get();
echo "Total: " . count($tipos) . "\n\n";
foreach ($tipos as $tipo) {
    echo "ID: {$tipo->id}\n";
    echo "  Nombre: {$tipo->nombre}\n";
    echo "\n";
}

echo "\n=== TELAS ===\n";
$telas = DB::table('telas_prenda')->get();
echo "Total: " . count($telas) . "\n\n";
foreach ($telas as $tela) {
    echo "ID: {$tela->id}\n";
    echo "  Nombre: {$tela->nombre}\n";
    echo "\n";
}
