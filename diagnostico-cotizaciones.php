<?php
/**
 * Script de diagnóstico para verificar cotizaciones y sus tipos
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Obtener usuario autenticado (usamos el primer usuario para pruebas)
$user = User::first();
if (!$user) {
    echo "❌ No hay usuarios en la BD\n";
    exit;
}

echo "👤 Usuario: {$user->name} (ID: {$user->id})\n";
echo "================================================\n\n";

// Obtener todas las cotizaciones del usuario
$allCotizaciones = Cotizacion::where('user_id', $user->id)
    ->where('es_borrador', false)
    ->with('tipoCotizacion', 'prendasCotizaciones', 'prendaCotizacion', 'logoCotizacion')
    ->orderBy('created_at', 'desc')
    ->get();

echo "📊 Total de cotizaciones: {$allCotizaciones->count()}\n\n";

// Agrupar por tipo
$porTipo = [
    'P' => [],
    'B' => [],
    'PB' => [],
    'null' => []
];

foreach ($allCotizaciones as $cot) {
    $tipo = $cot->obtenerTipoCotizacion();
    $tipoKey = $tipo ?? 'null';
    
    if (!isset($porTipo[$tipoKey])) {
        $porTipo[$tipoKey] = [];
    }
    $porTipo[$tipoKey][] = $cot;
}

// Mostrar resumen
echo "📈 RESUMEN POR TIPO:\n";
echo "─────────────────────────────────────────\n";
foreach ($porTipo as $tipo => $cotizaciones) {
    if (count($cotizaciones) > 0) {
        echo "✅ Tipo '{$tipo}': " . count($cotizaciones) . " registros\n";
    }
}
echo "\n";

// Detalles de cada cotización
echo "📋 DETALLES COMPLETOS:\n";
echo "─────────────────────────────────────────\n";

foreach ($allCotizaciones as $index => $cot) {
    $tienePrendas = $cot->prendasCotizaciones()->exists() || $cot->prendaCotizacion()->exists();
    $tieneLogo = $cot->logoCotizacion()->exists();
    $tipo = $cot->obtenerTipoCotizacion();
    $tipoDb = $cot->tipoCotizacion ? $cot->tipoCotizacion->codigo : 'null';
    
    echo "\n" . ($index + 1) . ". ID: {$cot->id} | Código: {$cot->numero_cotizacion}\n";
    echo "   Cliente: {$cot->cliente}\n";
    echo "   Prendas: " . ($tienePrendas ? 'SÍ' : 'NO') . "\n";
    echo "   Logo: " . ($tieneLogo ? 'SÍ' : 'NO') . "\n";
    echo "   Tipo obtenido: '{$tipo}'\n";
    echo "   Tipo DB (tipo_cotizacion_id): '{$tipoDb}'\n";
}

echo "\n\n";
echo "================================================\n";
echo "✅ Diagnóstico completado\n";
