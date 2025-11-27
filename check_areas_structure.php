<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE LA TABLA areas ===\n\n";

try {
    $columns = DB::select("DESCRIBE areas");
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo sprintf("  - %-30s | Tipo: %-20s | Null: %-5s | Key: %-5s | Default: %s\n",
            $col->Field,
            $col->Type,
            $col->Null,
            $col->Key,
            $col->Default ?? 'NULL'
        );
    }
} catch (\Exception $e) {
    echo "Error: La tabla 'areas' no existe.\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== ÁREAS EXISTENTES ===\n\n";

try {
    $areas = DB::table('areas')->get();
    echo "Total de áreas: " . count($areas) . "\n\n";
    foreach ($areas as $area) {
        echo "  - ID: {$area->id}, Nombre: {$area->nombre}\n";
    }
} catch (\Exception $e) {
    echo "No hay áreas o hay error: " . $e->getMessage() . "\n";
}
?>
