<?php
/**
 * SCRIPT DE PRUEBA: Verificar flujo de pedidos combinados (PL)
 * 
 * Este script simula lo que hace el frontend:
 * 1. Primer request: POST /crear-desde-cotizacion (crea en pedidos_produccion)
 * 2. Segundo request: POST /guardar-logo-pedido (crea en logo_pedidos)
 */

// Cargar Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Usar base de datos
use Illuminate\Support\Facades\DB;

echo "=== PRUEBA: PEDIDOS COMBINADOS (PL) ===\n\n";

// 1. Obtener una cotización tipo PL
$cotizacion = DB::table('cotizaciones')
    ->where('tipo_cotizacion_codigo', 'PL')
    ->orderByDesc('id')
    ->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones tipo PL en la BD\n";
    exit(1);
}

echo "✅ Cotización encontrada: " . $cotizacion->numero . " (ID: " . $cotizacion->id . ")\n";
echo "   Tipo: " . $cotizacion->tipo_cotizacion_codigo . "\n\n";

// 2. Verificar logo_cotizacion asociada
$logoCot = DB::table('logo_cotizaciones')
    ->where('cotizacion_id', $cotizacion->id)
    ->first();

if (!$logoCot) {
    echo "❌ No hay logo_cotizaciones asociadas\n";
    exit(1);
}

echo "✅ Logo Cotización encontrada (ID: " . $logoCot->id . ")\n\n";

// 3. Verificar si existe pedido de producción para esta cotización
$pedidoProd = DB::table('pedidos_produccion')
    ->where('cotizacion_id', $cotizacion->id)
    ->first();

if ($pedidoProd) {
    echo "✅ Pedido de PRODUCCIÓN ya existe:\n";
    echo "   ID: " . $pedidoProd->id . "\n";
    echo "   Número: " . $pedidoProd->numero_pedido . "\n";
} else {
    echo "❌ No existe pedido de producción para esta cotización\n";
}

// 4. Verificar si existe pedido de LOGO para esta cotización
$pedidoLogo = DB::table('logo_pedidos')
    ->where('cotizacion_id', $cotizacion->id)
    ->first();

if ($pedidoLogo) {
    echo "\n✅ Pedido de LOGO ya existe:\n";
    echo "   ID: " . $pedidoLogo->id . "\n";
    echo "   Número: " . $pedidoLogo->numero_pedido . "\n";
    echo "   pedido_id: " . $pedidoLogo->pedido_id . " (vinculación)\n";
    echo "   cantidad: " . $pedidoLogo->cantidad . "\n";
} else {
    echo "\n❌ No existe pedido de LOGO para esta cotización\n";
}

// 5. Resumen
echo "\n=== RESUMEN ===\n";
if ($pedidoProd && $pedidoLogo) {
    echo "✅ ÉXITO: Ambos pedidos existen\n";
    echo "   Pedido PRENDAS: " . $pedidoProd->numero_pedido . "\n";
    echo "   Pedido LOGO: " . $pedidoLogo->numero_pedido . "\n";
    echo "   Vinculación (logo_pedidos.pedido_id): " . $pedidoLogo->pedido_id . " → " . $pedidoProd->id . "\n";
    if ($pedidoLogo->pedido_id === $pedidoProd->id) {
        echo "   ✅ Vinculación correcta\n";
    } else {
        echo "   ❌ Vinculación incorrecta\n";
    }
} else if ($pedidoProd && !$pedidoLogo) {
    echo "❌ PROBLEMA: Solo existe pedido de PRENDAS, falta pedido de LOGO\n";
} else if (!$pedidoProd && $pedidoLogo) {
    echo "❌ PROBLEMA: Solo existe pedido de LOGO, falta pedido de PRENDAS\n";
} else {
    echo "❌ PROBLEMA: No existen pedidos para esta cotización\n";
}

echo "\nFin del test.\n";
