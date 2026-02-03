<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;

echo "=== Regenerando consecutivos para pedido 16 (con evento) ===\n\n";

// Autenticarse como supervisor
$user = DB::table('users')->where('email', 'yus22@gmail.com')->first();
if ($user) {
    Auth::loginUsingId($user->id);
    echo "✅ Autenticado como: {$user->name}\n\n";
}

// Obtener el pedido
$pedido = PedidoProduccion::find(16);

if (!$pedido) {
    echo "❌ Pedido no encontrado\n";
    exit;
}

echo "Pedido encontrado: #{$pedido->numero_pedido}\n";
echo "Estado actual: {$pedido->estado}\n\n";

// Cambiar a estado diferente primero
$pedido->estado = 'PENDIENTE_SUPERVISOR';
$pedido->save();
echo "✅ Cambiado a PENDIENTE_SUPERVISOR\n";

sleep(1);

// Cambiar nuevamente a PENDIENTE_INSUMOS para disparar el observer
$pedido->estado = 'PENDIENTE_INSUMOS';
$pedido->save();
echo "✅ Cambiado a PENDIENTE_INSUMOS (esto debe generar los consecutivos)\n\n";

sleep(1);

// Mostrar consecutivos generados
$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', 16)
    ->orderBy('tipo_recibo')
    ->get();

echo "Consecutivos generados:\n";
foreach ($consecutivos as $cons) {
    echo "ID: {$cons->id} | Tipo: {$cons->tipo_recibo} | Consecutivo: {$cons->consecutivo_actual} | Prenda: {$cons->prenda_id}\n";
}
?>
