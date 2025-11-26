<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TIPOS DE MANGA CREADOS ===\n";
$mangas = DB::table('tipos_manga')->get();
echo "Total: " . count($mangas) . "\n\n";

foreach ($mangas as $manga) {
    echo "ID: {$manga->id}\n";
    echo "  Nombre: {$manga->nombre}\n";
    echo "\n";
}
