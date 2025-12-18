#!/usr/bin/env php
<?php

/**
 * ✅ SCRIPT READ-ONLY - Solo lecturas de BD, sin modificaciones
 * 
 * Propósito: Verificar relación entre cotizaciones y pedidos
 * Garantía: NO modifica ningún dato
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔍 VERIFICACIÓN: Cotizaciones vs Pedidos - SOLO LECTURA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // 1. Estado de secuencias (SOLO LECTURA)
    echo "1️⃣  SECUENCIAS NUMERACIÓN\n";
    echo "─────────────────────────────────────────────\n";
    
    $seqCotizaciones = DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_universal')
        ->first();
    
    $seqPedidos = DB::table('numero_secuencias')
        ->where('tipo', 'pedido_produccion')
        ->first();
    
    echo "   Cotizaciones Universal: " . ($seqCotizaciones->siguiente ?? 'N/A') . "\n";
    echo "   Pedidos Producción:     " . ($seqPedidos->siguiente ?? 'N/A') . "\n\n";

    // 2. Contar total de cotizaciones (SOLO LECTURA)
    echo "2️⃣  TOTALES\n";
    echo "─────────────────────────────────────────────\n";
    
    $totalCotizaciones = DB::table('cotizaciones')->count();
    $totalPedidos = DB::table('pedidos_produccion')->count();
    $cotizacionesEnviadas = DB::table('cotizaciones')->where('es_borrador', false)->count();
    $cotizacionesBorradores = DB::table('cotizaciones')->where('es_borrador', true)->count();
    
    echo "   Total Cotizaciones:     $totalCotizaciones\n";
    echo "   ├─ Borradores:          $cotizacionesBorradores\n";
    echo "   └─ Enviadas:            $cotizacionesEnviadas\n";
    echo "   Total Pedidos:          $totalPedidos\n\n";

    // 3. Últimas 5 cotizaciones (SOLO LECTURA)
    echo "3️⃣  ÚLTIMAS 5 COTIZACIONES ENVIADAS\n";
    echo "─────────────────────────────────────────────\n";
    
    $ultimas = DB::table('cotizaciones')
        ->join('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
        ->select(
            'cotizaciones.id',
            'cotizaciones.numero_cotizacion',
            'cotizaciones.es_borrador',
            'tipos_cotizacion.codigo',
            'tipos_cotizacion.nombre'
        )
        ->where('es_borrador', false)
        ->orderBy('cotizaciones.id', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($ultimas as $cot) {
        echo sprintf(
            "   • %s (ID:%d, Tipo:%s)\n",
            $cot->numero_cotizacion ?? 'SIN NÚMERO',
            $cot->id,
            $cot->codigo
        );
    }
    echo "\n";

    // 4. Últimos 5 pedidos (SOLO LECTURA)
    echo "4️⃣  ÚLTIMOS 5 PEDIDOS\n";
    echo "─────────────────────────────────────────────\n";
    
    $ultimosPedidos = DB::table('pedidos_produccion')
        ->select('id', 'numero_pedido', 'cotizacion_id')
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($ultimosPedidos as $ped) {
        echo sprintf(
            "   • Pedido:%s (ID:%d, Cot:%s)\n",
            $ped->numero_pedido,
            $ped->id,
            $ped->cotizacion_id ?? 'N/A'
        );
    }
    echo "\n";

    // 5. Relación Cotización-Pedido (SOLO LECTURA)
    echo "5️⃣  RELACIÓN COTIZACIÓN→PEDIDO (Últimas 5)\n";
    echo "─────────────────────────────────────────────\n";
    
    $relaciones = DB::table('pedidos_produccion')
        ->leftJoin('cotizaciones', 'pedidos_produccion.cotizacion_id', '=', 'cotizaciones.id')
        ->select(
            'pedidos_produccion.numero_pedido',
            'cotizaciones.numero_cotizacion',
            DB::raw('COALESCE(tc.codigo, "N/A") as tipo')
        )
        ->leftJoin('tipos_cotizacion as tc', 'cotizaciones.tipo_cotizacion_id', '=', 'tc.id')
        ->orderBy('pedidos_produccion.id', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($relaciones as $rel) {
        echo sprintf(
            "   • Pedido:%s → Cotización:%s (Tipo:%s)\n",
            $rel->numero_pedido,
            $rel->numero_cotizacion ?? 'SIN NÚMERO',
            $rel->tipo
        );
    }
    echo "\n";

    // 6. Diagnóstico (SOLO LECTURA)
    echo "6️⃣  DIAGNÓSTICO\n";
    echo "─────────────────────────────────────────────\n";
    
    $cotizacionesConPedido = DB::table('cotizaciones')
        ->whereIn('id', DB::table('pedidos_produccion')->pluck('cotizacion_id'))
        ->count();
    
    $cotizacionesSinPedido = $cotizacionesEnviadas - $cotizacionesConPedido;
    
    echo "   Cotizaciones enviadas con Pedido:   $cotizacionesConPedido\n";
    echo "   Cotizaciones enviadas sin Pedido:   $cotizacionesSinPedido\n";
    
    if ($cotizacionesConPedido === $totalPedidos) {
        echo "   ✅ Relación 1:1 correcta\n";
    } else {
        echo "   ⚠️  Posible inconsistencia\n";
    }
    
    echo "\n";

    // 7. Conclusión (SOLO LECTURA)
    echo "7️⃣  CONCLUSIÓN\n";
    echo "─────────────────────────────────────────────\n";
    echo "   ✅ SCRIPT COMPLETADO SIN MODIFICACIONES\n";
    echo "   ✅ TODAS LAS OPERACIONES FUERON SOLO-LECTURA\n";
    echo "   ✅ BASE DE DATOS NO FUE ALTERADA\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n\n";
