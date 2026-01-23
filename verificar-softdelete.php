<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

// Ver en BD directamente (incluye deleted_at)
echo "=== DIRECTAMENTE DE LA BD ===\n";
$bdPedidos = DB::table('pedidos_produccion')
    ->where('estado', 'pendiente_cartera')
    ->select('id', 'numero_pedido', 'estado', 'deleted_at')
    ->get();

foreach ($bdPedidos as $p) {
    echo "ID: " . $p->id . " | Número: " . $p->numero_pedido . " | Deleted_at: " . ($p->deleted_at ?? 'NULL') . "\n";
}

// Ver con SoftDeletes (excluye borrados)
echo "\n=== CON ELOQUENT (SoftDeletes) ===\n";
$eloquentPedidos = PedidoProduccion::where('estado', 'pendiente_cartera')->get();
echo "Total encontrados: " . $eloquentPedidos->count() . "\n";

// Ver incluidos borrados
echo "\n=== CON ELOQUENT (withTrashed) ===\n";
$conTrashed = PedidoProduccion::withTrashed()
    ->where('estado', 'pendiente_cartera')
    ->get();

foreach ($conTrashed as $p) {
    echo "ID: " . $p->id . " | Número: " . $p->numero_pedido . " | Deleted_at: " . ($p->deleted_at ?? 'NULL') . "\n";
}
