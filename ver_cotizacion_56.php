<?php
/**
 * Verificar datos de cotización 56
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Services\Pedidos\CotizacionDataExtractorService;

$cotizacion = Cotizacion::with('prendas')->find(56);

if (!$cotizacion) {
    echo "❌ Cotización 56 no encontrada\n";
    exit(1);
}

echo "✅ Cotización 56 encontrada\n";
echo "ID: {$cotizacion->id}\n";
echo "Cliente: {$cotizacion->cliente_id}\n";
echo "Asesor: {$cotizacion->asesor_id}\n\n";

// Usar el extractor para ver qué datos extrae
$extractor = new CotizacionDataExtractorService();
$datosExtraidos = $extractor->extraerDatos($cotizacion);

echo "📦 Datos Extraídos:\n";
echo json_encode($datosExtraidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
