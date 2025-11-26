<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Reparando constraint de cotizacion_id...\n\n";

try {
    // Primero, eliminar el constraint si existe
    DB::statement('ALTER TABLE pedidos_produccion DROP FOREIGN KEY pedidos_produccion_cotizacion_id_foreign');
    echo "✅ Constraint eliminado\n";
} catch (\Exception $e) {
    echo "⚠️  No se pudo eliminar (probablemente no existe): " . $e->getMessage() . "\n";
}

try {
    // Hacer que cotizacion_id sea nullable
    DB::statement('ALTER TABLE pedidos_produccion MODIFY COLUMN cotizacion_id BIGINT UNSIGNED NULL');
    echo "✅ Columna cotizacion_id hecha nullable\n";
} catch (\Exception $e) {
    echo "❌ Error modificando columna: " . $e->getMessage() . "\n";
}

echo "\n✅ Reparación completada\n";
