php
// Script para limpiar caché de días y forzar recálculo
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Services\CacheCalculosService;

echo "Limpiando caché de días...\n";
CacheCalculosService::invalidarTodo();
echo "✅ Caché limpiada\n\n";

echo "Recalculando días para los últimos 100 pedidos...\n";
$ordenes = PedidoProduccion::orderBy('numero_pedido', 'DESC')->limit(100)->get();
CacheCalculosService::precalcularTodo();
echo "✅ Precálculo completado para " . $ordenes->count() . " pedidos\n";

foreach ($ordenes->take(10) as $orden) {
    $dias = CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
    echo "  Pedido {$orden->numero_pedido}: {$dias} días (Estado: {$orden->estado})\n";
}

echo "\n✅ Caché reconstruida exitosamente\n";
