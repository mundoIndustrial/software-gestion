<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE TABLA tipos_cotizacion ===\n\n";

// Verificar si la tabla existe
if (DB::connection()->getSchemaBuilder()->hasTable('tipos_cotizacion')) {
    echo "✅ Tabla 'tipos_cotizacion' existe\n\n";
    
    // Obtener registros
    $registros = DB::table('tipos_cotizacion')->get();
    
    echo "📊 Registros encontrados: " . count($registros) . "\n\n";
    
    foreach ($registros as $registro) {
        echo "ID: {$registro->id}\n";
        echo "  Código: {$registro->codigo}\n";
        echo "  Nombre: {$registro->nombre}\n";
        echo "  Descripción: {$registro->descripcion}\n";
        echo "  Activo: " . ($registro->activo ? 'Sí' : 'No') . "\n\n";
    }
} else {
    echo "❌ Tabla 'tipos_cotizacion' NO existe\n";
}

echo "✅ Verificación completada\n";
