<?php
require 'vendor/autoload.php';

use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Models\Cotizacion;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST: CotizacionDataExtractorService ===\n\n";

$extractor = new CotizacionDataExtractorService();

// Obtener última cotización
$cotizacion = Cotizacion::latest('id')->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones\n";
    exit(1);
}

echo "Cotización #" . $cotizacion->numero_cotizacion . " (ID: {$cotizacion->id})\n\n";

// Extraer datos
$datos = $extractor->extraerDatos($cotizacion);

echo "1️⃣  DATOS GENERALES:\n";
echo "   Número: " . $datos['numero_cotizacion'] . "\n";
echo "   Cliente: " . $datos['cliente'] . "\n";
echo "   Total Prendas: " . count($datos['prendas']) . "\n\n";

echo "2️⃣  PRENDAS CON TELAS:\n";
foreach ($datos['prendas'] as $idx => $prenda) {
    echo "\n   Prenda $idx: {$prenda['nombre_producto']}\n";
    echo "   - Tela: {$prenda['tela']}\n";
    echo "   - Referencia: {$prenda['tela_referencia']}\n";
    echo "   - Color: {$prenda['color']}\n";
    echo "   - tela_id: " . ($prenda['tela_id'] ?? "NULL") . "\n";
    echo "   - color_id: " . ($prenda['color_id'] ?? "NULL") . "\n";
    
    if ($prenda['tela_id'] && $prenda['color_id']) {
        echo "   ✅ TIENE AMBOS IDs\n";
    } else {
        echo "   ⚠ FALTA: " . (!$prenda['tela_id'] ? "tela_id" : "") . " " . (!$prenda['color_id'] ? "color_id" : "") . "\n";
    }
}

echo "\n\n3️⃣  RESUMEN:\n";
$conTelaId = 0;
$conColorId = 0;
foreach ($datos['prendas'] as $prenda) {
    if ($prenda['tela_id']) $conTelaId++;
    if ($prenda['color_id']) $conColorId++;
}
echo "   Prendas con tela_id: $conTelaId/" . count($datos['prendas']) . "\n";
echo "   Prendas con color_id: $conColorId/" . count($datos['prendas']) . "\n";
