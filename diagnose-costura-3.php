<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\BodegaDetalleTalla;

echo "=== TESTING QUERY OBTENERPENDIENTERPOAERA ===\n\n";

// Reproducir la query del método obtenerPendientesPorArea
$area = 'Costura';

$query = BodegaDetalleTalla::porArea($area)
    ->porEstado('Pendiente');

echo "After porArea and porEstado:\n";
echo "Total registros: " . $query->count() . "\n";

// Excluir pedidos anulados
$query->whereNotIn('numero_pedido', function($subquery) {
    $subquery->select('numero_pedido')
        ->from('pedidos_produccion')
        ->where('estado', 'Anulada');
});

echo "After excluding Anulada:\n";
echo "Total registros: " . $query->count() . "\n";

// Agrupar por numero_pedido
$query->select([
    'numero_pedido',
    DB::raw('MIN(id) as id'),
    DB::raw('MIN(empresa) as empresa'),
    DB::raw('MIN(asesor) as asesor'),
    DB::raw('MIN(prenda_nombre) as prenda_nombre'),
    DB::raw('MIN(area) as area'),
    DB::raw('MIN(estado_bodega) as estado_bodega'),
    DB::raw('MIN(fecha_entrega) as fecha_entrega'),
    DB::raw('MIN(created_at) as created_at'),
    DB::raw('SUM(cantidad) as cantidad_total'),
    DB::raw('SUM(pendientes) as pendientes_total'),
    DB::raw('MIN(talla) as talla_ejemplo')
])
->groupBy('numero_pedido')
->orderBy('numero_pedido', 'desc');

echo "After groupBy:\n";
$results = $query->get();
echo "Total registros: " . $results->count() . "\n";

foreach ($results as $row) {
    echo "\nPedido #{$row->numero_pedido}\n";
    echo "  - Empresa: {$row->empresa}\n";
    echo "  - Asesor: {$row->asesor}\n";
    echo "  - Prenda: {$row->prenda_nombre}\n";
    echo "  - Cantidad: {$row->cantidad_total}\n";
}

// Ver el SQL generado
echo "\n\n=== SQL GENERADO ===\n";
$query = BodegaDetalleTalla::porArea($area)
    ->porEstado('Pendiente')
    ->whereNotIn('numero_pedido', function($subquery) {
        $subquery->select('numero_pedido')
            ->from('pedidos_produccion')
            ->where('estado', 'Anulada');
    })
    ->select([
        'numero_pedido',
        DB::raw('MIN(id) as id'),
        DB::raw('MIN(empresa) as empresa'),
        DB::raw('MIN(asesor) as asesor'),
        DB::raw('MIN(prenda_nombre) as prenda_nombre'),
        DB::raw('MIN(area) as area'),
        DB::raw('MIN(estado_bodega) as estado_bodega'),
        DB::raw('MIN(fecha_entrega) as fecha_entrega'),
        DB::raw('MIN(created_at) as created_at'),
        DB::raw('SUM(cantidad) as cantidad_total'),
        DB::raw('SUM(pendientes) as pendientes_total'),
        DB::raw('MIN(talla) as talla_ejemplo')
    ])
    ->groupBy('numero_pedido')
    ->orderBy('numero_pedido', 'desc');

echo $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";
