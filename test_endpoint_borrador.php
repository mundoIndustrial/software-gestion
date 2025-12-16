<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Infrastructure\Http\Controllers\CotizacionController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

echo "\n=== PRUEBA: getReflectivoForEdit con cotización BORRADOR ===\n\n";

$cotizacion = \App\Models\Cotizacion::find(63); // Cotización borrador con fotos

if (!$cotizacion) {
    echo "❌ Cotización no encontrada\n";
    exit;
}

echo "✅ Cotización: #{$cotizacion->id}\n";
echo "   Asesor: {$cotizacion->asesor_id}\n";
echo "   Es borrador: " . ($cotizacion->es_borrador ? 'SÍ' : 'NO') . "\n\n";

// Login como el asesor
$user = \App\Models\User::find($cotizacion->asesor_id);
Auth::login($user);
echo "✅ Autenticado como: {$user->name}\n\n";

// Llamar el endpoint
$controller = app(CotizacionController::class);
$response = $controller->getReflectivoForEdit($cotizacion->id);
$data = json_decode($response->getContent(), true);

echo "📦 RESPUESTA:\n";
echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";

// Verificar fotos
echo "✅ VERIFICACIÓN:\n";
if (isset($data['data']['fotos'])) {
    echo "   Fotos encontradas: " . count($data['data']['fotos']) . "\n";
    if (!empty($data['data']['fotos'])) {
        foreach ($data['data']['fotos'] as $idx => $foto) {
            echo "      [{$idx}] url: {$foto['url']}\n";
        }
    }
} else {
    echo "   ❌ Sin key 'fotos' en data\n";
}

echo "\n";
