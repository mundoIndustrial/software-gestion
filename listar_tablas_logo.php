<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n==========================================\n";
echo "  TABLAS LOGO EN LA BASE DE DATOS\n";
echo "==========================================\n\n";

$tablas = \DB::select('SHOW TABLES LIKE "%logo%"');

echo "Tablas encontradas: " . count($tablas) . "\n\n";

foreach ($tablas as $tabla) {
    $nombreTabla = array_values((array)$tabla)[0];
    
    echo "📋 " . strtoupper($nombreTabla) . "\n";
    echo "─────────────────────────────────────────\n";
    
    // Mostrar columnas
    $columnas = \DB::select('DESCRIBE ' . $nombreTabla);
    
    echo "Columnas:\n";
    foreach ($columnas as $col) {
        $null = $col->Null === 'YES' ? ' (nullable)' : '';
        $key = $col->Key !== '' ? ' [' . $col->Key . ']' : '';
        echo "   • " . str_pad($col->Field, 25) . " " . str_pad($col->Type, 20) . $null . $key . "\n";
    }
    
    // Contar registros
    $count = \DB::table($nombreTabla)->count();
    echo "\nRegistros: $count\n\n";
}

echo "==========================================\n\n";
