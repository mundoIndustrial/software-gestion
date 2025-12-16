<?php
/**
 * Ver descripción guardada del pedido 45466
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

$pedido = PedidoProduccion::where('numero_pedido', '45466')->first();

if (!$pedido) {
    echo "❌ Pedido 45466 no encontrado\n";
    exit(1);
}

echo "✅ Pedido 45466 encontrado\n";
echo "ID: {$pedido->id}\n";
echo "Cliente: {$pedido->cliente}\n";
echo "Cotización ID: {$pedido->cotizacion_id}\n\n";

$prendas = $pedido->prendas()->get();
echo "📦 Total Prendas: {$prendas->count()}\n\n";

foreach ($prendas as $i => $prenda) {
    echo "=== PRENDA #" . ($i + 1) . " ===\n";
    echo "Nombre: {$prenda->nombre_prenda}\n";
    echo "Color ID: {$prenda->color_id}\n";
    echo "Tela ID: {$prenda->tela_id}\n";
    echo "Tipo Manga ID: {$prenda->tipo_manga_id}\n";
    echo "Cantidad Talla: {$prenda->cantidad_talla}\n";
    echo "Tiene Bolsillos: " . ($prenda->tiene_bolsillos ? 'Sí' : 'No') . "\n";
    echo "Tiene Reflectivo: " . ($prenda->tiene_reflectivo ? 'Sí' : 'No') . "\n";
    echo "\nDescripción (COMPLETA):\n";
    echo "---\n";
    echo $prenda->descripcion;
    echo "\n---\n\n";
}
