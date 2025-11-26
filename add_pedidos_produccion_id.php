<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🔧 Agregando columna pedidos_produccion_id a procesos_prenda...\n\n";

try {
    if (!Schema::hasColumn('procesos_prenda', 'pedidos_produccion_id')) {
        DB::statement('ALTER TABLE procesos_prenda ADD COLUMN pedidos_produccion_id BIGINT UNSIGNED AFTER id');
        echo "✅ Columna pedidos_produccion_id agregada\n";
        
        // Agregar index
        DB::statement('CREATE INDEX idx_procesos_prenda_pedido ON procesos_prenda(pedidos_produccion_id)');
        echo "✅ Index creado\n";
    } else {
        echo "⚠️  Columna ya existe\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Completado\n";
