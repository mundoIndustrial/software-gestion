<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Eliminando consecutivos generados incorrectamente ===\n";
echo "Pedido: 16\n\n";

// Eliminar registros
$deletedCount = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->delete();

echo "✅ Eliminados: {$deletedCount} registros\n\n";

// Resetear contadores maestro
$updates = [
    'COSTURA' => 0,
    'ESTAMPADO' => 0,
    'BORDADO' => 0,
    'REFLECTIVO' => 0,
    'DTF' => 0,
    'SUBLIMADO' => 0
];

foreach ($updates as $tipo => $valor) {
    DB::table('consecutivos_recibos')
        ->where('tipo_recibo', $tipo)
        ->update(['consecutivo_actual' => $valor]);
    echo "✅ Resetado {$tipo} a {$valor}\n";
}
?>
