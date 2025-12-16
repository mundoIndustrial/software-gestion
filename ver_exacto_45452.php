<?php
/**
 * Ver EXACTAMENTE cómo está guardado el 45452 - mostrar caracteres especiales
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

$pedido = PedidoProduccion::where('numero_pedido', '45452')->first();
$prenda = $pedido->prendas()->first();

echo "DESCRIPCIÓN EXACTA DEL 45452 - PRENDA #1:\n";
echo "═════════════════════════════════════════════════════════\n\n";

// Mostrar la descripción formateada
echo $prenda->descripcion . "\n\n";

echo "═════════════════════════════════════════════════════════\n\n";

// Mostrar línea por línea
echo "LÍNEA POR LÍNEA:\n";
echo "───────────────\n";
$lineas = explode("\n", $prenda->descripcion);
foreach ($lineas as $i => $linea) {
    echo "Línea " . ($i + 1) . ": " . json_encode($linea) . "\n";
}
