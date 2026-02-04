<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "=== Modificando tabla consecutivos_recibos ===\n\n";

    // 1. Modificar el enum para agregar COSTURA-BODEGA
    echo "1. Agregando COSTURA-BODEGA al enum...\n";
    
    // Obtener la conexión
    $connection = DB::connection();
    $pdo = $connection->getPdo();
    
    // Ejecutar la alteración del enum
    $alterQuery = "ALTER TABLE consecutivos_recibos MODIFY COLUMN tipo_recibo ENUM('COSTURA','ESTAMPADO','BORDADO','REFLECTIVO','GENERAL','DTF','SUBLIMADO','COSTURA-BODEGA')";
    
    try {
        $pdo->exec($alterQuery);
        echo "   ✓ Enum modificado exitosamente\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
            echo "   ✓ Enum ya estaba actualizado o sin cambios\n";
        } else {
            throw $e;
        }
    }

    // 2. Verificar si el registro ya existe
    echo "\n2. Verificando registro COSTURA-BODEGA...\n";
    
    $exists = DB::table('consecutivos_recibos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->exists();

    if ($exists) {
        echo "   ✓ Registro COSTURA-BODEGA ya existe\n";
    } else {
        // 3. Insertar el registro
        echo "   ✓ Registro no existe, insertando...\n";
        
        DB::table('consecutivos_recibos')->insert([
            'tipo_recibo' => 'COSTURA-BODEGA',
            'consecutivo_actual' => 0,
            'consecutivo_inicial' => 0,
            'año' => 2026,
            'activo' => 1,
            'notas' => 'Consecutivo para costura bodega - Configurar valor inicial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "   ✓ Registro COSTURA-BODEGA insertado exitosamente\n";
    }

    // 4. Mostrar todos los registros
    echo "\n3. Registros en consecutivos_recibos:\n";
    $registros = DB::table('consecutivos_recibos')
        ->orderBy('id')
        ->get();
    
    foreach ($registros as $reg) {
        echo "   ID: {$reg->id} | Tipo: {$reg->tipo_recibo} | Año: {$reg->año} | Activo: {$reg->activo}\n";
    }

    echo "\n✓ Proceso completado exitosamente\n";

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
