<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::capture());

$cot = \App\Models\Cotizacion::with(['reflectivoCotizacion.fotos'])->find(67);
if ($cot && $cot->reflectivoCotizacion) {
    echo "✅ Cotización 67 tiene reflectivo\n";
    echo "   Fotos en relación: " . count($cot->reflectivoCotizacion->fotos) . "\n";
    
    $arr = $cot->toArray();
    echo "   toArray() tiene reflectivo_cotizacion: " . (isset($arr['reflectivo_cotizacion']) ? 'SÍ ✅' : 'NO ❌') . "\n";
    
    if (isset($arr['reflectivo_cotizacion'])) {
        echo "   toArray reflectivo_cotizacion tiene fotos: " . (isset($arr['reflectivo_cotizacion']['fotos']) ? count($arr['reflectivo_cotizacion']['fotos']) : 0) . "\n";
    }
    
    // Simular lo que hace editBorrador
    echo "\n--- Simulando editBorrador ---\n";
    if ($cot->reflectivoCotizacion) {
        $arr['reflectivo_cotizacion'] = $cot->reflectivoCotizacion->toArray();
        if ($cot->reflectivoCotizacion->relationLoaded('fotos')) {
            $arr['reflectivo_cotizacion']['fotos'] = $cot->reflectivoCotizacion->fotos->toArray();
            echo "Fotos cargadas en array: " . count($arr['reflectivo_cotizacion']['fotos']) . "\n";
        }
    }
    
    // Convertir a JSON
    $json = json_encode($arr);
    $decoded = json_decode($json, true);
    echo "Fotos en JSON: " . (isset($decoded['reflectivo_cotizacion']['fotos']) ? count($decoded['reflectivo_cotizacion']['fotos']) : 0) . "\n";
    
} else {
    echo "❌ No hay reflectivo en cotización 67\n";
}
?>
