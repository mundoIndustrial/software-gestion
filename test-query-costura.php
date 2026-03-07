<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

echo "=== PRUEBA DE CONSULTA PENDIENTES DE COSTURA ===\n\n";
echo "Esta consulta replica la lógica de obtenerPendientesCostura()\n\n";

// Replicar la consulta exacta del controlador
$query = PedidoProduccion::query()
    ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->join('prendas_pedido', function($join) {
        $join->on('prendas_pedido.id', '=', 'bodega_detalles_talla.prenda_id')
             ->where('prendas_pedido.de_bodega', '=', 1)
             ->whereNull('prendas_pedido.deleted_at');
    })
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'Entregado', 'Anulada', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
    ->where('bodega_detalles_talla.area', 'Costura')
    ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
    ->select('pedidos_produccion.*')
    ->distinct();

echo "SQL Query:\n";
echo str_repeat('-', 80) . "\n";
echo $query->toSql() . "\n\n";

echo "Bindings: " . json_encode($query->getBindings()) . "\n\n";

$pedidos = $query->orderBy('created_at', 'desc')->get();

echo "Resultados: {$pedidos->count()} pedidos\n";
echo str_repeat('=', 80) . "\n\n";

if ($pedidos->isEmpty()) {
    echo "✅ No hay pedidos pendientes de costura\n";
    echo "   (o todos los pedidos con registros pendientes tienen prendas de_bodega=false)\n\n";
} else {
    foreach ($pedidos as $i => $pedido) {
        echo ($i + 1) . ". Pedido #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
        echo "   Cliente: {$pedido->cliente}\n";
        echo "   Estado: {$pedido->estado}\n";
        
        // Mostrar registros pendientes vinculados
        $pendientes = DB::table('bodega_detalles_talla as bdt')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'bdt.prenda_id')
            ->where('bdt.pedido_produccion_id', $pedido->id)
            ->where('bdt.area', 'Costura')
            ->where('bdt.estado_bodega', 'Pendiente')
            ->where('pp.de_bodega', 1)
            ->whereNull('pp.deleted_at')
            ->select('bdt.id', 'bdt.prenda_nombre', 'bdt.talla', 'pp.id as prenda_id', 'pp.nombre_prenda', 'pp.de_bodega')
            ->get();
        
        echo "   Registros pendientes vinculados a prendas de_bodega=true: {$pendientes->count()}\n";
        foreach ($pendientes as $p) {
            echo "     - ID {$p->id}: {$p->prenda_nombre} (Talla: {$p->talla}) -> prenda_id={$p->prenda_id}\n";
        }
        echo "\n";
    }
}

echo "\n=== VERIFICACIÓN ESPECÍFICA PEDIDO #37 ===\n\n";

$pedido37 = DB::table('pedidos_produccion')->where('numero_pedido', '37')->first();
if ($pedido37) {
    $enResultados = $pedidos->contains('id', $pedido37->id);
    
    if ($enResultados) {
        echo "❌ Pedido #37 APARECE en los resultados\n";
    } else {
        echo "✅ Pedido #37 NO APARECE en los resultados (correcto)\n";
    }
    
    // Análisis detallado del pedido 37
    echo "\n   Análisis del pedido #37:\n";
    
    $todosPendientes = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedido37->id)
        ->where('area', 'Costura')
        ->where('estado_bodega', 'Pendiente')
        ->get();
    
    echo "   - Total registros pendientes de Costura: {$todosPendientes->count()}\n";
    
    foreach ($todosPendientes as $p) {
        $prenda = DB::table('prendas_pedido')
            ->where('id', $p->prenda_id)
            ->whereNull('deleted_at')
            ->first();
        
        if ($prenda) {
            $deBodega = $prenda->de_bodega ? 'TRUE' : 'FALSE';
            $icon = $prenda->de_bodega ? '✅' : '❌';
            echo "     {$icon} ID {$p->id} -> prenda_id={$p->prenda_id} (de_bodega={$deBodega})\n";
        } else {
            echo "     ⚠️  ID {$p->id} -> prenda_id={$p->prenda_id} (prenda no encontrada o eliminada)\n";
        }
    }
} else {
    echo "Pedido #37 no encontrado\n";
}

echo "\n";
