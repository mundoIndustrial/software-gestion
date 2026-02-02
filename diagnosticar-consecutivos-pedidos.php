<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO: consecutivos_recibos_pedidos ===\n\n";

// Ver algunos registros
echo "--- Registros actuales ---\n";
$registros = DB::table('consecutivos_recibos_pedidos')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($registros as $reg) {
    echo "ID: {$reg->id} | Pedido: {$reg->pedido_produccion_id} | Prenda: {$reg->prenda_id} | Tipo: {$reg->tipo_recibo} | Actual: {$reg->consecutivo_actual} | Inicial: {$reg->consecutivo_inicial}\n";
}

echo "\n--- Resumen por tipo de recibo ---\n";
$resumen = DB::table('consecutivos_recibos_pedidos')
    ->groupBy('tipo_recibo')
    ->selectRaw('tipo_recibo, COUNT(*) as total, MIN(consecutivo_actual) as min, MAX(consecutivo_actual) as max, AVG(consecutivo_actual) as promedio')
    ->get();

foreach ($resumen as $res) {
    echo "{$res->tipo_recibo}: Total={$res->total}, Min={$res->min}, Max={$res->max}, Promedio=" . round($res->promedio, 2) . "\n";
}

echo "\n--- Registros con consecutivo = 1 ---\n";
$con_uno = DB::table('consecutivos_recibos_pedidos')
    ->where('consecutivo_actual', 1)
    ->count();

echo "Total con consecutivo_actual = 1: {$con_uno}\n";

if ($con_uno > 0) {
    echo "\nEjemplos:\n";
    $ejemplos = DB::table('consecutivos_recibos_pedidos')
        ->where('consecutivo_actual', 1)
        ->limit(10)
        ->get();
    
    foreach ($ejemplos as $ej) {
        echo "ID: {$ej->id} | Pedido: {$ej->pedido_produccion_id} | Tipo: {$ej->tipo_recibo} | Creado: {$ej->created_at}\n";
    }
}

echo "\n--- Últimos 5 registros creados ---\n";
$ultimos = DB::table('consecutivos_recibos_pedidos')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($ultimos as $u) {
    echo "ID: {$u->id} | Tipo: {$u->tipo_recibo} | Consecutivo: {$u->consecutivo_actual} | Creado: {$u->created_at}\n";
}
?>
