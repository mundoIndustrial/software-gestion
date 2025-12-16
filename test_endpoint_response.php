<?php
/**
 * Test: Ver qué retorna el endpoint
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

// Obtener último pedido
$pedido = PedidoProduccion::latest()->first();

if (!$pedido) {
    echo "❌ No hay pedidos\n";
    exit(1);
}

echo "✅ Última pedido: #{$pedido->numero_pedido}\n\n";

// Simular lo que hace el endpoint
$prendas = DB::table('prendas_pedido')
    ->where('numero_pedido', $pedido->numero_pedido)
    ->orderBy('id', 'asc')
    ->get(['nombre_prenda', 'descripcion', 'cantidad_talla']);

// Formatear como hace el controller
$prendasFormato = [];
foreach ($prendas as $index => $prenda) {
    $prendasFormato[] = [
        'numero' => $index + 1,
        'nombre' => $prenda->nombre_prenda ?? '-',
        'descripcion' => $prenda->descripcion ?? '-',
        'cantidad_talla' => $prenda->cantidad_talla ?? '-'
    ];
}

// Ver en JSON (lo que recibe el frontend)
echo "JSON que recibe el frontend:\n";
echo "===========================\n";
$json = json_encode(['prendas' => $prendasFormato], JSON_UNESCAPED_UNICODE);
echo $json;
echo "\n===========================\n\n";

// Ver la primera prenda
if (count($prendasFormato) > 0) {
    $prenda1 = $prendasFormato[0];
    echo "Primera prenda - descripción:\n";
    echo "===========================\n";
    echo $prenda1['descripcion'];
    echo "\n===========================\n";
}
