<?php
/**
 * Comparar pedido 45452 con 45466 (nuevo)
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "COMPARACIÓN: PEDIDO 45452 vs PEDIDO 45466\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

$pedido45452 = PedidoProduccion::where('numero_pedido', '45452')->first();
$pedido45466 = PedidoProduccion::where('numero_pedido', '45466')->first();

if (!$pedido45452) {
    echo "❌ Pedido 45452 no encontrado\n";
    exit(1);
}

if (!$pedido45466) {
    echo "❌ Pedido 45466 no encontrado\n";
    exit(1);
}

echo "PEDIDO 45452 (REFERENCIA LEGACY):\n";
echo "─────────────────────────────────\n";
echo "Prendas: " . $pedido45452->prendas()->count() . "\n\n";

foreach ($pedido45452->prendas()->get() as $i => $prenda) {
    echo "PRENDA #" . ($i + 1) . ":\n";
    echo $prenda->descripcion . "\n";
    echo "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

echo "PEDIDO 45466 (NUEVO CON FORMATTER ACTUALIZADO):\n";
echo "───────────────────────────────────────────────\n";
echo "Prendas: " . $pedido45466->prendas()->count() . "\n\n";

foreach ($pedido45466->prendas()->get() as $i => $prenda) {
    echo "PRENDA #" . ($i + 1) . ":\n";
    echo $prenda->descripcion . "\n";
    echo "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n";
echo "COMPARACIÓN DE ESTRUCTURA\n";
echo "═══════════════════════════════════════════════════════════════════════════════════\n\n";

$prenda45452_1 = $pedido45452->prendas()->first();
$prenda45466_1 = $pedido45466->prendas()->first();

echo "PRIMERA PRENDA - PEDIDO 45452:\n";
echo "  Nombre: {$prenda45452_1->nombre_prenda}\n";
echo "  Color ID: {$prenda45452_1->color_id}\n";
echo "  Tela ID: {$prenda45452_1->tela_id}\n";
echo "  Tipo Manga ID: {$prenda45452_1->tipo_manga_id}\n";
echo "  Bolsillos: " . ($prenda45452_1->tiene_bolsillos ? 'Sí' : 'No') . "\n";
echo "  Reflectivo: " . ($prenda45452_1->tiene_reflectivo ? 'Sí' : 'No') . "\n";
echo "  Longitud descripción: " . strlen($prenda45452_1->descripcion) . " caracteres\n\n";

echo "PRIMERA PRENDA - PEDIDO 45466:\n";
echo "  Nombre: {$prenda45466_1->nombre_prenda}\n";
echo "  Color ID: {$prenda45466_1->color_id}\n";
echo "  Tela ID: {$prenda45466_1->tela_id}\n";
echo "  Tipo Manga ID: {$prenda45466_1->tipo_manga_id}\n";
echo "  Bolsillos: " . ($prenda45466_1->tiene_bolsillos ? 'Sí' : 'No') . "\n";
echo "  Reflectivo: " . ($prenda45466_1->tiene_reflectivo ? 'Sí' : 'No') . "\n";
echo "  Longitud descripción: " . strlen($prenda45466_1->descripcion) . " caracteres\n\n";

echo "✅ Comparación completada\n";
