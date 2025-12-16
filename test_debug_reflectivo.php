<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Infrastructure\Http\Controllers\CotizacionController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

echo "\n=== SIMULACIÓN: ENDPOINT getReflectivoForEdit ===\n\n";

// Buscar una cotización con reflectivo
$cotizacion = \App\Models\Cotizacion::whereHas('reflectivoCotizacion')
    ->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones con reflectivo\n";
    exit;
}

echo "✅ Cotización encontrada: #{$cotizacion->id}\n";
echo "   Asesor: {$cotizacion->asesor_id}\n";
echo "   Es borrador: " . ($cotizacion->es_borrador ? 'SÍ' : 'NO') . "\n\n";

// Hacer "login" como el asesor de la cotización
$user = \App\Models\User::find($cotizacion->asesor_id);
if (!$user) {
    echo "❌ Usuario (asesor) no encontrado\n";
    exit;
}

Auth::login($user);
echo "✅ Autenticado como usuario: {$user->id} ({$user->name})\n\n";

// Crear controller y llamar al método
$controller = app(CotizacionController::class);

// Llamar el método
echo "📞 Llamando getReflectivoForEdit({$cotizacion->id})...\n\n";
$response = $controller->getReflectivoForEdit($cotizacion->id);
$data = json_decode($response->getContent(), true);

echo "📦 RESPUESTA:\n";
echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";

echo "✅ VERIFICACIÓN:\n";
if (isset($data['data']['fotos']) && !empty($data['data']['fotos'])) {
    echo "   ✅ Fotos encontradas: " . count($data['data']['fotos']) . "\n";
    foreach ($data['data']['fotos'] as $idx => $foto) {
        echo "      Foto {$idx}: {$foto['url']}\n";
    }
} else {
    echo "   ❌ SIN FOTOS EN LA RESPUESTA\n";
}

echo "\n";

// Mostrar los últimos logs
echo "📋 ÚLTIMOS LOGS (últimas 20 líneas):\n";
echo "─────────────────────────────────────\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo rtrim($line) . "\n";
    }
} else {
    echo "❌ Archivo de log no encontrado\n";
}

echo "\n";
