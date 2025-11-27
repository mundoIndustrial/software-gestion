<?php
// Script para limpiar caché de días y forzar recalcular
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Limpiar TODA la caché
\Illuminate\Support\Facades\Facade::clearResolvedInstances();
\Illuminate\Support\Facades\Cache::flush();

echo "✅ Caché completamente limpiada\n";

// Forzar recalcular para primeros 25 pedidos
$ordenes = \App\Models\PedidoProduccion::limit(25)->get();
$festivos = \App\Models\Festivo::pluck('fecha')->toArray();

$calculados = 0;
foreach ($ordenes as $orden) {
    $dias = \App\Services\CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
    echo "Pedido {$orden->numero_pedido}: {$dias} días\n";
    $calculados++;
}

echo "\n✅ Recalculados {$calculados} pedidos\n";
?>
