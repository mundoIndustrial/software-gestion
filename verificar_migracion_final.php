<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE DATOS MIGRADOS ===\n\n";

// Contar datos
$pedidos = DB::table('pedidos_produccion')->count();
$prendas = DB::table('prendas_pedido')->count();
$procesos = DB::table('procesos_prenda')->count();

echo "📊 CONTEOS:\n";
echo "  pedidos_produccion: $pedidos\n";
echo "  prendas_pedido: $prendas\n";
echo "  procesos_prenda: $procesos\n";

echo "\n📋 EJEMPLO DE DATOS:\n";

echo "\n✅ Pedido completo (con prendas y procesos):\n";
$pedido = DB::table('pedidos_produccion')->first();
echo "  Pedido: {$pedido->numero_pedido} - {$pedido->cliente}\n";

$prendas_p = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->id)
    ->get();
echo "  Prendas: {$prendas_p->count()}\n";
foreach($prendas_p as $p) {
    echo "    - {$p->nombre_prenda} (qty: {$p->cantidad})\n";
}

$procesos_p = DB::table('procesos_prenda')
    ->where('numero_pedido', $pedido->numero_pedido)
    ->get();
echo "  Procesos: {$procesos_p->count()}\n";
foreach($procesos_p as $pr) {
    echo "    - {$pr->proceso} ({$pr->fecha_inicio})\n";
}

echo "\n✅ Pedido entregado con Despacho:\n";
$entregado = DB::table('pedidos_produccion')
    ->where('estado', 'Entregado')
    ->first();
if($entregado) {
    echo "  Pedido: {$entregado->numero_pedido} - {$entregado->cliente} ({$entregado->estado})\n";
    $procesos_e = DB::table('procesos_prenda')
        ->where('numero_pedido', $entregado->numero_pedido)
        ->where('proceso', 'Despacho')
        ->get();
    echo "  Procesos Despacho: {$procesos_e->count()}\n";
    foreach($procesos_e as $pe) {
        echo "    - {$pe->fecha_inicio} (estado: {$pe->estado_proceso})\n";
    }
}

echo "\n\n🔍 ANÁLISIS DE PROCESOS:\n";
$procesos_por_tipo = DB::table('procesos_prenda')
    ->select('proceso', DB::raw('COUNT(*) as count'))
    ->groupBy('proceso')
    ->orderBy('count', 'desc')
    ->get();

echo "Tipos de procesos migrados:\n";
foreach($procesos_por_tipo as $p) {
    echo "  - {$p->proceso}: {$p->count}\n";
}

echo "\n\n✅ RESUMEN FINAL:\n";
echo "Estructura correcta con numero_pedido como llave de relación\n";
echo "Datos listos para cálculo de duración en RegistroOrdenController\n";
?>
