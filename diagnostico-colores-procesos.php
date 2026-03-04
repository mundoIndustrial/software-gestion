<?php
/**
 * Script diagnóstico para verificar tallas y colores de procesos
 * Ejecuta directamente desde terminal: php diagnostico-colores-procesos.php
 */

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO: TALLAS Y COLORES DE PROCESOS\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Pedido a diagnosticar
$pedido_id = 36;
echo "📋 Buscando datos para PEDIDO ID: {$pedido_id}\n";
echo "─────────────────────────────────────────────────────────────\n\n";

// 1. Obtener procesos del pedido
$procesos = DB::table('pedidos_procesos_prenda_detalles as ppd')
    ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
    ->where('pp.pedido_produccion_id', $pedido_id)
    ->select('ppd.id', 'ppd.tipo_recibo', 'pp.nombre_prenda')
    ->get();

echo "🔍 PROCESOS ENCONTRADOS: {$procesos->count()}\n";
if ($procesos->count() === 0) {
    echo "   ❌ No hay procesos para este pedido\n";
    exit;
}

foreach ($procesos as $proceso) {
    echo "\n📊 PROCESO:\n";
    echo "   ID: {$proceso->id}\n";
    echo "   Tipo: {$proceso->tipo_recibo}\n";
    echo "   Prenda: {$proceso->nombre_prenda}\n";
    echo "   ─────────────────────────────────────────────────────────\n";
    
    // 2. Obtener tallas del proceso
    $tallas = DB::table('pedidos_procesos_prenda_tallas')
        ->where('proceso_prenda_detalle_id', $proceso->id)
        ->get();
    
    echo "   📏 TALLAS: {$tallas->count()}\n";
    
    foreach ($tallas as $talla) {
        echo "\n      🔹 Talla ID: {$talla->id}\n";
        echo "         Genero: {$talla->genero}\n";
        echo "         Talla: {$talla->talla}\n";
        echo "         Cantidad: {$talla->cantidad}\n";
        
        // 3. Obtener colores para esta talla
        $colores = DB::table('pedidos_procesos_prenda_talla_colores')
            ->where('pedidos_procesos_prenda_talla_id', $talla->id)
            ->get();
        
        echo "         🎨 COLORES: {$colores->count()}\n";
        
        if ($colores->count() > 0) {
            foreach ($colores as $color) {
                echo "            ✓ Color: {$color->color_nombre}\n";
                echo "              Tela: {$color->tela_nombre}\n";
                echo "              Cantidad: {$color->cantidad}\n";
            }
        } else {
            echo "            ⚠️  SIN COLORES REGISTRADOS\n";
        }
    }
}

echo "\n\n════════════════════════════════════════════════════════════\n";
echo "RESUMEN FINAL:\n";
echo "════════════════════════════════════════════════════════════\n";

// Resumen global
$totalTallas = DB::table('pedidos_procesos_prenda_tallas as pppt')
    ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
    ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
    ->where('pp.pedido_produccion_id', $pedido_id)
    ->count();

$totalColores = DB::table('pedidos_procesos_prenda_talla_colores as ppptc')
    ->join('pedidos_procesos_prenda_tallas as pppt', 'ppptc.pedidos_procesos_prenda_talla_id', '=', 'pppt.id')
    ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
    ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
    ->where('pp.pedido_produccion_id', $pedido_id)
    ->count();

echo "\n✅ Total de TALLAS en procesos: {$totalTallas}\n";
echo "✅ Total de COLORES en procesos: {$totalColores}\n";

if ($totalColores === 0) {
    echo "\n⚠️  ADVERTENCIA: No hay colores registrados en pedidos_procesos_prenda_talla_colores\n";
    echo "   Los colores se grabaron en la tabla de tallas o no se grabaron en absoluto.\n";
} else {
    echo "\n✓ Los colores se encuentran correctamente en la BD\n";
}

echo "\n════════════════════════════════════════════════════════════\n\n";
