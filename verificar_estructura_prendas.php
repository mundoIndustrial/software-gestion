<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICAR ESTRUCTURA TABLA prendas_pedido ===\n";

try {
    // Obtener estructura de la tabla
    $columns = DB::select("DESCRIBE prendas_pedido");
    
    echo "\n--- Columnas de la tabla prendas_pedido ---\n";
    foreach ($columns as $column) {
        echo sprintf("%-20s | %-20s | %-10s | %-10s | %s\n", 
            $column->Field, 
            $column->Type, 
            $column->Null, 
            $column->Key, 
            $column->Default
        );
    }
    
    // Verificar si hay datos
    echo "\n--- Verificar datos existentes ---\n";
    $count = DB::table('prendas_pedido')->count();
    echo "Total de registros: " . $count . "\n";
    
    if ($count > 0) {
        $sample = DB::table('prendas_pedido')->limit(3)->get();
        echo "\n--- Muestra de datos ---\n";
        foreach ($sample as $row) {
            echo "ID: " . $row->id . " | ";
            // Mostrar todas las columnas que puedan ser relaciones
            foreach ($columns as $column) {
                if (strpos(strtolower($column->Field), 'pedido') !== false) {
                    echo $column->Field . ": " . $row->{$column->Field} . " | ";
                }
            }
            echo "nombre_prenda: " . ($row->nombre_prenda ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
