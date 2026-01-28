<?php
/**
 * Script para consultar el número de pedido actual
 * Uso: php consultar-pedidos.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║               CONSULTA DE PEDIDOS EN PRODUCCIÓN               ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Obtener auto-increment de la tabla
$autoIncrement = DB::select("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'");
$proximoNumero = $autoIncrement[0]->AUTO_INCREMENT ?? null;

// Último número de pedido
$ultimoPedido = DB::table('pedidos_produccion')
    ->max('numero_pedido');

if ($ultimoPedido) {
    echo "✓ Último número de pedido: \033[92m{$ultimoPedido}\033[0m\n";
} else {
    echo "⚠ No hay pedidos registrados\n\n";
    exit(0);
}

if ($proximoNumero) {
    echo "→ \033[96mPRÓXIMO NÚMERO SECUENCIAL: {$proximoNumero}\033[0m\n";
}

echo "\n";

// Total de pedidos
$total = DB::table('pedidos_produccion')
    ->whereNull('deleted_at')
    ->count();

echo "Total de pedidos activos: \033[94m{$total}\033[0m\n\n";

// Resumen por estado
echo "RESUMEN POR ESTADO:\n";
echo str_repeat("─", 60) . "\n";

$resumen = DB::table('pedidos_produccion')
    ->select('estado', DB::raw('COUNT(*) as cantidad'))
    ->whereNull('deleted_at')
    ->groupBy('estado')
    ->orderBy('cantidad', 'desc')
    ->get();

foreach ($resumen as $item) {
    $porcentaje = ($item->cantidad / $total) * 100;
    printf("%-30s: %3d (%.1f%%)\n", $item->estado, $item->cantidad, $porcentaje);
}

echo "\n";

// Pedidos pendientes
echo "PEDIDOS PENDIENTES / EN EJECUCIÓN:\n";
echo str_repeat("─", 60) . "\n";

$pendientes = DB::table('pedidos_produccion')
    ->whereNull('deleted_at')
    ->whereIn('estado', ['Pendiente', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'pendiente_cartera'])
    ->select('numero_pedido', 'cliente', 'estado', 'fecha_estimada_de_entrega')
    ->orderBy('numero_pedido', 'desc')
    ->limit(10)
    ->get();

if ($pendientes->count() > 0) {
    foreach ($pendientes as $pedido) {
        $entrega = $pedido->fecha_estimada_de_entrega ? date('d/m/Y', strtotime($pedido->fecha_estimada_de_entrega)) : 'N/A';
        printf("  #%-5d | %-30s | %-20s | Entrega: %s\n", 
            $pedido->numero_pedido, 
            substr($pedido->cliente, 0, 30), 
            $pedido->estado,
            $entrega
        );
    }
} else {
    echo "  ✓ No hay pedidos pendientes\n";
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "\n";
