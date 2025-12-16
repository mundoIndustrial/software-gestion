<?php
/**
 * Verificar estructura del pedido 45452 (referencia)
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "\n=== VERIFICAR PEDIDO 45452 (REFERENCIA) ===\n\n";

$pedido = PedidoProduccion::where('numero_pedido', '45452')->first();

if (!$pedido) {
    echo "❌ Pedido 45452 no encontrado\n";
    exit(1);
}

echo "✅ Pedido encontrado\n";
echo "ID: {$pedido->id}\n";
echo "Numero: {$pedido->numero_pedido}\n";
echo "Cliente: {$pedido->cliente}\n";
echo "Asesor ID: {$pedido->asesor_id}\n";
echo "Cotización ID: {$pedido->cotizacion_id}\n";
echo "Fecha: {$pedido->created_at}\n\n";

$prendas = $pedido->prendas()->get();
echo "📦 Total Prendas: {$prendas->count()}\n\n";

foreach ($prendas as $i => $prenda) {
    echo "PRENDA #" . ($i + 1) . ":\n";
    echo "  ID: {$prenda->id}\n";
    echo "  Color ID: {$prenda->color_id}\n";
    echo "  Tela ID: {$prenda->tela_id}\n";
    echo "  Tipo Manga ID: {$prenda->tipo_manga_id}\n";
    echo "  Bolsillos: " . ($prenda->tiene_bolsillos ? 'Sí' : 'No') . "\n";
    echo "  Reflectivo: " . ($prenda->tiene_reflectivo ? 'Sí' : 'No') . "\n";
    echo "  Descripción (primeros 200 caracteres):\n";
    echo "    " . substr($prenda->descripcion, 0, 200) . "\n\n";
}

echo "✅ Datos del pedido 45452 (referencia) listos\n";
