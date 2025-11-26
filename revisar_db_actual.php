<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== REVISIÓN ACTUAL DE LA BASE DE DATOS ===\n\n";

$tables = ['pedidos_produccion', 'prendas_pedido', 'procesos_prenda'];

foreach($tables as $table) {
    if(!Schema::hasTable($table)) {
        echo "❌ $table NO EXISTE\n";
        continue;
    }
    
    echo "✅ $table\n";
    $cols = Schema::getColumnListing($table);
    foreach($cols as $col) {
        echo "   - $col\n";
    }
    
    $count = DB::table($table)->count();
    echo "   Registros: $count\n\n";
}

// Muestra de datos
echo "=== MUESTRA DE DATOS ===\n\n";

if(Schema::hasTable('pedidos_produccion')) {
    $pedido = DB::table('pedidos_produccion')->first();
    if($pedido) {
        echo "Pedido ejemplo: #{$pedido->numero_pedido}\n";
        echo "  Estado: {$pedido->estado}\n";
        echo "  Cliente: {$pedido->cliente}\n";
    }
}

if(Schema::hasTable('prendas_pedido')) {
    $prenda = DB::table('prendas_pedido')->first();
    if($prenda) {
        echo "\nPrenda ejemplo:\n";
        echo "  Nombre: {$prenda->nombre_prenda}\n";
        echo "  Cantidad: {$prenda->cantidad}\n";
        if(isset($prenda->cantidad_talla)) {
            echo "  Tallas: {$prenda->cantidad_talla}\n";
        }
    }
}

if(Schema::hasTable('procesos_prenda')) {
    $proceso = DB::table('procesos_prenda')->first();
    if($proceso) {
        echo "\nProceso ejemplo:\n";
        echo "  Proceso: {$proceso->proceso}\n";
        echo "  Fecha inicio: {$proceso->fecha_inicio}\n";
        echo "  Estado: {$proceso->estado_proceso}\n";
    }
}
?>
