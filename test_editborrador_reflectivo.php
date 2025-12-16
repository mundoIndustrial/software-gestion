<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;

echo "\n=== TEST: editBorrador() con reflectivo ===\n\n";

// Buscar una cotización borrador con reflectivo que tenga fotos
$cotizacion = Cotizacion::where('es_borrador', true)
    ->whereHas('reflectivoCotizacion.fotos')
    ->with(['reflectivoCotizacion.fotos', 'prendas.fotos'])
    ->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones borrador con reflectivo y fotos\n";
    exit;
}

echo "✅ Cotización encontrada: #{$cotizacion->id}\n";
echo "   Asesor: {$cotizacion->asesor_id}\n";
echo "   Reflectivo ID: " . ($cotizacion->reflectivoCotizacion?->id ?? 'N/A') . "\n";
echo "   Reflectivo fotos: " . ($cotizacion->reflectivoCotizacion?->fotos ? count($cotizacion->reflectivoCotizacion->fotos) : 0) . "\n\n";

// Simular login
Auth::login(\App\Models\User::find($cotizacion->asesor_id));

// Llamar al controller
$controller = app(\App\Infrastructure\Http\Controllers\CotizacionController::class);

// Obtener la vista con datos
try {
    // Hacemos una reflexión para acceder al método privado/protected si es necesario
    $reflection = new \ReflectionMethod($controller, 'editBorrador');
    $reflection->setAccessible(true);
    
    // Para evitar que se ejecute realmente toda la vista, vamos a simular los pasos del controller
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
    ])->findOrFail($cotizacion->id);

    $cotizacionArray = $cotizacion->toArray();

    // Incluir relaciones
    if ($cotizacion->cliente) {
        $cotizacionArray['cliente'] = $cotizacion->cliente->toArray();
    }

    if ($cotizacion->prendas && count($cotizacion->prendas) > 0) {
        $cotizacionArray['prendas'] = $cotizacion->prendas->map(function($prenda) {
            return $prenda->toArray();
        })->toArray();
    }

    echo "📦 VERIFICACIÓN DE DATOS:\n";
    echo "   - Cliente: " . ($cotizacionArray['cliente']['nombre'] ?? 'N/A') . "\n";
    echo "   - Prendas: " . (isset($cotizacionArray['prendas']) ? count($cotizacionArray['prendas']) : 0) . "\n";
    echo "   - Reflectivo ID: " . ($cotizacionArray['reflectivo_cotizacion']['id'] ?? 'N/A') . "\n";
    
    // Verificar fotos del reflectivo
    if (isset($cotizacionArray['reflectivo_cotizacion']['fotos'])) {
        echo "   ✅ Fotos del reflectivo: " . count($cotizacionArray['reflectivo_cotizacion']['fotos']) . "\n";
        if (!empty($cotizacionArray['reflectivo_cotizacion']['fotos'])) {
            $primerFoto = $cotizacionArray['reflectivo_cotizacion']['fotos'][0];
            echo "      Primera foto:\n";
            echo "        - ID: {$primerFoto['id']}\n";
            echo "        - url: {$primerFoto['url']}\n";
            echo "        - ruta_webp: {$primerFoto['ruta_webp']}\n";
        }
    } else {
        echo "   ❌ Sin fotos en reflectivo_cotizacion\n";
    }

    // Simular JSON que se pasaría a la vista
    $datosJSON = json_encode($cotizacionArray);
    $datosDecodificado = json_decode($datosJSON, true);

    echo "\n📄 JSON FINAL A PASAR A VISTA:\n";
    echo "   - Tamaño: " . strlen($datosJSON) . " bytes\n";
    echo "   - Reflectivo fotos en JSON: " . 
        (isset($datosDecodificado['reflectivo_cotizacion']['fotos']) ? 
            count($datosDecodificado['reflectivo_cotizacion']['fotos']) : 0) . "\n";

    // Mostrar un sample del JSON que vería el JavaScript
    echo "\n🔍 MUESTRA DEL JAVASCRIPT ARRAY:\n";
    echo "   datosIniciales.reflectivo_cotizacion.fotos[0]:\n";
    if (isset($datosDecodificado['reflectivo_cotizacion']['fotos'][0])) {
        $foto = $datosDecodificado['reflectivo_cotizacion']['fotos'][0];
        echo "   " . json_encode($foto) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n";
