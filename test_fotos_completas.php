<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

$numeroPedido = 45452;

echo "═════════════════════════════════════════════════\n";
echo "TEST: OBTENER FOTOS COMPLETAS (Prendas + Telas + Logos)\n";
echo "═════════════════════════════════════════════════\n\n";

$pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
if (!$pedido) {
    echo "❌ Pedido no encontrado\n";
    exit;
}

echo "✅ Pedido encontrado: {$pedido->numero_pedido}\n";
echo "   - Cotización ID: {$pedido->cotizacion_id}\n\n";

$prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)->pluck('id')->toArray();
echo "🎽 PrendasCot encontradas: " . count($prendasCot) . "\n";
echo "   - IDs: " . json_encode($prendasCot) . "\n\n";

// Fotos de prendas
echo "─ FOTOS DE PRENDAS ─\n";
$fotosPrendas = \App\Models\PrendaFotoCot::whereIn('prenda_cot_id', $prendasCot)->get();
echo "Encontradas: {$fotosPrendas->count()}\n";
foreach($fotosPrendas as $f) {
    $ruta = $f->ruta_webp ?: $f->ruta_original;
    echo "  ✓ $ruta\n";
}
echo "\n";

// Fotos de telas
echo "─ FOTOS DE TELAS ─\n";
try {
    $fotosTelas = \App\Models\PrendaTelaFotoCot::whereIn('prenda_cot_id', $prendasCot)->get();
    echo "Encontradas: {$fotosTelas->count()}\n";
    foreach($fotosTelas as $f) {
        $ruta = $f->ruta_webp ?: $f->ruta_original;
        echo "  ✓ $ruta\n";
    }
} catch (\Exception $e) {
    echo "Error (modelo no existe?): {$e->getMessage()}\n";
}
echo "\n";

// Fotos de logos
echo "─ FOTOS DE LOGOS ─\n";
try {
    $logoCotIds = \App\Models\LogoCotizacion::where('cotizacion_id', $pedido->cotizacion_id)->pluck('id')->toArray();
    echo "LogoCotizacion IDs encontrados: " . count($logoCotIds) . "\n";
    echo "   - IDs: " . json_encode($logoCotIds) . "\n";
    
    if (!empty($logoCotIds)) {
        $fotosLogos = \App\Models\LogoFotoCot::whereIn('logo_cotizacion_id', $logoCotIds)->get();
        echo "Fotos de logos encontradas: {$fotosLogos->count()}\n";
        foreach($fotosLogos as $f) {
            $ruta = $f->ruta_webp ?: $f->ruta_original;
            echo "  ✓ $ruta\n";
        }
    } else {
        echo "Sin LogoCotizacion para esta cotización\n";
    }
} catch (\Exception $e) {
    echo "Error (modelo no existe?): {$e->getMessage()}\n";
}
echo "\n";

// Total
echo "═════════════════════════════════════════════════\n";
$total = $fotosPrendas->count() + $fotosTelas->count();
try {
    $logoCotIds = \App\Models\LogoCotizacion::where('cotizacion_id', $pedido->cotizacion_id)->pluck('id')->toArray();
    if (!empty($logoCotIds)) {
        $total += \App\Models\LogoFotoCot::whereIn('logo_cotizacion_id', $logoCotIds)->count();
    }
} catch (\Exception $e) {}

echo "TOTAL DE FOTOS: $total\n";
echo "═════════════════════════════════════════════════\n";
