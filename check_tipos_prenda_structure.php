<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Registros en tipos_prenda ===\n";
$tipos = DB::table('tipos_prenda')->get();
echo "Total de registros: " . count($tipos) . "\n\n";

foreach ($tipos as $tipo) {
    echo "ID: {$tipo->id}\n";
    echo "  Nombre: {$tipo->nombre}\n";
    echo "  Código: {$tipo->codigo}\n";
    echo "  Palabras clave: {$tipo->palabras_clave}\n";
    echo "  Activo: " . ($tipo->activo ? 'SI' : 'NO') . "\n";
    echo "\n";
}
