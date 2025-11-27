<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE PEDIDOS_PRODUCCION ===\n\n";

$columns = DB::select("DESCRIBE pedidos_produccion");
echo "Columnas:\n";
foreach ($columns as $col) {
    if (strpos($col->Field, 'descrip') !== false || strpos($col->Field, 'pren') !== false) {
        echo "  - {$col->Field}: {$col->Type}\n";
    }
}

echo "\n=== COMPARAR VALORES ===\n\n";

// Tomar un pedido con "napole"
$pedido = DB::table('pedidos_produccion as pp')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pp.id')
    ->where('prendas_pedido.descripcion', 'LIKE', '%napole%')
    ->first();

if ($pedido) {
    echo "Pedido ID: " . $pedido->pedido_produccion_id . "\n\n";
    echo "De prendas_pedido.descripcion:\n";
    echo "  " . substr($pedido->descripcion, 0, 100) . "...\n\n";
    
    $pedidoProduccion = DB::table('pedidos_produccion')
        ->where('id', $pedido->pedido_produccion_id)
        ->first();
    
    if ($pedidoProduccion) {
        echo "De pedidos_produccion.descripcion_prendas:\n";
        echo "  " . substr($pedidoProduccion->descripcion_prendas, 0, 100) . "...\n\n";
        
        echo "¿Son iguales? " . ($pedido->descripcion === $pedidoProduccion->descripcion_prendas ? "SÍ" : "NO") . "\n";
    }
}

echo "\n\n=== VERIFICAR RELACION CON PRENDAS ===\n\n";

$prenda = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->pedido_produccion_id)
    ->first();

if ($prenda) {
    echo "Prenda ID: {$prenda->id}\n";
    echo "Prenda tipo: {$prenda->prenda_id}\n\n";
    
    $tipoPrenda = DB::table('prendas')
        ->where('id', $prenda->prenda_id)
        ->first();
    
    if ($tipoPrenda) {
        echo "Nombre del tipo de prenda: {$tipoPrenda->nombre}\n";
        echo "Descripción de prenda: {$prenda->descripcion}\n";
        echo "Variaciones: {$prenda->variaciones}\n\n";
        
        // Ver si se construye así
        $construida = trim("{$tipoPrenda->nombre} {$prenda->descripcion} {$prenda->variaciones}");
        echo "Construida así: " . substr($construida, 0, 100) . "...\n\n";
        
        $enDropdown = DB::table('prendas_pedido')
            ->where('id', $prenda->id)
            ->pluck('descripcion')
            ->first();
        
        echo "En dropdown (descripcion): " . substr($enDropdown, 0, 100) . "...\n\n";
    }
}
?>
