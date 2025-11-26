<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== ESTRUCTURA DE TABLAS ACTUALES ===\n\n";

// Verificar tablas importantes
$tables = ['registros_por_orden', 'tabla_original', 'cotizaciones', 'users'];

foreach($tables as $table) {
    if(Schema::hasTable($table)) {
        echo "✅ $table\n";
        $cols = Schema::getColumnListing($table);
        if($table === 'registros_por_orden') {
            foreach($cols as $col) echo "   - $col\n";
        }
    } else {
        echo "❌ $table NO EXISTE\n";
    }
}

echo "\n=== DATOS EN TABLAS CRÍTICAS ===\n\n";

// registros_por_orden
$count_registros = DB::table('registros_por_orden')->count();
echo "registros_por_orden: $count_registros registros\n";

$sample = DB::table('registros_por_orden')->first();
if($sample) {
    echo "\nPrimer registro:\n";
    foreach((array)$sample as $key => $value) {
        echo "  $key: $value\n";
    }
}

// tabla_original
echo "\n\ntabla_original:\n";
$count_pedidos = DB::table('tabla_original')->count();
$unique_pedidos = DB::table('tabla_original')->distinct()->count('pedido');
echo "  Total registros: $count_pedidos\n";
echo "  Pedidos únicos: $unique_pedidos\n";

$sample_pedido = DB::table('tabla_original')->first();
if($sample_pedido) {
    echo "\nPrimer pedido:\n";
    echo "  pedido: {$sample_pedido->pedido}\n";
    echo "  cliente: {$sample_pedido->cliente}\n";
    echo "  asesora: {$sample_pedido->asesora}\n";
    echo "  estado: {$sample_pedido->estado}\n";
    echo "  fecha_de_creacion_de_orden: {$sample_pedido->fecha_de_creacion_de_orden}\n";
    echo "  despacho: {$sample_pedido->despacho}\n";
}

// Verificar si ya existen las nuevas tablas
echo "\n\n=== NUEVAS TABLAS ===\n\n";

if(Schema::hasTable('pedidos_produccion')) {
    $count = DB::table('pedidos_produccion')->count();
    echo "✅ pedidos_produccion: $count registros\n";
}

if(Schema::hasTable('prendas_pedido')) {
    $count = DB::table('prendas_pedido')->count();
    echo "✅ prendas_pedido: $count registros\n";
}

if(Schema::hasTable('procesos_prenda')) {
    $count = DB::table('procesos_prenda')->count();
    echo "✅ procesos_prenda: $count registros\n";
}
?>
