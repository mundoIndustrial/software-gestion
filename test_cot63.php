<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Probar con cotización 63 que tiene reflectivo
$cotizacion = \App\Models\Cotizacion::with([
    'cliente',
    'prendas.fotos',
    'prendas.telaFotos',
    'prendas.tallas',
    'prendas.variantes.manga',
    'prendas.variantes.broche',
    'logoCotizacion.fotos',
    'tipoCotizacion',
    'reflectivoCotizacion.fotos'
])->find(63);

if (!$cotizacion) {
    echo "❌ Cotización 63 no encontrada\n";
    exit;
}

echo "✅ Cotización 63 cargada\n";
echo "   es_borrador: " . ($cotizacion->es_borrador ? 'SÍ' : 'NO') . "\n";
echo "   reflectivoCotizacion: " . ($cotizacion->reflectivoCotizacion ? 'EXISTE' : 'NULL') . "\n";
if ($cotizacion->reflectivoCotizacion) {
    echo "   reflectivoCotizacion.fotos count: " . count($cotizacion->reflectivoCotizacion->fotos) . "\n";
}
echo "\n";

// Convertir a array como lo hace editBorrador
echo "--- Convertiendo a array (como editBorrador) ---\n";
$cotizacionArray = $cotizacion->toArray();

echo "reflectivo_cotizacion en array: " . (isset($cotizacionArray['reflectivo_cotizacion']) ? 'SÍ' : 'NO') . "\n";

if (isset($cotizacionArray['reflectivo_cotizacion'])) {
    echo "  fotos en array: " . (isset($cotizacionArray['reflectivo_cotizacion']['fotos']) ? count($cotizacionArray['reflectivo_cotizacion']['fotos']) : '0') . "\n";
    if (isset($cotizacionArray['reflectivo_cotizacion']['fotos']) && count($cotizacionArray['reflectivo_cotizacion']['fotos']) > 0) {
        $foto = $cotizacionArray['reflectivo_cotizacion']['fotos'][0];
        echo "  Keys de primera foto: " . implode(', ', array_keys($foto)) . "\n";
        echo "  ¿Tiene 'url'?: " . (isset($foto['url']) ? 'SÍ' : 'NO') . "\n";
    }
} else {
    // Intentar cargar manualmente como hace editBorrador
    echo "  reflectivo_cotizacion NO estaba en array, buscando por relación...\n";
    
    // Verificar si la relación existe pero no fue cargada
    if ($cotizacion->relationLoaded('reflectivoCotizacion') && $cotizacion->reflectivoCotizacion) {
        echo "  ✓ Relación sí existe y está cargada\n";
        $cotizacionArray['reflectivo_cotizacion'] = $cotizacion->reflectivoCotizacion->toArray();
        if ($cotizacion->reflectivoCotizacion->relationLoaded('fotos')) {
            $cotizacionArray['reflectivo_cotizacion']['fotos'] = $cotizacion->reflectivoCotizacion->fotos->toArray();
        }
        echo "  Fotos ahora en array: " . (isset($cotizacionArray['reflectivo_cotizacion']['fotos']) ? count($cotizacionArray['reflectivo_cotizacion']['fotos']) : '0') . "\n";
    }
}

// Convertir a JSON
echo "\n--- Convertiendo a JSON ---\n";
$datosJSON = json_encode($cotizacionArray);
$datosDecodificados = json_decode($datosJSON, true);

if (isset($datosDecodificados['reflectivo_cotizacion']['fotos'])) {
    echo "✅ Fotos en JSON: " . count($datosDecodificados['reflectivo_cotizacion']['fotos']) . "\n";
    if (count($datosDecodificados['reflectivo_cotizacion']['fotos']) > 0) {
        $foto = $datosDecodificados['reflectivo_cotizacion']['fotos'][0];
        echo "   Primera foto URL: " . $foto['url'] . "\n";
    }
} else {
    echo "❌ NO hay fotos en JSON\n";
}
?>
