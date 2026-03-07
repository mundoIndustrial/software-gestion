<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN FINAL - /despacho/pendientes ===\n\n";

// Simular obtenerPendientesCostura
echo "1️⃣  PENDIENTES DE COSTURA (área=Costura + estado_bodega=Pendiente + de_bodega=true)\n";
echo str_repeat('-', 80) . "\n";

$costura = DB::table('pedidos_produccion')
    ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->join('prendas_pedido', function($join) {
        $join->on('prendas_pedido.id', '=', 'bodega_detalles_talla.prenda_id')
             ->where('prendas_pedido.de_bodega', '=', 1)
             ->whereNull('prendas_pedido.deleted_at');
    })
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
    ->where('bodega_detalles_talla.area', 'Costura')
    ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
    ->whereNull('pedidos_produccion.deleted_at')
    ->distinct()
    ->pluck('pedidos_produccion.numero_pedido');

echo "Total: {$costura->count()}\n";
if ($costura->count() > 0) {
    echo "Pedidos: " . $costura->implode(', ') . "\n";
} else {
    echo "✅ Ningún pedido (correcto - todos los registros pendientes son de prendas con de_bodega=false)\n";
}
echo "\n";

// Simular obtenerPendientesBodegaSinProcesos
echo "2️⃣  BODEGA SIN PROCESOS (de_bodega=true sin procesos + pendiente en bodega)\n";
echo str_repeat('-', 80) . "\n";

$bodegaSinProcesos = DB::table('pedidos_produccion')
    ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
    ->where('pedidos_produccion.estado', '!=', 'Anulada')
    ->where('pedidos_produccion.estado', '!=', 'Entregado')
    ->where('prendas_pedido.de_bodega', 1)
    ->whereNull('prendas_pedido.deleted_at')
    ->whereNull('pedidos_procesos_prenda_detalles.id')
    ->whereExists(function ($q) {
        $q->select(DB::raw(1))
            ->from('bodega_detalles_talla as bdt')
            ->join('prendas_pedido as pp', function($join) {
                $join->on('pp.id', '=', 'bdt.prenda_id')
                     ->where('pp.de_bodega', '=', 1)
                     ->whereNull('pp.deleted_at');
            })
            ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
            ->where('bdt.estado_bodega', 'Pendiente');
    })
    ->whereNull('pedidos_produccion.deleted_at')
    ->distinct()
    ->pluck('pedidos_produccion.numero_pedido');

echo "Total: {$bodegaSinProcesos->count()}\n";
if ($bodegaSinProcesos->count() > 0) {
    echo "Pedidos: " . $bodegaSinProcesos->implode(', ') . "\n";
}
echo "\n";

// Simular obtenerPendientesEpp
echo "3️⃣  PENDIENTES DE EPP (área=EPP + estado_bodega=Pendiente)\n";
echo str_repeat('-', 80) . "\n";

$epp = DB::table('pedidos_produccion')
    ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
    ->whereNotNull('pedidos_produccion.numero_pedido')
    ->where('pedidos_produccion.numero_pedido', '!=', '')
    ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
    ->where('pedidos_produccion.estado', '!=', 'Anulada')
    ->where('pedidos_produccion.estado', '!=', 'Entregado')
    ->where('bodega_detalles_talla.area', 'EPP')
    ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
    ->whereNull('pedidos_produccion.deleted_at')
    ->distinct()
    ->pluck('pedidos_produccion.numero_pedido');

echo "Total: {$epp->count()}\n";
if ($epp->count() > 0) {
    echo "Pedidos: " . $epp->implode(', ') . "\n";
}
echo "\n";

// Total unificado
$todosLosPendientes = $costura->merge($bodegaSinProcesos)->merge($epp)->unique()->sort()->values();

echo str_repeat('=', 80) . "\n";
echo "=== TOTAL EN /despacho/pendientes ===\n";
echo str_repeat('=', 80) . "\n";
echo "Total pedidos únicos: {$todosLosPendientes->count()}\n";
if ($todosLosPendientes->count() > 0) {
    echo "Pedidos: " . $todosLosPendientes->implode(', ') . "\n";
} else {
    echo "No hay pedidos pendientes\n";
}
echo "\n";

// Verificación específica del pedido #37
echo str_repeat('=', 80) . "\n";
echo "=== VERIFICACIÓN PEDIDO #37 ===\n";
echo str_repeat('=', 80) . "\n";

if ($todosLosPendientes->contains('37')) {
    echo "❌ ERROR: Pedido #37 SÍ aparece (no debería)\n";
} else {
    echo "✅ CORRECTO: Pedido #37 NO aparece\n";
    echo "   Los registros pendientes están vinculados a prendas con de_bodega=false\n";
}

echo "\n";
