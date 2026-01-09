<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtener los triggers de forma más simple
echo "=== TRIGGERS EN LA BASE DE DATOS ===\n";
try {
    $triggers = DB::select("SHOW TRIGGERS");
    
    if (empty($triggers)) {
        echo "No hay triggers definidos\n";
    } else {
        foreach ($triggers as $trigger) {
            echo "Trigger encontrado:\n";
            print_r($trigger);
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error obteniendo triggers: " . $e->getMessage() . "\n";
}

// Obtener constraints
echo "\n=== CONSTRAINTS DE LA TABLA pedidos_produccion ===\n";
$constraints = DB::select("SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = 'pedidos_produccion' AND TABLE_SCHEMA = DATABASE()");

foreach ($constraints as $constraint) {
    echo "Constraint: {$constraint->CONSTRAINT_NAME} ({$constraint->CONSTRAINT_TYPE})\n";
}

// Intentar ver si hay referencias a tipo_cotizaciones en las constraints
echo "\n=== BUSCANDO REFERENCIAS A tipo_cotizaciones ===\n";
$result = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = 'tipo_cotizaciones' AND TABLE_SCHEMA = DATABASE()");

if (empty($result)) {
    echo "No hay referencias directas a tipo_cotizaciones\n";
} else {
    foreach ($result as $ref) {
        echo "Referencia encontrada: {$ref->CONSTRAINT_NAME}\n";
        echo "  Tabla origen: {$ref->COLUMN_NAME}\n";
        echo "  Tabla destino: {$ref->REFERENCED_TABLE_NAME}.{$ref->REFERENCED_COLUMN_NAME}\n";
    }
}
