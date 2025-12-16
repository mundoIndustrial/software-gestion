<?php
/**
 * Ver exactamente qué se guardó en BD
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PrendaPedido;

// Obtener última prenda guardada
$prenda = PrendaPedido::orderBy('id', 'desc')->first();

if (!$prenda) {
    echo "❌ No hay prendas guardadas\n";
    exit(1);
}

echo "✅ Última prenda guardada:\n";
echo "ID: {$prenda->id}\n";
echo "Número Pedido: {$prenda->numero_pedido}\n";
echo "Nombre: {$prenda->nombre_prenda}\n\n";

echo "DESCRIPCIÓN GUARDADA (RAW):\n";
echo "===========================\n";
echo $prenda->descripcion;
echo "\n===========================\n\n";

echo "DESCRIPCIÓN (JSON):\n";
echo "===========================\n";
echo json_encode($prenda->descripcion, JSON_UNESCAPED_UNICODE) . "\n";
echo "===========================\n\n";

echo "Contiene \\n literal: " . (strpos($prenda->descripcion, '\\n') !== false ? 'SÍ' : 'NO') . "\n";
echo "Contiene \\n real (newline): " . (strpos($prenda->descripcion, "\n") !== false ? 'SÍ' : 'NO') . "\n";
echo "Longitud: " . strlen($prenda->descripcion) . " caracteres\n";
