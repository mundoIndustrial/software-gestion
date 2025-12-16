<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Buscar una cotización que tenga reflectivo
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
])->where('es_borrador', true)->first();

if (!$cotizacion) {
    // Si no hay con es_borrador = true, buscar cualquiera con reflectivo
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
    ])->whereNotNull('reflectivo_id')->first();
}

if (!$cotizacion) {
    echo "❌ No se encontró cotización con reflectivo\n";
    exit;
}

echo "✅ Encontrada cotización ID: " . $cotizacion->id . "\n";
echo "   es_borrador: " . ($cotizacion->es_borrador ? 'SÍ' : 'NO') . "\n";
echo "   reflectivo_id: " . $cotizacion->reflectivo_id . "\n";
echo "   reflectivoCotizacion cargado: " . ($cotizacion->relationLoaded('reflectivoCotizacion') ? 'SÍ' : 'NO') . "\n";
echo "\n";

// Verificar que el reflectivo tiene fotos
if ($cotizacion->reflectivoCotizacion) {
    echo "✅ Existe reflectivoCotizacion\n";
    echo "   Fotos en relación: " . count($cotizacion->reflectivoCotizacion->fotos) . "\n";
    echo "   Fotos cargadas: " . ($cotizacion->reflectivoCotizacion->relationLoaded('fotos') ? 'SÍ' : 'NO') . "\n";
} else {
    echo "❌ No existe reflectivoCotizacion\n";
    exit;
}

echo "\n--- SIMULANDO EXACTAMENTE LO QUE HACE editBorrador ---\n\n";

// Paso 1: Convertir a array
echo "Paso 1: Convertir cotización a array\n";
$cotizacionArray = $cotizacion->toArray();
echo "  Keys principales: " . implode(', ', array_slice(array_keys($cotizacionArray), 0, 10)) . "...\n";
echo "  ¿Tiene 'reflectivo_cotizacion'?: " . (isset($cotizacionArray['reflectivo_cotizacion']) ? 'SÍ ✅' : 'NO ❌') . "\n";

if (isset($cotizacionArray['reflectivo_cotizacion'])) {
    echo "  Keys de reflectivo_cotizacion: " . implode(', ', array_keys($cotizacionArray['reflectivo_cotizacion'])) . "\n";
    echo "  ¿Tiene 'fotos'?: " . (isset($cotizacionArray['reflectivo_cotizacion']['fotos']) ? 'SÍ ✅' : 'NO ❌') . "\n";
    
    if (isset($cotizacionArray['reflectivo_cotizacion']['fotos'])) {
        echo "  Count fotos: " . count($cotizacionArray['reflectivo_cotizacion']['fotos']) . "\n";
        if (count($cotizacionArray['reflectivo_cotizacion']['fotos']) > 0) {
            $primeraFoto = $cotizacionArray['reflectivo_cotizacion']['fotos'][0];
            echo "  Keys de primera foto: " . implode(', ', array_keys($primeraFoto)) . "\n";
            echo "  ¿Tiene 'url'?: " . (isset($primeraFoto['url']) ? 'SÍ ✅' : 'NO ❌') . "\n";
        }
    }
}

// Paso 2: Verificar si está vacío y cargar manualmente
echo "\nPaso 2: Verificar si fotos están vacías (como hace editBorrador)\n";
if (isset($cotizacionArray['reflectivo_cotizacion'])) {
    $reflectivoId = $cotizacionArray['reflectivo_cotizacion']['id'] ?? null;
    
    if (empty($cotizacionArray['reflectivo_cotizacion']['fotos']) && $reflectivoId) {
        echo "  Fotos vacías en array, cargando desde modelo...\n";
        $reflectivoModel = \App\Models\ReflectivoCotizacion::with('fotos')->find($reflectivoId);
        if ($reflectivoModel && $reflectivoModel->fotos && count($reflectivoModel->fotos) > 0) {
            $cotizacionArray['reflectivo_cotizacion']['fotos'] = $reflectivoModel->fotos->toArray();
            echo "  ✅ Fotos cargadas: " . count($cotizacionArray['reflectivo_cotizacion']['fotos']) . "\n";
        }
    } else {
        echo "  Fotos NOT vacías o no se puede cargar\n";
        echo "  empty(): " . (empty($cotizacionArray['reflectivo_cotizacion']['fotos']) ? 'true' : 'false') . "\n";
        echo "  reflectivoId: " . ($reflectivoId ?? 'null') . "\n";
    }
}

// Paso 3: Convertir a JSON
echo "\nPaso 3: Convertir a JSON (como hace editBorrador)\n";
$datosJSON = json_encode($cotizacionArray);
echo "  JSON length: " . strlen($datosJSON) . "\n";

// Decodificar para verificar
$datosDecodificados = json_decode($datosJSON, true);
if (isset($datosDecodificados['reflectivo_cotizacion']['fotos'])) {
    echo "  Fotos en JSON: " . count($datosDecodificados['reflectivo_cotizacion']['fotos']) . "\n";
    if (count($datosDecodificados['reflectivo_cotizacion']['fotos']) > 0) {
        $primeraFoto = $datosDecodificados['reflectivo_cotizacion']['fotos'][0];
        echo "  Keys de primera foto: " . implode(', ', array_keys($primeraFoto)) . "\n";
        echo "  ¿Tiene 'url'?: " . (isset($primeraFoto['url']) ? 'SÍ ✅' : 'NO ❌') . "\n";
        if (isset($primeraFoto['url'])) {
            echo "  Valor: " . $primeraFoto['url'] . "\n";
        }
    }
} else {
    echo "  ❌ NO hay fotos en reflectivo_cotizacion en JSON\n";
}

// Paso 4: Verificar la estructura completa para el JavaScript
echo "\nPaso 4: Verificar estructura para JavaScript\n";
echo "  datosIniciales.reflectivo existe: " . (isset($datosDecodificados['reflectivo_cotizacion']) ? 'SÍ ✅' : 'NO ❌') . "\n";
echo "  datosIniciales.reflectivo.fotos existe: " . (isset($datosDecodificados['reflectivo_cotizacion']['fotos']) ? 'SÍ ✅' : 'NO ❌') . "\n";
if (isset($datosDecodificados['reflectivo_cotizacion']['fotos'])) {
    echo "  datosIniciales.reflectivo.fotos.length: " . count($datosDecodificados['reflectivo_cotizacion']['fotos']) . "\n";
}
?>
