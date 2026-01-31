<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "=== ESTRUCTURA TABLA consecutivos_recibos_pedidos ===\n";
    
    // Verificar si la tabla existe
    if (!Schema::hasTable('consecutivos_recibos_pedidos')) {
        echo "❌ La tabla 'consecutivos_recibos_pedidos' NO existe\n";
        exit(1);
    }
    
    echo "✅ Tabla 'consecutivos_recibos_pedidos' existe\n\n";
    
    // Obtener columnas
    $columns = DB::getSchemaBuilder()->getColumnListing('consecutivos_recibos_pedidos');
    
    echo "📋 Columnas encontradas:\n";
    foreach ($columns as $column) {
        echo "   - $column\n";
    }
    
    echo "\n📊 Estructura completa:\n";
    $structure = DB::select("DESCRIBE consecutivos_recibos_pedidos");
    
    foreach ($structure as $col) {
        echo "   - {$col->Field} ({$col->Type}) {$col->Null} {$col->Key} {$col->Default}\n";
    }
    
    // Verificar si tiene prenda_pedido_id
    $hasPrendaId = in_array('prenda_pedido_id', $columns);
    
    echo "\n🔍 ¿Tiene columna 'prenda_pedido_id'? " . ($hasPrendaId ? "✅ SÍ" : "❌ NO") . "\n";
    
    // Mostrar datos existentes
    echo "\n📄 Datos actuales:\n";
    $data = DB::table('consecutivos_recibos_pedidos')->get();
    
    if ($data->isEmpty()) {
        echo "   (No hay datos)\n";
    } else {
        foreach ($data as $row) {
            echo "   ID: {$row->id}, Pedido: {$row->pedido_produccion_id}, Tipo: {$row->tipo_recibo}, Consecutivo: {$row->consecutivo_actual}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
