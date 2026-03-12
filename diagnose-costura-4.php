<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUGGING whereNotIn ISSUE ===\n\n";

// Ver qué devuelve la subquery
$numerosPedidoAnulados = DB::table('pedidos_produccion')
    ->where('estado', 'Anulada')
    ->pluck('numero_pedido');

echo "Números de pedido CON estado = 'Anulada':\n";
echo "Total: " . $numerosPedidoAnulados->count() . "\n";
foreach ($numerosPedidoAnulados as $n) {
    echo "  - {$n}\n";
}

echo "\n";

// Ver los números de pedido en bodega_detalles_talla con Costura Pendiente
$numerosBodega = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->distinct('numero_pedido')
    ->pluck('numero_pedido');

echo "Números de pedido en bodega_detalles_talla con Costura Pendiente:\n";
echo "Total: " . $numerosBodega->count() . "\n";
foreach ($numerosBodega as $n) {
    echo "  - {$n}\n";
}

echo "\n";

// Verificar cuáles de estos están anulados
$anulados = $numerosPedidoAnulados->intersect($numerosBodega);
echo "Números de pedido QUE ESTÁN EN AMBAS (serían excluidos):\n";
echo "Total: " . $anulados->count() . "\n";
foreach ($anulados as $n) {
    echo "  - {$n}\n";
}

echo "\n";

// Ahora probar la query sin whereNotIn
echo "=== QUERY SIN whereNotIn ===\n";
$results = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->select([
        'numero_pedido',
        DB::raw('MIN(id) as id'),
        DB::raw('MIN(empresa) as empresa'),
    ])
    ->groupBy('numero_pedido')
    ->get();

echo "Resultados sin whereNotIn: " . $results->count() . "\n\n";
foreach ($results as $row) {
    echo "  - Pedido #{$row->numero_pedido}: {$row->empresa}\n";
}

echo "\n";

// Ver el problema específico del whereNotIn
echo "=== PROBLEMA DEL whereNotIn ===\n";
$sql = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente');

echo "Antes de whereNotIn: " . $sql->count() . " registros\n";

$sql->whereNotIn('numero_pedido', function($subquery) {
    $subquery->select('numero_pedido')
        ->from('pedidos_produccion')
        ->where('estado', 'Anulada');
});

echo "Después de whereNotIn: " . $sql->count() . " registros\n";

// Verificar los tipos de datos
echo "\n=== TIPOS DE DATOS ===\n";
$cosuturaSample = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->first();

echo "bodega_detalles_talla.numero_pedido: " . gettype($cosuturaSample->numero_pedido) . " = '" . $cosuturaSample->numero_pedido . "'\n";

$anulSample = DB::table('pedidos_produccion')
    ->where('estado', 'Anulada')
    ->first();

if ($anulSample) {
    echo "pedidos_produccion.numero_pedido (Anulada): " . gettype($anulSample->numero_pedido) . " = '" . $anulSample->numero_pedido . "'\n";
} else {
    echo "No hay pedidos anulados\n";
}
