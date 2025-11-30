php
// Script para probar el nuevo cálculo de días
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Services\CacheCalculosService;

echo "=" . str_repeat("=", 80) . "\n";
echo "TEST: Nuevo cálculo de días (soporta órdenes sin procesos)\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Obtener órdenes con diferentes estados
$ordenes = PedidoProduccion::orderBy('numero_pedido', 'DESC')->limit(10)->get();

foreach ($ordenes as $orden) {
    $dias = CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
    
    $procesos = \DB::table('procesos_prenda')
        ->where('numero_pedido', $orden->numero_pedido)
        ->count();
    
    echo "Pedido: {$orden->numero_pedido}\n";
    echo "  Estado: {$orden->estado}\n";
    echo "  Procesos: {$procesos}\n";
    echo "  Fecha creación: {$orden->fecha_de_creacion_de_orden}\n";
    echo "  Días calculados: {$dias}\n";
    echo "\n";
}

echo "✅ Test completado\n";
