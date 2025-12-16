<?php
/**
 * Test: Crear pedido desde cotización 56 con nuevo formatter
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Application\Services\PedidoProduccionCreatorService;
use App\DTOs\CrearPedidoProduccionDTO;

echo "🔄 Verificando cotización 56...\n\n";

$cotizacion = Cotizacion::with('prendas')->find(56);
if (!$cotizacion) {
    echo "❌ Cotización 56 no encontrada\n";
    exit(1);
}

// Extraer datos
$extractor = new CotizacionDataExtractorService();
$datosExtraidos = $extractor->extraerDatos($cotizacion);

echo "✅ Datos extraídos de cotización 56\n";
echo "Prendas: " . count($datosExtraidos['prendas']) . "\n\n";

// Ver estructura de primera prenda
$prenda1 = $datosExtraidos['prendas'][0];
echo "PRENDA 1 - Datos extraídos:\n";
echo "  nombre: {$prenda1['nombre_producto']}\n";
echo "  descripcion: {$prenda1['descripcion']}\n";
echo "  color: {$prenda1['color']}\n";
echo "  tela: {$prenda1['tela']}\n";
echo "  manga: {$prenda1['manga']}\n";
echo "  manga_obs: {$prenda1['manga_obs']}\n";
echo "  bolsillos_obs: {$prenda1['bolsillos_obs']}\n";
echo "  reflectivo_obs: " . substr($prenda1['reflectivo_obs'], 0, 50) . "...\n";
echo "  tiene_bolsillos: " . ($prenda1['tiene_bolsillos'] ? 'SI' : 'NO') . "\n";
echo "  tiene_reflectivo: " . ($prenda1['tiene_reflectivo'] ? 'SI' : 'NO') . "\n";
echo "\n";

// Crear DTO
$dto = new CrearPedidoProduccionDTO(
    cotizacion_id: $datosExtraidos['cotizacion_id'],
    cliente_id: $datosExtraidos['cliente_id'],
    cliente: $datosExtraidos['cliente'],
    asesor_id: $datosExtraidos['asesor_id'],
    prendas: $datosExtraidos['prendas']
);

// Crear pedido
$creatorService = new PedidoProduccionCreatorService();
$nuevoPedido = $creatorService->crear($dto);

echo "✅ PEDIDO CREADO: #{$nuevoPedido->numero_pedido}\n\n";

// Ver descripción guardada
$prendasGuardadas = $nuevoPedido->prendas()->get();
foreach ($prendasGuardadas as $i => $prenda) {
    echo "=== PRENDA #" . ($i + 1) . " ===\n";
    echo "Nombre: {$prenda->nombre_prenda}\n";
    echo "Color ID: {$prenda->color_id}\n";
    echo "Tela ID: {$prenda->tela_id}\n";
    echo "Tipo Manga ID: {$prenda->tipo_manga_id}\n";
    echo "\nDescripción guardada:\n";
    echo "---\n";
    echo $prenda->descripcion;
    echo "\n---\n\n";
}
